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
        $user = User::where('username', $data['name'])->first();
        if ($user) return $this->returnMsg(201);
        $tg = new TgService;
        $result = $tg->register($data['name']);
        \Illuminate\Support\Facades\Log::info("注册回调");
        \Illuminate\Support\Facades\Log::info($result);
        if ($result['code'] != 200) {
            return $this->returnMsg(500, [], $result['message']);
        }
        $is_agent = 0;
        $pp_user = User::where('id',intval($_COOKIE["pid"]))->first();
        // if ($pp_user && $pp_user->allowagent == 1) $is_agent = 1;
        $arr = [
            'username' => $data['name'],
            'password' => Hash::make($data['password']),
            'realname' => $data['realname'],
            'vip' => 1,
            'pid' => intval($_COOKIE["pid"]),
            'status' => 1,
            'reg_ip' => $request->ip(),
            'paypwd' => Hash::make($data['qukuanmima']),
            'isagent' => $is_agent,
            'allowagent' => 1
        ];
        $res = User::create($arr);
        \Illuminate\Support\Facades\Log::info("注册回调");
        \Illuminate\Support\Facades\Log::info($res);
        if (intval($_COOKIE["pid"]) > 0) {
            $data = [];
            $puser = User::where('id', $_COOKIE["pid"])->first();
            $Gamereport = new GamereportService();
            $data['uid'] = $puser->id;
            $data['pid'] = $puser->pid;

            $data['isagent'] = $puser->isagent;
            $data['recnum'] =  1;
            $Gamereport->add($data);
        }
        Auth::login($res);
        return $this->returnMsg($res ? 200 : 500);
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

            UserOperateLog::insertLog($user->id, 1, $_SERVER['HTTP_USER_AGENT'], $ip, $ip_address, '会员【' . $user->username . '】登录成功');

            return $this->returnMsg(200);
        } else {
            return $this->returnMsg(203);
        }
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
            UserOperateLog::insertLog($user->id, 2, $_SERVER['HTTP_USER_AGENT'], $ip, $ip_address, '会员【' . $user->username . '】注销账号');
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