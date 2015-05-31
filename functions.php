<?php
/**************************************************************************
 **********      English Wikipedia Account Request Interface      **********
 ***************************************************************************
 ** Wikipedia Account Request Graphic Design by Charles Melbye,           **
 ** which is licensed under a Creative Commons                            **
 ** Attribution-Noncommercial-Share Alike 3.0 United States License.      **
 **                                                                       **
 ** All other code are released under the Public Domain                   **
 ** by the ACC Development Team.                                          **
 **                                                                       **
 ** See CREDITS for the list of developers.                               **
 ***************************************************************************/

global $ACC;
global $baseurl;
global $dontUseWikiDb;

if (!defined("ACC")) {
	die();
} // Invalid entry point

require_once 'queryBrowser.php';
require_once 'LogClass.php';
require_once 'includes/session.php';

// Initialize the class objects.
$session = new session();

/**
 * Send a "close pend ticket" email to the end user. (created, taken, etc...)
 */
function sendemail($messageno, $target, $id) 
{
    $template = EmailTemplate::getById($messageno, gGetDb());
	$headers = 'From: accounts-enwiki-l@lists.wikimedia.org';
	    
	// Get the closing user's Email signature and append it to the Email.
	if(User::getCurrent()->getEmailSig() != "") 
    {
		$emailsig = html_entity_decode(User::getCurrent()->getEmailSig(), ENT_QUOTES, "UTF-8");
		mail($target, "RE: [ACC #$id] English Wikipedia Account Request", $template->getText() . "\n\n" . $emailsig, $headers);
	}
	else 
    {
		mail($target, "RE: [ACC #$id] English Wikipedia Account Request", $template->getText(), $headers);
	}
}

/**
 * Show the login page
 */
function showlogin() 
{
    global $smarty;
    
	// Check whether there are any errors.
    $errorbartext = "";
	if (isset($_GET['error'])) {
		if ($_GET['error']=='authfail') 
        {
            $errorbartext = BootstrapSkin::displayAlertBox("Username and/or password incorrect. Please try again.", "alert-error","Auth failure",true,false,true);
		} 
        elseif ($_GET['error']=='noid') 
        {
            $errorbartext = BootstrapSkin::displayAlertBox("User account is not identified. Please email accounts-enwiki-l@lists.wikimedia.org if you believe this is in error.", "alert-error","Auth failure",true,false,true);
		} 
        elseif ($_GET['error']=='newacct') 
        {
            $errorbartext = BootstrapSkin::displayAlertBox("I'm sorry, but, your account has not been approved by a site administrator yet. Please stand by.", "alert-info","Account pending",true,false,true);
		}
	}
    $smarty->assign("errorbar", $errorbartext);   
    
    $smarty->display("login.tpl");
}

function defaultpage() {
	global $availableRequestStates, $defaultRequestStateKey, $requestLimitShowOnly, $enableEmailConfirm;
    
    $database = gGetDb();
    
    $requestSectionData = array();
    
    if ($enableEmailConfirm == 1) 
    {		
        $query = "SELECT * FROM request WHERE status = :type AND emailconfirm = 'Confirmed' LIMIT :lim;";
        $totalquery = "SELECT COUNT(*) FROM request WHERE status = :type AND emailconfirm = 'Confirmed';";
    } 
    else 
    {
        $query = "SELECT * FROM request WHERE status = :type LIMIT :lim;";
        $totalquery = "SELECT COUNT(*) FROM request WHERE status = :type;";
    }
    
    $statement = $database->prepare($query);
    $statement->bindValue(":lim", $requestLimitShowOnly, PDO::PARAM_INT);
    
    $totalRequestsStatement = $database->prepare($totalquery);
            
	// list requests in each section
	foreach($availableRequestStates as $type => $v) 
    {
        $statement->bindValue(":type", $type);
        $statement->execute();
        
        $requests = $statement->fetchAll(PDO::FETCH_CLASS, "Request");
        foreach($requests as $req)
        {
            $req->setDatabase($database);   
        }

        $totalRequestsStatement->bindValue(":type", $type);
        $totalRequestsStatement->execute();
        $totalRequests = $totalRequestsStatement->fetchColumn();
        $totalRequestsStatement->closeCursor();
        
        $requestSectionData[$v['header']] = array(
            "requests" => $requests, 
            "total" => $totalRequests, 
            "api" => $v['api']);
    }
    
    global $smarty;
    $smarty->assign("requestLimitShowOnly", $requestLimitShowOnly);
	
    $query = <<<SQL
        SELECT r.id, r.name, r.checksum
        FROM request r 
        JOIN acc_log l ON r.id = l.log_pend 
        WHERE l.log_action LIKE 'Closed%' 
        ORDER BY l.log_time DESC 
        LIMIT 5;
SQL;
    
    $statement = $database->prepare($query);
    $statement->execute();
    
    $last5result = $statement->fetchAll(PDO::FETCH_ASSOC);
    
    $smarty->assign("lastFive", $last5result);
    $smarty->assign("requestSectionData", $requestSectionData);
    $html = $smarty->fetch("mainpage/mainpage.tpl");
    
	return $html;
}

