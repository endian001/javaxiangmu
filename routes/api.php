<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/login','Api\AuthController@login');
Route::post('/login_pc','Api\AuthController@login_pc');
Route::post('/register','Api\AuthController@register');
Route::get('/promotions/categories','Api\PromotionController@categories');
Route::get('/promotions/popup','Api\PromotionController@popup');
Route::post('/promotions/{id}/exposure','Api\PromotionController@recordExposure');
Route::get('/promotions/{id}','Api\PromotionController@show');
Route::get('/promotions','Api\PromotionController@index');
Route::post('/activitytype','Api\IndexController@activityType'); //获得类型
Route::post('/activitylist','Api\IndexController@activityList'); //活动列表
Route::post('/activitydeatil','Api\IndexController@activitydeatil'); //活动详情
Route::post('/getservicerurl','Api\IndexController@getServicerUrl'); //客户

Route::post('/wxgame/verify','Api\IndexController@wxgameVerify');
Route::post('/wxgame/balance','Api\IndexController@wxgameBalance');
Route::post('/wxgame/bet','Api\IndexController@wxgameBet');
Route::post('/wxgame/win','Api\IndexController@wxgameWin');
Route::post('/wxgame/refund','Api\IndexController@wxgameRefund');
Route::post('/wxgame/status','Api\IndexController@wxgameStatus');
// WXGame callback compatibility for configured Callback URL /notify
Route::post('/notify/verify','Api\IndexController@wxgameVerify');
Route::post('/notify/balance','Api\IndexController@wxgameBalance');
Route::post('/notify/bet','Api\IndexController@wxgameBet');
Route::post('/notify/win','Api\IndexController@wxgameWin');
Route::post('/notify/refund','Api\IndexController@wxgameRefund');
Route::post('/notify/status','Api\IndexController@wxgameStatus');


Route::post('/gamelist','Api\IndexController@getGameList');
Route::get('/image-proxy','Api\IndexController@imageProxy');
Route::get('/stream/config','Api\IndexController@getStreamConfig'); //获取 Stream Chat 配置
Route::post('/stream/token','Api\IndexController@getStreamToken'); //生成 Stream Chat Token
Route::post('/stream/channel','Api\IndexController@createStreamChannel'); //创建 Stream Chat 频道

Route::post('/banklist','Api\IndexController@banklist');

Route::post('/bannerList','Api\IndexController@bannerList');
Route::any('/homenotice','Api\IndexController@homenotice');
Route::any('/getpaybank','Api\IndexController@getpaybank');

Route::post('/homecontent','Api\IndexController@homecontent');

Route::post('/userblance','Api\AuthController@userblance');
Route::post('/systemstatus','Api\IndexController@Systemstatus');

