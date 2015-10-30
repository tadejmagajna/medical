<?php

require_once "database.php";
ini_set('display_errors', 1);
class general {

    function authenticate($username, $password) {
        if ($username == null) {
            return array("Authentication" => 0, "Status" => "No username");
        } else if ($password == null) {
            return array("Authentication" => 0, "Status" => "No password");
        }

        $db = new Database();

        $authen = $db->authenticateUser($username, $password);

        if ($authen) {
            return array("Authentication" => True, "Status" => "Login successful");
        } else {
            return array("Authentication" => False, "Status" => "Username or password incorrect");
        }
    }

    function getExtra() {
        if (isset($_SERVER['PATH_INFO'])) {
            $path_org = explode("/", $_SERVER['PATH_INFO']);
            $pat = array();
            foreach ($path_org as $val) {
                if (strlen($val) > 0) {
                    $path[] = $val;
                }
            }
            return $path;
        }
    }

    function okHeader() {
        header('HTTP/1.1 200 OK');
        header('Content-Type: application/json; charset=utf-8');
    }

    function serverError() {
        header('HTTP/1.1 500 Internal Server Error');
        header('Content-Type: application/json; charset=utf-8');
    }

    function unathorizedHeader() {
        header('HTTP/1.1 401 Unauthorized');
        header('Content-Type: application/json; charset=utf-8');
    }

    function badRequestHeader() {
        header('HTTP/1.1 400 Bad request');
        header('Content-Type: application/json; charset=utf-8');
    }

}
