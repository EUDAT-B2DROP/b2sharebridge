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
use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * Work on a database table
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class DepositStatusMapper extends QBMapper
{

    /**
     * Create the database mapper
     *
     * @param IDBConnection $db the database connection to use
     */
    private LoggerInterface $logger;

    public function __construct(IDBConnection $db, LoggerInterface $logger)
    {
        parent::__construct(
            $db,
            'b2sharebridge_status',
            '\OCA\B2shareBridge\Model\DepositStatus',
        );
        $this->logger = $logger;
    }

    /**
     * Find a database entry for a file id
     *
     * @param int $id id to find a transfer entry for
     *
     * @return Entity
     * @throws MultipleObjectsReturnedException if more than one
     * @throws DoesNotExistException if not found
     * @throws Exception
     */
    public function find(int $id): Entity
    {
        $qb = $this->db->getQueryBuilder();
        //'SELECT * FROM `' . $this->tableName . '` WHERE `id` = ?';
        $qb->select('*')->from($this->tableName)->where(
            $qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT))
        );
        return $this->findEntity($qb);
    }


    // public function findAll($limit=null, $offset=null) {
    //     $sql = 'SELECT * FROM `*PREFIX*b2sharebridge_filecache_status`';
    //     return $this->findEntities($sql, $limit, $offset);
    // }

    /**
     * Return all file transfers
     *
     * @return array(Entity)
     *
     * @throws Exception
     */
    public function findAll(): array
    {
        $qb = $this->db->getQueryBuilder();
        //$sql = 'SELECT * FROM `' . $this->tableName  .'`';
        $qb->select('*')->from($this->tableName);
        return $this->findEntities($qb);
    }


    /**
     * Return all file transfers for current user
     *
     * @param string $user name of the user to search transfers for
     *
     * @return array(Entities)
     *
     * @throws Exception if not found
     */
    public function findAllForUser($user): array
    {
        $qb = $this->db->getQueryBuilder();
        //$sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `owner` = ?';
        $qb->select('*')->from($this->tableName)->where(
            $qb->expr()->eq('owner', $qb->createNamedParameter($user))
        );
        return $this->findEntities($qb);
    }


    /**
     * Return the number of currently queued file transfers for a given user
     *
     * @param string $user name of the user to search transfers for
     * @param integer $statuscode statuscode to search transfers for
     *
     * @return int number of active publishs per user
     *
     * @throws Exception if not found
     */
    public function findCountForUser(string $user, int $statuscode): int
    {
        $qb = $this->db->getQueryBuilder();
        //$sql = 'SELECT COUNT(*) FROM `' . $this->tableName
        //    . '` WHERE owner = ? AND status = ?';
        $qb->selectAlias($qb->createFunction('COUNT(*)'), 'count')->from($this->tableName)->where(
            $qb->expr()->eq('owner', $qb->createNamedParameter($user))
        )->andWhere(
            $qb->expr()->eq('status', $qb->createNamedParameter($statuscode, IQueryBuilder::PARAM_INT))
        );
        /** @noinspection PhpDeprecationInspection */
        $cursor = $qb->execute();
        $row = $cursor->fetch();
        $cursor->closeCursor();
        return $row['count'];
    }

    /**
     * Return all file transfers for current user with state
     *
     * @param string $user name of the user to search transfers for
     * @param int $state state type
     *
     * @return array(Entities)
     *
     * @throws Exception if not found
     */
    public function findAllForUserAndState(string $user, int $state): array
    {
        $qb = $this->db->getQueryBuilder();
        //$sql = 'SELECT * FROM `' . $this->tableName
        //    . '` WHERE owner = ? AND status = ?';
        $qb->select('*')->from($this->tableName)->where(
            $qb->expr()->eq('owner', $qb->createNamedParameter($user))
        )->andWhere(
            $qb->expr()->eq('status', $qb->createNamedParameter($state, IQueryBuilder::PARAM_INT))
        );
        return $this->findEntities($qb);
    }

    /**
     * Return all file transfers for current user with state
     *
     * @param string $user name of the user to search transfers for
     * @param string $state type
     *
     * @return array(Entities)
     * @throws Exception if more th one
     *
     */
    public function findAllForUserAndStateString(string $user, string $state): array
    {
        $nStates = $this->mapFilterToStates($state);

        $deposits = [];
        foreach ($nStates as $nState) {
            foreach ($this->findAllForUserAndState($user, $nState) as $deposit) {
                $deposits[] = $deposit;
            }
        }

        return $deposits;
    }

    public function findLastUpdate(): int|null
    {
        $qb = $this->db->getQueryBuilder();
        $statusTableNameWithPrefix = str_replace(["'", "`"], "", $qb->getTableName($this->tableName));  // remove quotes
        $qb->automaticTablePrefix(false);  //information schema is a meta table
        $qb->select('update_time')->from('information_schema.tables')->where(
            $qb->expr()->eq('table_name', $statusTableNameWithPrefix)
        );

        try {
            $cursor = $qb->executeQuery();
            $row = $cursor->fetch();
            $cursor->closeCursor();
        } catch (Exception $e) {
            $this->logger->error("Could not fetch update_time", ["error" => $e]);
            return null;
        }
        $time = strtotime($row['update_time']);
        if (is_bool($time)) {
            $this->logger->error("Could not interprete database time", ["time" => $row['update_time']]);
            return null;
        }
        return $time;
    }


    /**
     * Return status code numbers for several keywords
     *
     * @param string $filter the filter to apply
     *
     * @return array containing integer status codes
     */
    public function mapFilterToStates(string $filter): array
    {
        return match ($filter) {
            'published' => [0],
            'pending' => [1, 2],
            'failed' => [3, 4, 5],
            default => [],
        };
    }
}
