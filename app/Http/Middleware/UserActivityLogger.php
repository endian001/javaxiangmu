<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\UserActivity;

class UserActivityLogger
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // 记录用户活动
        if (auth()->check()) {
            $user = auth()->user();
            
            UserActivity::create([
                'user_id' => $user->id,
                'action' => $this->getActionName($request),
                'details' => $this->getActionDetails($request),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'device' => $this->getDevice($request->userAgent()),
                'browser' => $this->getBrowser($request->userAgent()),
                'os' => $this->getOS($request->userAgent()),
                'url' => $request->fullUrl(),
                'referer' => $request->header('referer'),
            ]);
        }

        return $response;
    }

    /**
     * 获取操作名称
     */
    protected function getActionName(Request $request)
    {
        $method = strtoupper($request->method());
        $path = $request->path();
        
        return "{$method} {$path}";
    }

    /**
     * 获取操作详情
     */
    protected function getActionDetails(Request $request)
    {
        $details = [];
        
        if ($request->method() === 'POST' || $request->method() === 'PUT') {
            $details['input'] = $request->except(['password', 'password_confirmation']);
        }
        
        return json_encode($details);
    }
    
    /**
     * 获取设备类型
     */
    protected function getDevice($userAgent)
    {
        if (strpos($userAgent, 'Mobile') !== false) {
            return 'Mobile';
        } elseif (strpos($userAgent, 'Tablet') !== false) {
            return 'Tablet';
        } else {
            return 'Desktop';
        }
    }
    
    /**
     * 获取浏览器
     */
    protected function getBrowser($userAgent)
    {
        if (strpos($userAgent, 'Chrome') !== false) {
            return 'Chrome';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Firefox';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Edge';
        } elseif (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) {
            return 'Internet Explorer';
        } else {
            return 'Other';
        }
    }
    
    /**
     * 获取操作系统
     */
    protected function getOS($userAgent)
    {
        if (strpos($userAgent, 'Windows') !== false) {
            return 'Windows';
        } elseif (strpos($userAgent, 'Macintosh') !== false) {
            return 'MacOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            return 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false) {
            return 'iOS';
        } else {
            return 'Other';
        }
    }
}