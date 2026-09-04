<?php
namespace App\Controllers;
use Rnr\Http\Request;
use Rnr\Http\Response;

class HomeController
{
    public function Index(Request $request) : Response
    {
        return Response::HTML('<h1>Hello world!</h1>');
    }


    public function Article(Request $request) : Response
    {
        return Response::HTML('<p>Article id <strong>' . $request->getNamedParam('id_article') . '</strong></p>');
    }
}