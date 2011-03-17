<?php

/*
	[Discuz!] (C)2001-2009 Comsenz Inc.
	This is NOT a freeware, use is subject to license terms

	$Id$
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$inslang = $installlang['ezengage'];
$uninstallurl = "$BASESCRIPT?action=plugins&operation=$operation&dir=$dir&installtype=$installtype&uninstype=";

if($uninstype == '') {
	cpmsg($inslang['askuninstall'], '', 'error', 
	"<input type=\"button\" class=\"btn\" value=\" $inslang[uninstall] \" $disabledv1 onclick=\"javascript: window.location.href='{$uninstallurl}remain';\" /> &nbsp; ".
	"<input type=\"button\" class=\"btn\" value=\" $inslang[alluninstall] \" $disabledv2 onclick=\"javascript: window.location.href='{$uninstallurl}all';\" />");
} elseif ($uninstype == 'all') {
	$sql = "DROP TABLE IF EXISTS `{$tablepre}eze_profile`;";
	runquery($sql);
	$finish = TRUE;
} elseif($uninstype == 'remain') {
	$finish = TRUE;
}
?>
