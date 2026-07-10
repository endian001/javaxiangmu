<?php

namespace App\Admin\Services;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class GameManagementService
{
    public function pages()
    {
        return [
            '31202' => $this->pageDefinition(
                '31202',
                '中奖排行管理',
                'game_winner_rankings',
                [
                    'username' => '用户名',
                    'game_type' => '游戏类型',
                    'data_type' => '数据类型',
                    'status' => '状态',
                ],
                [
                    'username' => '用户名',
                    'game_name' => '游戏名称',
                    'amount' => '金额',
                    'player_level' => '玩家等级',
                    'game_type' => '游戏类型',
                    'bet_at' => '投注时间',
                    'ended_at' => '结束时间',
                    'data_type' => '数据类型',
                    'status' => '状态',
                ],
                [
                    'username' => $this->field('用户名', 'text', true),
                    'game_name' => $this->field('游戏名称', 'text', true),
                    'amount' => $this->field('金额', 'decimal', true),
                    'player_level' => $this->field('玩家等级', 'integer'),
                    'game_type' => $this->field('游戏类型', 'select', true, $this->gameTypeOptions()),
                    'bet_at' => $this->field('投注时间', 'datetime', true),
                    'ended_at' => $this->field('结束时间', 'datetime'),
                    'data_type' => $this->field('数据类型', 'select', true, [
                        'manual' => '手动',
                        'system' => '系统',
                    ]),
                    'status' => $this->statusField(),
                    'sort_order' => $this->field('排序', 'integer'),
                ],
                ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
                ['username', 'game_name'],
                'status',
                'bet_at'
            ),
            '31000' => $this->pageDefinition(
                '31000',
                '三方游戏列表',
                'game_lists',
                [
                    'platform_name' => '厂商',
                    'category_id' => '游戏类型',
                    'game_code' => '游戏代码',
                    'name' => '游戏名称',
                    'site_state' => '游戏状态',
                ],
                [
                    'name' => '游戏名称',
                    'game_code' => '游戏代码',
                    'platform_name' => '游戏厂商',
                    'category_id' => '游戏类型',
                    'site_state' => '站点状态',
                    'app_state' => 'APP 状态',
                    'is_hot' => '热门',
                    'is_new' => '新上架',
                    'is_recommend' => '推荐',
                    'order_by' => '排序',
                    'updated_at' => '更新时间',
                ],
                [
                    'platform_name' => $this->field('游戏厂商', 'select', true, [], 'platforms'),
                    'name' => $this->field('游戏名称', 'text', true),
                    'game_code' => $this->field('游戏代码', 'text', true),
                    'category_id' => $this->field('游戏类型', 'select', true, $this->gameTypeOptions()),
                    'site_state' => $this->booleanField('站点状态'),
                    'app_state' => $this->booleanField('APP 状态'),
                    'is_hot' => $this->booleanField('热门'),
                    'is_new' => $this->booleanField('新上架'),
                    'is_recommend' => $this->booleanField('推荐'),
                    'is_top' => $this->booleanField('前台公开'),
                    'order_by' => $this->field('排序', 'integer'),
                ],
                ['edit', 'status', 'import', 'export'],
                ['name', 'game_code'],
                'site_state'
            ),
            '70037' => $this->pageDefinition(
                '70037',
                '平台热门游戏',
                'game_lists',
                [
                    'platform_name' => '厂商',
                    'category_id' => '游戏类型',
                    'name' => '游戏名称',
                    'site_state' => '游戏状态',
                ],
                [
                    'name' => '游戏名称',
                    'game_code' => '游戏代码',
                    'platform_name' => '游戏厂商',
                    'category_id' => '游戏类型',
                    'is_hot' => '热门',
                    'is_new' => '新上架',
                    'is_recommend' => '推荐',
                    'order_by' => '排序',
                    'site_state' => '游戏状态',
                ],
                [
                    'is_hot' => $this->booleanField('热门'),
                    'is_new' => $this->booleanField('新上架'),
                    'is_recommend' => $this->booleanField('推荐'),
                    'order_by' => $this->field('排序', 'integer'),
                    'site_state' => $this->booleanField('站点状态'),
                    'app_state' => $this->booleanField('APP 状态'),
                ],
                ['edit', 'status', 'import', 'export'],
                ['name', 'game_code'],
                'is_hot'
            ),
            '20401' => $this->pageDefinition(
                '20401',
                '彩票分公司',
                'lottery_branches',
                [
                    'keyword' => '标签标题',
                    'branch_code' => '分公司代码',
                    'status' => '状态',
                ],
                [
                    'title' => '标签标题',
                    'branch_code' => '分公司代码',
                    'status' => '状态',
                    'sort_order' => '排序',
                    'created_at' => '创建时间',
                ],
                [
                    'title' => $this->field('标签标题', 'text', true),
                    'branch_code' => $this->field('分公司代码', 'text', true),
                    'status' => $this->statusField(),
                    'sort_order' => $this->field('排序', 'integer'),
                ],
                ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
                ['title', 'branch_code'],
                'status'
            ),
            '5000' => $this->pageDefinition(
                '5000',
                '彩票开奖记录',
                'lottery_draw_records',
                [
                    'branch_id' => '彩票分公司',
                    'lottery_code' => '彩种代码',
                    'issue_no' => '期号',
                    'status' => '状态',
                    'date_from' => '开奖开始时间',
                    'date_to' => '开奖结束时间',
                ],
                [
                    'lottery_name' => '彩种名称',
                    'lottery_code' => '彩种代码',
                    'issue_no' => '期号',
                    'draw_numbers' => '开奖号码',
                    'draw_at' => '开奖时间',
                    'source' => '来源',
                    'status' => '状态',
                ],
                [
                    'branch_id' => $this->field('彩票分公司', 'select', false, [], 'branches'),
                    'lottery_code' => $this->field('彩种代码', 'text', true),
                    'lottery_name' => $this->field('彩种名称', 'text', true),
                    'issue_no' => $this->field('期号', 'text', true),
                    'draw_numbers' => $this->field('开奖号码', 'text', true),
                    'draw_at' => $this->field('开奖时间', 'datetime', true),
                    'source' => $this->field('来源', 'select', true, [
                        'manual' => '手动',
                        'system' => '系统',
                        'provider' => '供应商',
                    ]),
                    'status' => $this->field('状态', 'select', true, [
                        'pending' => '待开奖',
                        'published' => '已发布',
                        'cancelled' => '已取消',
                    ]),
                ],
                ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
                ['lottery_name', 'lottery_code', 'issue_no'],
                'status',
                'draw_at'
            ),
            '5500' => $this->pageDefinition(
                '5500',
                '彩票基本设置',
                'lottery_group_settings',
                [
                    'keyword' => '彩种组',
                    'status' => '状态',
                ],
                [
                    'group_name' => '彩种组名称',
                    'max_bet_per_order' => '单笔投注金额上限',
                    'max_bet_per_issue' => '单期投注金额上限',
                    'max_win_per_order' => '单笔最大中奖金额',
                    'max_win_per_player_issue' => '每人每期最大中奖金额',
                    'max_multiple' => '投注最大倍数上限',
                    'unit_price' => '每注单价',
                    'slider_interval' => '投注拉杆间隔',
                    'commission_rate' => '平台佣金比例%',
                    'status' => '状态',
                ],
                [
                    'group_code' => $this->field('彩种组代码', 'text', true),
                    'group_name' => $this->field('彩种组名称', 'text', true),
                    'max_bet_per_order' => $this->field('单笔投注金额上限', 'decimal'),
                    'max_bet_per_issue' => $this->field('单期投注金额上限', 'decimal'),
                    'max_win_per_order' => $this->field('单笔最大中奖金额', 'decimal'),
                    'max_win_per_player_issue' => $this->field('每人每期最大中奖金额', 'decimal'),
                    'max_multiple' => $this->field('投注最大倍数上限', 'integer'),
                    'unit_price' => $this->field('每注单价', 'decimal'),
                    'slider_interval' => $this->field('投注拉杆间隔', 'integer'),
                    'commission_rate' => $this->field('平台佣金比例%', 'decimal'),
                    'chip_settings' => $this->field('预设筹码 / 快选金额', 'textarea'),
                    'status' => $this->statusField(),
                ],
                ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
                ['group_name', 'group_code'],
                'status'
            ),
            '5754' => $this->pageDefinition(
                '5754',
                '彩种基本参数',
                'lottery_types',
                [
                    'branch_id' => '彩票分公司',
                    'group_code' => '所属组别',
                    'lottery_name' => '彩种名称',
                    'status' => '游戏开关',
                    'attribute' => '彩种属性',
                ],
                [
                    'lottery_name' => '彩种名称',
                    'lottery_code' => '彩种代码',
                    'group_code' => '所属组别',
                    'attribute' => '属性',
                    'status' => '游戏开关',
                    'max_win_per_order' => '单笔最大中奖金额',
                    'max_win_per_player_issue' => '每人每期最高中奖',
                    'max_bet_per_order' => '单笔投注金额上限',
                    'max_bet_per_issue' => '单期投入金额上限',
                    'lock_seconds' => '锁定封盘时间',
                    'sort_order' => '排序',
                ],
                [
                    'branch_id' => $this->field('彩票分公司', 'select', false, [], 'branches'),
                    'group_code' => $this->field('所属组别', 'text', true),
                    'lottery_code' => $this->field('彩种代码', 'text', true),
                    'lottery_name' => $this->field('彩种名称', 'text', true),
                    'attribute' => $this->field('彩种属性', 'text'),
                    'icon' => $this->field('彩种图标', 'text'),
                    'max_win_per_order' => $this->field('单笔最大中奖金额', 'decimal'),
                    'max_win_per_player_issue' => $this->field('每人每期最高中奖', 'decimal'),
                    'max_bet_per_order' => $this->field('单笔投注金额上限', 'decimal'),
                    'max_bet_per_issue' => $this->field('单期投入金额上限', 'decimal'),
                    'lock_seconds' => $this->field('锁定封盘时间（秒）', 'integer'),
                    'is_hot' => $this->booleanField('热门'),
                    'is_new' => $this->booleanField('新上架'),
                    'sort_order' => $this->field('排序', 'integer'),
                    'status' => $this->statusField(),
                ],
                ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
                ['lottery_name', 'lottery_code', 'group_code'],
                'status'
            ),
            '6400' => $this->pageDefinition(
                '6400',
                '彩票玩法参数',
                'lottery_play_settings',
                [
                    'lottery_type_id' => '选择彩种',
                    'play_code' => '玩法代码',
                    'play_name' => '玩法名称',
                    'status' => '状态',
                ],
                [
                    'play_name' => '玩法名称',
                    'play_code' => '玩法代码',
                    'odds' => '赔率',
                    'min_bet' => '最低投注',
                    'max_bet' => '最高投注',
                    'max_win' => '最高中奖',
                    'sort_order' => '排序',
                    'status' => '状态',
                ],
                [
                    'lottery_type_id' => $this->field('选择彩种', 'select', true, [], 'lottery_types'),
                    'play_code' => $this->field('玩法代码', 'text', true),
                    'play_name' => $this->field('玩法名称', 'text', true),
                    'odds' => $this->field('赔率', 'decimal', true),
                    'min_bet' => $this->field('最低投注', 'decimal'),
                    'max_bet' => $this->field('最高投注', 'decimal'),
                    'max_win' => $this->field('最高中奖', 'decimal'),
                    'sort_order' => $this->field('排序', 'integer'),
                    'status' => $this->statusField(),
                ],
                ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
                ['play_name', 'play_code'],
                'status'
            ),
            '5749' => $this->pageDefinition(
                '5749',
                '玩法销售监控',
                'lottery_sales_controls',
                [
                    'lottery_type_id' => '选择彩种',
                    'play_code' => '玩法代码',
                    'mode' => '模式',
                    'status' => '状态',
                ],
                [
                    'lottery_type_id' => '彩种',
                    'play_code' => '玩法代码',
                    'stock_amount' => '库存',
                    'payout_adjustment' => '调赔幅度',
                    'bet_level_sort' => '下注等级排序',
                    'mode' => '模式',
                    'status' => '状态',
                    'updated_at' => '更新时间',
                ],
                [
                    'lottery_type_id' => $this->field('选择彩种', 'select', true, [], 'lottery_types'),
                    'play_code' => $this->field('玩法代码', 'text'),
                    'stock_amount' => $this->field('库存', 'decimal'),
                    'payout_adjustment' => $this->field('调赔幅度', 'decimal'),
                    'bet_level_sort' => $this->booleanField('下注等级排序'),
                    'mode' => $this->field('模式', 'select', true, [
                        'casino' => '娱乐城模式',
                        'standard' => '标准模式',
                    ]),
                    'status' => $this->statusField(),
                ],
                ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
                ['play_code'],
                'status'
            ),
            '5700' => $this->pageDefinition(
                '5700',
                '彩票投注干扰',
                'lottery_bet_interferences',
                [
                    'username' => '用户名',
                    'lottery_type_id' => '彩种',
                    'interference_type' => '干扰类型',
                    'status' => '状态',
                    'date_from' => '开始时间',
                    'date_to' => '结束时间',
                ],
                [
                    'username' => '用户名',
                    'lottery_type_id' => '彩种',
                    'play_code' => '玩法代码',
                    'interference_type' => '干扰类型',
                    'interference_value' => '干扰值',
                    'starts_at' => '开始时间',
                    'ends_at' => '结束时间',
                    'reason' => '原因',
                    'status' => '状态',
                ],
                [
                    'username' => $this->field('用户名', 'text', true),
                    'lottery_type_id' => $this->field('彩种', 'select', false, [], 'lottery_types'),
                    'play_code' => $this->field('玩法代码', 'text'),
                    'interference_type' => $this->field('干扰类型', 'select', true, [
                        'odds' => '赔率调整',
                        'limit' => '投注限额',
                        'block' => '禁止投注',
                    ]),
                    'interference_value' => $this->field('干扰值', 'decimal'),
                    'starts_at' => $this->field('开始时间', 'datetime', true),
                    'ends_at' => $this->field('结束时间', 'datetime'),
                    'reason' => $this->field('原因', 'textarea', true),
                    'status' => $this->statusField(),
                ],
                ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
                ['username', 'play_code', 'reason'],
                'status',
                'starts_at'
            ),
            '5600' => $this->pageDefinition(
                '5600',
                '彩种热门排序',
                'lottery_types',
                [
                    'branch_id' => '彩票分公司',
                    'group_code' => '所属组别',
                    'lottery_name' => '彩种名称',
                    'status' => '状态',
                ],
                [
                    'lottery_name' => '彩种名称',
                    'lottery_code' => '彩种代码',
                    'group_code' => '所属组别',
                    'is_hot' => '热门',
                    'is_new' => '新上架',
                    'sort_order' => '排序',
                    'status' => '状态',
                ],
                [
                    'is_hot' => $this->booleanField('热门'),
                    'is_new' => $this->booleanField('新上架'),
                    'sort_order' => $this->field('排序', 'integer'),
                    'status' => $this->statusField(),
                ],
                ['edit', 'status', 'import', 'export'],
                ['lottery_name', 'lottery_code', 'group_code'],
                'status'
            ),
            '260025' => $this->pageDefinition(
                '260025',
                '免费转次数',
                'free_spin_records',
                [
                    'stat_month' => '统计月份',
                    'vendor_code' => '厂商',
                    'plan_id' => '方案ID',
                    'status' => '状态',
                ],
                [
                    'stat_month' => '统计月份',
                    'vendor_code' => '厂商',
                    'plan_id' => '方案ID',
                    'available_spins' => '可使用免费转数',
                    'used_total_spins' => '已使用总转数',
                    'used_free_spins' => '已使用免费转数',
                    'used_paid_spins' => '已使用付费转数',
                    'win_amount' => '中奖',
                    'status' => '状态',
                ],
                [
                    'stat_month' => $this->field('统计月份', 'month', true),
                    'vendor_code' => $this->field('厂商', 'select', true, [], 'platforms'),
                    'plan_id' => $this->field('方案ID', 'text', true),
                    'available_spins' => $this->field('可使用免费转数', 'integer'),
                    'used_total_spins' => $this->field('已使用总转数', 'integer'),
                    'used_free_spins' => $this->field('已使用免费转数', 'integer'),
                    'used_paid_spins' => $this->field('已使用付费转数', 'integer'),
                    'win_amount' => $this->field('中奖', 'decimal'),
                    'status' => $this->statusField(),
                ],
                ['create', 'edit', 'delete', 'status', 'bulk-delete', 'import', 'export'],
                ['vendor_code', 'plan_id'],
                'status'
            ),
        ];
    }

