<?php

/**
 * ownCloud - eudat
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright EUDAT 2015
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
