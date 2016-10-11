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

$installedVersion = \OC::$server->getConfig()->getAppValue(
    'b2sharebridge',
    'installed_version'
);
$connection = \OC::$server->getDatabaseConnection();

if (version_compare($installedVersion, '0.0.8', '<')) {
    $connection->executeQuery(
        'DROP TABLE `*PREFIX*b2sharebridge_filecache_status`'
    );

}

if (version_compare($installedVersion, '0.0.9', '<')
    and $connection->tableExists('b2sharebridge_status_code')
) {
    $connection->dropTable('b2sharebridge_status_code');
}
