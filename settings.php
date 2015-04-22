<?php

OC_Util::checkAdminUser();

$appConfig = \OC::$server->getAppConfig();

$params = array('b2share_endpoint_url', 'b2share_bridge_enabled');

if($_POST) {
	foreach($params as $param) {
		if (isset($_POST[$param])) {
            $appConfig->setAppValue('eudat', $param, $_POST[$param]);
		}
	}
}
// fill template
$tmpl = new OCP\Template( 'eudat', 'settings');
$tmpl->assign('b2share_bridge_enabled', $appConfig->getAppValue('eudat', 'b2share_bridge_enabled', '1'));
$tmpl->assign('b2share_endpoint_url', $appConfig->getAppValue('eudat', 'b2share_endpoint_url', 'asd'));

return $tmpl->fetchPage();
