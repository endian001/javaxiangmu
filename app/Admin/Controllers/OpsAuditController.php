<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Layout\Content;
use Illuminate\Routing\Controller;

class OpsAuditController extends Controller
{
    public function index(Content $content)
    {
        $reports = [
            'wallet' => $this->readJson(storage_path('app/wallet-ops-audit/wallet_ops_audit.json')),
            'admin' => $this->readJson(storage_path('app/admin-ops-audit/admin_ops_audit.json')),
            'game' => $this->readJson(storage_path('app/game-ops-audit/game_ops_audit.json')),
            'frontend' => $this->readJson(storage_path('app/frontend-ops-audit/frontend_ops_audit.json')),
            'xh' => $this->readJson(storage_path('app/xh-api-ops-audit/xh_api_ops_audit.json')),
            'pg51' => $this->readJson(storage_path('app/pg51-sync/ops-review/xh-upstream-confirmation/manifest.json')),
        ];

        return $content
            ->header('运营审计')
            ->description('资金 / PG51 / XH / 前端')
            ->body($this->renderDashboard($reports));
    }

    protected function renderDashboard(array $reports)
    {
        $wallet = $reports['wallet']['data'];
        $admin = $reports['admin']['data'];
        $game = $reports['game']['data'];
        $frontend = $reports['frontend']['data'];
        $xh = $reports['xh']['data'];
        $pg51 = $reports['pg51']['data'];

        $walletIssues = $this->issueCount($wallet);
        $activePending = (int) $this->value($wallet, 'summary.active_pending_transfers', 0);
        $localPending = (int) $this->value($wallet, 'summary.external_success_local_pending', 0);
        $unknownReconcile = (int) $this->value($wallet, 'summary.recovery_unknown_reconcile', 0);
        $adminIssues = $this->issueCount($admin);
        $gameIssues = $this->issueCount($game);
        $frontendIssues = $this->issueCount($frontend);
        $xhIssues = $this->issueCount($xh);
        $requiredMissing = (int) $this->value($xh, 'endpoint_coverage.required_missing_count', 0);
        $remaining = (int) $this->value($pg51, 'counts.remaining', 0);
        $safeCandidates = (int) $this->value($pg51, 'counts.safe_auto_import_candidates', 0);

        $cards = [
            $this->card('资金安全', $reports['wallet'], $reports['wallet']['error'] === '' && $walletIssues === 0 && $activePending === 0 && $localPending === 0 && $unknownReconcile === 0, [
                '充值单' => $this->value($wallet, 'summary.recharge_count', 0),
                '提现单' => $this->value($wallet, 'summary.withdraw_count', 0),
                '转账流水' => $this->value($wallet, 'summary.transfer_log_count', 0),
                '处理中转账' => $activePending,
                '上游成功待入账' => $localPending,
                '待对账' => $unknownReconcile,
                '审计问题' => $walletIssues,
            ]),
            $this->card('后台控制', $reports['admin'], $reports['admin']['error'] === '' && $adminIssues === 0, [
                '关键路由' => $this->value($admin, 'summary.required_route_count', 0) - $this->value($admin, 'summary.missing_required_routes', 0),
                '关键控制器' => $this->value($admin, 'summary.required_controller_count', 0) - $this->value($admin, 'summary.missing_required_controllers', 0),
                '只读资源' => $this->value($admin, 'summary.read_only_controller_count', 0) - $this->value($admin, 'summary.read_only_missing_count', 0),
                '控制守卫' => $this->value($admin, 'summary.control_marker_count', 0) - $this->value($admin, 'summary.control_marker_missing_count', 0),
                '调试入口' => $this->value($admin, 'summary.debug_route_count', 0),
                '审计问题' => $adminIssues,
            ]),
            $this->card('游戏目录', $reports['game'], $gameIssues === 0, [
                '已公开游戏' => $this->value($game, 'summary.enabled_games', 0) . ' / ' . $this->value($game, 'summary.total_games', 0),
                '热门游戏' => $this->value($game, 'summary.hot_games', 0),
                '公开平台' => $this->value($game, 'summary.enabled_platforms', 0),
                '公开分类' => $this->value($game, 'summary.enabled_categories', 0),
                '审计问题' => $gameIssues,
            ]),
            $this->card('前端体验', $reports['frontend'], $frontendIssues === 0, [
                '首页状态' => $this->value($frontend, 'public.homepage.status', 0),
                'APP配置状态' => $this->value($frontend, 'public.app.status', 0),
                '前端游戏数' => $this->value($frontend, 'public.api.game_count', 0),
                '图片抽样' => $this->value($frontend, 'public.api.image_probe_count', 0) . ' / broken ' . $this->value($frontend, 'public.api.broken_image_count', 0),
                '审计问题' => $frontendIssues,
            ]),
            $this->card('XH接口', $reports['xh'], $xhIssues === 0 && $requiredMissing === 0, [
                '活跃平台' => $this->value($xh, 'catalog.active_platform_count', 0),
                '直属游戏' => $this->value($xh, 'catalog.active_direct_game_count', 0),
                '子游戏' => $this->value($xh, 'catalog.sub_game_count', 0),
                '端点覆盖' => $this->value($xh, 'endpoint_coverage.covered_count', 0) . ' / ' . $this->value($xh, 'endpoint_coverage.documented_count', 0),
                '缺必需端点' => $requiredMissing,
            ]),
            $this->card('PG51补齐门禁', $reports['pg51'], $remaining === 0 || $safeCandidates > 0, [
                '剩余缺口' => $remaining,
                '平台未确认' => $this->value($pg51, 'counts.platform_not_confirmed', 0),
                '已映射未命中' => $this->value($pg51, 'counts.mapped_but_xh_not_matched', 0),
                '碰撞人工复核' => $this->value($pg51, 'counts.collision_manual_review', 0),
                '安全自动导入' => $safeCandidates,
            ], $remaining > 0 && $safeCandidates === 0 ? '等待XH确认' : '可继续处理'),
        ];

        return '<div class="ops-audit-page">'
            . $this->style()
            . '<div class="ops-summary">'
            . '<h3>运营状态</h3>'
            . '<p>当前游戏和接口审计用于判断线上是否可运营；PG51补齐门禁用于防止把未确认的同名或跨平台游戏误导入。</p>'
            . '</div>'
            . '<div class="ops-grid">' . implode('', $cards) . '</div>'
            . $this->section('资金幂等索引', $this->table($this->value($wallet, 'unique_indexes', []), ['table', 'index', 'column', 'exists', 'unique']))
            . $this->section('资金重复订单检查', $this->table($this->value($wallet, 'duplicates', []), ['table', 'column', 'duplicate_count', 'sample']))
            . $this->section('资金状态字段', $this->table($this->value($wallet, 'schema', []), ['table', 'column', 'exists']))
            . $this->section('资金源码守卫', $this->table($this->value($wallet, 'source_guards', []), ['path', 'exists', 'direct_trans_references', 'missing_markers']))
            . $this->section('后台关键路由', $this->table($this->value($admin, 'routes', []), ['name', 'needle', 'exists']))
            . $this->section('后台只读资源', $this->table($this->value($admin, 'read_only_controllers', []), ['controller', 'exists', 'read_only']))
            . $this->section('后台控制守卫', $this->table($this->value($admin, 'control_markers', []), ['name', 'path', 'exists', 'missing_markers']))
            . $this->section('PG51缺口处理', $this->pg51Gate($pg51))
            . $this->section('公开分类', $this->table($this->value($game, 'category_counts', []), ['category_id', 'enabled', 'total']))
            . $this->section('主要平台', $this->table(array_slice($this->value($game, 'platform_counts', []), 0, 20), ['platform_name', 'enabled', 'total']))
            . $this->section('XH端点覆盖', $this->table($this->value($xh, 'endpoint_coverage.endpoints', []), ['path', 'title', 'covered', 'coverage_source']))
            . $this->section('报告文件', $this->files($reports))
            . '</div>';
    }

