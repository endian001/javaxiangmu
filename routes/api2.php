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

Route::post('/gamelist','Api\IndexController@getGameList');
Route::post('/gamelistBycode','Api\IndexController@gameslist');
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
Route::get('/get_pay_way','Api\PayController@getPayWay');
Route::get('/game/list','Api\IndexController@getAllGameList');
Route::get('/all/plat','Api\IndexController@getAllPlat');
 Route::post('/uservip','Api\IndexController@uservip');  
Route::post('/article','Api\IndexController@article');
Route::any('/pay/jc_notify','Api\PayController@jcNotify');

Route::middleware(['crosstttp','api_auth'])->group(function () {
    // 用户
    
    Route::post('/uploadimg','Api\AuthController@uploadimg');  //更新用户转账模式
    Route::post('/updateuserinfo','Api\AuthController@updateuserinfo');  //更新用户转账模式
    
    Route::post('/editPassword','Api\AuthController@editPassword');  //修改登录密码
    Route::post('/editPayPassword','Api\AuthController@editPayPasswordDo'); //修改支付密码
    Route::post('/user','Api\AuthController@user');  //获取用户信息
    Route::post('/uptransferstatus','Api\IndexController@uptransferstatus');  //更新用户转账模式
    Route::post('/payinfo','Api\PayController@getpayinfo');
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
    Route::post('/activityApplyLog','Api\IndexController@activityApplyLog');

    // 其它
    Route::post('/noticeList','Api\IndexController@noticeList');  //公告列表

    Route::post('/getGameUrl','Api\IndexController@getGameUrl');  //获得游戏链接

    Route::post('/betrecord','Api\IndexController@betRecord');  //获取下注记录
    Route::post('/balancelist','Api\IndexController@userbalancelist');  //

    //
    Route::post('/gettransrecord','Api\IndexController@transRecord');  //获取转账记录
    
    Route::post('/getrechargerecord','Api\IndexController@rechargeRecord');  //获取充值记录
    
    Route::post('/getwithdrawrecord','Api\IndexController@WithdrawRecord'); //获取提现记录

    Route::post('/message','Api\IndexController@messagecenter');//个人中心
    
    Route::post('/showmessage','Api\IndexController@message');//个人中心
    
    Route::post('/getdogame','Api\IndexController@getdogame');//个人中心

    Route::post('/getfanshui','Api\IndexController@fanshui');  //获取返水记录
    Route::post('/dofanshui','Api\IndexController@dofanshui');  //领取返水
    Route::post('/balance','Api\AuthController@getUserBalance');
    Route::post('/logoff','Api\AuthController@logoff');
    Route::post('/applyagentdo','Api\IndexController@applyagentdo');
    // 红包
    Route::post('/getredpacket','Api\PayController@getRedPacket');
    Route::any('/redpacket','Api\PayController@redPacket');
    Route::get('/userredpacket','Api\PayController@userRedPacket');
    Route::post('/douserredpacket','Api\PayController@doUserRedPacket');

});
