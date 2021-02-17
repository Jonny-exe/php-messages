<?php
// Sodium functions
function create_keypair()
{
    $keypair = sodium_crypto_box_keypair();
    return $keypair;
}
function encrypt_message($message, $public_key)
{
    try {
        $encrypted_message = sodium_crypto_box_seal($message, $public_key);
    } catch (Exception $e) {
        print_alert();
        $encrypted_message = false;
    }
    return $encrypted_message;
}

function decrypt_message($encrypted_message, $keypair)
{
    $decrypted = sodium_crypto_box_seal_open(
        $encrypted_message,
        $keypair
    );
    return $decrypted;
}

function create_keypair_if_not_exist($conn, $name)
{
    $does_user_exist = does_user_exist($conn, $name);

    if (!$does_user_exist) {
        $keypair = create_keypair();
        $public_key = sodium_crypto_box_publickey($keypair);
        $public_key = sodium_bin2base64($public_key, 1);

        setcookie("keypair_" . $name, $keypair, time() + (86400 * 30));
        insert_user($conn, $name, $public_key);
        return $keypair;
    } else {
        $keypair = $_COOKIE['keypair_' . $name];
        return $keypair;
    }
}

function public_key_into_bin($public_key)
{
    $public_key = sodium_base642bin($public_key, 1);
    return $public_key;
}
