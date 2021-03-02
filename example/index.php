<?php

declare(strict_types=1);

use Laminas\Diactoros\ResponseFactory;
use Psr\Http\Message\ServerRequestInterface;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Contract\MethodProviderInterface;
use Usox\JsonSchemaApi\Endpoint;

require_once __DIR__ . '/../vendor/autoload.php';

$request = Laminas\Diactoros\ServerRequestFactory::fromGlobals(
    $_SERVER,
    $_GET,
    $_POST,
    $_COOKIE,
    $_FILES
);

final class BeerlistMethod implements ApiMethodInterface
{
    public function handle(
        ServerRequestInterface $request,
        stdClass $parameter
    ): array {
        return [
            'beer_style_list' => [
                'ipa',
                'lager',
                'porter',
                'stout',
                'quadruple',
            ]
        ];
    }

    public function getSchemaFile(): string
    {
        return __DIR__ . '/schema/beerlist.json';
    }
}

$methodContainer = new class implements MethodProviderInterface {

    /** @var array<string, ApiMethodInterface> */
    private array $methods;

    public function __construct()
    {
        $this->methods = [
            'beerlist' => new BeerlistMethod(),
        ];
    }

    public function lookup(string $methodName): ?ApiMethodInterface
    {
        return $this->methods[$methodName] ?? null;
    }
};

$endpoint = Endpoint::factory(
    $methodContainer
);
$response = $endpoint->serve(
    $request,
    (new ResponseFactory())->createResponse()
);

$statusLine = sprintf(
    'HTTP/%s %s %s',
    $response->getProtocolVersion(),
    $response->getStatusCode(),
    $response->getReasonPhrase()
);
header($statusLine);

foreach ($response->getHeaders() as $name => $values) {
    $responseHeader = sprintf(
        '%s: %s',
        $name,
        $response->getHeaderLine($name)
    );
    header($responseHeader);
}

echo $response->getBody();
