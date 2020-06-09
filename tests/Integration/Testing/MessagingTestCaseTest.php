<?php

namespace Coajaxial\MessagingMediator\Test\Integration\Testing;

use Coajaxial\MessagingMediator\Testing\MessagingTestCase;
use Generator;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\TestFailure;
use PHPUnit\Framework\TestResult;
use stdClass;

class MessagingTestCaseTest extends TestCase
{
    public function test_it_will_run_void_tests_like_usual(): void
    {
        $test = new class('test') extends MessagingTestCase {
            public function test(): void
            {
                self::assertTrue(true);
            }
        };

        $result = $test->run();

        self::assertRunWasSuccessful($result);
    }

    public function test_it_will_run_failing_void_tests_like_usual(): void
    {
        $test = new class('test') extends MessagingTestCase {
            public function test(): void
            {
                self::assertTrue(false);
            }
        };

        $result = $test->run();

        self::assertRunFailedWithMessage($result, 'Failed asserting that false is true.');
    }

    public function test_it_will_run_void_test_with_parameters_like_usual(): void
    {
        $test = new class('test', [42, 'Hello World'], 'test') extends MessagingTestCase {
            public function test(int $i, string $j): void
            {
                self::assertSame(42, $i);
                self::assertSame('Hello World', $j);
            }
        };

        $result = $test->run();

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

        $result = $test->run();

        self::assertRunFailedWithMessage($result, 'There are/is 1 message(s) left');
    }

    public function test_it_will_not_fail_if_unhandled_messages_are_cleared(): void
    {
        $test = new class('test') extends MessagingTestCase {
            public function test(): Generator
            {
                yield new stdClass();
                $this->clearUnhandledMessages();
            }
        };

        $result = $test->run();

        self::assertRunWasSuccessful($result);
    }

    public function test_it_will_not_fail_if_unhandled_messages_are_popped(): void
    {
        $test = new class('test') extends MessagingTestCase {
            public function test(): Generator
            {
                /** @noinspection PhpUnnecessaryLocalVariableInspection */
                $message = new stdClass();

                yield $message;

                $poppedMessage = $this->popUnhandledMessage();
                self::assertSame($message, $poppedMessage);
            }
        };

        $result = $test->run();

        self::assertRunWasSuccessful($result);
    }

    public function test_it_will_clear_all_unhandled_messages_of_setUpDomain(): void
    {
        $test = new class('test') extends MessagingTestCase {
            public function test(): void
            {
            }

            protected function setUpContext(): Generator
            {
                yield new stdClass();
            }
        };

        $result = $test->run();

        self::assertRunWasSuccessful($result);
    }

    private static function resultErrorsToString(TestResult $result): string
    {
        $string = implode(
            PHP_EOL,
            array_map(
                static function (TestFailure $error): string {
                    return $error->toString();
                },
                array_merge($result->errors(), $result->failures()),
            )
        );

        $string = self::removeNonPrintableCharacters($string);

        return $string;
    }

    private static function assertRunWasSuccessful(TestResult $result): void
    {
        self::assertTrue(
            $result->wasSuccessful(),
            sprintf(
                'Failed asserting that run was successful. Errors: %d, Failures: %d:'.PHP_EOL.'%s',
                $result->errorCount(),
                $result->failureCount(),
                self::resultErrorsToString($result)
            )
        );
    }

    private static function assertRunFailedWithMessage(TestResult $result, string $message): void
    {
        self::assertFalse($result->wasSuccessful());
        self::assertStringContainsString($message, self::resultErrorsToString($result));
    }

    private static function removeNonPrintableCharacters(string $string): string
    {
        return preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
    }
}