    public function page($code)
    {
        $pages = $this->pages();
        $code = (string) $code;
        if (!isset($pages[$code])) {
            throw new InvalidArgumentException('未知游戏管理页面：'.$code);
        }

        return $pages[$code];
    }

    public function rows($code, array $filters = [], $perPage = 20)
    {
        $page = $this->page($code);
        $table = $page['storage'];
        if (!Schema::hasTable($table)) {
            return new LengthAwarePaginator([], 0, $perPage, 1);
        }

        $perPage = max(5, min(100, (int) $perPage ?: 20));

        return $this->buildQuery($code, $filters)
            ->paginate($perPage)
            ->appends($filters);
    }

    public function filterInput($code, array $input, $requireRequired = true)
    {
        $page = $this->page($code);
        $filtered = [];

        foreach ($page['fields'] as $name => $field) {
            if (!array_key_exists($name, $input)) {
                if ($requireRequired && !empty($field['required'])) {
                    throw new InvalidArgumentException($field['label'].'不能为空');
                }
                continue;
            }

            $value = $input[$name];
            if (is_string($value)) {
                $value = trim($value);
            }
            if ($requireRequired && !empty($field['required']) && $value === '') {
                throw new InvalidArgumentException($field['label'].'不能为空');
            }

            $type = $field['type'];
            if ($value === '' && !in_array($type, ['text', 'textarea'], true)) {
                $filtered[$name] = null;
                continue;
            }
            if ($type === 'integer') {
                $filtered[$name] = (int) $value;
            } elseif ($type === 'decimal') {
                $filtered[$name] = (float) $value;
            } elseif ($type === 'boolean') {
                $filtered[$name] = $this->toBoolean($value);
            } elseif ($type === 'month') {
                if ($value !== '' && !preg_match('/^\d{4}-\d{2}$/', (string) $value)) {
                    throw new InvalidArgumentException($field['label'].'格式必须为 YYYY-MM');
                }
                $filtered[$name] = (string) $value;
            } elseif ($type === 'datetime') {
                $filtered[$name] = $value === '' ? null : (string) $value;
            } else {
                $filtered[$name] = $this->cleanText($value, $type === 'textarea' ? 5000 : 500);
            }
        }

        if ($code === '5700' && isset($filtered['username'])) {
            if (!Schema::hasTable('users')) {
                throw new InvalidArgumentException('用户表不存在');
            }
            $userId = DB::table('users')
                ->where('username', $filtered['username'])
                ->value('id');
            if (!$userId) {
                throw new InvalidArgumentException('用户不存在：'.$filtered['username']);
            }
            $filtered['user_id'] = $userId;
        }

        return $filtered;
    }

