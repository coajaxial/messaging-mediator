<?php

namespace Coajaxial\MessagingMediator\Test\Unit;

use Coajaxial\MessagingMediator\IncomprehensibleMessage;
use PHPUnit\Framework\TestCase;

class IncomprehensibleMessageTest extends TestCase
{
    /**
     * @covers \Coajaxial\MessagingMediator\IncomprehensibleMessage::notAnObject
     */
    public function test_it_will_print_message(): void
    {
        $e = IncomprehensibleMessage::notAnObject(3);

        self::assertStringContainsString('3', $e->getMessage());
    }
}
