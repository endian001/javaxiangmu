<?php

namespace App\Console\Commands;

use App\Models\GameList;
use App\Models\SystemConfig;
use App\Services\TgService;
use Illuminate\Console\Command;

class XhApiOpsAudit extends Command
{
    protected $signature = 'XhApiOpsAudit
        {--out-dir=storage/app/xh-api-ops-audit : Directory for audit reports}
        {--ops-review-dir=storage/app/pg51-sync/ops-review : Optional PG51 operations review directory}
        {--page-size=100 : XH page size}
        {--max-pages=500 : Max pages for paged endpoints}
        {--pg51-only : Only audit the PG51 confirmation gate without database or upstream API queries}';

    protected $description = 'Generate read-only operational health reports for XH upstream API catalog';

    protected $apiUrl;
    protected $account;
    protected $apiKey;

    protected $typeMap = [
        1 => 'realbet',
        2 => 'fishing',
        3 => 'concise',
        4 => 'lottery',
        5 => 'sport',
        6 => 'joker',
        7 => 'gaming',
    ];

    protected $confirmationFiles = [
        '01_platform_mapping_request.csv' => [
            'manifest_key' => 'platform_groups',
            'required_columns' => ['pg_platform', 'pg_category'],
        ],
        '03_mapped_missing_game_request.csv' => [
            'manifest_key' => 'mapped_but_xh_not_matched',
            'required_columns' => ['pg_platform', 'pg_category', 'play_name', 'play_code'],
        ],
        '04_category_conflict_fast_check.csv' => [
            'manifest_key' => 'category_conflict_fast_check',
            'required_columns' => ['pg_platform', 'pg_category', 'play_name', 'play_code'],
        ],
        '05_collision_manual_review.csv' => [
            'manifest_key' => 'collision_manual_review',
            'required_columns' => ['pg_platform', 'pg_category', 'play_name', 'play_code'],
        ],
    ];

    protected $documentedEndpoints = [
        ['method' => 'register', 'path' => '/ley/register', 'title' => '注册会员', 'required_for_ops' => true],
        ['method' => 'login', 'path' => '/ley/login', 'title' => '登陆游戏', 'required_for_ops' => true],
        ['method' => 'balance', 'path' => '/ley/balance', 'title' => '查询会员余额', 'required_for_ops' => true],
        ['method' => 'deposit', 'path' => '/ley/deposit', 'title' => '转入游戏分数', 'required_for_ops' => true],
        ['method' => 'withdrawal', 'path' => '/ley/withdrawal', 'title' => '转出游戏分数', 'required_for_ops' => true],
        ['method' => 'orderstatus', 'path' => '/ley/orderstatus', 'title' => '查询转账订单状态', 'required_for_ops' => true],
        ['method' => 'orderrecord', 'path' => '/ley/orderrecord', 'title' => '获取转账记录', 'required_for_ops' => true],
        ['method' => 'gamerecord', 'path' => '/ley/gamerecord', 'title' => '获取游戏记录', 'required_for_ops' => true],
        ['method' => 'credit', 'path' => '/ley/credit', 'title' => '获取商户接口余额', 'required_for_ops' => true],
        ['method' => 'fetch_catalog_platforms', 'path' => '/ley/api_code/list', 'title' => '获取接口列表', 'required_for_ops' => true],
        ['method' => 'fetch_catalog_direct_games', 'path' => '/ley/api_code/game', 'title' => '获取游戏列表', 'required_for_ops' => true],
        ['method' => 'gameslist', 'path' => '/ley/gamelist', 'title' => '获取子游戏列表', 'required_for_ops' => true],
        ['method' => 'get_rtp', 'path' => '/ley/get_rtp', 'title' => '获取玩家RTP', 'required_for_ops' => false],
        ['method' => 'edit_rtp', 'path' => '/ley/edit_rtp', 'title' => '修改玩家RTP', 'required_for_ops' => false],
        ['method' => 'result', 'path' => '/ley/result', 'title' => '查询开奖结果', 'required_for_ops' => false],
    ];

