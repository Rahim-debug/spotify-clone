<?php
    include("../../config.php");
    if(isset($_POST['name']) && isset($_POST['username'])) {
        $name = $_POST['name'];
        $username = $_POST['username'];
        $date = date("Y-m-d");

        // SECURITY: Using prepared statements
        $stmt = $con->prepare("INSERT INTO playlists (name, owner, dateCreated) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $username, $date);
        $stmt->execute();
    }
    else {
        echo "Name or Username not passed";
    }
?>