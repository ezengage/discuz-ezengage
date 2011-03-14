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
        $script = "<script type='text/javascript'>
            try{
            var _eze_login_menu = document.getElementById('umenu');
            var _eze_login_link = document.createElement('a');
            _eze_login_link.href = 'plugin.php?id=ezengage:login';
            _eze_login_link.appendChild(document.createTextNode('$login_link_label'));
            _eze_login_menu.appendChild(_eze_login_link);
            }
            catch(e){
            }
            </script>";
        return $script;
    }

 	function global_footerlink() {
		global $db, $tablepre, $discuz_uid, $discuz_user, $scriptlang;
        return '<span class="pipe">|</span><a href="http://ezengage.com/?utm_source=party&utm_medium-friendlnk&utm_term=">ezEngage</a>';
    }
}
	

