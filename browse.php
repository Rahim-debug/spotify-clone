<?php 
    include("Includes/includedFiles.php");
?>

<div class="heroBanner" style="background: linear-gradient(45deg, #8a2be2, #00d2ff); border-radius: 20px; padding: 60px 40px; margin-bottom: 40px; box-shadow: 0 10px 30px rgba(138, 43, 226, 0.4); text-align: left; position: relative; overflow: hidden;">
    <h1 style="font-size: 48px; margin: 0; font-weight: 800; letter-spacing: -1px; z-index: 2; position: relative; text-shadow: 0 2px 10px rgba(0,0,0,0.2);">Discover the Sound of Tomorrow</h1>
    <p style="font-size: 18px; margin-top: 15px; font-weight: 300; z-index: 2; position: relative; opacity: 0.9; text-shadow: 0 1px 5px rgba(0,0,0,0.2);">Explore millions of tracks tailored just for you.</p>
    <div style="position: absolute; right: -50px; top: -50px; width: 300px; height: 300px; background: rgba(255,255,255,0.1); border-radius: 50%; z-index: 1;"></div>
    <div style="position: absolute; right: 100px; bottom: -80px; width: 200px; height: 200px; background: rgba(255,255,255,0.1); border-radius: 50%; z-index: 1;"></div>
</div>

<h2 style="font-size: 28px; margin-bottom: 20px; padding-left: 10px;">Trending Now</h2>

<div class="gridViewContainer" id="browseGrid">
    <!-- Will be populated by JavaScript with Jamendo API data -->
    <p style="text-align: center; padding: 20px; color: #999;">Loading trending tracks...</p>
</div>

<script>
    $(document).ready(function() {
        // Set a default Jamendo Client ID if not set
        if (!JamendoClient.clientId) {
            JamendoClient.setClientId('<?php echo defined("JAMENDO_CLIENT_ID") ? JAMENDO_CLIENT_ID : "YOUR_JAMENDO_CLIENT_ID_HERE"; ?>');
        }

        // Fetch trending tracks from Jamendo API
        JamendoClient.getTrendingTracks(20, 0, function(data) {
            var tracks = data.results || [];
            
            if (tracks.length === 0) {
                $('#browseGrid').html('<p style="text-align: center; padding: 20px; color: #999;">No tracks found.</p>');
                return;
            }

            var gridHtml = '';
            window.browseTracks = []; // Store all tracks for playback
            
            // Get unique albums from tracks
            var albumsMap = {};
            var uniqueAlbums = [];
            
            tracks.forEach(function(track) {
                var albumId = track.album_id;
                if (albumId && !albumsMap[albumId]) {
                    albumsMap[albumId] = true;
                    
                    var album = {
                        id: track.album_id,
                        album: track.album_name || 'Unknown Album',
                        image: track.image,
                        artist: track.artist_name,
                        artistId: track.artist_id
                    };
                    
                    uniqueAlbums.push(album);
                    
                    if (uniqueAlbums.length >= 10) return; // Show max 10 albums
                }
            });

            // Create grid items
            uniqueAlbums.forEach(function(album, index) {
                var imgSrc = (album.image && album.image.trim() !== '') ? "Includes/Handlers/ajax/proxy.php?url=" + encodeURIComponent(album.image) : '/spotify-clone/assets/images/artwork/default.jpg';
                var title = album.album || 'Unknown Album';
                
                gridHtml += '<div class="gridViewItem">';
                gridHtml += '  <span onclick="openPage(\'album.php?jamendoId=' + album.id + '\');" style="cursor: pointer;">';
                gridHtml += '    <img src="' + imgSrc + '" alt="' + title + '" ';
                gridHtml += 'onerror="if(this.src !== \'/spotify-clone/assets/images/artwork/default.jpg\') { this.src=\'/spotify-clone/assets/images/artwork/default.jpg\'; } else { this.style.backgroundColor=\'#333\'; this.style.opacity=\'0.3\'; }" ';
                gridHtml += 'style="width:100%; height:100%; object-fit:cover;">';
                gridHtml += '    <div class="gridViewInfo">' + TrackRenderer.escapeHtml(title) + '</div>';
                gridHtml += '  </span>';
                gridHtml += '</div>';
            });

            $('#browseGrid').html(gridHtml || '<p>No albums found</p>');
        }, function(error) {
            console.error('Failed to fetch Jamendo data:', error);
            $('#browseGrid').html('<p style="color: red; padding: 20px;">Error loading tracks. Please check your Jamendo Client ID configuration.</p>');
        });
    });
</script>