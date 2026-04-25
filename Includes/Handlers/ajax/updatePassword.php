<?php
    include("../../config.php");

    if(!isset($_POST['username'])) {
        echo "ERROR: Could not set username";
        exit();
    }
    
    if(!isset($_POST['oldPassword']) || !isset($_POST['newPassword1']) || !isset($_POST['newPassword2'])) {
        echo "Not all password have been set";
        exit();
    }

    if($_POST['oldPassword'] == "" || $_POST['newPassword1'] == "" || $_POST['newPassword2'] == "") {
        echo "Please fill all passwords";
        exit();
    }

    $username = $_POST['username'];
    $oldPassword = $_POST['oldPassword'];
    $newPassword1 = $_POST['newPassword1'];
    $newPassword2 = $_POST['newPassword2'];

    $oldMD5 = md5($oldPassword);

    // SECURITY: Using prepared statements
    $stmt = $con->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $oldMD5);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows == 0) {
        echo "Password is incorrect";
        exit();
    }

    if($newPassword1 != $newPassword2) {
        echo "Your new passwords do not match";
        exit();
    }

    if(preg_match('/[^a-zA-Z0-9_$!@%&*]/', $newPassword1)) {
        echo "Your new password must not contain illegal special characters.";
        exit();
    }

    if(strlen($newPassword1) > 30 || strlen($newPassword1) < 5) {
        echo "Your new password must be between 5 and 30 characters";
        exit();
    }


    $newMD5 = md5($newPassword1);

    // SECURITY: Using prepared statements
    $stmt = $con->prepare("UPDATE users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $newMD5, $username);
    $stmt->execute();
    echo "Password updated successfully!";
?>