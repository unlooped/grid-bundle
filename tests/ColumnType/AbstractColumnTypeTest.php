<?php

namespace Unlooped\GridBundle\Tests\ColumnType;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Unlooped\GridBundle\Tests\Fixtures\TestColumnType;

final class AbstractColumnTypeTest extends TestCase
{
    public function testIsVisible(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getRoles')->willReturn(['ADMIN', 'DEMO_ROLE']);

        $columnType = new TestColumnType('demo', [
            'permissions' => ['DEMO_ROLE'],
        ]);
        static::assertTrue($columnType->isVisible($user));
    }

    public function testIsAlwaysVisible(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->expects(static::never())->method('getRoles');

        $columnType = new TestColumnType('demo');
        static::assertTrue($columnType->isVisible($user));
    }

    public function testIsNotVisibleForGuests(): void
    {
        $columnType = new TestColumnType('demo', [
            'permissions' => ['DEMO_ROLE'],
        ]);
        static::assertFalse($columnType->isVisible(null));
    }

    public function testIsNotVisible(): void
    {
        $user = $this->createMock(UserInterface::class);
        $user->method('getRoles')->willReturn([]);

        $columnType = new TestColumnType('demo', [
            'permissions' => ['DEMO_ROLE'],
        ]);
        static::assertFalse($columnType->isVisible($user));
    }
}
