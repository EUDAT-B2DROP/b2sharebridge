<?php

declare(strict_types=1);

namespace OCA\B2shareBridge\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version010100Date20200324092922 extends SimpleMigrationStep
{

    public function __construct(IDBConnection $db)
    {
        $this->db = $db;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array   $options
     */
    public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
    {
    }

    /**
     * @param  IOutput $output
     * @param  Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param  array   $options
     * @return null|ISchemaWrapper
     */
    public function changeSchema(IOutput $output, Closure $schemaClosure, array $options)
    {
        $schema = $schemaClosure();

        $cTable = $schema->getTable('b2sharebridge_communities');
        $cTable->addColumn(
            'server_id', 'integer', [
            'notnull' => true,
            ]
        );
        $sTable = $schema->getTable('b2sharebridge_status');
        $sTable->addColumn(
            'server_id', 'integer', [
            'notnull' => true,
            ]
        );
        return $schema;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array   $options
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
    {
        $logger = \OC::$server->getLogger();

        // get old B2SHARE URL
        $q = $this->db->getQueryBuilder();
        $q->select('a.configvalue')
            ->from('appconfig', 'a')
            ->where('a.appid =' . $q->createParameter('appid'))
            ->andWhere('a.configkey =' . $q->createParameter('configkey'))
            ->setParameter('appid', 'b2sharebridge')
            ->setParameter('configkey', 'publish_baseurl');
        $arr = $q->execute()->fetch();

        if ($arr) {
            // create row in new server table for old URL
            $q = $this->db->getQueryBuilder();
            $q->insert('b2sharebridge_server')
                ->values(
                    array(
                    'name' => $q->createParameter('server_name'),
                    'publish_url' => $q->createParameter('publishUrl'))
                )
                ->setParameter('server_name', 'unspecified')
                ->setParameter('publishUrl', $arr['configvalue'])
                ->execute();

            // delete old URL config value
            $q = $this->db->getQueryBuilder();
            $q->delete('appconfig')
                ->where('appid =' . $q->createParameter('appid'))
                ->andWhere('configkey =' . $q->createParameter('configkey'))
                ->setParameter('appid', 'b2sharebridge')
                ->setParameter('configkey', 'publish_baseurl')
                ->execute();

            // get id of newly added server
            $q = $this->db->getQueryBuilder();
            $id = $q->select('id')
                ->from('b2sharebridge_server')
                ->execute()
                ->fetch()['id'];

            // append API key configkeys with server id
            $q = $this->db->getQueryBuilder();
            $q->update('preferences')
                ->set('configkey', $q->createParameter('newConfigkey'))
                ->where('configkey = '. $q->createParameter('configkey'))
                ->andWhere('appid = '.$q->createParameter('appid'))
                ->setParameter('configkey', 'token')
                ->setParameter('appid', 'b2sharebridge')
                ->setParameter('newConfigkey', 'token_'.$id)
                ->execute();

            // add server ID to communities
            $q = $this->db->getQueryBuilder();
            $q->update('b2sharebridge_communities')
                ->set('server_id', $q->createParameter('server_id'))
                ->setParameter('server_id', $id)
                ->execute();
        }
    }
}
