<?php

namespace App\Services;

use App\Helpers\ConfigHelper;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use phpDocumentor\Reflection\Types\Boolean;

class AuthorizationService extends ApiBaseService
{

    protected $aclPermissionService;

    protected $configRepository;

    public function __construct(AclPermissionService $aclPermissionService, ConfigRepository $configRepository)
    {
        $this->aclPermissionService = $aclPermissionService;
        $this->configRepository = $configRepository;
    }

    public function ensureAuthorization(Request $request)
    {
        try {

            if (!$this->checkSystemConfig()) {
                return true;
            }
            if ($this->isAllowedPath($request)) {
                return true;
            }

            list($controller, $method) = $this->getControllerMethod($request);

            if (!$controller) {
                return true;
            }
            if ($this->checkForPermission($request, $controller, $method)) {
                return true;
            }
            session()->put('permission_error', 'User Does Not Have Permission In ' . $controller . ' , ' . $method . ' method');
            return false;
        } catch (Exception $e) {
            $error['message'] = $e->getMessage();
            $error['trace'] = $e->getTraceAsString();
            Log::error(json_encode($error));
            throw new Exception($e->getMessage());
        }
    }

    public function isAllowedPath($request)
    {
        $requestPath = $request->path();
        if (in_array($requestPath, $this->allowedPaths())) {
            return true;
        }
        // ignore for btrc
        if (strpos($requestPath, 'btrc') !== false) {
            return true;
        }
        return false;
    }

