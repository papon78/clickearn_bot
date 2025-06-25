
import json
import time
from telegram import Update, ReplyKeyboardMarkup
from telegram.ext import ApplicationBuilder, CommandHandler, MessageHandler, filters, ContextTypes

TOKEN = '8065025177:AAFm5GCJ6Jdk15wFo1itKaj1N_In-CFqnyo'
CLICK_REWARD = 0.90
WAIT_TIME = 20
MAX_CLICKS = 100000
DATA_FILE = 'users.json'
ADMINS = ["SA_KI_B8_4x", "Badolroy638"]

try:
    with open(DATA_FILE, 'r') as f:
        users = json.load(f)
except FileNotFoundError:
    users = {}

def save_data():
    with open(DATA_FILE, 'w') as f:
        json.dump(users, f)

keyboard = ReplyKeyboardMarkup(
    [['💸 Click to Earn'], ['👤 Profile', '📜 History'], ['💳 Payment']],
    resize_keyboard=True
)

async def start(update: Update, context: ContextTypes.DEFAULT_TYPE):
    uid = str(update.effective_user.id)
    if uid not in users:
        users[uid] = {
            "clicks": 0,
            "balance": 0,
            "last_click": 0,
            "history": []
        }
        save_data()
    await update.message.reply_text("👋 Welcome to AutoClickBot!\nEarn $0.90 per click every 20s.", reply_markup=keyboard)

async def click(update: Update, context: ContextTypes.DEFAULT_TYPE):
    uid = str(update.effective_user.id)
    username = update.effective_user.username
    now = time.time()

    if uid not in users:
        users[uid] = {
            "clicks": 0,
            "balance": 0,
            "last_click": 0,
            "history": []
        }

    is_admin = username in ADMINS

    if not is_admin:
        if users[uid]["clicks"] >= MAX_CLICKS:
            await update.message.reply_text("🚫 You've reached the maximum 100,000 clicks.")
            return
        if now - users[uid]["last_click"] < WAIT_TIME:
            wait_left = int(WAIT_TIME - (now - users[uid]["last_click"]))
            await update.message.reply_text(f"⏳ Please wait {wait_left}s before next click.")
            return

    users[uid]["clicks"] += 1
    users[uid]["balance"] += CLICK_REWARD
    users[uid]["last_click"] = now
    save_data()

    await update.message.reply_text(
        f"✅ Click counted!\nTotal Clicks: {users[uid]['clicks']}\nBalance: ${users[uid]['balance']:.2f}"
    )

async def profile(update: Update, context: ContextTypes.DEFAULT_TYPE):
    uid = str(update.effective_user.id)
    data = users.get(uid, {})
    await update.message.reply_text(
        f"👤 Profile:\nTotal Clicks: {data.get('clicks', 0)}\nBalance: ${data.get('balance', 0):.2f}"
    )

async def history(update: Update, context: ContextTypes.DEFAULT_TYPE):
    uid = str(update.effective_user.id)
    hist = users[uid].get("history", [])
    if not hist:
        await update.message.reply_text("📜 No transactions yet.")
        return
    msg = "📜 Transaction History:\n" + "\n".join(hist[-5:])
    await update.message.reply_text(msg)

async def payment(update: Update, context: ContextTypes.DEFAULT_TYPE):
    await update.message.reply_text(
        "💳 Payment Instructions:\n"
        "Send payment to:\n\n"
        "📲 Bkash: 01917243974\n"
        "📲 Nagad: 01747401484\n\n"
        "Then reply with:\n\n"
        "`TRXID: 1234567890`\n"
        "_Your transaction will be verified manually._",
        parse_mode='Markdown'
    )

async def handle_trx(update: Update, context: ContextTypes.DEFAULT_TYPE):
    uid = str(update.effective_user.id)
    msg = update.message.text.strip()
    if msg.upper().startswith("TRXID:"):
        trx = msg[6:].strip()
        users[uid]["history"].append(f"💳 Payment submitted: {trx}")
        save_data()
        await update.message.reply_text("✅ Transaction ID received. Please wait for manual verification.")
    else:
        await update.message.reply_text("❌ Invalid format. Please send as:\nTRXID: 123456")

async def admin_users(update: Update, context: ContextTypes.DEFAULT_TYPE):
    username = update.effective_user.username
    if username not in ADMINS:
        await update.message.reply_text("❌ You are not authorized.")
        return
    msg = "📊 User Summary:\n"
    for uid, data in users.items():
        msg += f"👤 {uid}: {data['clicks']} clicks | ${data['balance']:.2f}\n"
    await update.message.reply_text(msg[:4096])

if __name__ == '__main__':
    app = ApplicationBuilder().token(TOKEN).build()

    app.add_handler(CommandHandler("start", start))
    app.add_handler(MessageHandler(filters.Regex("💸 Click to Earn"), click))
    app.add_handler(MessageHandler(filters.Regex("👤 Profile"), profile))
    app.add_handler(MessageHandler(filters.Regex("📜 History"), history))
    app.add_handler(MessageHandler(filters.Regex("💳 Payment"), payment))
    app.add_handler(MessageHandler(filters.TEXT & (~filters.COMMAND), handle_trx))
    app.add_handler(CommandHandler("users", admin_users))

    print("🤖 Bot is running...")
    app.run_polling()
