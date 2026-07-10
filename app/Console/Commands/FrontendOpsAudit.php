<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class FrontendOpsAudit extends Command
{
    protected $signature = 'FrontendOpsAudit
        {--base-url=https://xy281.eu.cc : Public site base URL}
        {--frontend-dist=/var/www/xy281/latest : Built frontend dist directory}
        {--out-dir=storage/app/frontend-ops-audit : Directory for audit reports}
        {--wap-src=../../project-wap-no-pc/src : Local WAP frontend source directory}
        {--latest-src=../../project-latest/src : Local latest frontend source directory}
        {--image-probe-limit=30 : Number of unique public game image URLs to probe}';

    protected $description = 'Generate read-only operational health reports for public frontend and game API';

    protected $expectedBundleMarkers = [
        'visibleHotGames',
        'visibleRealbetGames',
        'visibleSportGames',
        'visibleGamingGames',
        'visibleConciseGames',
        'visibleFishingGames',
        'visibleJokerGames',
        'visibleLotteryGames',
        'currentPlatformTabs',
        'platformTabsFromGames',
        'categoryTabsFromGames',
        'searchKeyword',
        'filterByKeyword',
        'selectCurrentPlatform',
        'openGamePage',
        'platform_name',
        'category_id',
        'game_code',
        'hotGames',
        'gamingList',
        'sidebar_esports_icon',
        'game_type',
        'game_code',
    ];

    protected $obsoleteBundleMarkers = [
        'slice(0,60)',
        'conciseList.slice',
        '/agent/index/',
    ];

    protected $requiredGameFields = [
        'platform_name',
        'category_id',
        'game_code',
        'name',
        'app_state',
        'is_hot',
        'api_logo_img',
        'mobile_img',
        'gamepic',
        'img',
    ];

    protected $requiredClickFields = [
        'platform_name',
        'category_id',
        'name',
    ];

    protected $subGameCategories = [
        'concise',
        'joker',
        'fishing',
        'gaming',
        'lottery',
        'lhc',
        'jsc',
        'jwc',
        'qkc',
    ];

    protected $categoryMinimums = [
        'concise' => 1000,
        'joker' => 100,
        'fishing' => 10,
        'sport' => 3,
        'gaming' => 3,
    ];

    protected $lotteryCategories = [
        'lottery',
        'lhc',
        'jsc',
        'jwc',
        'qkc',
    ];

    protected $forbiddenPublicRoutes = [
        [
            'name' => 'public_test',
            'path' => 'routes/web.php',
            'needle' => "Route::get('/test'",
        ],
        [
            'name' => 'public_test1',
            'path' => 'routes/web.php',
            'needle' => "Route::get('/test1'",
        ],
        [
            'name' => 'api_agent_auto_login',
            'path' => 'routes/api.php',
            'needle' => "Route::post('/agent/login/auto'",
        ],
        [
            'name' => 'web_agent_auto_login',
            'path' => 'routes/web.php',
            'needle' => "Route::post('/api/login/auto'",
        ],
        [
            'name' => 'api_auto_get_user_money',
            'path' => 'routes/api.php',
            'needle' => "Route::get('/autogetusermoney'",
        ],
        [
            'name' => 'api2_auto_get_user_money',
            'path' => 'routes/api2.php',
            'needle' => "Route::get('/autogetusermoney'",
        ],
    ];

    protected $forbiddenPublicSourceMarkers = [
        [
            'name' => 'web_index_test_method',
            'path' => 'app/Http/Controllers/Web/IndexController.php',
            'needle' => 'public function test(',
        ],
        [
            'name' => 'web_index_signature_test_output',
            'path' => 'app/Http/Controllers/Web/IndexController.php',
            'needle' => 'print_r($data)',
        ],
        [
            'name' => 'web_index_signature_test_helper',
            'path' => 'app/Http/Controllers/Web/IndexController.php',
            'needle' => 'private function generateCode(Array $data)',
        ],
        [
            'name' => 'agent_auto_login_guard_login',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "auth()->guard('agent')->login",
        ],
        [
            'name' => 'stream_token_external_user_id',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "\$request->input('user_id'",
        ],
        [
            'name' => 'stream_token_external_user_id_get',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "\$request->get('user_id'",
        ],
        [
            'name' => 'stream_token_external_user_id_post',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "\$request->post('user_id'",
        ],
        [
            'name' => 'stream_token_external_user_id_query',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "\$request->query('user_id'",
        ],
        [
            'name' => 'stream_token_external_user_id_json',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "\$request->json('user_id'",
        ],
        [
            'name' => 'stream_token_external_username',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "\$request->input('username'",
        ],
        [
            'name' => 'stream_token_external_username_get',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "\$request->get('username'",
        ],
        [
            'name' => 'stream_token_external_username_post',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "\$request->post('username'",
        ],
        [
            'name' => 'stream_token_external_username_query',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "\$request->query('username'",
        ],
        [
            'name' => 'stream_token_external_username_json',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "\$request->json('username'",
        ],
        [
            'name' => 'stream_token_external_user_id_global_request',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "request('user_id'",
        ],
        [
            'name' => 'stream_token_external_username_global_request',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "request('username'",
        ],
        [
            'name' => 'stream_token_external_user_id_data',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "\$data['user_id'",
        ],
        [
            'name' => 'stream_token_external_username_data',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'needle' => "\$data['username'",
        ],
        [
            'name' => 'agent_login_auto_login_source_route',
            'path' => 'resources/views/agent/auth/login.blade.php',
            'needle' => '/api/login/auto',
        ],
        [
            'name' => 'agent_login_auto_login_query_token',
            'path' => 'resources/views/agent/auth/login.blade.php',
            'needle' => "getQueryParam('token')",
        ],
        [
            'name' => 'agent_login_auto_login_query_name',
            'path' => 'resources/views/agent/auth/login.blade.php',
            'needle' => "getQueryParam('name')",
        ],
        [
            'name' => 'agent_login_auto_login_post_token',
            'path' => 'resources/views/agent/auth/login.blade.php',
            'needle' => 'token: token',
        ],
        [
            'name' => 'agent_login_auto_login_post_name',
            'path' => 'resources/views/agent/auth/login.blade.php',
            'needle' => 'name: name',
        ],
    ];

    protected $forbiddenFrontendSourceNeedles = [
        '/agent/index/',
        '/api/login/auto',
        '/agent/login/auto',
        '/api/auto-login-disabled',
        "getQueryParam('token')",
        "getQueryParam('name')",
        'token: token',
        'name: name',
    ];

    protected $forbiddenPublicFiles = [
        'public/nginx_test.php',
    ];

    protected $requiredPublicRouteMarkers = [
        [
            'name' => 'api_team_report_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => [
                "Route::match(['get', 'post'], '/api/team/report'",
                'Agent\IndexController@teamReportApi',
                "->middleware(['api_auth'])",
            ],
        ],
        [
            'name' => 'api_team_report_api_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::match(['get', 'post'], '/team/report'",
                'Agent\IndexController@teamReportApi',
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_performance_api_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::match(['get', 'post'], '/team/performance'",
                'Agent\IndexController@getPerformance',
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_childlist_api_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::match(['get', 'post'], '/team/childlist'",
                'Agent\IndexController@getChildList',
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_add_member_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::post('/team/addMember','Agent\IndexController@addMember')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_set_agent_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::post('/team/setAgent','Agent\IndexController@setAgent')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_recharge_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::post('/team/recharge','Agent\IndexController@rechargeApi')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_fdinfo_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::get('/team/fdinfo','Agent\IndexController@teamFdInfoApi')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_fdlist_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::get('/team/fdList','Agent\IndexController@teamFdListApi')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_setfd_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::post('/team/setFd','Agent\IndexController@teamSetFdApi')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_invite_list_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::get('/team/invite/list','Agent\IndexController@teamInviteListApi')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_invite_update_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::post('/team/invite/update','Agent\IndexController@teamInviteUpdateApi')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_commission_list_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::get('/team/commissionList','Agent\IndexController@teamCommissionListApi')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_user_detail_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::get('/user/{id}','Agent\IndexController@getUserInfo')",
                "Route::get('/team/user/{id}','Agent\IndexController@getUserInfo')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_member_bet_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::get('/team/member/bet/{id}','Agent\IndexController@getMemberBetRecord')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_member_recharge_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::get('/team/member/recharge/{id}','Agent\IndexController@getMemberRechargeRecord')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_member_withdraw_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::get('/team/member/withdraw/{id}','Agent\IndexController@getMemberWithdrawRecord')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'api_team_member_profit_route',
            'path' => 'routes/api.php',
            'markers' => [
                "Route::get('/team/member/profit/{id}','Agent\IndexController@getMemberProfitRecord')",
                "Route::middleware(['crosstttp','api_auth'])->group(function ()",
            ],
        ],
        [
            'name' => 'claim_upgrade_bonus_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => ["Route::post('/api/claim-upgrade-bonus','Member\MemberController@claimUpgradeBonus')->middleware(['api_auth'])"],
        ],
        [
            'name' => 'claim_weekly_salary_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => ["Route::post('/api/claim-weekly-salary','Member\MemberController@claimWeeklySalary')->middleware(['api_auth'])"],
        ],
        [
            'name' => 'claim_monthly_salary_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => ["Route::post('/api/claim-monthly-salary','Member\MemberController@claimMonthlySalary')->middleware(['api_auth'])"],
        ],
        [
            'name' => 'game_users_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => ["Route::post('/api/game/users','Member\MemberController@getGameUsers')->middleware(['api_auth'])"],
        ],
        [
            'name' => 'finance_report_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => ["Route::post('/api/financeReport','Member\MemberController@getFinanceReport')->middleware(['api_auth'])"],
        ],
        [
            'name' => 'tier_rewards_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => ["Route::post('/api/tier-rewards','Member\MemberController@getTierRewards')->middleware(['api_auth'])"],
        ],
        [
            'name' => 'claim_tier_reward_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => ["Route::post('/api/claim-tier-reward','Member\MemberController@claimTierReward')->middleware(['api_auth'])"],
        ],
        [
            'name' => 'agent_child_list_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => ["Route::post('/agent/index/getChildList','Agent\IndexController@getChildList')->middleware(['api_auth'])"],
        ],
        [
            'name' => 'agent_performance_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => ["Route::post('/agent/index/getPerformance','Agent\IndexController@getPerformance')->middleware(['api_auth'])"],
        ],
        [
            'name' => 'agent_user_info_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => ["Route::post('/agent/index/getUserInfo/{id}','Agent\IndexController@getUserInfo')->middleware(['api_auth'])"],
        ],
        [
            'name' => 'web_api_team_performance_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => ["Route::get('/api/team/performance','Agent\IndexController@getPerformance')->middleware(['api_auth'])"],
        ],
        [
            'name' => 'web_api_team_childlist_requires_api_auth',
            'path' => 'routes/web.php',
            'markers' => ["Route::get('/api/team/childlist','Agent\IndexController@getChildList')->middleware(['api_auth'])"],
        ],
    ];

    protected $requiredPublicSourceMarkers = [
        [
            'name' => 'api_login_uses_active_token_user',
            'path' => 'app/Http/Controllers/Api/AppController.php',
            'markers' => [
                'protected function activeUserFromApiToken',
                'protected function isBlockedApiUser',
                "\$lastsession = \$data['lastsession'] ?? '';",
                "\$user = \$this->activeUserFromApiToken(\$lastsession, true);",
            ],
        ],
        [
            'name' => 'stream_api_uses_bearer_current_user',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'markers' => [
                'protected function activeUserFromBearer',
                'protected function isBlockedApiUser',
                'public function getStreamToken(Request $request)',
                'public function createStreamChannel(Request $request)',
                '$jwtToken = $this->generateStreamToken($userId, $apiKey, $secret);',
                "'user_id' => (string)\$userId",
                "'members' => [(string)\$userId]",
                "'created_by_id' => (string)\$userId",
            ],
            'count_markers' => [
                [
                    'needle' => '$user = $this->activeUserFromBearer($request);',
                    'min_count' => 2,
                ],
                [
                    'needle' => '$userId = $user->id;',
                    'min_count' => 2,
                ],
            ],
        ],
    ];

    public function handle()
    {
        $outDir = $this->resolvePath($this->option('out-dir'));
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $baseUrl = rtrim($this->option('base-url'), '/');
        $distDir = rtrim($this->resolvePath($this->option('frontend-dist')), '/\\');
        $issues = [];

        $frontend = $this->frontendStatus($distDir, $issues);
        $public = $this->publicStatus($baseUrl, $issues);

        $report = [
            'generated_at' => date('c'),
            'base_url' => $baseUrl,
            'frontend_dist' => $distDir,
            'issue_counts' => $this->issueCounts($issues),
            'frontend' => $frontend,
            'public' => $public,
            'issues' => $issues,
        ];

        $this->writeJson($outDir . '/frontend_ops_audit.json', $report);
        $this->writeCsv($outDir . '/frontend_ops_issues.csv', $issues, [
            'severity',
            'code',
            'message',
            'count',
            'sample',
        ]);
        $this->writeCsv($outDir . '/frontend_api_category_counts.csv', $public['api']['category_counts'] ?? [], [
            'category_id',
            'count',
        ]);
        $this->writeCsv($outDir . '/frontend_api_filter_checks.csv', $public['api']['filter_checks'] ?? [], [
            'label',
            'status',
            'count',
            'expected_category',
            'expected_platform',
            'expected_count',
            'wrong_category_count',
            'wrong_platform_count',
            'sample',
        ]);
        $this->writeCsv($outDir . '/frontend_forbidden_public_routes.csv', $public['forbidden_public_routes'] ?? [], [
            'name',
            'path',
            'present',
        ]);
        $this->writeCsv($outDir . '/frontend_forbidden_public_sources.csv', $public['forbidden_public_sources'] ?? [], [
            'name',
            'path',
            'present',
            'needle',
        ]);
        $this->writeCsv($outDir . '/frontend_forbidden_public_files.csv', $public['forbidden_public_files'] ?? [], [
            'path',
            'present',
        ]);
        $this->writeCsv($outDir . '/frontend_required_public_routes.csv', $public['required_public_routes'] ?? [], [
            'name',
            'path',
            'missing_markers',
        ]);
        $this->writeCsv($outDir . '/frontend_required_public_sources.csv', $public['required_public_sources'] ?? [], [
            'name',
            'path',
            'missing_markers',
        ]);
        $this->writeCsv($outDir . '/frontend_wap_source_checks.csv', $frontend['wap_source']['checks'] ?? [], [
            'name',
            'path',
            'passed',
            'detail',
        ]);
        $this->writeCsv($outDir . '/frontend_latest_source_checks.csv', $frontend['latest_source']['checks'] ?? [], [
            'name',
            'path',
            'passed',
            'detail',
        ]);
        $this->writeCsv($outDir . '/frontend_wap_game_card_calls.csv', $frontend['wap_source']['game_card_calls'] ?? [], [
            'path',
            'line',
            'passed',
            'missing',
            'expression',
        ]);
        $this->writeCsv($outDir . '/frontend_wap_legacy_endpoint_refs.csv', $frontend['wap_source']['legacy_endpoint_refs'] ?? [], [
            'path',
            'line',
            'needle',
        ]);

        $this->info(sprintf(
            'homepage=%s app=%s api=%s games=%d hot=%d issues=%d critical=%d warnings=%d',
            $public['homepage']['status'] ?? 'n/a',
            $public['app']['status'] ?? 'n/a',
            $public['api']['status'] ?? 'n/a',
            $public['api']['game_count'] ?? 0,
            $public['api']['hot_count'] ?? 0,
            count($issues),
            $report['issue_counts']['critical'] ?? 0,
            $report['issue_counts']['warning'] ?? 0
        ));
        $this->comment('Reports: ' . $outDir);

        return 0;
    }

    protected function frontendStatus($distDir, array &$issues)
    {
        $indexPath = $distDir . '/index.html';
        $status = [
            'index_path' => $indexPath,
            'index_exists' => is_file($indexPath),
            'bundle_path' => '',
            'bundle_exists' => false,
            'bundle_size' => 0,
            'expected_markers' => [],
            'obsolete_markers' => [],
            'wap_source' => $this->wapSourceStatus($issues),
            'latest_source' => $this->latestSourceStatus($issues),
        ];

        if (!is_file($indexPath)) {
            $this->addIssue($issues, 'critical', 'frontend_index_missing', 'Built frontend index.html is missing.', 1, $indexPath);
            return $status;
        }

        $index = file_get_contents($indexPath);
        if (!preg_match('/static\/js\/app\.[^"\']+\.js/', $index, $match)) {
            $this->addIssue($issues, 'critical', 'frontend_bundle_reference_missing', 'index.html does not reference app bundle.', 1, $indexPath);
            return $status;
        }

        $bundlePath = $distDir . '/' . $match[0];
        $status['bundle_path'] = $bundlePath;
        $status['bundle_exists'] = is_file($bundlePath);
        $status['bundle_size'] = is_file($bundlePath) ? filesize($bundlePath) : 0;
        if (!is_file($bundlePath)) {
            sleep(2);
            $index = file_get_contents($indexPath);
            if (preg_match('/static\/js\/app\.[^"\']+\.js/', $index, $retryMatch)) {
                $retryBundlePath = $distDir . '/' . $retryMatch[0];
                $status['bundle_path'] = $retryBundlePath;
                $status['bundle_exists'] = is_file($retryBundlePath);
                $status['bundle_size'] = is_file($retryBundlePath) ? filesize($retryBundlePath) : 0;
                if (is_file($retryBundlePath)) {
                    $bundlePath = $retryBundlePath;
                }
            }
        }
        if (!is_file($bundlePath)) {
            $this->addIssue($issues, 'critical', 'frontend_bundle_missing', 'Referenced app bundle file is missing.', 1, $bundlePath);
            return $status;
        }

        $bundle = file_get_contents($bundlePath);
        foreach ($this->expectedBundleMarkers as $marker) {
            $present = strpos($bundle, $marker) !== false;
            $status['expected_markers'][$marker] = $present;
            if (!$present) {
                $this->addIssue($issues, 'critical', 'frontend_expected_marker_missing', 'Expected frontend fix marker is missing.', 1, $marker);
            }
        }
        foreach ($this->obsoleteBundleMarkers as $marker) {
            $present = strpos($bundle, $marker) !== false;
            $status['obsolete_markers'][$marker] = $present;
            if ($present) {
                if ($marker === '/agent/index/') {
                    $this->addIssue($issues, 'critical', 'frontend_obsolete_agent_index_marker_present', 'Legacy /agent/index API marker is still present in the built frontend bundle.', 1, $marker);
                    continue;
                }
                $this->addIssue($issues, 'warning', 'frontend_obsolete_marker_present', 'Obsolete frontend fallback marker is still present.', 1, $marker);
            }
        }

        return $status;
    }

    protected function wapSourceStatus(array &$issues)
    {
        $srcDir = rtrim(str_replace('\\', '/', $this->resolvePath($this->option('wap-src'))), '/');
        $gamePagePath = $srcDir . '/components/mode/gamePage.vue';
        $indexPagePath = $srcDir . '/components/mode/index.vue';
        $appPath = $srcDir . '/App.vue';
        $casinoPagePath = $srcDir . '/pages/cassino/casinoplaypage.vue';
        $status = [
            'src_dir' => $srcDir,
            'game_page_path' => $gamePagePath,
            'index_page_path' => $indexPagePath,
            'app_path' => $appPath,
            'casino_page_path' => $casinoPagePath,
            'checks' => [],
            'game_card_calls' => [],
            'legacy_endpoint_refs' => [],
        ];

        if (!is_dir($srcDir)) {
            $status['checks'][] = [
                'name' => 'wap_source_dir_skipped',
                'path' => $srcDir,
                'passed' => true,
                'detail' => 'WAP source is not deployed on production; source checks are local-only.',
            ];
            return $status;
        }

        $status['legacy_endpoint_refs'] = $this->sourceNeedleMatches($srcDir, [
            '/agent/index/getPerformance',
            '/agent/index/getChildList',
            '/agent/index/getUserInfo',
            '/agent/index/',
        ]);
        $this->addSourceCheck(
            $status['checks'],
            $issues,
            'wap_source_no_legacy_agent_index_endpoints',
            $srcDir,
            count($status['legacy_endpoint_refs']) === 0,
            'WAP source must not reference legacy /agent/index/* endpoints; use /api/team/* or /api/user/{id}.',
            'wap_source_legacy_agent_index_endpoint_present'
        );

        $gamePageSource = $this->readAbsoluteSource($gamePagePath);
        $gamePageExists = $gamePageSource !== null;
        $this->addSourceCheck($status['checks'], $issues, 'wap_game_page_exists', $gamePagePath, $gamePageExists, 'gamePage.vue is missing.', 'wap_game_page_missing');
        if ($gamePageExists) {
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_game_page_uses_iframe_src_binding',
                $gamePagePath,
                strpos($gamePageSource, ':src="iframeSrc"') !== false,
                'gamePage.vue iframe must bind :src="iframeSrc".',
                'wap_game_page_iframe_src_binding_missing'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_game_page_no_content_window_replace',
                $gamePagePath,
                strpos($gamePageSource, 'contentWindow.location.replace') === false,
                'gamePage.vue must not keep contentWindow.location.replace.',
                'wap_game_page_content_window_replace_present'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_game_page_no_updated_refresh_hook',
                $gamePagePath,
                !preg_match('/(^|\n)\s*updated\s*\(/', $gamePageSource),
                'gamePage.vue must not keep an updated() iframe refresh hook.',
                'wap_game_page_updated_hook_present'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_game_page_has_param_guard',
                $gamePagePath,
                strpos($gamePageSource, 'showGameOpenError') !== false
                    && strpos($gamePageSource, 'safeName') !== false
                    && strpos($gamePageSource, 'safeType') !== false
                    && strpos($gamePageSource, 'safeCode') !== false,
                'gamePage.vue must reject missing name/type/code before calling /api/getGameUrl.',
                'wap_game_page_param_guard_missing'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_game_page_has_empty_query_guard',
                $gamePagePath,
                strpos($gamePageSource, 'that.showGameOpenError();') !== false,
                'gamePage.vue must fail closed when opened without game, agent, customer, or app query params.',
                'wap_game_page_empty_query_guard_missing'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_game_page_normalizes_open_query',
                $gamePagePath,
                $this->hasGamePageOpenQueryNormalizer($gamePageSource),
                'gamePage.vue must normalize legacy type/code query params and support alternate game code fields.',
                'wap_game_page_open_query_normalizer_missing'
            );
        }

        $appSource = $this->readAbsoluteSource($appPath);
        $appExists = $appSource !== null;
        $this->addSourceCheck($status['checks'], $issues, 'wap_app_exists', $appPath, $appExists, 'App.vue is missing.', 'wap_app_missing');
        if ($appExists) {
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_app_site_state_fails_open',
                $appPath,
                strpos($appSource, 'v-if="!isSiteMaintenance"') !== false
                    && strpos($appSource, 'v-if="isSiteMaintenance"') !== false
                    && strpos($appSource, 'isSiteMaintenance()') !== false
                    && strpos($appSource, "return state === 0 || state === '0';") !== false,
                'App.vue must render the main route unless site_state is explicitly 0, otherwise empty /api/app responses can blank the homepage.',
                'wap_app_site_state_can_blank_homepage'
            );
            $openGamePageBody = $this->extractJavascriptMethodBody($appSource, 'openGamePage');
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_app_open_game_page_exists',
                $appPath,
                $openGamePageBody !== null,
                'App.vue openGamePage method is missing.',
                'wap_app_open_game_page_missing'
            );
            if ($openGamePageBody !== null) {
                $this->addSourceCheck(
                    $status['checks'],
                    $issues,
                    'wap_app_open_game_page_encodes_query',
                    $appPath,
                    strpos($openGamePageBody, 'encodeURIComponent') !== false,
                    'App.vue openGamePage must encode game query parameter values with encodeURIComponent.',
                    'wap_app_open_game_page_query_not_encoded'
                );
                $this->addSourceCheck(
                    $status['checks'],
                    $issues,
                    'wap_app_open_game_page_has_param_guard',
                    $appPath,
                    strpos($openGamePageBody, '!name || !type || !code') !== false,
                    'App.vue openGamePage must reject missing game open parameters before navigation.',
                    'wap_app_open_game_page_param_guard_missing'
                );
                $this->addSourceCheck(
                    $status['checks'],
                    $issues,
                    'wap_app_open_game_page_preserves_game_name',
                    $appPath,
                    strpos($openGamePageBody, 'gameName') !== false
                        && strpos($openGamePageBody, "params.push(['game_name', gameName])") !== false,
                    'App.vue openGamePage must preserve game_name in the gamePage query for operations/debug visibility.',
                    'wap_app_open_game_page_game_name_missing'
                );
            }
        }

        $casinoPageSource = $this->readAbsoluteSource($casinoPagePath);
        $casinoPageExists = $casinoPageSource !== null;
        $this->addSourceCheck($status['checks'], $issues, 'wap_casino_page_exists', $casinoPagePath, $casinoPageExists, 'casinoplaypage.vue is missing.', 'wap_casino_page_missing');
        if ($casinoPageExists) {
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_casino_page_has_param_guard',
                $casinoPagePath,
                strpos($casinoPageSource, 'showGameOpenError') !== false
                    && strpos($casinoPageSource, 'safeName') !== false
                    && strpos($casinoPageSource, 'safeType') !== false
                    && strpos($casinoPageSource, 'safeCode') !== false,
                'casinoplaypage.vue must reject missing name/type/code before calling /api/getGameUrl.',
                'wap_casino_page_param_guard_missing'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_casino_page_non_200_stops_loading',
                $casinoPagePath,
                strpos($casinoPageSource, 'that.showGameOpenError();') !== false,
                'casinoplaypage.vue must leave loading state on non-200 /api/getGameUrl responses.',
                'wap_casino_page_non_200_guard_missing'
            );
        }

        $indexPageSource = $this->readAbsoluteSource($indexPagePath);
        $indexPageExists = $indexPageSource !== null;
        $this->addSourceCheck($status['checks'], $issues, 'wap_index_page_exists', $indexPagePath, $indexPageExists, 'index.vue is missing.', 'wap_index_page_missing');
        if ($indexPageExists) {
            $platformBody = $this->extractJavascriptMethodBody($indexPageSource, 'getGamePlatform');
            $categoryBody = $this->extractJavascriptMethodBody($indexPageSource, 'getGameCategory');
            $codeBody = $this->extractJavascriptMethodBody($indexPageSource, 'getGameCode');
            $openGameBody = $this->extractJavascriptMethodBody($indexPageSource, 'openGame');
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_index_game_field_fallbacks_match_pg51',
                $indexPagePath,
                $platformBody !== null
                    && $categoryBody !== null
                    && $codeBody !== null
                    && strpos($platformBody, 'catecode') !== false
                    && strpos($platformBody, 'plat_name') !== false
                    && strpos($platformBody, 'platname') !== false
                    && strpos($platformBody, "'name'") === false
                    && strpos($platformBody, '"name"') === false
                    && strpos($categoryBody, 'category_id') !== false
                    && strpos($categoryBody, 'gametype') !== false
                    && strpos($categoryBody, 'catecode') === false
                    && strpos($categoryBody, 'gamecode') === false
                    && strpos($codeBody, 'gamecode') !== false
                    && strpos($codeBody, 'gameId') !== false
                    && strpos($codeBody, 'type') !== false,
                'WAP index.vue must keep platform/category/game-code fallback fields separated for game opening.',
                'wap_index_game_field_fallbacks_invalid'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_index_open_game_uses_normalized_fields',
                $indexPagePath,
                $openGameBody !== null
                    && strpos($openGameBody, 'this.getGamePlatform(item)') !== false
                    && strpos($openGameBody, 'this.getGameCategory(item, fallbackCategory)') !== false
                    && strpos($openGameBody, 'this.getGameCode(item)') !== false
                    && strpos($openGameBody, '!platform_name || !category_id || !game_code') !== false
                    && strpos($openGameBody, 'this.$parent.openGamePage(platform_name, category_id || fallbackCategory, game_code, this.getGameName(item))') !== false,
                'WAP index.vue openGame must normalize platform/category/game_code/name and fail closed before navigation.',
                'wap_index_open_game_normalized_params_missing'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_index_dynamic_tabs_and_search',
                $indexPagePath,
                strpos($indexPageSource, 'platformTabsFromGames') !== false
                    && strpos($indexPageSource, 'fishingPlatformTabs') !== false
                    && strpos($indexPageSource, 'jokerPlatformTabs') !== false
                    && strpos($indexPageSource, 'concisePlatformTabs') !== false
                    && strpos($indexPageSource, 'gamingPlatformTabs') !== false
                    && strpos($indexPageSource, 'hotCategoryTabs') !== false
                    && strpos($indexPageSource, 'realbetPlatformTabs') !== false
                    && strpos($indexPageSource, 'sportPlatformTabs') !== false
                    && strpos($indexPageSource, 'lotteryPlatformTabs') !== false
                    && strpos($indexPageSource, 'visibleRealbetGames') !== false
                    && strpos($indexPageSource, 'visibleSportGames') !== false
                    && strpos($indexPageSource, 'selectHotCategory') !== false
                    && strpos($indexPageSource, 'isSearchableGameType') !== false
                    && strpos($indexPageSource, 'applyGameFilters') !== false
                    && strpos($indexPageSource, "selectedPlatform = 'PG'") === false,
                'WAP index.vue must generate platform tabs from game data and search the current game category.',
                'wap_index_dynamic_tabs_missing'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_index_gaming_and_lottery_visibility',
                $indexPagePath,
                strpos($indexPageSource, 'gameType == 7') !== false
                    && strpos($indexPageSource, 'gameType === 7') !== false
                    && strpos($indexPageSource, 'visibleGamingGames') !== false
                    && strpos($indexPageSource, 'selectedGamingPlatform') !== false
                    && strpos($indexPageSource, 'gamingList') !== false
                    && substr_count($indexPageSource, 'lotteryList || []') >= 2,
                'WAP index.vue must render gaming games and include lotteryList in all-lottery views.',
                'wap_index_gaming_or_lottery_visibility_missing'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_index_lottery_tabs_use_all_lottery_games',
                $indexPagePath,
                strpos($indexPageSource, 'allLotteryGames') !== false
                    && strpos($indexPageSource, 'return this.platformTabsFromGames(this.allLotteryGames)') !== false
                    && strpos($indexPageSource, 'return this.allLotteryGames;') !== false,
                'WAP lottery platform tabs must be generated from the merged lottery family, not only lotteryList.',
                'wap_index_lottery_tabs_not_merged'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_index_empty_state_and_stable_keys',
                $indexPagePath,
                strpos($indexPageSource, 'gameItemKey') !== false
                    && strpos($indexPageSource, ':key="gameItemKey') !== false
                    && strpos($indexPageSource, 'game-empty') !== false
                    && strpos($indexPageSource, 'clearCurrentFilter') !== false
                    && strpos($indexPageSource, 'hasActiveGameFilter') !== false,
                'WAP index.vue must use stable game card keys and show a recoverable empty state for filters/search.',
                'wap_index_empty_state_or_stable_keys_missing'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'wap_index_image_fallback_and_proxy',
                $indexPagePath,
                strpos($indexPageSource, 'fallbackGameImage') !== false
                    && strpos($indexPageSource, 'handleGameImageError') !== false
                    && strpos($indexPageSource, 'getOwnedImageUrl') !== false
                    && strpos($indexPageSource, '/api/image-proxy?u=') !== false
                    && strpos($indexPageSource, 'setGameImgFallback') === false
                    && strpos($indexPageSource, 'getFallbackGameImg') === false,
                'WAP index.vue must use generated fallback images and image proxy handling for game cards.',
                'wap_index_image_fallback_missing'
            );
        }

        $status['game_card_calls'] = $this->wapGameCardCallStatus($srcDir, $issues);

        return $status;
    }

    protected function latestSourceStatus(array &$issues)
    {
        $srcDir = rtrim(str_replace('\\', '/', $this->resolvePath($this->option('latest-src'))), '/');
        $gamePagePath = $srcDir . '/components/mode/gamePage.vue';
        $appPath = $srcDir . '/App.vue';
        $status = [
            'src_dir' => $srcDir,
            'game_page_path' => $gamePagePath,
            'app_path' => $appPath,
            'checks' => [],
        ];

        if (!is_dir($srcDir)) {
            $status['checks'][] = [
                'name' => 'latest_source_dir_skipped',
                'path' => $srcDir,
                'passed' => true,
                'detail' => 'Latest source is not deployed on production; source checks are local-only.',
            ];
            return $status;
        }

        $gamePageSource = $this->readAbsoluteSource($gamePagePath);
        $gamePageExists = $gamePageSource !== null;
        $this->addSourceCheck($status['checks'], $issues, 'latest_game_page_exists', $gamePagePath, $gamePageExists, 'project-latest gamePage.vue is missing.', 'latest_game_page_missing');

        $appSource = $this->readAbsoluteSource($appPath);
        $appExists = $appSource !== null;
        $this->addSourceCheck($status['checks'], $issues, 'latest_app_exists', $appPath, $appExists, 'project-latest App.vue is missing.', 'latest_app_missing');

        if ($appExists) {
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'latest_app_site_state_fails_open',
                $appPath,
                strpos($appSource, 'v-if="!isSiteMaintenance"') !== false
                    && strpos($appSource, 'v-if="isSiteMaintenance"') !== false
                    && strpos($appSource, 'isSiteMaintenance()') !== false
                    && strpos($appSource, "return state === 0 || state === '0';") !== false,
                'project-latest App.vue must render the main route unless site_state is explicitly 0.',
                'latest_app_site_state_can_blank_homepage'
            );
        }

        if ($gamePageExists) {
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'latest_game_page_normalizes_open_query',
                $gamePagePath,
                $this->hasGamePageOpenQueryNormalizer($gamePageSource),
                'project-latest gamePage.vue must normalize legacy type/code query params and support alternate game code fields.',
                'latest_game_page_open_query_normalizer_missing'
            );
            $this->addSourceCheck(
                $status['checks'],
                $issues,
                'latest_game_page_has_param_guard',
                $gamePagePath,
                strpos($gamePageSource, 'showGameOpenError') !== false
                    && strpos($gamePageSource, 'safeName') !== false
                    && strpos($gamePageSource, 'safeType') !== false
                    && strpos($gamePageSource, 'safeCode') !== false,
                'project-latest gamePage.vue must reject missing name/type/code before calling /api/getGameUrl.',
                'latest_game_page_param_guard_missing'
            );
        }

        return $status;
    }

    protected function addSourceCheck(array &$checks, array &$issues, $name, $path, $passed, $detail, $issueCode)
    {
        $checks[] = [
            'name' => $name,
            'path' => $path,
            'passed' => (bool)$passed,
            'detail' => $passed ? '' : $detail,
        ];
        if (!$passed) {
            $this->addIssue($issues, 'critical', $issueCode, $detail, 1, $path);
        }
    }

    protected function hasGamePageOpenQueryNormalizer($source)
    {
        $baseMarkersPresent = strpos($source, 'normalizeGameQuery') !== false
            && strpos($source, 'isGameCategory') !== false
            && strpos($source, 'findLocalGame') !== false
            && strpos($source, 'const gameParams = that.normalizeGameQuery(query);') !== false
            && strpos($source, 'that.goGamePage(gameParams.name, gameParams.type, gameParams.code)') !== false;

        $legacyPlatformFallback = strpos($source, 'item.platform_name || item.plat_name || item.catecode || item.platform || item.platname || item.platformCode') !== false;
        $expandedPlatformFallback = strpos($source, 'getObjectField') !== false
            && strpos($source, "'platform_name'") !== false
            && strpos($source, "'platform_code'") !== false
            && strpos($source, "'provider_code'") !== false;

        $legacyCodeFallback = strpos($source, 'item.game_code || item.gamecode || item.code || item.gameId || item.type') !== false;
        $expandedCodeFallback = strpos($source, 'getObjectField') !== false
            && strpos($source, "'game_code'") !== false
            && strpos($source, "'GameCode'") !== false
            && strpos($source, "'game_id'") !== false;

        return $baseMarkersPresent
            && ($legacyPlatformFallback || $expandedPlatformFallback)
            && ($legacyCodeFallback || $expandedCodeFallback);
    }

    protected function wapGameCardCallStatus($srcDir, array &$issues)
    {
        $modeDir = $srcDir . '/components/mode';
        $rows = [];
        if (!is_dir($modeDir)) {
            $this->addIssue($issues, 'critical', 'wap_game_card_scan_dir_missing', 'WAP game card scan directory is missing.', 1, $modeDir);
            return $rows;
        }

        $invalidSamples = [];
        foreach ($this->vueFiles($modeDir) as $path) {
            $source = $this->readAbsoluteSource($path);
            if ($source === null) {
                continue;
            }
            foreach ($this->collectOpenGamePageCalls($source) as $call) {
                $missing = $this->missingGameCardOpenArgs($call['args']);
                $passed = !$missing;
                $rows[] = [
                    'path' => $path,
                    'line' => $call['line'],
                    'passed' => $passed,
                    'missing' => implode('|', $missing),
                    'expression' => $call['expression'],
                ];
                if (!$passed) {
                    $this->sampleAppend($invalidSamples, $path . ':' . $call['line'] . ' missing=' . implode('|', $missing));
                }
            }
        }

        if (!$rows) {
            $this->addIssue($issues, 'critical', 'wap_game_card_open_call_missing', 'No WAP game card openGamePage calls were found.', 1, $modeDir);
        }
        if ($invalidSamples) {
            $this->addIssue(
                $issues,
                'critical',
                'wap_game_card_open_args_invalid',
                'WAP game card openGamePage calls must pass item.platform_name, item.category_id fallback, and item.game_code.',
                count($invalidSamples),
                implode(' | ', $invalidSamples)
            );
        }

        return $rows;
    }

    protected function missingGameCardOpenArgs(array $args)
    {
        $missing = [];
        $platformArg = $args[0] ?? '';
        $categoryArg = $args[1] ?? '';
        $codeArg = $args[2] ?? '';

        if (!preg_match('/\bplatform_name\b/', $platformArg)) {
            $missing[] = 'platform_name';
        }
        if (!preg_match('/\bcategory_id\b/', $categoryArg) || strpos($categoryArg, '||') === false) {
            $missing[] = 'category_id_fallback';
        }
        if (!preg_match('/\bgame_code\b/', $codeArg)) {
            $missing[] = 'game_code';
        }

        return $missing;
    }

    protected function vueFiles($dir)
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && strtolower($file->getExtension()) === 'vue') {
                $files[] = str_replace('\\', '/', $file->getPathname());
            }
        }
        sort($files);

        return $files;
    }

    protected function collectOpenGamePageCalls($source)
    {
        $calls = [];
        $offset = 0;
        while (preg_match('/\bopenGamePage\s*\(/', $source, $match, PREG_OFFSET_CAPTURE, $offset)) {
            $start = $match[0][1];
            $openParen = strpos($source, '(', $start);
            $closeParen = $this->findMatchingDelimiter($source, $openParen, '(', ')');
            if ($closeParen === null) {
                break;
            }
            $argsSource = substr($source, $openParen + 1, $closeParen - $openParen - 1);
            $calls[] = [
                'line' => $this->lineNumberAt($source, $start),
                'expression' => trim(substr($source, $start, $closeParen - $start + 1)),
                'args' => $this->splitTopLevelArguments($argsSource),
            ];
            $offset = $closeParen + 1;
        }

        return $calls;
    }

    protected function splitTopLevelArguments($source)
    {
        $args = [];
        $start = 0;
        $length = strlen($source);
        $depth = 0;
        $quote = null;
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $source[$i];
            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === '"' || $char === "'" || $char === '`') {
                $quote = $char;
                continue;
            }
            if ($char === '(' || $char === '[' || $char === '{') {
                $depth++;
                continue;
            }
            if ($char === ')' || $char === ']' || $char === '}') {
                $depth = max(0, $depth - 1);
                continue;
            }
            if ($char === ',' && $depth === 0) {
                $args[] = trim(substr($source, $start, $i - $start));
                $start = $i + 1;
            }
        }

        $tail = trim(substr($source, $start));
        if ($tail !== '' || $source !== '') {
            $args[] = $tail;
        }

        return $args;
    }

    protected function extractJavascriptMethodBody($source, $methodName)
    {
        if (!preg_match('/\b' . preg_quote($methodName, '/') . '\s*\([^)]*\)\s*\{/', $source, $match, PREG_OFFSET_CAPTURE)) {
            return null;
        }

        $openBrace = strpos($source, '{', $match[0][1]);
        $closeBrace = $this->findMatchingDelimiter($source, $openBrace, '{', '}');
        if ($closeBrace === null) {
            return null;
        }

        return substr($source, $openBrace + 1, $closeBrace - $openBrace - 1);
    }

    protected function findMatchingDelimiter($source, $openOffset, $openChar, $closeChar)
    {
        if ($openOffset === false || $openOffset === null) {
            return null;
        }

        $length = strlen($source);
        $depth = 0;
        $quote = null;
        $escaped = false;
        for ($i = $openOffset; $i < $length; $i++) {
            $char = $source[$i];
            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === '"' || $char === "'" || $char === '`') {
                $quote = $char;
                continue;
            }
            if ($char === $openChar) {
                $depth++;
                continue;
            }
            if ($char === $closeChar) {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return null;
    }

    protected function lineNumberAt($source, $offset)
    {
        return substr_count(substr($source, 0, $offset), "\n") + 1;
    }

    protected function publicStatus($baseUrl, array &$issues)
    {
        $forbiddenPublicRoutes = $this->forbiddenPublicRouteStatus($issues);
        $forbiddenPublicSources = $this->forbiddenPublicSourceStatus($issues);
        $forbiddenPublicFiles = $this->forbiddenPublicFileStatus($issues);
        $requiredPublicRoutes = $this->requiredPublicRouteStatus($issues);
        $requiredPublicSources = $this->requiredPublicSourceStatus($issues);
        $homepage = $this->httpGet($baseUrl . '/');
        if ((int)$homepage['status'] !== 200) {
            $this->addIssue($issues, 'critical', 'homepage_http_not_200', 'Homepage HTTP status is not 200.', 1, 'status=' . $homepage['status'] . ' error=' . $homepage['error']);
        }

        $app = $this->appStatus($baseUrl, $issues);
        $api = $this->httpGet($baseUrl . '/api/game/list?category=');
        $apiStatus = [
            'status' => $api['status'],
            'error' => $api['error'],
            'game_count' => 0,
            'hot_count' => 0,
            'hot_missing_image_count' => 0,
            'unknown_category_count' => 0,
            'field_missing_count' => 0,
            'click_field_missing_count' => 0,
            'blank_sub_game_code_count' => 0,
            'blank_lobby_game_code_count' => 0,
            'disabled_app_state_count' => 0,
            'empty_api_logo_img' => 0,
            'empty_mobile_img' => 0,
            'empty_gamepic' => 0,
            'empty_img' => 0,
            'malformed_api_logo_img' => 0,
            'malformed_mobile_img' => 0,
            'malformed_gamepic' => 0,
            'malformed_img' => 0,
            'image_probe_limit' => (int)$this->option('image-probe-limit'),
            'image_probe_count' => 0,
            'broken_image_count' => 0,
            'broken_image_samples' => [],
            'has_is_hot_field' => false,
            'category_counts' => [],
            'category_minimums' => [],
            'filter_checks' => [],
            'get_game_url_probe' => [],
        ];

        if ((int)$api['status'] !== 200) {
            $this->addIssue($issues, 'critical', 'game_list_http_not_200', '/api/game/list HTTP status is not 200.', 1, 'status=' . $api['status'] . ' error=' . $api['error']);
            return ['homepage' => $homepage, 'app' => $app, 'api' => $apiStatus, 'forbidden_public_routes' => $forbiddenPublicRoutes, 'forbidden_public_sources' => $forbiddenPublicSources, 'forbidden_public_files' => $forbiddenPublicFiles, 'required_public_routes' => $requiredPublicRoutes, 'required_public_sources' => $requiredPublicSources];
        }

        $json = json_decode($api['body'], true);
        if (!is_array($json) || (string)($json['code'] ?? '') !== '200' || !isset($json['data']) || !is_array($json['data'])) {
            $this->addIssue($issues, 'critical', 'game_list_json_invalid', '/api/game/list response is not valid code=200 JSON.', 1, substr((string)$api['body'], 0, 200));
            return ['homepage' => $homepage, 'app' => $app, 'api' => $apiStatus, 'forbidden_public_routes' => $forbiddenPublicRoutes, 'forbidden_public_sources' => $forbiddenPublicSources, 'forbidden_public_files' => $forbiddenPublicFiles, 'required_public_routes' => $requiredPublicRoutes, 'required_public_sources' => $requiredPublicSources];
        }

        $allowedCategories = array_fill_keys([
            'realbet',
            'sport',
            'fishing',
            'joker',
            'concise',
            'lottery',
            'lhc',
            'jsc',
            'jwc',
            'qkc',
            'gaming',
        ], true);
        $categoryCounts = [];
        $platformCategoryCounts = [];
        $imageProbeLimit = max(0, (int)$this->option('image-probe-limit'));
        $imageProbeUrls = [];
        $malformedSamples = [];
        $fieldMissingSamples = [];
        $clickMissingSamples = [];
        $blankSubGameCodeSamples = [];
        $blankLobbyGameCodeSamples = [];
        $unknownCategorySamples = [];
        $disabledAppStateSamples = [];
        $clickProbeCandidate = null;
        foreach ($json['data'] as $game) {
            if (!is_array($game)) {
                $apiStatus['field_missing_count']++;
                $this->sampleAppend($fieldMissingSamples, 'non_object_row');
                continue;
            }

            $apiStatus['game_count']++;
            if (array_key_exists('is_hot', $game)) {
                $apiStatus['has_is_hot_field'] = true;
            }
            if ((int)($game['is_hot'] ?? 0) === 1) {
                $apiStatus['hot_count']++;
            }

            $platform = trim((string)$this->gameField($game, ['platform_name', 'catecode']));
            $category = strtolower(trim((string)$this->gameField($game, ['category_id', 'gametype'])));
            $gameCode = trim((string)$this->gameField($game, ['game_code', 'gamecode']));
            $name = trim((string)$this->gameField($game, ['name', 'gamename', 'game_name', 'title']));

            $missingFields = [];
            foreach ($this->requiredGameFields as $field) {
                if (!array_key_exists($field, $game)) {
                    $missingFields[] = $field;
                }
            }
            if ($missingFields) {
                $apiStatus['field_missing_count']++;
                $this->sampleAppend($fieldMissingSamples, $platform . '/' . $category . '/' . $gameCode . ' missing=' . implode(',', $missingFields));
            }

            $missingClickFields = [];
            if ($platform === '') {
                $missingClickFields[] = 'platform_name';
            }
            if ($category === '') {
                $missingClickFields[] = 'category_id';
            }
            if ($name === '') {
                $missingClickFields[] = 'name';
            }
            if ($missingClickFields) {
                $apiStatus['click_field_missing_count']++;
                $this->sampleAppend($clickMissingSamples, $platform . '/' . $category . '/' . $gameCode . ' missing=' . implode(',', $missingClickFields));
            }

            if ($gameCode === '') {
                if (in_array($category, $this->subGameCategories, true)) {
                    $apiStatus['blank_sub_game_code_count']++;
                    $this->sampleAppend($blankSubGameCodeSamples, $platform . '/' . $category . '/' . $name);
                } else {
                    $apiStatus['blank_lobby_game_code_count']++;
                    $this->sampleAppend($blankLobbyGameCodeSamples, $platform . '/' . $category . '/' . $name);
                }
            }

            if ((int)($game['app_state'] ?? 0) !== 1) {
                $apiStatus['disabled_app_state_count']++;
                $this->sampleAppend($disabledAppStateSamples, $platform . '/' . $category . '/' . $gameCode . ' app_state=' . ($game['app_state'] ?? 'missing'));
            }

            if ($category === '' || !isset($allowedCategories[$category])) {
                $apiStatus['unknown_category_count']++;
                $this->sampleAppend($unknownCategorySamples, $platform . '/' . $category . '/' . $gameCode);
            }

            if ($category !== '') {
                $categoryCounts[$category] = ($categoryCounts[$category] ?? 0) + 1;
                if ($platform !== '') {
                    if (!isset($platformCategoryCounts[$category])) {
                        $platformCategoryCounts[$category] = [];
                    }
                    $platformCategoryCounts[$category][$platform] = ($platformCategoryCounts[$category][$platform] ?? 0) + 1;
                }
            }

            $hasPublicImage = false;
            foreach (['api_logo_img', 'mobile_img', 'gamepic', 'img'] as $imageField) {
                if (empty($game[$imageField])) {
                    $apiStatus['empty_' . $imageField]++;
                    continue;
                }

                $url = (string)$game[$imageField];
                if (!$this->isValidPublicImageUrl($url)) {
                    $apiStatus['malformed_' . $imageField]++;
                    $this->sampleAppend($malformedSamples, $imageField . '=' . $url);
                    continue;
                }

                $hasPublicImage = true;
                if (count($imageProbeUrls) < $imageProbeLimit) {
                    $imageProbeUrls[$url] = true;
                }
            }

            if ((int)($game['is_hot'] ?? 0) === 1 && !$hasPublicImage) {
                $apiStatus['hot_missing_image_count']++;
            }

            if ($clickProbeCandidate === null && !$missingClickFields && ($gameCode !== '' || !in_array($category, $this->subGameCategories, true))) {
                $clickProbeCandidate = [
                    'plat_name' => $platform,
                    'game_type' => $category,
                    'game_code' => $gameCode,
                    'is_mobile_url' => 1,
                ];
            }
        }

        foreach (array_keys($imageProbeUrls) as $url) {
            $probe = $this->probeImageUrl($url);
            $apiStatus['image_probe_count']++;
            if (!$probe['ok']) {
                $apiStatus['broken_image_count']++;
                $this->sampleAppend($apiStatus['broken_image_samples'], $url . ' status=' . $probe['status'] . ' error=' . $probe['error']);
            }
        }

        arsort($categoryCounts);
        foreach ($categoryCounts as $category => $count) {
            $apiStatus['category_counts'][] = [
                'category_id' => $category,
                'count' => $count,
            ];
        }

        foreach ($this->categoryMinimums as $category => $minimum) {
            $actual = $categoryCounts[$category] ?? 0;
            $apiStatus['category_minimums'][] = [
                'category_id' => $category,
                'minimum' => $minimum,
                'actual' => $actual,
                'passed' => $actual >= $minimum,
            ];
            if ($actual < $minimum) {
                $this->addIssue($issues, 'critical', 'game_category_count_too_low', '/api/game/list category count is below operational minimum.', $minimum - $actual, $category . ' actual=' . $actual . ' minimum=' . $minimum);
            }
        }

        $lotteryFamilyCount = 0;
        foreach ($this->lotteryCategories as $category) {
            $lotteryFamilyCount += $categoryCounts[$category] ?? 0;
        }
        if ($lotteryFamilyCount < 1) {
            $this->addIssue($issues, 'critical', 'lottery_family_empty', '/api/game/list has no lottery family games for the lottery tab.', 1);
        }

        foreach ($this->categoryMinimums as $category => $minimum) {
            $apiStatus['filter_checks'][] = $this->validateGameListFilter(
                'category:' . $category,
                $this->httpGet($baseUrl . '/api/game/list?' . http_build_query(['category' => $category])),
                $category,
                '',
                $categoryCounts[$category] ?? null,
                $minimum,
                $issues
            );
        }

        foreach ($this->lotteryCategories as $category) {
            if (($categoryCounts[$category] ?? 0) < 1) {
                continue;
            }
            $apiStatus['filter_checks'][] = $this->validateGameListFilter(
                'category:' . $category,
                $this->httpGet($baseUrl . '/api/game/list?' . http_build_query(['category' => $category])),
                $category,
                '',
                $categoryCounts[$category],
                1,
                $issues
            );
        }

        $platformFilter = $this->selectPlatformFilterCandidate($platformCategoryCounts, 'concise');
        if ($platformFilter) {
            $params = ['category' => $platformFilter['category'], 'platform_name' => $platformFilter['platform']];
            $apiStatus['filter_checks'][] = $this->validateGameListFilter(
                'platform_name:' . $platformFilter['category'] . ':' . $platformFilter['platform'],
                $this->httpGet($baseUrl . '/api/game/list?' . http_build_query($params)),
                $platformFilter['category'],
                $platformFilter['platform'],
                $platformFilter['count'],
                1,
                $issues
            );

            $params = ['category' => $platformFilter['category'], 'platform' => $platformFilter['platform']];
            $apiStatus['filter_checks'][] = $this->validateGameListFilter(
                'platform:' . $platformFilter['category'] . ':' . $platformFilter['platform'],
                $this->httpGet($baseUrl . '/api/game/list?' . http_build_query($params)),
                $platformFilter['category'],
                $platformFilter['platform'],
                $platformFilter['count'],
                1,
                $issues
            );
        }

        $apiStatus['get_game_url_probe'] = $this->getGameUrlUnauthenticatedProbe($baseUrl, $clickProbeCandidate, $issues);

        if ($apiStatus['game_count'] < 1000) {
            $this->addIssue($issues, 'critical', 'game_list_count_too_low', '/api/game/list returned fewer than 1000 games.', $apiStatus['game_count'], 'count=' . $apiStatus['game_count']);
        }
        if (!$apiStatus['has_is_hot_field']) {
            $this->addIssue($issues, 'critical', 'game_list_missing_is_hot', '/api/game/list does not expose is_hot field.', 1);
        }
        if ($apiStatus['hot_count'] < 10) {
            $this->addIssue($issues, 'warning', 'hot_game_count_too_low', '/api/game/list has fewer than 10 hot games.', 1, 'hot=' . $apiStatus['hot_count']);
        }
        if ($apiStatus['hot_missing_image_count'] > 0) {
            $this->addIssue($issues, 'warning', 'hot_game_missing_image', 'Hot games without any public image URL.', $apiStatus['hot_missing_image_count']);
        }
        if ($apiStatus['unknown_category_count'] > 0) {
            $this->addIssue($issues, 'critical', 'game_list_unknown_category', '/api/game/list has categories the frontend does not render.', $apiStatus['unknown_category_count'], implode(' | ', $unknownCategorySamples));
        }
        if ($apiStatus['field_missing_count'] > 0) {
            $this->addIssue($issues, 'critical', 'game_list_required_field_missing', '/api/game/list rows are missing fields required by the frontend.', $apiStatus['field_missing_count'], implode(' | ', $fieldMissingSamples));
        }
        if ($apiStatus['click_field_missing_count'] > 0) {
            $this->addIssue($issues, 'critical', 'game_list_click_field_missing', '/api/game/list rows are missing click/open-game fields.', $apiStatus['click_field_missing_count'], implode(' | ', $clickMissingSamples));
        }
        if ($apiStatus['blank_sub_game_code_count'] > 0) {
            $this->addIssue($issues, 'critical', 'game_list_sub_game_code_blank', 'Sub-game rows need a non-empty game_code for game opening.', $apiStatus['blank_sub_game_code_count'], implode(' | ', $blankSubGameCodeSamples));
        }
        if ($apiStatus['blank_lobby_game_code_count'] > 0) {
            $this->addIssue($issues, 'warning', 'game_list_lobby_game_code_blank', 'Lobby/direct game rows have blank game_code; 0 or lobby is clearer for operations.', $apiStatus['blank_lobby_game_code_count'], implode(' | ', $blankLobbyGameCodeSamples));
        }
        if ($apiStatus['disabled_app_state_count'] > 0) {
            $this->addIssue($issues, 'critical', 'game_list_disabled_app_state', '/api/game/list exposed rows with app_state not enabled.', $apiStatus['disabled_app_state_count'], implode(' | ', $disabledAppStateSamples));
        }
        if ($apiStatus['empty_api_logo_img'] > 0) {
            $this->addIssue($issues, 'warning', 'game_list_empty_api_logo_img', '/api/game/list has empty api_logo_img values.', $apiStatus['empty_api_logo_img']);
        }
        if ($apiStatus['empty_mobile_img'] > 0) {
            $this->addIssue($issues, 'warning', 'game_list_empty_mobile_img', '/api/game/list has empty mobile_img values.', $apiStatus['empty_mobile_img']);
        }
        if ($apiStatus['empty_gamepic'] > 0) {
            $this->addIssue($issues, 'warning', 'game_list_empty_gamepic', '/api/game/list has empty gamepic values.', $apiStatus['empty_gamepic']);
        }
        if ($apiStatus['empty_img'] > 0) {
            $this->addIssue($issues, 'warning', 'game_list_empty_img', '/api/game/list has empty img values.', $apiStatus['empty_img']);
        }
        if ($apiStatus['malformed_api_logo_img'] > 0) {
            $this->addIssue($issues, 'critical', 'game_list_malformed_api_logo_img', '/api/game/list has malformed api_logo_img URLs.', $apiStatus['malformed_api_logo_img'], implode(' | ', $malformedSamples));
        }
        if ($apiStatus['malformed_mobile_img'] > 0) {
            $this->addIssue($issues, 'critical', 'game_list_malformed_mobile_img', '/api/game/list has malformed mobile_img URLs.', $apiStatus['malformed_mobile_img'], implode(' | ', $malformedSamples));
        }
        if ($apiStatus['malformed_gamepic'] > 0) {
            $this->addIssue($issues, 'critical', 'game_list_malformed_gamepic', '/api/game/list has malformed gamepic URLs.', $apiStatus['malformed_gamepic'], implode(' | ', $malformedSamples));
        }
        if ($apiStatus['malformed_img'] > 0) {
            $this->addIssue($issues, 'critical', 'game_list_malformed_img', '/api/game/list has malformed img URLs.', $apiStatus['malformed_img'], implode(' | ', $malformedSamples));
        }
        if ($apiStatus['broken_image_count'] > 0) {
            $this->addIssue($issues, 'warning', 'game_list_broken_image_sample', 'Sampled public game image URLs failed HTTP probe.', $apiStatus['broken_image_count'], implode(' | ', $apiStatus['broken_image_samples']));
        }

        return ['homepage' => $homepage, 'app' => $app, 'api' => $apiStatus, 'forbidden_public_routes' => $forbiddenPublicRoutes, 'forbidden_public_sources' => $forbiddenPublicSources, 'forbidden_public_files' => $forbiddenPublicFiles, 'required_public_routes' => $requiredPublicRoutes, 'required_public_sources' => $requiredPublicSources];
    }

    protected function forbiddenPublicRouteStatus(array &$issues)
    {
        $rows = [];
        foreach ($this->forbiddenPublicRoutes as $route) {
            $source = $this->readSource($route['path']);
            $present = strpos($source, $route['needle']) !== false;
            $rows[] = [
                'name' => $route['name'],
                'path' => $route['path'],
                'present' => $present,
            ];
            if ($present) {
                $this->addIssue($issues, 'critical', 'forbidden_public_test_route_present', 'Public test/debug route is present in production web routes.', 1, $route['name']);
            }
        }

        return $rows;
    }

    protected function forbiddenPublicSourceStatus(array &$issues)
    {
        $rows = [];
        foreach ($this->forbiddenPublicSourceMarkers as $marker) {
            $source = $this->readSource($marker['path']);
            $present = $source !== '' && strpos($source, $marker['needle']) !== false;
            $rows[] = [
                'name' => $marker['name'],
                'path' => $marker['path'],
                'present' => $present,
                'needle' => $marker['needle'],
            ];
            if ($present) {
                $this->addIssue($issues, 'critical', 'forbidden_public_debug_source_present', 'Public debug/test source marker is present in production code.', 1, $marker['name']);
            }
        }
        foreach ($this->forbiddenFrontendSourceMarkerRows() as $row) {
            $rows[] = $row;
            if (!empty($row['present'])) {
                $this->addIssue(
                    $issues,
                    'critical',
                    'frontend_forbidden_legacy_source_marker_present',
                    'Frontend source/view/public JS still contains a forbidden legacy agent or auto-login marker.',
                    1,
                    $row['path'] . ': ' . $row['needle']
                );
            }
        }

        return $rows;
    }

    protected function forbiddenFrontendSourceMarkerRows()
    {
        $rows = [];
        foreach ($this->frontendScanRoots() as $root) {
            if (!is_dir($root['path'])) {
                continue;
            }
            foreach ($this->textFilesUnder($root['path']) as $path) {
                $source = @file_get_contents($path);
                if (!is_string($source) || $source === '') {
                    continue;
                }
                foreach ($this->forbiddenFrontendSourceNeedles as $needle) {
                    if (strpos($source, $needle) === false) {
                        continue;
                    }
                    $rows[] = [
                        'name' => $root['name'] . '_forbidden_frontend_marker',
                        'path' => $this->relativeOrAbsolutePath($path),
                        'present' => true,
                        'needle' => $needle,
                    ];
                }
            }
        }

        return $rows;
    }

    protected function sourceNeedleMatches($root, array $needles)
    {
        $matches = [];
        foreach ($this->textFilesUnder($root) as $path) {
            $source = @file_get_contents($path);
            if (!is_string($source) || $source === '') {
                continue;
            }
            foreach ($needles as $needle) {
                $offset = 0;
                while (($pos = strpos($source, $needle, $offset)) !== false) {
                    $matches[] = [
                        'path' => $this->relativeOrAbsolutePath($path),
                        'line' => $this->lineNumberAt($source, $pos),
                        'needle' => $needle,
                    ];
                    if (count($matches) >= 100) {
                        return $matches;
                    }
                    $offset = $pos + strlen($needle);
                }
            }
        }

        return $matches;
    }

    protected function frontendScanRoots()
    {
        return [
            ['name' => 'admin_views', 'path' => base_path('resources/views')],
            ['name' => 'admin_public', 'path' => base_path('public')],
            ['name' => 'wap_src', 'path' => $this->resolvePath($this->option('wap-src'))],
            ['name' => 'latest_src', 'path' => $this->resolvePath($this->option('latest-src'))],
        ];
    }

    protected function textFilesUnder($root)
    {
        $files = [];
        $allowedExtensions = ['js', 'vue', 'html', 'htm', 'php', 'css'];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $path = $file->getPathname();
            $filename = strtolower($file->getFilename());
            $extension = strtolower($file->getExtension());
            $isBlade = substr($filename, -10) === '.blade.php';
            if (!$isBlade && !in_array($extension, $allowedExtensions, true)) {
                continue;
            }
            $normalized = str_replace('\\', '/', $path);
            if (strpos($normalized, '/public/vendor/') !== false || strpos($normalized, '/public/agent/vendor/') !== false) {
                continue;
            }
            if ($file->getSize() > 8 * 1024 * 1024) {
                continue;
            }
            $files[] = $path;
        }

        return $files;
    }

    protected function relativeOrAbsolutePath($path)
    {
        $base = str_replace('\\', '/', base_path());
        $normalized = str_replace('\\', '/', $path);
        if (strpos($normalized, $base . '/') === 0) {
            return substr($normalized, strlen($base) + 1);
        }

        return $normalized;
    }

    protected function forbiddenPublicFileStatus(array &$issues)
    {
        $paths = $this->forbiddenPublicFiles;
        foreach ($this->findPublicExecutableFiles() as $path) {
            $paths[] = $path;
        }

        $rows = [];
        foreach (array_values(array_unique($paths)) as $path) {
            $present = is_file(base_path($path));
            $rows[] = [
                'path' => $path,
                'present' => $present,
            ];
            if ($present) {
                $this->addIssue($issues, 'critical', 'forbidden_public_php_file_present', 'Public test/debug or upload PHP file is present under public web root.', 1, $path);
            }
        }

        return $rows;
    }

    protected function findPublicExecutableFiles()
    {
        $root = base_path('public');
        if (!is_dir($root)) {
            return [];
        }

        $paths = [];
        $allowed = ['public/index.php', 'public/.htaccess'];
        $blockedExtensions = ['php', 'phtml', 'pht', 'php3', 'php4', 'php5', 'phar'];
        $blockedFilenames = ['.user.ini', '.htaccess'];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            $fullPath = str_replace('\\', '/', $file->getPathname());
            $base = str_replace('\\', '/', base_path());
            $relative = ltrim(substr($fullPath, strlen($base)), '/');
            if (in_array($relative, $allowed, true)) {
                continue;
            }
            $extension = strtolower($file->getExtension());
            $filename = strtolower($file->getFilename());
            $looksExecutable = in_array($extension, $blockedExtensions, true) || in_array($filename, $blockedFilenames, true);
            if (!$looksExecutable && strpos($relative, 'public/uploads/') === 0) {
                $snippet = @file_get_contents($file->getPathname(), false, null, 0, 4096);
                $looksExecutable = is_string($snippet) && (stripos($snippet, '<?php') !== false || stripos($snippet, '<?=') !== false);
            }
            if (!$looksExecutable) {
                continue;
            }
            $paths[] = $relative;
        }

        return $paths;
    }

    protected function requiredPublicRouteStatus(array &$issues)
    {
        $rows = [];
        foreach ($this->requiredPublicRouteMarkers as $route) {
            $source = $this->readSource($route['path']);
            $missing = [];
            foreach ($route['markers'] as $marker) {
                if (strpos($source, $marker) === false) {
                    $missing[] = $marker;
                }
            }
            $missing = array_merge($missing, $this->missingRouteGroupGuards($source, $route));
            $rows[] = [
                'name' => $route['name'],
                'path' => $route['path'],
                'missing_markers' => implode('|', $missing),
            ];
            if ($missing) {
                $this->addIssue($issues, 'critical', 'required_public_route_guard_missing', 'Required public API route guard marker is missing.', count($missing), $route['name'] . ': ' . implode(', ', $missing));
            }
        }

        return $rows;
    }

    protected function missingRouteGroupGuards($source, array $route)
    {
        $groupMarker = null;
        foreach ($route['markers'] as $marker) {
            if (strpos($marker, "Route::middleware(['crosstttp','api_auth'])->group(function ()") !== false) {
                $groupMarker = $marker;
                break;
            }
        }

        if ($groupMarker === null || $source === '') {
            return [];
        }

        $missing = [];
        foreach ($route['markers'] as $marker) {
            if (strpos($marker, 'Route::') !== 0 || strpos($marker, 'Route::middleware(') === 0) {
                continue;
            }
            if (!$this->routeMarkerInsideGroup($source, $marker, $groupMarker)) {
                $missing[] = $marker . ' inside api_auth group';
            }
        }

        return $missing;
    }

    protected function routeMarkerInsideGroup($source, $routeMarker, $groupMarker)
    {
        $routePos = strpos($source, $routeMarker);
        if ($routePos === false) {
            return false;
        }

        $searchOffset = 0;
        while (($groupPos = strpos($source, $groupMarker, $searchOffset)) !== false) {
            if ($routePos < $groupPos) {
                return false;
            }

            $openBrace = strpos($source, '{', $groupPos);
            if ($openBrace === false || $openBrace > $routePos) {
                $searchOffset = $groupPos + strlen($groupMarker);
                continue;
            }

            $groupEnd = $this->matchingBracePosition($source, $openBrace);
            if ($groupEnd === false) {
                $groupEnd = strpos($source, "\n});", $groupPos);
                if ($groupEnd === false) {
                    $groupEnd = strlen($source);
                }
            }

            if ($routePos < $groupEnd) {
                return true;
            }

            $searchOffset = $groupEnd + 1;
        }

        return false;
    }

    protected function matchingBracePosition($source, $openBrace)
    {
        $length = strlen($source);
        $depth = 0;
        $quote = null;
        $escaped = false;

        for ($i = $openBrace; $i < $length; $i++) {
            $char = $source[$i];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                    continue;
                }
                if ($char === '\\') {
                    $escaped = true;
                    continue;
                }
                if ($char === $quote) {
                    $quote = null;
                }
                continue;
            }

            if ($char === '"' || $char === "'") {
                $quote = $char;
                continue;
            }

            if ($char === '{') {
                $depth++;
                continue;
            }

            if ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return false;
    }

    protected function requiredPublicSourceStatus(array &$issues)
    {
        $rows = [];
        foreach ($this->requiredPublicSourceMarkers as $markerSet) {
            $source = $this->readSource($markerSet['path']);
            $missing = [];
            foreach ($markerSet['markers'] as $marker) {
                if (strpos($source, $marker) === false) {
                    $missing[] = $marker;
                }
            }
            foreach (($markerSet['count_markers'] ?? []) as $countMarker) {
                $needle = $countMarker['needle'];
                $minCount = (int)($countMarker['min_count'] ?? 1);
                $actualCount = substr_count($source, $needle);
                if ($actualCount < $minCount) {
                    $missing[] = $needle . ' count>=' . $minCount . ' actual=' . $actualCount;
                }
            }
            $rows[] = [
                'name' => $markerSet['name'],
                'path' => $markerSet['path'],
                'missing_markers' => implode('|', $missing),
            ];
            if ($missing) {
                $this->addIssue($issues, 'critical', 'required_public_source_guard_missing', 'Required public API source guard marker is missing.', count($missing), $markerSet['name'] . ': ' . implode(', ', $missing));
            }
        }

        return $rows;
    }

    protected function appStatus($baseUrl, array &$issues)
    {
        $response = $this->httpPost($baseUrl . '/api/app', []);
        $status = [
            'status' => $response['status'],
            'error' => $response['error'],
            'json_valid' => false,
            'site_state' => null,
            'title' => '',
        ];

        if ((int)$response['status'] !== 200) {
            $this->addIssue($issues, 'critical', 'app_http_not_200', '/api/app HTTP status is not 200; App.vue may render a blank shell.', 1, 'status=' . $response['status'] . ' error=' . $response['error']);
            return $status;
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json) || (string)($json['code'] ?? '') !== '200' || !isset($json['data']) || !is_array($json['data'])) {
            $this->addIssue($issues, 'critical', 'app_json_invalid', '/api/app response is not valid code=200 JSON.', 1, substr((string)$response['body'], 0, 200));
            return $status;
        }

        $status['json_valid'] = true;
        $status['site_state'] = $json['data']['site_state'] ?? null;
        $status['title'] = (string)($json['data']['title'] ?? '');
        if ((int)$status['site_state'] !== 1) {
            $this->addIssue($issues, 'critical', 'app_site_state_not_open', '/api/app site_state is not 1; frontend route content will not render.', 1, 'site_state=' . (string)$status['site_state']);
        }
        if (trim($status['title']) === '') {
            $this->addIssue($issues, 'warning', 'app_title_missing', '/api/app title is empty.', 1);
        }

        return $status;
    }

    protected function validateGameListFilter($label, array $response, $expectedCategory, $expectedPlatform, $expectedCount, $minimum, array &$issues)
    {
        $check = [
            'label' => $label,
            'status' => $response['status'],
            'count' => 0,
            'expected_category' => $expectedCategory,
            'expected_platform' => $expectedPlatform,
            'expected_count' => $expectedCount,
            'wrong_category_count' => 0,
            'wrong_platform_count' => 0,
            'sample' => '',
        ];

        if ((int)$response['status'] !== 200) {
            $check['sample'] = 'status=' . $response['status'] . ' error=' . $response['error'];
            $this->addIssue($issues, 'critical', 'game_list_filter_http_not_200', '/api/game/list filter HTTP status is not 200.', 1, $label . ' ' . $check['sample']);
            return $check;
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json) || (string)($json['code'] ?? '') !== '200' || !isset($json['data']) || !is_array($json['data'])) {
            $check['sample'] = substr((string)$response['body'], 0, 160);
            $this->addIssue($issues, 'critical', 'game_list_filter_json_invalid', '/api/game/list filter response is not valid code=200 JSON.', 1, $label . ' ' . $check['sample']);
            return $check;
        }

        $samples = [];
        foreach ($json['data'] as $game) {
            if (!is_array($game)) {
                continue;
            }
            $check['count']++;
            $category = strtolower(trim((string)$this->gameField($game, ['category_id', 'gametype'])));
            $platform = trim((string)$this->gameField($game, ['platform_name', 'catecode']));
            if ($expectedCategory !== '' && $category !== strtolower($expectedCategory)) {
                $check['wrong_category_count']++;
                $this->sampleAppend($samples, 'category=' . $category);
            }
            if ($expectedPlatform !== '' && strtoupper($platform) !== strtoupper($expectedPlatform)) {
                $check['wrong_platform_count']++;
                $this->sampleAppend($samples, 'platform=' . $platform);
            }
        }

        $check['sample'] = implode(' | ', $samples);
        if ($check['count'] < $minimum) {
            $this->addIssue($issues, 'critical', 'game_list_filter_count_too_low', '/api/game/list filter returned fewer rows than required.', $minimum - $check['count'], $label . ' count=' . $check['count'] . ' minimum=' . $minimum);
        }
        if ($expectedCount !== null && $check['count'] !== (int)$expectedCount) {
            $this->addIssue($issues, 'critical', 'game_list_filter_count_mismatch', '/api/game/list filter count does not match the unfiltered dataset.', abs((int)$expectedCount - $check['count']), $label . ' expected=' . $expectedCount . ' actual=' . $check['count']);
        }
        if ($check['wrong_category_count'] > 0) {
            $this->addIssue($issues, 'critical', 'game_list_filter_wrong_category', '/api/game/list category filter returned wrong categories.', $check['wrong_category_count'], $label . ' ' . $check['sample']);
        }
        if ($check['wrong_platform_count'] > 0) {
            $this->addIssue($issues, 'critical', 'game_list_filter_wrong_platform', '/api/game/list platform filter returned wrong platforms.', $check['wrong_platform_count'], $label . ' ' . $check['sample']);
        }

        return $check;
    }

    protected function selectPlatformFilterCandidate(array $platformCategoryCounts, $preferredCategory)
    {
        if (!isset($platformCategoryCounts[$preferredCategory]) || !$platformCategoryCounts[$preferredCategory]) {
            return null;
        }

        arsort($platformCategoryCounts[$preferredCategory]);
        foreach ($platformCategoryCounts[$preferredCategory] as $platform => $count) {
            if ($platform !== '' && $count > 0) {
                return [
                    'category' => $preferredCategory,
                    'platform' => $platform,
                    'count' => $count,
                ];
            }
        }

        return null;
    }

    protected function getGameUrlUnauthenticatedProbe($baseUrl, $candidate, array &$issues)
    {
        $status = [
            'enabled' => $candidate !== null,
            'status' => 0,
            'code' => null,
            'message' => '',
            'sample' => $candidate ?: [],
        ];

        if ($candidate === null) {
            $this->addIssue($issues, 'critical', 'get_game_url_probe_no_candidate', 'No valid game row was available to probe /api/getGameUrl.', 1);
            return $status;
        }

        $response = $this->httpPost($baseUrl . '/api/getGameUrl', $candidate);
        $status['status'] = $response['status'];
        if (in_array((int)$response['status'], [401, 403], true)) {
            $status['code'] = $response['status'];
            $status['message'] = 'auth required';
            return $status;
        }
        if ((int)$response['status'] !== 200) {
            $this->addIssue($issues, 'critical', 'get_game_url_probe_http_not_200', '/api/getGameUrl unauthenticated probe HTTP status is not 200.', 1, 'status=' . $response['status'] . ' error=' . $response['error']);
            return $status;
        }

        $json = json_decode($response['body'], true);
        if (!is_array($json) || !array_key_exists('code', $json)) {
            $this->addIssue($issues, 'critical', 'get_game_url_probe_json_invalid', '/api/getGameUrl unauthenticated probe did not return JSON with code.', 1, substr((string)$response['body'], 0, 160));
            return $status;
        }

        $status['code'] = $json['code'];
        $status['message'] = (string)($json['message'] ?? ($json['msg'] ?? ''));
        if ((string)$json['code'] === '200') {
            $this->addIssue($issues, 'critical', 'get_game_url_opens_without_login', '/api/getGameUrl returned a playable URL without a login token.', 1, json_encode($candidate));
        }

        $message = strtolower($status['message']);
        if (strpos($message, 'closed') !== false || strpos($message, 'missing') !== false) {
            $this->addIssue($issues, 'critical', 'get_game_url_probe_game_not_playable', '/api/getGameUrl probe row is not playable according to backend allowlist.', 1, $status['message'] . ' ' . json_encode($candidate));
        }

        return $status;
    }

    protected function gameField(array $game, array $fields)
    {
        foreach ($fields as $field) {
            if (array_key_exists($field, $game)) {
                return $game[$field];
            }
        }

        return '';
    }

    protected function isValidPublicImageUrl($url)
    {
        $url = trim((string)$url);
        if ($url === '') {
            return false;
        }
        if (!preg_match('/^https?:\/\//i', $url)) {
            return false;
        }
        if (strpos($url, '/uploads/http') !== false) {
            return false;
        }
        if (preg_match('/\/uploads\/https?:\/\//i', $url)) {
            return false;
        }

        return true;
    }

    protected function probeImageUrl($url)
    {
        $result = ['ok' => false, 'status' => 0, 'error' => '', 'content_type' => ''];
        if (!function_exists('curl_init')) {
            $result['error'] = 'curl unavailable';
            return $result;
        }

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $result = ['ok' => false, 'status' => 0, 'error' => '', 'content_type' => ''];
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_RANGE => '0-0',
                CURLOPT_USERAGENT => 'xy281-frontend-ops-audit/1.0',
            ]);
            if (defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }
            $body = curl_exec($ch);
            $result['status'] = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $result['content_type'] = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
            if ($body === false) {
                $result['error'] = curl_error($ch);
            }
            curl_close($ch);

            $result['ok'] = $result['status'] >= 200 && $result['status'] < 400;
            if ($result['ok']) {
                return $result;
            }
            if (!$this->shouldRetryImageProbe($result)) {
                break;
            }
            if ($attempt < 2) {
                usleep($attempt === 0 ? 300000 : 1000000);
            }
        }

        if (!$result['ok'] && $result['error'] === '') {
            $result['error'] = 'http_not_ok';
        }

        return $result;
    }

    protected function shouldRetryImageProbe(array $result)
    {
        $status = (int)($result['status'] ?? 0);
        return $status === 0 || ($status >= 500 && $status < 600);
    }

    protected function sampleAppend(array &$samples, $value, $limit = 10)
    {
        if (count($samples) < $limit) {
            $samples[] = $value;
        }
    }

    protected function readAbsoluteSource($path)
    {
        return is_file($path) ? (string)file_get_contents($path) : null;
    }

    protected function readSource($path)
    {
        $fullPath = base_path($path);
        return is_file($fullPath) ? (string)file_get_contents($fullPath) : '';
    }

    protected function httpGet($url)
    {
        $result = ['status' => 0, 'body' => '', 'error' => '', 'url' => $url];
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);
            if (defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }
            $body = curl_exec($ch);
            $result['status'] = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($body === false) {
                $result['error'] = curl_error($ch);
                $body = '';
            }
            curl_close($ch);
            $result['body'] = (string)$body;
            return $result;
        }

        $context = stream_context_create([
            'http' => ['timeout' => 60],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $body = @file_get_contents($url, false, $context);
        $result['body'] = (string)$body;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $result['status'] = (int)$match[1];
        }
        if ($body === false) {
            $result['error'] = 'file_get_contents failed';
        }

        return $result;
    }

    protected function httpPost($url, array $data)
    {
        $result = ['status' => 0, 'body' => '', 'error' => '', 'url' => $url];
        $payload = http_build_query($data);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
            ]);
            if (defined('CURL_IPRESOLVE_V4')) {
                curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
            }
            $body = curl_exec($ch);
            $result['status'] = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($body === false) {
                $result['error'] = curl_error($ch);
                $body = '';
            }
            curl_close($ch);
            $result['body'] = (string)$body;
            return $result;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 60,
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => $payload,
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $body = @file_get_contents($url, false, $context);
        $result['body'] = (string)$body;
        if (isset($http_response_header[0]) && preg_match('/\s(\d{3})\s/', $http_response_header[0], $match)) {
            $result['status'] = (int)$match[1];
        }
        if ($body === false) {
            $result['error'] = 'file_get_contents failed';
        }

        return $result;
    }

    protected function addIssue(array &$issues, $severity, $code, $message, $count, $sample = '')
    {
        $count = (int)$count;
        if ($count <= 0) {
            return;
        }

        $issues[] = [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'count' => $count,
            'sample' => $sample,
        ];
    }

    protected function issueCounts(array $issues)
    {
        $counts = [];
        foreach ($issues as $issue) {
            $counts[$issue['severity']] = ($counts[$issue['severity']] ?? 0) + 1;
        }

        return $counts;
    }

    protected function writeJson($path, $data)
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        file_put_contents($path, json_encode($data, $flags));
    }

    protected function writeCsv($path, array $rows, array $headers)
    {
        $fh = fopen($path, 'w');
        fputcsv($fh, $headers);
        foreach ($rows as $row) {
            fputcsv($fh, array_map(function ($header) use ($row) {
                return $row[$header] ?? '';
            }, $headers));
        }
        fclose($fh);
    }

    protected function resolvePath($path)
    {
        if (preg_match('/^([A-Za-z]:)?[\/\\\\]/', $path)) {
            return $path;
        }

        return base_path($path);
    }
}
