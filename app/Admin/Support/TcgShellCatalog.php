<?php

namespace App\Admin\Support;

class TcgShellCatalog
{
    private const MENU = <<<'TXT'
系统用户设置|fa-users|10201:系统用户管理;10900:角色权限解析;10419:IP白名单;10600:任务管理;10700:历史任务管理;10300:系统用户日志
平台设置|fa-cogs|90400:平台基本配置;90510:平台站点配置;36000:域名线路管理;31018:游戏厂商设置;90401:平台功能配置;24001:提现风控配置;20068:平台支付管理;20028:支付账号设置;20500:代理政策设置;21150:平台佣金设置;12650:帮助中心设置;2981:简讯发送设置;800003:飞行员服务
KYC身份管理|fa-id-card-o|610110:用户信息管理;290000:KYC功能配置;290004:前台内容配置
推广渠道|fa-bullhorn|280000:投放链接设置;21160:推广域名管理;280004:落地页配置;280008:SEO配置;280015:未注册推播;12535:像素埋点设置;280012:事件记录
平台资金|fa-bank|31001:平台资金详情;20048:银行对账报表;20032:银行账号明细;90040:平台费用充值
游戏管理|fa-gamepad|31202:中奖排行管理;31000:三方游戏列表;70037:平台热门游戏;20401:彩票分公司;5000:彩票开奖记录;5500:彩票基本设置;5754:彩种基本参数;6400:彩票玩法参数;5749:玩法销售监控;5700:彩票投注干扰;5600:彩种热门排序;260025:免费转次数
代理管理|fa-address-book-o|20200:代理用户管理;20470:代理签约概况;20084:逐步日工资管理;60401:代理聊天室管理
代理资金|fa-money|31326:代理占比;31052:代理返点;31042:日工资;31032:逐步日工资;31039:代理佣金;31029:代理分红;31219:时薪
玩家资金|fa-credit-card|31009:玩家交易查询;20000:玩家充值;120000:玩家资金调整;20080:玩家互转;20008:玩家提现;20095:手工充值收款;2162:转账异常处理;21170:渠道监控
玩家管理|fa-user-o|311792:玩家用户管理;182:关联玩家查询;73050:用户操作日志;11041:用户登录限制;26100:用户内部架构;25100:玩家提款提示;20246:用户昵称限制;52000:玩家限红设置;12660:在线面板;41002:用户游戏限制;20430:运营标签;680308:OTP验证记录;20225:玩家钱包余额;2865:投诉建议查询
用户分析|fa-search|181:高级搜索
流水返水|fa-list-alt|31121:玩家游戏统计;31050:玩家投注返水;31002:彩票投注记录;31379:三方彩票订单;50300:真人游戏记录;40020:电子钓鱼记录;50100:体育电竞订单;40090:棋牌游戏记录;40101:游戏打赏记录
玩家等级|fa-star|250001:等级设置;250002:等级列表;250003:等级历史;250004:前台文案设置;20324:VIP俸禄奖励;20346:升级奖励
🌟活动红利|fa-gift|27000:活动公告管理;20393:活动黑名单;24780:注册班级活动🎯;24781:登入班级活动;24782:充值类活动;24783:兴趣班活动;24784:抽奖类活动;24785:手动活动红利;24786:活动票券管理;24800:翻倍管理策略;20400:红利派发记录;610223:任务活动管理🔥
邀请返佣|fa-share-alt|240001:邀请返佣设置;240110:前台文案配置;240111:返佣等级列表;240003:返佣发放记录;240004:邀请关系查询;240038:邀请贡献分析
积分商城|fa-shopping-cart|20599:商城商品设置;20530:商品兑换申请;20260:玩家积分调整;20220:积分规则设置;31210:积分奖励记录
公告通知|fa-bell-o|20300:公告管理（即将到来）;400000:公告管理;80001:玩家站内信;207200:营销邮件管理;650001:行销报表
报表管理|fa-bar-chart|31023:平台损益总表;31690:平台损益;31672:活跃用户分析;31456:每日充值留存;31100:周期充值留存;31207:代理市场分析;31020:代理贡献分析;31105:玩家贡献分析;31067:游戏分析玩家;31103:游戏收益分析;38060:游戏分析;39081:充值分析报表;39082:提现分析报表;31140:虚拟用户报表
风险控管|fa-shield|670008:登录与关联分析;680301:流量域名监控
TXT;

