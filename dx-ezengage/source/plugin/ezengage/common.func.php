<?php
/*
	ezEngage (C)2011  http://ezengage.com
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

$G_EZE_OPTIONS = $_G['cache']['plugin']['ezengage'];
if(!isset($G_EZE_OPTIONS['eze_auto_register'])){
    $G_EZE_OPTIONS['eze_auto_register'] = TRUE;
}
#include_once DISCUZ_ROOT.'/data/plugindata/ezengage.lang.php';


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
        $row = $db->fetch_first("SELECT identity FROM " . DB::table('eze_profile') . " WHERE uid={$uid} AND pid={$profile_id}");
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
    $query = $db->query("SELECT * FROM " . DB::table('eze_profile') . " WHERE uid=$uid;");
    while($profile = $db->fetch_array($query)) {
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
    global $_G;
    if (empty($uid)) return false;
    include_once DISCUZ_ROOT . './source/function/function_member.php';
    $member = DB::fetch_first("SELECT * FROM ".DB::table('common_member')." WHERE uid = $uid");
    if(is_array($member) && $member['username']){
        setloginstatus($member, $_G['gp_cookietime'] ? 2592000 : 0);
        DB::query("UPDATE ".DB::table('common_member_status')." SET lastip='".$_G['clientip']."', lastvisit='".time()."' WHERE uid='$_G[uid]'");
        $ucsynlogin = $_G['setting']['allowsynlogin'] ? uc_user_synlogin($_G['uid']) : '';

        include_once DISCUZ_ROOT . './source/function/function_stat.php';
        updatestat('login');
        updatecreditbyaction('daylogin', $_G['uid']);
        checkusergroup($_G['uid']);

        dsetcookie('eze_token', '');

        $_G['gp_refer'] = $_G['gp_refer'] ? $_G['gp_refer'] : 'forum.php';
        showmessage('login_success', $_G['gp_refer'], array('username' => $memeber['username']));
        return true;
    } 
    return false;
}

function eze_login_widget($style = 'normal', $width = 'auto', $height = 'auto'){
    global $_G;
    $eze_options = $_G['cache']['plugin']['ezengage'];
    $token_cb = $_G['siteurl'] . 'plugin.php?id=ezengage:token';
    if(in_array($style, array('normal','medium','small', 'tiny'))){
        $html = sprintf('<iframe class="eze_widget" border="0" src="http://%s.ezengage.net/login/%s/widget/%s?token_cb=%s&w=%s&h=%s" scrolling="no" frameBorder="no" style="width:%spx;height:%spx"></iframe>', 
               $eze_options['eze_app_domain'],
               $eze_options['eze_app_domain'],
               $style,
               urlencode($token_cb),
               $width,$height,
               $width,$height
        );
        return $html;
    }
}

function eze_login_widget_output($style = 'normal', $width = 'auto', $height = 'auto'){
    echo eze_login_widget($style, $width, $height);
}

function eze_get_profiles($uid){
    global $_G;
    $eze_profiles = array();
    $query = DB::query("SELECT * FROM " . DB::table('eze_profile') . " WHERE uid='$uid'");
    while($profile = DB::fetch($query)) {
        $eze_profiles[] = $profile;
    }
    return $eze_profiles;
}

function eze_current_profile(){
    global $_G;
    if(empty($_G['cookie']['eze_token'])){
        return NULL;
    }
    $token = authcode($_G['cookie']['eze_token'], 'DECODE');
    if(empty($token)){
        return NULL;
    }
    $escaped_token = mysql_real_escape_string($token);
    $profile = DB::fetch_first("SELECT * FROM " . DB::table('eze_profile') ." WHERE token='{$escaped_token}'");
    return $profile;
}

function eze_bind_user(){
    global $_G;
    if(empty($_G['cookie']['eze_token'])){
        return;
    }
    if(!$_G['uid']){
        return;
    }
    $token = authcode($_G['cookie']['eze_token'], 'DECODE');
    if($token){
        $ret = $db->query(sprintf(
            "UPDATE " . DB::table("eze_profile") . " SET uid = %d WHERE token = '%s'",
            $_G['uid'], $escaped_token)
        );
        dsetcookie('eze_token', '');
        dheader("location: home.php?mod=spacecp&ac=plugin&id=ezengage:accounts");
    }
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

