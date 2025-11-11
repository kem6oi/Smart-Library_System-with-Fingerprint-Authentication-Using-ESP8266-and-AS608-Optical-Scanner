# Security Configuration Guide

This document explains how to securely configure the Smart Library System with proper credential management.

## Overview

This system has been updated to remove all hardcoded credentials and API keys. Sensitive information is now stored in configuration files that are **not tracked by version control**.

## Critical Security Files (NEVER COMMIT THESE)

The following files contain sensitive credentials and are excluded from git via `.gitignore`:

- `.env` - PHP environment variables (database credentials, API keys)
- `esp8266/config.h` - ESP8266 WiFi and server configuration

**⚠️ WARNING:** Never commit these files to version control or share them publicly.

## Initial Setup

### 1. PHP Environment Configuration

#### Step 1: Create your `.env` file

```bash
cp .env.example .env
```

#### Step 2: Edit `.env` with your actual credentials

Open `.env` in a text editor and update the following values:

```env
# Database Configuration
DB_HOST=localhost
DB_USER=your_database_username
DB_PASS=your_database_password
DB_NAME=library

# Telegram Bot Configuration
TELEGRAM_BOT_TOKEN=your_actual_telegram_bot_token_here

# Server Configuration
SERVER_URL=http://your_server_ip/library0-2w/admin/fingerprint_auth.php
```

**How to get a Telegram Bot Token:**
1. Open Telegram and search for `@BotFather`
2. Send `/newbot` and follow the instructions
3. Copy the token provided by BotFather
4. Paste it in the `.env` file as `TELEGRAM_BOT_TOKEN`

### 2. ESP8266 Configuration

#### Step 1: Create your `config.h` file

```bash
cd esp8266
cp config.h.example config.h
```

#### Step 2: Edit `config.h` with your WiFi credentials

Open `esp8266/config.h` in your Arduino IDE or text editor and update:

```cpp
// WiFi credentials
const char* ssid = "Your_WiFi_Network_Name";
const char* password = "Your_WiFi_Password";

// Server details
const char* serverUrl = "http://YOUR_SERVER_IP/library0-2w/admin/fingerprint_auth.php";
```

**Note:** Replace `YOUR_SERVER_IP` with the actual IP address of your PHP server.

## File Structure

```
Smart-Library-System/
├── .env                          # Your credentials (NOT in git)
├── .env.example                  # Template (safe to commit)
├── .gitignore                    # Excludes sensitive files
├── includes/
│   ├── config.php                # Loads .env and sets up DB
│   └── env.php                   # Environment variable loader
├── admin/includes/
│   ├── config.php                # Admin DB and Telegram config
│   └── env.php                   # Environment variable loader
└── esp8266/
    ├── config.h                  # Your WiFi credentials (NOT in git)
    ├── config.h.example          # Template (safe to commit)
    └── fingerprint.ino           # ESP8266 firmware
```

## Security Best Practices

### ✅ DO:
- Keep `.env` and `esp8266/config.h` files secure and private
- Use strong, unique passwords for database and WiFi
- Regenerate API tokens if they are ever exposed
- Use HTTPS instead of HTTP for production deployments
- Regularly update your credentials
- Back up your `.env` and `config.h` files securely (encrypted storage)

### ❌ DON'T:
- Never commit `.env` or `config.h` to version control
- Never share your `.env` or `config.h` files publicly
- Never hardcode credentials directly in source files
- Never use default or weak passwords
- Never expose your Telegram bot token

## What Changed?

### Before (INSECURE):
```php
// ❌ Hardcoded credentials in source code
define('DB_PASS', 'mypassword');
$telegramBotToken = '123456:ABC-DEF...';
```

### After (SECURE):
```php
// ✅ Credentials loaded from environment variables
define('DB_PASS', env('DB_PASS', ''));
$telegramBotToken = env('TELEGRAM_BOT_TOKEN');
```

## Deployment Checklist

Before deploying this system:

- [ ] Created `.env` file from `.env.example`
- [ ] Updated all values in `.env` with actual credentials
- [ ] Created `esp8266/config.h` from `config.h.example`
- [ ] Updated WiFi credentials in `esp8266/config.h`
- [ ] Verified `.env` and `config.h` are in `.gitignore`
- [ ] Changed default database password
- [ ] Generated new Telegram bot token
- [ ] Updated server URL in both `.env` and `config.h`
- [ ] Tested database connection
- [ ] Tested Telegram notifications
- [ ] Tested ESP8266 WiFi connection

## Troubleshooting

### "Environment Configuration Error: .env file not found"

**Solution:** You haven't created the `.env` file. Run:
```bash
cp .env.example .env
```
Then edit `.env` with your credentials.

### "Database connection failed"

**Solution:** Check your database credentials in `.env`:
- Verify `DB_HOST`, `DB_USER`, `DB_PASS`, and `DB_NAME` are correct
- Ensure your database server is running
- Confirm the database user has proper permissions

### "Telegram notifications not working"

**Solution:**
- Verify `TELEGRAM_BOT_TOKEN` in `.env` is correct
- Test your bot token using the Telegram API
- Ensure the bot has been started by the user

### "ESP8266 won't connect to WiFi"

**Solution:**
- Verify WiFi credentials in `esp8266/config.h`
- Check that your WiFi network is 2.4GHz (ESP8266 doesn't support 5GHz)
- Ensure the WiFi password is correct

## Support

If you discover a security vulnerability, please email the maintainers immediately. Do not create a public GitHub issue for security vulnerabilities.

## Additional Security Recommendations

1. **Use HTTPS:** For production, configure SSL/TLS certificates
2. **Database Security:** Use a dedicated database user with minimal privileges
3. **Network Security:** Place the ESP8266 on a separate VLAN if possible
4. **Regular Updates:** Keep all dependencies and libraries up to date
5. **Access Control:** Implement proper authentication and authorization
6. **Logging:** Monitor access logs for suspicious activity
7. **Backup:** Regularly backup your database and configuration files (encrypted)

---

**Last Updated:** 2025-11-11
