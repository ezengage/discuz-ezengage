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
    //在快速回复的地方加入同步的选择框
    function viewthread_bottom_output(){
        global $G_EZE_OPTIONS;
        if($G_EZE_OPTIONS['eze_disable_auto_modify_template']){
            return '';
        }
        global $db,$discuz_uid,$tablepre;
        global $action;
        if($discuz_uid <= 0 ){
            return '';
        }
        $html = eze_sync_checkbox_wrapper($discuz_uid, false);
        $script = <<<EOT
            <script type='text/javascript'>
            try{
                var _target = document.getElementById('fastpostsubmit').parentNode;
                _eze_div = document.getElementById('eze_sync_checkbox_wrapper')
                _eze_div.parentNode.removeChild(_eze_div)
                _target.parentNode.insertBefore(_eze_div, _target); 
                display('eze_sync_checkbox_wrapper');
            }
            catch(e){
            }
            </script>
EOT;
        return $html . $script;
    }

 	function post_bottom_output() {
        global $G_EZE_OPTIONS;
        if($G_EZE_OPTIONS['eze_disable_auto_modify_template']){
            return '';
        }
 
        global $db,$discuz_uid,$tablepre;
        global $action;
        if($discuz_uid <= 0 ){
            return '';
        }
        //只有新发帖才同步，编辑不同步
        if($action != 'newthread' && $action != 'reply'){
            return '';
        }

        $html = eze_sync_checkbox_wrapper($discuz_uid, false);

        $script = <<<EOT
            <script type='text/javascript'>
            try{
                var _eze_btn_bar = document.getElementById('postsubmit').parentNode;
                var _target = _eze_btn_bar.previousSibling;
                while(_target.nodeName.toUpperCase() != 'DIV'){
                    _target = _target.previousSibling;
                }
                _eze_div = document.getElementById('eze_sync_checkbox_wrapper')
                _eze_div.parentNode.removeChild(_eze_div)
                _target.appendChild(_eze_div);
                display('eze_sync_checkbox_wrapper');
            }
            catch(e){
            }
            </script>
EOT;
        return $html . $script;
    }

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


