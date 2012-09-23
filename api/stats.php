<?php
require_once("../includes/config.php");
require_once("../includes/functions.php");
ini_set('display_errors', 0);
$prefix[0] = '';
db_connect();
$response = "json";
$locationLimit = 5;
if (isset($_GET['apiKey']) && isset($_GET['alias'])) {
	$apiKey = mysql_real_escape_string(trim($_GET['apiKey']));
	$alias = mysql_real_escape_string(trim($_GET['alias']));
	if (isset($_GET['locationLimit']) && (int)$_GET['locationLimit'] != 0) {
		$locationLimit = mysql_real_escape_string(trim($_GET['locationLimit']));
	}

	$db_result = mysql_query("SELECT id,apiKey FROM ".DB_PREFIX."users");
	while ($db_row = mysql_fetch_assoc($db_result)) {
	        $validApiKey[$db_row['apiKey']] = 1;
	}

    if (!isset($validApiKey[$apiKey]) || $validApiKey[$apiKey] != 1) {
	$_ERROR[] = "08";
    }

	$db_result = mysql_query("SELECT url,code,alias,date_added FROM ".DB_PREFIX."urls WHERE alias='$alias' OR code='$alias'");
	if (mysql_num_rows($db_result) == 0) {
		$_ERROR[] = "09";
	} else {
		$db_row = mysql_fetch_assoc($db_result);
		$urls = $db_row;
		$code = $urls['code'];
	}
    if (strlen($alias) > 0) {
        if (!preg_match("/^[a-zA-Z0-9_-]+$/", $alias)) {
            $_ERROR[] = "05";
        }
    }
    if (count($_ERROR) == 0) {
	$db_result = mysql_query("SELECT SUM(clicks) AS clicks FROM ".DB_PREFIX."stats WHERE alias='$code'");
	$total = mysql_fetch_assoc($db_result);
	$totalClicks = $total['clicks'];

	$db_result = mysql_query("SELECT country,clicks FROM ".DB_PREFIX."stats WHERE alias='$code' GROUP BY country ORDER BY clicks DESC LIMIT ".$locationLimit);
	if (mysql_num_rows($db_result) == 0) {
		$_ERROR[] = "10";
	} else {
		while ($db_row = mysql_fetch_assoc($db_result)) {
			$location[] = $db_row;
		}
	}

        $short_url = get_phurl_option('site_url')."/".$code;

	if ($response == "json") {
		$json = array('code'=>'200', 'request'=>$_GET, 'url'=>$short_url, 
		'clicks'=>$totalClicks, 'location'=>$location, 'data'=>$urls);
		echo json_encode($json, JSON_FORCE_OBJECT);
		exit();
	}
    }
}
if (!isset($_GET['apiKey'])) {
	$_ERROR[] = "07";
}
if (!isset($_GET['alias'])) {
	$_ERROR[] = "05";
}
if ($response == "json") {
	echo json_encode(array('code'=>'400', 'error'=>$_ERROR), JSON_FORCE_OBJECT);
}
