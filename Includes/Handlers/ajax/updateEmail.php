<?php
    include("../../config.php");

    if(!isset($_POST['username'])) {
        echo "ERROR: Could not set username";
        exit();
    }
    
    if(isset($_POST['email']) && $_POST['email'] != "") {
        $username = $_POST['username'];
        $email = $_POST['email'];

        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo "Email is invalid";
            exit();
        }

        // SECURITY: Using prepared statements
        $stmt = $con->prepare("SELECT email FROM users WHERE email = ? AND username != ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0) {
            echo "Email is already in use";
            exit();
        }

        // SECURITY: Using prepared statements
        $stmt = $con->prepare("UPDATE users SET email = ? WHERE username = ?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        echo "Email updated successfully!";
    }
    else {
        echo "You must provide an email";
        exit();
    }
?> 