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
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\BackgroundJob\IJobList;
use OCP\DB\Exception;
use OCP\IRequest;
use OCA\B2shareBridge\Cron\B2shareCommunityFetcher;


class ServerController extends Controller
{
    private $userId;
    private $mapper;
    private $joblist;

    public function __construct(
        $appName,
        IRequest $request,
        ServerMapper $mapper,
        IJobList $jobList,
        $userId
    ) {
        parent::__construct($appName, $request);
        $this->mapper = $mapper;
        $this->userId = $userId;
        $this->joblist = $jobList;
    }

    /**
     * @NoAdminRequired
     *
     * @throws Exception
     */
    public function listServers(): array
    {
        return $this->mapper->findAll();
    }

    /**
     * @throws MultipleObjectsReturnedException
     * @throws DoesNotExistException
     * @throws Exception
     */
    public function saveServer($server): array
    {
        if ($server?->id) {
            $old = $this->mapper->find($server['id']);
            $old->setName($server['name']);
            $old->setPublishUrl($server['publishUrl']);
            $this->mapper->update($old);
        }
        else {
            $newServer = new Server();
            $newServer->setName($server['name']);
            $newServer->setPublishUrl($server['publishUrl']);
            $this->mapper->insert($newServer);
        }

        // replace job to get communities instantly
        $this->joblist->remove(B2shareCommunityFetcher::class);
        $this->joblist->add(B2shareCommunityFetcher::class);

        return $this->mapper->findAll();
    }

    /**
     * @depreacted use saveServer instead
     * @throws     MultipleObjectsReturnedException
     * @throws     DoesNotExistException
     * @throws     Exception
     */
    public function saveServers($servers): array
    {
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
        // replace job to get communities instantly
        $this->joblist->remove(B2shareCommunityFetcher::class);
        $this->joblist->add(B2shareCommunityFetcher::class);

        return $this->mapper->findAll();
    }

    /**
     * @throws MultipleObjectsReturnedException
     * @throws DoesNotExistException
     * @throws Exception
     */
    public function deleteServer($id)
    {
        $this->mapper->delete($this->mapper->find($id));
    }
}