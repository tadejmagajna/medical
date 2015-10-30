<?php
class Rest_model extends CI_Model {

        public function __construct()
        {
                $this->load->database();
        }
        
        /**
         * checks if user is loggedin and returns its userid
         * 
         * @param string $username
         * @param string $password
         * @return (status code, user_id)
         */
        public function authenticate($username, $password)
        {
        	$sql = "SELECT u.id, a.active, u.deleted FROM Account a INNER JOIN users u ON u.Account_id = a.id WHERE a.username=? AND a.password=?;";
			$result = $this->db->query($sql, array($username, $password))->first_row();
			
			//if password incorrect or missing
			if (!$username or !$password  or !$result)
				return array(2, false);
			
			//if user deleted
        	if ($result->deleted)
        		return array(3, false);
        	
        	//if user inactive
        	if (!$result->active)
        		return array(4, false);
        	
        	//on success
        	return array(1, $result->id);
      	}
      	
      	/**
      	 * returns all user info
      	 * 
      	 * @param int $userid
      	 * @param string $type
      	 * @return unknown
      	 */
      	public function user_info($userid, $type = true)
      	{
      		$sql = "SELECT u.* FROM Users u JOIN Account a ON a.id=u.Account_id WHERE u.Deleted=False AND a.active=True AND u.id = ?;";
      		$result = $this->db->query($sql, array($userid))->first_row();
      		
      		//if user type is needed, also add user type data to retult
      		if ($result && $type)
      			$result->Type = array($this->db->query("SELECT u.* FROM User_type u JOIN Users_has_User_type ut ON u.id=ut.User_type_id WHERE ut.Users_id=?;", array($userid))->first_row());
      		
      		return $result;
      		
      	}
      	
      	/**
      	 * returns all users
      	 */
      	public function all_users()
      	{
      		//get all non deleted and activated users
      		$sql = "SELECT u.id FROM Users u JOIN Account a ON a.id=u.Account_id WHERE u.Deleted=False AND a.active=True;";
      		$query = $this->db->query($sql);
      		
      		foreach ($query->result() as $user)
      			$results[] = $this->user_info($user->id, false);
      		
      		return $results;
      	}
      	
      	/**
      	 * 
      	 * @param int $userid
      	 * @param string $from
      	 * @return unknown
      	 */
      	public function visits($userid, $from)
      	{

      		//get visits based on userid and date limit
      		$sql = "SELECT v.* FROM Visit v JOIN Users_has_Visits uv ON uv.Visits_id=v.id WHERE v.InActive = False AND uv.Users_id=? AND v.From >= ? ORDER BY v.From;";
      		$result = $this->db->query($sql, array($userid, $from))->result();
      		
      		foreach ($result as $visit)
      		{
      			//calculate total visit duration
      			$duration = 0;
      			$tasks = $this->get_tasks($userid, $visit->id);
      			foreach ($tasks as $task) {
      				$duration += $task->Duration;} 

      			//if visit has several tasks, calculate the new TO time
      			if (count($tasks) > 0)
      			{
      				//based only on task duration
      				if ($visit->To === NULL && $duration) {	
	      					$tz = new DateTimeZone("Europe/Ljubljana");
      					$date = new DateTime($visit->From, $tz);
      					$date->modify('+' . $duration . ' minutes');
      					$visit->To = $date->format("H:i:s");
      					//set this field to know that it was calculated
      					$visit->To_calculated = TRUE;
      				} else if ($visit->To !== NULL) {
      					//To time is set
      					//add tasks duration to the From time
      					$tz = new DateTimeZone("Europe/Ljubljana");
      					$date = new DateTime($visit->From, $tz);
      					$date->modify('+' . $duration . ' minutes');
      					$newToTime = $date->format("H:i:s");
      				
      					//disables timezone warning
      					date_default_timezone_set("Europe/Ljubljana");
      					//if new To time is longer than original To time, change them
      					if (strtotime($newToTime) > strtotime($visit->To)) {
      						$visit->To = $newToTime;
      						$visit->To_calculated = TRUE;
      						
      					}
      				}
      			}
      				
   				$visit->Address = $this->db->query("SELECT * FROM Address WHERE id=?;", array($visit->Address_id))->first_row();
   				$visit->Address->Postal = $this->db->query("SELECT * FROM Postal_code WHERE id=?;", array($visit->Address->Postal_code_id))->first_row();
	     		$users = $this->db->query("SELECT Users_id FROM Users_has_Visits WHERE Visits_id = ?;", array($visit->id))->result();
	     		$visit->Users = array();
	     		foreach ($users as $user)
	      			$visit->Users[] = $user->Users_id;
	    		unset($visit->Address->Postal_code_id);
	      		unset($visit->Address_id);

      		}
      		return $result;
      		
      	}
      	
