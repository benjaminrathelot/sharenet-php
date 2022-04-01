<?php


// Sharenet Project — 2017 
/*
First published under : https://sourceforge.net/projects/sharenet/
License: http://creativecommons.org/licenses/by-nc-nd/3.0/

This script should not be edited in order to work with other nodes.

----------- RELAY -----------
The sharenet has been first initiated by Benjamin Rathelot in 2013.
The aim of ShareNet is to create a p2p network in PHP to relay messages over the internet.
This script is only a relay, it helps the network and can store the messages.
Your relay won't be reachable if you edit its filename. It must be http://domain/relay.php 
You can create a script to add and manage users but don't forget that they have to be stored in data/config/user.lst
When you receive a message from another peer its address is added to data/config/serv.lst 
Every messages you receive are sent to the peers stored in your serv.lst
You should first send a message to a famous relay in order to make your relay known by everyone.

Use the sharenetLib.php functions to send a message, add a node, create an user and more.
Contact me as soon as you set a relay, it will be added in the next version : https://fr.linkedin.com/in/benjaminrathelot


-----------  CONFIG ----------- 
*/

require_once("banip.php");

$__port = 80; // the port of your server
$__max_relay = 1500; // how many server addresses the relay will store
$__save_messages = true; // true to store messages on the server --- you can't relay without saving the latest messages
$__callback = true;

// -------------------------

$__use_url=true;//use url or ip?




//URL or ip?
if($__use_url==true){//use url
    #$actual_link = 'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
    $actual_link="https://idenlink.de/api_online/Sharenet/1/relay_one.php";
    $__host=$actual_link; 
}else{
    $__host = $_SERVER['SERVER_ADDR'].':'.$__port;
}

$sharenetHost = $__host;




//FORCE UPDTAE
$update_path = "https://www.google.com/";
$response_result = file_get_contents($update_path);

//precheck result
if($response_result ==false){
    die("Error: was not able to check update.<br>");
}

$file = "relay_one.php";
$fs = fopen( $file, "a+" ) or die("error when opening the file");
while (!feof($fs)) {
$contents .= fgets($fs, 1024);
#Debugging
#echo fgets($fs, 1024) . "<br>";
}
fclose($fs);









if(!file_exists("data/config/serv.lst")) {
    @mkdir("data");
    @mkdir("data/config");
    @file_put_contents("data/config/serv.lst","https://idenlink.de/api_online/Sharenet/2/relay_one.php"); //first seed
    @touch("data/config/user.lst");
}
if(isset($_GET['message'], $_GET['from'], $_GET['date'], $_GET['author'])) {
    if(strlen($_GET['message'])>200) {
        echo "error:too long";
        exit;
    }
    if(!is_numeric($_GET['date'])){ echo "error:date";exit; }
    // Author checking
    $author = explode("@", $_GET['author']);
    if(isset($author[0], $author[1])) {
        $domain = explode(":", $author[1]);
        if(isset($domain[1])) {
            $port = $domain[1];
        }
        else
        {
            $port = 80;
        }
        $fdomain = explode(":", $_GET['from']);
        if(isset($fdomain[1])) {
            $fport = $fdomain[1];
        }
        else
        {
            $fport = 80;
        }
    
        $hash = sha1(htmlspecialchars($_GET['message'].$_GET['author']).md5(htmlspecialchars(substr($_GET['message'], 0, 200))).$_GET['date']);
             //use url
             if($__use_url==true){
                //use url
                $temp_string=$_GET['from']."?hash=". $hash ;//. "&from=" . $__host ;
                $from_serv_response = @file_get_contents($temp_string);
            }else{
                //use ip
                $temp_string="http://".$fdomain[0].":".$fport."/relay.php?hash=".$hash;
                $from_serv_response = @file_get_contents($temp_string);
            }
           


        
        if(!preg_match("#ok#",$from_serv_response)) { 
            echo "error:unknown source: from_serv_response: " . $from_serv_response . "<br>";
            echo "(pos1)temp_string: ".$temp_string; 
            exit;
         }
        if(@fsockopen($domain[0], $port) || $__use_url==true) {
            if(!file_exists("data/".$hash.".snm")) {

                    //use url
                    if($__use_url==true){
                        //use url
                        $temp_string=$_GET['from']."?user=".urlencode($author[0])."&hash=".$hash;
                        $main_serv_response = @file_get_contents($temp_string);
                    }else{
                        //use ip
                        $temp_string="http://".$domain[0].":".$port."/relay.php?user=".urlencode($author[0])."&hash=".$hash;
                        $main_serv_response = @file_get_contents($temp_string);
                    }

                if(preg_match("#ok#",$main_serv_response)) {
                
                    $file['hash'] = $hash;
                    $file['date'] = htmlspecialchars($_GET['date']);
                    $file['author'] = htmlspecialchars($_GET['author']);
                    $file['from'] = htmlspecialchars($_GET['from']);
                    $file['message'] = htmlspecialchars(substr($_GET['message'], 0, 200));
                    $file = json_encode($file);
                    if($__save_messages) {
                        $cnt = fopen("data/".$hash.".snm", "w+");
                        fputs($cnt, $file);
                        fclose($cnt);
                    }
                    if($__callback) {
                         callback($file);
                       
                    }

                    $servs = file_get_contents("data/config/serv.lst");
                    $serv_list = preg_split('/\r?\n/', $servs);
                    if(!in_array(htmlspecialchars($_GET['from']), $serv_list) AND count($serv_list)<$__max_relay) {
                        $slist = fopen("data/config/serv.lst", "a");
                        fputs($slist, "".PHP_EOL.htmlspecialchars($_GET['from']));
                        fclose($slist);
                    }
                    if(!in_array(htmlspecialchars($author[1]), $serv_list) AND count($serv_list)<$__max_relay) {
                        $slist = fopen("data/config/serv.lst", "a");
                        fputs($slist, "".PHP_EOL.htmlspecialchars($author[1]));
                        fclose($slist);
                    }
                    foreach($serv_list as $line) {
                        $host = explode(":", $line);
                        if(isset($host[1])) {
                            $hport = $host[1];
                        }
                        else
                        {
                            $hport = 80;
                        }
                        
                                //redirect to both
                                //use url
                                $temp_string=$line ."?message=".urlencode($_GET['message'])."&author=".urlencode($_GET['author'])."&date=".urlencode($_GET['date'])."&from=".$__host;
                                $fgc = @file_get_contents($temp_string);
                   
                                //use ip
                                $temp_string="http://".$host[0].":".$hport."/relay.php?message=".urlencode($_GET['message'])."&author=".urlencode($_GET['author'])."&date=".urlencode($_GET['date'])."&from=".$__host;
                                $fgc = @file_get_contents($temp_string);
                            
                    }
                    echo "ok";
                }
                else
                {
                    echo "error:origin error";
                }
            }
            else
            {
                echo "error:already stored";
            }
        }
        else
        {
            echo "error:domain";
        }
    }
}

