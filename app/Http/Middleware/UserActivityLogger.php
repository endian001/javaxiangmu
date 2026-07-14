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
            try {
                $user = auth()->user();
                $userAgent = (string) $request->userAgent();

                UserActivity::create([
                    'user_id' => $user->id,
                    'action' => $this->limitText($this->getActionName($request), 255),
                    'details' => $this->getActionDetails($request),
                    'ip' => $this->limitText($request->ip(), 50),
                    'user_agent' => $this->limitText($userAgent, 255),
                    'device' => $this->limitText($this->getDevice($userAgent), 100),
                    'browser' => $this->limitText($this->getBrowser($userAgent), 100),
                    'os' => $this->limitText($this->getOS($userAgent), 100),
                    'url' => $this->limitText($request->fullUrl(), 255),
                    'referer' => $this->limitText($request->header('referer'), 255),
                ]);
            } catch (\Throwable $e) {
                // Activity audit must never block player flows such as register/login.
            }
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
            $details['input'] = $request->except(['password', 'password_confirmation', 'qukuanmima', 'paypassword', 'paypwd']);
        }
        
        return json_encode($details);
    }

    protected function limitText($value, $limit)
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $limit);
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
