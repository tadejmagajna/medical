<?php

//GET
require_once "general.php";
require_once "database.php";

class getForms extends general {

    private $db;

    public function handleRequest() {
        $extra = $this->getExtra();

        if (count($extra) > 0 && end($extra) == "authenticate") {
            $result = $this->authenticate(isset($_GET['usn']) ? $_GET['usn'] : null, isset($_GET['pass']) ? $_GET['pass'] : null);

            if (!$result['Authentication']) {
                $this->unathorizedHeader();
                echo json_encode($result);
                exit;
            }

            $this->db = new Database();

            switch ($extra[0]) {
                case "login":
                    //url: ../login/authenticate?...
                    //params: usn, pass
                    $this->onLogin($_GET['usn']);
                    break;
                case "users":
                    //url: ../users/authenticate?...
                    //params: id, usn, pass
                    //check if id set
                    if ($extra[1] == "all") {
                        $this->okHeader();
                        echo json_encode(array("Authentication" => TRUE, "Users" => $this->db->getAllUsers(), "Status" => "Successful"));
                        exit();
                    }

                    if (!isset($_GET['id'])) {
                        $this->badRequestHeader();
                        echo json_encode(array("Authentication" => TRUE, "User" => NULL, "Status" => "Missing user id parameter"));
                    } else {
                        $this->onUserDetails($_GET['id']);
                    }
                    break;
                case "visits":
                    //url: ../visits/authenticate?...
                    //params: usn, pass, uid, from
                    if (!isset($_GET['uid'])) {
                        $this->badRequestHeader();
                        echo json_encode(array("Authentication" => TRUE, "Visits" => NULL, "Status" => "Missing user id parameter"));
                    } else {
                        $this->onGetVisits($_GET['uid'], isset($_GET['from']) ? $_GET['from'] : "1980-01-01 01:00:00");
                    }
                    break;
                case "tasks":
                    //url: .../tasks/authenticate?..
                    //params: usn, pass, vid, uid
                    if (!isset($_GET['vid']) || !isset($_GET['uid'])) {
                        $this->badRequestHeader();
                        echo json_encode(array("Authentication" => TRUE, "Tasks" => NULL, "Status" => "Missing parameters"));
                    } else {
                        $this->onGetTasks($_GET['vid'], $_GET['uid']);
                    }
                    break;
                case "address":
                    //this is for all addresses
                    if ($extra[1] == "all") {
                        $addresses = $this->db->getAddress();
                    } else if ($extra[1] == "user") {
                        //this is for all addresses for a specific user
                        if (!isset($_GET['uid'])) {
                            $this->badRequestHeader();
                            echo json_encode(array("Authentication" => TRUE, "Address" => NULL, "Status" => "Missing parameters"));
                            exit();
                        } else {
                            $addresses = $this->db->getUserAddress($_GET['uid']);
                        }
                    }
                    //add Postal code details to address for easier parsing on Android
                    foreach ($addresses as $row => $address) {
                        $postal = $this->db->getPostalCode($address['Postal_code_id']);
                        unset($address['Postal_code_id']);
                        $address['Postal'] = $postal[0];
                        $addresses[$row] = $address;
                    }
                    $this->okHeader();
                    echo json_encode(array("Authentication" => TRUE, "Address" => $addresses, "Status" => "Successful"));
                    break;
                case "postal":
                    if ($extra[1] == "all") {
                        $postal = $this->db->getPostalCode();
                        $this->okHeader();
                        echo json_encode(array("Authentication" => TRUE, "Postal" => $postal, "Status" => "Successful"));
                        exit();
                    }
                    break;
                case "device":
                    if ($extra[1] == "owner") {
                        if(!isset($_GET['hwKey'])){
                            $this->badRequestHeader();
                            echo json_encode(array("Authentication" => TRUE, "User" => NULL, "Status" => "Missing parameters"));
                        }else{
                            $userID = $this->db->getDeviceID($_GET['hwKey']);
                            $this->onUserDetails($userID);
                        }
                    }
                    break;
                default:
                    $this->badRequestHeader();
                    echo json_encode(array("Authentication" => TRUE, "Status" => "Uknown URL command"));
                    break;
            }
        } else {
            $this->badRequestHeader();
            echo json_encode(array("Status" => "error", "Info" => "authentication parameter missing or in wrong place", "tip" => " .php/.../authentication?usn=john&pass=doe"), JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * Checks if user can log into the system.
     * Prints JSON response to output.
     * 
     * @param string $username Username
     */
    private function onLogin($username) {
        try {
            $account = $this->db->getAccountID($username);
            if (!$account[0]['active']) {
                //user is not activated
                $this->unathorizedHeader();
                echo json_encode(array("Authentication" => FALSE, "Status" => "Not activated"));
                exit();
            }
            $user = $this->db->getUser(NULL, $account[0]['id']);
            if ($user[0]['Deleted']) {
                //user is deleted
                $this->unathorizedHeader();
                echo json_encode(array("Authentication" => FALSE, "Status" => "User deleted"));
                exit();
            }

            //user active and not deleted
            $this->okHeader();
            echo json_encode(array("Authentication" => TRUE, "UserID" => (int) $user[0]['id'], "Status" => "Login successful"));
        } catch (Exception $ex) {
            //return server error and stop
            $this->serverError();
            echo json_encode(array("Authentication" => FALSE, "Status" => "Error"));
            exit();
        }
    }

    /**
     * Returns information about the user including his types.
     * Prints JSON response to output.
     * 
     * @param int $userID ID of the user
     */
    private function onUserDetails($userID) {
        $user = $this->db->getUser($userID);
        if ($user) {
            $this->okHeader();
            $user[0]['Type'] = $this->db->getUserType($userID);
            echo json_encode(array("Authentication" => TRUE, "User" => $user[0], "Status" => "Successful"));
        } else {
            $this->badRequestHeader();
            echo json_encode(array("Authentication" => TRUE, "User" => NULL, "Status" => "User doesn't exist"));
        }
    }

    /**
     * Returns visits for this user from a specific date forward.
     * Prints JSON response to output.
     * 
     * @param int $userID ID of the user
     * @param string $from SQL Timestamp string
     */
    private function onGetVisits($userID, $from) {
        $visits = $this->db->getVisits($userID, $from);

        foreach ($visits as $key => $visit) {
            $tasks = $this->db->getTasks($visit["id"], $userID);

            //tasks exist for this visit
            if (count($tasks) > 0) {
                $duration = 0;
                //sum up all the durations of tasks
                foreach ($tasks as $key2 => $task) {
                    $duration += $task['Duration'];
                }
			//die(var_dump($visit['To'], $duration, $visit["id"]));
                //To time is not set AND duration of tasks is > 0
                //add tasks duration to the From time
                if ($visit['To'] === NULL && $duration) {
                    $tz = new DateTimeZone("Europe/Ljubljana");
                    $date = new DateTime($visit['From'], $tz);
                    $date->modify('+' . $duration . ' minutes');
                    $visit['To'] = $date->format("H:i:s");
                    //set this field to know that it was calculated
                    $visit['To_calculated'] = TRUE;
                } else if ($visit['To'] !== NULL) {
                    //To time is set
                    //add tasks duration to the From time
                    $tz = new DateTimeZone("Europe/Ljubljana");
                    $date = new DateTime($visit['From'], $tz);
                    $date->modify('+' . $duration . ' minutes');
                    $newToTime = $date->format("H:i:s");

                    //disables timezone warning
                    date_default_timezone_set("Europe/Ljubljana");
                    //if new To time is longer than original To time, change them
                    if (strtotime($newToTime) > strtotime($visit['To'])) {
                        $visit['To'] = $newToTime;
                        $visit['To_calculated'] = TRUE;
                    }
                }
            }

            /*
             * Set address and postal code
             */
            $addressArray = $this->db->getAddress($visit['Address_id']);
            $address = $addressArray[0];
            $postalArray = $this->db->getPostalCode($address['Postal_code_id']);
            $postal = $postalArray[0];

            unset($address['Postal_code_id']);
            $address['Postal'] = $postal;

            unset($visit['Address_id']);
            $visit['Address'] = $address;
            
            //add a list of user IDs for this visit
            $visit['Users'] = $this->db->getUsersForVisit($visit["id"]);

            $visits[$key] = $visit;
        }

        $this->okHeader();
        echo json_encode(array("Authentication" => TRUE, "Visits" => $visits, "Status" => "Successful"));
    }

    /**
     * Returns tasks for a specific visit.
     * Prints JSON response to output.
     * 
     * @param int $visitID ID of the visit for which to retrieve tasks
     */
    private function onGetTasks($visitID, $userID) {
        $tasks = $this->db->getTasks($visitID, $userID);
		
        $this->okHeader();
        echo json_encode(array("Authentication" => TRUE, "Tasks" => $tasks, "Status" => "Successful"));
    }
    
    private function onGetVisitsList($userID, $from){
        
    }

}
