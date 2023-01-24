<?php

use OCA\B2shareBridge\Model\DepositStatus;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\DepositFileMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Publish\B2share;
use PHPUnit\Framework\TestCase;
use \OCA\B2shareBridge\Cron\TransferHandler;

class TransferHandlerTest extends TestCase
{
    private TransferHandler $transferhandler;

    public function setUp(): void
    {
        parent::setUp();

        $deposit_mapper = $this->getMockBuilder(DepositStatusMapper::class)
            ->setMethods(['findAllForUser', 'findAllForUserAndStateString', 'find'])
            ->disableOriginalConstructor()
            ->getMock();

        $fcStatus = new DepositStatus();
        $fcStatus->setOwner("testId");
        $fcStatus->setStatus(0);
        $fcStatus->setCreatedAt(time());
        $fcStatus->setUpdatedAt(time());
        $fcStatus->setTitle("test_title");
        $fcStatus->setServerId(1);

        $deposit_mapper->method("find")
            ->willReturn($fcStatus);

        $deposit_file_mapper =
            $this->getMockBuilder(DepositFileMapper::class)
                ->disableOriginalConstructor()
                ->getMock();
        $publisher =
            $this->getMockBuilder(B2share::class)
                ->disableOriginalConstructor()
                ->getMock();
        $server_mapper = $this->getMockBuilder(ServerMapper::class)
            ->setMethods(['find'])
            ->disableOriginalConstructor()
            ->getMock();

        $server_mapper->method("find")
            ->willReturn(0);  // this doesn't matter, because create is mocked away

        $publisher->method("upload")
            ->willReturn(true);

        $publisher->method("create")
            ->willReturn("https://localhost:80/mockedaway");


        $this->transferhandler = new TransferHandler(null);//use cron environment explicitly!
        $transferhandler_refl = new ReflectionClass($this->transferhandler);

        //DepositStatusMapper
        $transferhandler_deposit_mapper = $transferhandler_refl->getProperty("_mapper");
        $transferhandler_deposit_mapper->setAccessible(true);
        $transferhandler_deposit_mapper->setValue($this->transferhandler, $deposit_mapper);

        //Publisher (B2Share)
        $transferhandler_publisher = $transferhandler_refl->getProperty("_publisher");
        $transferhandler_publisher->setAccessible(true);
        $transferhandler_publisher->setValue($this->transferhandler, $publisher);

        //DepositFileMapper
        $transferhandler_dfmapper = $transferhandler_refl->getProperty("_dfmapper");
        $transferhandler_dfmapper->setAccessible(true);
        $transferhandler_dfmapper->setValue($this->transferhandler, $deposit_file_mapper);

        //ServerMapper
        $transferhandler_smapper = $transferhandler_refl->getProperty("_smapper");
        $transferhandler_smapper->setAccessible(true);
        $transferhandler_smapper->setValue($this->transferhandler, $server_mapper);
    }

    function testRun()
    {
        $args = ["transferId" => 0,
            "token" => "test_token",
            "community" => 1,
            "open_access" => true,
            "title" => "test_title",
            "serverId" => 0
        ];
        $this->transferhandler->run($args);
    }
}