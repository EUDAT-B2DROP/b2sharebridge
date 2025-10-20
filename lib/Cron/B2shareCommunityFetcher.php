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

namespace OCA\B2shareBridge\Cron;

use OCA\B2shareBridge\AppInfo\Application;
use OCA\B2shareBridge\Model\Community;
use OCA\B2shareBridge\Model\CommunityMapper;
use OCA\B2shareBridge\Model\ServerMapper;
use OCA\B2shareBridge\Publish\B2ShareFactory;
use OCP\DB\Exception;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCP\IDBConnection;
use OCP\BackgroundJob\TimedJob;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Utility\ITimeFactory;

/**
 * Register a owncloud Job to regularly fetch b2share api to get communities list
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class B2shareCommunityFetcher extends TimedJob
{

    private IConfig $_config;
    private LoggerInterface $_logger;
    private IDBConnection $_dbconnection;
    private B2ShareFactory $_b2shareFactory;

    /**
     * Create cron that is fetching the b2share communities api
     * with dependency injection
     * 
     * @param \Psr\Log\LoggerInterface               $logger         Logger
     * @param \OCP\IConfig                           $config         Config
     * @param \OCP\IDBConnection                     $dbconnection   Connection
     * @param \OCP\AppFramework\Utility\ITimeFactory $time           Time
     * @param B2ShareFactory                         $b2shareFactory B2Share API factory
     */
    public function __construct(LoggerInterface $logger, IConfig $config, IDBConnection $dbconnection, ITimeFactory $time, B2ShareFactory $b2shareFactory)
    {
        parent::__construct($time);
        $this->_config = $config;
        $this->_logger = $logger;
        $this->_dbconnection = $dbconnection;
        $this->_b2shareFactory = $b2shareFactory;
        // Run once an hour
        $this->setInterval(3600);
    }

    /**
     * Cron code to execute
     *
     * @param array $args array of arguments
     * 
     * @throws Exception
     * 
     * @return void
     */
    public function run($args)
    {
        $serverMapper = new ServerMapper($this->_dbconnection);
        $communityMapper = new CommunityMapper($this->_dbconnection);
        $servers = $serverMapper->findAll();
        foreach ($servers as $server) {
            $publisher = $this->_b2shareFactory->get($server->getVersion());
            $json = $publisher->fetchCommunities($server);
            if (!$json) {
                $this->_logger->error(
                    'Fetching the B2SHARE communities API at ' . $server->getPublishUrl() .
                    ' was not possible.',
                    ['app' => Application::APP_ID]
                );
                continue;
            }
            $communities_fetched = json_decode($json, true)['hits']['hits'];
            if ($communities_fetched === null) {
                $this->_logger->error(
                    'Fetching the B2SHARE communities API at ' . $server->getPublishUrl() .
                    ' did not return a valid JSON.',
                    ['app' => Application::APP_ID]
                );
                continue;
            }

            $communities_b2share = [];
            foreach ($communities_fetched as $community) {
                $this->_logger->debug(
                    'Fetched community with id: ' . $community['id'] .
                    ' and name: ' . $community['name'] . ' fetched' .
                    ' and restricted_submission: ' . $community['restricted_submission'] . ' from server ' . $server->getName(),
                    ['app' => Application::APP_ID]
                );
                if ($server->getVersion() == 2) {
                    if ($community['restricted_submission'] !== true) {
                        $communities_b2share[$community['id']] = $community['name'];
                    } else {
                        $communities_b2share[$community['id']] = $community['name'] . ' ' .
                            "\u{0001F512}";
                    }
                } else {
                    if ($community['access']['record_policy'] !== 'open') {
                        $communities_b2share[$community['id']] = $community['metadata']['title'];
                    } else {
                        $communities_b2share[$community['id']] = $community['metadata']['title'] . ' ' .
                            "\u{0001F512}";
                    }
                }
            }

            $communities_b2drop = [];
            foreach ($communityMapper->findForServer($server->getId()) as $community) {
                $communities_b2drop[$community->getId()] = $community->getName();
            }

            // do we need to remove a community?
            $remove_communities = array_diff_key(
                $communities_b2drop,
                $communities_b2share
            );
            foreach ($remove_communities as $id => $name) {
                $this->_logger->info(
                    'Removing community with id: ' . $id .
                    ' and name: ' . $name . ' after synchronization with b2share',
                    ['app' => Application::APP_ID]
                );
                $communityMapper->deleteCommunity($id);
            }

            // do we need to add a community?
            $add_communities = array_diff_key($communities_b2share, $communities_b2drop);
            foreach ($add_communities as $id => $name) {
                $this->_logger->info(
                    'Adding community with id: ' . $id . ' and name: '
                    . $name . ' after synchronization with B2SHARE server ' . $server->getName(),
                    ['app' => Application::APP_ID]
                );
                $communityMapper->insert(Community::fromParams(['id' => $id, 'name' => $name, 'serverId' => $server->getId()]));
            }
        }

        // Remove orphans

        $communities = $communityMapper->findAll();
        foreach ($communities as $community) {
            $found = false;
            foreach ($servers as $server) {
                if ($server->getId() == $community->getServerId()) {
                    $found = true;
                }
            }
            if (!$found) {
                $this->_logger->info(
                    'Removing orphan community with id: ' . $community->getId()
                );
                $communityMapper->delete($community);
            }
        }
    }
}
