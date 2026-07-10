(function () {
  var core = window.TH2WPromotionCore;
  if (!core) {
    return;
  }

  var state = {
    categories: [],
    activities: [],
    activeType: 0,
    channel: window.innerWidth <= 640 || location.pathname.indexOf('/new-h5') === 0 ? 'mobile' : 'desktop',
    root: null,
    listNode: null,
    tabsNode: null
  };

  document.addEventListener('DOMContentLoaded', function () {
    if (core.isPromotionPath(location.pathname)) {
      renderPromotionPage();
      return;
    }
    initHomePopup();
  });

  window.addEventListener('popstate', function () {
    if (core.isPromotionPath(location.pathname)) {
      var id = new URL(location.href).searchParams.get('id');
      if (id) {
        openDetail(id, false);
      } else {
        closeDetail(false);
      }
    }
  });

  function api(path, options) {
    var separator = path.indexOf('?') === -1 ? '?' : '&';
    return fetch(path + separator + 'channel=' + encodeURIComponent(state.channel), options || {})
      .then(function (response) { return response.json(); });
  }

  function renderPromotionPage() {
    document.documentElement.classList.add('promotion-page-mode');
    document.body.classList.add('promotion-page-mode');
    document.title = 'โปรโมชั่นทั้งหมด - TH2.VIP';

    var root = document.createElement('main');
    root.className = 'promotion-center';
    root.innerHTML = [
      '<header class="promo-top">',
      '<a class="promo-brand" href="/"><strong>TH2.VIP</strong><span>ONLINE GAMING</span></a>',
      '<nav class="promo-links"><a href="/">หน้าแรก</a><a href="/gaming">เกม</a><a class="active" href="/promotions">โปรโมชั่น</a><a href="/new-h5/">H5</a></nav>',
      '<div class="promo-actions"><a href="/login">ล็อกอิน</a><a class="gold" href="/register">สมัครสมาชิก</a></div>',
      '</header>',
      '<section class="promo-hero"><div><span>TH2.VIP</span><h1>โปรโมชั่นทั้งหมด</h1><p>เลือกสิทธิพิเศษที่เหมาะกับคุณ และอ่านรายละเอียดก่อนเข้าร่วมกิจกรรม</p></div></section>',
      '<section class="promo-board">',
      '<div class="promo-board-head"><h2>กิจกรรมล่าสุด</h2><div class="promo-count" data-promo-count>0 รายการ</div></div>',
      '<div class="promo-tabs" data-promo-tabs></div>',
      '<div class="promo-list" data-promo-list><div class="promo-state">กำลังโหลดโปรโมชั่น...</div></div>',
      '</section>'
    ].join('');

    document.body.appendChild(root);
    state.root = root;
    state.tabsNode = root.querySelector('[data-promo-tabs]');
    state.listNode = root.querySelector('[data-promo-list]');

    Promise.all([api('/api/promotions/categories'), api('/api/promotions')])
      .then(function (results) {
        state.categories = core.responseData(results[0]) || [];
        state.activities = core.responseData(results[1]) || [];
        if (!Array.isArray(state.categories)) {
          state.categories = [];
        }
        if (!Array.isArray(state.activities)) {
          state.activities = [];
        }
        renderTabs();
        renderList();
        var id = new URL(location.href).searchParams.get('id');
        if (id) {
          openDetail(id, false);
        }
      })
      .catch(function () {
        state.listNode.innerHTML = '<div class="promo-state">ไม่สามารถโหลดโปรโมชั่นได้</div>';
      });
  }

  function renderTabs() {
    var categories = state.categories.length ? state.categories : [{ id: 0, name: 'ทั้งหมด' }];
    state.tabsNode.innerHTML = categories.map(function (category) {
      var id = Number(category.id || 0);
      return '<button type="button" class="promo-tab' + (id === state.activeType ? ' active' : '') + '" data-type="' + id + '">' + escapeHtml(category.name || 'ทั้งหมด') + '</button>';
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
      ? state.activities.filter(function (item) { return Number(item.type || 0) === state.activeType; })
      : state.activities.slice();

    var countNode = state.root.querySelector('[data-promo-count]');
    countNode.textContent = filtered.length + ' รายการ';

    if (!filtered.length) {
      state.listNode.innerHTML = '<div class="promo-state">ยังไม่มีโปรโมชั่นในหมวดนี้</div>';
      return;
    }

    state.listNode.innerHTML = filtered.map(cardTemplate).join('');
    if (!state.listNode.dataset.bound) {
      state.listNode.dataset.bound = '1';
      state.listNode.addEventListener('click', function (event) {
      var button = event.target.closest('[data-promo-id]');
      if (!button) {
        return;
      }
      openDetail(button.dataset.promoId, true);
      });
    }
  }

  function cardTemplate(item) {
    var image = item.banner || item.app_img || item.popup_image || '';
    return [
      '<article class="promotion-card">',
      image ? '<img src="' + escapeAttribute(image) + '" alt="' + escapeAttribute(item.title || 'โปรโมชั่น') + '" loading="lazy">' : '<div class="promo-placeholder">TH2.VIP</div>',
      '<div class="promotion-card-body">',
      '<span>' + escapeHtml(item.type_name || 'โปรโมชั่น') + '</span>',
      '<h3>' + escapeHtml(item.title || 'โปรโมชั่น') + '</h3>',
      '<button type="button" data-promo-id="' + Number(item.id || 0) + '">อ่านเพิ่มเติม</button>',
      '</div>',
      '</article>'
    ].join('');
  }

  function openDetail(id, pushUrl) {
    if (!id) {
      return;
    }
    api('/api/promotions/' + encodeURIComponent(id))
      .then(function (payload) {
        var item = core.responseData(payload);
        if (!item || payload.code === 404) {
          showDetailError('ไม่พบโปรโมชั่นนี้');
          return;
        }
        renderDetail(item);
        recordExposure(item.id, 'direct_link');
        if (pushUrl) {
          history.pushState({ promotionId: item.id }, '', core.buildDetailUrl(location.href, item.id));
        }
      })
      .catch(function () {
        showDetailError('ไม่สามารถโหลดรายละเอียดได้');
      });
  }

  function renderDetail(item) {
    closeDetail(false);
    var modal = document.createElement('section');
    modal.className = 'promo-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.innerHTML = [
      '<div class="promo-modal-backdrop" data-close-detail></div>',
      '<div class="promo-modal-panel">',
      '<button class="promo-close" type="button" data-close-detail aria-label="ปิด">×</button>',
      '<div class="promo-detail-scroller">',
      item.banner ? '<img class="promo-modal-banner" src="' + escapeAttribute(item.banner) + '" alt="' + escapeAttribute(item.title || 'โปรโมชั่น') + '">' : '',
      '<div class="promo-modal-content">',
      '<div class="promo-detail-heading"><span>' + escapeHtml(item.type_name || 'โปรโมชั่น') + '</span><h2>' + escapeHtml(item.title || 'โปรโมชั่น') + '</h2></div>',
      renderDetailArtwork(item),
      '<div class="promo-rich">' + sanitizeRichText(item.content || '') + '</div>',
      item.memo ? '<h3>เงื่อนไขกิจกรรม</h3><div class="promo-rich">' + sanitizeRichText(item.memo) + '</div>' : '',
      actionButton(item),
      '</div>',
      '</div>',
      '</div>'
    ].join('');
    document.body.appendChild(modal);
    modal.addEventListener('click', function (event) {
      if (event.target.closest('[data-close-detail]')) {
        closeDetail(true);
      }
      var action = event.target.closest('[data-promo-action]');
      if (action) {
        event.preventDefault();
        handleAction(item);
      }
    });
    document.body.classList.add('promo-modal-open');
  }

  function renderDetailArtwork(item) {
    if (core.hasDistinctDetailImage(item)) {
      return '<div class="promo-detail-artwork"><img class="promo-detail-image" src="' + escapeAttribute(item.detail_image) + '" alt="รายละเอียดโปรโมชั่น" loading="eager"></div>';
    }

    var deposit = String(item.type_name || item.title || '').indexOf('ฝาก') !== -1;
    var eyebrow = deposit ? 'สิทธิพิเศษสำหรับการฝากเงิน' : 'สิทธิพิเศษสำหรับสมาชิกใหม่';
    var highlight = deposit ? 'รับโบนัสเพิ่มตามยอดฝาก' : 'รับโบนัสต้อนรับสำหรับบัญชีใหม่';
    var steps = deposit
      ? ['เลือกช่องทางฝากเงิน', 'เติมเครดิตตามยอดที่กำหนด', 'รับโบนัสและเริ่มเล่น']
      : ['สมัครสมาชิกใหม่', 'ฝากเงินครั้งแรก', 'รับโบนัสต้อนรับ'];

    return [
      '<div class="promo-detail-artwork promo-detail-fallback' + (deposit ? ' deposit' : ' welcome') + '">',
      '<div class="promo-fallback-brand">TH2.VIP <small>ONLINE GAMING</small></div>',
      '<p class="promo-fallback-eyebrow">' + eyebrow + '</p>',
      '<strong>' + highlight + '</strong>',
      '<div class="promo-fallback-steps">',
      steps.map(function (step, index) {
        return '<div><b>' + (index + 1) + '</b><span>' + escapeHtml(step) + '</span></div>';
      }).join(''),
      '</div>',
      '<p>ตรวจสอบรายละเอียดและเงื่อนไขก่อนเข้าร่วมกิจกรรมทุกครั้ง</p>',
      '</div>'
    ].join('');
  }

  function actionButton(item) {
    if (item.action_url) {
      return '<a class="promo-primary" href="' + escapeAttribute(item.action_url) + '">เข้าร่วมกิจกรรม</a>';
    }
    if (Number(item.can_apply || 0) === 1) {
      return '<button type="button" class="promo-primary" data-promo-action>เข้าร่วมกิจกรรม</button>';
    }
    return '';
  }

  function handleAction(item) {
    var token = localStorage.getItem('token') || localStorage.getItem('api_token') || localStorage.getItem('Authorization') || '';
    if (!token) {
      location.href = '/login?redirect=' + encodeURIComponent(location.pathname + location.search);
      return;
    }
    fetch('/api/doactivityapply', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': token.indexOf('Bearer ') === 0 ? token : 'Bearer ' + token
      },
      body: JSON.stringify({ activityid: item.id })
    }).then(function (response) {
      return response.json();
    }).then(function (payload) {
      alert(Number(payload.code) === 200 ? 'ส่งคำขอเรียบร้อยแล้ว' : 'ไม่สามารถส่งคำขอได้');
    }).catch(function () {
      alert('ไม่สามารถส่งคำขอได้');
    });
  }

  function showDetailError(message) {
    closeDetail(false);
    var modal = document.createElement('section');
    modal.className = 'promo-modal';
    modal.innerHTML = '<div class="promo-modal-backdrop" data-close-detail></div><div class="promo-modal-panel small"><button class="promo-close" type="button" data-close-detail>×</button><div class="promo-state">' + escapeHtml(message) + '</div></div>';
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
      history.pushState({}, '', core.clearDetailUrl(location.href));
    }
  }

  function initHomePopup() {
    api('/api/promotions/popup')
      .then(function (payload) {
        var item = core.responseData(payload);
        if (!item || !item.id) {
          return;
        }
        var stores = core.storageSnapshot({ local: localStorage, session: sessionStorage });
        if (!core.shouldDisplayPopup(item, stores, new Date())) {
          return;
        }
        window.setTimeout(function () {
          renderHomePopup(item);
        }, Math.max(0, Number(item.popup_delay_seconds || 0)) * 1000);
      })
      .catch(function () {});
  }

  function renderHomePopup(item) {
    var image = item.popup_image || item.banner || item.detail_image;
    if (!image) {
      return;
    }
    var modal = document.createElement('section');
    modal.className = 'promo-home-popup';
    modal.innerHTML = [
      '<div class="promo-home-backdrop" data-popup-close></div>',
      '<div class="promo-home-panel">',
      '<button class="promo-close" type="button" data-popup-close aria-label="ปิด">×</button>',
      '<button class="promo-home-image" type="button" data-popup-open><img src="' + escapeAttribute(image) + '" alt="' + escapeAttribute(item.title || 'โปรโมชั่น') + '"></button>',
      '</div>'
    ].join('');
    document.body.appendChild(modal);
    recordExposure(item.id, 'home_popup');
    modal.addEventListener('click', function (event) {
      if (event.target.closest('[data-popup-close]')) {
        core.rememberPopupClosed(item, { local: localStorage, session: sessionStorage }, new Date());
        modal.remove();
      }
      if (event.target.closest('[data-popup-open]')) {
        core.rememberPopupClosed(item, { local: localStorage, session: sessionStorage }, new Date());
        location.href = core.buildDetailUrl('/promotions', item.id);
      }
    });
  }

  function recordExposure(id, source) {
    fetch('/api/promotions/' + encodeURIComponent(id) + '/exposure', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        source: source,
        session_key: getSessionKey()
      })
    }).catch(function () {});
  }

  function getSessionKey() {
    var key = sessionStorage.getItem('th2w:promo:session-key');
    if (!key) {
      key = 's' + Date.now().toString(36) + Math.random().toString(36).slice(2);
      sessionStorage.setItem('th2w:promo:session-key', key);
    }
    return key;
  }

  function sanitizeRichText(html) {
    if (!html) {
      return '';
    }
    var template = document.createElement('template');
    template.innerHTML = String(html);
    Array.prototype.slice.call(template.content.querySelectorAll('script,style,iframe,object,embed')).forEach(function (node) {
      node.remove();
    });
    Array.prototype.slice.call(template.content.querySelectorAll('*')).forEach(function (node) {
      Array.prototype.slice.call(node.attributes).forEach(function (attribute) {
        var name = attribute.name.toLowerCase();
        var value = String(attribute.value || '').trim().toLowerCase();
        if (name.indexOf('on') === 0 || value.indexOf('javascript:') === 0) {
          node.removeAttribute(attribute.name);
        }
      });
    });
    return template.innerHTML;
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[char];
    });
  }

  function escapeAttribute(value) {
    return escapeHtml(value).replace(/`/g, '&#96;');
  }
}());