    protected function card($title, array $report, $ok, array $items, $overrideLabel = null)
    {
        $status = $overrideLabel ?: ($ok ? '正常' : '异常');
        $badgeClass = $ok ? 'ok' : 'warn';
        $html = '<div class="ops-card">';
        $html .= '<div class="ops-card-head"><h4>' . $this->e($title) . '</h4><span class="ops-badge ' . $badgeClass . '">' . $this->e($status) . '</span></div>';
        $html .= '<div class="ops-generated">生成时间: ' . $this->e($this->value($report['data'], 'generated_at', '-')) . '</div>';
        if ($report['error'] !== '') {
            $html .= '<div class="ops-error">' . $this->e($report['error']) . '</div>';
        }
        $html .= '<dl>';
        foreach ($items as $label => $value) {
            $html .= '<dt>' . $this->e($label) . '</dt><dd>' . $this->e($value) . '</dd>';
        }
        $html .= '</dl></div>';

        return $html;
    }

    protected function pg51Gate(array $pg51)
    {
        $remaining = (int) $this->value($pg51, 'counts.remaining', 0);
        $safeCandidates = (int) $this->value($pg51, 'counts.safe_auto_import_candidates', 0);
        $gate = $remaining > 0 && $safeCandidates === 0 ? 'waiting_for_xh_confirmation' : 'review_ready';
        $files = $this->value($pg51, 'output_files', []);

        $html = '<div class="ops-gate">';
        $html .= '<p><strong>当前门禁:</strong> <span class="ops-code">' . $this->e($gate) . '</span></p>';
        $html .= '<p>剩余 ' . $this->e($remaining) . ' 个 PG51 游戏仍需 XH/上游确认 exact api_code、category/gameType、gameCode 和标题。安全自动导入候选为 ' . $this->e($safeCandidates) . '，所以后台不应盲导。</p>';
        $html .= '<ul>';
        foreach ($files as $file) {
            $html .= '<li>' . $this->e($file) . '</li>';
        }
        $html .= '</ul></div>';

        return $html;
    }

