<?php
namespace App\Controllers;
use Rnr\Http\Request;
use Rnr\Http\Response;
use Rnr\Http\FormData;

class User
{
    public function Login(Request $request) : Response
    {
        $view = new \stdClass;
        return Response::Template('loginform', $view)->addSystemInfoHeaders();
    }

    public function LoginSubmit(FormData $form, Request $request) : Response
    {
        return Response::JSON($form->getAll())->addSystemInfoHeaders();
    }
}