const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');

const root = path.resolve(__dirname, '..', '..');
const read = (file) => fs.readFileSync(path.join(root, file), 'utf8');

test('postback dispatcher falls back to curl when guzzle is unavailable', () => {
  const source = read('app/Services/Tracking/TrackingPostbackDispatcher.php');

  assert.match(source, /function sendWithCurl\(array \$request\): array/);
  assert.match(source, /function_exists\('curl_init'\)/);
  assert.match(source, /curl_init\(\$url\)/);
  assert.match(source, /CURLOPT_RETURNTRANSFER/);
  assert.match(source, /CURLOPT_CUSTOMREQUEST/);
  assert.match(source, /CURLOPT_POSTFIELDS/);
  assert.match(source, /http_client_missing/);
});
