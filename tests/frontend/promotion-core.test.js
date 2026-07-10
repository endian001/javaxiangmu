const test = require('node:test');
const assert = require('node:assert/strict');
const core = require('../../public/assets/promotion-core.js');

test('recognizes every supported promotion route', () => {
  assert.equal(core.isPromotionPath('/activity'), true);
  assert.equal(core.isPromotionPath('/activities'), true);
  assert.equal(core.isPromotionPath('/promotions'), true);
  assert.equal(core.isPromotionPath('/new-h5/'), false);
});

test('builds and removes direct detail ids without losing other query values', () => {
  assert.equal(
    core.buildDetailUrl('https://example.test/promotions?source=home', 42),
    'https://example.test/promotions?source=home&id=42'
  );
  assert.equal(
    core.clearDetailUrl('https://example.test/promotions?source=home&id=42'),
    'https://example.test/promotions?source=home'
  );
});

test('respects once daily and session popup close frequency', () => {
  const now = new Date('2026-07-11T12:00:00+08:00');
  const empty = { local: {}, session: {} };

  assert.equal(core.shouldDisplayPopup({ id: 1, popup_frequency: 'always' }, empty, now), true);
  assert.equal(core.shouldDisplayPopup({ id: 1, popup_frequency: 'once' }, empty, now), true);
  assert.equal(core.shouldDisplayPopup({ id: 1, popup_frequency: 'daily' }, empty, now), true);
  assert.equal(core.shouldDisplayPopup({ id: 1, popup_frequency: 'session' }, empty, now), true);

  assert.equal(core.shouldDisplayPopup(
    { id: 1, popup_frequency: 'once' },
    { local: { 'th2w:promo:once:1': '1' }, session: {} },
    now
  ), false);
  assert.equal(core.shouldDisplayPopup(
    { id: 1, popup_frequency: 'daily' },
    { local: { 'th2w:promo:daily:1': '2026-07-11' }, session: {} },
    now
  ), false);
  assert.equal(core.shouldDisplayPopup(
    { id: 1, popup_frequency: 'session' },
    { local: {}, session: { 'th2w:promo:session:1': '1' } },
    now
  ), false);
});

test('uses a detail image only when it differs from the card banner', () => {
  assert.equal(core.hasDistinctDetailImage({
    banner: '/assets/promotions/welcome-banner.png?v=1',
    detail_image: '/assets/promotions/welcome-banner.png?v=2'
  }), false);

  assert.equal(core.hasDistinctDetailImage({
    banner: '/assets/promotions/welcome-banner.png',
    detail_image: '/assets/promotions/welcome-detail.png'
  }), true);

  assert.equal(core.hasDistinctDetailImage({
    banner: '/assets/promotions/welcome-banner.png',
    detail_image: ''
  }), false);
});
