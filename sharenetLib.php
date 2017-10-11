<?php
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
$sharenetHost = "mydomain.com:80";



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
	    $date = time();
	    $author = $user."@".$host;
	    if(strlen($message)<201) {
	        $hash = sha1(htmlspecialchars($message.$author).md5(htmlspecialchars(substr($message, 0, 200)).$date);
	        $file = array("hash"=>$hash, "date"=>$date, "from"=>$host, "author"=>$author, "message"=>$message);
	        file_put_contents("data/".$hash.".snm", json_encode($file));

	        $s = file_get_contents("data/config/serv.lst");
	        $r_list = preg_split('/\r?\n/', $s);
	        foreach($r_list as $relay) {
	            $get = file_get_contents("http://".$relay."/relay.php?message=".urlencode($message)."&author=".$author."&date=".$date."&from=".$host);
	            if(preg_match("#ok#", $get)) {
	                echo "ok: ".$relay;
	            }
	            else
	            {
	                echo htmlspecialchars($get).">>".$relay;
	            }
	        }
	    }
	}
}

function sharenetOpenMessage($hash) {
	return json_decode(file_get_contents("data/".$hash.".snm"));
}
