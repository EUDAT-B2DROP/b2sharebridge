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

namespace OCA\B2shareBridge\Model;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Work on a database table
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class CommunityMapper extends QBMapper
{

    /**
     * Create the database mapper
     *
     * @param IDBConnection $db the database connection to use
     */
    public function __construct(IDBConnection $db)
    {
        parent::__construct(
            $db,
            'b2sharebridge_communities',
            Community::class,
        );
    }

    /**
     * Return all communities
     *
     * @return array(Entity)
     *
     * @throws Exception if not found
     */
    public function findAll(): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($this->tableName);
        //$sql = 'SELECT * FROM `' . $this->tableName  .'`';
        return $this->findEntities($qb);
    }


    /**
     * Return all communities for server with id
     *
     * @return array(Entity)
     * @throws Exception if more th one
     */
    public function findForServer($serverId): array
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($this->tableName)->where(
            $qb->expr()->eq('server_id', $qb->createNamedParameter($serverId, IQueryBuilder::PARAM_INT))
        );
        //$sql = 'SELECT * FROM `' . $this->tableName  . '` WHERE server_id=' . $serverId;
        return $this->findEntities($qb);
    }

    /**
     * Return all communities sorted by name
     *
     * @return array(Entity)
     * @throws Exception
     */
    public function getCommunityList(): array
    {
        $communities = $this->findAll();
        usort(
            $communities, function ($a, $b) {
                return strcmp($a->getName(), $b->getName());
            }
        );
        return $communities;
    }

    /**
     * Returns community name by given id.
     *
     * @param string $uid internal uid of the community
     * @param string $serverId internal uid of the server
     * @return Community
     * @throws Exception|MultipleObjectsReturnedException|DoesNotExistException
     */
    public function find(string $uid, string $serverId): Community
    {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($this->tableName)->where(
            $qb->expr()->eq('id', $qb->createNamedParameter($uid))
        )->andWhere(
            $qb->expr()->eq('server_id', $qb->createNamedParameter($serverId, IQueryBuilder::PARAM_INT))
        );
        return $this->findEntity($qb);
    }

    /**
     * Return all communities as array with id and name
     *
     * @param string $id internal id of the Community
     *
     * @return int  number of deleted communities
     * @throws Exception
     */
    public function deleteCommunity(string $id): int
    {
        //$sql = 'DELETE FROM `' . $this->tableName
        //    . '` WHERE id = ?';
        $qb = $this->db->getQueryBuilder();
        $qb->delete($this->tableName)->where(
            $qb->expr()->eq('id', $qb->createNamedParameter($id))
        );
        return $qb->executeStatement();
    }
}
