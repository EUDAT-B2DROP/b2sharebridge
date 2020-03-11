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

use OCP\AppFramework\Controller;
use OCA\B2shareBridge\Model\Server;
use OCA\B2shareBridge\Model\ServerMapper;
use OCP\IRequest;

class ServerController extends Controller {
    private $userId;
    private $mapper;

    public function __construct(
        $appName,
        IRequest $request,
        ServerMapper $mapper,
        $userId
    ) {
        parent::__construct($appName, $request);
        $this->mapper = $mapper;
        $this->userId = $userId;
    }
    /**
     * @NoAdminRequired
     * */
    public function listServers() {
        return $this->mapper->findAll();
    }

    public function saveServers($servers) {
        foreach($servers as $server) {
            if (array_key_exists('id', $server)) {
                $old = $this->mapper->find($server['id']);
                $old->setName($server['name']);
                $old->setPublishUrl($server['publishUrl']);
                $this->mapper->update($old);
            } else {
                $newServer = new Server();
                $newServer->setName($server['name']);
                $newServer->setPublishUrl($server['publishUrl']);
                $this->mapper->insert($newServer);
            }
        }
        return $this->mapper->findAll();
    }

    public function deleteServer($id) {
        $this->mapper->delete($this->mapper->find($id));
    }
}