<?php

/**
 * OwnCloud - B2sharebridge App
 *
 * Create your routes in here. The name is the lowercase name of the controller
 * without the controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\Eudat\Controller\PageController->index()
 *
 * The controller class has to be registered in the application.php file since
 * it's instantiated in there

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

namespace OCA\B2shareBridge\AppInfo;

$application = new Application();
$application->registerRoutes(
    $this,
    ['routes' => [
        ['name' => 'B2shareBridge#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'B2shareBridge#publish', 'url' => '/publish', 'verb' => 'POST'],
        ['name' => 'B2shareBridge#filter_pending', 'url' => '/pending',
        'verb' => 'GET'],
        ['name' => 'B2shareBridge#filter_published', 'url' => '/published',
        'verb' => 'GET'],
        ['name' => 'B2shareBridge#index', 'url' => '/status', 'verb' => 'GET'],
        ['name' => 'B2shareBridge#filter_failed', 'url' => '/failed',
        'verb' => 'GET'],
    ]]
);
