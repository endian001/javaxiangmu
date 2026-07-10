<style>
    .tcg-pixel-tools { font-size: 13px; color: #303b4a; }
    .tcg-pixel-tools .box { border-top-color: #3c8dbc; }
    .tcg-pixel-tools .nav-tabs-custom { margin-bottom: 15px; box-shadow: none; border: 1px solid #e7ebf0; border-radius: 3px; }
    .tcg-pixel-tools .nav-tabs-custom > .nav-tabs > li.active { border-top-color: #3c8dbc; }
    .tcg-pixel-tools .tab-content { padding: 12px; }
    .tcg-tool-panel { border: 1px solid #e5e9f0; border-radius: 3px; background: #fff; margin-bottom: 12px; }
    .tcg-tool-title { padding: 10px 12px; border-bottom: 1px solid #edf0f5; font-weight: 600; color: #263446; background: #fafbfc; }
    .tcg-tool-body { padding: 12px; }
    .tcg-tool-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px 12px; }
    .tcg-tool-field label { display: block; margin-bottom: 5px; color: #475467; font-weight: 600; }
    .tcg-tool-field .help-block { margin: 4px 0 0; color: #8b98a8; font-size: 12px; }
    .tcg-tool-actions { margin-top: 12px; display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .tcg-tool-actions .btn { margin: 0; }
    .tcg-tool-output { margin-top: 12px; }
    .tcg-tool-output textarea, .tcg-tool-log { font-family: Consolas, Monaco, monospace; font-size: 12px; }
    .tcg-tool-log { height: 260px; overflow: auto; white-space: pre-wrap; background: #1f2937; color: #d7e3f4; border-radius: 3px; padding: 10px; }
    .tcg-tool-badge { display: inline-block; padding: 3px 7px; border-radius: 10px; background: #eef6ff; color: #2c6aa0; margin: 2px 3px 2px 0; border: 1px solid #d8eafa; }
    .tcg-tool-badge.is-warn { background: #fff6e5; color: #a15c00; border-color: #f4d296; }
    .tcg-tool-badge.is-ok { background: #eaf8ef; color: #2c7a45; border-color: #c9ead3; }
    .tcg-tool-copy { cursor: pointer; }
    .tcg-tool-table { overflow-x: auto; }
    .tcg-tool-table table { margin-bottom: 0; }
    .tcg-tool-table th { white-space: nowrap; background: #f7f9fc; }
    .tcg-tool-table td { white-space: nowrap; vertical-align: middle !important; }
    .tcg-tool-kv { display: grid; grid-template-columns: 150px 1fr; gap: 8px; padding: 6px 0; border-bottom: 1px dashed #e5e9f0; }
    .tcg-tool-kv:last-child { border-bottom: 0; }
    .tcg-tool-kv strong { color: #4b5563; }
    .tcg-tool-note { color: #7b8794; line-height: 1.7; }
    .tcg-tool-divider { height: 1px; background: #edf0f5; margin: 12px 0; }
    .tcg-param-row { display: grid; grid-template-columns: minmax(130px, 1fr) minmax(130px, 1fr) 42px; gap: 8px; margin-bottom: 8px; }
    .tcg-param-row .btn { padding-left: 0; padding-right: 0; }
    @media (max-width: 767px) {
        .tcg-tool-kv { grid-template-columns: 1fr; }
        .tcg-param-row { grid-template-columns: 1fr; }
    }
</style>

<div
    id="tcgPixelTools"
    class="tcg-pixel-tools"
    data-save-url="{{ url(trim(config('admin.route.prefix'), '/').'/tcg/12535/pixel-config') }}"
    data-log-url="{{ url(trim(config('admin.route.prefix'), '/').'/tcg/12535/pixel-log') }}"
    data-facebook-test-url=""
    data-bridge-test-url=""
    data-encrypt-url=""
    data-csrf-token="{{ csrf_token() }}"
>
    <div class="box box-primary">
        <div class="box-header with-border">
            <h3 class="box-title">像素埋点工具面板</h3>
            <div class="box-tools pull-right">
                <button type="button" class="btn btn-xs btn-primary" id="toolSaveAll">保存全部配置</button>
                <button type="button" class="btn btn-xs btn-default" id="toolLoadSaved">恢复本地配置</button>
                <span class="label label-primary">Blade 自包含</span>
                <span class="label label-default">Bootstrap / jQuery</span>
            </div>
        </div>
        <div class="box-body">
            <div class="alert alert-info" style="margin-bottom:12px;">
                本工具面板用于投放链接、Facebook 事件、App Bridge、User-Agent 和 API 加密请求检查。点击保存会同时写入浏览器本地和后台配置文件，操作日志可提交到后台留存。
            </div>

            <div class="nav-tabs-custom">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#tcg-tool-link" data-toggle="tab">投放链接生成器</a></li>
                    <li><a href="#tcg-tool-facebook" data-toggle="tab">Facebook 自定义事件</a></li>
                    <li><a href="#tcg-tool-bridge" data-toggle="tab">App API Bridge 测试</a></li>
                    <li><a href="#tcg-tool-ua" data-toggle="tab">User-Agent 检测</a></li>
                    <li><a href="#tcg-tool-encrypt" data-toggle="tab">API 加密 Curl</a></li>
                    <li><a href="#tcg-tool-log" data-toggle="tab">操作结果 / 日志</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane active" id="tcg-tool-link">
                        <div class="tcg-tool-panel">
                            <div class="tcg-tool-title">基础链接</div>
                            <div class="tcg-tool-body">
                                <div class="tcg-tool-grid">
                                    <div class="tcg-tool-field">
                                        <label>推广域名</label>
                                        <input type="text" class="form-control" id="ptDomain" value="https://www.example.com" placeholder="https://www.example.com">
                                        <p class="help-block">投放链接必须使用 https；Adjust / App WebView 文档还要求域名以 www 开头。</p>
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>页面类型</label>
                                        <select class="form-control" id="ptPage">
                                            <option value="/m/home">首页 /m/home</option>
                                            <option value="/m/register">注册 /m/register</option>
                                            <option value="/m/index.html">App WebView /m/index.html</option>
                                        </select>
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>代理邀请码 affiliateCode</label>
                                        <input type="text" class="form-control" id="ptAffiliate" placeholder="agent001">
                                        <p class="help-block">注册页需要绑定代理时填写。</p>
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>渠道模板</label>
                                        <select class="form-control" id="ptPreset">
                                            <option value="">自定义参数</option>
                                            <option value="facebook">Facebook: fbPixelId</option>
                                            <option value="tiktok">TikTok: tiktokPixelId</option>
                                            <option value="kwai">Kwai: kwai_pixel_id</option>
                                            <option value="google">Google: gtagId</option>
                                            <option value="gtm">GTM: gtmId</option>
                                            <option value="voluum">Voluum: cid={clickid}</option>
                                            <option value="trafficFactory">Traffic Factory: tfTracker={conversion_tracking}</option>
                                            <option value="propeller">PropellerAds: visitor_id={sub_id}</option>
                                            <option value="redtrack">Red Track: rtCid={clickid}</option>
                                            <option value="okspin">OKSpin: pixel_click_id + oks_pixel_id</option>
                                            <option value="bigo">Bigo: bigoPixelId</option>
                                            <option value="outbrain">Outbrain: obclid</option>
                                            <option value="kadam">Kadam: kadam_id</option>
                                            <option value="phoenix">Phoenix Ads: phxCid</option>
                                            <option value="mgsky">MgSkyAds: mgsClickId</option>
                                            <option value="devils">Devils tracker: devilsClickId</option>
                                            <option value="macan">Macan Studio: macanClickId</option>
                                            <option value="routerhub">RouterHub: rbclickid</option>
                                            <option value="egw">EGW / 传音: egwId</option>
                                            <option value="fortune">Fortune: fortune=1 + clickId</option>
                                            <option value="keitaro">Keitaro: keitaroClickId</option>
                                            <option value="revosurge">Revosurge: clickid + revosurge=1</option>
                                            <option value="resilience">Resiliencemedia: rmClickId</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="tcg-tool-divider"></div>
                                <div class="clearfix">
                                    <strong>URL 参数</strong>
                                    <button type="button" class="btn btn-xs btn-default pull-right" id="ptAddParam">新增参数</button>
                                </div>
                                <div id="ptParams" style="margin-top:8px;"></div>

                                <div class="tcg-tool-actions">
                                    <button type="button" class="btn btn-primary btn-sm" id="ptGenerate">生成投放链接</button>
                                    <button type="button" class="btn btn-default btn-sm" id="ptCopy">复制链接</button>
                                    <button type="button" class="btn btn-default btn-sm" id="ptReset">重置</button>
                                    <button type="button" class="btn btn-info btn-sm" id="ptSave">预留保存</button>
                                </div>

                                <div class="tcg-tool-output">
                                    <label>生成结果</label>
                                    <textarea class="form-control" id="ptResult" rows="3" readonly></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="tcg-tool-panel">
                            <div class="tcg-tool-title">常用参数对照</div>
                            <div class="tcg-tool-body tcg-tool-table">
                                <table class="table table-bordered table-hover">
                                    <thead>
                                        <tr>
                                            <th>渠道</th>
                                            <th>参数</th>
                                            <th>宏值示例</th>
                                            <th>支持事件</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ptChannelTable"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="tcg-tool-facebook">
                        <div class="tcg-tool-panel">
                            <div class="tcg-tool-title">Facebook 自定义事件生成 / 测试界面</div>
                            <div class="tcg-tool-body">
                                <div class="tcg-tool-grid">
                                    <div class="tcg-tool-field">
                                        <label>Pixel ID</label>
                                        <input type="text" class="form-control" id="fbPixelId" placeholder="123456789012345">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>测试事件码 test_event_code</label>
                                        <input type="text" class="form-control" id="fbTestCode" placeholder="TEST12345">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>Event Source URL</label>
                                        <input type="text" class="form-control" id="fbSourceUrl" value="https://www.example.com/m/home">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>事件金额 value</label>
                                        <input type="number" class="form-control" id="fbValue" value="100" min="0" step="0.01">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>币种 currency</label>
                                        <input type="text" class="form-control" id="fbCurrency" value="USD">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>事件选择</label>
                                        <select class="form-control" id="fbEvent">
                                            <option value="PageView">PageView</option>
                                            <option value="firstOpen">firstOpen</option>
                                            <option value="registerSubmit">registerSubmit</option>
                                            <option value="CompleteRegistration">CompleteRegistration</option>
                                            <option value="InitiateCheckout">InitiateCheckout</option>
                                            <option value="firstDepositArrival">firstDepositArrival</option>
                                            <option value="StartTrial">StartTrial</option>
                                            <option value="Purchase">Purchase</option>
                                            <option value="redeposit">redeposit</option>
                                            <option value="gameLaunch">gameLaunch</option>
                                            <option value="withdraw">withdraw</option>
                                            <option value="downloadApp">downloadApp</option>
                                            <option value="login">login</option>
                                            <option value="a2hsFirstOpen">a2hsFirstOpen</option>
                                            <option value="a2hsInstalled">a2hsInstalled</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="tcg-tool-actions">
                                    <button type="button" class="btn btn-primary btn-sm" id="fbGenerate">生成浏览器事件代码</button>
                                    <button type="button" class="btn btn-success btn-sm" id="fbRunPixel">本页模拟触发</button>
                                    <button type="button" class="btn btn-info btn-sm" id="fbBuildCapi">生成 CAPI 测试 Payload</button>
                                    <button type="button" class="btn btn-default btn-sm" id="fbCopy">复制结果</button>
                                </div>

                                <div class="tcg-tool-output">
                                    <label>生成结果</label>
                                    <textarea class="form-control" id="fbResult" rows="9" readonly></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="tcg-tool-panel">
                            <div class="tcg-tool-title">事件映射提醒</div>
                            <div class="tcg-tool-body">
                                <span class="tcg-tool-badge is-ok">register: CompleteRegistration</span>
                                <span class="tcg-tool-badge is-ok">depositSubmit: InitiateCheckout</span>
                                <span class="tcg-tool-badge">firstDepositArrival: 自定义事件</span>
                                <span class="tcg-tool-badge is-ok">startTrial: StartTrial</span>
                                <span class="tcg-tool-badge is-ok">deposit: Purchase</span>
                                <span class="tcg-tool-badge">redeposit: 自定义事件</span>
                                <span class="tcg-tool-badge">withdraw: 自定义事件</span>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="tcg-tool-bridge">
                        <div class="tcg-tool-panel">
                            <div class="tcg-tool-title">App API Bridge 测试按钮</div>
                            <div class="tcg-tool-body">
                                <div class="tcg-tool-grid">
                                    <div class="tcg-tool-field">
                                        <label>事件名 eventTracker</label>
                                        <select class="form-control" id="bridgeEvent">
                                            <option value="firstOpen">firstOpen</option>
                                            <option value="registerSubmit">registerSubmit</option>
                                            <option value="register">register</option>
                                            <option value="depositSubmit">depositSubmit</option>
                                            <option value="firstDeposit">firstDeposit</option>
                                            <option value="firstDepositArrival">firstDepositArrival</option>
                                            <option value="startTrial">startTrial</option>
                                            <option value="deposit">deposit</option>
                                            <option value="redeposit">redeposit</option>
                                            <option value="withdraw">withdraw</option>
                                            <option value="login">login</option>
                                        </select>
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>注册方式 method</label>
                                        <select class="form-control" id="bridgeMethod">
                                            <option value="username">username</option>
                                            <option value="sms">sms</option>
                                        </select>
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>customerId</label>
                                        <input type="text" class="form-control" id="bridgeCustomerId" value="10001">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>customerName</label>
                                        <input type="text" class="form-control" id="bridgeCustomerName" value="demo_user">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>mobileNum</label>
                                        <input type="text" class="form-control" id="bridgeMobile" value="+10000000000">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>revenue / value</label>
                                        <input type="number" class="form-control" id="bridgeRevenue" value="100" step="0.01">
                                        <p class="help-block">提现 withdraw 的 af_revenue 应为负数。</p>
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>打开地址</label>
                                        <input type="text" class="form-control" id="bridgeUrl" value="https://www.example.com/m/home">
                                    </div>
                                </div>

                                <div class="tcg-tool-actions">
                                    <button type="button" class="btn btn-primary btn-sm bridge-action" data-action="eventTracker">eventTracker</button>
                                    <button type="button" class="btn btn-success btn-sm" id="bridgeFirstDepositBundle">首充到账组合测试</button>
                                    <button type="button" class="btn btn-default btn-sm bridge-action" data-action="openAndroid">openAndroid</button>
                                    <button type="button" class="btn btn-default btn-sm bridge-action" data-action="openWebView">openWebView</button>
                                    <button type="button" class="btn btn-default btn-sm bridge-action" data-action="closeWebView">closeWebView</button>
                                    <button type="button" class="btn btn-default btn-sm bridge-action" data-action="facebookLogin">facebookLogin</button>
                                    <button type="button" class="btn btn-default btn-sm bridge-action" data-action="googleLogin">googleLogin</button>
                                    <button type="button" class="btn btn-default btn-sm bridge-action" data-action="getFcmToken">getFcmToken</button>
                                    <button type="button" class="btn btn-default btn-sm bridge-action" data-action="openSafari">openSafari</button>
                                    <button type="button" class="btn btn-warning btn-sm" id="bridgeAlert">Alert 测试</button>
                                    <a class="btn btn-info btn-sm" href="#" id="bridgeLinkTest">超链接外跳测试</a>
                                </div>

                                <div class="tcg-tool-output">
                                    <label>Bridge 调用内容</label>
                                    <textarea class="form-control" id="bridgeResult" rows="8" readonly></textarea>
                                    <p class="help-block">首充到账必须组合触发 firstDepositArrival、startTrial、deposit；startTrial 的 Payload 在当前文档中未单独定义。</p>
                                </div>
                            </div>
                        </div>

                        <div class="tcg-tool-panel">
                            <div class="tcg-tool-title">Bridge 支持状态</div>
                            <div class="tcg-tool-body" id="bridgeSupport"></div>
                        </div>
                    </div>

                    <div class="tab-pane" id="tcg-tool-ua">
                        <div class="tcg-tool-panel">
                            <div class="tcg-tool-title">User-Agent 检测</div>
                            <div class="tcg-tool-body">
                                <div class="tcg-tool-field">
                                    <label>待检测 User-Agent</label>
                                    <textarea class="form-control" id="uaInput" rows="4"></textarea>
                                    <p class="help-block">App WebView 需要包含 AppShellVer 和 UUID，用于识别马甲包环境并隐藏 H5 App 下载 Bar。</p>
                                </div>

                                <div class="tcg-tool-actions">
                                    <button type="button" class="btn btn-primary btn-sm" id="uaDetect">检测</button>
                                    <button type="button" class="btn btn-default btn-sm" id="uaUseCurrent">使用当前浏览器 UA</button>
                                    <button type="button" class="btn btn-default btn-sm" id="uaFillAndroid">填入 Android 示例</button>
                                    <button type="button" class="btn btn-default btn-sm" id="uaFillIos">填入 iOS 示例</button>
                                </div>

                                <div class="tcg-tool-output" id="uaResult"></div>
                            </div>
                        </div>

                        <div class="tcg-tool-panel">
                            <div class="tcg-tool-title">App 启动 URL 检查</div>
                            <div class="tcg-tool-body">
                                <div class="tcg-tool-grid">
                                    <div class="tcg-tool-field">
                                        <label>WebView URL</label>
                                        <input type="text" class="form-control" id="uaLaunchUrl" value="https://www.example.com/m/index.html?ad_app_token=&gps_adid=&idfa=&adid=">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>平台</label>
                                        <select class="form-control" id="uaPlatform">
                                            <option value="android">Android</option>
                                            <option value="ios">iOS</option>
                                        </select>
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>对接类型</label>
                                        <select class="form-control" id="uaIntegration">
                                            <option value="adjust">Adjust S2S</option>
                                            <option value="appsflyer">AppsFlyer S2S</option>
                                            <option value="combined">Adjust + AppsFlyer</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="tcg-tool-actions">
                                    <button type="button" class="btn btn-info btn-sm" id="uaCheckLaunch">检查启动 URL</button>
                                </div>
                                <div class="tcg-tool-output" id="uaLaunchResult"></div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="tcg-tool-encrypt">
                        <div class="tcg-tool-panel">
                            <div class="tcg-tool-title">API 加密 Curl 生成界面</div>
                            <div class="tcg-tool-body">
                                <div class="alert alert-warning">
                                    此处仅用于兼容对标文档的旧版 RSA + DES-ECB 协议。新接口不应采用单 DES/ECB，应优先使用服务端现代加密与 TLS。
                                </div>
                                <div class="tcg-tool-grid">
                                    <div class="tcg-tool-field">
                                        <label>Domain</label>
                                        <input type="text" class="form-control" id="encDomain" value="https://www.example.com">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>Merchant</label>
                                        <input type="text" class="form-control" id="encMerchant" value="demoMerchant">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>用户名</label>
                                        <input type="text" class="form-control" id="encUsername" value="demo_user">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>密码</label>
                                        <input type="password" class="form-control" id="encPassword" value="123456" autocomplete="new-password">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>DES Key</label>
                                        <input type="password" class="form-control" id="encDesKey" value="1234567890ABCDEF" maxlength="16" autocomplete="new-password">
                                        <p class="help-block">文档同款 16 位随机密钥；留空时自动生成，不会保存到后台。</p>
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>uuId</label>
                                        <input type="text" class="form-control" id="encUuid" value="11111">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>ipAddress</label>
                                        <input type="text" class="form-control" id="encIpAddress" value="127.0.0.1">
                                    </div>
                                    <div class="tcg-tool-field">
                                        <label>接口路径</label>
                                        <input type="text" class="form-control" id="encPath" value="/wps/session/login">
                                    </div>
                                </div>

                                <div class="tcg-tool-field" style="margin-top:10px;">
                                    <label>RSA Public Key Modulus（十六进制）</label>
                                    <textarea class="form-control" id="encRsaKey" rows="4" placeholder="粘贴文档接口返回的十六进制 RSA 公钥，不要包含 PEM 头尾"></textarea>
                                </div>

                                <div class="tcg-tool-actions">
                                    <button type="button" class="btn btn-primary btn-sm" id="encGenerate">生成 Curl</button>
                                    <button type="button" class="btn btn-default btn-sm" id="encCopy">复制 Curl</button>
                                    <button type="button" class="btn btn-info btn-sm" id="encPayload">生成 Payload JSON</button>
                                </div>

                                <div class="tcg-tool-output">
                                    <label>Curl / Payload</label>
                                    <textarea class="form-control" id="encResult" rows="11" readonly></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="tcg-tool-panel">
                            <div class="tcg-tool-title">生成说明</div>
                            <div class="tcg-tool-body tcg-tool-note">
                                这里不引入加密库，不在浏览器内伪造真实 RSA/DES 密文；生成的是后端接入时可替换 encryptedData、encryptedKey、merchant 的 Curl 模板，便于运营和研发核对字段。
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane" id="tcg-tool-log">
                        <div class="tcg-tool-panel">
                            <div class="tcg-tool-title">操作结果 / 日志区</div>
                            <div class="tcg-tool-body">
                                <div class="tcg-tool-actions" style="margin-top:0;margin-bottom:10px;">
                                    <button type="button" class="btn btn-default btn-sm" id="logCopy">复制日志</button>
                                    <button type="button" class="btn btn-warning btn-sm" id="logClear">清空日志</button>
                                    <button type="button" class="btn btn-info btn-sm" id="logSnapshot">生成配置快照</button>
                                    <button type="button" class="btn btn-primary btn-sm" id="logSubmit">提交日志</button>
                                </div>
                                <div class="tcg-tool-log" id="toolLog"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="{{ asset('js/tcg/rsa-des.js') }}"></script>
<script>
(function ($) {
    'use strict';

    var root = $('#tcgPixelTools');
    if (!root.length) {
        return;
    }
    var storageKey = 'tcg_pixel_tools_config_v1';
    var serverConfig = @json($pixelConfig['config'] ?? []);
    var serverLogs = @json($pixelLogs ?? []);

    var channelPresets = {
        facebook: [{ key: 'fbPixelId', value: '123456789012345' }],
        tiktok: [{ key: 'tiktokPixelId', value: '1730000000000000' }],
        kwai: [{ key: 'kwai_pixel_id', value: '987654321' }],
        google: [{ key: 'gtagId', value: 'G-PFSS5SP9ND' }],
        gtm: [{ key: 'gtmId', value: 'GTM-DEMO01' }],
        voluum: [{ key: 'cid', value: '{clickid}' }],
        trafficFactory: [{ key: 'tfTracker', value: '{conversion_tracking}' }],
        propeller: [{ key: 'visitor_id', value: '{sub_id}' }],
        redtrack: [{ key: 'rtCid', value: '{clickid}' }],
        okspin: [{ key: 'pixel_click_id', value: '{click_id}' }, { key: 'oks_pixel_id', value: '{pixel_id}' }],
        bigo: [{ key: 'bigoPixelId', value: '{pixel_id}' }],
        outbrain: [{ key: 'obclid', value: '{ob_click_id}' }],
        kadam: [{ key: 'kadam_id', value: '{click_id}' }],
        phoenix: [{ key: 'phxCid', value: '{click_id}' }],
        mgsky: [{ key: 'mgsClickId', value: '{click_id}' }],
        devils: [{ key: 'devilsClickId', value: '{click_id}' }],
        macan: [{ key: 'macanClickId', value: '{click_id}' }],
        routerhub: [{ key: 'rbclickid', value: '{click_id}' }],
        egw: [{ key: 'egwId', value: '{pixel_id}' }],
        fortune: [{ key: 'fortune', value: '1' }, { key: 'clickId', value: '{click_id}' }],
        keitaro: [{ key: 'keitaroClickId', value: '{click_id}' }],
        revosurge: [{ key: 'clickid', value: '{click_id}' }, { key: 'revosurge', value: '1' }],
        resilience: [{ key: 'rmClickId', value: '{clickId}' }]
    };

    var channelRows = [
        ['Facebook', 'fbPixelId', '123456789012345', 'firstOpen / registerSubmit / register / depositSubmit / firstDepositArrival / startTrial / deposit / redeposit / withdraw'],
        ['TikTok', 'tiktokPixelId', '1730000000000000', 'firstOpen / registerSubmit / register / depositSubmit / firstDepositArrival / startTrial / deposit / redeposit / withdraw'],
        ['Kwai', 'kwai_pixel_id', '987654321', 'register / depositSubmit / firstDepositArrival / deposit'],
        ['Google Gtag', 'gtagId', 'G-PFSS5SP9ND', 'firstOpen / registerSubmit / register / depositSubmit / firstDepositArrival / startTrial / deposit / redeposit / withdraw'],
        ['GTM', 'gtmId', 'GTM-DEMO01', '全事件'],
        ['Voluum', 'cid', '{clickid}', 'deposit / startTrial / redeposit / register'],
        ['Traffic Factory', 'tfTracker', '{conversion_tracking}', 'deposit / redeposit / register / startTrial'],
        ['PropellerAds', 'visitor_id', '{sub_id}', 'register'],
        ['Red Track', 'rtCid', '{clickid}', 'depositSubmit / deposit / redeposit / register / startTrial'],
        ['OKSpin', 'pixel_click_id + oks_pixel_id', '{click_id} + {pixel_id}', 'EVENT_COMPLETE_REGISTRATION / EVENT_PURCHASE / EVENT_FIRST_DEPOSIT'],
        ['Bigo', 'bigoPixelId', '{pixel_id}', 'ec_register / ec_purchase（首充到账）'],
        ['Outbrain', 'obclid', '{ob_click_id}', 'deposit / redeposit / register / startTrial'],
        ['Kadam', 'kadam_id', '{click_id}', 'register'],
        ['Phoenix Ads', 'phxCid', '{click_id}', 'register / startTrial'],
        ['MgSkyAds', 'mgsClickId', '{click_id}', 'EVENT_COMPLETE_REGISTRATION / EVENT_PURCHASE / EVENT_FIRST_DEPOSIT'],
        ['Devils tracker', 'devilsClickId', '{click_id}', 'register / startTrial'],
        ['Macan Studio', 'macanClickId', '{click_id}', 'deposit / redeposit / register / startTrial'],
        ['RouterHub', 'rbclickid', '{click_id}', 'register / deposit / startTrial'],
        ['EGW / 传音', 'egwId', '{pixel_id}', 'register / deposit / startTrial'],
        ['Fortune', 'fortune=1 + clickId', '{click_id}', 'register / viewContent / depositSubmit / deposit / startTrial'],
        ['Keitaro', 'keitaroClickId', '{click_id}', 'register / depositSubmit / startTrial / deposit'],
        ['Revosurge', 'clickid + revosurge=1', '{click_id}', 'register / login / startTrial'],
        ['Resiliencemedia', 'rmClickId', '{clickId}', 'login / register / startTrial / deposit / withdraw / redeposit']
    ];

    function log(type, message, detail) {
        var now = new Date();
        var stamp = now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + ' ' + pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
        var line = '[' + stamp + '] [' + type + '] ' + message;
        if (detail !== undefined && detail !== null && detail !== '') {
            if (typeof detail === 'object') {
                line += '\n' + JSON.stringify(detail, null, 2);
            } else {
                line += '\n' + detail;
            }
        }
        var box = $('#toolLog');
        box.text((box.text() ? box.text() + '\n\n' : '') + line);
        box.scrollTop(box[0].scrollHeight);
    }

    function pad(num) {
        return String(num).length < 2 ? '0' + num : String(num);
    }

    function addParam(key, value) {
        var row = $('<div class="tcg-param-row"></div>');
        row.append('<input type="text" class="form-control param-key" placeholder="参数名" value="' + escapeAttr(key || '') + '">');
        row.append('<input type="text" class="form-control param-value" placeholder="参数值 / 宏值" value="' + escapeAttr(value || '') + '">');
        row.append('<button type="button" class="btn btn-default btn-sm param-remove"><i class="fa fa-trash"></i></button>');
        $('#ptParams').append(row);
    }

    function escapeAttr(value) {
        return String(value).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

    function normalizeDomain(domain) {
        domain = $.trim(domain || '');
        if (!domain) {
            return '';
        }
        if (!/^https?:\/\//i.test(domain)) {
            domain = 'https://' + domain;
        }
        return domain.replace(/\/+$/, '');
    }

    function encodeQueryValue(value) {
        return encodeURIComponent(value).replace(/%7B/g, '{').replace(/%7D/g, '}');
    }

    function buildTrackingUrl() {
        var domain = normalizeDomain($('#ptDomain').val());
        var page = $('#ptPage').val() || '/m/home';
        var affiliate = $.trim($('#ptAffiliate').val());
        var params = [];
        var warnings = [];

        if (!domain) {
            warnings.push('推广域名不能为空');
        }
        if (domain && !/^https:\/\//i.test(domain)) {
            warnings.push('投放链接必须使用 https 域名');
            return { url: '', warnings: warnings };
        }
        if (domain && $('#ptPage').val() === '/m/index.html' && !/^https:\/\/www\./i.test(domain)) {
            warnings.push('App WebView URL 必须使用 https 且 www 开头');
            return { url: '', warnings: warnings };
        }

        $('#ptParams .tcg-param-row').each(function () {
            var key = $.trim($(this).find('.param-key').val());
            var value = $.trim($(this).find('.param-value').val());
            if (key && value) {
                params.push(encodeURIComponent(key) + '=' + encodeQueryValue(value));
            }
        });
        if (affiliate) {
            params.push('affiliateCode=' + encodeQueryValue(affiliate));
        }

        var url = domain + page + (params.length ? '?' + params.join('&') : '');
        return { url: url, warnings: warnings };
    }

    function renderChannelTable() {
        var html = '';
        $.each(channelRows, function (_, row) {
            html += '<tr><td>' + row[0] + '</td><td><code>' + row[1] + '</code></td><td><code>' + row[2] + '</code></td><td>' + row[3] + '</td></tr>';
        });
        $('#ptChannelTable').html(html);
    }

    function copyText(text, label) {
        if (!text) {
            log('WARN', label + '为空，无法复制');
            return;
        }
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                log('OK', label + '已复制');
            }).catch(function () {
                fallbackCopy(text, label);
            });
        } else {
            fallbackCopy(text, label);
        }
    }

    function fallbackCopy(text, label) {
        var temp = $('<textarea style="position:fixed;left:-9999px;top:-9999px;"></textarea>').val(text).appendTo('body');
        temp[0].select();
        try {
            document.execCommand('copy');
            log('OK', label + '已复制');
        } catch (e) {
            log('ERROR', label + '复制失败，请手动复制', e.message);
        }
        temp.remove();
    }

    function optionalPost(dataKey, payload, successLabel) {
        var url = root.data(dataKey);
        if (!url) {
            log('SIMULATE', successLabel + '：未配置接口地址，仅生成本地预览', payload);
            return;
        }
        var csrfToken = root.data('csrfToken') || $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val() || '';
        var headers = { 'X-Requested-With': 'XMLHttpRequest' };
        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken;
        }
        $.ajax({
            url: url,
            method: 'POST',
            data: JSON.stringify(payload),
            contentType: 'application/json; charset=UTF-8',
            dataType: 'json',
            headers: headers
        }).done(function (res) {
            log('OK', successLabel + '：接口返回', res);
        }).fail(function (xhr) {
            log('ERROR', successLabel + '：接口请求失败', xhr.responseText || xhr.statusText);
        });
    }

    function facebookTrackMode(eventName) {
        return $.inArray(eventName, ['PageView', 'CompleteRegistration', 'InitiateCheckout', 'StartTrial', 'Purchase']) !== -1
            ? 'track'
            : 'trackCustom';
    }

    function facebookSnippet() {
        var pixelId = $.trim($('#fbPixelId').val());
        var eventName = $('#fbEvent').val();
        var trackMode = facebookTrackMode(eventName);
        var value = Number($('#fbValue').val() || 0);
        var currency = $.trim($('#fbCurrency').val() || 'USD');
        if (!pixelId) {
            log('WARN', 'Facebook Pixel ID 不能为空');
        }
        return [
            "if (typeof window.fbq !== 'function') {",
            "  throw new Error('当前页面尚未初始化 Facebook Pixel');",
            "}",
            "fbq('init', '" + pixelId + "');",
            "fbq('" + trackMode + "', '" + eventName + "', {value: " + value + ", currency: '" + currency + "'});"
        ].join('\n');
    }

    function facebookPayload() {
        var eventId = 'fb_' + Date.now();
        return {
            pixel_id: $.trim($('#fbPixelId').val()),
            test_event_code: $.trim($('#fbTestCode').val()),
            data: [{
                event_name: $('#fbEvent').val(),
                event_time: Math.floor(Date.now() / 1000),
                event_id: eventId,
                action_source: 'website',
                event_source_url: $.trim($('#fbSourceUrl').val()),
                custom_data: {
                    value: Number($('#fbValue').val() || 0),
                    currency: $.trim($('#fbCurrency').val() || 'USD')
                },
                user_data: {
                    client_user_agent: navigator.userAgent
                }
            }]
        };
    }

    function bridgeEventValues(eventName) {
        var revenue = Number($('#bridgeRevenue').val() || 0);
        var customerId = $.trim($('#bridgeCustomerId').val());
        var customerName = $.trim($('#bridgeCustomerName').val());
        var method = $('#bridgeMethod').val();

        if (eventName === 'firstOpen') {
            return {};
        }
        if (eventName === 'registerSubmit') {
            return { method: method };
        }
        if (eventName === 'register') {
            return {
                method: method,
                customerId: customerId,
                customerName: customerName,
                mobileNum: $.trim($('#bridgeMobile').val())
            };
        }
        if ($.inArray(eventName, ['depositSubmit', 'firstDeposit', 'firstDepositArrival', 'deposit']) !== -1) {
            return {
                customerName: customerName,
                customerId: customerId,
                revenue: revenue,
                value: revenue,
                af_revenue: revenue
            };
        }
        if (eventName === 'withdraw') {
            return {
                customerName: customerName,
                customerId: customerId,
                amount: revenue,
                value: revenue,
                af_revenue: -Math.abs(revenue)
            };
        }
        return {};
    }

    function bridgePayload(action) {
        var eventName = $('#bridgeEvent').val();
        return {
            action: action,
            eventName: eventName,
            eventValues: bridgeEventValues(eventName),
            url: $.trim($('#bridgeUrl').val()),
            payloadStatus: $.inArray(eventName, ['startTrial', 'redeposit', 'login']) !== -1
                ? '文档未定义 Payload，测试时发送空对象'
                : '文档已定义 Payload'
        };
    }

    function requireHttpsUrl(value, label) {
        var parsed;
        try {
            parsed = new URL($.trim(value));
        } catch (e) {
            throw new Error(label + '格式不正确');
        }
        if (parsed.protocol !== 'https:') {
            throw new Error(label + '必须使用 https');
        }
        return parsed.toString();
    }

    function callBridge(action) {
        var payload = bridgePayload(action);
        var result = '';
        var called = false;
        var callbackName = action === 'getFcmToken'
            ? 'tcgFcmTokenCallback'
            : (action === 'facebookLogin' ? 'tcgFacebookLoginCallback' : 'tcgGoogleLoginCallback');

        try {
            if ($.inArray(action, ['openAndroid', 'openWebView', 'openSafari']) !== -1) {
                payload.url = requireHttpsUrl(payload.url, 'Bridge 打开地址');
            }
            if (window.Android) {
                if (action === 'eventTracker' && typeof window.Android.eventTracker === 'function') {
                    window.Android.eventTracker(payload.eventName, JSON.stringify(payload.eventValues));
                    called = true;
                    result = 'Android.eventTracker(event, payload)';
                } else if ($.inArray(action, ['openAndroid', 'openWebView']) !== -1 && typeof window.Android[action] === 'function') {
                    window.Android[action](payload.url);
                    called = true;
                    result = 'Android.' + action + '(url)';
                } else if (action === 'closeWebView' && typeof window.Android.closeWebView === 'function') {
                    window.Android.closeWebView();
                    called = true;
                    result = 'Android.closeWebView()';
                } else if ($.inArray(action, ['facebookLogin', 'googleLogin', 'getFcmToken']) !== -1 && typeof window.Android[action] === 'function') {
                    window.Android[action](callbackName);
                    called = true;
                    result = 'Android.' + action + '(callback)';
                }
            }

            if (!called && window.webkit && window.webkit.messageHandlers) {
                if (action === 'eventTracker' && window.webkit.messageHandlers.eventTracker) {
                    window.webkit.messageHandlers.eventTracker.postMessage({
                        eventName: payload.eventName,
                        eventValue: JSON.stringify(payload.eventValues)
                    });
                    called = true;
                    result = 'webkit.messageHandlers.eventTracker.postMessage({eventName,eventValue})';
                } else if ($.inArray(action, ['openSafari', 'openWebView']) !== -1 && window.webkit.messageHandlers.openSafari) {
                    window.webkit.messageHandlers.openSafari.postMessage({
                        url: payload.url,
                        type: action === 'openSafari' ? 1 : 2
                    });
                    called = true;
                    result = 'webkit.messageHandlers.openSafari.postMessage({url,type})';
                } else if ($.inArray(action, ['facebookLogin', 'googleLogin', 'firebaseLogin']) !== -1 && window.webkit.messageHandlers.firebaseLogin) {
                    window.webkit.messageHandlers.firebaseLogin.postMessage({
                        callback: callbackName,
                        channel: action === 'facebookLogin' ? 'facebook' : 'google'
                    });
                    called = true;
                    result = 'webkit.messageHandlers.firebaseLogin.postMessage({callback,channel})';
                }
            }
        } catch (e) {
            result = 'Bridge 调用异常：' + e.message;
            log('ERROR', result, payload);
        }

        if (!called && !result) {
            result = '当前浏览器未发现原生 Bridge，已生成模拟调用。';
        }

        $('#bridgeResult').val(result + '\n\n' + JSON.stringify(payload, null, 2));
        log(called ? 'OK' : 'SIMULATE', action + ' 调用完成', {
            eventName: payload.eventName,
            payloadKeys: Object.keys(payload.eventValues),
            payloadStatus: payload.payloadStatus,
            url: payload.url
        });
        optionalPost('bridgeTestUrl', payload, 'Bridge 测试');
    }

    function renderBridgeSupport() {
        var actions = ['eventTracker', 'openAndroid', 'openWebView', 'closeWebView', 'facebookLogin', 'googleLogin', 'getFcmToken', 'openSafari'];
        var html = '';
        $.each(actions, function (_, action) {
            var ok = false;
            if (window.Android && typeof window.Android[action] === 'function') {
                ok = true;
            }
            if (window.webkit && window.webkit.messageHandlers) {
                if (action === 'eventTracker' && window.webkit.messageHandlers.eventTracker) {
                    ok = true;
                }
                if ($.inArray(action, ['openSafari', 'openWebView']) !== -1 && window.webkit.messageHandlers.openSafari) {
                    ok = true;
                }
                if ($.inArray(action, ['facebookLogin', 'googleLogin', 'firebaseLogin']) !== -1 && window.webkit.messageHandlers.firebaseLogin) {
                    ok = true;
                }
            }
            html += '<span class="tcg-tool-badge ' + (ok ? 'is-ok' : 'is-warn') + '">' + action + ': ' + (ok ? '已发现' : '未发现') + '</span>';
        });
        $('#bridgeSupport').html(html);
    }

    function detectUserAgent(ua) {
        ua = ua || '';
        var isAndroid = /Android/i.test(ua);
        var isIos = /(iPhone|iPad|iPod)/i.test(ua);
        var hasShell = /AppShellVer\/?([\w.\-]+)/i.test(ua);
        var hasUuid = /UUID\/?([A-Za-z0-9\-_]+)/i.test(ua);
        var inApp = /inApp[=\/]?1/i.test(ua);
        var items = [
            ['平台', isAndroid ? 'Android' : (isIos ? 'iOS' : '未知 / 浏览器')],
            ['AppShellVer', hasShell ? '已包含' : '缺失'],
            ['UUID', hasUuid ? '已包含' : '缺失'],
            ['inApp', inApp ? '已包含' : '未包含'],
            ['结论', hasShell && hasUuid ? '可识别 App WebView' : '不完整，可能无法隐藏下载栏或识别马甲包']
        ];
        return items;
    }

    function renderKv(target, items) {
        var html = '';
        $.each(items, function (_, item) {
            var ok = !/缺失|不完整|未知/.test(item[1]);
            html += '<div class="tcg-tool-kv"><strong>' + item[0] + '</strong><span class="' + (ok ? 'text-success' : 'text-warning') + '">' + item[1] + '</span></div>';
        });
        $(target).html(html);
    }

    function checkLaunchUrl() {
        var url = $.trim($('#uaLaunchUrl').val());
        var platform = $('#uaPlatform').val();
        var integration = $('#uaIntegration').val();
        var warnings = [];
        var parser;
        try {
            parser = new URL(url);
        } catch (e) {
            warnings.push('URL 格式不正确');
        }
        if (parser) {
            if (parser.protocol !== 'https:') {
                warnings.push('DOMAIN 必须使用 https');
            }
            if (!/^www\./i.test(parser.hostname)) {
                warnings.push('DOMAIN 必须以 www 开头');
            }
            if ($.inArray(integration, ['adjust', 'combined']) !== -1 && platform === 'android') {
                if (!parser.searchParams.get('ad_app_token')) warnings.push('Android 缺少 ad_app_token');
                if (!parser.searchParams.get('gps_adid') && !parser.searchParams.get('adid')) warnings.push('Android 缺少 gps_adid，取不到 gps_adid 时必须传 adid');
            }
            if ($.inArray(integration, ['adjust', 'combined']) !== -1 && platform === 'ios') {
                if (!parser.searchParams.get('idfa')) warnings.push('iOS 缺少 idfa');
                if (!parser.searchParams.get('adid')) warnings.push('iOS 缺少 adid');
            }
            if ($.inArray(integration, ['appsflyer', 'combined']) !== -1) {
                if (!parser.searchParams.get('af_app_id')) warnings.push('AppsFlyer 缺少 af_app_id');
                if (!parser.searchParams.get('appsflyer_id')) warnings.push('AppsFlyer 缺少 appsflyer_id');
            }
        }
        var items = warnings.length ? warnings.map(function (w) { return ['检查项', w]; }) : [['检查结果', '启动 URL 参数完整']];
        renderKv('#uaLaunchResult', items);
        log(warnings.length ? 'WARN' : 'OK', 'App 启动 URL 检查完成', warnings.length ? warnings : url);
    }

    function encryptionPayload() {
        return {
            merchant: $.trim($('#encMerchant').val()),
            username: $.trim($('#encUsername').val()),
            password: $.trim($('#encPassword').val()),
            desKey: $.trim($('#encDesKey').val()),
            rsaPublicKey: $.trim($('#encRsaKey').val()),
            uuId: $.trim($('#encUuid').val()),
            ipAddress: $.trim($('#encIpAddress').val())
        };
    }

    function buildEncryptedRequest() {
        if (typeof window.RSAAndDESEncrypt !== 'function') {
            throw new Error('RSA/DES 加密脚本未加载，请检查 /js/tcg/rsa-des.js');
        }
        var input = encryptionPayload();
        var rsaKey = input.rsaPublicKey.replace(/\s+/g, '').replace(/^0x/i, '');
        var desKey = input.desKey;
        if (!desKey && typeof window.rndString === 'function') {
            desKey = window.rndString();
            $('#encDesKey').val(desKey);
        }
        if (!/^[A-Za-z0-9]{16}$/.test(desKey)) {
            throw new Error('DES Key 必须是 16 位英文字母或数字');
        }
        if (!/^[0-9a-f]+$/i.test(rsaKey) || rsaKey.length < 128) {
            throw new Error('RSA 公钥必须是至少 128 位的十六进制 modulus');
        }
        var plain = {
            username: input.username,
            password: input.password,
            uuId: input.uuId,
            ipAddress: input.ipAddress
        };
        return {
            merchant: input.merchant,
            body: window.RSAAndDESEncrypt(rsaKey, desKey, plain)
        };
    }

    function buildCurl(encryptedRequest) {
        var domain = normalizeDomain($('#encDomain').val());
        var path = $.trim($('#encPath').val()) || '/wps/session/login';
        var merchant = encryptedRequest.merchant;
        var body = encryptedRequest.body;
        var parsedDomain = requireHttpsUrl(domain, 'API Domain');
        var parsedEndpoint = new URL(parsedDomain);
        if (parsedEndpoint.pathname !== '/' || parsedEndpoint.search || parsedEndpoint.hash) {
            throw new Error('API Domain 只能填写域名，接口路径请单独填写');
        }
        if (!/^\/[A-Za-z0-9._~!$&'()*+,;=:@%\/-]*$/.test(path)) {
            throw new Error('接口路径必须是以 / 开头的安全路径');
        }
        if (/[\r\n]/.test(merchant)) {
            throw new Error('Merchant 不能包含换行');
        }
        var shellQuote = function (value) {
            return "'" + String(value).replace(/'/g, "'\"'\"'") + "'";
        };
        return [
            'curl -X POST ' + shellQuote(parsedDomain.replace(/\/$/, '') + path) + ' \\',
            '  -H ' + shellQuote('Content-Type: application/json') + ' \\',
            '  -H ' + shellQuote('X-Merchant: ' + merchant) + ' \\',
            '  --data ' + shellQuote(JSON.stringify(body))
        ].join('\n');
    }

    function snapshot() {
        var ua = $('#uaInput').val();
        return {
            trackingUrl: $('#ptResult').val(),
            facebook: {
                eventName: $('#fbEvent').val(),
                pixelConfigured: !!$.trim($('#fbPixelId').val()),
                testCodeConfigured: !!$.trim($('#fbTestCode').val())
            },
            bridge: {
                eventName: $('#bridgeEvent').val(),
                payloadKeys: Object.keys(bridgeEventValues($('#bridgeEvent').val()))
            },
            userAgent: {
                appShellVerDetected: /AppShellVer/i.test(ua),
                uuidDetected: /UUID/i.test(ua)
            },
            launchUrl: {
                platform: $('#uaPlatform').val(),
                integration: $('#uaIntegration').val()
            },
            encryption: {
                domain: $.trim($('#encDomain').val()),
                path: $.trim($('#encPath').val()),
                merchant: $.trim($('#encMerchant').val()),
                rsaKeyConfigured: !!$.trim($('#encRsaKey').val()),
                desKeyLength: $.trim($('#encDesKey').val()).length
            }
        };
    }

    function collectConfig() {
        var fields = {};
        root.find('input[id], select[id], textarea[id]').each(function () {
            var id = this.id;
            if (!id || /Result$/.test(id) || id === 'toolLog' || $.inArray(id, [
                'encPassword',
                'encDesKey',
                'encRsaKey',
                'encUsername',
                'encUuid',
                'encIpAddress',
                'bridgeCustomerId',
                'bridgeCustomerName',
                'bridgeMobile',
                'fbTestCode',
                'uaInput',
                'uaLaunchUrl'
            ]) !== -1) {
                return;
            }
            fields[id] = $(this).val();
        });
        var params = [];
        $('#ptParams .tcg-param-row').each(function () {
            params.push({
                key: $(this).find('.param-key').val(),
                value: $(this).find('.param-value').val()
            });
        });
        return {
            version: 1,
            savedAt: new Date().toISOString(),
            fields: fields,
            params: params,
            snapshot: snapshot()
        };
    }

    function applyConfig(config) {
        if (!config || !config.fields) {
            throw new Error('配置内容为空或格式不正确');
        }
        $.each(config.fields, function (id, value) {
            var field = $('#' + id);
            if (field.length) {
                field.val(value);
            }
        });
        if ($.isArray(config.params)) {
            $('#ptParams').empty();
            $.each(config.params, function (_, item) {
                addParam(item.key, item.value);
            });
        }
        $('#ptGenerate').trigger('click');
        $('#uaDetect').trigger('click');
        renderBridgeSupport();
    }

    function saveAllConfig() {
        var config = collectConfig();
        try {
            window.localStorage.setItem(storageKey, JSON.stringify(config));
            log('OK', '配置已保存到当前浏览器', { savedAt: config.savedAt });
        } catch (e) {
            log('ERROR', '浏览器本地配置保存失败', e.message);
        }
        optionalPost('saveUrl', { config: config }, '全部配置保存');
    }

    function loadSavedConfig() {
        try {
            var raw = window.localStorage.getItem(storageKey);
            var config = raw ? JSON.parse(raw) : serverConfig;
            if (!config || !config.fields) {
                log('WARN', '浏览器和后台都没有已保存配置');
                return;
            }
            applyConfig(config);
            log('OK', raw ? '已恢复浏览器本地配置' : '已恢复后台配置', { savedAt: config.savedAt || '未知' });
        } catch (e) {
            log('ERROR', '恢复本地配置失败', e.message);
        }
    }

    $('#ptAddParam').on('click', function () {
        addParam('', '');
    });

    $('#ptParams').on('click', '.param-remove', function () {
        $(this).closest('.tcg-param-row').remove();
    });

    $('#ptPreset').on('change', function () {
        var preset = channelPresets[$(this).val()] || [];
        $('#ptParams').empty();
        $.each(preset, function (_, item) {
            addParam(item.key, item.value);
        });
        if (!preset.length) {
            addParam('', '');
        }
        log('INFO', '已套用渠道模板：' + ($('#ptPreset option:selected').text() || '自定义参数'));
    });

    $('#ptGenerate').on('click', function () {
        var built = buildTrackingUrl();
        $('#ptResult').val(built.url);
        log(built.warnings.length ? 'WARN' : 'OK', '投放链接已生成', { url: built.url, warnings: built.warnings });
    });

    $('#ptCopy').on('click', function () {
        copyText($('#ptResult').val(), '投放链接');
    });

    $('#ptReset').on('click', function () {
        $('#ptDomain').val('https://www.example.com');
        $('#ptPage').val('/m/home');
        $('#ptAffiliate').val('');
        $('#ptPreset').val('');
        $('#ptParams').empty();
        addParam('fbPixelId', '123456789012345');
        $('#ptResult').val('');
        log('INFO', '投放链接生成器已重置');
    });

    $('#ptSave').on('click', function () {
        saveAllConfig();
    });

    $('#toolSaveAll').on('click', saveAllConfig);
    $('#toolLoadSaved').on('click', loadSavedConfig);

    $('#fbGenerate').on('click', function () {
        var code = facebookSnippet();
        $('#fbResult').val(code);
        log('OK', 'Facebook 浏览器事件代码已生成');
    });

    $('#fbRunPixel').on('click', function () {
        var eventName = $('#fbEvent').val();
        var payload = facebookPayload();
        if (typeof window.fbq === 'function') {
            window.fbq(facebookTrackMode(eventName), eventName, payload.data[0].custom_data);
            log('OK', 'Facebook 事件已通过 fbq 触发', {
                eventName: eventName,
                pixelConfigured: !!payload.pixel_id
            });
        } else {
            log('SIMULATE', '当前页面未加载 fbq，已生成模拟 Facebook 事件', {
                eventName: eventName,
                pixelConfigured: !!payload.pixel_id
            });
        }
        $('#fbResult').val(JSON.stringify(payload, null, 2));
        optionalPost('facebookTestUrl', payload, 'Facebook 测试事件');
    });

    $('#fbBuildCapi').on('click', function () {
        var payload = facebookPayload();
        $('#fbResult').val(JSON.stringify(payload, null, 2));
        log('OK', 'Facebook CAPI 测试 Payload 已生成', {
            eventName: payload.data[0].event_name,
            pixelConfigured: !!payload.pixel_id,
            testCodeConfigured: !!payload.test_event_code
        });
    });

    $('#fbCopy').on('click', function () {
        copyText($('#fbResult').val(), 'Facebook 结果');
    });

    $('.bridge-action').on('click', function () {
        callBridge($(this).data('action'));
    });

    $('#bridgeFirstDepositBundle').on('click', function () {
        var originalEvent = $('#bridgeEvent').val();
        $.each(['firstDepositArrival', 'startTrial', 'deposit'], function (_, eventName) {
            $('#bridgeEvent').val(eventName);
            callBridge('eventTracker');
        });
        $('#bridgeEvent').val(originalEvent);
        log('OK', '首充到账组合测试已按顺序执行', ['firstDepositArrival', 'startTrial', 'deposit']);
    });

    $('#bridgeAlert').on('click', function () {
        alert('App API Bridge Alert 测试');
        log('OK', 'Alert 测试已触发');
    });

    $('#bridgeLinkTest').on('click', function (event) {
        event.preventDefault();
        var url;
        try {
            url = requireHttpsUrl($('#bridgeUrl').val(), '超链接外跳地址');
        } catch (e) {
            log('WARN', e.message);
            return;
        }
        var opened = window.open(url, '_blank');
        if (opened) {
            opened.opener = null;
        }
        log(opened ? 'OK' : 'WARN', '超链接外跳测试：' + url);
    });

    $('#uaUseCurrent').on('click', function () {
        $('#uaInput').val(navigator.userAgent);
        $('#uaDetect').trigger('click');
    });

    $('#uaFillAndroid').on('click', function () {
        $('#uaInput').val('Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 Chrome/126 Mobile Safari/537.36 AppShellVer/1.0.0 UUID/android-demo-uuid inApp=1');
        $('#uaDetect').trigger('click');
    });

    $('#uaFillIos').on('click', function () {
        $('#uaInput').val('Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 Version/17.5 Mobile/15E148 Safari/604.1 AppShellVer/1.0.0 UUID/ios-demo-uuid inApp=1');
        $('#uaDetect').trigger('click');
    });

    $('#uaDetect').on('click', function () {
        var ua = $('#uaInput').val();
        var items = detectUserAgent(ua);
        renderKv('#uaResult', items);
        log(/不完整/.test(items[4][1]) ? 'WARN' : 'OK', 'User-Agent 检测完成', {
            platform: items[0][1],
            appShellVer: items[1][1],
            uuid: items[2][1],
            inApp: items[3][1],
            conclusion: items[4][1]
        });
    });

    $('#uaCheckLaunch').on('click', checkLaunchUrl);

    $('#encGenerate').on('click', function () {
        try {
            var encryptedRequest = buildEncryptedRequest();
            $('#encResult').val(buildCurl(encryptedRequest));
            log('OK', 'API 加密 Curl 已使用 RSA + DES 生成', {
                encryptionLength: encryptedRequest.body.encryption.length,
                payloadLength: encryptedRequest.body.payload.length
            });
        } catch (e) {
            $('#encResult').val('');
            log('ERROR', 'API 加密 Curl 生成失败', e.message);
        }
    });

    $('#encPayload').on('click', function () {
        try {
            var encryptedRequest = buildEncryptedRequest();
            $('#encResult').val(JSON.stringify(encryptedRequest.body, null, 2));
            log('OK', 'API 加密 Payload JSON 已生成', {
                encryptionLength: encryptedRequest.body.encryption.length,
                payloadLength: encryptedRequest.body.payload.length
            });
        } catch (e) {
            $('#encResult').val('');
            log('ERROR', 'API 加密 Payload 生成失败', e.message);
        }
    });

    $('#encCopy').on('click', function () {
        copyText($('#encResult').val(), 'API 加密 Curl');
    });

    $('#logCopy').on('click', function () {
        copyText($('#toolLog').text(), '操作日志');
    });

    $('#logClear').on('click', function () {
        $('#toolLog').text('');
        log('INFO', '日志已清空');
    });

    $('#logSnapshot').on('click', function () {
        log('INFO', '当前配置快照', snapshot());
    });

    $('#logSubmit').on('click', function () {
        optionalPost('logUrl', {
            action: 'tools.log.submit',
            context: {
                submittedAt: new Date().toISOString(),
                log: $('#toolLog').text()
            }
        }, '操作日志提交');
    });

    renderChannelTable();
    renderBridgeSupport();
    window.tcgFacebookLoginCallback = window.tcgFacebookLoginCallback || function (idToken) {
        log('OK', 'Facebook 登录回调已收到', { idTokenLength: String(idToken || '').length });
    };
    window.tcgGoogleLoginCallback = window.tcgGoogleLoginCallback || function (idToken) {
        log('OK', 'Google 登录回调已收到', { idTokenLength: String(idToken || '').length });
    };
    window.tcgFcmTokenCallback = window.tcgFcmTokenCallback || function (token) {
        log('OK', 'FCM Token 回调已收到', { tokenLength: String(token || '').length });
    };
    if (serverConfig && serverConfig.fields) {
        applyConfig(serverConfig);
        log('OK', '已载入后台保存配置', { savedAt: serverConfig.savedAt || '未知' });
    } else {
        addParam('fbPixelId', '123456789012345');
    }
    $.each(serverLogs, function (_, entry) {
        log('SERVER', entry.action || '历史操作', {
            time: entry.time || '',
            admin: entry.admin || '',
            context: entry.context || {}
        });
    });
    $('#uaInput').val(navigator.userAgent);
    $('#uaDetect').trigger('click');
    $('#ptGenerate').trigger('click');
    log('INFO', '像素埋点工具面板已初始化');
})(jQuery);
</script>
