<?php
/**
 * Simple .env file loader
 * Loads environment variables from .env file into $_ENV and getenv()
 */

function loadEnv($path) {
    if (!file_exists($path)) {
        throw new Exception(".env file not found at: $path\n\nPlease create a .env file by copying .env.example:\ncp .env.example .env\n\nThen update the values in .env with your actual credentials.");
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // Skip comments and empty lines
        if (strpos(trim($line), '#') === 0 || trim($line) === '') {
            continue;
        }

        // Parse the line
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);

            // Remove quotes if present
            $value = trim($value, '"\'');

            // Set in environment
            if (!array_key_exists($name, $_ENV)) {
                $_ENV[$name] = $value;
                putenv("$name=$value");
            }
        }
    }
}

/**
 * Get environment variable with optional default value
 */
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        $value = $_ENV[$key] ?? $default;
    }
    return $value;
}

// Load .env file from root directory
$envPath = __DIR__ . '/../.env';
try {
    loadEnv($envPath);
} catch (Exception $e) {
    die("Environment Configuration Error: " . $e->getMessage());
}
?>
