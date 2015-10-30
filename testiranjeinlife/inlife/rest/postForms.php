<?php

require_once "general.php";
require_once "database.php";

class postForms extends general {

    private $db;

    function handleRequest() {
        $extra = $this->getExtra();
        if (count($extra) > 0 && end($extra) == "authenticate") {
            $result = $this->authenticate(isset($_REQUEST['usn']) ? $_REQUEST['usn'] : null, isset($_REQUEST['pass']) ? $_REQUEST['pass'] : null);

            if (!$result['Authentication']) {
                $this->unathorizedHeader();
                echo json_encode($result);
                exit;
            }

            $this->db = new Database();

            switch ($extra[0]) {
                case "postal":
                    $this->onPostal(json_decode(file_get_contents('php://input')));
                    break;
                case "visit":
                    if ($extra[1] == "change") {
                        $this->onVisitChange(json_decode(file_get_contents('php://input')));
                    } else {
                        $this->onVisit(json_decode(file_get_contents('php://input')));
                    }
                    break;
                case "task":
                    $this->onTask(json_decode(file_get_contents('php://input')));
                    break;
            }
        } else {
            $this->badRequestHeader();
            echo json_encode(array("Status" => "error", "Info" => "authentication parameter missing or in wrong place", "tip" => " .php/.../authentication?usn=john&pass=doe"), JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * Inserts a new postal code with data from received JSON.
     * If insert was successful, Inserted field in reponse JSON is set to TRUE.
     * 
     * @param type $json
     */
    private function onPostal($json) {
        $id = $this->db->addPostalCode($json->City, $json->Postal_code);
        if ($id == -1) {
            $this->badRequestHeader();
            echo json_encode(array("Authentication" => TRUE, "Inserted" => FALSE, "Status" => "Error inserting"));
        } else {
            $this->okHeader();
            echo json_encode(array("Authentication" => TRUE, "Inserted" => TRUE, "id" => $id, "Status" => "Successful"));
        }
    }

    /**
     * Inserts a new Visit. If new Address is send also, it adds it.
     * Connects new Visit to the list of received Users.
     * If insert was successful, Inserted field in response JSON is set to TRUE.
     * 
     * @param type $json
     */
    private function onVisit($json) {
        $address = $json->Address;

        //this is a new address, we have to add to the database
        $addressID = $address->id;
        if ($addressID == 0) {
            $addressID = $this->db->addAdress($address->Postal->id, $address->Street_Name, $address->Street_Number, $address->Floor, $address->Apartement_Number, null);

            //check number of users
            //if 2 -> first user is carer
            $users = $json->Users;
            if (count($users) == 1) {
                $this->db->setUserAddress($users[0], $addressID);
            } else {
                $this->db->setUserAddress($users[1], $addressID);
            }
        }

        //add new visit to database
        $newVisitID = $this->db->addVisit($addressID, $json->From, $json->To, $json->Comment);

        if ($newVisitID == -1) {
            $this->badRequestHeader();
            echo json_encode(array("Authentication" => TRUE, "Inserted" => FALSE, "Status" => "Error inserting"));
        } else {
            //on success, connect users to this visit
            foreach ($json->Users as $userID) {
                $this->db->setVisitToUser($userID, $newVisitID);
            }
            $this->okHeader();
            echo json_encode(array("Authentication" => TRUE, "Inserted" => TRUE, "id" => $newVisitID, "Status" => "Successful"));
        }
    }

    /**
     * Inserts a new Task and Task description. It also inserts a new Task duration.
     * If insert was successful, Inserted field in response JSON is set to TRUE.
     * 
     * @param type $json
     */
    private function onTask($json) {
        try {
            //add new task
            $taskID = $this->db->addTask($json->VisitID, $json->State, $json->Comment);

            //add new description
            $descriptionID = $this->db->addTaskDescription($json->Title, $json->Description);

            if ($taskID != -1 && $descriptionID != -1) {
                //connect task and description
                $ok = $this->db->setTaskDescription($taskID, $descriptionID);
                if ($ok) {
                    //add task duration to the visit
                    $taskDuration = $json->Duration;
                    //set duration for this task
                    $ok = $this->db->setUserTaskDuration($json->UserID, $descriptionID, $taskDuration);

                    if ($ok) {
                        $this->okHeader();
                        echo json_encode(array("Authentication" => TRUE, "Inserted" => TRUE, "Status" => "Successful"));
                        exit();
                    }
                }
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
        $this->badRequestHeader();
        echo json_encode(array("Authentication" => TRUE, "Inserted" => FALSE, "Status" => "Error inserting"));
    }

    /**
     * Inserts a new request for changing the visit time.
     * If insert was successful, Inserted field in response JSON is set to TRUE.
     * 
     * @param type $json
     */
    private function onVisitChange($json) {
        try {
            //retrieve original visit
            $oldVisit = $this->db->getVisits(NULL, NULL, $json->VisitID);
            $oldVisitUsers = $this->db->getUsersForVisit($oldVisit[0]['id']);

            //add new visit
            $newVisitID = $this->db->addVisit($oldVisit[0]['Address_id'], $json->From, $json->To, $oldVisit[0]['Comment'], ((isset($json->ChangeMode) && $json->ChangeMode == "clone") ? FALSE : TRUE));
            //copy all users to new visit
            foreach ($oldVisitUsers as $userID) {
                $this->db->setVisitToUser($userID, $newVisitID);
            }

            //copy tasks from old visit into new
            $oldTasks = $this->db->getTasks($oldVisit[0]['id'], $json->CreatedBy);
            foreach ($oldTasks as $key => $task) {
                //add new task
                $newTaskID = $this->db->addTask($newVisitID, $task['State'], $task['Comment']);
                if ($newTaskID != -1) {
                    //set task description for new task
                    $oldTaskDesc = $this->db->getTaskDescription($task['id']);
                    foreach ($oldTaskDesc as $x => $desc) {
                        $this->db->setTaskDescription($newTaskID, $desc['id']);
                    }
                }
            }

            $changeID = $this->db->addVisitChange($oldVisit[0]['id'], $newVisitID, $json->CreatedBy, $json->Reason);
            if ($changeID != -1) {
                $this->okHeader();
                echo json_encode(array("Authentication" => TRUE, "Inserted" => TRUE, "Status" => "Successful"));
                exit();
            }
        } catch (Exception $ex) {
            echo $ex->getMessage();
        }
        $this->badRequestHeader();
        echo json_encode(array("Authentication" => TRUE, "Inserted" => FALSE, "Status" => "Error inserting"));
    }

}

?>