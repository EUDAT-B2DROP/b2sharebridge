<?php

declare(strict_types=1);

namespace OCA\B2shareBridge\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version010100Date20200130075759 extends SimpleMigrationStep
{

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
        /**
    * @var ISchemaWrapper $schema 
*/
        $schema = $schemaClosure();

        if (!$schema->hasTable('b2sharebridge_status')) {
            $table = $schema->createTable('b2sharebridge_status');
            $table->addColumn(
                'id', 'bigint', [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 64,
                ]
            );
            $table->addColumn(
                'owner', 'string', [
                'notnull' => true,
                'length' => 64,
                'default' => '',
                ]
            );
            $table->addColumn(
                'status', 'integer', [
                'notnull' => true,
                'length' => 4,
                ]
            );
            $table->addColumn(
                'title', 'string', [
                'notnull' => false,
                'length' => 255,
                ]
            );
            $table->addColumn(
                'created_at', 'integer', [
                'notnull' => false,
                ]
            );
            $table->addColumn(
                'updated_at', 'integer', [
                'notnull' => false,
                ]
            );
            $table->addColumn(
                'url', 'string', [
                'notnull' => true,
                'default' => '',
                ]
            );
            $table->addColumn(
                'error_message', 'string', [
                'notnull' => true,
                'default' => '',
                ]
            );
            $table->setPrimaryKey(['id']);
        }

        if (!$schema->hasTable('b2sharebridge_communities')) {
            $table = $schema->createTable('b2sharebridge_communities');
            $table->addColumn(
                'id', 'string', [
                'notnull' => true,
                'length' => 36,
                ]
            );
            $table->addColumn(
                'name', 'string', [
                'notnull' => true,
                'length' => 64,
                ]
            );
        }

        if (!$schema->hasTable('b2sharebridge_file')) {
            $table = $schema->createTable('b2sharebridge_file');
            $table->addColumn(
                'id', 'bigint', [
                'autoincrement' => true,
                'notnull' => true,
                'length' => 64,
                ]
            );
            $table->addColumn(
                'deposit_status_id', 'bigint', [
                'notnull' => true,
                'length' => 64,
                'default' => 0,
                ]
            );
            $table->addColumn(
                'fileid', 'integer', [
                'notnull' => true,
                'length' => 4,
                ]
            );
            $table->addColumn(
                'filename', 'string', [
                'notnull' => false,
                'length' => 255,
                ]
            );
            $table->setPrimaryKey(['id']);
        }
        return $schema;
    }

    /**
     * @param IOutput $output
     * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
     * @param array   $options
     */
    public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options)
    {
    }
}
