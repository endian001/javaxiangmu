<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Models\AgentApply;
use App\Models\Article;
use App\Models\ActivityType;
use App\Models\Articlescate;
use App\Models\Banner;
use App\Models\PaySetting;
use App\Models\SystemConfig;
use App\Models\Template;
use App\Models\Usersmoney;
use App\Models\UserVip;
use App\Services\TgService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use App\Models\Message;
use App\Models\UserMessage;
use App\Models\GameList;

class IndexController extends Controller
{
    protected $path;
    protected $showlang;
    public function __construct(Request $request)
    {
        

        
        $site_state = SystemConfig::query()->find("site_state");
        if(!$site_state['value']){
            echo SystemConfig::query()->find("repair_tips")['value'];
            exit;
        }

        $data = $request->all();
        $lang = isset($data['lang']) ? $data['lang'] : "";
        if($lang==""){
            $cookielang = Cookie::get("userlang");
            $this->showlang = $cookielang;
            if($cookielang=="en"){
                $path = 'web.template.e_mb10';    
            }else{
                $path = Template::where('client_type',1)->where('state',2)->first();
                $path = $path ? 'web.template.'.$path->template_id : 'web';            
            }
        }elseif($lang=="en"){
            setcookie("userlang",$lang);
            //Cookie::queue('userlang', );
             $this->showlang = $lang;
            $path = 'web.template.e_mb10';
        }elseif(in_array($lang,['tw','zh'])){
            //Cookie::queue('userlang', $lang);
            setcookie("userlang",$lang);
            $this->showlang = $lang;
            $path = Template::where('client_type',1)->where('state',2)->first();
            $path = $path ? 'web.template.'.$path->template_id : 'web';  
        }

        $this->path = $path;
        
    }

    public function index(Request $request)
    {
        $url = env("PC_URL");
        if($this->isMobile()){
            $url = env("WAP_URL");
        }  
		return redirect()->away($url);
		exit;         
        if($this->isMobile()){
            $wapurl = env("WAP_URL").":".$_SERVER["SERVER_PORT"];
            return redirect()->away($wapurl);
            exit;
        } 

        $lang = $this->showlang;
        $isclose = SystemConfig::query()->find("isclose");
        if($isclose['value']){
            $webcontent = SystemConfig::query()->find("webcontent");
            $content = $webcontent['value'];
            $isclose = 1;
        }else{
            $content = [];
            $isclose = 0;
        }

        $articlelist = Article::where('cateid',6)->orderBy("id","desc")->get();
        $article = Article::where('cateid',6)->orderBy("id","desc")->first();
        $banners = Banner::where('state',1)->where('type',1)->get();
        $card = PaySetting::where('state', 1)->first();
        if(Auth::user()) {
           
            $balancelist = Usersmoney::getUserBalance(Auth::id());
        }else{
            $balancelist = [];
        }
        
        $ios_download_url = SystemConfig::getValue('ios_download_url');
        $ios_download_qrcode = SystemConfig::getValue('ios_download_qrcode');
        $h5_url = env('WAP_URL');

        return view($this->path.'.index',compact('isclose','content','article','banners','balancelist','card','articlelist','lang','ios_download_qrcode','ios_download_url','h5_url'));
    }
    
    
    public function  applyagent(Request $request){
        $user = Auth::user();
        return view($this->path . '.member.applyagent',compact('user'));
    }


    public function applyagentdo(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        $useragent = AgentApply::where('user_id',$user->id)->first();
         if ($useragent)return $this->returnMsg(500, '', '您已申请过代理'); 

            $arr = [
                'user_id' => $user->id,
                'apply_info' => $data['apply_info'],
                'state' => 1,
                'mobile' => $data['mobile'],
            ];
        if($res = AgentApply::create($arr)){
          return $this->returnMsg(200, '', '请选择转账方式');
        }else{
            return $this->returnMsg(500, '', '申请失败');
        }
            
        
    }        

