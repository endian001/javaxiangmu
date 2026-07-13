<?php

namespace App\Admin\Controllers;

use App\Admin\Services\PlatformSettingsService;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlatformSettingsController extends Controller
{
    private $service;

    public function __construct(PlatformSettingsService $service)
    {
        $this->service = $service;
    }

    public function index(Content $content, Request $request)
    {
        $tabs = $this->service->tabs();
        $tab = (string) $request->input('tab', 'platform');
        if (!isset($tabs[$tab])) {
            $tab = 'platform';
        }
        $fields = $this->service->fields($tab);
        $values = $this->loadValues($fields);
        $customerServices = DB::table('platform_customer_services')
            ->orderBy('position')
            ->orderBy('id')
            ->get();
        $appBuilds = DB::table('platform_app_builds as builds')
            ->leftJoin('admin_users as users', 'users.id', '=', 'builds.requested_by')
            ->select(['builds.*', 'users.username as requested_by_name'])
            ->orderByDesc('builds.id')
            ->limit(50)
            ->get();

        return $content
            ->title('平台基本配置')
            ->description('平台设置 / 90400')
            ->body(view('admin.platform-settings', compact(
                'tabs',
                'tab',
                'fields',
                'values',
                'customerServices',
                'appBuilds'
            ))->render());
    }

    public function saveTab(Request $request, $tab)
    {
        try {
            $fields = $this->service->fields($tab);
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }

        $values = $this->service->filterValues($tab, $request->all());
        foreach ($this->service->fileFields($tab) as $key) {
            if (!$request->hasFile($key)) {
                continue;
            }
            $file = $request->file($key);
            if (!$file->isValid()) {
                return $this->error($key.' 上传失败', 422);
            }
            $disk = config('admin.upload.disk') ?: 'public';
            $values[$key] = Storage::disk($disk)->putFile(
                'platform-settings',
                $file
            );
        }

        DB::transaction(function () use ($values) {
            foreach ($values as $key => $value) {
                DB::table('system_config')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
            $this->syncLegacyConfig($values);
        });

        $this->audit('platform.settings.update', 'platform_settings', '更新平台配置 '.$tab, [
            'tab' => $tab,
            'keys' => array_keys($values),
        ]);

        return $this->success('平台配置已保存');
    }

    public function saveCustomerService(Request $request, $id = null)
    {
        $data = $request->validate([
            'service_type' => 'required|string|max:50',
            'display_name' => 'required|string|max:100',
            'service_url' => 'required|string|max:1000',
            'position' => 'required|integer|min:0|max:100000',
            'min_player_level' => 'required|integer|min:0|max:100000',
        ]);
        $serviceUrl = trim($data['service_url']);
        if (!$this->isUsableCustomerContactUrl($serviceUrl)) {
            return $this->error('客服链接不可用，请填写有效的 http(s) 或电话链接', 422);
        }
        $now = now();
        $values = [
            'service_type' => trim($data['service_type']),
            'display_name' => trim($data['display_name']),
            'service_url' => $serviceUrl,
            'position' => (int) $data['position'],
            'min_player_level' => (int) $data['min_player_level'],
            'status' => $this->asBoolean($request->input('status', 0)),
            'updated_by' => $this->currentAdminId(),
            'updated_at' => $now,
        ];
        if ($id) {
            if (!DB::table('platform_customer_services')->where('id', $id)->exists()) {
                return $this->error('客服记录不存在', 404);
            }
            DB::table('platform_customer_services')->where('id', $id)->update($values);
            $action = 'platform.customer_service.update';
        } else {
            $values['created_by'] = $this->currentAdminId();
            $values['created_at'] = $now;
            $id = DB::table('platform_customer_services')->insertGetId($values);
            $action = 'platform.customer_service.create';
        }

        $this->audit($action, 'platform_customer_services', '保存客服链接 '.$values['display_name'], [
            'id' => (int) $id,
            'service_type' => $values['service_type'],
        ]);

        return $this->success('客服链接已保存');
    }

    public function deleteCustomerService($id)
    {
        $row = DB::table('platform_customer_services')->where('id', $id)->first();
        if (!$row) {
            return $this->error('客服记录不存在', 404);
        }
        DB::table('platform_customer_services')->where('id', $id)->delete();
        $this->audit(
            'platform.customer_service.delete',
            'platform_customer_services',
            '删除客服链接 '.$row->display_name,
            ['id' => (int) $id]
        );

        return $this->success('客服链接已删除');
    }

    public function requestAppBuild(Request $request)
    {
        $packageName = trim((string) DB::table('system_config')
            ->where('key', 'platform_package_suffix')
            ->value('value'));
        $desktopName = trim((string) DB::table('system_config')
            ->where('key', 'platform_app_desktop_name')
            ->value('value'));
        $domain = trim((string) DB::table('system_config')
            ->where('key', 'platform_android_fixed_domain')
            ->value('value'));
        if ($packageName === '' || $desktopName === '') {
            return $this->error('请先保存 APP 桌面名字和包名后缀', 422);
        }

        $buildNo = 'APP-'.date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $now = now();
        $id = DB::table('platform_app_builds')->insertGetId([
            'build_no' => $buildNo,
            'package_name' => $packageName,
            'domain' => $domain,
            'status' => 'pending',
            'requested_by' => $this->currentAdminId(),
            'requested_at' => $now,
            'expires_at' => $now->copy()->addDays(7),
            'details' => json_encode([
                'desktop_name' => $desktopName,
                'sync_download_links' => $this->asBoolean(
                    $request->input('sync_download_links', 0)
                ),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        $this->audit('platform.app_build.request', 'platform_app_builds', '提交 APP 打包请求 '.$buildNo, [
            'id' => $id,
            'package_name' => $packageName,
            'domain' => $domain,
        ]);

        return $this->success('APP 打包请求已进入队列', [
            'build_no' => $buildNo,
        ]);
    }

    private function loadValues(array $fields)
    {
        $keys = array_column($fields, 'key');
        $values = DB::table('system_config')
            ->whereIn('key', $keys)
            ->pluck('value', 'key')
            ->all();
        $fallbacks = [
            'platform_maintenance' => DB::table('system_config')->where('key', 'site_state')->value('value') === '0' ? '1' : '0',
            'platform_main_domain' => DB::table('system_config')->where('key', 'official_domain')->value('value')
                ?: DB::table('system_config')->where('key', 'agent_url')->value('value'),
            'platform_mobile_web_url' => DB::table('system_config')->where('key', 'agent_wap_uri')->value('value'),
            'platform_android_app_url' => DB::table('system_config')->where('key', 'android_download_url')->value('value'),
            'platform_app_version' => DB::table('system_config')->where('key', 'android_version')->value('value'),
            'platform_ios_unsigned_url' => DB::table('system_config')->where('key', 'ios_download_url')->value('value'),
        ];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $values)) {
                $values[$key] = isset($fallbacks[$key]) ? $fallbacks[$key] : '';
            }
        }

        return $values;
    }

    private function syncLegacyConfig(array $values)
    {
        $map = [
            'platform_main_domain' => 'official_domain',
            'platform_mobile_web_url' => 'agent_wap_uri',
            'platform_android_app_url' => 'android_download_url',
            'platform_app_version' => 'android_version',
            'platform_ios_unsigned_url' => 'ios_download_url',
        ];
        foreach ($map as $newKey => $legacyKey) {
            if (!array_key_exists($newKey, $values)) {
                continue;
            }
            DB::table('system_config')->updateOrInsert(
                ['key' => $legacyKey],
                ['value' => $values[$newKey]]
            );
        }
        if (array_key_exists('platform_maintenance', $values)) {
            DB::table('system_config')->updateOrInsert(
                ['key' => 'site_state'],
                ['value' => $values['platform_maintenance'] === '1' ? '0' : '1']
            );
        }
    }

    private function audit($action, $module, $content, array $context)
    {
        $admin = Admin::user();
        DB::table('admin_audit_logs')->insert([
            'admin_user_id' => $admin ? $admin->getKey() : null,
            'admin_name' => $admin ? $admin->username : null,
            'action' => $action,
            'module' => $module,
            'content' => $content,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 2000),
            'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);
    }

    private function currentAdminId()
    {
        $admin = Admin::user();

        return $admin ? (int) $admin->getKey() : null;
    }

    private function asBoolean($value)
    {
        return in_array($value, [1, '1', true, 'true', 'on', 'yes'], true);
    }

    private function isUsableCustomerContactUrl($url)
    {
        $url = trim((string) $url);
        if (preg_match('/^tel:\+?[0-9()\-\s]{5,30}$/i', $url)) {
            return true;
        }

        return $this->isUsableCustomerServiceUrl($url);
    }

    private function isUsableCustomerServiceUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '' || !preg_match('/^https?:\/\//i', $url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }
        if (strpos($host, 'www.') === 0) {
            $host = substr($host, 4);
        }

        $placeholderHosts = [
            'baidu.com',
            'example.com',
            'example.net',
            'example.org',
            'localhost',
            '127.0.0.1',
        ];

        if (in_array($host, $placeholderHosts, true)) {
            return false;
        }

        return substr($host, -10) !== '.baidu.com';
    }

    private function success($message, array $data = [])
    {
        return response()->json(['status' => true, 'message' => $message, 'data' => $data]);
    }

    private function error($message, $status)
    {
        return response()->json(['status' => false, 'message' => $message], $status);
    }
}
