<?php
defined('BASEPATH') OR exit('No direct script access allowed');

require_once APPPATH.'libraries/REST_Controller.php';

class RestApi extends REST_Controller
{
	private $userid;
	
	/**
	 * checks if the correct credencials have been sent and redirects to notloggedin page in case of incorrect input
	 */
	function __construct()
	{
		parent::__construct();
		$this->load->helper('url');
		$this->load->model('rest_model');

		//validate credentials
		$authentication = $this->rest_model->authenticate($this->query('usn'), $this->query('pass'));

		//redirect in case of bad status code
		if ($authentication[0] == 1)
			$this->userid = $authentication[1];
		elseif ($this->uri->segment(2) !== 'notloggedin')
			redirect('/restapi/notloggedin/'.$authentication[0]);
	}

  /**
   * Defaults to login page
  */
  public function index_get()
  {
    $this->response(array("Authentication" => TRUE, "UserID" => $this->userid, "Status" => "Login successful"));
  }
  
  /**
   * GET ENDPOINT returns user info for a sepecific user or ALL users if all parameter is set
   */
  public function users_get()
  {
  	//if all is set, return all users
  	if ($this->uri->segment(3) == 'all')
  		return $this->response(array("Authentication" => TRUE, "Users" => $this->rest_model->all_users(), "Status" => "Successful"));
  		
  	//validate input
  	$userid = $this->get('id');
  	if (!$userid)
  		$this->response(array("Authentication" => TRUE, "User" => NULL, "Status" => "Missing user id parameter"), 401);
  	
  	$user_info = $this->rest_model->user_info($userid);
  	
  	if ($user_info)
  		$this->response(array("Authentication" => TRUE, "User" => $user_info, "Status" => "Successful"));
  	else
  		$this->response(array("Authentication" => TRUE, "User" => NULL, "Status" => "User doesn't exist"), 401);
  }
  
  /**
   * GET ENDPOINT returns all visits for a user
   */
  public function visits_get()
  {
  	//validate input
  	$userid = $this->get('uid');
  	$from = $this->get('from') ? $this->get('from') : $from = "1980-01-01 01:00:00";
  		
  	if (!$userid)
  		$this->response(array("Authentication" => TRUE, "Visits" => NULL, "Status" => "Missing user id parameter"), 401);
  	else return 
  		$this->response(array("Authentication" => TRUE, "Visits" => $this->rest_model->visits($userid, $from), "Status" => "Successful"));
  }
  
  /**
   * GET ENDPOINT gets all task for a user in a visit
   */
  public function tasks_get()
  {
  	//validate input
  	$userid = $this->get('uid');
  	$visitid = $this->get('vid');
  	
  	if (!$userid or !$visitid)
  		$this->response(array("Authentication" => TRUE, "Tasks" => NULL, "Status" => "Missing parameters"), 401);
  	
  	else
  		return	$this->response(array("Authentication" => TRUE, "Tasks" => $this->rest_model->get_tasks($userid, $visitid), "Status" => "Successful"));
  }
  
  /**
   * GET ENDPOINT for retrieving all addresses
   */
  public function address_get($arg)
  {
  	if ($arg == "all")
  		return $this->response(array("Authentication" => TRUE, "Address" => $this->rest_model->all_addresses(), "Status" => "Successful"));
  	else
  		$this->response(array("Authentication" => TRUE, "Address" => NULL, "Status" => "Missing parameters"), 401);
  }
  
  /**
   * GET endpoint for getting all postal codes
   */
  public function postal_get($arg="")
  {
  	if ($arg == "all")
  		return $this->response(array("Authentication" => TRUE, "Postal" => $this->rest_model->all_postal_codes(), "Status" => "Successful"));
  	else
  		$this->response(array("Authentication" => TRUE, "Postal" => NULL, "Status" => "Missing parameters"), 401);
  }
  
