<?php
/**
 * IP Geolocation Helper Functions
 * Provides IP address geolocation lookup for security and analytics
 */

/**
 * Get geolocation information for an IP address
 * Uses ip-api.com free API (no key required, 45 requests/minute limit)
 *
 * @param string $ip IP address to lookup
 * @param bool $detailed Return detailed info
 * @return string|array Geolocation string or detailed array
 */
function getGeoLocation($ip, $detailed = false) {
    // Skip localhost and private IPs
    if (in_array($ip, ['127.0.0.1', '::1', 'unknown']) || isPrivateIP($ip)) {
        return $detailed ? [
            'country' => 'Local',
            'city' => 'Localhost',
            'region' => '',
            'timezone' => date_default_timezone_get(),
            'isp' => 'Local Network'
        ] : 'Local';
    }

    // Check cache first
    $cacheKey = "geo_" . md5($ip);
    if (isset($_SESSION[$cacheKey])) {
        $cached = $_SESSION[$cacheKey];
        if ($cached['expires'] > time()) {
            return $detailed ? $cached['data'] : $cached['string'];
        }
    }

    try {
        // Use ip-api.com free API
        $url = "http://ip-api.com/json/{$ip}?fields=status,message,country,regionName,city,timezone,isp";

        $context = stream_context_create([
            'http' => [
                'timeout' => 3, // 3 second timeout
                'ignore_errors' => true
            ]
        ]);

        $response = @file_get_contents($url, false, $context);

        if ($response === false) {
            return $detailed ? ['country' => 'Unknown', 'city' => 'Unknown'] : 'Unknown';
        }

        $data = json_decode($response, true);

        if ($data && $data['status'] === 'success') {
            $geoData = [
                'country' => $data['country'] ?? 'Unknown',
                'city' => $data['city'] ?? 'Unknown',
                'region' => $data['regionName'] ?? '',
                'timezone' => $data['timezone'] ?? '',
                'isp' => $data['isp'] ?? ''
            ];

            $geoString = trim("{$geoData['city']}, {$geoData['country']}");

            // Cache for 24 hours
            $_SESSION[$cacheKey] = [
                'data' => $geoData,
                'string' => $geoString,
                'expires' => time() + 86400
            ];

            return $detailed ? $geoData : $geoString;
        }
    } catch (Exception $e) {
        log_error("Geolocation API error: " . $e->getMessage());
    }

    return $detailed ? ['country' => 'Unknown', 'city' => 'Unknown'] : 'Unknown';
}

/**
 * Check if IP address is private/local
 *
 * @param string $ip IP address
 * @return bool True if private IP
 */
function isPrivateIP($ip) {
    // Convert to long integer for comparison
    $longIp = ip2long($ip);

    if ($longIp === false) {
        return false;
    }

    // Check private IP ranges
    $privateRanges = [
        ['10.0.0.0', '10.255.255.255'],       // Class A private
        ['172.16.0.0', '172.31.255.255'],     // Class B private
        ['192.168.0.0', '192.168.255.255'],   // Class C private
        ['127.0.0.0', '127.255.255.255'],     // Loopback
    ];

    foreach ($privateRanges as $range) {
        $start = ip2long($range[0]);
        $end = ip2long($range[1]);

        if ($longIp >= $start && $longIp <= $end) {
            return true;
        }
    }

    return false;
}

/**
 * Get detailed IP information
 *
 * @param string $ip IP address
 * @return array Detailed IP information
 */
function getIPInfo($ip) {
    $geoData = getGeoLocation($ip, true);

    return [
        'ip' => $ip,
        'is_private' => isPrivateIP($ip),
        'country' => $geoData['country'] ?? 'Unknown',
        'city' => $geoData['city'] ?? 'Unknown',
        'region' => $geoData['region'] ?? '',
        'timezone' => $geoData['timezone'] ?? '',
        'isp' => $geoData['isp'] ?? '',
        'location_string' => getGeoLocation($ip, false)
    ];
}

