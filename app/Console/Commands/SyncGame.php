<?php

namespace App\Console\Commands;

use App\Models\Api;
use App\Models\GameList;
use App\Models\SystemConfig;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncGame extends Command
{
    protected $signature = 'SyncGame
        {--pg51-csv=storage/app/pg51_games.csv : PG51 flattened games CSV}
        {--xh-catalog= : Optional XH catalog JSON from fetch_xh_catalog.php}
        {--review-dir=storage/app/pg51-sync : Directory for review reports}
        {--platform-map= : Optional approved PG51 platform mapping JSON}
        {--include-name-only : Also import same-platform same-name matches with different gameCode}
        {--confirm-name-only= : Dry-run/template-only value IMPORT_NAME_ONLY when --include-name-only is used without an approval file}
        {--name-only-approved= : JSON approval list required before importing selected name-only rows}
        {--confirmed-games-approved= : Optional JSON approval list for upstream-confirmed per-game mappings}
        {--only-confirmed-games : With --confirmed-games-approved, preview/import only upstream-confirmed per-game mappings}
        {--confirmation-gate= : Optional XhConfirmationReview confirmation_gate.json for PG51 gap import guardrails}
        {--confirm-dry-run-clean= : Required value DRY_RUN_CLEAN when importing rows from confirmation files}
        {--import : Write reviewed rows to the database}
        {--publish : Enable imported platforms and games immediately}
        {--overwrite-manual-state : Allow import to overwrite existing game site/app/top states}
        {--dry-run : Do not write database changes}';

    protected $description = 'Sync high-confidence PG51 games into local game_lists';

    protected $apiUrl;
    protected $account;
    protected $apiKey;

    protected $platformMap = [
        'CQ9电子' => ['codes' => ['CQ9'], 'category' => 'concise'],
        'MG电子' => ['codes' => ['MG'], 'category' => 'concise'],
        'PA 电子' => ['codes' => ['AG'], 'category' => 'concise'],
        'PG电子' => ['codes' => ['PG'], 'category' => 'concise'],
        'JDB 电子' => ['codes' => ['JDB', 'JDBS'], 'category' => 'concise'],
        'DB电子' => ['codes' => ['DBDZ'], 'category' => 'concise'],
        'BBIN 电子' => ['codes' => ['BBDZ'], 'category' => 'concise'],
        '瓦力棋牌' => ['codes' => ['WALI'], 'category' => 'joker'],
        '开元棋牌' => ['codes' => ['KY'], 'category' => 'joker'],
        '乐游棋牌' => ['codes' => ['LEG'], 'category' => 'joker'],
        'JDB 捕鱼' => ['codes' => ['JDB'], 'category' => 'fishing'],
        'PA 捕鱼' => ['codes' => ['AG'], 'category' => 'fishing'],
        'DB捕鱼' => ['codes' => ['DBBY'], 'category' => 'fishing'],
        'BG 捕鱼' => ['codes' => ['BGBY'], 'category' => 'fishing'],
        'FG捕鱼' => ['codes' => ['FG'], 'category' => 'fishing'],
        'MT捕鱼' => ['codes' => ['MT'], 'category' => 'fishing'],
        'BBIN 真人' => ['codes' => ['BBZR'], 'category' => 'realbet'],
        'PA真人' => ['codes' => ['AG'], 'category' => 'realbet'],
        'DB真人' => ['codes' => ['DBZR'], 'category' => 'realbet'],
        'BG真人' => ['codes' => ['BGZR'], 'category' => 'realbet'],
        'FB 体育' => ['codes' => ['FB'], 'category' => 'sport'],
        'IM 电竞' => ['codes' => ['IM'], 'category' => 'gaming'],
        'TF雷火电竞' => ['codes' => ['TFG'], 'category' => 'gaming'],
        'DB电竞' => ['codes' => ['DBDJ'], 'category' => 'gaming'],
    ];

    protected $typeMap = [
        1 => 'realbet',
        2 => 'fishing',
        3 => 'concise',
        4 => 'lottery',
        5 => 'sport',
        6 => 'joker',
        7 => 'gaming',
    ];

    public function handle()
    {
        $dryRun = (bool)$this->option('dry-run');
        $includeNameOnly = (bool)$this->option('include-name-only');
        $writeDatabase = !$dryRun && (bool)$this->option('import');
        $publish = (bool)$this->option('publish');
        $preserveManualState = !(bool)$this->option('overwrite-manual-state');
        $nameOnlyApprovalPath = $this->option('name-only-approved');
        $confirmedGamesApprovalPath = $this->option('confirmed-games-approved');
        $onlyConfirmedGames = (bool)$this->option('only-confirmed-games');
        $platformMapPath = $this->option('platform-map');
        $confirmationGatePath = $this->option('confirmation-gate');
        $usesConfirmationInputs = (bool)$platformMapPath || (bool)$confirmedGamesApprovalPath || (bool)$nameOnlyApprovalPath;
        $pg51Path = $this->resolvePath($this->option('pg51-csv'));
        $reviewDir = $this->resolvePath($this->option('review-dir'));

        if ($includeNameOnly && !$nameOnlyApprovalPath && $this->option('confirm-name-only') !== 'IMPORT_NAME_ONLY') {
            $this->error('--include-name-only requires --confirm-name-only=IMPORT_NAME_ONLY or --name-only-approved=path');
            return 1;
        }
        if ($writeDatabase && $includeNameOnly && !$nameOnlyApprovalPath) {
            $this->error('--import with --include-name-only requires --name-only-approved=path; --confirm-name-only=IMPORT_NAME_ONLY is dry-run/template only.');
            return 1;
        }
        if ($onlyConfirmedGames && !$confirmedGamesApprovalPath) {
            $this->error('--only-confirmed-games requires --confirmed-games-approved=path');
            return 1;
        }
        if ($writeDatabase && $usesConfirmationInputs && !$confirmationGatePath) {
            $this->error('--import with --platform-map, --name-only-approved, or --confirmed-games-approved requires --confirmation-gate=review/confirmation_gate.json');
            return 1;
        }
        if ($writeDatabase && $usesConfirmationInputs && $this->option('confirm-dry-run-clean') !== 'DRY_RUN_CLEAN') {
            $this->error('--import from confirmation files requires a previous clean dry-run. Add --confirm-dry-run-clean=DRY_RUN_CLEAN only after dry-run and all audits are clean.');
            return 1;
        }

        if (!is_file($pg51Path)) {
            $this->error('PG51 CSV not found: ' . $pg51Path);
            return 1;
        }

        $pgRows = $this->readCsv($pg51Path);
        $catalog = $this->loadCatalog($this->option('xh-catalog'));
        $platformMapAudit = [];
        if ($platformMapPath) {
            $platformMapAudit = $this->loadPlatformMap($platformMapPath, $catalog);
        }
        $match = $this->buildMatch($pgRows, $catalog);

        if (!is_dir($reviewDir)) {
            mkdir($reviewDir, 0755, true);
        }
        $confirmationGateAudit = [];
        if ($confirmationGatePath) {
            $confirmationGateAudit = $this->loadConfirmationGateAudit($confirmationGatePath, $writeDatabase, $usesConfirmationInputs);
            $this->writeJson($reviewDir . '/confirmation_gate_validation.json', $confirmationGateAudit);
            if ($writeDatabase && $confirmationGateAudit['blockers']) {
                foreach ($confirmationGateAudit['blockers'] as $blocker) {
                    $this->error($blocker);
                }
                return 1;
            }
        } elseif ($usesConfirmationInputs) {
            $confirmationGateAudit = $this->missingConfirmationGateAudit($writeDatabase, $usesConfirmationInputs);
            $this->writeJson($reviewDir . '/confirmation_gate_validation.json', $confirmationGateAudit);
        }
        $this->writeJson($reviewDir . '/match_report.json', $match['report']);
        $this->writeJson($reviewDir . '/name_only_review.json', $match['name_only']);
        $this->writeJson($reviewDir . '/name_only_approval_template.json', $this->nameOnlyApprovalTemplate($match['name_only']));
        $this->writeJson($reviewDir . '/unmatched.json', $match['unmatched']);
        $this->writeJson($reviewDir . '/skipped_unmapped_platforms.json', $match['skipped']);
        $this->writeJson($reviewDir . '/reverse_downline_report.json', $this->buildDownlineReport($match));
        if ($platformMapPath) {
            $this->writeJson($reviewDir . '/platform_map_validation.json', $platformMapAudit);
        }

        $importRows = $match['exact'];
        if ($includeNameOnly) {
            $nameOnlyRows = $match['name_only'];
            if ($nameOnlyApprovalPath) {
                $approved = $this->loadApprovedNameOnly($nameOnlyApprovalPath);
                $nameOnlyRows = $this->filterApprovedNameOnly($nameOnlyRows, $approved);
            }
            $importRows = array_merge($importRows, $nameOnlyRows);
        }
        $confirmedGamesAudit = [];
        if ($confirmedGamesApprovalPath) {
            [$confirmedGameRows, $confirmedGamesAudit] = $this->loadConfirmedGames($confirmedGamesApprovalPath, $pgRows, $catalog, $match['hot_keys']);
            $this->writeJson($reviewDir . '/confirmed_games_validation.json', $confirmedGamesAudit);
            $importRows = $onlyConfirmedGames ? $confirmedGameRows : $this->mergeImportRows($importRows, $confirmedGameRows);
        }
        $this->writeJson($reviewDir . '/import_preview.json', $this->importPreview($importRows, $publish));

        $stats = ['platform_inserted' => 0, 'platform_updated' => 0, 'game_inserted' => 0, 'game_updated' => 0, 'game_skipped' => 0];
        if ($writeDatabase) {
            DB::beginTransaction();
        }

        try {
            $stats = $this->importRows($importRows, !$writeDatabase, [
                'publish' => $publish,
                'preserve_manual_state' => $preserveManualState,
            ]);
            if ($writeDatabase) {
                DB::commit();
            }
        } catch (\Throwable $e) {
            if ($writeDatabase) {
                DB::rollBack();
            }
            throw $e;
        }

        $this->writeJson($reviewDir . '/audit.json', [
            'generated_at' => date('c'),
            'database_changed' => $writeDatabase,
            'options' => [
                'import' => $writeDatabase,
                'dry_run' => $dryRun,
                'publish' => $publish,
                'platform_map' => $platformMapPath ?: '',
                'include_name_only' => $includeNameOnly,
                'name_only_approved' => $nameOnlyApprovalPath ?: '',
                'confirmed_games_approved' => $confirmedGamesApprovalPath ?: '',
                'only_confirmed_games' => $onlyConfirmedGames,
                'confirmation_gate' => $confirmationGatePath ?: '',
                'confirm_dry_run_clean' => $this->option('confirm-dry-run-clean') === 'DRY_RUN_CLEAN',
                'preserve_manual_state' => $preserveManualState,
            ],
            'platform_map_audit' => $platformMapAudit,
            'confirmed_games_audit' => $confirmedGamesAudit,
            'confirmation_gate_audit' => $confirmationGateAudit,
            'match_counts' => [
                'pg51_visible' => $match['report']['pg51_visible'],
                'pg51_targets_no_hot' => $match['report']['pg51_targets_no_hot'],
                'exact' => $match['report']['exact_count'],
                'name_only' => $match['report']['name_only_count'],
                'unmatched' => $match['report']['unmatched_count'],
                'skipped' => $match['report']['skipped_count'],
                'import_preview' => count($importRows),
            ],
            'write_stats' => $stats,
            'reports' => [
                'match_report' => $reviewDir . '/match_report.json',
                'name_only_review' => $reviewDir . '/name_only_review.json',
                'name_only_approval_template' => $reviewDir . '/name_only_approval_template.json',
                'unmatched' => $reviewDir . '/unmatched.json',
                'skipped_unmapped_platforms' => $reviewDir . '/skipped_unmapped_platforms.json',
                'import_preview' => $reviewDir . '/import_preview.json',
                'reverse_downline_report' => $reviewDir . '/reverse_downline_report.json',
                'platform_map_validation' => $platformMapPath ? $reviewDir . '/platform_map_validation.json' : '',
                'confirmed_games_validation' => $confirmedGamesApprovalPath ? $reviewDir . '/confirmed_games_validation.json' : '',
                'confirmation_gate_validation' => $confirmationGateAudit ? $reviewDir . '/confirmation_gate_validation.json' : '',
            ],
        ]);

        $this->info(sprintf(
            'PG51 visible=%d target=%d exact=%d name_only=%d unmatched=%d skipped=%d',
            $match['report']['pg51_visible'],
            $match['report']['pg51_targets_no_hot'],
            $match['report']['exact_count'],
            $match['report']['name_only_count'],
            $match['report']['unmatched_count'],
            $match['report']['skipped_count']
        ));
        $this->info(sprintf(
            'Imported games inserted=%d updated=%d skipped=%d; platforms inserted=%d updated=%d',
            $stats['game_inserted'],
            $stats['game_updated'],
            $stats['game_skipped'],
            $stats['platform_inserted'],
            $stats['platform_updated']
        ));
        $this->comment('Review files: ' . $reviewDir);
        if (!$writeDatabase) {
            if ($usesConfirmationInputs) {
                $this->comment('Report only, database was not changed. Confirmation input runs stay dry-run/review only unless the confirmation gate explicitly allows a separate import decision.');
            } else {
                $this->comment('Report only, database was not changed. Add --import to write reviewed rows.');
            }
        }
        if ($writeDatabase && !$publish) {
            $this->comment('Imported rows were staged disabled. Add --publish only after operations review.');
        }
        if ($preserveManualState) {
            $this->comment('Existing game site/app/top states were preserved.');
        }
        if ($usesConfirmationInputs && !$writeDatabase) {
            $this->comment('Confirmation input mode: dry-run/review only. A future separate import decision would require --confirmation-gate, a clean dry-run, clean audits, and explicit readiness.');
        }
        if ($confirmationGateAudit) {
            $this->comment(sprintf(
                'Confirmation gate audit: gate=%s readiness=%s auto_import_gate=%s remaining=%s safe_auto_import_candidates=%s database_import_allowed_now=%s',
                $confirmationGateAudit['import_gate'] ?? 'missing',
                $confirmationGateAudit['review_readiness'] ?? 'unknown',
                $confirmationGateAudit['auto_import_gate'] ?? 'unknown',
                $confirmationGateAudit['manifest_remaining'] ?? 'unknown',
                $confirmationGateAudit['safe_auto_import_candidates'] ?? 'unknown',
                !empty($confirmationGateAudit['database_import_allowed_now']) ? 'true' : 'false'
            ));
            foreach (($confirmationGateAudit['warnings'] ?? []) as $warning) {
                $this->warn($warning);
            }
        }

        return 0;
    }

    protected function missingConfirmationGateAudit($writeDatabase, $usesConfirmationInputs)
    {
        return [
            'generated_at' => date('c'),
            'path' => '',
            'write_database_requested' => (bool)$writeDatabase,
            'confirmation_inputs_used' => (bool)$usesConfirmationInputs,
            'import_gate' => 'missing',
            'auto_import_gate' => 'unknown',
            'review_readiness' => 'unknown',
            'review_readiness_summary' => 'Confirmation inputs were used without --confirmation-gate; output is dry-run evidence only and cannot support import approval.',
            'database_import_allowed_now' => false,
            'ready_for_dry_run' => 0,
            'waiting_approval' => 0,
            'must_hold' => 0,
            'invalid' => 0,
            'manifest_remaining' => 0,
            'platform_not_confirmed' => 0,
            'mapped_but_xh_not_matched' => 0,
            'collision_manual_review' => 0,
            'category_conflict_fast_check' => 0,
            'safe_auto_import_candidates' => 0,
            'pending_pool_summary' => [],
            'blockers' => $writeDatabase ? ['Confirmation gate is required before importing confirmation inputs.'] : [],
            'warnings' => ['No --confirmation-gate was supplied; keep this run in dry-run/review mode only.'],
        ];
    }

    protected function loadConfirmationGateAudit($path, $writeDatabase, $usesConfirmationInputs)
    {
        $path = $this->resolvePath($path);
        if (!is_file($path)) {
            throw new \RuntimeException('Confirmation gate JSON not found: ' . $path);
        }

        $gate = $this->readJsonFile($path, 'Confirmation gate');
        $counts = $gate['counts'] ?? [];
        $readiness = $gate['review_readiness'] ?? [];
        $pendingPool = isset($gate['pending_pool_summary']) && is_array($gate['pending_pool_summary'])
            ? $gate['pending_pool_summary']
            : [];
        $ready = (int)($counts['ready_for_dry_run'] ?? 0);
        $remaining = (int)($counts['manifest_remaining'] ?? 0);
        $safeAuto = (int)($counts['safe_auto_import_candidates'] ?? 0);
        $pendingReady = (int)($pendingPool['ready'] ?? $ready);
        $pendingUpstreamPlain = (int)($pendingPool['upstream_plain'] ?? 0);
        $pendingConflictUnion = (int)($pendingPool['conflict_union'] ?? 0);
        $pendingReconciliation = isset($pendingPool['reconciliation']) && is_array($pendingPool['reconciliation'])
            ? $pendingPool['reconciliation']
            : [];
        $pendingConflictDetail = isset($pendingPool['conflict_union_detail']) && is_array($pendingPool['conflict_union_detail'])
            ? $pendingPool['conflict_union_detail']
            : [];
        $blockers = [];
        $warnings = [];

        if ($usesConfirmationInputs && !$writeDatabase) {
            $warnings[] = 'Confirmation gate supplied for dry-run/review only; this audit does not authorize database writes.';
        }
        if ($pendingPool) {
            if ($pendingReady !== $ready) {
                $warnings[] = 'Confirmation gate pending_pool_summary.ready does not match counts.ready_for_dry_run.';
            }
            if ($remaining > 0 && ($pendingUpstreamPlain + $pendingConflictUnion) !== $remaining) {
                $warnings[] = 'Confirmation gate pending pool summary does not add up to manifest_remaining.';
            }
            if ($pendingConflictUnion > 0) {
                $warnings[] = 'Confirmation gate has unresolved category/collision conflict union; keep this pool on HOLD.';
            }
            foreach ($pendingReconciliation as $name => $passed) {
                if ($passed === false || $passed === 0 || $passed === '0') {
                    $warnings[] = 'Confirmation gate reconciliation failed: ' . $name;
                }
            }
            if ($pendingConflictDetail) {
                $formulaUnion = (int)($pendingConflictDetail['category_conflict_fast_check'] ?? 0)
                    + (int)($pendingConflictDetail['collision_manual_review'] ?? 0)
                    - (int)($pendingConflictDetail['overlap'] ?? 0);
                if ($formulaUnion !== $pendingConflictUnion) {
                    $warnings[] = 'Confirmation gate conflict_union_detail formula does not match pending_pool_summary.conflict_union.';
                }
            }
        }

        if ($usesConfirmationInputs && $writeDatabase) {
            if ($ready <= 0) {
                $blockers[] = 'Confirmation gate has no READY_FOR_DRY_RUN rows; import is blocked.';
            }
            if (!in_array(($gate['import_gate'] ?? ''), ['dry_run_ready', 'dry_run_ready_with_holds'], true)) {
                $blockers[] = 'Confirmation gate is not dry-run ready: ' . ($gate['import_gate'] ?? 'unknown');
            }
            if (!empty($gate['guardrails']['blind_import_allowed'])) {
                $blockers[] = 'Confirmation gate guardrail is invalid: blind_import_allowed must be false.';
            }
            if (array_key_exists('database_import_allowed_now', $readiness) && empty($readiness['database_import_allowed_now'])) {
                $blockers[] = 'Confirmation review readiness does not allow database import now.';
            }
            if ($remaining > 0 && $safeAuto === 0 && ($readiness['auto_import_gate'] ?? '') === 'HOLD') {
                $blockers[] = 'Pending confirmation pool is HOLD with safe_auto_import_candidates=0; blind or automatic import is blocked.';
            }
            if ($pendingConflictUnion > 0) {
                $blockers[] = 'Pending confirmation pool still has unresolved category/collision conflicts; import is blocked.';
            }
            if ($pendingPool && $remaining > 0 && ($pendingUpstreamPlain + $pendingConflictUnion) !== $remaining) {
                $blockers[] = 'Pending confirmation pool summary does not reconcile to manifest_remaining; import is blocked.';
            }
            foreach ($pendingReconciliation as $name => $passed) {
                if ($passed === false || $passed === 0 || $passed === '0') {
                    $blockers[] = 'Pending confirmation pool reconciliation failed: ' . $name . '; import is blocked.';
                }
            }
            if ($pendingConflictDetail) {
                $formulaUnion = (int)($pendingConflictDetail['category_conflict_fast_check'] ?? 0)
                    + (int)($pendingConflictDetail['collision_manual_review'] ?? 0)
                    - (int)($pendingConflictDetail['overlap'] ?? 0);
                if ($formulaUnion !== $pendingConflictUnion) {
                    $blockers[] = 'Pending confirmation conflict union formula does not reconcile; import is blocked.';
                }
            }
        }

        return [
            'generated_at' => date('c'),
            'path' => $path,
            'write_database_requested' => (bool)$writeDatabase,
            'confirmation_inputs_used' => (bool)$usesConfirmationInputs,
            'import_gate' => $gate['import_gate'] ?? 'unknown',
            'auto_import_gate' => $gate['auto_import_gate'] ?? ($readiness['auto_import_gate'] ?? 'unknown'),
            'review_readiness' => $readiness['state'] ?? 'unknown',
            'review_readiness_summary' => $readiness['summary'] ?? '',
            'database_import_allowed_now' => (bool)($readiness['database_import_allowed_now'] ?? false),
            'ready_for_dry_run' => $ready,
            'waiting_approval' => (int)($counts['waiting_approval'] ?? 0),
            'must_hold' => (int)($counts['must_hold'] ?? 0),
            'invalid' => (int)($counts['invalid'] ?? 0),
            'manifest_remaining' => $remaining,
            'platform_not_confirmed' => (int)($counts['platform_not_confirmed'] ?? 0),
            'mapped_but_xh_not_matched' => (int)($counts['mapped_but_xh_not_matched'] ?? 0),
            'collision_manual_review' => (int)($counts['collision_manual_review'] ?? 0),
            'category_conflict_fast_check' => (int)($counts['category_conflict_fast_check'] ?? 0),
            'safe_auto_import_candidates' => $safeAuto,
            'pending_pool_summary' => $gate['pending_pool_summary'] ?? [],
            'pending_pool_ready' => $pendingReady,
            'pending_pool_upstream_plain' => $pendingUpstreamPlain,
            'pending_pool_conflict_union' => $pendingConflictUnion,
            'pending_pool_reconciliation' => $pendingReconciliation,
            'pending_pool_conflict_union_detail' => $pendingConflictDetail,
            'blockers' => $blockers,
            'warnings' => $warnings,
        ];
    }

    protected function loadPlatformMap($path, array $catalog)
    {
        $path = $this->resolvePath($path);
        if (!is_file($path)) {
            throw new \RuntimeException('Platform map JSON not found: ' . $path);
        }

        $data = $this->readJsonFile($path, 'Platform map');

        $items = $this->isAssoc($data) ? $this->platformMapObjectToItems($data) : $data;
        $validCategories = array_flip(array_values($this->typeMap));
        $catalogIndex = $this->buildCatalogValidationIndex($catalog);
        $audit = [
            'generated_at' => date('c'),
            'path' => $path,
            'approved_count' => 0,
            'skipped_unapproved_count' => 0,
            'approved' => [],
            'skipped_unapproved' => [],
        ];

        foreach ($items as $item) {
            if (!is_array($item) || empty($item['approved'])) {
                if (is_array($item)) {
                    $audit['skipped_unapproved_count']++;
                    $audit['skipped_unapproved'][] = [
                        'pg_platform' => $item['pg_platform'] ?? ($item['platform'] ?? ''),
                        'category' => $item['category'] ?? '',
                        'codes' => $item['codes'] ?? ($item['xh_codes'] ?? []),
                    ];
                }
                continue;
            }

            $pgPlatform = trim((string)($item['pg_platform'] ?? ($item['platform'] ?? '')));
            $category = trim((string)($item['category'] ?? ''));
            $codes = $item['codes'] ?? ($item['xh_codes'] ?? []);
            if (is_string($codes)) {
                $codes = preg_split('/[\s,]+/', $codes, -1, PREG_SPLIT_NO_EMPTY);
            }
            $codes = array_values(array_unique(array_filter(array_map(function ($code) {
                return strtoupper(trim((string)$code));
            }, (array)$codes))));

            if ($pgPlatform === '' || $category === '' || empty($codes)) {
                throw new \RuntimeException('Approved platform map rows require pg_platform, category and codes.');
            }
            if (!isset($validCategories[$category])) {
                throw new \RuntimeException('Invalid platform map category for ' . $pgPlatform . ': ' . $category);
            }
            foreach ($codes as $code) {
                if (empty($catalogIndex['platform_codes'][$code])) {
                    throw new \RuntimeException('Approved platform map code not found in active XH platform list for ' . $pgPlatform . ': ' . $code);
                }
                if (empty($catalogIndex['game_scopes'][$code . '|' . $category])) {
                    throw new \RuntimeException('Approved platform map code has no active XH games for category ' . $category . ' on ' . $pgPlatform . ': ' . $code);
                }
            }

            $this->platformMap[$pgPlatform] = [
                'codes' => $codes,
                'category' => $category,
                'source' => 'approved_override',
            ];
            $audit['approved_count']++;
            $audit['approved'][] = [
                'pg_platform' => $pgPlatform,
                'category' => $category,
                'codes' => $codes,
                'active_game_scopes' => array_map(function ($code) use ($category) {
                    return $code . '|' . $category;
                }, $codes),
            ];
        }

        return $audit;
    }

    protected function platformMapObjectToItems(array $data)
    {
        $items = [];
        foreach ($data as $platform => $map) {
            if (!is_array($map)) {
                continue;
            }
            $map['pg_platform'] = $map['pg_platform'] ?? $platform;
            $items[] = $map;
        }

        return $items;
    }

    protected function buildCatalogValidationIndex(array $catalog)
    {
        $platformCodes = [];
        foreach (($catalog['platforms'] ?? []) as $platform) {
            if ((int)($platform['status'] ?? 1) !== 1) {
                continue;
            }
            $code = strtoupper(trim($platform['code'] ?? ''));
            if ($code !== '') {
                $platformCodes[$code] = true;
            }
        }

        $gameScopes = [];
        $games = array_merge($catalog['direct_games'] ?? [], $catalog['sub_games'] ?? []);
        foreach ($games as $game) {
            if ((int)($game['status'] ?? 1) !== 1 || (int)($game['maintenance'] ?? 1) !== 1) {
                continue;
            }
            $code = strtoupper(trim($game['code'] ?? ''));
            $category = $this->typeMap[(int)($game['gameType'] ?? 0)] ?? '';
            if ($code !== '' && $category !== '') {
                $gameScopes[$code . '|' . $category] = true;
            }
        }

        return [
            'platform_codes' => $platformCodes,
            'game_scopes' => $gameScopes,
        ];
    }

    protected function buildMatch(array $pgRows, array $catalog)
    {
        $visible = [];
        foreach ($pgRows as $row) {
            if (($row['isShow'] ?? '') === 'True' && ($row['isMaintain'] ?? '') === 'False') {
                $visible[] = $row;
            }
        }

        $hotKeys = [];
        $targets = [];
        foreach ($visible as $row) {
            if (($row['topTypeCode'] ?? '') === 'HOTGAME') {
                $hotKeys[$this->hotKey($row)] = true;
            } else {
                $targets[] = $row;
            }
        }

        $games = array_merge($catalog['direct_games'] ?? [], $catalog['sub_games'] ?? []);
        $byExact = [];
        $byName = [];
        foreach ($games as $game) {
            if ((int)($game['status'] ?? 1) !== 1 || (int)($game['maintenance'] ?? 1) !== 1) {
                continue;
            }
            $category = $this->typeMap[(int)($game['gameType'] ?? 0)] ?? '';
            if ($category === '') {
                continue;
            }

            $code = strtoupper(trim($game['code'] ?? ''));
            $exactKey = $code . '|' . $category . '|' . $this->codeNorm($game['gameCode'] ?? '');
            $nameKey = $code . '|' . $category . '|' . $this->norm($game['title'] ?? '');
            $byExact[$exactKey][] = $game;
            $byName[$nameKey][] = $game;
        }

        $exact = [];
        $nameOnly = [];
        $unmatched = [];
        $skipped = [];
        foreach ($targets as $row) {
            $platformName = $row['platformName'] ?? '';
            if (!isset($this->platformMap[$platformName])) {
                $row['reason'] = 'unmapped_platform';
                $skipped[] = $row;
                continue;
            }

            $map = $this->platformMap[$platformName];
            $matched = null;
            $playCode = $this->codeNorm($row['playCode'] ?? '');
            $codeCandidates = [$playCode];
            if ($playCode === 'lobby') {
                $codeCandidates[] = '0';
            }

            foreach ($map['codes'] as $xhCode) {
                foreach (array_unique($codeCandidates) as $codeCandidate) {
                    $key = $xhCode . '|' . $map['category'] . '|' . $codeCandidate;
                    if (!empty($byExact[$key])) {
                        $matched = $this->chooseGame($byExact[$key], $row);
                        $matched['xh_code'] = $xhCode;
                        $matched['category'] = $map['category'];
                        if ($playCode === 'lobby' && $codeCandidate === '0') {
                            $matched['confidence'] = 'lobby_zero';
                        } else {
                            $matched['confidence'] = $this->norm($matched['xh']['title'] ?? '') === $this->norm($row['playName'] ?? '') ? 'exact_code_name' : 'exact_code';
                        }
                        break 2;
                    }
                }
            }

            if ($matched) {
                $matched['pg51'] = $row;
                $matched['is_hot'] = isset($hotKeys[$this->hotKey($row)]) ? 1 : 0;
                $exact[] = $matched;
                continue;
            }

            $nameCandidates = [];
            foreach ($map['codes'] as $xhCode) {
                $key = $xhCode . '|' . $map['category'] . '|' . $this->norm($row['playName'] ?? '');
                if (!empty($byName[$key]) && count($byName[$key]) === 1) {
                    $nameCandidates[] = ['xh_code' => $xhCode, 'xh' => $byName[$key][0]];
                }
            }

            if (count($nameCandidates) === 1) {
                $nameOnly[] = [
                    'pg51' => $row,
                    'xh' => $nameCandidates[0]['xh'],
                    'xh_code' => $nameCandidates[0]['xh_code'],
                    'category' => $map['category'],
                    'confidence' => 'name_only',
                    'is_hot' => isset($hotKeys[$this->hotKey($row)]) ? 1 : 0,
                ];
            } else {
                $row['xh_code'] = implode(',', $map['codes']);
                $row['category'] = $map['category'];
                $row['reason'] = count($nameCandidates) > 1 ? 'ambiguous_name' : 'no_match';
                $unmatched[] = $row;
            }
        }

        $report = [
            'generated_at' => date('c'),
            'pg51_visible' => count($visible),
            'pg51_targets_no_hot' => count($targets),
            'xh_games' => count($games),
            'exact_count' => count($exact),
            'name_only_count' => count($nameOnly),
            'unmatched_count' => count($unmatched),
            'skipped_count' => count($skipped),
            'exact_by_platform' => $this->countBy($exact, function ($row) { return $row['pg51']['platformName']; }),
            'name_only_by_platform' => $this->countBy($nameOnly, function ($row) { return $row['pg51']['platformName']; }),
            'unmatched_by_platform' => $this->countBy($unmatched, function ($row) { return $row['platformName']; }),
            'skipped_by_platform' => $this->countBy($skipped, function ($row) { return $row['platformName']; }),
        ];

        return compact('report', 'exact', 'nameOnly', 'unmatched', 'skipped') + [
            'name_only' => $nameOnly,
            'hot_keys' => $hotKeys,
        ];
    }

    protected function chooseGame(array $games, array $pgRow)
    {
        $pgName = $this->norm($pgRow['playName'] ?? '');
        foreach ($games as $game) {
            if ($this->norm($game['title'] ?? '') === $pgName) {
                return ['xh' => $game];
            }
        }

        return ['xh' => $games[0]];
    }

    protected function importRows(array $rows, $dryRun, array $options = [])
    {
        $publish = (bool)($options['publish'] ?? false);
        $preserveManualState = (bool)($options['preserve_manual_state'] ?? true);
        $stats = ['platform_inserted' => 0, 'platform_updated' => 0, 'game_inserted' => 0, 'game_updated' => 0, 'game_skipped' => 0];
        $now = date('Y-m-d H:i:s');
        $platformNames = [];

        foreach ($rows as $row) {
            $code = $row['xh_code'];
            if (!isset($platformNames[$code])) {
                $platformNames[$code] = $this->platformDisplayName($row);
            }
        }

        foreach ($platformNames as $code => $name) {
            $api = Api::where('api_code', $code)->first();
            if ($api) {
                $updates = [];
                if ($publish && (int)$api->state !== 1) {
                    $updates['state'] = 1;
                }
                if ($publish && (int)$api->app_state !== 1) {
                    $updates['app_state'] = 1;
                }
                if (!$api->api_name && $name) {
                    $updates['api_name'] = $name;
                }
                if ($updates) {
                    $updates['updated_at'] = $now;
                    if (!$dryRun) {
                        Api::where('id', $api->id)->update($updates);
                    }
                    $stats['platform_updated']++;
                }
            } else {
                if (!$dryRun) {
                    Api::create([
                        'api_code' => $code,
                        'api_name' => $name ?: $code,
                        'api_money' => 0,
                        'state' => $publish ? 1 : 0,
                        'app_state' => $publish ? 1 : 0,
                        'order_by' => 456,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
                $stats['platform_inserted']++;
            }
        }

        foreach ($rows as $row) {
            $data = $this->gameData($row, $now, $publish);
            if (!$data) {
                $stats['game_skipped']++;
                continue;
            }

            $query = GameList::where('platform_name', $data['platform_name'])
                ->where('category_id', $data['category_id'])
                ->where('game_code', $data['game_code']);
            $existing = $query->orderBy('id')->first();
            if ($existing) {
                unset($data['created_at']);
                if ($preserveManualState) {
                    unset($data['site_state'], $data['app_state'], $data['is_top']);
                }
                if (!$dryRun) {
                    GameList::where('id', $existing->id)->update($data);
                }
                $stats['game_updated']++;
            } else {
                if (!$dryRun) {
                    GameList::create($data);
                }
                $stats['game_inserted']++;
            }
        }

        return $stats;
    }

    protected function gameData(array $row, $now, $publish = false)
    {
        $pg = $row['pg51'];
        $xh = $row['xh'];
        $name = trim($pg['playName'] ?? ($xh['title'] ?? ''));
        $gameCode = trim((string)($xh['gameCode'] ?? $pg['playCode'] ?? ''));
        if ($name === '' || $gameCode === '') {
            return null;
        }

        $image = $this->pg51Image($pg['icon'] ?? '');
        if ($image === '' && !empty($xh['img'])) {
            $image = $xh['img'];
        }

        return [
            'platform_name' => $row['xh_code'],
            'name' => $name,
            'name_en' => null,
            'keywords' => $pg['platformName'] . ',' . $pg['playCode'],
            'game_code' => $gameCode,
            'category_id' => $row['category'],
            'order_by' => (int)($pg['_row_index'] ?? 456),
            'is_hot' => (int)($row['is_hot'] ?? 0),
            'is_new' => 0,
            'is_recommend' => 0,
            'is_pc' => 1,
            'is_mobile' => 1,
            'site_state' => $publish ? 1 : 0,
            'app_state' => $publish ? 1 : 0,
            'is_top' => $publish ? 1 : 0,
            'check_yes_img' => '',
            'check_no_img' => '',
            'mobile_img' => $image,
            'api_logo_img' => $image,
            'app_img' => $image,
            'app_icon' => $image,
            'header_logo' => '',
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    protected function importPreview(array $rows, $publish)
    {
        $items = [];
        foreach ($rows as $row) {
            $data = $this->gameData($row, date('Y-m-d H:i:s'), $publish);
            if (!$data) {
                continue;
            }
            $items[] = [
                'platform_name' => $data['platform_name'],
                'category_id' => $data['category_id'],
                'game_code' => $data['game_code'],
                'name' => $data['name'],
                'confidence' => $row['confidence'] ?? '',
                'approval_key' => $this->importRowKey($row),
                'publish_state' => $publish ? 'enabled' : 'staged_disabled',
                'pg51_platform' => $row['pg51']['platformName'] ?? '',
                'pg51_play_code' => $row['pg51']['playCode'] ?? '',
            ];
        }

        return [
            'generated_at' => date('c'),
            'count' => count($items),
            'publish' => (bool)$publish,
            'items' => $items,
        ];
    }

    protected function nameOnlyApprovalTemplate(array $rows)
    {
        $items = [];
        foreach ($rows as $row) {
            $pg = $row['pg51'] ?? [];
            $xh = $row['xh'] ?? [];
            $items[] = [
                'approved' => false,
                'approval_key' => $this->importRowKey($row),
                'pg_platform' => $pg['platformName'] ?? '',
                'pg_category' => $row['category'] ?? '',
                'pg_play_name' => $pg['playName'] ?? '',
                'pg_play_code' => $pg['playCode'] ?? '',
                'xh_code' => $row['xh_code'] ?? '',
                'xh_title' => $xh['title'] ?? '',
                'xh_game_code' => $xh['gameCode'] ?? '',
                'xh_game_type' => $xh['gameType'] ?? '',
                'confidence' => $row['confidence'] ?? '',
                'risk' => 'Name-only match. Approve only after confirming this is the same vendor game.',
            ];
        }

        return [
            'generated_at' => date('c'),
            'note' => 'Set approved=true only for reviewed rows, then use this file with --include-name-only --name-only-approved=path.',
            'count' => count($items),
            'items' => $items,
        ];
    }

    protected function loadApprovedNameOnly($path)
    {
        $path = $this->resolvePath($path);
        if (!is_file($path)) {
            throw new \RuntimeException('Name-only approval file not found: ' . $path);
        }

        $data = $this->readJsonFile($path, 'Name-only approval');

        $approved = [];
        $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : $data;
        foreach ($items as $item) {
            if (is_string($item) && $item !== '') {
                $approved[$item] = true;
                continue;
            }
            if (is_array($item)) {
                if (array_key_exists('approved', $item) && empty($item['approved'])) {
                    continue;
                }
                $key = $item['approval_key'] ?? ($item['key'] ?? '');
                if ($key !== '') {
                    $approved[$key] = true;
                }
            }
        }

        return $approved;
    }

    protected function filterApprovedNameOnly(array $rows, array $approved)
    {
        $filtered = [];
        foreach ($rows as $row) {
            if (isset($approved[$this->importRowKey($row)])) {
                $filtered[] = $row;
            }
        }

        return $filtered;
    }

    protected function loadConfirmedGames($path, array $pgRows, array $catalog, array $hotKeys)
    {
        $path = $this->resolvePath($path);
        if (!is_file($path)) {
            throw new \RuntimeException('Confirmed-games approval file not found: ' . $path);
        }

        $data = $this->readJsonFile($path, 'Confirmed-games approval');
        $items = isset($data['items']) && is_array($data['items']) ? $data['items'] : $data;
        $catalogIndex = $this->buildConfirmedGameIndex($catalog);
        $pgIndex = $this->buildPgRowIndex($pgRows);
        $validCategories = array_flip(array_values($this->typeMap));
        $rows = [];
        $audit = [
            'generated_at' => date('c'),
            'path' => $path,
            'approved_count' => 0,
            'ready_count' => 0,
            'skipped_count' => 0,
            'ready' => [],
            'skipped' => [],
        ];

        foreach ($items as $item) {
            if (!is_array($item) || !$this->confirmationTruthy($item['approved'] ?? false)) {
                $audit['skipped_count']++;
                $audit['skipped'][] = [
                    'reason' => 'not_approved',
                    'pg_platform' => is_array($item) ? ($item['pg_platform'] ?? '') : '',
                    'pg_play_code' => is_array($item) ? ($item['pg_play_code'] ?? '') : '',
                ];
                continue;
            }

            $audit['approved_count']++;
            $pgPlatform = trim((string)($item['pg_platform'] ?? ''));
            $pgPlayName = trim((string)($item['pg_play_name'] ?? ''));
            $pgPlayCode = trim((string)($item['pg_play_code'] ?? ''));
            $xhCode = strtoupper(trim((string)($item['xh_code'] ?? '')));
            $category = $this->normalizeConfirmedCategory($item['xh_category'] ?? ($item['pg_category'] ?? ''));
            $xhGameCode = $this->codeNorm($item['xh_game_code'] ?? '');
            $xhTitle = trim((string)($item['xh_title'] ?? ''));
            $sourceFile = trim((string)($item['source_file'] ?? ''));
            $errors = [];
            $allowedSourceFiles = [
                '03_mapped_missing_game_request.csv',
                '04_category_conflict_fast_check.csv',
                '05_collision_manual_review.csv',
            ];

            if ($pgPlatform === '' || $pgPlayName === '' || $pgPlayCode === '') {
                $errors[] = 'pg_platform, pg_play_name and pg_play_code are required';
            }
            if ($xhCode === '') {
                $errors[] = 'xh_code is required';
            }
            if ($category === '' || !isset($validCategories[$category])) {
                $errors[] = 'valid xh_category is required';
            }
            if ($xhGameCode === '') {
                $errors[] = 'xh_game_code is required';
            }
            if ($xhTitle === '') {
                $errors[] = 'xh_title is required';
            }
            if ($sourceFile === '' || !in_array($sourceFile, $allowedSourceFiles, true)) {
                $errors[] = 'valid source_file from XhConfirmationReview is required';
            }
            if ($sourceFile === '04_category_conflict_fast_check.csv' && !$this->confirmationTruthy($item['category_conflict_confirmed'] ?? false)) {
                $errors[] = 'category_conflict_confirmed=true is required for category conflict rows';
            }
            if ($sourceFile === '05_collision_manual_review.csv' && !$this->confirmationTruthy($item['collision_manual_confirmed'] ?? false)) {
                $errors[] = 'collision_manual_confirmed=true is required for collision review rows';
            }

            $gameKey = $xhCode . '|' . $category . '|' . $xhGameCode;
            $xhGame = $catalogIndex[$gameKey] ?? null;
            if (!$xhGame) {
                $errors[] = 'active XH game not found: ' . $gameKey;
            }

            if ($errors) {
                $audit['skipped_count']++;
                $audit['skipped'][] = [
                    'reason' => implode('; ', $errors),
                    'pg_platform' => $pgPlatform,
                    'pg_play_code' => $pgPlayCode,
                    'xh_code' => $xhCode,
                    'category' => $category,
                    'xh_game_code' => $xhGameCode,
                ];
                continue;
            }

            $xhGame['title'] = $xhTitle;

            $pg = $this->findPgRow($pgIndex, $pgPlatform, $pgPlayName, $pgPlayCode);
            if (!$pg) {
                $audit['skipped_count']++;
                $audit['skipped'][] = [
                    'reason' => 'pg51_current_row_not_found',
                    'pg_platform' => $pgPlatform,
                    'pg_play_name' => $pgPlayName,
                    'pg_play_code' => $pgPlayCode,
                    'xh_code' => $xhCode,
                    'category' => $category,
                    'xh_game_code' => $xhGame['gameCode'] ?? $xhGameCode,
                ];
                continue;
            }

            $row = [
                'pg51' => $pg,
                'xh' => $xhGame,
                'xh_code' => $xhCode,
                'category' => $category,
                'confidence' => $item['confidence'] ?? 'upstream_confirmed',
                'is_hot' => isset($hotKeys[$this->hotKey($pg)]) ? 1 : 0,
            ];
            $rows[] = $row;
            $audit['ready_count']++;
            $audit['ready'][] = [
                'approval_key' => $this->importRowKey($row),
                'pg_platform' => $pgPlatform,
                'pg_play_code' => $pgPlayCode,
                'xh_code' => $xhCode,
                'category' => $category,
                'xh_game_code' => $xhGame['gameCode'] ?? '',
            ];
        }

        return [$rows, $audit];
    }

    protected function confirmationTruthy($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_int($value)) {
            return $value === 1;
        }

        return in_array(strtolower(trim((string)$value)), ['1', 'true', 'yes', 'y', 'approved'], true);
    }

    protected function buildConfirmedGameIndex(array $catalog)
    {
        $index = [];
        foreach (array_merge($catalog['direct_games'] ?? [], $catalog['sub_games'] ?? []) as $game) {
            if ((int)($game['status'] ?? 1) !== 1 || (int)($game['maintenance'] ?? 1) !== 1) {
                continue;
            }
            $code = strtoupper(trim($game['code'] ?? ''));
            $category = $this->typeMap[(int)($game['gameType'] ?? 0)] ?? '';
            $gameCode = $this->codeNorm($game['gameCode'] ?? '');
            if ($code === '' || $category === '' || $gameCode === '') {
                continue;
            }
            $index[$code . '|' . $category . '|' . $gameCode] = $game;
        }

        return $index;
    }

    protected function buildPgRowIndex(array $pgRows)
    {
        $index = [];
        foreach ($pgRows as $row) {
            if (($row['isShow'] ?? '') !== 'True' || ($row['isMaintain'] ?? '') !== 'False') {
                continue;
            }
            if (($row['topTypeCode'] ?? '') === 'HOTGAME') {
                continue;
            }
            $platform = trim((string)($row['platformName'] ?? ''));
            $name = trim((string)($row['playName'] ?? ''));
            $code = trim((string)($row['playCode'] ?? ''));
            if ($platform === '' || $name === '' || $code === '') {
                continue;
            }
            $index['full'][$this->confirmedPgKey($platform, $name, $code)] = $row;
            $index['code'][$this->confirmedPgCodeKey($platform, $code)] = $row;
        }

        return $index;
    }

    protected function findPgRow(array $index, $platform, $name, $code)
    {
        $fullKey = $this->confirmedPgKey($platform, $name, $code);
        if (isset($index['full'][$fullKey])) {
            return $index['full'][$fullKey];
        }

        $codeKey = $this->confirmedPgCodeKey($platform, $code);
        return $index['code'][$codeKey] ?? null;
    }

    protected function confirmedPgKey($platform, $name, $code)
    {
        return trim((string)$platform) . '|' . $this->norm($name) . '|' . $this->codeNorm($code);
    }

    protected function confirmedPgCodeKey($platform, $code)
    {
        return trim((string)$platform) . '|' . $this->codeNorm($code);
    }

    protected function normalizeConfirmedCategory($category)
    {
        $category = strtolower(trim((string)$category));
        $numberMap = [
            '1' => 'realbet',
            '2' => 'fishing',
            '3' => 'concise',
            '4' => 'lottery',
            '5' => 'sport',
            '6' => 'joker',
            '7' => 'gaming',
        ];
        $category = $numberMap[$category] ?? $category;
        if (in_array($category, ['lhc', 'jsc', 'jwc', 'qkc'], true)) {
            return 'lottery';
        }

        return $category;
    }

    protected function mergeImportRows(array $baseRows, array $extraRows)
    {
        $merged = [];
        foreach (array_merge($baseRows, $extraRows) as $row) {
            $merged[$this->importRowKey($row)] = $row;
        }

        return array_values($merged);
    }

    protected function buildDownlineReport(array $match)
    {
        $expected = [];
        foreach (array_merge($match['exact'] ?? [], $match['name_only'] ?? []) as $row) {
            $code = strtoupper(trim($row['xh_code'] ?? ''));
            $category = trim($row['category'] ?? '');
            $gameCode = $this->codeNorm($row['xh']['gameCode'] ?? '');
            if ($code !== '' && $category !== '' && $gameCode !== '') {
                $expected[$code . '|' . $category . '|' . $gameCode] = true;
            }
        }

        $managedScopes = [];
        foreach ($this->platformMap as $map) {
            foreach ($map['codes'] as $code) {
                $managedScopes[strtoupper($code) . '|' . $map['category']] = true;
            }
        }

        $rows = GameList::where('site_state', 1)
            ->where('app_state', 1)
            ->orderBy('platform_name')
            ->orderBy('category_id')
            ->orderBy('game_code')
            ->get(['id', 'name', 'platform_name', 'category_id', 'game_code', 'site_state', 'app_state', 'is_top']);

        $items = [];
        $byPlatform = [];
        $hotKeys = $match['hot_keys'] ?? [];
        foreach ($rows as $row) {
            $scope = strtoupper(trim($row->platform_name)) . '|' . trim($row->category_id);
            if (!isset($managedScopes[$scope])) {
                continue;
            }

            $key = $scope . '|' . $this->codeNorm($row->game_code);
            if (isset($expected[$key])) {
                continue;
            }
            if (isset($hotKeys[$this->hotKey(['playCode' => $row->game_code, 'playName' => $row->name])])) {
                continue;
            }

            $platformKey = $row->platform_name . '|' . $row->category_id;
            $byPlatform[$platformKey] = ($byPlatform[$platformKey] ?? 0) + 1;
            $items[] = [
                'id' => $row->id,
                'platform_name' => $row->platform_name,
                'category_id' => $row->category_id,
                'game_code' => $row->game_code,
                'name' => $row->name,
                'site_state' => (int)$row->site_state,
                'app_state' => (int)$row->app_state,
                'is_top' => (int)$row->is_top,
                'reason' => 'active_local_game_not_in_pg51_matched_targets',
            ];
        }

        arsort($byPlatform);

        return [
            'generated_at' => date('c'),
            'note' => 'Review only. This command does not downline games automatically.',
            'count' => count($items),
            'by_platform_category' => $byPlatform,
            'items' => $items,
        ];
    }

    protected function importRowKey(array $row)
    {
        $pg = $row['pg51'] ?? [];
        $xh = $row['xh'] ?? [];
        return implode('|', [
            trim($pg['platformName'] ?? ''),
            $this->codeNorm($pg['playCode'] ?? ''),
            $this->norm($pg['playName'] ?? ''),
            strtoupper(trim($row['xh_code'] ?? '')),
            trim($row['category'] ?? ''),
            $this->codeNorm($xh['gameCode'] ?? ''),
        ]);
    }

    protected function loadCatalog($catalogPath)
    {
        if ($catalogPath) {
            $path = $this->resolvePath($catalogPath);
            if (!is_file($path)) {
                throw new \RuntimeException('XH catalog not found: ' . $path);
            }
            return $this->readJsonFile($path, 'XH catalog');
        }

        $this->apiUrl = rtrim(SystemConfig::getValue('game_api'), '/');
        $this->account = SystemConfig::getValue('merchant_account');
        $this->apiKey = SystemConfig::getValue('api_secret');

        return [
            'platforms' => $this->fetchPages('/ley/api_code/list'),
            'direct_games' => $this->fetchPages('/ley/api_code/game'),
            'sub_games' => $this->fetchPages('/ley/gamelist', [
                'status' => 1,
                'maintenance' => 1,
            ]),
        ];
    }

    protected function fetchPages($path, array $params = [])
    {
        $items = [];
        for ($page = 1; $page < 500; $page++) {
            $post = array_merge([
                'account' => $this->account,
                'api_key' => $this->apiKey,
                'currency' => 'CNY',
                'page' => $page,
                'pageSize' => 100,
            ], $params);
            $res = $this->sendRequest($path, $post);
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
            if (!$lastPage && count($list) < 100) {
                break;
            }
        }

        return $items;
    }

    protected function sendRequest($path, array $post)
    {
        $ch = curl_init($this->apiUrl . '/' . ltrim($path, '/'));
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
        curl_close($ch);
        if ($errno) {
            throw new \RuntimeException($error);
        }
        $data = json_decode($body, true);
        if (!is_array($data) || (string)($data['Code'] ?? '') !== '0') {
            throw new \RuntimeException('XH request failed: ' . substr((string)$body, 0, 200));
        }
        return $data;
    }

    protected function readCsv($path)
    {
        $rows = [];
        $fh = fopen($path, 'r');
        $headers = fgetcsv($fh);
        $index = 0;
        while (($row = fgetcsv($fh)) !== false) {
            $index++;
            if (count($row) !== count($headers)) {
                continue;
            }
            $item = array_combine($headers, $row);
            $item['_row_index'] = $index;
            $rows[] = $item;
        }
        fclose($fh);

        return $rows;
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

    protected function resolvePath($path)
    {
        if (preg_match('/^([A-Za-z]:)?[\/\\\\]/', $path)) {
            return $path;
        }
        return base_path($path);
    }

    protected function pg51Image($path)
    {
        $path = trim((string)$path);
        if ($path === '') {
            return '';
        }
        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }
        return 'https://emage.hnylqx.xyz/' . ltrim($path, '/');
    }

    protected function platformDisplayName(array $row)
    {
        $xh = $row['xh'];
        $pg = $row['pg51'];
        return $xh['title'] ?? ($pg['platformName'] ?? $row['xh_code']);
    }

    protected function hotKey(array $row)
    {
        return $this->codeNorm($row['playCode'] ?? '') . '|' . $this->norm($row['playName'] ?? '');
    }

    protected function norm($value)
    {
        $value = mb_strtolower((string)$value, 'UTF-8');
        return preg_replace('/[\s　\-_:：·.()（）\[\]【】]+/u', '', $value);
    }

    protected function codeNorm($value)
    {
        $value = trim((string)$value, " \t\n\r\0\x0B'\"");
        return strtolower($value);
    }

    protected function countBy(array $rows, callable $getter)
    {
        $data = [];
        foreach ($rows as $row) {
            $key = $getter($row);
            $data[$key] = ($data[$key] ?? 0) + 1;
        }
        arsort($data);
        return $data;
    }

    protected function isAssoc(array $data)
    {
        if ($data === []) {
            return false;
        }

        return array_keys($data) !== range(0, count($data) - 1);
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