  /**
   * POST ENDPOINT for adding posts
   */
  public function postal_post()
  {
  	//input
  	$city = $this->post('City');
  	$postal_code = $this->post('Postal_code');
  	
  	//validate input
  	if (!$city or !$postal_code or !is_numeric($postal_code))
  		$this->response(array("Authentication" => TRUE, "Inserted" => NULL, "Status" => "Error inserting"), 401);

  	$result = $this->rest_model->add_postal_code($city, $postal_code);
  	
  	if ($result)
  		$this->response(array("Authentication" => TRUE, "Inserted" => TRUE, "id" => $result, "Status" => "Successful"));
  	else
  		$this->response(array("Authentication" => TRUE, "Inserted" => NULL, "Status" => "Duplicate postal code"), 401);
  }
  
  
  /**
   * POST ENDPOINT for adding visits
   */
  public function visit_post()
  {
  	$post_array = $this->input->post();

  	//validate input
  	if (empty($post_array['From']) or
  			!isset($post_array['Address']['Postal']['id']) or

  			!isset($post_array['Address']['id']) or

  			empty($post_array['Users']) or
  			empty($post_array['From']) or
  			empty($post_array['Address']['Street_Name']) or
  			empty($post_array['Address']['Street_Number']) or
  			empty($post_array['Address']['Floor']) or
  			empty($post_array['Address']['Apartement_Number']))
  		$this->response(array("Authentication" => TRUE, "Inserted" => NULL, "Status" => "Error inserting"), 401);

  		//add new address
  		if ($post_array['Address']['id'] == 0)
  		{
  			$post_array['Address']['id'] = $this->rest_model->add_address($post_array['Address']['Postal']['id'], $post_array['Address']['Street_Name'], $post_array['Address']['Street_Number'], $post_array['Address']['Floor'], $post_array['Address']['Apartement_Number']);
  			if (empty($post_array['Users'][1]) == 1)
  				$result = $this->rest_model->setUserAddress($post_array['Users'][0], $post_array['Address']['id']);
  			else
  				$result = $this->rest_model->setUserAddress($post_array['Users'][1], $post_array['Address']['id']);
  			
  			if (!$result)
  				$this->response(array("Authentication" => TRUE, "Inserted" => NULL, "Status" => "Error inserting"), 401);
  		}
  		
  		//add new visit
  		$newVisitID = $this->rest_model->add_visit($post_array['Address']['id'], $post_array['From'], @$post_array['To'], @$post_array['Comment']);
  		if (!$newVisitID)
  			$this->response(array("Authentication" => TRUE, "Inserted" => NULL, "Status" => "Error inserting"), 401);
  			
  		//associate users with the new visit
  		foreach ($post_array['Users'] as $userID)
  			if (is_numeric($userID))
  				$this->rest_model->setVisitToUser($userID, $newVisitID);
  			
  		$this->response(array("Authentication" => TRUE, "Inserted" => TRUE, "id" => $newVisitID, "Status" => "Successful"));
  		
  
  }
  
  /**
   * POST ENDPOINT for adding tasks
   */
  public function task_post()
  {
  	$post_array = $this->input->post();
  	
  	//validate input
  	if (empty($post_array['VisitID']) or
  		empty($post_array['State']) or
  			empty($post_array['Title']) or
  			empty($post_array['Description']) or
  			!is_numeric($post_array['Duration']) or
  			empty($post_array['UserID']))
  		$this->response(array("Authentication" => TRUE, "Inserted" => NULL, "Status" => "Error inserting"), 401);
  		
  	//ask model to insert task into db	
  	$result = $this->rest_model->add_task_and_description($post_array['VisitID'], $post_array['State'], @$post_array['Comment'], $post_array['Title'], $post_array['Description'], $post_array['Duration'], $post_array['UserID']);
  		
  	if (!$result)
  		$this->response(array("Authentication" => TRUE, "Inserted" => NULL, "Status" => "Error inserting"), 401);
  	else
  		$this->response(array("Authentication" => TRUE, "Inserted" => TRUE, "Status" => "Successful"));
  }
  
  /**
   * PUT endpoint for modifying tasks
   */
  public function tasks_put($parameter)
  {
  	//validate input
  	if (!is_numeric($this->input->post('state_id')) or !is_numeric($this->input->post('tid')) or $parameter != 'state')
  			$this->response(array("Authentication" => TRUE, "State" => NULL, "Status" => "Missing parameters"), 401);
  	
  	//ask model to change task state
  	$result = $this->rest_model->change_task_state($this->input->post('state_id'), $this->input->post('tid'), @$this->input->post('Comment'));
  
  	if ($result)
  		$this->response(array("Authentication" => TRUE, "State" => $result, "Status" => "Successful"));
  	else
  		$this->response(array("Authentication" => TRUE, "State" => NULL, "Status" => "Missing parameters"), 401);
  }
  
  /**
   * GET endpoint for users that fail authentication
   * @param int $num status number
   */
  public function notloggedin_get($num)
  {
	//returns 401 with message based on status number: (1:User Deleted, 2:User Not Activated, 3:Username or password incorrect)
  	switch ($num)
  	{
  		case 3:	$this->response(array('Authentication' => false, 'Status' => 'User Deleted'), 401);
  		break;
  		case 4:	$this->response(array('Authentication' => false, 'Status' => 'User Not Activated'), 401);
  		break;
  		default: $this->response(array('Authentication' => false, 'Status' => 'Username or password incorrect'), 401);
		break;
  	}
  }
}