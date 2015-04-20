<?php

OC_Util::checkAdminUser();

$params = array('b2share_endpoint_url', 'b2share_bridge_enabled');
  
if($_POST) {
	foreach($params as $param) {
		if (isset($_POST[$param])) {
			OCP\Config::setAppValue('eudat', $param, $_POST[$param]);
		}
	}
}

// fill template
$tmpl = new OCP\Template( 'eudat', 'settings');
$tmpl->assign('b2share_endpoint_url', OCP\Config::getAppValue('eudat', 'b2share_endpoint_url', ''));
$tmpl->assign('b2share_bridge_enabled', OCP\Config::getAppValue('eudat', 'b2share_bridge_enabled', '0'));

return $tmpl->fetchPage();
