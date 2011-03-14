<?php
if(!defined('IN_DISCUZ')) {
	exit('Access Denied');
}

@include_once DISCUZ_ROOT.'./plugins/ezengage/common.inc.php';

unset($name, $directory, $vars);

extract($_DPLUGIN[$identifier], EXTR_SKIP);
extract($vars);

include plugintemplate('ezengage_login');
?>
