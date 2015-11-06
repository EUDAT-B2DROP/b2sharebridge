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
use OCP\Util;

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
        $cron_transfers = [];
        foreach(\OC::$server->getJobList()->getAll() as $cron_transfer){
            // filter on Transfers
            if($cron_transfer instanceof TransferHandler){
                // filter only own requested jobs
                if($cron_transfer->isPublishingUser($this->userId)) {
                    $id = $cron_transfer->getArgument()['transferId'];
                    $publication = $this->mapper->find($id);
                    $cron_transfers[] = ['id' => $id, 'filename' => $publication->getFilename(), 'date' => $publication->getCreatedAt()];
                }
                // TODO: admin can view all publications
            }
        }

        $publications = [];
        foreach(array_reverse($this->mapper->findAllForUser($this->userId)) as $publication){
            $publications[] = $publication;
        }

        $params = [
            'user' => $this->userId,
            'transfers' => $cron_transfers,
            'publications' => $publications
        ];
        return new TemplateResponse('eudat', 'main', $params);  // templates/main.php
    }

    /**
     * XHR request endpoint for getting publish command
     * @return JSONResponse
     * @NoAdminRequired
     */
    public function publish(){
        $param = $this->request->getParams();

        $error = false;
        if(!is_array($param) || !array_key_exists('id', $param)){
            $error = 'Parameters gotten from UI are no array';
        }
        $id = (int) $param['id'];
        if(!is_int($id)){
            $error = 'Problems while parsing fileid';
        }
        $userId = \OC::$server->getUserSession()->getUser()->getUID();
        if(strlen($userId) <= 0){
            $error = 'No user configured for session';
        }
        if (($error)) {
            Util::writeLog('eudat', $error, 3);
            return new JSONResponse(['message'=>'Internal server error, contact the EUDAT helpdesk', 'status' => 'error']);
        }

        // create the actual transfer job in the database
        $job = new TransferHandler($this->mapper, $this->config);
        $fcStatus = new FilecacheStatus();
        $fcStatus->setFileid($id);
        $fcStatus->setOwner($userId);
        $fcStatus->setStatus("new");
        $fcStatus->setCreatedAt(time());
        $fcStatus->setUpdatedAt(time());
        $this->mapper->insert($fcStatus);
        //TODO: we should add a configuration setting for admins to configure the maximum number of uploads per user and a max filesize. both to avoid DoS


        $token = '';
        // register transfer cron
        \OC::$server->getJobList()->add($job, ['transferId' => $fcStatus->getId(),'token' => $token, 'userId' => $userId]);

        return new JSONResponse(["message" => 'Transferring file to B2SHARE in the Background', 'status' => 'success']);
    }
}