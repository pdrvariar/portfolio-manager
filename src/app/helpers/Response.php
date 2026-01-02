<?php
class Response {
    public static function json($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }
    
    public static function error($message, $status = 400) {
        self::json(['error' => $message], $status);
    }
    
    public static function success($data = null, $message = '') {
        $response = ['success' => true];
        if ($message) $response['message'] = $message;
        if ($data !== null) $response['data'] = $data;
        self::json($response);
    }
}