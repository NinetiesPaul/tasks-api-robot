<?php

namespace App\Http\ApiHandler\Methods;

use App\Http\ApiHandler\TaskApi;
use Exception;

class ListTaskRequest extends TaskApi
{
    protected $methodUrl = '/api/task/list';

    public function __construct()
    {
        parent::__construct();
    }

    public function execute($params = [])
    {
        try{
            $response = $this->handleRequest('get', $this->methodUrl, [], '', $params);
            self::logRequest("Request successful: Retrieved " . $response['data']['total'] . " tasks");
    
            $taskIds = array_column($response['data']['tasks'], 'id');
            if (count($taskIds) > 0) {
                return $taskIds[array_rand($taskIds)];
            }
            
            self::logRequest("Request finished with no changes: No tasks found with status '" . $params['status'] . "'");
            return null;
        } catch (Exception $ex) {
            $exception = ($ex->getMessage()) ?? 'Check api log';
            self::logRequest("Request failed: " . $exception);

            if ($ex->getCode() == 401) {
                self::retrieveToken(true);
            }
        }
    }
}