    private const PAGES = <<<'TXT'
12535
summary:像素埋点设置按 WSD 文档校正：覆盖后台预设 ID、URL 参数追踪、事件映射、投放链接生成、Adjust / AppsFlyer S2S、App Bridge、马甲包参数与广告渠道回传。
tab:像素设置
guide:本页面用于按域名或代理推荐码设置默认推广 ID，也支持通过投放链接临时传入像素参数。
guide:Appsflyer 使用 Web SDK，相关服务需要通过代理付费开通；马甲包对接方式不适用普通 H5。
section_primary_table:预设推广 ID>推广平台|ID|操作
row:GA4 ID（预设）||编辑
row:脸书像素 ID（预设）||编辑
row:抖音像素 ID（预设）||编辑
row:Appsflyer ID（预设）||编辑
section_primary_buttons:常用操作>投放链接生成器
guide:如果域名没有配置专属推广 ID，系统统一套用上方预设推广 ID。
guide:投放只支持 H5 手机版。
guide:快手可以直接投放 H5 网址，不需要在这里配置。
guide:使用 GA4 ID、GTM ID 前，需要请客户经理开单调整 H5 后生效。
filters:域名|代理推荐码|推广ID|备注|状态
filter_buttons:新增|搜索
table:ID|域名|代理推荐码|GA4 ID|GTM ID|脸书像素ID|抖音像素ID|Appsflyer ID|备注|投放链接|操作|开关
empty_table:true
section_table:URL参数追踪>参数名|渠道|宏值/示例|说明|必填|状态|操作
row:fbPixelId|Facebook/Meta|123456789012345|Facebook Pixel ID，可用于首页/注册页追踪|否|启用|编辑
row:tiktokPixelId|TikTok|1730000000000000|TikTok Pixel ID|否|启用|编辑
row:kwai_pixel_id|Kwai 快手|987654321|新快手参数，替代 kwaiPixelBaseCode|否|启用|编辑
row:kwaiPixelBaseCode|Kwai 旧参数|987654321|旧参数，保留兼容，默认不推荐使用|否|兼容|编辑
row:gtagId|Google GA4|G-PFSS5SP9ND|Google tag / GA4 Measurement ID|否|启用|编辑
row:gtmId|Google Tag Manager|GTM-ABCDE12|GTM 容器 ID，支持全事件|否|启用|编辑
row:bigoPixelId|Bigo|bigo_pixel_id|Bigo Pixel Id，用于注册/首充事件|否|启用|编辑
row:affiliateCode|代理绑定|agent001|注册页绑定代理推荐码|否|启用|编辑
row:cid|Voluum|{clickid}|Voluum 点击 ID|按渠道|启用|编辑
row:tfTracker|Traffic Factory|{conversion_tracking}|Traffic Factory 转化追踪宏|按渠道|启用|编辑
row:visitor_id|PropellerAds|{sub_id}|PropellerAds 注册回传 visitor_id|按渠道|启用|编辑
row:rtCid|Red Track|{clickid}|Red Track 点击 ID|按渠道|启用|编辑
row:obclid|Outbrain|{ob_click_id}|Outbrain click ID|按渠道|启用|编辑
row:kadam_id|Kadam|{click_id}|Kadam 点击 ID|按渠道|启用|编辑
row:pixel_click_id|OKSpin|{click_id}|OKSpin 点击 ID|按渠道|启用|编辑
row:oks_pixel_id|OKSpin|{pixel_id}|OKSpin Pixel ID|按渠道|启用|编辑
row:phxCid|Phoenix Ads|{click_id}|Phoenix Ads 点击 ID|按渠道|启用|编辑
row:mgsClickId|MgSkyAds|{click_id}|MgSkyAds 点击 ID|按渠道|启用|编辑
row:devilsClickId|Devils tracker|{click_id}|Devils tracker 点击 ID|按渠道|启用|编辑
row:macanClickId|Macan Studio|{click_id}|Macan Studio 点击 ID|按渠道|启用|编辑
row:rbclickid|RouterHub|{click_id}|RouterHub 点击 ID|按渠道|启用|编辑
row:egwId|EGW / 传音|{pixel_id}|EGW 像素 ID|按渠道|启用|编辑
row:fortune|Fortune|1|Fortune 投放标记，需要同时传 clickId|按渠道|启用|编辑
row:clickId|Fortune|{click_id}|Fortune 点击 ID|按渠道|启用|编辑
row:keitaroClickId|Keitaro|{click_id}|Keitaro 点击 ID|按渠道|启用|编辑
row:clickid|Revosurge|{click_id}|需要同时传 revosurge=1|按渠道|启用|编辑
row:rmClickId|Resiliencemedia|{clickId}|Resiliencemedia 点击 ID|按渠道|启用|编辑
section_table:投放链接生成器>字段|可选值|示例|说明|操作
row:页面类型|首页 / 注册|/m/home 或 /m/register|文档示例首页和注册页均支持 URL 参数|生成
row:像素参数|fbPixelId / tiktokPixelId / kwai_pixel_id / gtagId / gtmId / bigoPixelId / oks_pixel_id|fbPixelId=xxxx&tiktokPixelId=yyyy|多个渠道可以组合传递|生成
row:代理邀请码|affiliateCode|affiliateCode=agent001|注册页绑定代理推荐码|生成
row:点击宏|cid / tfTracker / visitor_id / rtCid / obclid / kadam_id / phxCid / mgsClickId / rmClickId|cid={clickid}|广告平台点击 ID 宏透传|生成
section_table:事件映射>H5事件|说明|Facebook|TikTok|Kwai|Google Gtag/GTM|AppsFlyer PBA|Bigo|OKSpin|状态
row:firstOpen|首次开启 App|firstOpen (Custom Event)|firstOpen (Custom Event)|N/A|firstOpen (Custom Event)|firstOpen (Custom Event)|N/A|N/A|启用
row:registerSubmit|点击提交注册，不代表注册成功|registerSubmit Custom Event|registerSubmit Custom Event|不支持自定义|registerSubmit|registerSubmit Custom Event|-|-|启用
row:register|注册成功|CompleteRegistration|CompleteRegistration|completeRegistration|register (Custom Event)|register (Custom Event)|ec_register|EVENT_COMPLETE_REGISTRATION|启用
row:depositSubmit|点击充值按钮，未到账|InitiateCheckout|InitiateCheckout|initiatedCheckout|depositSubmit (Custom Event)|depositSubmit (Custom Event)|N/A|N/A|启用
row:firstDeposit|首充（点击/发起充值，渠道映射待正式文档）|N/A|N/A|N/A|N/A|N/A|N/A|N/A|仅记录
row:firstDepositArrival|首充到账旧事件|firstDepositArrival (Custom Event)|firstDepositArrival (Custom Event)|firstDeposit|firstDepositArrival (Custom Event)|firstDepositArrival (Custom Event)|ec_purchase|EVENT_FIRST_DEPOSIT|启用
row:startTrial|2024/4/12 新增，逐步替代 firstDepositArrival|StartTrial|Subscribe|N/A|startTrial (Custom Event)|startTrial (Custom Event)|N/A|N/A|启用
row:deposit|充值到账 / 购物|Purchase|CompletePayment|purchase|deposit (Custom Event)|deposit (Custom Event)|N/A|EVENT_PURCHASE|启用
row:redeposit|复充|redeposit (Custom Event)|redeposit (Custom Event)|N/A|redeposit (Custom Event)|redeposit (Custom Event)|N/A|N/A|启用
row:withdraw|提现到账|withdraw (Custom Event)|withdraw (Custom Event)|N/A|withdraw (Custom Event)|withdraw (Custom Event)|N/A|N/A|启用
section:事件注意事项>首充到账时必须同时触发 firstDepositArrival、startTrial、deposit；三个事件业务语义均需保留，不能互相替代。
tab:Adjust设置
guide:Adjust 后台：https://suite.adjust.com/
guide:1. app_token 需要在 Adjust 后台 AppView > 创建应用后取得。
guide:2. event_token 需要在对应应用中配置事件。
guide:3. event_token 需要和马甲包 H5 上报接口分开配置，否则统计会重复。
guide:4. 马甲包嵌入我方 H5 时必须传递以下 S2S 必要参数。
guide:- ad_app_token：应用 app_token，必传。
guide:- gps_adid：Android 必传。
guide:- idfa：iOS 必传。
guide:- adid：iOS 必传。
guide:范例H5网址：https://<DOMAIN>/m/index.html?ad_app_token=&gps_adid=
filters:app_token
filter_buttons:新增|搜索
table:app_token|app描述|操作
empty_table:true
section:补充说明>Adjust S2S 上报延迟约 20-40 分钟；接入 S2S 后 APP 不需要再实现 eventTracker。
section_table:事件 Token 与 S2S 扩展配置>事件|事件说明|Adjust Event Token|上报时机|状态|操作
row:login|登录||用户登录成功|启用|编辑
row:register|注册成功||注册接口成功返回|启用|编辑
row:firstDepositArrival|首充到账||首笔充值到账|启用|编辑
row:deposit|充值到账||任意充值到账|启用|编辑
row:redeposit|复充||非首笔充值到账|启用|编辑
row:withdraw|提现到账||提现到账|启用|编辑
section_table:WebView启动URL>场景|URL模板|必传参数|说明|操作
row:无邀请码|https://<DOMAIN>/m/index.html?ad_app_token=&gps_adid=&idfa=&adid=|Android: ad_app_token/gps_adid；iOS: idfa/adid|DOMAIN 必须 https 且必须以 www 开头|复制
row:有邀请码|https://<DOMAIN>/m/index.html?affiliateCode=&ad_app_token=&gps_adid=&idfa=&adid=|affiliateCode + 设备 ID|用于绑定注册代理|复制
row:User-Agent|AppShellVer + UUID|AppShellVer / UUID|用于隐藏 H5 App 下载 Bar|查看
section_table:客户端校验>检查项|Android|iOS|失败处理|状态|操作
row:域名格式|https + www 开头|https + www 开头|禁止启动并提示域名错误|启用|测试
row:设备标识|ad_app_token + gps_adid|idfa + adid|缺少时不发送 S2S|启用|测试
row:gps_adid获取失败|必须补传 adid|-|记录失败原因并阻止错误上报|启用|测试
row:User-Agent|包含 AppShellVer 与 UUID|包含 AppShellVer、model 与 UUID|隐藏下载栏并保存 App 登录信息|启用|测试
row:弹窗与链接|window.open / alert / a标签|window.open / alert / a标签|调用原生浏览器或内嵌 WebView|启用|测试
tab:Appsflyer设置
section:后台地址>https://hq1.appsflyer.com/
section_fields:基础配置>App Id|Dev Key|Web Dev Key|PBA Web SDK|Bundle ID|代理说明
section_fields:S2S设备参数>af_app_id|appsflyer_id|advertising_id|oaid|idfa|idfv|inApp
section_table:事件配置>H5事件|AppsFlyer事件名|说明|状态|操作
row:firstOpen|firstOpen (Custom Event)|首次开启 App|启用|编辑
row:registerSubmit|registerSubmit (Custom Event)|点击提交注册|启用|编辑
row:register|register (Custom Event)|注册成功事件|启用|编辑
row:depositSubmit|depositSubmit (Custom Event)|提交充值按钮|启用|编辑
row:firstDeposit|N/A|首充点击/申请，仅记录，渠道映射待正式文档|停用|查看
row:firstDepositArrival|firstDepositArrival (Custom Event)|首充到账旧事件|启用|编辑
row:startTrial|startTrial (Custom Event)|首充到账新事件|启用|编辑
row:deposit|deposit (Custom Event)|充值到账|启用|编辑
row:redeposit|redeposit (Custom Event)|复充|启用|编辑
row:withdraw|withdraw (Custom Event)|提现到账，af_revenue 为负数|启用|编辑
section_table:马甲包URL>场景|URL模板|必传参数|选填参数|操作
row:首页 Adjust S2S|https://<DOMAIN>/m/index.html?ad_app_token=&gps_adid=|ad_app_token / gps_adid|affiliateCode / inApp|复制
row:注册 AppsFlyer S2S|https://<DOMAIN>/m/register?af_app_id=&appsflyer_id=|af_app_id / appsflyer_id|advertising_id / oaid / idfa / idfv / affiliateCode|复制
buttons:新增App|新增事件|SDK测试|搜索
table:App Id|Dev Key|Web SDK|App 描述|事件数|状态|操作
row:com.demo.android|af_dev_key_demo|已启用|Android 主包|10|启用|编辑
row:id123456789|af_ios_dev_key|已启用|iOS 马甲包|10|启用|编辑
tab:App API对接
notice:启动游戏和登录依赖 APP 原生 Bridge；Android 使用 window.Android，iOS 使用 window.webkit.messageHandlers。
section_table:Android Bridge>方法|参数|用途|测试入口|状态|操作
row:eventTracker|eventName, eventValues JSON|接收 H5 埋点并交给 AppsFlyer/Facebook/Google SDK|测试事件发送|必做|测试
row:openAndroid|url|使用系统默认浏览器打开链接|测试外跳窗口|必做|测试
row:openWebView|url|打开 APP 内嵌 WebView|测试开启 webview|必做|测试
row:closeWebView|无|关闭当前内嵌 WebView|关闭测试页|必做|测试
row:facebookLogin|callback|Firebase Facebook 登录并回调 idToken|测试 Facebook 登录|选做|测试
row:googleLogin|callback|Firebase Google 登录并回调 idToken|测试 Google 登录|选做|测试
row:getFcmToken|callback|取得 FCM Token 并回调|测试 FCM|必做|测试
section_table:iOS Bridge>方法|参数|用途|测试入口|状态|操作
row:eventTracker|eventName, eventValue JSON|接收 H5 埋点|测试事件发送|必做|测试
row:openSafari|url, type=1|系统 Safari 外跳|测试外跳窗口|必做|测试
row:openSafari|url, type=2|APP 内嵌 WebView|测试开启 webview|必做|测试
row:firebaseLogin|callback, channel|Firebase Facebook / Google 登录|测试社交登录|选做|测试
section_table:事件Payload>事件|必传字段|金额字段|注意事项|操作
row:firstOpen|无|无|首次开启 App|查看
row:registerSubmit|method|无|method 为 username 或 sms|查看
row:register|method / customerId / customerName / mobileNum|无|注册成功后发送|查看
row:depositSubmit|customerId / customerName / revenue / value / af_revenue|af_revenue|点击充值，未到账|查看
row:firstDeposit|customerId / customerName / revenue / value / af_revenue|af_revenue|首充点击/申请|查看
row:withdraw|customerId / customerName / amount / value / af_revenue|af_revenue 必须为负数|提现到账|查看
row:firstDepositArrival|customerId / customerName / revenue / value / af_revenue|af_revenue|首充到账|查看
row:deposit|customerId / customerName / revenue / value / af_revenue|af_revenue|充值到账|查看
row:redeposit|customerId / customerName / revenue / value / af_revenue|af_revenue|复充到账|查看
buttons:测试User-Agent|测试WebView|测试外跳|测试事件|测试Google登录|测试Facebook登录|测试FCM
tab:马甲包参数
section_fields:H5启动参数>inApp|affiliateCode|ad_app_token|gps_adid|adid|idfa|af_app_id|appsflyer_id|advertising_id|oaid|idfv
section_table:参数规则>参数|平台|必填|说明|操作
row:inApp|Android / iOS|否|1 强制隐藏下载栏并使用 window.open；0 显示下载栏|编辑
row:affiliateCode|Android / iOS|否|代理推荐码|编辑
row:ad_app_token|Android|Adjust S2S 必填|Adjust App Token|编辑
row:gps_adid|Android|Adjust S2S 必填|Google Advertising ID|编辑
row:adid|Android / iOS|条件必填|iOS 必填；Android 无 gps_adid 时必填|编辑
row:idfa|iOS|Adjust S2S 必填|iOS 广告标识|编辑
row:af_app_id|Android / iOS|AppsFlyer S2S 必填|AppsFlyer 应用 ID|编辑
row:appsflyer_id|Android / iOS|AppsFlyer S2S 必填|AppsFlyer UID|编辑
row:advertising_id|Android|否|可取得时传 GAID|编辑
row:oaid|Android|否|Android OAID|编辑
row:idfv|iOS|否|iOS Vendor ID|编辑
section:上线前检查>实现 App Bridge|设置正确 User-Agent|测试事件回调|测试外跳/内嵌窗口|测试 FCM|验证 Adjust / AppsFlyer 后台事件
tab:Facebook 转换 API
notice:支持 Pixel 浏览器事件和 Facebook Conversions API；test_event_code 用于测试事件。
section_fields:API配置>Pixel Id|Access Token|test_event_code|Event Source URL|事件去重ID|有效
section_table:事件名称配置>H5事件|Facebook事件|类型|说明|状态|操作
row:registerSubmit|registerSubmit|Custom Event|点击提交注册|启用|编辑
row:register|CompleteRegistration|Standard Event|注册成功|启用|编辑
row:depositSubmit|InitiateCheckout|Standard Event|提交充值|启用|编辑
row:firstOpen|firstOpen|Custom Event|首次开启 App|启用|编辑
row:firstDeposit|N/A|N/A|首充点击/申请，仅记录，渠道映射待正式文档|停用|查看
row:firstDepositArrival|firstDepositArrival|Custom Event|首充到账旧事件|启用|编辑
row:startTrial|StartTrial|Standard Event|首充到账新事件|启用|编辑
row:deposit|Purchase|Standard Event|充值到账|启用|编辑
row:redeposit|redeposit|Custom Event|复充|启用|编辑
row:withdraw|withdraw|Custom Event|提现到账|启用|编辑
filters:Pixel Id|Access Token|test_event_code|有效
buttons:新增Pixel|Facebook活动设置|生成自定义事件|测试事件|搜索
table:Pixel Id|Access Token|test_event_code|描述|有效|事件数|操作
row:123456789012345|EAABsbCS1iHgBA...|TEST12345|主站 Meta Pixel|是|6|编辑
row:234567890123456|EAABsbCS1iHgBB...|TEST67890|代理落地页 Pixel|是|6|编辑
tab:TikTok 设置
section_fields:Pixel配置>Pixel Id|Access Token|广告账号|Event API|有效
section_table:事件名称配置>H5事件|TikTok事件|说明|状态|操作
row:registerSubmit|registerSubmit Custom Event|点击提交注册|启用|编辑
row:register|CompleteRegistration|注册成功|启用|编辑
row:depositSubmit|InitiateCheckout|提交充值|启用|编辑
row:firstOpen|firstOpen Custom Event|首次开启 App|启用|编辑
row:firstDeposit|N/A|首充点击/申请，仅记录，渠道映射待正式文档|停用|查看
row:firstDepositArrival|firstDepositArrival Custom Event|首充到账旧事件|启用|编辑
row:startTrial|Subscribe|首充到账新事件|启用|编辑
row:deposit|CompletePayment|充值到账|启用|编辑
row:redeposit|redeposit Custom Event|复充|启用|编辑
row:withdraw|withdraw Custom Event|提现到账|启用|编辑
buttons:新增Pixel|事件测试|搜索
table:Pixel Id|Access Token|描述|有效|事件数|操作
row:1730000000000000|tiktok_access_token_demo|TikTok 主 Pixel|是|7|编辑
row:1730000000000001|tiktok_access_token_agent|代理 Pixel|是|7|编辑
tab:Kwai设置
notice:Kwai 支持事件有限，快手广告可直接投放平台网址并自动附加 kwai_pixel_id。
section_fields:Kwai配置>Pixel Id|Access Token|kwai_pixel_id|kwaiPixelBaseCode旧参数|有效
section_table:事件名称配置>H5事件|Kwai事件|说明|状态|操作
row:register|completeRegistration|注册成功|启用|编辑
row:depositSubmit|initiatedCheckout|提交充值|启用|编辑
row:firstDepositArrival|firstDeposit|首充到账旧事件|启用|编辑
row:startTrial|N/A|首充到账新事件，Kwai 不支持|停用|查看
row:deposit|purchase|充值到账|启用|编辑
buttons:新增Pixel|同步Token|搜索
table:Pixel Id|Access Token|描述|有效|操作
row:987654321|kwai_access_token_demo|Kwai 主 Pixel|是|编辑
row:987654322|kwai_access_token_agent|代理 Pixel|是|编辑
tab:Voluum设置
section_fields:回传配置>上报域名|cid参数名|Postback URL|默认币种|签名密钥|启用
section_table:支持事件>事件|说明|投放URL参数|回传状态|操作
row:register|注册成功|cid={clickid}|启用|编辑
row:startTrial|首充到账|cid={clickid}|启用|编辑
row:deposit|充值到账|cid={clickid}|启用|编辑
row:redeposit|复充|cid={clickid}|启用|编辑
buttons:提交|测试回传|查看日志|复制URL模板
table:上报域名|cid参数|支持事件|状态|操作
row:https://tracker.voluum.com|cid|register/startTrial/deposit/redeposit|启用|编辑
tab:PropellerAds设置
section_fields:注册回传配置>aid|tid|visitor_id参数|回传URL|启用
section_table:回传说明>项目|内容|状态|操作
row:支持事件|register 注册成功|启用|编辑
row:URL参数|visitor_id={sub_id}|启用|编辑
row:回传地址|http://ad.propellerads.com/conversion.php?aid={aid}&pid=&tid={tid}&visitor_id={sub_id}|启用|复制
buttons:提交|测试注册回传|查看日志
table:aid|tid|visitor_id|描述|状态|操作
row:demo-aid|demo-tid|{sub_id}|PropellerAds 注册回传|启用|编辑
tab:Twitter设置
section_fields:Pixel配置>Pixel Id|Access Token|广告账号|Website Tag|Conversion API|有效
section_table:事件名称配置>H5事件|Twitter事件|说明|状态|操作
row:register|CompleteRegistration|注册成功|启用|编辑
row:depositSubmit|InitiateCheckout|提交充值|启用|编辑
row:deposit|Purchase|充值到账|启用|编辑
filters:Pixel Id|广告账号|有效
buttons:新增Pixel|事件测试|搜索
table:Pixel Id|描述|事件数|状态|操作
row:twitter_pixel_001|Twitter 主 Pixel|3|启用|编辑
row:twitter_pixel_002|代理推广 Pixel|3|启用|编辑
tab:Traffic Factory设置
section_fields:渠道配置>代理账号|tfTracker参数|conversion_tracking宏|Postback URL|启用
section_table:支持事件>事件|说明|投放URL参数|状态|操作
row:register|注册成功|tfTracker={conversion_tracking}|启用|编辑
row:startTrial|首充到账|tfTracker={conversion_tracking}|启用|编辑
row:deposit|充值到账|tfTracker={conversion_tracking}|启用|编辑
row:redeposit|复充|tfTracker={conversion_tracking}|启用|编辑
filters:代理账号|状态
buttons:新增|测试回传|搜索
table:代理账号|tfTracker|支持事件|描述|状态|操作
row:agent001|{conversion_tracking}|register/startTrial/deposit/redeposit|Traffic Factory 主配置|启用|编辑
tab:Red Track设置
section_fields:渠道配置>Default sub-domain|New Domain settings|rtCid参数|Postback URL|启用
section_table:支持事件>事件|说明|投放URL参数|状态|操作
row:register|注册成功|rtCid={clickid}|启用|编辑
row:depositSubmit|提交充值|rtCid={clickid}|启用|编辑
row:startTrial|首充到账|rtCid={clickid}|启用|编辑
row:deposit|充值到账|rtCid={clickid}|启用|编辑
row:redeposit|复充|rtCid={clickid}|启用|编辑
buttons:提交|测试回传|查看日志|复制URL模板
table:Default sub-domain|rtCid参数|支持事件|状态|操作
row:track.example.com|{clickid}|register/depositSubmit/startTrial/deposit/redeposit|启用|编辑
tab:渠道回传设置
notice:包含 Voluum、Traffic Factory、PropellerAds、Red Track、OKSpin、Bigo、Outbrain、Kadam、Phoenix Ads、MgSkyAds、Devils tracker、GTM、Macan Studio、RouterHub、EGW、Fortune、Keitaro、Revosurge、Resiliencemedia 等文档渠道。
section_table:渠道参数表>渠道|URL参数|宏值示例|支持事件|H5首页示例|H5注册绑定代理示例|状态|操作
row:Voluum|cid|{clickid}|deposit/startTrial/redeposit/register|https://{domain}/m/index.html?cid={clickid}|https://{domain}/m/register?cid={clickid}&affiliateCode=xxx|启用|编辑
row:Traffic Factory|tfTracker|{conversion_tracking}|deposit/redeposit/register/startTrial|https://{domain}/m/index.html?tfTracker={conversion_tracking}|https://{domain}/m/register?tfTracker={conversion_tracking}&affiliateCode=xxx|启用|编辑
row:PropellerAds|visitor_id|{sub_id}|register|https://{domain}/m/index.html?visitor_id={sub_id}|https://{domain}/m/register?visitor_id={sub_id}&affiliateCode=xxx|启用|编辑
row:Red Track|rtCid|{clickid}|depositSubmit/deposit/redeposit/register/startTrial|https://{domain}/m/index.html?rtCid={clickid}|https://{domain}/m/register?rtCid={clickid}&affiliateCode=xxx|启用|编辑
row:OKSpin|pixel_click_id + oks_pixel_id|{click_id}+{pixel_id}|EVENT_COMPLETE_REGISTRATION/EVENT_PURCHASE/EVENT_FIRST_DEPOSIT|https://{domain}/m/index.html?pixel_click_id={click_id}&oks_pixel_id={pixel_id}|https://{domain}/m/register?pixel_click_id={click_id}&oks_pixel_id={pixel_id}&affiliateCode=xxx|启用|编辑
row:Bigo|bigoPixelId|{pixel_id}|ec_register/ec_purchase（首充到账）|https://{domain}/m/index.html?bigoPixelId={pixel_id}|https://{domain}/m/register?bigoPixelId={pixel_id}&affiliateCode=xxx|启用|编辑
row:Outbrain|obclid|{ob_click_id}|deposit/redeposit/register/startTrial|https://{domain}/m/index.html?obclid={ob_click_id}|https://{domain}/m/register?obclid={ob_click_id}&affiliateCode=xxx|启用|编辑
row:Kadam|kadam_id|{click_id}|register|https://{domain}/m/index.html?kadam_id={click_id}|https://{domain}/m/register?kadam_id={click_id}&affiliateCode=xxx|启用|编辑
row:Phoenix Ads|phxCid|{click_id}|register/startTrial|https://{domain}/m/index.html?phxCid={click_id}|https://{domain}/m/register?phxCid={click_id}&affiliateCode=xxx|启用|编辑
row:MgSkyAds|mgsClickId|{click_id}|EVENT_COMPLETE_REGISTRATION/EVENT_PURCHASE/EVENT_FIRST_DEPOSIT|https://{domain}/m/index.html?mgsClickId={click_id}|https://{domain}/m/register?mgsClickId={click_id}&affiliateCode=xxx|启用|编辑
row:Devils tracker|devilsClickId|{click_id}|register/startTrial|https://{domain}/m/index.html?devilsClickId={click_id}|https://{domain}/m/register?devilsClickId={click_id}&affiliateCode=xxx|启用|编辑
row:GTM|gtmId|{gtmId}|全事件|https://{domain}/m/index.html?gtmId={gtmId}|https://{domain}/m/register?gtmId={gtmId}&affiliateCode=xxx|启用|编辑
row:Macan Studio|macanClickId|{click_id}|deposit/redeposit/register/startTrial|https://{domain}/m/index.html?macanClickId={click_id}|https://{domain}/m/register?macanClickId={click_id}&affiliateCode=xxx|启用|编辑
row:RouterHub|rbclickid|{click_id}|register/deposit/startTrial|https://{domain}/m/index.html?rbclickid={click_id}|https://{domain}/m/register?rbclickid={click_id}&affiliateCode=xxx|启用|编辑
row:EGW / 传音|egwId|{pixel_id}|register/deposit/startTrial|https://{domain}/m/index.html?egwId={pixel_id}|https://{domain}/m/register?egwId={pixel_id}&affiliateCode=xxx|启用|编辑
row:Fortune|fortune=1 + clickId|{click_id}|register/viewContent/depositSubmit/deposit/startTrial|https://{domain}/m/index.html?fortune=1&clickId={click_id}|https://{domain}/m/register?fortune=1&clickId={click_id}&affiliateCode=xxx|启用|编辑
row:Keitaro|keitaroClickId|{click_id}|register/depositSubmit/startTrial/deposit|https://{domain}/m/index.html?keitaroClickId={click_id}|https://{domain}/m/register?keitaroClickId={click_id}&affiliateCode=xxx|启用|编辑
row:Revosurge|clickid + revosurge=1|{click_id}|register/login/startTrial|https://{domain}/m/index.html?clickid={click_id}&revosurge=1|https://{domain}/m/register?clickid={click_id}&revosurge=1&affiliateCode=xxx|启用|编辑
row:Resiliencemedia|rmClickId|{clickId}|login/register/startTrial/deposit/withdraw/redeposit|https://{domain}/m/index.html?rmClickId={clickId}|https://{domain}/m/register?rmClickId={clickId}&affiliateCode=xxx|启用|编辑
buttons:新增渠道|批量导入|复制URL模板|导出渠道表|搜索
table:渠道|URL参数|宏值|支持事件|状态|操作
row:Voluum|cid|{clickid}|deposit/startTrial/redeposit/register|启用|编辑
row:Red Track|rtCid|{clickid}|depositSubmit/deposit/redeposit/register/startTrial|启用|编辑
row:Resiliencemedia|rmClickId|{clickId}|login/register/startTrial/deposit/withdraw/redeposit|启用|编辑
tab:Resiliencemedia设置
section_fields:渠道配置>rmClickId参数|Postback URL|签名密钥|Campaign ID|启用
section_table:支持事件>事件|说明|投放URL参数|状态|操作
row:login|登录成功|rmClickId={clickId}|启用|编辑
row:register|注册成功|rmClickId={clickId}|启用|编辑
row:startTrial|首充到账|rmClickId={clickId}|启用|编辑
row:deposit|充值/购买|rmClickId={clickId}|启用|编辑
row:withdraw|提现到账|rmClickId={clickId}|启用|编辑
row:redeposit|复充到账|rmClickId={clickId}|启用|编辑
buttons:提交|测试回传|查看日志|复制URL模板
table:rmClickId参数|支持事件|Postback URL|状态|操作
row:{clickId}|login/register/startTrial/deposit/withdraw/redeposit|https://tracker.example/postback|启用|编辑
tab:Keitaro 设置
section_fields:Postback配置>Postback URL|Postback Key|keitaroClickId|Campaign ID|Offer ID
section_table:事件回传>事件|Keitaro事件|URL参数|说明|状态|操作
row:register|register|keitaroClickId={click_id}|注册成功|启用|编辑
row:depositSubmit|depositSubmit|keitaroClickId={click_id}|发起充值|启用|编辑
row:startTrial|startTrial|keitaroClickId={click_id}|首充到账|启用|编辑
row:deposit|deposit|keitaroClickId={click_id}|充值到账|启用|编辑
buttons:提交|测试Postback|查看日志
table:Keitaro 设置|值|说明|操作
row:Postback URL|https://tracker.example/postback|服务端回传地址|编辑
row:Postback Key|demo-postback-key|签名/密钥|编辑
tab:Snapchat 设置
section_fields:Pixel配置>Pixel ID|Access Token|Conversion API|描述|有效
section_table:事件名称配置>H5事件|Snapchat事件|说明|状态|操作
row:register|SIGN_UP|注册成功|启用|编辑
row:depositSubmit|START_CHECKOUT|提交充值|启用|编辑
row:deposit|PURCHASE|充值到账|启用|编辑
filters:Pixel ID|Access Token|有效
buttons:新增Pixel|事件测试|搜索
table:Pixel ID|Access Token|描述|操作
row:snap_pixel_001|snap_access_token_demo|Snapchat 主 Pixel|编辑
row:snap_pixel_002|snap_access_token_agent|代理 Pixel|编辑
---
280000
summary:投放主站、落地页、测速页链接生成与全局过滤设置。
tab:投放链接设置
filters:代理账号|链接类型|状态
buttons:新增主站投放链接|新增落地页面投放链接|新增测速页投放链接|全局设置|搜索|批量删除
table:全选|ID|链接类型|推广域名|HTTPS状态|斗篷功能|广告审核通过|代理账号|投放工具|应用程序|状态|执行状态|今日/总下载次数|今日/总访问量|最后访问时间|备注|更新|操作
section:新增主站投放链接>域名设置|参数设置|完成|CNAME 指向 f5-sea.53923992.com
section:新增落地页面投放链接>域名设置|参数设置|落地页设置|完成
section:新增测速页投放链接>域名设置|完成
section:全局设置>过滤非首存日注册玩家
---
21160
tab:推广域名管理
filters:推广域名|用户|指向|状态
buttons:新增|批量设置|搜索
table:域类型|推广域名|用户|指向|总访问量|最后点击|状态|操作
section_fields:新增>域类型|推广域名
section_fields:批量更新>操作|旧的指向|新的指向
---
280004
tab:落地页配置
filters:APP名称
buttons:搜索|新增
table:ID|APP名称|语系|APP图|总评分|评分|下载人数|滚动图|应用域名|操作
section_fields:APP设置>APP名称|公司名称|语系|总评分|评分|下载人数|用户年龄|APP图 512x512|滚动图 720x1280
section_fields:页面配置>应用描述标题 0/40|应用描述内容 0/1200|网站图标 64x64|评论区设置
section_fields:评论区添加>评论者头像 50x50|评论者姓名 0/50|评论内容 0/500|评论日期
---
280008
tab:SEO配置
filters:状态|模板名称
buttons:搜索|新增
table:ID|模板名称|标题|描述元数据|关键字|预览图片|应用域名|状态|操作
section_fields:新增SEO模板>模板名称|标题 0/60|描述元数据 0/160|关键字|预览图片 大图/小图|上传
---
280015
tab:推播设置
notice:需要 Firebase 权限。
section:筛选推播对象>全部未注册用户|从未推播过|今日未推播|安装时间3日内|N日内陪伴推播|刷新|预计赠送 N 名用户
section_fields:设置推播内容>选择推播模板|推播标题 30-40 chars|推播内容 100-150 chars
buttons:新增模板|定时发送|发送
table:发送时间|推播标题|推播对象|推播数量|排程状态
tab:推播记录
filters:开始时间|结束时间
table:推播时间|推播数量|成功数
---
280012
tab:像素事件
filters:链接 ID|脸书像素ID|用户名|代理账号|事件|原始记录|抖音像素ID|用户代理|活动时间|注册时间
buttons:Facebook活动设置|导出|搜索
table:脸书像素ID|抖音像素ID|来自FB广告|注册时间|注册网址|代理账号|用户ID|用户名|事件|活动时间|钱|网址|用户代理|原始记录
section_fields:Facebook活动设置>像素 ID|设置上报事件名称
tab:调整S2S事件上报
filters:应用令牌|事件|事件代币|用户名|代理账号|活动时间
table:应用令牌|代理账号|用户ID|用户名|事件|事件代币|活动时间|钱|成功
---
90400
tab:平台配置
fields:平台维护|维护结束时间|平台主域名|彩票试玩|用户使用唯一IP|APP内启动游戏|客服插件
buttons:刷新|新增|更新客服插件
table:客服类型|显示名称|客服链接|定位序号|玩家等级|操作
tab:下载链接
fields:手机网页|H5App下载列图标|FACEBOOK|TELEGRAM|WHATSAPP|INSTAGRAM|Livechat|TELEGRAMBOT
buttons:刷新|提交
tab:用户信息
section_table:功能开关>用户信息|开启用户信息|修改用户信息|开启注册栏位|必填注册栏位
section:用户信息字段>用户名|密码|资金密码|昵称|真实姓名|身份证ID|电话号码|代理推荐码|邮箱|QQ号|Wechat|Line|Whatsapp|Facebook|Zalo|生日日期|银行卡|Telegram|Twitter|Viber
section_table:格式与长度>用户信息|格式|最小长度|最大长度
tab:前台显示样式
fields:手机端 Logo|网页端 Logo|网站图示(Fav icon)|手机端启动画面|桌面APP标题|桌面APP背景色|桌面APP Logo
buttons:刷新|提交
tab:APP打包
section_fields:基本设置>APP桌面名字|APP第一阶段启动页背景图|苹果标题描述|苹果免签APP域名|APP Icon 512x512|启动页 1080x1920|推送通知Icon 48x48
section_fields:进阶设置>APP包名后缀|google_service.json|google_web_client_id|facebook_app_id|facebook_client_token|Appsflyer DEV Key|Adjust APP Token|代理账号/代理推荐码|安卓app固定域名|Snow Ball App ID
section:APP打包>同步到下载链接|一键打包App|打包完成时间|打包进度|下载链接免签/安卓|复制|注意保存7天
section_table:APP打包日志>日期|包名|域名|状态|下载链接免签|下载链接安卓|操作人|详情
tab:APP下载设置
section_fields:下载列编辑>安卓APP|APP版本|苹果免签版APP|顶部APP标志|下载列描述文字颜色|下载列背景色|顶部APP描述文字|底部下载提示编辑|登入提示文案
section:下载设置>新增|预设分组|提示下载开关|APP下载跳转选项|PWA商店下载链接|APK商店下载链接|强制下载持久化|显示继续使用浏览器|首充/提现/绑卡等情境提示或强制下载
---
90510
tab:站点配置
section:安全设置>注册设置|用户名注册|手机号码注册|邮件注册|启用验证码|添加前台注册栏位|登入设置|玩家使用唯一IP登入|可使用多装置登入|账号错误密码上限限制|自动登录|短信OTP登录|邮件OTP登录|三方注册登录设置|找回密码功能|验证机制设置|验证码设置|IP限制设置|开发者工具验证码|取消|提交
section:会员中心>玩家等级|玩家自助返水|玩家头像数量|玩家年龄限制|登录信息|实名认证|显示绑卡真实姓名|转给上级|手动审核|站内信收发/收信箱/发信箱|取消|提交
section:代理中心>代理开户|开户类型|系列值默认最高|代理链接|链接有效期|渠道选单|默认代理链接类型|代理链接默认域名|红包雨功能|手动审核|下级管理|显示下级手机号码|取消|提交
tab:邮件OTP
section_table:邮箱设置>账号类型|寄件人|状态|注册验证|资金密码重置|密码重置|其他场景验证|更新时间|操作
section_fields:新增邮件账号>自有账号/系统账号|SMTP|接口|用户名|SMTP密码|应用程序密码|关闭|提交
section_table:模板管理>模板名称|主旨|操作者|更新时间|操作
section_table:报表发送>日期|寄件账号|创造|已发送|已送达|失败
---
36000
tab:域名管理
section:域名概览>剩余可用域名|已用/总域名号|免费域名|付费域名|验证中心域名|立即刷新
filters:域名|使用状态|国家域名|域名类型
buttons:批量新增|批量编辑|批量删除|新增|导出|搜索
table:全选|域名|域名类型|使用状态|国家域名|NS配置|备注|操作者|更新时间|操作
section_fields:新增域名>域名|Nameserver|域名类型|备注
section_fields:批量新增域名>清单 csv/xlsx|上传|example.com|下载模板
---
31018
tab:游戏厂商设置
buttons:修改厂商排序|上传图片
table:厂商名称|类型名称|显示名称|状态|可用余额|彩票|棋牌|电子|真人|钓鱼|体育|盘口彩票|所有游戏|操作
tab:游戏分类排序
filters:装置|游戏类型
buttons:还原默认值
table:游戏类型|分类ID|分类名称|前台状态显示|操作
section:排序操作>上移|下移|置顶
tab:有效投注及反水设置
section_table:有效投入设置>游戏类型|有效投注计算方式|操作
section:有效投入说明>钓鱼|电子|真人|彩票|棋牌|体育|盘口彩票|查看说明
section_table:反水设置>游戏类型|反水计算方式|操作
section:反水说明>无论输赢皆计算反水|仅输局时计算反水
section_table:随机流水设置>游戏类型|随机流水设置
section:随机流水游戏类型>钓鱼|电子|真人|棋牌|体育|三方彩票|随机消减流水|取消|提交
---
90401
tab:提醒通知设置
fields:新注册用户提醒通知|特定用户上线通知|身份证照任务提醒
buttons:刷新|保存
tab:用户互转设置
fields:上级转运下级|下级枢纽上级|下级转上级人工审核|下级恢复设置|下级回收审核
buttons:取消|提交
tab:平台功能选择
fields:紧急按钮设置|继承上级运营标签|KYC图片上传数量|身份证正面|身份证背面|第三张|第四张|第五张|第六张|提款人姓名|身份证类型|身份证号码
buttons:取消|提交
---
24001
tab:银行卡黑名单
filters:银行卡号码
buttons:搜索|新增
table:银行名称|匹配方式|银行卡号码|用户名|创建时间|备注|操作
tab:提款自动审核
notice:通过后代表一审/二审已通过，状态进入待出款。
fields:提现申请自动审核
buttons:取消|提交
tab:自动出款设置
fields:自动出款开关|出款渠道|金额范围|审核条件|失败重试
buttons:刷新|提交
tab:提款检查流水
filters:用户名|代理账号|状态|时间
buttons:导出|搜索
table:用户ID|用户名|提现金额|所需流水|已完成流水|状态|更新时间|操作
TXT;