function array_search_recursive($needle, $haystack, $path=array())
{
	foreach($haystack as $id => $val)
	{
		$path2=$path;
		$path2[] = $id;

		if($val === $needle)
		return $path2;
		else if(is_array($val))
		if($ret = array_search_recursive($needle, $val, $path2))
		return $ret;
	}
	return false;
}

require_once('zoompage.php');

function displayPreview($wikicode) {
	$parseresult = unserialize(file_get_contents('http://en.wikipedia.org/w/api.php?action=parse&format=php&text='.urlencode($wikicode)));
	$out = "<br />\n<h3>Preview</h3>\n<div style=\"border: 2px dashed rgb(26, 79, 133);\">\n<div style=\"margin: 20px;\">";
	$out .= $parseresult['parse']['text']['*'];
	$out .= '</div></div>';
	return $out;
}

/**
 * A simple implementation of a bubble sort on a multidimensional array.
 *
 * @param array $items A two-dimensional array, to be sorted by a date variable
 * in the 'time' field of the arrays inside the array passed.
 * @return array sorted array.
 */
function doSort(array $items)
{
	// Loop through until it's sorted
	do{
		// reset flag to false, we've not made any changes in this iteration yet
		$flag = false;
		
		// loop through the array
        $loopLimit = (count($items) - 1);
		for ($i = 0; $i < $loopLimit; $i++) {
			// are these two items out of order?
			if(strtotime($items[$i]['time']) > strtotime($items[$i + 1]['time']))
			{
				// swap them
				$swap = $items[$i];
				$items[$i] = $items[$i + 1];
				$items[$i + 1] = $swap;
				
				// set a flag to say we've modified the array this time around
				$flag = true;
			}
		}
	}
	while($flag);
	
	// return the array back to the caller
	return $items;
}

/**
 * @return string
 */
function getTrustedClientIP($dbip, $dbproxyip)
{
    global $xffTrustProvider;
    
	$clientIpAddr = $dbip;
	if($dbproxyip)
	{
		$ipList = explode(",", $dbproxyip);
		$ipList[] = $clientIpAddr;
		$ipList = array_reverse($ipList);
		
		foreach($ipList as $ipnumber => $ip)
        {
			if($xffTrustProvider->isTrusted(trim($ip)) && $ipnumber < (count($ipList) - 1)) 
            {
                continue;
            }
			
			$clientIpAddr = $ip;
			break;
		}
	}
	
	return $clientIpAddr;
}

function explodeCidr( $range ) 
{
	$ip_arr = explode( '/' , $range );

	if( ! isset( $ip_arr[1] ) ) 
    {
		return array( $range );
	}
	
	$blow = ( 
		str_pad( decbin( ip2long( $ip_arr[0] ) ), 32, "0", STR_PAD_LEFT) &
		str_pad( str_pad( "", $ip_arr[1], "1" ), 32, "0" ) 
		);
	$bhigh = ($blow | str_pad( str_pad( "", $ip_arr[1], "0" ), 32, "1" ) );

	$list = array();

    $bindecBHigh = bindec( $bhigh );
	for($x = bindec($blow); $x <= $bindecBHigh; $x++) 
    {
		$list[] = long2ip( $x );
	}

	return $list;
}

