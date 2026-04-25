<?php
    include("../../config.php");

    if(isset($_POST['playlistId'])) {
        $playlistId = $_POST['playlistId'];
        // SECURITY: Using prepared statements
        $stmt = $con->prepare("DELETE FROM playlists WHERE id = ?");
        $stmt->bind_param("i", $playlistId);
        $stmt->execute();
        
        $stmt = $con->prepare("DELETE FROM playlistssongs WHERE playlistId = ?");
        $stmt->bind_param("i", $playlistId);
        $stmt->execute();
    }
    else {
        echo "playlistId was not passed";
    }
?>