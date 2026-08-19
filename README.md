# Elitewave Backend

PHP backend for the Elitewave 360 logistics application (booking, tracking, payments, and admin).

## Setup

1. Clone this repository into the web root (or `public_html/php`).
2. Copy the example config files and fill in local credentials:
   - `config.ini.php.example` → `config.ini.php`
   - `includes/connect.php.example` → `includes/connect.php`
   - `user/include/connect.php.example` → `user/include/connect.php`
   - `web/include/connect.php.example` → `web/include/connect.php`
   - `user/razorpay/config.php.example` → `user/razorpay/config.php`
   - `secure_payment/config.php.example` → `secure_payment/config.php`
   - `Twillio/constant.php.example` → `Twillio/constant.php`
3. Install Composer dependencies in folders that have a `composer.json` (for example `Twillio/`, `web/`, `plivo_sms/`).

Keep this repository **private**. Some older PHP files still contain hardcoded credentials and should be cleaned up over time.
