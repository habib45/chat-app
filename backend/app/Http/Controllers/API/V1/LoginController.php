<?php

namespace App\Http\Controllers\API\V1;


use App\Http\Controllers\Controller;
use App\Http\Requests\API\OtpValidationAPIRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Traits\SystemTrait;
use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Session;
use Mail;

class LoginController extends Controller
{

    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var UserRepository
     */
    private $userRepository;


    /**
     * LoginController constructor.
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(LoginRequest $request)
    {
        return $this->userService->login($request);
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $token = $request->header('Authorization');
        return $this->userService->logout($token,$request);
    }
    
}
