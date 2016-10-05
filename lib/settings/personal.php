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

User::checkLoggedIn();

$tmpl = new Template('b2sharebridge', 'settings-personal');
$tmpl->assign(
    'publish_baseurl',
    \OC::$server->getConfig()->getAppValue(
        'b2sharebridge',
        'publish_baseurl'
    )
);

return $tmpl->fetchPage();
