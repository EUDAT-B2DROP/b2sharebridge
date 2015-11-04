<?php
/**
 * ownCloud - eudat
 *
 * This file is licensed under the MIT License. See the LICENSE file.
 *
 * @author Dennis Blommesteijn <dennis@blommesteijn.com>
 * @copyright Dennis Blommesteijn 2015
 */

namespace OCA\Eudat\Controller;

use OCP\IRequest;
use OCP\Util;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;

use OCA\Eudat\Job\TransferHandler;
use OCA\Eudat\Db\FilecacheStatusMapper;
use OCA\Eudat\Db\FilecacheStatus;

class PageController extends Controller {

    private $userId;

    /**
     * @param string $appName
     * @param IRequest $request
     * @param $userId
     * @param FilecacheStatusMapper $mapper
     */
    public function __construct($appName,
                                IRequest $request,
                                $userId,
                                FilecacheStatusMapper $mapper){
        Util::writeLog('controller', 'CONSTRUCT', 3);

        parent::__construct($appName, $request);
        $this->userId = $userId;
        $this->mapper = $mapper;
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
        Util::writeLog('controller', 'INDEX', 3);

        $jobs = [];
        foreach(\OC::$server->getJobList()->getAll() as $job){
            // filter on Transfers
            if($job instanceof TransferHandler){
                // filter only own requested jobs
                if($job->isPublishingUser($this->userId))
                    $jobs[] = $job;
                // TODO: admin can view all publications
            }
        }

        // \OC\Files\Filesystem::init($this->userId, '/');


        // $status = [];

        $status = [];
        foreach($this->mapper->findAll() as $file){
            $status[] = $file;
        }
        //TODO: add filter

        // $fcStatus = new FilecacheStatus();
        // $fcStatus->setFileid(8);
        // $fcStatus->setStatus("new");
        // $this->mapper->insert($fcStatus);
        // print_r($fcStatus)
        // $fcStatus->setFileId($id);
        // $fcStatus->setStatus("new");

        // prepare variables
        $params = [
            'user' => $this->userId,
            'jobs' => $jobs,
            'fileStatus' => $status
        ];
        return new TemplateResponse('eudat', 'main', $params);  // templates/main.php
    }

    /**
     * XHR request endpoint for getting publish command
     * @return DataResponse
     * @NoAdminRequired
     */
    public function publish(){
        Util::writeLog('controller', 'PUBLISH', 3);

        $param = $this->request->getParams();

        if(!is_array($param)){
            return new DataResponse(["error"=>"expected array"]);
        }
        if(!array_key_exists('id', $param)){
            return new DataResponse(["error"=>"no `id` present"]);
        }
        $id = (int) $param['id'];
        if(!is_int($id)){
            return new DataResponse(["error"=>"expected integer"]);
        }
        $userId = \OC::$server->getUserSession()->getUser()->getUID();
        if(strlen($userId) <= 0){
            return new DataResponse(["error"=>"no `userId` present"]);
        }
        // create new publish job
        $job = new TransferHandler($this->mapper);
        $fcStatus = new FilecacheStatus();
        $fcStatus->setFileid($id);
        $fcStatus->setStatus("new");
        $fcStatus->setCreatedAt(time());
        $fcStatus->setUpdatedAt(time());
        $this->mapper->insert($fcStatus);
        //TODO: perhaps we should add a duplicate publish check here!

        // register transfer job
        \OC::$server->getJobList()->add($job, ['fileId' => $id, 'transferId' => $fcStatus->getId(), 'userId' => $userId, 'requestDate' => time()]);

        // TODO: respond with success
        return new DataResponse(["publish" => ["name" => ""]]);
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