<?php

namespace App\Services;

use App\Enums\HttpStatusCode;
use App\Models\User;
use Exception;
use http\Exception\BadMessageException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use DB;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\Response as FResponse;

/**
 * Class UserService
 * @package App\Services
 */
class UserService extends ApiBaseService
{

    public $baseUri;
    private $secret;
    private $userId;

    /**
     * @var UserRepository
     */
    protected $userRepository;


    /**
     * UserService constructor.
     * @param UserRepository $UserRepository
     * @param OneGpApiService $oneGpApiService
     * @param ConfigRepository $configRepository
     * @param ExternalApiRepository $externalApiRepository
     * @param ExternalApiService $externalApiService
     */
    public function __construct(
        UserRepository        $UserRepository
    )
    {
        $this->userRepository = $UserRepository;
    }

    // Method to store user data
    public function store($request)
    {

        // Create a new user with the validated data
        $user = $this->userRepository->save([
            'name' => $request['name'],
            'email' => $request['email'],
            'mobile' => $request['mobile']??"",
            'password' => Hash::make($request['password']),  // Hash the password before storing it
        ]);
        return $this->sendSuccessResponse($request, 'User created successfully');
    }

    /**
     * This function use for user login by email or username and password
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function login(Request $request)
    {
        $data = [];
        $convertedUsername = '';
        try {
            // $userEmail = $request->input('email');
            $orgUsername = $request->input('email');
            $password = $request->input('password');
            $convertedUsername = $orgUsername;
            $user = $this->userRepository->getUserInfoByEmail($orgUsername);
            $fieldType = filter_var($convertedUsername, FILTER_VALIDATE_EMAIL) ? 'email' : 'mobile';
            $user = $this->userRepository->getUserInfo($convertedUsername, $fieldType);

            if (!$user && !Hash::check($request->password, $user->password)) {
                $this->insertLoginActivity($request, 1, 'These credentials do not match our records: Username: ' . $convertedUsername, '');
                return $this->sendErrorResponse(
                    'These credentials do not match our records',
                    [],
                    HttpStatusCode::BAD_REQUEST
                );
            }

            $token = $user->createToken('my-app-token')->plainTextToken;
                $chtUserInfo = $this->userRepository->getUserInfo($convertedUsername);
                if (empty($chtUserInfo) && $chtUserInfo == null) {
                    return $this->sendErrorResponse(
                        'Username or password is incorrect', [], HttpStatusCode::BAD_REQUEST);
                }

                
            $newTime = date("M d Y H:i:s", strtotime(date("Y-m-d H:i:s") . " +10 minutes"));
            unset($user['password']);
            $data['token'] = $token;
            $data['user'] = $user;
            Session::put('token', $token);
            return $this->sendSuccessResponse($data, 'You are successfully Login');
        } catch (Exception $exception) {
            Log::error($exception->getMessage());
            Log::error($exception->getTraceAsString());
          return $this->sendErrorResponse($exception->getMessage(), [],
                Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }




    /**
     * Revoked token
     *
     * @param $token
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout($token, $request)
    {
        if (empty($token)) {
            return $this->sendErrorResponse('Authorization token is empty', [],
                HttpStatusCode::VALIDATION_ERROR);
        }
        try {
            // $request->user()->currentAccessToken()->delete();;
            return $this->sendSuccessResponse([], 'You are successfully logged out');
        } catch (Exception $exception) {
            return $this->sendErrorResponse('Sorry, user cannot be logged out', [],
                Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getUserDetail($id){
        $user = $this->userRepository->getUserDetail($id);
        // unset($user->password())
        return $this->sendSuccessResponse($user, 'User fatch successfully');
    }

}
