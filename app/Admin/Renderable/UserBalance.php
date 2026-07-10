<?php

namespace App\Admin\Renderable;

use App\Models\Usersmoney;
use App\Services\TgService;
use Dcat\Admin\Support\LazyRenderable;
use Dcat\Admin\Widgets\Card;
use App\Models\User_Api;
use Dcat\Admin\Admin;
use Illuminate\Http\Request;
class UserBalance extends LazyRenderable
{
	public function user_balance(Request $request)
    {
        if (! Admin::user()) {
            abort(403, 'Admin authorization required.');
        }

		$username = (string) $request->username;
		$plat_name = (string) $request->plat_name;
        if ($username === '' || $plat_name === '') {
            return ['code' => 422, 'message' => '参数错误'];
        }

		$LeYuService = new TgService();
		$data = $LeYuService->balance($plat_name,$username);
        if($data['code'] == 200){
			$user_api = User_Api::where('api_user', $username)->where('api_code', $plat_name)->first();
            if ($user_api) {
                $user_api->api_money = $data['data'];
                $user_api->save();
            }
		}		
		return $data;
	}
    protected function script($user_api)
    {
		$data = "";
		foreach($user_api as $key => $value){
			$id = $value['id'];
			$username = $value['api_user'];
			$plat_name = $value['api_code'];
			$data.=<<<SCRIPT

$('#check-draw-money-$id').on('click', function () {
     $.ajax({
        type : "POST",
        url : "/user_balance",
        dataType : "json",
        data : {
            'username':'$username',
            'plat_name':'$plat_name'
        },
        success : function(data) {
            if(data.code == 200){
				$('#api_amount-$id').html(data.data);
			}else{
				alert('刷新失败：'+data.message);
			}
        },
    });
});
SCRIPT;
		}
		return $data;
    }	
    public function render()
    {
		
        // 获取外部传递的参数
       /* $username= $this->id;
        $tg = new TgService;
        $result = $tg->allusersbalance($username);
        // dd($result);
        $Balance = $result['data']['userblance'];
        $str = "";
        if($Balance){
            foreach ($Balance as $wo){
                if($wo['blance']>0){
                $str .= "<div style='line-height: 40px;'><li style='width:40%; float: left; padding-left: 10%; font-size:14px; '>".$wo['gamename']." </li><li style='width:40%; float: left; padding-left: 10%;font-size:14px;  '>".$wo['blance']."</li></div>";
                }
            }
            if ($str==""){
                $str = "<div style='line-height: 40px;font-size:18px; text-align:center '>暂无游戏余额</div>";
                             
            }
        }*/
		$username= $this->id;
		$user_api = User_Api::where('api_user', $username)->get()->toArray();
		Admin::script($this->script($user_api));
		$str = "";
		if($user_api){
			foreach($user_api as $key => $value){
				$str .= "<div style='line-height: 40px;'><li style='width:33%; float: left; padding-left: 10%; font-size:14px; '>".$value['api_code']." </li><li style='width:33%; float: left; padding-left: 10%;font-size:14px;  '><span id='api_amount-".$value['id']."'>".$value['api_money']."</span></li><li id='check-draw-money-".$value['id']."' style='width:33%; float: left; padding-left: 10%;font-size:14px;  '>刷新</li></div>";
			}
		}else{
			$str = "<div style='line-height: 40px;font-size:18px; text-align:center '>暂无游戏余额</div>";
		}
        return $str;
    }
}
