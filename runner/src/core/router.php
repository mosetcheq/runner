<?php
namespace Rnr\Core;
use Rnr\Http\Request;
use Rnr\Http\Response;
use Config;


final class RouterStatus
{
    public string $controller;
    public string $method;

    public array $middleware = [];
    public array $namedParams = [];
    public array $unnamedParams = [];

    private bool $error = false;
    private int $errorCode = 200;
    private string $errorMessage = '';

    /**
     * Úspěšná route
     */
    public static function ok(
        string $controller,
        string $method,
        array $namedParams = [],
        array $unnamedParams = [],
        array $middleware = []
    ): self {
        $s = new self();
        $s->controller = $controller;
        $s->method = $method;
        $s->namedParams = $namedParams;
        $s->unnamedParams = $unnamedParams;
        $s->middleware = $middleware;
        return $s;
    }

    /**
     * Defaultní route (fallback)
     */
    public static function default(): self
    {
        $s = new self();
        $s->controller = Config\Defaults::DEFAULT_CONTROLLER;
        $s->method = Config\Defaults::DEFAULT_METHOD;
        return $s;
    }

    /**
     * Chyba routování (404, 405, 500…)
     */
    public static function error(int $code, string $message): self
    {
        $s = new self();
        $s->error = true;
        $s->errorCode = $code;
        $s->errorMessage = $message;
        return $s;
    }

    public function isError(): bool
    {
        return $this->error;
    }

    /**
     * Převod chyby na Response
     */
    public function toResponse(): Response
    {
        return Response::JSON(['error' => $this->errorMessage])->responseCode($this->errorCode);
    }
}


enum RouteType: string
{
    case Static     = 'Static';
    case Regex      = 'Regex';
    case Parametric = 'Parametric';
    case Wildcard   = 'Wildcard';
    case Fallback   = 'Fallback';

    public function match(array $route, string $url): ?RouterStatus
    {
        return match($this) {
            self::Static     => $this->matchStatic($route, $url),
            self::Regex      => $this->matchRegex($route, $url),
            self::Parametric => $route['fastParametric'] ?? false
                                   ? $this->matchParametricFast($route, $url)
                                   : $this->matchParametric($route, $url),
            self::Wildcard   => $this->matchWildcard($route, $url),
            self::Fallback   => $this->matchFallback($route, $url),
        };
    }

    private function matchStatic(array $route, string $url): ?RouterStatus
    {
        if ($url !== $route['match']) {
            return null;
        }

        $s = new RouterStatus;
        $s->controller = Config\Defaults::CONTROLLER_NAMESPACE . '\\' . $route['controller'];
        $s->method = $route['methodName'];
        $s->middleware = $route['middleware'] ?? [];
        return $s;
    }

    private function matchRegex(array $route, string $url): ?RouterStatus
    {
        if (!preg_match($route['regex'], $url, $m)) {
            return null;
        }

        $s = new RouterStatus;
        $s->controller = Config\Defaults::CONTROLLER_NAMESPACE . '\\' . $route['controller'];
        $s->method = $route['methodName'];
        $s->middleware = $route['middleware'] ?? [];

        foreach ($route['params'] as $i => $name) {
            $s->namedParams[$name] = $m[$i + 1] ?? null;
        }

        return $s;
    }

    private function matchParametricFast(array $route, string $url): ?RouterStatus
    {
        if (!str_starts_with($url, $route['prefix'])) {
            return null;
        }

        $rest = substr($url, strlen($route['prefix']));
        $segments = $rest === '' ? [] : explode('/', $rest);

        if (count($segments) < $route['requiredParams']) {
            return null;
        }
        if (count($segments) > $route['requiredParams'] + $route['optionalParams']) {
            return null;
        }

        $s = new RouterStatus;
        $s->controller = Config\Defaults::CONTROLLER_NAMESPACE . '\\' . $route['controller'];
        $s->method = $route['methodName'];
        $s->middleware = $route['middleware'] ?? [];

        foreach ($route['params'] as $i => $name) {
            $s->namedParams[$name] = $segments[$i] ?? null;
        }

        return $s;
    }

    private function matchParametric(array $route, string $url): ?RouterStatus
    {
        $urlSeg = explode('/', $url);

        $s = new RouterStatus;

        foreach ($route['segments'] as $seg) {

            if ($seg['type'] === 'static') {
                if (array_shift($urlSeg) !== $seg['value']) {
                    return null;
                }

            } elseif ($seg['type'] === 'param') {

                if (empty($urlSeg) && !$seg['optional']) {
                    return null;
                }

                $s->namedParams[$seg['name']] = empty($urlSeg)
                    ? null
                    : array_shift($urlSeg);
            }
        }

        if (!empty($urlSeg)) {
            return null;
        }

        $s->controller = Config\Defaults::CONTROLLER_NAMESPACE . '\\' . $route['controller'];
        $s->method = $route['methodName'];
        $s->middleware = $route['middleware'] ?? [];

        return $s;
    }

