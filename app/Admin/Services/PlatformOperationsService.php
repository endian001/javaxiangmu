<?php

namespace App\Admin\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class PlatformOperationsService
{
    private const PAGES = [
        '90510' => [
            'code' => '90510',
            'title' => '平台站点配置',
            'module' => '平台设置',
            'mode' => 'settings',
            'filters' => ['section', 'keyword'],
            'columns' => ['setting_key', 'setting_value', 'updated_by', 'updated_at'],
            'actions' => ['save', 'export'],
            'setting_fields' => [
                'site_name',
                'site_title',
                'site_keyword',
                'site_logo',
                'site_state',
                'safe_domain',
            ],
        ],
        '36000' => [
            'code' => '36000',
            'title' => '域名线路管理',
            'module' => '平台设置',
            'mode' => 'records',
            'filters' => ['keyword', 'status', 'line_type'],
            'columns' => ['title', 'domain', 'line_type', 'status', 'sort_order', 'updated_at'],
            'actions' => ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
            'record_fields' => ['domain', 'line_type'],
        ],
        '31018' => [
            'code' => '31018',
            'title' => '游戏厂商设置',
            'module' => '平台设置',
            'mode' => 'legacy',
            'filters' => ['keyword', 'status', 'platform_name'],
            'columns' => ['platform_name', 'game_count', 'site_state', 'app_state', 'updated_at'],
            'actions' => ['edit', 'status', 'import', 'export'],
            'legacy_table' => 'game_lists',
            'legacy_fields' => ['platform_name', 'site_state', 'app_state'],
        ],
        '90401' => [
            'code' => '90401',
            'title' => '平台功能配置',
            'module' => '平台设置',
            'mode' => 'settings',
            'filters' => ['section', 'keyword'],
            'columns' => ['setting_key', 'setting_value', 'updated_by', 'updated_at'],
            'actions' => ['save', 'export'],
            'setting_fields' => [
                'customer_service_enabled',
                'customer_service_url',
                'online_service_url',
                'gameorder',
                'service_type',
                'service_url',
            ],
        ],
        '24001' => [
            'code' => '24001',
            'title' => '提现风控配置',
            'module' => '平台设置',
            'mode' => 'settings',
            'filters' => ['section', 'keyword'],
            'columns' => ['setting_key', 'setting_value', 'updated_by', 'updated_at'],
            'actions' => ['save', 'export'],
            'setting_fields' => [
                'daily_withdraw_times',
                'min_withdraw_money',
                'max_withdraw_money',
                'withdraw_cash_fee',
                'withdraw_fee',
                'withdraw_fee_usdt_erc',
                'withdraw_fee_usdt_trc',
                'withdraw_begin_time',
                'withdraw_end_time',
                'withdraw_apply_audio',
            ],
        ],
        '20068' => [
            'code' => '20068',
            'title' => '平台支付管理',
            'module' => '平台设置',
            'mode' => 'legacy',
            'filters' => ['keyword', 'status', 'category'],
            'columns' => ['name', 'category', 'bonus_ratio', 'state', 'sort_order', 'updated_at'],
            'actions' => ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
            'legacy_table' => 'pay_types',
            'legacy_fields' => [
                'name',
                'category',
                'bonus_ratio',
                'state',
                'sort_order',
                'merchant_no',
                'merchant_key',
                'merchant_url',
                'merchant_identifier',
                'merchant_code',
            ],
        ],
        '20028' => [
            'code' => '20028',
            'title' => '支付账号设置',
            'module' => '平台设置',
            'mode' => 'legacy',
            'filters' => ['keyword', 'status', 'account_type'],
            'columns' => ['account_type', 'account_name', 'account_no', 'status', 'updated_at'],
            'actions' => ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
            'legacy_tables' => ['pay_setting', 'code_pay', 'usdt_pay'],
            'legacy_fields' => [
                'account_type',
                'pay_type_id',
                'bank_id',
                'bank_no',
                'bank_owner',
                'bank_address',
                'info',
                'category',
                'mch_id',
                'key',
                'content',
                'remark',
                'download_name',
                'download_url',
                'wallet_address',
                'exchange_rate',
                'min_price',
                'max_price',
                'bonus_ratio',
                'sort_order',
            ],
        ],
        '20500' => [
            'code' => '20500',
            'title' => '代理政策设置',
            'module' => '平台设置',
            'mode' => 'settings',
            'filters' => ['section', 'keyword'],
            'columns' => ['setting_key', 'setting_value', 'updated_by', 'updated_at'],
            'actions' => ['save', 'export'],
            'setting_fields' => [
                'agent_apply_audio',
                'agent_pc_uri',
                'agent_uri_pre',
                'agent_url',
                'agent_wap_uri',
                'agentday',
            ],
        ],
        '21150' => [
            'code' => '21150',
            'title' => '平台佣金设置',
            'module' => '平台设置',
            'mode' => 'legacy',
            'filters' => ['keyword', 'status', 'type'],
            'columns' => ['name', 'type', 'member_fs', 'required_new_members', 'state', 'updated_at'],
            'actions' => ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
            'legacy_table' => 'agent_settlements',
            'legacy_fields' => [
                'name',
                'type',
                'realperson',
                'electron',
                'joker',
                'sport',
                'fish',
                'lottery',
                'e_sport',
                'member_fs',
                'required_new_members',
                'state',
            ],
        ],
        '12650' => [
            'code' => '12650',
            'title' => '帮助中心设置',
            'module' => '平台设置',
            'mode' => 'legacy',
            'filters' => ['keyword', 'category'],
            'columns' => ['name', 'category', 'sort_order', 'updated_at'],
            'actions' => ['create', 'edit', 'delete', 'bulk-delete', 'import', 'export'],
            'legacy_tables' => ['articles', 'articlescate'],
            'legacy_fields' => [
                'name',
                'enname',
                'cateid',
                'content',
                'encontent',
                'stor',
            ],
        ],
        '2981' => [
            'code' => '2981',
            'title' => '简讯发送设置',
            'module' => '平台设置',
            'mode' => 'settings',
            'filters' => ['section', 'keyword'],
            'columns' => ['setting_key', 'setting_value', 'updated_by', 'updated_at'],
            'actions' => ['save', 'export'],
            'setting_fields' => [
                'sms_provider',
                'sms_api_url',
                'sms_sender',
                'sms_daily_limit',
                'sms_enabled',
            ],
        ],
        '800003' => [
            'code' => '800003',
            'title' => '飞行员服务',
            'module' => '平台设置',
            'mode' => 'records',
            'filters' => ['keyword', 'status', 'service_type'],
            'columns' => ['title', 'service_type', 'endpoint', 'status', 'sort_order', 'updated_at'],
            'actions' => ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
            'record_fields' => ['service_type', 'endpoint', 'description'],
        ],
        '31001' => [
            'code' => '31001',
            'title' => '平台资金详情',
            'module' => '平台资金',
            'mode' => 'report',
            'filters' => ['date_from', 'date_to', 'transaction_type', 'status'],
            'columns' => ['business_no', 'transaction_type', 'amount', 'status', 'occurred_at'],
            'actions' => ['view', 'export'],
            'legacy_tables' => ['recharge', 'withdraws', 'transfer_logs'],
        ],
        '20048' => [
            'code' => '20048',
            'title' => '银行对账报表',
            'module' => '平台资金',
            'mode' => 'transactions',
            'filters' => ['date_from', 'date_to', 'status', 'account_no'],
            'columns' => ['business_no', 'account_name', 'account_no', 'amount', 'status', 'occurred_at'],
            'actions' => ['create', 'edit', 'status', 'import', 'export'],
            'transaction_fields' => ['account_name', 'account_no', 'amount', 'remark'],
        ],
        '20032' => [
            'code' => '20032',
            'title' => '银行账号明细',
            'module' => '平台资金',
            'mode' => 'legacy',
            'filters' => ['keyword', 'status', 'bank_name'],
            'columns' => ['bank_name', 'bank_no', 'bank_owner', 'state', 'updated_at'],
            'actions' => ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
            'legacy_tables' => ['banks', 'pay_setting'],
            'legacy_fields' => [
                'bank_id',
                'bank_no',
                'bank_owner',
                'bank_address',
                'info',
                'state',
            ],
        ],
        '90040' => [
            'code' => '90040',
            'title' => '平台费用充值',
            'module' => '平台资金',
            'mode' => 'transactions',
            'filters' => ['date_from', 'date_to', 'status', 'business_no'],
            'columns' => ['business_no', 'account_name', 'amount', 'balance_after', 'status', 'occurred_at'],
            'actions' => ['create', 'edit', 'status', 'import', 'export'],
            'transaction_fields' => [
                'account_name',
                'account_no',
                'amount',
                'balance_before',
                'balance_after',
                'currency',
                'remark',
            ],
        ],
    ];

