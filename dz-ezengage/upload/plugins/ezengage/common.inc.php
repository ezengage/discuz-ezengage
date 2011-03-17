<?php
/*
	ezEngage (C)2011  http://ezengage.com
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

@include_once DISCUZ_ROOT.'./forumdata/cache/plugin_ezengage.php';
$G_EZE_OPTIONS = $_DPLUGIN['ezengage']['vars'];
if(!isset($G_EZE_OPTIONS['eze_auto_register'])){
    $G_EZE_OPTIONS['eze_auto_register'] = TRUE;
}
@require_once DISCUZ_ROOT.'./forumdata/plugins/ezengage.lang.php';

/**
 * 同步帖子
 * @pid post pid
 * @should_sync profile pid array
 */
function eze_sync_post($pid, $should_sync){
    global $G_EZE_OPTIONS;
    global $tablepre;
    global $db;
    if(count($should_sync) <= 0){
        return;
    }
    //TODO raise error  
    if(empty($G_EZE_OPTIONS['eze_app_key'])){
        return ;
    }
    $ezeApiClient = new EzEngageApiClient($G_EZE_OPTIONS['eze_app_key']);
    $post = $db->fetch_first("SELECT tid,pid,authorid,subject,message FROM {$tablepre}posts WHERE pid={$pid};");
    if(!$post){
        return;
    }
    $uid = $post['authorid'];
    //这里可能可以优化
    $status = eze_format_status($post);
    foreach($should_sync as $profile_id){
        $row = $db->fetch_first("SELECT identity FROM {$tablepre}eze_profile WHERE uid={$uid} AND pid={$profile_id}");
        if($row){
            $ret = $ezeApiClient->updateStatus($row['identity'], $status);
        }
    }
}

function eze_convert($source, $in, $out){
    $in = strtoupper($in);
    if ($in == "UTF8"){
        $in = "UTF-8";
    }   
    if ($out == "UTF8"){
        $out = "UTF-8";
    }       
    if( $in==$out ){
        return $source;
    }   
    if(function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($source, $out, $in );
    }elseif (function_exists('iconv'))  {
        return iconv($in,$out."//IGNORE", $source);
    }   
    return $source;
}   

function eze_filter($content) {
    global $_DCACHE;
    //attach 
    $content = preg_replace('!\[(attachimg|attach)\]([^\[]+)\[/(attachimg|attach)\]!', '', $content);
    //image
    $content = preg_replace('|\[img(?:=[^\]]*)?](.*?)\[/img\]|', '\\1 ', $content);
    //UBB
    $re ="#\[([a-z]+)(?:=[^\]]*)?\](.*?)\[/\\1\]#sim";
    while(preg_match($re, $content)) {
        $content = preg_replace($re, '\2', $content);
    }
    //smiles
    $re = $_DCACHE['smileycodes'];
    $_DCACHE['smilies']['searcharray'] = isset($_DCACHE['smilies']['searcharray']) ? $_DCACHE['smilies']['searcharray'] : array();
    $content = str_replace($re, '', $content);
    $content = preg_replace($_DCACHE['smilies']['searcharray'], '', $content);
    return $content;
}

function eze_format_status($post){
    global $siteurl; 
    global $charset;
    if($post['first']){
        $url = $siteurl . "viewthread.php?tid=$post[tid]";
    }
    else{
        $url = $siteurl . "redirect.php?goto=findpost&pid=$post[pid]&ptid=$post[tid]";
    }
    $status = $post['subject'] . ' ' . $post['message'];
    $status = eze_convert($status, $charset, 'UTF-8');
    $status = eze_filter($status);
    $status = $url . ' ' . $status;
    #这里的截断只是为了防止大文章时发送过大的数据。
    $status = substr($status, 0, 1000);
    return $status;
}

function eze_sync_checkbox_wrapper($uid, $show = true){
    global $db,$tablepre,$scriptlang;
    $eze_profiles = array();
    $query = $db->query("SELECT * FROM {$tablepre}eze_profile WHERE uid=$uid;");
    while($profile = $db->fetch_array($query)) {
        $profile['provider_name'] = $scriptlang['ezengage']['provider_name_' . $profile['provider_code']];
        $eze_profiles[] = $profile;
    }
    $html = array(
        '<div id="eze_sync_checkbox_wrapper" style="margin-bottom:5px;' . ($show ? '':'display:none') . '">',
    );
    foreach($eze_profiles as $profile){
        if($profile['should_sync']){
            $html[] = "<input name='eze_should_sync[]' type='checkbox' class='checkbox' checked='checked' value='$profile[pid]' />";
        }
        else{
            $html[] = "<input name='eze_should_sync[]' type='checkbox' class='checkbox' value='$profile[pid]' />";
        }
        $html[] =  sprintf($scriptlang['ezengage']['sync_checkbox_label'], $profile['provider_name'], $profile['display_name']);
    }
    $html[] = '</div>';
    $html = implode('', $html);
    return $html;
}

function eze_sync_checkbox_output($uid){
    echo eze_sync_checkbox_wrapper($uid, true);
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
