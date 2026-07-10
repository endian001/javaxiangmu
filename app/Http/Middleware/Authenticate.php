<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return string
     */
    protected function redirectTo($request)
    {
        if (! $request->expectsJson()) {
           
            return url('/game/auth/login');
        } else {
            $return = ['code' => 401,'message' => '未授权','data' => []];
            echo json_encode($return);
            exit;
        }
    }
}
