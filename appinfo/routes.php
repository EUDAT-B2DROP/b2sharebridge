<?php

/**
 * OwnCloud - B2sharebridge App
 *
 * Create your routes in here. The name is the lowercase name of the Controller
 * without the Controller part, the stuff after the hash is the method.
 * e.g. page#index -> OCA\Eudat\Controller\PageController->index()
 *
 * The Controller class has to be registered in the Application.php file since
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

$application = new OCA\B2shareBridge\AppInfo\Application();

$application->registerRoutes(
    $this,
    ['routes' => [
        ['name' => 'B2shareBridge#publish', 'url' => '/publish', 'verb' => 'POST'],
        ['name' => 'View#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'View#filter_pending', 'url' => '/pending',
        'verb' => 'GET'],
        ['name' => 'View#filter_published', 'url' => '/published',
        'verb' => 'GET'],
        ['name' => 'View#index', 'url' => '/all', 'verb' => 'GET'],
        ['name' => 'View#filter_failed', 'url' => '/failed',
        'verb' => 'GET'],
    ]]
);
