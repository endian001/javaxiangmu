(function () {
  var core = window.TH2WPromotionCore || fallbackPromotionCore();

  var state = {
    categories: [],
    activities: [],
    activeType: 0,
    channel: window.innerWidth <= 640 || location.pathname.indexOf('/new-h5') === 0 ? 'mobile' : 'desktop',
    root: null,
    listNode: null,
    tabsNode: null
  };

  var copy = {
    pageTitle: 'Promotion Center - TH2.VIP',
    brandSub: 'Online games',
    home: 'Home',
    games: 'Games',
    promotions: 'Promotions',
    mobile: 'Mobile',
    login: 'Login',
    register: 'Register',
    heroTitle: 'Promotions and member rewards',
    heroText: 'Latest TH2.VIP campaigns are listed here. Open a campaign to view details and claim rewards.',
    depositNow: 'Deposit now',
    memberBonus: 'Member rewards',
    bonusNote: 'Claim according to campaign rules',
    featureTitle: 'Featured promotions',
    all: 'All',
    countSuffix: 'items',
    loading: 'Loading promotions...',
    loadError: 'Promotions failed to load. Please try again later.',
    empty: 'No promotions in this category',
    notFound: 'Promotion not found',
    close: 'Close',
    rules: 'Promotion rules',
    details: 'View details',
    apply: 'Claim promotion',
    applySuccess: 'Promotion request submitted',
    applyFailed: 'Submit failed. Please try again later.',
    support: 'Contact support',
    allPromotions: 'All promotions',
    popupText: 'Member promotion. Open the details to claim it.',
    fallbackContent: '<p>Select a promotion and submit a request according to the rules. Support will review the request.</p>',
    fallbackMemo: '<p>Please read the rules before claiming. Contact support if you need help.</p>'
  };

  var thaiCopy = {
    close: '\u0e1b\u0e34\u0e14',
    depositNow: '\u0e1d\u0e32\u0e01\u0e40\u0e07\u0e34\u0e19\u0e15\u0e2d\u0e19\u0e19\u0e35\u0e49',
    support: '\u0e15\u0e34\u0e14\u0e15\u0e48\u0e2d\u0e1d\u0e48\u0e32\u0e22\u0e1a\u0e23\u0e34\u0e01\u0e32\u0e23',
    allPromotions: '\u0e42\u0e1b\u0e23\u0e42\u0e21\u0e0a\u0e31\u0e19\u0e17\u0e31\u0e49\u0e07\u0e2b\u0e21\u0e14',
    popupText: '\u0e42\u0e1b\u0e23\u0e42\u0e21\u0e0a\u0e31\u0e19\u0e2a\u0e33\u0e2b\u0e23\u0e31\u0e1a\u0e2a\u0e21\u0e32\u0e0a\u0e34\u0e01 \u0e40\u0e1b\u0e34\u0e14\u0e23\u0e32\u0e22\u0e25\u0e30\u0e40\u0e2d\u0e35\u0e22\u0e14\u0e40\u0e1e\u0e37\u0e48\u0e2d\u0e23\u0e31\u0e1a\u0e2a\u0e34\u0e17\u0e18\u0e34\u0e4c'
  };

  function bootPromotionSystem() {
    document.documentElement.setAttribute('data-promotion-system', 'ready');
    if (core.isPromotionPath(location.pathname)) {
      renderPromotionPage();
      return;
    }
    initHomePopup();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootPromotionSystem);
  } else {
    bootPromotionSystem();
  }

  window.addEventListener('popstate', function () {
    if (!core.isPromotionPath(location.pathname)) {
      return;
    }
    var id = new URL(location.href).searchParams.get('id');
    if (id) {
      openDetail(id, false, 'direct_link');
    } else {
      closeDetail(false);
    }
  });

  function api(path, options) {
    var separator = path.indexOf('?') === -1 ? '?' : '&';
    return requestJson(path + separator + 'channel=' + encodeURIComponent(state.channel), options || {});
  }

  function requestJson(url, options) {
    options = options || {};
    if (typeof fetch === 'function') {
      return fetch(url, options)
        .then(function (response) {
          return response.json().catch(function () {
            return { code: response.status, message: response.statusText || 'Request failed' };
          });
        });
    }

    return new Promise(function (resolve, reject) {
      var xhr = new XMLHttpRequest();
      xhr.open(options.method || 'GET', url, true);
      var headers = options.headers || {};
      Object.keys(headers).forEach(function (key) {
        xhr.setRequestHeader(key, headers[key]);
      });
      xhr.onreadystatechange = function () {
        if (xhr.readyState !== 4) {
          return;
        }
        var text = xhr.responseText || '';
        try {
          resolve(text ? JSON.parse(text) : { code: xhr.status, data: null });
        } catch (error) {
          resolve({ code: xhr.status, message: xhr.statusText || 'Request failed' });
        }
      };
      xhr.onerror = function () {
        reject(new Error('Request failed'));
      };
      xhr.send(options.body || null);
    });
  }

  function renderPromotionPage() {
    document.documentElement.classList.add('promotion-page-mode');
    document.body.classList.add('promotion-page-mode');
    document.title = copy.pageTitle;

    var root = document.createElement('main');
    root.className = 'promotion-center';
    root.innerHTML = [
      '<header class="promo-top">',
      '<a class="promo-brand" href="/"><strong>TH2.VIP</strong><span>' + copy.brandSub + '</span></a>',
      '<nav class="promo-links"><a href="/">' + copy.home + '</a><a href="/gaming">' + copy.games + '</a><a class="active" href="/promotions">' + copy.promotions + '</a><a href="/new-h5/">' + copy.mobile + '</a></nav>',
      '<div class="promo-actions"><a href="/login">' + copy.login + '</a><a class="gold" href="/register">' + copy.register + '</a></div>',
      '</header>',
      '<section class="promo-hero">',
      '<div><span>TH2.VIP</span><h1>' + copy.heroTitle + '</h1><p>' + copy.heroText + '</p></div>',
      '<a class="promo-hero-card" href="/member/recharge"><small>' + copy.depositNow + '</small><strong>' + copy.memberBonus + '</strong><em>' + copy.bonusNote + '</em></a>',
      '</section>',
      '<section class="promo-feature" data-promo-feature></section>',
      '<section class="promo-board">',
      '<div class="promo-board-head"><h2>' + copy.featureTitle + '</h2><div class="promo-count" data-promo-count>0 ' + copy.countSuffix + '</div></div>',
      '<div class="promo-tabs" data-promo-tabs></div>',
      '<div class="promo-list" data-promo-list><div class="promo-state">' + copy.loading + '</div></div>',
      '</section>'
    ].join('');

    document.body.appendChild(root);
    state.root = root;
    state.tabsNode = root.querySelector('[data-promo-tabs]');
    state.listNode = root.querySelector('[data-promo-list]');

    Promise.all([api('/api/promotions/categories'), api('/api/promotions')])
      .then(function (results) {
        state.categories = normalizeArray(core.responseData(results[0]));
        state.activities = localizePromotionList(normalizeArray(core.responseData(results[1])));
        if (!state.activities.length) {
          state.activities = fallbackActivities();
        }
        if (!state.categories.length && state.activities.length) {
          state.categories = fallbackCategoriesFromActivities(state.activities);
        }
        state.activeType = resolveInitialType();
        renderFeature();
        renderTabs();
        renderList();
        var id = new URL(location.href).searchParams.get('id');
        if (id) {
          openDetail(id, false, 'direct_link');
        }
      })
      .catch(function () {
        state.activities = fallbackActivities();
        state.categories = fallbackCategoriesFromActivities(state.activities);
        state.activeType = resolveInitialType();
        renderFeature();
        renderTabs();
        renderList();
      });
  }

  function hasBrokenText(value) {
    var text = String(value || '');
    if (/\uFFFD|\?{3,}/.test(text)) {
      return true;
    }
    var tokens = [
      '\u68e3\u6813', '\u5a32\u8bf2', '\u5a13\u544a', '\u7027\u6fb6', '\u6d7c\u6c2c',
      '\u935c\u5728', '\u6748\u65c2', '\u9411', '\u942a', '\u59dd', '\u8930',
      '\u93ba', '\u7ad4', '\u9471', '\u93cc', '\u93b5', '\u947f\u6edd',
      '\u9427\u8bf2', '\u6ce8\u5a49', '\u9390\u7d22', '\u9470\u4f79',
      '\u93b9\u66de', '\u942a\u7194', '\u6d63\u64b6', '\u93b5\u5b0b',
      '\u7ad4\u5b2a', '\u59dd\u57ae', '\u8930\u6493'
    ];
    return tokens.some(function (token) {
      return text.indexOf(token) !== -1;
    });
  }

  function needsFallbackText(value) {
    var text = String(value == null ? '' : value).trim();
    return !text || hasBrokenText(text);
  }

  function localizePromotionItem(item) {
    if (!item) {
      return item;
    }
    var source = Object.assign({}, item);
    source.type_name = cleanText(source.type_name || source.entype_name || source.category_name, fallbackPromotionType(source));
    source.category_name = cleanText(source.category_name || source.type_name, fallbackPromotionType(source));
    source.title = cleanText(source.title || source.entitle || source.name, fallbackPromotionTitle(source));
    source.entitle = cleanText(source.entitle || source.title, source.title);
    source.button_text = cleanText(source.button_text, '');
    source.content = cleanRichText(source.content || source.encontent, '');
    source.encontent = cleanRichText(source.encontent || source.content, source.content);
    source.memo = cleanRichText(source.memo || source.enmemo || source.rule || source.rules, '');
    source.enmemo = cleanRichText(source.enmemo || source.memo, source.memo);
    source.rule = cleanRichText(source.rule || source.memo, source.memo);
    source.rules = cleanRichText(source.rules || source.memo, source.memo);
    return source;
  }

  function localizePromotionList(items) {
    return (items || []).map(function (item) {
      return localizePromotionItem(item);
    });
  }

  function fallbackActivities() {
    return [
      {
        id: 'fallback-welcome',
        type: 1,
        type_name: 'New member bonus',
        title: 'Welcome gift for new members',
        entitle: 'New member bonus',
        content: '<p>Register and complete the first deposit to claim the new member reward.</p>',
        memo: '<p>Only real member accounts are eligible. Final approval is based on support review.</p>',
        banner_image: '/assets/promotions/welcome-banner.png',
        card_image: '/assets/promotions/welcome-banner.png',
        popup_image: '/assets/promotions/welcome-banner.png',
        button_text: 'Claim promotion'
      },
      {
        id: 'fallback-deposit',
        type: 2,
        type_name: 'Deposit promotion',
        title: 'Daily deposit rewards',
        entitle: 'Deposit bonus',
        content: '<p>After depositing, members can submit a request for extra rewards according to the campaign rules.</p>',
        memo: '<p>Turnover requirements must be completed before withdrawal.</p>',
        banner_image: '/assets/promotions/deposit-banner.png',
        card_image: '/assets/promotions/deposit-banner.png',
        detail_image: '/assets/promotions/deposit-detail.png',
        button_text: 'Deposit now'
      },
      {
        id: 'fallback-referral',
        type: 3,
        type_name: 'Invite friends',
        title: 'Invite friends and earn rewards',
        entitle: 'Referral bonus',
        content: '<p>Invite friends to register and meet the requirements. The inviter can submit a reward request.</p>',
        memo: '<p>Each invited friend can only count once as a valid referral.</p>',
        banner_image: '/assets/promotions/referral-banner.webp',
        card_image: '/assets/promotions/referral-banner.webp',
        detail_image: '/assets/promotions/referral-detail.jpg',
        button_text: 'View details'
      }
    ];
  }

  function fallbackCategoriesFromActivities(items) {
    var seen = {};
    return (items || []).map(function (item) {
      var id = Number(item.type || item.type_id || 0);
      if (!id || seen[id]) {
        return null;
      }
      seen[id] = true;
      return { id: id, name: promotionTypeName(item) };
    }).filter(Boolean);
  }
  function normalizeArray(value) {
    if (Array.isArray(value)) {
      return value;
    }
    if (!value || typeof value !== 'object') {
      return [];
    }
    return ['data', 'list', 'rows', 'items', 'records'].reduce(function (found, key) {
      return found.length ? found : (Array.isArray(value[key]) ? value[key] : []);
    }, []);
  }

  function resolveInitialType() {
    var params = new URL(location.href).searchParams;
    var raw = params.get('type') || params.get('category') || '';
    if (!raw) {
      return 0;
    }
    if (/^\d+$/.test(raw)) {
      return Number(raw);
    }
    var query = raw.toLowerCase();
    var matched = state.categories.find(function (category) {
      return String(category.slug || category.code || category.name || category.enname || '').toLowerCase().indexOf(query) !== -1;
    });
    return matched ? Number(matched.id || 0) : 0;
  }

  function renderFeature() {
    var feature = state.root && state.root.querySelector('[data-promo-feature]');
    if (!feature) {
      return;
    }
    var items = state.activities.slice(0, 3);
    if (!items.length) {
      feature.innerHTML = '';
      return;
    }
    feature.innerHTML = [
      '<div class="promo-feature__main">',
      renderFeatureCard(items[0], true),
      '</div>',
      '<div class="promo-feature__side">',
      items.slice(1, 3).map(function (item) { return renderFeatureCard(item, false); }).join(''),
      '</div>'
    ].join('');
    if (!feature.dataset.bound) {
      feature.dataset.bound = '1';
      feature.addEventListener('click', function (event) {
        var button = event.target.closest('[data-promo-id]');
        if (button) {
          openDetail(button.dataset.promoId, true, 'promotion_feature');
        }
      });
    }
  }

  function renderFeatureCard(item, large) {
    var image = promotionImage(item, large ? 'banner' : 'card');
    return [
      '<button type="button" class="promo-feature-card' + (large ? ' large' : '') + '" data-promo-id="' + Number(item.id || 0) + '">',
      '<img src="' + escapeAttribute(image || fallbackPromotionImage(item, large ? 'banner' : 'card')) + '" alt="' + escapeAttribute(promotionTitle(item)) + '" loading="lazy">',
      '<span><small>' + escapeHtml(promotionTypeName(item)) + '</small><strong>' + escapeHtml(promotionTitle(item)) + '</strong><em>' + escapeHtml(buttonText(item)) + '</em></span>',
      '</button>'
    ].join('');
  }

  function renderTabs() {
    var categories = [{ id: 0, name: copy.all }].concat(state.categories.filter(function (category) {
      return Number(category.id || 0) !== 0;
    }));
    state.tabsNode.innerHTML = categories.map(function (category) {
      var id = Number(category.id || 0);
      return '<button type="button" class="promo-tab' + (id === state.activeType ? ' active' : '') + '" data-type="' + id + '">' + escapeHtml(cleanText(category.name || category.enname, copy.promotions)) + '</button>';
    }).join('');
    if (!state.tabsNode.dataset.bound) {
      state.tabsNode.dataset.bound = '1';
      state.tabsNode.addEventListener('click', function (event) {
        var button = event.target.closest('[data-type]');
        if (!button) {
          return;
        }
        state.activeType = Number(button.dataset.type || 0);
        renderTabs();
        renderList();
      });
    }
  }

  function renderList() {
    var filtered = state.activeType
      ? state.activities.filter(function (item) { return Number(item.type || item.type_id || 0) === state.activeType; })
      : state.activities.slice();

    state.root.querySelector('[data-promo-count]').textContent = filtered.length + ' ' + copy.countSuffix;
    if (!filtered.length) {
      state.listNode.innerHTML = '<div class="promo-state">' + copy.empty + '</div>';
      return;
    }

    state.listNode.innerHTML = filtered.map(cardTemplate).join('');
    if (!state.listNode.dataset.bound) {
      state.listNode.dataset.bound = '1';
      state.listNode.addEventListener('click', function (event) {
        var button = event.target.closest('[data-promo-id]');
        if (button) {
          openDetail(button.dataset.promoId, true, 'promotion_center');
        }
      });
    }
  }

  function cardTemplate(item) {
    var image = promotionImage(item, 'card');
    return [
      '<article class="promotion-card">',
      '<img src="' + escapeAttribute(image || fallbackPromotionImage(item, 'card')) + '" alt="' + escapeAttribute(promotionTitle(item)) + '" loading="lazy">',
      '<div class="promotion-card-body">',
      '<span>' + escapeHtml(promotionTypeName(item)) + '</span>',
      '<h3>' + escapeHtml(promotionTitle(item)) + '</h3>',
      '<button type="button" data-promo-id="' + Number(item.id || 0) + '">' + escapeHtml(buttonText(item)) + '</button>',
      '</div>',
      '</article>'
    ].join('');
  }

  function openDetail(id, pushUrl, source) {
    if (!id) {
      return;
    }
    api('/api/promotions/' + encodeURIComponent(id))
      .then(function (payload) {
        var item = core.responseData(payload);
        if (!item || Number(payload.code) === 404) {
          showDetailError(copy.notFound);
          return;
        }
        renderDetail(item);
        recordExposure(item.id, source || 'direct_link');
        if (pushUrl) {
          history.pushState({ promotionId: item.id }, '', buildDetailUrl('/promotions', item.id));
        }
      })
      .catch(function () {
        showDetailError(copy.loadError);
      });
  }

  function renderDetail(item) {
    closeDetail(false);
    var banner = promotionImage(item, 'banner');
    var modal = document.createElement('section');
    modal.className = 'promo-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.innerHTML = [
      '<div class="promo-modal-backdrop" data-close-detail></div>',
      '<div class="promo-modal-panel">',
      '<button class="promo-close" type="button" data-close-detail aria-label="' + copy.close + '">x</button>',
      '<div class="promo-detail-scroller">',
      banner ? '<img class="promo-modal-banner" src="' + escapeAttribute(banner) + '" alt="' + escapeAttribute(promotionTitle(item)) + '">' : '',
      '<div class="promo-modal-content">',
      '<div class="promo-detail-heading"><span>' + escapeHtml(promotionTypeName(item)) + '</span><h2>' + escapeHtml(promotionTitle(item)) + '</h2></div>',
      renderDetailArtwork(item),
      '<div class="promo-rich">' + sanitizeRichText(promotionContent(item)) + '</div>',
      promotionMemo(item) ? '<h3>' + copy.rules + '</h3><div class="promo-rich">' + sanitizeRichText(promotionMemo(item)) + '</div>' : '',
      actionButton(item),
      '</div></div></div>'
    ].join('');
    document.body.appendChild(modal);
    modal.addEventListener('click', function (event) {
      if (event.target.closest('[data-close-detail]')) {
        closeDetail(true);
      }
      if (event.target.closest('[data-promo-action]')) {
        event.preventDefault();
        handleAction(item);
      }
    });
    document.body.classList.add('promo-modal-open');
  }

  function renderDetailArtwork(item) {
    var image = promotionImage(item, 'detail') || fallbackPromotionImage(item, 'detail');
    if (image && image !== promotionImage(item, 'banner')) {
      return '<div class="promo-detail-artwork"><img class="promo-detail-image" src="' + escapeAttribute(image) + '" alt="' + escapeAttribute(promotionTitle(item)) + '" loading="eager"></div>';
    }

    var deposit = /deposit|recharge|充值|存款/i.test(promotionTypeName(item) + ' ' + promotionTitle(item));
    var steps = deposit
      ? ['选择充值渠道', '按指定金额完成提交', '等待系统或客服审核福利']
      : ['登录或注册会员', '打开活动详情', '提交申请并等待审核'];

    return [
      '<div class="promo-detail-artwork promo-detail-fallback' + (deposit ? ' deposit' : ' welcome') + '">',
      '<img class="promo-detail-image" src="' + escapeAttribute(image) + '" alt="' + escapeAttribute(promotionTitle(item)) + '" loading="eager">',
      '<div class="promo-detail-overlay"><p class="promo-fallback-eyebrow">' + (deposit ? '充值奖励' : '会员福利') + '</p>',
      '<strong>' + escapeHtml(promotionTitle(item)) + '</strong>',
      '<div class="promo-fallback-steps">',
      steps.map(function (step, index) {
        return '<div><b>' + (index + 1) + '</b><span>' + escapeHtml(step) + '</span></div>';
      }).join(''),
      '</div><p>' + copy.fallbackMemo.replace(/<\/?p>/g, '') + '</p></div></div>'
    ].join('');
  }

  function fallbackPromotionImage(item, mode) {
    var text = (promotionTypeName(item) + ' ' + promotionTitle(item)).toLowerCase();
    if (/deposit|recharge|充值|存款/.test(text)) {
      return mode === 'detail' ? '/assets/promotions/deposit-detail.png' : '/assets/promotions/deposit-banner.png';
    }
    if (/referral|invite|邀请|好友/.test(text)) {
      return mode === 'detail' ? '/assets/promotions/referral-detail.jpg' : '/assets/promotions/referral-banner.webp';
    }
    return mode === 'detail' ? '/assets/promotions/welcome-banner.png' : '/assets/promotions/welcome-banner.png';
  }

  function actionButton(item) {
    var label = buttonText(item);
    var requiresAuth = Number(item.requires_auth || 0) === 1;
    var url = safeActionUrl(item.action_url);
    if (url && !requiresAuth) {
      var external = /^https?:\/\//i.test(url);
      return '<a class="promo-primary" href="' + escapeAttribute(url) + '"' + (external ? ' target="_blank" rel="noopener noreferrer"' : '') + '>' + escapeHtml(label) + '</a>';
    }
    if (url || Number(item.can_apply || 0) === 1 || requiresAuth) {
      return '<button type="button" class="promo-primary" data-promo-action>' + escapeHtml(label) + '</button>';
    }
    return '<a class="promo-primary" href="/support/work-orders.html">' + copy.support + '</a>';
  }

  function handleAction(item) {
    var targetUrl = safeActionUrl(item.action_url);
    var token = authToken();
    if ((Number(item.requires_auth || 0) === 1 || Number(item.can_apply || 0) === 1) && !token) {
      location.href = '/login?redirect=' + encodeURIComponent(targetUrl || buildDetailUrl('/promotions', item.id));
      return;
    }
    if (targetUrl) {
      location.href = targetUrl;
      return;
    }
    if (Number(item.can_apply || 0) !== 1) {
      location.href = '/support/work-orders.html';
      return;
    }
    applyPromotion(item, token);
  }

  function applyPromotion(item, token) {
    requestApply('/api/promotions/' + encodeURIComponent(item.id) + '/apply', token, {})
      .then(function (payload) {
        if (Number(payload.code) === 404) {
          return requestApply('/api/doactivityapply', token, { activityid: item.id });
        }
        return payload;
      })
      .then(function (payload) {
        var ok = Number(payload.code) === 200;
        alert(ok ? copy.applySuccess : cleanText(payload.msg || payload.message, copy.applyFailed));
      })
      .catch(function () {
        alert(copy.applyFailed);
      });
  }

  function requestApply(path, token, body) {
    return requestJson(path, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': token.indexOf('Bearer ') === 0 ? token : 'Bearer ' + token
      },
      body: JSON.stringify(body || {})
    });
  }

  function showDetailError(message) {
    closeDetail(false);
    var modal = document.createElement('section');
    modal.className = 'promo-modal';
    modal.innerHTML = '<div class="promo-modal-backdrop" data-close-detail></div><div class="promo-modal-panel small"><button class="promo-close" type="button" data-close-detail>x</button><div class="promo-state">' + escapeHtml(message) + '</div></div>';
    document.body.appendChild(modal);
    modal.addEventListener('click', function (event) {
      if (event.target.closest('[data-close-detail]')) {
        closeDetail(true);
      }
    });
  }

  function closeDetail(updateUrl) {
    var modal = document.querySelector('.promo-modal');
    if (modal) {
      modal.remove();
    }
    document.body.classList.remove('promo-modal-open');
    if (updateUrl) {
      history.pushState({}, '', clearDetailUrl(location.href));
    }
  }

  function initHomePopup() {
    if (location.pathname.indexOf('/support/') === 0 || location.pathname === '/login' || location.pathname === '/register') {
      return;
    }
    document.documentElement.setAttribute('data-promotion-popup', 'requesting');
    api('/api/promotions/popup')
      .then(function (payload) {
        var item = localizePromotionItem(normalizeHomePopupItem(core.responseData(payload)), 0);
        document.documentElement.setAttribute('data-promotion-popup-code', String(payload && payload.code || ''));
        if (!item || !item.id || !shouldDisplayHomePopupNow(item)) {
          document.documentElement.setAttribute('data-promotion-popup', !item ? 'no-item' : (!item.id ? 'no-id' : 'gated'));
          return;
        }
        document.documentElement.setAttribute('data-promotion-popup', 'scheduled');
        document.documentElement.setAttribute('data-promotion-popup-id', String(item.id));
        document.documentElement.setAttribute('data-promotion-popup-frequency', String(item.popup_frequency || item.frequency || 'once'));
        window.setTimeout(function () {
          renderHomePopup(item);
        }, Math.max(0, Number(item.popup_delay_seconds || 0)) * 1000);
      })
      .catch(function () {
        document.documentElement.setAttribute('data-promotion-popup', 'error');
      });
  }

  function renderHomePopup(item) {
    if (document.querySelector('.promo-home-popup')) {
      document.documentElement.setAttribute('data-promotion-popup', 'already-rendered');
      return;
    }
    var viewCopy = copyForPromotion(item);
    var image = promotionImage(item, 'popup') || promotionImage(item, 'banner') || '/assets/promotions/welcome-banner.png';
    var modal = document.createElement('section');
    modal.className = 'promo-home-popup';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.innerHTML = [
      '<div class="promo-home-backdrop" data-popup-close></div>',
      '<div class="promo-home-panel promo-home-panel--split">',
      '<button class="promo-close" type="button" data-popup-close aria-label="' + viewCopy.close + '">x</button>',
      '<aside class="promo-home-menu">',
      '<button class="active" type="button" data-popup-open><small>TH2.VIP</small><strong>' + escapeHtml(promotionTitle(item)) + '</strong></button>',
      '<a href="/promotions">' + viewCopy.allPromotions + '</a>',
      '<a href="/member/recharge">' + viewCopy.depositNow + '</a>',
      '<a href="/support/work-orders.html">' + viewCopy.support + '</a>',
      '</aside>',
      '<div class="promo-home-main">',
      '<button class="promo-home-image" type="button" data-popup-open><img src="' + escapeAttribute(image) + '" alt="' + escapeAttribute(promotionTitle(item)) + '"></button>',
      '<div class="promo-home-body"><span class="promo-home-badge">' + escapeHtml(promotionTypeName(item)) + '</span><h2>' + escapeHtml(promotionTitle(item)) + '</h2>',
      '<p>' + viewCopy.popupText + '</p>',
      '<div class="promo-home-actions"><button type="button" data-popup-close>' + viewCopy.close + '</button><button type="button" data-popup-open>' + escapeHtml(buttonText(item)) + '</button></div></div>',
      '</div></div>'
    ].join('');
    document.body.appendChild(modal);
    document.documentElement.setAttribute('data-promotion-popup', 'rendered');
    markHomePopupDisplayed(item);
    recordExposure(item.id, 'home_popup');
    modal.addEventListener('click', function (event) {
      if (event.target.closest('[data-popup-close]')) {
        modal.remove();
      }
      if (event.target.closest('[data-popup-open]')) {
        modal.remove();
        location.href = buildDetailUrl('/promotions', item.id);
      }
    });
  }

  function normalizeHomePopupItem(item) {
    return Array.isArray(item) ? (item[0] || null) : item;
  }

  function copyForPromotion(item) {
    return hasThaiText([
      item && item.type_name,
      item && item.category_name,
      item && item.title,
      item && item.entitle,
      item && item.button_text,
      item && item.content,
      item && item.memo
    ].join(' ')) ? Object.assign({}, copy, thaiCopy) : copy;
  }

  function hasThaiText(value) {
    return /[\u0E00-\u0E7F]/.test(String(value || ''));
  }

  function shouldDisplayHomePopupNow(item) {
    if (location.search.indexOf('promo_preview=1') !== -1 || location.search.indexOf('force_popup=1') !== -1) {
      return true;
    }
    var frequency = String(item.popup_frequency || item.frequency || 'once').toLowerCase();
    if (frequency === 'always') {
      return true;
    }
    var key = homePopupStorageKey(item);
    if (frequency === 'once') {
      return localStorage.getItem(key) !== '1';
    }
    if (frequency === 'daily') {
      return localStorage.getItem(key) !== todayKey();
    }
    return sessionStorage.getItem(key) !== '1';
  }

  function markHomePopupDisplayed(item) {
    var frequency = String(item.popup_frequency || item.frequency || 'once').toLowerCase();
    var key = homePopupStorageKey(item);
    if (frequency === 'once') {
      localStorage.setItem(key, '1');
    } else if (frequency === 'daily') {
      localStorage.setItem(key, todayKey());
    } else if (frequency !== 'always') {
      sessionStorage.setItem(key, '1');
    }
  }

  function homePopupStorageKey(item) {
    return 'th2w:promo:home-popup:' + String(item.id || 'default');
  }

  function todayKey() {
    return new Date().toISOString().slice(0, 10);
  }

  function recordExposure(id, source) {
    if (!id) {
      return;
    }
    api('/api/promotions/' + encodeURIComponent(id) + '/exposure', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ source: source || 'promotion_center', channel: state.channel, session_key: getSessionKey() })
    }).catch(function () {});
  }

  function getSessionKey() {
    var key = sessionStorage.getItem('th2w:promo:session');
    if (!key) {
      key = Date.now() + '-' + Math.random().toString(16).slice(2);
      sessionStorage.setItem('th2w:promo:session', key);
    }
    return key;
  }

  function promotionImage(item, purpose) {
    var mobile = state.channel === 'mobile';
    var fields = purpose === 'popup'
      ? (mobile ? ['app_popup_image', 'popup_image', 'app_img', 'banner', 'detail_image'] : ['popup_image', 'banner', 'detail_image', 'app_popup_image'])
      : purpose === 'detail'
        ? (mobile ? ['app_detail_image', 'detail_image', 'app_img', 'banner'] : ['detail_image', 'banner', 'app_detail_image'])
        : purpose === 'banner'
          ? (mobile ? ['app_img', 'banner', 'popup_image'] : ['banner', 'app_img', 'popup_image'])
          : (mobile ? ['app_img', 'banner', 'popup_image', 'detail_image'] : ['banner', 'app_img', 'popup_image', 'detail_image']);
    for (var index = 0; index < fields.length; index += 1) {
      var url = safeAssetUrl(item && item[fields[index]]);
      if (url) {
        return url;
      }
    }
    return '';
  }

  function promotionTitle(item) {
    return cleanText(item && (item.entitle || item.title || item.name), 'TH2.VIP promotion');
  }

  function promotionTypeName(item) {
    return cleanText(item && (item.type_name || item.entype_name || item.category_name), fallbackPromotionType(item));
  }

  function promotionContent(item) {
    return cleanRichText(item && (item.encontent || item.content), copy.fallbackContent);
  }

  function promotionMemo(item) {
    return cleanRichText(item && (item.enmemo || item.memo), '');
  }

  function fallbackPromotionIndex(item) {
    var raw = Number(item && (item.type || item.type_id || item.id) || 0);
    if (!raw) {
      return 0;
    }
    return Math.abs(raw) % 3;
  }

  function fallbackPromotionType(item) {
    return ['New member bonus', 'Deposit promotion', 'Invite friends'][fallbackPromotionIndex(item)] || copy.promotions;
  }

  function fallbackPromotionTitle(item) {
    return ['Welcome gift for new members', 'Daily deposit rewards', 'Invite friends and earn rewards'][fallbackPromotionIndex(item)] || 'TH2.VIP promotion';
  }

  function buttonText(item) {
    var configured = cleanText(item && item.button_text, '');
    if (configured) {
      return configured;
    }
    var url = safeActionUrl(item && item.action_url);
    if (/recharge|deposit/i.test(url)) {
      return copy.depositNow;
    }
    if (/support|service/i.test(url)) {
      return copy.support;
    }
    return Number(item && item.can_apply || 0) === 1 ? copy.apply : copy.details;
  }

  function authToken() {
    return localStorage.getItem('token') || localStorage.getItem('api_token') || localStorage.getItem('Authorization') || '';
  }

  function cleanText(value, fallback) {
    var text = String(value == null ? '' : value).replace(/<[^>]*>/g, '').trim();
    return !text || hasBrokenText(text) ? fallback : text;
  }

  function cleanRichText(value, fallback) {
    var text = String(value == null ? '' : value).trim();
    return !text || hasBrokenText(text) ? fallback : text;
  }

  function buildDetailUrl(base, id) {
    return core.buildDetailUrl ? core.buildDetailUrl(base, id) : fallbackPromotionCore().buildDetailUrl(base, id);
  }

  function clearDetailUrl(base) {
    return core.clearDetailUrl ? core.clearDetailUrl(base) : fallbackPromotionCore().clearDetailUrl(base);
  }

  function safeAssetUrl(value) {
    var raw = String(value || '').trim();
    if (!raw) {
      return '';
    }
    try {
      var url = new URL(raw, location.origin);
      return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : '';
    } catch (error) {
      return '';
    }
  }

  function safeActionUrl(value) {
    var raw = String(value || '').trim();
    if (!raw) {
      return '';
    }
    if (raw.charAt(0) === '/' && raw.indexOf('//') !== 0) {
      return raw;
    }
    try {
      var url = new URL(raw, location.origin);
      return url.protocol === 'http:' || url.protocol === 'https:' ? url.href : '';
    } catch (error) {
      return '';
    }
  }

  function sanitizeRichText(html) {
    var raw = cleanRichText(html, '');
    if (!raw) {
      return '';
    }
    var template = document.createElement('template');
    template.innerHTML = raw;
    var allowedTags = {
      A: true, B: true, BR: true, DIV: true, EM: true, H2: true, H3: true, H4: true,
      I: true, IMG: true, LI: true, OL: true, P: true, SMALL: true, SPAN: true,
      STRONG: true, TABLE: true, TBODY: true, TD: true, TH: true, THEAD: true, TR: true,
      U: true, UL: true
    };
    Array.prototype.slice.call(template.content.querySelectorAll('*')).forEach(function (node) {
      if (!allowedTags[node.tagName]) {
        node.replaceWith(document.createTextNode(node.textContent || ''));
        return;
      }
      Array.prototype.slice.call(node.attributes).forEach(function (attribute) {
        var name = attribute.name.toLowerCase();
        if (name.indexOf('on') === 0 || name === 'style') {
          node.removeAttribute(attribute.name);
          return;
        }
        if (node.tagName === 'A' && name === 'href') {
          var href = safeActionUrl(attribute.value);
          if (href) {
            node.setAttribute('href', href);
            if (/^https?:\/\//i.test(href)) {
              node.setAttribute('target', '_blank');
              node.setAttribute('rel', 'noopener noreferrer');
            }
          } else {
            node.removeAttribute('href');
          }
          return;
        }
        if (node.tagName === 'IMG' && name === 'src') {
          var src = safeAssetUrl(attribute.value);
          if (src) {
            node.setAttribute('src', src);
          } else {
            node.remove();
          }
          return;
        }
        if (name !== 'class' && name !== 'colspan' && name !== 'rowspan' && name !== 'alt') {
          node.removeAttribute(attribute.name);
        }
      });
    });
    return template.innerHTML;
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function escapeAttribute(value) {
    return escapeHtml(value).replace(/`/g, '&#96;');
  }

  function fallbackPromotionCore() {
    return {
      isPromotionPath: function (path) {
        return ['/activity', '/activities', '/promotions'].indexOf(path.replace(/\/+$/, '') || '/') !== -1;
      },
      responseData: function (payload) {
        return payload && (payload.data || payload.list || payload.rows || payload);
      },
      buildDetailUrl: function (base, id) {
        var url = new URL(base, location.origin);
        url.pathname = '/promotions';
        url.searchParams.set('id', id);
        return url.pathname + url.search;
      },
      clearDetailUrl: function (base) {
        var url = new URL(base, location.origin);
        url.searchParams.delete('id');
        return url.pathname + url.search;
      }
    };
  }
})();
