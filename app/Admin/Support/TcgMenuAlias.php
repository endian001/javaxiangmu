<?php

namespace App\Admin\Support;

class TcgMenuAlias
{
    private const MAP = [
        '20200' => 'agents',
        '20470' => 'agent-applys',
        '20084' => 'agent-settlements',
        '60401' => 'work-orders',
        '31326' => 'agent-settlements',
        '31052' => 'agent-settlements',
        '31042' => 'agent-settlements',
        '31032' => 'agent-settlements',
        '31039' => 'agent-commission',
        '31029' => 'agent-commission',
        '31219' => 'agent-settlements',

        '31009' => 'finance-report',
        '20000' => 'recharge',
        '120000' => 'users',
        '20080' => 'transfer-logs',
        '20008' => 'withdraws',
        '20095' => 'recharge',
        '2162' => 'transfer-logs',
        '21170' => 'tcg/20068',

        '311792' => 'users',
        '182' => 'users',
        '73050' => 'user-operate-logs',
        '11041' => 'users',
        '26100' => 'agents',
        '25100' => 'withdraws',
        '20246' => 'users',
        '52000' => 'tcg/ops/52000',
        '12660' => 'users',
        '41002' => 'tcg/ops/41002',
        '20430' => 'tcg/ops/20430',
        '680308' => 'tcg/ops/680308',
        '20225' => 'finance-report',
        '2865' => 'work-orders',
        '181' => 'users',

        '31121' => 'game-records',
        '31050' => 'fanshui',
        '31002' => 'bet-report',
        '31379' => 'game-records',
        '50300' => 'game-records',
        '40020' => 'game-records',
        '50100' => 'game-records',
        '40090' => 'game-records',
        '40101' => 'game-records',

        '250001' => 'user-vips',
        '250002' => 'user-vips',
        '250003' => 'tcg/ops/250003',
        '250004' => 'tcg/ops/250004',
        '20324' => 'user-vips',
        '20346' => 'user-vips',

        '27000' => 'activities',
        '20393' => 'tcg/ops/20393',
        '24780' => 'activities',
        '24781' => 'activities',
        '24782' => 'activities',
        '24783' => 'activities',
        '24784' => 'activities',
        '24785' => 'activity-apply',
        '24786' => 'tcg/ops/24786',
        '24800' => 'tcg/ops/24800',
        '20400' => 'activity-apply',
        '610223' => 'activities',

        '240001' => 'agent-settlements',
        '240110' => 'agent-fenxiang',
        '240111' => 'agent-settlements',
        '240003' => 'agent-commission',
        '240004' => 'agents',
        '240038' => 'finance-report',

        '20300' => 'articles',
        '400000' => 'articles',
        '80001' => 'messages',
        '207200' => 'messages',
        '650001' => 'user-operate-logs',

        '20599' => 'tcg/ops/20599',
        '20530' => 'tcg/ops/20530',
        '20260' => 'tcg/ops/20260',
        '20220' => 'tcg/ops/20220',
        '31210' => 'tcg/ops/31210',

        '31023' => 'finance-report',
        '31690' => 'finance-report',
        '31672' => 'finance-report',
        '31456' => 'recharge',
        '31100' => 'recharge',
        '31207' => 'finance-report',
        '31020' => 'finance-report',
        '31105' => 'finance-report',
        '31067' => 'game-records',
        '31103' => 'game-records',
        '38060' => 'game-records',
        '39081' => 'recharge',
        '39082' => 'withdraws',
        '31140' => 'users',

        '670008' => 'user-operate-logs',
        '680301' => 'tcg/36000',
    ];

    public static function target(string $code): ?string
    {
        return self::MAP[$code] ?? null;
    }

    public static function all(): array
    {
        return self::MAP;
    }
}
