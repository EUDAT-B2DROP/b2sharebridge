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
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IRequest;
use OCP\ILogger;
use OCP\Util;
use OCA\B2shareBridge\AppInfo\Application;

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

    /**
     * Creates the AppFramwork Controller
     *
     * @param string $appName name of the app
     * @param IRequest $request request object
     * @param IConfig $config config object
     * @param DepositStatusMapper $mapper whatever
     * @param DepositFileMapper $fdmapper ORM for DepositFile
     * @param CommunityMapper $cMapper a community mapper
     * @param ServerMapper $smapper server mapper
     * @param StatusCodes $statusCodes whatever
     * @param string $userId userid
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
        $userId,
    )
    {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->mapper = $mapper;
        $this->cMapper = $cMapper;
        $this->fdmapper = $fdmapper;
        $this->smapper = $smapper;
        $this->statusCodes = $statusCodes;
        $this->config = $config;
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
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
     * returns all deposits for a user with the filter query parameter.
     * possible filters:
     *     'all': get all deposits
     *     'pending': get pending deposits
     *     'publish': get published deposits
     *     'failed': get failed deposits
     *
     * @return JSONResponse
     *
     * @throws Exception
     * @throws DoesNotExistException
     * @throws MultipleObjectsReturnedException
     * @NoAdminRequired
     */
    public function depositList(): JSONResponse
    {
        $param = $this->request->getParams();
        if (!array_key_exists('filter', $param)) {
            return new JSONResponse(
                [
                    "message" => "missing argument: filter",
                    "status" => "error"
                ],
                Http::STATUS_INTERNAL_SERVER_ERROR
            );
        }
        $filter = $param['filter'];
        if ($filter === 'all') {
            $publications = $this->mapper->findAllForUser($this->userId);
        } else {
            $publications = $this->mapper->findAllForUserAndStateString(
                $this->userId,
                $filter);
        }
        foreach ($publications as &$publication) {
            $publication->setFileCount(
                $this->fdmapper->getFileCount($publication->getId())
            );
            $publication = $publication->toJson();
        }


        return new JSONResponse($publications);
    }

    /**
     * XHR request endpoint for token setter
     *
     * @return          JSONResponse
     * @NoAdminRequired
     */
    public function setToken()
    {
        $param = $this->request->getParams();
        $error = false;
        if (!is_array($param)
            || !array_key_exists('token', $param)
        ) {
            $error = 'Parameters gotten from UI are no array or they are missing';
        }
        $token = $param['token'];
        $server_id = $param['serverid'];

        if (!is_string($token)) {
            $error = 'Problems while parsing fileid or publishToken';
        }
        $userId = \OC::$server->getUserSession()->getUser()->getUID();
        if (strlen($userId) <= 0) {
            $error = 'No user configured for session';
        }
        if (($error)) {
            \OC::$server->getLogger()->error($error, ['app' => 'b2sharebridge']);
            return new JSONResponse(
                [
                    'message' => 'Internal server error, contact the EUDAT helpdesk',
                    'status' => 'error'
                ]
            );
        }


        \OC::$server->getLogger()->info(
            'saving API token', ['app' => 'b2sharebridge']
        );
        $this->config->setUserValue($userId, $this->appName, "token_" . $server_id, $token);
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
     * @return          JSONResponse
     * @NoAdminRequired
     */
    public function deleteToken($id)
    {
        \OC::$server->getLogger()->info(
            'Deleting API token', ['app' => 'b2sharebridge']
        );
        $userId = \OC::$server->getUserSession()->getUser()->getUID();
        if (strlen($userId) <= 0) {
            \OC::$server->getLogger()->info(
                'No user configured for session', ['app' => 'b2sharebridge']
            );
            return new JSONResponse(
                [
                    'message' => 'Internal server error, contact the EUDAT helpdesk',
                    'status' => 'error'
                ]
            );
        }
        $this->config->setUserValue($userId, $this->appName, 'token_' . $id, '');
    }

    /**
     * request endpoint for gettin users tokens
     *
     * @return          array
     * @NoAdminRequired
     */
    public function getTokens()
    {
        $userId = \OC::$server->getUserSession()->getUser()->getUID();
        $ret = [];
        $servers = $this->smapper->findAll();
        foreach ($servers as $server) {
            $serverId = $server->getId();
            $ret[$serverId] = $this->config->getUserValue($userId, $this->appName, 'token_' . $serverId);
        };
        return $ret;
    }

    /**
     * XHR request endpoint for getting communities list dropdown for tabview
     *
     * @return          array
     * @NoAdminRequired
     */
    public function getTabViewContent()
    {

        return $this->cMapper->getCommunityList();
    }

    /**
     * XHR request endpoint for token state: disables or enables publish button
     *
     * @return          JSONResponse
     * @NoAdminRequired
     */
    public function initializeB2ShareUI()
    {
        $is_error = false;
        $error_msg = "";
        \OC::$server->getLogger()->debug(
            'in func initUI', ['app' => 'b2sharebridge']
        );
        $userId = \OC::$server->getUserSession()->getUser()->getUID();
        if (strlen($userId) <= 0) {
            \OC::$server->getLogger()->info(
                'No user configured for session', ['app' => 'b2sharebridge']
            );
            $is_error = true;
            $error_msg .= "Authorization failure: login first.<br>\n";
        }
        $param = $this->request->getParams();
        $id = (int)$param['file_id'];
        Filesystem::init($this->userId, '/');
        $view = Filesystem::getView();
        \OC::$server->getLogger()->debug(
            'File ID: ' . $id, ['app' => 'b2sharebridge']
        );
        $filesize = $view->filesize(Filesystem::getPath($id));
        $fileName = basename(Filesystem::getPath($id));
        $is_dir = $view->is_dir(Filesystem::getPath($id));
        if ($is_dir) {
            $is_error = true;
            $error_msg .= "You can only publish a file to B2SHARE.<br>\n";
        }

        $allowed_uploads = $this->config->getAppValue(
            'b2sharebridge',
            'max_uploads',
            5
        );
        $allowed_filesize = $this->config->getAppValue(
            'b2sharebridge',
            'max_upload_filesize',
            512
        );
        $active_uploads = count(
            $this->mapper->findAllForUserAndStateString(
                $this->userId,
                'pending'
            )
        );
        if ($active_uploads > $allowed_uploads) {
            $is_error = true;
            $error_msg .= "You already have " . $active_uploads .
                " active uploads. You are only allowed " . $allowed_uploads .
                " uploads. Please try again later.<br>\n";
        }
        if ($filesize > $allowed_filesize * 1024 * 1024) {
            $is_error = true;
            $error_msg .= "We currently only support files smaller then "
                . $allowed_filesize . " MB.<br>\n";
        }
        $result = [
            "title" => $fileName,
            "error" => $is_error,
            "error_msg" => $error_msg
        ];
        return new JSONResponse($result);
    }
}
