<?php
require_once("vendor/autoload.php");
require_once("db.php");
require_once("sodium_funcs.php");
require_once("render_funcs.php");
session_start();

function setup_db()
{
	$dbhost = "localhost";
	$port = "3306";
	$dbuser = "php-test";
	$dbpass = "password";

	$conn = new PDO("mysql:host=$dbhost;port=$port", $dbuser, $dbpass);

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

	return $conn;
}

$conn = setup_db();

$name = $_SERVER['REMOTE_ADDR'];
// $name = "test3";
$name = str_replace(".", "_", $name);

if (isset($_REQUEST['friend'])) {
	$friend = $_REQUEST['friend'];
	$_SESSION['friend'] = $friend;
}

if (isset($_SESSION['friend'])) {
	$friend = $_SESSION['friend'];
}

$keypair = create_keypair_if_not_exist($conn, $name);

use ReallySimpleJWT\Token;
// unset($_SESSION['jwt']);
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
	send_text($conn, $text, $friend, $name, $payload);
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

if (isset($_REQUEST['new_friend'])) {
	add_new_friend($conn, $name);
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


if (isset($friend)) {
	print "<h2>Friend: " . $friend . "</h2>";
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