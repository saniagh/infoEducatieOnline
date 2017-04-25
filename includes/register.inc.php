<?php

include_once 'db_connect.php';
include_once 'psl-config.php';

$error_msg = "";

// Here we handle the data received when a user registers

if (isset($_POST['username'], $_POST['email'], $_POST['p'], $_POST['name'], $_POST['cnp'], $_POST['serie'], $_POST['number'], $_POST['workplace_info'])) {
    $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $cnp = filter_input(INPUT_POST, 'cnp', FILTER_SANITIZE_STRING);
    $serie = filter_input(INPUT_POST, 'serie', FILTER_SANITIZE_STRING);
    $number = filter_input(INPUT_POST, 'number', FILTER_SANITIZE_STRING);
    $workplace_info = filter_input(INPUT_POST, 'workplace_info', FILTER_SANITIZE_STRING);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // Not a valid email
        $error_msg .= '<p class="error">Te rugam sa introduci un email valid.</p>';
    }

    $password = filter_input(INPUT_POST, 'p', FILTER_SANITIZE_STRING);
    if (strlen($password) != 128) {
        // The hashed pwd should be 128 characters long.
        // If it's not, something really odd has happened
        $error_msg .= '<p class="error">Te rugam sa introduci o parola valida.</p>';
    }
    //checking everything after password

    if (!is_numeric($cnp) || strlen($cnp)!=13)
    {
        $error_msg .= '<p class="error">Te rugam sa introduci un cod numeric personal valid.</p>';
    }

    if (!is_numeric($number) || strlen($number)!=6)
    {
        $error_msg .= '<p class="error">Te rugam sa introduci un numar valid de buletin. Il poti gasi dupa serie pe cartea dumneavoastra de identitate.</p>';
    }

    if (strlen($serie)!=2){
        $error_msg .= '<p class="error">Te rugam sa introduci o serie de buletin valida.</p>';
    }

    // Username validity and password validity have been checked client side.
    // This should should be adequate as nobody gains any advantage from
    // breaking these rules.
    //

    $prep_stmt = "SELECT id FROM members WHERE email = ? LIMIT 1";
    $stmt = $mysqli->prepare($prep_stmt);

    if ($stmt) {
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            // A user with this email address already exists
            $error_msg .= '<p class="error">Un utilizator cu acest email exista deja!</p>';
        }
    } else {
        $error_msg .= '<p class="error">Eroare la introducere in baza de date. Te rugam sa incerci din nou.</p>';
    }

    if (empty($error_msg)) {
        // Create a random salt
        $random_salt = hash('sha512', uniqid(openssl_random_pseudo_bytes(16), TRUE));

        // Create salted password
        $password = hash('sha512', $password . $random_salt);

        // Insert the new user into the database
        if ($insert_stmt = $mysqli->prepare("INSERT INTO members (username, email, password, salt, name, cnp, serie,
        number, workplace_info) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")) {
            $insert_stmt->bind_param('sssssssss', $username, $email, $password, $random_salt , $name, $cnp, $serie, $number, $workplace_info);
            // Execute the prepared query.
            if (! $insert_stmt->execute()) {
                header('Location: ../error.php?err=Registration failure: Insert error');
                exit();
            }
        }
        header('Location: ./register_success.php');
        exit();
    }
}