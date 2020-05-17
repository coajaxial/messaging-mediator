<?php

namespace Coajaxial\MessagingMediator\Testing;

interface HandlerStub
{
    public function matches(object $message): bool;

    /** @return mixed|null */
    public function invoke(object $message);
}
