<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

@include_once DISCUZ_ROOT.'./plugins/ezengage/common.inc.php';
@include_once DISCUZ_ROOT.'./plugins/ezengage/ezengage.lang.php';


unset($name, $directory, $vars);

extract($_DPLUGIN[$identifier], EXTR_SKIP);
extract($vars);

$token = $_GET['token'];

$escaped_token = mysql_real_escape_string($token);
$profile = $db->fetch_first("SELECT * FROM {$tablepre}eze_profile WHERE token='{$escaped_token}'");

if(!$profile){
    die('Bad Request');
}
if($profile['uid'] > 0 && eze_login_user($profile['uid'])){
    showmessage('login_succeed', $indexname);
}
else{
    //如果当前已经有discuz 用户登录了,将eze 用户绑定到该用户
    if($discuz_uid) {
        $ret = $db->query(sprintf(
            "UPDATE {$tablepre}eze_profile SET uid = %d WHERE token = '%s'",
            $discuz_uid, $escaped_token)
        );
        //showmessage('ezengage:bind_success', 'plugin.php?id=ezengage:accounts'); 
        dheader("Location: plugin.php?id=ezengage:accounts");
    }
    //否则显示将界面要求登录或注册
    else{
        include plugintemplate('ezengage_register');
    }
}

?>
