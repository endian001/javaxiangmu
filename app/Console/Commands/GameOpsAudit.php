<?php

namespace App\Console\Commands;

use App\Models\Api;
use App\Models\GameList;
use App\Models\SystemConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GameOpsAudit extends Command
{
    protected $signature = 'GameOpsAudit
        {--out-dir=storage/app/game-ops-audit : Directory for audit reports}
        {--pg51-report=storage/app/pg51-sync/pg51_operational_gap_report.json : Optional PG51 gap report}
        {--ops-review-dir=storage/app/pg51-sync/ops-review : Optional PG51 operations review directory}
        {--pg51-only : Only audit the PG51 confirmation gate without database queries}';

    protected $description = 'Generate read-only operational health reports for game catalog and XH integration';

    protected $knownCategories = [
        'realbet',
        'fishing',
        'concise',
        'lottery',
        'lhc',
        'jsc',
        'jwc',
        'qkc',
        'sport',
        'joker',
        'gaming',
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

    public function handle()
    {
        $outDir = $this->resolvePath($this->option('out-dir'));
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $pg51 = $this->pg51Status($this->option('pg51-report'), $this->option('ops-review-dir'));
        if ($this->option('pg51-only')) {
            return $this->handlePg51Only($outDir, $pg51);
        }

        $summary = $this->summary();
        $issues = $this->issues();
        $categoryCounts = $this->categoryCounts();
        $platformCounts = $this->platformCounts();
        $apiStatus = $this->apiStatus();
        $this->addPg51ConfirmationIssues($issues, $pg51['xh_confirmation_package'] ?? []);

        $report = [
            'generated_at' => date('c'),
            'summary' => $summary,
            'issue_counts' => $this->issueCounts($issues),
            'category_counts' => $categoryCounts,
            'platform_counts' => $platformCounts,
            'api_status' => $apiStatus,
            'pg51_status' => $pg51,
            'issues' => $issues,
        ];

        $this->writeJson($outDir . '/game_ops_audit.json', $report);
        $this->writeCsv($outDir . '/game_ops_issues.csv', $issues, [
            'severity',
            'code',
            'message',
            'count',
            'sample',
        ]);
        $this->writeCsv($outDir . '/game_category_counts.csv', $categoryCounts, ['category_id', 'enabled', 'total']);
        $this->writeCsv($outDir . '/game_platform_counts.csv', $platformCounts, ['platform_name', 'enabled', 'total']);

        $this->info(sprintf(
            'enabled=%d total=%d hot=%d issues=%d critical=%d warnings=%d',
            $summary['enabled_games'],
            $summary['total_games'],
            $summary['hot_games'],
            count($issues),
            $report['issue_counts']['critical'] ?? 0,
            $report['issue_counts']['warning'] ?? 0
        ));
        $confirmation = $pg51['xh_confirmation_package'] ?? [];
        if (!empty($confirmation['exists']) || !empty($confirmation['manifest_exists']) || !empty($confirmation['review_exists'])) {
                $this->comment(sprintf(
                'PG51 confirmation readiness=%s auto_import_gate=%s remaining=%s safe_auto_import_candidates=%s ready=%s upstream_plain=%s conflict_union=%s platform_not_confirmed=%s mapped_but_xh_not_matched=%s collision_manual_review=%s category_conflict_fast_check=%s import_gate=%s',
                $confirmation['review_readiness'] ?? 'unknown',
                $confirmation['auto_import_gate'] ?? 'unknown',
                $this->displayCount($confirmation['remaining'] ?? null),
                $this->displayCount($confirmation['safe_auto_import_candidates'] ?? null),
                $this->displayCount($confirmation['pending_pool_ready'] ?? null),
                $this->displayCount($confirmation['pending_pool_upstream_plain'] ?? null),
                $this->displayCount($confirmation['pending_pool_conflict_union'] ?? null),
                $this->displayCount($confirmation['platform_not_confirmed'] ?? null),
                $this->displayCount($confirmation['mapped_but_xh_not_matched'] ?? null),
                $this->displayCount($confirmation['collision_manual_review'] ?? null),
                $this->displayCount($confirmation['category_conflict_fast_check'] ?? null),
                $confirmation['import_gate'] ?? 'unknown'
            ));
            if (!empty($confirmation['review_readiness_summary'])) {
                $this->warn($confirmation['review_readiness_summary']);
            }
        }
        $this->comment('Reports: ' . $outDir);

        return 0;
    }

    protected function handlePg51Only($outDir, array $pg51)
    {
        $issues = [];
        $this->addPg51ConfirmationIssues($issues, $pg51['xh_confirmation_package'] ?? []);
        $report = [
            'generated_at' => date('c'),
            'mode' => 'pg51-only',
            'issue_counts' => $this->issueCounts($issues),
            'pg51_status' => $pg51,
            'issues' => $issues,
        ];

        $this->writeJson($outDir . '/game_ops_audit_pg51_only.json', $report);
        $this->writeCsv($outDir . '/game_ops_pg51_only_issues.csv', $issues, [
            'severity',
            'code',
            'message',
            'count',
            'sample',
        ]);

        $confirmation = $pg51['xh_confirmation_package'] ?? [];
        $this->info(sprintf(
            'mode=pg51-only issues=%d critical=%d warnings=%d readiness=%s auto_import_gate=%s remaining=%s ready=%s database_import_allowed_now=%s source_csv_integrity_failures=%d',
            count($issues),
            $report['issue_counts']['critical'] ?? 0,
            $report['issue_counts']['warning'] ?? 0,
            $confirmation['review_readiness'] ?? 'unknown',
            $confirmation['auto_import_gate'] ?? 'unknown',
            $this->displayCount($confirmation['remaining'] ?? null),
            $this->displayCount($confirmation['ready_for_dry_run'] ?? null),
            !empty($confirmation['database_import_allowed_now']) ? 'yes' : 'no',
            (int)($confirmation['source_csv_integrity_failures'] ?? 0)
        ));
        if (!empty($confirmation['review_readiness_summary'])) {
            $this->warn($confirmation['review_readiness_summary']);
        }
        if (!empty($confirmation['source_csv_integrity_summary'])) {
            $this->comment($confirmation['source_csv_integrity_summary']);
        }
        $this->comment('Reports: ' . $outDir);

        return 0;
    }

    protected function summary()
    {
        $enabled = $this->publicGames();

        return [
            'total_games' => (int)GameList::count(),
            'enabled_games' => (int)(clone $enabled)->count(),
            'hot_games' => (int)(clone $enabled)->where('is_hot', 1)->count(),
            'enabled_platforms' => (int)(clone $enabled)->distinct('platform_name')->count('platform_name'),
            'enabled_categories' => (int)(clone $enabled)->distinct('category_id')->count('category_id'),
            'empty_api_logo_img' => (int)(clone $enabled)->where(function ($query) {
                $query->whereNull('api_logo_img')->orWhere('api_logo_img', '');
            })->count(),
            'empty_mobile_img' => (int)(clone $enabled)->where(function ($query) {
                $query->whereNull('mobile_img')->orWhere('mobile_img', '');
            })->count(),
            'active_api_platforms' => (int)Api::where('state', 1)->where('app_state', 1)->count(),
            'game_api_configured' => SystemConfig::getValue('game_api') !== '',
            'merchant_account_configured' => SystemConfig::getValue('merchant_account') !== '',
            'api_secret_configured' => SystemConfig::getValue('api_secret') !== '',
            'site_app_enabled_but_not_public' => (int)$this->siteAppEnabledButNotPublic()->count(),
        ];
    }

    protected function issues()
    {
        $issues = [];
        $enabled = $this->publicGames();

        $this->addIssue($issues, 'critical', 'missing_game_api_config', 'XH game_api is not configured.', SystemConfig::getValue('game_api') === '' ? 1 : 0);
        $this->addIssue($issues, 'critical', 'missing_merchant_account', 'XH merchant_account is not configured.', SystemConfig::getValue('merchant_account') === '' ? 1 : 0);
        $this->addIssue($issues, 'critical', 'missing_api_secret', 'XH api_secret is not configured.', SystemConfig::getValue('api_secret') === '' ? 1 : 0);
        $this->addIssue($issues, 'warning', 'site_app_enabled_but_not_public', 'Games have site_state/app_state enabled but is_top is not public.', $this->siteAppEnabledButNotPublic()->count(), $this->sampleGames($this->siteAppEnabledButNotPublic()));

        $this->addIssue($issues, 'critical', 'enabled_game_without_platform', 'Enabled games with empty platform_name.', (clone $enabled)->where(function ($query) {
            $query->whereNull('platform_name')->orWhere('platform_name', '');
        })->count(), $this->sampleGames((clone $enabled)->where(function ($query) {
            $query->whereNull('platform_name')->orWhere('platform_name', '');
        })));

        $this->addIssue($issues, 'critical', 'enabled_game_without_code', 'Enabled games with empty game_code.', (clone $enabled)->where(function ($query) {
            $query->whereNull('game_code')->orWhere('game_code', '');
        })->count(), $this->sampleGames((clone $enabled)->where(function ($query) {
            $query->whereNull('game_code')->orWhere('game_code', '');
        })));

        $this->addIssue($issues, 'warning', 'enabled_game_without_api_logo_img', 'Enabled games with empty api_logo_img.', (clone $enabled)->where(function ($query) {
            $query->whereNull('api_logo_img')->orWhere('api_logo_img', '');
        })->count(), $this->sampleGames((clone $enabled)->where(function ($query) {
            $query->whereNull('api_logo_img')->orWhere('api_logo_img', '');
        })));

        $this->addIssue($issues, 'warning', 'enabled_game_without_mobile_img', 'Enabled games with empty mobile_img.', (clone $enabled)->where(function ($query) {
            $query->whereNull('mobile_img')->orWhere('mobile_img', '');
        })->count(), $this->sampleGames((clone $enabled)->where(function ($query) {
            $query->whereNull('mobile_img')->orWhere('mobile_img', '');
        })));

        $unknownCategories = (clone $enabled)
            ->whereNotIn('category_id', $this->knownCategories)
            ->select('category_id', DB::raw('COUNT(*) as count'))
            ->groupBy('category_id')
            ->get()
            ->map(function ($row) {
                return $row->category_id . ':' . $row->count;
            })
            ->implode(', ');
        $this->addIssue($issues, 'warning', 'unknown_enabled_category', 'Enabled games in unknown categories.', $unknownCategories === '' ? 0 : 1, $unknownCategories);

        $platforms = Api::select('api_code', 'state', 'app_state')->get()->keyBy(function ($api) {
            return strtoupper($api->api_code);
        });
        $badPlatforms = [];
        foreach ((clone $enabled)->select('platform_name', DB::raw('COUNT(*) as count'))->groupBy('platform_name')->get() as $row) {
            $code = strtoupper($row->platform_name);
            if (!isset($platforms[$code])) {
                $badPlatforms[] = $row->platform_name . ':' . $row->count . '(missing api)';
                continue;
            }
            $api = $platforms[$code];
            if ((int)$api->state !== 1 || (int)$api->app_state !== 1) {
                $badPlatforms[] = $row->platform_name . ':' . $row->count . '(api disabled)';
            }
        }
        $this->addIssue($issues, 'critical', 'enabled_games_on_disabled_or_missing_api', 'Enabled games whose platform API is disabled or missing.', count($badPlatforms), implode(', ', array_slice($badPlatforms, 0, 20)));

        $duplicates = GameList::select('platform_name', 'category_id', 'game_code', DB::raw('COUNT(*) as count'))
            ->whereNotIn('game_code', ['0', 'lobby'])
            ->groupBy('platform_name', 'category_id', 'game_code')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->map(function ($row) {
                return $row->platform_name . '/' . $row->category_id . '/' . $row->game_code . ':' . $row->count;
            })
            ->toArray();
        $this->addIssue($issues, 'warning', 'duplicate_platform_category_game_code', 'Duplicate platform/category/game_code rows.', count($duplicates), implode(', ', array_slice($duplicates, 0, 20)));

        $hotCount = (clone $enabled)->where('is_hot', 1)->count();
        $this->addIssue($issues, 'warning', 'low_hot_game_count', 'Hot game count is below 10.', $hotCount < 10 ? 1 : 0, 'hot=' . $hotCount);

        return $issues;
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

    protected function sampleGames($query)
    {
        return $query->orderBy('id')->limit(10)->get(['id', 'platform_name', 'category_id', 'game_code', 'name'])
            ->map(function ($row) {
                return $row->id . ':' . $row->platform_name . '/' . $row->category_id . '/' . $row->game_code . '/' . $row->name;
            })
            ->implode(' | ');
    }

    protected function categoryCounts()
    {
        $totals = GameList::select('category_id', DB::raw('COUNT(*) as total'))
            ->groupBy('category_id')
            ->pluck('total', 'category_id')
            ->toArray();
        $enabled = $this->publicGames()
            ->select('category_id', DB::raw('COUNT(*) as enabled'))
            ->groupBy('category_id')
            ->pluck('enabled', 'category_id')
            ->toArray();

        $rows = [];
        foreach ($totals as $category => $total) {
            $rows[] = [
                'category_id' => $category,
                'enabled' => (int)($enabled[$category] ?? 0),
                'total' => (int)$total,
            ];
        }

        usort($rows, function ($a, $b) {
            return $b['enabled'] <=> $a['enabled'];
        });

        return $rows;
    }

    protected function platformCounts()
    {
        $totals = GameList::select('platform_name', DB::raw('COUNT(*) as total'))
            ->groupBy('platform_name')
            ->pluck('total', 'platform_name')
            ->toArray();
        $enabled = $this->publicGames()
            ->select('platform_name', DB::raw('COUNT(*) as enabled'))
            ->groupBy('platform_name')
            ->pluck('enabled', 'platform_name')
            ->toArray();

        $rows = [];
        foreach ($totals as $platform => $total) {
            $rows[] = [
                'platform_name' => $platform,
                'enabled' => (int)($enabled[$platform] ?? 0),
                'total' => (int)$total,
            ];
        }

        usort($rows, function ($a, $b) {
            return $b['enabled'] <=> $a['enabled'];
        });

        return $rows;
    }

    protected function publicGames()
    {
        return GameList::where('site_state', 1)
            ->where('app_state', 1)
            ->where('is_top', 1);
    }

    protected function siteAppEnabledButNotPublic()
    {
        return GameList::where('site_state', 1)
            ->where('app_state', 1)
            ->where(function ($query) {
                $query->where('is_top', '<>', 1)->orWhereNull('is_top');
            });
    }

    protected function apiStatus()
    {
        return Api::orderBy('api_code')->get(['api_code', 'api_name', 'state', 'app_state'])
            ->map(function ($api) {
                return [
                    'api_code' => $api->api_code,
                    'api_name' => $api->api_name,
                    'state' => (int)$api->state,
                    'app_state' => (int)$api->app_state,
                ];
            })
            ->toArray();
    }

    protected function pg51Status($reportPath, $opsReviewDir)
    {
        $reportPath = $this->resolvePath($reportPath);
        $opsReviewDir = $this->resolvePath($opsReviewDir);
        $status = [
            'report_exists' => is_file($reportPath),
            'report_path' => $reportPath,
            'ops_review_dir_exists' => is_dir($opsReviewDir),
            'ops_review_dir' => $opsReviewDir,
            'ops_review_files' => [],
            'xh_confirmation_package' => [
                'exists' => false,
                'manifest_exists' => false,
                'remaining' => null,
                'platform_not_confirmed' => null,
                'platform_groups' => null,
                'mapped_but_xh_not_matched' => null,
                'collision_manual_review' => null,
                'category_conflict_fast_check' => null,
                'safe_auto_import_candidates' => null,
                'import_gate' => 'unknown',
                'auto_import_gate' => 'unknown',
                'review_readiness' => 'unknown',
                'review_readiness_summary' => '',
                'dry_run_allowed' => false,
                'database_import_allowed_now' => false,
                'review_exists' => false,
                'gate_exists' => false,
                'pending_pool_exists' => false,
                'pending_pool_summary_exists' => false,
                'dry_run_candidates_exists' => false,
                'dry_run_candidates_blocked' => null,
                'dry_run_candidates_dry_run_allowed' => null,
                'dry_run_candidates_command_count' => 0,
                'dry_run_candidates_blocked_reason' => '',
                'dry_run_candidate_total' => 0,
                'dry_run_command_safety' => [],
                'dry_run_command_safety_failures' => 0,
                'dry_run_command_safety_summary' => '',
                'pending_pool_review_rows' => null,
                'pending_pool_summary' => [],
                'pending_pool_classification' => [],
                'pending_pool_reconciliation' => [],
                'pending_pool_conflict_union_detail' => [],
                'source_csv_integrity' => [],
                'source_csv_integrity_failures' => 0,
                'source_csv_integrity_summary' => '',
                'pending_pool_ready' => null,
                'pending_pool_upstream_plain' => null,
                'pending_pool_conflict_union' => null,
                'platform_ready' => null,
                'confirmed_games_ready' => null,
                'ready_for_dry_run' => null,
                'waiting_approval' => null,
                'must_hold' => null,
                'invalid' => null,
            ],
        ];

        if (is_file($reportPath)) {
            $report = $this->readJsonFile($reportPath, 'PG51 gap report');
            $summary = $report['summary'] ?? [];
            $counts = $summary['status_counts'] ?? [];
            $status['pg51_visible'] = $summary['pg51_visible'] ?? null;
            $status['pg51_targets_no_hot'] = $summary['pg51_targets_no_hot'] ?? null;
            $status['db_enabled_games'] = $summary['db_enabled_games'] ?? null;
            $status['live_pg51_mapped'] = $counts['live_pg51_mapped'] ?? null;
            $status['mapped_but_xh_not_matched'] = $counts['mapped_but_xh_not_matched'] ?? null;
            $status['unmapped_pg51_platform'] = $counts['unmapped_pg51_platform'] ?? null;
        }

        if (is_dir($opsReviewDir)) {
            foreach ([
                'pg51_platform_confirmation.csv',
                'pg51_unmatched_games_review.csv',
                'pg51_platform_map_template.json',
                'name_only_approval_template.json',
                'platform_map_validation.json',
                'pg51_upstream_questions.md',
                'xh-upstream-confirmation/00_confirmation_summary.md',
                'xh-upstream-confirmation/review_readiness.md',
                'xh-upstream-confirmation/pending_pool_summary.json',
                'xh-upstream-confirmation/review/pending_pool_summary.json',
                'xh-upstream-confirmation/review/pending_pool_summary.csv',
            ] as $file) {
                $path = $opsReviewDir . '/' . $file;
                $status['ops_review_files'][$file] = [
                    'exists' => is_file($path),
                    'size' => is_file($path) ? filesize($path) : 0,
                ];
            }

            $confirmationDir = $opsReviewDir . '/xh-upstream-confirmation';
            $manifestPath = $confirmationDir . '/manifest.json';
            $status['xh_confirmation_package']['exists'] = is_dir($confirmationDir);
            $status['xh_confirmation_package']['manifest_exists'] = is_file($manifestPath);
            if (is_file($manifestPath)) {
                $manifest = $this->readJsonFile($manifestPath, 'XH confirmation manifest');
                $counts = $manifest['counts'] ?? [];
                $remaining = $counts['remaining'] ?? null;
                $safeAuto = $counts['safe_auto_import_candidates'] ?? null;
                $status['xh_confirmation_package']['remaining'] = $remaining;
                $status['xh_confirmation_package']['platform_not_confirmed'] = $counts['platform_not_confirmed'] ?? null;
                $status['xh_confirmation_package']['platform_groups'] = $counts['platform_groups'] ?? null;
                $status['xh_confirmation_package']['mapped_but_xh_not_matched'] = $counts['mapped_but_xh_not_matched'] ?? null;
                $status['xh_confirmation_package']['collision_manual_review'] = $counts['collision_manual_review'] ?? null;
                $status['xh_confirmation_package']['category_conflict_fast_check'] = $counts['category_conflict_fast_check'] ?? null;
                $status['xh_confirmation_package']['safe_auto_import_candidates'] = $safeAuto;
                $status['xh_confirmation_package']['import_gate'] = ((int)$remaining > 0 && (int)$safeAuto === 0)
                    ? 'waiting_for_xh_confirmation'
                    : 'review_required';
                $this->applyPg51ReviewReadiness($status['xh_confirmation_package']);
            }

            $reviewPath = $confirmationDir . '/review/confirmation_review.json';
            if (is_file($reviewPath)) {
                $review = $this->readJsonFile($reviewPath, 'XH confirmation review');
                $reviewSummary = $review['summary'] ?? ($review['counts'] ?? []);
                $status['xh_confirmation_package']['review_exists'] = true;
                $status['xh_confirmation_package']['platform_ready'] = $reviewSummary['platform_ready'] ?? null;
                $status['xh_confirmation_package']['confirmed_games_ready'] = $reviewSummary['confirmed_games_ready'] ?? null;
                $status['xh_confirmation_package']['ready_for_dry_run'] = $reviewSummary['ready_for_dry_run'] ?? null;
                $status['xh_confirmation_package']['waiting_approval'] = $reviewSummary['waiting_approval'] ?? null;
                $status['xh_confirmation_package']['must_hold'] = $reviewSummary['must_hold'] ?? null;
                $status['xh_confirmation_package']['invalid'] = $reviewSummary['invalid'] ?? null;
                $status['xh_confirmation_package']['pending_pool_review_rows'] = $reviewSummary['total_review_rows'] ?? $status['xh_confirmation_package']['pending_pool_review_rows'];
                if ((int)($status['xh_confirmation_package']['platform_ready'] ?? 0) > 0 || (int)($status['xh_confirmation_package']['confirmed_games_ready'] ?? 0) > 0) {
                    $status['xh_confirmation_package']['import_gate'] = 'dry_run_required';
                }
                $this->applyPg51ReviewReadiness($status['xh_confirmation_package']);
            }

            $gatePath = $confirmationDir . '/review/confirmation_gate.json';
            $status['xh_confirmation_package']['gate_exists'] = is_file($gatePath);
            if (is_file($gatePath)) {
                $gate = $this->readJsonFile($gatePath, 'XH confirmation gate');
                $gateCounts = $gate['counts'] ?? [];
                $status['xh_confirmation_package']['import_gate'] = $gate['import_gate'] ?? $status['xh_confirmation_package']['import_gate'];
                $status['xh_confirmation_package']['gate_import_gate'] = $gate['import_gate'] ?? null;
                $status['xh_confirmation_package']['ready_for_dry_run'] = $gateCounts['ready_for_dry_run'] ?? $status['xh_confirmation_package']['ready_for_dry_run'];
                $status['xh_confirmation_package']['waiting_approval'] = $gateCounts['waiting_approval'] ?? $status['xh_confirmation_package']['waiting_approval'];
                $status['xh_confirmation_package']['must_hold'] = $gateCounts['must_hold'] ?? $status['xh_confirmation_package']['must_hold'];
                $status['xh_confirmation_package']['invalid'] = $gateCounts['invalid'] ?? $status['xh_confirmation_package']['invalid'];
                $status['xh_confirmation_package']['safe_auto_import_candidates'] = $gateCounts['safe_auto_import_candidates'] ?? $status['xh_confirmation_package']['safe_auto_import_candidates'];
                $status['xh_confirmation_package']['collision_manual_review'] = $gateCounts['collision_manual_review'] ?? $status['xh_confirmation_package']['collision_manual_review'];
                $status['xh_confirmation_package']['category_conflict_fast_check'] = $gateCounts['category_conflict_fast_check'] ?? $status['xh_confirmation_package']['category_conflict_fast_check'];
                $status['xh_confirmation_package']['pending_pool_review_rows'] = $gateCounts['total_review_rows'] ?? $status['xh_confirmation_package']['pending_pool_review_rows'];
                $status['xh_confirmation_package']['gate_by_gate'] = $gate['by_gate'] ?? [];
                $status['xh_confirmation_package']['pending_pool_summary'] = $gate['pending_pool_summary'] ?? $status['xh_confirmation_package']['pending_pool_summary'];
                $status['xh_confirmation_package']['pending_pool_classification'] = $gate['pending_pool_classification'] ?? $status['xh_confirmation_package']['pending_pool_classification'];
                $status['xh_confirmation_package']['source_csv_integrity'] = $gate['source_csv_integrity'] ?? $status['xh_confirmation_package']['source_csv_integrity'];
                $status['xh_confirmation_package']['source_csv_integrity_failures'] = $gateCounts['source_csv_integrity_failures'] ?? $status['xh_confirmation_package']['source_csv_integrity_failures'];
                $this->applyPg51PendingPoolSummary($status['xh_confirmation_package']);
                if (!empty($gate['review_readiness']) && is_array($gate['review_readiness'])) {
                    $this->applyPg51ReviewReadiness($status['xh_confirmation_package'], $gate['review_readiness']);
                } else {
                    $this->applyPg51ReviewReadiness($status['xh_confirmation_package']);
                }
            }

            $pendingPoolPath = $confirmationDir . '/review/pending_confirmation_pool.csv';
            $status['xh_confirmation_package']['pending_pool_exists'] = is_file($pendingPoolPath);
            $status['xh_confirmation_package']['pending_pool_size'] = is_file($pendingPoolPath) ? filesize($pendingPoolPath) : 0;
            $pendingPoolSummaryPath = $confirmationDir . '/review/pending_pool_summary.json';
            $sameDirPendingPoolSummaryPath = $confirmationDir . '/pending_pool_summary.json';
            $status['xh_confirmation_package']['pending_pool_summary_exists'] = is_file($pendingPoolSummaryPath);
            $status['xh_confirmation_package']['pending_pool_summary_size'] = is_file($pendingPoolSummaryPath) ? filesize($pendingPoolSummaryPath) : 0;
            if (is_file($pendingPoolSummaryPath)) {
                $status['xh_confirmation_package']['pending_pool_summary'] = $this->readJsonFile($pendingPoolSummaryPath, 'XH pending pool summary');
                $this->applyPg51PendingPoolSummary($status['xh_confirmation_package']);
            } elseif (is_file($sameDirPendingPoolSummaryPath)) {
                $status['xh_confirmation_package']['pending_pool_summary_exists'] = true;
                $status['xh_confirmation_package']['pending_pool_summary_size'] = filesize($sameDirPendingPoolSummaryPath);
                $status['xh_confirmation_package']['pending_pool_summary'] = $this->readJsonFile($sameDirPendingPoolSummaryPath, 'XH pending pool summary');
                $this->applyPg51PendingPoolSummary($status['xh_confirmation_package']);
            }
            $dryRunCandidatesPath = $confirmationDir . '/review/dry_run_candidates.json';
            $status['xh_confirmation_package']['dry_run_candidates_exists'] = is_file($dryRunCandidatesPath);
            if (is_file($dryRunCandidatesPath)) {
                $dryRunCandidates = $this->readJsonFile($dryRunCandidatesPath, 'XH dry-run candidates');
                $this->applyPg51DryRunCandidates($status['xh_confirmation_package'], $dryRunCandidates);
            }

            $sourceCsvIntegrity = $this->pg51SourceCsvIntegrity($confirmationDir, $status['xh_confirmation_package']);
            $status['xh_confirmation_package']['source_csv_integrity'] = $sourceCsvIntegrity;
            $status['xh_confirmation_package']['source_csv_integrity_failures'] = (int)($sourceCsvIntegrity['failure_count'] ?? 0);
            $status['xh_confirmation_package']['source_csv_integrity_summary'] = $this->pg51SourceCsvIntegritySummary($sourceCsvIntegrity);
        }

        return $status;
    }

    protected function applyPg51PendingPoolSummary(array &$package)
    {
        $summary = $package['pending_pool_summary'] ?? [];
        if (!is_array($summary) || !$summary) {
            return;
        }

        $package['pending_pool_ready'] = $summary['ready'] ?? $package['pending_pool_ready'];
        $package['pending_pool_upstream_plain'] = $summary['upstream_plain'] ?? $package['pending_pool_upstream_plain'];
        $package['pending_pool_conflict_union'] = $summary['conflict_union'] ?? $package['pending_pool_conflict_union'];
        $package['pending_pool_reconciliation'] = $summary['reconciliation'] ?? ($package['pending_pool_reconciliation'] ?? []);
        $package['pending_pool_conflict_union_detail'] = $summary['conflict_union_detail'] ?? ($package['pending_pool_conflict_union_detail'] ?? []);
    }

    protected function applyPg51DryRunCandidates(array &$package, array $dryRunCandidates)
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

        $package['dry_run_candidates_blocked'] = (bool)($dryRunCandidates['blocked'] ?? false);
        $package['dry_run_candidates_dry_run_allowed'] = (bool)($dryRunCandidates['dry_run_allowed'] ?? false);
        $package['dry_run_candidates_command_count'] = $commandCount;
        $package['dry_run_candidates_blocked_reason'] = (string)($dryRunCandidates['blocked_reason'] ?? '');
        $candidateCounts = $dryRunCandidates['candidate_counts'] ?? [];
        if (is_array($candidateCounts)) {
            $package['dry_run_candidate_total'] = (int)($candidateCounts['total'] ?? $package['dry_run_candidate_total']);
        }
        $commandSafety = $dryRunCandidates['command_safety'] ?? [];
        if (is_array($commandSafety)) {
            $package['dry_run_command_safety'] = $commandSafety;
            $package['dry_run_command_safety_failures'] = (int)($commandSafety['failure_count'] ?? 0);
            $package['dry_run_command_safety_summary'] = $this->pg51DryRunCommandSafetySummary($commandSafety);
        }
        if (array_key_exists('dry_run_allowed', $dryRunCandidates)) {
            $package['dry_run_allowed'] = (bool)$dryRunCandidates['dry_run_allowed'];
        }
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

        $ready = (int)($confirmation['ready_for_dry_run'] ?? 0);
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
            || (int)($confirmation['invalid'] ?? 0) > 0
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

    protected function applyPg51ReviewReadiness(array &$package, array $readiness = [])
    {
        if (!$readiness) {
            $remaining = $package['remaining'];
            $safeAuto = $package['safe_auto_import_candidates'];
            $ready = (int)($package['ready_for_dry_run'] ?? 0);
            $waiting = (int)($package['waiting_approval'] ?? 0);
            $mustHold = (int)($package['must_hold'] ?? 0);
            $invalid = (int)($package['invalid'] ?? 0);
            $safeAutoHold = $remaining !== null && $safeAuto !== null && (int)$remaining > 0 && (int)$safeAuto === 0;

            $state = 'HOLD';
            if ($ready > 0) {
                $state = ($waiting > 0 || $mustHold > 0 || $invalid > 0) ? 'DRY_RUN_READY_WITH_HOLDS' : 'DRY_RUN_READY';
            } elseif ($invalid > 0) {
                $state = 'HOLD_INVALID_CONFIRMATION';
            } elseif ($remaining !== null && (int)$remaining === 0) {
                $state = 'NO_PENDING_GAP';
            }

            $autoImportGate = $safeAutoHold ? 'HOLD' : 'REVIEW_REQUIRED';
            if ($remaining !== null && $safeAuto !== null && (int)$remaining === 0 && (int)$safeAuto === 0) {
                $autoImportGate = 'CLEAR';
            }

            $readiness = [
                'state' => $state,
                'auto_import_gate' => $autoImportGate,
                'safe_auto_import_hold' => $safeAutoHold,
                'dry_run_allowed' => $ready > 0,
                'database_import_allowed_now' => false,
                'summary' => sprintf(
                    '%s: safe_auto_import_candidates=%s; pending confirmation pool remaining=%s (platform_not_confirmed=%s, mapped_but_xh_not_matched=%s, collision_manual_review=%s, category_conflict_fast_check=%s).',
                    $autoImportGate,
                    $this->displayCount($safeAuto),
                    $this->displayCount($remaining),
                    $this->displayCount($package['platform_not_confirmed'] ?? null),
                    $this->displayCount($package['mapped_but_xh_not_matched'] ?? null),
                    $this->displayCount($package['collision_manual_review'] ?? null),
                    $this->displayCount($package['category_conflict_fast_check'] ?? null)
                ),
            ];
        }

        $package['review_readiness'] = $readiness['state'] ?? 'unknown';
        $package['auto_import_gate'] = $readiness['auto_import_gate'] ?? 'unknown';
        $package['review_readiness_summary'] = $readiness['summary'] ?? '';
        $package['dry_run_allowed'] = (bool)($readiness['dry_run_allowed'] ?? false);
        $package['database_import_allowed_now'] = (bool)($readiness['database_import_allowed_now'] ?? false);
        $package['review_readiness_detail'] = $readiness;
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
}
