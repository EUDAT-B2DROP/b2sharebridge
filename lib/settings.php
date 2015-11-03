<?php

use OCP\Template;
use OCP\Util;

OC_Util::checkAdminUser();

Util::addScript('eudat', 'settings');
Util::addStyle('eudat', 'settings');

$config = \OC::$server->getConfig();

$tmpl = new Template( 'eudat', 'settings');
$tmpl->assign('b2share_endpoint_url', $config->getAppValue('eudat', 'b2share_endpoint_url'));

return $tmpl->fetchPage();
