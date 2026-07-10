<?php

namespace App\Admin\Forms;

use App\Admin\Support\OperationPermission;
use App\Admin\Support\OpsChangeAudit;
use App\Models\GameRecord;
use App\Models\Recharge;
use Dcat\Admin\Widgets\Form;
use App\Models\SystemConfig;
use App\Models\Withdraw;
use App\User;
use Illuminate\Http\Request;

class SiteSetting extends Form
{
    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        
        $changes = $this->configChanges($input);
        $this->assertNonFundingConfigPermissions($changes);

        foreach ($input as $k => $v) {
            $arr = ['key' => $k,'value' => $v ?? ''];
            SystemConfig::updateOrCreate(['key' => $k],$arr);
        }
        $this->writeNonFundingConfigAudit($changes);

        return $this
				->response()
				->success('操作成功')
				->refresh();
    }

    protected function configChanges(array $input)
    {
        $changes = [];
        foreach ($input as $key => $value) {
            $old = SystemConfig::getValue($key);
            $new = $value ?? '';
            if ($this->normalizeConfigValue($old) === $this->normalizeConfigValue($new)) {
                continue;
            }
            $changes[$key] = [
                'label' => $this->configLabel($key),
                'old' => $this->maskedConfigValue($key, $old),
                'new' => $this->maskedConfigValue($key, $new),
            ];
        }

        return $changes;
    }

    protected function assertNonFundingConfigPermissions(array $changes)
    {
        if ($this->hasChangedAny($changes, $this->siteSettingKeys())) {
            OperationPermission::assert(OperationPermission::OPS_SITE_SETTING_UPDATE);
        }
        if ($this->hasChangedAny($changes, $this->apiSettingKeys())) {
            OperationPermission::assert(OperationPermission::API_PLATFORM_UPDATE);
        }
        if ($this->hasChangedAny($changes, $this->activitySettingKeys())) {
            OperationPermission::assert(OperationPermission::ACTIVITY_CONTENT_UPDATE);
        }
    }

    protected function writeNonFundingConfigAudit(array $changes)
    {
        $allowed = array_flip(array_merge($this->siteSettingKeys(), $this->apiSettingKeys(), $this->activitySettingKeys()));
        $filtered = array_intersect_key($changes, $allowed);
        if (!$filtered) {
            return;
        }

        OpsChangeAudit::insert('ops.site.setting.update', 0, 'site settings', $filtered);
    }

    protected function hasChangedAny(array $changes, array $keys)
    {
        return (bool) array_intersect_key($changes, array_flip($keys));
    }

    protected function siteSettingKeys()
    {
        return [
            'site_name',
            'site_logo',
            'app_logo',
            'site_title',
            'site_keyword',
            'kf_url',
            'safe_domain',
            'agent_url',
            'official_domain',
            'navigation_domains',
            'asset_domain',
            'sponsor_page_url_1',
            'sponsor_page_url_2',
            'download_bar_icon',
            'vip_rule_title_img',
            'site_state',
            'repair_tips',
            'android_version',
            'android_download_url',
            'android_download_qrcode',
            'ios_download_url',
            'ios_download_qrcode',
            'stream_chat_api_key',
            'stream_chat_secret',
            'stream_chat_enabled',
            'stream_chat_message_limit',
        ];
    }

    protected function apiSettingKeys()
    {
        return [
            'game_api',
            'merchant_account',
            'api_secret',
        ];
    }

    protected function activitySettingKeys()
    {
        return [
            'login_bonus_img',
            'redpacket',
            'isclose',
            'webcontent',
        ];
    }

    protected function configLabel($key)
    {
        $labels = [
            'api_secret' => 'api secret',
            'stream_chat_secret' => 'stream chat secret',
            'game_api' => 'game api',
            'merchant_account' => 'merchant account',
            'site_state' => 'site state',
            'redpacket' => 'red packet switch',
            'isclose' => 'homepage modal switch',
            'webcontent' => 'homepage modal content',
        ];

        return $labels[$key] ?? str_replace('_', ' ', $key);
    }

    protected function maskedConfigValue($key, $value)
    {
        if (in_array($key, ['api_secret', 'stream_chat_secret'], true)) {
            return trim((string)$value) === '' ? '' : '***';
        }

        $value = (string)$value;
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, 1000);
        }

        return substr($value, 0, 1000);
    }

    protected function normalizeConfigValue($value)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return trim((string)$value);
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->tab('网站配置', function () {
            $this->text('site_name','网站名称');
            $this->image('site_logo','网站Logo')->uniqueName();
            $this->image('app_logo','APP图标')->uniqueName();
            $this->text('site_title','网站标题');
            $this->text('site_keyword','网站关键词');
            $this->text('kf_url','客服链接');
            $this->text('safe_domain','安全域名')->help("多个用,隔开，不填则不限制");
            $this->text('agent_url','代理域名')->help("代理后台的访问地址，需要包含http://前缀");
            $this->text('official_domain','前端展示域名')->help("例如 xy281.eu.cc，用于首页下载条和导航展示");
            $this->text('navigation_domains','导航备用域名')->help("多个用英文逗号隔开，留空时沿用安全域名");
            $this->text('asset_domain','前端素材域名')->help("可填素材 CDN 域名，不填则使用本地/上传素材");
            $this->text('sponsor_page_url_1','赞助页地址1')->help("用于赞助/导航 iframe 页面");
            $this->text('sponsor_page_url_2','赞助页地址2')->help("用于赞助/导航 iframe 页面");
            $this->image('download_bar_icon','下载条图标')->uniqueName();
            $this->image('login_bonus_img','登录页活动图')->uniqueName();
            $this->image('vip_rule_title_img','VIP规则标题图')->uniqueName();
            $this->radio('redpacket','红包开关')->options([1 => '正常',0 => '关闭'])->default(1);
            $this->radio('fanshui','返水开关')->options([1 => '正常',0 => '关闭'])->default(1);
            $this->radio('site_state','网站状态')->options([1 => '正常',0 => '维护'])->default(1);
            $this->text('repair_tips','网站维护提示语');
            $this->radio('isclose','首页弹窗')->options([1 => '正常',0 => '关闭'])->default(1);
            $this->editor('webcontent','弹窗内容');
        });

        $this->tab('APP配置', function () {
            $this->text('android_version','安卓版本号');
            $this->text('android_download_url','安卓下载地址');
            $this->image('android_download_qrcode','安卓下载二维码')->uniqueName();
            // $this->text('ios_version','IOS版本号');
            $this->text('ios_download_url','分发下载地址');
            $this->image('ios_download_qrcode','分发下载二维码')->uniqueName();
        });
        
        $this->tab('接口设置', function() {
            $this->text('game_api','API接口地址');
            $this->text('merchant_account','商户账号');
            $this->text('api_secret','商户API密钥');
        });
        
        $this->tab('支付设置', function() {
            $this->text('onlinepay_title','网上支付标题');
            $this->text('onlinepay_des','网上支付说明');
            $this->text('companypay_title','公司入款标题');
            $this->text('companypay_des','公司入款说明');

        });
        
        $this->tab('存款设置',function() {
            $this->number('min_recharge_money','最低存款限额');
            $this->text('recharge_fee','充值赠送比例(%)');
            $this->number('max_recharge_money','最高存款限额');
            $this->decimal('usdt_rate','USDT汇率');
            $this->decimal('min_price','银行卡最低充值金额')->required();
            $this->decimal('max_price','银行卡最大充值金额')->required();
        });

        $this->tab('提款设置',function() {
            $this->time('withdraw_begin_time','提款开始时间');
            $this->time('withdraw_end_time','提款结束时间');
            $this->number('daily_withdraw_times','每日可提款次数');
            $this->number('min_withdraw_money','最低提款限额');
            $this->number('max_withdraw_money','最高提款限额');
            $this->text('withdraw_fee','打码量倍数');
            $this->number('min_fanshui_money','最低返水限额');
            $this->decimal('withdraw_cash_fee','USDT-TRC20手续费');
            $this->decimal('withdraw_fee_usdt_erc','USDT-ERC20手续费');
            $this->decimal('withdraw_usdt_rate','提现USDT汇率');
        });

        
        $this->tab('代理设置',function() {
            $this->select('settlement','代理结算周期')->options([1 => 'T+1',2 => 'T+2',3 => 'T+3',4 => 'T+4',5 => 'T+5',6 => 'T+6',7 => 'T+7',10 => 'T+10',15 => 'T+15',20 => 'T+20',30 => 'T+30'])->default(4);
            $this->radio('settlementtypes','代理结算方式')->options([1 => '按输赢结算',0 => '按打码量结算'])->default(1);
            $this->number('settlementlevel','代理返佣级数');
        });
        
        $this->tab('提醒设置', function() {
            /*$this->select('notice_set','提醒方式')->options([1 => '语音加弹窗提醒',2 => '语音提醒',3 => '弹窗提醒'])->default(1);
            $this->radio('auto_refresh','是否自动刷新')->options([0 => '关闭',1 => '开启']);
            $this->number('auto_refresh_interval','刷新时间(秒)');*/			
            $this->file('recharge_apply_audio','充值提醒语音上传')->uniqueName();
            $this->file('withdraw_apply_audio','提款提醒语音上传')->uniqueName();
            $this->file('activity_apply_audio','活动申请语音上传')->uniqueName();
            $this->file('agent_apply_audio','代理申请语音上传')->uniqueName();
            // $this->text('syslogday','借呗申请语音上传');
            // $this->text('syslogday','金管家申请语音上传');

        });
        
        $this->tab('聊天室设置', function() {
            $this->text('stream_chat_api_key','API Key')->help('在 Stream Dashboard 获取 API Key');
            $this->text('stream_chat_secret','Secret')->help('在 Stream Dashboard 获取 Secret，用于生成用户 Token');
            $this->radio('stream_chat_enabled','聊天室开关')->options([1 => '开启',0 => '关闭'])->default(0);
            $this->number('stream_chat_message_limit','历史消息数量')->help('每次加载的历史消息数量，范围：10-500，默认：50')->default(50)->min(10)->max(500);
            
            // 添加配置教程
            $tutorial = <<<HTML
<div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 4px; border-left: 4px solid #409eff;">
    <h4 style="margin-top: 0; margin-bottom: 15px; color: #409eff; font-size: 16px;">
        <i class="fa fa-info-circle"></i> Stream Chat 配置教程
    </h4>
    <div style="line-height: 1.8; color: #606266;">
        <p style="margin: 10px 0;"><strong>第一步：注册 Stream Chat 账号</strong></p>
        <ol style="margin: 10px 0; padding-left: 25px;">
            <li>访问 Stream Chat 官网：<a href="https://getstream.io/try-for-free/" target="_blank" style="color: #409eff;">https://getstream.io/try-for-free/</a></li>
            <li>填写邮箱和密码完成注册(不验证邮箱)</li>
            <li>Work Email/注册邮箱</li> 
            <li>Organization Name/组织名称（可以加符号例如：MSH-aurora）</li> 
            <li>Username/用户名（可以加符号例如：MSH-aurora）</li> 
            <li>Password/密码（12位含大写字母、小写字母、数字、特殊符号）</li> 
        </ol>
        
        <p style="margin: 15px 0 10px 0;"><strong>第二步：创建应用并获取凭证</strong></p>
        <ol style="margin: 10px 0; padding-left: 25px;">
            <li>登录后，点击 <strong>"MSH-aurora"</strong> 注册时自动创建的</li>     
            <li>复制 <strong>"Key"</strong> 填入"API Key"字段</li>
            <li>复制 <strong>"Secret"</strong> 填入"Secret"字段</li>
        </ol>
        
        <p style="margin: 15px 0 10px 0;"><strong>第三步：配置频道权限（重要）</strong></p>
        <ol style="margin: 10px 0; padding-left: 25px;">
            <li>在 Stream Chat Dashboard 中，进入您的应用</li>
            <li>点击左侧菜单 <strong>"Permissions"</strong> 或 <strong>"权限"</strong></li>
            <li>找到 <strong>"Channel Types"</strong> 或 <strong>"频道类型"</strong></li>
            <li>选择 <strong>"team"</strong> 类型（用于公共聊天室）</li>
            <li>确保以下权限设置为允许：
                <ul style="margin: 5px 0; padding-left: 20px;">
                    <li><strong>Read Channel</strong>（读取频道）：允许所有角色</li>
                    <li><strong>Create Message</strong>（发送消息）：允许所有角色</li>
                    <li><strong>Join Channel</strong>（加入频道）：允许所有角色</li>
                </ul>
            </li>
            <li>或者，可以在 <strong>"Roles"</strong> 中将 <strong>"user"</strong> 角色的权限设置为允许访问 team 频道</li>
        </ol>
        
        <p style="margin: 15px 0 10px 0;"><strong>第四步：启用聊天室功能</strong></p>
        <ol style="margin: 10px 0; padding-left: 25px;">
            <li>填写完 API Key 和 Secret 后，将 <strong>"聊天室开关"</strong> 设置为 <strong>"开启"</strong></li>
            <li>点击 <strong>"提交"</strong> 保存配置</li>
        </ol>
        
        <div style="margin-top: 15px; padding: 10px; background: #fff3cd; border-radius: 4px; border-left: 3px solid #ffc107;">
            <p style="margin: 0; color: #856404;"><strong>提示：</strong></p>
            <ul style="margin: 5px 0 0 0; padding-left: 20px; color: #856404;">
                <li>Stream Chat 提供免费套餐：每月 1,000 月活用户（MAU）</li>
                <li>Secret 用于后端生成用户 Token，请妥善保管，不要泄露</li>
                <li>如果遇到权限错误（403 Forbidden），请检查 Stream Chat Dashboard 中的权限设置</li>
                <li>配置完成后，用户在前端访问"发现"页面的"聊天室"即可使用</li>
            </ul>
        </div>
        
        <p style="margin: 15px 0 0 0; color: #909399; font-size: 13px;">
            <i class="fa fa-external-link"></i> 
            更多帮助文档：<a href="https://getstream.io/chat/docs/" target="_blank" style="color: #409eff;">Stream Chat 官方文档</a>
        </p>
    </div>
</div>
HTML;
            
            $this->html($tutorial);
        });
        
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [
            'safe_domain'  => SystemConfig::getValue('safe_domain'),
            'agent_url'  => SystemConfig::getValue('agent_url'),
            'official_domain' => SystemConfig::getValue('official_domain'),
            'navigation_domains' => SystemConfig::getValue('navigation_domains'),
            'asset_domain' => SystemConfig::getValue('asset_domain'),
            'sponsor_page_url_1' => SystemConfig::getValue('sponsor_page_url_1'),
            'sponsor_page_url_2' => SystemConfig::getValue('sponsor_page_url_2'),
            'download_bar_icon' => SystemConfig::getValue('download_bar_icon'),
            'login_bonus_img' => SystemConfig::getValue('login_bonus_img'),
            'vip_rule_title_img' => SystemConfig::getValue('vip_rule_title_img'),
            'site_name'  => SystemConfig::getValue('site_name'),
            'site_logo' => SystemConfig::getValue('site_logo'),
            'app_logo' => SystemConfig::getValue('app_logo'),
            'site_title' => SystemConfig::getValue('site_title'),
            'site_keyword' => SystemConfig::getValue('site_keyword'),
            'kf_url' => SystemConfig::getValue('kf_url'),
            'site_state' => SystemConfig::getValue('site_state'),
            'repair_tips' => SystemConfig::getValue('repair_tips'),
            'android_version' => SystemConfig::getValue('android_version'),
            'android_download_url' => SystemConfig::getValue('android_download_url'),
            'android_download_qrcode' => SystemConfig::getValue('android_download_qrcode'),
            'ios_version' => SystemConfig::getValue('ios_version'),
            'ios_download_url' => SystemConfig::getValue('ios_download_url'),
            'ios_download_qrcode' => SystemConfig::getValue('ios_download_qrcode'),
            'game_api' => SystemConfig::getValue('game_api'),
            'merchant_account' => SystemConfig::getValue('merchant_account'),
            'api_secret' => SystemConfig::getValue('api_secret'),
            'withdraw_begin_time' => SystemConfig::getValue('withdraw_begin_time'),
            'withdraw_end_time' => SystemConfig::getValue('withdraw_end_time'),
            'daily_withdraw_times' => SystemConfig::getValue('daily_withdraw_times'),
            'min_withdraw_money' => SystemConfig::getValue('min_withdraw_money'),
            'max_withdraw_money' => SystemConfig::getValue('max_withdraw_money'),
            'min_recharge_money' => SystemConfig::getValue('min_recharge_money'),
            'max_recharge_money' => SystemConfig::getValue('max_recharge_money'),
            'isclose' => SystemConfig::getValue('isclose'),
            'applyday' => SystemConfig::getValue('applyday'),
            'gameorder' => SystemConfig::getValue('gameorder'),
            'syslogday' => SystemConfig::getValue('syslogday'),
            'accountday' => SystemConfig::getValue('accountday'),
            'agentday' => SystemConfig::getValue('agentday'),
            'webcontent' => SystemConfig::getValue('webcontent'),
            'fanshui' => SystemConfig::getValue('fanshui'),
            'redpacket' => SystemConfig::getValue('redpacket'),
            'withdraw_fee' => SystemConfig::getValue('withdraw_fee'),
            'recharge_fee' => SystemConfig::getValue('recharge_fee'),
            'min_fanshui_money' => SystemConfig::getValue('min_fanshui_money'),
            'settlement' => SystemConfig::getValue('settlement'),
            'settlementlevel'=> SystemConfig::getValue('settlementlevel'),
            'notice_set' => SystemConfig::getValue('notice_set'),
            'recharge_apply_audio' => SystemConfig::getValue('recharge_apply_audio'),
            'withdraw_apply_audio' => SystemConfig::getValue('withdraw_apply_audio'),
            'activity_apply_audio' => SystemConfig::getValue('activity_apply_audio'),
            'agent_apply_audio' => SystemConfig::getValue('agent_apply_audio'),
            'settlementtypes' =>SystemConfig::getValue('settlementtypes'),
            'usdt_rate' => SystemConfig::getValue('usdt_rate'),
            'withdraw_cash_fee' => SystemConfig::getValue('withdraw_cash_fee'),
            'withdraw_usdt_rate' => SystemConfig::getValue('withdraw_usdt_rate'),
            'withdraw_fee_usdt_erc' => SystemConfig::getValue('withdraw_fee_usdt_erc'),
            'min_price' => SystemConfig::getValue('min_price'),
            'max_price' => SystemConfig::getValue('max_price'),
            'auto_refresh' => SystemConfig::getValue('auto_refresh'),
            'auto_refresh_interval' => SystemConfig::getValue('auto_refresh_interval'),
            'stream_chat_api_key' => SystemConfig::getValue('stream_chat_api_key'),
            'stream_chat_secret' => SystemConfig::getValue('stream_chat_secret'),
            'stream_chat_enabled' => SystemConfig::getValue('stream_chat_enabled'),
            'stream_chat_message_limit' => SystemConfig::getValue('stream_chat_message_limit'),
        ];
    }
}
