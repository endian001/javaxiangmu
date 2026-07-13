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
        ] + $this->playerManagementSchemas() + $this->playerLevelSchemas() + $this->pointMallSchemas();
    }

    private function playerManagementSchemas(): array
    {
        return [
            '20430' => [
                'table' => 'tcg_operation_tags',
                'description' => '运营标签用于给玩家打上风控、VIP、活动等运营标记，支持按用户、来源和有效期管理。',
                'action_label' => '新增运营标签',
                'edit_label' => '编辑运营标签',
                'empty_state' => '暂无运营标签',
                'keyword_fields' => ['tag_code', 'tag_name', 'username', 'source', 'remark'],
                'fields' => [
                    ['name' => 'tag_code', 'label' => '标签编码', 'type' => 'text', 'required' => true, 'max' => 80],
                    ['name' => 'tag_name', 'label' => '标签名称', 'type' => 'text', 'required' => true, 'max' => 120],
                    ['name' => 'tag_color', 'label' => '标签颜色', 'type' => 'text', 'max' => 40],
                    ['name' => 'username', 'label' => '用户名', 'type' => 'text', 'max' => 100],
                    ['name' => 'user_id', 'label' => '用户 ID', 'type' => 'integer'],
                    ['name' => 'source', 'label' => '标签来源', 'type' => 'select', 'options' => ['manual' => '人工', 'risk' => '风控', 'vip' => 'VIP', 'activity' => '活动']],
                    ['name' => 'starts_at', 'label' => '开始时间', 'type' => 'datetime'],
                    ['name' => 'ends_at', 'label' => '结束时间', 'type' => 'datetime'],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'tag_code', 'label' => '标签编码'],
                    ['name' => 'tag_name', 'label' => '标签名称'],
                    ['name' => 'username', 'label' => '用户名'],
                    ['name' => 'source', 'label' => '来源'],
                    ['name' => 'status', 'label' => '状态'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
            '680308' => [
                'table' => 'tcg_otp_verification_records',
                'description' => 'OTP 验证记录用于追踪短信、邮件和验证器的一次性验证码发送与校验结果，方便安全审计。',
                'action_label' => '登记 OTP 记录',
                'edit_label' => '编辑 OTP 记录',
                'empty_state' => '暂无 OTP 验证记录',
                'keyword_fields' => ['username', 'receiver', 'otp_scene', 'channel', 'request_ip', 'failure_reason'],
                'fields' => [
                    ['name' => 'otp_scene', 'label' => '验证场景', 'type' => 'select', 'required' => true, 'options' => ['login' => '登录', 'register' => '注册', 'withdraw' => '提现', 'password_reset' => '找回密码', 'fund_password' => '资金密码']],
                    ['name' => 'channel', 'label' => '验证渠道', 'type' => 'select', 'required' => true, 'options' => ['sms' => '短信', 'email' => '邮件', 'authenticator' => '验证器', 'telegram' => 'Telegram']],
                    ['name' => 'receiver', 'label' => '接收账号', 'type' => 'text', 'required' => true, 'max' => 150],
                    ['name' => 'username', 'label' => '用户名', 'type' => 'text', 'max' => 100],
                    ['name' => 'user_id', 'label' => '用户 ID', 'type' => 'integer'],
                    ['name' => 'request_ip', 'label' => '请求 IP', 'type' => 'text', 'max' => 80],
                    ['name' => 'device_id', 'label' => '设备标识', 'type' => 'text', 'max' => 150],
                    ['name' => 'verify_result', 'label' => '验证结果', 'type' => 'select', 'required' => true, 'options' => ['sent' => '已发送', 'verified' => '验证通过', 'failed' => '验证失败', 'expired' => '已过期']],
                    ['name' => 'failure_reason', 'label' => '失败原因', 'type' => 'text', 'max' => 255],
                    ['name' => 'verified_at', 'label' => '验证时间', 'type' => 'datetime'],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'otp_scene', 'label' => '场景'],
                    ['name' => 'channel', 'label' => '渠道'],
                    ['name' => 'receiver', 'label' => '接收账号'],
                    ['name' => 'username', 'label' => '用户名'],
                    ['name' => 'verify_result', 'label' => '结果'],
                    ['name' => 'request_ip', 'label' => '请求 IP'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
        ];
    }

    private function playerLevelSchemas(): array
    {
        return [
            '250003' => [
                'table' => 'tcg_player_level_histories',
                'description' => '等级历史用于记录玩家等级变化，保留原等级、新等级、触发来源、原因和生效时间。',
                'action_label' => '登记等级变更',
                'edit_label' => '编辑等级变更',
                'empty_state' => '暂无等级历史',
                'keyword_fields' => ['username', 'change_source', 'reason', 'remark'],
                'fields' => [
                    ['name' => 'username', 'label' => '用户名', 'type' => 'text', 'required' => true, 'max' => 100],
                    ['name' => 'user_id', 'label' => '用户 ID', 'type' => 'integer'],
                    ['name' => 'old_level', 'label' => '原等级', 'type' => 'integer'],
                    ['name' => 'new_level', 'label' => '新等级', 'type' => 'integer'],
                    ['name' => 'change_source', 'label' => '变更来源', 'type' => 'select', 'required' => true, 'options' => ['deposit' => '充值', 'bet' => '投注', 'manual' => '人工', 'reward' => '奖励', 'system' => '系统']],
                    ['name' => 'reason', 'label' => '变更原因', 'type' => 'text', 'max' => 255],
                    ['name' => 'effective_at', 'label' => '生效时间', 'type' => 'datetime'],
                    ['name' => 'expired_at', 'label' => '失效时间', 'type' => 'datetime'],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'username', 'label' => '用户名'],
                    ['name' => 'old_level', 'label' => '原等级'],
                    ['name' => 'new_level', 'label' => '新等级'],
                    ['name' => 'change_source', 'label' => '来源'],
                    ['name' => 'status', 'label' => '状态'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
            '250004' => [
                'table' => 'tcg_frontend_copy_settings',
                'description' => '前台文案设置用于按语言、端类型和文案 key 管理会员等级相关展示文案，支持版本和发布状态。',
                'action_label' => '新增前台文案',
                'edit_label' => '编辑前台文案',
                'empty_state' => '暂无前台文案',
                'keyword_fields' => ['copy_key', 'locale', 'client_type', 'title', 'remark'],
                'fields' => [
                    ['name' => 'copy_key', 'label' => '文案 Key', 'type' => 'text', 'required' => true, 'max' => 120],
                    ['name' => 'locale', 'label' => '语言', 'type' => 'select', 'required' => true, 'options' => ['zh-CN' => '中文', 'th-TH' => '泰语', 'en-US' => '英语', 'vi-VN' => '越南语', 'id-ID' => '印尼语', 'ms-MY' => '马来语', 'km-KH' => '高棉语', 'lo-LA' => '老挝语', 'my-MM' => '缅甸语']],
                    ['name' => 'client_type', 'label' => '展示端', 'type' => 'select', 'required' => true, 'options' => ['all' => '全部', 'desktop' => '电脑端', 'mobile' => '手机端', 'app' => 'APP']],
                    ['name' => 'title', 'label' => '标题', 'type' => 'text', 'max' => 200],
                    ['name' => 'body', 'label' => '正文', 'type' => 'textarea', 'max' => 20000],
                    ['name' => 'version', 'label' => '版本', 'type' => 'text', 'max' => 80],
                    ['name' => 'published_at', 'label' => '发布时间', 'type' => 'datetime'],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'copy_key', 'label' => '文案 Key'],
                    ['name' => 'locale', 'label' => '语言'],
                    ['name' => 'client_type', 'label' => '展示端'],
                    ['name' => 'title', 'label' => '标题'],
                    ['name' => 'status', 'label' => '状态'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
        ];
    }

    private function pointMallSchemas(): array
    {
        return [
            '20220' => [
                'table' => 'tcg_point_rules',
                'description' => '积分规则设置用于配置充值、投注、活动等奖励积分规则，启用后给积分发放逻辑消费。',
                'action_label' => '新增积分规则',
                'edit_label' => '编辑积分规则',
                'empty_state' => '暂无积分规则',
                'keyword_fields' => ['rule_code', 'rule_name', 'earn_scene', 'remark'],
                'fields' => [
                    ['name' => 'rule_code', 'label' => '规则编码', 'type' => 'text', 'required' => true, 'max' => 80],
                    ['name' => 'rule_name', 'label' => '规则名称', 'type' => 'text', 'required' => true, 'max' => 150],
                    ['name' => 'earn_scene', 'label' => '积分场景', 'type' => 'select', 'required' => true, 'options' => ['deposit' => '充值', 'bet' => '投注', 'activity' => '活动', 'manual' => '人工']],
                    ['name' => 'points_per_unit', 'label' => '单位积分', 'type' => 'number'],
                    ['name' => 'daily_limit', 'label' => '每日上限', 'type' => 'integer'],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'rule_code', 'label' => '规则编码'],
                    ['name' => 'rule_name', 'label' => '规则名称'],
                    ['name' => 'earn_scene', 'label' => '场景'],
                    ['name' => 'points_per_unit', 'label' => '单位积分'],
                    ['name' => 'daily_limit', 'label' => '每日上限'],
                    ['name' => 'status', 'label' => '状态'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
            '20260' => [
                'table' => 'tcg_point_adjustments',
                'description' => '玩家积分调整用于运营人工加减积分，记录用户、调整方向、积分变动和原因。',
                'action_label' => '登记积分调整',
                'edit_label' => '编辑积分调整',
                'empty_state' => '暂无积分调整记录',
                'keyword_fields' => ['username', 'adjust_type', 'reason_code', 'related_order_no', 'remark'],
                'fields' => [
                    ['name' => 'username', 'label' => '用户名', 'type' => 'text', 'required' => true, 'max' => 100],
                    ['name' => 'user_id', 'label' => '用户 ID', 'type' => 'integer'],
                    ['name' => 'adjust_type', 'label' => '调整类型', 'type' => 'select', 'required' => true, 'options' => ['add' => '增加', 'subtract' => '扣减']],
                    ['name' => 'points_delta', 'label' => '积分变动', 'type' => 'integer'],
                    ['name' => 'reason_code', 'label' => '原因编码', 'type' => 'text', 'max' => 80],
                    ['name' => 'related_order_no', 'label' => '关联单号', 'type' => 'text', 'max' => 120],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'username', 'label' => '用户名'],
                    ['name' => 'adjust_type', 'label' => '类型'],
                    ['name' => 'points_delta', 'label' => '积分变动'],
                    ['name' => 'reason_code', 'label' => '原因'],
                    ['name' => 'status', 'label' => '状态'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
            '20599' => [
                'table' => 'tcg_point_mall_products',
                'description' => '商城商品设置用于维护积分商城可兑换商品、积分价格、库存和上下架状态。',
                'action_label' => '新增商城商品',
                'edit_label' => '编辑商城商品',
                'empty_state' => '暂无商城商品',
                'keyword_fields' => ['product_code', 'product_name', 'remark'],
                'fields' => [
                    ['name' => 'product_code', 'label' => '商品编码', 'type' => 'text', 'required' => true, 'max' => 80],
                    ['name' => 'product_name', 'label' => '商品名称', 'type' => 'text', 'required' => true, 'max' => 150],
                    ['name' => 'points_price', 'label' => '积分价格', 'type' => 'integer'],
                    ['name' => 'stock_total', 'label' => '总库存', 'type' => 'integer'],
                    ['name' => 'stock_used', 'label' => '已兑换', 'type' => 'integer'],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'product_code', 'label' => '商品编码'],
                    ['name' => 'product_name', 'label' => '商品名称'],
                    ['name' => 'points_price', 'label' => '积分价格'],
                    ['name' => 'stock_total', 'label' => '总库存'],
                    ['name' => 'stock_used', 'label' => '已兑换'],
                    ['name' => 'status', 'label' => '状态'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
            '20530' => [
                'table' => 'tcg_point_exchange_orders',
                'description' => '商品兑换申请用于记录玩家积分兑换订单、数量、消耗积分和发放信息。',
                'action_label' => '登记兑换申请',
                'edit_label' => '编辑兑换申请',
                'empty_state' => '暂无兑换申请',
                'keyword_fields' => ['order_no', 'username', 'product_code', 'delivery_info', 'remark'],
                'fields' => [
                    ['name' => 'order_no', 'label' => '兑换单号', 'type' => 'text', 'required' => true, 'max' => 120],
                    ['name' => 'username', 'label' => '用户名', 'type' => 'text', 'required' => true, 'max' => 100],
                    ['name' => 'user_id', 'label' => '用户 ID', 'type' => 'integer'],
                    ['name' => 'product_code', 'label' => '商品编码', 'type' => 'text', 'required' => true, 'max' => 80],
                    ['name' => 'quantity', 'label' => '数量', 'type' => 'integer'],
                    ['name' => 'points_cost', 'label' => '消耗积分', 'type' => 'integer'],
                    ['name' => 'delivery_info', 'label' => '发放信息', 'type' => 'textarea', 'max' => 20000],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'order_no', 'label' => '兑换单号'],
                    ['name' => 'username', 'label' => '用户名'],
                    ['name' => 'product_code', 'label' => '商品编码'],
                    ['name' => 'quantity', 'label' => '数量'],
                    ['name' => 'points_cost', 'label' => '消耗积分'],
                    ['name' => 'status', 'label' => '状态'],
                    ['name' => 'updated_at', 'label' => '更新时间'],
                ],
            ],
            '31210' => [
                'table' => 'tcg_point_reward_records',
                'description' => '积分奖励记录用于登记系统或运营发放的积分奖励，便于按来源和关联单号追溯。',
                'action_label' => '登记积分奖励',
                'edit_label' => '编辑积分奖励',
                'empty_state' => '暂无积分奖励记录',
                'keyword_fields' => ['reward_no', 'username', 'reward_source', 'related_order_no', 'remark'],
                'fields' => [
                    ['name' => 'reward_no', 'label' => '奖励单号', 'type' => 'text', 'required' => true, 'max' => 120],
                    ['name' => 'username', 'label' => '用户名', 'type' => 'text', 'required' => true, 'max' => 100],
                    ['name' => 'user_id', 'label' => '用户 ID', 'type' => 'integer'],
                    ['name' => 'reward_source', 'label' => '奖励来源', 'type' => 'text', 'required' => true, 'max' => 80],
                    ['name' => 'points_amount', 'label' => '奖励积分', 'type' => 'integer'],
                    ['name' => 'related_order_no', 'label' => '关联单号', 'type' => 'text', 'max' => 120],
                    ['name' => 'status', 'label' => '状态', 'type' => 'select', 'required' => true],
                    ['name' => 'remark', 'label' => '备注', 'type' => 'textarea', 'max' => 20000],
                ],
                'columns' => [
                    ['name' => 'id', 'label' => 'ID'],
                    ['name' => 'reward_no', 'label' => '奖励单号'],
                    ['name' => 'username', 'label' => '用户名'],
                    ['name' => 'reward_source', 'label' => '来源'],
                    ['name' => 'points_amount', 'label' => '奖励积分'],
                    ['name' => 'related_order_no', 'label' => '关联单号'],
                    ['name' => 'status', 'label' => '状态'],
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
            '20430' => ['title' => '运营标签', 'category' => '玩家管理'],
            '680308' => ['title' => 'OTP验证记录', 'category' => '玩家管理'],
            '250003' => ['title' => '等级历史', 'category' => '玩家等级'],
            '250004' => ['title' => '前台文案设置', 'category' => '玩家等级'],
            '20220' => ['title' => '积分规则设置', 'category' => '积分商城'],
            '20260' => ['title' => '玩家积分调整', 'category' => '积分商城'],
            '20599' => ['title' => '商城商品设置', 'category' => '积分商城'],
            '20530' => ['title' => '商品兑换申请', 'category' => '积分商城'],
            '31210' => ['title' => '积分奖励记录', 'category' => '积分商城'],
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
            'sent' => '已发送',
            'verified' => '已验证',
            'failed' => '失败',
            'draft' => '草稿',
            'published' => '已发布',
            'archived' => '已归档',
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