    public static function menu(): array
    {
        $groups = [];
        foreach (preg_split('/\R/u', trim(self::MENU)) as $line) {
            $parts = array_pad(explode('|', trim($line), 3), 3, '');
            $children = [];
            foreach (array_filter(explode(';', $parts[2])) as $item) {
                $pair = array_pad(explode(':', $item, 2), 2, '');
                $children[] = ['code' => trim($pair[0]), 'title' => trim($pair[1])];
            }
            $groups[] = ['title' => trim($parts[0]), 'icon' => trim($parts[1]), 'children' => $children];
        }

        return $groups;
    }

    public static function flattenMenu(): array
    {
        $items = [];
        foreach (self::menu() as $group) {
            foreach ($group['children'] as $child) {
                $items[$child['code']] = [
                    'code' => $child['code'],
                    'title' => $child['title'],
                    'category' => $group['title'],
                ];
            }
        }

        return $items;
    }

    public static function page(string $code): ?array
    {
        $entry = self::flattenMenu()[$code] ?? null;
        if (!$entry) {
            return null;
        }

        $page = self::defaultPage($code, $entry['title'], $entry['category']);
        $detail = self::pages()[$code] ?? [];

        return array_merge($page, $detail, [
            'code' => $code,
            'title' => $entry['title'],
            'category' => $entry['category'],
            'tabs' => $detail['tabs'] ?? $page['tabs'],
        ]);
    }

