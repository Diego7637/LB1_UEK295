<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/helpers/response.php';
require_once __DIR__ . '/helpers/slim_controller.php';
require_once __DIR__ . '/middleware/auth.php';

$app = AppFactory::create();
$app->setBasePath('/api/v1');

$app->addRoutingMiddleware();
$app->addBodyParsingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(false, true, true);
$errorMiddleware->setDefaultErrorHandler(
    static function (
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
        ?Psr\Log\LoggerInterface $logger = null
    ): ResponseInterface {
        $status = $exception instanceof HttpException ? $exception->getCode() : 500;
        if ($status < 400 || $status > 599) {
            $status = 500;
        }

        $message = match ($status) {
            404 => 'Not found.',
            405 => 'Method not allowed.',
            default => 'Internal server error.',
        };

        $response = new Slim\Psr7\Response($status);
        $response->getBody()->write(json_encode(['error' => $message], JSON_UNESCAPED_SLASHES));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
);

$authenticate = slimController(__DIR__ . '/controllers/authenticate.php');
$app->post('/authenticate', $authenticate);

$controller = static fn(string $name, ?string $idVariable = null): callable =>
    slimController(__DIR__ . '/controllers/' . $name . '.php', $idVariable);

$jwtMiddleware = static function (
    ServerRequestInterface $request,
    Psr\Http\Server\RequestHandlerInterface $handler
): ResponseInterface {
    $authorization = $request->getHeaderLine('Authorization');
    if (!preg_match('/^Bearer\s+(\S+)$/i', trim($authorization), $matches)) {
        $response = new Slim\Psr7\Response(401);
        $response->getBody()->write('{"error":"Unauthorized. A bearer token is required."}');
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    if (verifyJwt($matches[1]) === null) {
        $response = new Slim\Psr7\Response(401);
        $response->getBody()->write('{"error":"Unauthorized. The bearer token is invalid or expired."}');
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }

    return $handler->handle($request);
};

$app->group('', static function (RouteCollectorProxy $group) use ($controller): void {
    $group->get('/products', $controller('product/list'));
    $group->post('/products', $controller('product/create'));
    $group->post('/product', $controller('product/create'));
    $group->get('/product/{product_id:[0-9]+}', $controller('product/get', 'product_id'));
    $group->put('/product/{product_id:[0-9]+}', $controller('product/update', 'product_id'));
    $group->patch('/product/{product_id:[0-9]+}', $controller('product/update', 'product_id'));
    $group->delete('/product/{product_id:[0-9]+}', $controller('product/delete', 'product_id'));

    $group->get('/categories', $controller('category/list'));
    $group->post('/categories', $controller('category/create'));
    $group->post('/category', $controller('category/create'));
    $group->get('/category/{category_id:[0-9]+}', $controller('category/get', 'category_id'));
    $group->patch('/category/{category_id:[0-9]+}', $controller('category/update', 'category_id'));
    $group->put('/category/{category_id:[0-9]+}', $controller('category/update', 'category_id'));
    $group->delete('/category/{category_id:[0-9]+}', $controller('category/delete', 'category_id'));
})->add($jwtMiddleware);

$app->run();
