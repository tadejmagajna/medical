<?php
require_once "lib/nusoap.php";
$client = new nusoap_client("http://localhost/inlife/wsdltest.php");

$error = $client->getError();
if ($error) {
	echo "<h2>Constructor error</h2><pre>" . $error . "</pre>";
}

$result = $client->call("authenticate", array('jani','geslo'));



if ($client->fault) {
	echo "<h2>Fault</h2><pre>";
	print_r($result);
	echo "</pre>";
}
else {
	$error = $client->getError();
	if ($error) {
		echo "<h2>Error</h2><pre>" . $error . "</pre>";
	}
	else {
		echo "<h2>Books</h2><pre>";
		#echo $result;
		echo "</pre>";
		
		echo gettype($result);
		
		echo "<br />";
		foreach ($result as $key){
			echo $key." <br / >";
		}
	}
}


?>