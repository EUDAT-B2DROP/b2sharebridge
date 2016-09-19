<?php
/**
 * ownCloud - b2sharebridge
 *
 * This file is licensed under the MIT License. See the LICENSE file.
 *
 * @author Dennis Blommesteijn <dennis@blommesteijn.com>
 * @copyright Dennis Blommesteijn 2015
 */

namespace OCA\B2shareBridge\Controller;

use PHPUnit_Framework_TestCase;

use OCP\AppFramework\Http\TemplateResponse;


class PageControllerTest extends PHPUnit_Framework_TestCase {

    private $controller;
    private $userId = 'john';

    public function setUp() {
        $request = $this->getMockBuilder('OCP\IRequest')->getMock();
        $config = $this->getMockBuilder('OCP\IConfig')->getMock();
        $mapper = $this->getMockBuilder('OCA\B2shareBridge\Db\FilecacheStatusMapper')
            ->disableOriginalConstructor()
            ->getMock();
        $scMapper = $this->getMockBuilder('OCA\B2shareBridge\Db\StatusCodeMapper')
            ->disableOriginalConstructor()
            ->getMock();
            

        $this->controller = new B2shareBridge(
            'b2sharebridge', $request, $config, $mapper, $scMapper, $this->userId
        );
    }

    public function testIndex() {
        $result = $this->controller->index();
        $this->assertEquals(['user' => 'john', 'transfers' => Array (), 'publications' => Array (), 'fails' => Array (), 'statuscodes' => Array ()], $result->getParams());
        $this->assertEquals('main', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }
    
    public function testPublished() {
        $result = $this->controller->filterPublished();
        $this->assertEquals(['user' => 'john', 'publications' => Array (), 'statuscodes' => Array ()], $result->getParams());
        $this->assertEquals('published', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }
    
    public function testPending() {
        $result = $this->controller->filterPending();
        $this->assertEquals(['user' => 'john', 'transfers' => Array ()], $result->getParams());
        $this->assertEquals('pending', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }
    
    public function testFailed() {
        $result = $this->controller->filterFailed();
        $this->assertEquals(['user' => 'john', 'fails' => Array (), 'statuscodes' => Array ()], $result->getParams());
        $this->assertEquals('failed', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }
}
