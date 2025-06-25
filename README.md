# ClickEarn Telegram Bot

## Setup

1. Import `database.sql` to create database and tables.
2. Update `config.php` with your Telegram bot token, admin ID, and database credentials.
3. Upload all files to your PHP server with HTTPS.
4. Set Telegram webhook:

```
curl -F "url=https://yourdomain.com/bot.php" https://api.telegram.org/8065025177:AAFm5GCJ6Jdk15wFo1itKaj1N_In-CFqnyo/setWebhook
```

5. Start chatting with your bot on Telegram.

## Commands

- /start - Show welcome message
- /buy <number> - Buy clicks (0.90$ per click)
- /trx <transaction_id> - Submit payment transaction ID
- /verify <user_id> - Admin command to verify payment and add clicks
- /click <number> - Use clicks to earn
- /balance - Show click and earning balance
