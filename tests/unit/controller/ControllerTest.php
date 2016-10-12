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


class ViewControllerTest extends PHPUnit_Framework_TestCase {

    private $controller;
    private $userId = 'john';

    public function setUp() {
        $request = $this->getMockBuilder('OCP\IRequest')->getMock();
        $config = $this->getMockBuilder('OCP\IConfig')->getMock();
        $mapper = $this->getMockBuilder('OCA\B2shareBridge\Model\DepositStatusMapper')
            ->disableOriginalConstructor()
            ->getMock();
        $statusCodes = $this->getMockBuilder('OCA\B2shareBridge\Model\StatusCodes')
            ->disableOriginalConstructor()
            ->getMock();

        $navigation = $this->getMockBuilder('OCA\B2shareBridge\View\Navigation')
            ->disableOriginalConstructor()
            ->getMock();

        $this->controller = new ViewController(
            'b2sharebridge', $request, $config, $mapper, $statusCodes, $this->userId, $navigation
        );
    }

    public function testIndex() {
        $filter = 'all';
        $result = $this->controller->depositList();
        $this->assertEquals(['user' => 'john', 'publications' => Array (), 'statuscodes' => $this->statusCodes, 'appNavigation' => $this->navigation->getTemplate(), 'filter' => $filter], $result->getParams());
        $this->assertEquals('body', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }
    
    public function testPublished() {
        $filter = 'published';
        $result = $this->controller->depositList($filter);
        $this->assertEquals(['user' => 'john', 'publications' => Array (), 'statuscodes' => $this->statusCodes, 'appNavigation' => $this->navigation->getTemplate(), 'filter' => $filter], $result->getParams());
        $this->assertEquals('body', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }
    
    public function testPending() {
        $filter = 'pending';
        $result = $this->controller->depositList($filter);
        $this->assertEquals(['user' => 'john', 'publications' => Array (), 'statuscodes' => $this->statusCodes, 'appNavigation' => $this->navigation->getTemplate(), 'filter' => $filter], $result->getParams());
        $this->assertEquals('body', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }
    
    public function testFailed() {
        $filter = 'failed';
        $result = $this->controller->depositList($filter);
        $this->assertEquals(['user' => 'john', 'publications' => Array (), 'statuscodes' => $this->statusCodes, 'appNavigation' => $this->navigation->getTemplate(), 'filter' => $filter], $result->getParams());
        $this->assertEquals('body', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }
}
