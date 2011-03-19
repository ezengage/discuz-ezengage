<?php
/*
	ezEngage (C)2011  http://ezengage.com
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if(!$_G['uid']) {
	showmessage('not_loggedin', NULL, 'NOPERM');
}

@include_once DISCUZ_ROOT.'./source/plugin/ezengage/common.func.php';

if($_G['gp_pluginop'] == 'update' && submitcheck('updateuser')) {
    if(!empty($_G['gp_delete'])) {
		DB::query("DELETE FROM " . DB::table('eze_profile') ." WHERE uid='$_G[uid]' AND pid IN (".dimplode($_G['gp_delete']).")");
	}
    if(!empty($_G['gp_should_sync'])){
		DB::query("UPDATE " . DB::table('eze_profile') . " SET should_sync = 1 WHERE uid='$_G[uid]' AND pid IN (".dimplode($_G['gp_should_sync']).")");
		DB::query("UPDATE " . DB::table('eze_profile') . " SET should_sync = 0 WHERE uid='$_G[uid]' AND pid NOT IN (".dimplode($_G['gp_should_sync']).")");
	}
    else{
		DB::query("UPDATE " . DB::table('eze_profile') . " SET should_sync = 0 WHERE uid='$_G[uid]'");
    }
	showmessage('ezengage:updateuser_succeed', 'home.php?mod=spacecp&ac=plugin&id=ezengage:accounts');
}
	
$eze_profiles = eze_get_profiles($_G[uid]);

$_G['basescript'] = 'home';
?>
