<?php

namespace App\Admin\Controllers;

use App\Admin\Support\TcgShellCatalog;
use App\Http\Controllers\Controller;
use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TcgShellController extends Controller
{
    private const PIXEL_CONFIG_PATH = 'tcg/pixel-config.json';
    private const PIXEL_LOG_PATH = 'tcg/pixel-actions.log';
    private const PIXEL_LOG_ARCHIVE_PATH = 'tcg/pixel-actions.log.1';
    private const MAX_CONFIG_BYTES = 262144;
    private const MAX_LOG_BYTES = 1048576;
    private const PIXEL_TABS = [
        'pixel',
        'adjust',
        'appsflyer',
        'facebook',
        'voluum',
        'propellerads',
        'twitter',
        'traffic-factory',
        'red-track',
        'kwai',
        'tiktok',
        'keitaro',
        'snapchat',
    ];
    private const PIXEL_ACTIONS = [
        'record.create',
        'record.update',
        'record.delete',
        'record.toggle',
        'settings.save',
        'preset.save',
    ];

    public function show(Content $content, string $code)
    {
        $page = TcgShellCatalog::page($code);

        if (!$page) {
            abort(404);
        }

        if ($code === '12535') {
            $page['pixelConfig'] = $this->normalizePixelPayload($this->loadPixelConfig());
            $page['pixelLogs'] = $this->loadPixelLogs();
        }

        return $content
            ->title($page['title'])
            ->description($page['category'].' / '.$page['code'])
            ->body(view('admin.tcg-shell', compact('page'))->render());
    }

    public function savePixelConfig(Request $request)
    {
        $this->ensureAuthenticatedAdmin();

        $config = $request->input('config');
        if (!is_array($config)) {
            return response()->json([
                'status' => false,
                'message' => '配置格式不正确',
            ], 422);
        }

        $existingPayload = $this->normalizePixelPayload($this->loadPixelConfig());
        $payload = [
            'updated_at' => date('c'),
            'updated_by' => $this->currentAdminName(),
            'config' => $this->sanitizeConfig($config),
            'benchmark' => $existingPayload['benchmark'],
        ];

        $this->storePixelPayload($payload);
        $this->writePixelLog('config.save', [
            'keys' => array_keys($payload['config']),
        ]);

        return response()->json([
            'status' => true,
            'message' => '像素配置已保存',
            'saved_at' => $payload['updated_at'],
        ]);
    }

    public function mutatePixelData(Request $request)
    {
        $this->ensureAuthenticatedAdmin();

        $action = (string) $request->input('action', '');
        $tab = (string) $request->input('tab', '');

        if (!in_array($action, self::PIXEL_ACTIONS, true)) {
            return response()->json([
                'status' => false,
                'message' => '不支持的操作',
            ], 422);
        }

        if (!in_array($tab, self::PIXEL_TABS, true)) {
            return response()->json([
                'status' => false,
                'message' => '页面标识不正确',
            ], 422);
        }

        $payload = $this->normalizePixelPayload($this->loadPixelConfig());

        try {
            $payload = $this->mutatePixelState($payload, $action, $tab, $request);
            $payload['updated_at'] = date('c');
            $payload['updated_by'] = $this->currentAdminName();
            $this->storePixelPayload($payload);
        } catch (\InvalidArgumentException $exception) {
            return response()->json([
                'status' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        $tabState = $payload['benchmark']['tabs'][$tab];
        $this->writePixelLog($action, [
            'tab' => $tab,
            'record_id' => (string) $request->input('id', ''),
        ]);

        return response()->json([
            'status' => true,
            'message' => '保存成功',
            'saved_at' => $payload['updated_at'],
            'data' => $tabState,
        ]);
    }

    public function appendPixelLog(Request $request)
    {
        $this->ensureAuthenticatedAdmin();

        $action = trim((string) $request->input('action', 'tool.action'));
        $context = $request->input('context', []);

        if (!preg_match('/^[A-Za-z0-9_.:-]{1,120}$/', $action)) {
            return response()->json([
                'status' => false,
                'message' => '日志 action 格式不正确',
            ], 422);
        }

        if (!is_array($context)) {
            $context = ['value' => (string) $context];
        }

        $entry = $this->writePixelLog($action, $this->sanitizeConfig($context));

        return response()->json([
            'status' => true,
            'message' => '操作日志已记录',
            'entry' => $entry,
        ]);
    }

    private function mutatePixelState(array $payload, string $action, string $tab, Request $request): array
    {
        $tabState = $payload['benchmark']['tabs'][$tab] ?? $this->emptyPixelTabState();
        $records = array_values(is_array($tabState['records'] ?? null) ? $tabState['records'] : []);
        $recordId = substr(trim((string) $request->input('id', '')), 0, 100);

        if ($action === 'record.create') {
            $values = $this->sanitizeValueList($request->input('values', []));
            if (!$this->hasNonEmptyValue($values)) {
                throw new \InvalidArgumentException('请至少填写一个字段');
            }
            $now = date('c');
            $records[] = [
                'id' => str_replace('.', '', uniqid('px_', true)),
                'values' => $values,
                'enabled' => (bool) $request->input('enabled', true),
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $tabState['records'] = $records;
        }

        if ($action === 'record.update') {
            $recordIndex = $this->findRecordIndex($records, $recordId);
            if ($recordIndex < 0) {
                throw new \InvalidArgumentException('记录不存在或已删除');
            }
            $values = $this->sanitizeValueList($request->input('values', []));
            if (!$this->hasNonEmptyValue($values)) {
                throw new \InvalidArgumentException('请至少填写一个字段');
            }
            $records[$recordIndex]['values'] = $values;
            $records[$recordIndex]['enabled'] = (bool) $request->input(
                'enabled',
                $records[$recordIndex]['enabled'] ?? true
            );
            $records[$recordIndex]['updated_at'] = date('c');
            $tabState['records'] = $records;
        }

        if ($action === 'record.delete') {
            $recordIndex = $this->findRecordIndex($records, $recordId);
            if ($recordIndex < 0) {
                throw new \InvalidArgumentException('记录不存在或已删除');
            }
            array_splice($records, $recordIndex, 1);
            $tabState['records'] = array_values($records);
        }

        if ($action === 'record.toggle') {
            $recordIndex = $this->findRecordIndex($records, $recordId);
            if ($recordIndex < 0) {
                throw new \InvalidArgumentException('记录不存在或已删除');
            }
            $records[$recordIndex]['enabled'] = (bool) $request->input(
                'enabled',
                !($records[$recordIndex]['enabled'] ?? true)
            );
            $records[$recordIndex]['updated_at'] = date('c');
            $tabState['records'] = $records;
        }

        if ($action === 'settings.save') {
            $tabState['settings'] = $this->sanitizeValueList($request->input('values', []));
        }

        if ($action === 'preset.save') {
            $presetIndex = (int) $request->input('index', -1);
            if ($presetIndex < 0 || $presetIndex > 49) {
                throw new \InvalidArgumentException('预设项目不正确');
            }
            $presets = array_values(is_array($tabState['presets'] ?? null) ? $tabState['presets'] : []);
            while (count($presets) <= $presetIndex) {
                $presets[] = '';
            }
            $presets[$presetIndex] = $this->sanitizeScalar($request->input('value', ''));
            $tabState['presets'] = $presets;
        }

        $payload['benchmark']['tabs'][$tab] = [
            'records' => array_values($tabState['records'] ?? []),
            'settings' => array_values($tabState['settings'] ?? []),
            'presets' => array_values($tabState['presets'] ?? []),
        ];

        return $payload;
    }

    private function normalizePixelPayload(array $payload): array
    {
        $normalized = [
            'updated_at' => (string) ($payload['updated_at'] ?? ''),
            'updated_by' => (string) ($payload['updated_by'] ?? ''),
            'config' => is_array($payload['config'] ?? null) ? $payload['config'] : [],
            'benchmark' => [
                'tabs' => [],
            ],
        ];

        $tabs = $payload['benchmark']['tabs'] ?? [];
        foreach (self::PIXEL_TABS as $tab) {
            $tabState = is_array($tabs[$tab] ?? null) ? $tabs[$tab] : [];
            $normalized['benchmark']['tabs'][$tab] = [
                'records' => array_values(is_array($tabState['records'] ?? null) ? $tabState['records'] : []),
                'settings' => array_values(is_array($tabState['settings'] ?? null) ? $tabState['settings'] : []),
                'presets' => array_values(is_array($tabState['presets'] ?? null) ? $tabState['presets'] : []),
            ];
        }

        return $normalized;
    }

    private function emptyPixelTabState(): array
    {
        return [
            'records' => [],
            'settings' => [],
            'presets' => [],
        ];
    }

    private function findRecordIndex(array $records, string $recordId): int
    {
        if ($recordId === '') {
            return -1;
        }

        foreach ($records as $index => $record) {
            if ((string) ($record['id'] ?? '') === $recordId) {
                return $index;
            }
        }

        return -1;
    }

    private function sanitizeValueList($values): array
    {
        if (!is_array($values)) {
            throw new \InvalidArgumentException('字段格式不正确');
        }

        return array_values(array_map(function ($value) {
            return $this->sanitizeScalar($value);
        }, array_slice($values, 0, 50)));
    }

    private function sanitizeScalar($value): string
    {
        if (is_array($value) || is_object($value)) {
            return '';
        }

        return substr(strip_tags(trim((string) $value)), 0, 5000);
    }

    private function hasNonEmptyValue(array $values): bool
    {
        foreach ($values as $value) {
            if (trim((string) $value) !== '') {
                return true;
            }
        }

        return false;
    }

    private function loadPixelConfig(): array
    {
        if (!Storage::disk('local')->exists(self::PIXEL_CONFIG_PATH)) {
            return [];
        }

        $payload = json_decode(
            Storage::disk('local')->get(self::PIXEL_CONFIG_PATH),
            true
        );

        return is_array($payload) ? $payload : [];
    }

    private function storePixelPayload(array $payload): void
    {
        $encodedPayload = json_encode(
            $payload,
            JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
        );

        if ($encodedPayload === false || strlen($encodedPayload) > self::MAX_CONFIG_BYTES) {
            throw new \InvalidArgumentException('配置内容过大');
        }

        Storage::disk('local')->put(self::PIXEL_CONFIG_PATH, $encodedPayload);
    }

    private function loadPixelLogs(): array
    {
        if (!Storage::disk('local')->exists(self::PIXEL_LOG_PATH)) {
            return [];
        }

        $lines = preg_split(
            '/\R/u',
            trim(Storage::disk('local')->get(self::PIXEL_LOG_PATH))
        );

        return array_values(array_filter(array_map(function ($line) {
            $entry = json_decode($line, true);
            return is_array($entry) ? $entry : null;
        }, array_slice($lines ?: [], -20))));
    }

    private function writePixelLog(string $action, array $context = []): array
    {
        $disk = Storage::disk('local');

        if (
            $disk->exists(self::PIXEL_LOG_PATH)
            && $disk->size(self::PIXEL_LOG_PATH) >= self::MAX_LOG_BYTES
        ) {
            if ($disk->exists(self::PIXEL_LOG_ARCHIVE_PATH)) {
                $disk->delete(self::PIXEL_LOG_ARCHIVE_PATH);
            }
            $disk->move(self::PIXEL_LOG_PATH, self::PIXEL_LOG_ARCHIVE_PATH);
        }

        $entry = [
            'time' => date('c'),
            'admin' => $this->currentAdminName(),
            'action' => substr($action, 0, 120),
            'context' => $context,
        ];

        $disk->append(
            self::PIXEL_LOG_PATH,
            json_encode($entry, JSON_UNESCAPED_UNICODE)
        );

        return $entry;
    }

    private function currentAdminName(): string
    {
        $user = Admin::user();

        if (!$user) {
            return 'system';
        }

        return (string) ($user->username ?? $user->name ?? $user->id ?? 'admin');
    }

    private function ensureAuthenticatedAdmin(): void
    {
        if (!Admin::user()) {
            abort(403);
        }
    }

    private function sanitizeConfig(array $config, int $depth = 0): array
    {
        if ($depth > 4) {
            return [];
        }

        $result = [];
        foreach (array_slice($config, 0, 100, true) as $key => $value) {
            $safeKey = substr(preg_replace('/[^A-Za-z0-9_.-]/', '_', (string) $key), 0, 100);

            if (is_array($value)) {
                $result[$safeKey] = $this->sanitizeConfig($value, $depth + 1);
                continue;
            }

            if (is_bool($value) || is_numeric($value) || $value === null) {
                $result[$safeKey] = $value;
                continue;
            }

            $result[$safeKey] = substr(strip_tags((string) $value), 0, 5000);
        }

        return $result;
    }
}
