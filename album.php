<?php 
    include("Includes/includedFiles.php"); 

    $isJamendoAlbum = false;
    $album = null;
    $artist = null;
    $albumId = null;
    $jamendoAlbumId = null;

    // Check if this is a Jamendo album or local album
    if(isset($_GET['jamendoId'])) {
        $jamendoAlbumId = $_GET['jamendoId'];
        $isJamendoAlbum = true;
    }
    elseif(isset($_GET['id'])) {
        $albumId = $_GET['id'];
    }
    else {
        header("Location: index.php");
        exit;
    }
?>

<?php if($isJamendoAlbum): ?>
    <!-- JAMENDO ALBUM VIEW - Loaded via JavaScript -->
    <div class="entityInfo">
        <div class="leftSection">
            <img id="jamendoAlbumArt" src="" alt="Album" style="width: 200px; height: 200px; background: #333;">
        </div>
        <div class="rightSection">
            <h2 id="jamendoAlbumTitle">Loading...</h2>
            <p id="jamendoArtistName" class="pointer" style="cursor: pointer;">Loading...</p>
            <p id="jamendoSongCount">Loading...</p>
        </div>
    </div>

    <div class="tracklistContainer">
        <ul class="tracklist" id="jamendoTracklist">
            <li style="padding: 20px; text-align: center; color: #999;">Loading tracks...</li>
        </ul>
    </div>

    <script>
        $(document).ready(function() {
            var jamendoAlbumId = <?php echo $jamendoAlbumId; ?>;

            // Set Jamendo Client ID if not set
            if (!JamendoClient.clientId) {
                JamendoClient.setClientId('<?php echo defined("JAMENDO_CLIENT_ID") ? JAMENDO_CLIENT_ID : "YOUR_JAMENDO_CLIENT_ID_HERE"; ?>');
            }

            // Fetch album detail from Jamendo API
            JamendoClient.request('albums', { id: jamendoAlbumId }, function(data) {
                if (!data.results || data.results.length === 0) {
                    $('#jamendoTracklist').html('<li style="padding: 20px; color: red;">Album not found</li>');
                    return;
                }

                var albumData = data.results[0];
                
                // Update album header
                $('#jamendoAlbumTitle').text(albumData.name || 'Unknown Album');
                $('#jamendoArtistName').text(albumData.artist_name || 'Unknown Artist');
                
                var imgSrc = albumData.image ? "Includes/Handlers/ajax/proxy.php?url=" + encodeURIComponent(albumData.image) : 'assets/images/artwork/default.jpg';
                $('#jamendoAlbumArt').attr('src', imgSrc).css('display', 'block');

                // Fetch tracks for this album
                JamendoClient.request('tracks', { album_id: jamendoAlbumId, limit: 200 }, function(tracksData) {
                    var tracks = tracksData.results || [];
                    
                    if (tracks.length === 0) {
                        $('#jamendoSongCount').text('0 songs');
                        $('#jamendoTracklist').html('<li style="padding: 20px; text-align: center; color: #999;">No tracks found</li>');
                        return;
                    }

                    $('#jamendoSongCount').text(tracks.length + ' songs');
                    
                    // Store tracks globally for playback
                    window.albumTracks = [];
                    var tracklistHtml = '';
                    
                    tracks.forEach(function(jamendoTrack, index) {
                        var track = TrackMapper.fromJamendo(jamendoTrack);
                        window.albumTracks.push(track);
                        
                        var duration = TrackMapper.formatDuration(track.duration);
                        var count = index + 1;

                        tracklistHtml += '<li class="tracklistRow">';
                        tracklistHtml += '  <div class="trackCount">';
                        tracklistHtml += '    <img class="play" src="assets/images/Icons/play-white.png" onclick="setTrack(window.albumTracks[' + index + '], window.albumTracks, true)" style="cursor: pointer;">';
                        tracklistHtml += '    <span class="trackNumber">' + count + '.</span>';
                        tracklistHtml += '  </div>';
                        tracklistHtml += '  <div class="trackInfo">';
                        tracklistHtml += '    <span class="trackName">' + TrackRenderer.escapeHtml(track.title) + '</span>';
                        tracklistHtml += '    <span class="artistName">' + TrackRenderer.escapeHtml(track.artist) + '</span>';
                        tracklistHtml += '  </div>';
                        tracklistHtml += '  <div class="trackOptions">';
                        tracklistHtml += '    <input type="hidden" class="songId" value="' + track.id + '">';
                        tracklistHtml += '    <img class="optionButton" src="assets/images/Icons/more.png" onclick="showOptionsMenu(this)">';
                        tracklistHtml += '  </div>';
                        tracklistHtml += '  <div class="trackDuration">';
                        tracklistHtml += '    <span class="duration">' + duration + '</span>';
                        tracklistHtml += '  </div>';
                        tracklistHtml += '</li>';
                    });

                    $('#jamendoTracklist').html(tracklistHtml);
                }, function(error) {
                    console.error('Failed to fetch album tracks:', error);
                    $('#jamendoTracklist').html('<li style="padding: 20px; color: red;">Error loading tracks</li>');
                });
            }, function(error) {
                console.error('Failed to fetch album:', error);
                $('#jamendoTracklist').html('<li style="padding: 20px; color: red;">Error loading album</li>');
            });
        });
    </script>

    <nav class="optionsMenu">
        <input type="hidden" class="songId">
        <?php echo Playlist::getPlaylistsDropdown($con, $userLoggedIn->getUsername()); ?>
    </nav>

