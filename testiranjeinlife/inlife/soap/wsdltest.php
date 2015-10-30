<?php
  require_once 'lib/nusoap.php';

  
  $soap = new soap_server;
  $soap->configureWSDL('Kalkulator', 'http://testing.inlife.si/');
  $soap->wsdl->schemaTargetNamespace = 'http://testing.inlife.si/xsd/';
  $soap->register(
    'add',
    array('a' => 'xsd:int', 'b' => 'xsd:int'),
    array('c' => 'xsd:int'),
    'http://inlife.si/'
  );
  $soap->register(
  		'subtract',
  		array('a' => 'xsd:int', 'b' => 'xsd:int'),
  		array('c' => 'xsd:int'),
  		'http://inlife.si/'
  );
  
  $soap->register(
  		'divide',
  		array('a' => 'xsd:int', 'b' => 'xsd:int'),
  		array('c' => 'xsd:float'),
  		'http://inlife.si/'
  );
  
  $soap->register(
  		'authenticate',
  		array('username' => 'xsd:String', 'password' => 'xsd:String'),
//   		array('Password' => 'xsd:boolean', 'Active' => 'xsd:boolean'),
		array('c' => 'xsd:Array'),
  		'http://inlife.si'
  );
  
  $soap->service(isset($HTTP_RAW_POST_DATA) ?
    $HTTP_RAW_POST_DATA : '');
  function add($a, $b) {
    return $a + $b;
  }
  
  function subtract($a, $b) {
  	return $a - $b;
  }
  
  function divide($a, $b) {
  	return $a / $b;
  }

  function authenticate($username,$password){
  	require_once 'database.php';
  	$db = new Database();  	
  	$res  = $db->authenUser($username, $password);

  	
  	
//   	return $res['Password'];
	return $res;
  }

  
?>