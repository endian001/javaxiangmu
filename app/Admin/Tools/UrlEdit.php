<?php

namespace App\Admin\Tools;

use Dcat\Admin\Grid\Tools\AbstractTool;
use Illuminate\Http\Request;
use Cache;
use App\Models\GameList;
class UrlEdit extends AbstractTool
{
    /**
     * 按钮样式定义，默认 btn btn-white waves-effect
     * 
     * @var string 
     */
    protected $style = 'btn btn-white waves-effect';


    /**
     * 按钮文本
     * 
     * @return string|void
     */
    public function title()
    {
        return '一键修复';
    }

    /**
     *  确认弹窗，如果不需要则返回空即可
     * 
     * @return array|string|void
     */
    public function confirm()
    {
        // 只显示标题
//        return '您确定要发送新的提醒消息吗？';

        // 显示标题和内容
        return ['您确定要修复吗？', ''];
    }

    /**
     * 处理请求
     * 如果你的类中包含了此方法，则点击按钮后会自动向后端发起ajax请求，并且会通过此方法处理请求逻辑
     * 
     * @param Request $request
     */
    public function handle(Request $request)
    {
        $GameList = new GameList();
		$data = $GameList->get()->toArray();
		foreach($data as $key => $value){
			if($value['check_yes_img']){
				$url = explode('/uploads',$value['check_yes_img']);
				$check_yes_img = env('APP_URL').'/uploads'.$url[1];
                $edit1 = GameList::where('id', $value['id'])->first();
				$edit1->check_yes_img = $check_yes_img;
				$edit1->save();
			}
			if($value['check_no_img']){
				$url = explode('/uploads',$value['check_no_img']);
				$check_no_img = env('APP_URL').'/uploads'.$url[1];
                $edit1 = GameList::where('id', $value['id'])->first();
				$edit1->check_no_img = $check_no_img;
				$edit1->save();
			}
			if($value['api_logo_img']){
				$url = explode('/uploads',$value['api_logo_img']);
				$api_logo_img = env('APP_URL').'/uploads'.$url[1];
                $edit1 = GameList::where('id', $value['id'])->first();
				$edit1->api_logo_img = $api_logo_img;
				$edit1->save();
			}
			if($value['mobile_img']){
				$url = explode('/uploads',$value['mobile_img']);
				$mobile_img = env('APP_URL').'/uploads'.$url[1];
                $edit1 = GameList::where('id', $value['id'])->first();
				$edit1->mobile_img = $mobile_img;
				$edit1->save();
			}
			if($value['app_img']){
				$url = explode('/uploads',$value['app_img']);
				$app_img = env('APP_URL').'/uploads'.$url[1];
                $edit1 = GameList::where('id', $value['id'])->first();
				$edit1->app_img = $app_img;
				$edit1->save();
			}
			if($value['app_icon']){
				$url = explode('/uploads',$value['app_icon']);
				$app_icon = env('APP_URL').'/uploads'.$url[1];
                $edit1 = GameList::where('id', $value['id'])->first();
				$edit1->app_icon = $app_icon;
				$edit1->save();
			}			
		}
        return $this->response()->success('操作成功')->refresh();
    }

    /**
     * 设置请求参数
     * 
     * @return array|void
     */
    public function parameters()
    {
        return [

        ];
    }
}