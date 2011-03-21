<?php
/*
	ezEngage (C)2011  http://ezengage.com
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

//constants

define(EZE_ALL_SYNC_LIST, 'newthread,newblog,newshare,newdoing,reply,blogcomment,sharecomment,dogingcomment');
define(EZE_DEFAULT_SYNC_LIST, 'newthread,newblog,newshare,newdoing');
define(EZE_MY_ACCOUNT_URL, 'home.php?mod=spacecp&ac=plugin&id=ezengage:accounts');

function eze_convert($source, $in, $out){
    $in = strtoupper($in);
    if ($in == "UTF8"){
        $in = "UTF-8";
    }   
    if ($out == "UTF8"){
        $out = "UTF-8";
    }       
    if( $in == $out ){
        return $source;
    }   
    if(function_exists('mb_convert_encoding')) {
        return mb_convert_encoding($source, $out, $in );
    } elseif (function_exists('iconv'))  {
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
    //FIXME 
    $_DCACHE['smilies']['searcharray'] = isset($_DCACHE['smilies']['searcharray']) ? $_DCACHE['smilies']['searcharray'] : array();
    $content = str_replace($re, '', $content);
    $content = preg_replace($_DCACHE['smilies']['searcharray'], '', $content);
    return $content;
}

function eze_sync_checkbox_wrapper($uid, $event, $show = true){
    global $_G;
    include(DISCUZ_ROOT.'/data/plugindata/ezengage.lang.php');

    $eze_profiles = array();
    $query = DB::query("SELECT * FROM " . DB::table('eze_profile') . " WHERE uid=$uid;");
    while($profile = DB::fetch($query)) {
        $eze_profiles[] = $profile;
    }
    $html = array(
        '<div id="eze_sync_checkbox_wrapper" style="margin-bottom:5px;' . ($show ? '':'display:none') . '">',
    );
    foreach($eze_profiles as $profile){
        if(strpos($profile['sync_list'], $event) === FALSE){
            $html[] = "<input name='eze_should_sync[]' type='checkbox' class='checkbox' value='$profile[pid]' />";
        }
        else{
            $html[] = "<input name='eze_should_sync[]' type='checkbox' class='checkbox' checked='checked' value='$profile[pid]' />";
        }
        $html[] =  sprintf($scriptlang['ezengage']['sync_checkbox_label'], $profile['provider_name'], $profile['preferred_username']);
    }
    $html[] = "<input type='hidden' name='eze_include_sync_options' value='1'/>";
    $html[] = "<input type='hidden' name='eze_sync_event' value='$event'/>";
    $html[] = "</div>";
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
function eze_sync_list($profile){
    include(DISCUZ_ROOT.'/data/plugindata/ezengage.lang.php');
    $html = array();
    foreach(explode(',', EZE_ALL_SYNC_LIST) as $sync_item){
        if (strpos($profile['sync_list'], $sync_item) === FALSE){
            $html[] = "<input name='sync_list_{$profile[pid]}[]' type='checkbox' class='checkbox'
                       value='$sync_item' />";
        }
        else{
            $html[] = "<input name='sync_list_{$profile[pid]}[]' type='checkbox' class='checkbox'
                       value='$sync_item' checked='checked' />";
        }
        $html[] = $scriptlang['ezengage']['sync_name_' . $sync_item];
    }
    $html = implode(' ', $html);
    return $html;
}

function eze_sync_list_output($profile){
    print eze_sync_list($profile);
}

function eze_get_default_sync_to($uid, $event){
    $event = mysql_real_escape_string($event);
    $query = DB::query("SELECT pid FROM " . DB::table('eze_profile') . " WHERE uid='$uid' AND Contains(sync_list, '%$event%')");
    $pids = array();
    while($profile = DB::fetch($query)) {
        $pids[] = $profile['pid'];
    }
    return $pids;
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

function eze_bind($profile, $is_register = FALSE){
    global $_G;
    if($_G['uid'] && $profile && !$profile['uid']){
        $ret = DB::query(sprintf(
            "UPDATE " . DB::table("eze_profile") . " SET uid = %d WHERE pid = '%s'",
            $_G['uid'], $profile['pid'])
        );
        dsetcookie('eze_token', '');
        if($is_register){
            sendpm($_G['uid'], $subject, $message);
        }
    }
}

class eze_publisher {

    //同步主题
    static function sync_newthread($pid, $sync_to){
       self::sync_post($pid, $sync_to); 
    }

    //同步回复
    static function sync_reply($pid, $sync_to){
       self::sync_post($pid, $sync_to); 
    }

    //同步主题或回复
    static function sync_post($pid, $sync_to){
        $post = DB::fetch_first("SELECT tid,pid,authorid,subject,message,first FROM " . DB::table('forum_post') . " WHERE pid={$pid};");
        if(!$post){
            return;
        }
        $uid = $post['authorid'];
        $status = self::format_post_status($post);
        self::publish($uid, $sync_to, $status);
    } 

    static function format_post_status($post){
        global $_G;
        if($post['first']){
            $url = $_G['siteurl'] . "forum.php?mod=viewthread.php&tid=$post[tid]";
        }
        else{
            $url = $_G['siteurl'] . "forum.php?mod=redirect&goto=findpost&pid=$post[pid]&ptid=$post[tid]";
        }
        $status = $post['subject'] . ' ' . $post['message'];
        $status = eze_convert($status, $_G['charset'], 'UTF-8');
        $status = eze_filter($status);
        $status = $url . ' ' . $status;
        #这里的截断只是为了防止大文章时发送过大的数据。
        $status = substr($status, 0, 1000);
        return $status;
    }

    //同步记录
    static function sync_newdoing($doid, $sync_to){
        $doing = DB::fetch_first("SELECT uid,doid,message FROM " . DB::table('home_doing') . " WHERE doid={$doid};");
        if(!$doing){
            return;
        }
        $status = self::format_doing_status($doing);
        self::publish($doing['uid'], $sync_to, $status);
    }

    static function format_doing_status($doing){
        $status = eze_convert($doing['message'], $_G['charset'], 'UTF-8');
        $status = eze_filter($status);
        $status = substr($status, 0, 1000);
        return $status;
    }

    //同步Blog
    static function sync_newblog($blogid, $sync_to){
        $blog = DB::fetch_first("SELECT blogid,uid,subject FROM " . DB::table('home_blog') . " WHERE blogid={$blogid}");
        if(!$blog){
            return;
        }
        $status = self::format_blog_status($blog);
        self::publish($blog['uid'], $sync_to, $status);
    }

    static function format_blog_status($blog){
        global $_G;
        $status = eze_convert($blog['subject'], $_G['charset'], 'UTF-8');
        $link = $_G['siteurl']. "home.php?mod=space&uid={$blog[uid]}&do=blog&id={$blog[blogid]}";
        $status = eze_filter($status);
        $status = $link . ' ' . $status;
        $status = substr($status, 0, 1000);
        return $status;
    }

    //同步Share
    static function sync_newshare($sid, $arr, $sync_to){
        $share = DB::fetch_first("SELECT sid,uid,type,title_template,body_general FROM " . DB::table('home_share') . " WHERE sid={$sid}");
        $status = self::format_share_status($share);
        self::publish($share['uid'], $sync_to, $status);
    }

    static function format_share_status($share){
        global $_G;
        $status = !empty($share['body_general']) ? (string)$share['body_general'] : (string)$share['title_template'];
        //TODO fix this, check type 
        $status = eze_convert($status, $_G['charset'], 'UTF-8');
        $status = eze_filter($status);
        $status = substr($status, 0, 1000);
        return $status;
    }


    //将内容发布出去,所有的同步内容最终通过这个函数发布
    static function publish($uid, $sync_to, $status){
        global $_G;
        $eze_app_key = $_G['cache']['plugin']['ezengage']['eze_app_key'];
        if(empty($eze_app_key)){
            return ;
        }
        $ezeApiClient = new EzEngageApiClient($eze_app_key);
        foreach($sync_to as $profile_id){
            $row = DB::fetch_first("SELECT identity FROM " . DB::table('eze_profile') . " WHERE uid={$uid} AND pid={$profile_id}");
            if($row){
                $ret = $ezeApiClient->updateStatus($row['identity'], $status);
            }
        }
    }
}
