<?php
//decode by http://www.yunlu99.com/
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\ActivityApply;
use App\Models\ActivityType;
use App\Models\Bank;
use App\Models\Api;
use App\Models\User_Api;
use App\Models\Message;
use App\Models\UserMessage;
use App\Models\PaySetting;
use App\Models\SystemConfig;
use App\Models\UserCard;
use App\Models\User;
use App\Models\Users;
use App\Models\Usersmoney;
use App\Services\SafeGameTransferService;
use App\Services\TgService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\TransferLog;
use App\Models\Recharge;
use App\Models\Withdraw;
use App\Models\Article;
use App\Models\UserVip;
use App\Models\Banner;
use App\Models\GameRecord;
use App\Models\AgentApply;
use App\Models\GameList;
use App\Services\PromotionService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Pagination\LengthAwarePaginator;

class IndexController extends Controller
{
    protected $messages = [];
    protected $game_list ;
    protected $banklist;

    public function __construct()
    {
        $tg = New TgService;

        $this->game_list =$tg->getallgamename();
        $this->gamemoney_list =$tg->getallmoneygamelist();
        $this->banklist = ['工商银行'=>'Icbc','中国农业银行'=>'Abc','招商银行'=>'Cmb','建设银行'=>'Ccb','中信银行'=>'Cibk','中国银行'=>'Boc','交通银行'=>'Bocom','华夏银行'=>'Hxbc','民生银行'=>'Cmbc','光大银行'=>'Cebc','兴业银行'=>'Fjib','浦发银行'=>'Spdb'];
        $domain = SystemConfig::getValue('safe_domain');
        if ($domain) {
            $domain = explode(',',$domain);
            $referer = $_SERVER["HTTP_REFERER"] ?? '';
            if (!in_array($referer,$domain)) {
                return json_encode(['code'=>401,'message'=>'Authentication failed']);
                exit;
            }
        }

    }

    protected function activeUserFromBearer(Request $request)
    {
        $token = trim(str_replace('Bearer ', '', (string) $request->header('authorization')));
        if ($token === '') {
            return null;
        }

        $user = User::where('api_token', $token)->first();
        if (!$user || $this->isBlockedApiUser($user)) {
            return null;
        }

        return $user;
    }

    protected function isBlockedApiUser(User $user)
    {
        return (int) ($user->status ?? 0) <= 0
            || (int) ($user->isdel ?? 0) === 1
            || (int) ($user->isblack ?? 0) === 1;
    }




    protected function normalizeWxgameLanguage($lang)
    {
        $value = strtolower(str_replace('_', '-', trim((string)$lang)));
        $map = [
            'zh' => 'zh-CN',
            'zh-cn' => 'zh-CN',
            'en' => 'en-US',
            'en-us' => 'en-US',
            'pt' => 'pt-BR',
            'pt-br' => 'pt-BR',
            'es' => 'es-ES',
            'vi' => 'vi-VN',
            'th' => 'th-TH',
            'id' => 'id-ID',
        ];
        return $map[$value] ?? 'en-US';
    }

    protected function wxgameTokenSecret()
    {
        return (string) (SystemConfig::getValue('wxgame_token_secret') ?: SystemConfig::getValue('api_secret') ?: config('app.key'));
    }

    protected function base36ToInt($value)
    {
        $value = strtolower((string) $value);
        $result = 0;
        $chars = '0123456789abcdefghijklmnopqrstuvwxyz';
        for ($i = 0, $len = strlen($value); $i < $len; $i++) {
            $pos = strpos($chars, $value[$i]);
            if ($pos === false) return 0;
            $result = ($result * 36) + $pos;
        }
        return $result;
    }

    protected function createWxgamePlayerToken(User $user, $gameBrand, $gameId)
    {
        $uid = (int) $user->id;
        $exp = time() + 600;
        $nonce = substr(bin2hex(random_bytes(4)), 0, 8);
        $body = $uid . '.' . base_convert((string) $exp, 10, 36) . '.' . $nonce;
        $sig = substr(hash_hmac('sha256', $body, $this->wxgameTokenSecret()), 0, 32);
        return $body . '.' . $sig;
    }

    protected function parseWxgamePlayerToken($token)
    {
        $parts = explode('.', (string) $token);
        if (count($parts) === 4) {
            list($uid, $exp36, $nonce, $sig) = $parts;
            if (!ctype_digit((string) $uid) || $uid === '' || $nonce === '' || $sig === '') return null;
            $body = $uid . '.' . $exp36 . '.' . $nonce;
            $expected = substr(hash_hmac('sha256', $body, $this->wxgameTokenSecret()), 0, 32);
            if (!hash_equals($expected, $sig)) return null;
            $exp = $this->base36ToInt($exp36);
            if ($exp < time()) return null;
            return ['uid' => (int) $uid, 'exp' => $exp];
        }

        $legacyParts = explode('.', (string) $token, 2);
        if (count($legacyParts) !== 2) return null;
        list($body, $sig) = $legacyParts;
        if (!hash_equals(hash_hmac('sha256', $body, $this->wxgameTokenSecret()), $sig)) return null;
        $json = base64_decode(strtr($body, '-_', '+/'));
        $payload = json_decode($json, true);
        if (!is_array($payload) || empty($payload['uid']) || (int)($payload['exp'] ?? 0) < time()) return null;
        return $payload;
    }

    protected function wxgameJson($code = 0, $data = null, $msg = 'success')
    {
        return response()->json([
            'code' => (int) $code,
            'data' => $data,
            'msg' => $msg,
            'requestId' => md5(uniqid('', true)),
        ]);
    }

    protected function wxgameCallbackSignWindow()
    {
        $window = (int) (SystemConfig::getValue('wxgame_callback_sign_window') ?: 300);
        return $window > 0 ? $window : 300;
    }

    protected function wxgameVerifyCallbackSignature(Request $request)
    {
        if ((int) (SystemConfig::getValue('wxgame_callback_signature_required') ?: 1) !== 1) {
            return null;
        }

        $accessKeyId = trim((string) $request->header('AccessKeyId'));
        $sign = trim((string) $request->header('Sign'));
        $nonce = trim((string) $request->header('Nonce'));
        $timestamp = trim((string) $request->header('Timestamp'));
        $expectedKey = trim((string) SystemConfig::getValue('wxgame_access_key_id'));
        $secret = trim((string) SystemConfig::getValue('wxgame_access_key_secret'));

        if ($accessKeyId === '' || $sign === '' || $nonce === '' || $timestamp === '' || $expectedKey === '' || $secret === '') {
            return $this->wxgameJson(1005, null, 'Missing callback signature');
        }

        if (!hash_equals($expectedKey, $accessKeyId)) {
            return $this->wxgameJson(1005, null, 'Invalid access key');
        }

        if (!ctype_digit($timestamp) || abs(time() - (int) $timestamp) > $this->wxgameCallbackSignWindow()) {
            return $this->wxgameJson(1005, null, 'Invalid timestamp');
        }

        $expectedSign = hash('sha256', $secret . $nonce . $timestamp);
        if (!hash_equals(strtolower($expectedSign), strtolower($sign))) {
            return $this->wxgameJson(1005, null, 'Invalid signature');
        }

        $cacheKey = 'wxgame_callback_nonce_' . md5($accessKeyId . '|' . $nonce . '|' . $timestamp . '|' . $sign);
        $minutes = max(2, (int) ceil($this->wxgameCallbackSignWindow() / 60) + 2);
        if (!Cache::add($cacheKey, 1, $minutes)) {
            return $this->wxgameJson(1005, null, 'Duplicate callback signature');
        }

        return null;
    }

    protected function wxgameUserByPlayerId($playerId)
    {
        $playerId = trim((string) $playerId);
        if ($playerId === '') return null;
        if (preg_match('/^M(\d+)$/i', $playerId, $m)) return User::find((int) $m[1]);
        if (preg_match('/^\d+$/', $playerId)) return User::find((int) $playerId);
        return User::where('username', $playerId)->first();
    }

    protected function wxgameCurrency()
    {
        return strtoupper((string) (SystemConfig::getValue('wxgame_currency') ?: SystemConfig::getValue('currency') ?: 'THB'));
    }

    protected function wxgameValidateCurrency(array $payload)
    {
        $currency = strtoupper(trim((string)($payload['currency'] ?? '')));
        if ($currency !== '' && $currency !== $this->wxgameCurrency()) {
            return $this->wxgameJson(1005, null, 'Currency mismatch');
        }
        return null;
    }

    protected function wxgameBalancePayload(User $user)
    {
        return ['balance' => round((float) $user->balance, 2), 'currency' => $this->wxgameCurrency()];
    }

    protected function wxgameRecordExists($transactionId)
    {
        return TransferLog::where('order_no', (string) $transactionId)->exists()
            || GameRecord::where('bet_id', (string) $transactionId)->exists();
    }

    protected function wxgameBetTransfer($transactionId)
    {
        return TransferLog::where('order_no', (string) $transactionId)
            ->where('transfer_type', 2)
            ->first();
    }

    protected function wxgameValidateRelatedBet(array $payload, User $user, $amount = null, $required = false)
    {
        $betTransactionId = trim((string)($payload['betTransactionId'] ?? ''));
        if ($betTransactionId === '') {
            return $required ? $this->wxgameJson(1014, null, 'Transaction not found') : null;
        }

        $bet = $this->wxgameBetTransfer($betTransactionId);
        if (!$bet || (int) $bet->user_id !== (int) $user->id) {
            return $this->wxgameJson(1014, null, 'Transaction not found');
        }

        if ($amount !== null && round((float) $bet->money, 2) !== round((float) $amount, 2)) {
            return $this->wxgameJson(1005, null, 'Bet amount mismatch');
        }

        return null;
    }

    protected function wxgameCreateTransferLog(User $user, array $payload, $type, $amount, $before, $after, $remark)
    {
        TransferLog::create([
            'order_no' => (string) $payload['transactionId'],
            'api_type' => strtoupper((string)($payload['gameBrand'] ?? 'WXGAME')),
            'user_id' => $user->id,
            'transfer_type' => $type,
            'money' => abs((float) $amount),
            'cash_fee' => 0,
            'real_money' => abs((float) $amount),
            'before_money' => $before,
            'after_money' => $after,
            'state' => 2,
            'platform_type' => strtoupper((string)($payload['gameBrand'] ?? 'WXGAME')),
            'addtime' => time(),
            'betid' => (string)($payload['roundId'] ?? ''),
            'remark' => $remark,
            'bet_money' => $type === 2 ? abs((float) $amount) : 0,
            'win_money' => $type !== 2 ? abs((float) $amount) : 0,
        ]);
    }

    protected function wxgameCreateGameRecord(User $user, array $payload, $betAmount, $winLoss)
    {
        GameRecord::create([
            'user_id' => $user->id,
            'username' => $user->username,
            'bet_id' => (string) $payload['transactionId'],
            'bet_time' => date('Y-m-d H:i:s'),
            'platform_type' => strtoupper((string)($payload['gameBrand'] ?? 'WXGAME')),
            'game_type' => (string)($payload['gameType'] ?? 'slot'),
            'game_code' => (string)($payload['gameId'] ?? ''),
            'bet_amount' => abs((float) $betAmount),
            'valid_amount' => abs((float) $betAmount),
            'win_loss' => (float) $winLoss,
            'is_back' => 0,
            'status' => 1,
        ]);
    }

    public function wxgameStatus(Request $request)
    {
        $appUrl = rtrim((string) env('APP_URL'), '/');
        $configuredCallback = trim((string) SystemConfig::getValue('wxgame_callback_domain'));
        $callbackDomain = rtrim($configuredCallback !== '' ? $configuredCallback : $appUrl . '/notify', '/');
        $callbackPath = (string) parse_url($callbackDomain, PHP_URL_PATH);
        if ($callbackPath === '' || $callbackPath === '/') {
            $callbackDomain .= '/notify';
        }
        return $this->wxgameJson(0, [
            'enabled' => (new TgService)->isWxgameEnabled(),
            'appId' => (string) SystemConfig::getValue('wxgame_app_id'),
            'apiDomain' => (string) SystemConfig::getValue('wxgame_api_domain'),
            'callbackDomain' => $callbackDomain,
            'currency' => $this->wxgameCurrency(),
            'signatureRequired' => (int) (SystemConfig::getValue('wxgame_callback_signature_required') ?: 1) === 1,
            'endpoints' => [
                'verify' => $callbackDomain . '/verify',
                'balance' => $callbackDomain . '/balance',
                'bet' => $callbackDomain . '/bet',
                'win' => $callbackDomain . '/win',
                'refund' => $callbackDomain . '/refund',
            ],
            'availableEndpointBases' => [
                'notify' => $appUrl . '/notify',
                'api_wxgame' => $appUrl . '/api/wxgame',
                'api_notify' => $appUrl . '/api/notify',
            ],
        ]);
    }

    public function wxgameVerify(Request $request)
    {
        if ($error = $this->wxgameVerifyCallbackSignature($request)) return $error;
        $token = $request->input('token');
        if ($token === null || $token === '') $token = $request->input('playerToken');
        if ($token === null || $token === '') $token = $request->input('player_token');
        if ($token === null || $token === '') $token = $request->input('player_token_id');
        $payload = $this->parseWxgamePlayerToken($token);
        if (!$payload) return $this->wxgameJson(1006, null, 'Invalid player token');
        $user = User::find((int) $payload['uid']);
        if (!$user || $this->isBlockedApiUser($user)) return $this->wxgameJson(1012, null, 'Player not found');
        return $this->wxgameJson(0, ['playerId' => 'M' . $user->id, 'balance' => round((float) $user->balance, 2), 'currency' => $this->wxgameCurrency()]);
    }

