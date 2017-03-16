<?php
/**
 * b2sharebridge
 *
 * This file is licensed under the MIT License. See the LICENSE file.
 *
 * @author    Dennis Blommesteijn <dennis@blommesteijn.com>
 * @copyright Dennis Blommesteijn 2015
 */

namespace OCA\B2shareBridge\Controller;

use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http\TemplateResponse;

class ViewControllerTest extends TestCase
{

    private $controller;
    private $userId = 'john';
    private $navigation;
    private $statusCodes;

    public function setUp() 
    {
        $this->markTestSkipped(
            'ViewControllerTest currently not implemented because of missing persistency.'
        );
        $request = $this->getMockBuilder('OCP\IRequest')->getMock();
        $config = $this->getMockBuilder('OCP\IConfig')->getMock();
        $deposit_mapper = $this->getMockBuilder('OCA\B2shareBridge\Model\DepositStatusMapper')
            ->disableOriginalConstructor()
            ->getMock();
        $community_mapper = $this->getMockBuilder('OCA\B2shareBridge\Model\CommunityMapper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->statusCodes = $this->getMockBuilder('OCA\B2shareBridge\Model\StatusCodes')
            ->getMock();

        $this->navigation = $this->getMockBuilder('OCA\B2shareBridge\View\Navigation')
            ->disableOriginalConstructor()
            ->getMock();

        $this->navigation->method('getTemplate')
            ->willReturn($this->returnValue('OCP\AppFramework\Http\TemplateResponse'));

        $this->controller = new ViewController(
            'b2sharebridge', $request, $config, $deposit_mapper, $community_mapper, $this->statusCodes, $this->userId, $this->navigation
        );
    }

    public function testList() 
    {
        $this->markTestSkipped(
            'ViewControllerTest currently not implemented because of missing persistency.'
        );
        $filter = 'all';
        $result = $this->controller->depositList();
        $this->assertEquals(['user' => 'john', 'publications' => Array (), 'statuscodes' => $this->statusCodes, 'appNavigation' => $this->navigation->getTemplate(), 'filter' => $filter], $result->getParams());
        $this->assertEquals('body', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }
    
    public function testPublished() 
    {
        $this->markTestSkipped(
            'ViewControllerTest currently not implemented because of missing persistency.'
        );
        $filter = 'published';
        $result = $this->controller->depositList($filter);
        $this->assertEquals(['user' => 'john', 'publications' => Array (), 'statuscodes' => $this->statusCodes, 'appNavigation' => $this->navigation->getTemplate(), 'filter' => $filter], $result->getParams());
        $this->assertEquals('body', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }
    
    public function testPending() 
    {
        $this->markTestSkipped(
            'ViewControllerTest currently not implemented because of missing persistency.'
        );
        $filter = 'pending';
        $result = $this->controller->depositList($filter);
        $this->assertEquals(['user' => 'john', 'publications' => Array (), 'statuscodes' => $this->statusCodes, 'appNavigation' => $this->navigation->getTemplate(), 'filter' => $filter], $result->getParams());
        $this->assertEquals('body', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }
    
    public function testFailed() 
    {
        $this->markTestSkipped(
            'ViewControllerTest currently not implemented because of missing persistency.'
        );
        $filter = 'failed';
        $result = $this->controller->depositList($filter);
        $this->assertEquals(['user' => 'john', 'publications' => Array (), 'statuscodes' => $this->statusCodes, 'appNavigation' => $this->navigation->getTemplate(), 'filter' => $filter], $result->getParams());
        $this->assertEquals('body', $result->getTemplateName());
        $this->assertTrue($result instanceof TemplateResponse);
    }
}
