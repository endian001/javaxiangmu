<?php

namespace App\Admin\Support;

use App\Models\UserOperateLog;
use Dcat\Admin\Admin;

class OpsChangeAudit
{
    public static function writeFormChanges($action, $form, array $fields, array $maskedFields = [], $targetNameField = 'name')
    {
        if (! $form || method_exists($form, 'isCreating') && $form->isCreating()) {
            return;
        }

        $model = $form->model();
        if (! $model) {
            return;
        }

        $changes = [];
        foreach ($fields as $field => $label) {
            $old = static::modelValue($model, $field);
            $new = static::formValue($form, $field);
            if (static::sameValue($old, $new)) {
                continue;
            }

            if (in_array($field, $maskedFields, true)) {
                if (trim((string) $new) === '') {
                    continue;
                }
                $old = '***';
                $new = '***';
            }

            $changes[$field] = [
                'label' => $label,
                'old' => static::shortValue($old),
                'new' => static::shortValue($new),
            ];
        }

        if (! $changes) {
            return;
        }

        static::insert($action, static::modelKey($model), static::targetName($model, $targetNameField), $changes);
    }

    public static function changedFields($form, array $fields, array $maskedFields = [])
    {
        if (! $form || method_exists($form, 'isCreating') && $form->isCreating()) {
            return [];
        }

        $model = $form->model();
        if (! $model) {
            return [];
        }

        $changed = [];
        foreach ($fields as $field) {
            $old = static::modelValue($model, $field);
            $new = static::formValue($form, $field);
            if (in_array($field, $maskedFields, true) && trim((string) $new) === '') {
                continue;
            }
            if (! static::sameValue($old, $new)) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    public static function hasAnyChanged($form, array $fields, array $maskedFields = [])
    {
        return (bool) static::changedFields($form, $fields, $maskedFields);
    }

    public static function writeFormSnapshot($action, $form, array $fields, array $maskedFields = [], $targetNameField = 'name')
    {
        if (! $form) {
            return;
        }

        $model = $form->model();
        $changes = [];
        foreach ($fields as $field => $label) {
            $value = static::formValue($form, $field);
            if (in_array($field, $maskedFields, true)) {
                $value = trim((string) $value) === '' ? '' : '***';
            }

            $changes[$field] = [
                'label' => $label,
                'old' => '',
                'new' => static::shortValue($value),
            ];
        }

        if (! $changes) {
            return;
        }

        $targetId = $model ? static::modelKey($model) : 'new';
        $targetName = $model ? static::targetName($model, $targetNameField) : '';
        if ($targetName === '') {
            $targetName = static::shortValue(static::formValue($form, $targetNameField));
        }

        static::insert($action, $targetId ?: 'new', $targetName, $changes);
    }

    public static function insert($action, $targetId, $targetName, array $changes)
    {
        $request = request();
        $admin = Admin::user();
        $adminName = $admin ? ((string)($admin->username ?? $admin->name ?? $admin->getKey())) : 'unknown';

        $info = [
            'action' => $action,
            'admin' => $adminName,
            'target_id' => $targetId,
            'target_name' => $targetName,
            'changes' => $changes,
        ];

        UserOperateLog::insertLog(
            0,
            7,
            $request ? ($request->userAgent() ?: '') : '',
            $request ? $request->ip() : '',
            '',
            static::shortValue('admin '.$adminName.' '.$action.' #'.$targetId.' '.$targetName),
            static::shortValue(json_encode($info, JSON_UNESCAPED_UNICODE))
        );
    }

    protected static function formValue($form, $field)
    {
        try {
            return $form->{$field};
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected static function modelValue($model, $field)
    {
        try {
            return $model->{$field};
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected static function modelKey($model)
    {
        if (method_exists($model, 'getKey')) {
            return $model->getKey();
        }

        return static::modelValue($model, 'id');
    }

    protected static function targetName($model, $field)
    {
        $value = static::modelValue($model, $field);
        if ($value === null || $value === '') {
            $value = static::modelValue($model, 'title');
        }
        if ($value === null || $value === '') {
            $value = static::modelValue($model, 'username');
        }

        return static::shortValue($value);
    }

    protected static function sameValue($old, $new)
    {
        return static::normalize($old) === static::normalize($new);
    }

    protected static function normalize($value)
    {
        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        return trim((string) $value);
    }

    protected static function shortValue($value)
    {
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        $value = (string) $value;
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, 1000);
        }

        return substr($value, 0, 1000);
    }
}
