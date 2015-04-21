<?php

use OCP\IConfig;

OC_Util::checkAdminUser();

$params = array('b2share_endpoint_url', 'b2share_bridge_enabled');

if($_POST) {
	foreach($params as $param) {
		if (isset($_POST[$param])) {
			OCP\IConfig::setAppValue('eudat', $param, $_POST[$param]);
		}
	}
}
// fill template
$tmpl = new OCP\Template( 'eudat', 'settings');
$tmpl->assign('b2share_endpoint_url', OCP\IConfig::getAppValue('eudat', 'b2share_endpoint_url', 'asd'));
$tmpl->assign('b2share_bridge_enabled', OCP\IConfig::getAppValue('eudat', 'b2share_bridge_enabled', '0'));

return $tmpl->fetchPage();
