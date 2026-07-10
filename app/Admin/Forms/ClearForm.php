<?php

namespace App\Admin\Forms;

use App\Admin\Support\OperationPermission;
use App\Models\ActivityApply;
use App\Models\GameRecord;
use App\Models\UserOperateLog;
use Dcat\Admin\Admin;
use Dcat\Admin\Widgets\Form;

class ClearForm extends Form
{
    const MIN_SAVE_DAYS = 365;

    /**
     * Handle the form request.
     *
     * @param array $input
     *
     * @return mixed
     */
    public function handle(array $input)
    {
        try {
            OperationPermission::assert(OperationPermission::OPS_DATA_CLEANUP);
        } catch (\Throwable $e) {
            return $this->response()->error($e->getMessage());
        }

        $clear = $input['clear'] ?? [];
        if (!is_array($clear)) {
            $clear = $clear === '' ? [] : [$clear];
        }

        $clear = array_values(array_filter($clear));
        if (empty($clear)) {
            return $this->response()->error('Select at least one cleanup item');
        }

        $blocked = ['users_table', 'agent_table', 'finance_table', 'log_table'];
        if (array_intersect($clear, $blocked)) {
            return $this->response()->error('Member, agent, finance and operation-log cleanup is disabled');
        }

        $saveDays = isset($input['save_days']) ? (int) $input['save_days'] : 0;
        if ($saveDays < self::MIN_SAVE_DAYS) {
            return $this->response()->error('Retention days must be at least '.self::MIN_SAVE_DAYS);
        }

        $time = date('Y-m-d', strtotime('-'.$saveDays.' days'));
        $allowed = ['game_record_table', 'activity_table'];
        $preview = [];

        try {
            foreach ($clear as $v) {
                if (!in_array($v, $allowed, true)) {
                    continue;
                }

                if ($v === 'game_record_table') {
                    $preview[$v] = GameRecord::whereDate('created_at', '<', $time)->count();
                }

                if ($v === 'activity_table') {
                    $preview[$v] = ActivityApply::whereDate('created_at', '<', $time)->count();
                }
            }
        } catch (\Throwable $e) {
            return $this->response()->error($e->getMessage());
        }

        $this->writeAuditLog($clear, $saveDays, $time, $preview);

        return $this
            ->response()
            ->success('Cleanup preview generated; no data was deleted')
            ->refresh();
    }

    /**
     * Build a form here.
     */
    public function form()
    {
        $this->confirm('Generate cleanup preview', 'This form only counts old records. No data will be deleted.');
        $this->tab('Data cleanup preview', function () {
            $options = [
                'game_record_table' => 'Game records (preview only)',
                'activity_table' => 'Activity records (preview only)',
            ];

            $this->checkbox('clear', 'Preview items')->options($options);
            $this->number('save_days', 'Retention days')->default(self::MIN_SAVE_DAYS);
        });
    }

    /**
     * The data of the form.
     *
     * @return array
     */
    public function default()
    {
        return [
            'clear' => [],
            'save_days' => self::MIN_SAVE_DAYS,
        ];
    }

    protected function writeAuditLog(array $clear, $saveDays, $time, array $preview)
    {
        $request = request();
        UserOperateLog::insertLog(
            0,
            7,
            $request ? ($request->userAgent() ?: '') : '',
            $request ? $request->ip() : '',
            '',
            $this->shortText('admin '.$this->adminName().' cleanup preview '.implode(',', $clear).' before '.$time),
            $this->auditInfo([
                'action' => 'clear_form_cleanup_preview',
                'admin' => $this->adminName(),
                'clear' => $clear,
                'save_days' => $saveDays,
                'before_date' => $time,
                'preview' => $preview,
                'deleted' => 0,
            ])
        );
    }

    protected function adminName()
    {
        $admin = Admin::user();
        if (!$admin) {
            return 'unknown';
        }

        if (!empty($admin->username)) {
            return $admin->username;
        }

        if (!empty($admin->name)) {
            return $admin->name;
        }

        return (string) $admin->getKey();
    }

    protected function auditInfo(array $data)
    {
        $json = json_encode($data, JSON_UNESCAPED_UNICODE);
        return $json === false ? '' : $this->shortText($json);
    }

    protected function shortText($text)
    {
        return substr((string) $text, 0, 255);
    }
}
