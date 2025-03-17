<?php
/**
 * NextCloud - B2sharebridge App
 *
 * PHP Version 5-7
 *
 * @category  Nextcloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2020 EUDAT, CSC
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
 * Maps B2SHARE Server to Database table 
 * 
 * @category Nextcloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class ServerMapper extends QBMapper
{
    /**
     * Construct ServerMapper
     * 
     * @param \OCP\IDBConnection $db Database Connection
     */
    public function __construct(IDBConnection $db)
    {
        parent::__construct(
            $db,
            'b2sharebridge_server',
            Server::class,
        );
    }

    /**
     * Find a Server by ID
     * 
     * @param int $id Server ID
     * 
     * @return Server
     */
    public function find(int $id): Server
    {
        //$sql = 'SELECT * FROM `' . $this->tableName . '`WHERE `id` = ?';
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($this->tableName)->where(
            $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
        );
        return $this->findEntity($qb);
    }

    /**
     * Find all servers
     * 
     * @throws Exception
     * @return array of entities
     */
    public function findAll(): array
    {
        //$sql = 'SELECT * FROM ' . $this->tableName;
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($this->tableName);
        return $this->findEntities($qb);
    }
}