    public function handle()
    {
        $outDir = $this->resolvePath($this->option('out-dir'));
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        if ($this->option('pg51-only')) {
            return $this->handlePg51Only($outDir);
        }

        $this->apiUrl = rtrim(SystemConfig::getValue('game_api'), '/');
        $this->account = SystemConfig::getValue('merchant_account');
        $this->apiKey = SystemConfig::getValue('api_secret');

        $issues = [];
        $this->checkConfig($issues);

        $platforms = [];
        $directGames = [];
        $subGames = [];
        if (!$issues) {
            $platforms = $this->fetchPages('/ley/api_code/list', [], $issues);
            $directGames = $this->fetchPages('/ley/api_code/game', [], $issues);
            $subGames = $this->fetchPages('/ley/gamelist', [
                'status' => 1,
                'maintenance' => 1,
            ], $issues);
        }

        $catalog = $this->catalogSummary($platforms, $directGames, $subGames);
        $local = $this->localMappingStatus($platforms, array_merge($directGames, $subGames), $issues);
        $endpointCoverage = $this->endpointCoverage($issues);
        $pg51Confirmation = $this->pg51ConfirmationStatus($this->option('ops-review-dir'));
        $this->addPg51ConfirmationIssues($issues, $pg51Confirmation);

        $report = [
            'generated_at' => date('c'),
            'api_url' => $this->apiUrl,
            'account_configured' => $this->account !== '',
            'api_key_configured' => $this->apiKey !== '',
            'issue_counts' => $this->issueCounts($issues),
            'endpoint_coverage' => $endpointCoverage,
            'catalog' => $catalog,
            'local_mapping' => $local,
            'pg51_confirmation_package' => $pg51Confirmation,
            'issues' => $issues,
        ];

        $this->writeJson($outDir . '/xh_api_ops_audit.json', $report);
        $this->writeCsv($outDir . '/xh_api_ops_issues.csv', $issues, [
            'severity',
            'code',
            'message',
            'count',
            'sample',
        ]);
        $this->writeCsv($outDir . '/xh_local_missing_games.csv', $local['missing_games'], [
            'id',
            'platform_name',
            'category_id',
            'game_code',
            'name',
            'reason',
        ]);
        $this->writeCsv($outDir . '/xh_documented_endpoint_coverage.csv', $endpointCoverage['endpoints'], [
            'path',
            'title',
            'method',
            'required_for_ops',
            'covered',
            'coverage_source',
        ]);
        $this->writeCsv($outDir . '/xh_pg51_confirmation_gate.csv', [$this->pg51ConfirmationCsvRow($pg51Confirmation)], [
            'exists',
            'manifest_exists',
            'gate_exists',
            'review_readiness_exists',
            'import_gate',
            'review_readiness',
            'auto_import_gate',
            'remaining',
            'platform_not_confirmed',
            'mapped_but_xh_not_matched',
                'collision_manual_review',
                'category_conflict_fast_check',
                'safe_auto_import_candidates',
                'pending_pool_ready',
                'pending_pool_upstream_plain',
                'pending_pool_conflict_union',
                'dry_run_candidate_total',
                'dry_run_command_safety_failures',
                'dry_run_command_safety_summary',
                'dry_run_allowed',
                'database_import_allowed_now',
                'source_csv_integrity_failures',
                'source_csv_integrity_summary',
                'summary',
            ]);

        $this->info(sprintf(
            'platforms=%d direct=%d sub=%d local_enabled=%d missing=%d endpoints=%d/%d issues=%d critical=%d warnings=%d',
            $catalog['active_platform_count'],
            $catalog['active_direct_game_count'],
            $catalog['active_sub_game_count'],
            $local['enabled_game_count'],
            $local['missing_game_count'],
            $endpointCoverage['covered_count'],
            $endpointCoverage['documented_count'],
            count($issues),
            $report['issue_counts']['critical'] ?? 0,
            $report['issue_counts']['warning'] ?? 0
        ));
        if (!empty($pg51Confirmation['exists']) || !empty($pg51Confirmation['manifest_exists']) || !empty($pg51Confirmation['gate_exists'])) {
            $this->comment(sprintf(
                'PG51 confirmation readiness=%s auto_import_gate=%s remaining=%s safe_auto_import_candidates=%s ready=%s upstream_plain=%s conflict_union=%s database_import_allowed_now=%s',
                $pg51Confirmation['review_readiness'] ?? 'unknown',
                $pg51Confirmation['auto_import_gate'] ?? 'unknown',
                $this->displayCount($pg51Confirmation['remaining'] ?? null),
                $this->displayCount($pg51Confirmation['safe_auto_import_candidates'] ?? null),
                $this->displayCount($pg51Confirmation['pending_pool_ready'] ?? null),
                $this->displayCount($pg51Confirmation['pending_pool_upstream_plain'] ?? null),
                $this->displayCount($pg51Confirmation['pending_pool_conflict_union'] ?? null),
                !empty($pg51Confirmation['database_import_allowed_now']) ? 'true' : 'false'
            ));
        }
        $this->comment('Reports: ' . $outDir);

        return 0;
    }

    protected function handlePg51Only($outDir)
    {
        $issues = [];
        $pg51Confirmation = $this->pg51ConfirmationStatus($this->option('ops-review-dir'));
        $this->addPg51ConfirmationIssues($issues, $pg51Confirmation);

        $report = [
            'generated_at' => date('c'),
            'mode' => 'pg51-only',
            'issue_counts' => $this->issueCounts($issues),
            'pg51_confirmation_package' => $pg51Confirmation,
            'issues' => $issues,
        ];

        $this->writeJson($outDir . '/xh_api_ops_audit_pg51_only.json', $report);
        $this->writeCsv($outDir . '/xh_api_ops_pg51_only_issues.csv', $issues, [
            'severity',
            'code',
            'message',
            'count',
            'sample',
        ]);
        $this->writeCsv($outDir . '/xh_pg51_confirmation_gate.csv', [$this->pg51ConfirmationCsvRow($pg51Confirmation)], [
            'exists',
            'manifest_exists',
            'gate_exists',
            'review_readiness_exists',
            'import_gate',
            'review_readiness',
            'auto_import_gate',
            'remaining',
            'platform_not_confirmed',
            'mapped_but_xh_not_matched',
            'collision_manual_review',
            'category_conflict_fast_check',
            'safe_auto_import_candidates',
            'pending_pool_ready',
            'pending_pool_upstream_plain',
            'pending_pool_conflict_union',
            'dry_run_candidate_total',
            'dry_run_command_safety_failures',
            'dry_run_command_safety_summary',
            'dry_run_allowed',
            'database_import_allowed_now',
            'source_csv_integrity_failures',
            'source_csv_integrity_summary',
            'summary',
        ]);

        $this->info(sprintf(
            'mode=pg51-only issues=%d critical=%d warnings=%d readiness=%s auto_import_gate=%s remaining=%s ready=%s database_import_allowed_now=%s source_csv_integrity_failures=%d',
            count($issues),
            $report['issue_counts']['critical'] ?? 0,
            $report['issue_counts']['warning'] ?? 0,
            $pg51Confirmation['review_readiness'] ?? 'unknown',
            $pg51Confirmation['auto_import_gate'] ?? 'unknown',
            $this->displayCount($pg51Confirmation['remaining'] ?? null),
            $this->displayCount($pg51Confirmation['pending_pool_ready'] ?? null),
            !empty($pg51Confirmation['database_import_allowed_now']) ? 'yes' : 'no',
            (int)($pg51Confirmation['source_csv_integrity_failures'] ?? 0)
        ));
        if (!empty($pg51Confirmation['summary'])) {
            $this->warn($pg51Confirmation['summary']);
        }
        if (!empty($pg51Confirmation['source_csv_integrity_summary'])) {
            $this->comment($pg51Confirmation['source_csv_integrity_summary']);
        }
        $this->comment('Reports: ' . $outDir);

        return 0;
    }

    protected function checkConfig(array &$issues)
    {
        $this->addIssue($issues, 'critical', 'missing_game_api_config', 'XH game_api is not configured.', $this->apiUrl === '' ? 1 : 0);
        $this->addIssue($issues, 'critical', 'missing_merchant_account', 'XH merchant_account is not configured.', $this->account === '' ? 1 : 0);
        $this->addIssue($issues, 'critical', 'missing_api_secret', 'XH api_secret is not configured.', $this->apiKey === '' ? 1 : 0);
    }