Route::post('/homenoticelist','Api\IndexController@homenoticelist');
Route::post('/homenoticedeatil','Api\IndexController@homenoticedeatil');
Route::post('/app','Api\IndexController@app');
Route::post('/getAppUrl','Api\IndexController@getAppUrl');
Route::get('/getAgentLoginUrl','Api\IndexController@getAgentLoginUrl');
Route::get('/getVisitUrl','Api\IndexController@getVisitUrl');
Route::post('/getAgentInfo','Api\IndexController@getAgentInfo');
Route::get('/agent/url','Api\IndexController@getAgentUrl');
Route::any('/getApiUrl','Api\IndexController@getApiUrl');
Route::get('/get_pay_way','Api\PayController@getPayWay');
Route::get('/getCodePayList','Api\PayController@getCodePayList');
Route::get('/getAllPaymentMethods','Api\PayController@getAllPaymentMethods');
Route::get('/game/list','Api\IndexController@getAllGameList');
Route::get('/all/plat','Api\IndexController@getAllPlat');
Route::post('/uservip','Api\IndexController@uservip');  
Route::post('/article','Api\IndexController@article');
Route::any('/pay/jc_notify','Api\PayController@jcNotify');
Route::any('/pay/cgpay_notify','Api\PayController@cgpay_notify');
Route::any('/credit','Api\IndexController@credit');
Route::post('/gamelistBycode','Api\IndexController@gamelistBycode');
Route::middleware(['crosstttp','api_auth'])->group(function () {
    // 用户
    
    Route::post('/uploadimg','Api\AuthController@uploadimg');  //更新用户转账模式
	Route::post('/userapimoney/{api_code}','Api\IndexController@userapimoney');	
    Route::post('/updateuserinfo','Api\AuthController@updateuserinfo');  //更新用户转账模式
    
    Route::post('/editPassword','Api\AuthController@editPassword');  //修改登录密码
    Route::post('/editPayPassword','Api\AuthController@editPayPasswordDo'); //修改支付密码
    Route::post('/user','Api\AuthController@user');  //获取用户信息
    Route::post('/uptransferstatus','Api\IndexController@uptransferstatus');  //更新用户转账模式
    Route::post('/payinfo','Api\PayController@getpayinfo');
    Route::post('/systembankcardinfo','Api\PayController@systemBankCardInfo');
    Route::post('/recharge','Api\PayController@recharge');
    Route::any('/getPayRange','Api\PayController@getPayRange');
    Route::post('/getcard','Api\PayController@getAllUserCard');
    Route::post('/delcard','Api\PayController@DelbindCard');
    Route::post('/getBetAmount','Api\PayController@getBetAmount');
    Route::post('/refreshusermoney','Api\PayController@refreshusermoney');
  //更新用户转账模式
    
    
    // 充值
    Route::post('/systembankcardinfo','Api\IndexController@systemBankCardInfo');  //获取后台支持的银行
    Route::post('/recharge','Api\PayController@recharge');   //充值
    Route::any('/getPayRange','Api\PayController@getPayRange'); //充值通道范围
    Route::post('/bindcard','Api\PayController@bindCard');   //绑定银行卡
    Route::post('/delcard','Api\PayController@DelbindCard');   //删除银行卡
    Route::post('/getcard','Api\PayController@getAllUserCard');  //获得用户卡
    Route::post('/getBetAmount','Api\PayController@getBetAmount');
    Route::post('/withdraw','Api\PayController@withdraw');  //提现
    Route::post('/transfer','Api\PayController@transfer');  //转账
    Route::post('/transall','Api\PayController@transall'); //一键回收
    Route::post('/refreshusermoney','Api\PayController@refreshusermoney');//个人中心  
     
    Route::post('/doactivityapply','Api\IndexController@doactivity');  //优惠活动
    Route::post('/promotions/{id}/apply','Api\PromotionController@apply');
    Route::post('/activityApplyLog','Api\IndexController@activityApplyLog');

    // 其它
    Route::post('/noticeList','Api\IndexController@noticeList');  //公告列表

    Route::post('/getGameUrl','Api\IndexController@getGameUrl');  //获得游戏链接

    Route::post('/betrecord','Api\IndexController@betRecord');  //获取下注记录
    Route::post('/balancelist','Api\IndexController@userbalancelist');  //
    Route::post('/balancelist2','Api\IndexController@userbalancelist');  //
    //
    Route::post('/gettransrecord','Api\IndexController@transRecord');  //获取转账记录
    
    Route::post('/getrechargerecord','Api\IndexController@rechargeRecord');  //获取充值记录
    
    Route::post('/getwithdrawrecord','Api\IndexController@WithdrawRecord'); //获取提现记录

    Route::post('/message','Api\IndexController@messagecenter');//个人中心
    
    Route::post('/showmessage','Api\IndexController@message');//个人中心
    Route::get('/work-orders','Api\IndexController@workOrderList');
    Route::post('/work-orders','Api\IndexController@workOrderCreate');
    Route::match(['get', 'post'], '/work-orders/list','Api\IndexController@workOrderList');
    Route::post('/work-orders/create','Api\IndexController@workOrderCreate');
    Route::match(['get', 'post'], '/work-orders/{id}','Api\IndexController@workOrderDetail');
    Route::post('/work-orders/{id}/reply','Api\IndexController@workOrderReply');
    Route::post('/work-orders/{id}/close','Api\IndexController@workOrderClose');
    Route::match(['get', 'post'], '/workorder/list','Api\IndexController@workOrderList');
    Route::post('/workorder/create','Api\IndexController@workOrderCreate');
    Route::match(['get', 'post'], '/workorder/detail','Api\IndexController@workOrderDetail');
    Route::post('/workorder/reply','Api\IndexController@workOrderReply');
    Route::post('/workorder/close','Api\IndexController@workOrderClose');
    Route::match(['get', 'post'], '/ticket/list','Api\IndexController@workOrderList');
    Route::post('/ticket/create','Api\IndexController@workOrderCreate');
    Route::match(['get', 'post'], '/ticket/detail','Api\IndexController@workOrderDetail');
    Route::post('/ticket/reply','Api\IndexController@workOrderReply');
    Route::post('/ticket/close','Api\IndexController@workOrderClose');
    
    Route::post('/getdogame','Api\IndexController@getdogame');//个人中心

    Route::post('/getfanshui','Api\IndexController@fanshui');  //获取返水记录
    Route::post('/dofanshui','Api\IndexController@dofanshui');  //领取返水
    Route::post('/balance','Api\AuthController@getUserBalance');
    Route::post('/logoff','Api\AuthController@logoff');
    Route::post('/applyagentdo','Api\IndexController@applyagentdo');
    // 代理功能
    Route::match(['get', 'post'], '/team/report','Agent\IndexController@teamReportApi');
    Route::post('/team/addMember','Agent\IndexController@addMember');
    Route::post('/team/setAgent','Agent\IndexController@setAgent');
    Route::match(['get', 'post'], '/team/childlist','Agent\IndexController@getChildList');
    Route::match(['get', 'post'], '/team/performance','Agent\IndexController@getPerformance');
    Route::post('/team/recharge','Agent\IndexController@rechargeApi');
    Route::get('/team/fdinfo','Agent\IndexController@teamFdInfoApi');
    Route::get('/team/fdList','Agent\IndexController@teamFdListApi');
    Route::post('/team/setFd','Agent\IndexController@teamSetFdApi');
    Route::get('/team/invite/list','Agent\IndexController@teamInviteListApi');
    Route::post('/team/invite/update','Agent\IndexController@teamInviteUpdateApi');
    Route::get('/team/commissionList','Agent\IndexController@teamCommissionListApi');
    Route::get('/team/user/{id}','Agent\IndexController@getUserInfo');
    Route::get('/user/{id}','Agent\IndexController@getUserInfo');
    Route::get('/team/member/bet/{id}','Agent\IndexController@getMemberBetRecord');
    Route::get('/team/member/recharge/{id}','Agent\IndexController@getMemberRechargeRecord');
    Route::get('/team/member/withdraw/{id}','Agent\IndexController@getMemberWithdrawRecord');
    Route::get('/team/member/profit/{id}','Agent\IndexController@getMemberProfitRecord');
    // 红包
    Route::post('/getredpacket','Api\PayController@getRedPacket');
    Route::any('/redpacket','Api\PayController@redPacket');
    Route::get('/userredpacket','Api\PayController@userRedPacket');
    Route::post('/douserredpacket','Api\PayController@doUserRedPacket');

});

