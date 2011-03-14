<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

@include_once DISCUZ_ROOT.'./plugins/ezengage/ezengage.class.php';
@include_once DISCUZ_ROOT.'./forumdata/cache/plugin_'.$identifier.'.php';

unset($name, $directory, $vars);

extract($_DPLUGIN[$identifier], EXTR_SKIP);
extract($vars);

eze_sync_thread(intval($uid), intval($pid), intval($tid));
?>