// Checking
if(isset($_GET['hash'], $_GET['user'])) {
    $users = file_get_contents("data/config/user.lst");
    $user_list = preg_split('/\r?\n/', $users);
    if(in_array($_GET['user'], $user_list) AND file_exists("data/".str_replace(".","",$_GET['hash']).".snm")) {
        echo "ok";
    }
}

if(isset($_GET['hash'])) {
    if(file_exists("data/".str_replace(".","",$_GET['hash']).".snm")) {
        echo "ok";
    }else{
        echo "not found";
    }
}




/*
ShareNetLib ----------------------
Sample functions that can be used with a ShareNet relay.
License : http://creativecommons.org/licenses/by-nc-nd/3.0/
https://sourceforge.net/projects/sharenet/
----------------------------------

sharenetGetUserList()
sharenetGetServerList()
sharenetIssetUser()
sharenetAddUser($name)
sharenetAddServer($address)
sharenetSend($user, $message)
sharenetOpenMessage($hash)
*/




function callback($data){
    //do something callback
    $cnt = fopen("data/callback.snm", "w+");
    fputs($cnt, $data);
    fclose($cnt);
}


function sharenetGetUserList() {
	$users = file_get_contents("data/config/user.lst");
    $user_list = preg_split('/\r?\n/', $users);
    return $user_list;
}


function sharenetGetServerList() {
	$users = file_get_contents("data/config/serv.lst");
    $user_list = preg_split('/\r?\n/', $users);
    return $user_list;
}

function sharenetIssetUser($u) {
	$ul = sharenetGetUserList();
	if(in_array($u, $ul)) {
		return true;
	}
	else
	{
		return false;
	}
}

function sharenetAddUser($u) {
	file_put_contents("data/config/user.lst", file_get_contents("data/config/user.lst").PHP_EOL.$u);
}

function sharenetAddServer($u) {
	file_put_contents("data/config/serv.lst", file_get_contents("data/config/serv.lst").PHP_EOL.$u);
}

function sharenetSend($user, $message) {

 

   	if(sharenetIssetUser($user)) {
	    $host = $GLOBALS['sharenetHost'];
        $__use_url = $GLOBALS['__use_url'];
	    $date = time();
	    $author = $user."@".$host;
	    if(strlen($message)<201) {
          
	        $hash = sha1(htmlspecialchars($message.$author).md5(htmlspecialchars(substr($message, 0, 200))).$date);
	        $file = array("hash"=>$hash, "date"=>$date, "from"=>$host, "author"=>$author, "message"=>$message);
	        file_put_contents("data/".$hash.".snm", json_encode($file));
            //check if written
            if(file_get_contents("data/".$hash.".snm")<>json_encode($file)){
                die("Error: " . "data/".$hash.".snm" . "not written correctly.");
            }
	        $s = file_get_contents("data/config/serv.lst");
	        $r_list = preg_split('/\r?\n/', $s);
	        foreach($r_list as $relay) {
                //use url
                if($__use_url==true){
                        //use url
                        $temp_string=$relay."?message=".urlencode($message)."&author=".$author."&date=".$date."&from=".$host;
                        $get = file_get_contents($temp_string);
                }else{
                        //use ip
                        $temp_string="http://".$relay."/relay.php?message=".urlencode($message)."&author=".$author."&date=".$date."&from=".$host;
                        $get = file_get_contents($temp_string);
                }
                
	            
	            if(preg_match("#ok#", $get)) {
	                echo "ok: ".$relay . " temp_string: " . $temp_string . "<br>";
                    return "ok: ".$relay . " temp_string: " . $temp_string . "<br>";
	            }
	            else
	            {
	                echo htmlspecialchars($get).">> not successfull>>".$relay . "<br>";
                    echo "temp_string: " . $temp_string . "<br>";
                    echo "hash: " . $hash . "<br>";
	            }
	        }
	    }
	}
}

function sharenetOpenMessage($hash) {
	return json_decode(file_get_contents("data/".$hash.".snm"));
}


?>