    public function saveRecord($code, array $input, $id = null)
    {
        $page = $this->page($code);
        $table = $page['storage'];
        if (!Schema::hasTable($table)) {
            throw new InvalidArgumentException('业务表尚未建立：'.$table);
        }
        if ($table === 'game_lists' && !$id) {
            throw new InvalidArgumentException('三方游戏只能编辑现有游戏记录');
        }

        $data = $this->filterInput($code, $input, $id === null);
        if (!$data) {
            throw new InvalidArgumentException('没有可保存的字段');
        }

        $adminId = $this->currentAdminId();
        $now = date('Y-m-d H:i:s');
        $before = null;

        if ($id) {
            $before = DB::table($table)->where('id', $id)->first();
            if (!$before) {
                throw new InvalidArgumentException('记录不存在');
            }
            if (Schema::hasColumn($table, 'updated_by')) {
                $data['updated_by'] = $adminId;
            }
            if (Schema::hasColumn($table, 'updated_at')) {
                $data['updated_at'] = $now;
            }
            DB::table($table)->where('id', $id)->update($data);
        } else {
            if (Schema::hasColumn($table, 'created_by')) {
                $data['created_by'] = $adminId;
            }
            if (Schema::hasColumn($table, 'updated_by')) {
                $data['updated_by'] = $adminId;
            }
            if (Schema::hasColumn($table, 'created_at')) {
                $data['created_at'] = $now;
            }
            if (Schema::hasColumn($table, 'updated_at')) {
                $data['updated_at'] = $now;
            }
            $id = DB::table($table)->insertGetId($data);
        }

        return [
            'id' => (int) $id,
            'source_table' => $table,
            'before' => $before ? (array) $before : [],
            'after' => (array) DB::table($table)->where('id', $id)->first(),
        ];
    }

