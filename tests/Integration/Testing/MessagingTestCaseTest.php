<?php

namespace Coajaxial\MessagingMediator\Test\Integration\Testing;

use Coajaxial\MessagingMediator\Testing\HandlerStub;
use Coajaxial\MessagingMediator\Testing\MessagingTestCase;
use Coajaxial\MessagingMediator\Testing\UnhandledMessagesLeft;
use Generator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestSuite;
use stdClass;

class MessagingTestCaseTest extends TestCase
{
    /** @var TestSuite */
    private $testSuite;

    protected function setUp(): void
    {
        $this->testSuite = new TestSuite();
    }

    public function test_it_will_run_normal_test_like_usual(): void
    {
        $this->testSuite->addTest(
            new class('test') extends MessagingTestCase {
                public function test(): void
                {
                    self::assertTrue(true);
                }
            }
        );

        $result = $this->testSuite->run();

        self::assertTrue($result->wasSuccessful());
    }

    public function test_it_will_fail_if_unhandled_messages_are_left(): void
    {
        $this->testSuite->addTest(
            new class('test') extends MessagingTestCase {
                public function test(): Generator
                {
                    yield new stdClass();
                }
            }
        );

        $result = $this->testSuite->run();

        self::assertFalse($result->wasSuccessful());
        self::assertEquals(1, $result->errorCount());
        self::assertStringContainsString(UnhandledMessagesLeft::class, $result->errors()[0]->getExceptionAsString());
    }

    public function test_it_will_mediate_tests_that_return_a_generator(): void
    {
        $this->testSuite->addTest(
            new class('test') extends MessagingTestCase {
                public function test(): Generator
                {
                    $stub = $this->createMock(HandlerStub::class);

                    $stub->method('matches')->willReturn(true);
                    $stub->expects(self::once())->method('invoke');

                    $this->messageBus->activate($stub);

                    yield new stdClass();
                }
            }
        );

        $result = $this->testSuite->run();

        self::assertTrue($result->wasSuccessful());
    }
}