    public function wxgameBalance(Request $request)
    {
        if ($error = $this->wxgameVerifyCallbackSignature($request)) return $error;
        $user = $this->wxgameUserByPlayerId($request->input('playerId'));
        if (!$user || $this->isBlockedApiUser($user)) return $this->wxgameJson(1012, null, 'Player not found');
        return $this->wxgameJson(0, $this->wxgameBalancePayload($user));
    }

    public function wxgameBet(Request $request)
    {
        if ($error = $this->wxgameVerifyCallbackSignature($request)) return $error;
        $payload = $request->all();
        if ($error = $this->wxgameValidateCurrency($payload)) return $error;
        $transactionId = (string)($payload['transactionId'] ?? '');
        $amount = round((float)($payload['bet'] ?? 0), 2);
        if ($transactionId === '' || $amount <= 0) return $this->wxgameJson(1005, null, 'Invalid parameters');
        $user = $this->wxgameUserByPlayerId($payload['playerId'] ?? '');
        if (!$user || $this->isBlockedApiUser($user)) return $this->wxgameJson(1012, null, 'Player not found');
        if ($this->wxgameRecordExists($transactionId)) {
            return $this->wxgameJson(0, $this->wxgameBalancePayload($user));
        }
        $platform = strtoupper(trim((string)($payload['gameBrand'] ?? 'WXGAME')));
        $gameType = trim((string)($payload['gameType'] ?? ''));
        $gameCode = trim((string)($payload['gameId'] ?? ''));
        if ($hit = $this->gameRestrictionHit($user, $platform, $gameType, $gameCode)) {
            return $this->wxgameJson(1005, null, $this->tcgRestrictionMessage($hit, 'game access restricted'));
        }
        if ($limit = $this->amountExceedsPlayerLimit($user, $amount, $platform, $gameType, $gameCode)) {
            return $this->wxgameJson(1005, null, $this->tcgRestrictionMessage($limit, 'transfer amount exceeds player limit'));
        }

        return DB::transaction(function () use ($user, $payload, $amount, $transactionId) {
            $locked = User::where('id', $user->id)->lockForUpdate()->first();
            if ($this->wxgameRecordExists($transactionId)) {
                return $this->wxgameJson(0, $this->wxgameBalancePayload($locked));
            }
            if ((float)$locked->balance < $amount) return $this->wxgameJson(1011, null, 'Insufficient balance');
            $before = (float)$locked->balance;
            $locked->balance = round($before - $amount, 2);
            $locked->save();
            $this->wxgameCreateTransferLog($locked, $payload, 2, $amount, $before, (float)$locked->balance, 'WXGame bet');
            $this->wxgameCreateGameRecord($locked, $payload, $amount, -$amount);
            return $this->wxgameJson(0, $this->wxgameBalancePayload($locked));
        });
    }

    public function wxgameWin(Request $request)
    {
        if ($error = $this->wxgameVerifyCallbackSignature($request)) return $error;
        $payload = $request->all();
        if ($error = $this->wxgameValidateCurrency($payload)) return $error;
        $transactionId = (string)($payload['transactionId'] ?? '');
        $amount = round((float)($payload['win'] ?? 0), 2);
        if ($transactionId === '' || $amount < 0) return $this->wxgameJson(1005, null, 'Invalid parameters');
        $user = $this->wxgameUserByPlayerId($payload['playerId'] ?? '');
        if (!$user || $this->isBlockedApiUser($user)) return $this->wxgameJson(1012, null, 'Player not found');
        if ($this->wxgameRecordExists($transactionId)) {
            return $this->wxgameJson(0, $this->wxgameBalancePayload($user));
        }
        if ($error = $this->wxgameValidateRelatedBet($payload, $user, null, false)) return $error;

        return DB::transaction(function () use ($user, $payload, $amount, $transactionId) {
            $locked = User::where('id', $user->id)->lockForUpdate()->first();
            if ($this->wxgameRecordExists($transactionId)) {
                return $this->wxgameJson(0, $this->wxgameBalancePayload($locked));
            }
            $before = (float)$locked->balance;
            $locked->balance = round($before + $amount, 2);
            $locked->save();
            $this->wxgameCreateTransferLog($locked, $payload, 3, $amount, $before, (float)$locked->balance, 'WXGame win');
            $this->wxgameCreateGameRecord($locked, $payload, 0, $amount);
            return $this->wxgameJson(0, $this->wxgameBalancePayload($locked));
        });
    }

    public function wxgameRefund(Request $request)
    {
        if ($error = $this->wxgameVerifyCallbackSignature($request)) return $error;
        $payload = $request->all();
        if ($error = $this->wxgameValidateCurrency($payload)) return $error;
        $transactionId = (string)($payload['transactionId'] ?? '');
        $amount = round((float)($payload['bet'] ?? 0), 2);
        if ($transactionId === '' || $amount <= 0) return $this->wxgameJson(1005, null, 'Invalid parameters');
        $user = $this->wxgameUserByPlayerId($payload['playerId'] ?? '');
        if (!$user || $this->isBlockedApiUser($user)) return $this->wxgameJson(1012, null, 'Player not found');
        if ($this->wxgameRecordExists($transactionId)) {
            return $this->wxgameJson(0, $this->wxgameBalancePayload($user));
        }
        if ($error = $this->wxgameValidateRelatedBet($payload, $user, $amount, true)) return $error;

        return DB::transaction(function () use ($user, $payload, $amount, $transactionId) {
            $locked = User::where('id', $user->id)->lockForUpdate()->first();
            if ($this->wxgameRecordExists($transactionId)) {
                return $this->wxgameJson(0, $this->wxgameBalancePayload($locked));
            }
            $before = (float)$locked->balance;
            $locked->balance = round($before + $amount, 2);
            $locked->save();
            $this->wxgameCreateTransferLog($locked, $payload, 4, $amount, $before, (float)$locked->balance, 'WXGame refund');
            $this->wxgameCreateGameRecord($locked, $payload, 0, $amount);
            return $this->wxgameJson(0, $this->wxgameBalancePayload($locked));
        });
    }

    public function imageProxy(Request $request)
    {
        $url = trim($request->query('url', ''));
        if ($url === '') {
            $url = $this->decodeImageProxyUrl(trim($request->query('u', '')));
        }
        if (!$this->isAllowedImageProxyUrl($url)) {
            abort(404);
        }

        $hash = sha1($url);
        $dir = public_path('uploads/xy281-assets/game-proxy/'.substr($hash, 0, 2));
        $file = $dir.'/'.$hash.'.cache';
        $mimeFile = $file.'.mime';

        if (!is_file($file)) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            $image = $this->fetchRemoteImage($url);
            if ($image === false || strlen($image) < 1) {
                abort(404);
            }
            if (strlen($image) > 8 * 1024 * 1024) {
                abort(413);
            }

            $mime = $this->detectImageMime($image);
            if (!$mime) {
                abort(415);
            }

            file_put_contents($file, $image, LOCK_EX);
            file_put_contents($mimeFile, $mime, LOCK_EX);
        }

        $mime = is_file($mimeFile) ? trim(file_get_contents($mimeFile)) : $this->detectImageMime(file_get_contents($file));

        return response()->file($file, [
            'Content-Type' => $mime ?: 'image/jpeg',
            'Cache-Control' => 'public, max-age=2592000',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function decodeImageProxyUrl($value)
    {
        if ($value === '') {
            return '';
        }

        $value = strtr($value, '-_', '+/');
        $padding = strlen($value) % 4;
        if ($padding) {
            $value .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($value, true);
        return $decoded === false ? '' : $decoded;
    }

    private function isAllowedImageProxyUrl($url)
    {
        if (!$url || !preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        $parts = parse_url($url);
        if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }

        $scheme = strtolower($parts['scheme']);
        if (!in_array($scheme, ['http', 'https'])) {
            return false;
        }

        $ip = gethostbyname($parts['host']);
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return false;
        }

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function fetchRemoteImage($url)
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_MAXREDIRS => 3,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_USERAGENT => 'XY281ImageProxy/1.0',
            ]);
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status >= 200 && $status < 300 && $body !== false) {
                return $body;
            }

