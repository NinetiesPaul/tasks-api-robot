<?php

namespace App\Http\ApiHandler\Methods;

use App\Http\ApiHandler\TaskApi;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

class LoginRequest extends TaskApi
{
    protected $methodUrl = "/login";

    public function __construct()
    {
        parent::__construct(true);
        $this->execute();
    }

    public function execute()
    {
        $response = $this->handleRequest('post', $this->methodUrl, [
            'username' => Config::get('app.api_username'),
            'password' => Config::get('app.api_password'),
        ]);

        if ($response['token']) {
            Cache::forget('token');
            Cache::put('token', $response['token']);
        }
    }
}
