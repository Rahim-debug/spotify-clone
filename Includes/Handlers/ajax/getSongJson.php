<?php
    include ("../../config.php");

    if(isset($_POST['songId'])) {
        $songId = $_POST['songId'];
        $query = mysqli_query($con, "SELECT * FROM songs WHERE id = '$songId'");
        $resultArray = mysqli_fetch_array($query);
        
        // Convert relative paths to absolute paths from webroot for proper audio playback
        if(!empty($resultArray['path'])) {
            $resultArray['path'] = getAssetPath($resultArray['path']);
        }
        
        echo json_encode($resultArray);
    }
?>