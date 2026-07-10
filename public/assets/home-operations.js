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

  document.addEventListener('DOMContentLoaded', function () {
    if (!isHomePath()) {
      return;
    }

    initCarousel();
    initFloatingSupport();
  });

  function isHomePath() {
    var path = location.pathname.replace(/\/+$/, '') || '/';
    return path === '/' || path === '/new-h5';
  }

  function postJson(path, body) {
    return fetch(path, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(body || {})
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

  function initCarousel() {
    var root = document.querySelector('[data-home-banner]');
    if (!root) {
      return;
    }

    postJson('/api/bannerList', { type: 2 })
      .then(function (payload) {
        var rows = responseData(payload);
        var banners = Array.isArray(rows)
          ? rows.map(normalizeBanner).filter(Boolean)
          : [];

        if (banners.length) {
          renderCarousel(root, banners);
        }
      })
      .catch(function () {
        root.classList.add('home-carousel--fallback');
      });
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
      href: safeWebUrl(item.url || item.jump_url || '', false),
      background: safeColor(item.background)
    };
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
    carousel.root = root;
    carousel.count = banners.length;
    carousel.index = 0;

    root.classList.remove('game-hero');
    root.classList.remove('game-banner');
    root.classList.add('home-carousel');
    root.setAttribute('aria-label', 'โปรโมชั่นล่าสุด');
    root.innerHTML = [
      '<div class="home-carousel__viewport">',
      '<div class="home-carousel__track">',
      banners.map(function (banner, index) {
        var image = '<img src="' + escapeAttribute(banner.src) + '" alt="โปรโมชั่น TH2.VIP ' + (index + 1) + '" ' + (index === 0 ? 'loading="eager"' : 'loading="lazy"') + '>';
        var content = banner.href
          ? '<a class="home-carousel__slide-link" href="' + escapeAttribute(banner.href) + '">' + image + '</a>'
          : image;
        return '<div class="home-carousel__slide" aria-hidden="' + (index === 0 ? 'false' : 'true') + '" style="background:' + escapeAttribute(banner.background) + '">' + content + '</div>';
      }).join(''),
      '</div>',
      '</div>',
      banners.length > 1 ? '<button class="home-carousel__arrow prev" type="button" data-carousel-prev aria-label="ก่อนหน้า">' + icon('chevron-left') + '</button>' : '',
      banners.length > 1 ? '<button class="home-carousel__arrow next" type="button" data-carousel-next aria-label="ถัดไป">' + icon('chevron-right') + '</button>' : '',
      banners.length > 1 ? '<div class="home-carousel__dots">' + banners.map(function (_, index) {
        return '<button type="button" data-carousel-dot="' + index + '" aria-label="โปรโมชั่น ' + (index + 1) + '" class="' + (index === 0 ? 'active' : '') + '"></button>';
      }).join('') + '</div>' : ''
    ].join('');

    carousel.track = root.querySelector('.home-carousel__track');
    carousel.dots = Array.prototype.slice.call(root.querySelectorAll('[data-carousel-dot]'));
    if (banners.length < 2) {
      return;
    }

    root.querySelector('[data-carousel-prev]').addEventListener('click', function () {
      showSlide(carousel.index - 1);
      restartCarousel();
    });
    root.querySelector('[data-carousel-next]').addEventListener('click', function () {
      showSlide(carousel.index + 1);
      restartCarousel();
    });
    carousel.dots.forEach(function (dot) {
      dot.addEventListener('click', function () {
        showSlide(Number(dot.getAttribute('data-carousel-dot')));
        restartCarousel();
      });
    });

    var viewport = root.querySelector('.home-carousel__viewport');
    viewport.addEventListener('touchstart', function (event) {
      carousel.touchStartX = event.changedTouches[0].clientX;
      stopCarousel();
    }, { passive: true });
    viewport.addEventListener('touchend', function (event) {
      var distance = event.changedTouches[0].clientX - carousel.touchStartX;
      if (Math.abs(distance) > 40) {
        showSlide(carousel.index + (distance < 0 ? 1 : -1));
      }
      startCarousel();
    }, { passive: true });
    root.addEventListener('mouseenter', stopCarousel);
    root.addEventListener('mouseleave', startCarousel);
    root.addEventListener('focusin', stopCarousel);
    root.addEventListener('focusout', startCarousel);
    startCarousel();
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
    root.innerHTML = [
      '<div class="floating-support__contacts" data-contact-panel hidden>',
      '<div class="floating-support__contacts-head"><strong>ช่องทางติดต่อ</strong><button type="button" data-contact-close aria-label="ปิด">' + icon('x') + '</button></div>',
      '<div class="floating-support__contact-list" data-contact-list></div>',
      '</div>',
      '<button class="floating-support__phone" type="button" data-contact-toggle aria-label="เปิดช่องทางติดต่อ" aria-expanded="false">' + icon('phone-call') + '</button>',
      '<button class="floating-support__top" type="button" data-back-to-top aria-label="กลับขึ้นด้านบน" hidden>' + icon('chevrons-up') + '</button>',
      '<div class="floating-support__promo" data-floating-promo>',
      '<button class="floating-support__promo-close" type="button" data-floating-promo-close aria-label="ปิดโปรโมชั่น">' + icon('x') + '</button>',
      '<a href="/promotions" aria-label="ดูโปรโมชั่น"><img src="/assets/promotions/referral-banner.webp" alt="โปรโมชั่นสมาชิก"></a>',
      '</div>',
      '<a class="floating-support__online" data-online-service href="/support/work-orders.html" aria-label="ติดต่อฝ่ายบริการลูกค้า">',
      '<span class="floating-support__online-icon">' + icon('headphones') + '</span><span>ติดต่อเรา</span>',
      '</a>'
    ].join('');
    document.body.appendChild(root);

    var panel = root.querySelector('[data-contact-panel]');
    var toggle = root.querySelector('[data-contact-toggle]');
    var topButton = root.querySelector('[data-back-to-top]');
    var promo = root.querySelector('[data-floating-promo]');

    toggle.addEventListener('click', function () {
      setContactPanel(panel.hidden, panel, toggle);
    });
    root.querySelector('[data-contact-close]').addEventListener('click', function () {
      setContactPanel(false, panel, toggle);
    });
    document.addEventListener('click', function (event) {
      if (!panel.hidden && !root.contains(event.target)) {
        setContactPanel(false, panel, toggle);
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        setContactPanel(false, panel, toggle);
      }
    });

    topButton.addEventListener('click', function () {
      window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    window.addEventListener('scroll', function () {
      topButton.hidden = window.scrollY < 320;
    }, { passive: true });

    var closedAt = Number(localStorage.getItem('th2w:home:floating-promo-closed') || 0);
    if (closedAt && Date.now() - closedAt < 86400000) {
      promo.hidden = true;
    }
    root.querySelector('[data-floating-promo-close]').addEventListener('click', function (event) {
      event.preventDefault();
      event.stopPropagation();
      localStorage.setItem('th2w:home:floating-promo-closed', String(Date.now()));
      promo.hidden = true;
    });

    postJson('/api/getservicerurl')
      .then(function (payload) {
        renderCustomerServices(root, responseData(payload) || {});
      })
      .catch(function () {
        renderCustomerServices(root, { work_order_page_url: '/support/work-orders.html' });
      });
  }

  function setContactPanel(open, panel, toggle) {
    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    toggle.classList.toggle('active', open);
  }

  function renderCustomerServices(root, payload) {
    var list = root.querySelector('[data-contact-list]');
    var toggle = root.querySelector('[data-contact-toggle]');
    var online = root.querySelector('[data-online-service]');
    var rows = Array.isArray(payload.services) ? payload.services : [];
    var contacts = rows.map(normalizeContact).filter(Boolean);
    var onlineUrl = safeContactUrl(
      payload.service_url || payload.work_order_page_url || '/support/work-orders.html'
    );

    if (!contacts.length && onlineUrl) {
      contacts.push({
        type: 'online',
        label: 'แชตกับฝ่ายบริการลูกค้า',
        url: onlineUrl
      });
    }

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
      if (/^https?:/i.test(onlineUrl)) {
        online.target = '_blank';
        online.rel = 'noopener noreferrer';
      }
    } else {
      online.hidden = true;
    }
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
    var hasBrokenText = /[\u3400-\u9FFF\uFFFD]/.test(label);
    if (hasThai && !hasBrokenText) {
      return label;
    }

    var labels = {
      phone: 'ศูนย์บริการทางโทรศัพท์',
      line: 'ติดต่อผ่าน LINE',
      telegram: 'ติดต่อผ่าน Telegram',
      facebook: 'ติดต่อผ่าน Facebook',
      messenger: 'ติดต่อผ่าน Messenger',
      whatsapp: 'ติดต่อผ่าน WhatsApp',
      instagram: 'ติดต่อผ่าน Instagram',
      online: 'แชตกับฝ่ายบริการลูกค้า',
      custom: 'ติดต่อฝ่ายบริการลูกค้า'
    };
    return labels[type] || labels.custom;
  }

  function contactCaption(type) {
    var captions = {
      phone: 'แตะเพื่อโทร',
      line: 'เปิดแอป LINE',
      telegram: 'เปิด Telegram',
      facebook: 'เปิด Facebook',
      messenger: 'เปิด Messenger',
      whatsapp: 'เปิด WhatsApp',
      instagram: 'เปิด Instagram',
      online: 'บริการออนไลน์',
      custom: 'เปิดช่องทางติดต่อ'
    };
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
}());
