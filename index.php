<?php
require_once("vendor/autoload.php");
session_start();

$dbhost = "localhost";
$port = "3306";
$dbuser = "php-test";
$dbpass = "password";
//TODO: manage exceptions

$conn = new PDO("mysql:host=$dbhost;port=$port", $dbuser, $dbpass);
function setup_db($conn)
{
	$q = "CREATE database if not exists php_test";
	$conn->query($q);

	$q = "USE php_test";
	$conn->query($q);

	$q = "set names 'utf8'";

	$conn->query($q);

	$q = "CREATE table if not exists users (uid int(11) auto_increment, name varchar(30), public_key longtext, primary key (uid), unique key (name))";
	$conn->query($q);

	$q = "CREATE table if not exists messages (sender_text longtext, receiver_text longtext, receiver varchar(30),sender varchar(30))";
	$conn->query($q);

	$q = "CREATE table if not exists friends (name varchar(30), uid int(11))";
	$conn->query($q);

	$conn->query($q);
}

setup_db($conn);

$name = $_SERVER['REMOTE_ADDR'];
$name = "test3";
$name = str_replace(".", "_", $name);

if (isset($_REQUEST['friend'])) {
	$_SESSION['friend'] = $_REQUEST['friend'];
	$friend = $_REQUEST['friend'];
}

$does_user_exist = does_user_exist($conn, $name);
$keypair = create_keypair_if_not_exist($conn, $does_user_exist, $name);

use ReallySimpleJWT\Token;

try {
	if (!isset($_SESSION['jwt'])) {
		throw new Exception('New Token');
	}
	$secret = file_get_contents("secret.txt");
	$token  = $_SESSION['jwt'];

	$result = Token::validate($token, $secret);

	if ($result != 1) {
		print_alert();
		throw new Exception('New Token');
	}

	$header = Token::getHeader($token, $secret);
	$payload = Token::getPayload($token, $secret);

	if ($payload['user'] != $name) {
		throw new Exception('New Token');
	}
} catch (Exception $e) {
	$payload = create_jwt_token($conn, $name);
}

function print_alert()
{
	print '<div class="alert alert-danger" role="alert">Something went wrong</div>';
}

function create_jwt_token($conn, $name)
{
	unset($_SESSION['jwt']);
	$public_key_user = get_public_key($name, $conn, false);
	$payload = [
		'iat' => time(),
		'exp' => time() + 3600,
		'user' => $name,
		'public_key' => $public_key_user,
	];

	$secret = file_get_contents("secret.txt");

	$token = Token::customPayload($payload, $secret);

	$_SESSION['jwt'] = $token;

	return $payload;
}


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

function get_public_key($name, $conn, $bin)
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

function create_keypair_if_not_exist($conn, $does_user_exist, $name)
{
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




?>
<link hred="css/style.css.php" rel="stylesheet" type="text/css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js" integrity="sha384-q2kxQ16AaE6UbzuKqyBE9/u/KzioAlnx2maXQHiDX9d4/zp8Ok3f+M7DPm+Ib6IU" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.min.js" integrity="sha384-pQQkAEnwaBkjpqZ8RU1fF1AKtTcHJwFl3pblpTlHXybJjHpMYo79HY3hIi4NKxyj" crossorigin="anonymous"></script>



<form action=index.php>
	<div class="input-group">
		<input class="btn btn-primary" type=submit value="Send message">
		<textarea id=userInput class="form-control" name=text></textarea>
	</div>
</form>

<?php

if (isset($_REQUEST['text'])) {
	$text = $_REQUEST['text'];
	$friend = $_SESSION['friend'];
	$public_key_user = public_key_into_bin($payload["public_key"]);
	$public_key_friend = get_public_key($friend, $conn, true);

	$encrypted_text_user = encrypt_message($text, $public_key_user);
	$encrypted_text_friend = encrypt_message($text, $public_key_friend);
	if ($encrypted_text_user != false AND $encrypted_text_friend != false) {
		insert_message($conn, $encrypted_text_user, $encrypted_text_friend, $name, $friend);
	}

	$new_url = strip_param_from_url("text");
	set_url($new_url);
}

function strip_param_from_url($param)
{
	$url = $_SERVER['REQUEST_URI'];
	$base_url = strtok($url, '?');              // Get the base url
	$parsed_url = parse_url($url);              // Parse it 
	$query = $parsed_url['query'];              // Get the query string
	parse_str($query, $parameters);           // Convert Parameters into array
	unset($parameters[$param]);               // Delete the one you want
	$new_query = http_build_query($parameters); // Rebuilt query string
	return $base_url . '?' . $new_query;            // Finally url is ready
}

function set_url($url)
{
	echo ("<script>history.replaceState({},'','$url');</script>");
}

function add_new_friend($conn, $name)
{
	$friend_name = $_REQUEST['new_friend'];
	$uid = getUID($conn, $name);

	$is_friend_added = is_friend_already_added($conn, $uid, $friend_name);
	$does_friend_exist = does_friend_exist($conn, $friend_name);
	if ($is_friend_added == false AND $does_friend_exist == true) {
		insert_friend($conn, $uid, $friend_name);
	} else {
		print '<div class="alert alert-danger" role="alert"> Friend already added or friend doesn\'t exist</div>';
	}

	$new_url = strip_param_from_url("new_friend");
	set_url($new_url);
}

if (isset($_REQUEST['new_friend'])) {
	add_new_friend($conn, $name);
}

function get_messages($conn, $friend, $name)
{
	$q = "select sender_text, receiver_text, sender from messages where (sender='$name' and receiver='$friend') or (sender='$friend' and receiver='$name')";
	$messages = $conn->query($q);
	$messages = $messages->fetchAll();
	return $messages;
}

print "<h2>User: " . $name . "</h2>";
if (isset($_SESSION['friend'])) {
	$friend = $_SESSION['friend'];
	$uid = getUID($conn, $name);
	$messages = get_messages($conn, $friend, $name);

	print "<table class='table' border=1>
	<thead><td colspan=2> Messages </td></thead>";
	render_messages($messages, $name, $keypair);
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

function get_friends($conn, $uid)
{
	$result = $conn->query("select name, uid from friends where uid=$uid");
	if (!$result) {
		return [];
	}
	$result = $result->fetchAll();
	return $result;
}

if (isset($friend)) {
	print "<h2>Friend: " . $friend . "</h2>";
}

function print_friends($conn, $name)
{
	$uid = getUID($conn, $name);
	$friends = get_friends($conn, $uid);
	print "<table class='table' border=1>
    <thead><td colspan=2> Your friends </td></thead>";
	if ($friends) {
		foreach ($friends as $friend) {
			$vars = array(
				'$id' => $friend['uid'],
				'$friendname' => $friend['name'],
			);
			$line = '<tr><td>$id</td><td><a href="index.php?friend=$friendname"> $friendname </a></td></tr>';
			$line = strtr($line, $vars);
			print $line;
		}
	}
	print "</table>";
}
print_friends($conn, $name);

?>

<form action=index.php>
	<div class="input-group mb-3">
		<button class="btn btn-primary" type="submit" id="button-addon1">Add Friend</button>
		<input type="text" name="new_friend" class="form-control" placeholder="" aria-label="Example text with button addon" aria-describedby="button-addon1">
	</div>
</form>
<style>
	body {
		padding: 1%;
	}
</style>