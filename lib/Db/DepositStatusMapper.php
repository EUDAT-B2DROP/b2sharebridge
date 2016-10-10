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

namespace OCA\B2shareBridge\Db;

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;
use OCP\Util;

/**
 * Work on a database table
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class DepositStatusMapper extends Mapper
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
            'b2sharebridge_deposit_status',
            '\OCA\B2shareBridge\Db\DepositStatus'
        );
    }

    /**
     * Find a database entry for a file id
     *
     * @param string $id id to find a transfer entry for
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return Entity
     */
    public function find($id)
    {
        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `id` = ?';
        return $this->findEntity($sql, [$id]);
    }


    // public function findAll($limit=null, $offset=null) {
    //     $sql = 'SELECT * FROM `*PREFIX*b2sharebridge_filecache_status`';
    //     return $this->findEntities($sql, $limit, $offset);
    // }

    /**
     * Return all file transfers
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return array(Entity)
     */
    public function findAll()
    {
        $sql = 'SELECT * FROM `' . $this->tableName  .'`';
        return $this->findEntities($sql);
    }


    /**
     * Return all file transfers for current user
     *
     * @param string $user name of the user to search transfers for
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return array(Entities)
     */
    public function findAllForUser($user)
    {
        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `owner` = ?';
        return $this->findEntities($sql, [$user]);
    }


    /**
     * Return the number of currently queued file transfers for a given user
     *
     * @param string  $user       name of the user to search transfers for
     * @param integer $statuscode statuscode to search transfers for
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return int number of active publishs per user
     */
    public function findCountForUser($user, $statuscode)
    {
        $sql = 'SELECT COUNT(*) FROM `' . $this->tableName
            . '` WHERE owner = ? AND status = ?';
        return $this->execute($sql, [$user, $statuscode])->fetchColumn();
    }

    /**
     * Return all file transfers for current user with state
     *
     * @param string $user  name of the user to search transfers for
     * @param string $state styte type
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return array(Entities)
     */
    public function findAllForUserAndState($user, $state)
    {
        $sql = 'SELECT * FROM `' . $this->tableName
            . '` WHERE owner = ? AND status = ?';
        return $this->findEntities($sql, [$user, $state]);
    }

    /**
     * Return all file transfers for current user with state
     *
     * @param string $user  name of the user to search transfers for
     * @param string $state type
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return array(Entities)
     */
    public function findAllForUserAndStateString($user, $state)
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


    /**
     * Return status code numbers for several keywords
     *
     * @param string $filter the filter to apply
     *
     * @return array containing integer status codes
     */
    public function mapFilterToStates($filter)
    {
        switch ($filter) {
        case 'published':
            return [0];
        case 'pending':
            return [1, 2];
        case 'failed':
            return [3, 4, 5];
        default:
            return [];
        }
    }
}
