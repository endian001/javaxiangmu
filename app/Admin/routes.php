<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Dcat\Admin\Admin;

Admin::routes();

Route::group([
	'prefix'        => config('admin.route.prefix'),
	'namespace'     => config('admin.route.namespace'),
	'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

	$router->get('/', 'HomeController@index');
	$router->post('/tcg/12535/pixel-config', 'TcgShellController@savePixelConfig');
	$router->post('/tcg/12535/pixel-data', 'TcgShellController@mutatePixelData');
	$router->post('/tcg/12535/pixel-log', 'TcgShellController@appendPixelLog');
	$router->get('/tcg/10201', 'SystemUserSettingsController@users');
	$router->get('/tcg/10900', 'SystemUserSettingsController@roles');
	$router->get('/tcg/10419', 'SystemUserSettingsController@ipWhitelist');
	$router->get('/tcg/10600', 'SystemUserSettingsController@tasks');
	$router->get('/tcg/10700', 'SystemUserSettingsController@taskHistory');
	$router->get('/tcg/10300', 'SystemUserSettingsController@logs');
	$router->put('/tcg/system-users/{id}', 'SystemUserSettingsController@saveUser');
	$router->post('/tcg/system-roles/{id}/permissions', 'SystemUserSettingsController@saveRolePermissions');
	$router->post('/tcg/ip-whitelists', 'SystemUserSettingsController@saveIpWhitelist');
	$router->put('/tcg/ip-whitelists/{id}', 'SystemUserSettingsController@saveIpWhitelist');
	$router->delete('/tcg/ip-whitelists/{id}', 'SystemUserSettingsController@deleteIpWhitelist');
	$router->post('/tcg/tasks/run', 'SystemUserSettingsController@runTask');
	$router->post('/tcg/tasks/{id}/retry', 'SystemUserSettingsController@retryTask');
	$router->get('/tcg/task-history/export', 'SystemUserSettingsController@exportTaskHistory');
	$router->get('/tcg/system-logs/export', 'SystemUserSettingsController@exportLogs');
	$router->get('/tcg/90400', 'PlatformSettingsController@index');
	$router->post('/tcg/platform-settings/{tab}', 'PlatformSettingsController@saveTab');
	$router->post('/tcg/platform-customer-services', 'PlatformSettingsController@saveCustomerService');
	$router->put('/tcg/platform-customer-services/{id}', 'PlatformSettingsController@saveCustomerService');
	$router->delete('/tcg/platform-customer-services/{id}', 'PlatformSettingsController@deleteCustomerService');
	$router->post('/tcg/platform-app-builds', 'PlatformSettingsController@requestAppBuild');
	$router->get('/tcg/610110', 'KycSettingsController@fields');
	$router->get('/tcg/290000', 'KycSettingsController@rules');
	$router->get('/tcg/290004', 'KycSettingsController@content');
	$router->post('/tcg/kyc/fields', 'KycSettingsController@saveField');
	$router->put('/tcg/kyc/fields/{id}', 'KycSettingsController@saveField');
	$router->delete('/tcg/kyc/fields/{id}', 'KycSettingsController@deleteField');
	$router->post('/tcg/kyc/rules', 'KycSettingsController@saveRule');
	$router->put('/tcg/kyc/rules/{id}', 'KycSettingsController@saveRule');
	$router->delete('/tcg/kyc/rules/{id}', 'KycSettingsController@deleteRule');
	$router->post('/tcg/kyc/content', 'KycSettingsController@saveContent');
	$router->post('/tcg/kyc/upload', 'KycSettingsController@upload');
	$router->get('/tcg/280000', 'PromotionChannelController@links');
	$router->get('/tcg/21160', 'PromotionChannelController@domains');
	$router->get('/tcg/280004', 'PromotionChannelController@landing');
	$router->get('/tcg/280008', 'PromotionChannelController@seo');
	$router->get('/tcg/280015', 'PromotionChannelController@push');
	$router->get('/tcg/280012', 'PromotionChannelController@events');
	$router->post('/tcg/promotion/{code}/items', 'PromotionChannelController@saveItem');
	$router->put('/tcg/promotion/{code}/items/{id}', 'PromotionChannelController@saveItem');
	$router->delete('/tcg/promotion/{code}/items/{id}', 'PromotionChannelController@deleteItem');
	$router->post('/tcg/promotion/{code}/bulk-delete', 'PromotionChannelController@bulkDelete');
	$router->post('/tcg/promotion/{code}/settings', 'PromotionChannelController@saveSettings');
	$router->post('/tcg/promotion/{code}/push-jobs', 'PromotionChannelController@createPushJob');
	$router->get('/tcg/promotion/{code}/events/export', 'PromotionChannelController@exportEvents');
	$router->get('/tcg/90510', 'PlatformOperationsController@siteSettings');
	$router->get('/tcg/36000', 'PlatformOperationsController@domainRoutes');
	$router->get('/tcg/31018', 'PlatformOperationsController@gameVendors');
	$router->get('/tcg/90401', 'PlatformOperationsController@platformFeatures');
	$router->get('/tcg/24001', 'PlatformOperationsController@withdrawalRisk');
	$router->get('/tcg/20068', 'PlatformOperationsController@paymentManagement');
	$router->get('/tcg/20028', 'PlatformOperationsController@paymentAccounts');
	$router->get('/tcg/20500', 'PlatformOperationsController@agentPolicy');
	$router->get('/tcg/21150', 'PlatformOperationsController@commissionSettings');
	$router->get('/tcg/12650', 'PlatformOperationsController@helpCenter');
	$router->get('/tcg/2981', 'PlatformOperationsController@smsSettings');
	$router->get('/tcg/800003', 'PlatformOperationsController@pilotService');
	$router->get('/tcg/31001', 'PlatformOperationsController@fundDetails');
	$router->get('/tcg/20048', 'PlatformOperationsController@bankReconciliation');
	$router->get('/tcg/20032', 'PlatformOperationsController@bankAccounts');
	$router->get('/tcg/90040', 'PlatformOperationsController@feeRecharge');
	$router->post('/tcg/platform-operations/{code}/settings', 'PlatformOperationsController@saveSettings');
	$router->post('/tcg/platform-operations/{code}/records', 'PlatformOperationsController@saveRecord');
	$router->put('/tcg/platform-operations/{code}/records/{id}', 'PlatformOperationsController@saveRecord');
	$router->delete('/tcg/platform-operations/{code}/records/{id}', 'PlatformOperationsController@deleteRecord');
	$router->post('/tcg/platform-operations/{code}/bulk-delete', 'PlatformOperationsController@bulkDelete');
	$router->post('/tcg/platform-operations/{code}/status', 'PlatformOperationsController@changeStatus');
	$router->post('/tcg/platform-operations/{code}/import', 'PlatformOperationsController@import');
	$router->get('/tcg/platform-operations/{code}/export', 'PlatformOperationsController@export');
	$router->get('/tcg/31202', 'GameManagementController@winnerRankings');
	$router->get('/tcg/31000', 'GameManagementController@thirdPartyGames');
	$router->get('/tcg/70037', 'GameManagementController@hotGames');
	$router->get('/tcg/20401', 'GameManagementController@lotteryBranches');
	$router->get('/tcg/5000', 'GameManagementController@lotteryDraws');
	$router->get('/tcg/5500', 'GameManagementController@lotterySettings');
	$router->get('/tcg/5754', 'GameManagementController@lotteryTypes');
	$router->get('/tcg/6400', 'GameManagementController@lotteryPlays');
	$router->get('/tcg/5749', 'GameManagementController@lotterySalesMonitor');
	$router->get('/tcg/5700', 'GameManagementController@lotteryBetInterference');
	$router->get('/tcg/5600', 'GameManagementController@lotteryHotSort');
	$router->get('/tcg/260025', 'GameManagementController@freeSpins');
	$router->post('/tcg/game-management/{code}/records', 'GameManagementController@saveRecord');
	$router->put('/tcg/game-management/{code}/records/{id}', 'GameManagementController@saveRecord');
	$router->delete('/tcg/game-management/{code}/records/{id}', 'GameManagementController@deleteRecord');
	$router->post('/tcg/game-management/{code}/bulk-delete', 'GameManagementController@bulkDelete');
	$router->post('/tcg/game-management/{code}/status', 'GameManagementController@changeStatus');
	$router->post('/tcg/game-management/{code}/import', 'GameManagementController@import');
	$router->get('/tcg/game-management/{code}/export', 'GameManagementController@export');
	$router->get('/tcg/ops/{code}', 'TcgOperationalRecordsController@index');
	$router->post('/tcg/ops/{code}/records', 'TcgOperationalRecordsController@save');
	$router->put('/tcg/ops/{code}/records/{id}', 'TcgOperationalRecordsController@save');
	$router->delete('/tcg/ops/{code}/records/{id}', 'TcgOperationalRecordsController@delete');
	$router->get('/tcg/ops/{code}/export', 'TcgOperationalRecordsController@export');
	$router->get('/tcg/{code}', 'TcgShellController@show')->where('code', '[0-9]+');
	$router->resource('users', 'UserController');
	$router->resource('user-vips', 'UserVipController');
	$router->resource('messages', 'MessageController');
	$router->resource('work-orders', 'WorkOrderController');
	$router->resource('recharge','RechargeController');


	$router->resource('red-envelopes','RedEnvelopesController');
	$router->resource('code-pay','CodePayController');

	$router->resource('withdraws','WithdrawController');
	$router->resource('banks','BankController');
	$router->resource('syslog','SyslogController');
	$router->resource('pay-settings','PaySettingController');
	$router->resource('pay-types','PayTypeController');
	$router->get('/pay-config','SystemConfigController@index');
	$router->resource('activities','ActivityController');
	$router->resource('fanshui','FanshuiLogController');
	$router->resource('activity-apply','ActivityApplyController');
	$router->resource('activity-types','ActivityTypeController');
	$router->resource('transfer-logs','TransferLogController');
	$router->resource('finance-report','FinanceReportController');
	$router->resource('game-records','GameRecordController');
	$router->resource('apis','ApiController');
	$router->resource('game-lists','GameListController');
	$router->resource('game-lists-app','GameListAppController');
	$router->get('/ops-audit','OpsAuditController@index');
	$router->get('/system-setting','SystemConfigController@siteSetting');
	$router->get('/nav-redirect-setting','SystemConfigController@navRedirectSetting');
	$router->resource('bet-report','BetReportController');
	$router->resource('bet-sum','BetSumController');
	$router->resource('templates','TemplateController');
	$router->get('/templates','TemplateController@index');
	$router->post('/setDefaultTemplate/{id}/{type}','TemplateController@setDefaultTemplate');
	$router->resource('agents','AgentController');
	$router->resource('agent-applys','AgentApplyController');
	$router->resource('agent-fenxiang','AgentFenxiangController');
	$router->resource('agent-commission','AgentCommissionController');
	$router->resource('agent-settlements','AgentSettlementController');

	$router->resource('userredpacket','UserredpacketController');
	$router->resource('usercard','UserCardController');
	$router->resource('articlescate','ArticlescateController');
	$router->resource('articles','ArticleController');

	$router->resource('user-operate-logs','UserOperateLogController');
	$router->resource('banners','BannerController');
	$router->get('clear','SystemConfigController@clear');
	$router->post('alert','HomeController@getAlertData');
	$router->resource('user-jk', 'UserActivityController');

});
