<?php

namespace App\Admin\Controllers;

use App\Admin\Services\SystemUserAlignmentService;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SystemUserSettingsController extends Controller
{
    private $service;

    public function __construct(SystemUserAlignmentService $service)
    {
        $this->service = $service;
    }

    public function users(Content $content, Request $request)
    {
        $query = DB::table('admin_users as users')
            ->leftJoin(
                'admin_user_profiles as profiles',
                'profiles.admin_user_id',
                '=',
                'users.id'
            )
            ->leftJoin('admin_role_users as role_users', 'role_users.user_id', '=', 'users.id')
            ->leftJoin('admin_roles as roles', 'roles.id', '=', 'role_users.role_id')
            ->select([
                'users.id',
                'users.username',
                'users.name',
                'users.avatar',
                'users.created_at',
                'users.updated_at',
                'profiles.brand',
                'profiles.subscribed_brands',
                'profiles.google_auth_enabled',
                'profiles.last_seen_at',
                'profiles.last_login_ip',
            ])
            ->selectRaw('COALESCE(profiles.status, 1) as status')
            ->selectRaw("GROUP_CONCAT(DISTINCT roles.name ORDER BY roles.name SEPARATOR ', ') as roles")
            ->groupBy([
                'users.id',
                'users.username',
                'users.name',
                'users.avatar',
                'users.created_at',
                'users.updated_at',
                'profiles.brand',
                'profiles.subscribed_brands',
                'profiles.google_auth_enabled',
                'profiles.status',
                'profiles.last_seen_at',
                'profiles.last_login_ip',
            ]);

        if ($request->filled('keyword')) {
            $keyword = trim($request->input('keyword'));
            $query->where(function ($builder) use ($keyword) {
                $builder->where('users.username', 'like', '%'.$keyword.'%')
                    ->orWhere('users.name', 'like', '%'.$keyword.'%')
                    ->orWhere('profiles.brand', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('status')) {
            $query->whereRaw('COALESCE(profiles.status, 1) = ?', [
                (int) $request->input('status'),
            ]);
        }

        $users = $query->orderBy('users.id')->paginate(20)->appends($request->query());
        foreach ($users->items() as $user) {
            $brands = json_decode((string) $user->subscribed_brands, true);
            $user->subscribed_brands_list = is_array($brands) ? $brands : [];
        }

        return $this->render(
            $content,
            'users',
            '系统用户管理',
            compact('users')
        );
    }

    public function saveUser(Request $request, $id)
    {
        $user = DB::table('admin_users')->where('id', $id)->first();
        if (!$user) {
            return $this->error('系统用户不存在', 404);
        }

        $data = $request->validate([
            'name' => 'required|string|max:191',
            'brand' => 'nullable|string|max:100',
            'subscribed_brands' => 'nullable',
            'google_auth_enabled' => 'nullable',
            'status' => 'nullable',
        ]);
        $status = $this->asBoolean($request->input('status', 0));
        $currentAdminId = $this->currentAdminId();
        if ((int) $id === (int) $currentAdminId && !$status) {
            return $this->error('不能停用当前登录账号', 422);
        }

        $brands = $this->service->normalizeBrands(
            $request->input('subscribed_brands', '')
        );
        $now = now();
        DB::transaction(function () use ($id, $data, $request, $brands, $status, $now) {
            DB::table('admin_users')->where('id', $id)->update([
                'name' => trim($data['name']),
                'updated_at' => $now,
            ]);
            DB::table('admin_user_profiles')->updateOrInsert(
                ['admin_user_id' => (int) $id],
                [
                    'brand' => trim((string) $request->input('brand', '')),
                    'subscribed_brands' => json_encode(
                        $brands,
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                    ),
                    'google_auth_enabled' => $this->asBoolean(
                        $request->input('google_auth_enabled', 0)
                    ),
                    'status' => $status,
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );
        });

        $this->audit('user.update', 'system_users', '更新系统用户 '.$user->username, [
            'target_user_id' => (int) $id,
            'brand' => trim((string) $request->input('brand', '')),
            'subscribed_brands' => $brands,
            'status' => $status,
        ]);

        return $this->success('系统用户已更新');
    }

    public function roles(Content $content, Request $request)
    {
        $rolesQuery = DB::table('admin_roles as roles')
            ->leftJoin(
                'admin_role_permissions as role_permissions',
                'role_permissions.role_id',
                '=',
                'roles.id'
            )
            ->select(['roles.id', 'roles.name', 'roles.slug'])
            ->selectRaw('COUNT(role_permissions.permission_id) as permission_count')
            ->groupBy(['roles.id', 'roles.name', 'roles.slug']);
        if ($request->filled('role_keyword')) {
            $keyword = trim($request->input('role_keyword'));
            $rolesQuery->where(function ($builder) use ($keyword) {
                $builder->where('roles.name', 'like', '%'.$keyword.'%')
                    ->orWhere('roles.slug', 'like', '%'.$keyword.'%');
            });
        }
        $roles = $rolesQuery->orderBy('roles.id')->get();
        $selectedRoleId = (int) $request->input(
            'role_id',
            $roles->isNotEmpty() ? $roles->first()->id : 0
        );

        $permissionsQuery = DB::table('admin_permissions')
            ->select(['id', 'name', 'slug', 'http_method', 'http_path', 'parent_id', 'order']);
        if ($request->filled('permission_keyword')) {
            $keyword = trim($request->input('permission_keyword'));
            $permissionsQuery->where(function ($builder) use ($keyword) {
                $builder->where('name', 'like', '%'.$keyword.'%')
                    ->orWhere('slug', 'like', '%'.$keyword.'%')
                    ->orWhere('http_path', 'like', '%'.$keyword.'%');
            });
        }
        $permissions = $permissionsQuery
            ->orderBy('parent_id')
            ->orderBy('order')
            ->orderBy('id')
            ->get();
        foreach ($permissions as $permission) {
            $permission->depth = (int) $permission->parent_id > 0 ? 1 : 0;
        }

        $selectedPermissionIds = $selectedRoleId
            ? DB::table('admin_role_permissions')
                ->where('role_id', $selectedRoleId)
                ->pluck('permission_id')
                ->map(function ($id) {
                    return (int) $id;
                })
                ->all()
            : [];

        return $this->render(
            $content,
            'roles',
            '角色权限解析',
            compact(
                'roles',
                'selectedRoleId',
                'permissions',
                'selectedPermissionIds'
            )
        );
    }

    public function saveRolePermissions(Request $request, $id)
    {
        $role = DB::table('admin_roles')->where('id', $id)->first();
        if (!$role) {
            return $this->error('角色不存在', 404);
        }

        $requested = $request->input('permission_ids', []);
        if (!is_array($requested)) {
            return $this->error('权限参数格式不正确', 422);
        }
        $available = DB::table('admin_permissions')->pluck('id')->all();
        $permissionIds = $this->service->filterPermissionIds($requested, $available);
        $now = now();

        DB::transaction(function () use ($id, $permissionIds, $now) {
            DB::table('admin_role_permissions')->where('role_id', $id)->delete();
            if (!$permissionIds) {
                return;
            }
            $rows = [];
            foreach ($permissionIds as $permissionId) {
                $rows[] = [
                    'role_id' => (int) $id,
                    'permission_id' => $permissionId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('admin_role_permissions')->insert($rows);
        });

        $this->audit('role.permissions.update', 'system_roles', '更新角色权限 '.$role->name, [
            'role_id' => (int) $id,
            'permission_ids' => $permissionIds,
        ]);

        return $this->success('角色权限已保存');
    }

    public function ipWhitelist(Content $content, Request $request)
    {
        $query = DB::table('admin_ip_whitelists');
        if ($request->filled('keyword')) {
            $keyword = trim($request->input('keyword'));
            $query->where(function ($builder) use ($keyword) {
                $builder->where('ip_address', 'like', '%'.$keyword.'%')
                    ->orWhere('domain', 'like', '%'.$keyword.'%');
            });
        }
        if ($request->filled('status')) {
            $query->where('status', (int) $request->input('status'));
        }
        if ($request->filled('important')) {
            $query->where('is_important', (int) $request->input('important'));
        }
        $whitelists = $query->orderByDesc('is_important')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        return $this->render(
            $content,
            'ip-whitelist',
            'IP 白名单',
            compact('whitelists')
        );
    }

    public function saveIpWhitelist(Request $request, $id = null)
    {
        try {
            $ip = $this->service->validateIp($request->input('ip_address'));
        } catch (\InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $data = $request->validate([
            'domain' => 'nullable|string|max:191',
            'quota' => 'required|integer|min:1|max:100000',
            'auto_cleanup_days' => 'required|integer|min:0|max:3650',
        ]);
        $domain = trim((string) $request->input('domain', ''));
        $duplicate = DB::table('admin_ip_whitelists')
            ->where('ip_address', $ip)
            ->where('domain', $domain);
        if ($id) {
            $duplicate->where('id', '<>', $id);
        }
        if ($duplicate->exists()) {
            return $this->error('相同 IP 和域名已经存在', 422);
        }

        $now = now();
        $values = [
            'ip_address' => $ip,
            'domain' => $domain,
            'quota' => (int) $data['quota'],
            'auto_cleanup_days' => (int) $data['auto_cleanup_days'],
            'is_important' => $this->asBoolean($request->input('is_important', 0)),
            'status' => $this->asBoolean($request->input('status', 0)),
            'updated_by' => $this->currentAdminId(),
            'last_modified_at' => $now,
            'updated_at' => $now,
        ];
        if ($id) {
            $exists = DB::table('admin_ip_whitelists')->where('id', $id)->exists();
            if (!$exists) {
                return $this->error('IP 白名单记录不存在', 404);
            }
            DB::table('admin_ip_whitelists')->where('id', $id)->update($values);
            $action = 'ip_whitelist.update';
        } else {
            $values['created_by'] = $this->currentAdminId();
            $values['created_at'] = $now;
            $id = DB::table('admin_ip_whitelists')->insertGetId($values);
            $action = 'ip_whitelist.create';
        }

        $this->audit($action, 'ip_whitelist', '保存 IP 白名单 '.$ip, [
            'id' => (int) $id,
            'domain' => $domain,
            'quota' => (int) $data['quota'],
        ]);

        return $this->success('IP 白名单已保存');
    }

    public function deleteIpWhitelist($id)
    {
        $record = DB::table('admin_ip_whitelists')->where('id', $id)->first();
        if (!$record) {
            return $this->error('IP 白名单记录不存在', 404);
        }
        DB::table('admin_ip_whitelists')->where('id', $id)->delete();
        $this->audit(
            'ip_whitelist.delete',
            'ip_whitelist',
            '删除 IP 白名单 '.$record->ip_address,
            ['id' => (int) $id, 'domain' => $record->domain]
        );

        return $this->success('IP 白名单已删除');
    }

    public function tasks(Content $content, Request $request)
    {
        $query = $this->taskQuery($request);
        $tasks = $query->orderByDesc('id')->paginate(20)->appends($request->query());
        $taskTypes = $this->service->allowedTaskTypes();

        return $this->render(
            $content,
            'tasks',
            '任务管理',
            compact('tasks', 'taskTypes')
        );
    }

    public function runTask(Request $request)
    {
        $taskType = (string) $request->input('task_type', '');
        if (!$this->service->isAllowedTaskType($taskType)) {
            return $this->error('不支持的任务类型', 422);
        }
        $title = trim((string) $request->input(
            'title',
            $this->service->allowedTaskTypes()[$taskType]
        ));

        return $this->executeTask($taskType, $title);
    }

    public function retryTask($id)
    {
        $task = DB::table('admin_tasks')->where('id', $id)->first();
        if (!$task) {
            return $this->error('任务不存在', 404);
        }
        if (!$this->service->isAllowedTaskType($task->task_type)) {
            return $this->error('原任务类型已停用', 422);
        }

        return $this->executeTask(
            $task->task_type,
            '[重试] '.$task->title,
            ['retry_of' => (int) $task->id]
        );
    }

    public function taskHistory(Content $content, Request $request)
    {
        $query = $this->taskQuery($request)
            ->whereIn('status', ['success', 'failed']);
        $tasks = $query->orderByDesc('finished_at')
            ->orderByDesc('id')
            ->paginate(30)
            ->appends($request->query());

        return $this->render(
            $content,
            'task-history',
            '历史任务管理',
            compact('tasks')
        );
    }

    public function exportTaskHistory(Request $request)
    {
        $rows = $this->taskQuery($request)
            ->whereIn('status', ['success', 'failed'])
            ->orderByDesc('finished_at')
            ->limit(5000)
            ->get();

        return response()->stream(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [
                '任务号',
                '任务类型',
                '任务名称',
                '状态',
                '执行人',
                '开始时间',
                '结束时间',
                '错误信息',
            ]);
            foreach ($rows as $row) {
                fputcsv($output, [
                    $row->task_no,
                    $row->task_type,
                    $row->title,
                    $row->status,
                    $row->requested_by_name,
                    $row->started_at,
                    $row->finished_at,
                    $row->error_message,
                ]);
            }
            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="admin-task-history-'.date('Ymd-His').'.csv"',
        ]);
    }

    public function logs(Content $content, Request $request)
    {
        $tab = $request->input('tab') === 'user' ? 'user' : 'admin';
        $logs = $this->logQuery($request, $tab)
            ->paginate(30)
            ->appends($request->query());

        return $this->render(
            $content,
            'logs',
            '系统用户日志',
            compact('logs', 'tab')
        );
    }

    public function exportLogs(Request $request)
    {
        $tab = $request->input('tab') === 'user' ? 'user' : 'admin';
        $rows = $this->logQuery($request, $tab)->limit(10000)->get();

        return response()->stream(function () use ($rows, $tab) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            if ($tab === 'user') {
                fputcsv($output, [
                    'ID',
                    '用户名',
                    '动作类型',
                    '功能',
                    '日志内容',
                    'IP',
                    '时间',
                ]);
                foreach ($rows as $row) {
                    fputcsv($output, [
                        $row->id,
                        $row->username,
                        $row->type,
                        $row->module,
                        $row->content,
                        $row->ip_address,
                        $row->created_at,
                    ]);
                }
            } else {
                fputcsv($output, [
                    'ID',
                    '品牌/管理员',
                    '动作类型',
                    '功能',
                    '日志内容',
                    'IP',
                    '时间',
                ]);
                foreach ($rows as $row) {
                    fputcsv($output, [
                        $row->id,
                        $row->username,
                        $row->type,
                        $row->module,
                        $row->content,
                        $row->ip_address,
                        $row->created_at,
                    ]);
                }
            }
            fclose($output);
        }, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="system-user-logs-'.date('Ymd-His').'.csv"',
        ]);
    }

    private function render(Content $content, $module, $title, array $data)
    {
        $data['module'] = $module;
        $data['pageTitle'] = $title;

        return $content
            ->title($title)
            ->description('系统用户设置')
            ->body(view('admin.system-user-settings', $data)->render());
    }

    private function executeTask($taskType, $title, array $payload = [])
    {
        $taskNo = $this->service->makeTaskNumber();
        $now = now();
        $taskId = DB::table('admin_tasks')->insertGetId([
            'task_no' => $taskNo,
            'task_type' => $taskType,
            'title' => $title ?: $this->service->allowedTaskTypes()[$taskType],
            'status' => 'running',
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'requested_by' => $this->currentAdminId(),
            'started_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        try {
            $result = $this->performTask($taskType);
            DB::table('admin_tasks')->where('id', $taskId)->update([
                'status' => 'success',
                'result' => json_encode(
                    $result,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'finished_at' => now(),
                'updated_at' => now(),
            ]);
            $this->audit('task.run', 'admin_tasks', '执行后台任务 '.$taskNo, [
                'task_id' => $taskId,
                'task_type' => $taskType,
                'status' => 'success',
            ]);

            return $this->success('任务执行成功', ['task_no' => $taskNo]);
        } catch (Throwable $exception) {
            DB::table('admin_tasks')->where('id', $taskId)->update([
                'status' => 'failed',
                'error_message' => mb_substr($exception->getMessage(), 0, 2000),
                'finished_at' => now(),
                'updated_at' => now(),
            ]);
            $this->audit('task.run', 'admin_tasks', '后台任务失败 '.$taskNo, [
                'task_id' => $taskId,
                'task_type' => $taskType,
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            return $this->error('任务执行失败：'.$exception->getMessage(), 500);
        }
    }

    private function performTask($taskType)
    {
        if ($taskType === 'health_check') {
            DB::select('SELECT 1');
            return [
                'php_version' => PHP_VERSION,
                'laravel_version' => app()->version(),
                'database' => 'ok',
                'storage_writable' => is_writable(storage_path()),
                'checked_at' => date('c'),
            ];
        }

        $commands = [
            'cache_clear' => 'cache:clear',
            'view_clear' => 'view:clear',
            'route_clear' => 'route:clear',
        ];
        if (!isset($commands[$taskType])) {
            throw new \RuntimeException('不支持的任务类型');
        }
        $exitCode = Artisan::call($commands[$taskType]);
        if ($exitCode !== 0) {
            throw new \RuntimeException(trim(Artisan::output()) ?: '命令执行失败');
        }

        return [
            'command' => $commands[$taskType],
            'output' => trim(Artisan::output()),
            'finished_at' => date('c'),
        ];
    }

    private function taskQuery(Request $request)
    {
        $query = DB::table('admin_tasks as tasks')
            ->leftJoin('admin_users as users', 'users.id', '=', 'tasks.requested_by')
            ->select(['tasks.*', 'users.username as requested_by_name']);
        if ($request->filled('task_no')) {
            $query->where('tasks.task_no', 'like', '%'.trim($request->input('task_no')).'%');
        }
        if ($request->filled('status')) {
            $query->where('tasks.status', $request->input('status'));
        }
        if ($request->filled('requested_by')) {
            $keyword = trim($request->input('requested_by'));
            $query->where('users.username', 'like', '%'.$keyword.'%');
        }
        if ($request->filled('start_date')) {
            $query->whereDate('tasks.created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('tasks.created_at', '<=', $request->input('end_date'));
        }

        return $query;
    }

    private function logQuery(Request $request, $tab)
    {
        if ($tab === 'user') {
            $query = DB::table('user_operate_logs as logs')
                ->leftJoin('users', 'users.id', '=', 'logs.user_id')
                ->select([
                    'logs.id',
                    DB::raw("COALESCE(users.username, CONCAT('UID:', logs.user_id)) as username"),
                    'logs.type',
                    DB::raw("'用户操作' as module"),
                    DB::raw('COALESCE(logs.`desc`, logs.info) as content'),
                    'logs.login_ip as ip_address',
                    'logs.created_at',
                ]);
        } else {
            $query = DB::table('admin_audit_logs as logs')
                ->leftJoin(
                    'admin_user_profiles as profiles',
                    'profiles.admin_user_id',
                    '=',
                    'logs.admin_user_id'
                )
                ->select([
                    'logs.id',
                    DB::raw("CONCAT(COALESCE(profiles.brand, '默认品牌'), ' / ', COALESCE(logs.admin_name, '-')) as username"),
                    'logs.action as type',
                    'logs.module',
                    'logs.content',
                    'logs.ip_address',
                    'logs.created_at',
                ]);
        }

        if ($request->filled('keyword')) {
            $keyword = trim($request->input('keyword'));
            $query->where(function ($builder) use ($keyword, $tab) {
                if ($tab === 'user') {
                    $builder->where('users.username', 'like', '%'.$keyword.'%')
                        ->orWhere('logs.desc', 'like', '%'.$keyword.'%')
                        ->orWhere('logs.info', 'like', '%'.$keyword.'%');
                } else {
                    $builder->where('logs.admin_name', 'like', '%'.$keyword.'%')
                        ->orWhere('logs.content', 'like', '%'.$keyword.'%')
                        ->orWhere('logs.module', 'like', '%'.$keyword.'%');
                }
            });
        }
        if ($request->filled('action')) {
            if ($tab === 'user') {
                $query->where('logs.type', $request->input('action'));
            } else {
                $query->where('logs.action', 'like', '%'.trim($request->input('action')).'%');
            }
        }
        if ($request->filled('start_date')) {
            $query->whereDate('logs.created_at', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('logs.created_at', '<=', $request->input('end_date'));
        }

        return $query->orderByDesc('logs.id');
    }

    private function audit($action, $module, $content, array $context = [])
    {
        if (!Schema::hasTable('admin_audit_logs')) {
            return;
        }
        $admin = Admin::user();
        DB::table('admin_audit_logs')->insert([
            'admin_user_id' => $admin ? $admin->getKey() : null,
            'admin_name' => $admin ? $admin->username : null,
            'action' => $action,
            'module' => $module,
            'content' => $content,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 2000),
            'context' => json_encode(
                $context,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
            ),
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

    private function success($message, array $data = [])
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    private function error($message, $status)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $status);
    }
}
