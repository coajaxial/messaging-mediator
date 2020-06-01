<p align="center">
  <img src="https://raw.githubusercontent.com/coajaxial/messaging-mediator/master/res/logo.png" alt="Messaging Mediator" title="Messaging Mediator">
</p>

![Psalm](https://github.com/coajaxial/messaging-mediator/workflows/Psalm/badge.svg)
![Unit tests](https://github.com/coajaxial/messaging-mediator/workflows/Unit%20tests/badge.svg)
![Integration tests](https://github.com/coajaxial/messaging-mediator/workflows/Integration%20tests/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/coajaxial/messaging-mediator/v/stable)](https://packagist.org/packages/coajaxial/messaging-mediator)
[![Total Downloads](https://poser.pugx.org/coajaxial/messaging-mediator/downloads)](https://packagist.org/packages/coajaxial/messaging-mediator)
[![License](https://poser.pugx.org/coajaxial/messaging-mediator/license)](https://packagist.org/packages/coajaxial/messaging-mediator)

The messaging mediator hooks into your message bus, giving you the ability
to `yield` messages from your application and domain layer, including message
handlers, aggregates, value objects, domain services, etc.

Publish domain events, dispatch commands and issue queries and get their results 
without any dependency to your message bus.

<!--ts-->
   * [Installation](#installation)
      * [Symfony messenger component (manually)](#symfony-messenger-component-manually)
   * [Use cases](#use-cases)
      * [Publish domain events](#publish-domain-events)
      * [Execute commands](#execute-commands)
      * [Issue queries to enforce <em>soft</em> business rules](#issue-queries-to-enforce-soft-business-rules)
   * [Motivation](#motivation)
   * [Contribute](#contribute)
      * [Build docker image](#build-docker-image)
      * [Load shell aliases](#load-shell-aliases)
<!--te-->

# Installation

> :warning: **This library has no stable release! It currently only provides
> a middleware for [Symfony's messenger component](https://symfony.com/doc/current/components/messenger.html) 
> and testing aids for [PHPUnit](https://phpunit.de/).**

```shell script
composer require coajaxial/messaging-mediator:@dev
```

Next, you need to configure your message bus to use the mediator. 

## Symfony messenger component (manually)

```php
<?php

use Coajaxial\MessagingMediator\Adapter\Messenger\MessageBusAdapter;
use Coajaxial\MessagingMediator\Adapter\Messenger\MessagingMediatorMiddleware;
use Coajaxial\MessagingMediator\MessagingMediator;
use Coajaxial\MessagingMediator\Testing\LazyMessageBus;
use Symfony\Component\Messenger\Handler\HandlersLocator;
use Symfony\Component\Messenger\MessageBus;
use Symfony\Component\Messenger\Middleware\HandleMessageMiddleware;

$mediatorBus = new LazyMessageBus();

$mediator = new MessagingMediator($mediatorBus);

$bus = new MessageBus(
    [
        // The messaging mediator middleware should be right 
        // before the handle message middleware
        new MessagingMediatorMiddleware($mediator),
        new HandleMessageMiddleware(
            new HandlersLocator(
                [
                    // Your handler configuration
                ]
            )
        ),
    ]
);

$mediatorBus->initialize(new MessageBusAdapter($bus));

$bus->dispatch(new MyMessage());
```

# Use cases

## Publish domain events

Publish domain events from your aggregates by `yield`ing domain event instances.

```php
<?php 

class MyAggregate 
{
    public static function init(): Generator 
    {
        yield new MyAggregateInited();

        return new self();
    }
}

class MyHandler 
{
    public function __invoke(MyCommand $command): Generator 
    {
        $agg = yield from MyAggregate::init();
    }
}
```

## Execute commands

This is useful for domain event subscribers or long running processes (sagas). Just yield a command instance and you are done.

```php
<?php

/**
 * Give the user 100 starting credits when he signs up before 2020-01-01
 */
class StartingCreditListener 
{
    public function __invoke(UserSignedUp $event): Generator 
    {
        if ( $event->getPublishedAt() < DateTimeImmutable::createFromFormat('Y-m-d', '2021-01-01') ) {
            yield new ChargeAccount($event->getUserId(), 100);
        }       
    }
}
```

> :information_source: **You can use `try ... catch` around the `yield` statement to catch exceptions happening 
> during command execution.**

## Issue queries to enforce *soft* business rules

You can issue queries and get it's result to enforce some business constraints
that don't need to be transactional consistent. Just `yield` a query object and the
mediator will send the result back to the `Generator`.

```php
<?php

class Post 
{
    public function publish(): Generator 
    {
        $numberOfPublishedPostsToday = yield new NumberOfPublishedPostsTodayByAuthor($this->authorId);

        if ( $numberOfPublishedPostsToday >= 3 ) {
            throw new DomainException('Number of maximum posts per day reached.');
        }
    }
}

class PublishPostHandler 
{
    public function __invoke(MyCommand $command): Generator 
    {
        $post = new Post(); // Usually from the repository

        yield from $post->publish();
    }
}
```

> :warning: **Be absolutely sure you are enforcing a **soft** business rule!**
>
> Queries are usually eventual consistent, so the result may not be 100%
> true by the time issuing the query. 
>
> In the example above, domain experts are ok with the fact that there may 
> be more than 3 published posts per author and day in some (negligible) 
> circumstances.

# Motivation

When I first implemented domain events for my domain model, I stored all events
in a collection, that could be retrieved and cleared. It looked something like
this:

```php
<?php

class MyAggregate {
    /** @var object[] */
    private $events = [];

    public function doSomething(): void {
        // ...
        $this->events = new SomethingHappend();
    }
    
    /** @return object[] */
    public function getEvents(): array {
        return $this->events;
    }

    public function clearEvents(): void {
        $this->events = [];
    }
}
```

This works quite well, but every aggregate needed this boilerplate, so I had to
put this boilerplate either into a super-`class`, which all of my aggregates
extended, or into a `trait`.

There are some other techniques to implement domain events, for example you can
`return` them, like so:

```php
<?php

class MyAggregate {
    public function doSomething(): array {
        // ...
        return [
            new SomethingHappened()
        ];       
    }   
}
```

With this approach you have no more boilerplate, but you also loose the ability
to return normal values, like for example the `self` instance for named
constructors, or some calculated values.

So this project is my solution to do it, and has several advantages:

- Absolutely no boilerplate, no need to extend a super-class or use a trait
- You can still return normal values
- You can use this method to do any kind of messaging (commands and queries)

# Contribute

## Build docker image

```shell script
docker build -t coajaxial/messaging-mediator .
```

## Load shell aliases

There is a shell aliases file that you can `source` to import some useful
aliases, e.g. `composer` running from within the docker container 
(coajaxial/messaging-mediator)

```shell script
source .aliases
```
