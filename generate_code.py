import random
import requests
import sys

def send_telegram_message(chat_id, message, bot_token):
    url = f"https://api.telegram.org/bot{bot_token}/sendMessage?chat_id={chat_id}&text={message}"
    try:
        response = requests.get(url)
        response.raise_for_status() 
        return True
    except requests.exceptions.RequestException as e:
        print(f"Error sending Telegram message: {e}", file=sys.stderr)
        return False

def generate_verification_code():
    
    return str(random.randint(100000, 999999))

if __name__ == "__main__":
    if len(sys.argv) != 3:
       print("Usage: python generate_code.py <telegram_chat_id> <bot_token>", file=sys.stderr)
       sys.exit(1)

    chat_id = sys.argv[1]
    bot_token = sys.argv[2]
    verification_code = generate_verification_code()
    message = f"Your password recovery code is: {verification_code}"

    if send_telegram_message(chat_id, message, bot_token):
        print(verification_code)  
        sys.exit(0)
    else:
        sys.exit(1)  