    private function matchWildcard(array $route, string $url): ?RouterStatus
    {
        $segments = explode('/', $url);

        if ($segments[0] !== $route['prefix']) {
            return null;
        }

        $controller = ucfirst($segments[1] ?? '');
        $method     = $route['methodName'] ?? ($segments[2] ?? 'index');

        $named = $route['params'] ?? [];

        $s = new RouterStatus;
        $s->controller = Config\Defaults::CONTROLLER_NAMESPACE . '\\' . $route['namespace'] . '\\' . $controller . Config\Defaults::CONTROLLER_POSTFIX;
        $s->method = $method . Config\Defaults::METHOD_POSTFIX;
        $s->middleware = $route['middleware'] ?? [];

        // named params
        for ($i = 0; $i < count($named); $i++) {
            $s->namedParams[$named[$i]] = $segments[$i + 3] ?? null;
        }

        // unnamed params
        $s->unnamedParams = array_slice($segments, 3 + count($named));

        return $s;
    }

    private function matchFallback(array $route, string $url): ?RouterStatus
    {
        $segments = explode('/', $url);

        $controller = ucfirst($segments[0] ?? '');
        $method     = $route['methodName'] ?? ($segments[1] ?? 'index');

        $named = $route['params'] ?? [];

        $full = Config\Defaults::CONTROLLER_NAMESPACE . '\\' . $controller;
        if (!class_exists($full)) {
            return null;
        }

        $s = new RouterStatus;
        $s->controller = $full;
        $s->method = $method;
        $s->middleware = $route['middleware'] ?? [];

        // named params
        for ($i = 0; $i < count($named); $i++) {
            $s->namedParams[$named[$i]] = $segments[$i + 2] ?? null;
        }

        // unnamed params
        $s->unnamedParams = array_slice($segments, 2 + count($named));

        return $s;
    }
}



class Router {

    private Request $request;
    private array $routes;

    public function __construct(Request $request, array $routes)
    {
        $this->request = $request;
        $this->routes = $routes;
    }


