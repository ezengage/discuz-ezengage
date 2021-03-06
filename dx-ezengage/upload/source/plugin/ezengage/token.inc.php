<?php
/*
	ezEngage (C)2011  http://ezengage.com
    accept token from ezengage service, and fetch profile data via ezengage api
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

@include_once DISCUZ_ROOT.'./source/plugin/ezengage/common.func.php';
@include_once DISCUZ_ROOT.'./source/plugin/ezengage/apiclient.php';


$eze_app_key = $_G['cache']['plugin']['ezengage']['eze_app_key'];
if(empty($eze_app_key)){
    exit('Bad Configuration');
}

$ezeApiClient = new EzEngageApiClient($eze_app_key);
if(empty($_POST['token'])){
    showmessage('ezengage:bad_request', 'index.php');
    exit();
}

//may be do some basic check
$profile = $ezeApiClient->getProfile(strval($_POST['token']));
if(!$profile){
    showmessage('ezengage:eze_login_fail', 'index.php');
    exit();
}

//convert charset 
foreach($profile as $key => $val){
    if(is_string($val)){
        $profile[$key] = eze_convert($val, 'UTF-8', $_G['charset']);
    }
}

$identity = mysql_real_escape_string($profile['identity']);
$row = DB::fetch_first("SELECT token,uid,identity FROM " . DB::table('eze_profile') ." WHERE identity='{$identity}'");
//new user
if(!$row){
    $token = md5($_POST['token'] . time());
    $data = array(
        'token' => $token,
        'uid' => 0,
        'identity' => $profile['identity'],
        'provider_code' => $profile['provider_code'],
        'provider_name' => $profile['provider_name'],
        'preferred_username' => $profile['preferred_username'],
        'avatar_url' => $profile['avatar_url'],
        'sync_list' => EZE_DEFAULT_SYNC_LIST,
    );
    $newid = DB::insert('eze_profile', $data);
}
else{
    $token = $row['token'];    
}

$token_auth = authcode($token, 'ENCODE');
dsetcookie('eze_token', $token_auth);

//这个文件只处理同ezenenge 服务的交互和身份数据的保存，同discuz 系统的集成在下一步完成。
dheader("location: plugin.php?id=ezengage:bind");

?>
