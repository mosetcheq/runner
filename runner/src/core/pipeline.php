<?php
namespace Rnr\Core;

use Rnr\Http\Request;
use Rnr\Http\Response;

class Pipeline
{
    private Request $request;
    private array $requestMiddleware = [];
    private array $responseMiddleware = [];
    private Router $router;

    public function __construct(Request $request, Router $router)
    {
        $this->request = $request;
        $this->router = $router;

        $config = require \Config\Path::APP . 'Config/Middleware.php';
        $this->requestMiddleware = $config['request'] ?? [];
        $this->responseMiddleware = $config['response'] ?? [];
    }

    public function run(): Response
    {
        $request = $this->request;

        // Request middleware
        foreach ($this->requestMiddleware as $mwClass) {
            $mw = new $mwClass();
            $result = $mw->handleRequest($request);

            if ($result instanceof Response) {
                return $result;
            }

            $request = $result;
        }

        // Router → Response
        $status = $this->router->dispatch();

        if ($status->isError()) {
            return $status->toResponse();
        }

        $controller = new $status->controller();
        $response = $controller->{$status->method}(
            $request
        );

        // Response middleware
        foreach ($this->responseMiddleware as $mwClass) {
            $mw = new $mwClass();
            $response = $mw->handleResponse($response);
        }

        return $response;
    }
}