    public function sport()
    {
        $lang = $this->showlang;
        if(Auth::user()) {
            $balancelist = Usersmoney::getUserBalance(Auth::id());

        }else{
            $balancelist = [];
        }
        $list = GameList::where('category_id','sport')->where('site_state',1)->get();
        return view($this->path.'.sport',compact('balancelist','lang','list'));
    }
    public function realbet()
    {
        $lang = $this->showlang;
        if(Auth::user()) {
            $balancelist = Usersmoney::getUserBalance(Auth::id());

        }else{
            $balancelist = [];
        }
        $list = GameList::where('category_id','realbet')->where('site_state',1)->orderBy('order_by','desc')->get();
        return view($this->path.'.realbet',compact('balancelist','lang','list'));
    }

    public function joker()
    {
        $lang = $this->showlang;
        if(Auth::user()) {
            $balancelist = Usersmoney::getUserBalance(Auth::id());

        }else{
            $balancelist = [];
        }
        $list = GameList::where('category_id','joker')->where('site_state',1)->orderBy('order_by','desc')->get();
        return view($this->path.'.joker',compact('balancelist','lang','list'));
    }

    public function gaming()
    {
        $lang = $this->showlang;
        if(Auth::user()) {
            $balancelist = Usersmoney::getUserBalance(Auth::id());

        }else{
            $balancelist = [];
        }
        $list = GameList::where('category_id','gaming')->where('site_state',1)->orderBy('order_by','desc')->get();
        return view($this->path.'.gaming',compact('balancelist','lang','list'));
    }

    public function lottery()
    {
        $lang = $this->showlang;
        if(Auth::user()) {
            $balancelist = Usersmoney::getUserBalance(Auth::id());

        }else{
            $balancelist = [];
        }
        $list = GameList::where('category_id','lottery')->where('site_state',1)->orderBy('order_by','desc')->get();
        return view($this->path.'.lottery',compact('balancelist','lang','list'));
    }

    public function concise()
    {
        $lang = $this->showlang;
        // $tg = new TgService;
        $gamelist =array(
            'zeus'=>'zeus游戏',
            'cg'=>'cg游戏',
            'icg'=>'icg游戏',
            'pp'=>'PP电子',
            'pg'=>'PG游戏',
            'sg'=>'SG游戏',
            'vg'=>'VG棋牌',
            'tc'=>'TC彩票',
            'datqp'=>'大唐棋牌',
            'wg'=>'Wg真人',
            'tm'=>'TM棋牌',
        );
        $allgamelist = GameList::where('category_id','concise')->where('site_state',1)->where('game_code','')->orderBy('order_by','desc')->get();
        // dd($allgamelist);
        // $aegamelist = $tg->gameslist('ae');

        // $aegamelist = $aegamelist['data'];
        $aegamelist = GameList::where('platform_name','ae')->where('site_state',1)->orderBy('order_by','desc')->get();
        
        // $ppgamelist = $tg->gameslist('pp');
        // $ppgamelist = $ppgamelist['data'];
        $ppgamelist = GameList::where('platform_name','pp')->where('site_state',1)->orderBy('order_by','desc')->get();
        
        // $obggamelist = $tg->gameslist('obgdy');
        // $obggamelist = $obggamelist['data'];
        $obggamelist = GameList::where('platform_name','obgdy')->where('site_state',1)->orderBy('order_by','desc')->get();
        
        // $fggamelist = $tg->gameslist('fgdz');
        // $fggamelist = $fggamelist['data'];
        $fggamelist = GameList::where('platform_name','fgdz')->where('site_state',1)->orderBy('order_by','desc')->get();
        
        $cggamelist = GameList::where('platform_name','cg')->where('site_state',1)->orderBy('order_by','desc')->get();
        
        $fishgamelist = GameList::where('platform_name','fgfish')->where('site_state',1)->orderBy('order_by','desc')->get();
        
        if(Auth::user()) {
            $balancelist = Usersmoney::getUserBalance(Auth::id());
        }else{
            $balancelist = [];
        }
        return view($this->path.'.concise',compact("allgamelist","aegamelist","gamelist","ppgamelist",'balancelist','obggamelist','fggamelist','cggamelist','fishgamelist','lang'));
    }

    public function notice(Request $request)
    {
        $lang = $this->showlang;
        $activitylist = Article::orderBy('id','desc')->get();
        if(Auth::user()) {
            $balancelist = Usersmoney::getUserBalance(Auth::id());

        }else{
            $balancelist = [];
        }
        return view($this->path.'.notice',compact("activitylist",'balancelist','lang'));
    }

