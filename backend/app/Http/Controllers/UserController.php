<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRegistrationRequest;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function info(){
        echo phpinfo();
    }
    
    public function register(CreateUserAPIRequest $request)
    {
        return $this->userService->createUser($request);

    }

    /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        return $this->userService->getUserDetail($id);


    }
}
