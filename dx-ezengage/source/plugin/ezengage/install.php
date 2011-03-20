<?php
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) {
	exit('Access Denied');
}

$inslang = $installlang['ezengage'];
$installurl = "{$_G[basefilename]}?action=plugins&operation=$operation&dir=$dir&installtype=$installtype&instype=";
$sql = "SHOW TABLES LIKE '%eze_profile'";
$check = DB::num_rows(DB::query($sql));

$instype = strval($_GET['instype']);
if($check <= 0){
    $instype = 'new';
}

if($instype == '') {
	cpmsg($inslang['ask'], '', 'succeed', null,
        "<input type=\"button\" class=\"btn\" value=\" $inslang[newinstall] \" onclick=\"javascript: window.location.href='{$installurl}new';\" /> &nbsp; ".
	"<input type=\"button\" class=\"btn\" value=\" $inslang[install] \" onclick=\"javascript: window.location.href='{$installurl}remain';\" />");
} elseif($instype == 'new') {
	$newinstallsql = "
	DROP TABLE IF EXISTS `cdb_eze_profile`;
    CREATE TABLE `cdb_eze_profile` (
    `pid` mediumint(8) NOT NULL auto_increment,
    `token` varchar(50) NOT NULL,
    `uid` mediumint(8) unsigned NOT NULL default '0',
    `identity` varchar(255) NOT NULL,
    `provider_code` varchar(15) NOT NULL,
    `provider_name` varchar(50) NOT NULL,
    `preferred_username` varchar(100) NOT NULL default '',
    `sync_list` varchar(100) NOT NULL default '',
    `avatar_url` varchar(255) NOT NULL default '',
    `enable_avatar` tinyint(1) NOT NULL default '1',
    PRIMARY KEY  (`pid`),
    UNIQUE KEY `token` (`token`),
    UNIQUE KEY `identity` (`identity`),
    KEY `uid` (`uid`)
    ) ENGINE=MyISAM;";
	runquery($newinstallsql);
	$finish = TRUE;
} elseif($instype == 'remain') {
	$finish = TRUE;
}



?>
