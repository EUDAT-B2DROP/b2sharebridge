<?php

OC_Util::checkAdminUser();

$config = \OC::$server->getConfig();

$params = array('b2share_endpoint_url', 'b2share_bridge_enabled');

if($_POST) {
	foreach($params as $param) {
		if (isset($_POST[$param])) {
            $config->setAppValue('eudat', $param, $_POST[$param]);
		}
	}
}
// fill template
$tmpl = new OCP\Template( 'eudat', 'settings');
$tmpl->assign('b2share_bridge_enabled', $config->getAppValue('eudat', 'b2share_bridge_enabled', 'no'));
$tmpl->assign('b2share_endpoint_url', $config->getAppValue('eudat', 'b2share_endpoint_url', 'asd'));

return $tmpl->fetchPage();
