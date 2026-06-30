<?php
namespace Rnr\Routing;
use Rnr\Http\Request;


enum RouteType
{
    case Static;
    case Regex;
    case Parametric;
    case Fallback;
    case Wildcard;

    public function match(array $route, string $url): bool
    {
        return match($this) {
            self::Static => $url === $route['match'],
            self::Regex => preg_match($route['regex'], $url),
            self::Parametric => $this->matchParametric($route, $url),
            self::Wildcard => str_starts_with($url, $route['wildcardBase']),
            self::Fallback => true,
        };
    }

    private function matchParametric(array $route, string $url)
    {
        $url = explode('/', $url);
        foreach($route['segments'] as $seg) {
            switch($seg[0]) {
                case('='):
                    if(array_shift($url) === false) {
                        return false;
                    }
                    break;
                case('?'):
                    if(!empty($url)) {
                        array_shift($url);
                    }
                    break;
                default:
                    if($seg !== array_shift($url)) {
                        return false;
                    }
                    break;
            }
        }
        return empty($url);
    }
}


class Router {

    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->routeCompiler();
    }


    private function routeCompiler()
    {
        $source = file(\Config\Path::ETC . 'routes.txt');
        foreach($source as $line)
        {
            // oceseme pripadne komentare
            [$line] = explode("#", $line);
            $line = trim($line);

            if($line !== "")
            {
                $compiled = [];
                [$left, $right] = explode("=>", $line);
                $left = trim($left);
                $right = trim($right);

                $left = preg_split('/\s+/', $left);
                $compiled['method'] = array_shift($left);
                $route = array_shift($left);
                if($route === 'REGEX') {
                    // je regex
                    $compiled['routeType'] = RouteType::Regex;
                    $compiled['regex'] = array_shift($left);
                } else {
                    if(strpos($route, '{') !== false) {
                        // je parametric
                        $seg = explode('/', $route);
                        $compiled['segments'] = [];
                        foreach($seg as $segment) {
                            if($segment[0] === '{') {
                                $segment = trim($segment, '{}');
                                if($segment[0] !== '?') {
                                    $segment = '=' . $segment;
                                }
                            }
                            $compiled['segments'][] = $segment;
                        }
                        $compiled['routeType'] = RouteType::Parametric;
                    } elseif(strpos($route, '*') !== false) {
                        $pos = strpos($route, '*');
                        if($pos == 0) {
                            $compiled['segments'] = explode('/', $route);
                            $compiled['routeType'] = RouteType::Fallback;
                        } else {
                            $compiled['wildcardBbase'] = substr($route, 0, $pos);
                            $compiled['routeType'] = RouteType::Wildcard;
                        }
                    } else {
                        $compiled['match'] = $route;
                        $compiled['routeType'] = RouteType::Static;
                    }
                }


                $right = explode('@', $right, 2);
                $target = trim(array_shift($right));
                if(!empty($right)) {
                    $compiled['middleware'] = explode(',', trim($right[0]));
                }

                $compiled['params'] = [];

                // parametry {id,page}
                if (preg_match('/^(.*)\((.+)\)$/', $target, $m)) {
                    $target = trim($m[1]);
                    $compiled['params'] = array_map('trim', explode(',', $m[2]));
                }

                // controller:method
                if (strpos($target, ':') !== false) {
                    [$controller, $method] = explode(':', $target, 2);
                    $compiled['controller'] = trim($controller);
                    $compiled['method'] = trim($method);
                }
                // wildcard controller mapping Admin/*
                elseif (strpos($target, '/*') !== false) {
                    $compiled['controllerWildcard'] = rtrim($target, '/*');
                }
                // fallback *
                elseif ($target === '*') {
                    $compiled['controller'] = '*';
                }
                // pouze controller
                else {
                    $compiled['controller'] = trim($target);
                }


                print_r($compiled);

            }

        }
    }

    public function addStatic(?string $method, string $route, string $action) : self
    {
        return $this;
    }

    public function addRegex(?string $method, string $route, string $action): self
    {
        return $this;
    }

    public function addParametric(?string $method, string $route, string $action): self
    {
        return $this;
    }

    }