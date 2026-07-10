<?php

namespace App\Admin\Controllers;

use App\Admin\Services\GameManagementService;
use App\Admin\Support\OperationPermission;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

class GameManagementController extends Controller
{
    private $service;

    public function __construct(GameManagementService $service)
    {
        $this->service = $service;
    }

    public function winnerRankings(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '31202');
    }

    public function thirdPartyGames(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '31000');
    }

    public function hotGames(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '70037');
    }

    public function lotteryBranches(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '20401');
    }

    public function lotteryDraws(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '5000');
    }

    public function lotterySettings(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '5500');
    }

    public function lotteryTypes(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '5754');
    }

    public function lotteryPlays(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '6400');
    }

    public function lotterySalesMonitor(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '5749');
    }

    public function lotteryBetInterference(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '5700');
    }

    public function lotteryHotSort(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '5600');
    }

    public function freeSpins(Content $content, Request $request)
    {
        return $this->renderPage($content, $request, '260025');
    }

    public function saveRecord(Request $request, $code, $id = null)
    {
        if ($denied = $this->authorizeJson(OperationPermission::GAME_MANAGEMENT_WRITE)) {
            return $denied;
        }

        try {
            $page = $this->service->page($code);
            $requiredAction = $id ? 'edit' : 'create';
            if (!in_array($requiredAction, $page['actions'], true)) {
                return $this->error('当前页面不支持该操作', 422);
            }
            $result = DB::transaction(function () use ($code, $request, $id) {
                return $this->service->saveRecord($code, $request->all(), $id);
            });
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            report($exception);
            return $this->error('保存失败，请检查唯一字段和输入格式', 422);
        }

        $this->audit(
            $id ? 'game_management.record.update' : 'game_management.record.create',
            $result['source_table'],
            ($id ? '更新' : '新增').' '.$page['title'],
            [
                'page_code' => (string) $code,
                'record_id' => $result['id'],
                'before' => $result['before'],
                'after' => $result['after'],
            ]
        );

        return $this->success('记录已保存', $result);
    }

    public function deleteRecord(Request $request, $code, $id)
    {
        if ($denied = $this->authorizeJson(OperationPermission::GAME_MANAGEMENT_DELETE)) {
            return $denied;
        }

        try {
            $page = $this->service->page($code);
            $before = Schema::hasTable($page['storage'])
                ? DB::table($page['storage'])->where('id', (int) $id)->first()
                : null;
            $deleted = $this->service->deleteRecord($code, $id);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        if (!$deleted) {
            return $this->error('记录不存在', 404);
        }

        $this->audit(
            'game_management.record.delete',
            $page['storage'],
            '删除 '.$page['title'],
            [
                'page_code' => (string) $code,
                'record_id' => (int) $id,
                'before' => $before ? (array) $before : [],
            ]
        );

        return $this->success('记录已删除');
    }

    public function bulkDelete(Request $request, $code)
    {
        if ($denied = $this->authorizeJson(OperationPermission::GAME_MANAGEMENT_DELETE)) {
            return $denied;
        }

        try {
            $page = $this->service->page($code);
            $ids = (array) $request->input('ids', []);
            $deleted = $this->service->bulkDelete($code, $ids);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $this->audit(
            'game_management.record.bulk_delete',
            $page['storage'],
            '批量删除 '.$page['title'],
            ['page_code' => (string) $code, 'ids' => $ids, 'deleted' => $deleted]
        );

        return $this->success('已删除 '.$deleted.' 条记录', ['deleted' => $deleted]);
    }

    public function changeStatus(Request $request, $code)
    {
        if ($denied = $this->authorizeJson(OperationPermission::GAME_MANAGEMENT_WRITE)) {
            return $denied;
        }

        try {
            $page = $this->service->page($code);
            $ids = (array) $request->input('ids', []);
            $status = $request->input('status', 'enabled');
            $updated = $this->service->changeStatus($code, $ids, $status);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 422);
        }

        $this->audit(
            'game_management.status.update',
            $page['storage'],
            '更新 '.$page['title'].' 状态',
            [
                'page_code' => (string) $code,
                'ids' => $ids,
                'status' => $status,
                'updated' => $updated,
            ]
        );

        return $this->success('状态已更新', ['updated' => $updated]);
    }

    public function import(Request $request, $code)
    {
        if ($denied = $this->authorizeJson(OperationPermission::GAME_MANAGEMENT_WRITE)) {
            return $denied;
        }

        try {
            $page = $this->service->page($code);
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }
        if (!in_array('import', $page['actions'], true)) {
            return $this->error('当前页面不支持导入', 422);
        }

        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return $this->error('请选择有效的 CSV 文件', 422);
        }
        if (strtolower($file->getClientOriginalExtension()) !== 'csv') {
            return $this->error('只支持 CSV 文件', 422);
        }

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return $this->error('CSV 文件无法读取', 422);
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            fclose($handle);
            return $this->error('CSV 缺少表头', 422);
        }
        $headers = array_map(function ($header) {
            return trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $header));
        }, $headers);

        $imported = 0;
        $rowNumber = 1;
        try {
            DB::transaction(function () use (
                $handle,
                $headers,
                $code,
                &$imported,
                &$rowNumber
            ) {
                while (($row = fgetcsv($handle)) !== false) {
                    $rowNumber++;
                    if ($this->isEmptyCsvRow($row)) {
                        continue;
                    }
                    if (count($row) !== count($headers)) {
                        throw new InvalidArgumentException('第 '.$rowNumber.' 行栏位数量不一致');
                    }
                    $input = array_combine($headers, $row);
                    $id = $this->service->resolveImportId($code, $input);
                    $this->service->saveRecord($code, $input, $id);
                    $imported++;
                    if ($imported > 1000) {
                        throw new InvalidArgumentException('单次最多导入 1000 条记录');
                    }
                }
            });
        } catch (InvalidArgumentException $exception) {
            fclose($handle);
            return $this->error($exception->getMessage(), 422);
        } catch (\Throwable $exception) {
            fclose($handle);
            report($exception);
            return $this->error('第 '.$rowNumber.' 行导入失败，请检查唯一字段和数据类型', 422);
        }
        fclose($handle);

        if ($imported === 0) {
            return $this->error('CSV 中没有可导入的数据', 422);
        }

        $this->audit(
            'game_management.import',
            $page['storage'],
            '导入 '.$page['title'],
            ['page_code' => (string) $code, 'imported' => $imported]
        );

        return $this->success('CSV 导入完成', ['imported' => $imported]);
    }

    public function export(Request $request, $code)
    {
        if ($denied = $this->authorizeJson(OperationPermission::GAME_MANAGEMENT_EXPORT)) {
            return $denied;
        }

        try {
            $page = $this->service->page($code);
            $rows = $this->service->exportRows($code, $request->query());
        } catch (InvalidArgumentException $exception) {
            return $this->error($exception->getMessage(), 404);
        }

        $headers = array_merge(['id'], array_keys($page['columns']));
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, array_merge(['ID'], array_values($page['columns'])));
        foreach ($rows as $row) {
            $row = (array) $row;
            $values = [];
            foreach ($headers as $field) {
                $values[] = $this->csvValue(isset($row[$field]) ? $row[$field] : '');
            }
            fputcsv($stream, $values);
        }
        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        $this->audit(
            'game_management.export',
            $page['storage'],
            '导出 '.$page['title'],
            [
                'page_code' => (string) $code,
                'filters' => $request->query(),
                'rows' => count($rows),
            ]
        );

        $filename = 'game-management-'.$code.'-'.date('Ymd-His').'.csv';

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    private function renderPage(Content $content, Request $request, $code)
    {
        if (!OperationPermission::can(OperationPermission::GAME_MANAGEMENT_READ)) {
            abort(403, '无权访问游戏管理');
        }

        $page = $this->service->page($code);
        $records = $this->service->rows(
            $code,
            $request->query(),
            $request->input('per_page', 20)
        );
        $options = $this->service->fieldOptions($page);

        Admin::style('.content-header h1 small{display:block;margin-top:6px;}');

        return $content
            ->header($page['title'])
            ->description('游戏管理 / '.$page['title'].' / 真实业务数据')
            ->body(view('admin.game-management', compact('page', 'records', 'options')));
    }

    private function authorizeJson($ability)
    {
        if (OperationPermission::can($ability)) {
            return null;
        }

        return $this->error('无权执行该操作', 403);
    }

    private function audit($action, $module, $content, array $context)
    {
        if (!Schema::hasTable('admin_audit_logs')) {
            return;
        }

        $admin = null;
        try {
            $admin = Admin::user();
        } catch (\Throwable $exception) {
        }
        $request = request();

        DB::table('admin_audit_logs')->insert([
            'admin_user_id' => $admin ? $admin->getKey() : null,
            'admin_name' => $admin
                ? (string) ($admin->username ?? $admin->name ?? $admin->getKey())
                : 'unknown',
            'action' => $action,
            'module' => $module,
            'content' => $content,
            'ip_address' => $request ? $request->ip() : null,
            'user_agent' => $request ? ($request->userAgent() ?: '') : '',
            'context' => json_encode($context, JSON_UNESCAPED_UNICODE),
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function success($message, array $data = [])
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ]);
    }

    private function error($message, $statusCode)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
        ], $statusCode);
    }

    private function isEmptyCsvRow(array $row)
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function csvValue($value)
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }
        $value = (string) $value;
        if (preg_match('/^[=\-+@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}