    public function changeStatus($code, array $ids, $status)
    {
        $page = $this->page($code);
        if (!in_array('status', $page['actions'], true)) {
            throw new InvalidArgumentException('当前页面不支持状态操作');
        }

        $ids = $this->cleanIds($ids);
        if (!$ids) {
            throw new InvalidArgumentException('请选择记录');
        }

        $field = $page['status_field'];
        $fieldDefinition = isset($page['fields'][$field])
            ? $page['fields'][$field]
            : [];
        $value = isset($fieldDefinition['type']) && $fieldDefinition['type'] === 'boolean'
            ? $this->toBoolean($status)
            : $this->normalizeStatus($status);
        $update = [$field => $value];
        if (Schema::hasColumn($page['storage'], 'updated_at')) {
            $update['updated_at'] = date('Y-m-d H:i:s');
        }
        if (Schema::hasColumn($page['storage'], 'updated_by')) {
            $update['updated_by'] = $this->currentAdminId();
        }

        return DB::table($page['storage'])->whereIn('id', $ids)->update($update);
    }

    public function deleteRecord($code, $id)
    {
        $page = $this->page($code);
        if (!in_array('delete', $page['actions'], true)) {
            throw new InvalidArgumentException('当前页面不支持删除');
        }

        return DB::table($page['storage'])->where('id', (int) $id)->delete();
    }

