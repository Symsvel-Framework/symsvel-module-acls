<?php

declare(strict_types=1);

namespace SymSvel\Module\Acls\Tests\Unit;

use Doctrine\ORM\Mapping\ClassMetadata;
use PHPUnit\Framework\TestCase;
use SymSvel\Module\Acls\Entity\AclLabelOverride;

final class AclLabelOverrideTest extends TestCase
{
    public function testMappingsUseCompositePrimaryKeyWithoutInventedId(): void
    {
        $metadata = new ClassMetadata(AclLabelOverride::class);
        $driver = new \Doctrine\ORM\Mapping\Driver\AttributeDriver([
            dirname((new \ReflectionClass(AclLabelOverride::class))->getFileName()),
        ]);
        $driver->loadMetadataForClass(AclLabelOverride::class, $metadata);
        self::assertSame('symsvel_acl_labels', $metadata->getTableName());
        self::assertEqualsCanonicalizing(['resource', 'action'], $metadata->getIdentifierFieldNames());
        self::assertSame(150, $metadata->getFieldMapping('resource')->length);
        self::assertSame(100, $metadata->getFieldMapping('action')->length);
        self::assertSame(150, $metadata->getFieldMapping('label')->length);
    }
}
