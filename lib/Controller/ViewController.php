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
use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;
use OCP\Util;

use OCA\B2shareBridge\Job\TransferHandler;
use OCA\B2shareBridge\Db\FilecacheStatusMapper;
use OCA\B2shareBridge\Db\FilecacheStatus;
use OCA\B2shareBridge\Db\StatusCode;
use OCA\B2shareBridge\Db\StatusCodeMapper;

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
    private $_appName;
    private $_userId;
    private $_statusCodes;
    private $_lastGoodStatusCode = 2;

    /**
     * Creates the AppFramwork Controller
     *
     * @param string                $appName  name of the app
     * @param IRequest              $request  request object
     * @param IConfig               $config   config object
     * @param FilecacheStatusMapper $mapper   whatever
     * @param StatusCodeMapper      $scMapper whatever
     * @param string                $userId   userid
     */
    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        FilecacheStatusMapper $mapper,
        StatusCodeMapper $scMapper,
        $userId
    ) {
        parent::__construct($appName, $request);
        $this->_appName = $appName;
        $this->_userId = $userId;
        $this->mapper = $mapper;
        $this->scMapper = $scMapper;
        $this->config = $config;
        $this->_initStatusCode();
        $this->_statusCodes = $this->_listStatusCodes();
        $this->_lastGoodStatusCode = array_search('processing', $this->_statusCodes);
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
        Util::addStyle('b2sharebridge', 'style');
        Util::addStyle('files', 'files');

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
                $this->mapper->findSuccessfulForUser(
                    $this->_userId, $this->_lastGoodStatusCode
                )
            ) as $publication) {
                $publications[] = $publication;
        }
        
        $fails = [];
        foreach (
            array_reverse(
                $this->mapper->findFailedForUser(
                    $this->_userId, $this->_lastGoodStatusCode
                )
            ) as $fail) {
                $fails[] = $fail;
        }

        //$nav = new \OCP\Template('files', 'navigation', '');
        //$navItems = \OCA\Files\App::getNavigationManager()->getAll();
        //usort($navItems, function($item1, $item2) {
        //    return $item1['order'] - $item2['order'];
        //});
        //$nav->assign('navigationItems', $navItems);
        //
        //$contentItems = [];
        //
        //// render the container content for every navigation item
        //foreach ($navItems as $item) {
        //    $content = '';
        //    if (isset($item['script'])) {
        //        $content = $this->renderScript($item['appname'], $item['script']);
        //    }
        //    $contentItem = [];
        //    $contentItem['id'] = $item['id'];
        //    $contentItem['content'] = $content;
        //    $contentItems[] = $contentItem;
        //}
        //$nav->assign('navigationItems', $navItems);
        //$params = [
        //    'user' => $this->_userId,
        //    'appNavigation' => $nav,
        //    'appContents' =>  $contentItems,
        //    'transfers' => $cron_transfers,
        //    'publications' => $publications,
        //    'fails' => $fails,
        //    'statuscodes' => $this->_statusCodes,
        //];


        $params = [
            'user' => $this->_userId,
            'transfers' => $cron_transfers,
            'publications' => $publications,
            'fails' => $fails,
            'statuscodes' => $this->_statusCodes,
        ];

        $response = new TemplateResponse(
            $this->_appName,
            'index',
            $params
        );
        return $response;
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
                $this->mapper->findSuccessfulForUser(
                    $this->_userId, $this->_lastGoodStatusCode
                )
            ) as $publication) {
                $publications[] = $publication;
        }

        $params = [
            'user' => $this->_userId,
            'publications' => $publications,
            'statuscodes' => $this->_statusCodes
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
        $fails = [];
        foreach (
            array_reverse(
                $this->mapper->findFailedForUser(
                    $this->_userId, $this->_lastGoodStatusCode
                )
            ) as $fail) {
                $fails[] = $fail;
        }

        $params = [
            'user' => $this->_userId,
            'fails' => $fails,
            'statuscodes' => $this->_statusCodes
        ];
        return new TemplateResponse('b2sharebridge', 'failed', $params);
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     * %TODO: move this code away!
     *
     * @return something
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    private function _initStatusCode()
    {
        if ($this->scMapper->findCountForStatusCodes() != 6) {
            $statuscode = new StatusCode();
            $params = [
                'statusCode' => 0,
                'message' => 'published'
            ];
            $this->scMapper->insertStatusCode($statuscode->fromParams($params));
            $params = [
                'statusCode' => 1,
                'message' => 'new'
            ];
            $this->scMapper->insertStatusCode($statuscode->fromParams($params));
            $params = [
                'statusCode' => 2,
                'message' => 'processing'
            ];
            $this->scMapper->insertStatusCode($statuscode->fromParams($params));
            $params = [
                'statusCode' => 3,
                'message' => 'External error: during uploading file'
            ];
            $this->scMapper->insertStatusCode($statuscode->fromParams($params));
            $params = [
                'statusCode' => 4,
                'message' => 'External error: during creating deposit'
            ];
            $this->scMapper->insertStatusCode($statuscode->fromParams($params));
            $params = [
                'statusCode' => 5,
                'message' => 'Internal error: file not accessible'
            ];
            $this->scMapper->insertStatusCode($statuscode->fromParams($params));
        }
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @return array
     * 
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    private function _listStatusCodes()
    {
        $statuscodes = [];
        foreach (
                $this->scMapper->findAllStatusCodes()
            as $statuscode) {
                $statuscodes[] = $statuscode->getMessage();
        }
        return $statuscodes;
    }

    /**
     * Render some php scripts
     *
     * @param string $appName    The name of the app, actually b2sharebridge
     * @param string $scriptName The name of the script to load
     *
     * @return string            Some bits and bytes
     */
    protected function renderScript($appName, $scriptName)
    {
        $content = '';
        $appPath = \OC_App::getAppPath($appName);
        $scriptPath = $appPath . '/' . $scriptName;
        if (file_exists($scriptPath)) {
            // TODO: sanitize path / script name ?
            ob_start();
            include $scriptPath;
            $content = ob_get_contents();
            @ob_end_clean();
        }
        return $content;
    }
}
