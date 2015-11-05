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

use OCA\Eudat\Controller\Eudat;
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

        $container->registerService('EudatController', function(IContainer $c) {
            $server = $c->query('ServerContainer');
            return new Eudat(
                $c->query('AppName'),
                $server->getRequest(),
                $server->getConfig(),
                $c->query('FilecacheStatusMapper'),
                $c->query('CurrentUID')
            );
        });

        $container->registerService('CurrentUID', function(IContainer $c) {
            /** @var \OC\Server $server */
            $server = $c->query('ServerContainer');

            $user = $server->getUserSession()->getUser();
            return ($user) ? $user->getUID() : '';
        });
    }
}
