<?php
namespace Rnr\Core\Interfaces;
use Rnr\Http\Response;

interface ResponseMiddlewareInterface
{
    public function handleResponse(Response $response): Response;
}