<?php

/**
 * Class that connects to server database. Connection is created on new object instance.
 * It has methods for querying and updating tables.
 * 
 * @author Domen Lanišnik
 */
class Database {

    private $server = "";
    private $user = "";
    private $passwd = "";
    private $schema = "";
    private $conn;

    /**
     * Use constructor without parameters to use default values.
     * 
     * @param string $server URL of the MySQL server
     * @param string $user Username of MySQL user
     * @param string $passwd Password of MySQL user
     * @param string $defaultSchema Name of the database to use
     */
    public function __construct($server = "localhost", $user = "root", $passwd = "", $defaultSchema = "testinlife") {
        $this->server = $server;
        $this->user = $user;
        $this->passwd = $passwd;
        $this->schema = $defaultSchema;

        try {
            $this->conn = new PDO("mysql:host=$server;dbname=$this->schema", $user, $passwd);
            //TURN THIS OFF/ON FOR ERROR DISPLAY
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            //
        } catch (PDOException $ex) {
            echo "Error connection to database: " . $ex->getMessage();
        }
    }

    /*
     * ===============================================
     *              SELECT STATEMENTS
     * ===============================================
     */


    /*
     * ///////////////
     *      USER
     * //////////////
     */

    /**
     * Returns all users in Users table.
     * @return array Associative array (look at function toArray)
     */
    public function getAllUsers() {
        $cursor = $this->conn->query("SELECT u.* FROM Users u "
                . "JOIN Account a ON a.id=u.Account_id "
                . "WHERE u.Deleted=False AND a.active=True;");
        return $this->toArray($cursor);
    }

    /**
     * Returns all users of specific type in Users table.
     * 
     * @param integer $typeID ID of user type to filter (for ID use User_type table)
     * @return array Associative array of all users (look at function toArray) or empty array
     */
    public function getTypeUsers($typeID) {
        $select = $this->conn->prepare("SELECT * FROM Users u "
                . "JOIN Users_has_User_type uu ON uu.Users_id=u.id "
                . "WHERE uu.User_type_id=?;");
        $select->bindParam(1, $typeID);
        $select->execute();
        return $this->toArray($select);
    }

