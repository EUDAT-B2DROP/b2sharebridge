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

$states = [
    array(
        'status_code' => 0,
        'message'     => 'published'
    ),
    array(
        'status_code' => 1,
        'message'     => 'new'
    ),
    array(
        'status_code' => 2,
        'message'     => 'processing'
    ),
    array(
        'status_code' => 3,
        'message'     => 'External error: during uploading file'
    ),
    array(
        'status_code' => 4,
        'message'     => 'External error: during creating deposit'
    ),
    array(
        'status_code' => 5,
        'message'     => 'Internal error: file not accessible'
    ),
];

foreach ($states as $state) {
    try {
        if (version_compare($installedVersion, '0.0.8', '<')) {
            \OC::$server->getLogger()->debug(
                'Inside update function for b2sharebridge older 0.0.7',
                ['app' => 'b2sharebridge']
            );

            //alter old table, changing from string to int
            $connection->executeUpdate(
                'UPDATE `*PREFIX*b2sharebridge_filecache_status` SET `status`'
                . ' = ? WHERE `status` = ?', $state
            );
        }
        // create new table only holding states
        $result = $connection->insertIfNotExist(
            '*PREFIX*b2sharebridge_status_code',
            $state
        );
    } catch (\Exception $e) {
        \OC::$server->getLogger()->error(
            'Error while updating b2sharebridge 0.0.7 to 0.0.8',
            ['app' => 'b2sharebridge']
        );
        return false;
    }
}

