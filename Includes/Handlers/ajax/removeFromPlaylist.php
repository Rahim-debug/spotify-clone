<?php
    include("../../config.php");

    if(isset($_POST['playlistId']) && isset($_POST['songId'])) {
        $playlistId = $_POST['playlistId'];
        $songId = $_POST['songId'];
        // SECURITY: Using prepared statements
        $stmt = $con->prepare("DELETE FROM playlistssongs WHERE playlistId = ? AND songId = ?");
        $stmt->bind_param("ii", $playlistId, $songId);
        $stmt->execute();
    }
    else {
        echo "playlistId or songId was not passed";
    }
?>