    protected function fetchPages($path, array $params, array &$issues)
    {
        $items = [];
        $pageSize = max(1, (int)$this->option('page-size'));
        $maxPages = max(1, (int)$this->option('max-pages'));

        for ($page = 1; $page <= $maxPages; $page++) {
            $post = array_merge([
                'account' => $this->account,
                'api_key' => $this->apiKey,
                'currency' => 'CNY',
                'page' => $page,
                'pageSize' => $pageSize,
            ], $params);

            $res = $this->sendRequest($path, $post, $issues);
            if (!$res) {
                break;
            }

            $data = $res['Data'] ?? [];
            $list = [];
            if (isset($data['data']) && is_array($data['data'])) {
                $list = $data['data'];
            } elseif (is_array($data)) {
                $list = $data;
            }
            $items = array_merge($items, $list);

            $lastPage = $data['last_page'] ?? ($data['lastPage'] ?? null);
            if ($lastPage && $page >= (int)$lastPage) {
                break;
            }
            if (!$lastPage && count($list) < $pageSize) {
                break;
            }
        }

        return $items;
    }

    protected function sendRequest($path, array $post, array &$issues)
    {
        $url = $this->apiUrl . '/' . ltrim($path, '/');
        $maxAttempts = 3;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $post,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 90,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errno) {
                if ($attempt < $maxAttempts) {
                    sleep($attempt);
                    continue;
                }
                $this->addIssue($issues, 'critical', 'xh_request_curl_error', 'XH request failed with curl error.', 1, $path . ' attempts=' . $attempt . ' ' . $error);
                return null;
            }

            $data = json_decode($body, true);
            if (!is_array($data)) {
                if ($attempt < $maxAttempts) {
                    sleep($attempt);
                    continue;
                }
                $this->addIssue($issues, 'critical', 'xh_response_json_invalid', 'XH response is not valid JSON.', 1, $path . ' attempts=' . $attempt . ' http=' . $httpCode . ' ' . substr((string)$body, 0, 200));
                return null;
            }
            if ((string)($data['Code'] ?? '') !== '0') {
                if ($attempt < $maxAttempts) {
                    sleep($attempt);
                    continue;
                }
                $this->addIssue($issues, 'critical', 'xh_response_not_success', 'XH response Code is not 0.', 1, $path . ' attempts=' . $attempt . ' ' . ($data['Message'] ?? substr((string)$body, 0, 200)));
                return null;
            }

