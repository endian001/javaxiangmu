<?php
//decode by http://www.yunlu99.com/
namespace App\Services;
use Illuminate\Support\Facades\Cookie;
use App\Models\SystemConfig;
use App\Models\Api;
class TgService
{
    protected $api_account;
    protected $sign_key;
    protected $api_url;
    protected $showlang;
    protected $wxgame_mode = false;
    protected $wxgame_api_domain = '';
    protected $wxgame_access_key_id = '';
    protected $wxgame_access_key_secret = '';


    public function __construct()
    {
        $this->api_url = SystemConfig::getValue('game_api');
        $this->api_account = SystemConfig::getValue('merchant_account');
        $this->sign_key = SystemConfig::getValue('api_secret');

        /*$this->api_url = 'http://tggood.com/';
        $this->api_account = '16577927782473';
        $this->sign_key = 'caf89e569723f67ba2340a8995c55818';*/
		
        $this->showlang = empty(Cookie::get("userlang"))? 'zh' : Cookie::get("userlang");
        $this->wxgame_mode = (int) SystemConfig::getValue('wxgame_enabled') === 1;
        $this->wxgame_api_domain = rtrim((string) (SystemConfig::getValue('wxgame_api_domain') ?: 'https://test-openapi.cpigame.com'), '/');
        $this->wxgame_access_key_id = trim((string) SystemConfig::getValue('wxgame_access_key_id'));
        $this->wxgame_access_key_secret = trim((string) SystemConfig::getValue('wxgame_access_key_secret'));
                  
    }
    private function generateCode(Array $data)
    {
        ksort($data);
        $str = '';
        foreach($data as $v){
            $str .= $v;
        }
        $str .= $this->sign_key;
        return strtoupper(md5($str));
    }	
    private function sendRequest($url,$post_data=array()){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $contents = curl_exec($ch);
        curl_close($ch);

        if (!$contents) {
            return [];
        }

        $data = json_decode($contents, TRUE);
        return is_array($data) ? $data : [];
    }

    private function sendRequest_ley($url,$post_data=array()){
		$post_data['account'] = $this->api_account;
		$post_data['api_key'] = $this->sign_key;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        $data = curl_exec($ch);
		$meta = curl_getinfo($ch);
        curl_close($ch);
		if(!$data){
			$contents['Code'] = -1;
			$contents['Message'] = '本地与api通信失败,建议检查api地址';
			return $contents;
		}
		$data = json_decode ($data, TRUE);
		if(!is_array($data)){
			$contents['Code'] = -1;
			$contents['Message'] = '返回数据解析失败';
			return $contents;			
		}
        return $data;
    }
	







    public function isWxgameEnabled()
    {
        return $this->wxgame_mode
            && $this->wxgame_api_domain !== ''
            && $this->wxgame_access_key_id !== ''
            && $this->wxgame_access_key_secret !== '';
    }

    protected function wxgameHeaders()
    {
        $nonce = bin2hex(random_bytes(12));
        $timestamp = time();
        $sign = hash('sha256', $this->wxgame_access_key_secret . $nonce . $timestamp);
        return [
            'Content-Type: application/json',
            'Accept: application/json',
            'AccessKeyId: ' . $this->wxgame_access_key_id,
            'Sign: ' . $sign,
            'Nonce: ' . $nonce,
            'Timestamp: ' . $timestamp,
        ];
    }

