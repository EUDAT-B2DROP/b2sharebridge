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
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IRequest;
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

    private LoggerInterface $_logger;

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
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     * */
    public function index(): TemplateResponse
    {
        Util::addStyle(Application::APP_ID, 'style');
        Util::addStyle('files', 'files');
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
     * @NoAdminRequired
     * 
     * @return JSONResponse
     */
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
     * XHR request endpoint for token setter
     *
     * @return          JSONResponse
     * @NoAdminRequired
     * @throws          PreConditionNotMetException
     */
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
        if (($error)) {
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
     * @NoAdminRequired
     * 
     * @return JSONResponse
     */
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
     * @NoAdminRequired
     * 
     * @throws Exception
     * 
     * @return JSONResponse
     */
    public function getTokens(): JSONResponse
    {
        $ret = [];
        //TODO catch errors and return HTTP 500
        $servers = $this->smapper->findAll();
        foreach ($servers as $server) {
            $serverId = $server->getId();
            $token = $this->config->getUserValue($this->userId, $this->appName, 'token_' . $serverId, null);
            if ($token) {
                $ret[$serverId] = $token;
            } else {
                $ret[$serverId] = "";
            }
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
}