    public function bulkDelete($code, array $ids)
    {
        $page = $this->page($code);
        if (!in_array('bulk-delete', $page['actions'], true)) {
            throw new InvalidArgumentException('当前页面不支持批量删除');
        }
        $ids = $this->cleanIds($ids);
        if (!$ids) {
            throw new InvalidArgumentException('请选择记录');
        }

        return DB::table($page['storage'])->whereIn('id', $ids)->delete();
    }

    public function fieldOptions(array $page)
    {
        $options = [];
        foreach ($page['fields'] as $name => $field) {
            $options[$name] = isset($field['options']) ? $field['options'] : [];
            $dynamic = isset($field['dynamic']) ? $field['dynamic'] : null;
            if ($dynamic === 'branches' && Schema::hasTable('lottery_branches')) {
                $options[$name] = DB::table('lottery_branches')
                    ->orderBy('sort_order')
                    ->pluck('title', 'id')
                    ->toArray();
            } elseif ($dynamic === 'lottery_types' && Schema::hasTable('lottery_types')) {
                $options[$name] = DB::table('lottery_types')
                    ->orderBy('sort_order')
                    ->pluck('lottery_name', 'id')
                    ->toArray();
            } elseif ($dynamic === 'platforms' && Schema::hasTable('game_lists')) {
                $values = DB::table('game_lists')
                    ->whereNotNull('platform_name')
                    ->where('platform_name', '<>', '')
                    ->distinct()
                    ->orderBy('platform_name')
                    ->pluck('platform_name')
                    ->toArray();
                $options[$name] = array_combine($values, $values) ?: [];
            }
        }

        return $options;
    }

