<?php

namespace App\Http\Controllers\API\V1;

use App\Enums\HttpStatusCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\BtrcSingleSignOnRequest;
use App\Http\Requests\API\Users\CreateUserAPIRequest;
use App\Services\ApiBaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\UserService;
use App\Http\Requests\UserRegistrationRequest;
use App\Http\Requests\ChangePasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Services\GatewayService;
use Carbon\Carbon;
use Exception;
use Mail;
use Illuminate\Http\Response;

class UserController extends ApiBaseService
{
    /**
     * @var UserService
     */
    protected $userService;

    /**
     * PurchaseController constructor.
     * @param UserService $userService
     */
    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(CreateUserAPIRequest $request)
    {
        return $this->userService->store($request->all());

    }

    /**
     * @param LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function authenticate(LoginRequest $request)
    {
        return $this->userService->authenticate($request);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $token = $request->header('Authorization');
        return $this->userService->logout($token);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function token(Request $request)
    {
        $requestData=$request->all();
        // verify client credentials
        if (empty($requestData['client_id']) || empty($requestData['client_secret'])) {
            return $this->sendErrorResponse('BAD REQUEST', [], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
        try {

            $channel = Channel::where(['id'=>$requestData['client_id'],'client_secret'=>$requestData['client_secret']])->where('status','Active')->first();
            if (empty($channel)) {
                return $this->sendErrorResponseChtBackend('Invalid app credentials.', [], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
            $user= $this->userService->authenticate($request,true);
            $data = [
                'access_token' => $user['token'],
                'token_type' => 'Bearer',
            ];
            return $this->sendSuccessResponseChtBackend($data, 'Access Token');
        } catch (\Exception $e) {
            return $this->sendErrorResponseChtBackend($e->getMessage(), [], Response::HTTP_INTERNAL_SERVER_ERROR);

        }
    }

 /**
     * @param $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        return $this->userService->getUserDetail($id);


    }

    public function info(){
        echo phpinfo();
    }

}
