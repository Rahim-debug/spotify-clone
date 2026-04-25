<?php
/**
 * JamendoAPI - Handles all interactions with the Jamendo Music API
 * Base URL: https://api.jamendo.com/v3.0/
 * 
 * Note: Set JAMENDO_CLIENT_ID in config.php or environment variables
 */
class JamendoAPI
{
    private $clientId;
    private $baseUrl = "https://api.jamendo.com/v3.0/";
    private $cacheDir;

    public function __construct($clientId = null)
    {
        // Try to get client ID from parameter, then from constant, then from environment
        $this->clientId = $clientId ?? (defined('JAMENDO_CLIENT_ID') ? JAMENDO_CLIENT_ID : getenv('JAMENDO_CLIENT_ID'));
        
        if (empty($this->clientId)) {
            throw new Exception("JAMENDO_CLIENT_ID is not set. Please configure it in your config.php or environment variables.");
        }

        // Setup cache directory for API responses (optional optimization)
        $this->cacheDir = dirname(__FILE__) . '/../../.jamendo_cache';
        if (!is_dir($this->cacheDir) && !mkdir($this->cacheDir, 0755, true)) {
            // If cache directory can't be created, that's okay - we'll just skip caching
            $this->cacheDir = null;
        }
    }

    /**
     * Make a GET request to the Jamendo API
     * 
     * @param string $endpoint API endpoint (e.g., 'tracks/trending')
     * @param array $params Query parameters
     * @return array Decoded JSON response
     */
    private function makeRequest($endpoint, $params = [])
    {
        // Add client ID to all requests
        $params['client_id'] = $this->clientId;
        $params['format'] = 'json';

        // Build full URL with query string
        $queryString = http_build_query($params);
        $url = $this->baseUrl . $endpoint . '?' . $queryString;

        // Check cache first
        if ($this->cacheDir) {
            $cacheKey = md5($url);
            $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';
            $cacheTimeout = 3600; // Cache for 1 hour

            if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTimeout) {
                return json_decode(file_get_contents($cacheFile), true);
            }
        }