    protected function section($title, $body)
    {
        return '<div class="ops-section"><h4>' . $this->e($title) . '</h4>' . $body . '</div>';
    }

    protected function table($rows, array $columns)
    {
        if (!is_array($rows) || count($rows) === 0) {
            return '<p class="ops-muted">暂无数据</p>';
        }

        $html = '<div class="table-responsive"><table class="table table-sm table-striped ops-table"><thead><tr>';
        foreach ($columns as $column) {
            $html .= '<th>' . $this->e($column) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($columns as $column) {
                $value = is_array($row) && array_key_exists($column, $row) ? $row[$column] : '';
                $html .= '<td>' . $this->e($value) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody></table></div>';

        return $html;
    }

    protected function files(array $reports)
    {
        $html = '<table class="table table-sm ops-table"><thead><tr><th>报告</th><th>路径</th><th>更新时间</th><th>状态</th></tr></thead><tbody>';
        foreach ($reports as $name => $report) {
            $html .= '<tr><td>' . $this->e($name) . '</td><td><span class="ops-code">' . $this->e($report['path']) . '</span></td><td>' . $this->e($report['mtime'] ?: '-') . '</td><td>' . $this->e($report['error'] ?: 'ok') . '</td></tr>';
        }
        $html .= '</tbody></table>';

        return $html;
    }

    protected function readJson($path)
    {
        $result = [
            'path' => $path,
            'mtime' => is_file($path) ? date('Y-m-d H:i:s', filemtime($path)) : null,
            'error' => '',
            'data' => [],
        ];

        if (!is_file($path)) {
            $result['error'] = 'report file missing';
            return $result;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            $result['error'] = 'report file unreadable';
            return $result;
        }

        $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
        $data = json_decode($raw, true);
        if (!is_array($data)) {
            $result['error'] = 'invalid json: ' . json_last_error_msg();
            return $result;
        }

        $result['data'] = $data;
        return $result;
    }

    protected function issueCount(array $report)
    {
        $counts = $this->value($report, 'issue_counts', []);
        if (!is_array($counts)) {
            return 0;
        }

        $total = 0;
        foreach ($counts as $row) {
            if (is_array($row) && array_key_exists('count', $row)) {
                $total += (int) $row['count'];
            } elseif (is_numeric($row)) {
                $total += (int) $row;
            } else {
                $total++;
            }
        }

        return $total;
    }

    protected function value($data, $path, $default = '')
    {
        if (!is_array($data)) {
            return $default;
        }

        foreach (explode('.', $path) as $segment) {
            if (is_array($data) && array_key_exists($segment, $data)) {
                $data = $data[$segment];
            } else {
                return $default;
            }
        }

        return $data;
    }

    protected function e($value)
    {
        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif (is_array($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    protected function style()
    {
        return '<style>
            .ops-audit-page{font-size:13px}
            .ops-summary{background:#fff;border:1px solid #eef0f4;border-radius:6px;padding:16px;margin-bottom:14px}
            .ops-summary h3{margin:0 0 6px;font-size:18px}
            .ops-summary p{margin:0;color:#606975}
            .ops-grid{display:flex;flex-wrap:wrap;margin:0 -7px}
            .ops-card{background:#fff;border:1px solid #e8ebf1;border-radius:6px;padding:14px;margin:7px;flex:1 1 250px;min-width:250px}
            .ops-card-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:8px}
            .ops-card h4{font-size:16px;margin:0}
            .ops-badge{display:inline-block;border-radius:12px;padding:3px 9px;font-size:12px;color:#fff}
            .ops-badge.ok{background:#21a67a}
            .ops-badge.warn{background:#d9822b}
            .ops-generated{color:#88909b;font-size:12px;margin-bottom:8px}
            .ops-error{color:#c23b3b;margin-bottom:8px}
            .ops-card dl{display:grid;grid-template-columns:1fr auto;gap:6px 10px;margin:0}
            .ops-card dt{font-weight:500;color:#606975}
            .ops-card dd{margin:0;font-weight:600;color:#1f2933}
            .ops-section{background:#fff;border:1px solid #eef0f4;border-radius:6px;padding:14px;margin-top:14px}
            .ops-section h4{font-size:16px;margin:0 0 10px}
            .ops-code{font-family:Menlo,Consolas,monospace;font-size:12px;background:#f5f7fa;border-radius:4px;padding:2px 4px}
            .ops-muted{color:#88909b;margin:0}
            .ops-gate p{margin:0 0 8px}
            .ops-gate ul{margin:8px 0 0;padding-left:18px}
            .ops-table{margin-bottom:0}
            .ops-table th{white-space:nowrap}
        </style>';
    }
}
