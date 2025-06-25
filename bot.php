<?php
require_once 'config.php';
require_once 'helpers.php';

// Connect to DB
$mysqli = dbConnect();

// Get Telegram update
$content = file_get_contents("php://input");
$update = json_decode($content, true);
if (!$update) exit;

$message = $update['message'] ?? null;
if (!$message) exit;

$chat_id = $message['chat']['id'];
$telegram_user = $message['from'];
$user_id = $telegram_user['id'];
$username = $telegram_user['username'] ?? '';

// Load or create user
$user = getUser($mysqli, $user_id, $username);

$text = trim($message['text'] ?? '');

// --- Commands ---

// /start
if (strpos($text, "/start") === 0) {
    sendMessage($chat_id, "Welcome to ClickEarnBot!\nCommands:\n/buy <clicks> - Buy clicks\n/trx <transaction_id> - Send payment transaction ID\n/click <number> - Use clicks to earn\n/balance - Show your balance");
    exit;
}

// /buy <number>
if (preg_match('/^\/buy\s+(\d+)$/', $text, $matches)) {
    $buy_clicks = (int)$matches[1];
    if ($buy_clicks < 1) {
        sendMessage($chat_id, "Please enter a valid number of clicks to buy.");
        exit;
    }
    $amount = $buy_clicks * 0.90;

    // Store buy request in DB or temp (for simplicity store in table orders with pending status but no trx yet)
    $stmt = $mysqli->prepare("INSERT INTO orders (user_id, amount, payment_method, payment_number, trx_id, status) VALUES (?, ?, '', '', '', 'pending')");
    $stmt->bind_param("id", $user['id'], $amount);
    $stmt->execute();
    $stmt->close();

    sendMessage($chat_id, "Please send payment of \$$amount via BKash or Nagad.\n\nBKash Number: 01917243974\nNagad Number: 01747401484\n\nAfter payment, send your transaction ID like this:\n/trx YOUR_TRANSACTION_ID");
    exit;
}

// /trx <transaction_id>
if (preg_match('/^\/trx\s+(.+)$/', $text, $matches)) {
    $trx_id = trim($matches[1]);

    // Find latest pending order without trx for this user
    $stmt = $mysqli->prepare("SELECT id FROM orders WHERE user_id=? AND status='pending' AND trx_id='' ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $res = $stmt->get_result();
    $order = $res->fetch_assoc();
    $stmt->close();

    if (!$order) {
        sendMessage($chat_id, "No pending order found. Please use /buy command first.");
        exit;
    }

    // Update order with trx_id
    $stmt2 = $mysqli->prepare("UPDATE orders SET trx_id=?, payment_method='bkash', payment_number='01917243974' WHERE id=?");
    $stmt2->bind_param("si", $trx_id, $order['id']);
    $stmt2->execute();
    $stmt2->close();

    sendMessage($chat_id, "Payment recorded with transaction ID: $trx_id. Wait for admin verification.");
    exit;
}

// /verify <user_id> (admin only)
if ($user_id == ADMIN_ID && preg_match('/^\/verify\s+(\d+)$/', $text, $matches)) {
    $verify_user_id = (int)$matches[1];

    // Get latest pending order for that user
    $res = $mysqli->query("SELECT * FROM orders WHERE user_id=$verify_user_id AND status='pending' ORDER BY created_at DESC LIMIT 1");
    if ($res->num_rows === 0) {
        sendMessage($chat_id, "No pending orders found for user ID $verify_user_id.");
        exit;
    }
    $order = $res->fetch_assoc();

    // Verify order
    $mysqli->query("UPDATE orders SET status='verified', verified_at=NOW() WHERE id=".$order['id']);

    // Add clicks and earnings to user
    $add_clicks = intval($order['amount'] / 0.90);
    $mysqli->query("UPDATE users SET clicks = clicks + $add_clicks, earnings = earnings + {$order['amount']} WHERE id=$verify_user_id");

    sendMessage($chat_id, "Order #{$order['id']} verified. Added $add_clicks clicks to user ID $verify_user_id.");
    exit;
}

// /click <number>
if (preg_match('/^\/click\s+(\d+)$/', $text, $matches)) {
    $clicks_requested = (int)$matches[1];
    if ($clicks_requested < 1 || $clicks_requested > 1000) {
        sendMessage($chat_id, "Please enter clicks between 1 and 1000 per request.");
        exit;
    }
    if ($user['clicks'] < $clicks_requested) {
        sendMessage($chat_id, "You don't have enough clicks. Buy more using /buy command.");
        exit;
    }

    $now = time();
    $last_click = strtotime($user['last_click']);
    $wait_seconds = 20 * $clicks_requested;

    if ($user['last_click'] && ($now - $last_click) < $wait_seconds) {
        $seconds_left = $wait_seconds - ($now - $last_click);
        sendMessage($chat_id, "Please wait $seconds_left seconds before next clicks.");
        exit;
    }

    // Deduct clicks and update last_click
    $new_clicks = $user['clicks'] - $clicks_requested;
    $now_sql = date('Y-m-d H:i:s');

    $stmt = $mysqli->prepare("UPDATE users SET clicks=?, last_click=? WHERE id=?");
    $stmt->bind_param("isi", $new_clicks, $now_sql, $user['id']);
    $stmt->execute();
    $stmt->close();

    $earned = $clicks_requested * 0.90;

    sendMessage($chat_id, "You used $clicks_requested clicks and earned \$$earned.\nRemaining clicks: $new_clicks");
    exit;
}

// /balance
if ($text === "/balance") {
    sendMessage($chat_id, "Your total clicks: {$user['clicks']}\nYour total earnings: \${$user['earnings']}");
    exit;
}

sendMessage($chat_id, "Unknown command. Use /start, /buy, /trx, /click, or /balance.");

?>
