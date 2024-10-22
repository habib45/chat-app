<?php
namespace App\Services;

use App\Traits\RequestService;
use Laravel\Sanctum\PersonalAccessToken;
use Exception;


class GatewayService
{
    use RequestService;

    /**
     * @var
     */
    public $baseUri;

    /**
     * @var
     */
    private $secret;

    /**
     * @var
     */
    private $userId;

    /**
     * GatewayService constructor.
     * @param $baseUri
     * @param $secret
     * @param $userId
     */
    public function __construct($baseUri, $secret, $userId)
    {
        $this->baseUri = $baseUri;
        $this->secret = $secret;
        $this->userId = $userId;
    }


    public function processRequest($method, $url, $content)
    {
        switch (strtolower($method)) {
            case "get":
                return $this->get($url);
                break;
            case "post":
                return $this->post($url,$content);
                break;
            case "put":
                return $this->put($url,$content);
                break;
            case "delete":
                return $this->delete($url,$content);
                break;
        }

    }


    /**
     * @param $url
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get($url)
    {
        return $this->request('GET', $url);
    }

    /**
     * @param $url
     * @param $data
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post($url,$data)
    {
        return $this->request('POST', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function put($url, $data)
    {
        return $this->request('PUT', $url, $data);
    }

    /**
     * @param $url
     * @param $data
     * @return string
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function delete($url, $data)
    {
        return $this->request('DELETE', $url, $data);
    }

    /**
     * @param $hashedToken
     * @return mixed
     */
    public static function getUser($hashedToken){
        $token = PersonalAccessToken::findToken($hashedToken);
        if($token != null){
            return $token->tokenable;
        }
        return false;
    }

}
