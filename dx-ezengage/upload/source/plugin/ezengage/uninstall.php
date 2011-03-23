<?php
/*
	ezengage (C)2011 
    http://ezengage.com/
*/

if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$inslang = $installlang['ezengage'];
$uninstallurl = "{$_G[basefilename]}?action=plugins&operation=$operation&dir=$dir&installtype=$installtype&uninstype=";

$uninstype = strval($_GET['uninstype']);
if($uninstype == '') {
	cpmsg($inslang['askuninstall'], '', 'error', null,
	"<input type=\"button\" class=\"btn\" value=\" {$inslang[uninstall]} \"  onclick=\"javascript: window.location.href='{$uninstallurl}remain';\" />&nbsp;" .
	"<input type=\"button\" class=\"btn\" value=\" {$inslang[alluninstall]} \" onclick=\"javascript: window.location.href='{$uninstallurl}all';\" />"
    );
} elseif ($uninstype == 'all') {
	$sql = "DROP TABLE IF EXISTS `cdb_eze_profile`;";
	runquery($sql);
	$finish = TRUE;
} elseif($uninstype == 'remain') {
	$finish = TRUE;
}
?>
