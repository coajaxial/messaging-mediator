<?php

namespace Coajaxial\MessagingMediator;

use Generator;

interface MessagingMediatorInterface
{
    /**
     * @psalm-template TReturn
     * @psalm-param    Generator<mixed, mixed, mixed, TReturn> $ctx
     * @psalm-return   TReturn
     *
     * @return mixed
     */
    public function mediate(Generator $ctx);
}
