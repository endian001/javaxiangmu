<?php

namespace App\Admin\Controllers;

use App\Admin\Services\PlatformOperationsService;
use App\Admin\Support\OperationPermission;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PlatformOperationsController extends Controller
{
    private $service;

    public function __construct(PlatformOperationsService $service)
    {
        $this->service = $service;
    }

    public function siteSettings(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '90510');
    }

    public function domainRoutes(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '36000');
    }

    public function gameVendors(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '31018');
    }

    public function platformFeatures(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '90401');
    }

    public function withdrawalRisk(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '24001');
    }

    public function paymentManagement(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '20068');
    }

    public function paymentAccounts(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '20028');
    }

    public function agentPolicy(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '20500');
    }

    public function commissionSettings(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '21150');
    }

    public function helpCenter(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '12650');
    }

    public function smsSettings(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '2981');
    }

    public function pilotService(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '800003');
    }

    public function fundDetails(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '31001');
    }

    public function bankReconciliation(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '20048');
    }

    public function bankAccounts(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '20032');
    }

    public function feeRecharge(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '90040');
    }

    public function saveSettings(Request $request, $code)
    {
        if ($denied = $this->authorizeJson(OperationPermission::PLATFORM_OPERATIONS_WRITE)) {
            return $denied;
        }
        try {
            $page = $this->service->page($code);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }
        if ($page['mode'] !== 'settings') {
            return $this->error('当前页面不支持配置保存', 422);
        }

        $values = $this->service->filterSettings($code, $request->all());
        if (!$values) {
            return $this->error('没有可保存的配置内容', 422);
        }
        $section = mb_substr(trim((string) $request->input('section', 'general')), 0, 50);
        $adminId = $this->currentAdminId();
        $now = now();

        DB::transaction(function () use ($code, $section, $values, $adminId, $now) {
            foreach ($values as $key => $value) {
                DB::table('system_config')->updateOrInsert(
                    ['key' => $key],
                    ['value' => $value]
                );
                DB::table('admin_module_settings')->updateOrInsert(
                    [
                        'page_code' => (string) $code,
                        'section' => $section,
                        'setting_key' => $key,
                    ],
                    [
                        'setting_value' => $value,
                        'updated_by' => $adminId,
                        'updated_at' => $now,
                        'created_at' => $now,
                    ]
                );
            }
        });

        $this->audit(
            'platform_operations.settings.update',
            'platform_operations',
            '更新 '.$page['title'],
            ['page_code' => (string) $code, 'section' => $section, 'keys' => array_keys($values)]
        );

        return $this->success('配置已保存', ['keys' => array_keys($values)]);
    }

    public function saveRecord(Request $request, $code, $id = null)
    {
        if ($denied = $this->authorizeJson(OperationPermission::PLATFORM_OPERATIONS_WRITE)) {
            return $denied;
        }
        try {
            $page = $this->service->page($code);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }

        if ($page['mode'] === 'records') {
            return $this->saveModuleRecord($request, $page, $id);
        }
        if ($page['mode'] === 'transactions') {
            return $this->saveTransaction($request, $page, $id);
        }
        if ($page['mode'] === 'legacy') {
            try {
                $result = $this->service->saveLegacyRecord(
                    $page['code'],
                    $request->all(),
                    $id
                );
            } catch (InvalidArgumentException $exception) {
                return $this->error($exception->getMessage(), 422);
            }
            $this->audit(
                $id
                    ? 'platform_operations.legacy.update'
                    : 'platform_operations.legacy.create',
                $result['source_table'],
                '保存 '.$page['title'].' 旧业务记录',
                [
                    'page_code' => $page['code'],
                    'source_table' => $result['source_table'],
                    'source_id' => $result['source_id'],
                ]
            );

            return $this->success('记录已保存', $result);
        }

        return $this->error('当前页面不支持该保存操作', 422);
    }

    public function deleteRecord(Request $request, $code, $id)
    {
        if ($denied = $this->authorizeJson(OperationPermission::PLATFORM_OPERATIONS_DELETE)) {
            return $denied;
        }
        try {
            $page = $this->service->page($code);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }
        if ($page['mode'] === 'legacy') {
            try {
                $deleted = $this->service->deleteLegacyRecord($code, $id);
            } catch (InvalidArgumentException $exception) {
                return $this->error($exception->getMessage(), 422);
            }
            $this->audit(
                'platform_operations.legacy.delete',
                'platform_operations',
                '删除 '.$page['title'].' 旧业务记录',
                ['page_code' => (string) $code, 'id' => $id, 'deleted' => $deleted]
            );

            return $this->success('记录已删除', ['deleted' => $deleted]);
        }

        $table = $this->storageTable($page['mode']);
        if (!$table) {
            return $this->error('当前页面不支持删除', 422);
        }

        $row = DB::table($table)
            ->where('page_code', (string) $code)
            ->where('id', $id)
            ->first();
        if (!$row) {
            return $this->error('记录不存在', 404);
        }

        DB::table($table)->where('id', $id)->delete();
        $this->audit(
            'platform_operations.record.delete',
            $table,
            '删除 '.$page['title'].' 记录',
            ['page_code' => (string) $code, 'id' => (int) $id]
        );

        return $this->success('记录已删除');
    }

    public function bulkDelete(Request $request, $code)
    {
        if ($denied = $this->authorizeJson(OperationPermission::PLATFORM_OPERATIONS_DELETE)) {
            return $denied;
        }
        try {
            $page = $this->service->page($code);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }
        if ($page['mode'] === 'legacy') {
            $ids = array_values(array_unique(array_filter((array) $request->input('ids', []), function ($id) {
                return (string) $id !== '';
            })));
            if (!$ids) {
                return $this->error('请选择需要删除的记录', 422);
            }
            $deleted = 0;
            try {
                foreach ($ids as $id) {
                    $deleted += $this->service->deleteLegacyRecord($code, $id);
                }
            } catch (InvalidArgumentException $exception) {
                return $this->error($exception->getMessage(), 422);
            }
            $this->audit(
                'platform_operations.legacy.bulk_delete',
                'platform_operations',
                '批量删除 '.$page['title'].' 旧业务记录',
                ['page_code' => (string) $code, 'ids' => $ids, 'deleted' => $deleted]
            );

            return $this->success('批量删除完成', ['deleted' => $deleted]);
        }

        $table = $this->storageTable($page['mode']);
        if (!$table) {
            return $this->error('当前页面不支持批量删除', 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('ids', [])))));
        if (!$ids) {
            return $this->error('请选择需要删除的记录', 422);
        }
        $deleted = DB::table($table)
            ->where('page_code', (string) $code)
            ->whereIn('id', $ids)
            ->delete();

        $this->audit(
            'platform_operations.record.bulk_delete',
            $table,
            '批量删除 '.$page['title'].' 记录',
            ['page_code' => (string) $code, 'ids' => $ids, 'deleted' => $deleted]
        );

        return $this->success('批量删除完成', ['deleted' => $deleted]);
    }

    public function changeStatus(Request $request, $code)
    {
        if ($denied = $this->authorizeJson(OperationPermission::PLATFORM_OPERATIONS_WRITE)) {
            return $denied;
        }
        try {
            $page = $this->service->page($code);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }
        if ($page['mode'] === 'legacy') {
            $ids = array_values(array_unique(array_filter((array) $request->input('ids', []), function ($id) {
                return (string) $id !== '';
            })));
            $status = mb_substr(trim((string) $request->input('status')), 0, 30);
            if (!$ids || $status === '') {
                return $this->error('请选择记录并指定状态', 422);
            }
            try {
                $updated = $this->service->changeLegacyStatus($code, $ids, $status);
            } catch (InvalidArgumentException $exception) {
                return $this->error($exception->getMessage(), 422);
            }
            $this->audit(
                'platform_operations.legacy.status',
                'platform_operations',
                '更新 '.$page['title'].' 旧业务状态',
                ['page_code' => (string) $code, 'ids' => $ids, 'status' => $status]
            );

            return $this->success('状态已更新', ['updated' => $updated]);
        }

        $table = $this->storageTable($page['mode']);
        if (!$table) {
            return $this->error('当前页面不支持状态修改', 422);
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $request->input('ids', [])))));
        $status = mb_substr(trim((string) $request->input('status')), 0, 30);
        if (!$ids || $status === '') {
            return $this->error('请选择记录并指定状态', 422);
        }
        try {
            $status = $this->service->normalizeStatus($code, $status);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $updated = DB::table($table)
            ->where('page_code', (string) $code)
            ->whereIn('id', $ids)
            ->update([
                'status' => $status,
                'updated_by' => $this->currentAdminId(),
                'updated_at' => now(),
            ]);

        $this->audit(
            'platform_operations.status.update',
            $table,
            '更新 '.$page['title'].' 状态',
            ['page_code' => (string) $code, 'ids' => $ids, 'status' => $status]
        );

        return $this->success('状态已更新', ['updated' => $updated]);
    }

    public function import(Request $request, $code)
    {
        if ($denied = $this->authorizeJson(OperationPermission::PLATFORM_OPERATIONS_WRITE)) {
            return $denied;
        }
        try {
            $page = $this->service->page($code);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }
        if (!in_array('import', $page['actions'], true)) {
            return $this->error('当前页面不支持 CSV 导入', 422);
        }

        $file = $request->file('csv');
        if (!$file || !$file->isValid()) {
            return $this->error('请选择有效的 CSV 文件', 422);
        }
        if ((int) $file->getSize() > 5 * 1024 * 1024) {
            return $this->error('CSV 文件不能超过 5MB', 422);
        }

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return $this->error('CSV 文件无法读取', 422);
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);

            return $this->error('CSV 文件缺少表头', 422);
        }
        $headers = array_map(function ($header) {
            return $this->normalizeCsvValue($header);
        }, $headers);
        if (in_array('', $headers, true) || count($headers) !== count(array_unique($headers))) {
            fclose($handle);

            return $this->error('CSV 表头不能为空或重复', 422);
        }

        $imported = 0;
        $rowNumber = 1;
        try {
            DB::transaction(function () use ($handle, $headers, $page, &$imported, &$rowNumber) {
                while (($row = fgetcsv($handle)) !== false) {
                    $rowNumber++;
                    $hasValue = count(array_filter($row, function ($value) {
                        return trim((string) $value) !== '';
                    })) > 0;
                    if (!$hasValue) {
                        continue;
                    }
                    if (count($row) > count($headers)) {
                        throw new InvalidArgumentException('字段数量超过表头数量');
                    }
                    $row = array_pad($row, count($headers), null);
                    $values = array_combine($headers, $row);
                    $values = array_map(function ($value) {
                        return $this->normalizeCsvValue($value);
                    }, $values);

                    $this->importRow($page, $values);
                    $imported++;
                    if ($imported > 1000) {
                        throw new InvalidArgumentException('单次最多导入 1000 条记录');
                    }
                }
            });
        } catch (InvalidArgumentException $exception) {
            return $this->error('第 '.$rowNumber.' 行：'.$exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            return $this->error('第 '.$rowNumber.' 行数据无法保存，请检查字段格式和唯一值', 422);
        } finally {
            if (is_resource($handle)) {
                fclose($handle);
            }
        }

        if ($imported === 0) {
            return $this->error('CSV 文件没有可导入的数据', 422);
        }

        $this->audit(
            'platform_operations.import',
            'platform_operations',
            '导入 '.$page['title'],
            ['page_code' => (string) $code, 'imported' => $imported]
        );

        return $this->success('CSV 导入完成', ['imported' => $imported]);
    }

    public function export(Request $request, $code)
    {
        if ($denied = $this->authorizeJson(OperationPermission::PLATFORM_OPERATIONS_EXPORT)) {
            return $denied;
        }
        try {
            $page = $this->service->page($code);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }

        $table = $this->storageTable($page['mode']);
        if ($page['mode'] === 'legacy') {
            $paginator = $this->service->legacyRows($code, $request->all(), 10000);
            $rows = collect($paginator->items());
        } elseif ($page['mode'] === 'report') {
            $paginator = $this->service->reportRows($code, $request->all(), 10000);
            $rows = collect($paginator->items());
        } elseif ($page['mode'] === 'settings') {
            $rows = DB::table('admin_module_settings')
                ->where('page_code', (string) $code)
                ->orderBy('section')
                ->orderBy('setting_key')
                ->get();
        } elseif ($table) {
            $rows = $this->storedQuery($page, $request->all())->get();
        } else {
            return $this->error('当前页面暂不支持此导出方式', 422);
        }
        $headers = array_merge(['id'], $page['columns']);

        $handle = fopen('php://temp', 'r+');
        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, $headers);
        foreach ($rows as $row) {
            $data = (array) $row;
            if (!empty($data['business_data'])) {
                $business = json_decode($data['business_data'], true);
                if (is_array($business)) {
                    $data = array_merge($business, $data);
                }
            }
            $csvRow = [];
            foreach ($headers as $column) {
                $csvRow[] = isset($data[$column]) ? $data[$column] : null;
            }
            fputcsv($handle, $csvRow);
        }
        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        $this->audit(
            'platform_operations.export',
            $table ?: 'platform_operations',
            '导出 '.$page['title'],
            ['page_code' => (string) $code, 'count' => count($rows)]
        );

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="platform-'.$code.'-'.date('YmdHis').'.csv"',
        ]);
    }

    private function renderPage(Content $content, Request $request, $code)
    {
        if (!OperationPermission::can(OperationPermission::PLATFORM_OPERATIONS_READ)) {
            abort(403, '无权访问平台运营页面');
        }
        $page = $this->service->page($code);
        $statusOptions = $this->service->statusOptions($code);
        $values = [];
        $records = null;

        if ($page['mode'] === 'settings') {
            $keys = isset($page['setting_fields']) ? $page['setting_fields'] : [];
            $values = DB::table('system_config')
                ->whereIn('key', $keys)
                ->pluck('value', 'key')
                ->all();
        } elseif ($page['mode'] === 'legacy') {
            $records = $this->service->legacyRows(
                $code,
                $request->all()
            )->appends($request->query());
        } elseif ($page['mode'] === 'report') {
            $records = $this->service->reportRows(
                $code,
                $request->all()
            )->appends($request->query());
        } elseif ($this->storageTable($page['mode'])) {
            $records = $this->storedQuery($page, $request->all())
                ->paginate(20)
                ->appends($request->query());
        }

        return $content
            ->title($page['title'])
            ->description($page['module'].' / '.$page['code'])
            ->body(view('admin.platform-operations', compact(
                'page',
                'statusOptions',
                'values',
                'records'
            ))->render());
    }

    private function saveModuleRecord(Request $request, array $page, $id)
    {
        $data = $this->service->filterRecordInput($page['code'], $request->all());
        if ($data['title'] === '') {
            return $this->error('标题不能为空', 422);
        }
        try {
            $status = $this->service->normalizeStatus($page['code'], $data['status']);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
        $now = now();
        $values = [
            'page_code' => $page['code'],
            'record_type' => mb_substr(trim((string) $request->input('record_type', 'record')), 0, 50),
            'title' => $data['title'],
            'status' => $status,
            'sort_order' => $data['sort_order'],
            'amount' => $request->filled('amount') ? (float) $request->input('amount') : null,
            'currency' => $request->filled('currency') ? mb_substr(trim((string) $request->input('currency')), 0, 10) : null,
            'effective_at' => $request->filled('effective_at') ? $request->input('effective_at') : null,
            'business_data' => json_encode($data['business_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_by' => $this->currentAdminId(),
            'updated_at' => $now,
        ];

        if ($id) {
            $exists = DB::table('admin_module_records')
                ->where('page_code', $page['code'])
                ->where('id', $id)
                ->exists();
            if (!$exists) {
                return $this->error('记录不存在', 404);
            }
            DB::table('admin_module_records')->where('id', $id)->update($values);
            $action = 'platform_operations.record.update';
        } else {
            $values['created_by'] = $this->currentAdminId();
            $values['created_at'] = $now;
            $id = DB::table('admin_module_records')->insertGetId($values);
            $action = 'platform_operations.record.create';
        }

        $this->audit($action, 'admin_module_records', '保存 '.$page['title'].' 记录', [
            'page_code' => $page['code'],
            'id' => (int) $id,
        ]);

        return $this->success('记录已保存', ['id' => (int) $id]);
    }

    private function saveTransaction(Request $request, array $page, $id)
    {
        $data = $request->validate([
            'transaction_type' => 'required|string|max:50',
            'account_name' => 'nullable|string|max:191',
            'account_no' => 'nullable|string|max:191',
            'amount' => 'required|numeric',
            'balance_before' => 'nullable|numeric',
            'balance_after' => 'nullable|numeric',
            'currency' => 'nullable|string|max:10',
            'status' => 'required|string|max:30',
            'occurred_at' => 'nullable|date',
            'remark' => 'nullable|string|max:5000',
        ]);
        try {
            $status = $this->service->normalizeStatus($page['code'], $data['status']);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }
        $now = now();
        $values = [
            'page_code' => $page['code'],
            'transaction_type' => trim($data['transaction_type']),
            'account_name' => isset($data['account_name']) ? trim($data['account_name']) : null,
            'account_no' => isset($data['account_no']) ? trim($data['account_no']) : null,
            'amount' => $data['amount'],
            'balance_before' => isset($data['balance_before']) ? $data['balance_before'] : null,
            'balance_after' => isset($data['balance_after']) ? $data['balance_after'] : null,
            'currency' => isset($data['currency']) ? strtoupper(trim($data['currency'])) : null,
            'status' => $status,
            'occurred_at' => isset($data['occurred_at']) ? $data['occurred_at'] : $now,
            'remark' => isset($data['remark']) ? trim($data['remark']) : null,
            'business_data' => null,
            'updated_by' => $this->currentAdminId(),
            'updated_at' => $now,
        ];

        if ($id) {
            $row = DB::table('admin_module_transactions')
                ->where('page_code', $page['code'])
                ->where('id', $id)
                ->first();
            if (!$row) {
                return $this->error('交易记录不存在', 404);
            }
            DB::table('admin_module_transactions')->where('id', $id)->update($values);
            $businessNo = $row->business_no;
            $action = 'platform_operations.transaction.update';
        } else {
            $businessNo = 'PO-'.$page['code'].'-'.date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            $values['business_no'] = $businessNo;
            $values['created_by'] = $this->currentAdminId();
            $values['created_at'] = $now;
            $id = DB::table('admin_module_transactions')->insertGetId($values);
            $action = 'platform_operations.transaction.create';
        }

        $this->audit($action, 'admin_module_transactions', '保存 '.$page['title'].' 交易记录', [
            'page_code' => $page['code'],
            'id' => (int) $id,
            'business_no' => $businessNo,
        ]);

        return $this->success('交易记录已保存', [
            'id' => (int) $id,
            'business_no' => $businessNo,
        ]);
    }

    private function importRow(array $page, array $input)
    {
        $id = isset($input['id']) && trim((string) $input['id']) !== ''
            ? $input['id']
            : null;
        unset($input['id'], $input['source_id'], $input['source_table']);

        if ($page['mode'] === 'legacy') {
            if ($page['code'] === '31018' && !$id) {
                $id = isset($input['platform_name']) ? $input['platform_name'] : null;
            }
            $this->service->saveLegacyRecord($page['code'], $input, $id);

            return;
        }

        $now = now();
        if ($page['mode'] === 'records') {
            $data = $this->service->filterRecordInput($page['code'], $input);
            if ($data['title'] === '') {
                throw new InvalidArgumentException('标题不能为空');
            }
            $status = $this->service->normalizeStatus($page['code'], $data['status']);
            $values = [
                'page_code' => $page['code'],
                'record_type' => mb_substr(trim((string) (isset($input['record_type']) ? $input['record_type'] : 'record')), 0, 50),
                'title' => $data['title'],
                'status' => $status,
                'sort_order' => $data['sort_order'],
                'amount' => isset($input['amount']) && $input['amount'] !== '' ? (float) $input['amount'] : null,
                'currency' => isset($input['currency']) && $input['currency'] !== '' ? mb_substr(trim((string) $input['currency']), 0, 10) : null,
                'effective_at' => isset($input['effective_at']) && $input['effective_at'] !== '' ? $input['effective_at'] : null,
                'business_data' => json_encode($data['business_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_by' => $this->currentAdminId(),
                'updated_at' => $now,
            ];
            if ($id) {
                $exists = DB::table('admin_module_records')
                    ->where('page_code', $page['code'])
                    ->where('id', (int) $id)
                    ->exists();
                if (!$exists) {
                    throw new InvalidArgumentException('待更新记录不存在');
                }
                DB::table('admin_module_records')
                    ->where('page_code', $page['code'])
                    ->where('id', (int) $id)
                    ->update($values);
            } else {
                $values['created_by'] = $this->currentAdminId();
                $values['created_at'] = $now;
                DB::table('admin_module_records')->insert($values);
            }

            return;
        }

        if ($page['mode'] === 'transactions') {
            $type = trim((string) (isset($input['transaction_type']) ? $input['transaction_type'] : ''));
            if ($type === '') {
                throw new InvalidArgumentException('交易类型不能为空');
            }
            if (!isset($input['amount']) || $input['amount'] === '' || !is_numeric($input['amount'])) {
                throw new InvalidArgumentException('金额必须为数字');
            }
            $status = trim((string) (isset($input['status']) ? $input['status'] : 'pending'));
            $status = $this->service->normalizeStatus($page['code'], $status);
            $businessNo = trim((string) (isset($input['business_no']) ? $input['business_no'] : ''));
            if ($businessNo === '') {
                $businessNo = 'PO-'.$page['code'].'-'.date('YmdHis').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            }
            $values = [
                'page_code' => $page['code'],
                'business_no' => $businessNo,
                'transaction_type' => mb_substr($type, 0, 50),
                'account_name' => isset($input['account_name']) && $input['account_name'] !== '' ? mb_substr(trim((string) $input['account_name']), 0, 191) : null,
                'account_no' => isset($input['account_no']) && $input['account_no'] !== '' ? mb_substr(trim((string) $input['account_no']), 0, 191) : null,
                'amount' => (float) $input['amount'],
                'balance_before' => isset($input['balance_before']) && $input['balance_before'] !== '' ? (float) $input['balance_before'] : null,
                'balance_after' => isset($input['balance_after']) && $input['balance_after'] !== '' ? (float) $input['balance_after'] : null,
                'currency' => isset($input['currency']) && $input['currency'] !== '' ? strtoupper(mb_substr(trim((string) $input['currency']), 0, 10)) : null,
                'status' => $status,
                'occurred_at' => isset($input['occurred_at']) && $input['occurred_at'] !== '' ? $input['occurred_at'] : $now,
                'remark' => isset($input['remark']) && $input['remark'] !== '' ? mb_substr(trim((string) $input['remark']), 0, 5000) : null,
                'business_data' => null,
                'updated_by' => $this->currentAdminId(),
                'updated_at' => $now,
            ];
            if ($id) {
                $exists = DB::table('admin_module_transactions')
                    ->where('page_code', $page['code'])
                    ->where('id', (int) $id)
                    ->exists();
                if (!$exists) {
                    throw new InvalidArgumentException('待更新交易记录不存在');
                }
                DB::table('admin_module_transactions')
                    ->where('page_code', $page['code'])
                    ->where('id', (int) $id)
                    ->update($values);
            } else {
                $values['created_by'] = $this->currentAdminId();
                $values['created_at'] = $now;
                DB::table('admin_module_transactions')->insert($values);
            }

            return;
        }

        throw new InvalidArgumentException('当前页面不支持 CSV 导入');
    }

    private function storedQuery(array $page, array $filters)
    {
        $table = $this->storageTable($page['mode']);
        $query = DB::table($table)->where('page_code', (string) $page['code']);

        if (isset($filters['status']) && trim((string) $filters['status']) !== '') {
            $query->where('status', trim((string) $filters['status']));
        }
        if (isset($filters['keyword']) && trim((string) $filters['keyword']) !== '') {
            $keyword = trim((string) $filters['keyword']);
            $query->where(function ($nested) use ($table, $keyword) {
                if ($table === 'admin_module_records') {
                    $nested->where('title', 'like', '%'.$keyword.'%')
                        ->orWhere('business_data', 'like', '%'.$keyword.'%');
                } else {
                    $nested->where('business_no', 'like', '%'.$keyword.'%')
                        ->orWhere('account_name', 'like', '%'.$keyword.'%')
                        ->orWhere('account_no', 'like', '%'.$keyword.'%')
                        ->orWhere('remark', 'like', '%'.$keyword.'%');
                }
            });
        }

        if ($table === 'admin_module_records') {
            foreach ((array) (isset($page['record_fields']) ? $page['record_fields'] : []) as $field) {
                if (!isset($filters[$field]) || trim((string) $filters[$field]) === '') {
                    continue;
                }
                $encoded = json_encode(
                    trim((string) $filters[$field]),
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
                $query->where('business_data', 'like', '%"'.$field.'":'.$encoded.'%');
            }
        } else {
            foreach (['transaction_type', 'account_no', 'business_no'] as $field) {
                if (isset($filters[$field]) && trim((string) $filters[$field]) !== '') {
                    $query->where($field, trim((string) $filters[$field]));
                }
            }
            if (isset($filters['date_from']) && trim((string) $filters['date_from']) !== '') {
                $query->where('occurred_at', '>=', trim((string) $filters['date_from']).' 00:00:00');
            }
            if (isset($filters['date_to']) && trim((string) $filters['date_to']) !== '') {
                $query->where('occurred_at', '<=', trim((string) $filters['date_to']).' 23:59:59');
            }
        }

        return $query->orderByDesc('id');
    }

    private function normalizeCsvValue($value)
    {
        if ($value === null) {
            return null;
        }

        $value = preg_replace('/^\xEF\xBB\xBF/', '', (string) $value);
        if (!mb_check_encoding($value, 'UTF-8')) {
            $converted = @mb_convert_encoding($value, 'UTF-8', 'GB18030');
            if ($converted !== false) {
                $value = $converted;
            }
        }

        return trim($value);
    }

    private function storageTable($mode)
    {
        if ($mode === 'records') {
            return 'admin_module_records';
        }
        if ($mode === 'transactions') {
            return 'admin_module_transactions';
        }

        return null;
    }

    private function audit($action, $module, $content, array $context)
    {
        $admin = Admin::user();
        DB::table('admin_audit_logs')->insert([
            'admin_user_id' => $admin ? $admin->getKey() : null,
            'admin_name' => $admin ? $admin->username : null,
            'action' => $action,
            'module' => $module,
            'content' => $content,
            'ip_address' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 2000),
            'context' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => now(),
        ]);
    }

    private function currentAdminId()
    {
        $admin = Admin::user();

        return $admin ? (int) $admin->getKey() : null;
    }

    private function authorizeJson($ability)
    {
        if (OperationPermission::can($ability)) {
            return null;
        }

        return $this->error('无权执行该操作', 403);
    }

    private function success($message, array $data = [])
    {
        return response()->json(['status' => true, 'message' => $message, 'data' => $data]);
    }

    private function error($message, $status)
    {
        return response()->json(['status' => false, 'message' => $message], $status);
    }
}