            return false;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 12,
                'header' => "User-Agent: XY281ImageProxy/1.0\r\n",
            ],
        ]);

        return @file_get_contents($url, false, $context);
    }

    private function detectImageMime($content)
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($content);

        return $mime && strpos($mime, 'image/') === 0 ? $mime : null;
    }

    private function formatGameImage($path)
    {
        $path = trim((string)$path);
        if ($path === '') {
            return '';
        }

        $marker = '/uploads/http';
        $pos = strpos($path, $marker);
        if ($pos !== false) {
            return substr($path, $pos + strlen('/uploads/'));
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        if (strpos($path, '//') === 0) {
            return 'https:' . $path;
        }

        $baseUrl = rtrim(env('APP_URL'), '/');
        if (strpos($path, '/uploads/') === 0) {
            return $baseUrl . $path;
        }

        return $baseUrl . '/uploads/' . ltrim($path, '/');
    }

    private function enabledApiCodes()
    {
        return $this->cachedEnabledApiCodes();
    }

    private function cachedEnabledApiCodes()
    {
        return Cache::remember('public_game_enabled_api_codes:v1', now()->addSeconds(60), function () {
            $codes = Api::where('state', 1)->where('app_state', 1)->pluck('api_code')->toArray();
            $data = [];
            foreach ($codes as $code) {
                $data[strtoupper($code)] = true;
            }
            return $data;
        });
    }

    private function cachedPublicGameList($platform, $category, $full)
    {
        $platform = trim((string) $platform);
        $category = trim((string) $category);
        $full = (bool) $full;
        $cacheKey = 'public_game_list:v1:' . md5($platform . '|' . $category . '|' . ($full ? '1' : '0'));

        return Cache::remember($cacheKey, now()->addSeconds(60), function () use ($platform, $category, $full) {
            $columns = $full
                ? ['id','name','name_en','platform_name','category_id','game_code','app_state','is_hot','is_new','is_recommend','check_yes_img','check_no_img','api_logo_img','mobile_img','app_img','header_logo']
                : ['id','name','name_en','platform_name','category_id','game_code','app_state','is_hot','is_new','is_recommend','api_logo_img','mobile_img','app_img'];
            $list = GameList::when($platform, function ($query) use ($platform) {
                return $query->where('platform_name', $platform);
            })->when($category, function ($query) use ($category) {
                return $query->where('category_id', $category);
            })->where('is_top',1)->where('app_state',1)->where('site_state',1)
                ->select($columns)
                ->orderBy('order_by', 'asc')
                ->get()
                ->toArray();

            $enabledApis = $this->cachedEnabledApiCodes();
            $rows = [];
            foreach ($list as $value) {
                if (!isset($enabledApis[strtoupper($value['platform_name'])])) {
                    continue;
                }

                $img = $value['api_logo_img'] ?: $value['mobile_img'];
                if ($full) {
                    $row = $value;
                    $row['gamepic'] = $this->formatGameImage($img);
                    $row['img'] = $this->formatGameImage($img);
                    $row['api_code'] = $value['platform_name'];
                    $row['platform_code'] = $value['platform_name'];
                    $row['type_code'] = $value['category_id'];
                    $row['game_type'] = $value['category_id'];
                    $row['type_name'] = $this->gameCategoryLabel($value['category_id']);
                    $row['category_name'] = $this->gameCategoryLabel($value['category_id']);
                    $row['check_yes_img'] = $this->formatGameImage($value['check_yes_img']);
                    $row['check_no_img'] = $this->formatGameImage($value['check_no_img']);
                    $row['api_logo_img'] = $this->formatGameImage($value['api_logo_img']);
                    $row['mobile_img'] = $this->formatGameImage($value['mobile_img']);
                    $row['header_logo'] = $this->formatGameImage($value['header_logo']);
                    $rows[] = $row;
                    continue;
                }

                $rows[] = [
                    'id' => $value['id'] ?? null,
                    'gamepic' => $this->formatGameImage($img),
                    'img' => $this->formatGameImage($img),
                    'api_code' => $value['platform_name'],
                    'platform_code' => $value['platform_name'],
                    'type_code' => $value['category_id'],
                    'type_name' => $this->gameCategoryLabel($value['category_id']),
                    'category_name' => $this->gameCategoryLabel($value['category_id']),
                    'catecode' => $value['platform_name'],
                    'platform_name' => $value['platform_name'],
                    'gamename' => $value['name'],
                    'name' => $value['name'],
                    'gamecode' => $value['game_code'],
                    'game_code' => $value['game_code'],
                    'gametype' => $value['category_id'],
                    'category_id' => $value['category_id'],
                    'app_state' => $value['app_state'],
                    'is_hot' => (int)($value['is_hot'] ?? 0),
                    'is_new' => (int)($value['is_new'] ?? 0),
                    'is_recommend' => (int)($value['is_recommend'] ?? 0),
                ];
            }

            return $rows;
        });
    }

    public function credit(Request $request)
    {
        $api_code = $request->input('api_code');
		$tg = New TgService;
		$data = $tg->credit($api_code);
        return $data;
    }
    /**
     * 首页轮播图列表。
     */
    public function bannerList(Request $request)
    {
        $type = $request->input('type') ?? 2;
        $bannerlist = array(
            ["src"=>"/assets/promotions/welcome-banner.png","background"=>"#f4f6ff"],
            ["src"=>"/assets/promotions/deposit-banner.png","background"=>"rgb(100, 61, 202)"],
            );
        $noticeQuery = Banner::where('type', $type);
        if ($this->hasColumn('banners', 'state')) {
            $noticeQuery->where('state', 1);
        }
        if ($this->hasColumn('banners', 'order')) {
            $noticeQuery->orderBy('order', 'desc');
        }
        $notice = $noticeQuery->orderBy('id', 'desc')->select("pic as src","jump_url")->get()->toArray();

        if(count($notice)){
            $bannerlist=[];
            foreach ($notice as $val){
                $bannerlist[]=[
                    "src"=>$this->formatUploadUrl($val['src'] ?? ''),
                    "background"=>"#f4f6ff",
                    'url'=>$val['jump_url'] ?? ''
                ] ;
            }
        }
        return $this->returnMsg(200, $bannerlist);
    }

    public function article(Request $request)
    {
        $type = $request->input('type');
        $data = Article::where('cateid',$type)->first();
        return $this->returnMsg(200,$data);
    }

    /**
     * 平台开关状态。
     */
    public function Systemstatus()
    {
         $isclose = SystemConfig::query()->find("isclose");
         $data =[];
        if($isclose['value']){
            $webcontent = SystemConfig::query()->find("webcontent");
            $data['content'] = $webcontent['value'];
            $data['isclose'] = 0;
        }else{
            $data['content'] = '';
            $data['isclose'] = 0;
        }
        return $this->returnMsg(200, $data);
    }


    /**
     * 闁氨鐓￠崗顒€鎲￠崚妤勩€?
     *
     * @return void
     */
    public function uservip(Request $request)
    {
        $vip = UserVip::orderBy('id', 'asc')->get();
        foreach ($vip as $item) {
            $item->vipname = $item->vipname ?? 'VIP'.$item->id;
            $item->vippic = isset($item->vippic) ? $this->formatUploadUrl($item->vippic) : '';
        }
        return $this->returnMsg(200, $vip);
    }
    /**
     * 闁氨鐓￠崗顒€鎲￠崚妤勩€?
     *
     * @return void
     */
    public function homenotice(Request $request)
    {
        $notice = Article::where('cateid',6)->limit(3)->select("id", "name", "content")->get();
        return $this->returnMsg(200, $notice);
    }
    /**
     * 闁氨鐓￠崗顒€鎲￠崚妤勩€?
     *
     * @return void
     */
    public function homecontent(Request $request)
    {
        $notice = Article::where('cateid','<>',6)->get();
        return $this->returnMsg(200, $notice);
    }
    /**
     * 闁氨鐓￠崗顒€鎲￠崚妤勩€?
     *
     * @return void
     */
    public function homenoticelist(Request $request)
    {
        $notice = Article::where('cateid',6)->paginate(10);
        return $this->returnMsg(200, $notice);
    }
    /**
     * 闁氨鐓￠崗顒€鎲￠崚妤勩€?
     *
     * @return void
     */
    public function homenoticedeatil(Request $request)
    {
        $data = $request->all();
        $notice = Article::where('id',$data['id'])->first();
        return $this->returnMsg(200, $notice);
    }
    /**
     * 游戏分类列表。
     */
    public function cateList(Request $request)
    {
        $list = [
            ["id"=>1,"pid"=>0,"name"=>"电子游艺","enname"=>"concise"],
            ["id"=>2,"pid"=>0,"name"=>"棋牌游戏","enname"=>"joker"],
            ["id"=>3,"pid"=>0,"name"=>"视讯直播","enname"=>"realbet"],
            ["id"=>4,"pid"=>0,"name"=>"彩票游戏","enname"=>"lottery"],
            ["id"=>5,"pid"=>0,"name"=>"电竞游戏","enname"=>"gaming"],
            ["id"=>6,"pid"=>0,"name"=>"体育赛事","enname"=>"sport"],
        ];
        return $this->returnMsg(200, $list);
    }
    /**
     * 娑擃亙姹夊☉鍫熶紖
     *
     * @return void
     */
    public function noticeList(Request $request)
    {
        $rules = [
            'limit' => 'nullable|integer',
            'page' => 'nullable|integer',
        ];
        $this->validate($request, $rules, $this->messages);
        $data = $request->all();
        $limit = $data['limit'] ?? 10;
        $page = $data['page'] ?? 1;

        // 获取登录用户，用于筛选可见消息。
        $token = $request->header('authorization');
        $token = str_replace('Bearer ','',$token) ;
        $user = User::where('api_token',$token)->first();

        // 初始化消息查询。
        $query = Message::query();

        // 公共消息。
        $query->where(function($q) {
            $q->where('user_id', 0)
              ->where('vip_id', 0)
              ->where('isagent', 0);
        });

        // 代理可见消息。
        if ($user->isagent == 1) {
            $query->orWhere('isagent', 1);
        }

        // 按 VIP 等级可见的消息。
        $query->orWhere(function($q) use ($user) {
            $q->where('vip_id', $user->vip)
              ->where('isagent', '!=', 2);
        });

        // 指定给当前用户的消息。
        $query->orWhere('user_id', $user->id);

        $list = $query->orderBy('id', 'desc')->paginate($limit, ['*'], 'page', $page);

        return $this->returnMsg(200, $list);
    }
    /**
     * 活动类型列表。
     */
    public function activityType(Request $request)
    {
        $locale = $this->promotionLocaleFromRequest($request);
        $columns = $this->selectExistingColumns('activity_types', ['id', 'name', 'enname', 'icon', 'state', 'sort_order', 'created_at']);
        $query = ActivityType::select($columns ?: ['*']);
        if ($this->hasColumn('activity_types', 'state')) {
            $query->where('state', 1);
        }
        if ($this->hasColumn('activity_types', 'sort_order')) {
            $query->orderBy('sort_order', 'desc');
        }
        $list = $query->orderBy('id', 'asc')->get();
        foreach ($list as $item) {
            $adminName = (string) ($item->name ?? '');
            $item->name = $this->activityTypePublicName($item, $locale);
            $item->admin_name = $adminName;
            $item->enname = $item->enname ?? '';
            $item->icon = isset($item->icon) ? $this->formatUploadUrl($item->icon) : '';
        }
        return $this->returnMsg(200, $list);
    }

    /**
     * 活动列表。
     */
    public function activityList(Request $request)
    {
        $rules = [
            'type' => 'nullable|integer'
        ];
        $this->validate($request, $rules, $this->messages);
        $type = $request->input('type', '');
        $channel = $this->promotionChannel($request);
        $locale = $this->promotionLocaleFromRequest($request);
        $rows = [];
        foreach ($this->promotionVisibleActivities($request, $type) as $activity) {
            $rows[] = $this->legacyPromotionPayload($activity, $channel, false, $locale);
        }

        $page = max(1, (int) $request->input('page', 1));
        $perPage = max(1, min(99, (int) $request->input('pagesize', $request->input('limit', 99))));
        $list = new LengthAwarePaginator(
            array_slice($rows, ($page - 1) * $perPage, $perPage),
            count($rows),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return $this->returnMsg(200, $list);
    }
    /**
     * 活动详情。
     */
    public function activitydeatil(Request $request)
    {
        $rules = [
            'id' => 'nullable|integer'
        ];
        $this->validate($request, $rules, $this->messages);
        $id = $request->input('id', 0);
        $channel = $this->promotionChannel($request);
        $locale = $this->promotionLocaleFromRequest($request);
        $activity = Activity::with('type_data')->where('id', $id)->first();
        if (!$activity || empty((new PromotionService())->visible([$activity], $channel))) {
            return $this->returnMsg(200, $this->legacyPromotionFallback($id), 'activity not found');
        }

        return $this->returnMsg(200, $this->legacyPromotionPayload($activity, $channel, true, $locale));
    }

    /**
     * 閼惧嘲褰囩€广垺婀囬柧鐐复
     *
     * @return void
     */
    public function getServicerUrl(Request $request)
    {
        return $this->returnMsg(200, $this->customerServicePayload($this->requestPlayerLevel($request)));
    }

    public function liveChatSession(Request $request)
    {
        $unavailable = $this->liveChatUnavailableResponse();
        if ($unavailable) {
            return $unavailable;
        }

        $session = $this->findOrCreateLiveChatSession($request);
        if ($session instanceof \Illuminate\Http\JsonResponse) {
            return $session;
        }

        $this->markLiveChatReadByUser($session->id);

        return $this->returnMsg(200, [
            'session' => $this->formatLiveChatSession($session),
            'messages' => $this->liveChatMessageRows($session->id),
            'poll_interval' => 2500,
        ], 'success');
    }

    public function liveChatMessages(Request $request)
    {
        $unavailable = $this->liveChatUnavailableResponse();
        if ($unavailable) {
            return $unavailable;
        }

        $session = $this->findOrCreateLiveChatSession($request);
        if ($session instanceof \Illuminate\Http\JsonResponse) {
            return $session;
        }

        $this->markLiveChatReadByUser($session->id);
        $afterId = max(0, (int) $request->input('after_id', 0));

        return $this->returnMsg(200, [
            'session' => $this->formatLiveChatSession($session),
            'messages' => $this->liveChatMessageRows($session->id, $afterId),
        ], 'success');
    }

    public function liveChatSend(Request $request)
    {
        $unavailable = $this->liveChatUnavailableResponse();
        if ($unavailable) {
            return $unavailable;
        }

        $session = $this->findOrCreateLiveChatSession($request);
        if ($session instanceof \Illuminate\Http\JsonResponse) {
            return $session;
        }

        $content = trim((string) $request->input('content', $request->input('message', '')));
        if ($content === '') {
            return $this->returnMsg(422, [], 'Message content is required');
        }

        $content = mb_substr($content, 0, 1000);
        $user = $this->activeUserFromBearer($request);
        $now = now();
        $messageId = DB::table('live_chat_messages')->insertGetId([
            'session_id' => $session->id,
            'user_id' => $user ? $user->id : null,
            'admin_id' => null,
            'sender_type' => 'user',
            'content' => $content,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('live_chat_sessions')->where('id', $session->id)->update([
            'status' => 'open',
            'last_message' => $content,
            'last_message_at' => $now,
            'last_user_message_at' => $now,
            'admin_unread_count' => DB::raw('admin_unread_count + 1'),
            'closed_at' => null,
            'updated_at' => $now,
        ]);

        $freshSession = DB::table('live_chat_sessions')->where('id', $session->id)->first();
        $message = DB::table('live_chat_messages')->where('id', $messageId)->first();

        return $this->returnMsg(200, [
            'session' => $this->formatLiveChatSession($freshSession),
            'message' => $this->formatLiveChatMessage($message),
        ], 'success');
    }

    public function liveChatClose(Request $request)
    {
        $unavailable = $this->liveChatUnavailableResponse();
        if ($unavailable) {
            return $unavailable;
        }

        $session = $this->findOrCreateLiveChatSession($request);
        if ($session instanceof \Illuminate\Http\JsonResponse) {
            return $session;
        }

        DB::table('live_chat_sessions')->where('id', $session->id)->update([
            'status' => 'closed',
            'closed_at' => now(),
            'updated_at' => now(),
        ]);

        $freshSession = DB::table('live_chat_sessions')->where('id', $session->id)->first();

        return $this->returnMsg(200, [
            'session' => $this->formatLiveChatSession($freshSession),
        ], 'success');
    }

    protected function liveChatUnavailableResponse()
    {
        if ((int) SystemConfig::getValue('internal_live_chat_enabled') !== 1) {
            return $this->returnMsg(403, [], 'Live chat is disabled');
        }

        if (!Schema::hasTable('live_chat_sessions') || !Schema::hasTable('live_chat_messages')) {
            return $this->returnMsg(503, [], 'Live chat is not ready');
        }

        return null;
    }

    protected function findOrCreateLiveChatSession(Request $request)
    {
        $user = $this->activeUserFromBearer($request);
        $visitorId = $this->liveChatVisitorId($request);
        if (!$user && $visitorId === '') {
            return $this->returnMsg(422, [], 'Visitor id is required');
        }

        $sessionId = (int) $request->input('session_id', 0);
        if ($sessionId > 0) {
            $session = $this->liveChatSessionQuery($user, $visitorId)
                ->where('id', $sessionId)
                ->where('status', '<>', 'closed')
                ->first();
            if ($session) {
                return $this->attachLiveChatUser($session, $user, $visitorId);
            }
        }

        $session = $this->liveChatSessionQuery($user, $visitorId)
            ->where('status', '<>', 'closed')
            ->orderByDesc('id')
            ->first();
        if ($session) {
            return $this->attachLiveChatUser($session, $user, $visitorId);
        }

        $now = now();
        $id = DB::table('live_chat_sessions')->insertGetId([
            'session_no' => $this->makeLiveChatSessionNo(),
            'visitor_id' => $visitorId ?: null,
            'user_id' => $user ? $user->id : null,
            'username' => $user ? (string) $user->username : ('游客-' . substr($visitorId, -6)),
            'status' => 'open',
            'last_message' => '',
            'last_message_at' => null,
            'last_user_message_at' => null,
            'last_admin_message_at' => null,
            'admin_unread_count' => 0,
            'user_unread_count' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return DB::table('live_chat_sessions')->where('id', $id)->first();
    }

    protected function liveChatSessionQuery($user, $visitorId)
    {
        $query = DB::table('live_chat_sessions')->whereNull('deleted_at');
        if ($user) {
            $query->where(function ($builder) use ($user, $visitorId) {
                $builder->where('user_id', $user->id);
                if ($visitorId !== '') {
                    $builder->orWhere('visitor_id', $visitorId);
                }
            });
        } else {
            $query->whereNull('user_id')->where('visitor_id', $visitorId);
        }

        return $query;
    }

    protected function attachLiveChatUser($session, $user, $visitorId)
    {
        if ($user && ((int) ($session->user_id ?? 0) === 0 || (string) ($session->username ?? '') === '')) {
            DB::table('live_chat_sessions')->where('id', $session->id)->update([
                'user_id' => $user->id,
                'username' => (string) $user->username,
                'visitor_id' => $visitorId ?: $session->visitor_id,
                'updated_at' => now(),
            ]);

            return DB::table('live_chat_sessions')->where('id', $session->id)->first();
        }

        return $session;
    }

    protected function liveChatVisitorId(Request $request)
    {
        $visitorId = trim((string) $request->input('visitor_id', $request->header('X-Visitor-Id', '')));
        if ($visitorId === '') {
            return '';
        }

        $visitorId = mb_substr($visitorId, 0, 100);
        return preg_match('/^[A-Za-z0-9._:-]{8,100}$/', $visitorId) ? $visitorId : '';
    }

    protected function liveChatMessageRows($sessionId, $afterId = 0)
    {
        return DB::table('live_chat_messages')
            ->whereNull('deleted_at')
            ->where('session_id', $sessionId)
            ->when($afterId > 0, function ($query) use ($afterId) {
                $query->where('id', '>', $afterId);
            })
            ->orderBy('id')
            ->limit(100)
            ->get()
            ->map(function ($message) {
                return $this->formatLiveChatMessage($message);
            })
            ->values()
            ->all();
    }

    protected function markLiveChatReadByUser($sessionId)
    {
        DB::table('live_chat_sessions')->where('id', $sessionId)->update([
            'user_unread_count' => 0,
            'updated_at' => now(),
        ]);

        DB::table('live_chat_messages')
            ->where('session_id', $sessionId)
            ->where('sender_type', 'admin')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    protected function makeLiveChatSessionNo()
    {
        do {
            $sessionNo = 'LC' . date('YmdHis') . random_int(1000, 9999);
        } while (DB::table('live_chat_sessions')->where('session_no', $sessionNo)->exists());

        return $sessionNo;
    }

    protected function formatLiveChatSession($session)
    {
        return [
            'id' => (int) $session->id,
            'session_no' => (string) $session->session_no,
            'visitor_id' => (string) ($session->visitor_id ?? ''),
            'user_id' => (int) ($session->user_id ?? 0),
            'username' => (string) ($session->username ?? ''),
            'status' => (string) $session->status,
            'last_message' => (string) ($session->last_message ?? ''),
            'last_message_at' => (string) ($session->last_message_at ?? ''),
            'last_user_message_at' => (string) ($session->last_user_message_at ?? ''),
            'last_admin_message_at' => (string) ($session->last_admin_message_at ?? ''),
            'admin_unread_count' => (int) ($session->admin_unread_count ?? 0),
            'user_unread_count' => (int) ($session->user_unread_count ?? 0),
            'created_at' => (string) ($session->created_at ?? ''),
            'updated_at' => (string) ($session->updated_at ?? ''),
        ];
    }

    protected function formatLiveChatMessage($message)
    {
        return [
            'id' => (int) $message->id,
            'session_id' => (int) $message->session_id,
            'sender_type' => (string) $message->sender_type,
            'is_admin' => (string) $message->sender_type === 'admin',
            'content' => (string) $message->content,
            'created_at' => (string) ($message->created_at ?? ''),
        ];
    }

    public function workOrderList(Request $request)
    {
        $user = $this->activeUserFromBearer($request);
        if (!$user) {
            return $this->returnMsg(401, [], 'Authentication required');
        }

        $pageSize = max(1, min(50, (int) $request->input('page_size', $request->input('limit', 10))));
        $query = \App\Models\WorkOrder::where('user_id', $user->id)->orderBy('id', 'desc');

        $status = trim((string) $request->input('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        $list = $query->paginate($pageSize);
        $rows = [];
        foreach ($list as $item) {
            $rows[] = $this->formatWorkOrder($item, false);
        }

        return $this->returnMsg(200, [
            'current_page' => $list->currentPage(),
            'data' => $rows,
            'per_page' => $list->perPage(),
            'total' => $list->total(),
            'last_page' => $list->lastPage(),
            'totalPages' => $list->lastPage(),
        ], 'success');
    }

    public function workOrderDetail(Request $request, $id = null)
    {
        $user = $this->activeUserFromBearer($request);
        if (!$user) {
            return $this->returnMsg(401, [], 'Authentication required');
        }

        $id = $id ?: $request->input('id', $request->input('work_order_id', $request->input('order_id')));
        $workOrder = \App\Models\WorkOrder::with('replies')
            ->where('user_id', $user->id)
            ->where('id', (int) $id)
            ->first();

        if (!$workOrder) {
            return $this->returnMsg(404, [], 'Work order not found');
        }

        return $this->returnMsg(200, $this->formatWorkOrder($workOrder, true), 'success');
    }

    public function workOrderCreate(Request $request)
    {
        $user = $this->activeUserFromBearer($request);
        if (!$user) {
            return $this->returnMsg(401, [], 'Authentication required');
        }

        $title = trim((string) $request->input('title', ''));
        $content = trim((string) $request->input('content', $request->input('message', '')));
        $category = trim((string) $request->input('category', 'general')) ?: 'general';
        $priority = trim((string) $request->input('priority', 'normal')) ?: 'normal';

        if ($title === '') {
            $title = mb_substr($content, 0, 30);
        }
        if ($title === '' || $content === '') {
            return $this->returnMsg(422, [], 'Title and content are required');
        }

        $allowedPriorities = ['low', 'normal', 'high', 'urgent'];
        if (!in_array($priority, $allowedPriorities, true)) {
            $priority = 'normal';
        }

        $workOrder = \App\Models\WorkOrder::create([
            'order_no' => $this->makeWorkOrderNo($user->id),
            'user_id' => $user->id,
            'username' => $user->username,
            'title' => mb_substr($title, 0, 200),
            'content' => $content,
            'category' => mb_substr($category, 0, 50),
            'priority' => $priority,
            'status' => 'pending',
        ]);

        \App\Models\WorkOrderReply::create([
            'work_order_id' => $workOrder->id,
            'user_id' => $user->id,
            'content' => $content,
            'type' => 'user',
        ]);

        return $this->returnMsg(200, $this->formatWorkOrder($workOrder->fresh('replies'), true), 'success');
    }

    public function workOrderReply(Request $request, $id = null)
    {
        $user = $this->activeUserFromBearer($request);
        if (!$user) {
            return $this->returnMsg(401, [], 'Authentication required');
        }

        $id = $id ?: $request->input('id', $request->input('work_order_id', $request->input('order_id')));
        $content = trim((string) $request->input('content', $request->input('message', '')));
        if ($content === '') {
            return $this->returnMsg(422, [], 'Reply content is required');
        }

        $workOrder = \App\Models\WorkOrder::where('user_id', $user->id)->where('id', (int) $id)->first();
        if (!$workOrder) {
            return $this->returnMsg(404, [], 'Work order not found');
        }
        if ($workOrder->status === 'closed') {
            return $this->returnMsg(422, [], 'Work order is closed');
        }

        \App\Models\WorkOrderReply::create([
            'work_order_id' => $workOrder->id,
            'user_id' => $user->id,
            'content' => $content,
            'type' => 'user',
        ]);

        $workOrder->status = 'open';
        $workOrder->save();

        return $this->returnMsg(200, $this->formatWorkOrder($workOrder->fresh('replies'), true), 'success');
    }

    public function workOrderClose(Request $request, $id = null)
    {
        $user = $this->activeUserFromBearer($request);
        if (!$user) {
            return $this->returnMsg(401, [], 'Authentication required');
        }

        $id = $id ?: $request->input('id', $request->input('work_order_id', $request->input('order_id')));
        $workOrder = \App\Models\WorkOrder::where('user_id', $user->id)->where('id', (int) $id)->first();
        if (!$workOrder) {
            return $this->returnMsg(404, [], 'Work order not found');
        }

        $workOrder->status = 'closed';
        $workOrder->closed_at = date('Y-m-d H:i:s');
        $workOrder->save();

        return $this->returnMsg(200, $this->formatWorkOrder($workOrder->fresh('replies'), true), 'success');
    }

    protected function makeWorkOrderNo($userId)
    {
        do {
            $orderNo = 'WO' . date('YmdHis') . (int) $userId . random_int(1000, 9999);
        } while (\App\Models\WorkOrder::where('order_no', $orderNo)->exists());

        return $orderNo;
    }

    protected function formatWorkOrder(\App\Models\WorkOrder $workOrder, $withReplies = false)
    {
        $row = [
            'id' => (int) $workOrder->id,
            'order_no' => (string) $workOrder->order_no,
            'orderNo' => (string) $workOrder->order_no,
            'title' => (string) $workOrder->title,
            'content' => (string) $workOrder->content,
            'category' => (string) $workOrder->category,
            'priority' => (string) $workOrder->priority,
            'status' => (string) $workOrder->status,
            'admin_reply' => (string) ($workOrder->admin_reply ?? ''),
            'adminReply' => (string) ($workOrder->admin_reply ?? ''),
            'admin_reply_time' => $workOrder->admin_reply_time ? (string) $workOrder->admin_reply_time : '',
            'created_at' => (string) $workOrder->created_at,
            'updated_at' => (string) $workOrder->updated_at,
            'closed_at' => $workOrder->closed_at ? (string) $workOrder->closed_at : '',
        ];

        if ($withReplies) {
            $row['replies'] = [];
            foreach ($workOrder->replies as $reply) {
                $row['replies'][] = [
                    'id' => (int) $reply->id,
                    'work_order_id' => (int) $reply->work_order_id,
                    'content' => (string) $reply->content,
                    'type' => (string) $reply->type,
                    'is_admin' => $reply->type === 'admin',
                    'created_at' => (string) $reply->created_at,
                ];
            }
        }

        return $row;
    }


    /**
     * 游戏列表。
     */
    public function getGameList(Request $request)
    {
        $platform = $request->input('platform_name') ?? ($request->input('platform') ?? '');
        $category = $request->input('game_type') ?? ($request->input('category') ?? '');
        $gamelist = $this->cachedPublicGameList($platform, $category, false);

        return $this->returnMsg(200,$gamelist);
    }

    /**
     * 游戏启动地址。
     */
    public function getGameUrl(Request $request)
    {
        $rules = [
            'plat_name' => 'required',
            'game_type' => 'required',
            'game_code' => 'nullable',
            'is_mobile_url' => 'nullable',
            'lang' => 'nullable',
            'locale' => 'nullable',
            'language' => 'nullable',
            'game_lang' => 'nullable',
        ];

        $this->validate($request, $rules, $this->messages);
        $data = $request->all();

        $api_code = strtoupper(trim((string)$data['plat_name']));
        $rawGameType = trim((string)($data['game_type'] ?? ''));
        $rawGameCode = trim((string)($data['game_code'] ?? ''));
        $is_mobile_url = $data['is_mobile_url'] ?? 1;
        $gameLang = $this->normalizeGameLang($data['game_lang'] ?? $data['lang'] ?? $data['locale'] ?? $data['language'] ?? $request->header('Lang'));

        $categoryMap = $this->gameCategoryMap();
        $normalizedGame = $this->normalizeGameRequest($rawGameType, $rawGameCode, $categoryMap);
        $gameType = $normalizedGame['game_type'];
        $gameCode = $normalizedGame['game_code'];
        $leixing = $categoryMap[$gameType] ?? (preg_match('/^[1-7]$/', $gameType) ? $gameType : '1');

        if (!$this->isApiPlayable($api_code)) {
            return $this->returnMsg(500, [], 'game platform is closed');
        }

        if (!$this->isGamePlayable($api_code, $gameType, $gameCode)) {
            return $this->returnMsg(500, [], 'game is closed or missing');
        }

        $token = $request->header('authorization');
        $token = str_replace('Bearer ','',$token) ;

        $user = User::where('api_token',$token)->lockForUpdate()->first();
        if (!$user) {
            return $this->returnMsg(500, [], 'login expired');
        }

        if ($hit = $this->gameRestrictionHit($user, $api_code, $gameType, $gameCode)) {
            return $this->returnMsg(500, [], $this->tcgRestrictionMessage($hit, 'game access restricted'));
        }

        $tg = new TgService;
        $wxgameEnabled = $tg->isWxgameEnabled();
		$User_Api = User_Api::whereRaw('UPPER(api_code) = ?', [$api_code])->where('user_id',$user->id)->first();

        if ($wxgameEnabled) {
            if (!$User_Api) {
                $User_Api = User_Api::create([
                    'user_id' => $user->id,
                    'api_user' => $user->username,
                    'api_pass' => 123456,
                    'api_code' => $api_code,
                ]);
            }
            $wxToken = $this->createWxgamePlayerToken($user, $api_code, $gameCode);
            $res = $tg->wxgameLogin($wxToken, $api_code, $gameCode, $this->normalizeWxgameLanguage($gameLang));
        } else {
            if(!$User_Api){
                $result = $tg->register($api_code,$user->username);
                if($result['code'] != 200){
                    return $this->returnMsg(201, '', $result['message']);
                }
                $arr = [
                    'user_id' => $user->id,
                    'api_user' => $user->username,
                    'api_pass' => 123456,
                    'api_code' => $api_code,
                ];
                $User_Api = User_Api::create($arr);
            }
            if($user->transferstatus == 1){
                $mz = $this->allmz($api_code,$user->id);
                if($mz['code'] != 200){
                    return $this->returnMsg(500,[],$mz['message']);
                }
            }
            $res = $tg->login($user->username, $api_code, $leixing, $is_mobile_url, $gameCode, $gameLang);
        }
        if ($res['code'] == 200) {

            return $this->returnMsg(200, ['url' => $res['data']]);
        } else {
            return $this->returnMsg(500,$res,$res['message']);
        }
    }

    protected function normalizeGameLang($lang)
    {
        $value = strtolower(str_replace('_', '-', trim((string)$lang)));
        if ($value === '') {
            return 'zh-cn';
        }
        if (strpos($value, 'zh') === 0) {
            return 'zh-cn';
        }
        if (strpos($value, 'en') === 0) {
            return 'en';
        }
        if (strpos($value, 'pt') === 0) {
            return 'pt';
        }
        if (strpos($value, 'es') === 0) {
            return 'es';
        }
        if (strpos($value, 'vi') === 0) {
            return 'vi';
        }
        if (strpos($value, 'th') === 0) {
            return 'th';
        }
        if (strpos($value, 'id') === 0) {
            return 'id';
        }
        return 'en';
    }

    protected function gameCategoryMap()
    {
        return [
            'realbet' => '1',
            'live' => '1',
            'fishing' => '2',
            'fish' => '2',
            'concise' => '3',
            'slot' => '3',
            'slots' => '3',
            'lottery' => '4',
            'lhc' => '4',
            'jsc' => '4',
            'jwc' => '4',
            'qkc' => '4',
            'sport' => '5',
            'joker' => '6',
            'chess' => '6',
            'poker' => '6',
            'table' => '6',
            'card' => '6',
            'gaming' => '7',
            'esport' => '7',
        ];
    }

    protected function normalizeGameRequest($rawGameType, $rawGameCode, array $categoryMap)
    {
        if ($this->isGameCategory($rawGameType, $categoryMap)) {
            $gameType = strtolower(trim((string)$rawGameType));
            $gameCode = $rawGameCode;
        } else {
            $gameType = strtolower(trim((string)$rawGameCode));
            $gameCode = $rawGameType;
        }

        $gameCode = $this->normalizeGameCode($gameCode);
        if ($gameCode === '') {
            $gameCode = '0';
        }

        return [
            'game_type' => $gameType,
            'game_code' => $gameCode,
        ];
    }

    protected function isGameCategory($value, array $categoryMap)
    {
        $value = strtolower(trim((string)$value));
        return isset($categoryMap[$value]) || preg_match('/^[1-7]$/', $value);
    }

    protected function isApiPlayable($apiCode)
    {
        return Api::whereRaw('UPPER(api_code) = ?', [strtoupper(trim((string)$apiCode))])
            ->where('state', 1)
            ->where('app_state', 1)
            ->exists();
    }

    protected function isGamePlayable($apiCode, $gameType, $gameCode)
    {
        $gameCode = $this->normalizeGameCode($gameCode);
        $categories = $this->localCategoriesForGameType($gameType);
        if (!$categories) {
            return false;
        }

        $query = GameList::whereRaw('UPPER(platform_name) = ?', [strtoupper(trim((string)$apiCode))])
            ->whereIn('category_id', $categories)
            ->where('site_state', 1)
            ->where('app_state', 1)
            ->where('is_top', 1);

        $gameCodeKey = strtolower($gameCode);
        if (!in_array($gameCodeKey, ['0', 'lobby'], true)) {
            $query->where('game_code', $gameCode);
        } else {
            $query->where(function ($query) {
                $query->where('game_code', '0')
                    ->orWhere('game_code', 'lobby')
                    ->orWhereNull('game_code')
                    ->orWhere('game_code', '');
            });
        }

        return $query->exists();
    }

    protected function localCategoriesForGameType($gameType)
    {
        $gameType = strtolower(trim((string)$gameType));
        $numberToCategory = [
            '1' => 'realbet',
            '2' => 'fishing',
            '3' => 'concise',
            '4' => 'lottery',
            '5' => 'sport',
            '6' => 'joker',
            '7' => 'gaming',
        ];
        $category = $numberToCategory[$gameType] ?? $gameType;

        $groups = [
            'realbet' => ['realbet', 'live'],
            'live' => ['live', 'realbet'],
            'fishing' => ['fishing', 'fish'],
            'fish' => ['fish', 'fishing'],
            'concise' => ['concise', 'slot', 'slots'],
            'slot' => ['slot', 'slots', 'concise'],
            'slots' => ['slots', 'slot', 'concise'],
            'lottery' => ['lottery', 'lhc', 'jsc', 'jwc', 'qkc'],
            'lhc' => ['lottery', 'lhc', 'jsc', 'jwc', 'qkc'],
            'jsc' => ['lottery', 'lhc', 'jsc', 'jwc', 'qkc'],
            'jwc' => ['lottery', 'lhc', 'jsc', 'jwc', 'qkc'],
            'qkc' => ['lottery', 'lhc', 'jsc', 'jwc', 'qkc'],
            'sport' => ['sport'],
            'joker' => ['joker', 'chess', 'poker', 'table', 'card'],
            'chess' => ['chess', 'joker', 'poker', 'table', 'card'],
            'poker' => ['poker', 'table', 'joker', 'chess', 'card'],
            'table' => ['table', 'poker', 'joker', 'chess', 'card'],
            'card' => ['card', 'poker', 'table', 'joker', 'chess'],
            'gaming' => ['gaming', 'esport'],
            'esport' => ['esport', 'gaming'],
        ];

        if (isset($groups[$category])) {
            return $groups[$category];
        }

        if (!isset($this->gameCategoryMap()[$category])) {
            return [];
        }

        return [$category];
    }

    protected function gameCategoryLabel($category)
    {
        $category = strtolower(trim((string)$category));
        $labels = [
            'realbet' => 'Live Casino',
            'live' => 'Live Casino',
            'fishing' => 'Fishing',
            'fish' => 'Fishing',
            'concise' => 'Slots',
            'slot' => 'Slots',
            'slots' => 'Slots',
            'lottery' => 'Lottery',
            'lhc' => 'Lottery',
            'jsc' => 'Lottery',
            'jwc' => 'Lottery',
            'qkc' => 'Lottery',
            'sport' => 'Sports',
            'joker' => 'Cards',
            'chess' => 'Cards',
            'poker' => 'Poker',
            'table' => 'Poker',
            'card' => 'Cards',
            'gaming' => 'Arcade',
            'esport' => 'Esports',
        ];

        return $labels[$category] ?? ucfirst($category);
    }
    protected function normalizeGameCode($value)
    {
        return trim((string)$value, " \t\n\r\0\x0B'\"");
    }


    public function allmz($plat_name,$userid){
        $user = User::where('id',$userid)->first();
        if (!$user) {
            return ['code' => 201, 'message' => 'login expired'];
        }

        $transferService = new SafeGameTransferService();
        return $transferService->autoMoveToPlatform($user, $plat_name, new TgService);
	}

    /**
     * 转入三方游戏账户。
     */
    public function transToTgAccount($user,$plat_name, $game_type)
    {
        if (!$user) {
            return false;
        }

        $plat_name = ($plat_name == 'fgdz') ? 'fg' : $plat_name;
        $transferService = new SafeGameTransferService();
        $result = $transferService->autoMoveToPlatform($user, $plat_name, new TgService);

        return ($result['code'] ?? 201) == 200;
    }
    /**
     * 娑撳鏁炵拋鏉跨秿
     *
     * @param Request $request
     * @return void
     */
    public function betRecord(Request $request)
    {

        $data = $request->all();
        $start = $end = '';
        if (isset($data['date'])) {
            switch($data['date']){
                case 1:
                    list($start, $end) = [date('Y-m-d 00:00:00',time()), date('Y-m-d 23:59:59',time())];
                    break;
                case 2:
                    list($start, $end) =  [date('Y-m-d 00:00:00',time()-7*60*60*24), date('Y-m-d 23:59:59',time())];
                    break;
                case 3:
                    list($start, $end) =[date('Y-m-d 00:00:00',time()-15*60*60*24), date('Y-m-d 23:59:59',time())];
                    break;
                case 4:
                    list($start, $end) =[date('Y-m-d 00:00:00',time()-30*60*60*24), date('Y-m-d 23:59:59',time())];
                    break;
                case 5:
                    list($start, $end) = [date('Y-m-d 00:00:00',time()-1*60*60*24), date('Y-m-d 23:59:59',time()-1*60*60*24)];
                    break;
                case 6:
                    $weekStart = strtotime('monday this week');
                    list($start, $end) = [date('Y-m-d 00:00:00', $weekStart), date('Y-m-d 23:59:59',time())];
                    break;
                case 7:
                    $lastWeekStart = strtotime('monday last week');
                    $lastWeekEnd = strtotime('sunday last week');
                    list($start, $end) = [date('Y-m-d 00:00:00', $lastWeekStart), date('Y-m-d 23:59:59', $lastWeekEnd)];
                    break;
            }
        }
        $api_type = $data['api_type'] ?? '';

        $token = $request->header('authorization');
        $token = str_replace('Bearer ','',$token) ;
        $user = User::where('api_token',$token)->first();
        $pagesize = isset($data['pagesize']) ? $data['pagesize'] : 10 ;

                $list = GameRecord::where('user_id', $user->id)

                  ->when($api_type, function ($query) use ($api_type) {
                        return $query->where('platform_type', strtolower($api_type));
                    })
                    ->when($start, function ($query) use ($start) {
                        return $query->where('created_at', '>=', $start);
                    })->when($end, function ($query) use ($end) {
                        return $query->where('created_at', '<=', $end);
                    })->orderBy('id', 'desc')->select('bet_id','bet_time','platform_type','bet_amount','win_loss','status')->paginate($pagesize);
                    foreach ($list as $k => $v) {
                        $list[$k]['Code'] =$this->game_list[$v['platform_type']] ?? '';

                    }

        return $this->returnMsg(200, $list);
    }

    /**
     * 閼惧嘲褰囧〒鍛婂灆
     *
     * @return void
     */
    public function getdogame()
    {
        $gamelist = $this->game_list;
        //$game =[];
       // foreach ($gamelist as $key=>$val){
       //     $game[]=['id'=>$key,'name'=>$val];
       // }
        unset($gamelist['universal']);
        return $this->returnMsg(200, $gamelist);
    }


    /**
     * 娴溿倖妲楃拋鏉跨秿
     *
     * @return void
     */
    public function transRecord(Request $request)
    {
        $data = $request->all();
        $token = $request->header('authorization');
        $token = str_replace('Bearer ','',$token) ;
        $user = User::where('api_token',$token)->first();
        $start = $end = '';
        if (isset($data['date'])) {
            switch($data['date']){
                case 1:
                    list($start, $end) = [date('Y-m-d 00:00:00',time()), date('Y-m-d 23:59:59',time())];
                    break;
                case 2:
                    list($start, $end) =  [date('Y-m-d 00:00:00',time()-7*60*60*24), date('Y-m-d 23:59:59',time())];
                    break;
                case 3:
                    list($start, $end) =[date('Y-m-d 00:00:00',time()-15*60*60*24), date('Y-m-d 23:59:59',time())];
                    break;
                case 4:
                    list($start, $end) =[date('Y-m-d 00:00:00',time()-30*60*60*24), date('Y-m-d 23:59:59',time())];
                    break;
            }
        }
        $type = $data['type'];
        $api_type = $data['api_type'] ?? '';
        $pagesize = isset($data['pagesize']) ? $data['pagesize'] : 10 ;
        $gamelist = $this->gamemoney_list;

        $pay_way =[1=>'Bank card',2 => '',3=>'Alipay',4=>'WeChat',5 => 'USDT-TRC20',6 => 'USDT-ERC20', 10 => 'USDT'];
        switch ($type) {
            case 1:
                $list = Recharge::where('user_id', $user->id)
                    ->when($start, function ($query) use ($start) {
                        return $query->where('created_at', '>=', $start);
                    })->when($end, function ($query) use ($end) {
                        return $query->where('created_at', '<=', $end);
                    })->orderBy('id', 'desc')->select('amount','created_at','state','pay_way','out_trade_no')->paginate($pagesize);
                    foreach ($list as $k => $v) {
                        $list[$k]['pay_way'] = $pay_way[$v['pay_way']];
                        $list[$k]['amount'] = abs($v['amount']);
                    }
                break;
            case 2:
                $pay_way = [0 => 'Bank card',1 => 'Bank card',2 => 'USDT-TRC20',3 => 'USDT-ERC20'];
                $list = Withdraw::where('user_id', $user->id)
                    ->when($start, function ($query) use ($start) {
                        return $query->where('created_at', '>=', $start);
                    })->when($end, function ($query) use ($end) {
                        return $query->where('created_at', '<=', $end);
                    })->orderBy('id', 'desc')->select('real_money','created_at','state','order_no as out_trade_no','type')->paginate($pagesize);
                    foreach ($list as $k => $v) {
                        $list[$k]['pay_way'] = $pay_way[$v['type']];
                        $list[$k]['amount'] = abs($v['real_money']);
                    }
                break;
            case 3:
                $list = TransferLog::where('user_id', $user->id)->where('transfer_type', 0)
                    ->when($start, function ($query) use ($start) {
                        return $query->where('created_at', '>=', $start);
                    })->when($end, function ($query) use ($end) {
                        return $query->where('created_at', '<=', $end);
                    })->when($api_type,function ($query) use ($api_type){
                        return $query->where('api_type',$api_type);
                    })->select('real_money','created_at','state','api_type')->orderBy('id', 'desc')->paginate($pagesize);

                    foreach ($list as $k => $v) {
                        $list[$k]['pay_way'] = $gamelist[$v['api_type']];
                        $list[$k]['amount'] = abs($v['real_money']);
                    }
                break;
            case 4:
                $list = TransferLog::where('user_id', $user->id)->whereIn('transfer_type', [1,3])
                    ->when($start, function ($query) use ($start) {
                        return $query->where('created_at', '>=', $start);
                    })->when($end, function ($query) use ($end) {
                        return $query->where('created_at', '<=', $end);
                    })->when($api_type,function ($query) use ($api_type){
                        return $query->where('api_type',$api_type);
                    })->select('real_money','created_at','state','api_type')->orderBy('id', 'desc')->paginate($pagesize);
                    foreach ($list as $k => $v) {
                        if($v['api_type']=='web'){
                            $list[$k]['pay_way'] = 'Wallet';
                        }else{
                            $list[$k]['pay_way'] = $gamelist[$v['api_type']];
                        }

                        $list[$k]['amount'] = abs($v['real_money']);
                    }
                break;
            default:
                // code...
                break;
        }

        return $this->returnMsg(200, $list);

    }


    /**
     * 娴溿倖妲楃拋鏉跨秿
     *
     * @return void
     */
    public function rechargeRecord(Request $request)
    {
        $data = $request->all();
        $start = $end = '';
        if (isset($data['time'])) {
            list($start, $end) = [$data['time'][0], $data['time'][1]];
        }

        $list = Recharge::where('user_id', Auth::id())
            ->when($start, function ($query) use ($start) {
                return $query->where('created_at', '>=', $start);
            })->when($end, function ($query) use ($end) {
                return $query->where('created_at', '<=', $end);
            })->orderBy('id', 'desc')->paginate(10);

        foreach ($list as $k => $v) {
            $list[$k]['type'] = ($v->pay_way == 10) ? 'USDT' : 'Bank card';
        }

        return $this->returnMsg(200, $list);
    }

    public function WithdrawRecord(Request $request)
    {
        $data = $request->all();
        $start = $end = '';
        if (isset($data['time'])) {
            list($start, $end) = [$data['time'][0], $data['time'][1]];
        }

        $list = Withdraw::where('user_id', Auth::id())
            ->when($start, function ($query) use ($start) {
                return $query->where('created_at', '>=', $start);
            })->when($end, function ($query) use ($end) {
                return $query->where('created_at', '<=', $end);
            })->orderBy('id', 'desc')->paginate(10);

        foreach ($list as $k => $v) {
            $list[$k]['state'] = $this->state[$v->state];
            $list[$k]['out_trade_no'] = $v->order_sn;
            $list[$k]['type'] = 'Bank card';
        }

        return $this->returnMsg(200, $list);
    }

    public function userbalancelist(Request $request){
        $data = $request->all();
        $token = $request->header('authorization');
        $token = str_replace('Bearer ','',$token) ;
        $user = User::where('api_token',$token)->first();
        $Api = Api::where('state',1)->orderBy('order_by', 'asc')->get()->toArray();
		$data = array();
        foreach($Api as $key => $v){
			$User_Api = User_Api::where('api_code',$v['api_code'])->where('user_id',$user->id)->first();
            $data[$key]['balance'] = $User_Api ? sprintf("%.2f",$User_Api->api_money) : 0;
			$data[$key]['name'] = $v['api_name'];
			$data[$key]['platname'] = $v['api_code'];
			$data[$key]['app_icon'] = env('APP_URL').'/uploads/'.$v['app_icon'];
		}
        return $this->returnMsg(200, $data);
    }
    public function userapimoney(Request $request)
    {
        $api_code = $request->route('api_code');
        $token = $request->header('authorization');
        $token = str_replace('Bearer ','',$token) ;
        $user = User::where('api_token',$token)->first();
		$User_Api = User_Api::where('api_code',$api_code)->where('user_id',$user->id)->first();
		$tg = New TgService;
		if(!$User_Api){
			$result = $tg->register($api_code,$user->username);
            if($result['code'] != 200){
				return $this->returnMsg(201, '', $result['message']);
			}
			$arr = [
				'user_id' => $user->id,
				'api_user' => $user->username,
				'api_pass' => 123456,
				'api_code' => $api_code,
			];
			$User_Api = User_Api::create($arr);
		}
        $result = $tg->balance($api_code,$user->username);
		if($result['code'] != 200){
			return $this->returnMsg(201, '', $result['message']);
		}
		$User_Api->api_money = $result['data'];
		$User_Api->save();
        return $this->returnMsg(200,['balance' => $result['data']]);
    }
    public function uptransferstatus(Request $request){
            $data = $request->all();
            $token = $request->header('authorization');
            $token = str_replace('Bearer ','',$token) ;
            $user = User::where('api_token',$token)->first();
            $user->update($data);
            return $this->returnMsg(200, '', 'success');
    }

    public function fanshui(Request $request){
        $data = $request->all();
        $user = $this->apiUser($request);
        if (!$user) {
            return $this->returnMsg(100, [], 'login expired');
        }
        $start = $end = '';
        $pagesize = isset($data['pagesize']) ? $data['pagesize'] : 10 ;
        if (isset($data['date'])) {
            switch($data['date']){
                case 1:
                    list($start, $end) = [date('Y-m-d 00:00:00',time()), date('Y-m-d 23:59:59',time())];
                    break;
                case 2:
                    list($start, $end) =  [date('Y-m-d 00:00:00',time()-7*60*60*24), date('Y-m-d 23:59:59',time())];
                    break;
                case 3:
                    list($start, $end) =[date('Y-m-d 00:00:00',time()-15*60*60*24), date('Y-m-d 23:59:59',time())];
                    break;
                case 4:
                    list($start, $end) =[date('Y-m-d 00:00:00',time()-30*60*60*24), date('Y-m-d 23:59:59',time())];
                    break;
            }
        }
        $api_type = $data['api_type'] ?? '';
        $type =  $data['type'] ?? '';


        $lists = TransferLog::where('user_id', $user->id)->where('transfer_type', 6)
            ->when($start, function ($query) use ($start) {
                return $query->where('created_at', '>=', $start);
            })->when($end, function ($query) use ($end) {
                return $query->where('created_at', '<=', $end);
            })->when($api_type, function ($query) use ($api_type) {
                return $query->where('platform_type', '=', $api_type);
            })->when($type, function ($query) use ($type) {
                return $query->where('state', '=', ($type-1));
            })->orderBy('id', 'desc')->paginate($pagesize);

        foreach ($lists as $k => $v) {
            $lists[$k]['gamename'] = $this->game_list[$v['platform_type']] ?? ($v['platform_type'] ?? '');
        }
         $list['list'] = $lists;
         $list['jisuan'] = TransferLog::where('user_id', $user->id)->where('transfer_type', 6)->where('state', 1)->sum('real_money');
         $list['nojisuan'] = TransferLog::where('user_id',  $user->id)->where('transfer_type', 6)->where('state', 0)->sum('real_money');
        return $this->returnMsg(200, $list);
    }

    public function dofanshui(Request $request)
    {
            $user = $this->apiUser($request);
            if (!$user) {
                return $this->returnMsg(100, [], 'login expired');
            }
                $betlist = TransferLog::where('user_id', $user->id)->where('state', 0)->where('transfer_type', 6)->select('betid')->get();
                $userfanshui = TransferLog::where('user_id', $user->id)->where('state', 0)->where('transfer_type', 6)->sum('real_money');
                if ($userfanshui) {
                    $userinfo = Users::where('id', $user->id)->lockForUpdate()->first();
                    if (!$userinfo) {
                        return $this->returnMsg(202, '', 'No eligible records');
                    }
                    $userinfo->balance = $userinfo->balance + $userfanshui;
                    $userinfo->save();
                    TransferLog::where('user_id', $user->id)
                        ->where('state', 0)
                        ->update(['state' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
                    $betidarray=[];
                    foreach ($betlist as $val){
                        $betidarray[]=$val['betid'];
                    }

                    GameRecord::where('user_id', $user->id)->whereIn('bet_id', $betidarray)->update(['is_back' => 1, 'updated_at' => date('Y-m-d H:i:s')]);

                    return $this->returnMsg(200, '', 'success');
                } else {
                    return $this->returnMsg(202, '', 'No eligible records');
                }

    }

    public function banklist()
    {
        $banklist = Bank::where('state', 1)->get();
        foreach ($banklist as &$val){
            $val->ico= env('APP_URL').'/uploads/'. $val->bank_img;
        }
         return $this->returnMsg(200, $banklist);
    }

    public function getpaybank()
    {
		$cardlist = PaySetting::where('state',1)->get();
		foreach ($cardlist as &$val){
			if($val->bank_data->bank_name!='USDT'){
				$val->ico= env('APP_URL').'/uploads/'. $val->bank_data->bank_img;
			}else{
				$val->ico='';
			}
		}
         return $this->returnMsg(200, $cardlist);
    }

    public function doactivity(Request $request){
            $data = $request->all();
            $user = $this->apiUser($request);
            if (!$user) {
                return $this->returnMsg(100, [], 'login expired');
            }

            $activityId = (int)($data['activityid'] ?? ($data['activity_id'] ?? ($data['id'] ?? 0)));
            if ($activityId <= 0) {
                return $this->returnMsg(202, '', 'No eligible records');
            }

            $activity = Activity::where('id', $activityId)->first();
            if(empty($activity)){
                return $this->returnMsg(202, '', 'No eligible records');
            }

            if ((int)($activity->state ?? 0) !== 1 || (int)($activity->can_apply ?? 0) !== 1) {
                return $this->returnMsg(202, '', 'No eligible records');
            }

            if ($hit = $this->activityBlacklistHit($user, $activityId)) {
                return $this->returnMsg(202, '', $this->activityBlacklistMessage($hit, 'No eligible records'));
            }

            $couponCheck = $this->validateActivityCouponForApply($request, $user, $activityId);
            if (!$couponCheck['ok']) {
                return $this->returnMsg(202, '', $couponCheck['message']);
            }

            $isapple = ActivityApply::where("user_id",$user->id)->where('activity_id',$activityId)->first();
            if($isapple){
                if($isapple->state==1){
                    return $this->returnMsg(202, '', 'No eligible records');
                }
                if($isapple->state==2){
                    return $this->returnMsg(202, '', 'No eligible records');
                }
                if($isapple->state==3){
                    return $this->returnMsg(202, '', 'No eligible records');
                }
            }

            try {
                $created = DB::transaction(function () use ($activityId, $couponCheck, $user) {
                    $created = ActivityApply::create($this->activityApplyPayload($activityId, $user, $couponCheck));
                    if (!$this->markActivityCouponUsed($couponCheck['coupon'], $user)) {
                        throw new \RuntimeException('activity coupon consume failed');
                    }

                    return $created;
                });
            } catch (\Throwable $e) {
                if (stripos($e->getMessage(), 'Duplicate') !== false || stripos($e->getMessage(), 'activity_apply_activity_id_user_id_unique') !== false) {
                    return $this->returnMsg(202, '', 'No eligible records');
                }

                \Illuminate\Support\Facades\Log::error('activity apply failed', [
                    'user_id' => $user->id,
                    'activity_id' => $activityId,
                    'message' => $e->getMessage(),
                ]);
                return $this->returnMsg(500, '', 'activity apply failed');
            }

            return $this->returnMsg($created ? 200 : 500, '', $created ? 'activity apply success' : 'activity apply failed');

    }

    public function activityApplyLog(Request $request)
    {
        $user = $this->apiUser($request);
        if (!$user) {
            return $this->returnMsg(100, [], 'login expired');
        }
        $limit = $request->input('limit') ?? 10;
        $list = ActivityApply::with('activity_data')->where("user_id",$user->id)->paginate($limit);
        foreach ($list as $k => $v) {
            $activity = $v->activity_data;
            $list[$k]->activity_name = $activity ? ($activity->title ?? '') : '';
            $list[$k]->activity_banner = $activity ? $this->formatUploadUrl($activity->banner ?? '') : '';
        }
        return $this->returnMsg(200,$list);
    }
    /**
     * 閻劍鍩涢幍鈧張澶愭懕鐞涘苯宕?
     */
    public function getAllUserCard(Request $request)
    {
     $token = $request->header('authorization');
     $token = str_replace('Bearer ','',$token) ;
            $user = User::where('api_token',$token)->first();
        if (!$user) {
            return $this->returnMsg(401, [], 'Authentication required');
        }
        $list = UserCard::where('user_id', $user->id)->get();
        foreach ($list as &$val){
			if($val->bank!='USDT' && $val->bank != 'ebpay'){
				$banklist = Bank::where('bank_name', $val->bank)->first();
				$val->ico= $banklist ? env('APP_URL').'/uploads/'. $banklist->bank_img : '';
			}else{
				$val->ico='';
			}
        }
        return $this->returnMsg(200, $list);
    }

    /**
     * 获取系统银行卡信息。
     */
    public function systemBankCardInfo(Request $request)
    {
        $data = $request->all();
        if($data['payType']!=1){
            $card = PaySetting::where('state', 1)->where('bank_id','>', 1)->first();
        }else{
            $card = PaySetting::where('state', 1)->where('bank_id', 1)->first();
        }

        return $this->returnMsg(200, $card);
    }


    public function gameslist(Request $request)
    {
        $data = $request->all();
        $tg = new TgService;
        $gamelist = $tg->gameslist($data['gamecode']);
        $gamelist = $gamelist['data'];
       return $this->returnMsg(200, $gamelist);
    }

    public function  messagecenter(Request $request){
     $token = $request->header('authorization');
     $token = str_replace('Bearer ','',$token) ;
        $user = User::where('api_token',$token)->first();

        $data = $request->all();

        // 初始化消息查询。
        $query = Message::where('type', $data['type']);

        // 公共消息。
        $query->where(function($q) {
            $q->where('user_id', 0)
              ->where('vip_id', 0)
              ->where('isagent', 0);
        });

        // 代理可见消息。
        if ($user->isagent == 1) {
            $query->orWhere('isagent', 1);
        }

        // 按 VIP 等级可见的消息。
        $query->orWhere(function($q) use ($user) {
            $q->where('vip_id', $user->vip)
              ->where('isagent', '!=', 2);
        });

        // 指定给当前用户的消息。
        $query->orWhere('user_id', $user->id);

        $list = $query->paginate(10);
        foreach ($list as $k => &$v) {
            $user_message = UserMessage::where('message_id', $v->id)->count();
            $v->is_read = $user_message ?? 0;
            $v->desc = mb_substr(strip_tags($v->content),0,20,'utf-8');
        }

       return $this->returnMsg(200, $list);
    }

    public function  message(Request $request){
     $token = $request->header('authorization');
     $token = str_replace('Bearer ','',$token) ;
            $user = User::where('api_token',$token)->first();

        $data = $request->all();

        // 初始化消息查询。
        $query = Message::where('id', $data['id']);

        // 公共消息。
        $query->where(function($q) {
            $q->where('user_id', 0)
              ->where('vip_id', 0)
              ->where('isagent', 0);
        });

        // 代理可见消息。
        if ($user->isagent == 1) {
            $query->orWhere('isagent', 1);
        }

        // 按 VIP 等级可见的消息。
        $query->orWhere(function($q) use ($user) {
            $q->where('vip_id', $user->vip)
              ->where('isagent', '!=', 2);
        });

        // 指定给当前用户的消息。
        $query->orWhere('user_id', $user->id);

        $list = $query->first();


       return $this->returnMsg(200, $list);
    }

    private function firstConfiguredUrl($value)
    {
        $items = array_values(array_filter(array_map('trim', explode(',', (string) $value))));

        return $items[0] ?? '';
    }

    private function uploadUrl($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        if (strpos($path, '/assets/') === 0) {
            return rtrim(env('APP_URL'), '/') . $path;
        }

        return rtrim(env('APP_URL'), '/') . '/uploads/' . ltrim($path, '/');
    }

    private function tableColumns($table)
    {
        try {
            return Schema::getColumnListing($table);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function hasColumn($table, $column)
    {
        return in_array($column, $this->tableColumns($table));
    }

    private function selectExistingColumns($table, array $columns)
    {
        $existing = $this->tableColumns($table);
        if (!$existing) {
            return $columns;
        }

        return array_values(array_intersect($columns, $existing));
    }

    private function promotionVisibleActivities(Request $request, $type = '')
    {
        $columns = $this->selectExistingColumns('activities', [
            'id',
            'title',
            'type',
            'entitle',
            'content',
            'encontent',
            'memo',
            'enmemo',
            'apply_count',
            'banner',
            'app_img',
            'can_apply',
            'state',
            'app_state',
            'sort_order',
            'starts_at',
            'ends_at',
            'is_popup',
            'popup_frequency',
            'popup_delay_seconds',
            'popup_image',
            'app_popup_image',
            'detail_image',
            'app_detail_image',
            'action_url',
            'button_text',
            'requires_auth',
            'created_at',
        ]);

        $query = Activity::with('type_data')->select($columns ?: ['*']);
        if ((int) $type > 0) {
            $query->where('type', (int) $type);
        }

        return (new PromotionService())->visible($query->get()->all(), $this->promotionChannel($request));
    }

    private function legacyPromotionPayload(Activity $activity, $channel, $full = false, $locale = 'zh')
    {
        $banner = $channel === 'mobile'
            ? (($activity->app_img ?? '') ?: ($activity->banner ?? ''))
            : (($activity->banner ?? '') ?: ($activity->app_img ?? ''));
        $popupImage = $channel === 'mobile'
            ? (($activity->app_popup_image ?? '') ?: ($activity->popup_image ?? '') ?: $banner)
            : (($activity->popup_image ?? '') ?: ($activity->app_popup_image ?? '') ?: $banner);
        $detailImage = $channel === 'mobile'
            ? (($activity->app_detail_image ?? '') ?: ($activity->detail_image ?? '') ?: $banner)
            : (($activity->detail_image ?? '') ?: ($activity->app_detail_image ?? '') ?: $banner);
        $typeName = $this->activityTypePublicName($activity->type_data, $locale);
        $title = $this->promotionDisplayText($activity->title ?? '', $activity->entitle ?? '', $locale);
        $content = $this->promotionDisplayText($activity->content ?? '', $activity->encontent ?? '', $locale);
        $memo = $this->promotionDisplayText($activity->memo ?? '', $activity->enmemo ?? '', $locale);

        $row = [
            'id' => (int) ($activity->id ?? 0),
            'title' => $title,
            'entitle' => (string) ($activity->entitle ?? ''),
            'type' => (int) ($activity->type ?? 0),
            'type_name' => $typeName,
            'content' => $full ? $content : '',
            'memo' => $memo,
            'enmemo' => (string) ($activity->enmemo ?? ''),
            'apply_count' => (int) ($activity->apply_count ?? 0),
            'banner' => $this->formatUploadUrl($banner),
            'app_img' => $this->formatUploadUrl($activity->app_img ?? ''),
            'popup_image' => $this->formatUploadUrl($popupImage),
            'app_popup_image' => $this->formatUploadUrl($activity->app_popup_image ?? ''),
            'detail_image' => $this->formatUploadUrl($detailImage),
            'app_detail_image' => $this->formatUploadUrl($activity->app_detail_image ?? ''),
            'button_text' => $this->promotionButtonText($activity, $locale),
            'can_apply' => (int) ($activity->can_apply ?? 0),
            'requires_auth' => (int) ($activity->requires_auth ?? 0),
            'action_url' => (string) ($activity->action_url ?? ''),
            'is_popup' => (int) ($activity->is_popup ?? 0),
            'popup_frequency' => (string) (($activity->popup_frequency ?? '') ?: 'once'),
            'popup_delay_seconds' => (int) ($activity->popup_delay_seconds ?? 0),
            'sort_order' => (int) ($activity->sort_order ?? 0),
            'starts_at' => isset($activity->starts_at) ? (string) $activity->starts_at : '',
            'ends_at' => isset($activity->ends_at) ? (string) $activity->ends_at : '',
            'state' => (int) ($activity->state ?? 0),
            'app_state' => (int) ($activity->app_state ?? 0),
            'created_at' => isset($activity->created_at) ? (string) $activity->created_at : '',
        ];

        return $row;
    }

    private function legacyPromotionFallback($id)
    {
        return [
            'id' => (int) $id,
            'title' => '',
            'entitle' => '',
            'type' => 0,
            'type_name' => '',
            'content' => '',
            'memo' => '',
            'enmemo' => '',
            'apply_count' => 0,
            'banner' => '',
            'app_img' => '',
            'popup_image' => '',
            'app_popup_image' => '',
            'detail_image' => '',
            'app_detail_image' => '',
            'button_text' => '',
            'can_apply' => 0,
            'requires_auth' => 0,
            'action_url' => '',
            'is_popup' => 0,
            'popup_frequency' => 'once',
            'popup_delay_seconds' => 0,
            'sort_order' => 0,
            'starts_at' => '',
            'ends_at' => '',
            'state' => 0,
            'app_state' => 0,
        ];
    }

    private function promotionChannel(Request $request)
    {
        $channel = strtolower((string) $request->input('channel', ''));
        if (in_array($channel, ['mobile', 'app', 'h5'], true)) {
            return 'mobile';
        }
        if (in_array($channel, ['desktop', 'pc', 'web'], true)) {
            return 'desktop';
        }

        return preg_match('/Mobile|Android|iPhone|iPad|iPod/i', (string) $request->header('User-Agent'))
            ? 'mobile'
            : 'desktop';
    }

    private function promotionButtonText(Activity $activity, $locale = 'zh')
    {
        if ($this->hasColumn('activities', 'button_text')) {
            $configured = trim((string) ($activity->button_text ?? ''));
            if ($configured !== '' && !$this->promotionHasBrokenText($configured) && ($locale === 'th' || !$this->promotionHasThaiText($configured))) {
                return $configured;
            }
        }

        $url = trim((string) ($activity->action_url ?? ''));
        if ($locale !== 'th') {
            if ($url !== '') {
                if (stripos($url, 'recharge') !== false || stripos($url, 'deposit') !== false) {
                    return '立即充值';
                }
                if (stripos($url, 'support') !== false || stripos($url, 'service') !== false) {
                    return '联系客服';
                }

                return '查看详情';
            }

            return (int) ($activity->can_apply ?? 0) === 1 ? '申请活动' : '查看详情';
        }

        if ($url !== '') {
            if (stripos($url, 'recharge') !== false || stripos($url, 'deposit') !== false) {
                return 'เติมเงินทันที';
            }
            if (stripos($url, 'support') !== false || stripos($url, 'service') !== false) {
                return 'ติดต่อฝ่ายบริการ';
            }

            return 'ดูรายละเอียด';
        }

        return (int) ($activity->can_apply ?? 0) === 1 ? 'รับโปรโมชั่น' : 'ดูรายละเอียด';
    }

    private function promotionDisplayText($primary, $fallback, $locale = 'zh')
    {
        if ($locale === 'th') {
            $translated = trim((string) $fallback);
            if ($translated !== '' && !$this->promotionHasBrokenText($translated)) {
                return $translated;
            }
        }

        $primary = trim((string) $primary);
        if ($primary !== '' && !$this->promotionHasBrokenText($primary)) {
            return $primary;
        }

        $fallback = trim((string) $fallback);
        return $this->promotionHasBrokenText($fallback) ? '' : $fallback;
    }

    private function activityTypePublicName($type, $locale = 'zh')
    {
        if (!$type) {
            return '';
        }

        return $this->promotionDisplayText($type->name ?? '', $type->enname ?? '', $locale);
    }

    private function promotionLocaleFromRequest(Request $request)
    {
        $locale = (string) (
            $request->input('locale')
            ?: $request->input('language')
            ?: $request->input('lang')
            ?: $request->header('Lang')
            ?: $request->header('Accept-Language')
            ?: 'zh-CN'
        );
        $locale = strtolower(str_replace('_', '-', trim($locale)));

        return strpos($locale, 'th') === 0 ? 'th' : 'zh';
    }

    private function promotionHasThaiText($value)
    {
        return preg_match('/[\x{0E00}-\x{0E7F}]/u', (string) $value) === 1;
    }

    private function promotionHasBrokenText($value)
    {
        $text = (string) $value;
        if ($text === '') {
            return false;
        }
        if (preg_match('/[\x{F000}-\x{F8FF}\x{FFFD}]/u', $text) === 1) {
            return true;
        }

        foreach ([
            "\u{5599}\u{20AC}",
            "\u{5594}\u{66D5}",
            "\u{5594}\u{65B7}",
            "\u{5594}\u{FF40}",
            "\u{9435}",
            "\u{93BA}",
            "\u{942A}",
            "\u{947F}\u{6EDD}",
            "\u{95C1}\u{517C}",
            "\u{943E}",
            "\u{9395}",
        ] as $token) {
            if (strpos($text, $token) !== false) {
                return true;
            }
        }

        return false;
    }

    private function formatUploadUrl($path)
    {
        $path = trim((string) $path);
        if ($path === '') {
            return '';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        if (strpos($path, '/assets/') === 0) {
            return rtrim(env('APP_URL'), '/') . $path;
        }

        return rtrim(env('APP_URL'), '/') . '/uploads/' . ltrim($path, '/');
    }

    private function apiUser(Request $request)
    {
        $token = trim(str_replace('Bearer ', '', (string) $request->header('authorization')));
        if ($token === '') {
            return null;
        }

        return User::where('api_token', $token)->first();
    }

    private function requestPlayerLevel(Request $request)
    {
        $user = $this->apiUser($request);
        if (!$user) {
            return 0;
        }

        return max((int) ($user->vip ?? 0), (int) ($user->level ?? 0));
    }

    public function app()
    {
        $ios_download_url = SystemConfig::getValue('ios_download_url');
        $ios_download_qrcode = SystemConfig::getValue('ios_download_qrcode');
        $ios_download_qrcode = $this->uploadUrl($ios_download_qrcode);
        $h5_url = env('WAP_URL');
        $wap_url = $this->firstUsablePublicUrl(env('WAP_URL'));
        $pc_url = $this->firstUsablePublicUrl(env('PC_URL'));
        $app_url = $this->appPublicUrl();
        $agent_login_url = $this->agentLoginPublicUrl();
        $official_domain = SystemConfig::getValue('official_domain') ?: preg_replace('/^https?:\/\//i', '', $wap_url);
        $navigation_domains = SystemConfig::getValue('navigation_domains') ?: SystemConfig::getValue('safe_domain');
        $asset_domain = rtrim(SystemConfig::getValue('asset_domain') ?: '', '/');
        $sponsor_page_url_1 = SystemConfig::getValue('sponsor_page_url_1');
        $sponsor_page_url_2 = SystemConfig::getValue('sponsor_page_url_2');
        $agent_url = $this->agentPublicUrl();
        $servicePayload = $this->customerServicePayload($this->requestPlayerLevel(request()));
        $kf_url = $servicePayload['kf_url'];
        $url = $servicePayload['url'];
        $service_url = $servicePayload['service_url'];
        $service_link = $servicePayload['service_link'];
        $customer_service_url = $servicePayload['service_url'];
        $online_service_url = $servicePayload['service_url'];
        $service_type = $servicePayload['service_type'];
        $customer_service_configured = $servicePayload['configured'];
        $link_configured = $servicePayload['link_configured'];
        $work_order_enabled = $servicePayload['work_order_enabled'];
        $work_order_page_url = $servicePayload['work_order_page_url'];
        $work_order_list_url = $servicePayload['work_order_list_url'];
        $work_order_create_url = $servicePayload['work_order_create_url'];
        $work_order_detail_url = $servicePayload['work_order_detail_url'];
        $work_order_reply_url = $servicePayload['work_order_reply_url'];
        $work_order_close_url = $servicePayload['work_order_close_url'];
        $ws_enabled = $servicePayload['ws_enabled'];
        $stream_chat = $servicePayload['stream_chat'];
        $stream_config_url = $servicePayload['stream_config_url'];
        $stream_token_url = $servicePayload['stream_token_url'];
        $stream_channel_url = $servicePayload['stream_channel_url'];
        $mode = $servicePayload['mode'];
        $realtime_enabled = $servicePayload['realtime_enabled'];
        $realtime_provider = $servicePayload['realtime_provider'];
        $realtime_url = $servicePayload['realtime_url'];
        $livechat_url = $servicePayload['livechat_url'];
        $external_livechat_url = $servicePayload['external_livechat_url'];
        $internal_live_chat_enabled = $servicePayload['internal_live_chat_enabled'];
        $internal_live_chat_url = $servicePayload['internal_live_chat_url'];
        $fallback_url = $servicePayload['fallback_url'];
        $services = $servicePayload['services'];
        $customer_service = $servicePayload;
        $title = SystemConfig::getValue('site_title') ?? 'TH2.VIP';
        $redpacket_switch = SystemConfig::getValue('redpacket');
        $site_state = SystemConfig::getValue('site_state');
        $fanshui = SystemConfig::getValue('fanshui');
        $index_modal = SystemConfig::getValue('isclose');
        $repair_tips = SystemConfig::getValue('repair_tips');
        $webcontent = SystemConfig::getValue('webcontent');
        $site_logo = SystemConfig::getValue('site_logo');
        $site_logo = $this->uploadUrl($site_logo);
        $app_logo = SystemConfig::getValue('app_logo');
        $app_logo = $this->uploadUrl($app_logo);
        $download_bar_icon = $this->uploadUrl(SystemConfig::getValue('download_bar_icon')) ?: $app_logo ?: $site_logo;
        $login_bonus_img = $this->uploadUrl(SystemConfig::getValue('login_bonus_img'));
        $vip_rule_title_img = $this->uploadUrl(SystemConfig::getValue('vip_rule_title_img'));
        return $this->returnMsg(200,compact('ios_download_qrcode','ios_download_url','h5_url','wap_url','pc_url','app_url','agent_login_url','official_domain','navigation_domains','asset_domain','sponsor_page_url_1','sponsor_page_url_2','agent_url','url','kf_url','service_url','service_link','customer_service_url','online_service_url','service_type','customer_service_configured','link_configured','work_order_enabled','work_order_page_url','work_order_list_url','work_order_create_url','work_order_detail_url','work_order_reply_url','work_order_close_url','ws_enabled','stream_chat','stream_config_url','stream_token_url','stream_channel_url','mode','realtime_enabled','realtime_provider','realtime_url','livechat_url','external_livechat_url','internal_live_chat_enabled','internal_live_chat_url','fallback_url','services','customer_service','title','redpacket_switch','site_state','fanshui','index_modal','repair_tips','webcontent','site_logo','app_logo','download_bar_icon','login_bonus_img','vip_rule_title_img'));
    }


    public function applyagentdo(Request $request)
    {
        $data = $request->all();
        $token = $request->header('authorization');
        $token = str_replace('Bearer ','',$token) ;
        $user = User::where('api_token',$token)->first();

        $useragent = AgentApply::where('user_id',$user->id)->first();
         if ($useragent)return $this->returnMsg(500, '', 'Agent application already submitted');

            $arr = [
                'user_id' => $user->id,
                'apply_info' => $data['apply_info'],
                'state' => 1,
                'mobile' => $data['mobile'],
            ];
        if($res = AgentApply::create($arr)){
          return $this->returnMsg(200, '', 'success');
        }else{
        }
    }

    public function getAgentLoginUrl()
    {
        return $this->returnMsg(200, ['url' => $this->agentLoginPublicUrl()]);
    }

    public function getVisitUrl(Request $request) {
        $origin = $request->headers->get('origin') ?: $request->getSchemeAndHttpHost();
        if($this->isMobile()){
            $wapurl = env("WAP_URL");
		$wapurl = explode(',', $wapurl);
		if(in_array($origin,$wapurl)){
			return $this->returnMsg(500,[],'wap');
		}else{
			return $this->returnMsg(200, ['url' => $wapurl[0]]);
		}

        } else {
            $url = env("PC_URL");
		$weburl = explode(',', $url);
		if(in_array($origin,$weburl)){
			return $this->returnMsg(500,[],'pc');
		}else{
			return $this->returnMsg(200, ['url' => $weburl[0]]);
		}
        }
    }

    /**
     * 閼惧嘲褰囨禒锝囨倞閹恒劌绠嶆穱鈩冧紖
     *
     * @return void
     */
    public function getAgentInfo(Request $request)
    {
        $token = $request->header('Authorization', $request->header('authorization', ''));
        $token = trim(preg_replace('/^Bearer\s+/i', '', (string) $token));
        $user = User::where('api_token',$token)->first();
        if (!$user) {
            return $this->returnMsg(401, '', 'Authentication required');
        }

        $pcUrl = $this->invitePublicUrl(SystemConfig::getValue('agent_pc_uri') ?: env('PC_URL'), $user->id);
        $wapUrl = $this->invitePublicUrl(SystemConfig::getValue('agent_wap_uri') ?: env('WAP_URL'), $user->id);

        $qrcodePath = '/uploads/agent/qrcode/' . $user->id . '.png';

        return $this->returnMsg(200, [
            'pc_url' => $pcUrl,
            'wap_url' => $wapUrl,
            'pcInviteUrl' => $pcUrl,
            'wapInviteUrl' => $wapUrl,
            'invite_url' => $wapUrl,
            'inviteUrl' => $wapUrl,
            'invite_code' => (string) $user->id,
            'inviteCode' => (string) $user->id,
            'qrcode' => $this->appPublicUrl() . $qrcodePath
        ]);
    }

    public function getApiUrl()
    {
        return $this->returnMsg(200, env('API_URL'));
    }

    public function getAllPlat()
    {
        $vaild_plat = GameList::where('is_top',1)->where('site_state',1)->where('app_state',1)->select('platform_name')->distinct()->pluck('platform_name')->toArray();
        $res = array_unique($vaild_plat);
        return $this->returnMsg(200,$res);
    }

    public function getAllGameList(Request $request)
    {
        $platform = $request->input('platform_name') ?? ($request->input('platform') ?? '');
        $category = $request->input('game_type') ?? ($request->input('category') ?? '');
        $list = $this->cachedPublicGameList($platform, $category, true);
        return $this->returnMsg(200,$list);
    }
    public function gamelistBycode(Request $request)
    {
        $list = GameList::where('is_top',1)->where('site_state',1)->where('app_state',1)->where('category_id','fishing')->orderBy('order_by','asc')->get()->toArray();
        $enabledApis = $this->enabledApiCodes();
		$listarray = array();
		foreach($list as $key => $value){
			if(!isset($enabledApis[strtoupper($value['platform_name'])])){
				unset($list[$key]);
                continue;
			}
			$listarray[$key]['gamepic'] = $this->formatGameImage($value['api_logo_img'] ?: $value['mobile_img']);
			$listarray[$key]['catecode'] = $value['platform_name'];
			$listarray[$key]['gamename'] = $value['name'];
			$listarray[$key]['gamecode'] = $value['game_code'];
			$listarray[$key]['gametype'] = 'fishing';
		}
        $listarray = array_merge($listarray);
        return $this->returnMsg(200,$listarray);
    }
    public function getAppUrl()
    {
        $url = env('APP_URL');
        return $this->returnMsg(200,compact('url'));
    }

    /**
     * Get Stream Chat configuration.
     */
    public function getStreamConfig()
    {
        $enabled = SystemConfig::getValue('stream_chat_enabled');
        $apiKey = SystemConfig::getValue('stream_chat_api_key');
        $workOrderUrl = SystemConfig::getValue('work_order_page_url') ?: (env('APP_URL') . '/support/work-orders.html');

        if ($enabled != 1 || empty($apiKey)) {
            return $this->returnMsg(200, [
                'enabled' => false,
                'provider' => 'work_order',
                'fallback_url' => $workOrderUrl,
                'work_order_enabled' => true,
            ], 'Live chat is unavailable, using work order support');
        }

        return $this->returnMsg(200, [
            'enabled' => true,
            'api_key' => $apiKey,
            'provider' => 'stream',
            'user_id_prefix' => 'pg_user_',
            'channel_type' => 'messaging'
        ]);
    }

    /**
     * Get Stream Chat token.
     */
    public function getStreamToken(Request $request)
    {
        $user = $this->activeUserFromBearer($request);
        if (!$user) {
            return $this->returnMsg(401, null, 'Authentication required');
        }

        $enabled = SystemConfig::getValue('stream_chat_enabled');
        if ($enabled != 1) {
            return $this->returnMsg(403, null, 'Live chat is not enabled');
        }

        $apiKey = SystemConfig::getValue('stream_chat_api_key');
        $secret = SystemConfig::getValue('stream_chat_secret');

        if (empty($apiKey) || empty($secret)) {
            return $this->returnMsg(500, null, 'Stream Chat configuration is incomplete');
        }

        $userId = $user->id;
        $jwtToken = $this->generateStreamToken($userId, $apiKey, $secret);

        if (!$jwtToken) {
            return $this->returnMsg(500, null, 'Token generation failed');
        }

        return $this->returnMsg(200, [
            'token' => $jwtToken,
            'user_id' => $userId,
        ]);
    }

    /**
     * Generate Stream Chat JWT token.
     */
    private function generateStreamToken($userId, $apiKey, $secret)
    {
        try {
            $header = [
                'typ' => 'JWT',
                'alg' => 'HS256',
            ];

            $now = time();
            $payload = [
                'user_id' => (string)$userId,
                'iat' => $now,
                'exp' => $now + (60 * 60 * 24),
            ];

            $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($header)));
            $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode(json_encode($payload)));

            $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);
            $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));

            return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
        } catch (\Exception $e) {
            \Log::error('Stream token generation failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create or join a Stream Chat channel.
     */
    public function createStreamChannel(Request $request)
    {
        $user = $this->activeUserFromBearer($request);
        if (!$user) {
            return $this->returnMsg(401, null, 'Authentication required');
        }

        $enabled = SystemConfig::getValue('stream_chat_enabled');
        if ($enabled != 1) {
            return $this->returnMsg(403, null, 'Live chat is not enabled');
        }

        $apiKey = SystemConfig::getValue('stream_chat_api_key');
        $secret = SystemConfig::getValue('stream_chat_secret');

        if (empty($apiKey) || empty($secret)) {
            return $this->returnMsg(500, null, 'Stream Chat configuration is incomplete');
        }

        $channelType = $request->input('channel_type', 'livestream');
        $channelId = $request->input('channel_id', 'general');
        $userId = $user->id;

        try {
            $url = "https://chat.stream-io-api.com/channels/{$channelType}/{$channelId}";

            $data = json_encode([
                'members' => [(string)$userId],
                'created_by_id' => (string)$userId,
            ]);

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: ' . $secret,
                'stream-auth-type: jwt',
                'Content-Type: application/json',
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                return $this->returnMsg(200, [
                    'channel_type' => $channelType,
                    'channel_id' => $channelId,
                ]);
            }

            if ($httpCode == 400) {
                return $this->returnMsg(200, [
                    'channel_type' => $channelType,
                    'channel_id' => $channelId,
                ]);
            }

            \Log::warning('Create Stream channel failed, HTTP status: ' . $httpCode . ', response: ' . $response);
            return $this->returnMsg(500, null, 'Create Stream channel failed');
        } catch (\Exception $e) {
            \Log::error('Create Stream channel failed: ' . $e->getMessage());
            return $this->returnMsg(500, null, 'Create Stream channel failed');
        }
    }

    public function getAgentUrl()
    {
        $agentUrl = $this->agentPublicUrl();
        return $this->returnMsg(200, [
            'agent_url' => $agentUrl,
            'url' => $agentUrl,
            'agent_login_url' => $this->agentLoginPublicUrl(),
        ]);
    }

    /**
     * 閼奉亜濮╅惂璇茬秿娴狅絿鎮婇崥搴″酱
     *
     * @param Request $request
     * @return void
     */
    public function autoLogin(Request $request)
    {
        return $this->returnMsg(403, '', 'Auto login is disabled');
    }

}
