<?
if (isset($_SERVER['REQUEST_METHOD'])) {
	die();
} // Web clients die.
ini_set('display_errors', 1);
require_once("includes/WebStart.php");
require_once 'config.inc.php';

$strictMode = 0;

function entryFromString($line) {
	// Function adapted from MediaWiki's source code. Original function can be found at:
	// http://svn.wikimedia.org/svnroot/mediawiki/trunk/extensions/TitleBlacklist/TitleBlacklist.list.php
	$options = array();
	$line = preg_replace ("/^\\s*([^#]*)\\s*((.*)?)$/", "\\1", $line);
	$line = trim ($line);
	preg_match('/^(.*?)(\s*<([^<>]*)>)?$/', $line, $pockets);
	@list($full, $regex, $null, $opts_str) = $pockets;
	$regex = trim($regex);
	$regex = str_replace('_', ' ', $regex);
	$opts_str = trim($opts_str);
	$opts = preg_split('/\s*\|\s*/', $opts_str);
	$casesensitive = false;
	foreach ($opts as $opt) {
		$opt2 = strtolower($opt);
		if ($opt2 == 'moveonly') {
			return null;
		}
		if ($opt2 == 'casesensitive') {
			$casesensitive = true;
		}
	}
	if ($regex) {
		return array($regex, $casesensitive);
	} else {
		return null;
	}
}

$queryresult = unserialize(file_get_contents("http://en.wikipedia.org/w/api.php?action=query&format=php&prop=revisions&titles=MediaWiki:Titleblacklist&rvprop=content"));
$queryresult = current($queryresult['query']['pages']);

$text = $queryresult['revisions'][0]['*'];
$lines = preg_split("/\r?\n/", $text);
$result = array();
foreach ($lines as $line) {
	$line = entryFromString($line);
	if ($line) {
		$entries[] = $line;
	}
}

mysql_connect($toolserver_host, $toolserver_username, $toolserver_password);
@ mysql_select_db($toolserver_database) or die(mysql_error());

$sanitycheck=array();

mysql_query("SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;");

if(mysql_query("START TRANSACTION;"))
{
	$success1 = mysql_query("DELETE FROM `acc_titleblacklist`;");
	if(!$success1)
	{
		echo mysql_error()."\n";
		mysql_query("ROLLBACK;");
		echo "Error in transaction.\n";
	}	
		
	$query = "INSERT INTO `acc_titleblacklist` (`titleblacklist_regex`, `titleblacklist_casesensitive`) VALUES ";
	foreach ($entries as $entry) {
		list($regex, $casesensitive) = $entry;
		
		$regex = mysql_real_escape_string($regex);
		
		if(array_key_exists($regex, $sanitycheck))
			continue;
			
		$sanitycheck[$regex]=1;
		
		$rquery = $query . "('$regex', ";
		if ($casesensitive)
			$rquery .= 'TRUE';
		else
			$rquery .= 'FALSE';
		$rquery .= ");";
		
		$success2 = mysql_query($rquery);
		if(!$success2)
		{
			echo mysql_error()."\n";
			if($strictMode == 1)
			{
				mysql_query("ROLLBACK;");
				echo "Error in transaction.\n";
				break;
			}
		}
	}
	
	mysql_query("COMMIT;");
	echo "The title blacklist table has been recreated.\n";
}
else
	echo "Error starting transaction.\n";

mysql_close();
?>
