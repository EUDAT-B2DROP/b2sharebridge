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
use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

/**
 * Work on a database table
 *
 * @category Owncloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class FilecacheStatusMapper extends Mapper
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
            'b2sharebridge_filecache_status',
            '\OCA\B2shareBridge\Db\FilecacheStatus'
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
        $sql = 'SELECT * FROM `*PREFIX*b2sharebridge_filecache_status` '
            . 'WHERE `id` = ?';
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
        $sql = 'SELECT * FROM *PREFIX*b2sharebridge_filecache_status';
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
        $sql = 'SELECT * FROM `*PREFIX*b2sharebridge_filecache_status` '
            . 'WHERE `owner` = ?';
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
        $sql = 'SELECT COUNT(*) FROM `*PREFIX*b2sharebridge_filecache_status` '
            .'WHERE owner = ? AND status = ?';
        return $this->execute($sql, [$user, $statuscode])->fetchColumn();
    }

    /**
     * Return all failed file transfers for current user
     *
     * @param string  $user               name of the user to search transfers for
     * @param integer $lastGoodStatusCode last status code for a sucessful transfer
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return array(Entities)
     */
    public function findFailedForUser($user, $lastGoodStatusCode)
    {
        $sql = 'SELECT * FROM `*PREFIX*b2sharebridge_filecache_status` '
            . 'WHERE `status` > ? AND `owner` = ?';
        return $this->findEntities($sql, [$lastGoodStatusCode, $user]);
    }
    
    /**
     * Return all failed file transfers for current user
     *
     * @param string  $user               name of the user to search transfers for
     * @param integer $lastGoodStatusCode last status code for a sucessful transfer
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return array(Entities)
     */
    public function findSuccessfulForUser($user, $lastGoodStatusCode)
    {
        $sql = 'SELECT * FROM `*PREFIX*b2sharebridge_filecache_status` '
            . 'WHERE `status` <= ? AND `owner` = ?';
        return $this->findEntities($sql, [$lastGoodStatusCode, $user]);
    }
}
