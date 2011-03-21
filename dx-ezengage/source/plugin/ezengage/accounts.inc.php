<?php
/*
	ezEngage (C)2011  http://ezengage.com
    这个文件显示用户绑定的帐号，并允许设置同步那些东西，或者解除绑定。
*/

if(!defined('IN_DISCUZ')) {
        exit('Access Denied');
}

@include_once DISCUZ_ROOT.'./source/plugin/ezengage/common.func.php';

if(!$_G['uid']) {
	showmessage('not_loggedin', NULL, 'NOPERM');
    exit();
}

if($_G['gp_pluginop'] == 'update' && submitcheck('updateuser')) {
    if(!empty($_G['gp_delete'])) {
		DB::query("DELETE FROM " . DB::table('eze_profile') ." WHERE uid = '$_G[uid]' AND pid IN (".dimplode($_G['gp_delete']).")");
	}
    $eze_profiles = eze_get_profiles($_G['uid']);
    foreach($eze_profiles as &$profile){
        if(is_array($_G['gp_sync_list_' . $profile['pid']])){
           $sync_list = implode(',', $_G['gp_sync_list_' . $profile['pid']]); 
        }
        else{
           $sync_list = '';
        }
        $profile['sync_list'] = $sync_list;
        $e_sync_list = mysql_real_escape_string($sync_list);
		DB::query("UPDATE " . DB::table('eze_profile') . " SET sync_list = '$e_sync_list' WHERE uid='$_G[uid]';");
    }
	showmessage('ezengage:updateuser_succeed', EZE_MY_ACCOUNT_URL);
    exit();
}
	
$eze_profiles = eze_get_profiles($_G['uid']);

$_G['basescript'] = 'home';
?>
