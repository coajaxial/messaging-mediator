<?php

namespace Coajaxial\MessagingMediator\Testing;

use Coajaxial\MessagingMediator\MessagingMediator;
use Generator;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\TestCase;

/**
 * @codeCoverageIgnore This can only be tested with an integration test
 * @see                \Coajaxial\MessagingMediator\Test\Integration\Testing\MessagingTestCaseTest
 */
abstract class MessagingTestCase extends TestCase
{
    /** @var MessageBusStub */
    protected $messageBus;

    /** @var string */
    private $originalName;

    /** @var MessagingMediator */
    private $mediator;

    /**
     * @return mixed|void
     * @noinspection PhpUnused
     */
    public function wrapTestExecution()
    {

        /** @var Generator|mixed|void $result */
        $result = call_user_func_array([$this, $this->originalName], func_get_args());

        if ($result instanceof Generator) {
            /**
             * @var mixed|void $result
             * @psalm-suppress MixedArgumentTypeCoercion
             */
            $result = $this->mediator->mediate($result);
        }

        return $result;
    }

    /** @return mixed|void */
    protected function runTest()
    {
        /** @psalm-suppress InternalMethod */
        $this->originalName = $this->getName(false);
        /** @psalm-suppress InternalMethod */
        $this->setName('wrapTestExecution');

        try {
            /** @var mixed|void $result */
            $result = parent::runTest();
        } finally {
            /** @psalm-suppress InternalMethod */
            $this->setName($this->originalName);
        }

        return $result;
    }

    final protected function setUp(): void
    {
        $this->messageBus = new MessageBusStub();
        $this->mediator   = new MessagingMediator($this->messageBus);

        /** @var mixed|void $result */
        $result = $this->setUpDomain();

        if ($result instanceof Generator) {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $this->mediator->mediate($result);
        }
    }

    /** @return mixed|void */
    protected function setUpDomain()
    {
    }

    final protected function tearDown(): void
    {
        /** @psalm-suppress InternalMethod */
        $expectedException   = $this->getExpectedException();
        $noExceptionExpected = $expectedException === null || $expectedException === AssertionFailedError::class;

        if ($noExceptionExpected) {
            $this->messageBus->ensureNoUnhandledMessagesLeft();
        }
    }

    /** @return mixed|void */
    protected function tearDownDomain()
    {
    }
}
