<?php

/**
 * B2SHAREBRIDGE
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


namespace OCA\B2sharebridge\AppInfo;

return [
    'routes' => [
        [
            'name' => 'Publish#publish',
            'url' => '/publish',
            'verb' => 'POST'
        ],
        [
            'name' => 'View#depositList',
            'url' => '/',
            'verb' => 'GET'
        ],
        [
            'name' => 'View#setToken',
            'url' => '/apitoken',
            'verb' => 'POST'
        ],
        [
            'name' => 'View#deleteToken',
            'url' => '/apitoken/{id}',
            'verb' => 'DELETE'
        ],
        [
            'name' => 'View#getTokens',
            'url' => '/apitoken',
            'verb' => 'GET'
        ],
        [
            'name' => 'View#getTabViewContent',
            'url'=>'/gettabviewcontent',
            'verb'=>'GET'
        ],
        [
            'name' => 'View#initializeB2ShareUI',
            'url' => '/initializeb2shareui',
            'verb' => 'GET'
        ],
        [
            'name' => 'Server#listServers',
            'url' => '/servers',
            'verb' => 'GET'
        ],
        [
            'name' => 'Server#saveServers',
            'url' => '/servers',
            'verb' => 'POST'
        ],
        [
            'name' => 'Server#deleteServer',
            'url' => '/servers/{id}',
            'verb' => 'DELETE'
        ]
    ]
];
