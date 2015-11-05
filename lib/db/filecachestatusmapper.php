<?php
/**
 * ownCloud - eudat
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE file.
 *
 * @author EUDAT <b2drop-devel@postit.csc.fi>
 * @copyright EUDAT 2015
 */

namespace OCA\Eudat\Db;

use OCP\IDBConnection;
use OCP\AppFramework\Db\Mapper;

class FilecacheStatusMapper extends Mapper {

    public function __construct(IDBConnection $db) {
        parent::__construct($db, 'eudat_filecache_status', '\OCA\Eudat\Db\FilecacheStatus');
    }


    /**
     * @throws \OCP\AppFramework\Db\DoesNotExistException if not found
     * @throws \OCP\AppFramework\Db\MultipleObjectsReturnedException if more than one result
     */
    public function find($id) {
        $sql = 'SELECT * FROM `*PREFIX*eudat_filecache_status` ' . 'WHERE `id` = ?';
        return $this->findEntity($sql, [$id]);
    }


    // public function findAll($limit=null, $offset=null) {
    //     $sql = 'SELECT * FROM `*PREFIX*eudat_filecache_status`';
    //     return $this->findEntities($sql, $limit, $offset);
    // }

    public function findAll() {
        $sql = 'SELECT * FROM *PREFIX*eudat_filecache_status';
        return $this->findEntities($sql);
    }

    public function findAllForUser($user) {
        $sql = 'SELECT * FROM `*PREFIX*eudat_filecache_status` ' . 'WHERE `owner` = ?';
        return $this->findEntities($sql, [$user]);
    }

    // public function authorNameCount($name) {
    //     $sql = 'SELECT COUNT(*) AS `count` FROM `*PREFIX*eudat_filecache_status` ' .
    //         'WHERE `name` = ?';
    //     $stmt = $this->execute($sql, [$name]);

    //     $row = $stmt->fetch();
    //     $stmt->closeCursor();
    //     return $row['count'];
    // }

}