    /**
     * Returns a user with specific id. Can also get user from account id. 
     * 
     * getUser(NULL, accountID) to get user connected with account.
     * 
     * @param integer $userID ID of the user to retrieve
     * @param integer $accountID ID of the account to which user is linked; can be NULL
     * @return array Associative array of user (look at function toArray) or empty array
     */
    public function getUser($userID, $accountID = NULL) {
        try {
            if (!$accountID) {
                $select = $this->conn->prepare("SELECT * FROM Users "
                        . "WHERE id=?;");
                $select->bindParam(1, $userID);
            } else {
                $select = $this->conn->prepare("SELECT * FROM Users "
                        . "WHERE Account_id=?;");
                $select->bindParam(1, $accountID);
            }
            $select->execute();
            return $this->toArray($select);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Retrieve all user types for a specific user.
     * @param int $userID ID of the user
     * @return array List of types
     */
    public function getUserType($userID) {
        try {
            $q = $this->conn->prepare("SELECT u.* FROM User_type u "
                    . "JOIN Users_has_User_type ut ON u.id=ut.User_type_id "
                    . "WHERE ut.Users_id=?;");
            $q->bindParam(1, $userID);
            $q->execute();
            return $this->toArray($q);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Retrieve phone number type of a specific phone number OR
     * retrieve all phone number types.
     * 
     * @param int $phoneNumberID ID of the phone number; NULL or empty for all types
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getPhoneNumberType($phoneNumberID = NULL) {
        try {
            if ($phoneNumberID) {
                $select = $this->conn->prepare("SELECT t.* "
                        . "FROM PhoneNumber_type t "
                        . "JOIN Phone_Number p ON p.type_id=t.id "
                        . "WHERE p.id=?;");
                $select->bindParam(1, $phoneNumberID);
            } else {
                //return all
                $select = $this->conn->prepare("SELECT * FROM PhoneNumber_type;");
            }
            $select->execute();
            return $this->toArray($select);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Retrieves all postal codes or a specific one.
     * 
     *  
     * @param int $id ID or NULL to retrieve all
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getPostalCode($id = NULL) {
        try {
            if ($id) {
                $q = $this->conn->prepare("SELECT * FROM Postal_code "
                        . "WHERE id=?;");
                $q->bindParam(1, $id);
            } else {
                $q = $this->conn->prepare("SELECT * FROM Postal_code;");
            }
            $q->execute();
            return $this->toArray($q);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Retrieve address type/types for a specific address OR
     * retrieve all address types.
     * 
     * @param int $addressID ID of the address; NULL or empty for all types
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getAddressType($addressID = NULL) {
        try {
            if ($addressID) {
                $select = $this->conn->prepare("SELECT t.* "
                        . "FROM Address_type t "
                        . "JOIN Address_has_Address_type a ON a.Address_type_id=t.id "
                        . "WHERE a.Address_id=?;");
                $select->bindParam(1, $addressID);
            } else {
                //return all
                $select = $this->conn->prepare("SELECT * FROM Address_type;");
            }
            $select->execute();
            return $this->toArray($select);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Retrieves all addresses OR addresses with specific id OR postal code OR type.
     * You can only use one filter.
     * Use getAddress() to get all.
     * 
     * @param int $addressID ID of the address to retrieve
     * @param int $postalCode 4-digit postal code (ex. 1000)
     * @param string $type Name of the type (ex. "Home")
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getAddress($addressID = NULL, $postalCode = NULL, $type = NULL) {
        try {
            if ($addressID) {
                //get specific address
                $q = $this->conn->prepare("SELECT * FROM Address "
                        . "WHERE id=?;");
                $q->bindParam(1, $addressID);
            } else if ($postalCode) {
                //filter by postal code
                $q = $this->conn->prepare("SELECT a.* FROM Address a "
                        . "JOIN Postal_code p ON a.Postal_code_id=p.id "
                        . "WHERE p.Postal_code=?;");
                $q->bindParam(1, $postalCode);
            } else if ($type) {
                //filter by type
                $q = $this->conn->prepare("SELECT a.* FROM Address a "
                        . "JOIN Address_has_Address_type aa ON a.id=aa.Address_id "
                        . "JOIN Address_type at ON aa.Address_type_id=at.id "
                        . "WHERE at.Type=?;");
                $q->bindParam(1, $type);
            } else {
                //return all
                $q = $this->conn->prepare("SELECT * FROM Address a;");
            }
            $q->execute();
            return $this->toArray($q);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Retrieves all adresses for a specific user.
     * 
     * @param int $userID ID of the user
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getUserAddress($userID) {
        try {
            if ($userID) {
                $q = $this->conn->prepare("SELECT a.* FROM Address a "
                        . "JOIN Users_has_Address uha ON uha.Address_id=a.id "
                        . "WHERE uha.Users_id=?;");
                $q->bindParam(1, $userID);
                $q->execute();
                return $this->toArray($q);
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Retrives emails for a specific user OR all emails in the database.
     * 
     * @param inf $userID ID of the user; NULL or empty for all emails
     * @return array Associative array (ex. array([0] => john.doe@mail.com))
     */
    public function getEmail($userID = NULL) {
        try {
            if ($userID) {
                $q = $this->conn->prepare("SELECT e.email FROM email e "
                        . "JOIN email_has_Users eu ON eu.email_id=e.id "
                        . "WHERE eu.Users_id=?;");
                $q->bindParam(1, $userID);
            } else {
                $q = $this->conn->prepare("SELECT email FROM email;");
            }
            $q->execute();
            return $q->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Checks if provided password matches saved password for this user.
     * 
     * @param string $username
     * @param string $password
     * @return boolean True if correct
     */
    public function authenticateUser($username, $password) {
        try {
            $select = $this->conn->prepare("SELECT id FROM Account "
                    . "WHERE username=? AND password=?;");
            $select->bindParam(1, $username);
            $select->bindParam(2, $password);
            if ($select->execute() && $select->rowCount() != 0) {
                return TRUE;
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return FALSE;
    }

    /**
     * Returns account ID associated with a username. Also returns account active/inactive state.
     * 
     * @param string $username
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getAccountID($username) {
        try {
            $select = $this->conn->prepare("SELECT id, active FROM Account "
                    . "WHERE username=?;");
            $select->bindParam(1, $username);
            if ($select->execute() && $select->rowCount() != 0) {
                return $this->toArray($select);
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Return account information for a specific user OR active/inactive users OR all users.
     * getAccount() for all accounts
     * getAccount(ID, NULL) for a specific user
     * getAccount(NULL, TRUE/FALSE) for all active/inactive accounts
     * 
     * @param int $userID ID of user; NULL to ignore
     * @param boolean $active TRUE for active, FALSE for inactive; NULL to ignore
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getAccount($userID = NULL, $active = NULL) {
        try {
            if ($userID) {
                $q = $this->conn->prepare("SELECT a.* FROM Account a "
                        . "JOIN Users u ON u.Account_id=a.id "
                        . "WHERE u.id=?;");
                $q->bindParam(1, $userID);
            } else if ($active !== NULL) {
                $q = $this->conn->prepare("SELECT a.* FROM Account a "
                        . "WHERE a.active=?;");
                $q->bindParam(1, $active);
            } else {
                $q = $this->conn->prepare("SELECT * FROM Account;");
            }
            $q->execute();
            return $this->toArray($q);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Gets all active (InActive = FALSE) visits or visits for a specific user. Visits can be filtered by start time.
     * 
     * @param int $userID ID of the user, NULL for all
     * @param string $from Timestamp to show only visits from that time forward
     * @param int $visitID Use this to get details for a specific visit only
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getVisits($userID = NULL, $from = "1980-01-01 01:00:00", $visitID = NULL) {
        try {
            if ($visitID) {
                $q = $this->conn->prepare("SELECT * FROM Visit "
                        . "WHERE id = ?;");
                $q->bindParam(1, $visitID);
            } else if ($userID) {
                $q = $this->conn->prepare("SELECT v.* FROM Visit v "
                        . "JOIN Users_has_Visits uv ON uv.Visits_id=v.id "
                        . "WHERE v.InActive = False AND uv.Users_id=? AND v.`From` >= ? "
                        . "ORDER BY v.`From`;");
                $q->bindParam(1, $userID);
                $q->bindParam(2, $from);
            } else {
                $q = $this->conn->prepare("SELECT * FROM Visit "
                        . "WHERE `From` >= ? "
                        . "ORDER BY `From`;");
                $q->bindParam(1, $from);
            }
            $q->execute();
            return $this->toArray($q);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Returns a list of User IDs associated with the specified Visit.
     * @param int $visitID ID of the visit
     * @return array Array of user IDs or empty array
     */
    public function getUsersForVisit($visitID) {
        try {
            $q = $this->conn->prepare("SELECT Users_id FROM Users_has_Visits WHERE Visits_id = ?;");
            $q->bindParam(1, $visitID);
            $q->execute();
            return $q->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Gets all tasks for a specific visit.
     * 
     * @param int $visitID ID of the visit
     * @param int $userID ID of the user
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getTasks($visitID, $userID) {
        try {
            $q = $this->conn->prepare("SELECT t.id, t.Task_state_id as `State`, t.Comment, td.Title, td.Description, utd.Duration "
                    . "FROM Task t "
                    . "JOIN Task_has_Task_description ttd ON ttd.Task_id=t.id "
                    . "JOIN Task_description td ON td.id = ttd.Task_description_id "
                    . "JOIN User_Task_Duration utd ON utd.Task_description_id = td.id "
                    . "WHERE t.Visits_id=? AND utd.User_id = ?;");
            $q->bindParam(1, $visitID);
            $q->bindParam(2, $userID);
            $q->execute();
			
            return $this->toArray($q);
        } catch (Exception $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /*
     * ///////////////
     *  CARER SUPPORT
     * //////////////
     */

    /**
     * Retrieve task description for a specific task.
     * @param int $taskID ID of the task
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getTaskDescription($taskID) {
        try {
            $q = $this->conn->prepare("SELECT td.* FROM Task_description td "
                    . "JOIN Task_has_Task_description tt ON tt.Task_description_id=td.id "
                    . "WHERE tt.Task_id=?;");
            $q->bindParam(1, $taskID);
            $q->execute();
            return $this->toArray($q);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Retrieves the id of the task state.
     * @param int $taskID ID of the task
     * @return int ID of state or 0 if error
     */
    public function getTaskState($taskID) {
        try {
            $q = $this->conn->prepare("SELECT s.id FROM Task_state s "
                    . "JOIN Task t ON t.Task_state_id=s.id "
                    . "WHERE t.id=?;");
            $q->bindParam(1, $taskID);
            $q->execute();
            return (int) $q->fetchColumn();
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return 0;
    }

    /*
     * ///////////////
     *    ALARMS
     * //////////////
     */

    /**
     * Retrieves all alarms or specific alarms. You can retrieve specific alarm by using
     * alarmID OR alarms from a specific user AND/OR based on active/inactive.
     * 
     * @param int $alarmID ID of alarm OR NULL
     * @param int $userID ID of user OR NULL
     * @param boolean $active True/false for active/inactive; NULL to ignore
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getAlarm($alarmID = NULL, $userID = NULL, $active = NULL) {
        try {
            if ($alarmID && $userID && $active !== NULL) {
                //filter by all three
                $q = $this->conn->prepare("SELECT * FROM Alarm "
                        . "WHERE id=? AND from_id=? AND active=?;");
                $q->bindParam(1, $alarmID);
                $q->bindParam(2, $userID);
                $q->bindParam(3, $active);
            } else if ($alarmID && $userID && $active === NULL) {
                //filter by id and user
                $q = $this->conn->prepare("SELECT * FROM Alarm "
                        . "WHERE id=? AND from_id=?;");
                $q->bindParam(1, $alarmID);
                $q->bindParam(2, $userID);
            } else if (!$alarmID && $userID && $active !== NULL) {
                //filter by user and active
                $q = $this->conn->prepare("SELECT * FROM Alarm "
                        . "WHERE from_id=? AND active=?;");
                $q->bindParam(1, $userID);
                $q->bindParam(2, $active);
            } else if ($alarmID && !$userID && $active === NULL) {
                //filter by alarmID
                $q = $this->conn->prepare("SELECT * FROM Alarm WHERE id=?;");
                $q->bindParam(1, $alarmID);
            } else if (!$alarmID && $userID && $active === NULL) {
                //filter by user
                $q = $this->conn->prepare("SELECT * FROM Alarm WHERE from_id=?;");
                $q->bindParam(1, $userID);
            } else {
                //get all
                $q = $this->conn->prepare("SELECT * FROM Alarm");
            }
            $q->execute();
            return $this->toArray($q);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Retrieves alarms with a specific type OR all alarms.
     * @param int $typeID ID of alarm type; NULL for all
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getAlarmsByType($typeID = NULL) {
        try {
            if ($typeID) {
                $q = $this->conn->prepare("SELECT * FROM Alarm WHERE Alarm_Type_id=?;");
                $q->bindParam(1, $typeID);
                $q->execute();
                return $this->toArray($q);
            } else {
                //get all alarms
                return $this->getAlarm();
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Retrieve alarm action details.
     * 
     * @param int $actionID ID of specific action to retrieve; NULL to ignore
     * @param int $alarmID ID of alarm connected to this action; NULL to ignore
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getAlarmAction($actionID = NULL, $alarmID = NULL) {
        try {
            if ($actionID && !$alarmID) {
                //find specific action
                $q = $this->conn->prepare("SELECT * FROM Alarm_Action WHERE id=?;");
                $q->bindParam(1, $actionID);
            } else if (!$actionID && $alarmID) {
                //filter with alarm id
                $q = $this->conn->prepare("SELECT * FROM Alarm_Action WHERE Alarm_id=?;");
                $q->bindParam(1, $alarmID);
            } else {
                //get all
                $q = $this->conn->prepare("SELECT * FROM Alarm_Action;");
            }
            $q->execute();
            return $this->toArray($q);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /*
     * ///////////////
     *    HARDWARE
     * //////////////
     */

    /**
     * Retrieves device information.
     * getDevice() to get all devices.
     * getDevice(ID, NULL, NULL) to get specific
     * getDevice(NULL, ID, NULL) to get users devices
     * getDevice(NULL, ID, ID) to get users devices of specific type
     * getDevice(NULL, NULL, ID) to get all devices of type
     * 
     * @param int $deviceID ID of the device; NULL to ignore
     * @param int $userID ID of the user; NULL to ignore
     * @param int $typeID ID of device type; NULL to ignore
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getDevice($deviceID = NULL, $userID = NULL, $typeID = NULL) {
        try {
            if ($deviceID && !$userID && !$typeID) {
                //get specific device
                $q = $this->conn->prepare("SELECT * FROM Device WHERE id=?;");
                $q->bindParam(1, $deviceID);
            } else if (!$deviceID && $userID && !$typeID) {
                //get devices from specific user
                $q = $this->conn->prepare("SELECT * FROM Device WHERE Users_id=?;");
                $q->bindParam(1, $userID);
            } else if (!$deviceID && $userID && $typeID) {
                //get specific type of device from specific user
                $q = $this->conn->prepare("SELECT * FROM Device WHERE Users_id=? AND Device_type_id=?;");
                $q->bindParam(1, $userID);
                $q->bindParam(2, $typeID);
            } else if (!$deviceID && !$userID && $typeID) {
                //get devices of same type
                $q = $this->conn->prepare("SELECT * FROM Device WHERE Device_type_id=?;");
                $q->bindParam(1, $typeID);
            } else {
                //get all devices
                $q = $this->conn->prepare("SELECT * FROM Device;");
            }
            $q->execute();
            return $this->toArray($q);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /**
     * Returns ID of the device to which the HW key belongs to.
     * @param String $hwKey Unique identified 
     * @return int ID of the device OR -1 if device not found or error happened
     */
    public function getDeviceID($hwKey) {
        try {
            $q = $this->conn->prepare("SELECT Users_id FROM Device WHERE HW_key = ?;");
            $q->bindParam(1, $hwKey);
            if ($q->execute() && $q->rowCount() > 0) {
                return $q->fetchColumn(0);
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Return location information. Coordinates are in two columns, X and Y
     * 
     * getLocation() to get all locations
     * getLocation(ID, NULL) to get a specific location
     * getLocation(NULL, ID) to get location from a device
     * @param int $locationID ID of location; NULL to ignore
     * @param int $deviceID  ID of device; NULL to ignore
     * @return array Associative array (look at function toArray) or empty array
     */
    public function getLocation($locationID = NULL, $deviceID = NULL) {
        try {
            if ($locationID && !$deviceID) {
                $q = $this->conn->prepare("SELECT id, X(Location) as X, Y(Location) as Y, Device_id, Time, Alarm_id FROM Location WHERE id=?;");
                $q->bindParam(1, $locationID);
            } else if (!$locationID && $deviceID) {
                $q = $this->conn->prepare("SELECT id, X(Location) as X, Y(Location) as Y, Device_id, Time, Alarm_id FROM Location WHERE Device_id=?;");
                $q->bindParam(1, $deviceID);
            } else {
                $q = $this->conn->prepare("SELECT id, X(Location) as X, Y(Location) as Y, Device_id, Time, Alarm_id FROM Location;");
            }
            $q->execute();
            return $this->toArray($q);
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return array();
    }

    /*
     * ===============================================
     *              INSERT STATEMENTS
     * ===============================================
     */

    /*
     * ///////////////
     *      USER
     * //////////////
     */

    /**
     * Creates a new account for user. 
     * 
     * @param string $username
     * @param string $password
     * @param boolean $active

     * @return int ID of the new account, or -1 if error
     */
    public function addAccount($username, $password, $active) {
        try {
            $insert = $this->conn->prepare("INSERT INTO Account (username, password, active, activation_key) VALUES (?, ?, ?, ?)");
            $insert->bindParam(1, $username);
            $insert->bindParam(2, $password);
            $insert->bindParam(3, $active);
            $activation_key = $this->generateRandomString(16);
            $insert->bindParam(4, $activation_key);
            if ($insert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Creates a new user in Users table.
     * 
     * @param string $username
     * @param string $password
     * @param string $name
     * @param string $middleName
     * @param string $lastName
     * @param string $birthday MySQL Date standard (YYYY-mm-dd)
     * @param string $comment
     * @param string $deleted

     * @return int ID of the new user, or -1 if error
     */
    public function addUser($username, $password, $name, $middleName, $lastName, $birthday, $comment, $deleted) {
        try {
            //create new account for this user
            $accountID = $this->addAccount($username, $password, False);
            if ($accountID == -1) {
                return -1;
            }

            $insert = $this->conn->prepare("INSERT INTO Users (Account_id, "
                    . "Name, MiddleName, LastName, Birthday, Comment, Deleted) "
                    . "VALUES (?, ?, ?, ?, ?, ?, ?);");
            $insert->bindParam(1, $accountID);
            $insert->bindParam(2, $name);
            $insert->bindParam(3, $middleName);
            $insert->bindParam(4, $lastName);
            $insert->bindParam(5, $birthday);
            $insert->bindParam(6, $comment);
            $insert->bindParam(7, $deleted);
            if ($insert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Adds a new email address.
     * 
     * @param string $email Email adress
     * @param string $comment A comment for email, can be NULL

     * @return int ID of new email or -1 on failure
     */
    public function addEmail($email, $comment) {
        try {
            //insert email
            $insert = $this->conn->prepare("INSERT INTO email (email, Comment) VALUES (?, ?);");
            $insert->bindParam(1, $email);
            $insert->bindParam(2, $comment);
            if ($insert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Add an email address to a specific user.
     * 
     * @param int $userID ID of user
     * @param int $emailID ID of email

     * @return boolean TRUE on success, FALSE on failure
     */
    public function setUserEmail($userID, $emailID) {
        try {
            $insertUE = $this->conn->prepare("INSERT INTO email_has_Users VALUES (?, ?);");
            $insertUE->bindParam(1, $emailID);
            $insertUE->bindParam(2, $userID);
            if ($insertUE->execute()) {
                return TRUE;
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return FALSE;
    }

    /**
     * Adds a new phone number.
     * 
     * @param string $number Phone number to add
     * @param int $typeID ID of phone number type (look at table PhoneNumber_type)
     * @param string $comment A comment for phone number, can be NULL

     * @return int ID of new phone number or -1 on failure
     */
    public function addPhoneNumber($number, $typeID, $comment) {
        try {
            //insert phone number
            $pnInsert = $this->conn->prepare("INSERT INTO Phone_Number (type_id, Number, Comment) VALUES (?, ?, ?);");
            $pnInsert->bindParam(1, $typeID);
            $pnInsert->bindParam(2, $number);
            $pnInsert->bindParam(3, $comment);
            if ($pnInsert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Add a new postal code.
     * 
     * @param string $city Name of the city
     * @param int $postalCode Postal code number
     * @return int ID of new postal code or -1 on failure
     */
    public function addPostalCode($city, $postalCode) {
        try {
            //insert phone number
            $pcInsert = $this->conn->prepare("INSERT INTO Postal_code (City, Postal_code) VALUES (?, ?);");
            $pcInsert->bindParam(1, $city);
            $pcInsert->bindParam(2, $postalCode);
            if ($pcInsert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Add a phone number to a specific user.
     * 
     * @param int $userID ID of user
     * @param int $numberID ID of phone number

     * @return boolean TRUE on success, FALSE on failure
     */
    public function setUserPhoneNumber($userID, $numberID) {
        try {
            $upInsert = $this->conn->prepare("INSERT INTO Users_has_Phone VALUES (?, ?);");
            $upInsert->bindParam(1, $userID);
            $upInsert->bindParam(2, $numberID);
            if ($upInsert->execute()) {
                return TRUE;
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return FALSE;
    }

    /**
     * Adds a new address.
     * 
     * @param int $postalCodeID ID of postal code (table Postal_code)
     * @param string $streetName Name of the street (not empty)
     * @param string $streetNumber not empty
     * @param string $floor Can be empty
     * @param string $apartmentNumber Can be NULL
     * @param string $comment Can be NULL

     * @return int ID of new address or -1 on failure
     */
    public function addAdress($postalCodeID, $streetName, $streetNumber, $floor, $apartmentNumber, $comment) {
        try {
            //insert address
            $addressIn = $this->conn->prepare("INSERT INTO Address (Postal_code_id, Street_Name, Street_Number, Floor, Apartement_Number, Comment) VALUES (?, ?, ?, ?, ?, ?);");
            $addressIn->bindParam(1, $postalCodeID);
            $addressIn->bindParam(2, $streetName);
            $addressIn->bindParam(3, $streetNumber);
            $addressIn->bindParam(4, $floor);
            $addressIn->bindParam(5, $apartmentNumber);
            $addressIn->bindParam(6, $comment);
            if ($addressIn->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Sets an address for a specific user
     * 
     * @param int $userID ID of user
     * @param int $addressID ID of address

     * @return boolean TRUE on success, FALSE on failure
     */
    public function setUserAddress($userID, $addressID) {
        try {
            $userAdIn = $this->conn->prepare("INSERT INTO Users_has_Address VALUES (?, ?);");
            $userAdIn->bindParam(1, $userID);
            $userAdIn->bindParam(2, $addressID);
            if ($userAdIn->execute()) {
                return TRUE;
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return FALSE;
    }

    /**
     * Sets an address type for a specific address.
     * 
     * @param int $addressID ID of address
     * @param int $typeID ID of address type

     * @return boolean TRUE on success, FALSE on failure
     */
    public function setAddressType($addressID, $typeID) {
        try {
            $addressTypeIn = $this->conn->prepare("INSERT INTO Address_has_Address_type VALUES (?, ?);");
            $addressTypeIn->bindParam(1, $addressID);
            $addressTypeIn->bindParam(2, $typeID);
            if ($addressTypeIn->execute()) {
                return TRUE;
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return FALSE;
    }

    /*
     * ///////////////
     *  CARER SUPPORT
     * //////////////
     */

    /**
     * Creates a new visit.
     * 
     * @param int $addressID ID of address
     * @param string $from MySQL Timestamp (YYYY-MM-DD HH:MM:SS)
     * @param string $to MySQL Time (can be NULL)
     * @param string $comment (can be NULL)

     * @return int ID of the new visit, or -1 if error
     */
    public function addVisit($addressID, $from, $to, $comment, $inActive = FALSE) {
        try {
            $visitIn = $this->conn->prepare("INSERT INTO Visit (Address_id, `From`, `To`, Comment, InActive) VALUES (?, ?, ?, ?, ?);");
            $visitIn->bindParam(1, $addressID);
            $visitIn->bindParam(2, $from);
            $visitIn->bindParam(3, $to);
            $visitIn->bindParam(4, $comment);
            $visitIn->bindParam(5, $inActive);
            if ($visitIn->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Stores a new Change Visit request to be reviewed by the administrator.
     * 
     * @param int $oldVisitID ID of the original visit
     * @param int $newVisitID ID of the newly created InActive visit
     * @param int $createdBy ID of the user creating the request
     * @param string $reason Reason for changing
     * @return int ID of the new visit change, or -1 if error
     */
    public function addVisitChange($oldVisitID, $newVisitID, $createdBy, $reason) {
        try {
            $changeIn = $this->conn->prepare("INSERT INTO Change_visit (visit_id, new_visit_id, created_by, Reason, time_of_change) VALUES (?, ?, ?, ?, now());");
            $changeIn->bindParam(1, $oldVisitID);
            $changeIn->bindParam(2, $newVisitID);
            $changeIn->bindParam(3, $createdBy);
            $changeIn->bindParam(4, $reason);
            if ($changeIn->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Adds a visit to a user.
     * 
     * @param int $userID ID of user
     * @param int $visitID ID of visit

     * @return boolean TRUE on success, FALSE on failure
     */
    public function setVisitToUser($userID, $visitID) {
        try {
            $insert = $this->conn->prepare("INSERT INTO Users_has_Visits VALUES (?, ?);");
            $insert->bindParam(1, $userID);
            $insert->bindParam(2, $visitID);
            if ($insert->execute()) {
                return TRUE;
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return FALSE;
    }

    /**
     * Creates a new task.
     * 
     * @param int $visitID ID of the visit connected to this task
     * @param int $stateID ID of the current state
     * @param string $comment (can be NULL)

     * @return int ID of the new task, or -1 if error
     */
    public function addTask($visitID, $stateID, $comment) {
        try {
            $insert = $this->conn->prepare("INSERT INTO Task (Visits_id, Task_state_id, Comment) VALUES (?, ?, ?);");
            $insert->bindParam(1, $visitID);
            $insert->bindParam(2, $stateID);
            $insert->bindParam(3, $comment);
            if ($insert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Creates a new task description.
     * 
     * @param string $title Title of the task
     * @param string $description Description of the task

     * @return int ID of the new task, or -1 if error
     */
    public function addTaskDescription($title, $description) {
        try {
            $insert = $this->conn->prepare("INSERT INTO Task_description (Title, Description) VALUES (?, ?);");
            $insert->bindParam(1, $title);
            $insert->bindParam(2, $description);
            if ($insert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Sets a description to a specific task.
     * 
     * @param int $taskID ID of the task
     * @param int $descriptionID ID of the description

     * @return boolean TRUE on success, FALSE on failure
     */
    public function setTaskDescription($taskID, $descriptionID) {
        try {
            $insert = $this->conn->prepare("INSERT INTO Task_has_Task_description VALUES (?, ?);");
            $insert->bindParam(1, $taskID);
            $insert->bindParam(2, $descriptionID);
            if ($insert->execute()) {
                return TRUE;
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return FALSE;
    }

    /**
     * 
     * Set a duration for a task for a specific user.
     * 
     * @param int $userID ID of the user
     * @param int $taskDescriptionID ID of the task description
     * @param int $duration Duration in minutes
     * @return boolean TRUE on success, FALSE on failure
     */
    public function setUserTaskDuration($userID, $taskDescriptionID, $duration) {
        try {
            $insert = $this->conn->prepare("INSERT INTO User_Task_Duration VALUES (?, ?, ?);");
            $insert->bindParam(1, $userID);
            $insert->bindParam(2, $taskDescriptionID);
            $insert->bindParam(3, $duration);
            if ($insert->execute()) {
                return TRUE;
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return FALSE;
    }

    /*
     * ///////////////
     *    ALARMS
     * //////////////
     */

    /**
     * Creates a new alarm.
     * 
     * @param int $alarmTypeID ID of the alarm type
     * @param int $fromUserID ID of the user to which this alarm is connected with
     * @param int $operatorUserID ID of the user (can be NULL)
     * @param boolean $active Is alarm active?
     * @param string $time Timestamp of the alarm (YYYY-MM-DD HH:MM:SS)
     * @param string $comment Comment (can be NULL)
     * @param int $deviceID ID of the device on which this alarm was started (can be NULL)
     * 
     * @return int ID of the new alarm, or -1 if error
     */
    public function addAlarm($alarmTypeID, $fromUserID, $operatorUserID, $active, $time, $comment, $deviceID) {
        try {
            $insert = $this->conn->prepare("INSERT INTO Alarm (Alarm_Type_id, from_id, operator_id, active, time, Comment, Device_id) VALUES (?, ?, ?, ?, ?, ?, ?);");
            $insert->bindParam(1, $alarmTypeID);
            $insert->bindParam(2, $fromUserID);
            $insert->bindParam(3, $operatorUserID);
            $insert->bindParam(4, $active);
            $insert->bindParam(5, $time);
            $insert->bindParam(6, $comment);
            $insert->bindParam(7, $deviceID);
            if ($insert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Adds a new alarm type.
     * 
     * @param string $typeName Name of the alarm type
     * 
     * @return int ID of the new alarm type, or -1 if error
     */
    public function addAlarmType($typeName) {
        try {
            $insert = $this->conn->prepare("INSERT INTO Alarm_Type (Type) VALUES (?);");
            $insert->bindParam(1, $typeName);
            if ($insert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (Exception $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Add a new alarm action.
     * 
     * @param int $alarmID ID of the alarm connected to this action.
     * @param int $visitID ID of the visit (can be NULL)
     * @param string $comment Comment (can't be NULL)
     * 
     * @return int ID of the new alarm action, or -1 if error
     */
    public function addAlarmAction($alarmID, $visitID, $comment) {
        try {
            $insert = $this->conn->prepare("INSERT INTO Alarm_Action (Alarm_id, Visit_id, Comment) VALUES (?, ?, ?);");
            $insert->bindParam(1, $alarmID);
            $insert->bindParam(2, $visitID);
            $insert->bindParam(3, $comment);
            if ($insert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (Exception $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /*
     * ///////////////
     *    HARDWARE
     * //////////////
     */

    /**
     * Add a new device.
     * 
     * @param int $deviceTypeID ID of the device type
     * @param string $hwKey Signature of the device (can be NULL)
     * @param int $userID ID of the user this device belongs to
     * @param int $phoneNumbID ID of the phone number (can be NULL)
     * 
     * @return int ID of the new alarm action, or -1 if error
     */
    public function addDevice($deviceTypeID, $hwKey, $userID, $phoneNumbID) {
        try {
            $insert = $this->conn->prepare("INSERT INTO Device (Device_type_id, HW_key, Users_id, Phone_Number_id) VALUES (?, ?, ?, ?);");
            $insert->bindParam(1, $deviceTypeID);
            $insert->bindParam(2, $hwKey);
            $insert->bindParam(3, $userID);
            $insert->bindParam(4, $phoneNumbID);
            if ($insert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (Exception $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Adds a new device type.
     * 
     * @param string $typeName Name of the device type
     * 
     * @return int ID of the new alarm type, or -1 if error
     */
    public function addDeviceType($typeName) {
        try {
            $insert = $this->conn->prepare("INSERT INTO Device_type (Type) VALUES (?);");
            $insert->bindParam(1, $typeName);
            if ($insert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (Exception $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /**
     * Adds a new location connected to device.
     * 
     * @param string $locationPoint Formatted as "POINT(X-value Y-value)"
     * @param type $deviceID ID of the device 
     * @param type $time Timestamp (can be NULL)
     * @param type $alarmID ID of the alarm (can be NULL)
     * 
     * @return int ID of the new alarm type, or -1 if error
     */
    public function addLocation($locationPoint, $deviceID, $time, $alarmID) {
        try {
            $insert = $this->conn->prepare("INSERT INTO Location (Location, Device_id, Time, Alarm_id) VALUES (PointFromText(?), ?, ?, ?);");
            $insert->bindParam(1, $locationPoint);
            $insert->bindParam(2, $deviceID);
            $insert->bindParam(3, $time);
            $insert->bindParam(4, $alarmID);
            if ($insert->execute()) {
                return $this->conn->lastInsertId();
            }
        } catch (Exception $ex) {
            //echo $ex->getMessage();
        }
        return -1;
    }

    /*
     * ===============================================
     *              UPDATE STATEMENTS
     * ===============================================
     */

    /**
     * Sets a specific account active or inactive.
     * 
     * @param int $accountID ID of the account to change status
     * @param boolean $activate True to activate, False to deactivate

     * @return boolean TRUE on success, FALSE on failure
     */
    public function activateAccount($accountID, $activate) {
        try {
            $update = $this->conn->prepare("UPDATE Account SET active=? WHERE id=?;");
            $update->bindParam(1, $activate);
            $update->bindParam(2, $accountID);
            if ($update->execute()) {
                return TRUE;
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return FALSE;
    }

    /**
     * Changes current task state.
     * 
     * @param int $taskID ID of the task
     * @param int $stateID ID of the new state
     * @param string $comment Comment, can be NULL
     * 
     * @return boolean TRUE on success, FALSE on failure
     */
    public function setTaskState($taskID, $stateID, $comment = NULL) {
        try {
            if (!$comment) {
                $update = $this->conn->prepare("UPDATE Task SET Task_state_id=? WHERE id=?;");
                $update->bindParam(1, $stateID);
                $update->bindParam(2, $taskID);
            } else {
                $update = $this->conn->prepare("UPDATE Task SET Task_state_id=?, Comment=? WHERE id=?;");
                $update->bindParam(1, $stateID);
                $update->bindParam(2, $comment);
                $update->bindParam(3, $taskID);
            }
            if ($update->execute()) {
                return TRUE;
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return FALSE;
    }

    /**
     * Add a specific amount of time to a specific visit.
     * 
     * @param int $visitID ID of the visit
     * @param int $duration Time to add in minutes
     * @return boolean TRUE on success, FALSE on failure
     */
    public function setVisitToTime($visitID, $duration) {
        try {
            $insert = $this->conn->prepare("UPDATE Visit SET `To`=ADDTIME(`To`, SEC_TO_TIME(?*60)) WHERE id=?;");
            $insert->bindParam(1, $duration);
            $insert->bindParam(2, $visitID);
            if ($insert->execute()) {
                return TRUE;
            }
        } catch (PDOException $ex) {
            //echo $ex->getMessage();
        }
        return FALSE;
    }

    /*
      /////////////SUPPORT FUNCTIONS/////////////
     */

    /**
     * Returns an associative array representiation of a cursor.
     * Each row is represented as an associative array where keys are column names.
     * Rows are 0-numbered.
     * Output array can be empty: array()
     * or contain returned rows:
     * Array(
     *   [0] => Array
     *       (
     *           [id] => 1
     *           [type] => Carrer
     *           [Comment] =>
     *       )
     *
     *   [1] => Array
     *       (
     *           [id] => 2
     *           [type] => User
     *           [Comment] => Regular user.
     *       )
     * )
     * 
     * @param cursor $cursor query object after execute
     * @return array
     */
    public function toArray($cursor) {
        return $cursor->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generates a random string containing all characters (alpha and special)
     * of desired length. 
     * 
     * @param len Length of output string
     * @return Random string
     */
    private function generateRandomString($len) {
        $str = "";
        $chars = str_split('ABCDEFGHIJKLMNOPQRSTUVWXYZ$_?!-abcdefghijklmnopqrstuvwxyz0123456789');
        for ($i = 0; $i < $len; $i++) {
            $str .= "" . $chars[array_rand($chars)];
        }
        return $str;
    }

}