    protected function sendWxgameRequest($path, array $payload)
    {
        $url = $this->wxgame_api_domain . $path;
        $verifySsl = (int) SystemConfig::getValue('wxgame_ssl_verify') === 1;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->wxgameHeaders());
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $verifySsl ? TRUE : FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $verifySsl ? 2 : FALSE);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$raw) {
            return ['code' => -1, 'msg' => $error ?: 'WXGame request failed', 'httpCode' => $httpCode, 'data' => null];
        }

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            return ['code' => -1, 'msg' => 'WXGame response parse failed', 'httpCode' => $httpCode, 'raw' => $raw, 'data' => null];
        }

        $data['httpCode'] = $httpCode;
        return $data;
    }

    public function wxgameLogin($token, $gameBrand, $gameId, $language = 'zh-CN')
    {
        $payload = [
            'token' => (string) $token,
            'gameId' => (string) $gameId,
            'gameBrand' => strtolower((string) $gameBrand),
            'language' => $language ?: 'zh-CN',
        ];
        $res = $this->sendWxgameRequest('/v1/api/get_game_url', $payload);
        if ((int)($res['code'] ?? -1) !== 0) {
            return ['code' => 201, 'message' => $res['msg'] ?? 'WXGame get_game_url failed', 'raw' => $res];
        }
        $url = $res['data']['url'] ?? (is_string($res['data'] ?? null) ? $res['data'] : '');
        if ($url === '') {
            return ['code' => 201, 'message' => 'WXGame did not return url', 'raw' => $res];
        }
        return ['code' => 200, 'message' => 'success', 'data' => $url, 'raw' => $res];
    }

    public function wxgameList($gameBrand = '', $gameType = '')
    {
        $payload = [];
        if ($gameBrand !== '') $payload['gameBrand'] = strtolower((string) $gameBrand);
        if ($gameType !== '') $payload['gameType'] = (string) $gameType;
        return $this->sendWxgameRequest('/v1/api/get_game_list', $payload);
    }

    public function wxgameSetPlayerRtp(array $playerIds, $rtp)
    {
        return $this->sendWxgameRequest('/v1/api/set_player_rtp', [
            'playerIds' => array_values($playerIds),
            'rtp' => (string) $rtp,
        ]);
    }

    public function wxgameGetPlayerRtp(array $playerIds)
    {
        return $this->sendWxgameRequest('/v1/api/get_player_rtp', [
            'playerIds' => array_values($playerIds),
        ]);
    }

    public function register($api_code,$username,$password='123456')
    {
		$return = array();
		$return['code'] = 200;
		$return['message'] = '成功';
        $data = [
            'username' => $username,
            'password' => $password,
            'api_code' => $api_code,
        ];
        $res = $this->sendRequest_ley($this->api_url."/ley/register",$data);
		if($res['Code'] != '0'){
			$return['code'] = 201;
			$return['message'] = $res['Message'];	
            return $return;			
		}
        return $return;
    }

    public function login($username,$plat_type="ag",$game_type="1",$is_mobile_url=0,$game_code="0",$lang='zh-cn',$password='123456')
    {
		$return = array();
		$return['code'] = 200;
		$return['message'] = '成功';		
        $data = [
			'api_code' => $plat_type,
			'username' => $username,
			'password' => $password,
			'isMobile' => $is_mobile_url,
			'gameType' => $game_type,  //游戏类型：1真人,2捕鱼,3电子,4彩票,5体育,6棋牌,7电竞
			'gameCode' => $game_code,
			'lang' => $lang ?: 'zh-cn',
        ];

        $res = $this->sendRequest_ley($this->api_url."/ley/login",$data);
		if($res['Code'] != '0'){
			$return['code'] = 201;
			$return['message'] = $res['Message'];	
            return $return;			
		}		
		$return['data'] = $res['Data']['url'];
        return $return;
    }
	
    public function balance($code,$user_api,$pass_api='123456')
    {
		$return = array();
		$return['code'] = 200;
		$return['message'] = '成功';
		$data=array(
			'api_code' => $code,
			'username' => $user_api,
			'password' => $pass_api,
			
		);
		$res = $this->sendRequest_ley($this->api_url."/ley/balance",$data);
		if($res['Code'] != '0'){
			$return['code'] = 201;
			$return['message'] = $res['Message'];	
            return $return;			
		}		
		$return['data'] = $res['Data']['balance'];
        return $return;		
	}

    public function deposit($user_api,$amount,$transferno,$code)
    {
		$amount = floor($amount);
		$pass_api = '123456';
		$return = array();
		$return['code'] = 200;
		$return['message'] = '成功';		
		$data=array(
			'api_code' => $code,
			'username' => $user_api,
			'password' => $pass_api,
			'amount' => $amount,
			'transferno' => $transferno,
			
		);
		$res = $this->sendRequest_ley($this->api_url."/ley/deposit",$data);

		if($res['Code'] != '0'){
			$return['code'] = 201;
			$return['message'] = $res['Message'];
			
            return $return;			
		}		
        return $return;	
	}
    public function withdrawal($user_api,$amount,$transferno,$code)
    {
		$amount = floor($amount);
		$pass_api = '123456';
		$return = array();
		$return['code'] = 200;
		$return['message'] = '成功';	
		$data=array(
			'api_code' => $code,
			'username' => $user_api,
			'password' => $pass_api,
			'amount' => $amount,
			'transferno' => $transferno,
			
		);

	    $res = $this->sendRequest_ley($this->api_url."/ley/withdrawal",$data);

		if($res['Code'] != '0'){
			$return['code'] = 201;
			$return['message'] = $res['Message'];
			
            return $return;			
		}		
        return $return;		
	}
    public function credit($api_code)
    {
		$return = array();
		$return['code'] = 200;
		$return['message'] = '成功';	
		$data=array(
			'api_code' => $api_code,			
		);

	    $res = $this->sendRequest_ley($this->api_url."/ley/credit",$data);

		if($res['Code'] != '0'){
			$return['code'] = 201;
			$return['message'] = $res['Message'];
			
            return $return;			
		}
        $return['data'] = $res['Data']['money'];
        return $return;		
	}
    public function gamerecord($data){
        $post_data = [	
            'page' =>$data['page'],
			'pageSize' => $data['pageSize'],
			'start_at' => $data['start_at'],
			'end_at' => $data['end_at'],
			'method' => $data['method']
        ];
		$res = $this->sendRequest_ley($this->api_url."/ley/gamerecord",$data);

        return $res;
    }	
    /**
     * [login 登录获取游戏地址]
     * @DateTime  2020-09-16T09:54:01+0800
     * @param        [string]                   $username      [会员用户名]
     * @param        string                   $plat_type     [平台类型]
     * @param        string                   $game_type     [游戏类型]
     * @param        integer                  $is_mobile_url [是否手机访问]
     * @param        string                   $game_code     [游戏代码]
     * @return      [Array]
     */
    public function loginaaa($username,$plat_type="ag",$game_type="1",$is_mobile_url=0,$game_code="",$lang='zh-cn')
    {
        $data = [
            'username' => $username,
            'api_account' => $this->api_account,
            'plat_type' => $plat_type,
            'game_type' => $game_type,
            'is_mobile' => $is_mobile_url,
            'game_code' => $game_code,
            'lang' => empty($this->showlang) ? $lang : $this->showlang,
        ];
        
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/login",$data);
              \Illuminate\Support\Facades\Log::info("Iglogin游戏记录参数");
               \Illuminate\Support\Facades\Log::info($data);      
        return $res;
    }

    /**
     * [userBalance 用户某平台余额]
     * @param        [string]                   $username  [用户名]
     * @param        string                   $plat_type [平台类型]
     * @return      [array]
     */
    public function userBalance($username,$plat_type="ag")
    {
        $data = [
            'username' => $username,
            'api_account' => $this->api_account,
            'plat_type' => $plat_type,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/user/balance",$data);

        return $res;
    }

    /**
     * [allBalance 查询会员游戏全部平台余额]
     * @param        [string]                   $username [用户名]
     * @return      [Array]
     */
    public function allBalance($username)
    {
        $data = [
            'username' => $username,
            'api_account' => $this->api_account,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/user/all-balance",$data);
        return $res;
    }

    /**
     * [allRecords 所有游戏记录]
     * @DateTime  2020-09-16T11:12:24+0800
     * @param        string                   $username  [用户名]
     * @param        string                   $plat_type [平台类型]
     * @param        string                   $game_type [游戏类型]
     * @param        integer                  $page      [页码]
     * @param        integer                  $limit     [每页数量]
     * @return      [Array]
     */
    public function allRecords($username="",$plat_type="",$game_type="",$page=1,$limit=100)
    {
        $data = [
            'username' => $username,
            'plat_type' => $plat_type,
            'game_type' => $game_type,
            'page' => $page,
            'limit' => $limit,
            'api_account' => $this->api_account,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/record-all",$data);
        return $res;
    }

    public function gameRecords($username="",$plat_type="",$game_type="",$page=1,$start,$end)
    {
        $data = [
            'username' => $username,
            'platform_type' => $plat_type,
            'game_type' => $game_type,
            'page' => $page,
            'start' => $start,
            'end' => $end,
            'api_account' => $this->api_account,
        ];
        //echo $this->api_url;
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/gamelistRecord",$data);
        return $res;
    }


    /**
     * [trans 更新免转钱包额度]
     * @param        [string]                   $username           [用户名]
     * @param        [decimal]                   $money              [金额]
     * @param        [string]                   $client_transfer_id [订单id]
     * @return      [Array]
     */
    public function trans($username,$money,$client_transfer_id,$plat_name='ag',$game_type='200')
    {
        $data = [
            'username' => $username,
            'money' => $money,
            'plat_name' => $plat_name,
            'game_type' => $game_type,
            'client_transfer_id' => $client_transfer_id,
            'api_account' => $this->api_account,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/userswallet/trans",$data);
        return $res;
    }

    /**
     * [walletBalance 免转钱包余额]
     * @DateTime  2020-09-16T13:57:09+0800
     * @param        [string]                   $username [用户名]
     * @return      [Array]
     */
    public function walletBalance($username)
    {
        $data = [
            'username' => $username,
            'api_account' => $this->api_account,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/wallet/balance",$data);
        return $res;
    }

    /**
     * 一键转出所有额度
     *
     * @param [type] $username
     * @return void
     */
    public function transAll($username)
    {
        $data = [
            'username' => $username,
            'api_account' => $this->api_account,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/user/trans-all",$data);
        return $res;
    }

    /**
     * 商户额度查询
     *
     * @return void
     */
    public function merchantCredit()
    {
        $data = [
            'api_account' => $this->api_account,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/user/all-credit",$data);
        return $res;
    }

    /**
     * 游戏查询
     *
     * @return void
     */
    public function gameslist($plat_type)
    {
        $data = [
            'gamecode' => $plat_type,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/gamelist",$data);
        return $res;
    }
    /**
     * 游戏查询
     *
     * @return void
     */
    public function gamesalllist()
    {
		$Api = Api::where('state',1)->get(['api_code','api_name'])->toArray();

		$data = array();
		foreach($Api as $key => $v){
			$data[$key]['platform_code'] = $v['api_code'];
			$data[$key]['platformname'] = $v['api_name'];
		}
/*		
        $data = [];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/allgamelist",$data);*/
        return $data;
    }
    /**
     * 游戏查询
     *
     * @return void
     */
    public function gametypelist($plat_type)
    {
        $data = [
            'gametype' => $plat_type,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/gametypelist",$data);
        return $res;
    }
    /**
     * 一键提取
     *
     * @return void
     */
    public function recoverallbalance($plat_type)
    {
        $data = [
            'username' => $plat_type,
            'api_account' => $this->api_account,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/goapi/alluserblance",$data);
        return $res;
    }
    
    /**
     * 刷新用户余额
     *
     * @return void
     */
    
    public function allusersbalance($plat_type)
    {
        $data = [
            'username' => $plat_type,
            'api_account' => $this->api_account,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/goapi/userblance",$data);
        return $res;
    }
    
    /**
     * 更新用户模式
     *
     * @return void
     */
    public function updateusertype($usernamee,$transferstatus)
    {
        $data = [
            'username' => $usernamee,
            'type' => ($transferstatus==0) ? 2 : 1,
            'api_account' => $this->api_account,
        ];
        $data['code'] = $this->generateCode($data);
        $res = $this->sendRequest($this->api_url."/api/user/updateusertype",$data);
        return $res;
    }  
    
    /**
     * 获取按照大类显示游戏
     *
     * @return void
     */
    public function getallbetgamelist()
    {
        $data = [
            
        ];
        $res = $this->sendRequest($this->api_url."/api/getgamelistbet",$data);
        
        return (is_array($res) && isset($res['data']) && is_array($res['data'])) ? $res['data'] : [];
    }      
    
    public function getallmoneygamelist()
    {
		$Api = Api::where('state',1)->get(['api_code','api_name'])->toArray();

		$data = array();
		foreach($Api as $key => $v){
			$data[$v['api_code']] = $v['api_name'];
		}
        return $data;
    }   
    
    
    /**
     * 获取所有游戏名称
     *
     * @return void
     */
    public function getallgamename()
    {
		$Api = Api::where('state',1)->get(['api_code','api_name'])->toArray();

		$data = array();
		foreach($Api as $key => $v){
			$data[$v['api_code']] = $v['api_name'];
		}
        return $data;		
        /*$data = [
            
        ];
        $res = $this->sendRequest($this->api_url."/api/getallgamename",$data);
        
        return $res['data'];*/
    }    
        /**
     * 获取游戏代码
     *
     * @return void
     */
    public function engamelist()
    {
        $data = [
            
        ];
        $res = $this->sendRequest($this->api_url."/api/engamelist",$data);
        
        return (is_array($res) && isset($res['data']) && is_array($res['data'])) ? $res['data'] : [];
    }    
    
    /**
     * 获取游戏记录名称
     *
     */
    public function getgamerecordallgamename()
    {
        $data = [];
        $res = $this->sendRequest($this->api_url."/api/getgamerecordallgamename",$data);
        
        return $res['data'] ?? [];
    }    
        
    /**
     * 获取返水游戏列表
     *
     */
    public function getfanshuigamelistname()
    {
        $data = [];
        $res = $this->sendRequest($this->api_url."/api/getfanshuigamelist",$data);
        
        return $res ?? [];
    }         
   /**
     * 转账记录游戏列表
     *
     */
    public function getallmoneygame()
    {
        $data = [];
        $res = $this->sendRequest($this->api_url."/api/getallmoneygame",$data);
        
        return $res['data'] ?? [];
    } 
    
    
}