    private function checkForPermission($request, $controller, $method)
    {
        if ($request->token != null) {
            $token = $request->token;
        } else {
            $token = $request->bearerToken();
        }

        if (!$token) {
            throw new Exception('User Request Token Not Found !');
        }
        $user = $this->getUser($token);

        if (!$user) {
            throw new Exception('No User Found With Given Token ' . $token);
        }

        if (!$user->groups) {
            throw new Exception('No Group Found For User ' . $user->email);
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        $service = $this->getServiceName($request);
        if (!in_array($service, $this->allowedServices())) {
            $service = 'gateway';
        }

        $userCanAccessResource = $this->aclPermissionService->canUserAccessResource($user, $controller, $method, $service);
        if ($userCanAccessResource) {
            return true;
        }
        return false;
    }

    private function isAdmin($user)
    {
        $userGroups = $user->groups;
        $adminGroups = $this->getAllowedGroups();
        foreach ($userGroups as $group) {
            if (in_array($group->alias, $adminGroups)) {
                return true;
            }
        }
        return false;
    }

    public function getUser($hashedTooken)
    {
        return GatewayService::getUser($hashedTooken);
    }

    private function allowedPaths()
    {
        return [
            // token route 
            'api/v1/login',
            // profile route 
            'api/v1/user/profile',
            'api/v1/user/authorization/check-edit-delete-permission',
            'api/v1/user/authorization/check-menu-permission',
            'api/v1/user/authorization/route-match',
            'api/v1/notification/db-stats',
        ];
    }

    private function getRoute($request)
    {
        $route = Route::getRoutes()->match($request);
        return $route;
    }

    public function getControllerMethod($request)
    {
        return $this->makeRequest($request);
    }

    private function getAllowedGroups()
    {
        return [
            'admin'
        ];
    }

    public function getServiceName($request)
    {
        $url = $request->getRequestUri();
        $arr = explode("/", $url);
        $service = $arr[3];
        return $service;
    }

    public function getAuthAllowedServices()
    {
        return $this->allowedServices();
    }

    private function allowedServices()
    {
        return [
            'user',
            'form',
            'workflow',
            'ticket',
            'report',
            'minio',
            'notification',
            'gateway',
        ];
    }

    /**
     * check system config
     * @return boolean
     */
    private function checkSystemConfig(): bool
    {
        $key = 'authorization-check';

        $config = $this->configRepository->findBy(['key' => $key])->first();

        if (!$config) {
            // if key not found in config -> dont check 
            return false;
        }
        if ($config['value']) {
            // admin wants to check 
            return true;
        }
        // admin dont want to  check 
        return false;
    }

    private function getRelativeUrlPath($request)
    {
        $url = $request->getRequestUri();
        $arr = explode("/", $url);
        if (in_array($arr[3], $this->allowedServices())) {
            unset($arr[0]);
            unset($arr[3]);
            $url = implode("/", $arr);
        }
        return $url;
    }

    private function getOriginalUrl($request)
    {
        $baseUri = $this->getBaseUrl($request);
        $firstCharacter = substr($this->getRelativeUrlPath($request), 0, 1);
        $slash = '/';
        if ($firstCharacter == '/') {
            $slash = '';
        }
        return $baseUri . $slash . $this->getRelativeUrlPath($request);
    }

    private function getOriginalRoute($request)
    {
        $baseUri = $this->getBaseUrl($request);
        $firstCharacter = substr($this->getRelativeUrlPath($request), 0, 1);
        $slash = '/';
        if ($firstCharacter == '/') {
            $slash = '';
        }
        return $this->getRelativeUrlPath($request);
    }

    private function getBaseUrl($request)
    {
        $service = $this->getServiceName($request);
        if (!in_array($service, $this->allowedServices())) {
            $service = 'gateway';
        }
        $baseUri = ConfigHelper::getBaseUri($service);
        return $baseUri;
    }

    private function getAuthorizationUrl()
    {
        return '/api/v1/authorization/route-match';
    }

    private function makeRequest($request)
    {
        $serviceName = $this->getServiceName($request);
        if (!in_array($serviceName, $this->allowedServices())) {
            $serviceName = 'gateway';
        }
        $originalUrl = $this->getOriginalUrl($request);
        if ($serviceName == 'gateway') {
            return $this->getControllerMethodGatewayInternal($originalUrl, $request->getMethod());
        }

        $controllerAndMethod = $this->getControllerAndMethodFromDB($request, $serviceName);

        return $controllerAndMethod;
    }

    private function getControllerAndMethodFromDB($request, $serviceName)
    {
        $originalRoute = $this->getOriginalRoute($request);
        $requestType = $request->getMethod();
        $service = $serviceName."_service";

        $routes = RouteMatchApi::where('method', $requestType)
            ->where('service', $service)
            ->get()->toArray();

        foreach ($routes as $route){
            Route::$requestType($route['route'], [
                'uses' => $route['controller'] . '@' . $route['action'],
            ]);
        }

        $routeExists = Route::getRoutes();

        foreach ($routeExists as $route) {
            $newRequest = Request::create($originalRoute, $requestType);
            $match = $route->matches($newRequest);
            if ($match){
                $actionName = $route->getActionName();
                $exploded = explode("@", $actionName);
                $action = $exploded[1];
                $controller = $exploded[0];
                $result = [
                    $controller,
                    $action
                ];
                return $result;
            }
        }


        throw new Exception('Invalid Route !!!');
    }

    public function getControllerMethodGatewayInternal($url, $method)
    {
        try {

            $route = $this->getRouteGateway('$request', $url, $method);
            if (is_null($route)) {
                throw new Exception('No route path defined in ticket service with -> ' . $url);
            }
            $actionName = $route->getActionName();
            $action = substr($actionName, strpos($actionName, '@') + 1);
            $controller = substr($actionName, strrpos($actionName, '\\') + 1, -(strlen($action) + 1));
            $result = [
                $controller,
                $action
            ];
            return $result;
        } catch (Exception $e) {
            $result = [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
            Log::error(json_encode($result));
            return $this->sendErrorResponse($result, 'Route Information Fetching Failed');
        }
    }

    public function getControllerMethodGateway(Request $request)
    {
        try {
            $url = $request->input('url');
            $method = $request->input('method');
            $route = $this->getRouteGateway($request, $url, $method);
            if (is_null($route)) {
                throw new Exception('No route path defined in ticket service with -> ' . $url);
            }
            $actionName = $route->getActionName();
            $action = substr($actionName, strpos($actionName, '@') + 1);
            $controller = substr($actionName, strrpos($actionName, '\\') + 1, -(strlen($action) + 1));
            $result = [
                $controller,
                $action
            ];
            return $this->sendSuccessResponse($result, 'Route Information Fetched Successfully');
        } catch (Exception $e) {
            $result = [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
            Log::error(json_encode($result));
            return $this->sendErrorResponse($result, 'Route Information Fetching Failed');
        }
    }

    private function getRouteGateway($request, $url, $method = 'GET')
    {
        $route = collect(\Illuminate\Support\Facades\Route::getRoutes())->first(function ($route) use ($url, $method) {
            return $route->matches(request()->create($url, $method));
        });
        return $route;
    }
    public function isGatewayService($request)
    {
        $serviceName = $this->getServiceName($request);
        if (!in_array($serviceName, ConfigHelper::allowedServices())) {
            return true;
        }
        return false;
    }
}