const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..', '..');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('mobile register URLs are routed to the auth renderer instead of the home page', () => {
  const webRoutes = read('routes/web.php');
  const homeScript = read('public/assets/home-operations.js');
  const desktopIndex = read('public/index.html');

  assert.match(webRoutes, /Route::any\('\/m\/login'.*auth\.html/);
  assert.match(webRoutes, /Route::any\('\/m\/register'.*auth\.html/);
  assert.match(webRoutes, /Route::get\('\/new-h5\/login'.*auth\.html/);
  assert.match(webRoutes, /Route::get\('\/new-h5\/register'.*auth\.html/);
  assert.match(homeScript, /path === '\/m\/login'/);
  assert.match(homeScript, /path === '\/m\/register'/);
  assert.match(homeScript, /path === '\/new-h5\/login'/);
  assert.match(homeScript, /path === '\/new-h5\/register'/);
  assert.match(homeScript, /registering = .*\/m\/register/);
  assert.match(desktopIndex, /p==='\/m\/login'\|\|p==='\/m\/register'/);
  assert.match(desktopIndex, /location\.replace\('\/new-h5\/'\+location\.search\+location\.hash\)/);
});

test('public pages load the shared pixel runtime before member actions fire', () => {
  for (const file of ['public/index.html', 'public/new-h5/index.html', 'public/auth.html']) {
    const source = read(file);
    assert.match(source, /\/assets\/pixel-tracking\.js\?v=20260714pixel4/, file);
  }

  const auth = read('public/auth.html');
  assert.ok(auth.indexOf('/assets/pixel-tracking.js?v=20260714pixel4') < auth.indexOf('function setMode'), 'auth pixel runtime must load before auth mode URL rewriting');
  assert.match(auth, /new URLSearchParams\(location\.search \|\| ''\)/);
  assert.match(auth, /nextParams\.set\('redirect', redirect\)/);
  assert.match(auth, /window\.__th2wQueuedPixelEvents/);
});

test('pixel runtime supports public SDK loading and backend event recording', () => {
  const source = read('public/assets/pixel-tracking.js');

  for (const marker of [
    'fbPixelId',
    'tiktokPixelId',
    'kwai_pixel_id',
    'kwaiPixelBaseCode',
    'gtagId',
    'gtmId',
    'bigoPixelId',
    'pixel_click_id',
    'oks_pixel_id',
    'af_app_id',
    'ad_app_token',
    'connect.facebook.net/en_US/fbevents.js',
    'analytics.tiktok.com/i18n/pixel/events.js',
    'googletagmanager.com/gtag/js',
    'googletagmanager.com/gtm.js',
    '/api/pixel/event',
    'legacyStorageKey',
    'flushQueuedEvents',
    'firstOpen',
    'registerSubmit',
    'register',
    'depositSubmit',
    'withdraw'
  ]) {
    assert.ok(source.includes(marker), marker);
  }
});

test('pixel runtime captures every documented attribution and postback parameter', () => {
  const source = read('public/assets/pixel-tracking.js');

  for (const key of [
    'affiliateCode',
    'agentCode',
    'invite_code',
    'pid',
    'linkId',
    'fbPixelId',
    'tiktokPixelId',
    'kwai_pixel_id',
    'kwaiPixelBaseCode',
    'gtagId',
    'gtmId',
    'bigoPixelId',
    'pixel_click_id',
    'oks_pixel_id',
    'fbclid',
    'ttclid',
    'gclid',
    'cid',
    'tfTracker',
    'visitor_id',
    'rtCid',
    'obclid',
    'kadam_id',
    'phxCid',
    'mgsClickId',
    'devilsClickId',
    'macanClickId',
    'rbclickid',
    'egwId',
    'fortune',
    'clickId',
    'keitaroClickId',
    'clickid',
    'revosurge',
    'rmClickId',
    'af_app_id',
    'appsflyer_id',
    'advertising_id',
    'oaid',
    'idfa',
    'idfv',
    'ad_app_token',
    'gps_adid',
    'adid'
  ]) {
    assert.ok(source.includes(`'${key}'`), key);
  }

  for (const marker of [
    'th2w:pixel:browser-id',
    'th2w:pixel:session-id',
    'event_id',
    'referrer',
    'screen'
  ]) {
    assert.ok(source.includes(marker), marker);
  }
});

test('fresh attribution URLs replace stale stored click parameters', () => {
  const source = read('public/assets/pixel-tracking.js');

  assert.match(source, /var fresh = params && Object\.keys\(params\)\.length > 0;/);
  assert.match(source, /var merged = fresh \? Object\.assign\(\{\}, params\) : existing;/);
  assert.doesNotMatch(source, /\n\s*'kw'\s*(,|\])/);
});

test('frontend member actions emit pixel lifecycle events', () => {
  const source = read('public/assets/home-operations.js');

  assert.match(source, /trackPixelEvent\('registerSubmit'/);
  assert.match(source, /trackPixelEvent\('register'/);
  assert.match(source, /trackPixelEvent\('login'/);
  assert.match(source, /trackPixelEvent\('depositSubmit'/);
  assert.match(source, /trackPixelEvent\('withdraw'/);
});

test('registration binds invite codes captured from ad links', () => {
  const frontend = read('public/assets/home-operations.js');
  const authController = read('app/Http/Controllers/Api/AuthController.php');
  const appController = read('app/Http/Controllers/Api/AppController.php');

  assert.match(frontend, /function trackingInviteCode\(\)/);
  assert.match(frontend, /window\.TH2WPixel\.params\(\)/);
  assert.match(frontend, /th2w:pixel:tracking/);
  assert.match(frontend, /affiliateCode/);
  assert.match(frontend, /agentCode/);
  assert.match(frontend, /invite_code/);
  assert.match(frontend, /pid: body\.pid \|\| body\.invite_code \|\| trackingInviteCode\(\)/);

  for (const controller of [authController, appController]) {
    assert.match(controller, /private function resolveInvitePid/);
    assert.match(controller, /User::where\('username', \$inviteCode\)/);
    assert.match(controller, /'pid' => \$this->resolveInvitePid\(\$data\['pid'\] \?\? 0\)/);
  }
});

test('backend records pixel events and ties recharge arrival back to user tracking data', () => {
  const apiRoutes = read('routes/api.php');
  const controller = read('app/Http/Controllers/Api/PixelController.php');
  const service = read('app/Services/PromotionPixelEventService.php');
  const payController = read('app/Http/Controllers/Api/PayController.php');
  const legacyPayController = read('app/Http/Controllers/Member/PayController.php');
  const userActivityLogger = read('app/Http/Middleware/UserActivityLogger.php');
  const rechargePass = read('app/Admin/Actions/Grid/Recharge/Pass.php');

  assert.match(apiRoutes, /Route::match\(\['get', 'post'\], '\/pixel\/event'/);
  assert.match(controller, /function record\(Request \$request\)/);
  assert.match(service, /promotion_event_records/);
  assert.match(service, /recordFromRequest/);
  assert.match(service, /recordDepositArrival/);
  assert.match(payController, /PromotionPixelEventService/);
  assert.match(payController, /recordDepositArrival\(\$recharge/);
  assert.match(legacyPayController, /PromotionPixelEventService/);
  assert.match(legacyPayController, /recordDepositArrival\(\$recharge/);
  assert.match(legacyPayController, /legacy_fourway_notify/);
  assert.match(userActivityLogger, /limitText\(\$request->header\('referer'\), 255\)/);
  assert.match(userActivityLogger, /catch \(\\Throwable \$e\)/);
  assert.match(userActivityLogger, /qukuanmima/);
  assert.match(rechargePass, /PromotionPixelEventService/);
  assert.match(rechargePass, /recordDepositArrival\(\$model/);
});
