<?php
/**
 * ownCloud - eudat
 *
 * This file is licensed under the MIT License. See the COPYING file.
 *
 * @author Dennis Blommesteijn <dennis@blommesteijn.com>
 * @copyright Dennis Blommesteijn 2015
 */

namespace OCA\Eudat\Controller;

use OCP\IRequest;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Controller;

use OCA\Eudat\Db\FilecacheStatusMapper;
use OCA\Eudat\Db\FilecacheStatus;

class PageController extends Controller {

	private $userId;

	public function __construct($AppName, IRequest $request, $userId, FilecacheStatusMapper $mapper){
		parent::__construct($AppName, $request);
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
		$jobs = [];
		foreach(\OC::$server->getJobList()->getAll() as $job){
			// filter on Transfers
			if($job instanceof \OCA\Eudat\Transfer){
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
	 * @NoAdminRequired
	 */
	public function publish(){
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
		$job = new \OCA\Eudat\Transfer();
		$fcStatus = new FilecacheStatus();
		$fcStatus->setFileid($id);
		$fcStatus->setStatus("new");
		$fcStatus->setCreatedAt(time());
		$fcStatus->setUpdatedAt(time());
		$this->mapper->insert($fcStatus);
		//TODO: perhaps we should add a duplicate publish check here!

		// register transfer job
		\OC::$server->getJobList()->add($job, ["fileId" => $id, "userId" => $userId, "requestDate" => time()]);

		// TODO: respond with success
		return new DataResponse(["publish" => ["name" => ""]]);
	}

	// /**
	// * Page do view publication view
	// * @NoAdminRequired
	// * @NoCSRFRequired
	// */
	// public function publishQueue(){
	// 	$params = [];
	// 	// return new TemplateResponse('eudat', 'publishQueue', $params);
	// 	return new TemplateResponse('eudat', 'publishQueue', $params);
	// }

	/**
	 * Simply method that posts back the payload of the request
	 * @NoAdminRequired
	 */
	public function doEcho($echo) {
		return new DataResponse(['echo' => $echo]);
	}

}