    public function activity(Request $request)
    {
        $lang = $this->showlang;
        $data = $request->all();
        $activitytype = ActivityType::get();
        if(isset($data['id']) && $data['id']){
            $id = $data['id'];
            $activitylist = Activity::where('type',$data['id'])->orderBy('id','desc')->get();
        }else{
            $id = 0;
            $activitylist = Activity::orderBy('id','desc')->get();
        }
        if(Auth::user()) {
            $balancelist = Usersmoney::getUserBalance(Auth::id());

        }else{
            $balancelist = [];
        }
        return view($this->path.'.activity',compact("activitytype","activitylist","id",'balancelist','lang'));
    }

    public function promotionEntry(Request $request)
    {
        $path = public_path('index.html');
        if (is_file($path)) {
            return response()->file($path);
        }

        return $this->activity($request);
    }
        public function articles(Request $request)
    {
        $lang = $this->showlang;
        $data = $request->all();
        $articlescate = Articlescate::get();
        if(isset($data['id']) && $data['id']){
            $id = $data['id'];
            $articleslist = Article::where('cateid',$data['id'])->get();
        }else{
            $id = 0;
            $articleslist = Article::get();
        }

		 if(isset($data['artid']) && $data['artid']){
            $id = $data['artid'];
            $article = Article::where('id',$data['artid'])->first();
        }else{
            $artid = 0;
            $article = Article::first();
        }

        return view($this->path.'.articles',compact("articlescate","articleslist","id","artid","article",'lang'));
    }

    public function app()
    {
        $lang = $this->showlang;
        $ios_download_url = SystemConfig::getValue('ios_download_url');
        $ios_download_qrcode = SystemConfig::getValue('ios_download_qrcode');
        $h5_url = env('WAP_URL');
        return view($this->path.'.app',compact('lang','ios_download_qrcode','ios_download_url','h5_url'));
    }
    public function agent()
    {
        return view($this->path.'.agent');
    }

    public function appindex()
    {
        return view($this->path.'.appindex');
    }

    public function upload(Request $request)
    {
        if ($request->hasFile('file') && $request->file('file')->isValid()) {
            $file = $request->file('file');
            $extension = $file->extension();
            if (!in_array($extension,['jpg','png','jpeg'])) return $this->returnMsg(500);
            $filename = uniqid().'.'.$extension;
            $store_result = $file->storeAs('file', $filename);
            return $this->returnMsg(200,$filename);
        }
    }

    public function content($id)
    {
        //$data = $request->all();
        $lang = $this->showlang;
        $content = Article::where('id',$id)->first();
        return view($this->path.'.content',compact("content",'lang'));
    }
    
    public function  messagecenter(Request $request){
         $user = Auth::user();
        
        // 构建查询条件
        $query = Message::query();
        
        // 基础条件：面向所有用户的消息
        $query->where(function($q) {
            $q->where('user_id', 0)
              ->where('vip_id', 0)
              ->where('isagent', 0);
        });
        
        // 如果是代理用户，可以看到代理消息
        if ($user->isagent == 1) {
            $query->orWhere('isagent', 1);
        }
        
        // 可以看到对应VIP等级的消息（排除VIP黑名单消息）
        $query->orWhere(function($q) use ($user) {
            $q->where('vip_id', $user->vip)
              ->where('isagent', '!=', 2);
        });
        
        // 可以看到专门发给自己的消息
        $query->orWhere('user_id', $user->id);
        
        $list = $query->paginate(20);
        foreach ($list as $k => $v) {
            $user_message = UserMessage::where('message_id', $v->id)->count();
            $list[$k]['is_read'] = $user_message ?? 0;
        }        
       
        return view($this->path . '.member.messages',compact('list'));
    }    
    
    public function vip()
    {
        $lang = $this->showlang;
        $list = UserVip::where('status',1)->get();
        return view($this->path . '.vip',compact('lang','list'));
    }
    public function pull(Request $request)
    {       
        return view($this->path.'.pull');
    }	
}
