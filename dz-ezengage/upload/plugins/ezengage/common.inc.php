<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}
//@include_once DISCUZ_ROOT.'./forumdata/cache/plugin_'.$identifier.'.php';
//$G_EZE_OPTIONS = $_DPLUGIN[$identifier]['vars'];
@include_once DISCUZ_ROOT.'./forumdata/cache/plugin_ezengage.php';
$G_EZE_OPTIONS = $_DPLUGIN['ezengage']['vars'];
@include_once DISCUZ_ROOT.'./plugins/ezengage/ezengage.class.php';
//@require_once DISCUZ_ROOT.'./forumdata/plugins/ezengage.lang.php';
