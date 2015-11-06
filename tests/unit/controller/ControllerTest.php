<?php
/**
 * ownCloud - eudat
 *
 * This file is licensed under the MIT License. See the LICENSE file.
 *
 * @author Dennis Blommesteijn <dennis@blommesteijn.com>
 * @copyright Dennis Blommesteijn 2015
 */

namespace OCA\Eudat\Controller;

use PHPUnit_Framework_TestCase;

use OCP\AppFramework\Http\TemplateResponse;


class PageControllerTest extends PHPUnit_Framework_TestCase {

    private $controller;
    private $userId = 'john';

    public function setUp() {
        $request = $this->getMockBuilder('OCP\IRequest')->getMock();
        $config = $this->getMockBuilder('OCP\IConfig')->getMock();
        $mapper = $this->getMockBuilder('OCA\Eudat\Db\FilecacheStatusMapper')->getMock();


        $this->controller = new Eudat(
            'eudat', $request, $config, $mapper, $this->userId
        );
    }

    public function testIndex() {
        $result = $this->controller->index();
        $this->assertEquals(['user' => 'john'], $result->getParams());
    }
}