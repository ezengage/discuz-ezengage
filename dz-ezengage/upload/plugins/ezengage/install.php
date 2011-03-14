<?php
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$inslang = $installlang['ezengage'];
$installurl = "$BASESCRIPT?action=plugins&operation=$operation&dir=$dir&installtype=$installtype&instype=";
$sql = "SHOW TABLES LIKE '%eze_profile%'";
$check = $db->fetch_array($db->query($sql));
$tag = 'Tables_in_'.$dbname.' (%eze_profile%)';

if(!$check[$tag]){
	$instype = 'new';
}

if($instype == '') {
	cpmsg($inslang['ask'], '', 'succeed',
	"<input type=\"button\" class=\"btn\" value=\" $inslang[newinstall] \" $disabledv1 onclick=\"javascript: window.location.href='{$installurl}new';\" /> &nbsp; ".
	"<input type=\"button\" class=\"btn\" value=\" $inslang[install] \" $disabledv2 onclick=\"javascript: window.location.href='{$installurl}remain';\" />");
} elseif($instype == 'new') {
	$newinstallsql = "
	DROP TABLE IF EXISTS `{$tablepre}eze_profile`;
    CREATE TABLE `{$tablepre}eze_profile` (
    `pid` mediumint(8) NOT NULL auto_increment,
    `token` varchar(50) NOT NULL,
    `uid` mediumint(8) unsigned NOT NULL default '0',
    `identity` varchar(255) NOT NULL,
    `provider_code` varchar(15) NOT NULL,
    `preferred_username` varchar(100) NOT NULL default '',
    `display_name` varchar(100) NOT NULL default '',
    `avatar_url` varchar(255) NOT NULL default '',
    `should_sync` tinyint(1) NOT NULL default '1',
    `enable_avatar` tinyint(1) NOT NULL default '1',
    `profile_json` text NOT NULL,
    PRIMARY KEY  (`pid`),
    UNIQUE KEY `token` (`token`),
    UNIQUE KEY `identity` (`identity`),
    KEY `uid` (`uid`)
    ) ENGINE=MyISAM;"
	runquery($newinstallsql);
	$finish = TRUE;
} elseif($instype == 'remain') {
	$finish = TRUE;
}



?>
