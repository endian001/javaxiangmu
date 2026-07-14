# TH2W / TH2.VIP 优秀代码示例

## 1. 文档定位

本文只收录当前项目中真正值得分析和复用的实现思想。示例代码不是原始源码复制，而是根据仓库中的核心实现抽取出的 **最小可运行示例**，用于说明背后的工程模式。

所有示例均可用 PHP CLI 直接运行，目标是让读者理解机制，而不是依赖 Laravel 环境。

运行方式示例：

```bash
php example.php
```

## 2. 示例一：活动可见性筛选

### 所在模块

活动服务。

### 解决的问题

前台活动列表、活动详情和首页弹窗都需要判断活动是否应该展示。规则包括：

- 活动是否启用。
- 移动端是否可见。
- 是否到开始时间。
- 是否已过结束时间。
- 展示顺序。

如果这些规则散落在控制器和前端中，活动展示会不一致。

### 为什么值得关注

这个实现把“可见性”做成一个纯规则服务，不直接关心数据库、登录态、申请记录或响应格式。它让活动展示规则更容易测试和复用。

### 体现的思想

- 单一职责。
- 纯函数式筛选。
- 先过滤，再排序。
- 控制器协调上下文，服务负责核心规则。

### 最小可运行代码示例

```php
<?php

function visiblePromotions(array $items, string $channel, DateTimeImmutable $now): array
{
    $channel = $channel === 'mobile' ? 'mobile' : 'desktop';

    $visible = array_filter($items, function ($item) use ($channel, $now) {
        if ((int)($item['state'] ?? 0) !== 1) {
            return false;
        }
        if ($channel === 'mobile' && (int)($item['app_state'] ?? 1) !== 1) {
            return false;
        }

        $startsAt = empty($item['starts_at']) ? null : new DateTimeImmutable($item['starts_at']);
        if ($startsAt && $startsAt > $now) {
            return false;
        }

        $endsAt = empty($item['ends_at']) ? null : new DateTimeImmutable($item['ends_at']);
        if ($endsAt && $endsAt < $now) {
            return false;
        }

        return true;
    });

    usort($visible, function ($a, $b) {
        $sort = (int)($b['sort_order'] ?? 0) <=> (int)($a['sort_order'] ?? 0);
        return $sort !== 0 ? $sort : (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0);
    });

    return array_values($visible);
}

$items = [
    ['id' => 1, 'title' => '旧活动', 'state' => 1, 'app_state' => 1, 'starts_at' => '2026-01-01', 'ends_at' => '2026-02-01', 'sort_order' => 100],
    ['id' => 2, 'title' => '移动端关闭', 'state' => 1, 'app_state' => 0, 'starts_at' => null, 'ends_at' => null, 'sort_order' => 200],
    ['id' => 3, 'title' => '当前活动', 'state' => 1, 'app_state' => 1, 'starts_at' => '2026-07-01', 'ends_at' => '2026-08-01', 'sort_order' => 300],
];

$visible = visiblePromotions($items, 'mobile', new DateTimeImmutable('2026-07-13'));
echo $visible[0]['title'] . PHP_EOL;
```

预期输出：

```text
当前活动
```

### 是否值得复用

值得复用。活动、公告、Banner、弹窗等展示型模块都可以采用“启用状态 + 渠道 + 时间窗 + 排序”的规则服务。

### 局限

它只解决展示可见性，不解决活动申请、黑名单、优惠券、曝光和审核流程。这些应由更高层流程协调。

## 3. 示例二：资金转账的 pending 状态机

### 所在模块

安全游戏转账服务。

### 解决的问题

主钱包和游戏平台之间转账不是本地数据库单点事务。外部游戏平台可能失败，也可能已经成功但本地后处理失败。如果只返回成功或失败，后续无法对账。

### 为什么值得关注

项目把转账拆成：

- 本地预留余额。
- 创建 pending 流水。
- 调用外部接口。
- 外部失败则回补。
- 外部成功则本地落账。
- 外部成功但本地失败则进入待恢复状态。

这是资金系统中非常关键的生产思维。

### 体现的思想

- 显式状态。
- 幂等和并发保护意识。
- 外部系统不确定性建模。
- 对账优先于简单异常吞掉。

### 最小可运行代码示例

