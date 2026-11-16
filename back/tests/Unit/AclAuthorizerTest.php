<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Tests\Unit;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use SymSvel\Module\Acls\AclAuthorizer;

final class AclAuthorizerTest extends TestCase
{
    public function testNativeRolesAndExplicitGrants(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is required.');
        }
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $db->executeStatement('CREATE TABLE symsvel_acl_rules (id INTEGER PRIMARY KEY, resource VARCHAR(150), action VARCHAR(100), role_code VARCHAR(100), allowed BOOLEAN)');
        $acl = new AclAuthorizer($db);

        self::assertTrue($acl->allows('ROLE_DEV', 'admins', 'create'));
        self::assertTrue($acl->allows('ROLE_DEV', 'settings', 'system', true));
        self::assertTrue($acl->allows('ROLE_SUPER_ADMIN', 'admins', 'create'));
        self::assertFalse($acl->allows('ROLE_SUPER_ADMIN', 'settings', 'system', true));
        self::assertFalse($acl->allows('ROLE_ADMIN', 'admins', 'create'));
        self::assertFalse($acl->allows('ROLE_ADMIN', 'roles', 'assign:ROLE_SUPER_ADMIN'));
        self::assertFalse($acl->allows('bad', 'admins', 'create'));
        self::assertFalse($acl->allows('ROLE_ADMIN', '', 'create'));

        $db->insert('symsvel_acl_rules', [
            'resource' => 'admins', 'action' => 'create', 'role_code' => 'ROLE_ADMIN', 'allowed' => true,
        ]);
        $db->insert('symsvel_acl_rules', [
            'resource' => 'admins', 'action' => 'resend', 'role_code' => 'ROLE_ADMIN', 'allowed' => false,
        ]);
        self::assertTrue($acl->allows('ROLE_ADMIN', 'admins', 'create'));
        self::assertFalse($acl->allows('ROLE_ADMIN', 'admins', 'resend'));
    }

    public function testMissingPermissionNeverBecomesImplicitGrant(): void
    {
        if (!extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is required.');
        }
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $db->executeStatement('CREATE TABLE symsvel_acl_rules (resource VARCHAR(150), action VARCHAR(100), role_code VARCHAR(100), allowed BOOLEAN)');
        self::assertFalse((new AclAuthorizer($db))->allows('ROLE_EDITOR', 'admins', 'create'));
    }
}
