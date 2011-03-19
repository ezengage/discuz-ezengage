<?php
/*
	ezEngage (C)2011  http://ezengage.com
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
    exit('Bad Request, missing token.');
}
$profile = $ezeApiClient->getProfile($_POST['token']);
if(!$profile){
    exit('remote server error');
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
    $ret = DB::query(sprintf(
        "INSERT INTO " . DB::table('eze_profile') . " (token,uid,identity,provider_code,provider_name,preferred_username,display_name,avatar_url,profile_json) VALUES('%s', %d, '%s', '%s', '%s', '%s', '%s', '%s', '%s');",
        $token, 0, mysql_real_escape_string($profile['identity']), mysql_real_escape_string($profile['provider_code']),
        mysql_real_escape_string($profile['provider_name']),
        mysql_real_escape_string($profile['preferred_username']),
        mysql_real_escape_string($profile['display_name']),
        mysql_real_escape_string($profile['avatar_url']),
        json_encode($profile)
    ));
}
else{
    $token = $row['token'];    
}
$token_auth = authcode($token, 'ENCODE');
dsetcookie('eze_token', $token_auth);
dheader("Location: plugin.php?id=ezengage:bind");

?>
