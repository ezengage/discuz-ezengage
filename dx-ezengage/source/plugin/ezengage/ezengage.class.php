<?php
//error_reporting(E_ALL);
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
        include(DISCUZ_ROOT.'/data/plugindata/ezengage.lang.php');
        $this->slang = $scriptlang['ezengage'];
        $this->tlang = $templatelang['ezengage'];
        $this->profile = eze_current_profile();
    }

    function global_footer(){
        global $_G;
        if($_G['uid'] > 0){
            $this->_try_bind();
            return '';
        }
        else{
            return $this->_top_login_widget();
        }
    }

    function _try_bind(){
        global $_G;
        if($this->profile){
            $ret = DB::query(sprintf(
                "UPDATE " . DB::table("eze_profile") . " SET uid = %d WHERE pid = '%s'",
                $_G['uid'], $this->profile['pid'])
            );
            dsetcookie('eze_token', '');
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

/*
 	function global_footerlink() {
        global $G_EZE_OPTIONS;
        if($G_EZE_OPTIONS['eze_disable_auto_modify_template']){
            return '';
        }
		global $db, $tablepre, $discuz_uid, $discuz_user, $scriptlang;
        return '<span class="pipe">|</span><a href="http://ezengage.com/?utm_source=party&utm_medium-friendlnk&utm_term=">ezEngage</a>';
    }
*/
}
	
class plugin_ezengage_forum extends plugin_ezengage {

    var $insert_sync_checkbox_script = "
        <script type='text/javascript'>
        try{
            function _add_eze_checkbox_wrapper(){
                var _target = document.getElementById('fastpostsubmit').parentNode;
                _eze_div = document.getElementById('eze_sync_checkbox_wrapper')
                _eze_div.parentNode.removeChild(_eze_div)
                _target.parentNode.insertBefore(_eze_div, _target); 
                display('eze_sync_checkbox_wrapper');
            }
            _attachEvent(window, 'load', _add_eze_checkbox_wrapper, null);
        }
        catch(e){
        }
        </script>
    ";

    function plugin_ezengage_forum() { 
        $this->__construct();
    } 

    function __construct() { 
        parent::__construct(); 
    } 

    function post_middle(){
        global $_G;
        if($_G['uid']){
            return eze_sync_checkbox_wrapper($_G['uid'], $_G['gp_action']);
        }
    }

    function forumdisplay_fastpost_content(){
        global $_G;
        if(!$_G['uid']){
            return '';
        }
        $html = eze_sync_checkbox_wrapper($_G['uid'], 'newthread', false);
        return $html . $this->insert_sync_checkbox_script;
    }

    function viewthread_fastpost_content(){
        global $_G;
        if(!$_G['uid']){
            return '';
        }
        $html = eze_sync_checkbox_wrapper($_G['uid'], 'reply', false);
        return $html . $this->insert_sync_checkbox_script;
    }

    function post_register_shutdown(){
        global $_G;
        if(count($_G['gp_eze_should_sync']) > 0){
            register_shutdown_function(array('plugin_ezengage_forum', '_sync_post'));
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

    function spacecp_bottom(){
        global $_G;
        if(!$_G['uid']){
            return '';
        }
        if($_G['gp_ac'] == 'blog'){
            $html = eze_sync_checkbox_wrapper($_G['uid'], 'newblog', false);
            $script = "
            <script type='text/javascript'>
            try{
                function _add_eze_checkbox_wrapper(){
                    var _target = document.getElementById('ttHtmlEditor')
                    _eze_div = document.getElementById('eze_sync_checkbox_wrapper')
                    _eze_div.parentNode.removeChild(_eze_div)
                    _target.appendChild(_eze_div); 
                    display('eze_sync_checkbox_wrapper');
                }
                _add_eze_checkbox_wrapper();
            }
            catch(e){
            }
            </script>
            ";
            return $html . $script;
        }
    }

    function space_share_bottom(){
        global $_G;
        if(!$_G['uid']){
            return '';
        }
        $html = eze_sync_checkbox_wrapper($_G['uid'], 'newshare', false);
        $script = "
        <script type='text/javascript'>
        try{
            function _add_eze_checkbox_wrapper(){
                var _target = document.getElementById('shareform')
                _eze_div = document.getElementById('eze_sync_checkbox_wrapper')
                _eze_div.parentNode.removeChild(_eze_div)
                _target.appendChild(_eze_div); 
                display('eze_sync_checkbox_wrapper');
            }
            _add_eze_checkbox_wrapper();
        }
        catch(e){
        }
        </script>
        ";
        return $html . $script;
    }

    function space_doing_bottom(){
        global $_G;
        if(!$_G['uid']){
            return '';
        }
        $html = eze_sync_checkbox_wrapper($_G['uid'], 'newdoing', false);
        $script = "
        <script type='text/javascript'>
        try{
            function _add_eze_checkbox_wrapper(){
                var _target = document.getElementById('mood_addform')
                _eze_div = document.getElementById('eze_sync_checkbox_wrapper')
                _eze_div.parentNode.removeChild(_eze_div)
                _target.appendChild(_eze_div); 
                display('eze_sync_checkbox_wrapper');
            }
            _add_eze_checkbox_wrapper();
            //_attachEvent(window, 'load', _add_eze_checkbox_wrapper, null);
        }
        catch(e){
        }
        </script>
        ";
        return $html . $script;
    }

    function spacecp_register_shutdown(){
        global $_G;
        if(count($_G['gp_eze_should_sync']) > 0){
            $func = array('plugin_ezengage_home', '_sync_' . strval($_G['gp_eze_sync_event']));
            if(is_callable($func)){
                register_shutdown_function($func);
            }
        }
    }

    static function _sync_newshare(){
        global $_G;
        $arr = isset($GLOBALS['arr']) ? (array)$GLOBALS['arr'] : array();
        $sid = isset($GLOBALS['sid']) ? (int)$GLOBALS['sid'] : 0; 
        if($sid >= 1){
            eze_publisher::sync_newshare($sid, $arr, $_G['gp_eze_should_sync']);
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
            return eze_login_widget('medium');
		}
        else{
            return '';
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
        else{
            return '';
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
}


