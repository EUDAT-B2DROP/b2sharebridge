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

use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\DepositFileMapper;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Model\StatusCodes;
use OCA\B2shareBridge\Publish\B2ShareFactory;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Notification\IManager;
use PHPUnit\Framework\TestCase;
use OCP\AppFramework\Http;
use Psr\Log\LoggerInterface;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;

class PublishControllerTest extends TestCase
{
    private PublishController $controller;
    private string $userId = 'john';

    private $request;
    private $config;
    private $deposit_mapper;
    private $deposit_file_mapper;
    private $statusCodes;
    private $server_mapper;
    private $community_mapper;
    private $logger;
    private $storage;
    private $manager;
    private $jobList;
    private $b2shareFactory;

    public function setUp(): void
    {
        parent::setUp();
        
        $this->request = $this->getMockBuilder(IRequest::class)->getMock();
        $this->config = $this->getMockBuilder(IConfig::class)->getMock();
        $this->deposit_mapper = $this->getMockBuilder(DepositStatusMapper::class)
            ->onlyMethods(['insert', 'findAllForUserAndStateString'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->deposit_mapper->method('findAllForUserAndStateString')
            ->willReturn([]);
        $this->deposit_file_mapper = $this->getMockBuilder(DepositFileMapper::class)
            ->onlyMethods(['insert'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->statusCodes = $this->getMockBuilder(StatusCodes::class)
            ->getMock();
        $this->server_mapper = $this->getMockBuilder(ServerMapper::class)
            ->onlyMethods(['find'])
            ->disableOriginalConstructor()
            ->getMock();
        $this->community_mapper = $this->getMockBuilder(CommunityMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storage = $this->getMockBuilder(IRootFolder::class)->getMock();
        $this->manager = $this->getMockBuilder(IManager::class)->getMock();
        $this->jobList = $this->getMockBuilder(IJobList::class)->getMock();
        $this->b2shareFactory = $this->getMockBuilder(B2ShareFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
    }

    private function createController($userId = 'john')
    {
        $this->controller = new PublishController(
            'b2sharebridge',
            $this->request,
            $this->config,
            $this->deposit_mapper,
            $this->deposit_file_mapper,
            $this->statusCodes,
            $this->getMockBuilder(\OCP\AppFramework\Utility\ITimeFactory::class)->getMock(),
            $this->server_mapper,
            $this->community_mapper,
            $this->manager,
            $this->logger,
            $this->jobList,
            $this->storage,
            $this->b2shareFactory,
            $userId
        );
    }

    public function testPublishMissingUserId()
    {
        $this->createController(null);
        $result = $this->controller->publish();
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
        $this->assertEquals(["message" => "missing user id"], $result->getData());
    }

    public function testPublishMissingParameters()
    {
        $this->createController($this->userId);
        $this->request->method('getParams')->willReturn([]);
        $result = $this->controller->publish();
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
        $this->assertEquals([
            'message' => 'Missing parameters for publishing',
            'status' => 'error'
        ], $result->getData());
    }

    public function testAttachMissingUserId()
    {
        $this->createController(null);
        $result = $this->controller->attach();
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
        $this->assertEquals(["message" => "missing user id"], $result->getData());
    }

    public function testAttachMissingParameters()
    {
        $this->createController($this->userId);
        $this->request->method('getParams')->willReturn([]);
        $result = $this->controller->attach();
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
        $this->assertEquals([
            'message' => 'Missing parameters for publishing',
            'status' => 'error'
        ], $result->getData());
    }

    public function testNextVersionMissingUserId()
    {
        $this->createController(null);
        $result = $this->controller->nextVersion();
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
        $this->assertEquals(["message" => "missing user id"], $result->getData());
    }

    public function testNextVersionMissingParameters()
    {
        $this->createController($this->userId);
        $this->request->method('getParams')->willReturn([]);
        $result = $this->controller->nextVersion();
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
        $this->assertEquals([
            'message' => 'Missing parameters for file uploads',
            'status' => 'error'
        ], $result->getData());
    }

    public function testNextVersionInvalidServerId()
    {
        $this->createController($this->userId);
        $params = [
            'server_id' => 1,
            'recordId' => 'test-record-id'
        ];
        $this->request->method('getParams')->willReturn($params);
        
        $this->server_mapper->method('find')
            ->willThrowException(new DoesNotExistException('Server not found'));
        
        $result = $this->controller->nextVersion();
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
        $this->assertEquals([
            'message' => 'Invalid server id',
            'status' => 'error'
        ], $result->getData());
    }
}
