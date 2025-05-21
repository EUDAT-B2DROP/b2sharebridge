<?php
/**
 * OwnCloud - B2sharebridge App
 *
 * PHP Version 5-7
 *
 * @category  Owncloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2015 EUDAT
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\Controller;

use OCP\Files\IRootFolder;
use OCP\Files\File;
use OCA\B2shareBridge\AppInfo\Application;
use OCA\B2shareBridge\Cron\TransferHandler;
use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\DepositStatus;
use OCA\B2shareBridge\Model\DepositFile;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\DepositFileMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Model\StatusCodes;
use OCA\B2shareBridge\Publish\B2share;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IRequest;
use OCP\Notification\IManager;
use Psr\Log\LoggerInterface;

/**
 * Implement a ownCloud AppFramework Controller
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class PublishController extends Controller
{
    protected IConfig $config;
    protected DepositStatusMapper $mapper;
    protected DepositFileMapper $dfmapper;
    protected StatusCodes $statusCodes;
    protected string $userId;
    protected IManager $notManager;
    protected LoggerInterface $logger;
    private ITimeFactory $_time;
    private B2share $_publisher;
    private ServerMapper $_smapper;
    private CommunityMapper $_cmapper;
    private IJobList $_jobList;
    private IRootFolder $_rootFolder;

    /**
     * Creates the AppFramwork Controller
     *
     * @param string              $appName     name of the app
     * @param IRequest            $request     request object
     * @param IConfig             $config      config object
     * @param DepositStatusMapper $mapper      Deposit Status Mapper
     * @param DepositFileMapper   $dfmapper    ORM for DepositFile objects
     * @param StatusCodes         $statusCodes Status Code Mapper
     * @param ITimeFactory        $time        Time
     * @param B2share             $publisher   B2SHARE
     * @param ServerMapper        $smapper     Server Mapper
     * @param CommunityMapper     $cmapper     Community Mapper
     * @param IManager            $notManager  Manager
     * @param LoggerInterface     $logger      Logger
     * @param IJobList            $jobList     NC job interface
     * @param IRootFolder         $rootFolder  Nextcloud filesystem interface
     * @param string              $userId      userid
     */
    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        DepositStatusMapper $mapper,
        DepositFileMapper $dfmapper,
        StatusCodes $statusCodes,
        ITimeFactory $time,
        B2share $publisher,
        ServerMapper $smapper,
        CommunityMapper $cmapper,
        IManager $notManager,
        LoggerInterface $logger,
        IJobList $jobList,
        IRootFolder $rootFolder,
        string $userId
    ) {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->mapper = $mapper;
        $this->dfmapper = $dfmapper;
        $this->statusCodes = $statusCodes;
        $this->config = $config;
        $this->_time = $time;
        $this->_publisher = $publisher;
        $this->_smapper = $smapper;
        $this->_cmapper = $cmapper;
        $this->logger = $logger;
        $this->_jobList = $jobList;
        $this->_rootFolder = $rootFolder;
        $this->notManager = $notManager;
    }

    /**
     * XHR request endpoint for getting Publish command
     *
     * @return JSONResponse
     * 
     * @throws Exception
     * @throws MultipleObjectsReturnedException
     */
    #[NoAdminRequired]
    public function publish(): JSONResponse
    {
        $param = $this->request->getParams();

        // check params
        if (!array_key_exists('ids', $param)
            || !array_key_exists('community', $param)
            || !array_key_exists('server_id', $param)
            || !array_key_exists('title', $param)
            || !array_key_exists('open_access', $param)
        ) {
            return new JSONResponse(
                [
                    'message' => 'Missing parameters',
                    'status' => 'error'
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        // get params
        $serverId = $param['server_id'];
        $ids = $param['ids'];
        $title = $param['title'];
        $this->logger->debug("ids: " . print_r($ids, true), ['app' => Application::APP_ID]);

        $token = $this->config->getUserValue($this->userId, $this->appName, "token_" . $serverId);
        if (!is_string($token)) {
            return new JSONResponse(
                [
                    'message' => 'Could not find token for user',
                    'status' => 'error'
                ],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }

        try {
            $server = $this->_smapper->find($serverId);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(
                [
                    'message' => 'Invalid server id',
                    'status' => 'error'
                ],
                Http::STATUS_BAD_REQUEST
            );
        }
        $this->_publisher->setCheckSSL($server->getCheckSsl());

        // check existing uploads
        $active_uploads = count(
            $this->mapper->findAllForUserAndStateString(
                $this->userId,
                'pending'
            )
        );
        if ($active_uploads >= $server->getMaxUploads()) {
            return new JSONResponse(
                [
                    'message' => 'Until your ' . $server->getMaxUploads() . ' deposits 
                        are done, you are not allowed to create further deposits.',
                    'status' => 'error'
                ],
                Http::STATUS_TOO_MANY_REQUESTS
            );
        }

        $rootFolder = $this->_rootFolder->getUserFolder($this->userId);

        // check deposit size
        $filesize = 0;
        foreach ($ids as $id) {
            $fileArr = $rootFolder->getById($id);
            if (count($fileArr) > 1) {
                $this->logger->debug("Ambiguous upload may interfere with file sizes", ['app' => Application::APP_ID]);
            }
            foreach ($fileArr as $file) {
                if ($file instanceof File) {
                    $currentSize = $file->getSize();
                    $filesize += $currentSize;

                    if ($currentSize >= $server->getMaxUploadFilesize() * 1024 * 1024) {
                        return new JSONResponse(
                            [
                                'message' => 'We currently only support 
                                    files smaller then ' . $server->getMaxUploadFilesize() . ' MB',
                                'status' => 'error'
                            ],
                            Http::STATUS_REQUEST_ENTITY_TOO_LARGE
                        );
                    }
                }
            }
        }

        if ($filesize >= 8 * 1024 * 1024 * 1024) {
            return new JSONResponse(
                [
                    'message' => 'You can\'t upload more than 8 GB at once',
                    'status' => 'error'
                ],
                Http::STATUS_REQUEST_ENTITY_TOO_LARGE
            );
        }


        // create new file status
        $fcStatus = new DepositStatus();
        $fcStatus->setOwner($this->userId);
        $fcStatus->setStatus(1);
        $currentTime = time();
        $fcStatus->setCreatedAt($currentTime);
        $fcStatus->setUpdatedAt($currentTime);
        $fcStatus->setTitle($title);
        $fcStatus->setServerId($serverId);
        $depositStatus = $this->mapper->insert($fcStatus);
        foreach ($ids as $id) {
            $fileArr = $rootFolder->getById($id);
            if (count($fileArr) > 1) {
                $this->logger->debug("Ambiguous upload, maybe too many files will be inserted", ['app' => Application::APP_ID]);
            }
            foreach ($fileArr as $file) {
                if ($file instanceof File) {
                    $depositFile = new DepositFile();

                    $depositFile->setFilename($file->getName());
                    $depositFile->setFileid($id);
                    $depositFile->setDepositStatusId($depositStatus->getId());
                    $this->logger->debug(
                        "Inserting " . $depositFile->getFilename(),
                        ['app' => Application::APP_ID]
                    );
                    $this->dfmapper->insert($depositFile);
                } else {
                    $this->logger->debug("Invalid file type", ['app' => Application::APP_ID]);
                }
            }
        }

        // prepare transfer job
        $param["transferId"] = $depositStatus->getId();
        $param["_userId"] = $this->userId;
        $param["token"] = $token;

        $job = new TransferHandler(
            $this->_time,
            $this->mapper,
            $this->dfmapper,
            $this->_publisher,
            $this->_smapper,
            $this->_cmapper,
            $this->notManager,
            $this->logger,
            $this->_rootFolder
        );

        if ($filesize < 250 * 1024 * 1024) {
            // do small jobs directly, under 250 MB should go in under 5 seconds
            $job->run($param);
            return new JSONResponse(
                [
                    "message" => 'Successfully transferred file(s) to B2SHARE. ' .
                        'Review the status in B2SHARE app.',
                    'status' => 'success'
                ]
            );
        } else {
            // register transfer cron
            $this->_jobList->add(
                $job,
                $param,
            );
            return new JSONResponse(
                [
                    "message" => 'Transferring file(s) to B2SHARE in the Background. ' .
                        'Review the status in B2SHARE app.',
                    'status' => 'success'
                ]
            );
        }
    }
}

