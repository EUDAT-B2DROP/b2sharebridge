<?php
/**
 * OwnCloud - B2sharebridge App
 *
 * Settings view for a user, showing the b2share api url
 * PHP Version 5-7
 *
 * @category  Owncloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2015 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

use OCP\Template;
use OCP\User;
use OCP\Util;

User::checkLoggedIn();
$userId = \OC::$server->getUserSession()->getUser()->getUID();


Util::addScript('b2sharebridge', 'settings-personal');

$tmpl = new Template('b2sharebridge', 'settings-personal');
$tmpl->assign(
    'publish_baseurl',
    \OC::$server->getConfig()->getAppValue(
        'b2sharebridge',
        'publish_baseurl'
    )
);

$tmpl->assign(
	'b2share_apitoken',
	\OC::$server->getConfig()->getUserValue(
			$userId
			,'b2sharebridge'
			,'token'
	)
);

return $tmpl->fetchPage();
