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

OC_Util::checkLoggedIn();

$config = \OC::$server->getConfig();
$tmpl = new Template('b2sharebridge', 'settings-personal');
//$tmpl->assign('publish_url', $config->getAppValue('b2sharebridge', 'publish_baseurl'));
$tmpl->assign('publish_baseurl', $config->getAppValue('b2sharebridge', 'publish_baseurl'));

return $tmpl->fetchPage();
