<?php
if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}
@include_once DISCUZ_ROOT.'./plugins/ezengage/apiclient.php';
@include_once DISCUZ_ROOT.'./forumdata/cache/plugin_ezengage.php';

class plugin_ezengage {

    function global_header(){  
        global $G_EZE_OPTIONS;
        if($G_EZE_OPTIONS['eze_disable_auto_modify_template']){
            return '';
        }
        global $discuz_uid;
        global $scriptlang;
        if($discuz_uid > 0){
            return '';
        }
        $login_link_label = $scriptlang['ezengage']['login_link_label'];
        $html = "<a id='eze_top_login_link' style='display:none;' onclick='showWindow(\"eze-login\",this.href);return false;' href='plugin.php?id=ezengage:login'>$login_link_label</a>";
        $script = "<script type='text/javascript'>
            try{
                var _eze_login_menu = document.getElementById('umenu');
                var _eze_login_link = document.getElementById('eze_top_login_link');
                _eze_login_menu.appendChild(_eze_login_link);
                display('eze_top_login_link');
            }
            catch(e){
            }
            </script>";
        return $html . $script;
    }

    function global_footer(){
        global $G_EZE_OPTIONS;
        if($G_EZE_OPTIONS['eze_disable_auto_modify_template']){
            return '';
        }
        if(CURSCRIPT == 'logging'){
            global $scriptlang;
            $login_link_label = $scriptlang['ezengage']['login_link_label_long'];
            $script = "<script type='text/javascript'>
                try{
                    var _eze_login_form = document.getElementById('loginform');
                    var _eze_login_link = document.createElement('a');
                    _eze_login_link.href = 'plugin.php?id=ezengage:login';
                    _eze_login_link.appendChild(document.createTextNode('$login_link_label'));
                    _eze_login_form.appendChild(_eze_login_link);
                }
                catch(e){
                }
                </script>";
            return $script;
        }
        else{
            return '';
        }
    }

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
}
	

