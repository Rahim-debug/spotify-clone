<?php
    ob_start();

    // Start sessions
    session_start();

    // ============================================
    // HTTP CACHING HEADERS - PERFORMANCE OPTIMIZATION
    // ============================================
    // Cache static assets for 7 days
    $current_url = $_SERVER['REQUEST_URI'];
    if (preg_match('/\.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|svg|eot)$/i', $current_url)) {
        header('Cache-Control: public, max-age=604800'); // 7 days
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 604800) . ' GMT');
    } else {
        // Dynamic content - no caching
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
    }

    // Sets timezone for saving datetimes in DB
    $timezone = date_default_timezone_set('Africa/Cairo');

    // Create connection
    // URL, USER, PW, DB
    $con = mysqli_connect("localhost", "root", "", "spotify-clone");

    if(mysqli_connect_errno()) {
        echo "Failed to connect" . mysqli_connect_errno();
    }

    // ============================================
    // JAMENDO API CONFIGURATION
    // ============================================
    // Get free API key at https://www.jamendo.com/api/v3.0/
    // Replace 'YOUR_JAMENDO_CLIENT_ID_HERE' with your actual client ID
    define('JAMENDO_CLIENT_ID', '609644fa');

    // ============================================
    // PATH HELPER FUNCTION
    // ============================================
    /**
     * Convert relative file paths to absolute paths from webroot
     * This ensures files load correctly regardless of page context
     * 
     * @param string $path The file path from database
     * @return string Absolute path from webroot
     */
    function getAssetPath($path) {
        if (empty($path)) {
            return $path;
        }
        
        // If already absolute URL or starts with /, return as-is
        if (strpos($path, 'http') === 0 || strpos($path, '/') === 0) {
            return $path;
        }
        
        // Get the base directory name (spotify-clone)
        // __DIR__ is Includes folder, dirname gets parent (spotify-clone), basename gets folder name
        $basePath = basename(dirname(__DIR__));
        
        // Return path with leading slash for webroot-relative path
        return '/' . $basePath . '/' . $path;
    }
?>