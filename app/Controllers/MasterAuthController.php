<?php
require_once __DIR__ . '/../Helpers/Response.php';
require_once __DIR__ . '/../Helpers/Validator.php';
require_once __DIR__ . '/../Security/Hash.php';
require_once __DIR__ . '/../Services/MasterAuthService.php';

class MasterAuthController {

    public static function login() {
        $payload = json_decode(file_get_contents('php://input'), true);

        $v = new Validator($payload);
        $v->required('email')->email('email')
          ->required('password');

        if ($v->fails()) {
            Response::error(implode(', ', $v->errors()), 400);
        }

        $tenantinfo = MasterAuthService::login($payload);
        
        // FIXED: Removed the stray ']'
        Response::success('Login successful', $tenantinfo); 
    }
}