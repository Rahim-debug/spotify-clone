<?php
    include("Includes/includedFiles.php");

    if(isset($_GET['query'])) {
        $query = urldecode($_GET['query']);
    }
    else {
        $query = "";
    }
?>

<div class="searchContainer">
    <h4>Search for a song, artist, album, or a playlist</h4>
    <input type="text" class="searchInput" value="<?php echo htmlspecialchars($query); ?>" placeholder="Search..." onfocus="this.value = this.value">
</div>

<script>
    // Refocus input on page reload
    $(".searchInput").focus();
    var temp = $(".searchInput").val();
    $(".searchInput").val("");
    $(".searchInput").val(temp);

    // IIFE to reload page with new query after 2 seconds when stopped typing
    $(function() {
        $(".searchInput").keyup(function() {
            clearTimeout(timer);
            timer = setTimeout(function() {
                var val = $(".searchInput").val();
                openPage("search.php?query=" + val);
            }, 2000);
        });
    });
</script>

<?php
    // If empty query, stop loading the rest of the page
    if($query == "") {
        exit();
    }
?>

<div id="searchResults">
    <!-- Will be populated by JavaScript with Jamendo API data -->
    <p style="text-align: center; padding: 20px; color: #999;">Searching Jamendo...</p>
</div>

<script>
    $(document).ready(function() {
        var searchQuery = "<?php echo addslashes($query); ?>";
        
        if (!searchQuery) return;

        // Set a default Jamendo Client ID if not set
        if (!JamendoClient.clientId) {
            JamendoClient.setClientId('<?php echo defined("JAMENDO_CLIENT_ID") ? JAMENDO_CLIENT_ID : "YOUR_JAMENDO_CLIENT_ID_HERE"; ?>');
        }

        // Perform parallel API calls for tracks and artists
        var tracksData = null;
        var artistsData = null;
        var callsCompleted = 0;

        function renderResults() {
            if (callsCompleted < 2) return; // Wait for both calls to complete

            var html = '';

            // ===== SONGS RESULTS =====
            if (tracksData && tracksData.results && tracksData.results.length > 0) {
                var tracks = tracksData.results.slice(0, 10);
                
                html += '<div class="tracklistContainer borderBottom">';
                html += '<h2>SONGS</h2>';
                html += '<ul class="tracklist">';

                var count = 1;
                window.searchTracks = []; // Store tracks globally for onclick handlers
                
                tracks.forEach(function(jamendoTrack) {
                    var track = TrackMapper.fromJamendo(jamendoTrack);
                    window.searchTracks.push(track);
                    var trackIndex = window.searchTracks.length - 1;

                    var duration = TrackMapper.formatDuration(track.duration);

                    html += '<li class="tracklistRow">';
                    html += '  <div class="trackCount">';
                    html += '    <img class="play" src="assets/images/Icons/play-white.png" onclick="setTrack(window.searchTracks[' + trackIndex + '], window.searchTracks, true)" style="cursor: pointer;">';
                    html += '    <span class="trackNumber">' + count + '.</span>';
                    html += '  </div>';
                    html += '  <div class="trackInfo">';
                    html += '    <span class="trackName">' + TrackRenderer.escapeHtml(track.title) + '</span>';
                    html += '    <span class="artistName">' + TrackRenderer.escapeHtml(track.artist) + '</span>';
                    html += '  </div>';
                    html += '  <div class="trackOptions">';
                    html += '    <input type="hidden" class="songId" value="' + track.id + '">';
                    html += '    <img class="optionButton" src="assets/images/Icons/more.png" onclick="showOptionsMenu(this)">';
                    html += '  </div>';
                    html += '  <div class="trackDuration">';
                    html += '    <span class="duration">' + duration + '</span>';
                    html += '  </div>';
                    html += '</li>';

                    count++;
                });

                html += '</ul>';
                html += '</div>';
            }



            if (!html) {
                html = '<p class="noResults" style="padding: 20px;">No results found for "' + TrackRenderer.escapeHtml(searchQuery) + '"</p>';
            }

            $('#searchResults').html(html);
        }

        // Call 1: Search Tracks
        JamendoClient.searchTracks(searchQuery, 10, 0, function(data) {
            tracksData = data;
            callsCompleted = 2; // Override to allow immediate rendering
            console.log('Tracks loaded:', data);
            renderResults();
        }, function(error) {
            console.error('Track search error:', error);
            callsCompleted = 2;
            renderResults();
        });
    });
</script>

<nav class="optionsMenu">
    <input type="hidden" class="songId">
    <?php echo Playlist::getPlaylistsDropdown($con, $userLoggedIn->getUsername()); ?>
</nav>

    <div class="tracklistContainer borderBottom">
        <h2>LOCAL SONGS</h2>
        <ul class="tracklist">
            <?php
                $songsQuery = mysqli_query($con, "SELECT id FROM songs WHERE title LIKE '$query%' LIMIT 10");

                if(mysqli_num_rows($songsQuery) == 0) {
                    echo "<p class='noResults'>Oops, No local songs found matching: " . $query . "</p>";
                }

                $songIdArray = array();
                $count = 1;
                while($row = mysqli_fetch_array($songsQuery)) {
                    array_push($songIdArray, $row['id']);
                    $albumSong = new Song($con, $row['id']);
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

    <div class="artistsContainer borderBottom">
        <h2>LOCAL ARTISTS</h2>
        <?php
        $artistsQuery = mysqli_query($con, "SELECT id FROM artists WHERE name LIKE '$query%' LIMIT 10");
        if(mysqli_num_rows($artistsQuery) == 0) {
            echo "<p class='noResults'>Oops, No artists found matching: " . $query . "</p>";
        }

        while($row = mysqli_fetch_array($artistsQuery)) {
            $artistRow = new Artist($con, $row['id']);

            echo    "<div class='artistRow'>
                        <div class='artistRowName'>
                            <span class='pointer' onclick='openPage(\"artist.php?id=" . $artistRow->getId() . "\")'>
                                "
                                . $artistRow->getName() .
                                "
                            </span>
                        </div>
                    </div>";
        }
    ?>
</div>

<div class="gridViewContainer">
    <h2>ALBUMS</h2>
    <?php 
        $albumQuery = mysqli_query($con, "SELECT * FROM albums WHERE title LIKE '$query%' LIMIT 10");

        if(mysqli_num_rows($albumQuery) == 0) {
            echo "<p class='noResults'>Oops, No albums found matching: " . $query . "</p>";
        }

        while ($row = mysqli_fetch_array($albumQuery)) {
            echo    "<div class='gridViewItem'>
                        <span onclick='openPage(\"album.php?id=" . $row['id'] . "\");'>
                            <img src='" . getAssetPath($row['artworkPath']) . "'>
                            <div class='gridViewInfo'>"
                                . $row['title'] .
                            "</div>
                        </span>
                    </div>";
        }
    ?>
</div>

<nav class="optionsMenu">
    <input type="hidden" class="songId">
    <?php echo Playlist::getPlaylistsDropdown($con, $userLoggedIn->getUsername()); ?>
</nav>