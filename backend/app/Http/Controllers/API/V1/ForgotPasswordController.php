<?php

namespace App\Http\Controllers\API\V1\Auth;

use App\Http\Controllers\Controller;
use App\Traits\SystemTrait;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Enums\HttpStatusCode;
use App\Services\ApiBaseService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use App\Repositories\UserRepository;
use App\Models\PasswordReset;
use App\Models\Config;
use Str;
use App\Mail\ForgetPasswordMail;
use Mail;
use Illuminate\Support\Facades\Hash;
use Swift_TransportException;
use Carbon\Carbon;
use App\Http\Requests\API\PasswordReset\ResetPasswordRequest;
use function Webmozart\Assert\Tests\StaticAnalysis\false;

class ForgotPasswordController extends Controller
{
//    use SendsPasswordResetEmails;

    protected $bearerToken;

    /**
     * @var \App\Services\ApiBaseService
     */
    protected $apiBaseService;

    /**
     * @var \App\Repositories\UserRepository
     */
    protected $userRepository;

    /**
     * Create a new controller instance.
     */
    public function __construct(ApiBaseService $apiBaseService, UserRepository $userRepository)
    {
        $this->middleware('guest');
        $this->apiBaseService = $apiBaseService;
        $this->userRepository = $userRepository;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Exception
     */
    public function __invoke(Request $request)
    {
        $this->bearerToken = $request->bearerToken();
        $input = $request->all();
        $requestUserData=[];
        $fieldType = filter_var($input['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $rules = array(
            'email' => "required",
        );
        $validator = Validator::make($input, $rules);
        if ($validator->fails()) {
            return $this->apiBaseService->sendErrorResponse('validation error', $validator->errors()->first(), HttpStatusCode::BAD_REQUEST);
        } else {
            try {
                $user = $this->userRepository->findOneByProperties([$fieldType => $input['email']]);
                if ($user) {
                    $expire_at = date("Y-m-d H:i:s", strtotime( '30 minutes', time()));
                    $token = $user['id'].time();//Str::random(10);
                    $saveTokenInfo = new PasswordReset();
                    $saveTokenInfo->email = $input['email'];
                    $saveTokenInfo->token = $token;
                    $saveTokenInfo->expire_at = $expire_at;
                    if($saveTokenInfo->save()){
                        $requestUserData['name']= $user['name'];
                        $requestUserData['email']= $user['email'];
                        $requestUserData['token']=$token;
                        $requestUserData['userId']=$user['id'];
                        if($this->sendEMail($requestUserData)==true){
                               return $this->apiBaseService->sendSuccessResponse([], 'You have been sent an email to reset your password with link');
                        }else{
                            return $this->apiBaseService->sendErrorResponse('Something Went Wrong. Please tray again ', ['detail' => 'User Not found'],
                                HttpStatusCode::BAD_REQUEST
                            );
                        }
                    }

                } else {
                    return $this->apiBaseService->sendErrorResponse('Invalid '.$fieldType, ['detail' => 'User Not found'],
                        HttpStatusCode::BAD_REQUEST
                    );
                }

            } catch (Exception $exception) {
                return $this->sendErrorResponse($exception->getMessage(), [], $exception->getStatusCode());
            } catch (Swift_TransportException $exception) {
                return $this->sendErrorResponse($exception->getMessage(), [], $exception->getStatusCode());
            }

        }


    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */

    public function tokenVerification(Request $request)
    {
        if(empty($request->input('token'))){
            return $this->apiBaseService->sendErrorResponse('Token not found', [], HttpStatusCode::NOT_FOUND);
        }

        $validator = Validator::make($request->all(), [
            'token' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->sendErrorResponse('validation error', $validator->errors(), HttpStatusCode::VALIDATION_ERROR);
        }

        $input = $request->all();
        $verified = PasswordReset::where('token', $input['token'])->first();
        if ($verified) {
            $data = [
                'token' => $input['token'],
                'email' => $verified->email,
                'validation' => true
            ];
            if($verified->expire_at < Carbon::now()->format('Y-m-d H:i:s')){
                $data['validation']=false;
                return $this->apiBaseService->sendSuccessResponse($data,'Password reset link time has been expired');
            }else{
                return $this->apiBaseService->sendSuccessResponse($data, 'This is a valid token');
            }

        } else {
            return $this->apiBaseService->sendErrorResponse('Token dose not found', [], HttpStatusCode::NOT_FOUND);
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePassword(ResetPasswordRequest $request)
    {

        $input = $request->all();
        $fieldType = filter_var($input['email'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        try {
            $user = $this->userRepository->findOneBy([$fieldType => $input['email']]);
            if ($user) {
                $password=Hash::make($input['password']);
                $user->password = $password;

                if (!empty($user->password)){
                    if (!empty($user->old_password)){
                        $oldPassword=json_decode($user->old_password);
                        if (count($oldPassword)>2){
                            array_shift($oldPassword);
                        }
                    }

                    $oldPassword[]=$password;
                    $user->old_password = json_encode($oldPassword);
                }
                $user->save();
                PasswordReset::where('token', $input['token'])->delete();
                return $this->apiBaseService->sendSuccessResponse($input, 'Password has been updated successfully');

            } else {
                return $this->apiBaseService->sendErrorResponse('Invalid Token', ['detail' => 'User Not found'],
                    HttpStatusCode::BAD_REQUEST
                );
            }

        } catch (Exception $exception) {
            return $this->apiBaseService->sendErrorResponse($exception->getMessage(), [], $exception->getStatusCode());
        }
    }

    /**
     * @param null $requestUserData
     * @return bool
     * @throws \Exception
     */
    private function sendEMail($requestUserData = null)
    {
        $config = Config::where(['key' => 'base-url'])->first();
        $param = array();
        Session::put('token',$this->bearerToken);
        if (!empty($requestUserData)) {
            $serviceUrls = SystemTrait::getSystemConfig('service-urls');
            $url = $serviceUrls['notification'] . '/api/v1/send-email';
            $param['to'] = $requestUserData['email'];
            $param['from_name'] = $requestUserData['name'];
            $param['subject'] = 'Reset your Password';
            $linkUrl = '<a href="' . $config->value . '/email-verification?code=' . $requestUserData['token'] . '"  target="_blank">here</a>';
            $param['message'] = 'Dear ' . $requestUserData['name'] . '<br> You recently requested to reset your password. Click on this link ' . $linkUrl . ' to reset your password.';
            $response = SystemTrait::serviceToServicePostApiCall($url, $param, 'POST');
            $response = json_decode($response, true);
            if (isset($response['status_code']) && $response['status_code'] == 200) {
                return true;
            } else {
                Log::info('something went wrong in notification server when user try to resat password send email');
                Log::error(json_encode($response));
                return false;
            }
        }
    }


}

