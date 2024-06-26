<?php

namespace App\Http\ApiHandler;

use App\Http\ApiHandler\Methods\LoginRequest;
use App\Models\RequestLogs;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TaskApi
{
    protected $url = '';

    protected $token = '';

    protected $databaseLog = [];

    public function __construct($skipToken = false)
    {
        $this->url = $this->retrieveUrl();
        if (!$skipToken) {
            $this->token = self::retrieveToken();
        }
    }

    protected function handleRequest(string $methodType, string $methodUrl, array $payload = [], string $targetId = '', array $params = [])
    {
        self::logRequest(strtoupper($methodType) . " $methodUrl" . (($targetId) ? "/$targetId" : '') . (($params) ? "?status=" . $params['status'] : ''));
        if ($payload) {
            //self::logRequest("With payload: " . json_encode($payload));
        }

        $response = Http::withToken($this->token);

        if ($params) {
            $response->withQueryParameters($params);
        }
        
        $requestedAt = new DateTime();
        $this->databaseLog = [
            'type' => $methodType,
            'host' => $this->url,
            'url' => "$methodUrl" . (($targetId) ? "/$targetId" : ''),
            'params' => (($params) ? "?status=" . $params['status'] : null),
            'request_body' => (($payload) ? json_encode($payload) : null),
            'requested_at' => $requestedAt->format('Y-m-d h:i:s:u'),
        ];

        $response = $response->{$methodType}($this->url . $methodUrl . (($targetId) ? "/$targetId" : ''), $payload);
        $responseStatus = $response->status();
        $responseJson = $response->json();

        $respondedAt = new DateTime();
        $duration = $requestedAt->diff($respondedAt);
        $this->databaseLog['response_body'] = json_encode($responseJson);
        $this->databaseLog['responded_at'] = $respondedAt->format('Y-m-d h:i:s:u');
        $this->databaseLog['duration'] = $duration->format('%I:%S:%F');
        $this->databaseLog['status'] = $responseStatus;
        
        self::logRequest("API Response Code: " . $responseStatus);
        //self::logRequest("API Response Body: " . json_encode($responseJson));

        if ($responseStatus !== 200) {
            RequestLogs::create($this->databaseLog);
            $message = (isset($response['msg'])) ? $response['msg'] : $response['message'];
            throw new Exception($message, $responseStatus);
        }

        RequestLogs::create($this->databaseLog);
        return $response;
    }

    protected static function retrieveToken($forceRefresh = false)
    {
        self::logRequest("Retrieving token.");

        $token = Cache::get('token', false);
        if (!$token || $forceRefresh){
            self::logRequest("Requesting new token.");
            new LoginRequest();
            $token = Cache::get('token');
        }

        self::logRequest("Token retrieved");
        return $token;
    }

    protected static function logRequest($message)
    {
        Log::channel('requests')->info("[LOG] " . $message);
    }

    protected function retrieveUrl()
    {
        return Config::get('app.api_url');
        /*$hosts = explode(";", Config::get('app.api_url'));
        $this->url = $hosts[array_rand($hosts)];

        $users = explode(";", Config::get('app.api_credentials'));
        $user = $users[array_rand($users)];
        $user = explode(":", $user);*/
        
    }
}
