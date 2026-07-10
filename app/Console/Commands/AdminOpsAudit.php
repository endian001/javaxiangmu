<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AdminOpsAudit extends Command
{
    protected $signature = 'AdminOpsAudit
        {--out-dir=storage/app/admin-ops-audit : Directory for audit reports}';

    protected $description = 'Generate read-only operational health reports for admin controls';

    protected $requiredRoutes = [
        ['name' => 'users', 'needle' => "resource('users'"],
        ['name' => 'recharge', 'needle' => "resource('recharge'"],
        ['name' => 'withdraws', 'needle' => "resource('withdraws'"],
        ['name' => 'transfer-logs', 'needle' => "resource('transfer-logs'"],
        ['name' => 'finance-report', 'needle' => "resource('finance-report'"],
        ['name' => 'game-records', 'needle' => "resource('game-records'"],
        ['name' => 'apis', 'needle' => "resource('apis'"],
        ['name' => 'game-lists', 'needle' => "resource('game-lists'"],
        ['name' => 'user-operate-logs', 'needle' => "resource('user-operate-logs'"],
        ['name' => 'ops-audit', 'needle' => "get('/ops-audit'"],
    ];

    protected $requiredControllers = [
        'UserController.php',
        'RechargeController.php',
        'WithdrawController.php',
        'TransferLogController.php',
        'FinanceReportController.php',
        'GameRecordController.php',
        'ApiController.php',
        'GameListController.php',
        'UserOperateLogController.php',
        'OpsAuditController.php',
    ];

    protected $readOnlyControllers = [
        'ActivityApplyController.php',
        'AgentApplyController.php',
        'AgentCommissionController.php',
        'BetReportController.php',
        'BetSumController.php',
        'FanshuiLogController.php',
        'FinanceReportController.php',
        'GameRecordController.php',
        'RechargeController.php',
        'SyslogController.php',
        'TransferLogController.php',
        'UserOperateLogController.php',
        'UserredpacketController.php',
        'WithdrawController.php',
    ];

    protected $controlMarkers = [
        [
            'name' => 'game_public_switches',
            'path' => 'app/Admin/Controllers/GameListController.php',
            'markers' => ['is_top', 'site_state', 'app_state', 'is_hot', 'is_new', 'is_recommend'],
        ],
        [
            'name' => 'game_filters',
            'path' => 'app/Admin/Controllers/GameListController.php',
            'markers' => ["equal('category_id'", "equal('platform_name'", "like('name'", "like('game_code'"],
        ],
        [
            'name' => 'game_delete_disabled',
            'path' => 'app/Admin/Controllers/GameListController.php',
            'markers' => ['disableDelete'],
        ],
        [
            'name' => 'api_platform_switches',
            'path' => 'app/Admin/Controllers/ApiController.php',
            'markers' => ['api_code', 'api_name', 'state', 'app_state', 'disableBatchDelete', 'disableDelete'],
        ],
        [
            'name' => 'user_wallet_actions',
            'path' => 'app/Admin/Controllers/UserController.php',
            'markers' => ['new Balance()', 'new BackBalance()', 'UserBalance', 'disableBatchDelete', 'disableDelete'],
        ],
        [
            'name' => 'transfer_reconcile_columns',
            'path' => 'app/Admin/Controllers/TransferLogController.php',
            'markers' => ['recovery_status', 'external_status', 'external_checked_at', 'posted_at', 'reconcile_note'],
        ],
        [
            'name' => 'ops_dashboard_reports',
            'path' => 'app/Admin/Controllers/OpsAuditController.php',
            'markers' => [
                'wallet-ops-audit/wallet_ops_audit.json',
                'admin-ops-audit/admin_ops_audit.json',
                'game-ops-audit/game_ops_audit.json',
                'frontend-ops-audit/frontend_ops_audit.json',
                'xh-api-ops-audit/xh_api_ops_audit.json',
            ],
        ],
        [
            'name' => 'data_cleanup_preview_only',
            'path' => 'app/Admin/Forms/ClearForm.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::OPS_DATA_CLEANUP)',
                'clear_form_cleanup_preview',
                'Cleanup preview generated; no data was deleted',
                "'deleted' => 0",
                'count()',
            ],
        ],
        [
            'name' => 'ops_change_audit_helper',
            'path' => 'app/Admin/Support/OpsChangeAudit.php',
            'markers' => [
                'class OpsChangeAudit',
                'writeFormChanges',
                'changedFields',
                'hasAnyChanged',
                'UserOperateLog::insertLog',
                "'changes' => \$changes",
            ],
        ],
        [
            'name' => 'operation_permission_nonfunding_seeder',
            'path' => 'database/seeds/AdminOperationPermissionSeeder.php',
            'markers' => [
                'class AdminOperationPermissionSeeder',
                'updateOrCreate',
                'OperationPermission::MEMBER_PASSWORD_RESET',
                'OperationPermission::MEMBER_AGENT_UPDATE',
                'OperationPermission::MEMBER_STATUS_UPDATE',
                'OperationPermission::MEMBER_VIP_UPDATE',
                'OperationPermission::GAME_LIST_UPDATE',
                'OperationPermission::GAME_PUBLISH_SWITCH',
                'OperationPermission::API_PLATFORM_UPDATE',
                'OperationPermission::API_PLATFORM_SWITCH',
                'OperationPermission::ACTIVITY_CONTENT_UPDATE',
                'OperationPermission::ACTIVITY_PUBLISH_SWITCH',
                'OperationPermission::OPS_DATA_CLEANUP',
                'OperationPermission::OPS_SITE_SETTING_UPDATE',
            ],
        ],
        [
            'name' => 'member_form_change_audit',
            'path' => 'app/Admin/Controllers/UserController.php',
            'markers' => [
                'OpsChangeAudit::writeFormChanges',
                "'member.user.update'",
                "'password' => 'login password'",
                "['password', 'paypwd']",
            ],
        ],
        [
            'name' => 'member_form_operation_permissions',
            'path' => 'app/Admin/Controllers/UserController.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::MEMBER_PASSWORD_RESET)',
                'OperationPermission::assert(OperationPermission::MEMBER_AGENT_UPDATE)',
                'OperationPermission::assert(OperationPermission::MEMBER_STATUS_UPDATE)',
                'OperationPermission::assert(OperationPermission::MEMBER_VIP_UPDATE)',
                'OpsChangeAudit::hasAnyChanged',
            ],
        ],
        [
            'name' => 'game_form_change_audit',
            'path' => 'app/Admin/Controllers/GameListController.php',
            'markers' => [
                'OpsChangeAudit::writeFormChanges',
                'OpsChangeAudit::writeFormSnapshot',
                "'game.list.create'",
                "'game.list.update'",
                "'site_state' => 'site state'",
                "'app_state' => 'app state'",
            ],
        ],
        [
            'name' => 'game_form_operation_permissions',
            'path' => 'app/Admin/Controllers/GameListController.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::GAME_LIST_UPDATE)',
                'OperationPermission::assert(OperationPermission::GAME_PUBLISH_SWITCH)',
                '$form->isCreating() || OpsChangeAudit::hasAnyChanged',
                'OpsChangeAudit::hasAnyChanged',
            ],
        ],
        [
            'name' => 'game_app_form_change_audit',
            'path' => 'app/Admin/Controllers/GameListAppController.php',
            'markers' => [
                'OpsChangeAudit::writeFormChanges',
                'OpsChangeAudit::writeFormSnapshot',
                "'game.app.list.create'",
                "'game.app.list.update'",
                "'app_img' => 'app image'",
                "'app_state' => 'app state'",
            ],
        ],
        [
            'name' => 'game_app_form_operation_permissions',
            'path' => 'app/Admin/Controllers/GameListAppController.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::GAME_LIST_UPDATE)',
                'OperationPermission::assert(OperationPermission::GAME_PUBLISH_SWITCH)',
                '$form->isCreating() || OpsChangeAudit::hasAnyChanged',
                'OpsChangeAudit::hasAnyChanged',
            ],
        ],
        [
            'name' => 'api_platform_form_change_audit',
            'path' => 'app/Admin/Controllers/ApiController.php',
            'markers' => [
                'OpsChangeAudit::writeFormChanges',
                'OpsChangeAudit::writeFormSnapshot',
                "'api.platform.create'",
                "'api.platform.update'",
                "'state' => 'state'",
                "'app_state' => 'app state'",
            ],
        ],
        [
            'name' => 'api_platform_form_operation_permissions',
            'path' => 'app/Admin/Controllers/ApiController.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::API_PLATFORM_UPDATE)',
                'OperationPermission::assert(OperationPermission::API_PLATFORM_SWITCH)',
                '$form->isCreating() || OpsChangeAudit::hasAnyChanged',
                'OpsChangeAudit::hasAnyChanged',
            ],
        ],
        [
            'name' => 'activity_form_change_audit',
            'path' => 'app/Admin/Controllers/ActivityController.php',
            'markers' => [
                'OpsChangeAudit::writeFormChanges',
                'OpsChangeAudit::writeFormSnapshot',
                "'activity.config.create'",
                "'activity.config.update'",
                "'content' => 'content'",
                "'can_apply' => 'can apply'",
            ],
        ],
        [
            'name' => 'activity_form_operation_permissions',
            'path' => 'app/Admin/Controllers/ActivityController.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::ACTIVITY_CONTENT_UPDATE)',
                'OperationPermission::assert(OperationPermission::ACTIVITY_PUBLISH_SWITCH)',
                '$form->isCreating() || OpsChangeAudit::hasAnyChanged',
                'OpsChangeAudit::hasAnyChanged',
            ],
        ],
        [
            'name' => 'member_vip_config_form_operation_permissions',
            'path' => 'app/Admin/Controllers/UserVipController.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::MEMBER_VIP_UPDATE)',
                'OpsChangeAudit::writeFormChanges',
                "'member.vip.config.update'",
                'OpsChangeAudit::hasAnyChanged',
            ],
        ],
        [
            'name' => 'activity_type_form_operation_permissions',
            'path' => 'app/Admin/Controllers/ActivityTypeController.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::ACTIVITY_CONTENT_UPDATE)',
                'OpsChangeAudit::writeFormSnapshot',
                'OpsChangeAudit::writeFormChanges',
                "'activity.type.create'",
                "'activity.type.update'",
                '$form->isCreating() || OpsChangeAudit::hasAnyChanged',
                'OpsChangeAudit::hasAnyChanged',
            ],
        ],
        [
            'name' => 'banner_form_operation_permissions',
            'path' => 'app/Admin/Controllers/BannerController.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::OPS_SITE_SETTING_UPDATE)',
                'OpsChangeAudit::writeFormSnapshot',
                'OpsChangeAudit::writeFormChanges',
                "'ops.banner.update'",
                'OpsChangeAudit::hasAnyChanged',
            ],
        ],
        [
            'name' => 'article_form_operation_permissions',
            'path' => 'app/Admin/Controllers/ArticleController.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::OPS_SITE_SETTING_UPDATE)',
                'OpsChangeAudit::writeFormSnapshot',
                'OpsChangeAudit::writeFormChanges',
                "'ops.article.update'",
                'OpsChangeAudit::hasAnyChanged',
            ],
        ],
        [
            'name' => 'article_category_form_operation_permissions',
            'path' => 'app/Admin/Controllers/ArticlescateController.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::OPS_SITE_SETTING_UPDATE)',
                'OpsChangeAudit::writeFormSnapshot',
                'OpsChangeAudit::writeFormChanges',
                "'ops.article.category.create'",
                "'ops.article.category.update'",
                '$form->isCreating() || OpsChangeAudit::hasAnyChanged',
            ],
        ],
        [
            'name' => 'message_form_operation_permissions',
            'path' => 'app/Admin/Controllers/MessageController.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::OPS_SITE_SETTING_UPDATE)',
                'OpsChangeAudit::writeFormSnapshot',
                'OpsChangeAudit::writeFormChanges',
                "'ops.message.update'",
                'OpsChangeAudit::hasAnyChanged',
            ],
        ],
        [
            'name' => 'site_setting_nonfunding_operation_permissions',
            'path' => 'app/Admin/Forms/SiteSetting.php',
            'markers' => [
                'OperationPermission::assert(OperationPermission::OPS_SITE_SETTING_UPDATE)',
                'OperationPermission::assert(OperationPermission::API_PLATFORM_UPDATE)',
                'OperationPermission::assert(OperationPermission::ACTIVITY_CONTENT_UPDATE)',
                'writeNonFundingConfigAudit',
                "'ops.site.setting.update'",
                'api_secret',
                'stream_chat_secret',
            ],
        ],
        [
            'name' => 'public_content_api_allowlist',
            'path' => 'routes/web.php',
            'markers' => [
                "Route::get('/api/share/info','Member\MemberController@getShareInfo')",
                "Route::post('/api/share/info','Member\MemberController@getShareInfo')",
                "Route::get('/api/about','Member\MemberController@getAboutContent')",
                "Route::post('/api/about','Member\MemberController@getAboutContent')",
                "Route::post('/api/vip/privileges','Member\MemberController@getVipPrivileges')",
            ],
        ],
        [
            'name' => 'stream_api_bearer_current_user_guard',
            'path' => 'app/Http/Controllers/Api/IndexController.php',
            'markers' => [
                'public function getStreamToken(Request $request)',
                'public function createStreamChannel(Request $request)',
                '$user = $this->activeUserFromBearer($request);',
                '$jwtToken = $this->generateStreamToken($userId, $apiKey, $secret);',
                "'members' => [(string)\$userId]",
                "'created_by_id' => (string)\$userId",
            ],
        ],
        [
            'name' => 'app_api_login_active_token_guard',
            'path' => 'app/Http/Controllers/Api/AppController.php',
            'markers' => [
                'protected function activeUserFromApiToken',
                "\$lastsession = \$data['lastsession'] ?? '';",
                '$user = $this->activeUserFromApiToken($lastsession, true);',
                'normalizeAppGameRequest',
                'isAppApiPlayable',
            ],
        ],
        [
            'name' => 'xh_confirmation_pending_pool_reconciliation',
            'path' => 'app/Console/Commands/XhConfirmationReview.php',
            'markers' => [
                'pendingPoolReconciliationIssues',
                "'conflict_union_detail'",
                "'reconciliation'",
                'ready_plus_upstream_plain_plus_conflict_union_equals_manifest_total',
                'conflict_union_matches_category_plus_collision_minus_overlap',
                'hold_invalid_confirmation',
            ],
        ],
        [
            'name' => 'sync_game_confirmation_gate_import_block',
            'path' => 'app/Console/Commands/SyncGame.php',
            'markers' => [
                'loadConfirmationGateAudit',
                "'pending_pool_reconciliation'",
                '(bool)$nameOnlyApprovalPath',
                '--import with --include-name-only requires --name-only-approved=path',
                'Pending confirmation pool reconciliation failed',
                'import is blocked',
                "'database_import_allowed_now'",
                "'blockers'",
            ],
        ],
        [
            'name' => 'game_ops_confirmation_gate_audit',
            'path' => 'app/Console/Commands/GameOpsAudit.php',
            'markers' => [
                'addPg51ConfirmationIssues',
                'pg51_pending_pool_reconciliation_failed',
                'pg51_confirmation_import_allowed_with_pending_pool',
                'pg51_dry_run_candidates_missing',
                'pending_pool_conflict_union_detail',
            ],
        ],
        [
            'name' => 'xh_api_confirmation_gate_audit',
            'path' => 'app/Console/Commands/XhApiOpsAudit.php',
            'markers' => [
                'addPg51ConfirmationIssues',
                'pg51_pending_pool_reconciliation_failed',
                'pg51_confirmation_import_allowed_with_pending_pool',
                'pg51_dry_run_candidates_missing',
                'dry_run_candidates_exists',
            ],
        ],
    ];

    protected $adminOnlyWebRoutes = [
        [
            'name' => 'web_user_balance',
            'path' => 'routes/web.php',
            'route_marker' => "Route::post('/user_balance'",
            'admin_marker' => "Route::post('/user_balance','\\App\\Admin\\Renderable\\UserBalance@user_balance')->middleware(['admin'])",
        ],
        [
            'name' => 'web_usdt_pay_resource',
            'path' => 'routes/web.php',
            'route_marker' => "Route::resource('usdt-pay'",
            'admin_marker' => "Route::prefix('game')->middleware(['admin'])->group",
        ],
    ];

    protected $sensitiveWebApiRoutes = [
        [
            'name' => 'api_team_report',
            'path' => 'routes/api.php',
            'route_marker' => "Route::match(['get', 'post'], '/team/report'",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_performance',
            'path' => 'routes/api.php',
            'route_marker' => "Route::match(['get', 'post'], '/team/performance'",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_childlist',
            'path' => 'routes/api.php',
            'route_marker' => "Route::match(['get', 'post'], '/team/childlist'",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_add_member',
            'path' => 'routes/api.php',
            'route_marker' => "Route::post('/team/addMember','Agent\IndexController@addMember')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_set_agent',
            'path' => 'routes/api.php',
            'route_marker' => "Route::post('/team/setAgent','Agent\IndexController@setAgent')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_recharge',
            'path' => 'routes/api.php',
            'route_marker' => "Route::post('/team/recharge','Agent\IndexController@rechargeApi')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_fdinfo',
            'path' => 'routes/api.php',
            'route_marker' => "Route::get('/team/fdinfo','Agent\IndexController@teamFdInfoApi')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_fdlist',
            'path' => 'routes/api.php',
            'route_marker' => "Route::get('/team/fdList','Agent\IndexController@teamFdListApi')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_setfd',
            'path' => 'routes/api.php',
            'route_marker' => "Route::post('/team/setFd','Agent\IndexController@teamSetFdApi')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_invite_list',
            'path' => 'routes/api.php',
            'route_marker' => "Route::get('/team/invite/list','Agent\IndexController@teamInviteListApi')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_invite_update',
            'path' => 'routes/api.php',
            'route_marker' => "Route::post('/team/invite/update','Agent\IndexController@teamInviteUpdateApi')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_commission_list',
            'path' => 'routes/api.php',
            'route_marker' => "Route::get('/team/commissionList','Agent\IndexController@teamCommissionListApi')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_member_bet',
            'path' => 'routes/api.php',
            'route_marker' => "Route::get('/team/member/bet/{id}','Agent\IndexController@getMemberBetRecord')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_member_recharge',
            'path' => 'routes/api.php',
            'route_marker' => "Route::get('/team/member/recharge/{id}','Agent\IndexController@getMemberRechargeRecord')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_member_withdraw',
            'path' => 'routes/api.php',
            'route_marker' => "Route::get('/team/member/withdraw/{id}','Agent\IndexController@getMemberWithdrawRecord')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_member_profit',
            'path' => 'routes/api.php',
            'route_marker' => "Route::get('/team/member/profit/{id}','Agent\IndexController@getMemberProfitRecord')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_user_detail',
            'path' => 'routes/api.php',
            'route_marker' => "Route::get('/user/{id}','Agent\IndexController@getUserInfo')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'api_team_user_detail',
            'path' => 'routes/api.php',
            'route_marker' => "Route::get('/team/user/{id}','Agent\IndexController@getUserInfo')",
            'auth_group_marker' => "Route::middleware(['crosstttp','api_auth'])->group(function ()",
        ],
        [
            'name' => 'claim_upgrade_bonus',
            'path' => 'routes/web.php',
            'route_marker' => "Route::post('/api/claim-upgrade-bonus'",
            'auth_marker' => "Route::post('/api/claim-upgrade-bonus','Member\MemberController@claimUpgradeBonus')->middleware(['api_auth'])",
        ],
        [
            'name' => 'claim_weekly_salary',
            'path' => 'routes/web.php',
            'route_marker' => "Route::post('/api/claim-weekly-salary'",
            'auth_marker' => "Route::post('/api/claim-weekly-salary','Member\MemberController@claimWeeklySalary')->middleware(['api_auth'])",
        ],
        [
            'name' => 'claim_monthly_salary',
            'path' => 'routes/web.php',
            'route_marker' => "Route::post('/api/claim-monthly-salary'",
            'auth_marker' => "Route::post('/api/claim-monthly-salary','Member\MemberController@claimMonthlySalary')->middleware(['api_auth'])",
        ],
        [
            'name' => 'team_report',
            'path' => 'routes/web.php',
            'route_marker' => "Route::match(['get', 'post'], '/api/team/report'",
            'auth_marker' => "Route::match(['get', 'post'], '/api/team/report','Agent\IndexController@teamReportApi')->middleware(['api_auth'])",
        ],
        [
            'name' => 'team_performance',
            'path' => 'routes/web.php',
            'route_marker' => "Route::get('/api/team/performance'",
            'auth_marker' => "Route::get('/api/team/performance','Agent\IndexController@getPerformance')->middleware(['api_auth'])",
        ],
        [
            'name' => 'team_childlist',
            'path' => 'routes/web.php',
            'route_marker' => "Route::get('/api/team/childlist'",
            'auth_marker' => "Route::get('/api/team/childlist','Agent\IndexController@getChildList')->middleware(['api_auth'])",
        ],
        [
            'name' => 'agent_child_list_legacy',
            'path' => 'routes/web.php',
            'route_marker' => "Route::post('/agent/index/getChildList'",
            'auth_marker' => "Route::post('/agent/index/getChildList','Agent\IndexController@getChildList')->middleware(['api_auth'])",
        ],
        [
            'name' => 'agent_performance_legacy',
            'path' => 'routes/web.php',
            'route_marker' => "Route::post('/agent/index/getPerformance'",
            'auth_marker' => "Route::post('/agent/index/getPerformance','Agent\IndexController@getPerformance')->middleware(['api_auth'])",
        ],
        [
            'name' => 'agent_user_info_legacy',
            'path' => 'routes/web.php',
            'route_marker' => "Route::post('/agent/index/getUserInfo/{id}'",
            'auth_marker' => "Route::post('/agent/index/getUserInfo/{id}','Agent\IndexController@getUserInfo')->middleware(['api_auth'])",
        ],
        [
            'name' => 'web_user_info',
            'path' => 'routes/web.php',
            'route_marker' => "Route::get('/user','Member\MemberController@getUserInfo')",
            'auth_marker' => "Route::get('/user','Member\MemberController@getUserInfo')->middleware(['api_auth'])",
        ],
    ];

    protected $sensitiveControllerMarkers = [
        [
            'name' => 'agent_api_bearer_user_guard',
            'path' => 'app/Http/Controllers/Agent/IndexController.php',
            'markers' => [
                'protected function apiBearerUser',
                "attributes->get('api_auth_user')",
                "preg_replace('/^Bearer\\s+/i'",
                'User::where(\'api_token\', $token)->first()',
            ],
        ],
        [
            'name' => 'agent_api_team_user_guard',
            'path' => 'app/Http/Controllers/Agent/IndexController.php',
            'markers' => [
                'protected function apiTeamUser',
                'protected function isValidAgentUser',
                '$user->isagent',
                '$user->status',
            ],
        ],
        [
            'name' => 'api_auth_middleware_bearer_guard',
            'path' => 'app/Http/Middleware/Apiauthenticate.php',
            'markers' => [
                "preg_replace('/^Bearer\\s+/i'",
                "attributes->set('api_auth_user'",
                'protected function isBlockedUser',
                "array_key_exists('status'",
                "array_key_exists('isdel'",
                "array_key_exists('isblack'",
                "response()->json(['code' => 403",
                "response()->json(['code' => 401",
            ],
        ],
        [
            'name' => 'agent_report_get_post_json',
            'path' => 'app/Http/Controllers/Agent/IndexController.php',
            'markers' => [
                'public function teamReportApi',
                '$request->isMethod(\'get\')',
                'teamMembers',
                'total_commission',
                'totalPages',
            ],
        ],
        [
            'name' => 'agent_childlist_bearer_and_pagination',
            'path' => 'app/Http/Controllers/Agent/IndexController.php',
            'markers' => [
                'public function getChildList',
                '$this->apiTeamUser($request)',
                '$this->childIdArray($puser->id)',
                'totalPages',
            ],
        ],
        [
            'name' => 'agent_user_detail_team_guard',
            'path' => 'app/Http/Controllers/Agent/IndexController.php',
            'markers' => [
                'public function getUserInfo',
                '$this->apiTeamUser($request)',
                '$this->childIdArray($puser->id)',
                'User::find($id)',
            ],
        ],
        [
            'name' => 'agent_setfd_team_guard',
            'path' => 'app/Http/Controllers/Agent/IndexController.php',
            'markers' => [
                'public function teamSetFdApi',
                'DB::transaction(function () use ($user, $rate',
                '$request->input(\'user_id\'',
                '$this->childIdArray($user->id)',
                '$member->fanshuifee',
                'writeAgentOperateLog($user',
            ],
        ],
        [
            'name' => 'agent_invite_update_readonly',
            'path' => 'app/Http/Controllers/Agent/IndexController.php',
            'markers' => [
                'public function teamInviteUpdateApi',
                '$settings[\'readonly\'] = 1',
            ],
        ],
        [
            'name' => 'agent_recharge_safe_delegate',
            'path' => 'app/Http/Controllers/Agent/IndexController.php',
            'markers' => [
                'return $this->safeTeamRechargeApi($request);',
                'performTeamRecharge',
                'client_order_no',
                'TransferLog::create([',
                "Log::error('team recharge failed",
                'writeAgentOperateLog($agent',
            ],
        ],
    ];

    protected $actionPermissionMarkers = [
        [
            'name' => 'recharge_pass_permission',
            'path' => 'app/Admin/Actions/Grid/Recharge/Pass.php',
            'markers' => ['OperationPermission::FINANCE_RECHARGE_PASS', 'OperationPermission::can('],
        ],
        [
            'name' => 'recharge_refuse_permission',
            'path' => 'app/Admin/Actions/Grid/Recharge/Refuse.php',
            'markers' => ['OperationPermission::FINANCE_RECHARGE_REFUSE', 'OperationPermission::can('],
        ],
        [
            'name' => 'withdraw_pass_permission',
            'path' => 'app/Admin/Actions/Grid/Withdraw/Pass.php',
            'markers' => ['OperationPermission::FINANCE_WITHDRAW_PASS', 'OperationPermission::can('],
        ],
        [
            'name' => 'withdraw_refuse_permission',
            'path' => 'app/Admin/Actions/Grid/Withdraw/Refuse.php',
            'markers' => ['OperationPermission::FINANCE_WITHDRAW_REFUSE', 'OperationPermission::can('],
        ],
        [
            'name' => 'member_balance_action_permission',
            'path' => 'app/Admin/Actions/Grid/User/Balance.php',
            'markers' => ['OperationPermission::MEMBER_BALANCE_ADJUST', 'OperationPermission::can('],
        ],
        [
            'name' => 'member_balance_form_permission',
            'path' => 'app/Admin/Forms/Userbalance.php',
            'markers' => ['OperationPermission::MEMBER_BALANCE_ADJUST', 'OperationPermission::assert('],
        ],
        [
            'name' => 'member_back_balance_permission',
            'path' => 'app/Admin/Actions/Grid/User/BackBalance.php',
            'markers' => ['OperationPermission::MEMBER_BALANCE_RECOVER', 'OperationPermission::can('],
        ],
        [
            'name' => 'agent_commission_settle_permission',
            'path' => 'app/Admin/Actions/Grid/User/Fanyong.php',
            'markers' => ['OperationPermission::AGENT_COMMISSION_SETTLE', 'OperationPermission::can('],
        ],
        [
            'name' => 'agent_apply_pass_permission',
            'path' => 'app/Admin/Actions/Grid/AgentApply/Pass.php',
            'markers' => ['OperationPermission::AGENT_APPLY_AUDIT', 'OperationPermission::can('],
        ],
        [
            'name' => 'agent_apply_refuse_permission',
            'path' => 'app/Admin/Actions/Grid/AgentApply/Refuse.php',
            'markers' => ['OperationPermission::AGENT_APPLY_AUDIT', 'OperationPermission::can('],
        ],
        [
            'name' => 'activity_apply_pass_permission',
            'path' => 'app/Admin/Actions/Grid/Activity/Pass.php',
            'markers' => [
                'OperationPermission::ACTIVITY_APPLY_AUDIT',
                'OperationPermission::can(',
                'OperationPermission::assert(OperationPermission::ACTIVITY_APPLY_AUDIT)',
                'OpsChangeAudit::insert',
                "'activity.apply.pass'",
            ],
        ],
        [
            'name' => 'activity_apply_refuse_permission',
            'path' => 'app/Admin/Actions/Grid/Activity/Refuse.php',
            'markers' => [
                'OperationPermission::ACTIVITY_APPLY_AUDIT',
                'OperationPermission::can(',
                'OperationPermission::assert(OperationPermission::ACTIVITY_APPLY_AUDIT)',
                'OpsChangeAudit::insert',
                "'activity.apply.refuse'",
            ],
        ],
        [
            'name' => 'operation_permission_helper',
            'path' => 'app/Admin/Support/OperationPermission.php',
            'markers' => ['class OperationPermission', 'isAdministrator', "config('admin.permission.enable')", 'permission denied: '],
        ],
        [
            'name' => 'operation_permission_nonfunding_baseline',
            'path' => 'app/Admin/Support/OperationPermission.php',
            'markers' => [
                'MEMBER_PASSWORD_RESET',
                'MEMBER_AGENT_UPDATE',
                'MEMBER_STATUS_UPDATE',
                'MEMBER_VIP_UPDATE',
                'GAME_LIST_UPDATE',
                'GAME_PUBLISH_SWITCH',
                'API_PLATFORM_UPDATE',
                'API_PLATFORM_SWITCH',
                'ACTIVITY_CONTENT_UPDATE',
                'ACTIVITY_PUBLISH_SWITCH',
                'OPS_DATA_CLEANUP',
                'OPS_SITE_SETTING_UPDATE',
            ],
        ],
    ];

    protected $forbiddenAdminSources = [
        [
            'name' => 'debug_controller_file',
            'path' => 'app/Admin/Controllers/DebugController.php',
            'needle' => 'class DebugController',
        ],
        [
            'name' => 'debug_controller_echo_output',
            'path' => 'app/Admin/Controllers/DebugController.php',
            'needle' => 'echo "<h1>',
        ],
        [
            'name' => 'debug_controller_view_reference',
            'path' => 'app/Admin/Controllers/DebugController.php',
            'needle' => "view('admin.debug')",
        ],
        [
            'name' => 'clear_form_game_record_delete',
            'path' => 'app/Admin/Forms/ClearForm.php',
            'needle' => "GameRecord::whereDate('created_at', '<', \$time)->delete()",
        ],
        [
            'name' => 'clear_form_activity_delete',
            'path' => 'app/Admin/Forms/ClearForm.php',
            'needle' => "ActivityApply::whereDate('created_at', '<', \$time)->delete()",
        ],
        [
            'name' => 'clear_form_agent_cleanup_option',
            'path' => 'app/Admin/Forms/ClearForm.php',
            'needle' => "'agent_table' =>",
        ],
    ];

    public function handle()
    {
        $outDir = $this->resolvePath($this->option('out-dir'));
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $routes = $this->routeStatus();
        $controllers = $this->controllerStatus();
        $readOnly = $this->readOnlyStatus();
        $controls = $this->controlMarkerStatus();
        $adminOnlyWebRoutes = $this->adminOnlyWebRouteStatus();
        $sensitiveWebApiRoutes = $this->sensitiveWebApiRouteStatus();
        $sensitiveControllerMarkers = $this->sensitiveControllerMarkerStatus();
        $actionPermissionMarkers = $this->actionPermissionMarkerStatus();
        $debug = $this->debugRouteStatus();
        $forbiddenAdminSources = $this->forbiddenAdminSourceStatus();
        $issues = $this->issues($routes, $controllers, $readOnly, $controls, $adminOnlyWebRoutes, $sensitiveWebApiRoutes, $sensitiveControllerMarkers, $actionPermissionMarkers, $debug, $forbiddenAdminSources);

        $summary = [
            'required_route_count' => count($routes),
            'missing_required_routes' => $this->missingCount($routes),
            'required_controller_count' => count($controllers),
            'missing_required_controllers' => $this->missingCount($controllers),
            'read_only_controller_count' => count($readOnly),
            'read_only_missing_count' => $this->missingCount($readOnly, 'read_only'),
            'control_marker_count' => count($controls),
            'control_marker_missing_count' => $this->missingControlCount($controls),
            'admin_only_web_route_count' => count($adminOnlyWebRoutes),
            'admin_only_web_route_unprotected_count' => $this->missingCount($adminOnlyWebRoutes, 'admin_protected'),
            'sensitive_web_api_route_count' => count($sensitiveWebApiRoutes),
            'sensitive_web_api_unprotected_count' => $this->missingCount($sensitiveWebApiRoutes, 'api_auth_protected'),
            'sensitive_controller_marker_count' => count($sensitiveControllerMarkers),
            'sensitive_controller_marker_missing_count' => $this->missingControlCount($sensitiveControllerMarkers),
            'action_permission_marker_count' => count($actionPermissionMarkers),
            'action_permission_marker_missing_count' => $this->missingControlCount($actionPermissionMarkers),
            'debug_route_count' => $debug['count'],
            'forbidden_admin_source_count' => $this->presentCount($forbiddenAdminSources),
        ];

        $report = [
            'generated_at' => date('c'),
            'summary' => $summary,
            'routes' => $routes,
            'controllers' => $controllers,
            'read_only_controllers' => $readOnly,
            'control_markers' => $controls,
            'admin_only_web_routes' => $adminOnlyWebRoutes,
            'sensitive_web_api_routes' => $sensitiveWebApiRoutes,
            'sensitive_controller_markers' => $sensitiveControllerMarkers,
            'action_permission_markers' => $actionPermissionMarkers,
            'debug_routes' => $debug,
            'forbidden_admin_sources' => $forbiddenAdminSources,
            'issue_counts' => $this->issueCounts($issues),
            'issues' => $issues,
        ];

        $this->writeJson($outDir.'/admin_ops_audit.json', $report);
        $this->writeCsv($outDir.'/admin_ops_issues.csv', $issues, [
            'severity',
            'code',
            'message',
            'count',
            'sample',
        ]);
        $this->writeCsv($outDir.'/admin_routes.csv', $routes, ['name', 'needle', 'exists']);
        $this->writeCsv($outDir.'/admin_read_only.csv', $readOnly, ['controller', 'exists', 'read_only']);
        $this->writeCsv($outDir.'/admin_control_markers.csv', $controls, ['name', 'path', 'exists', 'missing_markers']);
        $this->writeCsv($outDir.'/admin_only_web_routes.csv', $adminOnlyWebRoutes, ['name', 'path', 'route_exists', 'admin_protected']);
        $this->writeCsv($outDir.'/admin_sensitive_web_api_routes.csv', $sensitiveWebApiRoutes, ['name', 'path', 'route_exists', 'api_auth_protected']);
        $this->writeCsv($outDir.'/admin_sensitive_controller_markers.csv', $sensitiveControllerMarkers, ['name', 'path', 'exists', 'missing_markers']);
        $this->writeCsv($outDir.'/admin_action_permission_markers.csv', $actionPermissionMarkers, ['name', 'path', 'exists', 'missing_markers']);
        $this->writeCsv($outDir.'/admin_forbidden_sources.csv', $forbiddenAdminSources, ['name', 'path', 'present', 'needle']);

        $this->info(sprintf(
            'routes=%d controllers=%d read_only=%d controls=%d web_admin=%d sensitive_api=%d sensitive_controllers=%d action_permissions=%d debug_routes=%d forbidden_sources=%d issues=%d critical=%d warnings=%d',
            $summary['required_route_count'] - $summary['missing_required_routes'],
            $summary['required_controller_count'] - $summary['missing_required_controllers'],
            $summary['read_only_controller_count'] - $summary['read_only_missing_count'],
            $summary['control_marker_count'] - $summary['control_marker_missing_count'],
            $summary['admin_only_web_route_count'] - $summary['admin_only_web_route_unprotected_count'],
            $summary['sensitive_web_api_route_count'] - $summary['sensitive_web_api_unprotected_count'],
            $summary['sensitive_controller_marker_count'] - $summary['sensitive_controller_marker_missing_count'],
            $summary['action_permission_marker_count'] - $summary['action_permission_marker_missing_count'],
            $summary['debug_route_count'],
            $summary['forbidden_admin_source_count'],
            count($issues),
            $report['issue_counts']['critical'] ?? 0,
            $report['issue_counts']['warning'] ?? 0
        ));
        $this->comment('Reports: '.$outDir);

        return 0;
    }

    protected function routeStatus()
    {
        $source = $this->readSource('app/Admin/routes.php');
        $rows = [];
        foreach ($this->requiredRoutes as $route) {
            $rows[] = [
                'name' => $route['name'],
                'needle' => $route['needle'],
                'exists' => strpos($source, $route['needle']) !== false,
            ];
        }

        return $rows;
    }

    protected function controllerStatus()
    {
        $rows = [];
        foreach ($this->requiredControllers as $controller) {
            $path = 'app/Admin/Controllers/'.$controller;
            $rows[] = [
                'controller' => $controller,
                'path' => $path,
                'exists' => is_file(base_path($path)),
            ];
        }

        return $rows;
    }

    protected function readOnlyStatus()
    {
        $rows = [];
        foreach ($this->readOnlyControllers as $controller) {
            $path = 'app/Admin/Controllers/'.$controller;
            $source = $this->readSource($path);
            $rows[] = [
                'controller' => $controller,
                'exists' => $source !== '',
                'read_only' => strpos($source, 'use ReadOnlyResource;') !== false,
            ];
        }

        return $rows;
    }

    protected function controlMarkerStatus()
    {
        $rows = [];
        foreach ($this->controlMarkers as $control) {
            $source = $this->readSource($control['path']);
            $missing = [];
            foreach ($control['markers'] as $marker) {
                if (strpos($source, $marker) === false) {
                    $missing[] = $marker;
                }
            }

            $rows[] = [
                'name' => $control['name'],
                'path' => $control['path'],
                'exists' => $source !== '',
                'missing_markers' => implode('|', $missing),
            ];
        }

        return $rows;
    }

    protected function adminOnlyWebRouteStatus()
    {
        $rows = [];
        foreach ($this->adminOnlyWebRoutes as $route) {
            $source = $this->readSource($route['path']);
            $rows[] = [
                'name' => $route['name'],
                'path' => $route['path'],
                'route_exists' => strpos($source, $route['route_marker']) !== false,
                'admin_protected' => strpos($source, $route['admin_marker']) !== false,
            ];
        }

        return $rows;
    }

    protected function sensitiveWebApiRouteStatus()
    {
        $rows = [];
        foreach ($this->sensitiveWebApiRoutes as $route) {
            $source = $this->readSource($route['path']);
            $rows[] = [
                'name' => $route['name'],
                'path' => $route['path'],
                'route_exists' => strpos($source, $route['route_marker']) !== false,
                'api_auth_protected' => $this->routeHasApiAuth($source, $route),
            ];
        }

        return $rows;
    }

    protected function sensitiveControllerMarkerStatus()
    {
        $rows = [];
        foreach ($this->sensitiveControllerMarkers as $control) {
            $source = $this->readSource($control['path']);
            $missing = [];
            foreach ($control['markers'] as $marker) {
                if (strpos($source, $marker) === false) {
                    $missing[] = $marker;
                }
            }

            $rows[] = [
                'name' => $control['name'],
                'path' => $control['path'],
                'exists' => $source !== '',
                'missing_markers' => implode('|', $missing),
            ];
        }

        return $rows;
    }

    protected function actionPermissionMarkerStatus()
    {
        $rows = [];
        foreach ($this->actionPermissionMarkers as $control) {
            $source = $this->readSource($control['path']);
            $missing = [];
            foreach ($control['markers'] as $marker) {
                if (strpos($source, $marker) === false) {
                    $missing[] = $marker;
                }
            }

            $rows[] = [
                'name' => $control['name'],
                'path' => $control['path'],
                'exists' => $source !== '',
                'missing_markers' => implode('|', $missing),
            ];
        }

        return $rows;
    }

    protected function routeHasApiAuth($source, array $route)
    {
        if (isset($route['auth_marker']) && strpos($source, $route['auth_marker']) !== false) {
            return true;
        }

        if (empty($route['auth_group_marker'])) {
            return false;
        }

        $routePos = strpos($source, $route['route_marker']);
        if ($routePos === false) {
            return false;
        }

        $searchOffset = 0;
        while (($groupPos = strpos($source, $route['auth_group_marker'], $searchOffset)) !== false) {
            if ($routePos < $groupPos) {
                return false;
            }

            $openBrace = strpos($source, '{', $groupPos);
            if ($openBrace === false || $openBrace > $routePos) {
                $searchOffset = $groupPos + strlen($route['auth_group_marker']);
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

    protected function debugRouteStatus()
    {
        $source = $this->readSource('app/Admin/routes.php');
        preg_match_all('/debug[^\'"]*/', $source, $matches);

        return [
            'count' => count($matches[0]),
            'routes' => array_values(array_unique($matches[0])),
        ];
    }

    protected function forbiddenAdminSourceStatus()
    {
        $rows = [];
        foreach ($this->forbiddenAdminSources as $sourceMarker) {
            $source = $this->readSource($sourceMarker['path']);
            $present = $source !== '' && strpos($source, $sourceMarker['needle']) !== false;
            $rows[] = [
                'name' => $sourceMarker['name'],
                'path' => $sourceMarker['path'],
                'present' => $present,
                'needle' => $sourceMarker['needle'],
            ];
        }

        return $rows;
    }

    protected function issues(array $routes, array $controllers, array $readOnly, array $controls, array $adminOnlyWebRoutes, array $sensitiveWebApiRoutes, array $sensitiveControllerMarkers, array $actionPermissionMarkers, array $debug, array $forbiddenAdminSources)
    {
        $issues = [];

        $missingRoutes = array_filter($routes, function ($row) {
            return empty($row['exists']);
        });
        $this->addIssue($issues, 'critical', 'missing_admin_route', 'Required admin route is missing.', count($missingRoutes), $this->sampleNames($missingRoutes, 'name'));

        $missingControllers = array_filter($controllers, function ($row) {
            return empty($row['exists']);
        });
        $this->addIssue($issues, 'critical', 'missing_admin_controller', 'Required admin controller is missing.', count($missingControllers), $this->sampleNames($missingControllers, 'controller'));

        $missingReadOnly = array_filter($readOnly, function ($row) {
            return empty($row['exists']) || empty($row['read_only']);
        });
        $this->addIssue($issues, 'critical', 'missing_admin_read_only_guard', 'Read-only admin resource guard is missing.', count($missingReadOnly), $this->sampleNames($missingReadOnly, 'controller'));

        $missingControls = array_filter($controls, function ($row) {
            return empty($row['exists']) || $row['missing_markers'] !== '';
        });
        $this->addIssue($issues, 'critical', 'missing_admin_control_marker', 'Required admin control marker is missing.', count($missingControls), $this->sampleNames($missingControls, 'name'));

        $unprotectedWebRoutes = array_filter($adminOnlyWebRoutes, function ($row) {
            return empty($row['route_exists']) || empty($row['admin_protected']);
        });
        $this->addIssue($issues, 'critical', 'unprotected_admin_web_route', 'Admin-only web route is missing admin middleware.', count($unprotectedWebRoutes), $this->sampleNames($unprotectedWebRoutes, 'name'));

        $unprotectedSensitiveApiRoutes = array_filter($sensitiveWebApiRoutes, function ($row) {
            return empty($row['route_exists']) || empty($row['api_auth_protected']);
        });
        $this->addIssue($issues, 'critical', 'unprotected_sensitive_web_api_route', 'Sensitive web API route is missing api_auth middleware.', count($unprotectedSensitiveApiRoutes), $this->sampleNames($unprotectedSensitiveApiRoutes, 'name'));

        $missingSensitiveControllerMarkers = array_filter($sensitiveControllerMarkers, function ($row) {
            return empty($row['exists']) || $row['missing_markers'] !== '';
        });
        $this->addIssue($issues, 'critical', 'missing_sensitive_controller_marker', 'Sensitive agent controller guard or response marker is missing.', count($missingSensitiveControllerMarkers), $this->sampleNames($missingSensitiveControllerMarkers, 'name'));

        $missingActionPermissionMarkers = array_filter($actionPermissionMarkers, function ($row) {
            return empty($row['exists']) || $row['missing_markers'] !== '';
        });
        $this->addIssue($issues, 'critical', 'missing_action_permission_marker', 'High-risk admin action is missing an explicit operation permission guard.', count($missingActionPermissionMarkers), $this->sampleNames($missingActionPermissionMarkers, 'name'));

        $this->addIssue($issues, 'critical', 'admin_debug_route_present', 'Debug admin route is present in production admin routes.', $debug['count'] ?? 0, implode(', ', $debug['routes'] ?? []));

        $forbiddenSources = array_filter($forbiddenAdminSources, function ($row) {
            return !empty($row['present']);
        });
        $this->addIssue($issues, 'critical', 'admin_debug_source_present', 'Debug admin source marker is present in production code.', count($forbiddenSources), $this->sampleNames($forbiddenSources, 'name'));

        return $issues;
    }

    protected function missingCount(array $rows, $field = 'exists')
    {
        $count = 0;
        foreach ($rows as $row) {
            if (empty($row[$field])) {
                $count++;
            }
        }

        return $count;
    }

    protected function missingControlCount(array $rows)
    {
        $count = 0;
        foreach ($rows as $row) {
            if (empty($row['exists']) || $row['missing_markers'] !== '') {
                $count++;
            }
        }

        return $count;
    }

    protected function presentCount(array $rows)
    {
        $count = 0;
        foreach ($rows as $row) {
            if (!empty($row['present'])) {
                $count++;
            }
        }

        return $count;
    }

    protected function sampleNames(array $rows, $field)
    {
        $values = [];
        foreach (array_slice($rows, 0, 10) as $row) {
            $values[] = $row[$field] ?? '';
        }

        return implode(', ', array_filter($values));
    }

    protected function readSource($path)
    {
        $fullPath = base_path($path);
        return is_file($fullPath) ? (string) file_get_contents($fullPath) : '';
    }

    protected function addIssue(array &$issues, $severity, $code, $message, $count, $sample = '')
    {
        if ((int) $count <= 0) {
            return;
        }

        $issues[] = [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'count' => (int) $count,
            'sample' => (string) $sample,
        ];
    }

    protected function issueCounts(array $issues)
    {
        $counts = ['critical' => 0, 'warning' => 0];
        foreach ($issues as $issue) {
            if (isset($counts[$issue['severity']])) {
                $counts[$issue['severity']]++;
            }
        }

        return $counts;
    }

    protected function writeJson($path, array $data)
    {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function writeCsv($path, array $rows, array $columns)
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, $columns);
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $column) {
                $line[] = $row[$column] ?? '';
            }
            fputcsv($handle, $line);
        }
        fclose($handle);
    }

    protected function resolvePath($path)
    {
        if (strpos($path, '/') === 0 || preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return $path;
        }

        return base_path($path);
    }
}
