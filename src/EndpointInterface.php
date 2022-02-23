<?php

namespace Usox\JsonSchemaApi;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface EndpointInterface extends
   RequestHandlerInterface
{
    public function serve(
        ServerRequestInterface $request,
    ): ResponseInterface;
}
