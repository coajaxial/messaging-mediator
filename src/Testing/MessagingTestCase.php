<?php

namespace Coajaxial\MessagingMediator\Testing;

use Coajaxial\MessagingMediator\MessagingMediator;
use Generator;
use PHPUnit\Framework\TestCase;

/**
 * @codeCoverageIgnore
 */
abstract class MessagingTestCase extends TestCase
{
    /** @var MessageBusStub */
    private $messageBus;

    /** @var string */
    private $originalName;

    /** @var MessagingMediator */
    private $mediator;

    /**
     * @return mixed
     * @noinspection PhpUnused
     */
    final public function wrapTestExecution()
    {
        /** @var Generator|mixed $result */
        $result = $this->{$this->originalName}(...func_get_args());

        if ($result instanceof Generator) {
            return $this->mediator->mediate($result);
        }

        return $result;
    }

    /** @return mixed */
    final protected function runTest()
    {
        $this->originalName = $this->getName(false);
        $this->setName('wrapTestExecution');

        try {
            /** @var mixed $result */
            $result = parent::runTest();
            if (!$this->isExceptionExpected()) {
                $this->messageBus->ensureNoUnhandledMessagesLeft();
            }
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
        $this->mediator->mediate($this->setUpContext());
        $this->messageBus->clearUnhandledMessages();
    }

    protected function setUpContext(): Generator
    {
        yield from [];
    }

    final protected function clearUnhandledMessages(): void
    {
        $this->messageBus->clearUnhandledMessages();
    }

    final protected function popUnhandledMessage(): object
    {
        return $this->messageBus->popUnhandledMessage();
    }

    private function isExceptionExpected(): bool
    {
        return $this->getExpectedException() !== null
            || $this->getExpectedExceptionCode() !== null
            || $this->getExpectedExceptionMessage() !== null
            || $this->getExpectedExceptionMessageRegExp() !== null;
    }
}
