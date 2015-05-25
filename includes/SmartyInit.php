<?php
if (!defined("ACC")) {
    die();
} // Invalid entry point

require_once 'vendor/smarty/smarty/libs/Smarty.class.php';

global $smarty, $smartydebug;
$smarty = new Smarty();

$toolVersion = Environment::getToolVersion();
$currentUser = User::getCurrent();

$smarty->assign("baseurl", $baseurl);
$smarty->assign("wikiurl", $wikiurl);
$smarty->assign("mediawikiScriptPath", $mediawikiScriptPath);
$smarty->assign("toolversion", $toolVersion);
$smarty->assign("currentUser", $currentUser);
$smarty->debugging = $smartydebug;
