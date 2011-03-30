<?php
/*
	ezEngage (C)2011  http://ezengage.com
*/

if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

//constants

define(EZE_ALL_SYNC_LIST, 'newthread,newblog,newshare,newdoing,reply,blogcomment,sharecomment,doingcomment');
define(EZE_DEFAULT_SYNC_LIST, 'newthread,newblog,newshare,newdoing');
define(EZE_MY_ACCOUNT_URL, 'home.php?mod=spacecp&ac=plugin&id=ezengage:accounts');

//转换编码
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

//过滤
function eze_filter($content) {
    global $_G;
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
    $re = isset($_G['cache']['smileycodes']) ? (array)$_G['cache']['smileycodes'] : array();
    $smiles_searcharray = isset($_G['cache']['smilies']['searcharray']) ? (array)$_G['cache']['smilies']['searcharray'] : array();
    $content = str_replace($re, '', $content);
    $content = preg_replace($smiles_searcharray, '', $content);
    return $content;
}

function eze_login_user($uid){
    global $_G;
    if (empty($uid)) return false;
    include_once DISCUZ_ROOT . './source/function/function_member.php';
    $member = DB::fetch_first("SELECT * FROM ".DB::table('common_member')." WHERE uid = $uid");
    if(is_array($member) && $member['username']){
        setloginstatus($member, $_G['gp_cookietime'] ? 2592000 : 0);
        DB::query("UPDATE ".DB::table('common_member_status')." SET lastip='".$_G['clientip']."', lastvisit='".time()."' WHERE uid='$_G[uid]'");

        include_once DISCUZ_ROOT . './source/function/function_stat.php';
        updatestat('login');
        updatecreditbyaction('daylogin', $_G['uid']);
        checkusergroup($_G['uid']);
        return true;
    } 
    return false;
}

/**
 * 尝试注册用户,如果成功返回True,否则返回False
 */
function eze_register_user($profile){
    global $_G;
    loaducenter();
    require_once libfile('function/misc');
    require_once libfile('function/member');
    require_once DISCUZ_ROOT.'/data/plugindata/ezengage.lang.php';
    $eze_options = $_G['cache']['plugin']['ezengage'];

    $lang = $scriptlang['ezengage'];

    $username = $profile['preferred_username'];

    $result = uc_user_checkname($username);
    if($result != 1){
        return FALSE;
    }

    $password = md5(mt_rand(7,999999));
    $password = substr($password,5,8); 

    //TODO:make the email suffix as an option
    $email = md5($profile['identity']) . '_' . strval($profile['pid']) . '@' . $eze_options['eze_app_domain'] . '.ezengage.net';

    $groupinfo = array();
    if($_G['setting']['regverify']) {
        $groupinfo['groupid'] = 8;
    } else {
        $groupinfo = DB::fetch_first("SELECT groupid FROM ".DB::table('common_usergroup')." WHERE creditshigher<=".intval($_G['setting']['initcredits'])." AND ".intval($_G['setting']['initcredits'])."<creditslower LIMIT 1");
    }

    //注册到UCenter
    $uid = uc_user_register($username, $password, $email, '', '', $_G['clientip']);
    if($uid <= 0) {
        return FALSE;
    }

    //检测uid重复
    if(DB::result_first("SELECT uid FROM ".DB::table('common_member')." WHERE uid='$uid'")) {
        return FALSE;
    }

    //插入数据表
    $dzpassword = md5(random(10));
    $init_arr = explode(',', $_G['setting']['initcredits']);
    $userdata = array(
        'uid' => $uid,
        'username' => $username,
        'password' => $dzpassword,
        'email' => $email,
        'adminid' => 0,
        'groupid' => $groupinfo[groupid],
        'regdate' => TIMESTAMP,
        'credits' => $init_arr[0],
        'timeoffset' => 9999
    );
    DB::insert('common_member', $userdata);
    $status_data = array(
        'uid' => $uid,
        'regip' => $_G['clientip'],
        'lastip' => $_G['clientip'],
        'lastvisit' => TIMESTAMP,
        'lastactivity' => TIMESTAMP,
        'lastpost' => 0,
        'lastsendmail' => 0,
    );
    DB::insert('common_member_status', $status_data);

    //初始化积分
    $count_data = array(
        'uid' => $uid,
        'extcredits1' => $init_arr[1],
        'extcredits2' => $init_arr[2],
        'extcredits3' => $init_arr[3],
        'extcredits4' => $init_arr[4],
        'extcredits5' => $init_arr[5],
        'extcredits6' => $init_arr[6],
        'extcredits7' => $init_arr[7],
        'extcredits8' => $init_arr[8]
    );
    DB::insert('common_member_count', $count_data);
    manyoulog('user', $uid, 'add');
    //更新最新注册
    $totalmembers = DB::result_first("SELECT COUNT(*) FROM ".DB::table('common_member'));
    $userstats = array('totalmembers' => $totalmembers, 'newsetuser' => $username);
    //更新缓存
    save_syscache('userstats', $userstats);
    
    //更新session
    $_G['uid'] = $uid;
    $_G['username'] = $username;
    $_G['member']['username'] = dstripslashes($_G['username']);
    $_G['member']['password'] = $dzpassword;
    $_G['groupid'] = $groupinfo['groupid'];
    include_once libfile('function/stat');
    updatestat('register');
    
    $_CORE = & discuz_core::instance();
    $_CORE->session->set('uid', $uid);
    $_CORE->session->set('username', $username);
    //创建cookie
    dsetcookie('auth', authcode("{$_G['member']['password']}\t$_G[uid]", 'ENCODE'), 2592000, 1, true);

    $pm_subject = replacesitevar($lang['auto_register_pm_subject']);
    $pm_message = replacesitevar($lang['auto_register_pm_message']);
    $pm_message = str_replace(array('{password}'), array($password), $pm_message);

    $pm_subject = addslashes($pm_subject);
    $pm_message = addslashes($pm_message);

    sendpm($uid, $pm_subject, $pm_message, 0);

    return True;
}

