<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license   AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 */
if (!defined('PHPUNIT_RUN')) {
    define('PHPUNIT_RUN', 1);
}

if (!getenv('NEXTCLOUD_ROOT')) {
    include_once __DIR__ . '/../../../lib/base.php';
} else {
    include_once getenv('NEXTCLOUD_ROOT') . '/lib/base.php';
}

// Fix for "Autoload path not allowed: .../tests/lib/testcase.php"
\OC::$loader->addValidRoot(OC::$SERVERROOT . '/tests');

\OC_App::loadApp('b2sharebridge');

if (!class_exists('\PHPUnit\Framework\TestCase')) {
    include_once 'PHPUnit/Autoload.php';
}

OC_Hook::clear();
