<?php
    include("../../config.php");

    if(isset($_POST['playlistId']) && isset($_POST['songId'])) {
        $playlistId = $_POST['playlistId'];
        $songId = $_POST['songId'];

        // SECURITY: Using prepared statements to check for duplicates
        $stmt = $con->prepare("SELECT songId FROM playlistssongs WHERE playlistId = ? AND songId = ?");
        $stmt->bind_param("ii", $playlistId, $songId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if($result->num_rows > 0) {
            // Duplicate in same playlist
            exit();
        }

        // Get last order to put the song in
        $stmt = $con->prepare("SELECT MAX(playlistOrder) + 1 AS playlistOrder FROM playlistssongs WHERE playlistId = ?");
        $stmt->bind_param("i", $playlistId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_array(MYSQLI_ASSOC);
        $order = $row['playlistOrder'];

        // SECURITY: Using prepared statements for INSERT
        $stmt = $con->prepare("INSERT INTO playlistssongs (songId, playlistId, playlistOrder) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $songId, $playlistId, $order);
        $stmt->execute();
    }
    else {
        echo "playlistId or songId was not passed";
    }
?>