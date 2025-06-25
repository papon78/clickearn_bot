<?php
require_once 'config.php';

// Connect to database
function dbConnect() {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli->connect_error) {
        error_log("DB Connection failed: " . $mysqli->connect_error);
        exit;
    }
    return $mysqli;
}

// Send message to Telegram user
function sendMessage($chat_id, $text) {
    $url = API_URL . "sendMessage?chat_id=$chat_id&text=" . urlencode($text);
    file_get_contents($url);
}

// Get user by telegram_id or create new user
function getUser($mysqli, $telegram_id, $username) {
    $stmt = $mysqli->prepare("SELECT * FROM users WHERE telegram_id=?");
    $stmt->bind_param("i", $telegram_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
        $stmt2 = $mysqli->prepare("INSERT INTO users (telegram_id, username) VALUES (?, ?)");
        $stmt2->bind_param("is", $telegram_id, $username);
        $stmt2->execute();
        $user_id = $stmt2->insert_id;
        $stmt2->close();

        $user = [
            'id' => $user_id,
            'telegram_id' => $telegram_id,
            'username' => $username,
            'clicks' => 0,
            'earnings' => 0,
            'last_click' => null,
            'is_admin' => 0,
            'created_at' => null
        ];
    }
    return $user;
}
?>
