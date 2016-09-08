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

use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Util;

use OCA\B2shareBridge\Job\TransferHandler;
use OCA\B2shareBridge\Db\FilecacheStatusMapper;
use OCA\B2shareBridge\Db\FilecacheStatus;

/**
 * Implement a ownCloud AppFramework Controller
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class B2shareBridge extends Controller
{
    private $_userId;

    /**
     * Creates the AppFramwork Controller
     *
     * @param string                $appName name of the app
     * @param IRequest              $request request object
     * @param IConfig               $config  config object
     * @param FilecacheStatusMapper $mapper  whatever
     * @param string                $userId  userid
     */
    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        FilecacheStatusMapper $mapper,
        $userId
    ) {
        parent::__construct($appName, $request);
        $this->_userId = $userId;
        $this->mapper = $mapper;
        $this->config = $config;

    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @return          TemplateResponse
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index()
    {
        $cron_transfers = [];
        foreach (\OC::$server->getJobList()->getAll() as $cron_transfer) {
            // filter on Transfers
            if ($cron_transfer instanceof TransferHandler) {
                // filter only own requested jobs
                if ($cron_transfer->isPublishingUser($this->_userId)) {
                    $id = $cron_transfer->getArgument()['transferId'];
                    $publication = $this->mapper->find($id);
                    $cron_transfers[] = [
                        'id' => $id,
                        'filename' => $publication->getFilename(),
                        'date' => $publication->getCreatedAt()
                    ];
                }
                // TODO: admin can view all publications
            }
        }

        $publications = [];
        foreach (
            array_reverse(
                $this->mapper->findAllForUser($this->_userId)
            ) as $publication) {
                $publications[] = $publication;
        }

        $params = [
            'user' => $this->_userId,
            'transfers' => $cron_transfers,
            'publications' => $publications
        ];
        return new TemplateResponse('b2sharebridge', 'main', $params);
    }
    
    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @return          TemplateResponse
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function filterPending()
    {
        $cron_transfers = [];
        foreach (\OC::$server->getJobList()->getAll() as $cron_transfer) {
            // filter on Transfers
            if ($cron_transfer instanceof TransferHandler) {
                // filter only own requested jobs
                if ($cron_transfer->isPublishingUser($this->_userId)) {
                    $id = $cron_transfer->getArgument()['transferId'];
                    $publication = $this->mapper->find($id);
                    $cron_transfers[] = [
                        'id' => $id,
                        'filename' => $publication->getFilename(),
                        'date' => $publication->getCreatedAt()
                    ];
                }
                // TODO: admin can view all publications
            }
        }
        
        $params = [
            'user' => $this->_userId,
            'transfers' => $cron_transfers
        ];
        return new TemplateResponse('b2sharebridge', 'pending', $params);
    }
    
    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @return          TemplateResponse
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function filterPublished()
    {
        $publications = [];
        foreach (
            array_reverse(
                $this->mapper->findSuccessfulForUser($this->_userId)
            ) as $publication) {
                $publications[] = $publication;
        }

        $params = [
            'user' => $this->_userId,
            'publications' => $publications
        ];
        return new TemplateResponse('b2sharebridge', 'published', $params);
    }
    
    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @return          TemplateResponse
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function filterFailed()
    {
        $publications = [];
        foreach (
            array_reverse(
                $this->mapper->findFailedForUser($this->_userId)
            ) as $publication) {
                $publications[] = $publication;
        }

        $params = [
            'user' => $this->_userId,
            'publications' => $publications
        ];
        return new TemplateResponse('b2sharebridge', 'failed', $params);
    }

    /**
     * XHR request endpoint for getting publish command
     *
     * @return          JSONResponse
     * @NoAdminRequired
     */
    public function publish()
    {
        $param = $this->request->getParams();

        $error = false;
        if (!is_array($param)
            || !array_key_exists('id', $param)
            || !array_key_exists('token', $param)
        ) {
            $error = 'Parameters gotten from UI are no array or they are missing';
        }
        $id = (int) $param['id'];
        $token = $param['token'];

        if (!is_int($id) || !is_string($token)) {
            $error = 'Problems while parsing fileid or publishToken';
        }
        $_userId = \OC::$server->getUserSession()->getUser()->getUID();
        if (strlen($_userId) <= 0) {
            $error = 'No user configured for session';
        }
        if (($error)) {
            Util::writeLog('b2sharebridge', $error, 3);
            return new JSONResponse(
                [
                    'message'=>'Internal server error, contact the EUDAT helpdesk',
                    'status' => 'error'
                ]
            );
        }

        // create the actual transfer job in the database
        $job = new TransferHandler($this->mapper, $this->config);
        $fcStatus = new FilecacheStatus();
        $fcStatus->setFileid($id);
        $fcStatus->setOwner($_userId);
        $fcStatus->setStatus("new");
        $fcStatus->setCreatedAt(time());
        $fcStatus->setUpdatedAt(time());
        $this->mapper->insert($fcStatus);
        /* TODO: we should add a configuration setting for admins to
         * configure the maximum number of uploads per user and a max filesize.
         *both to avoid DoS
         *
         */

        // register transfer cron
        \OC::$server->getJobList()->add(
            $job, [
                'transferId' => $fcStatus->getId(),
                'token' => $token,
                '_userId' => $_userId
            ]
        );

        return new JSONResponse(
            [
                "message" => 'Transferring file to B2SHARE in the Background',
                'status' => 'success'
            ]
        );
    }
}
