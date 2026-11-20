<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/** Additive, multibase storage of ACL label overrides. Existing rule schema is immutable. */
final class Version20261116000201 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $table = $schema->createTable('symsvel_acl_labels');
        $table->addColumn('resource', 'string', ['length' => 150]);
        $table->addColumn('action', 'string', ['length' => 100]);
        $table->addColumn('label', 'string', ['length' => 150]);
        $table->setPrimaryKey(['resource', 'action']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('symsvel_acl_labels');
    }
}
