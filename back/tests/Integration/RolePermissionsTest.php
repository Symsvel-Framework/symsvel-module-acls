<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Tests\Integration;

use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use SymSvel\Module\Acls\Access\BoAccessGate;
use SymSvel\Module\Acls\Access\BoAccessDecision;
use SymSvel\Module\Acls\AclAuthorizer;
use SymSvel\Module\Acls\Controller\BoAclController;
use SymSvel\Module\Acls\Permission\NativeAclPermissionProvider;
use SymSvel\Module\Acls\Permission\PermissionCatalog;
use SymSvel\Module\Acls\Permission\PermissionDefinition;
use SymSvel\Module\Acls\Permission\PermissionProviderInterface;
use SymSvel\Module\Acls\Permission\RolePermissionManager;

final class RolePermissionsTest extends TestCase
{
    public function testDeclaredCatalogAndAtomicGrantReplacement(): void
    {
        if (!extension_loaded('pdo_sqlite')) self::markTestSkipped('pdo_sqlite required.');
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $db->executeStatement('CREATE TABLE symsvel_roles (code VARCHAR(100) PRIMARY KEY)');
        $db->executeStatement('CREATE TABLE symsvel_acl_rules (id INTEGER PRIMARY KEY AUTOINCREMENT, resource VARCHAR(150), action VARCHAR(100), role_code VARCHAR(100), allowed BOOLEAN, UNIQUE(resource,action,role_code))');
        foreach (['ROLE_ADMIN', 'ROLE_EDITOR', 'ROLE_DEV', 'ROLE_SUPER_ADMIN'] as $code) {
            $db->insert('symsvel_roles', ['code' => $code]);
        }
        $provider = new class implements PermissionProviderInterface {
            public function permissions(): iterable {
                yield new PermissionDefinition('admins', 'list', 'Voir les administrateurs');
                yield new PermissionDefinition('roles', 'assign:ROLE_ADMIN', 'Attribuer le rôle admin');
            }
        };
        $catalog = new PermissionCatalog([new NativeAclPermissionProvider(), $provider]);
        self::assertNotNull($catalog->get('roles.assign:ROLE_ADMIN'));
        self::assertTrue($catalog->get('acls.manage')->technical);
        $manager = new RolePermissionManager($db, $catalog);
        $manager->replace('ROLE_EDITOR', ['admins.list', 'roles.assign:ROLE_ADMIN']);
        self::assertSame(['admins.list', 'roles.assign:ROLE_ADMIN'], $manager->granted('ROLE_EDITOR'));
        self::assertTrue((new AclAuthorizer($db))->allows('ROLE_EDITOR', 'admins', 'list'));
        try {
            $manager->replace('ROLE_EDITOR', ['admins.list', 'not.declared']);
            self::fail('Unknown permission accepted');
        } catch (\InvalidArgumentException) {
            self::assertSame(['admins.list', 'roles.assign:ROLE_ADMIN'], $manager->granted('ROLE_EDITOR'));
        }
        $manager->replace('ROLE_EDITOR', []);
        self::assertSame([], $manager->granted('ROLE_EDITOR'));
        self::assertFalse((new AclAuthorizer($db))->allows('ROLE_EDITOR', 'admins', 'list'));
        $this->expectException(\DomainException::class);
        $manager->replace('ROLE_DEV', ['admins.list']);
    }

    public function testControllerRequiresAuthenticationAndTechnicalAclForMutation(): void
    {
        if (!extension_loaded('pdo_sqlite')) self::markTestSkipped('pdo_sqlite required.');
        $db = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $db->executeStatement('CREATE TABLE symsvel_roles (code VARCHAR(100) PRIMARY KEY)');
        $db->insert('symsvel_roles', ['code' => 'ROLE_ADMIN']);
        $db->executeStatement('CREATE TABLE symsvel_acl_rules (id INTEGER PRIMARY KEY AUTOINCREMENT, resource VARCHAR(150), action VARCHAR(100), role_code VARCHAR(100), allowed BOOLEAN)');
        $gate = new class implements BoAccessGate {
            public function check(Request $request, string $resource, string $action): BoAccessDecision {
                if (!$request->headers->has('Authorization')) return new BoAccessDecision(401);
                return new BoAccessDecision(200, 1, 'ROLE_SUPER_ADMIN');
            }
        };
        $catalog = new PermissionCatalog([new NativeAclPermissionProvider()]);
        $controller = new BoAclController($gate, new AclAuthorizer($db), $catalog, new RolePermissionManager($db, $catalog));
        self::assertSame(401, $controller->catalog(Request::create('/apibo/acls'))->getStatusCode());
        $get = Request::create('/apibo/acls', 'GET', [], [], [], ['HTTP_AUTHORIZATION' => 'Bearer some-token']);
        self::assertSame(200, $controller->catalog($get)->getStatusCode());
        self::assertFalse(json_decode((string) $controller->catalog($get)->getContent(), true)['can_manage']);
        $put = Request::create('/apibo/acls/roles/ROLE_ADMIN', 'PUT', [], [], [],
            ['HTTP_AUTHORIZATION' => 'Bearer some-token', 'CONTENT_TYPE' => 'application/json'],
            '{"permissions":["acls.manage"]}');
        self::assertSame(403, $controller->update($put, 'ROLE_ADMIN')->getStatusCode());
    }

    public function testDuplicatePermissionDeclarationFailsClosed(): void
    {
        $provider = new NativeAclPermissionProvider();
        $this->expectException(\LogicException::class);
        new PermissionCatalog([$provider, $provider]);
    }
}
