<?php
/*
	ezEngage (C)2011  http://ezengage.com
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

if(!$discuz_uid) {
	showmessage('not_loggedin', NULL, 'NOPERM');
}

@include_once DISCUZ_ROOT.'./plugins/ezengage/common.inc.php';

if($op == 'update' && submitcheck('updateuser')) {
    if(!empty($delete)) {
		$db->query("DELETE FROM {$tablepre}eze_profile WHERE uid='$discuz_uid' AND pid IN (".implodeids($delete).")");
	}
    if(!empty($should_sync)){
		$db->query("UPDATE {$tablepre}eze_profile SET should_sync = 1 WHERE uid='$discuz_uid' AND pid IN (".implodeids($should_sync).")");
		$db->query("UPDATE {$tablepre}eze_profile SET should_sync = 0 WHERE uid='$discuz_uid' AND pid NOT IN (".implodeids($should_sync).")");
	}
    else{
		$db->query("UPDATE {$tablepre}eze_profile SET should_sync = 0 WHERE uid='$discuz_uid'");
    }
	showmessage('ezengage:updateuser_succeed', 'plugin.php?id=ezengage:accounts');
}
	
$eze_profiles = eze_get_profiles($discuz_uid);
include template('ezengage:accounts');

?>