////////////////////////////////APP操作////////////////////////////////
Route::any('/app/open/{code}','Api\AppController@open');  //注册
Route::post('/app/register','Api\AppController@register');  //注册
Route::post('/app/login','Api\AppController@login');  //登陆
Route::any('/app/pay_list','Api\AppController@pay_list');  //获取所有充值通道状态
Route::post('/app/islogin','Api\AppController@islogin');  //检查登陆状态
Route::post('/app/getMoney','Api\AppController@getMoney');  //检查余额，网站+接口
Route::post('/app/update_password','Api\AppController@update_password');  //修改密码
Route::post('/app/hall_list','Api\AppController@hall_list');   //获取大厅游戏
Route::post('/app/api_login','Api\AppController@api_login');  //获取游戏登陆链接
Route::post('/app/service_center','Api\AppController@service_center');  //获取公告信息
Route::post('/app/systeminfo','Api\AppController@systeminfo');  // customer service links
Route::post('/app/querys','Api\AppController@querys');  //获取APP常见问题
Route::post('/app/userChildren','Api\AppController@userChildren');  //获取代理直属下线数据
Route::post('/app/Regurgitation','Api\AppController@Regurgitation');  //获取代理直属下线数据
Route::post('/app/gameRecordList','Api\AppController@gameRecordList');  //获取自己投注记录
Route::post('/app/usergameForm','Api\AppController@usergameForm');  //获取个人报表
Route::post('/app/activities','Api\AppController@activities');  //获取活动列表
Route::post('/app/activitiesgo','Api\AppController@activitiesgo');  //申请活动
Route::post('/app/bindbanklist','Api\AppController@bindbanklist');  //获取可绑定银行卡列表
Route::post('/app/post_update_bank_info','Api\AppController@post_update_bank_info');  //绑定银行卡
Route::post('/app/post_drawing','Api\AppController@post_drawing');  //提现
Route::post('/app/cash_list','Api\AppController@cash_list');  //提现记录
Route::post('/app/getbank_info','Api\AppController@getbank_info');  //获取充值银行卡信息
Route::post('/app/alipay_info','Api\AppController@alipay_info');  //获取充值支付宝信息
Route::post('/app/wechat_info','Api\AppController@wechat_info');  //获取充值微信信息
Route::post('/app/usdt_info','Api\AppController@usdt_info');  //获取充值USDT信息
Route::post('/app/cgpay_info','Api\AppController@cgpay_info');  //获取充值CGPay信息
Route::post('/app/bank_pay','Api\AppController@bank_pay');  //银行卡充值
Route::post('/app/alipay_pay','Api\AppController@alipay_pay');  //支付宝充值
Route::post('/app/wechat_pay','Api\AppController@wechat_pay');  //微信充值
Route::post('/app/usdt_pay','Api\AppController@usdt_pay');  //USDT充值
Route::post('/app/cgpay_pay','Api\AppController@cgpay_pay');  //CGPay充值
Route::post('/app/zxcgpay_pay','Api\AppController@zxcgpay_pay');  //在线CGPay充值
Route::post('/app/recharge_list','Api\AppController@recharge_list');  //充值记录
