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
use OCA\B2shareBridge\Cron\TransferHandler;
use OCA\B2shareBridge\Model\DepositStatus;
use OCA\B2shareBridge\Model\DepositFile;
use OCA\B2shareBridge\Model\DepositStatusMapper;
use OCA\B2shareBridge\Model\DepositFileMapper;
use OCA\B2shareBridge\Model\StatusCodes;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use \OCP\ILogger;
use OCP\Util;

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
    protected $config;
    protected $mapper;
    protected $dfmapper;
    protected $statusCodes;
    protected $userId;

    /**
     * Creates the AppFramwork Controller
     *
     * @param string              $appName     name of the app
     * @param IRequest            $request     request object
     * @param IConfig             $config      config object
     * @param DepositStatusMapper $mapper      whatever
     * @param DepositFileMapper   $dfmapper    ORM for DepositFile objects
     * @param StatusCodes         $statusCodes whatever
     * @param string              $userId      userid
     */
    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        DepositStatusMapper $mapper,
        DepositFileMapper $dfmapper,
        StatusCodes $statusCodes,
        string $userId
    ) {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->mapper = $mapper;
        $this->dfmapper = $dfmapper;
        $this->statusCodes = $statusCodes;
        $this->config = $config;
    }

    /**
     * XHR request endpoint for getting Publish command
     *
     * @return          JSONResponse
     * @NoAdminRequired
     */
    public function publish()
    {
        $param = $this->request->getParams();
        //TODO what if token wasn't set? We couldn't have gotten here
        //but still a check seems in place.
        $serverId = $param['server_id'];
        $_userId = \OC::$server->getUserSession()->getUser()->getUID();
        $token = $this->config->getUserValue($_userId, $this->appName, "token_" . $serverId);

        $error = false;
        if (strlen($_userId) <= 0) {
            $error = 'No user configured for session';
        }
        if (!is_array($param)
            || !array_key_exists('ids', $param)
            || !array_key_exists('community', $param)
        ) {
            $error = 'Parameters gotten from UI are no array or they are missing';
        }
        $ids = $param['ids'];
        $community = $param['community'];
        $open_access = $param['open_access'];
        $title = $param['title'];
        if (!is_string($token)) {
            $error = 'Problems while parsing publishToken';
        }

        if (($error)) {
            \OC::$server->getLogger()->error($error, ['app' => 'b2sharebridge']);
            return new JSONResponse(
                [
                    'message'=>'Internal server error, contact the EUDAT helpdesk',
                    'status' => 'error'
                ]
            );
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
        if ($active_uploads < $allowed_uploads) {
            Filesystem::init($this->userId, '/');
            $view = Filesystem::getView();
            $filesize = 0;
            foreach ($ids as $id) {
                $filesize = $filesize + $view->filesize(Filesystem::getPath($id));
            }
            if ($filesize < $allowed_filesize * 1024 * 1024) {
                $job = new TransferHandler($this->mapper);
                $fcStatus = new DepositStatus();
                $fcStatus->setOwner($this->userId);
                $fcStatus->setStatus(1);
                $fcStatus->setCreatedAt(time());
                $fcStatus->setUpdatedAt(time());
                $fcStatus->setTitle($title);
                $fcStatus->setServerId($serverId);
                $depositId = $this->mapper->insert($fcStatus);
                foreach ($ids as $id) { 
                    $depositFile = new DepositFile();
                    $depositFile->setFilename(basename(Filesystem::getPath($id)));
                    $depositFile->setFileid($id);
                    $depositFile->setDepositStatusId($depositId->getId());
                    \OC::$server->getLogger()->info(
                        $depositFile, ['app' => 'b2sharebridge']
                    );
                    $this->dfmapper->insert($depositFile);
                }
            } else {
                return new JSONResponse(
                    [
                        'message' => 'We currently only support 
                        files smaller then ' . $allowed_filesize . ' MB',
                        'status' => 'error'
                    ]
                );
            }
        } else {
            return new JSONResponse(
                [
                    'message' => 'Until your ' . $active_uploads . ' deposits 
                        are done, you are not allowed to create further deposits.',
                    'status' => 'error'
                ]
            );
        }
        // create the actual transfer Cron in the database


        // register transfer cron
        \OC::$server->getJobList()->add(
            $job,
            [
                'transferId' => $fcStatus->getId(),
                'token' => $token,
                '_userId' => $this->userId,
                'community' => $community,
                'open_access' => $open_access,
                'title' => $title,
                'serverId' => $serverId
            ]
        );

        return new JSONResponse(
            [
                "message" => 'Transferring file to B2SHARE in the Background. '.
                'Review the status in B2SHARE app.',
                'status' => 'success'
            ]
        );
    }
}
