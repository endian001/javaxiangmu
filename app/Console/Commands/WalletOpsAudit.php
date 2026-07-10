<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WalletOpsAudit extends Command
{
    protected $signature = 'WalletOpsAudit
        {--out-dir=storage/app/wallet-ops-audit : Directory for audit reports}';

    protected $description = 'Generate read-only operational health reports for wallet transfer and payment safety';

    protected $requiredTransferColumns = [
        'recovery_key',
        'recovery_status',
        'external_status',
        'external_checked_at',
        'posted_at',
        'reconcile_note',
    ];

    protected $requiredUniqueIndexes = [
        ['table' => 'recharge', 'index' => 'recharge_order_no_unique', 'column' => 'order_no'],
        ['table' => 'recharge', 'index' => 'recharge_out_trade_no_unique', 'column' => 'out_trade_no'],
        ['table' => 'withdraws', 'index' => 'withdraws_order_no_unique', 'column' => 'order_no'],
        ['table' => 'transfer_logs', 'index' => 'transfer_logs_order_no_unique', 'column' => 'order_no'],
        ['table' => 'transfer_logs', 'index' => 'transfer_logs_recovery_key_unique', 'column' => 'recovery_key'],
    ];

    public function handle()
    {
        $outDir = $this->resolvePath($this->option('out-dir'));
        if (!is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $summary = $this->summary();
        $indexes = $this->uniqueIndexStatus();
        $duplicates = $this->duplicateStatus();
        $schema = $this->schemaStatus();
        $source = $this->sourceGuardStatus();
        $issues = $this->issues($indexes, $duplicates, $schema, $source);

        $report = [
            'generated_at' => date('c'),
            'summary' => $summary,
            'unique_indexes' => $indexes,
            'duplicates' => $duplicates,
            'schema' => $schema,
            'source_guards' => $source,
            'issue_counts' => $this->issueCounts($issues),
            'issues' => $issues,
        ];

        $this->writeJson($outDir.'/wallet_ops_audit.json', $report);
        $this->writeCsv($outDir.'/wallet_ops_issues.csv', $issues, [
            'severity',
            'code',
            'message',
            'count',
            'sample',
        ]);

        $this->info(sprintf(
            'recharge=%d withdraws=%d transfer_logs=%d active_pending=%d local_pending=%d issues=%d critical=%d warnings=%d',
            $summary['recharge_count'],
            $summary['withdraw_count'],
            $summary['transfer_log_count'],
            $summary['active_pending_transfers'],
            $summary['external_success_local_pending'],
            count($issues),
            $report['issue_counts']['critical'] ?? 0,
            $report['issue_counts']['warning'] ?? 0
        ));
        $this->comment('Reports: '.$outDir);

        return 0;
    }

    protected function summary()
    {
        return [
            'recharge_count' => (int) DB::table('recharge')->count(),
            'withdraw_count' => (int) DB::table('withdraws')->count(),
            'transfer_log_count' => (int) DB::table('transfer_logs')->count(),
            'active_pending_transfers' => (int) DB::table('transfer_logs')
                ->where('state', 0)
                ->whereIn('external_status', ['pending', 'calling'])
                ->count(),
            'external_success_local_pending' => (int) DB::table('transfer_logs')
                ->where('recovery_status', 'external_success_local_pending')
                ->count(),
            'recovery_unknown_reconcile' => (int) DB::table('transfer_logs')
                ->where('recovery_status', 'unknown_reconcile')
                ->count(),
        ];
    }

    protected function uniqueIndexStatus()
    {
        $rows = [];
        foreach ($this->requiredUniqueIndexes as $item) {
            $index = $this->indexRows($item['table'], $item['index']);
            $rows[] = [
                'table' => $item['table'],
                'index' => $item['index'],
                'column' => $item['column'],
                'exists' => !empty($index),
                'unique' => !empty($index) && (int) $index[0]->Non_unique === 0,
            ];
        }

        return $rows;
    }

    protected function duplicateStatus()
    {
        $checks = [
            ['table' => 'recharge', 'column' => 'order_no'],
            ['table' => 'recharge', 'column' => 'out_trade_no'],
            ['table' => 'withdraws', 'column' => 'order_no'],
            ['table' => 'transfer_logs', 'column' => 'order_no'],
        ];

        $rows = [];
        foreach ($checks as $check) {
            $dupes = DB::select(
                'SELECT `'.$check['column'].'` AS value, COUNT(*) AS count_rows FROM `'.$check['table'].'` '.
                'WHERE `'.$check['column'].'` IS NOT NULL AND LENGTH(`'.$check['column'].'`) > 0 '.
                'GROUP BY `'.$check['column'].'` HAVING COUNT(*) > 1 ORDER BY count_rows DESC LIMIT 5'
            );
            $rows[] = [
                'table' => $check['table'],
                'column' => $check['column'],
                'duplicate_count' => count($dupes),
                'sample' => empty($dupes) ? '' : $dupes[0]->value,
            ];
        }

        return $rows;
    }

    protected function schemaStatus()
    {
        $rows = [];
        foreach ($this->requiredTransferColumns as $column) {
            $rows[] = [
                'table' => 'transfer_logs',
                'column' => $column,
                'exists' => Schema::hasColumn('transfer_logs', $column),
            ];
        }

        return $rows;
    }

    protected function sourceGuardStatus()
    {
        $files = [
            [
                'path' => base_path('app/User.php'),
                'required_markers' => ['moveAccountBalanceToPlatform', 'movePlatformBalanceToAccount', 'walletTransferReturn'],
            ],
            [
                'path' => base_path('app/Http/Controllers/Member/PayController.php'),
                'required_markers' => [
                    "Accounttranso('ag'",
                    "transToAccount('ag'",
                    'transfer failed',
                    'protected function createSafeWithdrawRequest',
                    'protected function markZgpayWithdrawSucceeded',
                    'protected function refundFailedWithdrawRequest',
                    "UserCard::where('id', \$data['bank'])->where('user_id', \$user->id)->first()",
                    "\$request->only(['bank','bank_no','bank_address','bank_owner'])",
                    "UserCard::where('id',\$cardId)->where('user_id', Auth::id())->update(\$data)",
                    "UserCard::where('id',\$id)->where('user_id', Auth::id())->first()",
                    "UserCard::where('id',\$id)->where('user_id', Auth::id())->delete()",
                    'DB::transaction(function () use ($user, $card, $data, $order_no)',
                    "User::where('id', \$user->id)->lockForUpdate()->first()",
                    'Withdraw::create($item)',
                    'zgpay withdraw refunded',
                ],
                'forbidden_markers' => [
                    '$user->balance -= $data[\'amount\'];',
                    '$card = UserCard::find($data[\'bank\']);',
                    '$order_no = time().rand(1000,9999);',
                ],
                'forbidden_patterns' => [
                    ['label' => 'legacy_withdraw_direct_balance_debit', 'pattern' => '~\$user->balance\s*(?:-=|=\s*\$user->balance\s*-)\s*\$data\[[\'"]amount[\'"]\]\s*;~'],
                    ['label' => 'legacy_withdraw_card_without_owner_scope', 'pattern' => '~UserCard::find\(\s*\$data\[[\'"]bank[\'"]\]\s*\)~'],
                    ['label' => 'legacy_withdraw_time_random_order_no', 'pattern' => '~\$order_no\s*=\s*time\(\)\s*\.\s*rand\s*\(~'],
                ],
            ],
            [
                'path' => base_path('app/Services/SafeGameTransferService.php'),
                'required_markers' => ['moveAccountBalanceToPlatform', 'movePlatformBalanceToAccount', 'moveLastPlatformBalanceToAccount'],
            ],
            [
                'path' => base_path('routes/api.php'),
                'required_markers' => [],
                'forbidden_markers' => [
                    "Route::get('/autogetusermoney'",
                ],
            ],
            [
                'path' => base_path('routes/api2.php'),
                'required_markers' => [],
                'forbidden_markers' => [
                    "Route::get('/autogetusermoney'",
                ],
            ],
            [
                'path' => base_path('app/Http/Controllers/Agent/IndexController.php'),
                'required_markers' => [
                    'safeTeamRechargeApi',
                    'return $this->safeTeamRechargeApi($request);',
                    'performTeamRecharge',
                    'normalizeTeamRechargeAmount',
                    '$request->input(\'client_order_no\', \'\')',
                    '$amount = $this->normalizeTeamRechargeAmount($amount);',
                    '$clientOrderNo = trim((string) $clientOrderNo);',
                    'teamRechargeOutTradeNo',
                    'DB::transaction(function () use ($user, $childId, $amount, $clientOrderNo, $outTradeNo, $existingResult)',
                    'Recharge::where(\'out_trade_no\', $outTradeNo)->lockForUpdate()',
                    '$existingAfterLocks = Recharge::where(\'out_trade_no\', $outTradeNo)->lockForUpdate()->first();',
                    'User::where(\'id\', $user->id)->lockForUpdate()',
                    'User::where(\'id\', $childId)->lockForUpdate()',
                    'TransferLog::create([',
                    'agent_recharge_debit',
                    'agent_recharge_credit',
                ],
                'required_counts' => [
                    ['marker' => 'Recharge::where(\'out_trade_no\', $outTradeNo)->lockForUpdate()', 'min' => 2],
                    ['marker' => 'TransferLog::create([', 'min' => 2],
                    ['marker' => 'agent_recharge_debit', 'min' => 1],
                    ['marker' => 'agent_recharge_credit', 'min' => 1],
                ],
                'forbidden_markers' => [
                    '$user->balance -= $data[\'amount\'];',
                    '$child->balance += $data[\'amount\'];',
                    '$arr[\'out_trade_no\'] = $child->id.time().rand(10000,90000);',
                ],
                'forbidden_patterns' => [
                    ['label' => 'legacy_user_balance_debit', 'pattern' => '~\$user->balance\s*(?:-=|=\s*\$user->balance\s*-)\s*(?:\$amount|\$data\[[\'"]amount[\'"]\])\s*;~'],
                    ['label' => 'legacy_child_balance_credit', 'pattern' => '~\$child->balance\s*(?:\+=|=\s*\$child->balance\s*\+)\s*(?:\$amount|\$data\[[\'"]amount[\'"]\])\s*;~'],
                    ['label' => 'legacy_user_balance_mutator', 'pattern' => '~\$user->(?:increment|decrement)\([\'"]balance[\'"]~'],
                    ['label' => 'legacy_child_balance_mutator', 'pattern' => '~\$child->(?:increment|decrement)\([\'"]balance[\'"]~'],
                    ['label' => 'legacy_child_time_random_out_trade_no', 'pattern' => '~\$arr\[[\'"]out_trade_no[\'"]\]\s*=\s*\$child->id\s*\.\s*time\(\)\s*\.\s*rand\s*\(~'],
                ],
            ],
        ];

        $rows = [];
        foreach ($files as $file) {
            $contents = is_file($file['path']) ? file_get_contents($file['path']) : '';
            $missing = [];
            foreach ($file['required_markers'] as $marker) {
                if (strpos($contents, $marker) === false) {
                    $missing[] = $marker;
                }
            }
            $missingCounts = [];
            $markerCounts = [];
            foreach ($file['required_counts'] ?? [] as $rule) {
                $count = substr_count($contents, $rule['marker']);
                $markerCounts[$rule['marker']] = $count;
                if ($count < $rule['min']) {
                    $missingCounts[] = $rule['marker'].' count='.$count.' min='.$rule['min'];
                }
            }
            $forbidden = [];
            foreach ($file['forbidden_markers'] ?? [] as $marker) {
                if (strpos($contents, $marker) !== false) {
                    $forbidden[] = $marker;
                }
            }
            foreach ($file['forbidden_patterns'] ?? [] as $rule) {
                if (preg_match($rule['pattern'], $contents)) {
                    $forbidden[] = $rule['label'];
                }
            }

            $rows[] = [
                'path' => str_replace(base_path().'/', '', $file['path']),
                'exists' => $contents !== '',
                'direct_trans_references' => substr_count($contents, '->trans('),
                'missing_markers' => $missing,
                'missing_count_markers' => $missingCounts,
                'marker_counts' => $markerCounts,
                'forbidden_markers' => $forbidden,
            ];
        }

        return $rows;
    }

    protected function issues(array $indexes, array $duplicates, array $schema, array $source)
    {
        $issues = [];

        foreach ($indexes as $row) {
            if (!$row['exists'] || !$row['unique']) {
                $this->addIssue($issues, 'critical', 'missing_wallet_unique_index', 'Required wallet unique index is missing or non-unique.', 1, $row['table'].'.'.$row['index']);
            }
        }

        foreach ($duplicates as $row) {
            if ($row['duplicate_count'] > 0) {
                $this->addIssue($issues, 'critical', 'duplicate_wallet_order_no', 'Duplicate wallet order values exist.', $row['duplicate_count'], $row['table'].'.'.$row['column'].'='.$row['sample']);
            }
        }

        foreach ($schema as $row) {
            if (!$row['exists']) {
                $this->addIssue($issues, 'critical', 'missing_transfer_reconcile_column', 'transfer_logs is missing a reconcile/status column.', 1, $row['column']);
            }
        }

        foreach ($source as $row) {
            if (!$row['exists'] || !empty($row['missing_markers']) || !empty($row['missing_count_markers'])) {
                $missing = array_merge($row['missing_markers'], $row['missing_count_markers'] ?? []);
                $this->addIssue($issues, 'warning', 'missing_wallet_source_guard', 'Wallet source safety marker is missing.', count($missing) ?: 1, $row['path'].':'.implode('|', $missing));
            }
            if (!empty($row['forbidden_markers'])) {
                $this->addIssue($issues, 'critical', 'forbidden_wallet_source_marker', 'Legacy non-transactional wallet source marker is present.', count($row['forbidden_markers']), $row['path'].':'.implode('|', $row['forbidden_markers']));
            }
        }

        return $issues;
    }

    protected function indexRows($table, $index)
    {
        return DB::select('SHOW INDEX FROM `'.$table.'` WHERE Key_name = ?', [$index]);
    }

    protected function addIssue(array &$issues, $severity, $code, $message, $count, $sample = '')
    {
        if ((int) $count <= 0) {
            return;
        }

        $issues[] = [
            'severity' => $severity,
            'code' => $code,
            'message' => $message,
            'count' => (int) $count,
            'sample' => (string) $sample,
        ];
    }

    protected function issueCounts(array $issues)
    {
        $counts = ['critical' => 0, 'warning' => 0];
        foreach ($issues as $issue) {
            if (isset($counts[$issue['severity']])) {
                $counts[$issue['severity']]++;
            }
        }

        return $counts;
    }

    protected function resolvePath($path)
    {
        if (strpos($path, '/') === 0 || preg_match('/^[A-Za-z]:\\\\/', $path)) {
            return $path;
        }

        return base_path($path);
    }

    protected function writeJson($path, array $data)
    {
        file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    protected function writeCsv($path, array $rows, array $columns)
    {
        $handle = fopen($path, 'w');
        fputcsv($handle, $columns);
        foreach ($rows as $row) {
            $line = [];
            foreach ($columns as $column) {
                $line[] = $row[$column] ?? '';
            }
            fputcsv($handle, $line);
        }
        fclose($handle);
    }
}
