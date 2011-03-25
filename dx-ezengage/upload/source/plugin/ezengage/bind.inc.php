<?php
/*
	ezEngage (C)2011  http://ezengage.com
    第三方帐号登录成功后的逻辑
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
        showmessage('ezengage:bad_request', EZE_MY_ACCOUNT_URL);
    }
    else {
        showmessage('ezengage:bad_request', 'index.php');
    }
}
else{
    if($profile['uid'] > 0){
        if($_G['uid'] && $profile['uid'] != $_G['uid']){
            dsetcookie('eze_token', '');
            showmessage('ezengage:already_bind_to_other_user', EZE_MY_ACCOUNT_URL);
        }
        else {
            if(eze_login_user($profile['uid'])){
                dsetcookie('eze_token', '');
                loaducenter();
                $ucsynlogin = $_G['setting']['allowsynlogin'] ? uc_user_synlogin($_G['uid']) : '';
                $_G['gp_refer'] = $_G['gp_refer'] ? $_G['gp_refer'] : 'index.php';
                showmessage('login_succeed', $_G['gp_refer'], 
                    array('username' => $_G['member']['username'], 'ucsynlogin' => $ucsynlogin, 'uid' => $_G['uid'])
                );
            }
            else{
                showmessage('ezengage:login_fail', 'member.php?mod=logging&action=login');
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
            dheader("location: ". EZE_MY_ACCOUNT_URL);
        }
        //否则显示将界面要求登录或注册
        else{

            if($_G['cache']['plugin']['ezengage']['eze_enable_auto_register'] \
                && eze_register_user($profile)){
                if($_G['uid']){
                    eze_bind($profile, TRUE);
                }
                $_G['gp_refer'] = $_G['gp_refer'] ? $_G['gp_refer'] : 'index.php';
                showmessage('login_succeed', $_G['gp_refer'], 
                    array('username' => $_G['member']['username'],'uid' => $_G['uid'])
                );
            }
            else{
                dheader("location: member.php?mod=register&referer=" .urlencode(EZE_MY_ACCOUNT_URL));
            }
        }
    }
}
?>
