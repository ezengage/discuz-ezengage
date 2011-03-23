<?php
if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

@include_once DISCUZ_ROOT . '/source/plugin/ezengage/common.func.php';
@include_once DISCUZ_ROOT . '/source/plugin/ezengage/apiclient.php';

class plugin_ezengage {

    function plugin_ezengage(){
        $this->__construct();
    }

    function __construct() { 
        global $_G;
        include(DISCUZ_ROOT.'/data/plugindata/ezengage.lang.php');
        $this->slang = $scriptlang['ezengage'];
        $this->tlang = $templatelang['ezengage'];
        $this->profile = eze_current_profile();
        $this->options = $_G['cache']['plugin']['ezengage'];
    }

    function global_footer(){
        global $_G;
        if(!$_G['uid']){
            return $this->_top_login_widget();
        }
    }

    function _top_login_widget(){
        $html = "<div style='display:none' id='eze_footer_wrap'>" . eze_login_widget('tiny', 150, 54) . "</div>";
        $script = "
            <script type='text/javascript'>
            try{
                var _target = document.getElementById('lsform');
                _eze_login = document.getElementById('eze_footer_wrap').firstChild;
                _eze_login.parentNode.removeChild(_eze_login);
                _target.parentNode.insertBefore(_eze_login, _target); 
            }
            catch(e){
            }
        </script>
        <style>
        </style>
        ";
        return $html . $script;
    }

 	function global_footerlink() {
        return sprintf(
            '<span class="pipe">|</span><a href="http://ezengage.com/?utm_source=%s&utm_medium=powerby-footerlink" title="%s">%s</a>', 
            $this->options['eze_app_domain'],
            $this->slang['footer_link_title'],
            $this->slang['footer_link_text']
        );
    }
}
	
class plugin_ezengage_forum extends plugin_ezengage {

    function plugin_ezengage_forum() { 
        $this->__construct();
    } 

    function __construct() { 
        parent::__construct(); 
    } 

    function post_register_shutdown(){
        global $_G;
        //only in post
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            if($_G['gp_action'] == 'newthread' || $_G['gp_action'] == 'reply'){
                if(!isset($_G['gp_eze_sync_event'])){
                    $_G['gp_eze_sync_event'] = $_G['gp_action'];
                    $_G['gp_eze_should_sync'] = eze_get_default_sync_to($_G['uid'], $_G['gp_eze_sync_event']);
                }
                if(count($_G['gp_eze_should_sync']) > 0){
                    register_shutdown_function(array('plugin_ezengage_forum', '_sync_post'));
                }
            }
        }
    }

    static function _sync_post(){
        global $_G;
        $pid = isset($GLOBALS['pid'])  ? (int)$GLOBALS['pid'] : 0;
        if($pid >= 1){
            $event = $_G['gp_eze_sync_event'];
            if($event == 'newthread'){
                eze_publisher::sync_newthread($pid, $_G['gp_eze_should_sync']);
            }
            else if($event == 'reply'){
                eze_publisher::sync_reply($pid, $_G['gp_eze_should_sync']);
            }
        }
    }
}

class plugin_ezengage_home extends plugin_ezengage{

    function plugin_ezengage_home() { 
        $this->__construct();
    } 

    function __construct() { 
        parent::__construct(); 
    } 

