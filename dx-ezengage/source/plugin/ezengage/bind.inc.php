<?php
/*
	ezEngage (C)2011  http://ezengage.com
    这个文件用于在第三方帐号登录成功后绑定到disucz 的用户上。
    如果当前没有登录，提示注册。
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

@include_once DISCUZ_ROOT.'./source/plugin/ezengage/common.func.php';

$token = authcode($_G['cookie']['eze_token'], 'DECODE');

$escaped_token = mysql_real_escape_string($token);
$profile = DB::fetch_first("SELECT * FROM " . DB::table('eze_profile') ." WHERE token='{$escaped_token}'");

//找不到profile,说明cookie 不正确或已经过期，提示用户
if(!$profile){
    if($_G['uid']){
        showmessage('ezengage:bad_request', 'home.php?mod=spacecp&ac=plugin&id=ezengage:accounts');
    }
    else {
        showmessage('ezengage:bad_request', 'index.php');
    }
}
else{
    if($profile['uid'] > 0){
        if($_G['uid'] && $profile['uid'] != $_G['uid']){
            dsetcookie('eze_token', '');
            showmessage('ezengage:already_bind_to_other_user', "home.php?mod=spacecp&ac=plugin&id=ezengage:accounts");
        }
        else {
            if(!eze_login_user($profile['uid'])){
                showmessage('ezengage:login_fail', 'memeber.php?mod=login');
            }
        } 
    }
    else{
        //如果当前已经有discuz 用户登录了,将eze 用户绑定到该用户
        if($_G['uid']) {
            $ret = DB::query(sprintf(
                "UPDATE " . DB::table('eze_profile'). " SET uid = %d WHERE pid = '%s'",
                $_G['uid'], $profile['pid'])
            );
            dsetcookie('eze_token', '');
            dheader("location: home.php?mod=spacecp&ac=plugin&id=ezengage:accounts");
        }
        //否则显示将界面要求登录或注册
        else{
            dheader("location: member.php?mod=register&referer=" .urlencode("home.php?mod=spacecp&ac=plugin&id=ezengage:accounts"));
        }
    }
}
?>
