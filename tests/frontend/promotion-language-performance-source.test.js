const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..', '..');

function read(relativePath) {
  return fs.readFileSync(path.join(root, relativePath), 'utf8');
}

test('promotion api defaults to Chinese copy before translated activity fields', () => {
  const source = read('app/Http/Controllers/Api/PromotionController.php');

  assert.match(source, /function localeFromRequest\(Request \$request\)/);
  assert.match(source, /localizedText\(\$activity->title \?\? '', \$activity->entitle \?\? '', \$locale\)/);
  assert.match(source, /localizedText\(\$activity->content \?\? '', \$activity->encontent \?\? '', \$locale\)/);
  assert.match(source, /localizedText\(\$activity->memo \?\? '', \$activity->enmemo \?\? '', \$locale\)/);
  assert.match(source, /localizedText\(\$type->name \?\? '', \$type->enname \?\? '', \$locale\)/);
  assert.doesNotMatch(source, /x\{4E00\}-\\x\{9FFF\}/);
});

test('legacy and app activity endpoints use the same Chinese default promotion copy', () => {
  const index = read('app/Http/Controllers/Api/IndexController.php');
  const app = read('app/Http/Controllers/Api/AppController.php');

  for (const source of [index, app]) {
    assert.match(source, /promotionLocaleFromRequest\(Request \$request\)/);
    assert.match(source, /promotionDisplayText\(\$activity->title \?\? '', \$activity->entitle \?\? '', \$locale\)/);
    assert.match(source, /promotionDisplayText\(\$activity->content \?\? '', \$activity->encontent \?\? '', \$locale\)/);
    assert.match(source, /promotionDisplayText\(\$activity->memo \?\? '', \$activity->enmemo \?\? '', \$locale\)/);
    assert.match(source, /promotionDisplayText\(\$type->name \?\? '', \$type->enname \?\? '', \$locale\)/);
  }
});

test('promotion frontend requests locale and renders Chinese fields first by default', () => {
  const source = read('public/assets/promotion-system.js');

  assert.match(source, /function currentLocale\(\)/);
  assert.match(source, /locale=' \+ encodeURIComponent\(currentLocale\(\)\)/);
  assert.match(source, /source\.title = cleanText\(source\.title \|\| source\.entitle \|\| source\.name/);
  assert.match(source, /return cleanText\(item && \(item\.title \|\| item\.entitle \|\| item\.name\), '\\u6d3b\\u52a8\\u4e2d\\u5fc3'\)/);
  assert.match(source, /return cleanRichText\(item && \(item\.content \|\| item\.encontent\), copy\.fallbackContent\)/);
  assert.match(source, /return cleanRichText\(item && \(item\.memo \|\| item\.enmemo\), ''\)/);
  assert.doesNotMatch(source, /hasThaiText\(\[/);
});

test('home entry files bust cached promotion runtime after language fixes', () => {
  for (const file of ['public/index.html', 'public/new-h5/index.html']) {
    const source = read(file);
    assert.match(source, /\/assets\/promotion-system\.js\?v=20260714lang1/);
  }
});

test('standalone promotion route shell uses the shared Chinese promotion runtime', () => {
  const source = read('public/pages/activity.html');
  assert.match(source, /<html lang="zh-CN">/);
  assert.match(source, /<title>\u6d3b\u52a8\u4e2d\u5fc3 - TH2\.VIP<\/title>/);
  assert.match(source, /\/assets\/promotion-system\.js\?v=20260714lang1/);
  assert.doesNotMatch(source, /promotion-popup\.js|th2w-pages\.js|TH2W Thailand|[\u0E00-\u0E7F]/);
});

test('public game list endpoints cache formatted game payloads', () => {
  const source = read('app/Http/Controllers/Api/IndexController.php');

  assert.match(source, /private function cachedEnabledApiCodes\(\)/);
  assert.match(source, /Cache::remember\('public_game_enabled_api_codes:v1', now\(\)->addSeconds\(60\)/);
  assert.match(source, /private function cachedPublicGameList\(\$platform, \$category, \$full\)/);
  assert.match(source, /Cache::remember\(\$cacheKey, now\(\)->addSeconds\(60\), function \(\) use \(\$platform, \$category, \$full\)/);
  assert.match(source, /cachedPublicGameList\(\$platform, \$category, false\)/);
  assert.match(source, /cachedPublicGameList\(\$platform, \$category, true\)/);
});
