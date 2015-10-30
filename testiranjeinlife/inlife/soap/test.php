<?php
require_once "lib/nusoap.php";
 
function getProd($category) {
    if ($category == "books") {
//         return join(",", array(
//             "The WordPress Anthology",
//             "PHP Master: Write Cutting Edge Code",
//             "Build Your Own Website the Right Way"));
		return array("The WordPress Anthology",
            "PHP Master: Write Cutting Edge Code",
            "Build Your Own Website the Right Way");
    }
    else {
            return "No products listed under that category";
    }
}
 
$server = new soap_server();
$server->configureWSDL('Bookoreader', 'http://testing.inlife.si/');

$server->register("getProd");
// $server->service($HTTP_RAW_POST_DATA);
$server->service(isset($HTTP_RAW_POST_DATA) ? $HTTP_RAW_POST_DATA : '');
?>