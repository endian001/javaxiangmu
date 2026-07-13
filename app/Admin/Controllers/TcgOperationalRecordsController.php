<?php

namespace App\Admin\Controllers;

use App\Admin\Support\OperationPermission;
use App\Admin\Support\TcgShellCatalog;
use Carbon\Carbon;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TcgOperationalRecordsController extends Controller
{
    public function index(Content $content, Request $request, string $code)
    {
        if (!OperationPermission::can(OperationPermission::PLATFORM_OPERATIONS_READ)) {
            abort(403, '无权访问运营页面');
        }

        $page = TcgShellCatalog::page($code);
        if (!$page) {
            abort(404);
        }

        $schema = $this->schemaForCode($code);
        $page = array_merge($page, $this->pageMetaForCode($code));
        $keyword = trim((string) $request->input('keyword', ''));
        $status = trim((string) $request->input('status', ''));
        $tableReady = Schema::hasTable($schema['table']);
        $records = $tableReady
            ? $this->recordsQuery($request, $code, $schema)->paginate(20)
            : $this->emptyPaginator($request);

        return $content
            ->title($page['title'])
            ->description($page['category'].' / '.$page['code'].' / '.$schema['mode_label'])
            ->body(view('admin.tcg-operational-records', [
                'page' => $page,
                'schema' => $schema,
                'records' => $records,
                'keyword' => $keyword,
                'status' => $status,
                'tableReady' => $tableReady,
                'canWrite' => OperationPermission::can(OperationPermission::PLATFORM_OPERATIONS_WRITE),
                'canDelete' => OperationPermission::can(OperationPermission::PLATFORM_OPERATIONS_DELETE),
                'canExport' => OperationPermission::can(OperationPermission::PLATFORM_OPERATIONS_EXPORT),
            ])->render());
    }

    public function save(Request $request, string $code, $id = null)
    {
        if ($denied = $this->authorizeJson(OperationPermission::PLATFORM_OPERATIONS_WRITE)) {
            return $denied;
        }

        if (!TcgShellCatalog::page($code)) {
            return $this->error('页面不存在', 404);
        }

        $schema = $this->schemaForCode($code);
        if (!Schema::hasTable($schema['table'])) {
            return $this->error('业务表尚未创建，请先执行迁移', 500);
        }

        $data = $request->validate($this->rulesForSchema($schema));
        $now = now();
        $adminId = Admin::user() ? Admin::user()->getKey() : null;
        $values = $this->valuesForSchema($schema, $data);
        $values['updated_by'] = $adminId;
        $values['updated_at'] = $now;

        if (!$schema['business']) {
            $values['page_code'] = $code;
        }

        if ($id) {
            $query = DB::table($schema['table'])->where('id', (int) $id);
            if (!$schema['business']) {
                $query->where('page_code', $code);
            }
            $updated = $query->update($values);

            if (!$updated) {
                return $this->error('记录不存在', 404);
            }
            $action = $schema['business'] ? 'tcg_ops.business.update' : 'tcg_ops.record.update';
        } else {
            $values['created_by'] = $adminId;
            $values['created_at'] = $now;
            $id = DB::table($schema['table'])->insertGetId($values);
            $action = $schema['business'] ? 'tcg_ops.business.create' : 'tcg_ops.record.create';
        }

        $this->audit($action, $schema['table'], '保存 '.$schema['mode_label'], [
            'page_code' => $code,
            'id' => (int) $id,
            'table' => $schema['table'],
            'business' => $schema['business'],
        ]);

        return response()->json([
            'status' => true,
            'message' => '记录已保存',
            'data' => ['id' => (int) $id],
        ]);
    }

    public function delete(string $code, $id)
    {
        if ($denied = $this->authorizeJson(OperationPermission::PLATFORM_OPERATIONS_DELETE)) {
            return $denied;
        }

        $schema = $this->schemaForCode($code);
        if (!Schema::hasTable($schema['table'])) {
            return $this->error('业务表尚未创建，请先执行迁移', 500);
        }

        $query = DB::table($schema['table'])->where('id', (int) $id);
        if (!$schema['business']) {
            $query->where('page_code', $code);
        }
        $deleted = $query->delete();

        if (!$deleted) {
            return $this->error('记录不存在', 404);
        }

        $this->audit($schema['business'] ? 'tcg_ops.business.delete' : 'tcg_ops.record.delete', $schema['table'], '删除 '.$schema['mode_label'], [
            'page_code' => $code,
            'id' => (int) $id,
            'deleted' => $deleted,
        ]);

        return response()->json(['status' => true, 'message' => '记录已删除']);
    }

    public function export(Request $request, string $code): StreamedResponse
    {
        if (!OperationPermission::can(OperationPermission::PLATFORM_OPERATIONS_EXPORT)) {
            abort(403, '无权导出运营记录');
        }

        $page = TcgShellCatalog::page($code);
        if (!$page) {
            abort(404);
        }

        $schema = $this->schemaForCode($code);
        $rows = Schema::hasTable($schema['table'])
            ? $this->recordsQuery($request, $code, $schema)->get()
            : collect();
        $columns = $this->exportColumns($schema);

        $filename = 'tcg-'.$code.'-'.($schema['business'] ? 'business' : 'records').'-'.date('YmdHis').'.csv';
        $this->audit($schema['business'] ? 'tcg_ops.business.export' : 'tcg_ops.record.export', $schema['table'], '导出 '.$schema['mode_label'], [
            'page_code' => $code,
            'rows' => $rows->count(),
            'filters' => [
                'keyword' => trim((string) $request->input('keyword', '')),
                'status' => trim((string) $request->input('status', '')),
            ],
        ]);

        return response()->streamDownload(function () use ($rows, $columns) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, array_column($columns, 'label'));
            foreach ($rows as $row) {
                fputcsv($out, array_map(function ($column) use ($row) {
                    return $row->{$column['name']} ?? '';
                }, $columns));
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function schemaForCode(string $code): array
    {
        $business = $this->businessSchemas();
        if (isset($business[$code])) {
            return $business[$code] + [
                'business' => true,
                'mode_label' => '业务专属逻辑',
                'status_options' => $this->statusOptions(),
            ];
        }

        return [
            'business' => false,
            'mode_label' => '通用运营记录',
            'table' => 'tcg_operational_records',
            'description' => '当前页面仍为通用运营记录。扩展字段可填写 JSON，后续继续拆成业务专属逻辑。',
            'keyword_fields' => ['title', 'username', 'target_key', 'remark'],
            'status_options' => $this->statusOptions(),
            'fields' => [
                ['name' => 'title', 'label' => '标题', 'type' => 'text', 'required' => true, 'max' => 200],
                ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                ['name' => 'username', 'label' => '用户名', 'type' => 'text', 'max' => 100],
                ['name' => 'target_key', 'label' => '对象标识', 'type' => 'text', 'max' => 200],
                ['name' => 'amount', 'label' => '金额', 'type' => 'number'],
                ['name' => 'payload', 'label' => '扩展 JSON', 'type' => 'textarea', 'max' => 20000],
                ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
            ],
            'columns' => [
                ['name' => 'id', 'label' => 'ID'],
                ['name' => 'title', 'label' => '标题'],
                ['name' => 'username', 'label' => '用户'],
                ['name' => 'target_key', 'label' => '对象标识'],
                ['name' => 'amount', 'label' => '金额'],
                ['name' => 'status', 'label' => '状态'],
                ['name' => 'remark', 'label' => '备注'],
                ['name' => 'updated_at', 'label' => '更新时间'],
            ],
        ];
    }

    private function businessSchemas(): array
    {
        return [
            '20393' => [
                'table' => 'tcg_activity_blacklists',
                'description' => '活动黑名单会在前台活动申请时生效。启用状态、未过期，并命中用户或活动的记录会阻止申请。',
                'keyword_fields' => ['username', 'reason', 'remark'],
                'fields' => [
                    ['name' => 'username', 'label' => '用户名', 'type' => 'text', 'required' => true, 'max' => 100],
                    ['name' => 'user_id', 'label' => '用户 ID', 'type' => 'integer'],
                    ['name' => 'activity_id', 'label' => '活动 ID', 'type' => 'integer'],
                    ['name' => 'reason', 'label' => '原因', 'type' => 'text', 'max' => 255],
                    ['name' => 'starts_at', 'label' => '开始时间', 'type' => 'datetime'],
                    ['name' => 'ends_at', 'label' => '结束时间', 'type' => 'datetime'],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'username', 'label' => '用户名'],
                    ['name' => 'activity_id', 'label' => '活动 ID'],
                    ['name' => 'reason', 'label' => '原因'],
                    ['name' => 'status', 'label' => '状态'],
                    ['name' => 'ends_at', 'label' => '结束时间'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
            '24786' => [
                'table' => 'tcg_activity_coupons',
                'description' => '活动票券使用专属票券表维护，支持按活动、用户、金额、有效期和状态管理。',
                'keyword_fields' => ['coupon_code', 'username', 'remark'],
                'fields' => [
                    ['name' => 'coupon_code', 'label' => '票券码', 'type' => 'text', 'required' => true, 'max' => 100],
                    ['name' => 'activity_id', 'label' => '活动 ID', 'type' => 'integer'],
                    ['name' => 'username', 'label' => '用户名', 'type' => 'text', 'max' => 100],
                    ['name' => 'amount', 'label' => '金额', 'type' => 'number'],
                    ['name' => 'expires_at', 'label' => '过期时间', 'type' => 'datetime'],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'coupon_code', 'label' => '票券码'],
                    ['name' => 'activity_id', 'label' => '活动 ID'],
                    ['name' => 'username', 'label' => '用户名'],
                    ['name' => 'amount', 'label' => '金额'],
                    ['name' => 'status', 'label' => '状态'],
                    ['name' => 'expires_at', 'label' => '过期时间'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
            '24800' => [
                'table' => 'tcg_activity_multiplier_rules',
                'description' => '翻倍管理策略使用专属规则表维护，支持活动、倍数、金额范围、有效期和状态。',
                'keyword_fields' => ['rule_name', 'remark'],
                'fields' => [
                    ['name' => 'rule_name', 'label' => '策略名称', 'type' => 'text', 'required' => true, 'max' => 150],
                    ['name' => 'activity_id', 'label' => '活动 ID', 'type' => 'integer'],
                    ['name' => 'multiplier', 'label' => '倍数', 'type' => 'number'],
                    ['name' => 'min_amount', 'label' => '最低金额', 'type' => 'number'],
                    ['name' => 'max_amount', 'label' => '最高金额', 'type' => 'number'],
                    ['name' => 'starts_at', 'label' => '开始时间', 'type' => 'datetime'],
                    ['name' => 'ends_at', 'label' => '结束时间', 'type' => 'datetime'],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'rule_name', 'label' => '策略名称'],
                    ['name' => 'activity_id', 'label' => '活动 ID'],
                    ['name' => 'multiplier', 'label' => '倍数'],
                    ['name' => 'status', 'label' => '状态'],
                    ['name' => 'starts_at', 'label' => '开始时间'],
                    ['name' => 'ends_at', 'label' => '结束时间'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
            '52000' => [
                'table' => 'tcg_player_limit_rules',
                'description' => '玩家限红设置使用专属规则表维护，支持按用户和游戏范围设置最大下注与最大派彩。',
                'keyword_fields' => ['username', 'game_scope', 'remark'],
                'fields' => [
                    ['name' => 'username', 'label' => '用户名', 'type' => 'text', 'required' => true, 'max' => 100],
                    ['name' => 'user_id', 'label' => '用户 ID', 'type' => 'integer'],
                    ['name' => 'game_scope', 'label' => '游戏范围', 'type' => 'text', 'required' => true, 'max' => 150],
                    ['name' => 'max_bet', 'label' => '最大下注', 'type' => 'number'],
                    ['name' => 'max_payout', 'label' => '最大派彩', 'type' => 'number'],
                    ['name' => 'starts_at', 'label' => '开始时间', 'type' => 'datetime'],
                    ['name' => 'ends_at', 'label' => '结束时间', 'type' => 'datetime'],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'username', 'label' => '用户名'],
                    ['name' => 'game_scope', 'label' => '游戏范围'],
                    ['name' => 'max_bet', 'label' => '最大下注'],
                    ['name' => 'max_payout', 'label' => '最大派彩'],
                    ['name' => 'status', 'label' => '状态'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
            '41002' => [
                'table' => 'tcg_user_game_restrictions',
                'description' => '用户游戏限制使用专属限制表维护，支持按用户、游戏范围和限制类型管理。',
                'keyword_fields' => ['username', 'game_scope', 'restriction_type', 'remark'],
                'fields' => [
                    ['name' => 'username', 'label' => '用户名', 'type' => 'text', 'required' => true, 'max' => 100],
                    ['name' => 'user_id', 'label' => '用户 ID', 'type' => 'integer'],
                    ['name' => 'game_scope', 'label' => '游戏范围', 'type' => 'text', 'required' => true, 'max' => 150],
                    ['name' => 'restriction_type', 'label' => '限制类型', 'type' => 'text', 'required' => true, 'max' => 50],
                    ['name' => 'starts_at', 'label' => '开始时间', 'type' => 'datetime'],
                    ['name' => 'ends_at', 'label' => '结束时间', 'type' => 'datetime'],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'username', 'label' => '用户名'],
                    ['name' => 'game_scope', 'label' => '游戏范围'],
                    ['name' => 'restriction_type', 'label' => '限制类型'],
                    ['name' => 'status', 'label' => '状态'],
                    ['name' => 'starts_at', 'label' => '开始时间'],
                    ['name' => 'ends_at', 'label' => '结束时间'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
        ];
    }

    private function pageMetaForCode(string $code): array
    {
        $meta = [
            '20393' => ['title' => '活动黑名单', 'category' => '活动红利'],
            '24786' => ['title' => '活动票券管理', 'category' => '活动红利'],
            '24800' => ['title' => '翻倍管理策略', 'category' => '活动红利'],
            '52000' => ['title' => '玩家限红设置', 'category' => '玩家管理'],
            '41002' => ['title' => '用户游戏限制', 'category' => '玩家管理'],
        ];

        return $meta[$code] ?? [];
    }

    private function statusOptions(): array
    {
        return [
            'active' => '启用',
            'pending' => '待处理',
            'disabled' => '停用',
            'closed' => '已关闭',
            'issued' => '已发放',
            'used' => '已使用',
            'expired' => '已过期',
        ];
    }

    private function rulesForSchema(array $schema): array
    {
        $rules = [];
        foreach ($schema['fields'] as $field) {
            $rule = !empty($field['required']) ? ['required'] : ['nullable'];
            if (($field['name'] ?? '') === 'status') {
                $rule[] = Rule::in(array_keys($schema['status_options']));
            } elseif (($field['type'] ?? 'text') === 'number') {
                $rule[] = 'numeric';
            } elseif (($field['type'] ?? 'text') === 'integer') {
                $rule[] = 'integer';
            } elseif (($field['type'] ?? 'text') === 'datetime') {
                $rule[] = 'date';
            } else {
                $rule[] = 'string';
                $rule[] = 'max:'.(int) ($field['max'] ?? 255);
            }
            $rules[$field['name']] = $rule;
        }

        return $rules;
    }

    private function valuesForSchema(array $schema, array $data): array
    {
        $values = [];
        foreach ($schema['fields'] as $field) {
            $name = $field['name'];
            $type = $field['type'] ?? 'text';
            $value = $data[$name] ?? null;

            if ($type === 'number' || $type === 'integer') {
                $values[$name] = ($value === null || $value === '') ? null : $value;
                continue;
            }

            if ($type === 'datetime') {
                $values[$name] = ($value === null || trim((string) $value) === '')
                    ? null
                    : Carbon::parse($value)->format('Y-m-d H:i:s');
                continue;
            }

            if ($name === 'payload') {
                $payloadText = trim((string) $value);
                if ($payloadText !== '') {
                    $decoded = json_decode($payloadText, true);
                    if (!is_array($decoded)) {
                        abort(response()->json(['status' => false, 'message' => '扩展数据必须是合法 JSON 对象'], 422));
                    }
                    $values[$name] = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                } else {
                    $values[$name] = null;
                }
                continue;
            }

            $text = trim(strip_tags((string) $value));
            $values[$name] = $text === '' && empty($field['required']) ? null : $text;
        }

        if (array_key_exists('status', $values)) {
            $values['status'] = preg_replace('/[^a-z0-9_-]/i', '', (string) $values['status']) ?: 'active';
        }

        return $values;
    }

    private function recordsQuery(Request $request, string $code, array $schema)
    {
        $query = DB::table($schema['table']);
        if (!$schema['business']) {
            $query->where('page_code', $code);
        }

        $keyword = trim((string) $request->input('keyword', ''));
        $fields = $schema['keyword_fields'] ?? [];
        if ($keyword !== '' && $fields) {
            $query->where(function ($inner) use ($keyword, $fields) {
                foreach ($fields as $index => $field) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $inner->{$method}($field, 'like', '%'.$keyword.'%');
                }
            });
        }

        $status = trim((string) $request->input('status', ''));
        if ($status !== '') {
            $query->where('status', $status);
        }

        return $query->orderByDesc('id');
    }

    private function exportColumns(array $schema): array
    {
        $columns = $schema['columns'];
        $existing = array_column($columns, 'name');
        foreach (['remark', 'created_at', 'updated_at'] as $name) {
            if (!in_array($name, $existing, true)) {
                $columns[] = ['name' => $name, 'label' => $name];
            }
        }

        return $columns;
    }

    private function emptyPaginator(Request $request): LengthAwarePaginator
    {
        return new LengthAwarePaginator([], 0, 20, 1, [
            'path' => $request->url(),
            'query' => $request->query(),
        ]);
    }

    private function error(string $message, int $status)
    {
        return response()->json(['status' => false, 'message' => $message], $status);
    }

    private function authorizeJson($ability)
    {
        if (OperationPermission::can($ability)) {
            return null;
        }

        return $this->error('无权执行该操作', 403);
    }

    private function audit($action, $module, $content, array $context): void
    {
        if (!Schema::hasTable('admin_audit_logs')) {
            return;
        }

        $admin = Admin::user();
        DB::table('admin_audit_logs')->insert([
            'admin_user_id' => $admin ? $admin->getKey() : null,
            'admin_name' => $admin ? ($admin->username ?? $admin->name ?? null) : null,
            'action' => $action,
            'module' => $module,
            'content' => $content,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 2000),
            'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);
    }
}