    public function pages(): array
    {
        return self::PAGES;
    }

    public function page($code): array
    {
        $code = (string) $code;
        if (!isset(self::PAGES[$code])) {
            throw new InvalidArgumentException('平台运营页面不存在');
        }

        return self::PAGES[$code];
    }

    public function filterRecordInput($code, array $input): array
    {
        $page = $this->page($code);
        $allowed = isset($page['record_fields']) ? $page['record_fields'] : [];
        $businessData = [];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $input) || is_array($input[$field])) {
                continue;
            }
            $businessData[$field] = $this->clean($input[$field], 5000);
        }

        return [
            'title' => $this->clean(isset($input['title']) ? $input['title'] : '', 191),
            'status' => $this->clean(isset($input['status']) ? $input['status'] : '0', 30),
            'sort_order' => (int) (isset($input['sort_order']) ? $input['sort_order'] : 0),
            'business_data' => $businessData,
        ];
    }

    public function filterSettings($code, array $input): array
    {
        $page = $this->page($code);
        $allowed = isset($page['setting_fields']) ? $page['setting_fields'] : [];
        $filtered = [];

        foreach ($allowed as $key) {
            if (!array_key_exists($key, $input) || is_array($input[$key])) {
                continue;
            }
            $filtered[$key] = $this->clean($input[$key], 10000);
        }

        return $filtered;
    }

    public function legacyRows($code, array $filters = [], $perPage = 20)
    {
        $code = (string) $code;
        $rows = collect();

        if ($code === '31018') {
            $rows = DB::table('game_lists')
                ->selectRaw(
                    'MIN(id) as id, platform_name, COUNT(*) as game_count, '.
                    'MAX(site_state) as site_state, MAX(app_state) as app_state, '.
                    'MAX(updated_at) as updated_at'
                )
                ->groupBy('platform_name')
                ->get()
                ->map(function ($row) {
                    $row->source_table = 'game_lists';
                    $row->source_id = $row->platform_name;
                    $row->id = $row->platform_name;
                    $row->status = ((int) $row->site_state === 1 || (int) $row->app_state === 1)
                        ? 'enabled'
                        : 'disabled';

                    return $row;
                });
        } elseif ($code === '20068') {
            $rows = DB::table('pay_types')->orderBy('sort_order')->orderBy('id')->get()
                ->map(function ($row) {
                    $row->source_table = 'pay_types';
                    $row->source_id = $row->id;
                    $row->status = (int) $row->state === 1 ? 'enabled' : 'disabled';

                    return $row;
                });
        } elseif ($code === '20028') {
            $rows = $this->paymentAccountRows();
        } elseif ($code === '21150') {
            $rows = DB::table('agent_settlements')->orderBy('id')->get()
                ->map(function ($row) {
                    $row->source_table = 'agent_settlements';
                    $row->source_id = $row->id;
                    $row->status = (int) $row->state === 1 ? 'enabled' : 'disabled';

                    return $row;
                });
        } elseif ($code === '12650') {
            $rows = DB::table('articles as article')
                ->leftJoin('articlescate as category', 'category.id', '=', 'article.cateid')
                ->select([
                    'article.*',
                    'category.name as category',
                ])
                ->orderBy('article.stor')
                ->orderBy('article.id')
                ->get()
                ->map(function ($row) {
                    $row->source_table = 'articles';
                    $row->source_id = $row->id;
                    $row->sort_order = isset($row->stor) ? $row->stor : 0;

                    return $row;
                });
        } elseif ($code === '20032') {
            $rows = DB::table('pay_setting as account')
                ->leftJoin('banks as bank', 'bank.id', '=', 'account.bank_id')
                ->select([
                    'account.*',
                    'bank.bank_name',
                ])
                ->orderBy('account.id')
                ->get()
                ->map(function ($row) {
                    $row->source_table = 'pay_setting';
                    $row->source_id = $row->id;
                    $row->status = (int) $row->state === 1 ? 'enabled' : 'disabled';

                    return $row;
                });
        } else {
            throw new InvalidArgumentException('当前页面没有旧表适配器');
        }

        $rows = $this->filterNormalizedRows($rows, $filters);

        return $this->paginateCollection($rows, $perPage);
    }

    public function reportRows($code, array $filters = [], $perPage = 20)
    {
        if ((string) $code !== '31001') {
            throw new InvalidArgumentException('当前页面没有资金报表适配器');
        }

        $rows = collect();
        if (Schema::hasTable('recharge')) {
            $rows = $rows->merge(DB::table('recharge')->get()->map(function ($row) {
                return (object) [
                    'id' => 'recharge:'.$row->id,
                    'source_table' => 'recharge',
                    'source_id' => $row->id,
                    'business_no' => $row->order_no ?: $row->out_trade_no,
                    'transaction_type' => 'recharge',
                    'amount' => $row->real_money,
                    'status' => (string) $row->state,
                    'occurred_at' => $row->created_at,
                ];
            }));
        }
        if (Schema::hasTable('withdraws')) {
            $rows = $rows->merge(DB::table('withdraws')->get()->map(function ($row) {
                return (object) [
                    'id' => 'withdraws:'.$row->id,
                    'source_table' => 'withdraws',
                    'source_id' => $row->id,
                    'business_no' => $row->order_no,
                    'transaction_type' => 'withdrawal',
                    'amount' => $row->real_money,
                    'status' => (string) $row->state,
                    'occurred_at' => $row->created_at,
                ];
            }));
        }
        if (Schema::hasTable('transfer_logs')) {
            $rows = $rows->merge(DB::table('transfer_logs')->get()->map(function ($row) {
                return (object) [
                    'id' => 'transfer_logs:'.$row->id,
                    'source_table' => 'transfer_logs',
                    'source_id' => $row->id,
                    'business_no' => $row->order_no,
                    'transaction_type' => 'game_transfer',
                    'amount' => $row->real_money,
                    'status' => (string) $row->state,
                    'occurred_at' => $row->created_at,
                    'platform_name' => isset($row->api_type) ? $row->api_type : null,
                ];
            }));
        }

        $rows = $this->filterNormalizedRows($rows, $filters)
            ->sortByDesc(function ($row) {
                return (string) $row->occurred_at;
            })
            ->values();

        return $this->paginateCollection($rows, $perPage);
    }

    public function saveLegacyRecord($code, array $input, $id = null)
    {
        $code = (string) $code;
        if ($code === '20068') {
            return $this->saveLegacyTableRow(
                'pay_types',
                $id,
                [
                    'name' => $this->required($input, 'name'),
                    'category' => $this->nullableValue($input, 'category'),
                    'bonus_ratio' => $this->nullableValue($input, 'bonus_ratio'),
                    'state' => $this->statusValue(isset($input['status']) ? $input['status'] : (isset($input['state']) ? $input['state'] : 0)),
                    'sort_order' => (int) (isset($input['sort_order']) ? $input['sort_order'] : 0),
                    'merchant_no' => $this->nullableValue($input, 'merchant_no'),
                    'merchant_key' => $this->nullableValue($input, 'merchant_key'),
                    'merchant_url' => $this->nullableValue($input, 'merchant_url'),
                    'merchant_identifier' => $this->nullableValue($input, 'merchant_identifier'),
                    'merchant_code' => $this->nullableValue($input, 'merchant_code'),
                ]
            );
        }
        if ($code === '20028') {
            $decoded = $this->decodeLegacyId($id);
            $source = $decoded['table'] ?: $this->paymentAccountTable(
                isset($input['account_type']) ? $input['account_type'] : ''
            );
            $sourceId = $decoded['id'];
            if ($source === 'pay_setting') {
                return $this->saveLegacyTableRow($source, $sourceId, [
                    'bank_id' => (int) $this->required($input, 'bank_id'),
                    'bank_no' => $this->required($input, 'bank_no'),
                    'bank_owner' => $this->required($input, 'bank_owner'),
                    'bank_address' => $this->nullableValue($input, 'bank_address', ''),
                    'info' => $this->nullableValue($input, 'info'),
                    'state' => $this->statusValue(isset($input['status']) ? $input['status'] : 0),
                ]);
            }
            if ($source === 'code_pay') {
                return $this->saveLegacyTableRow($source, $sourceId, [
                    'pay_type_id' => $this->nullableValue($input, 'pay_type_id'),
                    'category' => $this->nullableValue($input, 'category'),
                    'mch_id' => $this->nullableValue($input, 'mch_id'),
                    'key' => $this->nullableValue($input, 'key'),
                    'content' => $this->nullableValue($input, 'content'),
                    'status' => $this->statusValue(isset($input['status']) ? $input['status'] : 0),
                    'remark' => $this->nullableValue($input, 'remark'),
                    'download_name' => $this->nullableValue($input, 'download_name', ''),
                    'download_url' => $this->nullableValue($input, 'download_url', ''),
                    'min_price' => $this->nullableValue($input, 'min_price', 0),
                    'max_price' => $this->nullableValue($input, 'max_price', 0),
                ]);
            }
            if ($source === 'usdt_pay') {
                return $this->saveLegacyTableRow($source, $sourceId, [
                    'category' => $this->nullableValue($input, 'category'),
                    'wallet_address' => $this->required($input, 'wallet_address'),
                    'exchange_rate' => $this->nullableValue($input, 'exchange_rate', 1),
                    'min_price' => $this->nullableValue($input, 'min_price', 1),
                    'max_price' => $this->nullableValue($input, 'max_price', 10000),
                    'bonus_ratio' => $this->nullableValue($input, 'bonus_ratio', 0),
                    'status' => $this->statusValue(isset($input['status']) ? $input['status'] : 0),
                    'sort_order' => (int) (isset($input['sort_order']) ? $input['sort_order'] : 0),
                ]);
            }
        }
        if ($code === '21150') {
            return $this->saveLegacyTableRow('agent_settlements', $id, [
                'name' => $this->required($input, 'name'),
                'type' => (int) (isset($input['type']) ? $input['type'] : 0),
                'realperson' => $this->nullableValue($input, 'realperson', 0),
                'electron' => $this->nullableValue($input, 'electron', 0),
                'joker' => $this->nullableValue($input, 'joker', 0),
                'sport' => $this->nullableValue($input, 'sport', 0),
                'fish' => $this->nullableValue($input, 'fish', 0),
                'lottery' => $this->nullableValue($input, 'lottery', 0),
                'e_sport' => $this->nullableValue($input, 'e_sport', 0),
                'member_fs' => $this->nullableValue($input, 'member_fs', 0),
                'required_new_members' => $this->nullableValue($input, 'required_new_members', 0),
                'state' => $this->statusValue(isset($input['status']) ? $input['status'] : (isset($input['state']) ? $input['state'] : 0)),
            ]);
        }
        if ($code === '12650') {
            return $this->saveLegacyTableRow('articles', $id, [
                'name' => $this->required($input, 'name'),
                'enname' => $this->nullableValue($input, 'enname'),
                'cateid' => $this->nullableValue($input, 'cateid'),
                'content' => $this->nullableValue($input, 'content'),
                'encontent' => $this->nullableValue($input, 'encontent'),
                'stor' => (int) (isset($input['stor']) ? $input['stor'] : 0),
            ]);
        }
        if ($code === '20032') {
            return $this->saveLegacyTableRow('pay_setting', $id, [
                'bank_id' => (int) $this->required($input, 'bank_id'),
                'bank_no' => $this->required($input, 'bank_no'),
                'bank_owner' => $this->required($input, 'bank_owner'),
                'bank_address' => $this->nullableValue($input, 'bank_address', ''),
                'info' => $this->nullableValue($input, 'info'),
                'state' => $this->statusValue(isset($input['status']) ? $input['status'] : (isset($input['state']) ? $input['state'] : 0)),
            ]);
        }
        if ($code === '31018') {
            $platform = $id ?: $this->required($input, 'platform_name');
            $updated = DB::table('game_lists')->where('platform_name', $platform)->update([
                'site_state' => $this->statusValue(isset($input['site_state']) ? $input['site_state'] : 0),
                'app_state' => $this->statusValue(isset($input['app_state']) ? $input['app_state'] : 0),
                'updated_at' => now(),
            ]);

            return ['id' => $platform, 'source_id' => $platform, 'source_table' => 'game_lists', 'updated' => $updated];
        }

        throw new InvalidArgumentException('当前页面不支持旧表保存');
    }

    public function changeLegacyStatus($code, array $ids, $status)
    {
        $code = (string) $code;
        $enabled = $this->statusValue($status);
        if ($code === '31018') {
            return DB::table('game_lists')->whereIn('platform_name', $ids)->update([
                'site_state' => $enabled,
                'app_state' => $enabled,
                'updated_at' => now(),
            ]);
        }

        $map = [
            '20068' => ['pay_types', 'state'],
            '21150' => ['agent_settlements', 'state'],
            '20032' => ['pay_setting', 'state'],
        ];
        if (isset($map[$code])) {
            return DB::table($map[$code][0])->whereIn('id', array_map('intval', $ids))->update([
                $map[$code][1] => $enabled,
                'updated_at' => now(),
            ]);
        }
        if ($code === '20028') {
            $updated = 0;
            $grouped = [];
            foreach ($ids as $id) {
                $decoded = $this->decodeLegacyId($id);
                if ($decoded['table'] && $decoded['id']) {
                    $grouped[$decoded['table']][] = $decoded['id'];
                }
            }
            foreach ($grouped as $table => $sourceIds) {
                $column = $table === 'pay_setting' ? 'state' : 'status';
                $updated += DB::table($table)->whereIn('id', $sourceIds)->update([
                    $column => $enabled,
                    'updated_at' => now(),
                ]);
            }

            return $updated;
        }

        throw new InvalidArgumentException('当前页面不支持旧表状态修改');
    }

    public function deleteLegacyRecord($code, $id)
    {
        $code = (string) $code;
        if ($code === '20068') {
            if (Schema::hasColumn('code_pay', 'pay_type_id')
                && DB::table('code_pay')->where('pay_type_id', $id)->exists()) {
                throw new InvalidArgumentException('支付类型已被支付账号使用，不能删除');
            }

            return DB::table('pay_types')->where('id', $id)->delete();
        }
        if ($code === '20028') {
            $decoded = $this->decodeLegacyId($id);
            if (!$decoded['table'] || !$decoded['id']) {
                throw new InvalidArgumentException('支付账号标识无效');
            }

            return DB::table($decoded['table'])->where('id', $decoded['id'])->delete();
        }
        if ($code === '21150') {
            if (Schema::hasTable('users')
                && Schema::hasColumn('users', 'settlement_id')
                && DB::table('users')->where('settlement_id', $id)->exists()) {
                throw new InvalidArgumentException('佣金政策已被代理使用，不能删除');
            }

            return DB::table('agent_settlements')->where('id', $id)->delete();
        }
        if ($code === '12650') {
            return DB::table('articles')->where('id', $id)->delete();
        }
        if ($code === '20032') {
            return DB::table('pay_setting')->where('id', $id)->delete();
        }

        throw new InvalidArgumentException('当前页面不支持旧表删除');
    }

    private function paymentAccountRows()
    {
        $rows = collect();
        if (Schema::hasTable('pay_setting')) {
            $rows = $rows->merge(
                DB::table('pay_setting as account')
                    ->leftJoin('banks as bank', 'bank.id', '=', 'account.bank_id')
                    ->select(['account.*', 'bank.bank_name'])
                    ->get()
                    ->map(function ($row) {
                        return (object) array_merge((array) $row, [
                            'id' => 'pay_setting:'.$row->id,
                            'source_table' => 'pay_setting',
                            'source_id' => $row->id,
                            'account_type' => 'bank',
                            'account_name' => $row->bank_owner,
                            'account_no' => $row->bank_no,
                            'status' => (int) $row->state === 1 ? 'enabled' : 'disabled',
                        ]);
                    })
            );
        }
        if (Schema::hasTable('code_pay')) {
            $rows = $rows->merge(DB::table('code_pay')->get()->map(function ($row) {
                return (object) array_merge((array) $row, [
                    'id' => 'code_pay:'.$row->id,
                    'source_table' => 'code_pay',
                    'source_id' => $row->id,
                    'account_type' => 'code',
                    'account_name' => $row->category,
                    'account_no' => $row->mch_id,
                    'status' => (int) $row->status === 1 ? 'enabled' : 'disabled',
                ]);
            }));
        }
        if (Schema::hasTable('usdt_pay')) {
            $rows = $rows->merge(DB::table('usdt_pay')->get()->map(function ($row) {
                return (object) array_merge((array) $row, [
                    'id' => 'usdt_pay:'.$row->id,
                    'source_table' => 'usdt_pay',
                    'source_id' => $row->id,
                    'account_type' => 'usdt',
                    'account_name' => $row->category,
                    'account_no' => $row->wallet_address,
                    'status' => (int) $row->status === 1 ? 'enabled' : 'disabled',
                ]);
            }));
        }

        return $rows;
    }

    private function saveLegacyTableRow($table, $id, array $values)
    {
        $values = $this->existingColumns($table, $values);
        $now = now();
        if (Schema::hasColumn($table, 'updated_at')) {
            $values['updated_at'] = $now;
        }

        if ($id) {
            if (!DB::table($table)->where('id', $id)->exists()) {
                throw new InvalidArgumentException('旧业务记录不存在');
            }
            DB::table($table)->where('id', $id)->update($values);
            $sourceId = (int) $id;
        } else {
            if (Schema::hasColumn($table, 'created_at')) {
                $values['created_at'] = $now;
            }
            $sourceId = DB::table($table)->insertGetId($values);
        }

        return [
            'id' => $sourceId,
            'source_id' => $sourceId,
            'source_table' => $table,
        ];
    }

    private function existingColumns($table, array $values)
    {
        return array_filter($values, function ($key) use ($table) {
            return Schema::hasColumn($table, $key);
        }, ARRAY_FILTER_USE_KEY);
    }

    private function filterNormalizedRows(Collection $rows, array $filters)
    {
        if (!empty($filters['keyword'])) {
            $keyword = mb_strtolower((string) $filters['keyword']);
            $rows = $rows->filter(function ($row) use ($keyword) {
                return mb_strpos(
                    mb_strtolower(json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)),
                    $keyword
                ) !== false;
            });
        }
        if (!empty($filters['status'])) {
            $status = (string) $filters['status'];
            $rows = $rows->filter(function ($row) use ($status) {
                return isset($row->status) && (string) $row->status === $status;
            });
        }
        foreach ([
            'category',
            'account_type',
            'platform_name',
            'bank_name',
            'type',
            'account_no',
            'business_no',
            'line_type',
            'service_type',
        ] as $field) {
            if (!isset($filters[$field]) || trim((string) $filters[$field]) === '') {
                continue;
            }
            $expected = mb_strtolower(trim((string) $filters[$field]));
            $rows = $rows->filter(function ($row) use ($field, $expected) {
                return isset($row->{$field})
                    && mb_strtolower(trim((string) $row->{$field})) === $expected;
            });
        }
        if (!empty($filters['transaction_type'])) {
            $type = (string) $filters['transaction_type'];
            $rows = $rows->filter(function ($row) use ($type) {
                return isset($row->transaction_type) && $row->transaction_type === $type;
            });
        }
        if (!empty($filters['date_from'])) {
            $from = (string) $filters['date_from'];
            $rows = $rows->filter(function ($row) use ($from) {
                return !empty($row->occurred_at) && substr((string) $row->occurred_at, 0, 10) >= $from;
            });
        }
        if (!empty($filters['date_to'])) {
            $to = (string) $filters['date_to'];
            $rows = $rows->filter(function ($row) use ($to) {
                return !empty($row->occurred_at) && substr((string) $row->occurred_at, 0, 10) <= $to;
            });
        }

        return $rows->values();
    }

    private function paginateCollection(Collection $rows, $perPage)
    {
        $page = max(1, (int) LengthAwarePaginator::resolveCurrentPage());
        $perPage = max(1, min(200, (int) $perPage));

        return new LengthAwarePaginator(
            $rows->forPage($page, $perPage)->values()->all(),
            $rows->count(),
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => function_exists('request') ? request()->query() : [],
            ]
        );
    }

    private function decodeLegacyId($id)
    {
        if (is_string($id) && strpos($id, ':') !== false) {
            list($table, $sourceId) = explode(':', $id, 2);
            if (in_array($table, ['pay_setting', 'code_pay', 'usdt_pay'], true)) {
                return ['table' => $table, 'id' => (int) $sourceId];
            }
        }

        return ['table' => null, 'id' => $id ? (int) $id : null];
    }

    private function paymentAccountTable($type)
    {
        $map = [
            'bank' => 'pay_setting',
            'code' => 'code_pay',
            'usdt' => 'usdt_pay',
        ];
        if (!isset($map[$type])) {
            throw new InvalidArgumentException('支付账号类型无效');
        }

        return $map[$type];
    }

    private function required(array $input, $key)
    {
        $value = isset($input[$key]) ? trim((string) $input[$key]) : '';
        if ($value === '') {
            throw new InvalidArgumentException($key.' 不能为空');
        }

        return mb_substr(strip_tags($value), 0, 10000);
    }

    private function nullableValue(array $input, $key, $default = null)
    {
        if (!array_key_exists($key, $input) || $input[$key] === '') {
            return $default;
        }

        return is_string($input[$key])
            ? mb_substr(trim(strip_tags($input[$key])), 0, 10000)
            : $input[$key];
    }

    private function statusValue($status)
    {
        return in_array($status, [1, '1', true, 'true', 'on', 'yes', 'enabled', 'completed'], true)
            ? 1
            : 0;
    }

    private function clean($value, $length)
    {
        return mb_substr(trim(strip_tags((string) $value)), 0, $length);
    }
}