      	/**
      	 * returns task information including description and duration
      	 * 
      	 * @param int $userid
      	 * @param int $visitid
      	 */
      	public function get_tasks($userid, $visitid)
      	{
      		$sql = "SELECT t.id, t.Task_state_id as `State`, t.Comment, td.Title, td.Description, utd.Duration "
      				. "FROM Task t "
      				. "JOIN Task_has_Task_description ttd ON ttd.Task_id=t.id "
      				. "JOIN Task_description td ON td.id = ttd.Task_description_id "
      				. "JOIN User_Task_Duration utd ON utd.Task_description_id = td.id "
      				. "WHERE t.Visits_id=? AND utd.User_id = ?;";
      		
      		return $this->db->query($sql, array($visitid, $userid))->result();
      	}
      	
      	/**
      	 * returns all addresses
      	 */
      	public function all_addresses()
      	{
      		$addresses = $this->db->query("SELECT * FROM Address a;")->result();		
      		
      		foreach ($addresses as $address)
      		{
      			$address->Postal = $this->db->query("SELECT * FROM Postal_code WHERE id=?;", array($address->Postal_code_id))->first_row();
      			unset($address->Postal_code_id);
      		}
      		
      		return $addresses;
      	}
      	
      	/**
      	 * returns all postal codes
      	 */
      	public function all_postal_codes()
      	{
      		return $this->db->query("SELECT * FROM Postal_code;")->result();
      	}
      	
      	/**
      	 * 
      	 * @param string $city
      	 * @param int $postal_code
      	 * @return boolean
      	 */
      	public function add_postal_code($city, $postal_code)
      	{
      		//check for duplicate postal codes and return a negative result if it already exists
      		$duplicate_check = $this->db->query("SELECT id FROM Postal_code WHERE Postal_code=?;", array($postal_code));
      		if ($duplicate_check->num_rows())
      			return false;

      		$this->db->insert('Postal_code', array('City' => $city, 'Postal_code' => $postal_code));
      		return $this->db->insert_id();
      	}
      	
      	/**
      	 * adds an address to database
      	 * 
      	 * @param int $postalCodeID
      	 * @param string $streetName
      	 * @param int $streetNumber
      	 * @param int $floor
      	 * @param int $apartmentNumber
      	 * @param string $comment
      	 */
      	public function add_address($postalCodeID, $streetName, $streetNumber, $floor, $apartmentNumber, $comment = NULL)
      	{
      		$data = array('Postal_code_id' => $postalCodeID, 'Street_Name' => $streetName, 'Street_Number' => $streetNumber, 'Floor' => $floor, 'Apartement_Number' => $apartmentNumber, 'Comment' => $comment);
      		$this->db->insert('Address', $data);
      		return $this->db->insert_id();
      	}
      	
      	/**
      	 * joins a user with an address
      	 * 
      	 * @param int $userid
      	 * @param int $addressid
      	 * @return boolean
      	 */
      	public function setUserAddress($userid, $addressid)
      	{
      		//if user id is incorrect return a negative result
      		if (!$this->db->query("SELECT id FROM users WHERE id = ?", array($userid))->first_row())
      			return false;
      		
      		$this->db->insert('users_has_address', array('Users_id' => $userid, 'Address_id' => $addressid));
      		return true;
      		
      	}
      	