<?php else: ?>
    <!-- LOCAL DATABASE ALBUM VIEW (Original Code) -->
    <div class="entityInfo">
        <div class="leftSection">
            <img src="<?php echo $album->getArtworkPath(); ?>" alt="Album">
        </div>

        <div class="rightSection">
            <h2><?php echo $album->getTitle(); ?></h2>
            <p class="pointer" onclick="openPage('artist.php?id=<?php echo $artistId; ?>')">By <?php echo $artist->getName(); ?></p>
            <p><?php echo $album->getNumberOfSongs(); ?> songs</p>
        </div>
    </div>

    <div class="tracklistContainer">
        <ul class="tracklist">
            <?php
                $songIdArray = $album->getSongIds();

                $count = 1;
                foreach($songIdArray as $songId) {
                    $albumSong = new Song($con, $songId);
                    $albumArtist = $albumSong->getArtist();

                    echo    "<li class='tracklistRow'>
                                <div class='trackCount'>
                                    <img class='play' src='assets/images/Icons/play-white.png' onclick='setTrack(\"" . $albumSong->getId() . "\", tempPlaylist, true)'>
                                    <span class='trackNumber'>$count.</span>
                                </div>
                                <div class='trackInfo'>
                                    <span class='trackName'>" . $albumSong->getTitle() . "</span>
                                    <span class='artistName'>" . $albumArtist->getName() . "</span>
                                </div>
                                <div class='trackOptions'>
                                    <input type='hidden' class='songId' value='" . $albumSong->getId() . "'>
                                    <img class='optionButton' src='assets/images/Icons/more.png' onclick='showOptionsMenu(this)'>
                                </div>
                                <div class='trackDuration'>
                                    <span class='duration'>" . $albumSong->getDuration() . "</span>
                                </div>  
                            </li>";
                    
                    $count++;
                }
            ?>

            <script>
                var tempSongIds = '<?php echo json_encode($songIdArray); ?>';
                tempPlaylist = JSON.parse(tempSongIds);
            </script>
        </ul>
    </div>

    <nav class="optionsMenu">
        <input type="hidden" class="songId">
        <?php echo Playlist::getPlaylistsDropdown($con, $userLoggedIn->getUsername()); ?>
    </nav>

<?php endif; ?>

<?php
// Only load database album if NOT Jamendo
if (!$isJamendoAlbum) {
    $album = new Album($con, $albumId);
    $artist = $album->getArtist();
    $artistId = $artist->getId();
}
?>