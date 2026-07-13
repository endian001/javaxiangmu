<?php

namespace App\Admin\Tools;

use App\Admin\Support\OperationPermission;
use Dcat\Admin\Grid\Tools\AbstractTool;
use Illuminate\Http\Request;
use Cache;

class AgentFanyong extends AbstractTool
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
        return '加入返佣队列';
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
        return ['确认将全部代理返佣加入队列吗？', '定时任务执行后才会真正入账。'];
    }

    /**
     * 处理请求
     * 如果你的类中包含了此方法，则点击按钮后会自动向后端发起ajax请求，并且会通过此方法处理请求逻辑
     * 
     * @param Request $request
     */
    public function handle(Request $request)
    {
        if (! OperationPermission::can(OperationPermission::AGENT_COMMISSION_SETTLE)) {
            return $this->response()->error('无权执行代理返佣')->refresh();
        }

        // 你的代码逻辑
        Cache::put('all_agent_fanyong',1);
        return $this->response()->success('已加入返佣队列，等待定时任务执行')->refresh();
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
