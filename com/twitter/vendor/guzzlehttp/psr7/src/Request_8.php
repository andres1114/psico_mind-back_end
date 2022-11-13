e the value delivered to promise B.

**Note**: when you do not unwrap the promise, no value is returned.


# Cancellation

You can cancel a promise that has not yet been fulfilled using the `cancel()`
method of a promise. When creating a promise you can provide an optional
cancel function that when invoked cancels the action of computing a resolution
of the promise.


# API


## Promise

When creating a promise object, you can provide an optional `$waitFn` and
`$cancelFn`. `$waitFn` is a function that is invoked with no arguments and is
expected to resolve the promise. `$cancelFn` is a function with no arguments
that is expected to cancel the computation of a promise. It is invoked when the
`cancel()` method of a promise is called.

```php
use GuzzleHttp\Promise\Promise;

$promise = new Promise(
    function () use (&$promise) {
        $promise->resolve('waited');
    },
    function () {
        // do something that will cancel the promise computation (e.g., close
        // a socket, cancel a database query, etc...)
    }
);

assert('waited' === $promise->wait());
```

A promise has the following methods:

- `then(callable $onFulfilled, callable $onRejected) : PromiseInterface`
  
  Appends fulfillment and rejection handlers to the promise, and returns a new promise resolving to the return value of the called handler.

- `otherwise(callable $onRejected) : PromiseInterface`
  
  Appends a rejection handler callback to the promise, and returns a new promise resolving to the return value of the callback if it is called, or to its original fulfillment value if the promise is instead fulfilled.

- `wait($unwrap = true) : mixed`

  Synchronously waits on the promise to complete.
  
  `$unwrap` controls whether or not the value of the promise is returned for a
  fulfilled promise or if an exception is thrown if the promise is rejected.
  This is set to `true` by default.

- `cancel()`

  Attempts to cancel the promise if possible. The promise being cancelled and
  the parent most ancestor that has not yet been resolved will also be
  cancelled. Any promises waiting on the cancelled promise to resolve will also
  be cancelled.

- `getState() : string`

  Returns the state of the promise. One of `pending`, `fulfilled`, or
  `rejected`.

- `resolve($value)`

  Fulfills the promise with the given `$value`.

- `reject($reason)`

  Rejects the promise with the given `$reason`.


## FulfilledPromise

A fulfilled promise can be created to represent a promise that has been
fulfilled.

```php
use GuzzleHttp\Promise\FulfilledPromise;

$promise = new FulfilledPromise('value');

// Fulfilled callbacks are immediately invoked.
$promise->then(function ($value) {
    echo $value;
});
```


## RejectedPromise

A rejected promise can be created to represent a promise that has been
rejected.

```php
use GuzzleHttp\Promise\RejectedPromise;

$promise = new RejectedPromise('Error');

// Rejected callbacks are immediately invoked.
$promise->then(null, function ($reason) {
    echo $reason;
});
```


# Promise interop

This library works with foreign promises that have a `then` method. This means
you can use Guzzle promises with [React promises](https://github.com/reactphp/promise)
for example. When a foreign promise is returned inside of a then method
callback, promise resolution will occur recursively.

```php
// Create a React promise
$deferred =