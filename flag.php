<?php
//go.php handles the session and xss check for admin
//pages and pages where a session is necessary
require_once "go.php";
//functions.php gives us access to getRealIpAddr() function
require_once "functions.php";
//Mail.php is the PEAR script that includes the mail class for sending mail
require_once "Mail.php";

//check for xss attempt
if ($_POST['xsrfkey'] != $_SESSION['xsrfkey']) {
	die("Session variables do not match");
}

//try to do this and catch the error if there is an issue
try {
	//get the statement object for this insert statement
  $insert = $connection->prepare("INSERT INTO flag (code, user, ipaddress, institution) VALUES (?, ?, ?, ?)");
  
  //we want to add the current code to the session array
  //"flagged" so we know the user has flagged this code
  $_SESSION['flagged'][] = $_POST["code"];
  
  
  //bind the values represented by the "?" in the statement
  //first bind code
  $insert->bindValue(1, $_POST["code"]);
  if (isset($_SESSION["AUTH"])) {
  	//bind the logged in user
  	$insert->bindValue(2, $_SESSION["AUTH"]->getId());
  } else {
  	//otherwise just leave the user field blank
  	$insert->bindValue(2, '');
  }
  //bind the ipaddress
  $insert->bindValue(3, getRealIpAddr());
  
  //bind the institution
  $insert->bindValue(4, $_POST["institution"]);
  
  //finally execute the statement
  $insert->execute();
  
  //send mail to each go admin indicating that this 
  //code has been flagged using the goAdmin array
  //from config.php to get the emails of each admin
  foreach ($goAdmin as $current_admin) {
  //$to = array('lafrance@middlebury.edu');
  $to[] = GoAuthCas::getEmail($current_admin);
  }
  $headers['From'] = 'go@middlebury.edu';
  $headers['Subject'] = 'The go code '.$_POST["code"].' was flagged as linking to inappropriate content.';
  if (isset($_SESSION["AUTH"])) {
  $body = 'The GO code (aka. link) "'.$_POST["code"].'" was flagged by '.$_SESSION["AUTH"]->getName().' from '.getRealIpAddr().' as linking to inappropriate content. Please administer this flag via the admin interface.

- The GO application';
} else {
	$body = 'The GO code (aka. link) "'.$_POST["code"].'" was flagged by Anon from '.getRealIpAddr().' as linking to inappropriate content. Please administer this flag via the admin interface.

- The GO application';	
	}
  $message = Mail::factory('mail');
  foreach ($to as $current_address) {
  	$message->send($current_address, $headers, $body);
  }
//now catch any exceptions
} catch (Exception $e) {
	throw $e;
}

//redirect on completion
header("location: info.php?code=".$_POST['code']);
?>



