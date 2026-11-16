<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Tests\Unit;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use PHPUnit\Framework\TestCase;
use SymSvel\Module\Acls\Entity\AclLabelOverride;

final class AclLabelOverrideTest extends TestCase
{
    public function testMappingsUseCompositePrimaryKeyWithoutInventedId(): void
    {
        if (!extension_loaded('pdo_sqlite')) self::markTestSkipped('pdo_sqlite required.');
        $config = ORMSetup::createAttributeMetadataConfiguration([
            dirname((new \ReflectionClass(AclLabelOverride::class))->getFileName()),
        ], true);
        $config->enableNativeLazyObjects(true);
        $manager = new EntityManager(DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]), $config);
        $metadata = $manager->getClassMetadata(AclLabelOverride::class);
        self::assertSame('symsvel_acl_labels', $metadata->getTableName());
        self::assertEqualsCanonicalizing(['resource', 'action'], $metadata->getIdentifierFieldNames());
        self::assertSame(150, $metadata->getFieldMapping('resource')->length);
        self::assertSame(100, $metadata->getFieldMapping('action')->length);
        self::assertSame(150, $metadata->getFieldMapping('label')->length);
    }
}
