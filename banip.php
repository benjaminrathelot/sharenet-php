<?php

/*

The MIT License (MIT)

Copyright (c) 2015 Jan Knipper <j.knipper@part.berlin>
Copyright (c) 2021 CS-Digital UG <info  @cs  -  digital-   ug . ~~ de >

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.

SOURCE: https://github.com/dmd2222/php-ban-ip
   */
    if (!empty($_POST['csd_identifier']) || !empty($_GET['csd_identifier'])) {
        var_dump("csd_identifier:","found-csd-identifier",(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
    }
    
/*
 * Settings
 */

const MAX_RETRY = 1000;
const FIND_TIME = 86400; //in seconds (86400 sec = 1 day)

const UNBAN_AFTER_X_SECONDS=86400; //in seconds (86400 sec = 1 day)

const IP_DB_FILE  = __DIR__ ."/ban_ip_db.txt";

$email_ricipiants_contacts = array("");


$develop_mode=false;



//disable errors, warnings and notice 0=all off
error_reporting(0);




if($develop_mode==true){
	echo 'Protected by ON BAN IP  <a href="https://github.com/dmd2222/php-ban-ip" target="_blank">?</a> - Ip: ' . get_ip() . ' Number of retries.: '.  ip_number_of_tries( get_ip() );
}




//secure db file
chmod(IP_DB_FILE,0600);

/*
 * Functions
 */

function sendmailtorecipients($email_ricipiants_contacts,$subject,$message){
	// $contacts array
	//   $contacts = array("youremailaddress@yourdomain.com","youremailaddress@yourdomain.com");
	//....as many email address as you need
	
			foreach($email_ricipiants_contacts as $contact) {
			
			$to = $contact;
			mail($to, $subject, $message);
			
			}
	
	
	}



function dirname_safe($path, $level = 0){
    $dir = explode(DIRECTORY_SEPARATOR, $path);
    $level = $level * -1;
    if($level == 0) $level = count($dir);
    array_splice($dir, $level);
    return implode($dir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
}

function check_ip( $ip ) {

	$ban = false;
	$db  = array();

	if ( file_exists( IP_DB_FILE ) ) {
		$db = load();
	}

	if ( ! empty( $db ) && array_key_exists( $ip, $db ) ) {

		$tdiff = time() - $db[ $ip ]["timestamp"];

		if ( $db[ $ip ]["retries"] >= $db[ $ip ]["granted_retries"] && $tdiff <= FIND_TIME ) {
			$ban = true;
			$db[ $ip ]["retries"]   = $db[ $ip ]["retries"] + 1;
		} elseif ( $tdiff > FIND_TIME ) {
			$db[ $ip ]["timestamp"] = time();
			$db[ $ip ]["retries"]   = 1;
		} else {
			$db[ $ip ]["timestamp"] = time();
			$db[ $ip ]["retries"]   = $db[ $ip ]["retries"] + 1;
		}
	} else {
		$db[ $ip ] = array( "timestamp" => time(), "retries" => 1 ,"granted_retries" => MAX_RETRY,"ip" => $ip);
	}

	save( $db );

	return $ban;
}


function ip_number_of_tries( $ip ) {


	$db  = array();

	if ( file_exists( IP_DB_FILE ) ) {
		$db = load();
	}

	if ( ! empty( $db ) && array_key_exists( $ip, $db ) ) {

		$tdiff = time() - $db[ $ip ]["timestamp"];

	} else {
		$db[ $ip ] = array( "timestamp" => time(), "retries" => 1 ,"granted_retries" => MAX_RETRY,"ip" => $ip);
	}

	save( $db );

	return $db[ $ip ]["retries"];
}


function check_ip_grant_access_unban( $ip ) {

	$unban = false;
	$db  = array();

	if ( file_exists( IP_DB_FILE ) ) {
		$db = load();
	}

	if ( ! empty( $db ) && array_key_exists( $ip["ip"], $db ) ) {

		$tdiff = time() - $db[ $ip ]["timestamp"];
		
		if($tdiff >= UNBAN_AFTER_X_SECONDS){

			$unban = true;
			// in db grant access
			$db[ $ip ]["timestamp"] = time();
			$db[ $ip ]["retries"]   = $db[ $ip ]["granted_retries"];


			//in .htaccess grant access
			unban_ip( $ip );
		}

	}

	save( $db );

	return $unban;
}

function ban_ip( $ip ) {
	$deny = sprintf( "\nDENY FROM %s", $ip );
	file_put_contents( dirname_safe(__DIR__, 1) . ".htaccess", $deny, FILE_APPEND );
}

function unban_ip( $ip ) {
	//creat deny string
	$deny_string = sprintf( "DENY FROM %s", $ip["ip"] );

	$file_path= dirname_safe(__DIR__, 1) . ".htaccess";

	//in .htaccess grant access
				// search line and delete it
				$lines = file($file_path); // reads a file into a array with the lines

				$output_text ="";
				foreach ($lines as $line) {
					if (!strstr(sha1($line) , sha1($deny_string))) {
						$output_text .= $line;
					} 
				}
		// replace the contents of the file with the output
		file_put_contents($file_path, $output_text);

}

function load() {
	return unserialize( file_get_contents( IP_DB_FILE ) );
}

function save( $data ) {
	return file_put_contents( IP_DB_FILE, serialize( $data ) );
}

function get_ip() {
	global $_SERVER;
	$ip = null;

	if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	return $ip;
}

/*
 * Get IP Address
 */

$ip = get_ip();

/*
 * Check IP and ban after MAX_RETRY
 */

if ( filter_var( $ip, FILTER_VALIDATE_IP ) && check_ip( $ip ) ) {




	//inform admin
	$email_text="Hi, here Ban_ip installed on: " . dirname(__FILE__) . " An device with the ip: " . $ip . " tried more than " . MAX_RETRY . " times at " . date("d.m.Y - H:i", time()) . " to send a request. The ip has been banned for " . UNBAN_AFTER_X_SECONDS . " seconds (" . (UNBAN_AFTER_X_SECONDS/60/60/24) . " days). Best regards";
	sendmailtorecipients($email_ricipiants_contacts,"Ban_ip information",$email_text);

	//Ban him and tell him
	ban_ip( $ip );


	//send response
	http_response_code( 401 );
	
	echo '
	<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
	<html>
	<head>
		<title>401 Authorization Required</title>
	</head>
	<body>
	<h1>Authorization Required</h1>
	
	<p>This server could not verify that you
		are authorized to access the document
		requested. Either you supplied the wrong
		credentials (e.g., ba2003:45:4b35:500:9dc0:f1a3:8f8f:7797d password), or your
		browser doesnt understand how to supply
		the credentials required.</p>
	</body>
	</html>';


	//Throw exception
	throw new Exception('You are not allowed to do this.');

	// Stop script
	die();
}else{

	//Normal request / no banning
		//Make things after user left
			//ignore_user_abort(true);
			//set_time_limit(0);
			//ob_end_flush();
			//flush();
			// do some work after user has been gone

			//go through hole db if it is more then 10 minutes gone
			$datetime_file= filemtime(__DIR__ ."/grant_access_check_datetime.txt");
			$datetime_now = time();
			$datetime_diff = $datetime_now-$datetime_file;


			if($datetime_diff >=10){ //600 seconds =10 minutes
					//write down last grant_access check
					file_put_contents(__DIR__ ."/grant_access_check_datetime.txt", $datetime_now);

					//secure file
					chmod(__DIR__ ."/grant_access_check_datetime.txt",0600);
					
					//Go through every ip in db
					$db  = array();

					if ( file_exists( IP_DB_FILE ) ) {
						$db = load();
					}
					
					if ( ! empty( $db ) ) {

						foreach ($db as $object_value) {
							$tdiff = time() - $object_value["timestamp"];
							if($tdiff >= UNBAN_AFTER_X_SECONDS){
								check_ip_grant_access_unban( $object_value );
							}
							
						}
						
						

					}



			}




}

/*
 * Send response
 */



?>