```php
<?php

final class Wallet
{
    public $balance;

    public function __construct(float $balance)
    {
        $this->balance = $balance;
    }
}

function transferToGame(Wallet $wallet, float $amount, callable $upstreamDeposit, callable $localPost): array
{
    if ($amount <= 0 || $amount > $wallet->balance) {
        return ['state' => 'rejected', 'message' => 'invalid amount'];
    }

    // 关键步骤 1：先本地预扣，形成 pending 流水。
    $wallet->balance -= $amount;
    $log = [
        'state' => 'pending',
        'external_status' => 'calling',
        'recovery_status' => 'calling',
        'amount' => $amount,
    ];

    // 关键步骤 2：调用外部游戏平台。
    $externalOk = $upstreamDeposit($amount);
    if (!$externalOk) {
        // 外部失败：回补本地余额，流水标记外部失败。
        $wallet->balance += $amount;
        $log['state'] = 'failed';
        $log['external_status'] = 'failed';
        $log['recovery_status'] = 'external_failed';
        return $log + ['balance' => $wallet->balance];
    }

    // 关键步骤 3：外部成功后，本地落平台余额缓存等后处理。
    try {
        $localPost($amount);
        $log['state'] = 'success';
        $log['external_status'] = 'success';
        $log['recovery_status'] = 'success';
    } catch (Throwable $e) {
        // 外部已成功，本地失败，不能简单回滚，只能进入待恢复。
        $log['state'] = 'pending';
        $log['external_status'] = 'success';
        $log['recovery_status'] = 'external_success_local_pending';
    }

    return $log + ['balance' => $wallet->balance];
}

$wallet = new Wallet(100);
$result = transferToGame(
    $wallet,
    30,
    function () { return true; },
    function () { throw new RuntimeException('local write failed'); }
);

echo $result['recovery_status'] . PHP_EOL;
echo $result['balance'] . PHP_EOL;
```

预期输出：

```text
external_success_local_pending
70
```

### 是否值得复用

非常值得。充值、提现、代理充值、返水、红包和佣金结算都可以参考这种“先定义状态，再处理外部不确定性”的模式。

### 局限

示例省略了数据库事务、行锁、唯一索引和真实对账任务。真实系统必须把这些机制补齐。

## 4. 示例三：运营限制的 scope 匹配

### 所在模块

TCG 业务运营服务。

### 解决的问题

运营人员可能需要限制某个用户：

- 禁止所有游戏。
- 禁止某个平台。
- 禁止某个游戏类型。
- 禁止某个具体游戏。
- 禁止某个平台下的某个游戏。

如果每种限制都设计独立字段和独立查询，后台会变复杂。

### 为什么值得关注

项目使用 `game_scope` 统一表达限制范围，并在运行时生成多个候选 scope，包括 `*`、`all`、平台、游戏类型、游戏 code、平台:游戏 code 等。

### 体现的思想

- 用统一 scope 表达多层级规则。
- 兼容大小写。
- 规则表结构简单，查询逻辑集中。

### 最小可运行代码示例

```php
<?php

function gameScopes(string $platform, string $gameType = '', string $gameCode = ''): array
{
    $platform = trim($platform);
    $gameType = trim($gameType);
    $gameCode = trim($gameCode);

    $scopes = ['*', 'all'];
    foreach ([$platform, $gameType, $gameCode] as $scope) {
        if ($scope !== '') {
            $scopes[] = $scope;
            $scopes[] = strtoupper($scope);
            $scopes[] = strtolower($scope);
        }
    }

    if ($platform !== '' && $gameCode !== '') {
        $scopes[] = $platform . ':' . $gameCode;
        $scopes[] = strtoupper($platform) . ':' . strtoupper($gameCode);
        $scopes[] = strtolower($platform) . ':' . strtolower($gameCode);
    }

    return array_values(array_unique($scopes));
}

$rules = ['WG:3002', 'SPORT', 'all'];
$requestScopes = gameScopes('wg', 'slot', '3002');
$matched = array_values(array_intersect($rules, $requestScopes));

echo $matched[0] . PHP_EOL;
```

预期输出：

```text
WG:3002
```

### 是否值得复用

值得复用。活动、人群、支付通道、客服等级和推广渠道都可以用类似 scope 机制表示“全局、分类、个体”的规则层级。

### 局限

scope 字符串灵活但也容易失控。长期应配合枚举、后台校验和文档字典。

## 5. 示例四：后台页面契约和输入白名单

### 所在模块

后台游戏管理服务、平台运营服务、平台设置服务。

### 解决的问题

TCG 风格后台有大量页面。如果每个页面都手写字段、筛选、列表、保存、状态、导入导出，会产生大量重复代码。

### 为什么值得关注

项目使用页面契约描述：

- 页面 code。
- 标题。
- 存储表。
- 字段列表。
- 字段类型。
- 是否必填。
- 支持动作。

