<?php

declare(strict_types=1);

namespace Usox\JsonSchemaApi\Input;

use stdClass;
use Teapot\StatusCode;
use Usox\JsonSchemaApi\Contract\ApiMethodInterface;
use Usox\JsonSchemaApi\Contract\MethodProviderInterface;
use Usox\JsonSchemaApi\Exception\MethodNotFoundException;

final class MethodRetriever implements MethodRetrieverInterface
{
    private MethodValidatorInterface $methodValidator;

    private MethodProviderInterface $methodProvider;

    public function __construct(
        MethodValidatorInterface $methodValidator,
        MethodProviderInterface $methodProvider
    ) {
        $this->methodValidator = $methodValidator;
        $this->methodProvider = $methodProvider;
    }

    /**
     * @throws MethodNotFoundException
     */
    public function retrieve(
        stdClass $input
    ): ApiMethodInterface {
        // Get the method and version from the request
        $methodName = $input->method;
        $version = $input->version;
        
        if ($version !== null) {
            $methodName = sprintf('%s.%d', $methodName, $version);
        }

        $handler = $this->methodProvider->lookup($methodName);
        if ($handler === null) {
            throw new MethodNotFoundException(
                'Method not found',
                StatusCode::BAD_REQUEST
            );
        }

        $this->methodValidator->validate($handler, $input);
        
        return $handler;
    }
}