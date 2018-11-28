<?php
// this script simulates a SOAP server for Naviance SSO
// your PHP build must have LDAP support and you must also install the adLDAP library (https://github.com/Rich2k/adLDAP)

// load the adLDAP Library
require_once './adLDAP.php';

// if requesting the WSDL then return the naviance.wsdl file, otherwise process the authentication request.
if (isset($_GET['wsdl'])) {
	header("Content-Type: text/xml; charset=utf-8");
	echo file_get_contents('naviance.wsdl'); // this file is already formated for Naviance (see the example)
    exit;
}

// LOG the Traffic
	// get source IP and add it to the log_entry
	$clientIp = $_SERVER["REMOTE_ADDR"];
	$wsdl_tag = "?WSDL " . (isset($_GET['WSDL'])) ? "YES" : "NO";
	$log_entry = $clientIp . " - " . $wsdl_tag . "<br />";
	
	// set the log_entry filename
	$log_dir = dirname( __FILE__ ) . '/logs/';
	$log_name = "requests-" . $clientIp . "-" . date("Y-m-d-H") . ".log";

	// get all the headers
	$headers = "";
	foreach (getallheaders() as $name => $value) {
		$headers .= "$name: $value\n";
	}
	$log_entry .= $headers;
	
	// get the POST data (the XML payload sent from naviance)	
	$POST_DATA = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : '';	
	$log_entry .= ("-------------- XML Input Stream ----------------\n$POST_DATA\n\n");
	
	// save the log entry
	$fp=fopen( $log_dir . $log_name, 'a' ); 
	fputs($fp, $log_entry);
	fclose($fp);
// end LOG the Traffic

// proceed with authentication


// this is a sample XML payload for testing and debugging
$xml_from_naviance = '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="https://server.mydomain.com/navianceauthentication/types">
	<SOAP-NV:Body>
		<ns1:Authenticate>
			<username>testaccount</username>
			<password>xyZ&123</password>
			<userType>student</userType>
			<sourceIp>192.168.1.1</sourceIp>
			<passphrase>yoursecretpassphrase</passphrase>
		</ns1:Authenticate>
	</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

// this is the real payload (comment out this line if using the sample payload above)
$xml_from_naviance = $POST_DATA;

// search/parse the XML payload for relevant data
preg_match_all('/<username>(.*?)<\/username>/s', $xml_from_naviance, $matches);
$username = $matches[1][0];

preg_match_all('/<password>(.*?)<\/password>/s', $xml_from_naviance, $matches);
$password = html_entity_decode( $matches[1][0] ); 

preg_match_all('/<passphrase>(.*?)<\/passphrase>/s', $xml_from_naviance, $matches);
$passphrase = $matches[1][0];

// uncomment below to show/debug the parsing results
//		echo "
//			Username: $username <br />
//			Password: $password <br />
//			Passphrase: $passphrase <br />
//		";
//		exit;

// first verify the passphrase as specified in the SSO settings from Naviance
if ($passphrase=='yoursecretpassphrase') {

	// look up username & password -- LDAP users
	$adldap = new adLDAP();
	if ($adldap->authenticate($username, $password)) {
		
		// looks good, get the user details
		$result = $adldap->user()->info($username,array('displayname'));
		$display_name = $result[0]['displayname'][0];
		$resultCode = 0;
		$authResult = TRUE;
		$resultMessage = 'Service call completed';
		$resultDescription = 'User was authenticated.';
		
		// check for inactive account here (unsupported)
		
	} else {
			
		// looks bad, password or username is incorrect
		$resultCode = 0;
		$authResult = FALSE;
		$resultMessage = 'Service call completed';
		$resultDescription = 'User was not authenticated.';

	}
	unset($adldap);

} else {
			
		// passphrase error
		$resultCode = 2;				
		$authResult = FALSE;
		$resultMessage = 'Service call completed';
		$resultDescription = 'Incorrect Passphrase';
		
}

$authResult_str = ($authResult) ? 'true' : 'false';

// construct and send the response XML that naviance is expecting
echo 
'<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:ns1="https://server.mydomain.com/navianceauthentication/types">
<SOAP-ENV:Body>
<ns1:AuthenticateResponse>
<AuthenticateResult>'.$authResult_str.'</AuthenticateResult>
<ResultMetaData>
<ResultCode>'.$resultCode.'</ResultCode>
<ResultMessage>'.$resultMessage.'</ResultMessage>
<ResultDescription>'.$resultDescription.'</ResultDescription>
</ResultMetaData>
</ns1:AuthenticateResponse>
</SOAP-ENV:Body>
</SOAP-ENV:Envelope>';

exit;	
?>
