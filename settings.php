<?php

OC_Util::checkAdminUser();

$config = \OC::$server->getConfig();

$tmpl = new OCP\Template( 'eudat', 'settings');
$tmpl->assign('b2share_bridge_enabled', $config->getAppValue('eudat', 'b2share_bridge_enabled', 'no'));
$tmpl->assign('b2share_endpoint_url', $config->getAppValue('eudat', 'b2share_endpoint_url'));

return $tmpl->fetchPage();
