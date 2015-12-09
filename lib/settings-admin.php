<?php
/**
 * ownCloud - b2sharebridge
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright EUDAT 2015
 */
use OCP\Template;
use OCP\Util;

OC_Util::checkAdminUser();

Util::addScript('b2sharebridge', 'settings');
Util::addStyle('b2sharebridge', 'settings');

$config = \OC::$server->getConfig();

$tmpl = new Template( 'b2sharebridge', 'settings-admin');
$tmpl->assign('publish_baseurl', $config->getAppValue('b2sharebridge', 'publish_baseurl'));

return $tmpl->fetchPage();