      	/**
      	 * adds new visit to database
      	 * 
      	 * @param int $addressID
      	 * @param string $from
      	 * @param string $to
      	 * @param string $comment
      	 * @param boolean $inActive
      	 */
      	public function add_visit($addressID, $from, $to=NULL, $comment=NULL, $inActive = FALSE)
      	{
      		$this->db->insert('Visit', array('Address_id' => $addressID, 'From' => $from, 'To' => $to, 'Comment' => $comment, 'InActive' => $inActive));
      		return $this->db->insert_id();
      	}
      	
      	/**
      	 * joins user with a visit
      	 * 
      	 * @param int $userid
      	 * @param int $visitid
      	 * @return boolean
      	 */
      	public function setVisitToUser($userid, $visitid)
      	{
      		//if user id is incorrect return a negative result
      		if (!$this->db->query("SELECT id FROM users WHERE id = ?", array($userid))->first_row())
      			return false;
      		
      		return $this->db->insert('Users_has_Visits', array('Users_id' => $userid, 'Visits_id' => $visitid));
      	}
      	
      	/**
      	 * adds new task to database
      	 * 
      	 * @param int $visitID
      	 * @param int $stateID
      	 * @param string $comment
      	 * @param string $title
      	 * @param string $description
      	 * @param int $duration
      	 * @param int $userid
      	 * @return boolean
      	 */
      	public function add_task_and_description($visitID, $stateID, $comment=NULL, $title, $description=NULL, $duration, $userid) 
      	{
      		//if user id is incorrect return a negative result
      		if (!$this->db->query("SELECT id FROM users WHERE id = ?", array($userid))->first_row())
      			return false;
      		
      		//if visit id is incorrect return a negative result
      		if (!$this->db->query("SELECT id FROM Visit WHERE id = ?", array($visitID))->first_row())
      				return false;
      		
      		//if state id is incorrect return a negative result
      		if (!$this->db->query("SELECT id FROM task_state WHERE id = ?", array($stateID))->first_row())
      			return false;
      		
      		//insert new task
      		$task_data = array('Visits_id' => $visitID , 'Task_state_id' => $stateID, 'Comment' => $comment);
      		$this->db->insert('Task', $task_data);
      		$task_id = $this->db->insert_id();
      		
      		//insert new task description
      		$task_description_data = array('Title' => $title , 'Description' => $description);
      		$this->db->insert('Task_description', $task_description_data);
      		$description_id = $this->db->insert_id();
      		
      		//connect task and task description
      		$this->db->insert('Task_has_Task_description', array('Task_id' => $task_id, 'Task_description_id' => $description_id));
      		
      		//connect user and task duration
      		$this->db->insert('User_Task_Duration', array('User_id' => $userid, 'Task_description_id' => $description_id, 'Duration' => $duration));
      		
      		return true;
      		
      	}
      	
      	/**
      	 * changes task state and modifies its comment
      	 * 
      	 * @param int $stateid
      	 * @param int $taskid
      	 * @param int $comment
      	 * @return boolean
      	 */
      	public function change_task_state($stateid, $taskid, $comment = false)
      	{
      		//if state id is incorrect return a negative result
      		if (!$this->db->query("SELECT id FROM task_state WHERE id = ?", array($stateid))->first_row())
      			return false;
      		
      		//if task idis incorrect return a negative result
      		if (!$this->db->query("SELECT id FROM Task WHERE id = ?", array($taskid))->first_row())
      			return false;
      		
      		$data = array('Task_state_id' => $stateid);
      		
      		//comment is optimal
      		if ($comment)
      			$data['comment'] = $comment;
      			
      		//update
      		$this->db->where('id', $taskid);
      		$this->db->update('Task', $data);
      		
      		return true;
      	}
      	
      	
      	
}