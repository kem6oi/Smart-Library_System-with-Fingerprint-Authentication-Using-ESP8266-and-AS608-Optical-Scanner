<?php
/**
 * Security Helper Functions
 * Provides centralized error handling, input sanitization, and session security
 */

/**
 * Sanitize output for HTML display
 * Prevents XSS attacks by escaping HTML special characters
 *
 * @param string $string String to sanitize
 * @return string Sanitized string
 */
function sanitize_output($string) {
    return htmlspecialchars($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * Sanitize input for safe processing
 * Removes whitespace and strips tags
 *
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $data;
}

/**
 * Secure error handler - logs errors instead of displaying them
 *
 * @param string $message Error message
 * @param string $logFile Optional log file path
 * @return void
 */
function log_error($message, $logFile = null) {
    if ($logFile === null) {
        $logFile = __DIR__ . '/../logs/error.log';
    }

    // Create logs directory if it doesn't exist
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}\n";
    error_log($logMessage, 3, $logFile);
}

/**
 * Display user-friendly error message and log actual error
 *
 * @param Exception $e Exception object
 * @param string $userMessage User-friendly message
 * @return void
 */
function handle_exception($e, $userMessage = "An error occurred. Please try again later.") {
    // Log the actual error
    log_error("Exception: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());

    // Display user-friendly message
    $_SESSION['error'] = $userMessage;
}

/**
 * Secure database error handler
 *
 * @param PDOException $e PDO exception
 * @param string $userMessage User-friendly message
 * @return void
 */
function handle_database_error($e, $userMessage = "A database error occurred. Please try again.") {
    log_error("Database Error: " . $e->getMessage());
    $_SESSION['error'] = $userMessage;
}

/**
 * Initialize secure session with timeout
 * Should be called at the beginning of every protected page
 *
 * @param int $timeout Session timeout in seconds (default: 1800 = 30 minutes)
 * @return bool True if session is valid, false if timed out
 */
function init_secure_session($timeout = 1800) {
    // Configure secure session parameters
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_samesite', 'Strict');

        // Enable secure flag if HTTPS is available
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            ini_set('session.cookie_secure', 1);
        }

        session_start();
    }

    // Check for session timeout
    if (isset($_SESSION['LAST_ACTIVITY'])) {
        $elapsed = time() - $_SESSION['LAST_ACTIVITY'];
        if ($elapsed > $timeout) {
            // Session timed out
            session_unset();
            session_destroy();
            return false;
        }
    }

    // Update last activity time
    $_SESSION['LAST_ACTIVITY'] = time();

    // Regenerate session ID periodically to prevent fixation
    if (!isset($_SESSION['CREATED'])) {
        $_SESSION['CREATED'] = time();
    } elseif (time() - $_SESSION['CREATED'] > 600) {
        // Regenerate session ID every 10 minutes
        session_regenerate_id(true);
        $_SESSION['CREATED'] = time();
    }

    return true;
}

/**
 * Check if user is authenticated (student)
 *
 * @return bool True if authenticated, false otherwise
 */
function is_authenticated() {
    return isset($_SESSION['login']) && strlen($_SESSION['login']) > 0;
}

/**
 * Check if admin is authenticated
 *
 * @return bool True if authenticated, false otherwise
 */
function is_admin_authenticated() {
    return isset($_SESSION['alogin']) && strlen($_SESSION['alogin']) > 0;
}

/**
 * Require authentication - redirect if not logged in
 *
 * @param string $redirectUrl URL to redirect to if not authenticated
 * @return void
 */
function require_auth($redirectUrl = 'index.php') {
    if (!init_secure_session()) {
        $_SESSION['error'] = "Your session has expired. Please log in again.";
        header("Location: {$redirectUrl}");
        exit();
    }

    if (!is_authenticated()) {
        header("Location: {$redirectUrl}");
        exit();
    }
}

/**
 * Require admin authentication - redirect if not logged in
 *
 * @param string $redirectUrl URL to redirect to if not authenticated
 * @return void
 */
function require_admin_auth($redirectUrl = '../adminlogin.php') {
    if (!init_secure_session()) {
        $_SESSION['error'] = "Your session has expired. Please log in again.";
        header("Location: {$redirectUrl}");
        exit();
    }

    if (!is_admin_authenticated()) {
        header("Location: {$redirectUrl}");
        exit();
    }
}

/**
 * Validate email format
 *
 * @param string $email Email address to validate
 * @return bool True if valid, false otherwise
 */
function validate_email($email) {
    // Filter validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    // Additional regex validation
    $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
    return preg_match($pattern, $email) === 1;
}

/**
 * Validate phone number format
 *
 * @param string $phone Phone number to validate
 * @return bool True if valid, false otherwise
 */
function validate_phone($phone) {
    // Remove common separators
    $phone = preg_replace('/[\s\-\(\)]/', '', $phone);

    // Check if it's numeric and has reasonable length
    return is_numeric($phone) && strlen($phone) >= 7 && strlen($phone) <= 15;
}

/**
 * Generate CSRF token
 *
 * @return string CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 *
 * @param string $token Token to validate
 * @return bool True if valid, false otherwise
 */
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Secure redirect
 * Prevents open redirect vulnerabilities
 *
 * @param string $url URL to redirect to
 * @param array $allowedHosts Array of allowed hosts
 * @return void
 */
function secure_redirect($url, $allowedHosts = []) {
    // Parse URL
    $parsedUrl = parse_url($url);

    // If it's a relative URL, it's safe
    if (!isset($parsedUrl['host'])) {
        header("Location: {$url}");
        exit();
    }

    // Check if host is in allowed list
    if (empty($allowedHosts)) {
        $allowedHosts = [$_SERVER['HTTP_HOST']];
    }

    if (in_array($parsedUrl['host'], $allowedHosts)) {
        header("Location: {$url}");
        exit();
    }

    // Unsafe redirect attempt - log and redirect to safe location
    log_error("Unsafe redirect attempt to: {$url}");
    header("Location: index.php");
    exit();
}

/**
 * Rate limiting check
 * Simple rate limiting based on IP address
 *
 * @param string $action Action identifier (e.g., 'login', 'signup')
 * @param int $maxAttempts Maximum attempts allowed
 * @param int $timeWindow Time window in seconds
 * @return bool True if allowed, false if rate limit exceeded
 */
function check_rate_limit($action, $maxAttempts = 5, $timeWindow = 900) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "ratelimit_{$action}_{$ip}";

    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }

    $data = $_SESSION[$key];
    $elapsed = time() - $data['first_attempt'];

    // Reset if time window has passed
    if ($elapsed > $timeWindow) {
        $_SESSION[$key] = [
            'attempts' => 1,
            'first_attempt' => time()
        ];
        return true;
    }

    // Check if limit exceeded
    if ($data['attempts'] >= $maxAttempts) {
        return false;
    }

    // Increment attempts
    $_SESSION[$key]['attempts']++;
    return true;
}

/**
 * Get remaining rate limit time
 *
 * @param string $action Action identifier
 * @param int $timeWindow Time window in seconds
 * @return int Remaining seconds until rate limit resets
 */
function get_rate_limit_reset_time($action, $timeWindow = 900) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $key = "ratelimit_{$action}_{$ip}";

    if (!isset($_SESSION[$key])) {
        return 0;
    }

    $elapsed = time() - $_SESSION[$key]['first_attempt'];
    $remaining = $timeWindow - $elapsed;

    return max(0, $remaining);
}
?>
