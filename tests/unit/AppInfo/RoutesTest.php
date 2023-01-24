<?php
/**
 * B2SHAREBRIDGE
 *
 * PHP Version 7
 *
 * @category  Nextcloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2015 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\Tests\AppInfo;

use PHPUnit\Framework\TestCase;

class RoutesTest extends TestCase
{
    public function testFile()
    {
        $routes = include_once __DIR__ . '/../../../appinfo/routes.php';


        $expected = [
            'routes' => [
                [
                    'name' => 'View#index',
                    'url' => '/',
                    'verb' => 'GET'
                ],
                [
                    'name' => 'Publish#publish',
                    'url' => '/publish',
                    'verb' => 'POST'
                ],
                [
                    'name' => 'View#depositList',
                    'url' => '/deposits',
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
                    'url' => '/gettabviewcontent',
                    'verb' => 'GET'
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
        $this->assertSame($expected, $routes);
    }
}
