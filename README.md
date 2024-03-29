[![Unittests](https://github.com/usox/json-schema-api/actions/workflows/php.yml/badge.svg)](https://github.com/usox/json-schema-api/actions/workflows/php.yml)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/usox/json-schema-api/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/usox/json-schema-api/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/usox/json-schema-api/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/usox/json-schema-api/?branch=master)

# JsonSchemaApi

This library provides a simple way to create a json api using [json-schema](http://json-schema.org/) to validate the request.
You can leverage most of the input validation tasks (variables types, length/content-constraints, lists containing just certain items, etc.)
to the json schema validator and work with the data right away.

## Json-Schema

Every method needs a corresponding schema-file which describes, how the request data should look alike.
You can find a simple example in the `example/schema`-folder.

Every request has also to follow a basic schema (see `dist/request.json`) which contains informations about the method.

## Validation

Every request and the generated response will be validated against the provided schema.

## Requirements

This library requires existing psr7 and psr17 implementations.

## Install

```
composer require usox/json-schema-api
```

## Usage

Just use the factory method to create the endpoint. The factory automatically searches for a existing psr17 stream factory implementation,
but you can also provide a factory instance when calling the method.

Get your method-provider ready (see below) and call the `factory`-method on the endpoint to retrieve a working instance:

The serve method requires a psr request and returns a psr response.
```php
$endpoint = \Usox\JsonSchemaApi\Endpoint::factory(
    $methodProvider
);

$endpoint->serve(
    $psr7Request,
    $psr7Response
);
```

#### Optional: PSR15 Middleware

The endpoint class implements the [PSR15](https://www.php-fig.org/psr/psr-15/) `RequestHandlerInterface`.

### MethodProvider

The `MethodProvider` is the source for your api methods - this could be a simple array, a DI-Container, etc. The class has to implement `Usox\JsonSchemApi\Contract\MethodProviderInterface`.

```php
class MyMethodProvider implements \Usox\JsonSchemaApi\Contract\MethodProviderInterface
{
    private array $methodList = ['beerlist' => BeerlistMethod::class];

    public function lookup(string $methodName) : ?\Usox\JsonSchemaApi\Contract\ApiMethodInterface 
    {
        $handler = $this->methodList[$methodName] ?? null;
        if ($handler === null) {
            return null;
        }
        return new $handler;
    }
}
```

### API-Method

The `lookup`-method in the MethodProvider must return an instance of `Usox\JsonSchemaApi\Contract\ApiMethodInterface`.

Every api method handler must define at least those two methods:
- The `handle` method which processes the request and returns the result
- The `getSchemaFile` method which returns the path to the schema file which is used to validate the request

```php
class BeerlistMethod implements \Usox\JsonSchemaApi\Contract\ApiMethodInterface
{
    public function handle(stdClass $parameter) : array
    {
        return ['ipa', 'lager', 'weizen'];
    }
    
    public function getSchemaFile() : string
    {
        return '/path/to/schema.json';
    }
}
```

## Example

You can find a working example in the `example`-folder.

Just cd to the example-folder and fire up the the php internal webserver `php -S localhost:8888`.
Now you can send `POST`-Requests to the api like this using curl.

```shell script
curl -X POST -d '{"method": "beerlist", "parameter": {"test1": "foobar", "test2": 666}}' "http://localhost:8888"
```

## Error-Handling

Basically there are three types of errors. All of them get logged.

### ApiExceptions
If a handler throws an exception which extends the `ApiException` exception class, the api will
return a `Bad Request (400)` response including an error message (the exception message) and an error code for reference
within a json response.

### InternalException
Internal errors, like non-existing schema files, invalid schemas and such, will return a `Internal Server Error (500)`
response and create a log entry (if a logger is provided).

In addition, optionally available context information within the exception will be logged, too.

### Throwable

Any Throwable which are thrown within an api handler, will be catched, logged and return a `Internal Server Error (500)` response.