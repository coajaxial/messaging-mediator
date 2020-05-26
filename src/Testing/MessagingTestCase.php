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
            /** @psalm-suppress MixedArgumentTypeCoercion */
            return $this->mediator->mediate($result);
        }

        return $result;
    }

    /** @return mixed|void */
    protected function runTest()
    {
        $this->originalName = $this->getName(false);
        $this->setName('wrapTestExecution');

        try {
            /** @var mixed|void $result */
            $result = parent::runTest();
        } finally {
            $this->setName($this->originalName);
        }

        return $result;
    }

    /**
     * @before
     * @noinspection PhpUnused
     */
    final protected function beforeTest(): void
    {
        $this->messageBus = new MessageBusStub();
        $this->mediator   = new MessagingMediator($this->messageBus);

        /** @var Generator|mixed|void $result */
        $result = $this->setUpContext();

        if ($result instanceof Generator) {
            /** @psalm-suppress MixedArgumentTypeCoercion */
            $this->mediator->mediate($result);
        }

        $this->messageBus->clearUnhandledMessages();
    }

    protected function setUpContext(): Generator
    {
        yield from [];
    }

    /**
     * @after
     * @noinspection PhpUnused
     */
    final protected function afterTest(): void
    {
        $expectedException   = $this->getExpectedException();
        $noExceptionExpected = $expectedException === null || $expectedException === AssertionFailedError::class;

        if ($noExceptionExpected) {
            $this->messageBus->ensureNoUnhandledMessagesLeft();
        }
    }
}
