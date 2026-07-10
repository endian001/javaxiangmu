(function (root, factory) {
  if (typeof module === 'object' && module.exports) {
    module.exports = factory();
  } else {
    root.TH2WPromotionCore = factory();
  }
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
  var PROMOTION_PATHS = ['/activity', '/activities', '/promotions'];

  function cleanPath(pathname) {
    var path = String(pathname || '/').split('?')[0].replace(/\/+$/, '');
    return path || '/';
  }

  function isPromotionPath(pathname) {
    return PROMOTION_PATHS.indexOf(cleanPath(pathname)) !== -1;
  }

  function buildDetailUrl(currentUrl, id) {
    var url = new URL(currentUrl, 'https://th2w.local');
    url.searchParams.set('id', String(id));
    return stripLocalOrigin(url);
  }

  function clearDetailUrl(currentUrl) {
    var url = new URL(currentUrl, 'https://th2w.local');
    url.searchParams.delete('id');
    return stripLocalOrigin(url);
  }

  function stripLocalOrigin(url) {
    if (url.origin === 'https://th2w.local') {
      return url.pathname + url.search + url.hash;
    }
    return url.toString();
  }

  function popupKey(activity, frequency) {
    return 'th2w:promo:' + frequency + ':' + activity.id;
  }

  function todayKey(now) {
    var value = now instanceof Date ? now : new Date(now || Date.now());
    var year = value.getFullYear();
    var month = String(value.getMonth() + 1).padStart(2, '0');
    var day = String(value.getDate()).padStart(2, '0');
    return year + '-' + month + '-' + day;
  }

  function shouldDisplayPopup(activity, stores, now) {
    if (!activity || !activity.id) {
      return false;
    }
    stores = stores || {};
    var local = stores.local || {};
    var session = stores.session || {};
    var frequency = activity.popup_frequency || 'daily';

    if (frequency === 'always') {
      return true;
    }
    if (frequency === 'once') {
      return !local[popupKey(activity, 'once')];
    }
    if (frequency === 'session') {
      return !session[popupKey(activity, 'session')];
    }
    if (frequency === 'daily') {
      return local[popupKey(activity, 'daily')] !== todayKey(now || new Date());
    }

    return true;
  }

  function rememberPopupClosed(activity, storage, now) {
    if (!activity || !activity.id || !storage) {
      return;
    }
    var frequency = activity.popup_frequency || 'daily';
    if (frequency === 'once') {
      storage.local.setItem(popupKey(activity, 'once'), '1');
    } else if (frequency === 'session') {
      storage.session.setItem(popupKey(activity, 'session'), '1');
    } else if (frequency === 'daily') {
      storage.local.setItem(popupKey(activity, 'daily'), todayKey(now || new Date()));
    }
  }

  function storageSnapshot(storage) {
    var local = {};
    var session = {};
    copyStorage(storage && storage.local, local);
    copyStorage(storage && storage.session, session);
    return { local: local, session: session };
  }

  function copyStorage(source, target) {
    if (!source) {
      return;
    }
    for (var index = 0; index < source.length; index += 1) {
      var key = source.key(index);
      target[key] = source.getItem(key);
    }
  }

  function responseData(payload) {
    if (!payload) {
      return null;
    }
    if (Array.isArray(payload)) {
      return payload;
    }
    if (payload.data && Array.isArray(payload.data.data)) {
      return payload.data.data;
    }
    if (Array.isArray(payload.data)) {
      return payload.data;
    }
    if (payload.data) {
      return payload.data;
    }
    return payload;
  }

  function normalizeAssetUrl(value) {
    var raw = String(value || '').trim();
    if (!raw) {
      return '';
    }

    try {
      return new URL(raw, 'https://th2w.local').pathname.replace(/\/+/g, '/');
    } catch (error) {
      return raw.split(/[?#]/)[0].replace(/\/+/g, '/');
    }
  }

  function hasDistinctDetailImage(activity) {
    if (!activity) {
      return false;
    }

    var banner = normalizeAssetUrl(activity.banner || activity.app_img || '');
    var detail = normalizeAssetUrl(activity.detail_image || '');
    return Boolean(detail && detail !== banner);
  }

  return {
    isPromotionPath: isPromotionPath,
    buildDetailUrl: buildDetailUrl,
    clearDetailUrl: clearDetailUrl,
    shouldDisplayPopup: shouldDisplayPopup,
    rememberPopupClosed: rememberPopupClosed,
    storageSnapshot: storageSnapshot,
    responseData: responseData,
    hasDistinctDetailImage: hasDistinctDetailImage
  };
}));
