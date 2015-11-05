<?php
/**
 * ownCloud - eudat
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright EUDAT 2015
 *
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\Eudat\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there
 */

namespace OCA\Eudat\AppInfo;

$application = new Application();
$application->registerRoutes($this, ['routes' => [
    ['name' => 'Eudat#index', 'url' => '/', 'verb' => 'GET'],
    ['name' => 'Eudat#publish', 'url' => '/publish', 'verb' => 'POST'],
]]);