    private static function defaultPage(string $code, string $title, string $category): array
    {
        return [
            'code' => $code,
            'title' => $title,
            'category' => $category,
            'summary' => '该页面已按对标后台建立入口和基础操作区，真实业务逻辑后续逐项接入。',
            'tabs' => [[
                'title' => '列表',
                'filters' => ['关键词', '用户名', '状态', '开始时间', '结束时间'],
                'buttons' => ['新增', '批量设置', '批量删除', '导出', '搜索', '重置'],
                'table' => ['全选', 'ID', '名称', '用户', '金额', '状态', '创建时间', '更新时间', '操作者', '操作'],
                'sections' => [[
                    'title' => '功能入口',
                    'items' => ['新增/编辑', '启用/停用', '详情', '导出', '批量操作', '操作日志'],
                ]],
            ]],
        ];
    }

    private static function pages(): array
    {
        static $pages;
        if ($pages !== null) {
            return $pages;
        }

        $pages = [];
        foreach (preg_split('/\R---\R/u', trim(self::PAGES)) as $block) {
            $lines = preg_split('/\R/u', trim($block));
            $code = trim(array_shift($lines));
            if ($code === '') {
                continue;
            }

            $page = ['summary' => '', 'tabs' => []];
            $current = null;
            $rowTarget = 'tab';
            $lastSectionIndex = null;

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || strpos($line, ':') === false) {
                    continue;
                }

                [$key, $value] = array_pad(explode(':', $line, 2), 2, '');
                $key = trim($key);
                $value = trim($value);

                if ($key === 'summary') {
                    $page['summary'] = $value;
                    continue;
                }

                if ($key === 'tab') {
                    if ($current !== null) {
                        $page['tabs'][] = $current;
                    }
                    $current = [
                        'title' => $value,
                        'guide' => [],
                        'filterButtons' => [],
                        'emptyTable' => false,
                        'notice' => [],
                        'sections' => [],
                        'rows' => [],
                    ];
                    $rowTarget = 'tab';
                    $lastSectionIndex = null;
                    continue;
                }

                if ($current === null) {
                    $current = [
                        'title' => '列表',
                        'guide' => [],
                        'filterButtons' => [],
                        'emptyTable' => false,
                        'notice' => [],
                        'sections' => [],
                        'rows' => [],
                    ];
                }

                if (in_array($key, ['filters', 'buttons', 'table', 'fields'], true)) {
                    $current[$key] = self::splitList($value);
                    if ($key === 'table') {
                        $rowTarget = 'tab';
                        $lastSectionIndex = null;
                    }
                    continue;
                }

                if ($key === 'guide') {
                    $current['guide'][] = $value;
                    continue;
                }

                if ($key === 'filter_buttons') {
                    $current['filterButtons'] = self::splitList($value);
                    continue;
                }

                if ($key === 'empty_table') {
                    $current['emptyTable'] = strtolower($value) === 'true';
                    continue;
                }

                if ($key === 'row') {
                    if ($rowTarget === 'section' && $lastSectionIndex !== null && isset($current['sections'][$lastSectionIndex])) {
                        $current['sections'][$lastSectionIndex]['rows'][] = self::splitList($value, true);
                    } else {
                        $current['rows'][] = self::splitList($value, true);
                    }
                    continue;
                }

                if ($key === 'notice') {
                    $current['notice'][] = $value;
                    continue;
                }

                if (strpos($key, 'section') === 0) {
                    $current['sections'][] = self::parseSection($key, $value);
                    if (strpos($key, 'table') !== false) {
                        $rowTarget = 'section';
                        $lastSectionIndex = count($current['sections']) - 1;
                    } else {
                        $rowTarget = 'tab';
                        $lastSectionIndex = null;
                    }
                }
            }

            if ($current !== null) {
                $page['tabs'][] = $current;
            }
            if (!$page['summary']) {
                unset($page['summary']);
            }
            $pages[$code] = $page;
        }

        return $pages;
    }

    private static function parseSection(string $key, string $value): array
    {
        [$title, $items] = array_pad(explode('>', $value, 2), 2, '');
        $section = ['title' => trim($title)];
        $list = self::splitList($items);

        if (strpos($key, 'table') !== false) {
            $section['table'] = $list;
        } elseif (strpos($key, 'buttons') !== false) {
            $section['buttons'] = $list;
        } elseif ($key === 'section_fields') {
            $section['fields'] = $list;
        } else {
            $section['items'] = $list;
        }

        if (strpos($key, 'section_primary_') === 0) {
            $section['primary'] = true;
        }

        return $section;
    }

    private static function splitList(string $value, bool $preserveEmpty = false): array
    {
        $items = array_map('trim', explode('|', $value));
        if ($preserveEmpty) {
            return array_values($items);
        }

        return array_values(array_filter($items, function ($item) {
            return $item !== '';
        }));
    }
}
