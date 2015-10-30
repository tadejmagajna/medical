<?php

class Database{

	public function connect(){
		$servername = "localhost";
		$username = "remoteinlife";
		$password = "remoteinlife1_";
		
		$conn = new mysqli($servername, $username, $password);
		
		return $conn;
	}
	
	
	public function authenUser($username,$password){
	
		$conn = $this->connect();
		
		// Check connection
		if ($conn->connect_error) {
			die("Connection failed: " . $conn->connect_error);
		}
		
		
		$query  = "SELECT *
					FROM testinlife.account
					WHERE username='$username'";
	
		

		$result = mysqli_query($conn, $query);

// 		echo $conn->connect_error;
		
		if (mysqli_num_rows($result) > 0) {
			// output data of each row
			while($row = mysqli_fetch_assoc($result)) {
	// 			#echo "id: " . $row["id"]. " - Username: " . $row["username"]. " " . $row["password"]."  Active: ".$row['active']. "<br>";
			
				$response = array("Password" => 1, "Active" => 0);
				
				if ($password != $row['password'])
					$response['Password'] = 0;
				
				$response['Active'] = $row['active'];
				
				$conn->close();
				return $response;
			}
	
	
			
		} else {
			#echo "0 results";
		}
		
		
		$conn->close();
	} 

}

// print_r(authenUser('jani', 'geslos'));
// $db = new Database();

// $username = 4;
// $password = 5;

// $res = $db->authenUser($username, $password);

// print_r($res);

?>