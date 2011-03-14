<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
@include_once DISCUZ_ROOT.'./plugins/ezengage/common.inc.php';

$ezeApiClient = new EzEngageApiClient($G_EZE_OPTIONS['eze_app_key']);
$profile = $ezeApiClient->getProfile($_POST['token']);
if(!$profile){
    exit('bad request');
}

$identity = mysql_real_escape_string($profile['identity']);
$row = $db->fetch_first("SELECT token,uid,identity FROM {$tablepre}eze_profile WHERE identity='{$identity}'");
//new user
if(!$row){
    $token = md5($_POST['token'] . time());
    $ret = $db->query(sprintf(
        "INSERT INTO {$tablepre}eze_profile (token,uid,identity,provider_code,preferred_username,display_name,avatar_url,profile_json) VALUES('%s', %d, '%s', '%s', '%s', '%s', '%s', '%s');",
        $token, $discuz_uid, mysql_real_escape_string($profile['identity']), mysql_real_escape_string($profile['provider_code']),
        mysql_real_escape_string($profile['preferred_username']),
        mysql_real_escape_string($profile['display_name']),
        mysql_real_escape_string($profile['avatar_url']),
        json_encode($profile)
    ));
}
else{
    $token = $row['token'];    
}
dheader("Location: plugin.php?id=ezengage:bind&token=". $token);

?>
