<?php

$dbhost = 'localhost';
$dbuser = 'php-test';
$dbpass = 'password';
$dbname = 'php_test';
$conn = new PDO('mysql:host=' . $dbhost . ';dbname=' . $dbname, $dbuser, $dbpass);
// $conn->query("insert into users (name) values ('hi')");

if ($conn->connect_error) {
	die("Connection failed");
}

function insertUsername($conn, $name)
{
	$conn->query("if not exist (select uid from users where name=$name) insert into users (name) values ('" . $name . "')");
}

if ($_REQUEST['see']) {
	$sql = 'SELECT * FROM users ORDER BY id desc';
	foreach ($conn->query($sql) as $row) {
		print $row;
	}
}

print <<<EOF
<link hred="css/style.css.php" rel="stylesheet" type="text/css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-giJF6kkoqNQ00vy+HMDP7azOuL0xtbfIcaT9wjKHr8RbDVddVHyTfAAsrekwKmP1" crossorigin="anonymous">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.bundle.min.js" integrity="sha384-ygbV9kiqUc6oa4msXn9868pTtWMgiQaeYH7/t7LECLbyPA2x65Kgf80OJFdroafW" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js" integrity="sha384-q2kxQ16AaE6UbzuKqyBE9/u/KzioAlnx2maXQHiDX9d4/zp8Ok3f+M7DPm+Ib6IU" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta1/dist/js/bootstrap.min.js" integrity="sha384-pQQkAEnwaBkjpqZ8RU1fF1AKtTcHJwFl3pblpTlHXybJjHpMYo79HY3hIi4NKxyj" crossorigin="anonymous"></script>

<a href="localhost:3000/index.php?text=hi"> HEllo </a>

<form action=index.php>
    <textarea id=userInput class="form-control" name=username></textarea>
    <input class="btn btn-primary" type=submit value="message">
</form>
EOF;

function getUID($conn, $name)
{
	$uid = $conn->query("select id from users where name='$name' order by id desc limit 1");
	$uid = $uid->fetch();
	return $uid[0];
}

if ($_REQUEST['username']) {
	$name = $_REQUEST['username'];
	print "<h2>User: " . $name . "</h2>";
	insertUsername($conn, $name);
	$uid = getUID($conn, $name);
	print "UID: " . $uid;
	$result = $conn->query("select name, uid from friends where uid='$uid'");
	$result = $result->fetchAll();
	print "<table class='table' border=1>
    <thead><td colspan=2> Your friends </td></thead>";
	foreach ($result as $row) {
		$id = $row['uid'];
		$friendname = $row['name'];
		print <<<EOF
    <tr><td>$id</td><td>$friendname</td></tr>
    EOF;
	}
	print "</table>";
}

print <<<EOF
<form action=actions.php>
    <div class="input-group mb-3">
        <button class="btn btn-outline-secondary" type="submit" id="button-addon1">Button</button>
        <input type="text" name="newFriend" class="form-control" placeholder="" aria-label="Example text with button addon" aria-describedby="button-addon1">
    </div>
</form>
EOF;

print <<<EOF
<style>
body {
    padding: 1%;
}
</style>

EOF;