        // Make the actual request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("Jamendo API request failed: " . $error);
        }

        if ($httpCode !== 200) {
            throw new Exception("Jamendo API returned status code: " . $httpCode);
        }

        $data = json_decode($response, true);

        // Cache the response
        if ($this->cacheDir && isset($cacheFile)) {
            file_put_contents($cacheFile, $response);
        }

        return $data;
    }

    /**
     * Get trending tracks
     * 
     * @param int $limit Number of tracks to fetch (default 10, max 200)
     * @param int $offset Pagination offset (default 0)
     * @return array Array of track objects
     */
    public function getTrendingTracks($limit = 10, $offset = 0)
    {
        $params = [
            'limit' => min($limit, 200),
            'offset' => $offset,
            'order' => 'popularity_week'
        ];

        $response = $this->makeRequest('tracks', $params);
        return $response['results'] ?? [];
    }

    /**
     * Search for tracks by query
     * 
     * @param string $query Search query (track name, artist name, etc.)
     * @param int $limit Number of results to fetch (default 10, max 200)
     * @param int $offset Pagination offset (default 0)
     * @return array Array of track objects
     */
    public function searchTracks($query, $limit = 10, $offset = 0)
    {
        $params = [
            'search' => $query,
            'limit' => min($limit, 200),
            'offset' => $offset
        ];

        $response = $this->makeRequest('tracks', $params);
        return $response['results'] ?? [];
    }

    /**
     * Search for artists by query
     * 
     * @param string $query Search query (artist name)
     * @param int $limit Number of results to fetch (default 10, max 200)
     * @param int $offset Pagination offset (default 0)
     * @return array Array of artist objects
     */
    public function searchArtists($query, $limit = 10, $offset = 0)
    {
        $params = [
            'search' => $query,
            'limit' => min($limit, 200),
            'offset' => $offset
        ];

        $response = $this->makeRequest('artists', $params);
        return $response['results'] ?? [];
    }

    /**
     * Get albums by search or trending
     * 
     * @param string $query Search query (album name)
     * @param int $limit Number of results to fetch (default 10, max 200)
     * @param int $offset Pagination offset (default 0)
     * @return array Array of album objects
     */
    public function searchAlbums($query, $limit = 10, $offset = 0)
    {
        $params = [
            'search' => $query,
            'limit' => min($limit, 200),
            'offset' => $offset
        ];

        $response = $this->makeRequest('albums', $params);
        return $response['results'] ?? [];
    }

    /**
     * Get an artist's top tracks
     * 
     * @param int $artistId Jamendo artist ID
     * @param int $limit Number of tracks to fetch (default 10, max 200)
     * @return array Array of track objects
     */
    public function getArtistTracks($artistId, $limit = 10)
    {
        $params = [
            'artist_id' => $artistId,
            'limit' => min($limit, 200),
            'order' => 'popularity'
        ];

        $response = $this->makeRequest('tracks', $params);
        return $response['results'] ?? [];
    }

    /**
     * Get album tracks
     * 
     * @param int $albumId Jamendo album ID
     * @return array Array of track objects
     */
    public function getAlbumTracks($albumId)
    {
        $params = [
            'album_id' => $albumId,
            'limit' => 200
        ];

        $response = $this->makeRequest('tracks', $params);
        return $response['results'] ?? [];
    }

    /**
     * Get track details by ID
     * 
     * @param int $trackId Jamendo track ID
     * @return array Track object with audio URL and metadata
     */
    public function getTrackDetail($trackId)
    {
        $params = [
            'id' => $trackId
        ];

        $response = $this->makeRequest('tracks', $params);
        
        if (isset($response['results'][0])) {
            return $response['results'][0];
        }

        return null;
    }

    /**
     * Get artist details
     * 
     * @param int $artistId Jamendo artist ID
     * @return array Artist object
     */
    public function getArtistDetail($artistId)
    {
        $params = [
            'id' => $artistId
        ];

        $response = $this->makeRequest('artists', $params);
        
        if (isset($response['results'][0])) {
            return $response['results'][0];
        }

        return null;
    }

    /**
     * Get album details
     * 
     * @param int $albumId Jamendo album ID
     * @return array Album object
     */
    public function getAlbumDetail($albumId)
    {
        $params = [
            'id' => $albumId
        ];

        $response = $this->makeRequest('albums', $params);
        
        if (isset($response['results'][0])) {
            return $response['results'][0];
        }

        return null;
    }

    /**
     * Format a Jamendo track for frontend consumption
     * Converts API response to match expected object structure
     * 
     * @param array $jamendoTrack Raw Jamendo API track object
     * @return array Formatted track object with: id, title, artist, artistId, 
     *              album, albumId, image, audio, duration, jamendoId
     */
    public static function formatTrack($jamendoTrack)
    {
        return [
            'id' => $jamendoTrack['id'],
            'jamendoId' => $jamendoTrack['id'],
            'title' => $jamendoTrack['name'] ?? 'Unknown',
            'artist' => $jamendoTrack['artist_name'] ?? 'Unknown Artist',
            'artistId' => $jamendoTrack['artist_id'] ?? null,
            'album' => $jamendoTrack['album_name'] ?? 'Unknown Album',
            'albumId' => $jamendoTrack['album_id'] ?? null,
            'image' => $jamendoTrack['image'] ?? null,
            'audio' => $jamendoTrack['audio'] ?? ($jamendoTrack['audio_url'] ?? null),
            'duration' => $jamendoTrack['duration'] ?? 0,
            'isStreamable' => !empty($jamendoTrack['audio'] || $jamendoTrack['audio_url'] ?? null),
            'releaseDate' => $jamendoTrack['releasedate'] ?? null
        ];
    }

    /**
     * Format multiple tracks at once
     * 
     * @param array $jamendoTracks Array of raw Jamendo API track objects
     * @return array Array of formatted track objects
     */
    public static function formatTracks($jamendoTracks)
    {
        return array_map([self::class, 'formatTrack'], $jamendoTracks);
    }
}
?>
