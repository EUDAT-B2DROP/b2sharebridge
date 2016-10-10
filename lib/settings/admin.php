<?php
/**
 * OwnCloud - B2sharebridge App
 *
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
use OCP\Util;

script('b2sharebridge', 'settings');

$config = \OC::$server->getConfig();

$tmpl = new Template('b2sharebridge', 'settings-admin');
$tmpl->assign(
    'publish_baseurl',
    $config->getAppValue(
        'b2sharebridge',
        'publish_baseurl'
    )
);
$tmpl->assign(
    'max_uploads',
    $config->getAppValue(
        'b2sharebridge',
        'max_uploads'
    )
);
$tmpl->assign(
    'max_upload_filesize',
    $config->getAppValue(
        'b2sharebridge',
        'max_upload_filesize'
    )
);

return $tmpl->fetchPage();
