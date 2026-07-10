<?php

namespace App\Http\Middleware;

use App\User;
use Closure;

class Apiauthenticate
{
    public function handle($request, Closure $next)
    {
        $token = $request->header('Authorization', $request->header('authorization', ''));
        $token = trim(preg_replace('/^Bearer\s+/i', '', (string) $token));
        $user = $token === '' ? null : User::where('api_token', $token)->first();

        if (!$user) {
            return response()->json(['code' => 401, 'message' => 'authentication failed'], 401);
        }

        if ($this->isBlockedUser($user)) {
            return response()->json(['code' => 403, 'message' => 'account disabled'], 403);
        }

        $request->attributes->set('api_auth_user', $user);

        return $next($request);
    }

    protected function isBlockedUser(User $user)
    {
        $attributes = $user->getAttributes();

        if (array_key_exists('status', $attributes) && (int) $attributes['status'] <= 0) {
            return true;
        }

        if (array_key_exists('isdel', $attributes) && (int) $attributes['isdel'] === 1) {
            return true;
        }

        if (array_key_exists('isblack', $attributes) && (int) $attributes['isblack'] === 1) {
            return true;
        }

        return false;
    }
}
