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
    pageTitle: '\u6d3b\u52a8\u4e2d\u5fc3 - TH2.VIP',
    brandSub: '\u5728\u7ebf\u6e38\u620f',
    home: '\u9996\u9875',
    games: '\u6e38\u620f',
    promotions: '\u6d3b\u52a8',
    mobile: '\u624b\u673a\u7248',
    login: '\u767b\u5f55',
    register: '\u6ce8\u518c',
    heroTitle: '\u6d3b\u52a8\u4e0e\u4f1a\u5458\u5956\u52b1',
    heroText: '\u6700\u65b0\u6d3b\u52a8\u90fd\u5728\u8fd9\u91cc\uff0c\u6253\u5f00\u6d3b\u52a8\u67e5\u770b\u8be6\u60c5\u5e76\u6309\u89c4\u5219\u9886\u53d6\u5956\u52b1\u3002',
    depositNow: '\u7acb\u5373\u5145\u503c',
    memberBonus: '\u4f1a\u5458\u5956\u52b1',
    bonusNote: '\u6309\u6d3b\u52a8\u89c4\u5219\u9886\u53d6',
    featureTitle: '\u70ed\u95e8\u6d3b\u52a8',
    all: '\u5168\u90e8',
    countSuffix: '\u4e2a\u6d3b\u52a8',
    loading: '\u6b63\u5728\u52a0\u8f7d\u6d3b\u52a8...',
    loadError: '\u6d3b\u52a8\u52a0\u8f7d\u5931\u8d25\uff0c\u8bf7\u7a0d\u540e\u518d\u8bd5\u3002',
    empty: '\u8be5\u5206\u7c7b\u6682\u65e0\u6d3b\u52a8',
    notFound: '\u6d3b\u52a8\u4e0d\u5b58\u5728',
    close: '\u5173\u95ed',
    rules: '\u6d3b\u52a8\u89c4\u5219',
    details: '\u67e5\u770b\u8be6\u60c5',
    apply: '\u7533\u8bf7\u6d3b\u52a8',
    applySuccess: '\u6d3b\u52a8\u7533\u8bf7\u5df2\u63d0\u4ea4',
    applyFailed: '\u63d0\u4ea4\u5931\u8d25\uff0c\u8bf7\u7a0d\u540e\u518d\u8bd5\u3002',
    support: '\u8054\u7cfb\u5ba2\u670d',
    allPromotions: '\u5168\u90e8\u6d3b\u52a8',
    popupText: '\u4f1a\u5458\u6d3b\u52a8\uff0c\u6253\u5f00\u8be6\u60c5\u540e\u6309\u89c4\u5219\u9886\u53d6\u3002',
    fallbackContent: '<p>\u9009\u62e9\u8981\u53c2\u52a0\u7684\u6d3b\u52a8\uff0c\u5e76\u6309\u9875\u9762\u89c4\u5219\u63d0\u4ea4\u7533\u8bf7\uff0c\u7cfb\u7edf\u4f1a\u4ea4\u7ed9\u5ba2\u670d\u5ba1\u6838\u3002</p>',
    fallbackMemo: '<p>\u7533\u8bf7\u524d\u8bf7\u9605\u8bfb\u6d3b\u52a8\u89c4\u5219\uff0c\u5982\u6709\u7591\u95ee\u8bf7\u8054\u7cfb\u5ba2\u670d\u3002</p>'
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
    return requestJson(path + separator + 'channel=' + encodeURIComponent(state.channel) + '&locale=' + encodeURIComponent(currentLocale()), options || {});
  }

  function currentLocale() {
    try {
      return localStorage.getItem('th2w:locale') || 'zh-CN';
    } catch (error) {
      return 'zh-CN';
    }
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
        type_name: '\u65b0\u4eba\u5956\u52b1',
        title: '\u65b0\u4f1a\u5458\u6b22\u8fce\u793c',
        entitle: 'New member bonus',
        content: '<p>\u6ce8\u518c\u5e76\u5b8c\u6210\u9996\u5b58\u540e\uff0c\u53ef\u6309\u6d3b\u52a8\u89c4\u5219\u9886\u53d6\u65b0\u4eba\u5956\u52b1\u3002</p>',
        memo: '<p>\u4ec5\u9650\u771f\u5b9e\u4f1a\u5458\u8d26\u53f7\u53c2\u4e0e\uff0c\u6700\u7ec8\u4ee5\u5ba2\u670d\u5ba1\u6838\u4e3a\u51c6\u3002</p>',
        banner_image: '/assets/promotions/welcome-banner.png',
        card_image: '/assets/promotions/welcome-banner.png',
        popup_image: '/assets/promotions/welcome-banner.png',
        button_text: '\u7533\u8bf7\u6d3b\u52a8'
      },
      {
        id: 'fallback-deposit',
        type: 2,
        type_name: '\u5145\u503c\u6d3b\u52a8',
        title: '\u6bcf\u65e5\u5145\u503c\u5956\u52b1',
        entitle: 'Deposit bonus',
        content: '<p>\u4f1a\u5458\u5145\u503c\u540e\u53ef\u6309\u6d3b\u52a8\u89c4\u5219\u63d0\u4ea4\u989d\u5916\u5956\u52b1\u7533\u8bf7\u3002</p>',
        memo: '<p>\u63d0\u73b0\u524d\u9700\u6309\u6d3b\u52a8\u89c4\u5219\u5b8c\u6210\u6d41\u6c34\u8981\u6c42\u3002</p>',
        banner_image: '/assets/promotions/deposit-banner.png',
        card_image: '/assets/promotions/deposit-banner.png',
        detail_image: '/assets/promotions/deposit-detail.png',
        button_text: '\u7acb\u5373\u5145\u503c'
      },
      {
        id: 'fallback-referral',
        type: 3,
        type_name: '\u9080\u8bf7\u597d\u53cb',
        title: '\u9080\u8bf7\u597d\u53cb\u8d5a\u53d6\u5956\u52b1',
        entitle: 'Referral bonus',
        content: '<p>\u9080\u8bf7\u597d\u53cb\u6ce8\u518c\u5e76\u8fbe\u6210\u8981\u6c42\u540e\uff0c\u9080\u8bf7\u4eba\u53ef\u63d0\u4ea4\u5956\u52b1\u7533\u8bf7\u3002</p>',
        memo: '<p>\u6bcf\u4f4d\u88ab\u9080\u8bf7\u597d\u53cb\u4ec5\u53ef\u8ba1\u7b97\u4e00\u6b21\u6709\u6548\u9080\u8bf7\u3002</p>',
        banner_image: '/assets/promotions/referral-banner.webp',
        card_image: '/assets/promotions/referral-banner.webp',
        detail_image: '/assets/promotions/referral-detail.jpg',
        button_text: '\u67e5\u770b\u8be6\u60c5'
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
    return /^th/i.test(currentLocale()) ? Object.assign({}, copy, thaiCopy) : copy;
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
    return cleanText(item && (item.title || item.entitle || item.name), '\u6d3b\u52a8\u4e2d\u5fc3');
  }

  function promotionTypeName(item) {
    return cleanText(item && (item.type_name || item.entype_name || item.category_name), fallbackPromotionType(item));
  }

  function promotionContent(item) {
    return cleanRichText(item && (item.content || item.encontent), copy.fallbackContent);
  }

  function promotionMemo(item) {
    return cleanRichText(item && (item.memo || item.enmemo), '');
  }

  function fallbackPromotionIndex(item) {
    var raw = Number(item && (item.type || item.type_id || item.id) || 0);
    if (!raw) {
      return 0;
    }
    return Math.abs(raw) % 3;
  }

  function fallbackPromotionType(item) {
    return ['\u65b0\u4eba\u5956\u52b1', '\u5145\u503c\u6d3b\u52a8', '\u9080\u8bf7\u597d\u53cb'][fallbackPromotionIndex(item)] || copy.promotions;
  }

  function fallbackPromotionTitle(item) {
    return ['\u65b0\u4f1a\u5458\u6b22\u8fce\u793c', '\u6bcf\u65e5\u5145\u503c\u5956\u52b1', '\u9080\u8bf7\u597d\u53cb\u8d5a\u53d6\u5956\u52b1'][fallbackPromotionIndex(item)] || '\u6d3b\u52a8\u4e2d\u5fc3';
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
