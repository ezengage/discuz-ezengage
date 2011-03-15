<?php
@include_once DISCUZ_ROOT.'./plugins/ezengage/apiclient.php';

if(!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class plugin_ezengage {

    function global_header(){  
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

    function loggin_add_ezengage_login_link_output(){
        return '<h1>TEST XXXXXXXX</h1>'; 
    }

    function _eze_sync_checkbox_wrapper_html($uid){
        global $db,$tablepre;
        $eze_profiles = array();
        $query = $db->query("SELECT * FROM {$tablepre}eze_profile WHERE uid=$uid");
        while($profile = $db->fetch_array($query)) {
            $eze_profiles[] = $profile;
        }
        $html = array(
            '<div id="eze_sync_checkbox_wrapper" style="margin-bottom:5px;display:none;">',
        );
        foreach($eze_profiles as $profile){
            if($profile['should_sync']){
                $html[] = "<input name='eze_should_sync[]' type='checkbox' class='checkbox' checked='checked' value='$profile[pid]' />";
            }
            else{
                $html[] = "<input name='eze_should_sync[]' type='checkbox' class='checkbox' value='$profile[pid]' />";
            }
            $html[] =  "同步到$profile[provider_code] 的$profile[display_name]";
        }
        $html[] = '</div>';
        $html = implode('', $html);
        return $html;
    }

    //在快速回复的地方加入同步的选择框
    function viewthread_bottom_output(){
        global $db,$discuz_uid,$tablepre;
        global $action;
        if($discuz_uid <= 0 ){
            return '';
        }
        $html = $this->_eze_sync_checkbox_wrapper_html($discuz_uid);
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
        global $db,$discuz_uid,$tablepre;
        global $action;
        if($discuz_uid <= 0 ){
            return '';
        }
        //只有新发帖才同步，编辑不同步
        if($action != 'newthread' && $action != 'reply'){
            return '';
        }

        $html = $this->_eze_sync_checkbox_wrapper_html($discuz_uid);

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
		global $db, $tablepre, $discuz_uid, $discuz_user, $scriptlang;
        return '<span class="pipe">|</span><a href="http://ezengage.com/?utm_source=party&utm_medium-friendlnk&utm_term=">ezEngage</a>';
    }
}
	

