<?php
namespace Rnr\Core\Interfaces;
use Rnr\Http\Request;
use Rnr\Http\Response;

interface RequestMiddlewareInterface
{
    public function handleRequest(Request $request): Request | Response;
}