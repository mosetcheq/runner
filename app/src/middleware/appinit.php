<?php
namespace App\Middleware;
use Rnr\Core\Interfaces\RequestMiddlewareInterface;
use Rnr\Http\Request;
use Rnr\Http\Response;
use Rnr\Http\Session;
use Rnr\Core\DI;

class AppInit implements RequestMiddlewareInterface
{
    public function handleRequest(Request $request) : Request | Response
    {
        $di = DI::getInstance();
        $di->set('session', new Session());
        $di->set('error', new \Rnr\Utils\ErrorHandler());
        $di->set('db', new \Rnr\DB\DB('localhost', 'root', '', 'uuii'));
        return $request;
    }
}