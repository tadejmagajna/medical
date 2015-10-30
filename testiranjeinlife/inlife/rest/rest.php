<?php 

require_once "getForms.php";
require_once "postForms.php";
require_once "putForms.php";
require_once "deleteForms.php";


// Require https
//if ($_SERVER['HTTPS'] != "on") {
//    $url = "https://". $_SERVER['SERVER_NAME'] . $_SERVER['REQUEST_URI'];
//    header("Location: $url");
//    exit;
//}


if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $get = new getForms();
    $get->handleRequest();
} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $get = new postForms();
    $get->handleRequest();
} else if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
    $get = new putForms();
    $get->handleRequest();
} else if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
    $get = new deleteForms();
    $get->handleRequest();
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allowed: GET, PUT, DELETE, POST');
    echo json_encode(array('status' => 'wrong method', 'allowed' => 'GET, PUT, DELETE, POST'));
}