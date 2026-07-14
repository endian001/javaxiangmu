<?php
//decode by http://www.yunlu99.com/
namespace App\Http\Controllers\Member;
use App\Http\Controllers\Controller;
use App\Models\Bank;
use App\Models\Syslog;
use App\Services\GamereportService;
use Illuminate\Http\Request;
use App\Services\TgService;
use App\User;
use App\Models\UserVip;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Template;
use App\Models\UserOperateLog;
use App\Services\Lib;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;
use App\Models\Session;

class AuthController extends Controller
{
    protected $path;
    protected $showlang;
    public function __construct()
    {
        $lang = Cookie::get("userlang");
        $this->showlang = $lang;
        if($lang=="en"){
            $path = 'web.template.e_mb10';    
        }else{
            $path = Template::where('client_type',1)->where('state',2)->first();
            $path = $path ? 'web.template.'.$path->template_id : 'web';            
        }   
        $this->path = $path;
    }

    public function register(Request $request)
    {
        $data = $request->all();
        $lang = $this->showlang;
        $id = isset($data['id']) ? $data['id'] : 0;
        $pid = intval($id);
        setcookie("pid", $pid);
        return view($this->path . '.auth.register', compact('lang'));
    }

    public function registerDo(Request $request)
    {
        $data = $request->all();
        if (empty($data['name']) || empty($data['password'])) {
            return $this->returnMsg(500, [], '用户名和密码不能为空');
        }
        if (!isset($data['realname']) || $data['realname'] === '') {
            $data['realname'] = $data['name'];
        }
        $payPassword = $data['qukuanmima'] ?? ($data['paypassword'] ?? '258963');
        $pid = intval($request->cookie('pid', $data['pid'] ?? 0));

        $user = User::where('username', $data['name'])->first();
        if ($user) return $this->returnMsg(201);

        // The external game account registration can fail or require a platform code;
        // do not block local member registration. Game launch flows can provision later.
        try {
            $tg = new TgService;
            $result = $tg->register($data['api_code'] ?? 'wg', $data['name']);
            \Illuminate\Support\Facades\Log::info('member external register result', ['result' => $result]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('External member registration failed; local registration will continue. '.$e->getMessage());
        }

        $is_agent = 0;
        $pp_user = $pid > 0 ? User::where('id', $pid)->first() : null;
        $arr = [
            'username' => $data['name'],
            'password' => Hash::make($data['password']),
            'realname' => $data['realname'],
            'vip' => 1,
            'level' => 1,
            'pid' => $pid,
            'status' => 1,
            'reg_ip' => $request->ip(),
            'paypwd' => Hash::make($payPassword),
            'isagent' => $is_agent,
            'allowagent' => 1,
            'api_token' => Str::random(60)
        ];
        $res = User::create($arr);
        if ($pid > 0 && $pp_user) {
            $reportData = [];
            $Gamereport = new GamereportService();
            $reportData['uid'] = $pp_user->id;
            $reportData['pid'] = $pp_user->pid;
            $reportData['isagent'] = $pp_user->isagent;
            $reportData['recnum'] = 1;
            $Gamereport->add($reportData);
        }
        Auth::login($res);
        return $this->returnMsg($res ? 200 : 500, $res ? $this->authResponseData($res) : []);
    }

    public function login()
    {
        $lang = $this->showlang;
        return view($this->path . '.auth.login', compact('lang'));
    }

    public function applogin()
    {
        $lang = $this->showlang;
        return view($this->path . '.auth.applogin', compact('lang'));
    }

    public function loginDo(Request $request)
    {
        $data = $request->all();
        $user = User::where('username', $data['name'])->first();
        if (!$user) return $this->returnMsg(202);
        if (Hash::check($data['password'], $user->password)) {
            $ip = $request->ip();
            $res = Lib::getIpAddress($ip);
            $res = json_decode($res, true);
            $ip_address = '';
            if ($res['code'] == 200) {
                $ip_address = $res['data']['country'] . $res['data']['province'] . $res['data']['city'];
            }
            $user->last_login_ip_address = $ip_address;
            $user->lastip = $request->getClientIp();
            $user->logintime = time();
            $user->loginsum++;
            if (empty($user->api_token)) {
                $user->api_token = Str::random(60);
            }
            $user->save();
            $uservip = UserVip::where('id',$user->level)->first();
            if($uservip){
                $user->level= $uservip->vipname;
            }else{
                $user->level= 'VIP0';
            }
            Session::where('user_id',$user->id)->delete();
            Auth::login($user);
            // $datas['id'] = 0;
            // $datas['uid'] = $user->id;
            // $datas['type'] = 1;
            // $datas['addtime'] = date('Y-m-d H:i:s');

            // Syslog::create($datas);

            $userAgent = $request->userAgent() ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');
            UserOperateLog::insertLog($user->id, 1, $userAgent, $ip, $ip_address, 'member login success');

            return $this->returnMsg(200, $this->authResponseData($user));
        } else {
            return $this->returnMsg(203);
        }
    }

    protected function authResponseData(User $user)
    {
        return [
            'id' => (int) $user->id,
            'username' => $user->username,
            'realname' => $user->realname,
            'api_token' => $user->api_token,
            'token' => $user->api_token,
            'isagent' => (int) $user->isagent,
            'is_agent' => (int) $user->isagent,
            'allowagent' => (int) $user->allowagent,
            'balance' => $user->balance,
            'level' => $user->level,
        ];
    }

    public function logout(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $ip = $request->ip();
            $res = Lib::getIpAddress($ip);
            $res = json_decode($res, true);
            $ip_address = '';
            if ($res['code'] == 200) {
                $ip_address = $res['data']['country'] . $res['data']['province'] . $res['data']['city'];
            }
            $userAgent = $request->userAgent() ?: ($_SERVER['HTTP_USER_AGENT'] ?? '');
            UserOperateLog::insertLog($user->id, 2, $userAgent, $ip, $ip_address, 'member logout');
            Auth::logout();
        }

        return redirect('/');
    }

    public function editPassword()
    {
         $lang = $this->showlang;
        $user = Auth::user();
        return view($this->path . '.auth.edit_password', compact('user','lang'));
    }

    public function editPasswordDo(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        
        if (!Hash::check($data['old_password'], $user->password)) return $this->returnMsg(205);
        $user->password = Hash::make($data['new_password']);
        $user->save();
        return $this->returnMsg(200);
    }


    public function editPayPassword()
    {
        $user = Auth::user();
         $lang = $this->showlang;
        return view($this->path . '.auth.edit_paypassword', compact('user','lang'));
    }

    public function editPayPasswordDo(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();

        if (!Hash::check($data['old_password'], $user->paypwd)) return $this->returnMsg(205);
        $user->paypwd = Hash::make($data['new_password']);
        $user->save();
        return $this->returnMsg(200);
    }

    public function banklist()
    {
        $banklist = Bank::where('state', 1)->select('bank_name as label')->get()->toArray();

       // return $this->returnMsg(200, $banklist,'转账成功');

        echo (json_encode($banklist));
    }
}
