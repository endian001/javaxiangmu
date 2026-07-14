(function () {
  'use strict';

  var storageKey = 'th2w:pixel:tracking';
  var legacyStorageKey = 'th2w_pixel_tracking';
  var firstOpenPrefix = 'th2w:pixel:first-open:';
  var browserIdKey = 'th2w:pixel:browser-id';
  var sessionIdKey = 'th2w:pixel:session-id';
  var initialized = false;
  var allowedKeys = [
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
    'af_app_id',
    'appsflyer_id',
    'advertising_id',
    'oaid',
    'idfa',
    'idfv',
    'ad_app_token',
    'gps_adid',
    'adid',
    'cid',
    'visitor_id',
    'tfTracker',
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
    'rmClickId'
  ];

  function randomId(prefix) {
    var random = '';
    try {
      var bytes = new Uint8Array(16);
      window.crypto.getRandomValues(bytes);
      random = Array.prototype.map.call(bytes, function (value) {
        return ('0' + value.toString(16)).slice(-2);
      }).join('');
    } catch (error) {
      random = String(Math.random()).slice(2) + String(Date.now());
    }
    return prefix + '_' + random;
  }

  function storedId(key, prefix, storage) {
    try {
      var value = storage.getItem(key);
      if (!value) {
        value = randomId(prefix);
        storage.setItem(key, value);
      }
      return value;
    } catch (error) {
      return randomId(prefix);
    }
  }

  function browserId() {
    return storedId(browserIdKey, 'br', localStorage);
  }

  function sessionId() {
    return storedId(sessionIdKey, 'ss', sessionStorage);
  }

  function eventId(eventName, payload) {
    var order = payload && (payload.order_no || payload.order_id || payload.recharge_id);
    return [
      'evt',
      eventName || 'custom',
      order || Date.now(),
      Math.random().toString(16).slice(2)
    ].join('_').replace(/[^A-Za-z0-9_.:-]/g, '_');
  }

  function readQueryParams() {
    var params = new URLSearchParams(location.search || '');
    if (location.hash && location.hash.indexOf('?') >= 0) {
      var hashQuery = location.hash.slice(location.hash.indexOf('?') + 1);
      new URLSearchParams(hashQuery).forEach(function (value, key) {
        if (!params.has(key)) {
          params.set(key, value);
        }
      });
    }
    var tracking = {};
    allowedKeys.forEach(function (key) {
      var value = params.get(key);
      if (value) {
        tracking[key] = value;
      }
    });
    return tracking;
  }

  function loadStoredParams() {
    try {
      return JSON.parse(localStorage.getItem(storageKey) || localStorage.getItem(legacyStorageKey) || '{}') || {};
    } catch (error) {
      return {};
    }
  }

  function storeParams(params) {
    var existing = loadStoredParams();
    var fresh = params && Object.keys(params).length > 0;
    var merged = fresh ? Object.assign({}, params) : existing;
    if (Object.keys(merged).length) {
      merged.updatedAt = new Date().toISOString();
      localStorage.setItem(storageKey, JSON.stringify(merged));
      localStorage.setItem(legacyStorageKey, JSON.stringify(merged));
    }
    return merged;
  }

  function currentTrackingParams() {
    return storeParams(readQueryParams());
  }

  function appendScript(id, src) {
    if (!src || document.getElementById(id)) {
      return;
    }
    var script = document.createElement('script');
    script.id = id;
    script.async = true;
    script.src = src;
    document.head.appendChild(script);
  }

  function initFacebook(pixelId) {
    if (!pixelId) {
      return;
    }
    if (!window.fbq) {
      var fbq = function () {
        fbq.callMethod ? fbq.callMethod.apply(fbq, arguments) : fbq.queue.push(arguments);
      };
      fbq.push = fbq;
      fbq.loaded = true;
      fbq.version = '2.0';
      fbq.queue = [];
      window.fbq = fbq;
    }
    appendScript('th2w-facebook-pixel', 'https://connect.facebook.net/en_US/fbevents.js');
    window.fbq('init', pixelId);
    window.fbq('track', 'PageView');
  }

  function initTikTok(pixelId) {
    if (!pixelId) {
      return;
    }
    if (!window.ttq) {
      var ttq = window.ttq = [];
      ttq.methods = ['page', 'track', 'identify', 'instances', 'debug', 'on', 'off', 'once', 'ready', 'alias', 'group', 'enableCookie', 'disableCookie'];
      ttq.setAndDefer = function (target, method) {
        target[method] = function () {
          target.push([method].concat(Array.prototype.slice.call(arguments, 0)));
        };
      };
      for (var i = 0; i < ttq.methods.length; i += 1) {
        ttq.setAndDefer(ttq, ttq.methods[i]);
      }
      ttq.load = function (id) {
        appendScript('th2w-tiktok-pixel-' + id, 'https://analytics.tiktok.com/i18n/pixel/events.js?sdkid=' + encodeURIComponent(id) + '&lib=ttq');
      };
    }
    window.ttq.load(pixelId);
    window.ttq.page();
  }

  function initGtag(gtagId) {
    if (!gtagId) {
      return;
    }
    window.dataLayer = window.dataLayer || [];
    window.gtag = window.gtag || function () {
      window.dataLayer.push(arguments);
    };
    appendScript('th2w-google-gtag-' + gtagId, 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gtagId));
    window.gtag('js', new Date());
    window.gtag('config', gtagId);
  }

  function initGtm(gtmId) {
    if (!gtmId) {
      return;
    }
    window.dataLayer = window.dataLayer || [];
    window.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
    appendScript('th2w-google-gtm-' + gtmId, 'https://www.googletagmanager.com/gtm.js?id=' + encodeURIComponent(gtmId));
  }

  function initTracking() {
    var params = currentTrackingParams();
    initFacebook(params.fbPixelId);
    initTikTok(params.tiktokPixelId);
    initGtag(params.gtagId);
    initGtm(params.gtmId);
    trackFirstOpen(params);
    return params;
  }

  function eventMap(eventName) {
    var facebook = {
      firstOpen: 'PageView',
      registerSubmit: 'Lead',
      register: 'CompleteRegistration',
      login: 'Login',
      depositSubmit: 'InitiateCheckout',
      firstDepositArrival: 'firstDepositArrival',
      startTrial: 'StartTrial',
      deposit: 'Purchase',
      redeposit: 'redeposit',
      withdraw: 'withdraw'
    };
    var tiktok = {
      firstOpen: 'Browse',
      registerSubmit: 'SubmitForm',
      register: 'CompleteRegistration',
      login: 'Login',
      depositSubmit: 'InitiateCheckout',
      firstDepositArrival: 'firstDepositArrival',
      startTrial: 'Subscribe',
      deposit: 'CompletePayment',
      redeposit: 'redeposit',
      withdraw: 'Withdraw'
    };
    return {
      facebook: facebook[eventName] || eventName,
      tiktok: tiktok[eventName] || eventName,
      google: eventName
    };
  }

  function vendorPayload(payload) {
    payload = payload || {};
    var out = {};
    if (payload.amount || payload.value) {
      out.value = Number(payload.amount || payload.value) || 0;
      out.currency = payload.currency || 'THB';
    }
    if (payload.order_no) {
      out.order_id = payload.order_no;
    }
    return out;
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

  function recordBackendEvent(eventName, payload, params, id) {
    var body = {
      event: eventName,
      event_id: id,
      url: location.href,
      title: document.title,
      referrer: document.referrer || '',
      browser_id: browserId(),
      session_id: sessionId(),
      screen: {
        width: window.screen && window.screen.width || 0,
        height: window.screen && window.screen.height || 0,
        pixelRatio: window.devicePixelRatio || 1
      },
      amount: payload && (payload.amount || payload.value) || 0,
      currency: payload && payload.currency || 'THB',
      tracking: params,
      payload: payload || {}
    };
    var headers = {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    };
    var token = currentAuthToken();
    if (token) {
      headers.Authorization = 'Bearer ' + token;
    }
    return fetch('/api/pixel/event', {
      method: 'POST',
      headers: headers,
      body: JSON.stringify(body),
      keepalive: true
    }).catch(function () {});
  }

  function trackPixelEvent(eventName, payload) {
    var params = currentTrackingParams();
    var names = eventMap(eventName);
    var data = vendorPayload(payload || {});
    var id = eventId(eventName, payload || {});
    data.event_id = id;

    try {
      if (params.fbPixelId && window.fbq) {
        if (names.facebook === 'PageView') {
          window.fbq('track', 'PageView');
        } else if (['CompleteRegistration', 'Lead', 'InitiateCheckout', 'Purchase'].indexOf(names.facebook) >= 0) {
          window.fbq('track', names.facebook, data, { eventID: id });
        } else {
          window.fbq('trackCustom', names.facebook, data, { eventID: id });
        }
      }
    } catch (error) {}

    try {
      if (params.tiktokPixelId && window.ttq && window.ttq.track) {
        window.ttq.track(names.tiktok, data);
      }
    } catch (error) {}

    try {
      if (params.gtagId && window.gtag) {
        window.gtag('event', names.google, data);
      }
      if (params.gtmId && window.dataLayer) {
        window.dataLayer.push(Object.assign({ event: eventName }, data));
      }
    } catch (error) {}

    return recordBackendEvent(eventName, payload || {}, params, id);
  }

  function trackFirstOpen(params) {
    if (!Object.keys(params || {}).length) {
      return;
    }
    var keyParams = Object.assign({}, params);
    delete keyParams.updatedAt;
    var key = firstOpenPrefix + location.pathname + ':' + JSON.stringify(keyParams);
    if (sessionStorage.getItem(key) === '1') {
      return;
    }
    sessionStorage.setItem(key, '1');
    window.setTimeout(function () {
      trackPixelEvent('firstOpen', {});
    }, 100);
  }

  function flushQueuedEvents() {
    var queue = Array.isArray(window.__th2wQueuedPixelEvents) ? window.__th2wQueuedPixelEvents.splice(0) : [];
    queue.forEach(function (item) {
      if (item && item.name) {
        trackPixelEvent(item.name, item.payload || {});
      }
    });
  }

  function bootTracking() {
    if (!initialized) {
      initialized = true;
      initTracking();
    }
    flushQueuedEvents();
  }

  window.TH2WPixel = {
    init: initTracking,
    params: currentTrackingParams,
    browserId: browserId,
    sessionId: sessionId,
    track: trackPixelEvent
  };
  window.trackPixelEvent = trackPixelEvent;

  bootTracking();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootTracking);
  }
}());
