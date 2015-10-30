<?php

require_once "general.php";
require_once "database.php";

class putForms extends general {

    private $db;

    public function handleRequest() {
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
                case "tasks":
                    if ($extra[1] == "state") {
                        //url: ../tasks/state/authenticate?...
                        //params: usn, pass, tid (task id), sid (state id)
                        if (!isset($_REQUEST['tid']) || !isset($_REQUEST['sid'])) {
                            $this->badRequestHeader();
                            echo json_encode(array("Authentication" => TRUE, "State" => NULL, "Status" => "Missing parameters"));
                        } else {
                            $this->onTaskStateChange($_REQUEST['tid'], $_REQUEST['sid'], (isset($_REQUEST['comment']) ? $_REQUEST['comment'] : NULL));
                        }
                    } else {
                        //UNKNOWN
                        $this->badRequestHeader();
                        echo json_encode(array("Authentication" => TRUE, "Status" => "URL is not valid"));
                    }
                    break;
            }
        } else {
            $this->badRequestHeader();
            echo json_encode(array("Status" => "error", "Info" => "authentication parameter missing or in wrong place", "tip" => " .php/.../authentication?usn=john&pass=doe"), JSON_UNESCAPED_SLASHES);
        }
    }

    /**
     * Changes task state for a specific task.
     * Returns JSON to output whether, change was successful or not.
     * 
     * @param int $taskID ID of the task
     * @param int $stateID ID of the state
     */
    private function onTaskStateChange($taskID, $stateID, $comment) {
        $success = $this->db->setTaskState($taskID, $stateID, $comment);
        $this->okHeader();
        echo json_encode(array("Authentication" => TRUE, "State" => $success, "Status" => "Successful"));
    }

}
