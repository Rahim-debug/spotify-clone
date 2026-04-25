/**
 * Jamendo API Utilities and Track Mapping Functions
 * 
 * This file provides helper functions for working with Jamendo API data
 * and converting between Jamendo format and our internal track object format.
 */

// ============================================================
// JAMENDO API CLIENT
// ============================================================

var JamendoClient = {
    baseUrl: 'https://api.jamendo.com/v3.0/',
    clientId: null,

    /**
     * Set the Jamendo Client ID
     * Get one free at https://www.jamendo.com/api/v3.0/
     */
    setClientId: function(id) {
        this.clientId = id;
    },

    /**
     * Make a GET request to Jamendo API
     */
    request: function(endpoint, params, callback, errorCallback) {
        if (!this.clientId) {
            console.error('Jamendo Client ID not set. Please call JamendoClient.setClientId(id)');
            if (errorCallback) errorCallback({ error: 'Client ID not configured' });
            return;
        }

        params = params || {};
        params.client_id = this.clientId;
        params.format = 'json';

        var queryString = Object.keys(params)
            .map(key => encodeURIComponent(key) + '=' + encodeURIComponent(params[key]))
            .join('&');

        var url = this.baseUrl + endpoint + '?' + queryString;

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            timeout: 10000,
            success: function(data) {
                console.log('Jamendo API success:', endpoint, data);
                if (callback) callback(data);
            },
            error: function(xhr, status, error) {
                var errorMsg = 'Jamendo API Error: ' + status + ' - ' + error;
                if (xhr && xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        errorMsg += ' - ' + (response.error_message || response.error || '');
                    } catch (e) {}
                }
                console.error(errorMsg, xhr);
                if (errorCallback) errorCallback({ error: errorMsg, status: status, xhr: xhr });
            }
        });
    },

    /**
     * Get trending tracks
     */
    getTrendingTracks: function(limit, offset, callback, errorCallback) {
        limit = limit || 10;
        offset = offset || 0;
        
        // Use /tracks endpoint with order parameter for trending
        this.request('tracks', {
            limit: Math.min(limit, 200),
            offset: offset,
            order: 'popularity_week'
        }, callback, errorCallback);
    },

    /**
     * Search for tracks
     */
    searchTracks: function(query, limit, offset, callback, errorCallback) {
        limit = limit || 10;
        offset = offset || 0;
        
        this.request('tracks', {
            search: query,
            limit: Math.min(limit, 200),
            offset: offset
        }, callback, errorCallback);
    },

    /**
     * Search for artists
     */
    searchArtists: function(query, limit, offset, callback, errorCallback) {
        limit = limit || 10;
        offset = offset || 0;
        
        this.request('artists', {
            search: query,
            limit: Math.min(limit, 200),
            offset: offset
        }, callback, errorCallback);
    },

    /**
     * Get tracks by artist ID
     */
    getArtistTracks: function(artistId, limit, callback, errorCallback) {
        limit = limit || 10;
        
        this.request('tracks', {
            artist_id: artistId,
            limit: Math.min(limit, 200),
            order: 'popularity'
        }, callback, errorCallback);
    }
};

// ============================================================
// TRACK OBJECT MAPPING
// ============================================================

var TrackMapper = {
    /**
     * Convert a Jamendo API track to our internal format
     * 
     * Jamendo fields: id, name, artist_name, artist_id, album_name, album_id, 
     *                 image, audio, duration, releasedate
     * 
     * Our internal format: id, jamendoId, title, artist, artistId, album, 
     *                      albumId, image, audio, duration, isStreamable
     */
    fromJamendo: function(jamendoTrack) {
        if (!jamendoTrack) return null;

        return {
            id: jamendoTrack.id,
            jamendoId: jamendoTrack.id,
            title: jamendoTrack.name || 'Unknown Title',
            artist: jamendoTrack.artist_name || 'Unknown Artist',
            artistId: jamendoTrack.artist_id,
            album: jamendoTrack.album_name || 'Unknown Album',
            albumId: jamendoTrack.album_id,
            image: (jamendoTrack.image || jamendoTrack.album_image) ? "Includes/Handlers/ajax/proxy.php?url=" + encodeURIComponent(jamendoTrack.image || jamendoTrack.album_image) : null,
            audio: (jamendoTrack.audio || jamendoTrack.audio_url) ? "Includes/Handlers/ajax/proxy.php?url=" + encodeURIComponent(jamendoTrack.audio || jamendoTrack.audio_url) : null,
            duration: jamendoTrack.duration || 0,
            isStreamable: !!(jamendoTrack.audio || jamendoTrack.audio_url),
            releaseDate: jamendoTrack.releasedate,
            // Keep original for reference
            _original: jamendoTrack
        };
    },

    /**
     * Convert multiple Jamendo tracks
     */
    fromJamendoArray: function(jamendoTracks) {
        if (!jamendoTracks || !Array.isArray(jamendoTracks)) return [];
        return jamendoTracks.map(this.fromJamendo.bind(this));
    },

    /**
     * Check if a track object is from Jamendo (has jamendoId)
     */
    isJamendoTrack: function(track) {
        return track && (track.jamendoId || (typeof track.id === 'number' && !track.path));
    },

    /**
     * Format duration in seconds to MM:SS format
     */
    formatDuration: function(seconds) {
        if (!seconds || isNaN(seconds)) return '0:00';
        
        var mins = Math.floor(seconds / 60);
        var secs = Math.floor(seconds % 60);
        
        return mins + ':' + (secs < 10 ? '0' : '') + secs;
    }
};

