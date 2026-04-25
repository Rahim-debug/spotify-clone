<?php
// Smart Cache/Proxy for Jamendo Audio and Images
// This bypasses browser-level ad-blockers and properly supports HTML5 Audio range requests
// by downloading the file to the local server first, then serving it natively.

if (!isset($_GET['url'])) {
    http_response_code(400);
    exit("No URL provided");
}

$url = $_GET['url'];

// Ensure we only proxy Jamendo URLs to prevent abuse
if (strpos($url, 'jamendo.com') === false) {
    http_response_code(403);
    exit("Only jamendo URLs are allowed");
}

// Create a cache directory
$cacheDir = __DIR__ . '/../../../assets/cache';
if (!file_exists($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

// Generate a safe filename based on URL hash
$hash = md5($url);
$isImage = strpos($url, 'usercontent') !== false;
$ext = $isImage ? '.jpg' : '.mp3';
$filename = $hash . $ext;
$filepath = $cacheDir . '/' . $filename;
$webPath = 'assets/cache/' . $filename;

// Disable time limit for downloading large files
ini_set('max_execution_time', 0);

// Download if not already in cache or if file is empty
if (!file_exists($filepath) || filesize($filepath) === 0) {
    $ch = curl_init($url);
    $fp = fopen($filepath, 'w+');
    
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Execute download
    curl_exec($ch);
    
    if (curl_errno($ch)) {
        // Handle error gracefully
        fclose($fp);
        unlink($filepath); // Remove broken file
        http_response_code(502);
        exit("Failed to download media: " . curl_error($ch));
    }
    
    curl_close($ch);
    fclose($fp);
}

// Use a robust local path calculation without including config.php to avoid session locking!
$basePath = basename(dirname(__DIR__, 3));
$webPath = '/' . $basePath . '/' . $webPath;

// Redirect the browser to the cached local file
// This allows the browser to handle Range requests natively for scrubbing/seeking!
header("Location: $webPath");
exit();
?>
