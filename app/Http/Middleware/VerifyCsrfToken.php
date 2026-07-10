<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * Indicates whether the XSRF-TOKEN cookie should be set on the response.
     *
     * @var bool
     */
    protected $addHttpCookie = true;

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        '/upload',
        '/api/claim-upgrade-bonus',
        '/api/claim-weekly-salary',
        '/api/claim-monthly-salary',
        '/api/vip/privileges',
        '/api/game/users',
        '/agent/index/getChildList',
        '/agent/index/getPerformance',
        '/agent/index/getUserInfo/*',
        '/user',
        '/api/team/report',
        '/api/team/performance',
        '/api/team/childlist',
        '/api/financeReport',
        '/api/tier-rewards',
        '/api/claim-tier-reward',
        '/api/share/info',
        '/notify/*'
    ];
}