/**
 * Takes an array( "low" => "high ) values, and returns true if $needle is in at least one of them.
 * @param string $ip
 */
function ipInRange( $haystack, $ip ) 
{
	$needle = ip2long($ip);

	foreach( $haystack as $low => $high ) 
    {
		if( ip2long($low) <= $needle && ip2long($high) >= $needle ) 
        {
			return true;
		}
	}
    
	return false;
}

/**
 * @return string
 */
function welcomerbotRenderSig($creator, $sig) 
{
	$signature = html_entity_decode($sig) . ' ~~~~~';
	if (!preg_match("/((\[\[[ ]*(w:)?[ ]*(en:)?)|(\{\{subst:))[ ]*User[ ]*:[ ]*".$creator."[ ]*(\]\]|\||\}\}|\/)/i", $signature)) 
    {
		$signature = "--[[User:$creator|$creator]] ([[User talk:$creator|talk]]) ~~~~~";
	}
	return $signature;
}

/**
 * Transforms a date string into a relative representation of the date ("2 weeks ago").
 * @param string $input A string representing a date
 * @return string
 * @example {$variable|relativedate} from Smarty
 */
function relativedate($input) 
{
    $now = new DateTime();
    $then = new DateTime($input);
    
    $secs = $now->getTimestamp() - $then->getTimestamp();
    
    $second = 1;
    $minute = 60 * $second;
    $minuteCut = 60 * $second;
    $hour = 60 * $minute;
    $hourCut = 60 * $minute;
    $day = 24 * $hour;
    $dayCut = 48 * $hour;
    $week = 7 * $day;
    $weekCut = 14 * $day;
    $month = 30 * $day;
    $year = 365 * $day;
    
    $pluralise = true;
    
    if ($secs <= 10) 
    {
        $output = "just now";
        $pluralise = false;
    }
    elseif ($secs > 10 && $secs < $minuteCut) 
    {
        $output = round($secs/$second) . " second";
    }
    elseif ($secs >= $minuteCut && $secs < $hourCut) 
    {
        $output = round($secs/$minute) . " minute";
    }
    elseif ($secs >= $hourCut && $secs < $dayCut) 
    {
        $output = round($secs/$hour) . " hour";
    }
    elseif ($secs >= $dayCut && $secs < $weekCut) 
    {
        $output = round($secs/$day) . " day";
    }
    elseif ($secs >= $weekCut && $secs < $month) 
    {
        $output = round($secs/$week) . " week";
    }
    elseif ($secs >= $month && $secs < $year) 
    {
        $output = round($secs/$month) . " month";
    }
    elseif ($secs >= $year && $secs < $year * 10) 
    {
        $output = round($secs/$year) . " year";
    }
    else
    { 
        $output = "a long time ago";
        $pluralise = false;
    }
    
    if ($pluralise)
    {
        $output = (substr($output,0,2) <> "1 ") ? $output . "s ago" : $output . " ago";
    }

    return $output;
}

function reattachOAuthAccount(User $user)
{
    global $oauthConsumerToken, $oauthSecretToken, $oauthBaseUrl, $oauthBaseUrlInternal;

    try
    {
        // Get a request token for OAuth
        $util = new OAuthUtility($oauthConsumerToken, $oauthSecretToken, $oauthBaseUrl, $oauthBaseUrlInternal);
        $requestToken = $util->getRequestToken();

        // save the request token for later
        $user->setOAuthRequestToken($requestToken->key);
        $user->setOAuthRequestSecret($requestToken->secret);
        $user->save();
            
        $redirectUrl = $util->getAuthoriseUrl($requestToken);
            
        header("Location: {$redirectUrl}");
        die();
    }
    catch(Exception $ex)
    {
        throw new TransactionException($ex->getMessage(), "Connection to Wikipedia failed.", "alert-error", 0, $ex);
    }     
}
