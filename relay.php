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
$__port = 80; // the port of your server
$__max_relay = 1500; // how many server addresses the relay will store
$__save_messages = true; // true to store messages on the server --- you can't relay without saving the latest messages

// -------------------------
$__host = $_SERVER['SERVER_ADDR'].':'.$__port;

if(!file_exists("data/config/serv.lst")) {
    @mkdir("data");
    @mkdir("data/config");
    @file_put_contents("data/config/serv.lst","opensource.agencys.eu:80");
    @touch("data/config/user.lst");
}
if(isset($_GET['message'], $_GET['from'], $_GET['date'], $_GET['author'])) {

    if(strlen($_GET['message'])>200) {
        echo "error:too long";exit;
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
        $hash = sha1(htmlspecialchars($_GET['message']).$_GET['author']).md5(htmlspecialchars(substr($_GET['message'], 0, 200)).$_GET['date']);
        $from_serv_response = @file_get_contents("http://".$fdomain[0].":".$fport."/relay.php?hash=".$hash);
        if(!preg_match("#ok#",$from_serv_response)) { echo "error:unknown source";exit; }
        if(@fsockopen($domain[0], $port)) {
            if(!file_exists("data/".$hash.".snm")) {
                $main_serv_response = @file_get_contents("http://".$domain[0].":".$port."/relay.php?user=".urlencode($author[0])."&hash=".$hash);
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
                        $fgc = @file_get_contents("http://".$host[0].":".$hport."/relay.php?message=".urlencode($_GET['message'])."&author=".urlencode($_GET['author'])."&date=".urlencode($_GET['date'])."&from=".$__host);
                        
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
    }
}