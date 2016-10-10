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

use OCA\B2shareBridge\Data;
use OCA\B2shareBridge\Db\DepositStatusMapper;
use OCA\B2shareBridge\Db\StatusCode;
use OCA\B2shareBridge\Db\StatusCodeMapper;
use OCA\B2shareBridge\View\Navigation;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IRequest;
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
class ViewController extends Controller
{
    protected $appName;
    protected $userId;
    protected $statusCodes;
    protected $lastGoodStatusCode;
    protected $navigation;
    protected $data;

    /**
     * Creates the AppFramwork Controller
     *
     * @param string              $appName    name of the app
     * @param IRequest            $request    request object
     * @param IConfig             $config     config object
     * @param DepositStatusMapper $mapper     whatever
     * @param StatusCodeMapper    $scMapper   whatever
     * @param string              $userId     userid
     * @param Navigation          $navigation navigation bar object
     */
    public function __construct(
        $appName,
        IRequest $request,
        IConfig $config,
        DepositStatusMapper $mapper,
        StatusCodeMapper $scMapper,
        $userId,
        Navigation $navigation
    ) {
        parent::__construct($appName, $request);
        $this->appName = $appName;
        $this->userId = $userId;
        $this->mapper = $mapper;
        $this->scMapper = $scMapper;
        $this->config = $config;
        $this->statusCodes = $this->_listStatusCodes();
        $this->navigation = $navigation;
    }

    /**
     * CAUTION: the @Stuff turns off security checks; for this page no admin is
     *          required and no CSRF check. If you don't know what CSRF is, read
     *          it up in the docs or you might create a security hole. This is
     *          basically the only required method to add this exemption, don't
     *          add it to any other method if you don't exactly know what it does
     *
     * @param string $filter filtering string
     *
     * @return TemplateResponse
     *
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function depositList($filter = 'all')
    {

        Util::addStyle('b2sharebridge', 'style');
        Util::addStyle('files', 'files');

        $publications = [];
        if ($filter == 'all') {
            foreach (
                array_reverse(
                    $this->mapper->findAllForUser($this->userId)
                ) as $publication) {
                $publications[] = $publication;
            }
        } else {
            foreach (
                array_reverse(
                    $this->mapper->findAllForUserAndStateString(
                        $this->userId,
                        $filter
                    )
                ) as $publication) {
                $publications[] = $publication;
            }
        }

        $params = [
            'user' => $this->userId,
            'publications' => $publications,
            'statuscodes' => $this->statusCodes,
            'appNavigation' => $this->navigation->getTemplate($filter),
            'filter' => $filter,
        ];

        return new TemplateResponse(
            $this->appName,
            'body',
            $params
        );
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
}
