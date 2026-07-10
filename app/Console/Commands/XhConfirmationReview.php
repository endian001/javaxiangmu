<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class XhConfirmationReview extends Command
{
    protected $signature = 'XhConfirmationReview
        {--confirmation-dir=storage/app/pg51-sync/ops-review/xh-upstream-confirmation : Directory containing XH confirmation CSV replies}
        {--xh-catalog=storage/app/xh_catalog.json : XH catalog JSON}
        {--out-dir= : Output directory, defaults to confirmation-dir/review}';

    protected $description = 'Validate XH upstream confirmation replies and generate dry-run gate suggestions';

    protected $typeMap = [
        1 => 'realbet',
        2 => 'fishing',
        3 => 'concise',
        4 => 'lottery',
        5 => 'sport',
        6 => 'joker',
        7 => 'gaming',
    ];

    protected $validCategories = [
        'realbet',
        'fishing',
        'concise',
        'lottery',
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
        $confirmationDir = $this->resolvePath($this->option('confirmation-dir'));
        $catalogPath = $this->resolvePath($this->option('xh-catalog'));
        $outDir = $this->option('out-dir')
            ? $this->resolvePath($this->option('out-dir'))
            : $confirmationDir . '/review';

        if (!is_dir($confirmationDir)) {
            $this->error('Confirmation directory not found: ' . $confirmationDir);
            return 1;
        }
        if (!is_file($catalogPath)) {
            $this->error('XH catalog JSON not found: ' . $catalogPath);
            return 1;
        }
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $catalog = $this->readJsonFile($catalogPath, 'XH catalog');
        $index = $this->buildCatalogIndex($catalog);
        $manifest = $this->loadManifest($confirmationDir);

        $issues = [];
        $platformReview = [];
        $platformMap = $this->reviewPlatformMap($confirmationDir, $index, $platformReview, $issues);

        $gameReview = [];
        $confirmedGames = $this->reviewConfirmedGames($confirmationDir, $index, $gameReview, $issues);
        $gate = $this->buildConfirmationGate($confirmationDir, $manifest, $platformReview, $gameReview, $platformMap, $confirmedGames, $issues);
        $dryRunCandidates = $this->buildDryRunCandidates($outDir, $gate, $platformMap, $confirmedGames);
        $summary = [
            'manifest_remaining' => $gate['counts']['manifest_remaining'],
            'platform_not_confirmed' => $gate['counts']['platform_not_confirmed'],
            'mapped_but_xh_not_matched' => $gate['counts']['mapped_but_xh_not_matched'],
            'collision_manual_review' => $gate['counts']['collision_manual_review'],
            'category_conflict_fast_check' => $gate['counts']['category_conflict_fast_check'],
            'safe_auto_import_candidates' => $gate['counts']['safe_auto_import_candidates'],
            'auto_import_gate' => $gate['review_readiness']['auto_import_gate'],
            'review_readiness' => $gate['review_readiness']['state'],
            'platform_rows' => count($platformReview),
            'platform_ready' => count($platformMap),
            'confirmed_game_rows' => count($gameReview),
            'confirmed_games_ready' => count($confirmedGames),
            'ready_for_dry_run' => $gate['counts']['ready_for_dry_run'],
            'waiting_approval' => $gate['counts']['waiting_approval'],
            'must_hold' => $gate['counts']['must_hold'],
            'invalid' => $gate['counts']['invalid'],
            'issues' => count($issues),
            'import_gate' => $gate['import_gate'],
        ];

        $report = [
            'generated_at' => date('c'),
            'confirmation_dir' => $confirmationDir,
            'xh_catalog' => $catalogPath,
            'summary' => $summary,
            'counts' => $summary,
            'issues' => $issues,
        ];

        $this->writeJson($outDir . '/confirmation_review.json', $report);
        $this->writeCsv($outDir . '/platform_confirmation_review.csv', $platformReview, [
            'status',
            'pg_platform',
            'pg_category',
            'confirmed_codes',
            'confirmed_category',
            'message',
        ]);
        $this->writeCsv($outDir . '/confirmed_game_review.csv', $gameReview, [
            'status',
            'source_file',
            'pg_platform',
            'pg_category',
            'play_name',
            'play_code',
            'xh_code',
            'xh_category',
            'xh_game_code',
            'message',
        ]);
        $this->writeCsv($outDir . '/confirmation_review_issues.csv', $issues, [
            'severity',
            'source_file',
            'row',
            'code',
            'message',
        ]);
        $this->writeJson($outDir . '/platform_map_from_confirmation.json', $platformMap);
        $this->writeJson($outDir . '/confirmed_games_approved.json', [
            'generated_at' => date('c'),
            'note' => 'Use with SyncGame --confirmed-games-approved after reviewing XH replies. Empty items means no approved confirmed-game rows are ready.',
            'count' => count($confirmedGames),
            'items' => $confirmedGames,
        ]);
        $this->writeJson($outDir . '/confirmation_gate.json', $gate);
        $this->writeJson($outDir . '/pending_pool_summary.json', $gate['pending_pool_summary']);
        $this->writeJson($outDir . '/pending_pool_classification.json', $gate['pending_pool_classification']);
        $this->writeCsv($outDir . '/pending_pool_summary.csv', $gate['pending_pool_summary']['rows'], [
            'bucket',
            'manifest_count',
            'review_rows',
            'source_file',
            'gate',
            'readiness',
            'import_policy',
        ]);
        $this->writeCsv($outDir . '/pending_pool_classification.csv', $gate['pending_pool_classification']['rows'], [
            'bucket',
            'gate',
            'source_file',
            'risk_level',
            'item_count',
            'dry_run_eligible',
            'database_import_allowed_now',
            'required_actions',
            'sample_keys',
        ]);
        $this->writeCsv($outDir . '/pending_confirmation_pool.csv', $gate['items'], [
            'gate',
            'status',
            'source_file',
            'pg_platform',
            'pg_category',
            'play_name',
            'play_code',
            'expected_xh_codes',
            'xh_code',
            'xh_category',
            'xh_game_code',
            'risk_level',
            'required_action',
            'message',
        ]);
        $this->writeJson($outDir . '/dry_run_candidates.json', $dryRunCandidates);
        $this->writeCsv($outDir . '/dry_run_command_safety.csv', $dryRunCandidates['command_safety']['rows'], [
            'name',
            'has_command',
            'has_dry_run',
            'has_confirmation_gate',
            'has_import_flag',
            'has_publish_flag',
            'passed',
            'command',
        ]);
        $this->writeJson($outDir . '/review_readiness.json', $gate['review_readiness']);
        $this->writeGateMarkdown($outDir . '/confirmation_gate.md', $gate, $dryRunCandidates);
        $this->writeReviewReadinessMarkdown($outDir . '/review_readiness.md', $gate);
        $this->writeNextCommands($outDir, $gate);

        $this->info(sprintf(
            'remaining=%d safe_auto_import_candidates=%d auto_import_gate=%s review_readiness=%s platform_not_confirmed=%d mapped_but_xh_not_matched=%d collision_manual_review=%d category_conflict_fast_check=%d ready_for_dry_run=%d must_hold=%d issues=%d gate=%s',
            $gate['counts']['manifest_remaining'],
            $gate['counts']['safe_auto_import_candidates'],
            $gate['review_readiness']['auto_import_gate'],
            $gate['review_readiness']['state'],
            $gate['counts']['platform_not_confirmed'],
            $gate['counts']['mapped_but_xh_not_matched'],
            $gate['counts']['collision_manual_review'],
            $gate['counts']['category_conflict_fast_check'],
            $gate['counts']['ready_for_dry_run'],
            $gate['counts']['must_hold'],
            count($issues),
            $gate['import_gate']
        ));
        $this->info(sprintf(
            'platform_ready=%d confirmed_games_ready=%d waiting_approval=%d invalid=%d pending_pool_review_rows=%d pending_pool_manifest_total=%d database_import_allowed_now=%s',
            count($platformMap),
            count($confirmedGames),
            $gate['counts']['waiting_approval'],
            $gate['counts']['invalid'],
            $gate['counts']['total_review_rows'],
            $gate['pending_pool_summary']['manifest_total'],
            $gate['review_readiness']['database_import_allowed_now'] ? 'yes' : 'no'
        ));
        if ($gate['review_readiness']['safe_auto_import_hold']) {
            $this->warn($gate['review_readiness']['summary']);
        }
        $this->comment('Reports: ' . $outDir);

        return 0;
    }

    protected function reviewPlatformMap($confirmationDir, array $index, array &$review, array &$issues)
    {
        $path = $confirmationDir . '/01_platform_mapping_request.csv';
        if (!is_file($path)) {
            $this->addIssue($issues, 'critical', '01_platform_mapping_request.csv', 0, 'missing_file', 'Platform mapping confirmation CSV is missing.');
            return [];
        }

        $items = [];
        foreach ($this->readCsv($path) as $i => $row) {
            $rowNum = $i + 2;
            $approved = $this->truthy($row['approved_for_import'] ?? '');
            $pgPlatform = trim((string)($row['pg_platform'] ?? ''));
            $pgCategory = trim((string)($row['pg_category'] ?? ''));
            $category = $this->normalizeCategory($row['xh_confirmed_category'] ?? ($row['target_category_to_confirm'] ?? $pgCategory));
            $codes = $this->parseCodes($row['xh_confirmed_codes'] ?? '');

            if (!$approved) {
                $review[] = $this->platformReviewRow('waiting_approval', $pgPlatform, $pgCategory, $codes, $category, 'approved_for_import is not true.');
                continue;
            }

            $messages = [];
            if ($pgPlatform === '') {
                $messages[] = 'pg_platform is required';
            }
            if ($category === '' || !in_array($category, $this->validCategories, true)) {
                $messages[] = 'valid xh_confirmed_category is required';
            }
            if (!$codes) {
                $messages[] = 'xh_confirmed_codes is required';
            }
            foreach ($codes as $code) {
                if (empty($index['platform_codes'][$code])) {
                    $messages[] = 'inactive_or_missing_platform:' . $code;
                    continue;
                }
                if (empty($index['game_scopes'][$code . '|' . $category])) {
                    $messages[] = 'no_active_games_for_scope:' . $code . '|' . $category;
                }
            }

            if ($messages) {
                $message = implode('; ', $messages);
                $review[] = $this->platformReviewRow('invalid', $pgPlatform, $pgCategory, $codes, $category, $message);
                $this->addIssue($issues, 'warning', '01_platform_mapping_request.csv', $rowNum, 'invalid_platform_confirmation', $message);
                continue;
            }

            $items[] = [
                'pg_platform' => $pgPlatform,
                'category' => $category,
                'codes' => $codes,
                'approved' => true,
                'count' => (int)($row['pg_missing_count'] ?? 0),
                'source' => 'xh_upstream_confirmation',
                'note' => 'Generated by XhConfirmationReview from upstream-confirmed platform mapping.',
            ];
            $review[] = $this->platformReviewRow('ready', $pgPlatform, $pgCategory, $codes, $category, 'Ready for SyncGame --platform-map dry-run.');
        }

        return $items;
    }

    protected function reviewConfirmedGames($confirmationDir, array $index, array &$review, array &$issues)
    {
        $files = [
            '03_mapped_missing_game_request.csv',
            '04_category_conflict_fast_check.csv',
            '05_collision_manual_review.csv',
        ];
        $items = [];

        foreach ($files as $file) {
            $path = $confirmationDir . '/' . $file;
            if (!is_file($path)) {
                $this->addIssue($issues, 'warning', $file, 0, 'missing_file', 'Confirmed-game CSV is missing.');
                continue;
            }

            foreach ($this->readCsv($path) as $i => $row) {
                $rowNum = $i + 2;
                $approved = $this->truthy($row['approved_for_import'] ?? '');
                $pgPlatform = trim((string)($row['pg_platform'] ?? ''));
                $pgCategory = trim((string)($row['pg_category'] ?? ''));
                $playName = trim((string)($row['play_name'] ?? ''));
                $playCode = trim((string)($row['play_code'] ?? ''));
                $xhCode = strtoupper(trim((string)($row['xh_confirmed_code'] ?? ($row['expected_xh_code'] ?? ''))));
                $xhCategory = $this->normalizeCategory($row['xh_confirmed_category'] ?? ($row['xh_category'] ?? ''));
                $xhGameCode = trim((string)($row['xh_confirmed_game_code'] ?? ($row['xh_game_code'] ?? '')));
                $xhTitle = trim((string)($row['xh_confirmed_title'] ?? ''));

                if (!$approved) {
                    $review[] = $this->gameReviewRow('waiting_approval', $file, $pgPlatform, $pgCategory, $playName, $playCode, $xhCode, $xhCategory, $xhGameCode, 'approved_for_import is not true.');
                    continue;
                }

                $messages = [];
                if ($pgPlatform === '' || $playName === '' || $playCode === '') {
                    $messages[] = 'pg_platform, play_name and play_code are required';
                }
                if ($xhCode === '') {
                    $messages[] = 'xh_confirmed_code is required';
                }
                if ($xhCategory === '' || !in_array($xhCategory, $this->validCategories, true)) {
                    $messages[] = 'valid xh_confirmed_category is required';
                }
                if ($xhGameCode === '') {
                    $messages[] = 'xh_confirmed_game_code is required';
                }
                if ($xhTitle === '') {
                    $messages[] = 'xh_confirmed_title is required';
                }
                if ($xhCode !== '' && empty($index['platform_codes'][$xhCode])) {
                    $messages[] = 'inactive_or_missing_platform:' . $xhCode;
                }
                if ($file === '04_category_conflict_fast_check.csv' && !$this->truthy($row['category_conflict_confirmed'] ?? ($row['xh_category_authoritative'] ?? ''))) {
                    $messages[] = 'category conflict requires category_conflict_confirmed=true or xh_category_authoritative=true';
                }
                if ($file === '05_collision_manual_review.csv' && !$this->truthy($row['collision_manual_confirmed'] ?? ($row['manual_review_confirmed'] ?? ''))) {
                    $messages[] = 'collision review requires collision_manual_confirmed=true or manual_review_confirmed=true';
                }

                $gameKey = $xhCode . '|' . $xhCategory . '|' . $this->codeNorm($xhGameCode);
                $game = $index['games_exact'][$gameKey] ?? null;
                if (!$game) {
                    $messages[] = 'active_xh_game_not_found:' . $gameKey;
                }

                if ($messages) {
                    $message = implode('; ', $messages);
                    $review[] = $this->gameReviewRow('invalid', $file, $pgPlatform, $pgCategory, $playName, $playCode, $xhCode, $xhCategory, $xhGameCode, $message);
                    $this->addIssue($issues, 'warning', $file, $rowNum, 'invalid_confirmed_game', $message);
                    continue;
                }

                $items[] = [
                    'approved' => true,
                    'pg_platform' => $pgPlatform,
                    'pg_category' => $pgCategory,
                    'pg_play_name' => $playName,
                    'pg_play_code' => $playCode,
                    'xh_code' => $xhCode,
                    'xh_category' => $xhCategory,
                    'xh_game_code' => $xhGameCode,
                    'xh_title' => $xhTitle,
                    'xh_game_type' => (int)($game['gameType'] ?? 0),
                    'confidence' => 'upstream_confirmed',
                    'source_file' => $file,
                    'category_conflict_confirmed' => $file === '04_category_conflict_fast_check.csv' ? true : false,
                    'collision_manual_confirmed' => $file === '05_collision_manual_review.csv' ? true : false,
                    'upstream_note' => $row['upstream_note'] ?? '',
                ];
                $review[] = $this->gameReviewRow('ready', $file, $pgPlatform, $pgCategory, $playName, $playCode, $xhCode, $xhCategory, $xhGameCode, 'Ready for SyncGame --confirmed-games-approved dry-run.');
            }
        }

        return $items;
    }

    protected function loadManifest($confirmationDir)
    {
        $path = $confirmationDir . '/manifest.json';
        if (!is_file($path)) {
            return [
                'exists' => false,
                'path' => $path,
                'counts' => [],
            ];
        }

        $manifest = $this->readJsonFile($path, 'XH confirmation manifest');
        $manifest['exists'] = true;
        $manifest['path'] = $path;

        return $manifest;
    }

    protected function buildConfirmationGate($confirmationDir, array $manifest, array $platformReview, array $gameReview, array $platformMap, array $confirmedGames, array &$issues)
    {
        $sourceRows = $this->buildSourceRows($confirmationDir);
        $sourceCsvIntegrity = $this->sourceCsvIntegrity($confirmationDir, $manifest);
        $items = [];
        $counts = [
            'manifest_remaining' => (int)($manifest['counts']['remaining'] ?? 0),
            'platform_not_confirmed' => (int)($manifest['counts']['platform_not_confirmed'] ?? 0),
            'mapped_but_xh_not_matched' => (int)($manifest['counts']['mapped_but_xh_not_matched'] ?? 0),
            'collision_manual_review' => (int)($manifest['counts']['collision_manual_review'] ?? 0),
            'category_conflict_fast_check' => (int)($manifest['counts']['category_conflict_fast_check'] ?? 0),
            'safe_auto_import_candidates' => (int)($manifest['counts']['safe_auto_import_candidates'] ?? 0),
            'platform_ready' => count($platformMap),
            'confirmed_games_ready' => count($confirmedGames),
            'ready_for_dry_run' => 0,
            'waiting_approval' => 0,
            'must_hold' => 0,
            'invalid' => 0,
            'total_review_rows' => count($platformReview) + count($gameReview),
            'source_csv_integrity_failures' => 0,
        ];

        foreach (($sourceCsvIntegrity['issues'] ?? []) as $issue) {
            $this->addIssue($issues, $issue['severity'], $issue['source_file'], $issue['row'], $issue['code'], $issue['message']);
        }
        $counts['source_csv_integrity_failures'] = (int)($sourceCsvIntegrity['failure_count'] ?? 0);
        if ($counts['source_csv_integrity_failures'] > 0) {
            $counts['invalid'] += $counts['source_csv_integrity_failures'];
        }

        foreach ($platformReview as $row) {
            $sourceFile = '01_platform_mapping_request.csv';
            $source = $sourceRows[$sourceFile][$this->sourceKey($row['pg_platform'] ?? '', '', '', $row['pg_category'] ?? '')] ?? [];
            [$gate, $requiredAction] = $this->platformGate($row['status'] ?? '', $row['message'] ?? '');
            $this->incrementGateCounts($counts, $gate);
            $items[] = $this->gateItem($gate, $row['status'] ?? '', $sourceFile, $row, $source, $requiredAction);
        }

        foreach ($gameReview as $row) {
            $sourceFile = $row['source_file'] ?? '';
            $source = $sourceRows[$sourceFile][$this->sourceKey($row['pg_platform'] ?? '', $row['play_name'] ?? '', $row['play_code'] ?? '', $row['pg_category'] ?? '')] ?? [];
            [$gate, $requiredAction] = $this->gameGate($row['status'] ?? '', $sourceFile, $row['message'] ?? '', $source);
            $this->incrementGateCounts($counts, $gate);
            $items[] = $this->gateItem($gate, $row['status'] ?? '', $sourceFile, $row, $source, $requiredAction);
        }

        $pendingPoolSummary = $this->buildPendingPoolSummary($counts, $items);
        $pendingPoolClassification = $this->buildPendingPoolClassification($counts, $items, $pendingPoolSummary);
        $reconciliationIssues = $this->pendingPoolReconciliationIssues($counts, $pendingPoolSummary);
        foreach ($reconciliationIssues as $message) {
            $this->addIssue($issues, 'critical', 'confirmation_gate.json', 0, 'pending_pool_reconciliation_failed', $message);
        }
        $counts['reconciliation_issues'] = count($reconciliationIssues);
        if ($reconciliationIssues) {
            $counts['invalid'] += count($reconciliationIssues);
        }

        $importGate = 'waiting_for_xh_confirmation';
        if ($counts['source_csv_integrity_failures'] > 0 || $reconciliationIssues) {
            $importGate = 'hold_invalid_confirmation';
        } elseif ($counts['ready_for_dry_run'] > 0) {
            $importGate = $counts['must_hold'] > 0 ? 'dry_run_ready_with_holds' : 'dry_run_ready';
        } elseif ($counts['manifest_remaining'] === 0 && $counts['total_review_rows'] === 0) {
            $importGate = 'no_pending_pg51_gap';
        } elseif ($counts['invalid'] > 0 && $counts['waiting_approval'] === 0) {
            $importGate = 'hold_invalid_confirmation';
        }

        $reviewReadiness = $this->buildReviewReadiness($counts, $importGate, count($issues), $pendingPoolSummary);

        return [
            'generated_at' => date('c'),
            'import_gate' => $importGate,
            'auto_import_gate' => $reviewReadiness['auto_import_gate'],
            'review_readiness' => $reviewReadiness,
            'pending_pool_summary' => $pendingPoolSummary,
            'pending_pool_classification' => $pendingPoolClassification,
            'source_csv_integrity' => $sourceCsvIntegrity,
            'manifest' => [
                'exists' => (bool)($manifest['exists'] ?? false),
                'path' => $manifest['path'] ?? '',
                'counts' => $manifest['counts'] ?? [],
            ],
            'counts' => $counts,
            'by_gate' => $this->countGateItems($items, 'gate'),
            'by_platform' => $this->countGateItems($items, 'pg_platform'),
            'issues_count' => count($issues),
            'guardrails' => [
                'safe_auto_import_candidates_must_be_zero_or_reviewed' => $counts['safe_auto_import_candidates'] === 0,
                'blind_import_allowed' => false,
                'dry_run_required_before_import' => true,
                'production_audits_required_before_import' => [
                    'GameOpsAudit',
                    'XhApiOpsAudit',
                    'FrontendOpsAudit',
                ],
            ],
            'items' => $items,
        ];
    }

    protected function buildPendingPoolSummary(array $counts, array $items)
    {
        $bySource = $this->countGateItems($items, 'source_file');
        $manifestTotal = (int)($counts['manifest_remaining'] ?? 0);
        $ready = (int)($counts['ready_for_dry_run'] ?? 0);
        $conflictDetail = $this->conflictUnionDetail($items, $manifestTotal, $counts);
        $conflictUnion = $conflictDetail['union'];
        $upstreamPlain = max(0, $manifestTotal - $ready - $conflictUnion);
        $conflictReviewRows = (int)($bySource['04_category_conflict_fast_check.csv'] ?? 0) + (int)($bySource['05_collision_manual_review.csv'] ?? 0);
        $plainReviewRows = max(0, (int)($counts['total_review_rows'] ?? 0) - $conflictReviewRows);
        $rows = [
            [
                'bucket' => 'ready',
                'manifest_count' => $ready,
                'review_rows' => $ready,
                'source_file' => 'confirmation_gate.json',
                'gate' => $ready > 0 ? 'READY_FOR_DRY_RUN' : 'HOLD_NO_READY_ROWS',
                'readiness' => $ready > 0 ? 'DRY_RUN_ONLY' : 'HOLD',
                'import_policy' => 'READY rows may enter SyncGame dry-run only; this does not authorize import or publish.',
            ],
            [
                'bucket' => 'upstream_plain',
                'manifest_count' => $upstreamPlain,
                'review_rows' => $plainReviewRows,
                'source_file' => '01_platform_mapping_request.csv|03_mapped_missing_game_request.csv',
                'gate' => 'WAIT_XH_CONFIRMATION',
                'readiness' => 'HOLD',
                'import_policy' => 'No blind import. These non-conflict rows still need upstream-confirmed code/category/gameCode before dry-run.',
            ],
            [
                'bucket' => 'conflict_union',
                'manifest_count' => $conflictUnion,
                'review_rows' => $conflictUnion,
                'source_file' => '04_category_conflict_fast_check.csv|05_collision_manual_review.csv',
                'gate' => 'HOLD_CONFLICT_UNION',
                'readiness' => 'HOLD',
                'import_policy' => 'No import while category conflicts or collision review overlap remains unresolved.',
            ],
            [
                'bucket' => 'platform_not_confirmed',
                'manifest_count' => (int)($counts['platform_not_confirmed'] ?? 0),
                'review_rows' => (int)($bySource['01_platform_mapping_request.csv'] ?? 0),
                'source_file' => '01_platform_mapping_request.csv',
                'gate' => 'WAIT_XH_PLATFORM_CONFIRMATION',
                'readiness' => 'HOLD',
                'import_policy' => 'No import. XH/upstream must confirm active api_code and category before any dry-run candidate can be generated.',
            ],
            [
                'bucket' => 'mapped_but_xh_not_matched',
                'manifest_count' => (int)($counts['mapped_but_xh_not_matched'] ?? 0),
                'review_rows' => (int)($bySource['03_mapped_missing_game_request.csv'] ?? 0),
                'source_file' => '03_mapped_missing_game_request.csv',
                'gate' => 'WAIT_XH_GAME_CONFIRMATION',
                'readiness' => 'HOLD',
                'import_policy' => 'No blind import. XH/upstream must confirm exact code/category/gameCode/title before dry-run.',
            ],
            [
                'bucket' => 'collision_manual_review',
                'manifest_count' => (int)($counts['collision_manual_review'] ?? 0),
                'review_rows' => (int)($bySource['05_collision_manual_review.csv'] ?? 0),
                'source_file' => '05_collision_manual_review.csv',
                'gate' => 'HOLD_COLLISION_MANUAL_REVIEW',
                'readiness' => 'HOLD',
                'import_policy' => 'No name-only or same-code import. Confirm exact vendor/code/gameCode manually and upstream first.',
            ],
            [
                'bucket' => 'category_conflict_fast_check',
                'manifest_count' => (int)($counts['category_conflict_fast_check'] ?? 0),
                'review_rows' => (int)($bySource['04_category_conflict_fast_check.csv'] ?? 0),
                'source_file' => '04_category_conflict_fast_check.csv',
                'gate' => 'HOLD_CATEGORY_CONFLICT_CONFIRMATION',
                'readiness' => 'HOLD',
                'import_policy' => 'No import until XH category/gameType authority is confirmed for the exact game.',
            ],
            [
                'bucket' => 'safe_auto_import_candidates',
                'manifest_count' => (int)($counts['safe_auto_import_candidates'] ?? 0),
                'review_rows' => 0,
                'source_file' => 'manifest.json',
                'gate' => (int)($counts['safe_auto_import_candidates'] ?? 0) === 0 ? 'HOLD_NO_AUTO_CANDIDATES' : 'REVIEW_REQUIRED',
                'readiness' => (int)($counts['safe_auto_import_candidates'] ?? 0) === 0 ? 'HOLD' : 'REVIEW_REQUIRED',
                'import_policy' => 'This bucket never authorizes import. Non-zero candidates still require explicit review and dry-run gate output.',
            ],
        ];

        return [
            'generated_at' => date('c'),
            'manifest_total' => $manifestTotal,
            'ready' => $ready,
            'upstream_plain' => $upstreamPlain,
            'conflict_union' => $conflictUnion,
            'review_row_total' => (int)($counts['total_review_rows'] ?? 0),
            'safe_auto_import_candidates' => (int)($counts['safe_auto_import_candidates'] ?? 0),
            'auto_import_gate' => ($manifestTotal > 0 && (int)($counts['safe_auto_import_candidates'] ?? 0) === 0) ? 'HOLD' : 'REVIEW_REQUIRED',
            'database_import_allowed_now' => false,
            'note' => 'ready/upstream_plain/conflict_union are PG51 game counts. conflict_union deduplicates rows that appear in both category conflict and collision review files.',
            'conflict_union_detail' => $conflictDetail,
            'reconciliation' => [
                'ready_plus_upstream_plain_plus_conflict_union_equals_manifest_total' => ($ready + $upstreamPlain + $conflictUnion) === $manifestTotal,
                'conflict_union_matches_category_plus_collision_minus_overlap' => $conflictUnion === ($conflictDetail['category_conflict_fast_check'] + $conflictDetail['collision_manual_review'] - $conflictDetail['overlap']),
                'ready_matches_counts_ready_for_dry_run' => $ready === (int)($counts['ready_for_dry_run'] ?? 0),
                'database_import_allowed_now' => false,
            ],
            'rows' => $rows,
        ];
    }

    protected function buildPendingPoolClassification(array $counts, array $items, array $pendingPoolSummary)
    {
        $groups = [];
        foreach ($items as $item) {
            $bucket = $this->classificationBucket($item);
            $gate = (string)($item['gate'] ?? '');
            $sourceFile = (string)($item['source_file'] ?? '');
            $riskLevel = (string)($item['risk_level'] ?? '');
            $key = implode('|', [$bucket, $gate, $sourceFile, $riskLevel]);

            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'bucket' => $bucket,
                    'gate' => $gate,
                    'source_file' => $sourceFile,
                    'risk_level' => $riskLevel,
                    'item_count' => 0,
                    'dry_run_eligible' => $gate === 'READY_FOR_DRY_RUN' ? 1 : 0,
                    'database_import_allowed_now' => 0,
                    'required_actions' => [],
                    'sample_keys' => [],
                ];
            }

            $groups[$key]['item_count']++;
            $requiredAction = trim((string)($item['required_action'] ?? ''));
            if ($requiredAction !== '' && count($groups[$key]['required_actions']) < 6) {
                $groups[$key]['required_actions'][$requiredAction] = true;
            }
            if (count($groups[$key]['sample_keys']) < 8) {
                $groups[$key]['sample_keys'][] = $this->gateItemIdentity($item);
            }
        }

        $rows = array_values(array_map(function ($row) {
            $row['required_actions'] = implode(' || ', array_keys($row['required_actions']));
            $row['sample_keys'] = implode(' || ', $row['sample_keys']);
            return $row;
        }, $groups));

        usort($rows, function ($a, $b) {
            if ((int)$a['dry_run_eligible'] !== (int)$b['dry_run_eligible']) {
                return (int)$a['dry_run_eligible'] < (int)$b['dry_run_eligible'] ? 1 : -1;
            }
            if ((int)$a['item_count'] !== (int)$b['item_count']) {
                return (int)$a['item_count'] < (int)$b['item_count'] ? 1 : -1;
            }
            return strcmp($a['bucket'] . $a['gate'], $b['bucket'] . $b['gate']);
        });

        return [
            'generated_at' => date('c'),
            'database_import_allowed_now' => false,
            'blind_import_allowed' => false,
            'dry_run_only' => true,
            'manifest_remaining' => (int)($counts['manifest_remaining'] ?? 0),
            'ready_for_dry_run' => (int)($counts['ready_for_dry_run'] ?? 0),
            'waiting_approval' => (int)($counts['waiting_approval'] ?? 0),
            'must_hold' => (int)($counts['must_hold'] ?? 0),
            'invalid' => (int)($counts['invalid'] ?? 0),
            'pending_pool_summary' => [
                'ready' => (int)($pendingPoolSummary['ready'] ?? 0),
                'upstream_plain' => (int)($pendingPoolSummary['upstream_plain'] ?? 0),
                'conflict_union' => (int)($pendingPoolSummary['conflict_union'] ?? 0),
            ],
            'rows' => $rows,
        ];
    }

    protected function classificationBucket(array $item)
    {
        $sourceFile = (string)($item['source_file'] ?? '');
        $gate = (string)($item['gate'] ?? '');
        if ($gate === 'READY_FOR_DRY_RUN') {
            return 'ready';
        }
        if ($sourceFile === '01_platform_mapping_request.csv') {
            return 'platform_not_confirmed';
        }
        if ($sourceFile === '03_mapped_missing_game_request.csv') {
            return 'mapped_but_xh_not_matched';
        }
        if ($sourceFile === '04_category_conflict_fast_check.csv') {
            return 'category_conflict_fast_check';
        }
        if ($sourceFile === '05_collision_manual_review.csv') {
            return 'collision_manual_review';
        }

        return 'other';
    }

    protected function gateItemIdentity(array $item)
    {
        return implode('/', array_filter([
            $item['pg_platform'] ?? '',
            $item['pg_category'] ?? '',
            $item['play_name'] ?? '',
            $item['play_code'] ?? '',
        ], function ($value) {
            return trim((string)$value) !== '';
        }));
    }

    protected function conflictUnionDetail(array $items, $manifestTotal, array $counts)
    {
        $categoryKeys = [];
        $collisionKeys = [];
        foreach ($items as $item) {
            $sourceFile = $item['source_file'] ?? '';
            if (!in_array($sourceFile, ['04_category_conflict_fast_check.csv', '05_collision_manual_review.csv'], true)) {
                continue;
            }
            $key = $this->sourceKey($item['pg_platform'] ?? '', $item['play_name'] ?? '', $item['play_code'] ?? '', $item['pg_category'] ?? '');
            if ($key === '|||') {
                continue;
            }
            if ($sourceFile === '04_category_conflict_fast_check.csv') {
                $categoryKeys[$key] = true;
            }
            if ($sourceFile === '05_collision_manual_review.csv') {
                $collisionKeys[$key] = true;
            }
        }

        $categoryCount = count($categoryKeys);
        $collisionCount = count($collisionKeys);
        if ($categoryCount === 0 && (int)($counts['category_conflict_fast_check'] ?? 0) > 0) {
            $categoryCount = (int)$counts['category_conflict_fast_check'];
        }
        if ($collisionCount === 0 && (int)($counts['collision_manual_review'] ?? 0) > 0) {
            $collisionCount = (int)$counts['collision_manual_review'];
        }

        $overlap = count(array_intersect_key($categoryKeys, $collisionKeys));
        $union = $categoryCount + $collisionCount - $overlap;
        if ($union === 0 && ($categoryCount > 0 || $collisionCount > 0)) {
            $union = min((int)$manifestTotal, $categoryCount + $collisionCount);
        }

        return [
            'category_conflict_fast_check' => $categoryCount,
            'collision_manual_review' => $collisionCount,
            'overlap' => $overlap,
            'union' => $union,
        ];
    }

    protected function pendingPoolReconciliationIssues(array $counts, array $summary)
    {
        $issues = [];
        $manifestTotal = (int)($summary['manifest_total'] ?? 0);
        $ready = (int)($summary['ready'] ?? 0);
        $upstreamPlain = (int)($summary['upstream_plain'] ?? 0);
        $conflictUnion = (int)($summary['conflict_union'] ?? 0);
        $detail = $summary['conflict_union_detail'] ?? [];
        $categoryCount = (int)($detail['category_conflict_fast_check'] ?? 0);
        $collisionCount = (int)($detail['collision_manual_review'] ?? 0);
        $overlap = (int)($detail['overlap'] ?? 0);

        if (($ready + $upstreamPlain + $conflictUnion) !== $manifestTotal) {
            $issues[] = 'ready + upstream_plain + conflict_union must equal manifest_total.';
        }
        if ($conflictUnion !== ($categoryCount + $collisionCount - $overlap)) {
            $issues[] = 'conflict_union must equal category_conflict_fast_check + collision_manual_review - overlap.';
        }
        if ($ready !== (int)($counts['ready_for_dry_run'] ?? 0)) {
            $issues[] = 'pending_pool_summary.ready must match counts.ready_for_dry_run.';
        }

        return $issues;
    }

    protected function buildReviewReadiness(array $counts, $importGate, $issuesCount, array $pendingPoolSummary)
    {
        $remaining = (int)($counts['manifest_remaining'] ?? 0);
        $safeAuto = (int)($counts['safe_auto_import_candidates'] ?? 0);
        $ready = (int)($counts['ready_for_dry_run'] ?? 0);
        $waiting = (int)($counts['waiting_approval'] ?? 0);
        $mustHold = (int)($counts['must_hold'] ?? 0);
        $invalid = (int)($counts['invalid'] ?? 0);
        $sourceCsvFailures = (int)($counts['source_csv_integrity_failures'] ?? 0);
        $reconciliationIssues = (int)($counts['reconciliation_issues'] ?? 0);
        $safeAutoHold = $remaining > 0 && $safeAuto === 0;
        $dryRunBlockers = [];
        if ($ready <= 0) {
            $dryRunBlockers[] = 'no_ready_for_dry_run_rows';
        }
        if ($invalid > 0) {
            $dryRunBlockers[] = 'invalid_confirmation_rows';
        }
        if ($sourceCsvFailures > 0) {
            $dryRunBlockers[] = 'source_csv_integrity_failures';
        }
        if ($reconciliationIssues > 0) {
            $dryRunBlockers[] = 'pending_pool_reconciliation_failed';
        }
        $blockingDryRunIssues = array_diff($dryRunBlockers, ['no_ready_for_dry_run_rows']);
        $dryRunAllowed = $ready > 0 && count($blockingDryRunIssues) === 0;

        $state = 'HOLD';
        $nextAction = 'Wait for XH/upstream confirmation; do not import or publish any row from this pool.';
        if ($invalid > 0 || $sourceCsvFailures > 0 || $reconciliationIssues > 0) {
            $state = 'HOLD_INVALID_CONFIRMATION';
            $nextAction = 'Fix invalid confirmation rows, source CSV integrity, and pending-pool reconciliation before any dry-run.';
        } elseif ($ready > 0) {
            $state = ($waiting > 0 || $mustHold > 0 || $invalid > 0)
                ? 'DRY_RUN_READY_WITH_HOLDS'
                : 'DRY_RUN_READY';
            $nextAction = 'Run SyncGame dry-run only, then review dry-run output and all ops audits before requesting separate import approval.';
        } elseif ($remaining === 0 && (int)($counts['total_review_rows'] ?? 0) === 0) {
            $state = 'NO_PENDING_GAP';
            $nextAction = 'No pending PG51 gap was found.';
        } elseif ($invalid > 0) {
            $state = 'HOLD_INVALID_CONFIRMATION';
            $nextAction = 'Fix invalid approved confirmation rows before any dry-run.';
        }

        $autoImportGate = 'REVIEW_REQUIRED';
        if ($safeAutoHold) {
            $autoImportGate = 'HOLD';
        } elseif ($remaining === 0 && $safeAuto === 0) {
            $autoImportGate = 'CLEAR';
        }

        $breakdown = [
            'remaining' => $remaining,
            'platform_not_confirmed' => (int)($counts['platform_not_confirmed'] ?? 0),
            'mapped_but_xh_not_matched' => (int)($counts['mapped_but_xh_not_matched'] ?? 0),
            'collision_manual_review' => (int)($counts['collision_manual_review'] ?? 0),
            'category_conflict_fast_check' => (int)($counts['category_conflict_fast_check'] ?? 0),
            'safe_auto_import_candidates' => $safeAuto,
            'pending_pool_review_rows' => (int)($counts['total_review_rows'] ?? 0),
            'source_csv_integrity_failures' => (int)($counts['source_csv_integrity_failures'] ?? 0),
            'ready' => (int)($pendingPoolSummary['ready'] ?? $ready),
            'upstream_plain' => (int)($pendingPoolSummary['upstream_plain'] ?? 0),
            'conflict_union' => (int)($pendingPoolSummary['conflict_union'] ?? 0),
        ];

        return [
            'generated_at' => date('c'),
            'state' => $state,
            'import_gate' => $importGate,
            'auto_import_gate' => $autoImportGate,
            'safe_auto_import_hold' => $safeAutoHold,
            'dry_run_allowed' => $dryRunAllowed,
            'dry_run_blockers' => $dryRunBlockers,
            'database_import_allowed_now' => false,
            'blind_import_allowed' => false,
            'pending_confirmation_pool' => $breakdown,
            'ready_for_dry_run' => $ready,
            'waiting_approval' => $waiting,
            'must_hold' => $mustHold,
            'invalid' => $invalid,
            'issues_count' => (int)$issuesCount,
            'required_next_action' => $nextAction,
            'summary' => sprintf(
                '%s: ready=%d; upstream_plain=%d; conflict_union=%d; safe_auto_import_candidates=%d; pending confirmation pool remaining=%d (platform_not_confirmed=%d, mapped_but_xh_not_matched=%d, collision_manual_review=%d, category_conflict_fast_check=%d).',
                $autoImportGate,
                $breakdown['ready'],
                $breakdown['upstream_plain'],
                $breakdown['conflict_union'],
                $safeAuto,
                $remaining,
                $breakdown['platform_not_confirmed'],
                $breakdown['mapped_but_xh_not_matched'],
                $breakdown['collision_manual_review'],
                $breakdown['category_conflict_fast_check']
            ),
        ];
    }

    protected function buildSourceRows($confirmationDir)
    {
        $files = [
            '01_platform_mapping_request.csv',
            '03_mapped_missing_game_request.csv',
            '04_category_conflict_fast_check.csv',
            '05_collision_manual_review.csv',
        ];
        $rows = [];

        foreach ($files as $file) {
            $path = $confirmationDir . '/' . $file;
            $rows[$file] = [];
            if (!is_file($path)) {
                continue;
            }
            foreach ($this->readCsv($path) as $row) {
                if ($file === '01_platform_mapping_request.csv') {
                    $key = $this->sourceKey($row['pg_platform'] ?? '', '', '', $row['pg_category'] ?? '');
                } else {
                    $key = $this->sourceKey($row['pg_platform'] ?? '', $row['play_name'] ?? '', $row['play_code'] ?? '', $row['pg_category'] ?? '');
                }
                $rows[$file][$key] = $row;
            }
        }

        return $rows;
    }

    protected function sourceCsvIntegrity($confirmationDir, array $manifest)
    {
        $manifestCounts = $manifest['counts'] ?? [];
        $files = [];
        $issues = [];
        $failureCount = 0;

        foreach ($this->confirmationFiles as $file => $config) {
            $path = $confirmationDir . '/' . $file;
            $expected = (int)($manifestCounts[$config['manifest_key']] ?? 0);
            $details = $this->inspectConfirmationCsv($path, $config['required_columns'], $expected);
            $files[$file] = $details;

            foreach (($details['issues'] ?? []) as $issue) {
                $issues[] = $issue;
            }
            if ((int)($details['failure_count'] ?? 0) > 0) {
                $failureCount += (int)$details['failure_count'];
            }
        }

        return [
            'generated_at' => date('c'),
            'failure_count' => $failureCount,
            'files' => $files,
            'issues' => $issues,
        ];
    }

    protected function inspectConfirmationCsv($path, array $requiredColumns, $expectedRows)
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
            $this->recordCsvIntegrityIssue($issues, $file, 0, 'missing_confirmation_csv', 'Confirmation CSV is missing.');
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
            $this->recordCsvIntegrityIssue(
                $issues,
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
            $this->recordCsvIntegrityIssue(
                $issues,
                $file,
                1,
                'missing_required_columns',
                'CSV required columns missing: ' . implode(', ', $details['missing_required_columns'])
            );
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (!$lines) {
            $this->recordCsvIntegrityIssue($issues, $file, 0, 'unreadable_confirmation_csv', 'Confirmation CSV could not be read.');
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
            $this->recordCsvIntegrityIssue(
                $issues,
                $file,
                0,
                'manifest_source_row_count_mismatch',
                'Manifest count does not match source CSV physical rows: expected=' . (int)$expectedRows . ' physical=' . $details['physical_data_rows']
            );
        }
        if ($details['physical_data_rows'] !== $details['parsed_rows']) {
            $this->recordCsvIntegrityIssue(
                $issues,
                $file,
                0,
                'physical_parsed_row_count_mismatch',
                'CSV parser row count does not match physical rows; possible malformed quoting or delimiter issue: physical=' . $details['physical_data_rows'] . ' parsed=' . $details['parsed_rows']
            );
        }
        if ($details['long_rows'] > 0) {
            $this->recordCsvIntegrityIssue(
                $issues,
                $file,
                0,
                'csv_field_count_mismatch_long',
                'CSV rows have more fields than headers and may be shifted: long=' . $details['long_rows']
            );
        }
        if ($details['blank_required_identity_rows'] > 0) {
            $this->recordCsvIntegrityIssue(
                $issues,
                $file,
                0,
                'blank_required_identity_fields',
                'CSV rows have blank required identity fields: count=' . $details['blank_required_identity_rows']
            );
        }
        if ($details['duplicate_identity_key_count'] > 0) {
            $this->recordCsvIntegrityIssue(
                $issues,
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

    protected function recordCsvIntegrityIssue(array &$issues, $file, $row, $code, $message)
    {
        $issues[] = [
            'severity' => 'critical',
            'source_file' => $file,
            'row' => $row,
            'code' => $code,
            'message' => $message,
        ];
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

    protected function sourceKey($platform, $name = '', $code = '', $category = '')
    {
        return trim((string)$platform)
            . '|' . strtolower(trim((string)$category))
            . '|' . $this->norm($name)
            . '|' . $this->codeNorm($code);
    }

    protected function platformGate($status, $message)
    {
        if ($status === 'ready') {
            return ['READY_FOR_DRY_RUN', 'Run SyncGame with --platform-map and --dry-run only; import requires clean dry-run and audits.'];
        }
        if ($status === 'invalid') {
            return ['HOLD_INVALID_PLATFORM_CONFIRMATION', 'Fix XH platform code/category confirmation before any dry-run.'];
        }

        return ['WAIT_XH_PLATFORM_CONFIRMATION', 'Ask XH/upstream to fill api_code, category/gameType, merchant-open status and approved_for_import=TRUE.'];
    }

    protected function gameGate($status, $sourceFile, $message, array $source)
    {
        if ($status === 'ready') {
            return ['READY_FOR_DRY_RUN', 'Run SyncGame with --confirmed-games-approved --only-confirmed-games and --dry-run only.'];
        }
        if ($status === 'invalid') {
            return ['HOLD_INVALID_GAME_CONFIRMATION', 'Fix the approved row: code/category/gameCode must resolve to one active XH game.'];
        }
        if ($sourceFile === '05_collision_manual_review.csv') {
            return ['HOLD_COLLISION_MANUAL_REVIEW', $this->sourceRequiredAction($source) ?: 'Confirm exact vendor/code/gameCode; never import by same name only.'];
        }
        if ($sourceFile === '04_category_conflict_fast_check.csv') {
            return ['HOLD_CATEGORY_CONFLICT_CONFIRMATION', $this->sourceRequiredAction($source) ?: 'Confirm whether XH category is authoritative before dry-run.'];
        }

        return ['WAIT_XH_GAME_CONFIRMATION', $this->sourceRequiredAction($source) ?: 'Ask XH/upstream to confirm code/category/gameCode/title and approved_for_import=TRUE.'];
    }

    protected function incrementGateCounts(array &$counts, $gate)
    {
        if ($gate === 'READY_FOR_DRY_RUN') {
            $counts['ready_for_dry_run']++;
            return;
        }

        if (strpos($gate, 'HOLD_') === 0) {
            $counts['must_hold']++;
            if (strpos($gate, 'HOLD_INVALID_') === 0) {
                $counts['invalid']++;
            }
            return;
        }

        $counts['waiting_approval']++;
    }

    protected function gateItem($gate, $status, $sourceFile, array $reviewRow, array $source, $requiredAction)
    {
        return [
            'gate' => $gate,
            'status' => $status,
            'source_file' => $sourceFile,
            'pg_platform' => $reviewRow['pg_platform'] ?? '',
            'pg_category' => $reviewRow['pg_category'] ?? '',
            'play_name' => $reviewRow['play_name'] ?? '',
            'play_code' => $reviewRow['play_code'] ?? '',
            'expected_xh_codes' => $this->sourceExpectedCodes($source),
            'xh_code' => $reviewRow['xh_code'] ?? ($reviewRow['confirmed_codes'] ?? ''),
            'xh_category' => $reviewRow['xh_category'] ?? ($reviewRow['confirmed_category'] ?? ''),
            'xh_game_code' => $reviewRow['xh_game_code'] ?? '',
            'risk_level' => $source['risk_level'] ?? $this->riskFromGate($gate),
            'required_action' => $requiredAction,
            'message' => $reviewRow['message'] ?? '',
        ];
    }

    protected function sourceExpectedCodes(array $source)
    {
        foreach (['expected_xh_codes', 'expected_xh_code', 'possible_xh_code_hints', 'same_name_xh_codes'] as $key) {
            if (!empty($source[$key])) {
                return $source[$key];
            }
        }

        return '';
    }

    protected function sourceRequiredAction(array $source)
    {
        foreach (['required_action', 'recommended_question', 'question_to_xh'] as $key) {
            if (!empty($source[$key])) {
                return $source[$key];
            }
        }

        return '';
    }

    protected function riskFromGate($gate)
    {
        if ($gate === 'READY_FOR_DRY_RUN') {
            return 'ready_reviewed';
        }
        if (strpos($gate, 'HOLD_') === 0) {
            return 'high';
        }

        return 'waiting';
    }

    protected function countGateItems(array $items, $field)
    {
        $counts = [];
        foreach ($items as $item) {
            $key = (string)($item[$field] ?? '');
            if ($key === '') {
                $key = '(blank)';
            }
            $counts[$key] = ($counts[$key] ?? 0) + 1;
        }
        arsort($counts);

        return $counts;
    }

    protected function buildDryRunCandidates($outDir, array $gate, array $platformMap, array $confirmedGames)
    {
        $platformCommand = 'php artisan SyncGame --xh-catalog=storage/app/xh_catalog.json --platform-map=' . $outDir . '/platform_map_from_confirmation.json --confirmation-gate=' . $outDir . '/confirmation_gate.json --dry-run';
        $gameCommand = 'php artisan SyncGame --xh-catalog=storage/app/xh_catalog.json --confirmed-games-approved=' . $outDir . '/confirmed_games_approved.json --only-confirmed-games --confirmation-gate=' . $outDir . '/confirmation_gate.json --dry-run';
        $readiness = $gate['review_readiness'] ?? [];
        $dryRunAllowed = !empty($readiness['dry_run_allowed']);
        $dryRunBlockers = $readiness['dry_run_blockers'] ?? [];
        $blockedReason = $dryRunAllowed ? '' : ($readiness['summary'] ?? 'Dry-run is blocked by confirmation gate.');
        if (!$dryRunAllowed && !empty($dryRunBlockers)) {
            $blockedReason .= ' blockers=' . implode(',', $dryRunBlockers);
        }
        $commands = [
            'platform_map_dry_run' => $dryRunAllowed && count($platformMap) > 0 ? $platformCommand : '',
            'confirmed_games_dry_run' => $dryRunAllowed && count($confirmedGames) > 0 ? $gameCommand : '',
        ];
        $candidateTotal = count($platformMap) + count($confirmedGames);
        $commandSafety = $this->buildDryRunCommandSafety(
            $commands,
            $dryRunAllowed,
            (int)($gate['counts']['ready_for_dry_run'] ?? 0),
            $candidateTotal
        );

        return [
            'generated_at' => date('c'),
            'import_gate' => $gate['import_gate'],
            'auto_import_gate' => $readiness['auto_import_gate'] ?? 'unknown',
            'review_readiness' => $readiness,
            'pending_pool_summary' => $gate['pending_pool_summary'] ?? [],
            'pending_pool_classification' => $gate['pending_pool_classification'] ?? [],
            'ready_for_dry_run' => $gate['counts']['ready_for_dry_run'],
            'dry_run_allowed' => $dryRunAllowed,
            'blocked' => !$dryRunAllowed,
            'blocked_reason' => $blockedReason,
            'dry_run_blockers' => $dryRunBlockers,
            'dry_run_only' => true,
            'blind_import_allowed' => false,
            'database_import_allowed_now' => false,
            'requires_separate_import_approval' => true,
            'candidate_counts' => [
                'platform_map' => count($platformMap),
                'confirmed_games' => count($confirmedGames),
                'total' => $candidateTotal,
                'ready_for_dry_run' => (int)($gate['counts']['ready_for_dry_run'] ?? 0),
                'matches_ready_for_dry_run' => $candidateTotal === (int)($gate['counts']['ready_for_dry_run'] ?? 0),
            ],
            'platform_map' => [
                'count' => count($platformMap),
                'file' => $outDir . '/platform_map_from_confirmation.json',
                'items' => $platformMap,
            ],
            'confirmed_games' => [
                'count' => count($confirmedGames),
                'file' => $outDir . '/confirmed_games_approved.json',
                'items' => $confirmedGames,
            ],
            'commands' => $commands,
            'command_safety' => $commandSafety,
            'guardrail' => 'This file never authorizes import. Keep commands dry-run only until a separate approval follows clean dry-run and clean GameOpsAudit/XhApiOpsAudit/FrontendOpsAudit.',
        ];
    }

    protected function buildDryRunCommandSafety(array $commands, $dryRunAllowed, $readyForDryRun, $candidateTotal)
    {
        $rows = [];
        $issues = [];
        $commandCount = 0;
        foreach ($commands as $name => $command) {
            $command = trim((string)$command);
            $hasCommand = $command !== '';
            if ($hasCommand) {
                $commandCount++;
            }

            $hasDryRun = !$hasCommand || preg_match('/(^|\s)--dry-run(\s|$)/', $command) === 1;
            $hasConfirmationGate = !$hasCommand || strpos($command, '--confirmation-gate=') !== false;
            $hasImportFlag = $hasCommand && preg_match('/(^|\s)--import(\s|$)/', $command) === 1;
            $hasPublishFlag = $hasCommand && preg_match('/(^|\s)--publish(\s|$)/', $command) === 1;
            $passed = $hasDryRun && $hasConfirmationGate && !$hasImportFlag && !$hasPublishFlag;

            if (!$passed) {
                $issues[] = $name . ': command must include --dry-run and --confirmation-gate and must not include --import/--publish.';
            }
            $rows[] = [
                'name' => $name,
                'has_command' => $hasCommand ? 1 : 0,
                'has_dry_run' => $hasDryRun ? 1 : 0,
                'has_confirmation_gate' => $hasConfirmationGate ? 1 : 0,
                'has_import_flag' => $hasImportFlag ? 1 : 0,
                'has_publish_flag' => $hasPublishFlag ? 1 : 0,
                'passed' => $passed ? 1 : 0,
                'command' => $command,
            ];
        }

        if (!$dryRunAllowed && $commandCount > 0) {
            $issues[] = 'blocked dry-run gate must not emit executable commands.';
        }
        if ($dryRunAllowed && (int)$readyForDryRun > 0 && $commandCount === 0) {
            $issues[] = 'dry-run gate is allowed but no executable dry-run command was emitted.';
        }
        if ((int)$candidateTotal !== (int)$readyForDryRun) {
            $issues[] = 'candidate total must match ready_for_dry_run.';
        }

        return [
            'generated_at' => date('c'),
            'dry_run_allowed' => (bool)$dryRunAllowed,
            'ready_for_dry_run' => (int)$readyForDryRun,
            'candidate_total' => (int)$candidateTotal,
            'command_count' => $commandCount,
            'database_import_allowed_now' => false,
            'all_commands_dry_run_only' => count($issues) === 0,
            'failure_count' => count($issues),
            'issues' => $issues,
            'rows' => $rows,
        ];
    }

    protected function buildCatalogIndex(array $catalog)
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
        $gamesExact = [];
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
            $gameScopes[$code . '|' . $category] = true;
            $gamesExact[$code . '|' . $category . '|' . $gameCode] = $game;
        }

        return [
            'platform_codes' => $platformCodes,
            'game_scopes' => $gameScopes,
            'games_exact' => $gamesExact,
        ];
    }

    protected function platformReviewRow($status, $pgPlatform, $pgCategory, array $codes, $category, $message)
    {
        return [
            'status' => $status,
            'pg_platform' => $pgPlatform,
            'pg_category' => $pgCategory,
            'confirmed_codes' => implode('|', $codes),
            'confirmed_category' => $category,
            'message' => $message,
        ];
    }

    protected function gameReviewRow($status, $file, $pgPlatform, $pgCategory, $playName, $playCode, $xhCode, $xhCategory, $xhGameCode, $message)
    {
        return [
            'status' => $status,
            'source_file' => $file,
            'pg_platform' => $pgPlatform,
            'pg_category' => $pgCategory,
            'play_name' => $playName,
            'play_code' => $playCode,
            'xh_code' => $xhCode,
            'xh_category' => $xhCategory,
            'xh_game_code' => $xhGameCode,
            'message' => $message,
        ];
    }

    protected function addIssue(array &$issues, $severity, $file, $row, $code, $message)
    {
        $issues[] = [
            'severity' => $severity,
            'source_file' => $file,
            'row' => $row,
            'code' => $code,
            'message' => $message,
        ];
    }

    protected function parseCodes($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return [];
        }

        $decoded = json_decode($value, true);
        if (is_array($decoded)) {
            $codes = $decoded;
        } else {
            $codes = preg_split('/[\s,;|]+/', $value, -1, PREG_SPLIT_NO_EMPTY);
        }

        return array_values(array_unique(array_filter(array_map(function ($code) {
            return strtoupper(trim((string)$code));
        }, (array)$codes))));
    }

    protected function normalizeCategory($category)
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

    protected function truthy($value)
    {
        return in_array(strtolower(trim((string)$value)), ['1', 'true', 'yes', 'y', 'approved'], true);
    }

    protected function readCsv($path)
    {
        $rows = [];
        $lines = file($path, FILE_IGNORE_NEW_LINES);
        if (!$lines) {
            throw new \RuntimeException('CSV could not be read: ' . $path);
        }

        $headers = $this->csvHeaders($path);
        foreach (array_slice($lines, 1) as $line) {
            if (!$headers || trim($line) === '') {
                continue;
            }
            $data = str_getcsv($line);
            $row = [];
            foreach ($headers as $i => $header) {
                $row[$header] = $data[$i] ?? '';
            }
            $rows[] = $row;
        }

        return $rows;
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

    protected function writeGateMarkdown($path, array $gate, array $dryRunCandidates)
    {
        $counts = $gate['counts'];
        $readiness = $gate['review_readiness'];
        $pending = $readiness['pending_confirmation_pool'];
        $dryRunAllowed = $this->boolText($readiness['dry_run_allowed']);
        $databaseImportAllowedNow = $this->boolText($readiness['database_import_allowed_now']);
        $platformDryRun = $dryRunCandidates['commands']['platform_map_dry_run'] ?: '(no platform-map dry-run candidates)';
        $gameDryRun = $dryRunCandidates['commands']['confirmed_games_dry_run'] ?: '(no confirmed-game dry-run candidates)';
        $pendingPoolManifestTotal = $gate['pending_pool_summary']['manifest_total'] ?? 0;
        $contents = <<<MD
# PG51 / XH Confirmation Gate

- generated_at: {$gate['generated_at']}
- import_gate: {$gate['import_gate']}
- auto_import_gate: {$readiness['auto_import_gate']}
- review_readiness: {$readiness['state']}
- manifest_remaining: {$counts['manifest_remaining']}
- platform_not_confirmed: {$counts['platform_not_confirmed']}
- mapped_but_xh_not_matched: {$counts['mapped_but_xh_not_matched']}
- collision_manual_review: {$counts['collision_manual_review']}
- category_conflict_fast_check: {$counts['category_conflict_fast_check']}
- ready_for_dry_run: {$counts['ready_for_dry_run']}
- waiting_approval: {$counts['waiting_approval']}
- must_hold: {$counts['must_hold']}
- invalid: {$counts['invalid']}
- safe_auto_import_candidates: {$counts['safe_auto_import_candidates']}
- pending_pool_review_rows: {$pending['pending_pool_review_rows']}
- pending_pool_manifest_total: {$pendingPoolManifestTotal}
- pending_pool_ready: {$pending['ready']}
- pending_pool_upstream_plain: {$pending['upstream_plain']}
- pending_pool_conflict_union: {$pending['conflict_union']}

## Review Readiness

{$readiness['summary']}

- dry_run_allowed: {$dryRunAllowed}
- database_import_allowed_now: {$databaseImportAllowedNow}
- required_next_action: {$readiness['required_next_action']}

## Guardrails

- Blind import is not allowed.
- READY rows may only enter `SyncGame --dry-run` first.
- This review output does not generate or authorize `--import` or `--publish`.
- Any future import decision requires a clean dry-run plus clean `GameOpsAudit`, `XhApiOpsAudit`, and `FrontendOpsAudit`.
- HOLD rows require upstream or manual confirmation before they can become READY.

## Dry-run Commands

```bash
{$platformDryRun}
```

```bash
{$gameDryRun}
```

See `pending_pool_summary.csv` for aggregate HOLD buckets and `pending_confirmation_pool.csv` for every WAIT / HOLD / READY row.
MD;
        file_put_contents($path, $contents);
    }

    protected function writeReviewReadinessMarkdown($path, array $gate)
    {
        $readiness = $gate['review_readiness'];
        $pending = $readiness['pending_confirmation_pool'];
        $dryRunAllowed = $this->boolText($readiness['dry_run_allowed']);
        $databaseImportAllowedNow = $this->boolText($readiness['database_import_allowed_now']);
        $contents = <<<MD
# PG51 / XH Review Readiness

- generated_at: {$readiness['generated_at']}
- state: {$readiness['state']}
- import_gate: {$readiness['import_gate']}
- auto_import_gate: {$readiness['auto_import_gate']}
- database_import_allowed_now: {$databaseImportAllowedNow}
- dry_run_allowed: {$dryRunAllowed}

## Pending Confirmation Pool

- remaining: {$pending['remaining']}
- platform_not_confirmed: {$pending['platform_not_confirmed']}
- mapped_but_xh_not_matched: {$pending['mapped_but_xh_not_matched']}
- collision_manual_review: {$pending['collision_manual_review']}
- category_conflict_fast_check: {$pending['category_conflict_fast_check']}
- safe_auto_import_candidates: {$pending['safe_auto_import_candidates']}
- pending_pool_review_rows: {$pending['pending_pool_review_rows']}
- ready: {$pending['ready']}
- upstream_plain: {$pending['upstream_plain']}
- conflict_union: {$pending['conflict_union']}

## Decision

{$readiness['summary']}

{$readiness['required_next_action']}

No import command is generated by this review output.
MD;
        file_put_contents($path, $contents);
    }

    protected function writeNextCommands($outDir, array $gate)
    {
        $readiness = $gate['review_readiness'];
        $contents = <<<TXT
# Next commands after XH replies are reviewed

Current import gate: {$gate['import_gate']}
Review readiness: {$readiness['state']}
Auto import gate: {$readiness['auto_import_gate']}

{$readiness['summary']}

Review the pool first:

```bash
less {$outDir}/pending_confirmation_pool.csv
```

Run platform-map dry-run:

```bash
cd /var/www/xy281/admin
php artisan SyncGame \\
  --xh-catalog=storage/app/xh_catalog.json \\
  --platform-map={$outDir}/platform_map_from_confirmation.json \\
  --confirmation-gate={$outDir}/confirmation_gate.json \\
  --dry-run
```

Run confirmed-game dry-run:

```bash
cd /var/www/xy281/admin
php artisan SyncGame \\
  --xh-catalog=storage/app/xh_catalog.json \\
  --confirmed-games-approved={$outDir}/confirmed_games_approved.json \\
  --only-confirmed-games \\
  --confirmation-gate={$outDir}/confirmation_gate.json \\
  --dry-run
```

No import command is generated here. Keep this package in dry-run/review mode until a separate approval follows clean dry-run, GameOpsAudit, XhApiOpsAudit, and FrontendOpsAudit.
TXT;
        file_put_contents($outDir . '/next_commands.md', $contents);
    }

    protected function boolText($value)
    {
        return $value ? 'true' : 'false';
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

    protected function norm($value)
    {
        $value = strtolower(trim((string)$value));
        $value = preg_replace('/\s+/u', '', $value);

        return $value;
    }
}
