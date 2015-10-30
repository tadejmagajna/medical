<?php
require_once 'database.php';

echo "tukaj sem";

$db = new Database();

$username = 'jani';
$password = 'geslo';

$res = $db->authenUser($username, $password);

print_r($res);
echo $res['Password'];

?>