function eze_login_widget($style = 'normal', $width = 'auto', $height = 'auto'){
    global $_G;
    $eze_options = $_G['cache']['plugin']['ezengage'];
    $token_cb = $_G['siteurl'] . 'plugin.php?id=ezengage:token';
    if(in_array($style, array('normal','medium','small', 'tiny'))){
        $html = sprintf('<iframe class="eze_widget" border="0" src="http://%s.ezengage.net/login/%s/widget/%s?token_cb=%s&w=%s&h=%s" scrolling="no" frameBorder="no" style="width:%s;height:%s;"></iframe>', 
               $eze_options['eze_app_domain'],
               $eze_options['eze_app_domain'],
               $style,
               urlencode($token_cb),
               $width,$height,
               $width != 'auto' ? $width .'px' : 'auto',
               $height != 'auto' ? $height .'px' : 'auto'
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
    $query = DB::query("SELECT pid FROM " . DB::table('eze_profile') . " WHERE uid='$uid' AND sync_list LIKE '%$event%'");
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

function eze_bind($profile, $send_pm = FALSE){
    global $_G;
    include(DISCUZ_ROOT.'/data/plugindata/ezengage.lang.php');
    $lang = $scriptlang['ezengage'];

    if($_G['uid'] && $profile && !$profile['uid']){
        $ret = DB::query(sprintf(
            "UPDATE " . DB::table("eze_profile") . " SET uid = %d WHERE pid = '%s'",
            $_G['uid'], $profile['pid']
        ));
        dsetcookie('eze_token', '');
        if($send_pm){
            $replaces = array(
                '{siteurl}' => $_G['siteurl'],
                '{provider_name}' => $profile['provider_name'],
                '{preferred_username}' => $profile['preferred_username'],
            );
            $subject = addslashes(str_replace(array_keys($replaces), array_values($replaces), $lang['new_bind_pm_subject']));
            $message = addslashes(str_replace(array_keys($replaces), array_values($replaces), $lang['new_bind_pm_message']));
            sendpm($_G['uid'], $subject, $message, 0);
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
            $url = $_G['siteurl'] . "forum.php?mod=viewthread&tid=$post[tid]";
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
        global $_G;
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
    static function sync_newshare($sid, $sync_to){
        $share = DB::fetch_first("SELECT * FROM " . DB::table('home_share') . " WHERE sid={$sid}");
        $status = self::format_share_status($share);
        if(!empty($status)){
            self::publish($share['uid'], $sync_to, $status);
        }
    }

    static function format_share_status($share){
        global $_G;
        $type_map = array(
            'space' => 'username',
			'blog' => 'subject',
			'album' => 'albumname',
			'pic' => 'albumname',
			'thread' => 'subject',
			'article' => 'title',
			'link' => 'link',
			'video' => 'link',
			'music' => 'link',
			'flash' => 'link',
		);
        $t = $type_map[$share['type']];
        if(empty($t)){
            return false;
        }

		$body_data = unserialize($share['body_data']);
		if('link' != $t){
            //如果分享的是站内的内容，把链接提取出来
			$pattern = '/^<a[ ]+href[ ]*=[ ]*"([a-zA-Z0-9\/\\\\@:%_+.~#*?&=\-]+)"[ ]*>(.+)<\/a>$/';
			preg_match($pattern, $body_data[$t], $match);
			if(count($match) !== 3){
				return false;
			}
			$link = $_G['siteurl']. $match[1];
			$title = ('pic' == $t) ? $body_data['title'] : $match[2];
		}else{
			$link = $body_data['data'];
		}
		
		$status = !empty($share['body_general']) ? $share['body_general'] : $body_data['title_template'];

		if(!empty($title)){
            $status .= '  '. strval($title);
        }
        $status = $link . ' ' . $status;

        $status = eze_convert($status, $_G['charset'], 'UTF-8');
        $status = eze_filter($status);
        $status = substr($status, 0, 1000);
        return $status;
    }

    static function sync_comment($cid, $sync_to){
        $comment = DB::fetch_first("SELECT cid,uid,idtype,id,authorid,message FROM " . DB::table('home_comment')  . " WHERE cid = $cid ");
        $status = self::format_comment_status($comment);
        self::publish($comment['authorid'], $sync_to, $status);
    }

    static function format_comment_status($comment){
        global $_G;
        switch($comment['idtype']){
            case 'blogid':
                $do = 'blog';
                break;
            case 'sid':
                $do = 'share';
                break;
            default:
                return;
        }
        $link = $_G['siteurl'] . "home.php?mod=space&do=$do&uid={$comment[uid]}&id={$comment[id]}#comment_anchor_{$comment[cid]}";
        $status = eze_convert($comment['message'], $_G['charset'], 'UTF-8');
        $status = $link . ' ' . eze_filter($status);
        $status = substr($status, 0, 1000);
        return $status;
    }

    static function sync_doingcomment($dcid, $sync_to){
        $comment = DB::fetch_first("SELECT id,uid,message FROM " . DB::table('home_docomment')  . " WHERE id = $dcid ");
        $status = self::format_doingcomment_status($comment);
        self::publish($comment['uid'], $sync_to, $status);
    }

    static function format_doingcomment_status($docomment){
        global $_G;
        $status = eze_convert($docomment['message'], $_G['charset'], 'UTF-8');
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
