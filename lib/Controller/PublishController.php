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
use OCA\B2shareBridge\Exceptions\ControllerValidationException;
use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\DepositStatus;
use OCA\B2shareBridge\Model\DepositFile;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\DepositFileMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Model\StatusCodes;
use OCA\B2shareBridge\Util\Helper;
use OCA\B2shareBridge\Publish\B2ShareFactory;
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
    private ServerMapper $_smapper;
    private CommunityMapper $_cmapper;
    private IJobList $_jobList;
    private IRootFolder $_rootFolder;
    private B2ShareFactory $_b2shareFactory;

    /**
     * Creates the AppFramwork Controller
     *
     * @param string              $appName        name of the app
     * @param IRequest            $request        request object
     * @param IConfig             $config         config object
     * @param DepositStatusMapper $mapper         Deposit Status Mapper
     * @param DepositFileMapper   $dfmapper       ORM for DepositFile objects
     * @param StatusCodes         $statusCodes    Status Code Mapper
     * @param ITimeFactory        $time           Time
     * @param ServerMapper        $smapper        Server Mapper
     * @param CommunityMapper     $cmapper        Community Mapper
     * @param IManager            $notManager     Manager
     * @param LoggerInterface     $logger         Logger
     * @param IJobList            $jobList        NC job interface
     * @param IRootFolder         $rootFolder     Nextcloud filesystem interface
     * @param B2ShareFactory      $b2shareFactory B2share API factory
     * @param string              $userId         userid
     */
    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        DepositStatusMapper $mapper,
        DepositFileMapper $dfmapper,
        StatusCodes $statusCodes,
        ITimeFactory $time,
        ServerMapper $smapper,
        CommunityMapper $cmapper,
        IManager $notManager,
        LoggerInterface $logger,
        IJobList $jobList,
        IRootFolder $rootFolder,
        B2ShareFactory $b2shareFactory,
        string $userId
    ) {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->mapper = $mapper;
        $this->dfmapper = $dfmapper;
        $this->statusCodes = $statusCodes;
        $this->config = $config;
        $this->_time = $time;
        $this->_smapper = $smapper;
        $this->_cmapper = $cmapper;
        $this->logger = $logger;
        $this->_jobList = $jobList;
        $this->_rootFolder = $rootFolder;
        $this->notManager = $notManager;
        $this->_b2shareFactory = $b2shareFactory;
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
        if (!Helper::arrayKeysExist(['community', 'open_access', 'title'], $param)) {
            return new JSONResponse(
                [
                    'message' => 'Missing parameters for publishing',
                    'status' => 'error'
                ],
                Http::STATUS_BAD_REQUEST
            );
        }
        $param['mode'] = 'create';
        return $this->_scheduleFileUploads($param);
    }


    /**
     * Endpoint for attaching files to an existing deposit
     *
     * @return JSONResponse
     * 
     * @throws Exception
     * @throws MultipleObjectsReturnedException
     */
    #[NoAdminRequired]
    public function attach(): JSONResponse
    {
        $param = $this->request->getParams();

        // check params
        if (!Helper::arrayKeysExist(['draftId'], $param)) {
            return new JSONResponse(
                [
                    'message' => 'Missing parameters for publishing',
                    'status' => 'error'
                ],
                Http::STATUS_BAD_REQUEST
            );
        }
        $param['mode'] = 'attach';
        return $this->_scheduleFileUploads($param);
    }

    /**
     * Endpoint for attaching files to an existing deposit
     * 
     * @return JSONResponse
     * 
     * @throws Exception
     * @throws MultipleObjectsReturnedException
     */
    #[NoAdminRequired]
    public function nextVersion(): JSONResponse
    {
        $param = $this->request->getParams();
        try {
            if (!Helper::arrayKeysExist(['server_id', 'recordId'], $param)) {
                throw new ControllerValidationException('Missing parameters for file uploads', Http::STATUS_BAD_REQUEST);
            }
            $serverId = $param['server_id'];
            try {
                $server = $this->_smapper->find($serverId);
            } catch (DoesNotExistException $e) {
                throw new ControllerValidationException('Invalid server id', Http::STATUS_BAD_REQUEST, $e);
            }

            $token = $this->config->getUserValue($this->userId, $this->appName, "token_$serverId");
            $serverUrl = $server->getPublishUrl();
            $recordId = $param['recordId'];
            $publisher = $this->_b2shareFactory->get($server->getVersion());
            $content = $publisher->nextVersion($server, $recordId, $token);
            if (!$content) {
                throw new ControllerValidationException("error on upstream server $serverUrl", Http::STATUS_BAD_GATEWAY);
            }
        } catch (ControllerValidationException $e) {
            return new JSONResponse(
                [
                    'message' => $e->getMessage(),
                    'status' => 'error'
                ],
                $e->getStatusCode()
            );
        }

        $draft = json_decode($content, true);
        if (array_key_exists('status', $draft)) {
            if ($draft['status'] == 405) {
                return new JSONResponse(
                    [
                    'message' => 'Method not allowed from upstream'
                    ], Http::STATUS_METHOD_NOT_ALLOWED
                );
            }
        }
        return new JSONResponse($draft);
    }

    /**
     * Summary of _scheduleFileUploads
     *
     * @param array $param Array of parameters, which needs to contain 'ids', 'server_id', 'mode' and more depending on the mode.
     * 
     * @return JSONResponse Status
     */
    private function _scheduleFileUploads($param): JSONResponse
    {
        try {
            // check params
            if (!Helper::arrayKeysExist(['ids', 'server_id', 'mode'], $param)) {
                throw new ControllerValidationException('Missing parameters for file uploads', Http::STATUS_BAD_REQUEST);
            }

            // get params
            $serverId = $param['server_id'];
            $ids = $param['ids'];
            $title = $param['title'] ?? null;

            $token = $this->config->getUserValue($this->userId, $this->appName, "token_$serverId");

            // validate token
            if (!is_string($token)) {
                throw new ControllerValidationException('Could not find token for user', Http::STATUS_INTERNAL_SERVER_ERROR);
            }

            try {
                $server = $this->_smapper->find($serverId);
            } catch (DoesNotExistException $e) {
                throw new ControllerValidationException('Invalid server id', Http::STATUS_BAD_REQUEST, $e);
            }

            // rate limit
            $this->_checkExistingUploads($server);

            // check file size
            $filesize = $this->_checkDepositSize($ids, $server);

            // create database entries
            $depositStatusId = $this->_createFileStatus($serverId, $ids, $title);

            // upload
            $direct = $filesize < 250 * 1024 * 1024;
            return $this->_prepareTransferJob($param, $token, $depositStatusId, $direct);
        } catch (ControllerValidationException $e) {
            return new JSONResponse(
                [
                    'message' => $e->getMessage(),
                    'status' => 'error'
                ],
                $e->getStatusCode()
            );
        }
    }

    /**
     * Checks if a user has too many pending uploads on a server
     * 
     * @param mixed $server Server to check
     * 
     * @throws \OCA\B2shareBridge\Exceptions\ControllerValidationException
     * 
     * @return void
     */
    private function _checkExistingUploads($server)
    {
        // check existing uploads
        $active_uploads = count(
            $this->mapper->findAllForUserAndStateString(
                $this->userId,
                'pending'
            )
        );
        if ($active_uploads >= $server->getMaxUploads()) {
            $message = 'Until your ' . $server->getMaxUploads() . ' deposits 
                                    are done, you are not allowed to create further deposits.';
            throw new ControllerValidationException($message, Http::STATUS_TOO_MANY_REQUESTS);
        }
    }

    /**
     * Checks if a deposit contains files, that are too big, or the deposit itself is too big
     * 
     * @param mixed $ids    File ids to check
     * @param mixed $server Server to check allowed file sizes to
     * 
     * @throws \OCA\B2shareBridge\Exceptions\ControllerValidationException
     * 
     * @return int Total deposit size in bytes
     */
    private function _checkDepositSize($ids, $server): int
    {
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
                        $message = 'We currently only support 
                                    files smaller then ' . $server->getMaxUploadFilesize() . ' MB';
                        throw new ControllerValidationException($message, Http::STATUS_REQUEST_ENTITY_TOO_LARGE);
                    }
                }
            }
        }

        if ($filesize >= 8 * 1024 * 1024 * 1024) {
            throw new ControllerValidationException('You can\'t upload more than 8 GB at once', Http::STATUS_REQUEST_ENTITY_TOO_LARGE);
        }
        return $filesize;
    }

    /**
     * Prepares database entries for a future file upload
     *
     * @param int         $serverId ID of the server
     * @param array       $ids      array of file ids
     * @param string|null $title    deposit title
     * 
     * @return int DepositStatus ID
     */
    private function _createFileStatus(int $serverId, array $ids, string|null $title): int
    {
        $rootFolder = $this->_rootFolder->getUserFolder($this->userId);
        // create new file status
        $fcStatus = new DepositStatus();
        $fcStatus->setOwner($this->userId);
        $fcStatus->setStatus(1);
        $currentTime = time();
        $fcStatus->setCreatedAt($currentTime);
        $fcStatus->setUpdatedAt($currentTime);
        if ($title) {
            $fcStatus->setTitle($title);
        } else {
            $fcStatus->setTitle("AttachToDraft-$currentTime");
        }
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
                    $this->dfmapper->insert($depositFile);
                } else {
                    $this->logger->debug("Invalid file type", ['app' => Application::APP_ID]);
                }
            }
        }
        return $depositStatus->getId();
    }

    /**
     * Create and schedule or run a transfer job, pushing the files to b2share
     * 
     * @param array  $param           Parameters needed for the file transfer
     * @param string $token           Users b2share token
     * @param int    $depositStatusId Id of the deposit status object in the database
     * @param bool   $direct          Run the job directly or schedule it for later
     * 
     * @return JSONResponse Response for the api endpoints
     */
    private function _prepareTransferJob(array $param, string $token, int $depositStatusId, bool $direct): JSONResponse
    {
        // prepare transfer job
        $param["transferId"] = $depositStatusId;
        $param["_userId"] = $this->userId;
        $param["token"] = $token;

        $job = new TransferHandler(
            $this->_time,
            $this->mapper,
            $this->dfmapper,
            $this->_smapper,
            $this->_cmapper,
            $this->notManager,
            $this->logger,
            $this->_rootFolder
        );

        if ($direct) {
            // do small jobs directly, under 250 MB should go in under 5 seconds
            $job->run($param);
            $message = 'Successfully transferred file(s) to B2SHARE. Review the status in the B2SHARE app.';
        } else {
            // register transfer cron
            $this->_jobList->add(
                $job,
                $param,
            );
            $message = 'Transferring file(s) to B2SHARE in the Background. Review the status in the B2SHARE app.';
        }
        return new JSONResponse(
            [
                'message' => $message,
                'status' => 'success'
            ]
        );
    }
}
