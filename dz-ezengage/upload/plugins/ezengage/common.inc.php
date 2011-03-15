<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

//@include_once DISCUZ_ROOT.'./forumdata/cache/plugin_'.$identifier.'.php';
@include_once DISCUZ_ROOT.'./forumdata/cache/plugin_ezengage.php';
$G_EZE_OPTIONS = $_DPLUGIN['ezengage']['vars'];
@require_once DISCUZ_ROOT.'./forumdata/plugins/ezengage.lang.php';

function eze_sync_post($pid, $should_sync){
    global $G_EZE_OPTIONS;
    global $tablepre;
    global $db;
    if(count($should_sync) <= 0){
        return;
    }
    #TODO CHECK eze_app_key
    $ezeApiClient = new EzEngageApiClient($G_EZE_OPTIONS['eze_app_key']);
    $post = $db->fetch_first("SELECT tid,pid,authorid,subject,message FROM {$tablepre}posts WHERE pid={$pid};");
    if(!$post){
        return;
    }
    $uid = $post['authorid'];
    foreach($should_sync as $profile_id){
        $row = $db->fetch_first("SELECT identity FROM {$tablepre}eze_profile WHERE uid={$uid} AND pid={$profile_id}");
        if($row){
            $status = eze_format_status($post);
            $ret = $ezeApiClient->updateStatus($row['identity'], $status);
        }
    }
}

function eze_format_status($post){
    global $siteurl; 
    $url = $siteurl . "viewthread.php?tid=$post[tid]";
    #make sure truncate it in server side
    $status = $post['subject'] . ' ' . $post['message'] . ' ' . $url;
    return $status;
}

function eze_sync_checkbox($uid){
    global $db;
    global $tablepre;
    $eze_profiles = array();
    $query = $db->query("SELECT * FROM {$tablepre}eze_profile WHERE uid='$uid'");
    while($profile = $db->fetch_array($query)) {
        $eze_profiles[] = $profile;
    }
    include template('ezengage:sync_checkbox');
}

function eze_login_user($uid){
    global $tablepre;
    global $db;
    if (empty($uid)) return false;
    
    $member = $db->fetch_first("
        SELECT m.uid AS discuz_uid, m.username AS discuz_user, 
               m.password AS discuz_pw, m.secques AS discuz_secques,
               m.email, m.adminid, m.groupid, m.styleid AS styleidmem, m.lastvisit, m.lastpost, u.allowinvisible
        FROM {$tablepre}members m LEFT JOIN {$tablepre}usergroups u USING (groupid)
        WHERE m.uid='$uid'"
    );

    if($member){
        extract($member);
        $GLOBALS['discuz_userss'] = dhtmlspecialchars($discuz_user);
        $GLOBALS['discuz_uid'] = dhtmlspecialchars($discuz_uid);
        $cookietime = 0;
        dsetcookie('cookietime', $cookietime, 31536000);
        dsetcookie('auth', authcode("$discuz_pw\t$discuz_secques\t$discuz_uid", 'ENCODE'), $cookietime, 1, true);
        dsetcookie('loginuser');
        dsetcookie('sid');
        dsetcookie('activationauth');
        dsetcookie('pmnum'); 
        return true;
    }else{  
        return false;
    }       
}


function eze_login_widget($style = 'normal'){
    global $siteurl;
    global $G_EZE_OPTIONS;
    $token_cb = $siteurl . 'plugin.php?id=ezengage:token';
    if($style == 'normal'){
        $html = sprintf('<iframe border="0" src="http://%s.ezengage.net/login/%s/widget/%s?token_cb=%s" scrolling="no" frameBorder="no" style="width:350px;height:200px;margin-bottom:10px;"></iframe>', 
               $G_EZE_OPTIONS['eze_app_domain'],
               $G_EZE_OPTIONS['eze_app_domain'],
               $style,
               urlencode($token_cb)
        );
        echo $html;
    }
    //TODO add more style
}

function eze_get_profiles($uid){
    global $db;
    global $scriptlang;
    global $tablepre;
    $eze_profiles = array();
    $query = $db->query("SELECT * FROM {$tablepre}eze_profile WHERE uid='$uid'");
    while($profile = $db->fetch_array($query)) {
        $profile['provider_name'] = $scriptlang['ezengage']['provider_name_' . $profile['provider_code']];
        $eze_profiles[] = $profile;
    }
    return $eze_profiles;
}

function eze_trigger($event){
    if($event == 'newthread' || $event == 'newreply'){
        global $db,$discuz_uid,$tablepre;
        global $pid,$action;
        global $eze_should_sync;
        if($pid && $eze_should_sync){
            eze_sync_post(intval($pid), $eze_should_sync);
        }
    }
}