// ============================================================
// HTML RENDERING HELPERS
// ============================================================

var TrackRenderer = {
    /**
     * Create a tracklist row HTML for a Jamendo track
     * 
     * Same structure as existing local tracks:
     * <li class='tracklistRow'>
     *   <div class='trackCount'>
     *     <img class='play' onclick='setTrack(...)'>
     *     <span class='trackNumber'>1.</span>
     *   </div>
     *   <div class='trackInfo'>
     *     <span class='trackName'>Song Title</span>
     *     <span class='artistName'>Artist Name</span>
     *   </div>
     *   <div class='trackOptions'>
     *     <input type='hidden' class='songId'>
     *     <img class='optionButton' onclick='showOptionsMenu(this)'>
     *   </div>
     *   <div class='trackDuration'>
     *     <span class='duration'>3:45</span>
     *   </div>
     * </li>
     */
    createTracklistRow: function(track, index, playlist) {
        // Serialize track object to JSON string for onclick handler
        var trackJson = JSON.stringify(track).replace(/'/g, "&apos;");
        var playlistJson = JSON.stringify(playlist).replace(/'/g, "&apos;");
        
        var duration = TrackMapper.formatDuration(track.duration);
        
        return `<li class='tracklistRow'>
            <div class='trackCount'>
                <img class='play' src='assets/images/Icons/play-white.png' 
                     onclick="setTrack('${trackJson}'|JSON, '${playlistJson}'|JSON, true)">
                <span class='trackNumber'>${index}.</span>
            </div>
            <div class='trackInfo'>
                <span class='trackName'>${this.escapeHtml(track.title)}</span>
                <span class='artistName'>${this.escapeHtml(track.artist)}</span>
            </div>
            <div class='trackOptions'>
                <input type='hidden' class='songId' value='${track.id}'>
                <img class='optionButton' src='assets/images/Icons/more.png' 
                     onclick='showOptionsMenu(this)'>
            </div>
            <div class='trackDuration'>
                <span class='duration'>${duration}</span>
            </div>
        </li>`;
    },

    /**
     * Create a grid view item for an album
     * 
     * Same structure as existing grid items
     */
    createGridViewItem: function(album) {
        var imgSrc = album.image || 'assets/images/artwork/default.jpg';
        var title = album.album || 'Unknown Album';
        var id = album.albumId || album.id;
        
        return `<div class='gridViewItem'>
            <span onclick='openPage("album.php?id=${id}");'>
                <img src='${this.escapeHtml(imgSrc)}' alt='${this.escapeHtml(title)}'>
                <div class='gridViewInfo'>
                    ${this.escapeHtml(title)}
                </div>
            </span>
        </div>`;
    },

    /**
     * Escape HTML special characters to prevent XSS
     */
    escapeHtml: function(text) {
        if (!text) return '';
        
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
};

// Helper function to parse JSON from onclick handlers
// Usage: onclick="setTrack(parseJsonAttribute(this, 'data-track'), ...)"
function parseJsonAttribute(element, attrName) {
    var jsonStr = element.getAttribute(attrName);
    try {
        return JSON.parse(jsonStr);
    } catch (e) {
        console.error('Failed to parse JSON attribute:', e);
        return null;
    }
}
