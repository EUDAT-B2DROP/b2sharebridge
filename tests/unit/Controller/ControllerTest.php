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
use OCP\Template;


class ViewControllerTest extends PHPUnit_Framework_TestCase {

    private $controller;
    private $userId = 'john';
    private $navigation;
    private $statusCodes;

    public function setUp() {
        $request = $this->getMockBuilder('OCP\IRequest')->getMock();
        $config = $this->getMockBuilder('OCP\IConfig')->getMock();
        $mapper = $this->getMockBuilder('OCA\B2shareBridge\Model\DepositStatusMapper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->statusCodes = $this->getMockBuilder('OCA\B2shareBridge\Model\StatusCodes')
            ->getMock();

        $this->navigation = $this->getMockBuilder('OCA\B2shareBridge\View\Navigation')
            ->disableOriginalConstructor()
            ->getMock();

        $template = $this->createMock(Template::class);

        $this->navigation->method('getTemplate')
            ->willReturn($template);

        $this->controller = new ViewController(
            'b2sharebridge', $request, $config, $mapper,  $this->statusCodes, $this->userId, $this->navigation
        );
    }

    public function testList() {
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
