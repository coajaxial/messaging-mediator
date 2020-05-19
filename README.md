# Messaging Mediator

![Unit tests](https://github.com/coajaxial/messaging-mediator/workflows/Unit%20tests/badge.svg)
![Integration tests](https://github.com/coajaxial/messaging-mediator/workflows/Integration%20tests/badge.svg)
[![Latest Stable Version](https://poser.pugx.org/coajaxial/messaging-mediator/v/stable)](https://packagist.org/packages/coajaxial/messaging-mediator)
[![Total Downloads](https://poser.pugx.org/coajaxial/messaging-mediator/downloads)](https://packagist.org/packages/coajaxial/messaging-mediator)
[![License](https://poser.pugx.org/coajaxial/messaging-mediator/license)](https://packagist.org/packages/coajaxial/messaging-mediator)

Send messages directly from your domain model without any dependencies

```php
<?php

class Post
{
    /** @var int */
    private $authorId;

    /** @var bool */
    private $published;

    /** @return Generator<int, object, mixed, self> */
    public static function draft(int $authorId): Generator
    {
        $post = new self();

        $post->authorId = $authorId;
        $post->published = false;

        // Publish events without storing it, returning it or using a dependency
        // to the message bus.
        yield new PostDrafted($authorId);

        return $post;
    }

    /** @return Generator<int, object, mixed, void> */
    public function publish(): Generator
    {
        // Dispatch queries and get the result directly. No need of a service
        // that gets passed as an argument!
        // :warning: But be careful, queries are usually eventual consistent!
        $numberOfPublishedPostsToday = yield new NumberOfPublishedPostsTodayByAuthor($this->authorId);

        if ( $numberOfPublishedPostsToday > 3 ) {
            throw new DomainException('You may only publish less than 3 posts a day.');
        }

        // Use `yield from` to call nested methods that also dispatch messages.
        // `yield from` returns the real return value of a generator (In this
        // case: 42).
        $result = yield from $this->nestedMethod();

        $this->published = true;

        yield new PostPublished($this->authorId, $result);
    }

    private function nestedMethod(): Generator
    {
        $result = yield new SomeQuery();
        yield new NestedMethodCalled($result);

        return 42;
    }

    private function __construct()
    {
    }
}
```

## Quick start

> :warning: This library has no stable release! It currently only provides
> a middleware for [Symfony's messenger component](https://symfony.com/doc/current/components/messenger.html)
> and testing aids for [PHPUnit](https://phpunit.de/).

```shell script
composer require coajaxial/messaging-mediator:@dev
```

## The idea behind this project

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

So I don't know exactly how I came up with this new idea, but it basically is:
`yield`ing the domain events:

```php
<?php

class MyAggregate {
    public function doSomething(): Generator {
        // ...
        yield new SomethingHappened();       
    }   
}
```

This has several advantages:

- Absolutely no boilerplate, no need to extend a super-class or use a trait
- You can still return normal values
- You can extend this method to use it for any kind of messaging (more on that
  later)

## What this library does

The mediator is actually quite simple: it iterates through an invoked generator
and every `yield`ed message is dispatched on a message bus. If the message bus
returns a result, it is sent back to the `Generator`. If the message bus throws
an `Exception`, it is thrown within the `Generator` (so you can even catch it).
The final return value of the handler (if any) is returned by the mediator.

The mediator is usually invoked by a middleware of a message bus. Whenever
a handler is called, the middleware checks if the result of the handler is a
`Generator` instance. If that is the case, it gets mediated. The actual return
value of the mediated handler is given to the message bus as normal.

The mediator builds upon the following features of PHP generators:

*   Generators can be chained using the `yield from` operator
*   If a chained Generator returns a value, `yield from` is returning it:
    ```php
    <?php
    function a(): Generator {
        $result = yield from b();
        assert($result === 10);
    }
    function b(): Generator {
        yield 'a';
        return 10;
    }
    ```
*   The `yield` statement can not only send something, but it can also retrieve
    values, even at the same time:
    ```php
    <?php
    function a(): Generator {
        $incoming = yield 'outgoing';
        assert($incoming === 10);
    }
    $a = a();
    assert($a->current() === 'outgoing');
    $a->send(10);
    ```

## Contribute

### Build docker image

```shell script
docker build -t coajaxial/messaging-mediator .
```

### Load shell aliases

There is a shell aliases file that you can `source` to import some useful
aliases, e.g. `composer` running from within the docker container 
(coajaxial/messaging-mediator)

```shell script
source .aliases
```