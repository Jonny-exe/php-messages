<?php


function print_friends($conn, $name)
{
    $uid = getUID($conn, $name);
    $friends = get_friends($conn, $uid);
    print "<table class='table' border=1>
    <thead><td colspan=4> Your friends </td></thead>";
    if ($friends) {
        foreach ($friends as $friend) {
            $vars = array(
                '$id' => $friend['uid'],
                '$friendname' => $friend['name'],
            );
            $line = '<tr><td>$id</td><td><a href="index.php?friend=$friendname"> $friendname </a></td><td><a href="index.php?remove=$friendname" class="btn-close" aria-label="Close"></a></td></tr>';
            $line = strtr($line, $vars);
            print $line;
        }
    }
    print "</table>";
}


function render_messages($messages, $name, $keypair)
{
    if ($messages != null)
        foreach ($messages as $message) {
            $sender = $message["sender"];
            if ($sender == $name) {
                $text = $message["sender_text"];
            } else {
                $text = $message["receiver_text"];
            }
            $text = sodium_base642bin($text, 1);

            $text = decrypt_message($text, $keypair);
            $sender = $message["sender"];
            if ($sender == $name) {
                $td = "<tr><td>$text</td><td></td></tr>";
            } else {
                $td = "<tr><td></td><td>$text</td></tr>";
            }
            print $td;
        }
    else {
        print "<tr><td> No messages </td></tr>";
    }
}


function add_new_friend($conn, $name)
{
    $friend_name = $_REQUEST['new_friend'];
    $uid = getUID($conn, $name);

    $is_friend_added = is_friend_already_added($conn, $uid, $friend_name);
    $does_friend_exist = does_friend_exist($conn, $friend_name);
    if ($is_friend_added == false and $does_friend_exist == true) {
        insert_friend($conn, $uid, $friend_name);
    } else {
        print '<div class="alert alert-danger" role="alert"> Friend already added or friend doesn\'t exist</div>';
    }

    $new_url = strip_param_from_url("new_friend");
    set_url($new_url);
}

function send_text($conn, $text, $friend, $name, $payload)
{
    $public_key_user = public_key_into_bin($payload["public_key"]);
    $public_key_friend = get_public_key($friend, $conn);

    $encrypted_text_user = encrypt_message($text, $public_key_user);
    $encrypted_text_friend = encrypt_message($text, $public_key_friend);
    if ($encrypted_text_user != false and $encrypted_text_friend != false) {
        insert_message($conn, $encrypted_text_user, $encrypted_text_friend, $name, $friend);
    }

    $new_url = strip_param_from_url("text");
    set_url($new_url);
}