    public function exportRows($code, array $filters = [])
    {
        $page = $this->page($code);
        if (!Schema::hasTable($page['storage'])) {
            return [];
        }

        return $this->buildQuery($code, $filters)
            ->limit(5000)
            ->get()
            ->all();
    }

    public function resolveImportId($code, array $input)
    {
        if (!empty($input['id'])) {
            return (int) $input['id'];
        }
        $page = $this->page($code);
        if ($page['storage'] !== 'game_lists') {
            return null;
        }
        if (!empty($input['game_code'])) {
            return DB::table('game_lists')
                ->where('game_code', trim((string) $input['game_code']))
                ->value('id');
        }

        return null;
    }

    private function applyFilters($query, array $page, array $filters)
    {
        $table = $page['storage'];
        foreach ($page['filters'] as $name => $label) {
            if (!array_key_exists($name, $filters) || $filters[$name] === '') {
                continue;
            }
            $value = $filters[$name];
            if ($name === 'keyword') {
                $searchFields = $page['search_fields'];
                $query->where(function ($nested) use ($searchFields, $value) {
                    foreach ($searchFields as $index => $field) {
                        if ($index === 0) {
                            $nested->where($field, 'like', '%'.$value.'%');
                        } else {
                            $nested->orWhere($field, 'like', '%'.$value.'%');
                        }
                    }
                });
                continue;
            }
            if ($name === 'date_from' && !empty($page['date_field'])) {
                $query->where($page['date_field'], '>=', $value.' 00:00:00');
                continue;
            }
            if ($name === 'date_to' && !empty($page['date_field'])) {
                $query->where($page['date_field'], '<=', $value.' 23:59:59');
                continue;
            }

            $field = $name === 'status' ? $page['status_field'] : $name;
            if (!Schema::hasColumn($table, $field)) {
                continue;
            }
            if (in_array($field, ['name', 'game_code', 'lottery_name', 'lottery_code', 'issue_no', 'username', 'play_code', 'plan_id'], true)) {
                $query->where($field, 'like', '%'.$value.'%');
                continue;
            }
            if ($name === 'status' && Schema::getColumnType($table, $field) === 'integer') {
                $value = $this->toBoolean($value);
            }
            $query->where($field, $value);
        }
    }

