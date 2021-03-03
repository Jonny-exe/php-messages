<?php

// Database functions 
// function does_user_exist1($conn, $name)
// {
//     $q = "SELECT * from users where name = '$name'";
//     $result = $conn->query($q);
//     $result = $result->fetchAll();
//     if (count($result) == 0) {
//         return false;
//     } else {
//         return true;
//     }
// }

function does_user_exist($conn, $name)
{
    $q = "SELECT * from users where name = ?";
    $prepare = $conn->prepare($q);
    $prepare->execute(array($name));
    $result = $prepare->fetchAll();
    if (count($result) == 0) {
        return false;
    } else {
        return true;
    }
}

function is_friend_already_added($conn, $uid, $friend_name)
{
    $q = "SELECT * from friends where name = ? and uid = ?";
    $prepare = $conn->prepare($q);
    $prepare->execute(array($friend_name, $uid));
    $result = $prepare->fetchAll();
    if (count($result) == 0) {
        return false;
    } else {
        return true;
    }
}

function does_friend_exist($conn, $friend_name)
{
    $q = "SELECT * from users where name = ?";
    $prepare = $conn->prepare($q);
    $prepare->execute(array($friend_name));
    $result = $prepare->fetchAll();
    if (count($result) == 0) {
        return false;
    } else {
        return true;
    }
}


function get_messages($conn, $friend, $name)
{
    $q = "select sender_text, receiver_text, sender from messages where (sender=? and receiver=?) or (sender=? and receiver=?)";
    $prepare = $conn->prepare($q);
    $prepare->execute(array($name, $friend, $friend, $name));
    $result = $prepare->fetchAll();
    return $result;
}

function insert_user($conn, $name, $public_key)
{
    $q = "insert into users (name, public_key) values (?, ?)";
    $prepare = $conn->prepare($q);
    $prepare->execute(array($name, $public_key));
}

function insert_friend($conn, $uid, $friend_name)
{
    $q = "insert into friends (name, uid) values (?, ?)";
    $prepare = $conn->prepare($q);
    $prepare->execute(array($friend_name, $uid));
}

function insert_message($conn, $sender_text, $receiver_text, $sender, $receiver)
{
    $sender_text = sodium_bin2base64($sender_text, 1);
    $receiver_text = sodium_bin2base64($receiver_text, 1);
    $q = "insert into messages (sender_text, receiver_text, sender, receiver) values (?,?,?,?)";
    $prepare = $conn->prepare($q);
    $prepare->execute(array($sender_text, $receiver_text, $sender, $receiver));
}

function get_public_key($name, $conn, $bin = true)
{
    //TODO: change $conn, to first place
    $q = "SELECT public_key from users where name = ?";
    $prepare = $conn->prepare($q);
    $prepare->execute(array($name));
    $result = $prepare->fetch()[0];
    if ($bin == true) {
        $result = sodium_base642bin($result, 1);
    }
    return $result;
}

function getUID($conn, $name)
{
    $q = "select uid from users where name=? order by uid desc";
    $prepare = $conn->prepare($q);
    $prepare->execute(array($name));
    $result = $prepare->fetch()[0];
    return $result;
}

function get_friends($conn, $uid)
{
    $q = "select name, uid from friends where uid = ? ";
    $result = $conn->prepare($q);
    // $result = $conn->query($q);
    $result->execute(array($uid));
    if (!$result) {
        return [];
    }
    $result = $result->fetchAll();
    return $result;
}

function remove_friend($conn, $uid, $friend, $name)
{
    $q = "delete from friends where uid=? and name=?";
    $prepare = $conn->prepare($q);
    $prepare->execute(array($uid, $friend));

    $q = "delete from messages where (sender=? and receiver=?) or (sender=? and receiver=?)";
    $prepare = $conn->prepare($q);
    $prepare->execute(array($name, $friend, $friend, $name));
}
