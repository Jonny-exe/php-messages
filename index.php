<?php

session_start();

$dbhost = 'localhost';
$dbuser = 'php-test';
$dbpass = 'password';
$dbname = 'php_test';
$conn = new PDO('mysql:host=' . $dbhost . ';dbname=' . $dbname, $dbuser, $dbpass);
// $conn->query("insert into users (name) values ('hi')");

if ($conn->connect_error) {
	die("Connection failed");
}

if ($_REQUEST['username']) {
	$_SESSION['username'] = $_REQUEST['username'];
}

if ($_REQUEST['new_friend']) {
	$_SESSION['new_friend'] = $_REQUEST['new_friend'];
}

if ($_REQUEST['friend']) {
	$_SESSION['friend'] = $_REQUEST['friend'];
}



function insertUsername($conn, $name)
{
	$q = "insert into users (name) values ('$name')";
	$conn->query($q);
}

function insertFriend($conn, $uid, $friend_name)
{
	$q = "insert into friends (name, uid) values ('$friend_name', $uid)";
	$conn->query($q);
}

if ($_SESSION['see']) {
	$sql = 'SELECT * FROM users ORDER BY id desc';
	foreach ($conn->query($sql) as $row) {
		print $row;
	}
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

function insert_message($conn, $text, $sender, $receiver)
{
	$p = "insert into messages (text, sender, receiver) values ('$text', '$sender', '$receiver')";
	$conn->query($p);
}
if ($_REQUEST['text']) {
	$text = $_REQUEST['text'];
	$name = $_SESSION['username'];
	$friend = $_SESSION['friend'];
	insert_message($conn, $text, $name, $friend);
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

function getUID($conn, $name)
{
	$uid = $conn->query("select uid from users where name='$name' order by uid desc");
	$uid = $uid->fetch();
	return $uid[0];
}

if ($_SESSION['new_friend']) {
	$friend_name = $_SESSION['new_friend'];
	$name = $_SESSION['username'];
	$uid = getUID($conn, $name);
	insertFriend($conn, $uid, $friend_name);
}

function get_messages($conn, $friend, $name)
{
	$q = "select text from messages where sender='$name' and receiver='$friend'";
	$messages = $conn->query($q);
	if ($messages != null) {
		$messages = $messages->fetchAll();
	}
	return $messages;
}

if ($_SESSION['friend']) {
	$friend = $_SESSION['friend'];
	print "<h2>User: " . $name . "</h2>";
	$uid = getUID($conn, $name);
	$messages = get_messages($conn, $friend, $name);

	print "<table class='table' border=1>
	<thead><td colspan=2> Messages </td></thead>";
	render_messages($messages, $name);
	print "</table>";
}

function render_messages($messages, $name)
{
	if ($messages != null)
		foreach ($messages as $message) {
			$text = $message["text"];
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
	$result = $result->fetchAll();
	return $result;
}

print "<h2>Friend: " . $friend . "</h2>";
if ($_SESSION['username']) {
	$name = $_SESSION['username'];
	insertUsername($conn, $name);
	$uid = getUID($conn, $name);
	$friends = get_friends($conn, $uid);
	print "<table class='table' border=1>
    <thead><td colspan=2> Your friends </td></thead>";
	foreach ($friends as $friend) {
		$id = $friend['uid'];
		$friendname = $friend['name'];
		print <<<EOF
    <tr><td>$id</td><td><a href="index.php?username=$name&new_friend=$friend_name&friend=$friendname"> $friendname </a></td></tr>
    EOF;
	}
	print "</table>";
}


?>
<form action=index.php>
	<div class="input-group mb-3">
		<button class="btn btn-primary" type="submit" id="button-addon1">Add Friend</button>
		<input type="text" name="new_friend" class="form-control" placeholder="" aria-label="Example text with button addon" aria-describedby="button-addon1">
	</div>
	<div class="input-group mb-3">
		<button class="btn btn-primary" type="submit" id="button-addon1">Login</button>
		<input type="text" name="username" class="form-control" placeholder="" aria-label="Example text with button addon" aria-describedby="button-addon1">
	</div>
</form>
<style>
	body {
		padding: 1%;
	}
</style>