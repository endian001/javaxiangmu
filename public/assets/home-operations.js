(function () {
  'use strict';

  var carousel = {
    root: null,
    track: null,
    dots: [],
    index: 0,
    count: 0,
    timer: null,
    touchStartX: 0
  };

  var customerServicePayloadCache = null;
  var customerServiceFetchPromise = null;

  var supportedLocales = [
    { code: 'zh-CN', label: '中文', native: '中文', flag: '🇨🇳' },
    { code: 'th-TH', label: 'ไทย', native: 'ไทย', flag: '🇹🇭' },
    { code: 'en-US', label: 'English', native: 'English', flag: '🇺🇸' },
    { code: 'vi-VN', label: 'Tiếng Việt', native: 'Tiếng Việt', flag: '🇻🇳' },
    { code: 'id-ID', label: 'Indonesia', native: 'Indonesia', flag: '🇮🇩' },
    { code: 'ms-MY', label: 'Melayu', native: 'Melayu', flag: '🇲🇾' },
    { code: 'km-KH', label: 'ខ្មែរ', native: 'ខ្មែរ', flag: '🇰🇭' },
    { code: 'my-MM', label: 'မြန်မာ', native: 'မြန်မာ', flag: '🇲🇲' }
  ];

  function bootHomeOperations() {
    if (normalizeLegacyAuthHash()) {
      return;
    }

    if (window.__homeOperationsBooted) {
      return;
    }
    window.__homeOperationsBooted = true;

    if (isAuthPath()) {
      renderAuthRoute();
      return;
    }

    if (isMemberToolPath()) {
      renderMemberToolPage();
      initFloatingSupport();
      return;
    }

    if (isGameLaunchPath()) {
      initHeaderTools();
      initGameLaunchBridge();
      initFloatingSupport();
      return;
    }

    if (isGameCatalogPath()) {
      initHeaderTools();
      renderWxGameCatalogPage();
      scheduleWxCatalogGuard();
      initFloatingSupport();
      return;
    }

    if (isHomePath()) {
      initHeaderTools();
      initCarousel();
      initHomeParityEnhancements();
      initWxGameHome();
      scheduleWxHomeGuard();
      initFloatingSupport();
      return;
    }

    if (isPromotionPath()) {
      initFloatingSupport();
      localizeVisibleCopy(document.body);
      return;
    }

    if (isNoticePath()) {
      renderNoticePage();
      initFloatingSupport();
      return;
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootHomeOperations);
  } else {
    bootHomeOperations();
  }

  window.addEventListener('hashchange', function () {
    if (normalizeLegacyAuthHash()) {
      return;
    }
    if (isAuthPath()) {
      renderAuthRoute();
    }
  });

  window.TH2WWxGameCatalog = function () {
    if (isGameCatalogPath() && !isGameLaunchPath()) {
      renderWxGameCatalogPage();
      scheduleWxCatalogGuard();
    }
  };

  function isHomePath() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    return path === '/' || path === '/index.html' || path === '/new-h5' || path === '/new-h5/index.html';
  }

  function isAuthPath() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    return path === '/login' ||
      path === '/register';
  }

  function normalizeLegacyAuthHash() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    var hash = location.hash || '';
    var hashPath = hash.replace(/^#/, '').split('?')[0].replace(/\/+$/, '') || '';
    if ((path === '/' || path === '/index.html') && (hashPath === '/login' || hashPath === '/register')) {
      var hashQuery = hash.indexOf('?') >= 0 ? hash.slice(hash.indexOf('?')) : '';
      location.replace(hashPath + hashQuery);
      return true;
    }
    return false;
  }

  function isPromotionPath() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    return path === '/activity' || path === '/activities' || path === '/promotions';
  }

  function isNoticePath() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    return path === '/notice' || path === '/notices' || path === '/announcement' || path === '/announcements';
  }

  function isGameLaunchPath() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    if (path !== '/gaming') {
      return false;
    }
    var params = new URLSearchParams(location.search || '');
    return !!(params.get('platform_name') || params.get('plat_name') || params.get('game_code') || params.get('game_type'));
  }

  function isGameCatalogPath() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    return path === '/gaming' ||
      path === '/sport' ||
      path === '/realbet' ||
      path === '/joker' ||
      path === '/lottery';
  }

  function isMemberToolPath() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    return path === '/member/center' ||
      path === '/member/wallet' ||
      path === '/member/recharge' ||
      path === '/member/withdraw' ||
      path === '/wallet' ||
      path === '/recharge' ||
      path === '/withdraw';
  }

  function postJson(path, body, extraHeaders) {
    var headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    };
    Object.keys(extraHeaders || {}).forEach(function (key) {
      headers[key] = extraHeaders[key];
    });

    return fetch(path, {
      method: 'POST',
      headers: headers,
      body: JSON.stringify(body || {})
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('Request failed');
      }
      return response.json();
    });
  }

  function getJson(path, extraHeaders) {
    var headers = {
      'Accept': 'application/json'
    };
    Object.keys(extraHeaders || {}).forEach(function (key) {
      headers[key] = extraHeaders[key];
    });

    return fetch(path, {
      method: 'GET',
      headers: headers
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('Request failed');
      }
      return response.json();
    });
  }

  function responseData(payload) {
    return payload && Object.prototype.hasOwnProperty.call(payload, 'data')
      ? payload.data
      : payload;
  }

  function authHeaders(token) {
    return token ? { Authorization: 'Bearer ' + token } : {};
  }

  function apiPost(path, body, token) {
    return postJson(path, body || {}, authHeaders(token || currentAuthToken()));
  }

  function payloadCode(payload) {
    return Number(payload && Object.prototype.hasOwnProperty.call(payload, 'code') ? payload.code : 0);
  }

  function initHeaderTools() {
    initLanguageSwitcher();

    Array.prototype.forEach.call(document.querySelectorAll('[data-home-search-form]'), function (form) {
      form.addEventListener('submit', function (event) {
        event.preventDefault();
        var input = form.querySelector('[data-home-search-input]');
        var keyword = input ? input.value.trim() : '';
        if (!keyword && !form.classList.contains('top-search--open')) {
          form.classList.add('top-search--open');
          if (input) {
            input.focus();
          }
          return;
        }
        location.href = keyword ? '/gaming?keyword=' + encodeURIComponent(keyword) : '/gaming';
      });
    });

    var token = currentAuthToken();
    Array.prototype.forEach.call(document.querySelectorAll('[data-guest-actions]'), function (node) {
      node.hidden = !!token;
    });
    Array.prototype.forEach.call(document.querySelectorAll('[data-member-actions]'), function (node) {
      node.hidden = !token;
    });
    if (!token) {
      return;
    }

    postJson('/api/user', {}, {
      Authorization: 'Bearer ' + token
    }).then(function (payload) {
      var user = responseData(payload) || {};
      renderMemberName(user.username || user.name || user.realname || '会员');
    }).catch(function () {
      renderMemberName('会员');
    });
  }

  function initLanguageSwitcher() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-language-switcher]'), function (switcher) {
      if (switcher.getAttribute('data-language-bound') === '1') {
        return;
      }
      switcher.setAttribute('data-language-bound', '1');
      applyLocaleToSwitcher(switcher, currentLocale());
      switcher.addEventListener('click', function (event) {
        var option = event.target.closest('[data-locale-code]');
        if (option) {
          event.preventDefault();
          setLocale(option.getAttribute('data-locale-code'));
          closeLanguageMenus();
          return;
        }
        switcher.classList.toggle('language-switcher--open');
        var button = switcher.querySelector('[data-language-toggle]');
        if (button) {
          button.setAttribute('aria-expanded', switcher.classList.contains('language-switcher--open') ? 'true' : 'false');
        }
      });
    });

    document.addEventListener('click', function (event) {
      if (!event.target.closest('[data-language-switcher]')) {
        closeLanguageMenus();
      }
    });
  }

  function currentLocale() {
    return localStorage.getItem('th2w:locale') || 'zh-CN';
  }

  function setLocale(code) {
    var locale = supportedLocales.filter(function (item) {
      return item.code === code;
    })[0] || supportedLocales[0];
    localStorage.setItem('th2w:locale', locale.code);
    document.documentElement.setAttribute('lang', locale.code);
    Array.prototype.forEach.call(document.querySelectorAll('[data-language-switcher]'), function (switcher) {
      applyLocaleToSwitcher(switcher, locale.code);
    });
  }

  function applyLocaleToSwitcher(switcher, code) {
    var locale = supportedLocales.filter(function (item) {
      return item.code === code;
    })[0] || supportedLocales[0];
    document.documentElement.setAttribute('lang', locale.code);
    var label = switcher.querySelector('[data-language-label]');
    var flag = switcher.querySelector('[data-language-flag]');
    if (label) {
      label.textContent = locale.native;
    }
    if (flag) {
      flag.textContent = locale.flag;
      flag.setAttribute('aria-hidden', 'true');
    }
    switcher.querySelectorAll('[data-locale-code]').forEach(function (option) {
      option.classList.toggle('active', option.getAttribute('data-locale-code') === locale.code);
    });
  }

  function closeLanguageMenus() {
    Array.prototype.forEach.call(document.querySelectorAll('[data-language-switcher]'), function (switcher) {
      switcher.classList.remove('language-switcher--open');
      var button = switcher.querySelector('[data-language-toggle]');
      if (button) {
        button.setAttribute('aria-expanded', 'false');
      }
    });
  }

  function currentAuthToken() {
    var keys = ['api_token', 'token', 'Authorization', 'userToken', 'member_token', 'th2w:api_token'];
    for (var index = 0; index < keys.length; index += 1) {
      var value = localStorage.getItem(keys[index]) || sessionStorage.getItem(keys[index]);
      if (value) {
        return String(value).replace(/^Bearer\s+/i, '').trim();
      }
    }
    return '';
  }

  function renderMemberName(name) {
    Array.prototype.forEach.call(document.querySelectorAll('[data-member-name]'), function (node) {
      node.textContent = name;
    });
  }

  function renderAuthRoute() {
    renderAuthPage();
    initFloatingSupport();
  }

  function renderAuthPage() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    var hashPath = location.hash.replace(/^#/, '').split('?')[0].replace(/\/+$/, '') || '';
    var registering = path === '/register' || hashPath === '/register';
    var hashQuery = location.hash.indexOf('?') >= 0 ? location.hash.slice(location.hash.indexOf('?')) : '';
    var searchParams = new URLSearchParams(location.search || hashQuery || '');
    var redirect = searchParams.get('redirect') || '/member/center';
    var title = registering ? '会员注册' : '会员登录';
    var subtitle = registering
      ? '创建账号后即可进入游戏大厅，管理充值、提现和活动权益。'
      : '登录后可进入游戏大厅、管理钱包，并查看最新交易记录。';
    var alternate = registering
      ? '<a href="/login">已有账号，去登录</a>'
      : '<a href="/register">还没有账号，立即注册</a>';

    document.body.innerHTML = [
      '<main class="auth-shell">',
      '<section class="auth-panel">',
      '<a class="brand-logo auth-logo" href="/"><span class="brand-logo__main">TH2.VIP</span><span class="brand-logo__sub">在线游戏</span></a>',
      '<div class="auth-hero">',
      '<span>会员入口</span>',
      '<h1>' + title + '</h1>',
      '<p>' + subtitle + '</p>',
      '<div class="auth-game-icons">',
      fallbackWxGames().slice(0, 5).map(function (game) {
        return '<img src="' + escapeAttribute(game.img) + '" alt="' + escapeAttribute(game.name) + '" loading="lazy" data-wxgame-img>';
      }).join(''),
      '</div>',
      '</div>',
      '<form class="auth-card" data-auth-form data-auth-mode="' + (registering ? 'register' : 'login') + '">',
      '<h2>' + title + '</h2>',
      '<label><span>用户名</span><input name="username" autocomplete="username" required placeholder="请输入用户名"></label>',
      '<label><span>密码</span><input name="password" type="password" autocomplete="' + (registering ? 'new-password' : 'current-password') + '" required placeholder="请输入密码"></label>',
      registering ? '<label><span>确认密码</span><input name="password_confirmation" type="password" autocomplete="new-password" required placeholder="请再次输入密码"></label>' : '',
      registering ? '<label><span>邀请码</span><input name="invite_code" placeholder="选填"></label>' : '',
      '<button type="submit">' + title + '</button>',
      '<p class="auth-message" data-auth-message></p>',
      '<div class="auth-links">' + alternate + '<a href="' + escapeAttribute(redirect) + '">返回上一页</a></div>',
      '</form>',
      '</section>',
      '</main>'
    ].join('');

    bindAuthForm(redirect);
    bindWxGameImageFallbacks(document.body);
  }

  function bindAuthForm(redirect) {
    var form = document.querySelector('[data-auth-form]');
    if (!form) {
      return;
    }

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var message = form.querySelector('[data-auth-message]');
      var mode = form.getAttribute('data-auth-mode') || 'login';
      var body = {};
      Array.prototype.forEach.call(form.elements, function (field) {
        if (field.name) {
          body[field.name] = field.value;
        }
      });
      var endpoint = mode === 'register' ? '/api/register' : '/api/login';
      if (message) {
        message.textContent = '正在处理...';
      }
      postJson(endpoint, body)
        .then(function (payload) {
          var data = responseData(payload) || {};
          var token = data.token || data.api_token || data.access_token || payload.token || '';
          if (token) {
            localStorage.setItem('th2w:api_token', token);
            localStorage.setItem('api_token', token);
            if (message) {
              message.textContent = '成功，正在进入会员中心';
            }
            window.setTimeout(function () {
              location.href = redirect || '/member/center';
            }, 500);
            return;
          }
          if (message) {
            message.textContent = (payload && payload.message) || '操作失败，请稍后重试';
          }
        })
        .catch(function () {
          if (message) {
            message.textContent = '连接失败，请稍后重试';
          }
        });
    });
  }

  function loginUrl() {
    var redirect = encodeURIComponent(location.pathname + location.search);
    return '/login?redirect=' + redirect;
  }

  function initGameLaunchBridge() {
    var params = new URLSearchParams(location.search || '');
    var platform = (params.get('platform_name') || params.get('plat_name') || 'WG').trim();
    var gameType = (params.get('game_type') || params.get('category') || 'slot').trim();
    var gameCode = (params.get('game_code') || params.get('gamecode') || '').trim();

    document.body.innerHTML = renderGameLaunchShell('正在打开游戏', '请稍候...', true);

    if (!platform || !gameType || !gameCode) {
      updateGameLaunchShell('没有找到游戏信息', '请返回游戏大厅重新选择。', false, '/gaming');
      return;
    }

    var token = currentAuthToken();
    if (!token) {
      updateGameLaunchShell('请先登录', '登录后即可进入 WXGAME。', false, loginUrl());
      window.setTimeout(function () {
        location.href = loginUrl();
      }, 900);
      return;
    }

    postJson('/api/getGameUrl', {
      plat_name: platform,
      game_type: gameType,
      game_code: gameCode,
      is_mobile_url: isMobileClient() ? 1 : 0,
      game_lang: 'zh'
    }, {
      Authorization: 'Bearer ' + token
    }).then(function (payload) {
      var data = responseData(payload) || {};
      var url = typeof data === 'string' ? data : (data.url || data.game_url || data.login_url || '');
      if (payloadCode(payload) === 200 && url) {
        updateGameLaunchShell('游戏已打开', '正在跳转到游戏页面...', true);
        location.href = url;
        return;
      }
      updateGameLaunchShell('打开游戏失败', escapeHtml((payload && payload.message) || '请稍后重试。'), false, '/gaming');
    }).catch(function () {
      updateGameLaunchShell('打开游戏失败', '连接异常，请稍后重试。', false, '/gaming');
    });
  }

  function renderGameLaunchShell(title, message, loading) {
    return [
      '<main class="game-launch-shell">',
      '<section class="game-launch-card">',
      '<a class="brand-logo game-launch-logo" href="/"><span class="brand-logo__main">TH2.VIP</span><span class="brand-logo__sub">在线游戏</span></a>',
      '<div class="game-launch-spinner"' + (loading ? '' : ' hidden') + '></div>',
      '<h1 data-game-launch-title>' + escapeHtml(title) + '</h1>',
      '<p data-game-launch-message>' + message + '</p>',
      '<a class="game-launch-action" data-game-launch-action href="/gaming" hidden>查看全部游戏</a>',
      '</section>',
      '</main>'
    ].join('');
  }

  function updateGameLaunchShell(title, message, loading, actionUrl) {
    var titleNode = document.querySelector('[data-game-launch-title]');
    var messageNode = document.querySelector('[data-game-launch-message]');
    var spinner = document.querySelector('.game-launch-spinner');
    var action = document.querySelector('[data-game-launch-action]');
    if (titleNode) {
      titleNode.textContent = title;
    }
    if (messageNode) {
      messageNode.innerHTML = message;
    }
    if (spinner) {
      spinner.hidden = !loading;
    }
    if (action) {
      action.hidden = !actionUrl;
      if (actionUrl) {
        action.href = actionUrl;
      }
    }
  }

  function isMobileClient() {
    return window.innerWidth <= 640 ||
      (window.matchMedia && window.matchMedia('(max-width: 640px)').matches) ||
      /Android|iPhone|iPad|iPod|Mobile/i.test(navigator.userAgent || '');
  }

  function renderMemberToolPage() {
    var token = currentAuthToken();
    if (!token) {
      renderMemberLoginGate();
      return;
    }

    document.body.innerHTML = [
      '<main class="member-tool-shell">',
      '<header class="member-tool-top">',
      '<a class="brand-logo" href="/"><span class="brand-logo__main">TH2.VIP</span><span class="brand-logo__sub">在线游戏</span></a>',
      '<a class="member-tool-back" href="/">返回首页</a>',
      '</header>',
      '<section class="member-tool-hero">',
      '<div>',
      '<div class="member-tool-kicker">会员中心</div>',
      '<h1>' + memberToolTitle() + '</h1>',
      '<p>' + memberToolDescription() + '</p>',
      '</div>',
      '<div class="member-balance-card" data-member-balance-card>',
      '<span>账户余额</span>',
      '<strong data-member-balance>--</strong>',
      '<small data-member-name>会员</small>',
      '</div>',
      '</section>',
      '<section class="member-tool-card">',
      '<div class="member-tool-grid">',
      memberToolLink('/member/center', '会员中心', 'center'),
      memberToolLink('/member/wallet', '钱包', 'wallet'),
      memberToolLink('/member/recharge', '充值', 'deposit'),
      memberToolLink('/member/withdraw', '提现', 'withdraw'),
      '</div>',
      '<div class="member-workspace" data-member-workspace>',
      renderMemberWorkspaceSkeleton(),
      '</div>',
      '</section>',
      '</main>'
    ].join('');

    loadMemberToolData(token);
  }

  function renderMemberLoginGate() {
    document.body.innerHTML = [
      '<main class="member-tool-shell member-login-shell">',
      '<section class="member-tool-hero member-login-hero">',
      '<div>',
      '<div class="member-tool-kicker">会员登录</div>',
      '<h1>登录后使用会员功能</h1>',
      '<p>管理余额、充值、提现和最近交易记录。</p>',
      '<div class="member-login-actions">',
      '<a class="member-login-primary" href="' + escapeAttribute(loginUrl()) + '">登录</a>',
      '<a class="member-login-secondary" href="/register">注册会员</a>',
      '<a class="member-login-secondary" href="/">返回首页</a>',
      '</div>',
      '</div>',
      '<div class="member-login-preview">',
      '<span>资金功能</span>',
      '<strong>充值 / 提现 / 钱包</strong>',
      '<small>登录后即可查看和提交记录</small>',
      '</div>',
      '</section>',
      '<section class="member-tool-card member-login-card">',
      '<div class="member-tool-grid">',
      memberToolLink('/member/center', '会员中心', 'center'),
      memberToolLink('/member/wallet', '钱包', 'wallet'),
      memberToolLink('/member/recharge', '充值', 'deposit'),
      memberToolLink('/member/withdraw', '提现', 'withdraw'),
      '</div>',
      renderMemberGuestPreview(),
      '</section>',
      '</main>'
    ].join('');
  }

  function memberToolTitle() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    var titles = {
      '/member/wallet': '钱包',
      '/wallet': '钱包',
      '/member/recharge': '充值',
      '/recharge': '充值',
      '/member/withdraw': '提现',
      '/withdraw': '提现'
    };
    return titles[path] || '会员中心';
  }

  function memberToolDescription() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    if (path.indexOf('recharge') !== -1) {
      return '选择充值渠道、金额并提交充值订单。';
    }
    if (path.indexOf('withdraw') !== -1) {
      return '选择已绑定收款账户并提交提现申请。';
    }
    if (path.indexOf('wallet') !== -1) {
      return '查看主钱包余额、最近资金记录和返水状态。';
    }
    return '查看余额、充值、提现和会员账户信息。';
  }

  function memberToolLink(href, label, iconText) {
    var active = (location.pathname.replace(/\/+$/, '') || '/') === href;
    return '<a class="member-tool-link' + (active ? ' active' : '') + '" href="' + href + '"><span>' + memberToolIcon(iconText) + '</span><strong>' + label + '</strong></a>';
  }

  function loadMemberToolData(token) {
    postJson('/api/user', {}, {
      Authorization: 'Bearer ' + token
    }).then(function (payload) {
      var user = responseData(payload) || {};
      var name = user.username || user.name || user.realname || '会员';
      var balance = user.money || user.balance || user.user_money || user.score || '0.00';
      renderMemberName(name);
      updateMemberBalance(name, balance);
      renderMemberWorkspace(user, balance);
      hydrateMemberWorkspace(token, user, balance);
    }).catch(function () {
      updateMemberBalance('会员', '0.00');
      renderMemberWorkspace({}, '0.00');
      hydrateMemberWorkspace(token, {}, '0.00');
    });
  }

  function updateMemberBalance(name, balance) {
    var balanceNode = document.querySelector('[data-member-balance]');
    var nameNode = document.querySelector('[data-member-balance-card] small');
    if (balanceNode) {
      balanceNode.textContent = formatMoney(balance);
    }
    if (nameNode) {
      nameNode.textContent = name || '会员';
    }
  }

  function renderMemberWorkspace(user, balance) {
    var node = document.querySelector('[data-member-workspace]');
    if (!node) {
      return;
    }
    var path = location.pathname.replace(/\/+$/, '') || '/';
    if (path.indexOf('recharge') !== -1) {
      node.innerHTML = renderRechargeWorkspace(balance);
      bindMemberAmountPicker(node);
      bindMemberRecharge(node, currentAuthToken());
      return;
    }
    if (path.indexOf('withdraw') !== -1) {
      node.innerHTML = renderWithdrawWorkspace(user, balance);
      bindMemberWithdraw(node, currentAuthToken());
      return;
    }
    if (path.indexOf('wallet') !== -1) {
      node.innerHTML = renderWalletWorkspace(balance);
      return;
    }
    node.innerHTML = renderCenterWorkspace(user, balance);
  }

  function renderMemberWorkspaceSkeleton() {
    return '<div class="member-tool-state">正在加载数据...</div>';
  }

  function renderCenterWorkspace(user, balance) {
    return [
      '<div class="member-dashboard-grid">',
      memberMetric('主账户余额', formatMoney(balance), '可进入游戏'),
      memberMetric('VIP 等级', escapeHtml(user.level_name || user.vip_name || '普通会员'), '累计游戏流水'),
      memberMetric('活动福利', '查看优惠', '每日更新'),
      '</div>',
      '<div class="member-profile-panel">',
      '<div><span>账户状态</span><strong>可正常使用</strong><small>实时查看余额和交易记录</small></div>',
      '<div><span>银行 / USDT</span><strong>' + escapeHtml(user.bank_name || user.usdt_address || '暂未绑定') + '</strong><small>绑定账户后可提交提现</small></div>',
      '<div><span>消息中心</span><strong data-member-message-count>加载中</strong><small>最新公告和会员通知</small></div>',
      '</div>',
      '<div class="member-action-row">',
      '<a href="/member/recharge">充值</a>',
      '<a href="/member/withdraw">提现</a>',
      '<a href="/promotions">会员活动</a>',
      '<a href="#" data-customer-service>联系客服</a>',
      '<button type="button" data-member-rebate>领取返水</button>',
      '</div>',
      renderMemberTimeline('正在加载真实记录...')
    ].join('');
  }

  function renderWalletWorkspace(balance) {
    return [
      '<div class="member-wallet-layout">',
      '<section class="member-wallet-main">',
      '<span>主钱包</span>',
      '<strong>' + formatMoney(balance) + '</strong>',
      '<p>查看主账户余额，并进入游戏大厅或管理资金记录。</p>',
      '<div class="member-action-row">',
      '<a href="/member/recharge">充值</a>',
      '<a href="/member/withdraw">提现</a>',
      '<button type="button" data-member-refresh>刷新</button>',
      '<button type="button" data-member-rebate>领取返水</button>',
      '</div>',
      '</section>',
      '<section class="member-wallet-side">',
      '<strong>游戏钱包</strong><span data-member-game-balance>正在同步</span>',
      '<strong>返水记录</strong><span data-member-rebate-state>正在查询</span>',
      '<strong>余额刷新</strong><span>点击刷新获取最新余额</span>',
      '</section>',
      '</div>',
      renderMemberTimeline('正在加载真实记录...')
    ].join('');
  }

  function renderRechargeWorkspace(balance) {
    return [
      '<div class="member-form-layout">',
      '<section class="member-form-card">',
      '<h2>选择充值金额</h2>',
      '<div class="member-channel-grid" data-member-paytype>',
      '<button type="button" class="active" data-paytype="bank">银行卡</button>',
      '<button type="button" data-paytype="usdt" data-catepay="TRC20">USDT-TRC20</button>',
      '<button type="button" data-paytype="usdt" data-catepay="ERC20">USDT-ERC20</button>',
      '<button type="button" data-paytype="alipay">支付宝</button>',
      '<button type="button" data-paytype="wxpay">微信</button>',
      '</div>',
      '<div class="member-amount-grid" data-member-amounts>',
      [100, 300, 500, 1000, 3000, 5000].map(function (amount) {
        return '<button type="button" data-amount="' + amount + '">' + amount.toLocaleString('en-US') + '</button>';
      }).join(''),
      '</div>',
      '<label class="member-field"><span>充值金额</span><input inputmode="decimal" data-member-amount-input value="500"></label>',
      '<label class="member-field"><span>付款姓名</span><input data-member-payer placeholder="请输入与账户一致的姓名"></label>',
      '<button class="member-submit" type="button" data-member-submit>提交充值</button>',
      '<p class="member-form-state" data-member-form-state>提交后会生成充值订单，后台审核或支付回调后入账。</p>',
      '</section>',
      '<aside class="member-form-help"><strong>当前余额</strong><span>' + formatMoney(balance) + '</span><p>付款后系统会自动更新，异常请联系客服处理。</p></aside>',
      '</div>',
      renderMemberTimeline('正在加载充值记录...')
    ].join('');
  }

  function renderWithdrawWorkspace(user, balance) {
    return [
      '<div class="member-form-layout">',
      '<section class="member-form-card">',
      '<h2>提交提现申请</h2>',
      '<div class="member-withdraw-rule">',
      '<span>最低 100</span><span>24H 内审核</span><span>提现前会校验流水</span>',
      '</div>',
      '<label class="member-field"><span>提现金额</span><input inputmode="decimal" data-member-withdraw-amount value="500"></label>',
      '<label class="member-field"><span>收款账户</span><select data-member-withdraw-card><option value="">正在加载收款账户...</option></select></label>',
      '<label class="member-field"><span>取款密码</span><input type="password" data-member-withdraw-password placeholder="请输入取款密码"></label>',
      '<button class="member-submit" type="button" data-member-withdraw-submit>确认提现</button>',
      '<p class="member-form-state" data-member-form-state>提现会提交到后台审核，请确认收款账户和取款密码。</p>',
      '</section>',
      '<aside class="member-form-help"><strong>可提现</strong><span>' + formatMoney(balance) + '</span><p>提交前请确认收款账户信息正确。</p></aside>',
      '</div>',
      renderMemberTimeline('正在加载提现记录...')
    ].join('');
  }

  function renderMemberGuestPreview() {
    return [
      '<div class="member-workspace member-guest-preview">',
      '<div class="member-dashboard-grid">',
      memberMetric('账户余额', '--', '登录后查看'),
      memberMetric('充值', '100+', '支持多种渠道'),
      memberMetric('提现', '24H', '客服每日处理'),
      '</div>',
      '</div>'
    ].join('');
  }

  function renderMemberTimeline(message) {
    return [
      '<section class="member-history" data-member-history>',
      '<div><strong>最近记录</strong><span>查看资金状态</span></div>',
      '<ul data-member-history-list>',
      '<li><b>记录</b><span>' + escapeHtml(message || '正在加载真实记录...') + '</span></li>',
      '</ul>',
      '</section>'
    ].join('');
  }

  function memberMetric(label, value, note) {
    return '<div class="member-metric"><span>' + label + '</span><strong>' + value + '</strong><small>' + note + '</small></div>';
  }

  function bindMemberAmountPicker(root) {
    var input = root.querySelector('[data-member-amount-input]');
    Array.prototype.forEach.call(root.querySelectorAll('[data-amount]'), function (button) {
      button.addEventListener('click', function () {
        if (input) {
          input.value = button.getAttribute('data-amount') || '';
        }
      });
    });
  }

  function setMemberInlineState(root, message) {
    var help = root.querySelector('[data-member-form-state]') || root.querySelector('.member-form-help p') || root.querySelector('[data-member-history-list]');
    if (help) {
      help.textContent = message;
    }
  }

  function bindMemberRecharge(root, token) {
    var selected = { paytype: 'bank', catepay: '' };
    Array.prototype.forEach.call(root.querySelectorAll('[data-paytype]'), function (button) {
      button.addEventListener('click', function () {
        Array.prototype.forEach.call(root.querySelectorAll('[data-paytype]'), function (item) {
          item.classList.toggle('active', item === button);
        });
        selected.paytype = button.getAttribute('data-paytype') || 'bank';
        selected.catepay = button.getAttribute('data-catepay') || '';
        loadPayRange(root, selected);
      });
    });
    loadPayRange(root, selected);
    var submit = root.querySelector('[data-member-submit]');
    if (!submit) {
      return;
    }
    submit.addEventListener('click', function () {
      var amountInput = root.querySelector('[data-member-amount-input]');
      var payerInput = root.querySelector('[data-member-payer]');
      var amount = Number(amountInput ? amountInput.value : 0);
      if (!amount || amount <= 0) {
        setMemberInlineState(root, '请输入正确的充值金额。');
        return;
      }
      submit.disabled = true;
      submit.textContent = '提交中...';
      apiPost('/api/recharge', {
        amount: amount,
        paytype: selected.paytype,
        catepay: selected.catepay,
        real_name: payerInput ? payerInput.value.trim() : '',
        bank_owner: payerInput ? payerInput.value.trim() : ''
      }, token).then(function (payload) {
        if (payloadCode(payload) === 200) {
          var orderNo = payload.message || payload.msg || '';
          setMemberInlineState(root, orderNo ? '充值订单已提交，订单号：' + orderNo : '充值订单已提交，请按提示完成付款。');
          loadMemberHistory(token);
        } else {
          setMemberInlineState(root, (payload && (payload.message || payload.msg)) || '充值提交失败，请检查渠道和金额。');
        }
      }).catch(function () {
        setMemberInlineState(root, '充值提交失败，请稍后重试或联系客服。');
      }).finally(function () {
        submit.disabled = false;
        submit.textContent = '提交充值';
      });
    });
  }

  function loadPayRange(root, selected) {
    var type = selected.paytype === 'usdt' ? (selected.catepay === 'ERC20' ? 'usdt-erc20' : 'usdt-trc20') : selected.paytype;
    apiPost('/api/getPayRange', { type: type }, currentAuthToken()).then(function (payload) {
      var data = responseData(payload) || {};
      var min = data.min_price || data.min || '';
      var max = data.max_price || data.max || '';
      if (min || max) {
        setMemberInlineState(root, '当前通道限额：' + (min || '不限') + ' - ' + (max || '不限'));
      }
    }).catch(function () {
      setMemberInlineState(root, '请选择充值金额并提交订单。');
    });
  }

  function bindMemberWithdraw(root, token) {
    var cardSelect = root.querySelector('[data-member-withdraw-card]');
    loadWithdrawCards(cardSelect, token);
    var submit = root.querySelector('[data-member-withdraw-submit]');
    if (!submit) {
      return;
    }
    submit.addEventListener('click', function () {
      var amountInput = root.querySelector('[data-member-withdraw-amount]');
      var passwordInput = root.querySelector('[data-member-withdraw-password]');
      var amount = Number(amountInput ? amountInput.value : 0);
      var card = cardSelect ? cardSelect.value : '';
      var password = passwordInput ? passwordInput.value : '';
      if (!amount || amount <= 0) {
        setMemberInlineState(root, '请输入正确的提现金额。');
        return;
      }
      if (!card) {
        setMemberInlineState(root, '请先选择已绑定的收款账户。');
        return;
      }
      if (!password) {
        setMemberInlineState(root, '请输入取款密码。');
        return;
      }
      submit.disabled = true;
      submit.textContent = '提交中...';
      apiPost('/api/withdraw', { amount: amount, bank: card, password: password }, token).then(function (payload) {
        if (payloadCode(payload) === 200) {
          setMemberInlineState(root, '提现申请已提交，等待后台审核。');
          loadMemberHistory(token);
          loadMemberToolData(token);
        } else {
          setMemberInlineState(root, (payload && (payload.message || payload.msg)) || '提现提交失败，请检查余额、流水或取款密码。');
        }
      }).catch(function () {
        setMemberInlineState(root, '提现提交失败，请稍后重试或联系客服。');
      }).finally(function () {
        submit.disabled = false;
        submit.textContent = '确认提现';
      });
    });
  }

  function loadWithdrawCards(select, token) {
    if (!select) {
      return;
    }
    Promise.all([
      apiPost('/api/getcard', { type: 1 }, token).catch(function () { return null; }),
      apiPost('/api/getcard', { type: 2 }, token).catch(function () { return null; }),
      apiPost('/api/getcard', { type: 3 }, token).catch(function () { return null; })
    ]).then(function (payloads) {
      var cards = [];
      payloads.forEach(function (payload) {
        var data = responseData(payload);
        if (Array.isArray(data)) {
          cards = cards.concat(data);
        } else if (data && Array.isArray(data.data)) {
          cards = cards.concat(data.data);
        }
      });
      if (!cards.length) {
        select.innerHTML = '<option value="">暂无已绑定账户，请先联系客服绑定</option>';
        return;
      }
      select.innerHTML = cards.map(function (card) {
        var label = [card.bank || '收款账户', card.bank_not ? '尾号 ' + card.bank_not : '', card.bank_owner || card.bank_address || ''].filter(Boolean).join(' - ');
        return '<option value="' + escapeAttribute(card.id) + '">' + escapeHtml(label) + '</option>';
      }).join('');
    }).catch(function () {
      select.innerHTML = '<option value="">收款账户加载失败</option>';
    });
  }

  function hydrateMemberWorkspace(token, user, balance) {
    var workspace = document.querySelector('[data-member-workspace]');
    if (!workspace) {
      return;
    }
    bindMemberRefresh(workspace, token);
    bindMemberRebate(workspace, token);
    loadMemberHistory(token);
    loadMemberMessages(token);
    loadMemberGameBalance(token);
    loadMemberRebateState(token);
  }

  function bindMemberRefresh(root, token) {
    Array.prototype.forEach.call(root.querySelectorAll('[data-member-refresh]'), function (button) {
      button.addEventListener('click', function () {
        button.disabled = true;
        button.textContent = '刷新中...';
        apiPost('/api/user', {}, token).then(function (payload) {
          var user = responseData(payload) || {};
          var name = user.username || user.name || user.realname || '会员';
          var balance = user.money || user.balance || user.user_money || user.score || '0.00';
          renderMemberName(name);
          updateMemberBalance(name, balance);
          var main = root.querySelector('.member-wallet-main strong');
          if (main) {
            main.textContent = formatMoney(balance);
          }
          button.textContent = '已刷新';
          setTimeout(function () { button.textContent = '刷新'; button.disabled = false; }, 1200);
          loadMemberHistory(token);
          loadMemberGameBalance(token);
        }).catch(function () {
          button.textContent = '刷新失败';
          setTimeout(function () { button.textContent = '刷新'; button.disabled = false; }, 1600);
        });
      });
    });
  }

  function bindMemberRebate(root, token) {
    Array.prototype.forEach.call(root.querySelectorAll('[data-member-rebate]'), function (button) {
      button.addEventListener('click', function () {
        button.disabled = true;
        button.textContent = '领取中...';
        apiPost('/api/dofanshui', {}, token).then(function (payload) {
          var message = payload && payload.message ? payload.message : (payloadCode(payload) === 200 ? '返水领取成功' : '暂无可领取返水');
          button.textContent = message;
          loadMemberRebateState(token);
          loadMemberHistory(token);
          setTimeout(function () { button.textContent = '领取返水'; button.disabled = false; }, 1800);
        }).catch(function () {
          button.textContent = '领取失败';
          setTimeout(function () { button.textContent = '领取返水'; button.disabled = false; }, 1600);
        });
      });
    });
  }

  function loadMemberHistory(token) {
    var list = document.querySelector('[data-member-history-list]');
    if (!list) {
      return;
    }
    list.innerHTML = '<li><b>记录</b><span>正在加载真实记录...</span></li>';
    var requests = [
      apiPost('/api/getrechargerecord', { limit: 3 }, token).then(function (payload) { return normalizeMemberRecords(payload, '充值'); }).catch(function () { return []; }),
      apiPost('/api/getwithdrawrecord', { limit: 3 }, token).then(function (payload) { return normalizeMemberRecords(payload, '提现'); }).catch(function () { return []; }),
      apiPost('/api/gettransrecord', { limit: 3 }, token).then(function (payload) { return normalizeMemberRecords(payload, '转账'); }).catch(function () { return []; })
    ];
    Promise.all(requests).then(function (groups) {
      var rows = [];
      groups.forEach(function (items) {
        rows = rows.concat(items);
      });
      rows.sort(function (a, b) {
        return String(b.time || '').localeCompare(String(a.time || ''));
      });
      if (!rows.length) {
        list.innerHTML = '<li><b>暂无记录</b><span>后台没有返回充值、提现或转账记录</span></li>';
        return;
      }
      list.innerHTML = rows.slice(0, 6).map(function (item) {
        var amount = item.amount ? '¥' + formatMoney(item.amount) : '';
        var status = item.status || item.state || item.remark || '已提交';
        return '<li><b>' + escapeHtml(item.type) + '</b><span>' + escapeHtml([amount, status, item.time].filter(Boolean).join(' - ')) + '</span></li>';
      }).join('');
    });
  }

  function normalizeMemberRecords(payload, type) {
    var data = responseData(payload);
    var rows = [];
    if (Array.isArray(data)) {
      rows = data;
    } else if (data && Array.isArray(data.data)) {
      rows = data.data;
    } else if (data && Array.isArray(data.list)) {
      rows = data.list;
    } else if (data && Array.isArray(data.rows)) {
      rows = data.rows;
    }
    return rows.map(function (row) {
      return {
        type: type,
        amount: row.amount || row.money || row.real_money || row.score || row.balance || '',
        status: row.status_text || row.state_text || row.type || row.status || row.state || '',
        time: row.created_at || row.updated_at || row.addtime || row.time || '',
        remark: row.remark || row.memo || row.note || ''
      };
    });
  }

  function loadMemberMessages(token) {
    var node = document.querySelector('[data-member-message-count]');
    if (!node) {
      return;
    }
    apiPost('/api/message', { type: 1, limit: 5 }, token).then(function (payload) {
      var data = responseData(payload);
      var rows = Array.isArray(data) ? data : (data && (data.data || data.list || data.rows)) || [];
      node.textContent = rows && rows.length ? rows.length + ' 条消息' : '暂无新消息';
    }).catch(function () {
      node.textContent = '暂无新消息';
    });
  }

  function loadMemberGameBalance(token) {
    var node = document.querySelector('[data-member-game-balance]');
    if (!node) {
      return;
    }
    apiPost('/api/balance', {}, token).then(function (payload) {
      var data = responseData(payload) || {};
      var value = data.balance || data.money || data.user_money || data.score || '';
      node.textContent = value === '' ? '已同步主余额' : '余额 ' + formatMoney(value);
    }).catch(function () {
      node.textContent = '暂未返回游戏余额';
    });
  }

  function loadMemberRebateState(token) {
    var node = document.querySelector('[data-member-rebate-state]');
    if (!node) {
      return;
    }
    apiPost('/api/getfanshui', { limit: 1 }, token).then(function (payload) {
      var data = responseData(payload);
      var rows = Array.isArray(data) ? data : (data && (data.data || data.list || data.rows)) || [];
      node.textContent = rows && rows.length ? '有返水记录' : '暂无可领取返水';
    }).catch(function () {
      node.textContent = '返水接口暂无数据';
    });
  }

  function memberToolIcon(type) {
    var map = {
      center: 'ME',
      wallet: '¥',
      deposit: '+',
      withdraw: '-'
    };
    return map[type] || 'ME';
  }

  function formatMoney(value) {
    var number = Number(String(value == null ? 0 : value).replace(/,/g, ''));
    if (!isFinite(number)) {
      return String(value || '0.00');
    }
    return number.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function initCarousel() {
    var root = document.querySelector('[data-home-banner], [data-reference-banner]');
    if (!root) {
      return;
    }

    postJson('/api/bannerList', { type: 2 })
      .then(function (payload) {
        var rows = responseData(payload);
        var banners = Array.isArray(rows)
          ? rows.map(normalizeBanner).filter(Boolean)
          : [];

        renderCarousel(root, banners.length ? banners : fallbackHomeBanners());
      })
      .catch(function () {
        root.classList.add('home-carousel--fallback');
        renderCarousel(root, fallbackHomeBanners());
      });
  }

  function fallbackHomeBanners() {
    return [
      {
        src: '/assets/promotions/referral-banner.webp',
        href: '/promotions',
        background: '#123252'
      },
      {
        src: '/assets/promotions/welcome-banner.png',
        href: '/activity',
        background: '#163b5c'
      },
      {
        src: '/assets/promotions/deposit-banner.png',
        href: '/member/recharge',
        background: '#1b496d'
      }
    ];
  }

  function normalizeBanner(item) {
    if (!item || !item.src) {
      return null;
    }

    var src = safeWebUrl(item.src, true);
    if (!src) {
      return null;
    }

    return {
      src: src,
      href: safeWebUrl(item.url || item.jump_url || '', false) || '/promotions',
      background: safeColor(item.background)
    };
  }

  function initHomeParityEnhancements() {
    if (document.querySelector('[data-home-parity-enhanced]')) {
      return;
    }

    var marker = document.querySelector('[data-home-banner]');
    if (!marker || !marker.parentNode) {
      return;
    }

    marker.insertAdjacentHTML('afterend', homeParityMarkup());
    hydrateHomeGameCards(document.querySelector('[data-home-parity-enhanced]'));
  }

  function homeShortcuts() {
    return [
      { href: '/member/wallet', type: 'wallet', label: '钱包', note: '余额' },
      { href: '/member/recharge', type: 'deposit', label: '充值', note: '存款' },
      { href: '/member/withdraw', type: 'withdraw', label: '提现', note: '到账' },
      { href: '/promotions?type=vip', type: 'vip', label: 'VIP', note: '会员等级' },
      { href: '/promotions?type=invite', type: 'invite', label: '邀请好友', note: '领取奖励' },
      { href: '/promotions?type=wheel', type: 'wheel', label: '幸运转盘', note: '抽取奖品' },
      { href: '/promotions?type=certificate', type: 'certificate', label: '安全认证', note: '放心游戏' },
      { href: '/promotions?type=rebate', type: 'rebate', label: '返水', note: '每日返利' }
    ];
  }

  function fallbackHomeGames() {
    return [
      { href: '/joker', img: '/uploads/th2w-ui/game-icons/cherry.svg', title: '热门老虎机', amount: '¥8,932,110' },
      { href: '/realbet', img: '/uploads/th2w-ui/game-icons/dices.svg', title: '真人娱乐', amount: '¥6,418,720' },
      { href: '/gaming?keyword=Fishing', img: '/uploads/th2w-ui/game-icons/fish.svg', title: '捕鱼游戏', amount: '¥3,804,690' },
      { href: '/sport', img: '/uploads/th2w-ui/game-icons/trophy.svg', title: '体育赛事', amount: '¥2,990,430' }
    ];
  }

  function homeParityMarkup() {
    var shortcuts = homeShortcuts();
    return [
      '<section class="home-parity" data-home-parity-enhanced>',
      '<div class="home-quick-actions" aria-label="会员快捷菜单">',
      shortcuts.map(function (item) {
        return [
          '<a class="home-quick-action" data-shortcut="' + escapeAttribute(item.type) + '" href="' + escapeAttribute(item.href) + '">',
          '<span class="home-quick-art">' + shortcutIcon(item.type) + '</span>',
          '<strong>' + item.label + '</strong>',
          '<small>' + item.note + '</small>',
          '</a>'
        ].join('');
      }).join(''),
      '</div>',
      '<a class="home-jackpot" href="/promotions" aria-label="今日奖池">',
      '<div><span>今日奖池</span><strong>¥18,888,520</strong><small>会员每日奖金与福利专区</small></div>',
      '<em>立即参与</em>',
      '</a>',
      '<div class="home-promo-stage">',
      '<a class="home-promo-stage__main" href="/activity"><img src="/assets/promotions/welcome-banner.png" alt="新会员奖励" loading="lazy"><span>热门活动</span></a>',
      '<a class="home-promo-stage__tile" href="/member/recharge"><img src="/assets/promotions/deposit-banner.png" alt="充值活动" loading="lazy"></a>',
      '<a class="home-promo-stage__tile" href="/promotions"><img src="/assets/promotions/referral-banner.webp" alt="邀请好友" loading="lazy"></a>',
      '</div>',
      '<div class="home-game-showcase"><div class="home-game-head"><strong>热门活动</strong><a href="/gaming">查看全部</a></div><div class="home-game-cards" data-home-game-cards>',
      renderHomeGameCards(fallbackHomeGames()),
      '</div></div>',
      '</section>'
    ].join('');
  }

  function shortcutIcon(type) {
    var icons = {
      wallet: '/uploads/th2w-ui/quick-icons/wallet.svg',
      deposit: '/uploads/th2w-ui/quick-icons/deposit.svg',
      withdraw: '/uploads/th2w-ui/quick-icons/withdraw.svg',
      vip: '/uploads/th2w-ui/quick-icons/vip.svg',
      invite: '/uploads/th2w-ui/quick-icons/invite.svg',
      wheel: '/uploads/th2w-ui/quick-icons/wheel.svg',
      certificate: '/uploads/th2w-ui/quick-icons/certificate.svg',
      rebate: '/uploads/th2w-ui/quick-icons/rebate.svg'
    };
    return '<img class="shortcut-real-icon" src="' + escapeAttribute(icons[type] || icons.wallet) + '" alt="" decoding="async" loading="lazy">';
  }

  function hydrateHomeGameCards(scope) {
    var root = scope && scope.querySelector ? scope.querySelector('[data-home-game-cards]') : null;
    if (!root) {
      return;
    }

    fetchSiteGames().then(function (allGames) {
      var games = normalizeHomeGames(allGames);
      if (games.length) {
        root.innerHTML = renderHomeGameCards(games);
        root.setAttribute('data-home-game-source', 'site-games');
        bindWxGameImageFallbacks(root);
      }
    }).catch(function () {
      root.setAttribute('data-home-game-source', 'fallback');
    });
  }

  function normalizeHomeGames(data) {
    var rows = extractGameRows(data);
    return homeShowcaseGames(rows, 8).map(function (row, index) {
      return {
        href: row.href,
        img: row.image || row.img,
        title: row.title,
        amount: index < 3 ? '热门' : String(row.platform || '').toUpperCase()
      };
    }).filter(function (game) {
      return game.img;
    });
  }

  function extractGameRows(data) {
    if (Array.isArray(data)) {
      return data;
    }
    if (!data || typeof data !== 'object') {
      return [];
    }

    var keys = ['list', 'rows', 'data', 'games', 'items', 'records'];
    for (var index = 0; index < keys.length; index += 1) {
      var value = data[keys[index]];
      if (Array.isArray(value)) {
        return value;
      }
      if (value && typeof value === 'object') {
        var nested = extractGameRows(value);
        if (nested.length) {
          return nested;
        }
      }
    }

    return Object.keys(data).reduce(function (items, key) {
      return items.concat(extractGameRows(data[key]));
    }, []);
  }

  function gameLaunchUrl(platform, type, code) {
    var params = [
      'platform_name=' + encodeURIComponent(platform || 'WG'),
      'game_type=' + encodeURIComponent(type || 'slot')
    ];
    if (code) {
      params.push('game_code=' + encodeURIComponent(code));
    }
    return '/gaming?' + params.join('&');
  }

  function renderHomeGameCards(games) {
    return games.map(function (game) {
      return [
        '<a class="home-game-card" href="' + escapeAttribute(game.href) + '">',
        '<span class="home-game-card__image"><img src="' + escapeAttribute(game.img || game.image) + '" alt="' + escapeAttribute(game.title) + '" loading="lazy" data-wxgame-img></span>',
        '<span>' + escapeHtml(game.title) + '</span>',
        '<strong>' + escapeHtml(game.amount || '') + '</strong>',
        '<em>立即游戏</em>',
        '</a>'
      ].join('');
    }).join('');
  }

  function initWxGameHome() {
    fetchSiteGames().then(function (games) {
      if (!games.length) {
        games = normalizeWxGames(fallbackWxGames());
      }

      renderHomeWxSide(games);
      renderHomeWxTabs(games);
      renderHomeWxPromoGrid(games);
      renderHomeWxMobileBottom();
    }).catch(function () {
      var games = normalizeWxGames(fallbackWxGames());
      renderHomeWxSide(games);
      renderHomeWxTabs(games);
      renderHomeWxPromoGrid(games);
      renderHomeWxMobileBottom();
    });
  }

  function scheduleWxHomeGuard() {
    [450, 1600, 3200].forEach(function (delay) {
      window.setTimeout(function () {
        if (!isHomePath()) {
          return;
        }
        if (!document.querySelector('[data-wxgame-grid="ready"]') || !document.querySelector('[data-wxgame-side="ready"]')) {
          initWxGameHome();
        }
      }, delay);
    });
  }

  function fetchWxGames() {
    return fetch('/api/game/list?platform_name=WG&_=' + Date.now(), {
      headers: { 'Accept': 'application/json' }
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('wxgame-list-failed');
      }
      return response.json();
    }).then(function (payload) {
      return normalizeWxGames(responseData(payload));
    });
  }

  function fetchSiteGames() {
    return fetch('/api/game/list?_=' + Date.now(), {
      headers: { 'Accept': 'application/json' }
    }).then(function (response) {
      if (!response.ok) {
        throw new Error('game-list-failed');
      }
      return response.json();
    }).then(function (payload) {
      var games = normalizeSiteGames(responseData(payload));
      return games.length ? games : fetchWxGames();
    });
  }

  function fallbackWxGames() {
    var base = 'https://wxgame99-icon.suttonbouqloj5j.workers.dev/BHdownload/';
    return [
      { name: 'Water Margin', name_en: 'Water Margin', game_code: '3002', category_id: 'slot', img: base + '-en-1762741168.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Fortune Toucan', name_en: 'Fortune Toucan', game_code: '3008', category_id: 'slot', img: base + '-en-1762741109.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Dragon\'s Treasure', name_en: 'Dragon\'s Treasure', game_code: '3009', category_id: 'slot', img: base + '-en-1762740875.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Leopard Of Gold', name_en: 'Leopard Of Gold', game_code: '3010', category_id: 'slot', img: base + '-en-1762741054.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Mr Turtle', name_en: 'Mr Turtle', game_code: '3011', category_id: 'slot', img: base + '-en-1762740673.webp', platform_name: 'WG', app_state: 1 },
      { name: 'World Cup', name_en: 'World Cup', game_code: '3012', category_id: 'slot', img: base + '-en-1762738594.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Lucky Dog', name_en: 'Lucky Dog', game_code: '3013', category_id: 'slot', img: base + '-en-1762740736.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Dragon vs Tiger', name_en: 'Dragon vs Tiger', game_code: '3014', category_id: 'slot', img: base + '-en-1762738652.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Treasure Marmosets', name_en: 'Treasure Marmosets', game_code: '3015', category_id: 'slot', img: base + '-en-1762738709.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Samba Dance', name_en: 'Samba Dance', game_code: '3016', category_id: 'slot', img: base + '-en-1762738764.webp', platform_name: 'WG', app_state: 1 },
      { name: 'The Vault', name_en: 'The Vault', game_code: '3017', category_id: 'slot', img: base + '-en-1762738825.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Festival of the Saints', name_en: 'Festival of the Saints', game_code: '3018', category_id: 'slot', img: base + '-en-1762740612.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Treasure Bowl', name_en: 'Treasure Bowl', game_code: '3019', category_id: 'slot', img: base + '-en-1762738220.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Animal Kingdom', name_en: 'Animal Kingdom', game_code: '3020', category_id: 'slot', img: base + '-en-1762738275.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Fishing Master', name_en: 'Fishing Master', game_code: '3022', category_id: 'slot', img: base + '-en-1762738484.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Fortune Dragon', name_en: 'Fortune Dragon', game_code: '3023', category_id: 'slot', img: base + '-en-1762738159.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Pirates Treasure', name_en: 'Pirates Treasure', game_code: '3024', category_id: 'slot', img: base + '-en-1762737921.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Black Myth: Wukong', name_en: 'Black Myth: Wukong', game_code: '3025', category_id: 'slot', img: base + '-en-1762738539.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Crazy777', name_en: 'Crazy777', game_code: '3026', category_id: 'slot', img: base + '-en-1762737975.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Dragon\'s Treasure2', name_en: 'Dragon\'s Treasure2', game_code: '3028', category_id: 'slot', img: base + '-en-1762738101.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Mahjong Ways', name_en: 'Mahjong Ways', game_code: '3029', category_id: 'slot', img: base + '-en-1762738040.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Fortune Tiger', name_en: 'Fortune Tiger', game_code: '3031', category_id: 'slot', img: base + '-en-1762737685.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Mahjong Ways2', name_en: 'Mahjong Ways2', game_code: '3032', category_id: 'slot', img: base + '-en-1762737749.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Fortune Ox', name_en: 'Fortune Ox', game_code: '3033', category_id: 'slot', img: base + '-en-1762737807.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Fortune Rabbit', name_en: 'Fortune Rabbit', game_code: '3035', category_id: 'slot', img: base + '-en-1762737862.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Queen of Bounty', name_en: 'Queen of Bounty', game_code: '3036', category_id: 'slot', img: base + '-en-1762737610.webp', platform_name: 'WG', app_state: 1 },
      { name: 'Wild Bounty', name_en: 'Wild Bounty', game_code: '3037', category_id: 'slot', img: base + '-en-1762737524.webp', platform_name: 'WG', app_state: 1 }
    ];
  }

  function normalizeWxGames(data) {
    return normalizeGameRows(data, { onlyPlatform: 'WG' });
  }

  function normalizeSiteGames(data) {
    return normalizeGameRows(data, {});
  }

  function normalizeGameRows(data, options) {
    var rows = extractGameRows(data);
    var seen = {};
    var onlyPlatform = String(options && options.onlyPlatform || '').toUpperCase();

    return rows.map(function (row, index) {
      if (!row || Number(row.app_state || 1) !== 1) {
        return null;
      }

      var platform = row.platform_name || row.platform_code || row.api_code || 'WG';
      if (onlyPlatform && String(platform).toUpperCase() !== onlyPlatform) {
        return null;
      }

      var code = String(row.game_code || row.gamecode || row.code || row.id || '').trim();
      var key = String(platform).toUpperCase() + ':' + code;
      if (!code || seen[key]) {
        return null;
      }
      seen[key] = true;

      var image = wxGameImage(row);
      if (!image) {
        return null;
      }

      var type = String(row.category_id || row.game_type || row.type_code || 'slot').toLowerCase();
      var title = String(row.name || row.name_en || row.gamename || ('WG-' + code)).trim();
      return {
        id: row.id || key,
        key: key,
        title: title,
        image: image,
        img: image,
        platform: platform,
        type: type,
        code: code,
        category: wxCategoryLabel(type, row.category_name || row.type_name),
        group: gameDisplayGroup(row, platform, type),
        href: gameLaunchUrl(platform, type, code),
        hot: Number(row.is_hot || 0) === 1 || index < 8,
        fresh: Number(row.is_new || 0) === 1,
        recommend: Number(row.is_recommend || 0) === 1 || index < 12,
        rank: index + 1
      };
    }).filter(Boolean);
  }

  function gameDisplayGroup(row, platform, type) {
    var vendor = String(platform || '').toUpperCase();
    var raw = String(type || '').toLowerCase();
    var typeName = String(row.category_name || row.type_name || row.game_type || row.type_code || '').toLowerCase();

    if (/fish|fishing/.test(raw + ' ' + typeName)) {
      return 'fish';
    }
    if (vendor === 'SPRIBE' || vendor === 'INOUT') {
      return 'mini';
    }
    if (/table|poker|card|chess/.test(raw + ' ' + typeName)) {
      return 'table';
    }
    if (/slot|slots|rng/.test(raw + ' ' + typeName)) {
      return 'slot';
    }
    return 'other';
  }

  function wxGameImage(row) {
    var fields = ['img', 'gamepic', 'app_img', 'mobile_img', 'api_logo_img'];
    for (var index = 0; index < fields.length; index += 1) {
      var url = safeWebUrl(row[fields[index]], true);
      if (url) {
        return url;
      }
    }
    return '';
  }

  function wxCategoryLabel(type, configuredLabel) {
    var label = String(configuredLabel || '').trim();
    if (label && !/[\u3400-\u9FFF\uFFFD]/.test(label)) {
      return label;
    }

    var labels = {
      slot: '老虎机',
      slots: '老虎机',
      concise: '老虎机',
      fishing: '捕鱼游戏',
      fish: '捕鱼游戏',
      poker: '棋牌',
      joker: '棋牌',
      chess: '棋牌',
      sport: '体育',
      gaming: '游戏',
      esport: '电竞',
      realbet: '真人娱乐',
      live: '真人娱乐',
      lottery: '彩票'
    };
    return labels[type] || '游戏';
  }

  function wxPageConfig() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    var params = new URLSearchParams(location.search || '');
    var keyword = String(params.get('keyword') || '').trim();
    var map = {
      '/gaming': {
        key: 'all',
        title: '游戏大厅',
        subtitle: '接入我们自己的游戏数据，覆盖老虎机、棋牌、捕鱼和热门厂商。',
        active: 'gaming',
        filter: function () { return true; }
      },
      '/joker': {
        key: 'slot',
        title: '老虎机',
        subtitle: '来自 PG、JILI、TADA、JDB、WG 等厂商的真实游戏。',
        active: 'joker',
        filter: function (game) { return game.group === 'slot'; }
      },
      '/sport': {
        key: 'mini',
        title: '体育 / 小游戏',
        subtitle: '展示站内已接入的体育、小游戏和高速玩法。',
        active: 'sport',
        filter: function (game) { return game.group === 'mini'; }
      },
      '/realbet': {
        key: 'table',
        title: '真人 / 棋牌',
        subtitle: '展示真实接入的真人、棋牌和桌面游戏。',
        active: 'realbet',
        filter: function (game) { return game.group === 'table'; }
      },
      '/lottery': {
        key: 'providers',
        title: '全部厂商',
        subtitle: '按 PG、JILI、TADA、JDB、INOUT、WG、SPRIBE 等厂商浏览。',
        active: 'lottery',
        filter: function () { return true; }
      }
    };

    var config = map[path] || map['/gaming'];
    config.keyword = keyword;
    return config;
  }

  function filterWxGames(games, config) {
    var list = games.filter(config.filter || function () { return true; });

    if (config.keyword) {
      var query = config.keyword.toLowerCase();
      var searched = list.filter(function (game) {
        return game.title.toLowerCase().indexOf(query) !== -1 ||
          game.code.toLowerCase().indexOf(query) !== -1 ||
          game.category.toLowerCase().indexOf(query) !== -1 ||
          String(game.platform || '').toLowerCase().indexOf(query) !== -1;
      });
      list = searched;
    }

    return list;
  }

  function renderHomeWxSide(games) {
    var showcase = homeShowcaseGames(games, 10);
    var side = document.querySelector('.side');
    if (side) {
      side.setAttribute('data-wxgame-side', 'ready');
      side.innerHTML = showcase.map(function (game, index) {
        return [
          '<a class="side-card side-card--game" href="' + escapeAttribute(game.href) + '">',
          '<span class="side-left"><span class="side-icon side-icon--real"><img src="' + escapeAttribute(game.image) + '" alt="' + escapeAttribute(game.title) + '" loading="lazy" data-wxgame-img></span>',
          '<span><b>' + escapeHtml(index < 3 ? '热门' : String(game.platform).toUpperCase()) + '</b>' + escapeHtml(game.title) + '</span></span>',
          '<span class="arrow">&rsaquo;</span>',
          '</a>'
        ].join('');
      }).join('');
      bindWxGameImageFallbacks(side);
    }

    var accountList = document.querySelector('.account-list');
    if (accountList) {
      accountList.setAttribute('data-wxgame-side', 'ready');
      accountList.innerHTML = showcase.slice(0, 6).map(function (game, index) {
        return [
          '<a class="account-item account-item--game" href="' + escapeAttribute(game.href) + '">',
          '<span class="account-left"><span class="account-icon account-icon--real"><img src="' + escapeAttribute(game.image) + '" alt="' + escapeAttribute(game.title) + '" loading="lazy" data-wxgame-img></span>',
          '<span><b>' + escapeHtml(index < 2 ? '热门' : String(game.platform).toUpperCase()) + '</b>' + escapeHtml(game.title) + '</span></span>',
          '<span class="go">&rsaquo;</span>',
          '</a>'
        ].join('');
      }).join('');
      bindWxGameImageFallbacks(accountList);
    }
  }

  function renderHomeWxTabs(games) {
    var tabs = document.querySelector('.tabs');
    if (!tabs) {
      return;
    }

    var categories = wxHomeTabs(games);
    tabs.setAttribute('data-wxgame-tabs', 'ready');
    tabs.innerHTML = categories.map(function (item, index) {
      return '<a class="tab' + (index === 0 ? ' active' : '') + '" href="' + escapeAttribute(item.href) + '">' + escapeHtml(item.label) + '</a>';
    }).join('');
  }

  function wxHomeTabs(games) {
    var counts = gameGroupCounts(games || []);
    return [
      { label: '全部 ' + counts.all, href: '/gaming' },
      { label: '老虎机 ' + counts.slot, href: '/joker' },
      { label: '棋牌 ' + counts.table, href: '/realbet' },
      { label: '小游戏 ' + counts.mini, href: '/sport' },
      { label: '捕鱼 ' + counts.fish, href: '/gaming?keyword=Fishing' },
      { label: '平台 ' + counts.platforms, href: '/lottery' }
    ];
  }

  function renderHomeWxPromoGrid(games) {
    var grids = Array.prototype.slice.call(document.querySelectorAll('.promo-grid'));
    grids.forEach(function (grid) {
      var isMobile = grid.tagName.toLowerCase() === 'section';
      var items = homeShowcaseGames(games, isMobile ? 6 : 8);
      grid.setAttribute('data-wxgame-grid', 'ready');
      grid.innerHTML = items.map(function (game) {
        if (isMobile) {
          return '<a class="promo promo--game" href="' + escapeAttribute(game.href) + '"><img src="' + escapeAttribute(game.image) + '" alt="' + escapeAttribute(game.title) + '" decoding="async" loading="lazy" data-wxgame-img><div class="promo-info"><b>' + escapeHtml(game.title) + '</b><span class="more">进入</span></div></a>';
        }
        return '<article class="promo-card promo-card--game promo-card--' + escapeAttribute(game.group || 'game') + '"><a href="' + escapeAttribute(game.href) + '"><img class="pic" src="' + escapeAttribute(game.image) + '" alt="' + escapeAttribute(game.title) + '" decoding="async" loading="lazy" data-wxgame-img><div class="promo-body"><span class="promo-title">' + escapeHtml(game.title) + '</span><span class="read-more">立即游戏</span></div></a></article>';
      }).join('');
      bindWxGameImageFallbacks(grid);
    });
  }

  function homeShowcaseGames(games, limit) {
    var list = Array.isArray(games) ? games.slice() : [];
    list.sort(function (a, b) {
      var scoreA = (a.hot ? 30 : 0) + (a.recommend ? 20 : 0) + (a.fresh ? 10 : 0) - (a.rank || 0) / 1000;
      var scoreB = (b.hot ? 30 : 0) + (b.recommend ? 20 : 0) + (b.fresh ? 10 : 0) - (b.rank || 0) / 1000;
      return scoreB - scoreA;
    });
    var target = limit || list.length;
    var buckets = {};
    var platforms = [];
    list.forEach(function (game) {
      var platform = String(game.platform || 'OTHER').toUpperCase();
      if (!buckets[platform]) {
        buckets[platform] = [];
        platforms.push(platform);
      }
      buckets[platform].push(game);
    });
    var result = [];
    while (result.length < target && platforms.length) {
      platforms = platforms.filter(function (platform) {
        var next = buckets[platform].shift();
        if (next && result.indexOf(next) === -1) {
          result.push(next);
        }
        return buckets[platform].length > 0 && result.length < target;
      });
    }
    list.some(function (game) {
      if (result.length >= target) {
        return true;
      }
      if (result.indexOf(game) === -1) {
        result.push(game);
      }
      return false;
    });
    return result.slice(0, target);
  }

  function gameGroupCounts(games) {
    var platforms = {};
    var counts = { all: games.length, slot: 0, table: 0, fish: 0, mini: 0, platforms: 0 };
    games.forEach(function (game) {
      if (counts[game.group] != null) {
        counts[game.group] += 1;
      }
      if (game.platform) {
        platforms[String(game.platform).toUpperCase()] = true;
      }
    });
    counts.platforms = Object.keys(platforms).length;
    return counts;
  }

  function renderHomeWxMobileBottom() {
    var bottom = document.querySelector('.bottom');
    if (!bottom || bottom.getAttribute('data-wxgame-bottom') === 'ready') return;
    bottom.setAttribute('data-wxgame-bottom', 'ready');
    bottom.innerHTML = [
      '<a class="bn active" href="/new-h5/"><i>' + icon('home') + '</i>首页</a>',
      '<a class="bn" href="/activity"><i>' + icon('gift') + '</i>活动</a>',
      '<a class="bn" href="/gaming"><i>' + icon('gamepad') + '</i>游戏</a>',
      '<a class="bn" href="#" data-customer-service><i>' + icon('headphones') + '</i>客服</a>',
      '<a class="bn" href="/member/center"><i>' + icon('user') + '</i>会员</a>'
    ].join('');
  }

  function renderWxGameCatalogPage() {
    var config = wxPageConfig();
    var root = document.querySelector('main') || document.body;
    root.className = 'wxgame-page-host';
    root.setAttribute('data-wxgame-host', 'ready');
    root.innerHTML = renderWxCatalogShell(config);
    fetchSiteGames().then(function (games) {
      if (!games.length) games = normalizeWxGames(fallbackWxGames());
      var filtered = filterWxGames(games, config);
      var shell = document.querySelector('[data-wxgame-page]');
      if (!shell) return;
      shell.setAttribute('data-wxgame-count', String(filtered.length));
      shell.querySelector('[data-wxgame-total]').textContent = filtered.length + ' / ' + games.length + ' 款游戏';
      shell.querySelector('[data-wxgame-grid]').innerHTML = renderWxCatalogGames(filtered);
      shell.querySelector('[data-wxgame-rail]').innerHTML = renderWxCatalogRail(games);
      shell.querySelector('.wxgame-tabs').innerHTML = renderWxCatalogTabs(config, games);
      bindWxGameImageFallbacks(shell);
      bindWxCatalogSearch(shell, games, config);
    }).catch(function () {
      var games = normalizeWxGames(fallbackWxGames());
      var filtered = filterWxGames(games, config);
      var shell = document.querySelector('[data-wxgame-page]');
      if (shell) {
        shell.setAttribute('data-wxgame-count', String(filtered.length));
        shell.querySelector('[data-wxgame-total]').textContent = filtered.length + ' / ' + games.length + ' 款游戏';
        shell.querySelector('[data-wxgame-grid]').innerHTML = renderWxCatalogGames(filtered);
        shell.querySelector('[data-wxgame-rail]').innerHTML = renderWxCatalogRail(games);
        bindWxGameImageFallbacks(shell);
        bindWxCatalogSearch(shell, games, config);
      }
    });
  }

  function scheduleWxCatalogGuard() {
    window.setTimeout(function () {
      if (isGameCatalogPath() && !document.querySelector('[data-wxgame-page]')) {
        renderWxGameCatalogPage();
      }
    }, 450);
    window.setTimeout(function () {
      if (isGameCatalogPath() && !document.querySelector('[data-wxgame-page]')) {
        renderWxGameCatalogPage();
      }
    }, 1600);
  }

  function renderWxCatalogShell(config) {
    return [
      '<section class="wxgame-page" data-wxgame-page>',
      '<div class="wxgame-hero"><div class="wxgame-hero__copy"><span>游戏大厅</span><h1>' + escapeHtml(config.title) + '</h1><p>' + escapeHtml(config.subtitle) + '</p></div>',
      '<form class="wxgame-search" data-wxgame-search><input type="search" value="' + escapeAttribute(config.keyword || '') + '" placeholder="搜索游戏"><button type="submit">' + icon('search') + '</button></form></div>',
      '<div class="wxgame-tabs">' + renderWxCatalogTabs(config, []) + '</div>',
      '<div class="wxgame-layout"><aside class="wxgame-rail"><strong>热门游戏</strong><div data-wxgame-rail></div></aside>',
      '<section class="wxgame-results"><div class="wxgame-results__head"><strong>真实游戏列表</strong><span data-wxgame-total>正在加载</span></div><div class="wxgame-grid" data-wxgame-grid>' + renderWxSkeletonCards() + '</div></section></div>',
      '</section>'
    ].join('');
  }

  function renderWxCatalogTabs(config, games) {
    return wxHomeTabs(games || []).map(function (item) {
      var active = item.href.indexOf('/' + config.active) === 0 || (config.active === 'gaming' && item.href === '/gaming');
      return '<a class="' + (active ? 'active' : '') + '" href="' + escapeAttribute(item.href) + '">' + escapeHtml(item.label) + '</a>';
    }).join('');
  }

  function renderWxSkeletonCards() {
    var html = [];
    for (var index = 0; index < 8; index += 1) {
      html.push('<div class="wxgame-card wxgame-card--loading"><span></span><b></b><i></i></div>');
    }
    return html.join('');
  }

  function renderWxCatalogGames(games) {
    if (!games.length) return '<div class="wxgame-empty">当前分类暂无游戏</div>';
    return games.map(function (game) {
      var badges = [game.hot ? '<span>热门</span>' : '', game.fresh ? '<span>新品</span>' : '', game.recommend ? '<span>推荐</span>' : ''].join('');
      return '<a class="wxgame-card" href="' + escapeAttribute(game.href) + '"><span class="wxgame-card__image"><img src="' + escapeAttribute(game.image) + '" alt="' + escapeAttribute(game.title) + '" loading="lazy" data-wxgame-img></span><span class="wxgame-card__badges">' + badges + '</span><strong>' + escapeHtml(game.title) + '</strong><small>' + escapeHtml(game.category) + ' / ' + escapeHtml(game.code) + '</small><em>立即游戏</em></a>';
    }).join('');
  }

  function renderWxCatalogRail(games) {
    return games.slice(0, 9).map(function (game) {
      return [
        '<a class="wxgame-rail-game" href="' + escapeAttribute(game.href) + '">',
        '<img src="' + escapeAttribute(game.image) + '" alt="' + escapeAttribute(game.title) + '" loading="lazy" data-wxgame-img>',
        '<span>' + escapeHtml(game.title) + '<small>' + escapeHtml(game.category) + '</small></span>',
        '</a>'
      ].join('');
    }).join('');
  }

  function renderNoticePage() {
    var fallbackCards = [
      noticeCard('系统公告', '充值与提现通道正常运行，若遇到延迟请优先联系客服处理。', '今日'),
      noticeCard('游戏公告', 'WXGAME 游戏列表已接入真实数据，首页和分类页会持续使用本站游戏。', '最新'),
      noticeCard('活动公告', '首次打开网站会展示活动弹窗，活动中心可查看全部优惠和申请规则。', '活动'),
      noticeCard('客服公告', '右侧电话按钮可展开多种联系方式，也可以进入工单页面提交问题。', '客服')
    ];
    document.body.innerHTML = ['<main class="notice-shell"><header class="member-tool-top notice-top"><a class="brand-logo" href="/"><span class="brand-logo__main">TH2.VIP</span><span class="brand-logo__sub">在线游戏</span></a><a class="member-tool-back" href="/">返回首页</a></header><section class="member-tool-hero notice-hero"><div><span>网站公告</span><h1>公告中心</h1><p>系统、游戏、活动和客服信息集中展示。</p></div><div class="member-balance-card"><span>TH2.VIP</span><strong>NOTICE</strong><small>实时更新</small></div></section><section class="notice-grid" data-notice-grid>', fallbackCards.join(''), '</section></main>'].join('');
    localizeVisibleCopy(document.body);
    hydrateNoticeCards();
  }

  function noticeCard(title, text, tag) {
    return '<article class="notice-card"><span>' + escapeHtml(tag) + '</span><h2>' + escapeHtml(title) + '</h2><p>' + escapeHtml(text) + '</p></article>';
  }

  function hydrateNoticeCards() {
    var grid = document.querySelector('[data-notice-grid]');
    if (!grid) {
      return;
    }

    postJson('/api/homenotice', {}, authHeaders(currentAuthToken()))
      .then(function (payload) {
        var rows = responseData(payload) || [];
        if (!Array.isArray(rows) || rows.length === 0) {
          return;
        }
        grid.innerHTML = rows.slice(0, 12).map(function (item, index) {
          var title = cleanPlainText(item.name || item.title || '网站公告', '网站公告');
          var text = cleanPlainText(item.content || item.message || item.text || '', '请查看公告详情。');
          return noticeCard(title, text, index === 0 ? '最新' : '公告');
        }).join('');
        localizeVisibleCopy(grid);
      })
      .catch(function () {});
  }

  function cleanPlainText(value, fallback) {
    var div = document.createElement('div');
    div.innerHTML = String(value || '');
    var text = (div.textContent || div.innerText || '').replace(/\s+/g, ' ').trim();
    return text || fallback || '';
  }

  function bindWxCatalogSearch(shell, games, config) {
    var form = shell.querySelector('[data-wxgame-search]');
    if (!form) return;
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var input = form.querySelector('input');
      var nextConfig = Object.assign({}, config, { keyword: input ? input.value.trim() : '' });
      var filtered = filterWxGames(games, nextConfig);
      shell.querySelector('[data-wxgame-grid]').innerHTML = renderWxCatalogGames(filtered);
      shell.setAttribute('data-wxgame-count', String(filtered.length));
      shell.querySelector('[data-wxgame-total]').textContent = filtered.length + ' / ' + games.length + ' 款游戏';
      bindWxGameImageFallbacks(shell);
    });
  }

  function bindWxGameImageFallbacks(scope) {
    Array.prototype.forEach.call((scope || document).querySelectorAll('img[data-wxgame-img]'), function (image) {
      if (image.getAttribute('data-wxgame-img-bound') === '1') {
        return;
      }
      image.setAttribute('data-wxgame-img-bound', '1');
      image.addEventListener('error', function () {
        renderWxGameImageFallback(image);
      });
      if (image.complete && image.naturalWidth === 0) {
        renderWxGameImageFallback(image);
      }
    });
  }

  function renderWxGameImageFallback(image) {
    if (!image || image.getAttribute('data-wxgame-img-fallback') === '1') {
      return;
    }
    image.setAttribute('data-wxgame-img-fallback', '1');
    var label = String(image.getAttribute('alt') || 'GAME').trim();
    var fallback = document.createElement('span');
    fallback.className = 'game-image-missing';
    fallback.textContent = gameImageInitials(label);
    fallback.setAttribute('aria-label', label);
    image.replaceWith(fallback);
  }

  function gameImageInitials(label) {
    var words = String(label || 'GAME').replace(/[^\w\s]/g, ' ').trim().split(/\s+/).filter(Boolean);
    if (!words.length) {
      return 'GAME';
    }
    return words.slice(0, 2).map(function (word) {
      return word.charAt(0).toUpperCase();
    }).join('');
  }

  function safeWebUrl(value, asset) {
    var raw = String(value || '').trim();
    if (!raw) {
      return '';
    }
    if (!asset && raw.charAt(0) === '/') {
      return raw.indexOf('//') === 0 ? '' : raw;
    }

    try {
      var url = new URL(raw, location.origin);
      return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : '';
    } catch (error) {
      return '';
    }
  }

  function safeContactUrl(value) {
    var raw = String(value || '').trim();
    if (/^tel:\+?[0-9()\-\s]{5,30}$/i.test(raw)) {
      return raw;
    }
    return safeWebUrl(raw, false);
  }

  function safeColor(value) {
    var color = String(value || '').trim();
    return /^#[0-9a-f]{3,8}$/i.test(color) || /^rgb(a)?\([0-9,\s.]+\)$/i.test(color)
      ? color
      : '#dff3ff';
  }

  function renderCarousel(root, banners) {
    carousel.root = root; carousel.count = banners.length; carousel.index = 0;
    root.classList.remove('game-hero'); root.classList.remove('game-banner'); root.classList.add('home-carousel'); root.setAttribute('aria-label', '活动轮播');
    root.innerHTML = ['<div class="home-carousel__viewport"><div class="home-carousel__track">', banners.map(function (banner, index) { var image = '<img src="' + escapeAttribute(banner.src) + '" alt="TH2.VIP 活动 ' + (index + 1) + '" ' + (index === 0 ? 'loading="eager"' : 'loading="lazy"') + '>'; var content = banner.href ? '<a href="' + escapeAttribute(banner.href) + '">' + image + '</a>' : image; return '<div class="home-carousel__slide" aria-hidden="' + (index === 0 ? 'false' : 'true') + '" style="background:' + escapeAttribute(banner.background) + '">' + content + '</div>'; }).join(''), '</div></div>', banners.length > 1 ? '<button class="home-carousel__arrow prev" type="button" data-carousel-prev aria-label="上一张">' + icon('chevron-left') + '</button>' : '', banners.length > 1 ? '<button class="home-carousel__arrow next" type="button" data-carousel-next aria-label="下一张">' + icon('chevron-right') + '</button>' : '', banners.length > 1 ? '<div class="home-carousel__dots">' + banners.map(function (_, index) { return '<button type="button" data-carousel-dot="' + index + '" aria-label="轮播图 ' + (index + 1) + '" class="' + (index === 0 ? 'active' : '') + '"></button>'; }).join('') + '</div>' : ''].join('');
    carousel.track = root.querySelector('.home-carousel__track'); carousel.dots = Array.prototype.slice.call(root.querySelectorAll('[data-carousel-dot]'));
    if (banners.length < 2) return;
    root.querySelector('[data-carousel-prev]').addEventListener('click', function () { showSlide(carousel.index - 1); restartCarousel(); });
    root.querySelector('[data-carousel-next]').addEventListener('click', function () { showSlide(carousel.index + 1); restartCarousel(); });
    carousel.dots.forEach(function (dot) { dot.addEventListener('click', function () { showSlide(Number(dot.getAttribute('data-carousel-dot'))); restartCarousel(); }); });
    var viewport = root.querySelector('.home-carousel__viewport');
    viewport.addEventListener('touchstart', function (event) { carousel.touchStartX = event.changedTouches[0].clientX; stopCarousel(); }, { passive: true });
    viewport.addEventListener('touchend', function (event) { var distance = event.changedTouches[0].clientX - carousel.touchStartX; if (Math.abs(distance) > 40) showSlide(carousel.index + (distance < 0 ? 1 : -1)); startCarousel(); }, { passive: true });
    root.addEventListener('mouseenter', stopCarousel); root.addEventListener('mouseleave', startCarousel); root.addEventListener('focusin', stopCarousel); root.addEventListener('focusout', startCarousel); startCarousel();
  }

  function showSlide(index) {
    if (!carousel.track || !carousel.count) {
      return;
    }

    carousel.index = (index + carousel.count) % carousel.count;
    carousel.track.style.transform = 'translate3d(-' + (carousel.index * 100) + '%,0,0)';

    Array.prototype.forEach.call(
      carousel.root.querySelectorAll('.home-carousel__slide'),
      function (slide, slideIndex) {
        slide.setAttribute('aria-hidden', slideIndex === carousel.index ? 'false' : 'true');
      }
    );
    carousel.dots.forEach(function (dot, dotIndex) {
      dot.classList.toggle('active', dotIndex === carousel.index);
    });
  }

  function startCarousel() {
    if (carousel.count < 2 || carousel.timer) {
      return;
    }
    carousel.timer = window.setInterval(function () {
      showSlide(carousel.index + 1);
    }, 5000);
  }

  function stopCarousel() {
    if (carousel.timer) {
      window.clearInterval(carousel.timer);
      carousel.timer = null;
    }
  }

  function restartCarousel() {
    stopCarousel();
    startCarousel();
  }

  function initFloatingSupport() {
    var root = document.createElement('aside');
    root.className = 'floating-support';
    root.innerHTML = ['<div class="floating-support__contacts" data-contact-panel hidden><div class="floating-support__contacts-head"><strong>联系方式</strong><button type="button" data-contact-close aria-label="关闭">' + icon('x') + '</button></div><div class="floating-support__contact-list" data-contact-list></div></div>', '<button class="floating-support__phone" type="button" data-contact-toggle aria-label="打开联系方式" aria-expanded="false">' + icon('phone-call') + '</button>', '<button class="floating-support__top" type="button" data-back-to-top aria-label="返回顶部" hidden>' + icon('chevrons-up') + '</button>', '<div class="floating-support__promo" data-floating-promo><button class="floating-support__promo-close" type="button" data-floating-promo-close aria-label="关闭活动">' + icon('x') + '</button><a href="/promotions" aria-label="查看活动"><img src="/assets/promotions/referral-banner.webp" alt="会员活动"></a></div>', '<a class="floating-support__online" data-online-service data-customer-service href="#" aria-label="联系客服"><span class="floating-support__online-icon">' + icon('headphones') + '</span><span>联系客服</span></a>'].join('');
    document.body.appendChild(root);
    var panel = root.querySelector('[data-contact-panel]');
    var toggle = root.querySelector('[data-contact-toggle]');
    var topButton = root.querySelector('[data-back-to-top]');
    var promo = root.querySelector('[data-floating-promo]');
    var promoKey = 'th2w:floating-promo:closed';
    if (promo && localStorage.getItem(promoKey) === '1') {
      promo.hidden = true;
    }
    toggle.addEventListener('click', function () { setContactPanel(panel.hidden, panel, toggle); });
    root.querySelector('[data-contact-close]').addEventListener('click', function () { setContactPanel(false, panel, toggle); });
    document.addEventListener('click', function (event) { if (!panel.hidden && !root.contains(event.target)) setContactPanel(false, panel, toggle); });
    document.addEventListener('keydown', function (event) { if (event.key === 'Escape') setContactPanel(false, panel, toggle); });
    topButton.addEventListener('click', function () { window.scrollTo({ top: 0, behavior: 'smooth' }); });
    window.addEventListener('scroll', function () { topButton.hidden = window.scrollY < 320; }, { passive: true });
    if (promo) promo.querySelector('[data-floating-promo-close]').addEventListener('click', function () { localStorage.setItem(promoKey, '1'); promo.hidden = true; });
    bindCustomerServiceTriggers();
    renderCustomerServices(root, { services: [] }, { skipCache: true });
    fetchCustomerServicePayload()
      .then(function (payload) {
        renderCustomerServices(root, payload || {});
      })
      .catch(function () {});
  }

  function setContactPanel(open, panel, toggle) {
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.classList.toggle('active', open);
  }

  function renderCustomerServices(root, payload, options) {
    payload = payload || {};
    options = options || {};
    if (!options.skipCache) {
      customerServicePayloadCache = payload;
    }
    root.__customerServicePayload = payload;
    var list = root.querySelector('[data-contact-list]');
    var toggle = root.querySelector('[data-contact-toggle]');
    var online = root.querySelector('[data-online-service]');
    var rows = Array.isArray(payload.services) ? payload.services : [];
    var contacts = rows.map(normalizeContact).filter(Boolean);
    var onlineUrl = resolveCustomerServiceUrl(payload);

    contacts = ensureDefaultContacts(contacts, onlineUrl);

    toggle.hidden = contacts.length === 0;
    list.innerHTML = contacts.map(function (contact) {
      var external = /^https?:/i.test(contact.url);
      return [
        '<a class="floating-support__contact floating-support__contact--' + escapeAttribute(contact.type) + '" href="' + escapeAttribute(contact.url) + '"' + (external ? ' target="_blank" rel="noopener noreferrer"' : '') + '>',
        '<span class="floating-support__contact-icon">' + icon(contactIcon(contact.type)) + '</span>',
        '<span><strong>' + escapeHtml(contact.label) + '</strong><small>' + escapeHtml(contactCaption(contact.type)) + '</small></span>',
        icon('chevron-right'),
        '</a>'
      ].join('');
    }).join('');

    if (onlineUrl) {
      online.href = onlineUrl;
      online.hidden = false;
      if (/^https?:/i.test(onlineUrl)) {
        online.target = '_blank';
        online.rel = 'noopener noreferrer';
      } else {
        online.removeAttribute('target');
        online.removeAttribute('rel');
      }
    } else {
      online.hidden = true;
    }
  }

  function ensureDefaultContacts(contacts, onlineUrl) {
    var normalized = Array.isArray(contacts) ? contacts.slice() : [];
    var fallbackUrl = onlineUrl || '';
    var defaults = [
      { type: 'online', label: '在线客服', url: fallbackUrl },
      { type: 'line', label: 'LINE 客服', url: fallbackUrl },
      { type: 'telegram', label: 'Telegram 客服', url: fallbackUrl },
      { type: 'vip', label: 'VIP 专属客服', url: fallbackUrl }
    ];
    defaults.forEach(function (item) {
      var hasType = normalized.some(function (contact) { return contact.type === item.type; });
      if (!hasType && item.url) normalized.push(item);
    });
    return normalized;
  }

  function bindCustomerServiceTriggers() {
    if (window.__th2wCustomerServiceTriggersBound) {
      return;
    }
    window.__th2wCustomerServiceTriggersBound = true;
    document.addEventListener('click', function (event) {
      var trigger = closestCustomerServiceTrigger(event.target);
      if (!trigger) {
        return;
      }
      event.preventDefault();
      fetchCustomerServicePayload()
        .then(function (payload) { openCustomerService(payload); })
        .catch(function () { openCustomerService(customerServicePayloadCache || {}); });
    });
  }

  function closestCustomerServiceTrigger(node) {
    while (node && node !== document) {
      if (node.getAttribute && node.getAttribute('data-customer-service') !== null) {
        return node;
      }
      node = node.parentNode;
    }
    return null;
  }

  function fetchCustomerServicePayload() {
    if (customerServicePayloadCache && resolveCustomerServiceUrl(customerServicePayloadCache)) {
      return Promise.resolve(customerServicePayloadCache);
    }
    if (!customerServiceFetchPromise) {
      customerServiceFetchPromise = postJson('/api/getservicerurl', {}, authHeaders(currentAuthToken()))
        .then(function (payload) {
          customerServicePayloadCache = responseData(payload) || {};
          return customerServicePayloadCache;
        })
        .catch(function (error) {
          customerServiceFetchPromise = null;
          throw error;
        });
    }
    return customerServiceFetchPromise;
  }

  function openCustomerService(payload) {
    var url = resolveCustomerServiceUrl(payload || customerServicePayloadCache || {});
    if (!url) {
      return false;
    }
    if (/^https?:/i.test(url)) {
      window.open(url, '_blank', 'noopener,noreferrer');
    } else {
      window.location.href = url;
    }
    return true;
  }

  function resolveCustomerServiceUrl(payload) {
    payload = payload || {};
    if (payload.customer_service && typeof payload.customer_service === 'object') {
      payload = Object.assign({}, payload, payload.customer_service);
    }
    var candidates = [
      payload.livechat_url,
      payload.realtime_url,
      payload.service_url,
      payload.kf_url,
      payload.url,
      payload.service_link,
      payload.fallback_url,
      payload.work_order_page_url
    ];
    if (Array.isArray(payload.services)) {
      payload.services.forEach(function (service) {
        if (service && typeof service === 'object') {
          candidates.push(service.url || service.link || service.service_url);
        }
      });
    }
    candidates.push('/support/work-orders.html');
    for (var i = 0; i < candidates.length; i += 1) {
      var url = safeContactUrl(candidates[i]);
      if (url) {
        return url;
      }
    }
    return '';
  }

  function normalizeContact(item) {
    if (!item) {
      return null;
    }

    var type = String(item.service_type || 'custom')
      .toLowerCase()
      .replace(/[^a-z0-9_-]/g, '') || 'custom';
    var url = safeContactUrl(item.service_url);
    if (!url) {
      return null;
    }

    return {
      type: type,
      label: contactLabel(type, item.display_name),
      url: url
    };
  }

  function contactLabel(type, configuredLabel) {
    var label = String(configuredLabel || '').trim();
    var hasThai = /[\u0E00-\u0E7F]/.test(label);
    var hasBrokenText = /[\uFFFD]/.test(label);
    if (label && !hasThai && !hasBrokenText) return label;
    var labels = { phone: '电话客服', line: 'LINE 客服', telegram: 'Telegram 客服', facebook: 'Facebook 客服', messenger: 'Messenger 客服', whatsapp: 'WhatsApp 客服', instagram: 'Instagram 客服', online: '在线客服', vip: 'VIP 专属客服', custom: '联系方式' };
    return labels[type] || labels.custom;
  }

  function contactCaption(type) {
    var captions = { phone: '每日在线服务', line: '打开 LINE 联系', telegram: '打开 Telegram 联系', facebook: '打开 Facebook 联系', messenger: '打开 Messenger 联系', whatsapp: '打开 WhatsApp 联系', instagram: '打开 Instagram 联系', online: '打开在线客服', vip: '联系客服', custom: '联系客服' };
    return captions[type] || captions.custom;
  }

  function contactIcon(type) {
    var icons = {
      phone: 'phone',
      line: 'messages-square',
      telegram: 'send',
      facebook: 'message-circle',
      messenger: 'message-circle',
      whatsapp: 'message-circle',
      instagram: 'camera',
      online: 'headphones',
      vip: 'crown',
      custom: 'link'
    };
    return icons[type] || icons.custom;
  }

  function icon(name) {
    var paths = {
      'phone-call': '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.33 1.85.56 2.81.69A2 2 0 0 1 22 16.92z"/><path d="M14.5 2.5a5 5 0 0 1 5 5"/><path d="M14.5 6.5a1 1 0 0 1 1 1"/>',
      phone: '<path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6A19.79 19.79 0 0 1 2.12 4.18 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13.96.36 1.9.69 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.91.33 1.85.56 2.81.69A2 2 0 0 1 22 16.92z"/>',
      headphones: '<path d="M4 14a8 8 0 0 1 16 0"/><path d="M18 19c0 1.7-1.3 3-3 3h-1"/><path d="M4 14v4a2 2 0 0 0 2 2h1v-8H6a2 2 0 0 0-2 2z"/><path d="M20 14v4a2 2 0 0 1-2 2h-1v-8h1a2 2 0 0 1 2 2z"/>',
      'chevrons-up': '<path d="m17 11-5-5-5 5"/><path d="m17 18-5-5-5 5"/>',
      'chevron-left': '<path d="m15 18-6-6 6-6"/>',
      'chevron-right': '<path d="m9 18 6-6-6-6"/>',
      x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>',
      send: '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
      'message-circle': '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/>',
      'messages-square': '<path d="M10 16H5a3 3 0 0 1-3-3V6a3 3 0 0 1 3-3h11a3 3 0 0 1 3 3v1"/><path d="m2 16 3-3"/><path d="M14 9h5a3 3 0 0 1 3 3v3a3 3 0 0 1-3 3h-2l-3 3z"/>',
      camera: '<path d="M14.5 4h-5L7.7 6H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-3.7z"/><circle cx="12" cy="13" r="3"/>',
      crown: '<path d="m2 7 5 5 5-9 5 9 5-5-2 12H4Z"/><path d="M5 22h14"/>',
      home: '<path d="m3 10 9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/>',
      gift: '<path d="M20 12v10H4V12"/><path d="M2 7h20v5H2z"/><path d="M12 22V7"/><path d="M12 7H7.5A2.5 2.5 0 1 1 10 4.5c0 1.5 2 2.5 2 2.5z"/><path d="M12 7h4.5A2.5 2.5 0 1 0 14 4.5c0 1.5-2 2.5-2 2.5z"/>',
      gamepad: '<path d="M6 11h4"/><path d="M8 9v4"/><path d="M15 12h.01"/><path d="M18 10h.01"/><path d="M17.3 5H6.7A4.7 4.7 0 0 0 2 9.7v5.6A3.7 3.7 0 0 0 5.7 19c1 0 1.9-.4 2.6-1.1L10 16h4l1.7 1.9A3.7 3.7 0 0 0 22 15.3V9.7A4.7 4.7 0 0 0 17.3 5z"/>',
      search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
      user: '<path d="M20 21a8 8 0 0 0-16 0"/><circle cx="12" cy="7" r="4"/>',
      link: '<path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>'
    };
    return '<svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">' + (paths[name] || paths.link) + '</svg>';
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function escapeAttribute(value) {
    return escapeHtml(value);
  }

  function localizeVisibleCopy(root) {
    return root || document.body;
  }
}());
