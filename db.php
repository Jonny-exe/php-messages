<?php

// Database functions 
function does_user_exist($conn, $name)
{
    $q = "SELECT * from users where name = '$name'";
    $result = $conn->query($q);
    $result = $result->fetchAll();
    if (count($result) == 0) {
        return false;
    } else {
        return true;
    }
}

function is_friend_already_added($conn, $uid, $friend_name)
{
    $q = "SELECT * from friends where name = '$friend_name' and uid = $uid";
    $result = $conn->query($q);
    $result = $result->fetchAll();
    if (count($result) == 0) {
        return false;
    } else {
        return true;
    }
}

function does_friend_exist($conn, $friend_name)
{
    $q = "SELECT * from users where name = '$friend_name'";
    $result = $conn->query($q);
    $result = $result->fetchAll();
    if (count($result) == 0) {
        return false;
    } else {
        return true;
    }
}


function get_messages($conn, $friend, $name)
{
    $q = "select sender_text, receiver_text, sender from messages where (sender='$name' and receiver='$friend') or (sender='$friend' and receiver='$name')";
    $messages = $conn->query($q);
    $messages = $messages->fetchAll();
    return $messages;
}

function insert_user($conn, $name, $public_key)
{
    $q = "insert into users (name, public_key) values ('$name', '$public_key')";
    $conn->query($q);
}

function insert_friend($conn, $uid, $friend_name)
{
    $q = "insert into friends (name, uid) values ('$friend_name', $uid)";
    $conn->query($q);
}

function insert_message($conn, $sender_text, $receiver_text, $sender, $receiver)
{
    $sender_text = sodium_bin2base64($sender_text, 1);
    $receiver_text = sodium_bin2base64($receiver_text, 1);
    $q = "insert into messages (sender_text, receiver_text, sender, receiver) values ('$sender_text','$receiver_text', '$sender', '$receiver')";
    $conn->query($q);
}

function get_public_key($name, $conn, $bin = true)
{
    $result = $conn->query("SELECT public_key from users where name = '$name'");
    $result = $result->fetch()[0];
    if ($bin == true) {
        $result = sodium_base642bin($result, 1);
    }
    return $result;
}

function getUID($conn, $name)
{
    $uid = $conn->query("select uid from users where name='$name' order by uid desc");
    $uid = $uid->fetch();
    return $uid[0];
}

function get_friends($conn, $uid)
{
    $result = $conn->query("select name, uid from friends where uid=$uid");
    if (!$result) {
        return [];
    }
    $result = $result->fetchAll();
    return $result;
}
