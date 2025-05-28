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
use OCA\B2shareBridge\Model\DepositStatus;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Model\StatusCodes;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Notification\IManager;
use PHPUnit\Framework\TestCase;

use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http;
use Psr\Log\LoggerInterface;

class ViewControllerTest extends TestCase
{

    private ViewController $controller;
    private string $userId = 'john';

    private $statusCodes;

    private $request;

    private array $data;
    private $deposit_mapper;

    public function setUp(): void
    {
        parent::setUp();
        $this->request = $this->getMockBuilder(IRequest::class)->getMock();
        $config = $this->getMockBuilder(IConfig::class)->getMock();
        $this->deposit_mapper = $this->getMockBuilder(DepositStatusMapper::class)
            ->onlyMethods(['findAllForUser', 'findAllForUserAndStateString'])
            ->disableOriginalConstructor()
            ->getMock();
        $deposit_file_mapper =
            $this->getMockBuilder(DepositFileMapper::class)
                ->onlyMethods(['getFileCount'])
                ->disableOriginalConstructor()
                ->getMock();
        $community_mapper = $this->getMockBuilder(CommunityMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $server_mapper = $this->getMockBuilder(ServerMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->statusCodes = $this->getMockBuilder(StatusCodes::class)
            ->getMock();

        $logger = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $storage = $this->getMockBuilder(IRootFolder::class)
            ->getMock();

        $manager = $this->getMockBuilder(IManager::class)
            ->getMock();

        $urlGenerator = $this->getMockBuilder(IURLGenerator::class)
            ->getMock();

        $this->data = [
            $this->createDepositStatus($this->userId, 0, "dep1", 1),//published
            $this->createDepositStatus($this->userId, 1, "dep2", 2),//pending
            $this->createDepositStatus($this->userId, 2, "dep3", 1),//pending
            $this->createDepositStatus($this->userId, 3, "dep4", 2),//failed
            $this->createDepositStatus($this->userId, 4, "dep5", 1),//failed
            $this->createDepositStatus($this->userId, 5, "dep6", 2),//failed
        ];

        $this->deposit_mapper->method('findAllForUser')
            ->willReturn($this->data);

        $deposit_file_mapper->method('getFileCount')
            ->willReturn(1);

        $this->controller = new ViewController(
            'b2sharebridge',
            $this->request,
            $config,
            $this->deposit_mapper,
            $deposit_file_mapper,
            $community_mapper,
            $server_mapper,
            $this->statusCodes,
            $storage,
            $manager,
            $urlGenerator,
            $logger,
            $this->userId
        );
    }

    public function createDepositStatus($owner, $status, $title, $serverId): DepositStatus
    {
        $fcStatus = new DepositStatus();
        $fcStatus->setId(1);
        $fcStatus->setOwner($owner);
        $fcStatus->setStatus($status);
        $fcStatus->setCreatedAt(time());
        $fcStatus->setUpdatedAt(time());
        $fcStatus->setTitle($title);
        $fcStatus->setServerId($serverId);
        return $fcStatus;
    }

    public function setParams($filter)
    {
        if ($filter === null) {
            $this->request->method('getParams')
                ->willReturn([]);
            $this->deposit_mapper->method('findAllForUserAndStateString')
                ->willReturn([]);
            return;
        }
        $params = ["filter" => $filter];
        $this->request->method('getParams')
            ->willReturn($params);

        $filtered_data = array_filter(
            $this->data,
            function (DepositStatus $entity) use ($filter) {
                return in_array($entity->getStatus(), $this->deposit_mapper->mapFilterToStates($filter));
            }
        );
        $filtered_data = array_values($filtered_data);  //reset index of array
        $this->deposit_mapper->method('findAllForUserAndStateString')
            ->willReturn($filtered_data);
    }

    public function createDeposit($filter): JSONResponse
    {
        $this->setParams($filter);
        return $this->controller->depositList();
    }

    public function testList()
    {
        $filter = 'all';
        $result = $this->createDeposit($filter);
        $this->assertEquals(Http::STATUS_OK, $result->getStatus());
        foreach ($this->data as $index => $entity) {
            $this->assertEquals(json_decode(json_encode($entity), true), $result->getData()[$index]);
        }
    }

    public function testPublished()
    {
        $filter = 'published';
        $result = $this->createDeposit($filter);
        $this->assertEquals(Http::STATUS_OK, $result->getStatus());
        $this->assertTrue(sizeof($result->getData()) == 1);
        $this->assertEquals(json_decode(json_encode($this->data[0]), true), $result->getData()[0]);
    }

    public function testPending()
    {
        $filter = 'pending';
        $result = $this->createDeposit($filter);
        $this->assertEquals(Http::STATUS_OK, $result->getStatus());
        $this->assertTrue(sizeof($result->getData()) == 2);
        for ($i = 0; $i < sizeof($result->getData()); $i++) {
            $this->assertEquals(json_decode(json_encode($this->data[$i + 1]), true), $result->getData()[$i]);
        }
    }

    public function testFailed()
    {
        $filter = 'failed';
        $result = $this->createDeposit($filter);
        $this->assertEquals(Http::STATUS_OK, $result->getStatus());
        $this->assertTrue(sizeof($result->getData()) == 3);
        for ($i = 0; $i < sizeof($result->getData()); $i++) {
            $this->assertEquals(json_decode(json_encode($this->data[$i + 3]), true), $result->getData()[$i]);
        }
    }

    public function testNoFilter()
    {
        $filter = null;
        $result = $this->createDeposit($filter);
        $this->assertEquals(Http::STATUS_BAD_REQUEST, $result->getStatus());
    }
}
