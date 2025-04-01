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

use OC\Files\Filesystem;
use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\DepositFileMapper;
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
use OCP\Authentication\Exceptions\InvalidTokenException;
use OCP\DB\Exception;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IRequest;
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

    /**
     * Creates the AppFramwork Controller
     *
     * @param string              $appName     Name of the app
     * @param IRequest            $request     Request
     * @param IConfig             $config      Config
     * @param DepositStatusMapper $mapper      Deposit Status Mapper
     * @param DepositFileMapper   $fdmapper    ORM for DepositFile
     * @param CommunityMapper     $cMapper     Community mapper
     * @param ServerMapper        $smapper     Server Mapper
     * @param StatusCodes         $statusCodes Status Code Mapper
     * @param IManager            $manager     IManager for Notifications
     * @param LoggerInterface     $logger      Logger
     * @param string              $userId      User ID
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
        //Util::addStyle('files', 'files');
        $params = [
            'user' => $this->userId,
            'statuscodes' => $this->statusCodes,
        ];

        Util::addScript(Application::APP_ID, 'b2sharebridge-main');

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
        foreach ($publications as $publication) {
            $publication->setFileCount(
                $this->fdmapper->getFileCount($publication->getId())
            );
        }
        return new JSONResponse($publications);
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
            return new JSONResponse(["message" => "missing user id"]);
        }
        $size = $size > 50 ? 50 : $size;
        $serverResponses = [];
        $servers = $this->smapper->findAll();
        foreach ($servers as $server) {
            $serverUrl = $server->getPublishUrl();
            $records = $this->_getUserRecords($server->getId(), $serverUrl, $draft, $page, $size);
            if ($records) {
                $serverResponses[$serverUrl] = $records;
                $serverResponses[$serverUrl]["server_id"] = $server->getId();
            }
        }
        return new JSONResponse($serverResponses);
    }

    /**
     * XHR request endpoint for token setter
     *
     * @return          JSONResponse
     * @throws          PreConditionNotMetException
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
            $serverId = $server->getId();
            $token = $this->_getB2shareAccessToken($serverId);
            $ret[$serverId] = $token ?? "";
        }
        return new JSONResponse($ret);
    }

    /**
     * XHR request endpoint for getting communities list dropdown for tabview
     *
     * @return          array
     * @NoAdminRequired
     * @throws          Exception
     */
    public function getTabViewContent(): array
    {
        return $this->cMapper->getCommunityList();
    }

    /**
     * Delete a draft with ID from server
     *
     * @param mixed $serverId ID of the server
     * @param mixed $recordId ID of the draft
     * 
     * @return array
     */
    #[NoAdminRequired]
    public function deleteRecord($serverId, $recordId)
    {
        $token = $this->_getB2shareAccessToken($serverId);
        if (!$token) {
            return [];
        }
        $server = $this->smapper->find($serverId);
        $serverUrl = $server->getPublishUrl();
        $urlPath = "$serverUrl/api/records/$recordId/draft?access_token=$token";
        $this->_logger->debug($urlPath, ["b2sharebridge"]);
        $output = $this->_curlRequest($urlPath, "DELETE");
        return json_decode($output, true);
    }

    /**
     * download record files and put them into user storage
     * 
     * @param number $serverId ID of the server
     * @param array  $record   a full json like object
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
        $error = False;
        $errorKey = '';
        foreach ($neededKeys as $key) {
            if (!array_key_exists($key, $record)) {
                $errorKey = $key;
                $error = True;
                break;
            }
        }
        if (!$error) {
            if (!array_key_exists("files", $record["links"])) {
                $errorKey = "[links][files]";
                $error = True;
            } elseif (!array_key_exists("titles", $record["metadata"])) {
                $errorKey = "[metadata][titles]";
                $error = True;
            } elseif (count($record["metadata"]["titles"]) == 0) {
                $errorKey = "[metadata][titles][0]";
                $error = True;
            } elseif (!array_key_exists("title", $record["metadata"]["titles"][0])) {
                $errorKey = "[metadata][titles][0][title]";
                $error = True;
            }
        }
        if ($error) {
            return new JSONResponse(
                [
                    "message" => "Missing key in record: $errorKey",
                    "status" => "error",
                    "code" => "2",
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        // get data
        $title = $record["metadata"]["titles"][0]["title"];
        $recordId = $record["id"];
        $server = $this->smapper->find($serverId);
        $userFolder = $this->_storage->getUserFolder($this->userId);
        $accessToken = $this->_getB2shareAccessToken($server->getId());
        $filesUrl = $record["links"]["files"] . "?access_token=$accessToken";

        // check file sizes and user space
        if (!str_starts_with($filesUrl, $server->getPublishUrl())) {
            return new JSONResponse(
                [
                    "message" => "Did you really think I wouldn't check?",
                    "status" => "error",
                    "code" => "3",
                ],
                Http::STATUS_BAD_REQUEST
            );
        }

        $outputRaw = $this->_curlRequest($filesUrl);
        $output = json_decode($outputRaw, true);

        if (!array_key_exists("contents", $output)) {
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

        //$this->_logger->debug("output: $output", ["b2sharebridge"]);
        //$this->_logger->debug("files url:" . $filesUrl, ["b2sharebridge"]);
        $requiredSize = 4; // start directory
        foreach ($files as $file) {
            // validate file
            if (
                !array_key_exists("links", $file) ||
                !array_key_exists("size", $file) ||
                !array_key_exists("key", $file) ||
                !array_key_exists("self", $file["links"])
            ) {
                $this->_logger->debug($file, ["b2sharebridge"]);
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
                $content = $this->_curlRequest($urlFilePath);
                $folder->newFile($file["key"], $content);
            }
        } catch (\OCP\Files\NotPermittedException $e) {
            return new JSONResponse(
                [
                    "message" => "File creation failed",
                    "status" => "error",
                    "code" => "8",
                ],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }

        //$message = "";
        //$this->_notifiyUser($message, "Download: $title is ready");

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
     * @param int    $serverId  ID of the configured server, e.g. 0, 1, ...
     * @param string $serverUrl server URL, e.g. b2share.eudat.eu
     * @param bool   $draft     true for (only) draft records, else false
     * @param int    $page      page number, you are limited to 50 records by B2SHARE Api
     * @param int    $size      page size, number of records per page
     * 
     * @return array
     */
    private function _getUserRecords($serverId, $serverUrl, $draft, $page, $size): array
    {
        $token = $this->_getB2shareAccessToken($serverId);
        if (!$token) {
            return [];
        }
        // https://doc.eudat.eu/b2share/httpapi/#search-drafts
        if ($draft) {
            $urlPath = "$serverUrl/api/records/?drafts&access_token=$token"
                . "&page=" . $page
                . "&size=" . $size
                . "&sort=mostrecent";
        } else {
            $ownerID = $this->_getB2shareUserId($serverUrl, $token);
            if ($ownerID == null) {
                return [];
            }
            $urlPath = "$serverUrl/api/records/?sort=mostrecent"
                . "&page=" . $page
                . "&size=" . $size
                . "&q=owners:$ownerID";
        }

        $this->_logger->debug("B2SHARE records URL: $urlPath", ['app' => Application::APP_ID]);

        $output = $this->_curlRequest($urlPath);

        if (!$output) {
            return [];
        }
        $records = json_decode($output, true);
        if (array_key_exists("hits", $records)) {
            return $records["hits"];
        }
        return [];
    }

    /**
     * Get B2SHARE token of a user by $serverId
     * 
     * @param string $serverId Server ID
     * 
     * @return ?string
     */
    private function _getB2shareAccessToken($serverId): ?string
    {
        return $this->config->getUserValue($this->userId, $this->appName, 'token_' . $serverId, null);
    }

    /**
     * Gets the B2SHARE User ID with the b2share API token
     * 
     * @param mixed $serverUrl baseUrl of the server
     * @param mixed $token     B2SHARE API token
     * 
     * @return ?string
     */
    private function _getB2shareUserId($serverUrl, $token): ?string
    {
        $response = $this->_curlRequest("$serverUrl/api/user/?access_token=$token");
        if (!$response) {
            return null;
        }
        $b2accessIdResponse = json_decode($response, true);
        if (array_key_exists("id", $b2accessIdResponse)) {
            return $b2accessIdResponse["id"];
        }
        return null;
    }

    /**
     * Send a curl get request to $urlPath
     *
     * @param string $urlPath URL
     * @param string $type    REST type, e.g. GET, DELETE, PUT, ...
     * 
     * @return bool|string
     */
    private function _curlRequest($urlPath, $type = 'GET'): bool|string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $urlPath);
        if ($type != 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }

    private function _notifiyUser($message, $title) {
        $notification = $this->notManager->createNotification();
        $notification->setApp(Application::APP_ID);
        $notification->setUser($this->userId);
        $notification->setSubject($title);
        $this->notManager->notify($notification);
    }
}
