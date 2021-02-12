<?php
$dbhost = "database";
$dbuser = "root";
$dbpass = "tiger";

print $dbhost . "\n";
print $dbuser . "\n";
print $dbpass . "\n";

$conn = new PDO("mysql:host=$dbhost", $dbuser, $dbpass);

print "Works";