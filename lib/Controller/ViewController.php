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

use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\DepositFileMapper;
use OCA\B2shareBridge\Model\Server;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Model\StatusCodes;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\DB\Exception;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Notification\IManager;
use OCP\PreConditionNotMetException;
use OCP\Util;
use OCA\B2shareBridge\AppInfo\Application;
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
class ViewController extends Controller
{
    protected $userId;
    protected $statusCodes;
    protected $mapper;
    protected $fdmapper;
    protected $config;
    protected $cMapper;
    protected $smapper;
    protected IManager $notManager;

    private LoggerInterface $_logger;
    private IRootFolder $_storage;
    private IURLGenerator $_urlGenerator;

    /**
     * Creates the AppFramwork Controller
     *
     * @param string              $appName      Name of the app
     * @param IRequest            $request      Request
     * @param IConfig             $config       Config
     * @param DepositStatusMapper $mapper       Deposit Status Mapper
     * @param DepositFileMapper   $fdmapper     ORM for DepositFile
     * @param CommunityMapper     $cMapper      Community mapper
     * @param ServerMapper        $smapper      Server Mapper
     * @param StatusCodes         $statusCodes  Status Code Mapper
     * @param IRootFolder         $storage      Storage Interface for file creation
     * @param IManager            $manager      IManager for Notifications
     * @param IURLGenerator       $urlGenerator Url Generator
     * @param LoggerInterface     $logger       Logger
     * @param string              $userId       User ID
     */
    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        DepositStatusMapper $mapper,
        DepositFileMapper $fdmapper,
        CommunityMapper $cMapper,
        ServerMapper $smapper,
        StatusCodes $statusCodes,
        IRootFolder $storage,
        IManager $manager,
        IURLGenerator $urlGenerator,
        LoggerInterface $logger,
        string $userId,
    ) {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->mapper = $mapper;
        $this->cMapper = $cMapper;
        $this->fdmapper = $fdmapper;
        $this->smapper = $smapper;
        $this->statusCodes = $statusCodes;
        $this->config = $config;
        $this->_storage = $storage;
        $this->notManager = $manager;
        $this->_urlGenerator = $urlGenerator;
        $this->_logger = $logger;
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @return TemplateResponse
     */
    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function index(): TemplateResponse
    {
        Util::addStyle(Application::APP_ID, 'style');
        Util::addScript(Application::APP_ID, 'b2sharebridge-main');

        $params = [
            'user' => $this->userId,
            'statuscodes' => $this->statusCodes,
        ];

        return new TemplateResponse(
            Application::APP_ID,
            'main',
            $params
        );
    }

    /**
     * Returns all deposits for a user with the filter query parameter.
     * possible filters:
     *     'all': get all deposits
     *     'pending': get pending deposits
     *     'publish': get published deposits
     *     'failed': get failed deposits
     *
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * 
     * @return JSONResponse
     */
    #[NoAdminRequired]
    public function depositList(): JSONResponse
    {
        $param = $this->request->getParams();

        //check filter param
        if (!array_key_exists('filter', $param)) {
            return new JSONResponse(
                [
                    "message" => "missing argument: filter",
                    "status" => "error"
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $filter = $param['filter'];
        if ($filter === 'all') {
            $publications = $this->mapper->findAllForUser($this->userId);
        } else {
            $publications = $this->mapper->findAllForUserAndStateString(
                $this->userId,
                $filter
            );
        }

        $servers = $this->smapper->findAll();
        $data = [];
        foreach ($publications as $publication) {
            $publication->setFileCount(
                $this->fdmapper->getFileCount($publication->getId())
            );
            $raw_data = json_decode(json_encode($publication), true);
            foreach ($servers as $server) {
                if ($server->getId() == $publication->getServerId()) {
                    $raw_data["server_name"] = $server->getName();
                    $raw_data["server_version"] = $server->getVersion();
                }
            }
            $data[] = $raw_data;
        }
        return new JSONResponse($data);
    }

    /**
     * Returns all Records of a user, either draft or not.
     * $size is limited to 50
     * 
     * @param bool $draft only draft or published records
     * @param int  $page  page number
     * @param int  $size  size per page
     * 
     * @return JSONResponse
     */
    #[NoAdminRequired]
    public function publicationList($draft, $page, $size): JSONResponse
    {
        if (!$this->userId) {
            return new JSONResponse(["message" => "missing user id"], Http::STATUS_BAD_REQUEST);
        }
        $size = $size > 50 ? 50 : $size;
        $serverResponses = [];
        $servers = $this->smapper->findAll();
        foreach ($servers as $server) {
            $records = $this->_getUserRecords($server, $draft, $page, $size);
            $serverId = $server->getId();
            if ($records) {
                $serverResponses[$serverId] = $records;
                $serverResponses[$serverId]["server_url"] = $server->getPublishUrl();
                $serverResponses[$serverId]["server_version"] = $server->getVersion();
                $serverResponses[$serverId]["server_name"] = $server->getName();
            }
        }
        return new JSONResponse($serverResponses);
    }

    /**
     * XHR request endpoint for token setter
     *
     * @return JSONResponse
     * @throws PreConditionNotMetException
     */
    #[NoAdminRequired]
    public function setToken(): JSONResponse
    {
        $param = $this->request->getParams();
        $error = false;
        if (!array_key_exists('token', $param) || !array_key_exists('serverid', $param)) {
            $error = 'Parameters gotten from UI are no array or they are missing';
        }
        $token = $param['token'];
        $server_id = $param['serverid'];

        if (!is_string($token)) {
            $error = 'Problems while parsing fileid or publishToken';
        }
        if (strlen($this->userId) <= 0) {
            $error = 'No user configured for session';
        }
        if ($error) {
            $this->_logger->error($error, ['app' => Application::APP_ID]);
            return new JSONResponse(
                [
                    'message' => 'Internal server error, contact the EUDAT helpdesk',
                    'status' => 'error'
                ],
                Http::STATUS_BAD_REQUEST
            );
        }


        $this->_logger->info(
            'saving API token',
            ['app' => Application::APP_ID]
        );
        $this->config->setUserValue($this->userId, $this->appName, "token_" . $server_id, $token);
        return new JSONResponse(
            [
                "data" => ["message" => "Saved"],
                "status" => "success"
            ]
        );
    }

    /**
     * XHR request endpoint for token setter
     *
     * @param $id Token ID
     * 
     * @throws PreConditionNotMetException
     * 
     * @return JSONResponse
     */
    #[NoAdminRequired]
    public function deleteToken($id): JSONResponse
    {
        $this->_logger->info(
            'Deleting API token',
            ['app' => Application::APP_ID]
        );
        if (strlen($this->userId) <= 0) {
            $this->_logger->info(
                'No user configured for session',
                ['app' => Application::APP_ID]
            );
            return new JSONResponse(
                [
                    'message' => 'Internal server error, contact the EUDAT helpdesk',
                    'status' => 'error'
                ],
                Http::STATUS_BAD_REQUEST
            );
        }
        $this->config->setUserValue($this->userId, $this->appName, 'token_' . $id, '');
        return new JSONResponse(
            [
                'message' => 'Ok',
                'status' => 'success'
            ]
        );
    }

    /**
     * Request endpoint for gettin users tokens
     * 
     * @throws Exception
     * 
     * @return JSONResponse
     */
    #[NoAdminRequired]
    public function getTokens(): JSONResponse
    {
        $ret = [];
        //TODO catch errors and return HTTP 500
        $servers = $this->smapper->findAll();
        foreach ($servers as $server) {
            $publisher = $server->getPublisher();
            $token = $publisher->getAccessToken($server, $this->userId);
            $serverId = $server->getId();
            $ret[$serverId] = $token ?? "";
        }
        return new JSONResponse($ret);
    }

    /**
     * XHR request endpoint for getting communities list dropdown for tabview
     *
     * @return JSONResponse
     * 
     * @throws Exception
     */
    #[NoAdminRequired]
    public function getTabViewContent(): JSONResponse
    {
        return new JSONResponse($this->cMapper->getCommunityList());
    }

    /**
     * Delete a draft with ID from server
     *
     * @param mixed $serverId ID of the server
     * @param mixed $recordId ID of the draft
     * 
     * @return JSONResponse
     */
    #[NoAdminRequired]
    public function deleteRecord($serverId, $recordId)
    {
        $server = $this->smapper->find($serverId);
        $publisher = $server->getPublisher();
        $token = $publisher->getAccessToken($server, $this->userId);
        if (!$token) {
            return new JSONResponse([], Http::STATUS_BAD_REQUEST);
        }

        $output = $publisher->deleteDraft($server, $recordId, $token);
        return new JSONResponse(json_decode($output, true));
    }

    /**
     * Download record files and put them into user storage. Requires to POST a record
     * 
     * @param number $serverId ID of the server
     * 
     * @return JSONResponse
     */
    #[UserRateLimit(limit: 5, period: 120)]
    #[NoAdminRequired]
    public function downloadRecordFiles($serverId)
    {
        // check parameter
        $param = $this->request->getParams();
        if (!array_key_exists("record", $param)) {
            $this->_notifiyUser("error_download_record", ["code" => "1"]);
            return new JSONResponse(
                [
                    "message" => "Missing record",
                    "status" => "error",
                    "code" => "1",
                ],
                Http::STATUS_BAD_REQUEST
            );
        }
        $record = $param["record"];

        $neededKeys = ["links", "id", "metadata"];

        // Validation
        $error = false;
        $errorKey = '';
        foreach ($neededKeys as $key) {
            if (!array_key_exists($key, $record)) {
                $errorKey = $key;
                $error = true;
                break;
            }
        }
        if (!$error) {
            if (!array_key_exists("files", $record["links"])) {
                if (!array_key_exists("self", $record["links"])) {
                    $errorKey = "[links][files] or [links][self]";
                    $error = true;
                }
            } elseif (!array_key_exists("titles", $record["metadata"])) {
                $errorKey = "[metadata][titles]";
                $error = true;
            } elseif (count($record["metadata"]["titles"]) == 0) {
                $errorKey = "[metadata][titles][0]";
                $error = true;
            } elseif (!array_key_exists("title", $record["metadata"]["titles"][0])) {
                $errorKey = "[metadata][titles][0][title]";
                $error = true;
            }
        }

        $server = $this->smapper->find($serverId);
        $publisher = $server->getPublisher();
        $accessToken = $publisher->getAccessToken($server, $this->userId);

        // do a recovery if necessary
        if (!array_key_exists("files", $record["links"])) {
            $selfPath = $record["links"]["self"] . "?access_token=$accessToken";
            $content = $publisher->request($server, $selfPath);
            
            if ($content) {
                $selfRecord = json_decode($content, true);
                $record["links"]["files"] = $selfRecord["links"]["files"];
            } else {
                $errorKey = "self[links][files]";
                $error = true;
            }
        }

        if ($error) {
            $this->_notifiyUser("error_download_record", ["code" => "2"]);
            return new JSONResponse(
                [
                    "message" => "Missing key in record: $errorKey",
                    "status" => "error",
                    "code" => "2",
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $userFolder = $this->_storage->getUserFolder($this->userId);
        $title = $record["metadata"]["titles"][0]["title"];
        $filesUrl = $record["links"]["files"] . "?access_token=$accessToken";
        $outputRaw = $publisher->request($server, $filesUrl);

        // check file sizes and user space
        if (!$outputRaw) {
            $this->_notifiyUser("error_download_malicious", ["code" => "3"]);
            return new JSONResponse(
                [
                    "message" => "Did you really think I wouldn't check?",
                    "status" => "error",
                    "code" => "3",
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $output = json_decode($outputRaw, true);

        if (!array_key_exists("contents", $output)) {
            $this->_notifiyUser("error_download_downstream", ["code" => "4", "url" => $server->getPublishUrl()]);
            return new JSONResponse(
                [
                    "message" => "Bad response from " . $server->getPublishUrl(),
                    "status" => "error",
                    "code" => "4",
                ],
                Http::STATUS_BAD_GATEWAY
            );
        }

        $files = $output["contents"];
        $requiredSize = 4; // start directory
        foreach ($files as $file) {
            // validate file
            if (!array_key_exists("links", $file)
                || !array_key_exists("size", $file)
                || !array_key_exists("key", $file)
                || !array_key_exists("self", $file["links"])
            ) {
                $this->_logger->debug($file, ["b2sharebridge"]);
                $this->_notifiyUser("error_download_downstream", ["code" => "5", "url" => $server->getPublishUrl()]);
                return new JSONResponse(
                    [
                        "message" => "Bad response from " . $server->getPublishUrl(),
                        "status" => "error",
                        "code" => "5",
                    ],
                    Http::STATUS_BAD_GATEWAY
                );
            }
            $requiredSize += $file["size"];
        }

        if ($userFolder->getFreeSpace() < $requiredSize) {
            $this->_notifiyUser("error_download_space", ["code" => "6", "title" => $title, "sizeFiles" => $requiredSize, "freeSpace" => $userFolder->getFreeSpace()]);
            return new JSONResponse(
                [
                    "message" => "You don't have enough storage space",
                    "status" => "error",
                    "code" => "6",
                ],
                Http::STATUS_BAD_REQUEST,
            );
        }

        // create data
        try {
            if ($userFolder->get($title)) {
                $this->_notifiyUser("error_download_exists", ["code" => "7", "url" => $server->getPublishUrl(), "title" => $title]);
                return new JSONResponse(
                    [
                        "message" => "Directory '$title' exists already! Please rename, move or delete it manually first",
                        "status" => "error",
                        "code" => "7",
                    ],
                    Http::STATUS_BAD_REQUEST,
                );
            }
        } catch (\OCP\Files\NotFoundException $e) {
            // TODO rewrite this as soon as you find out how to do this exceptionless
        }

        try {
            $folder = $userFolder->newFolder($title);
            foreach ($files as $file) {
                $urlFilePath = $file["links"]["self"] . "?access_token=$accessToken";
                $content = $publisher->request($server, $urlFilePath);
                $folder->newFile($file["key"], $content);
            }
        } catch (\OCP\Files\NotPermittedException $e) {
            $this->_notifiyUser("error_download_permissions", ["code" => "8", "title" => $title]);
            return new JSONResponse(
                [
                    "message" => "File creation failed",
                    "status" => "error",
                    "code" => "8",
                ],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }

        //e.g. http://127.0.0.1/index.php/apps/files/?dir=/multiple_files_test
        $urlParts = [
            $this->_urlGenerator->getBaseUrl(),
            "index.php/apps/files/?dir=",
            $folder->getName()
        ];
        $url = implode("/", $urlParts);
        $this->_notifiyUser(
            "success_download",
            [
                "title" => $title,
                "fileId" => (string) $folder->getId(),
                "fileName" => $folder->getName(),
                "filePath" => $url,
                "fileUrl" => $url,
            ]
        );
        return new JSONResponse(
            [
                "message" => "success",
                "status" => "success",
                "url" => $folder->getName(),
            ],
            Http::STATUS_OK
        );
    }

    /**
     * Gets user records for a single server
     * 
     * @param Server $server Server object
     * @param bool   $draft  true for (only) draft records, else false
     * @param int    $page   page number, you are limited to 50 records by B2SHARE Api
     * @param int    $size   page size, number of records per page
     * 
     * @return array
     */
    private function _getUserRecords($server, $draft, $page, $size): array
    {
        $publisher = $server->getPublisher();
        $token = $publisher->getAccessToken($server, $this->userId);
        if (!$token) {
            return [];
        }

        $params = [
            'page' => $page,
            'size' => $size,
            'sort' => 'mostrecent'
        ];
        if ($draft) {
            // https://doc.eudat.eu/b2share/httpapi/#search-drafts
            $params = ["drafts" => 1, "access_token" => $token] + $params;
        } else {
            $userId = $publisher->getB2shareUserId($server, $token);
            $params["q"] = "owners:$userId";
        }
        $httpParams = http_build_query($params);
        $serverUrl = $server->getPublishUrl();
        $urlPath = "$serverUrl/api/records/?$httpParams";

        $output = $publisher->request($server, $urlPath);

        if (!$output) {
            return [];
        }
        $outputRecords = json_decode($output, true);
        if (array_key_exists("hits", $outputRecords)) {
            $records = $outputRecords["hits"];

            if (array_key_exists("hits", $records)) {
                return $records;
            }
        }
        return [];
    }

    /**
     * Create a notification
     *
     * @param string $subject    notification type string
     * @param array  $parameters parameters for later template filling
     * 
     * @return void
     */
    private function _notifiyUser($subject, $parameters = [])
    {
        $notification = $this->notManager->createNotification();
        $notification->setApp(Application::APP_ID)
            ->setDateTime(new \DateTime())
            ->setObject('b2sharebridge', $subject)
            ->setUser($this->userId)
            ->setSubject($subject, $parameters);
        $this->notManager->notify($notification);
    }
}