    public static function compileRoutes(): void
    {
        $source = file(\Config\Path::ETC . 'routes.txt');
        if ($source === false) {
            throw new \RuntimeException('Unable to read routes file: ' . \Config\Path::ETC . 'routes.txt');
        }

        $compiled = [];

        foreach ($source as $lineNumber => $line) {

            // odstraň komentáře
            if (($commentPos = strpos($line, '#')) !== false) {
                $line = substr($line, 0, $commentPos);
            }
            $line = trim($line);

            if ($line === "") {
                continue;
            }

            $route = [
                'params' => [],
                'match' => null,
            ];

            // levá strana: METHOD + ROUTE
            [$left, $right] = explode('=>', $line, 2);
            $left = trim($left);
            $right = trim($right);

            $leftParts = preg_split('/\s+/', $left, 3);
            if (count($leftParts) < 2) {
                throw new \RuntimeException('Invalid route syntax on line ' . ($lineNumber + 1) . ': expected METHOD and route');
            }

            $route['method'] = array_shift($leftParts);
            $routePath = array_shift($leftParts);

            //
            // ROUTE TYPE DETEKCE
            //
            if ($routePath === 'REGEX') {

                $route['type'] = 'Regex';
                $pattern = array_shift($leftParts);
                $route['regex'] = '#' . $pattern . '#';

            } elseif (strpos($routePath, '{') !== false) {

                //
                // PARAMETRIC
                //
                $route['type'] = 'Parametric';

                $segments = explode('/', $routePath);
                $parsed = [];
                $staticPrefix = [];
                $paramCount = 0;
                $optionalCount = 0;
                $paramFound = false;
                $staticAfterParam = false;

                foreach ($segments as $seg) {

                    if ($seg === '') continue;

                    if ($seg[0] === '{') {
                        // parametr
                        $name = trim($seg, '{}');

                        $optional = false;
                        if ($name[0] === '?') {
                            $optional = true;
                            $name = substr($name, 1);
                            $optionalCount++;
                        } else {
                            $paramCount++;
                        }

                        $parsed[] = [
                            'type' => 'param',
                            'name' => $name,
                            'optional' => $optional
                        ];

                        $paramFound = true;

                    } else {
                        // statický segment
                        $parsed[] = [
                            'type' => 'static',
                            'value' => $seg
                        ];

                        if ($paramFound) {
                            $staticAfterParam = true;
                        } else {
                            $staticPrefix[] = $seg;
                        }
                    }
                }

                $route['segments'] = $parsed;

                //
                // FAST PARAMETRIC DETEKCE
                //
                if (!$staticAfterParam && $paramFound) {
                    // prefix = všechny statické segmenty před první param
                    $route['fastParametric'] = true;
                    $route['prefix'] = $staticPrefix ? implode('/', $staticPrefix) . '/' : '';
                    $route['requiredParams'] = $paramCount;
                    $route['optionalParams'] = $optionalCount;

                    // parametry podle pořadí
                    $route['params'] = [];
                    foreach ($parsed as $seg) {
                        if ($seg['type'] === 'param') {
                            $route['params'][] = $seg['name'];
                        }
                    }

                } else {
                    $route['fastParametric'] = false;
                    $route['params'] = [];
                    foreach ($parsed as $seg) {
                        if ($seg['type'] === 'param') {
                            $route['params'][] = $seg['name'];
                        }
                    }
                }

            } elseif (strpos($routePath, '*') !== false) {

                $pos = strpos($routePath, '*');

                if ($pos === 0) {
                    //
                    // FALLBACK
                    //
                    $route['type'] = 'Fallback';
                    $route['params'] = []; // named params doplní pravá strana

                } else {
                    //
                    // WILDCARD
                    //
                    $route['type'] = 'Wildcard';

                    // prefix = první segment před /*
                    $prefix = rtrim(substr($routePath, 0, $pos), '/');
                    $route['prefix'] = $prefix;

                    // namespace = část před \*
                    // controllerWildcard = "Admin\*" → namespace = "Admin"
                    if (isset($route['controllerWildcard'])) {
                        $route['namespace'] = rtrim($route['controllerWildcard'], '\\*');
                    } else {
                        // fallback: pokud není controllerWildcard, namespace = ucfirst(prefix)
                        $route['namespace'] = ucfirst($prefix);
                    }

                    // named params doplní pravá strana
                    $route['params'] = [];
                }

            } else {

                //
                // STATIC
                //
                $route['type'] = 'Static';
                $route['match'] = $routePath;
                $route['params'] = [];
            }

            //
            // PRAVÁ STRANA: controller, method, middleware, named params
            //
            $rightParts = explode('@', $right, 2);
            $target = trim($rightParts[0]);

            if (!empty($rightParts[1])) {
                $route['middleware'] = array_map('trim', explode(',', $rightParts[1]));
            } else {
                $route['middleware'] = [];
            }

            // named params: Controller:method(id,page)
            if (preg_match('/^(.*)\(([^)]*)\)$/', $target, $m)) {
                $target = trim($m[1]);
                $extraParams = array_map('trim', explode(',', $m[2]));
                $route['params'] = array_merge($route['params'], $extraParams);
            }

            // controller:method
            if (strpos($target, ':') !== false) {
                [$controller, $method] = explode(':', $target, 2);
                $route['controller'] = trim($controller);
                $route['methodName'] = trim($method);

            } elseif (str_ends_with($target, '/*')) {
                // wildcard controller mapping
                $route['controllerWildcard'] = rtrim($target, '/*');

            } elseif ($target === '*') {
                // fallback controller
                $route['controller'] = '*';

            } else {
                // only controller
                $route['controller'] = trim($target);
            }

            if ($route['type'] === 'Wildcard' && isset($route['controllerWildcard'])) {
                $route['namespace'] = rtrim($route['controllerWildcard'], '\\*');
            }

            $compiled[] = $route;
        }

        file_put_contents(\Config\Path::CACHE . 'routes.php', "<?php\n\nuse Rnr\\Routing\\RouteType;\n\nreturn " . var_export($compiled, true) . ';');
    }


    public function dispatch(): RouterStatus
    {
        $method = $this->request->getMethod();
        $url = trim($this->request->getPath(), '/');

        // 1) Prázdná URL → default controller
        if ($url === '') {
            return RouterStatus::default();
        }

        $methodMatched = false;

        foreach ($this->routes as $route) {

        // 2) Metoda nesedí → přeskočit, ale zapamatovat si, že URL existuje
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                // URL sedí, ale metoda ne → možný 405
                if ($route['match'] === $url || $route['type'] !== 'Static') {
                    $methodMatched = true;
                }
                continue;
            }

            /** @var RouteType $type */
            $type = RouteType::from($route['type']);
            $status = $type->match($route, $url);

            if ($status !== null) {

                // 3) Doplnění defaultní metody, pokud není definovaná
                if (empty($status->method)) {
                    $status->method = \Config\Defaults::DEFAULT_METHOD;
                }

                // 4) Doplnění parametrů do Request objektu
                foreach ($status->namedParams as $name => $value) {
                    $this->request->addNamedParam($name, $value);
                }

                foreach ($status->unnamedParams as $value) {
                    $this->request->addUnnamedParam($value);
                }

                return $status;
            }
        }

        // 5) URL existuje, ale metoda neodpovídá → 405
        if ($methodMatched) {
            return RouterStatus::error(405, 'Method Not Allowed');
        }

        // 6) URL neodpovídá žádné routě → 404
        return RouterStatus::error(404, 'Not Found');
    }


}