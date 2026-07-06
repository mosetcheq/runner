<?php
namespace App\Middleware;

use Override;
use Rnr\Core\Interfaces\RequestMiddlewareInterface;
use Rnr\Http\Request;
use Rnr\Http\Response;
use Rnr\Http\Session;
use Rnr\Core\DI;

class QuickEnd implements RequestMiddlewareInterface
{
    public function handleRequest(Request $request): Request|Response
    {
        return Response::JSON(false);
    }
}
