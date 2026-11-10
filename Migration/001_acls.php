<?php

declare(strict_types=1);

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Immutable module migration compiled by SymSvel Tooling. */
final class AclsInitialMigration extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('symsvel_acl_rules');
        $table->addColumn('id', 'integer', ['autoincrement' => true]);
        $table->addColumn('resource', 'string', ['length' => 150]);
        $table->addColumn('action', 'string', ['length' => 100]);
        $table->addColumn('role_code', 'string', ['length' => 100]);
        $table->addColumn('allowed', 'boolean', ['default' => false]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['resource', 'action', 'role_code'], 'uniq_symsvel_acl_rule');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('symsvel_acl_rules');
    }
}
