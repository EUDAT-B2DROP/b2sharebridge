<?php
/**
 * OwnCloud - B2sharebridge App
 *
 * PHP Version 5-7
 *
 * @category  Nextcloud
 * @package   B2shareBridge
 * @author    EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright 2017 EUDAT, SURFSara
 * @license   AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link      https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */

namespace OCA\B2shareBridge\Model;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

/**
 * Work on a database table
 *
 * @category Nextcloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class DepositFileMapper extends QBMapper
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
            'b2sharebridge_file',
            DepositFile::class,
        );
    }

    /**
     * Find a database entry for a file id
     *
     * @param string $id id to find a transfer entry for
     *
     * @return Entity
     * @throws Exception|MultipleObjectsReturnedException|DoesNotExistException
     */
    public function find(string $id)
    {
        //$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `id` = ?';
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($this->tableName)->where(
            $qb->expr()->eq('id', $qb->createNamedParameter($id))
        );
        return $this->findEntity($qb);
    }

    /**
     * Return all file transfers
     *
     * @return array(Entity)
     * @throws Exception
     */
    public function findAll(): array
    {
        //$sql = 'SELECT * FROM `' . $this->tableName . '`';
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($this->tableName);
        return $this->findEntities($qb);
    }


    /**
     * Return all files for a deposit
     *
     * @param string $depositId the id of the deposit to search transfers for
     *
     * @return array(Entities)
     *
     * @throws Exception
     */
    public function findAllForDeposit(string $depositId): array
    {
        //$sql = 'SELECT * FROM `'
        //    . $this->tableName
        //    . '` WHERE `deposit_status_id` = ?';
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($this->tableName)->where(
            $qb->expr()->eq('deposit_status_id', $qb->createNamedParameter($depositId))
        );
        return $this->findEntities($qb);
    }

    /**
     * Return file count for a deposit
     *
     * @param int $depositId the id of the deposit to search transfers for
     *
     * @return int
     *
     * @throws Exception
     */
    public function getFileCount(int $depositId): int
    {
        //$sql = 'SELECT count(*) FROM `'
        //    . $this->tableName
        //    . '` WHERE `deposit_status_id` = ?';
        $qb = $this->db->getQueryBuilder();
        $qb->selectAlias($qb->createFunction('COUNT(*)'), 'count')->from($this->tableName)->where(
            $qb->expr()->eq('deposit_status_id', $qb->createNamedParameter($depositId, IQueryBuilder::PARAM_INT))
        );
        $cursor = $qb->executeQuery();
        $row = $cursor->fetch();
        $cursor->closeCursor();
        return $row['count'];
    }
}