/**
 * Check if IP is from suspicious location
 * (Can be customized based on your security requirements)
 *
 * @param string $ip IP address
 * @param array $allowedCountries List of allowed country codes (empty = allow all)
 * @return bool True if suspicious
 */
function isSuspiciousIP($ip, $allowedCountries = []) {
    if (empty($allowedCountries)) {
        return false; // No restrictions
    }

    $geoData = getGeoLocation($ip, true);
    $country = $geoData['country'] ?? '';

    // Check if country is in allowed list
    return !in_array($country, $allowedCountries);
}

/**
 * Get client's real IP address (handles proxies)
 *
 * @return string Client IP address
 */
function getClientIP() {
    // Check for shared internet/ISP IP
    if (!empty($_SERVER['HTTP_CLIENT_IP']) && filter_var($_SERVER['HTTP_CLIENT_IP'], FILTER_VALIDATE_IP)) {
        return $_SERVER['HTTP_CLIENT_IP'];
    }

    // Check for IPs passing through proxies
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // HTTP_X_FORWARDED_FOR can contain multiple IPs
        $ipList = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        foreach ($ipList as $ip) {
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }

    // Return standard remote address
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

/**
 * Detect location changes for security alerts
 *
 * @param PDO $dbh Database handle
 * @param int $userId User ID
 * @param string $userType 'admin' or 'student'
 * @return array ['is_new_location' => bool, 'previous_location' => string, 'current_location' => string]
 */
function detectLocationChange($dbh, $userId, $userType) {
    $currentIp = getClientIP();
    $currentLocation = getGeoLocation($currentIp);

    try {
        // Get last login location from audit logs
        $sql = "SELECT geo_location, ip_address
                FROM tblaudit_logs
                WHERE user_id = :user_id
                AND user_type = :user_type
                AND action = 'login_success'
                AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)
                ORDER BY created_at DESC
                LIMIT 1";

        $query = $dbh->prepare($sql);
        $query->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $query->bindParam(':user_type', $userType, PDO::PARAM_STR);
        $query->execute();

        $lastLogin = $query->fetch(PDO::FETCH_ASSOC);

        if (!$lastLogin) {
            return [
                'is_new_location' => false,
                'previous_location' => null,
                'current_location' => $currentLocation,
                'is_first_login' => true
            ];
        }

        $previousLocation = $lastLogin['geo_location'];
        $previousIp = $lastLogin['ip_address'];

        // Consider it a new location if city/country changed
        $isNewLocation = ($currentLocation !== $previousLocation) &&
                        ($currentIp !== $previousIp) &&
                        (!isPrivateIP($currentIp));

        return [
            'is_new_location' => $isNewLocation,
            'previous_location' => $previousLocation,
            'current_location' => $currentLocation,
            'previous_ip' => $previousIp,
            'current_ip' => $currentIp,
            'is_first_login' => false
        ];
    } catch (PDOException $e) {
        log_error("Error detecting location change: " . $e->getMessage());
        return [
            'is_new_location' => false,
            'previous_location' => null,
            'current_location' => $currentLocation
        ];
    }
}

/**
 * Get location statistics for analytics
 *
 * @param PDO $dbh Database handle
 * @param string $period 'today', 'week', 'month'
 * @return array Location statistics
 */
function getLocationStats($dbh, $period = 'week') {
    try {
        $dateFilter = "";
        switch ($period) {
            case 'today':
                $dateFilter = "DATE(created_at) = CURDATE()";
                break;
            case 'week':
                $dateFilter = "created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
                break;
            case 'month':
                $dateFilter = "created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
                break;
        }

        $sql = "SELECT
                    geo_location,
                    COUNT(*) as access_count,
                    COUNT(DISTINCT user_id) as unique_users
                FROM tblaudit_logs
                WHERE $dateFilter
                AND geo_location IS NOT NULL
                AND geo_location != 'Local'
                AND geo_location != 'Unknown'
                GROUP BY geo_location
                ORDER BY access_count DESC
                LIMIT 10";

        $query = $dbh->prepare($sql);
        $query->execute();

        return $query->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        log_error("Error fetching location stats: " . $e->getMessage());
        return [];
    }
}
?>