保存时只接受契约中的字段，并按类型清洗。这比直接保存全部请求输入更安全。

### 体现的思想

- 配置驱动后台。
- 字段白名单。
- 输入规范化。
- 通用页面能力复用。

### 最小可运行代码示例

```php
<?php

$page = [
    'code' => '31000',
    'title' => '三方游戏列表',
    'fields' => [
        'name' => ['label' => '游戏名称', 'type' => 'text', 'required' => true],
        'site_state' => ['label' => '站点状态', 'type' => 'boolean', 'required' => false],
        'order_by' => ['label' => '排序', 'type' => 'integer', 'required' => false],
    ],
];

function filterInput(array $page, array $input): array
{
    $filtered = [];

    foreach ($page['fields'] as $name => $field) {
        if (!array_key_exists($name, $input)) {
            if (!empty($field['required'])) {
                throw new InvalidArgumentException($field['label'] . '不能为空');
            }
            continue;
        }

        $value = is_string($input[$name]) ? trim($input[$name]) : $input[$name];
        if (!empty($field['required']) && $value === '') {
            throw new InvalidArgumentException($field['label'] . '不能为空');
        }

        if ($field['type'] === 'boolean') {
            $filtered[$name] = in_array((string)$value, ['1', 'true', 'enabled', 'yes', 'on'], true) ? 1 : 0;
        } elseif ($field['type'] === 'integer') {
            $filtered[$name] = (int)$value;
        } else {
            $filtered[$name] = substr(strip_tags((string)$value), 0, 500);
        }
    }

    return $filtered;
}

$input = [
    'name' => '<b>Fortune Game</b>',
    'site_state' => 'on',
    'order_by' => '20',
    'unexpected_admin_field' => 'should be ignored',
];

print_r(filterInput($page, $input));
```

预期输出要点：

```text
name => Fortune Game
site_state => 1
order_by => 20
```

### 是否值得复用

值得复用。后台大量 CRUD 页面、配置页、运营记录页都适合用契约描述。

### 局限

契约继续膨胀后，服务类会变得庞大。建议按业务域拆分契约文件，并维护 code 到业务含义的字典。

## 6. 示例五：API 用户状态守卫

### 所在模块

API 鉴权中间件。

### 解决的问题

API token 只证明请求携带了某个用户的凭证，但不能证明该用户当前允许继续使用系统。用户可能已经被禁用、删除或加入黑名单。

### 为什么值得关注

中间件把 token 校验和用户状态校验放在统一入口，避免每个接口重复判断。

### 体现的思想

- 鉴权不仅是“找到用户”。
- 用户状态是访问控制的一部分。
- 高风险系统必须把黑名单纳入 API 边界。

### 最小可运行代码示例

```php
<?php

function authenticate(array $users, string $token): array
{
    $token = trim(preg_replace('/^Bearer\s+/i', '', $token));
    $user = null;

    foreach ($users as $candidate) {
        if (($candidate['api_token'] ?? '') === $token) {
            $user = $candidate;
            break;
        }
    }

    if (!$user) {
        return ['allowed' => false, 'status' => 401, 'message' => 'authentication failed'];
    }

    if (($user['status'] ?? 1) <= 0 || ($user['isdel'] ?? 0) == 1 || ($user['isblack'] ?? 0) == 1) {
        return ['allowed' => false, 'status' => 403, 'message' => 'account disabled'];
    }

    return ['allowed' => true, 'status' => 200, 'user_id' => $user['id']];
}

$users = [
    ['id' => 1, 'api_token' => 'abc', 'status' => 1, 'isdel' => 0, 'isblack' => 0],
    ['id' => 2, 'api_token' => 'blocked', 'status' => 1, 'isdel' => 0, 'isblack' => 1],
];

print_r(authenticate($users, 'Bearer blocked'));
```

预期输出要点：

```text
allowed => false
status => 403
message => account disabled
```

### 是否值得复用

值得复用。所有需要登录的 API 都应统一通过这类守卫，而不是在控制器里手动解析 token。

### 局限

当前项目还存在旧接口兼容逻辑，部分路径可能没有完全统一到中间件。后续应把 token 解析、用户状态和黑名单检查进一步集中。

## 7. 总结

当前项目中最值得学习的代码不是某个语法技巧，而是几类工程思路：

- 高风险资金链路必须显式表达状态。
- 展示型规则适合抽成纯服务。
- 运营限制适合用 scope 统一表达。
- 大量后台页面适合契约化，但要配合白名单和测试。
- 鉴权边界必须包含用户状态，而不仅是 token 存在。

这些模式都适合在后续重构中继续强化。
