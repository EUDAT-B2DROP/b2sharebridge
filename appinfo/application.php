<?php

/**
 * ownCloud - Activity App
 *
 * @author Joas Schilling
 * @copyright 2014 Joas Schilling nickvergessen@owncloud.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Eudat\AppInfo;

use OCA\Eudat\Db\FilecacheStatusMapper;
use OCP\AppFramework\App;
use OCP\IContainer;

class Application extends App {
    public function __construct (array $urlParams = array())
    {
        parent::__construct('eudat', $urlParams);
        $container = $this->getContainer();

        $container->registerService('EudatL10N', function (IContainer $c) {
            return $c->query('ServerContainer')->getL10N('eudat');
        });
        $container->registerService('FilecacheStatusMapper', function (IContainer $c) {
            $server = $c->query('ServerContainer');
            return new FilecacheStatusMapper(
                $server->getDatabaseConnection()
            );
        });

    }
}
