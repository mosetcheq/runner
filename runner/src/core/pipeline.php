<?php
namespace Rnr\Core;

use Rnr\Http\Request;
use Rnr\Http\Response;
use Config;

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

        // 1) Framework request middleware
        foreach ($this->requestMiddleware as $mwClass) {
            $mw = new $mwClass();
            $result = $mw->handleRequest($request);
            if ($result instanceof Response) return $result;
            $request = $result;
        }

        // 2) FormHandler BEFORE (public forms)
        if ($request->formHandler->isPublic()) {
            $response = $request->formHandler->callHandler();
            if ($response instanceof Response) return $response;
        }

        // 3) Router dispatch
        $status = $this->router->dispatch();
        if ($status->isError()) return $status->toResponse();

        // 4) Router request middleware
        foreach ($status->middleware as $mwClass) {
            $middlewareClass = Config\Defaults::MIDDLEWARE_NAMESPACE . '\\' . $mwClass;
            $mw = new $middlewareClass();
            $result = $mw->handleRequest($request);
            if ($result instanceof Response) return $result;
            $request = $result;
        }

        // 5) FormHandler AFTER (private forms)
        if (!$request->formHandler->isPublic()) {
            $fhResponse = $request->formHandler->callHandler();
            if ($fhResponse instanceof Response) return $fhResponse;
        }

        // 6) Controller
        $controller = new $status->controller();
        $response = $controller->{$status->method}($request);

        // 7) Framework response middleware
        foreach ($this->responseMiddleware as $mwClass) {
            $mw = new $mwClass();
            $response = $mw->handleResponse($response);
        }

        // 8) Router response middleware
        foreach ($status->middleware as $mwClass) {
            $middlewareClass = Config\Defaults::MIDDLEWARE_NAMESPACE . '\\' . $mwClass;
            $mw = new $middlewareClass();
            $response = $mw->handleResponse($response);
        }

        return $response;
    }

}
