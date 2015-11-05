<?php
/**
 * ownCloud - eudat
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright EUDAT 2015
 */

namespace OCA\Eudat\Controller;

use OCP\IConfig;
use OCP\IRequest;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\JSONResponse;

use OCA\Eudat\Job\TransferHandler;
use OCA\Eudat\Db\FilecacheStatusMapper;
use OCA\Eudat\Db\FilecacheStatus;

class Eudat extends Controller {

    private $userId;

    /**
     * @param string $appName
     * @param IRequest $request
     * @param IConfig $config
     * @param $userId
     * @param FilecacheStatusMapper $mapper
     */
    public function __construct($appName,
                                IRequest $request,
                                IConfig $config,
                                FilecacheStatusMapper $mapper,
                                $userId
                                ){

        parent::__construct($appName, $request);
        $this->userId = $userId;
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
     * @NoAdminRequired
     * @NoCSRFRequired
     */
    public function index() {
        $jobs = [];
        foreach(\OC::$server->getJobList()->getAll() as $job){
            // filter on Transfers
            if($job instanceof TransferHandler){
                // filter only own requested jobs
                if($job->isPublishingUser($this->userId)) {
                    $id = $job->getArgument()['transferId'];
                    $transfer = $this->mapper->find($id);
                    $jobs[] = ['id' => $id, 'filename' => $transfer->getFilename(), 'date' => $transfer->getCreatedAt()];
                }
                // TODO: admin can view all publications
            }
        }

        $transfers = [];
        foreach(array_reverse($this->mapper->findAllForUser($this->userId)) as $transfer){
            $transfers[] = $transfer;
        }

        $params = [
            'user' => $this->userId,
            'jobs' => $jobs,
            'fileStatus' => $transfers
        ];
        return new TemplateResponse('eudat', 'main', $params);  // templates/main.php
    }

    /**
     * XHR request endpoint for getting publish command
     * @return DataResponse
     * @NoAdminRequired
     */
    public function publish(){
        $param = $this->request->getParams();

        if(!is_array($param)){
            return new JSONResponse(["message"=>"expected array"], Http::STATUS_SERVICE_UNAVAILABLE);
        }
        if(!array_key_exists('id', $param)){
            return new JSONResponse(["message"=>"no `id` present"], Http::STATUS_SERVICE_UNAVAILABLE);
        }
        $id = (int) $param['id'];
        if(!is_int($id)){
            return new JSONResponse(["message"=>"expected integer"], Http::STATUS_SERVICE_UNAVAILABLE);
        }
        $userId = \OC::$server->getUserSession()->getUser()->getUID();
        if(strlen($userId) <= 0){
            return new JSONResponse(["message"=>"no `userId` present"], Http::STATUS_SERVICE_UNAVAILABLE);
        }
        // create new publish job
        $job = new TransferHandler($this->mapper, $this->config);
        $fcStatus = new FilecacheStatus();
        $fcStatus->setFileid($id);
        $fcStatus->setOwner($userId);
        $fcStatus->setStatus("new");
        $fcStatus->setCreatedAt(time());
        $fcStatus->setUpdatedAt(time());
        $this->mapper->insert($fcStatus);
        //TODO: we should add a configuration setting for admins to configure the maximum number of uploads per user

        // register transfer job
        \OC::$server->getJobList()->add($job, ['transferId' => $fcStatus->getId(), 'userId' => $userId]);

        // TODO: respond with success
        return new JSONResponse(["message" => 'Transferring file to B2SHARE in the Background'], Http::STATUS_ACCEPTED);
    }
    // /**
    // * Page do view publication view
    // * @NoAdminRequired
    // * @NoCSRFRequired
    // */
    // public function publishQueue(){
    //     $params = [];
    //     // return new TemplateResponse('eudat', 'publishQueue', $params);
    //     return new TemplateResponse('eudat', 'publishQueue', $params);
    // }


    /**
     * Simply method that posts back the payload of the request
     * @param \string     $echo
     * @return DataResponse
     * @NoAdminRequired
     */
    public function doEcho($echo) {
        return new DataResponse(['echo' => $echo]);
    }

}