            return $data;
        }

        return null;
    }

    protected function catalogSummary(array $platforms, array $directGames, array $subGames)
    {
        $activePlatforms = array_filter($platforms, function ($row) {
            return (int)($row['status'] ?? 1) === 1;
        });
        $activeDirect = array_filter($directGames, function ($row) {
            return (int)($row['status'] ?? 1) === 1 && (int)($row['maintenance'] ?? 1) === 1;
        });
        $activeSub = array_filter($subGames, function ($row) {
            return (int)($row['status'] ?? 1) === 1 && (int)($row['maintenance'] ?? 1) === 1;
        });

        return [
            'platform_count' => count($platforms),
            'active_platform_count' => count($activePlatforms),
            'direct_game_count' => count($directGames),
            'active_direct_game_count' => count($activeDirect),
            'sub_game_count' => count($subGames),
            'active_sub_game_count' => count($activeSub),
            'active_game_category_counts' => $this->gameCategoryCounts(array_merge($activeDirect, $activeSub)),
        ];
    }

    protected function localMappingStatus(array $platforms, array $games, array &$issues)
    {
        $activePlatformCodes = [];
        foreach ($platforms as $platform) {
            if ((int)($platform['status'] ?? 1) !== 1) {
                continue;
            }
            $code = strtoupper(trim($platform['code'] ?? ''));
            if ($code !== '') {
                $activePlatformCodes[$code] = true;
            }
        }

        $gameExact = [];
        $gameScope = [];
        foreach ($games as $game) {
            if ((int)($game['status'] ?? 1) !== 1 || (int)($game['maintenance'] ?? 1) !== 1) {
                continue;
            }
            $code = strtoupper(trim($game['code'] ?? ''));
            $category = $this->typeMap[(int)($game['gameType'] ?? 0)] ?? '';
            $gameCode = $this->codeNorm($game['gameCode'] ?? '');
            if ($code === '' || $category === '' || $gameCode === '') {
                continue;
            }
            $gameScope[$code . '|' . $category] = true;
            $gameExact[$code . '|' . $category . '|' . $gameCode] = true;
        }

        $enabled = GameList::where('site_state', 1)->where('app_state', 1)->where('is_top', 1)
            ->orderBy('platform_name')
            ->orderBy('category_id')
            ->orderBy('game_code')
            ->get(['id', 'platform_name', 'category_id', 'game_code', 'name']);

        $missing = [];
        $missingPlatforms = [];
        foreach ($enabled as $row) {
            $code = strtoupper(trim($row->platform_name));
            $category = $this->normalizeLocalCategory($row->category_id);
            $gameCode = $this->codeNorm($row->game_code);
            $reason = '';

            if (empty($activePlatformCodes[$code])) {
                $reason = 'platform_not_active_in_xh';
                $missingPlatforms[$code] = true;
            } elseif (empty($gameScope[$code . '|' . $category])) {
                $reason = 'no_active_xh_games_for_platform_category';
            } elseif (!in_array($gameCode, ['0', 'lobby'], true) && empty($gameExact[$code . '|' . $category . '|' . $gameCode])) {
                $reason = 'game_code_not_active_in_xh_catalog';
            }

            if ($reason !== '') {
                $missing[] = [
                    'id' => $row->id,
                    'platform_name' => $row->platform_name,
                    'category_id' => $row->category_id,
                    'game_code' => $row->game_code,
                    'name' => $row->name,
                    'reason' => $reason,
                ];
            }
        }

        $this->addIssue($issues, 'critical', 'local_enabled_games_missing_in_xh', 'Enabled local games missing from active XH catalog.', count($missing), $this->sampleMissing($missing));
        $this->addIssue($issues, 'critical', 'local_enabled_platform_missing_in_xh', 'Enabled local game platforms missing from active XH platform list.', count($missingPlatforms), implode(', ', array_keys($missingPlatforms)));

        return [
            'enabled_game_count' => count($enabled),
            'missing_game_count' => count($missing),
            'missing_platform_count' => count($missingPlatforms),
            'missing_games' => $missing,
        ];
    }

    protected function endpointCoverage(array &$issues)
    {
        $serviceMethods = array_change_key_case(array_flip(get_class_methods(TgService::class)), CASE_LOWER);
        $covered = 0;
        $requiredMissing = [];
        $rows = [];

        foreach ($this->documentedEndpoints as $endpoint) {
            $method = strtolower($endpoint['method']);
            $source = '';
            $isCovered = false;
            if (strpos($method, 'fetch_catalog_') === 0) {
                $isCovered = true;
                $source = 'XhApiOpsAudit catalog fetch';
            } elseif (isset($serviceMethods[$method])) {
                $isCovered = true;
                $source = 'App\\Services\\TgService::' . $endpoint['method'];
            }

            if ($isCovered) {
                $covered++;
            } elseif (!empty($endpoint['required_for_ops'])) {
                $requiredMissing[] = $endpoint['path'];
            }

            $rows[] = [
                'path' => $endpoint['path'],
                'title' => $endpoint['title'],
                'method' => $endpoint['method'],
                'required_for_ops' => !empty($endpoint['required_for_ops']) ? 1 : 0,
                'covered' => $isCovered ? 1 : 0,
                'coverage_source' => $source,
            ];
        }

        $this->addIssue($issues, 'critical', 'xh_required_endpoint_not_wrapped', 'Required XH documented endpoints are not wrapped locally.', count($requiredMissing), implode(', ', $requiredMissing));

        return [
            'documented_count' => count($this->documentedEndpoints),
            'covered_count' => $covered,
            'required_missing_count' => count($requiredMissing),
            'endpoints' => $rows,
        ];
    }

    protected function pg51ConfirmationStatus($opsReviewDir)
    {
        $opsReviewDir = $this->resolvePath($opsReviewDir);
        $confirmationDir = $opsReviewDir . '/xh-upstream-confirmation';
        $reviewDir = $confirmationDir . '/review';
        $manifestPath = $confirmationDir . '/manifest.json';
        $gatePath = $reviewDir . '/confirmation_gate.json';
        $readinessPath = $reviewDir . '/review_readiness.json';
        $pendingSummaryPath = $reviewDir . '/pending_pool_summary.json';
        $sameDirPendingSummaryPath = $confirmationDir . '/pending_pool_summary.json';

        $status = [
            'ops_review_dir' => $opsReviewDir,
            'confirmation_dir' => $confirmationDir,
            'exists' => is_dir($confirmationDir),
            'manifest_exists' => is_file($manifestPath),
            'gate_exists' => is_file($gatePath),
            'review_readiness_exists' => is_file($readinessPath),
            'pending_pool_summary_exists' => is_file($pendingSummaryPath) || is_file($sameDirPendingSummaryPath),
            'import_gate' => 'unknown',
            'review_readiness' => 'unknown',
            'auto_import_gate' => 'unknown',
            'remaining' => null,
            'platform_not_confirmed' => null,
            'platform_groups' => null,
            'mapped_but_xh_not_matched' => null,
            'collision_manual_review' => null,
            'category_conflict_fast_check' => null,
            'safe_auto_import_candidates' => null,
            'pending_pool_ready' => null,
            'pending_pool_upstream_plain' => null,
            'pending_pool_conflict_union' => null,
            'pending_pool_reconciliation' => [],
            'pending_pool_conflict_union_detail' => [],
            'source_csv_integrity' => [],
            'source_csv_integrity_failures' => 0,
            'source_csv_integrity_summary' => '',
            'dry_run_candidates_exists' => false,
            'dry_run_candidates_blocked' => null,
            'dry_run_candidates_dry_run_allowed' => null,
            'dry_run_candidates_command_count' => 0,
            'dry_run_candidates_blocked_reason' => '',
            'dry_run_candidate_total' => 0,
            'dry_run_command_safety' => [],
            'dry_run_command_safety_failures' => 0,
            'dry_run_command_safety_summary' => '',
            'dry_run_allowed' => false,
            'database_import_allowed_now' => false,
            'summary' => '',
            'pending_pool_summary' => [],
            'pending_pool_classification' => [],
        ];

        if (is_file($manifestPath)) {
            $manifest = $this->readJsonFile($manifestPath, 'XH confirmation manifest');
            $this->applyPg51Counts($status, $manifest['counts'] ?? []);
            $this->inferPg51Readiness($status);
        }

        if (is_file($gatePath)) {
            $gate = $this->readJsonFile($gatePath, 'XH confirmation gate');
            $status['import_gate'] = $gate['import_gate'] ?? $status['import_gate'];
            $this->applyPg51Counts($status, $gate['counts'] ?? []);
            if (!empty($gate['pending_pool_summary']) && is_array($gate['pending_pool_summary'])) {
                $status['pending_pool_summary'] = $gate['pending_pool_summary'];
                $this->applyPg51PendingPoolSummary($status);
            }
            $status['pending_pool_classification'] = $gate['pending_pool_classification'] ?? $status['pending_pool_classification'];
            $status['source_csv_integrity'] = $gate['source_csv_integrity'] ?? $status['source_csv_integrity'];
            $status['source_csv_integrity_failures'] = (int)($gate['counts']['source_csv_integrity_failures'] ?? $status['source_csv_integrity_failures']);
            if (!empty($gate['review_readiness']) && is_array($gate['review_readiness'])) {
                $this->applyPg51Readiness($status, $gate['review_readiness']);
            } else {
                $this->inferPg51Readiness($status);
            }
        }

        if (is_file($readinessPath)) {
            $readiness = $this->readJsonFile($readinessPath, 'XH confirmation review readiness');
            $this->applyPg51Readiness($status, $readiness);
        }

        if (is_file($pendingSummaryPath)) {
            $status['pending_pool_summary'] = $this->readJsonFile($pendingSummaryPath, 'XH pending pool summary');
            $this->applyPg51PendingPoolSummary($status);
        } elseif (is_file($sameDirPendingSummaryPath)) {
            $status['pending_pool_summary'] = $this->readJsonFile($sameDirPendingSummaryPath, 'XH pending pool summary');
            $this->applyPg51PendingPoolSummary($status);
        }
        $dryRunCandidatesPath = $reviewDir . '/dry_run_candidates.json';
        $status['dry_run_candidates_exists'] = is_file($dryRunCandidatesPath);
        if (is_file($dryRunCandidatesPath)) {
            $dryRunCandidates = $this->readJsonFile($dryRunCandidatesPath, 'XH dry-run candidates');
            $this->applyPg51DryRunCandidates($status, $dryRunCandidates);
        }

        if (is_dir($confirmationDir)) {
            $sourceCsvIntegrity = $this->pg51SourceCsvIntegrity($confirmationDir, $status);
            $status['source_csv_integrity'] = $sourceCsvIntegrity;
            $status['source_csv_integrity_failures'] = (int)($sourceCsvIntegrity['failure_count'] ?? 0);
            $status['source_csv_integrity_summary'] = $this->pg51SourceCsvIntegritySummary($sourceCsvIntegrity);
        }

        return $status;
    }

    protected function applyPg51PendingPoolSummary(array &$status)
    {
        $summary = $status['pending_pool_summary'] ?? [];
        if (!is_array($summary) || !$summary) {
            return;
        }

        $status['pending_pool_ready'] = $summary['ready'] ?? $status['pending_pool_ready'];
        $status['pending_pool_upstream_plain'] = $summary['upstream_plain'] ?? $status['pending_pool_upstream_plain'];
        $status['pending_pool_conflict_union'] = $summary['conflict_union'] ?? $status['pending_pool_conflict_union'];
        $status['pending_pool_reconciliation'] = $summary['reconciliation'] ?? ($status['pending_pool_reconciliation'] ?? []);
        $status['pending_pool_conflict_union_detail'] = $summary['conflict_union_detail'] ?? ($status['pending_pool_conflict_union_detail'] ?? []);
    }

    protected function applyPg51DryRunCandidates(array &$status, array $dryRunCandidates)
    {
        $commands = $dryRunCandidates['commands'] ?? [];
        $commandCount = 0;
        if (is_array($commands)) {
            foreach ($commands as $command) {
                if (trim((string)$command) !== '') {
                    $commandCount++;
                }
            }
        }

        $status['dry_run_candidates_blocked'] = (bool)($dryRunCandidates['blocked'] ?? false);
        $status['dry_run_candidates_dry_run_allowed'] = (bool)($dryRunCandidates['dry_run_allowed'] ?? false);
        $status['dry_run_candidates_command_count'] = $commandCount;
        $status['dry_run_candidates_blocked_reason'] = (string)($dryRunCandidates['blocked_reason'] ?? '');
        $candidateCounts = $dryRunCandidates['candidate_counts'] ?? [];
        if (is_array($candidateCounts)) {
            $status['dry_run_candidate_total'] = (int)($candidateCounts['total'] ?? $status['dry_run_candidate_total']);
        }
        $commandSafety = $dryRunCandidates['command_safety'] ?? [];
        if (is_array($commandSafety)) {
            $status['dry_run_command_safety'] = $commandSafety;
            $status['dry_run_command_safety_failures'] = (int)($commandSafety['failure_count'] ?? 0);
            $status['dry_run_command_safety_summary'] = $this->pg51DryRunCommandSafetySummary($commandSafety);
        }
        if (array_key_exists('dry_run_allowed', $dryRunCandidates)) {
            $status['dry_run_allowed'] = (bool)$dryRunCandidates['dry_run_allowed'];
        }
    }

    protected function applyPg51Counts(array &$status, array $counts)
    {
        $status['remaining'] = $counts['manifest_remaining'] ?? ($counts['remaining'] ?? $status['remaining']);
        $status['platform_not_confirmed'] = $counts['platform_not_confirmed'] ?? $status['platform_not_confirmed'];
        $status['platform_groups'] = $counts['platform_groups'] ?? $status['platform_groups'];
        $status['mapped_but_xh_not_matched'] = $counts['mapped_but_xh_not_matched'] ?? $status['mapped_but_xh_not_matched'];
        $status['collision_manual_review'] = $counts['collision_manual_review'] ?? $status['collision_manual_review'];
        $status['category_conflict_fast_check'] = $counts['category_conflict_fast_check'] ?? $status['category_conflict_fast_check'];
        $status['safe_auto_import_candidates'] = $counts['safe_auto_import_candidates'] ?? $status['safe_auto_import_candidates'];
    }

    protected function inferPg51Readiness(array &$status)
    {
        $remaining = $status['remaining'];
        $safeAuto = $status['safe_auto_import_candidates'];
        if ($remaining !== null && $safeAuto !== null && (int)$remaining > 0 && (int)$safeAuto === 0) {
            $status['import_gate'] = $status['import_gate'] === 'unknown' ? 'waiting_for_xh_confirmation' : $status['import_gate'];
            $status['review_readiness'] = 'HOLD';
            $status['auto_import_gate'] = 'HOLD';
            $status['dry_run_allowed'] = false;
            $status['database_import_allowed_now'] = false;
            $status['summary'] = sprintf(
                'HOLD: safe_auto_import_candidates=%s; pending confirmation pool remaining=%s.',
                $this->displayCount($safeAuto),
                $this->displayCount($remaining)
            );
        }
    }

    protected function applyPg51Readiness(array &$status, array $readiness)
    {
        $status['review_readiness'] = $readiness['state'] ?? $status['review_readiness'];
        $status['import_gate'] = $readiness['import_gate'] ?? $status['import_gate'];
        $status['auto_import_gate'] = $readiness['auto_import_gate'] ?? $status['auto_import_gate'];
        $status['dry_run_allowed'] = (bool)($readiness['dry_run_allowed'] ?? false);
        $status['database_import_allowed_now'] = (bool)($readiness['database_import_allowed_now'] ?? false);
        $status['summary'] = $readiness['summary'] ?? $status['summary'];
    }

    protected function pg51ConfirmationCsvRow(array $status)
    {
        return [
            'exists' => !empty($status['exists']) ? 1 : 0,
            'manifest_exists' => !empty($status['manifest_exists']) ? 1 : 0,
            'gate_exists' => !empty($status['gate_exists']) ? 1 : 0,
            'review_readiness_exists' => !empty($status['review_readiness_exists']) ? 1 : 0,
            'import_gate' => $status['import_gate'] ?? 'unknown',
            'review_readiness' => $status['review_readiness'] ?? 'unknown',
            'auto_import_gate' => $status['auto_import_gate'] ?? 'unknown',
            'remaining' => $this->displayCount($status['remaining'] ?? null),
            'platform_not_confirmed' => $this->displayCount($status['platform_not_confirmed'] ?? null),
            'mapped_but_xh_not_matched' => $this->displayCount($status['mapped_but_xh_not_matched'] ?? null),
            'collision_manual_review' => $this->displayCount($status['collision_manual_review'] ?? null),
            'category_conflict_fast_check' => $this->displayCount($status['category_conflict_fast_check'] ?? null),
            'safe_auto_import_candidates' => $this->displayCount($status['safe_auto_import_candidates'] ?? null),
            'pending_pool_ready' => $this->displayCount($status['pending_pool_ready'] ?? null),
            'pending_pool_upstream_plain' => $this->displayCount($status['pending_pool_upstream_plain'] ?? null),
            'pending_pool_conflict_union' => $this->displayCount($status['pending_pool_conflict_union'] ?? null),
            'dry_run_candidate_total' => (int)($status['dry_run_candidate_total'] ?? 0),
            'dry_run_command_safety_failures' => (int)($status['dry_run_command_safety_failures'] ?? 0),
            'dry_run_command_safety_summary' => $status['dry_run_command_safety_summary'] ?? '',
            'dry_run_allowed' => !empty($status['dry_run_allowed']) ? 1 : 0,
            'database_import_allowed_now' => !empty($status['database_import_allowed_now']) ? 1 : 0,
            'source_csv_integrity_failures' => (int)($status['source_csv_integrity_failures'] ?? 0),
            'source_csv_integrity_summary' => $status['source_csv_integrity_summary'] ?? '',
            'summary' => $status['summary'] ?? '',
        ];
    }

    protected function gameCategoryCounts(array $games)
    {
        $counts = [];
        foreach ($games as $game) {
            $category = $this->typeMap[(int)($game['gameType'] ?? 0)] ?? 'unknown';
            $counts[$category] = ($counts[$category] ?? 0) + 1;
        }
        arsort($counts);

        return $counts;
    }

    protected function sampleMissing(array $rows)
    {
        return implode(' | ', array_map(function ($row) {
            return $row['id'] . ':' . $row['platform_name'] . '/' . $row['category_id'] . '/' . $row['game_code'] . '/' . $row['name'] . '(' . $row['reason'] . ')';
        }, array_slice($rows, 0, 20)));
    }

    protected function addIssue(array &$issues, $severity, $code, $message, $count, $sample = '')
    {
        $count = (int)$count;
        if ($count <= 0) {
            return;
        }

        $issues[] = [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'count' => $count,
            'sample' => $sample,
        ];
    }

    protected function addPg51ConfirmationIssues(array &$issues, array $confirmation)
    {
        if (empty($confirmation['exists']) && empty($confirmation['manifest_exists']) && empty($confirmation['gate_exists'])) {
            return;
        }

        $this->addIssue(
            $issues,
            'critical',
            'pg51_confirmation_gate_missing',
            'PG51 confirmation package exists but confirmation_gate.json is missing.',
            !empty($confirmation['manifest_exists']) && empty($confirmation['gate_exists']) ? 1 : 0,
            $confirmation['import_gate'] ?? 'unknown'
        );

        $ready = (int)($confirmation['pending_pool_ready'] ?? 0);
        $this->addIssue(
            $issues,
            'critical',
            'pg51_dry_run_candidates_missing',
            'PG51 confirmation has READY rows but dry_run_candidates.json is missing.',
            $ready > 0 && empty($confirmation['dry_run_candidates_exists']) ? 1 : 0,
            'ready_for_dry_run=' . $ready
        );

        $commandCount = (int)($confirmation['dry_run_candidates_command_count'] ?? 0);
        $dryRunAllowed = !empty($confirmation['dry_run_allowed']);
        $dryRunBlocked = !$dryRunAllowed
            || !empty($confirmation['dry_run_candidates_blocked'])
            || count($this->pg51ReconciliationFailures($confirmation['pending_pool_reconciliation'] ?? [])) > 0
            || count($this->pg51SourceCsvIntegrityFailures($confirmation['source_csv_integrity'] ?? [])) > 0;
        $this->addIssue(
            $issues,
            'critical',
            'pg51_blocked_dry_run_candidates_have_commands',
            'PG51 dry-run candidates are blocked but still contain executable SyncGame commands.',
            !empty($confirmation['dry_run_candidates_exists']) && $dryRunBlocked && $commandCount > 0 ? 1 : 0,
            'commands=' . $commandCount . ' blocked_reason=' . ($confirmation['dry_run_candidates_blocked_reason'] ?? '')
        );
        $this->addIssue(
            $issues,
            'critical',
            'pg51_dry_run_allowed_without_commands',
            'PG51 confirmation allows dry-run but dry_run_candidates.json has no executable SyncGame command.',
            !empty($confirmation['dry_run_candidates_exists']) && $dryRunAllowed && $ready > 0 && $commandCount === 0 ? 1 : 0,
            'ready_for_dry_run=' . $ready
        );
        $candidateTotal = (int)($confirmation['dry_run_candidate_total'] ?? 0);
        $this->addIssue(
            $issues,
            'critical',
            'pg51_dry_run_candidate_count_mismatch',
            'PG51 dry-run candidate count must equal READY rows from confirmation_gate.json.',
            !empty($confirmation['dry_run_candidates_exists']) && $ready > 0 && $candidateTotal !== $ready ? 1 : 0,
            'ready_for_dry_run=' . $ready . ' candidate_total=' . $candidateTotal
        );
        $commandSafetyFailures = (int)($confirmation['dry_run_command_safety_failures'] ?? 0);
        $this->addIssue(
            $issues,
            'critical',
            'pg51_dry_run_command_safety_failed',
            'PG51 dry-run command safety failed; generated commands must be dry-run only and must never include import/publish.',
            $commandSafetyFailures,
            $confirmation['dry_run_command_safety_summary'] ?? ''
        );
        $this->addIssue(
            $issues,
            'critical',
            'pg51_confirmation_database_import_not_locked',
            'PG51 confirmation review output must keep database_import_allowed_now=false until a separate approved import turn.',
            !empty($confirmation['database_import_allowed_now']) ? 1 : 0,
            'database_import_allowed_now=true'
        );

        $remaining = (int)($confirmation['remaining'] ?? 0);
        $this->addIssue(
            $issues,
            'critical',
            'pg51_confirmation_import_allowed_with_pending_pool',
            'PG51 confirmation still has pending rows but database_import_allowed_now is true.',
            $remaining > 0 && !empty($confirmation['database_import_allowed_now']) ? 1 : 0,
            'remaining=' . $remaining
        );

        $reconciliationFailures = $this->pg51ReconciliationFailures($confirmation['pending_pool_reconciliation'] ?? []);
        $this->addIssue(
            $issues,
            'critical',
            'pg51_pending_pool_reconciliation_failed',
            'PG51 pending pool reconciliation failed.',
            count($reconciliationFailures),
            implode(', ', $reconciliationFailures)
        );

        $sourceCsvFailures = $this->pg51SourceCsvIntegrityFailures($confirmation['source_csv_integrity'] ?? []);
        $this->addIssue(
            $issues,
            'critical',
            'pg51_confirmation_source_csv_integrity_failed',
            'PG51 confirmation source CSV integrity failed; do not dry-run or import until row counts and identity fields are fixed.',
            count($sourceCsvFailures),
            implode(' | ', array_slice($sourceCsvFailures, 0, 12))
        );

        $detail = $confirmation['pending_pool_conflict_union_detail'] ?? [];
        if (is_array($detail) && $detail) {
            $formulaUnion = (int)($detail['category_conflict_fast_check'] ?? 0)
                + (int)($detail['collision_manual_review'] ?? 0)
                - (int)($detail['overlap'] ?? 0);
            $this->addIssue(
                $issues,
                'critical',
                'pg51_pending_pool_conflict_union_mismatch',
                'PG51 pending pool conflict union detail does not reconcile.',
                $formulaUnion !== (int)($detail['union'] ?? $formulaUnion) ? 1 : 0,
                'formula=' . $formulaUnion . ' union=' . ($detail['union'] ?? 'missing')
            );
        }
    }

    protected function pg51ReconciliationFailures($reconciliation)
    {
        if (!is_array($reconciliation)) {
            return [];
        }

        $failures = [];
        foreach ($reconciliation as $name => $passed) {
            if ($name === 'database_import_allowed_now') {
                continue;
            }
            if ($passed === false || $passed === 0 || $passed === '0') {
                $failures[] = $name;
            }
        }

        return $failures;
    }

    protected function pg51SourceCsvIntegrity($confirmationDir, array $counts)
    {
        $files = [];
        $issues = [];
        $failureCount = 0;

        foreach ($this->confirmationFiles as $file => $config) {
            $path = $confirmationDir . '/' . $file;
            $expected = (int)($counts[$config['manifest_key']] ?? 0);
            $details = $this->inspectPg51ConfirmationCsv($path, $config['required_columns'], $expected);
            $files[$file] = $details;
            foreach (($details['issues'] ?? []) as $issue) {
                $issues[] = $issue;
            }
            $failureCount += (int)($details['failure_count'] ?? 0);
        }

        return [
            'generated_at' => date('c'),
            'failure_count' => $failureCount,
            'files' => $files,
            'issues' => $issues,
        ];
    }

    protected function inspectPg51ConfirmationCsv($path, array $requiredColumns, $expectedRows)
    {
        $file = basename($path);
        $issues = [];
        $details = [
            'exists' => is_file($path),
            'expected_manifest_rows' => (int)$expectedRows,
            'physical_data_rows' => 0,
            'parsed_rows' => 0,
            'header_count' => 0,
            'duplicate_header_columns' => [],
            'missing_required_columns' => [],
            'blank_required_identity_rows' => 0,
            'duplicate_identity_key_count' => 0,
            'duplicate_identity_key_samples' => [],
            'approval_column_exists' => false,
            'approved_for_import_true_rows' => 0,
            'approved_for_import_blank_rows' => 0,
            'short_rows' => 0,
            'long_rows' => 0,
            'parse_shape_mismatch_rows' => 0,
            'failure_count' => 0,
            'issues' => [],
        ];

        if (!is_file($path)) {
            $issues[] = $this->pg51SourceCsvIssue($file, 0, 'missing_confirmation_csv', 'Confirmation CSV is missing.');
            $details['issues'] = $issues;
            $details['failure_count'] = count($issues);
            return $details;
        }

        $headers = $this->csvHeaders($path);
        $details['header_count'] = count($headers);
        $details['physical_data_rows'] = $this->physicalCsvDataRows($path);
        $details['duplicate_header_columns'] = $this->duplicateValues($headers);
        $details['approval_column_exists'] = in_array('approved_for_import', $headers, true);

        if ($details['duplicate_header_columns']) {
            $issues[] = $this->pg51SourceCsvIssue(
                $file,
                1,
                'duplicate_header_columns',
                'CSV header contains duplicate columns: ' . implode(', ', $details['duplicate_header_columns'])
            );
        }

        foreach ($requiredColumns as $column) {
            if (!in_array($column, $headers, true)) {
                $details['missing_required_columns'][] = $column;
            }
        }
        if ($details['missing_required_columns']) {
            $issues[] = $this->pg51SourceCsvIssue(
                $file,
                1,
                'missing_required_columns',
                'CSV required columns missing: ' . implode(', ', $details['missing_required_columns'])
            );
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (!$lines) {
            $issues[] = $this->pg51SourceCsvIssue($file, 0, 'unreadable_confirmation_csv', 'Confirmation CSV could not be read.');
            $details['issues'] = $issues;
            $details['failure_count'] = count($issues);
            return $details;
        }

        $rawHeaders = $headers;
        $headerCount = count($rawHeaders);
        $rowNum = 1;
        $seenIdentityKeys = [];
        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) === '') {
                continue;
            }
            $rowNum++;
            $data = str_getcsv($line);
            $details['parsed_rows']++;
            $dataCount = count($data);
            if ($dataCount < $headerCount) {
                $details['short_rows']++;
            } elseif ($dataCount > $headerCount) {
                $details['long_rows']++;
            }

            $row = [];
            foreach ((array)$rawHeaders as $i => $header) {
                $row[$header] = $data[$i] ?? '';
            }
            if ($details['approval_column_exists']) {
                $approvalValue = trim((string)($row['approved_for_import'] ?? ''));
                if ($approvalValue === '') {
                    $details['approved_for_import_blank_rows']++;
                } elseif ($this->truthy($approvalValue)) {
                    $details['approved_for_import_true_rows']++;
                }
            }

            $identityKey = $this->csvIdentityKey($row, $requiredColumns);
            if ($identityKey !== '') {
                if (isset($seenIdentityKeys[$identityKey])) {
                    $details['duplicate_identity_key_count']++;
                    if (count($details['duplicate_identity_key_samples']) < 10) {
                        $details['duplicate_identity_key_samples'][] = 'row=' . $rowNum . ' key=' . $identityKey;
                    }
                } else {
                    $seenIdentityKeys[$identityKey] = true;
                }
            }

            foreach ($requiredColumns as $column) {
                if (trim((string)($row[$column] ?? '')) === '') {
                    $details['blank_required_identity_rows']++;
                    break;
                }
            }
        }

        $details['parse_shape_mismatch_rows'] = $details['long_rows'];
        if ((int)$expectedRows > 0 && $details['physical_data_rows'] !== (int)$expectedRows) {
            $issues[] = $this->pg51SourceCsvIssue(
                $file,
                0,
                'manifest_source_row_count_mismatch',
                'Manifest count does not match source CSV physical rows: expected=' . (int)$expectedRows . ' physical=' . $details['physical_data_rows']
            );
        }
        if ($details['physical_data_rows'] !== $details['parsed_rows']) {
            $issues[] = $this->pg51SourceCsvIssue(
                $file,
                0,
                'physical_parsed_row_count_mismatch',
                'CSV parser row count does not match physical rows; possible malformed quoting or delimiter issue: physical=' . $details['physical_data_rows'] . ' parsed=' . $details['parsed_rows']
            );
        }
        if ($details['long_rows'] > 0) {
            $issues[] = $this->pg51SourceCsvIssue(
                $file,
                0,
                'csv_field_count_mismatch_long',
                'CSV rows have more fields than headers and may be shifted: long=' . $details['long_rows']
            );
        }
        if ($details['blank_required_identity_rows'] > 0) {
            $issues[] = $this->pg51SourceCsvIssue(
                $file,
                0,
                'blank_required_identity_fields',
                'CSV rows have blank required identity fields: count=' . $details['blank_required_identity_rows']
            );
        }
        if ($details['duplicate_identity_key_count'] > 0) {
            $issues[] = $this->pg51SourceCsvIssue(
                $file,
                0,
                'duplicate_required_identity_keys',
                'CSV rows repeat the same required identity fields: count=' . $details['duplicate_identity_key_count'] . ' samples=' . implode(' | ', $details['duplicate_identity_key_samples'])
            );
        }

        $details['issues'] = $issues;
        $details['failure_count'] = count($issues);
        return $details;
    }

    protected function duplicateValues(array $values)
    {
        $seen = [];
        $duplicates = [];
        foreach ($values as $value) {
            $key = strtolower(trim((string)$value));
            if ($key === '') {
                continue;
            }
            if (isset($seen[$key])) {
                $duplicates[$key] = $value;
                continue;
            }
            $seen[$key] = true;
        }

        return array_values($duplicates);
    }

    protected function csvIdentityKey(array $row, array $columns)
    {
        $parts = [];
        foreach ($columns as $column) {
            $value = trim((string)($row[$column] ?? ''));
            if ($value === '') {
                return '';
            }
            $parts[] = strtolower($value);
        }

        return implode('|', $parts);
    }

    protected function truthy($value)
    {
        return in_array(strtolower(trim((string)$value)), ['1', 'true', 'yes', 'y', 'approved'], true);
    }

    protected function pg51SourceCsvIssue($file, $row, $code, $message)
    {
        return [
            'severity' => 'critical',
            'source_file' => $file,
            'row' => $row,
            'code' => $code,
            'message' => $message,
        ];
    }

    protected function pg51SourceCsvIntegrityFailures($integrity)
    {
        if (!is_array($integrity)) {
            return [];
        }

        $failures = [];
        foreach (($integrity['issues'] ?? []) as $issue) {
            $failures[] = ($issue['source_file'] ?? 'unknown') . ':' . ($issue['code'] ?? 'unknown') . ':' . ($issue['message'] ?? '');
        }

        return $failures;
    }

    protected function pg51SourceCsvIntegritySummary(array $integrity)
    {
        $failures = $this->pg51SourceCsvIntegrityFailures($integrity);
        if (!$failures) {
            return 'source_csv_integrity=clean';
        }

        return 'source_csv_integrity=failed ' . implode(' | ', array_slice($failures, 0, 8));
    }

    protected function pg51DryRunCommandSafetySummary(array $safety)
    {
        $issues = $safety['issues'] ?? [];
        if (!is_array($issues) || !$issues) {
            return 'dry_run_command_safety=clean';
        }

        return 'dry_run_command_safety=failed ' . implode(' | ', array_slice($issues, 0, 8));
    }

    protected function csvHeaders($path)
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (!$lines) {
            return [];
        }
        $headerLine = preg_replace('/^\xEF\xBB\xBF/', '', (string)$lines[0]);
        $headers = str_getcsv($headerLine);
        if (!$headers) {
            return [];
        }
        return array_map(function ($header) {
            $header = preg_replace('/^\xEF\xBB\xBF/', '', (string)$header);
            return trim($header, " \t\n\r\0\x0B\"");
        }, $headers);
    }

    protected function physicalCsvDataRows($path)
    {
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (!$lines) {
            return 0;
        }

        $count = 0;
        foreach (array_slice($lines, 1) as $line) {
            if (trim($line) !== '') {
                $count++;
            }
        }

        return $count;
    }

    protected function issueCounts(array $issues)
    {
        $counts = [];
        foreach ($issues as $issue) {
            $counts[$issue['severity']] = ($counts[$issue['severity']] ?? 0) + 1;
        }

        return $counts;
    }

    protected function writeJson($path, $data)
    {
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT;
        if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
            $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
        }
        $json = json_encode($data, $flags);
        if ($json === false) {
            throw new \RuntimeException('JSON encode failed for ' . $path . ': ' . json_last_error_msg());
        }
        if (file_put_contents($path, $json) === false) {
            throw new \RuntimeException('JSON could not be written: ' . $path);
        }
        $check = json_decode((string)file_get_contents($path), true);
        if (!is_array($check)) {
            throw new \RuntimeException('JSON self-check failed after writing ' . $path . ': ' . json_last_error_msg());
        }
    }

    protected function writeCsv($path, array $rows, array $headers)
    {
        $fh = fopen($path, 'w');
        fputcsv($fh, $headers);
        foreach ($rows as $row) {
            fputcsv($fh, array_map(function ($header) use ($row) {
                return $row[$header] ?? '';
            }, $headers));
        }
        fclose($fh);
    }

    protected function displayCount($value)
    {
        return $value === null ? 'unknown' : (string)$value;
    }

    protected function resolvePath($path)
    {
        if (preg_match('/^([A-Za-z]:)?[\/\\\\]/', $path)) {
            return $path;
        }

        return base_path($path);
    }

    protected function readJsonFile($path, $label)
    {
        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new \RuntimeException($label . ' JSON could not be read: ' . $path);
        }

        $contents = preg_replace('/^\xEF\xBB\xBF/', '', $contents);
        $data = json_decode($contents, true);
        if (!is_array($data)) {
            throw new \RuntimeException($label . ' JSON invalid: ' . $path . ' (' . json_last_error_msg() . ')');
        }

        return $data;
    }

    protected function codeNorm($value)
    {
        return strtolower(trim((string)$value, " \t\n\r\0\x0B'\""));
    }

    protected function normalizeLocalCategory($category)
    {
        $category = trim((string)$category);
        if (in_array($category, ['lhc', 'jsc', 'jwc', 'qkc'], true)) {
            return 'lottery';
        }

        return $category;
    }
}
