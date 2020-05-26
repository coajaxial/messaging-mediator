<?php

namespace Coajaxial\MessagingMediator\Test\Integration\Testing;

use Coajaxial\MessagingMediator\Testing\HandlerStub;
use Coajaxial\MessagingMediator\Testing\MessagingTestCase;
use Coajaxial\MessagingMediator\Testing\UnhandledMessagesLeft;
use Generator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestFailure;
use PHPUnit\Framework\TestResult;
use PHPUnit\Framework\TestSuite;
use stdClass;
use UnderflowException;

class MessagingTestCaseTest extends TestCase
{
    /** @var TestSuite */
    private $testSuite;

    public function test_it_will_run_normal_test_like_usual(): void
    {
        $test = new class('test') extends MessagingTestCase {
            public function test(): void
            {
                self::assertTrue(true);
            }
        };

        $this->testSuite->addTest($test);
        $result = $this->testSuite->run();

        self::assertRunWasSuccessful($result);
    }

    public function test_it_will_run_normal_test_with_parameters_like_usual(): void
    {
        $test = new class('test', [42, 'Hello World'], 'test') extends MessagingTestCase {
            public function test(int $i, string $j): void
            {
                self::assertSame(42, $i);
                self::assertSame('Hello World', $j);
            }
        };

        $this->testSuite->addTest($test);
        $result = $this->testSuite->run();

        self::assertRunWasSuccessful($result);
    }

    public function test_it_will_fail_if_unhandled_messages_are_left(): void
    {
        $test = new class('test') extends MessagingTestCase {
            public function test(): Generator
            {
                yield new stdClass();
            }
        };

        $this->testSuite->addTest($test);
        $result = $this->testSuite->run();

        self::assertFalse($result->wasSuccessful());
        self::assertEquals(1, $result->errorCount());
        self::assertStringContainsString(UnhandledMessagesLeft::class, $result->errors()[0]->getExceptionAsString());
    }

    public function test_it_will_clear_all_unhandled_messages_of_setUpDomain(): void
    {
        $test = new class('test') extends MessagingTestCase {
            public function test(): void
            {
                $this->expectException(UnderflowException::class);
                $this->messageBus->popUnhandledMessage();
            }

            protected function setUpContext(): Generator
            {
                yield new stdClass();
            }
        };

        $this->testSuite->addTest($test);
        $result = $this->testSuite->run();

        self::assertRunWasSuccessful($result);
    }

    public function test_it_will_mediate_tests_that_return_a_generator(): void
    {
        $test = new class('test') extends MessagingTestCase {
            public function test(): Generator
            {
                $stub = $this->createMock(HandlerStub::class);

                $stub->method('matches')->willReturn(true);
                $stub->expects(self::once())->method('invoke');

                $this->messageBus->activate($stub);

                yield new stdClass();
            }
        };

        $this->testSuite->addTest($test);
        $result = $this->testSuite->run();

        self::assertRunWasSuccessful($result);
    }

    protected function setUp(): void
    {
        $this->testSuite = new TestSuite();
    }

    private static function assertRunWasSuccessful(TestResult $result): void
    {
        $convertToString = static function (TestFailure $error): string {
            return $error->toString();
        };

        self::assertTrue(
            $result->wasSuccessful(),
            sprintf(
                "Failed asserting that run was successful. Errors(%d), Failures(%d):".PHP_EOL."%s".PHP_EOL."%s",
                $result->errorCount(),
                $result->failureCount(),
                implode(PHP_EOL, array_map($convertToString, $result->errors())),
                implode(PHP_EOL, array_map($convertToString, $result->failures())),
            )
        );
    }
}
