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
    private ITimeFactory $time;
    private B2share $publisher;
    private ServerMapper $smapper;
    private CommunityMapper $cmapper;
    private IJobList $jobList;

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
        ITimeFactory $time,
        B2share $publisher,
        ServerMapper $smapper,
        CommunityMapper $cmapper,
        IManager $notManager,
        LoggerInterface $logger,
        IJobList $jobList,
        string $userId
    ) {
        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->mapper = $mapper;
        $this->dfmapper = $dfmapper;
        $this->statusCodes = $statusCodes;
        $this->config = $config;
        $this->time = $time;
        $this->publisher = $publisher;
        $this->smapper = $smapper;
        $this->cmapper = $cmapper;
        $this->logger = $logger;
        $this->jobList = $jobList;
        $this->notManager = $notManager;
    }

    /**
     * XHR request endpoint for getting Publish command
     *
     * @return          JSONResponse
     * @NoAdminRequired
     * @throws          Exception
     * @throws          MultipleObjectsReturnedException
     */
    public function publish(): JSONResponse
    {
        $param = $this->request->getParams();
        //TODO what if token wasn't set? We couldn't have gotten here
        //but still a check seems in place.

        if (
            !array_key_exists('ids', $param)
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
        $serverId = $param['server_id'];
        $ids = $param['ids'];
        $community = $param['community'];
        $open_access = $param['open_access'];
        $title = $param['title'];

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
            $server = $this->smapper->find($serverId);
        } catch (DoesNotExistException $e) {
            return new JSONResponse(
                [
                    'message' => 'Invalid server id',
                    'status' => 'error'
                ],
                Http::STATUS_BAD_REQUEST
            );
        }
        $this->publisher->setCheckSSL($server->getCheckSsl());

        $active_uploads = count(
            $this->mapper->findAllForUserAndStateString(
                $this->userId,
                'pending'
            )
        );
        if ($active_uploads < $server->getMaxUploads()) {
            Filesystem::init($this->userId, '/');
            $view = Filesystem::getView();
            $filesize = 0;
            foreach ($ids as $id) {
                $filesize = $filesize + $view->filesize(Filesystem::getPath($id));
            }
            if ($filesize < $server->getMaxUploadFilesize() * 1024 * 1024) {
                $job = new TransferHandler(
                    $this->time,
                    $this->mapper,
                    $this->dfmapper,
                    $this->publisher,
                    $this->smapper,
                    $this->cmapper,
                    $this->notManager,
                    $this->logger
                );
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
                    $this->logger->debug(
                        "Inserting " . $depositFile->getFilename(),
                        ['app' => Application::APP_ID]
                    );
                    $this->dfmapper->insert($depositFile);
                }
            } else {
                return new JSONResponse(
                    [
                        'message' => 'We currently only support 
                        files smaller then ' . $server->getMaxUploadFilesize() . ' MB',
                        'status' => 'error'
                    ],
                    Http::STATUS_REQUEST_ENTITY_TOO_LARGE
                );
            }
        } else {
            return new JSONResponse(
                [
                    'message' => 'Until your ' . $server->getMaxUploads() . ' deposits 
                        are done, you are not allowed to create further deposits.',
                    'status' => 'error'
                ],
                Http::STATUS_TOO_MANY_REQUESTS
            );
        }
        // create the actual transfer Cron in the database


        // register transfer cron
        $this->jobList->add(
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
                "message" => 'Transferring file to B2SHARE in the Background. ' .
                    'Review the status in B2SHARE app.',
                'status' => 'success'
            ]
        );
    }
}