    function spacecp_register_shutdown(){
        global $_G;
        if($_SERVER['REQUEST_METHOD'] == 'POST'){
            switch($_G['gp_ac']){
                case 'share':
                    $_G['gp_eze_sync_event'] = 'newshare';
                    break;
                case 'blog':
                    $_G['gp_eze_sync_event'] = 'newblog';
                    break;
                case 'doing':
                    if($_G['gp_op'] == 'comment'){
                        $_G['gp_eze_sync_event'] = 'doingcomment';
                    }
                    else{
                        $_G['gp_eze_sync_event'] = 'newdoing';
                    }
                    break;
                case 'comment':
                    if($_G['gp_idtype'] == 'blogid'){
                        $_G['gp_eze_sync_event'] = 'blogcomment';
                    }
                    else if($_G['gp_idtype'] == 'sid'){
                        $_G['gp_eze_sync_event'] = 'sharecomment';
                    }
                    break;
            }
            if(isset($_G['gp_eze_sync_event'])){
                $_G['gp_eze_should_sync'] = eze_get_default_sync_to($_G['uid'], $_G['gp_eze_sync_event']);
                if(count($_G['gp_eze_should_sync']) > 0){
                    $func = array('plugin_ezengage_home', '_sync_' . $_G['gp_eze_sync_event']);
                    if(is_callable($func)){
                        register_shutdown_function($func);
                    }
                }
            }
        }
    }

    static function _sync_newshare(){
        global $_G;
        $sid = isset($GLOBALS['sid']) ? (int)$GLOBALS['sid'] : 0; 
        if($sid >= 1){
            eze_publisher::sync_newshare($sid, $_G['gp_eze_should_sync']);
        }
    }

    static function _sync_newblog(){
        global $_G;
        $blogid = isset($GLOBALS['newblog']['blogid']) ? (int)$GLOBALS['newblog']['blogid'] : 0;
        if($blogid >= 1){
            eze_publisher::sync_newblog($blogid, $_G['gp_eze_should_sync']);
        }
    }

    static function _sync_newdoing(){
        global $_G;
        $doid = isset($GLOBALS['newdoid'])  ? (int)$GLOBALS['newdoid'] : 0;
        if($doid >= 1){
            eze_publisher::sync_newdoing($doid, $_G['gp_eze_should_sync']);
        }
    }

    static function _sync_doingcomment(){
        global $_G;
        $newid = intval($GLOBALS['newid']);
        if($newid > 0){
            eze_publisher::sync_doingcomment($newid, $_G['gp_eze_should_sync']);
        }
    }

    static function _sync_blogcomment(){
        self::_sync_comment();
    }

    static function _sync_sharecomment(){
        self::_sync_comment();
    }

    static function _sync_comment(){
        global $_G;
        $cid = intval($GLOBALS['cid']);
        if($cid > 0){
            eze_publisher::sync_comment($cid, $_G['gp_eze_should_sync']);
        }
    }
}

class plugin_ezengage_member extends plugin_ezengage{

    function plugin_ezengage_member() { 
        $this->__construct();
    } 

    function __construct() { 
        parent::__construct(); 
    } 

	function logging_input() {
		global $_G;
		if(!$_G['uid']){
            return eze_login_widget('medium', 252, 150);
		}
	}
	
	function register_side() {
		global $_G;
		if(!$_G['uid']){
            $profile = $this->profile;
            if(!empty($profile)){
                $html = "<p id='eze_login_tip'>
                    {$this->tlang[auto_bind_after_login]}<br/>
                    $profile[provider_name]
                    {$this->tlang[account]}
                    $profile[preferred_username]
                    </p>
                ";
                $script = "<script type='text/javascript'>
                    document.getElementById('username').value = '$profile[preferred_username]';
                </script>";
                return $html . $script;
            }
            else{
                return eze_login_widget('medium', 250, 125);
            }
		}
	}

    function register_top(){
        global $_G;
        if(!$_G['uid']){
            $profile = $this->profile;
            if(!empty($profile)){
                $html = "<h1 id='eze_reg_tip'>
                {$this->tlang[you_are_using]}$profile[provider_name]
                {$this->tlang[account]}<i>$profile[preferred_username]</i>.  
                {$this->tlang[for_better_service]}
                </h1>";
                return $html;
            }
        }
    }

    function register_bind(){
        if($this->profile){
            register_shutdown_function('eze_bind', $this->profile, TRUE);
        }
    }

    function logging_bind(){
        if($this->profile){
            register_shutdown_function('eze_bind', $this->profile, False);
        }
    }
}