    private function buildQuery($code, array $filters)
    {
        $page = $this->page($code);
        $table = $page['storage'];
        $query = DB::table($table);
        if ((string) $code === '70037') {
            $query->where('is_hot', 1);
        }

        $this->applyFilters($query, $page, $filters);
        $sortColumn = Schema::hasColumn($table, 'sort_order')
            ? 'sort_order'
            : (Schema::hasColumn($table, 'order_by') ? 'order_by' : null);
        if ($sortColumn) {
            $query->orderBy($sortColumn);
        }
        if (Schema::hasColumn($table, 'id')) {
            $query->orderByDesc('id');
        }

        return $query;
    }

    private function pageDefinition(
        $code,
        $title,
        $storage,
        array $filters,
        array $columns,
        array $fields,
        array $actions,
        array $searchFields,
        $statusField,
        $dateField = null
    ) {
        return [
            'code' => (string) $code,
            'title' => $title,
            'module' => '游戏管理',
            'storage' => $storage,
            'filters' => $filters,
            'columns' => $columns,
            'fields' => $fields,
            'actions' => $actions,
            'search_fields' => $searchFields,
            'status_field' => $statusField,
            'date_field' => $dateField,
        ];
    }

    private function field($label, $type, $required = false, array $options = [], $dynamic = null)
    {
        return [
            'label' => $label,
            'type' => $type,
            'required' => (bool) $required,
            'options' => $options,
            'dynamic' => $dynamic,
        ];
    }

    private function statusField()
    {
        return $this->field('状态', 'select', true, [
            'enabled' => '启用',
            'disabled' => '停用',
        ]);
    }

    private function booleanField($label)
    {
        return $this->field($label, 'boolean', false, [
            1 => '是',
            0 => '否',
        ]);
    }

    private function gameTypeOptions()
    {
        return [
            'slot' => '电子',
            'table' => '桌台',
            'poker' => '棋牌',
            'realbet' => '真人',
            'sport' => '体育',
            'gaming' => '电竞',
            'joker' => '棋牌',
            'lottery' => '彩票',
            'fish' => '捕鱼',
            'fishing' => '捕鱼',
            'lhc' => '六合彩',
            'jsc' => '极速彩',
            'jwc' => '境外彩',
            'qkc' => '区块彩',
        ];
    }

    private function cleanText($value, $length)
    {
        $value = trim(strip_tags((string) $value));

        return function_exists('mb_substr')
            ? mb_substr($value, 0, $length)
            : substr($value, 0, $length);
    }

    private function toBoolean($value)
    {
        return in_array((string) $value, ['1', 'true', 'enabled', 'yes', 'on'], true) ? 1 : 0;
    }

    private function normalizeStatus($status)
    {
        $status = strtolower(trim((string) $status));
        if (in_array($status, ['enabled', 'published', 'pending', 'cancelled', 'disabled'], true)) {
            return $status;
        }

        return $this->toBoolean($status) ? 'enabled' : 'disabled';
    }

    private function cleanIds(array $ids)
    {
        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    private function currentAdminId()
    {
        try {
            if (class_exists(\Dcat\Admin\Admin::class) && \Dcat\Admin\Admin::user()) {
                return \Dcat\Admin\Admin::user()->getKey();
            }
        } catch (\Throwable $exception) {
        }

        return null;
    }
}
