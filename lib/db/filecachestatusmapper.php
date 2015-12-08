<?php
/**
 * ownCloud - b2sharebridge
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright EUDAT 2015
 */

namespace OCA\B2shareBridge\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class FilecacheStatusMapper extends Mapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'b2sharebridge_filecache_status', '\OCA\B2shareBridge\Db\FilecacheStatus');
    }


    /**
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*b2sharebridge_filecache_status` ' . 'WHERE `id` = ?';
        return $this->findEntity($sql, [$id]);
    }


    // public function findAll($limit=null, $offset=null) {
    //     $sql = 'SELECT * FROM `*PREFIX*b2sharebridge_filecache_status`';
    //     return $this->findEntities($sql, $limit, $offset);
    // }

    public function findAll() {
        $sql = 'SELECT * FROM *PREFIX*b2sharebridge_filecache_status';
        return $this->findEntities($sql);
    }

    public function findAllForUser($user) {
        $sql = 'SELECT * FROM `*PREFIX*b2sharebridge_filecache_status` ' . 'WHERE `owner` = ?';
        return $this->findEntities($sql, [$user]);
    }

    // public function authorNameCount($name) {
    //     $sql = 'SELECT COUNT(*) AS `count` FROM `*PREFIX*b2sharebridge_filecache_status` ' .
    //         'WHERE `name` = ?';
    //     $stmt = $this->execute($sql, [$name]);

    //     $row = $stmt->fetch();
    //     $stmt->closeCursor();
    //     return $row['count'];
    // }

}