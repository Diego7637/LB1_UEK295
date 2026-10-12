<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

require_once __DIR__ . '/response.php';

function slimController(string $controllerFile, ?string $idVariable = null): callable
{
    return static function (
        ServerRequestInterface $request,
        ResponseInterface $response,
        array $args = []
    ) use ($controllerFile, $idVariable): ResponseInterface {
        $parsedBody = $request->getParsedBody();
        $GLOBALS['apiRequestBody'] = is_array($parsedBody) ? $parsedBody : [];

        $controllerVariables = [];
        if ($idVariable !== null) {
            $controllerVariables[$idVariable] = (int) ($args[$idVariable] ?? 0);
        }

        try {
            (static function () use ($controllerFile, $controllerVariables): void {
                extract($controllerVariables, EXTR_SKIP);
                require $controllerFile;
            })();
        } catch (ApiResponseException $apiResponse) {
            $json = json_encode(
                $apiResponse->payload,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            );

            if ($json === false) {
                throw new RuntimeException('Could not encode the API response.');
            }

            $response->getBody()->write($json);
            return $response
                ->withStatus($apiResponse->status)
                ->withHeader('Content-Type', 'application/json; charset=utf-8');
        } finally {
            unset($GLOBALS['apiRequestBody']);
        }

        throw new LogicException('Controller completed without producing a response.');
    };
}
