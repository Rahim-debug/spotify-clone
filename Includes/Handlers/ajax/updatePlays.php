<?php
    include("../../config.php");

    if(isset($_POST['songId'])) {
        $songId = $_POST['songId'];
        
        // SECURITY: Using prepared statements
        $stmt = $con->prepare("UPDATE songs SET plays = plays + 1 WHERE id = ?");
        $stmt->bind_param("i", $songId);
        $stmt->execute();
    }
?>