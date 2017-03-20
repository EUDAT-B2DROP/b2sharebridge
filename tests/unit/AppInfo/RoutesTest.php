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

class Test extends TestCase  {
    public function testFile() {
        $routes = require_once __DIR__ . '/../../../appinfo/routes.php';


        $expected = [
            'routes' => [
                [
                    'name' => 'Publish#publish',
                    'url' => '/publish',
                    'verb' => 'POST'],
                [
                    'name' => 'View#depositList',
                    'url' => '/',
                    'verb' => 'GET'
                ],
                [
                    'name' => 'View#set_token',
                    'url' => '/apitoken',
                    'verb' => 'POST'
                ],
                [
                    'name' => 'View#delete_token',
                    'url' => '/apitoken',
                    'verb' => 'DELETE'
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
            ]
        ];
        $this->assertSame($expected, $routes);
    }
}
