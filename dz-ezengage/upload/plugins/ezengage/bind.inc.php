<?php
/*
	ezEngage (C)2011  http://ezengage.com
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

@include_once DISCUZ_ROOT.'./plugins/ezengage/common.inc.php';

$token = authcode($_DCOOKIE['eze_token'], 'DECODE');

$escaped_token = mysql_real_escape_string($token);
$profile = $db->fetch_first("SELECT * FROM {$tablepre}eze_profile WHERE token='{$escaped_token}'");

if(!$profile){
    showmessage('ezengage:bad_request', 'plugin.php?id=ezengage:accounts');
}

if($profile['uid'] > 0){
    if($discuz_uid && $profile['uid'] != $discuz_uid){
        dsetcookie('eze_token', '');
        showmessage('ezengage:already_bind_to_other_user', 'plugin.php?id=ezengage:accounts');
    }
    else if(eze_login_user($profile['uid'])){
        dsetcookie('eze_token', '');
        showmessage('login_succeed', $indexname);
    } 
}
else{
    //如果当前已经有discuz 用户登录了,将eze 用户绑定到该用户
    if($discuz_uid) {
        $ret = $db->query(sprintf(
            "UPDATE {$tablepre}eze_profile SET uid = %d WHERE token = '%s'",
            $discuz_uid, $escaped_token)
        );
        dsetcookie('eze_token', '');
        dheader("location: plugin.php?id=ezengage:accounts");
    }
    //否则显示将界面要求登录或注册
    else{
        include plugintemplate('ezengage_register');
    }
}

?>
