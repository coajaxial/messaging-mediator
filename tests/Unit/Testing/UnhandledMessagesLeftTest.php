<?php

namespace Coajaxial\MessagingMediator\Test\Unit\Testing;

use Coajaxial\MessagingMediator\Testing\UnhandledMessagesLeft;
use PHPUnit\Framework\TestCase;
use stdClass;

class UnhandledMessagesLeftTest extends TestCase
{
    /**
     * @covers \Coajaxial\MessagingMediator\Testing\UnhandledMessagesLeft::messages
     */
    public function test_it_will_print_messages(): void
    {
        $SUT = UnhandledMessagesLeft::messages(new stdClass(), new stdClass());
        self::assertStringContainsString(stdClass::class, $SUT->getMessage());
    }
}
