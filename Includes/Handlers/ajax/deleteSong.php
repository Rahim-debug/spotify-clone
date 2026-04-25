<?php
    include("../../config.php");

    if(isset($_POST['songId'])) {
        $songId = $_POST['songId'];

        // SECURITY: Using prepared statements
        $stmt = $con->prepare("DELETE FROM songs WHERE id = ?");
        $stmt->bind_param("i", $songId);
        $stmt->execute();

        $stmt = $con->prepare("DELETE FROM playlistssongs WHERE songId = ?");
        $stmt->bind_param("i", $songId);
        $stmt->execute();
    }
    else {
        echo "songId was not passed";
    }
?>