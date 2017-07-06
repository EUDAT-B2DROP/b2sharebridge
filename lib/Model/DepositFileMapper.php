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

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\Mapper;
use OCP\IDBConnection;
use OCP\Util;

/**
 * Work on a database table
 *
 * @category Nextcloud
 * @package  B2shareBridge
 * @author   EUDAT <b2drop-devel@postit.csc.fi>
 * @license  AGPL3 https://github.com/EUDAT-B2DROP/b2sharebridge/blob/master/LICENSE
 * @link     https://github.com/EUDAT-B2DROP/b2sharebridge.git
 */
class DepositFileMapper extends Mapper
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
            'b2sharebridge_deposit_file',
            '\OCA\B2shareBridge\Model\DepositFile'
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
     * Return all files for a deposit
     *
     * @param string $depositId the id of the deposit to search transfers for
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return array(Entities)
     */
    public function findAllForDeposit($depositId)
    {
        $sql = 'SELECT * FROM `' . $this->tableName . '` WHERE `deposit_status_id` = ?';
        return $this->findEntities($sql, [$depositId]);
    }
	
    /**
     * Return file count for a deposit
     *
     * @param string $depositId the id of the deposit to search transfers for
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return array(Entities)
     */
    public function getFileCount($depositId)
    {
        $sql = 'SELECT count(*) FROM `' . $this->tableName . '` WHERE `deposit_status_id` = ?';
		return $this->execute($sql, [$depositId])->fetchColumn();
    }
	
    /**
     * Return the number of files belonging to a given deposit
     *
     * @param string  $deposit       name of the user to search transfers for
     *
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more th one
     *
     * @return int number files in a deposit
     */
    public function findCountForUser($depositId)
    {
        $sql = 'SELECT COUNT(*) FROM `' . $this->tableName
            . '` WHERE depositStatusId = ?';
        return $this->execute($sql, [$depositId])->fetchColumn();
    }


  
}
