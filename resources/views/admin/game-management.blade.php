@php
    $actions = $page['actions'];
    $statusField = $page['status_field'];
    $recordPayloads = [];
    foreach ($records as $record) {
        $recordPayloads[(string) $record->id] = (array) $record;
    }
    $filterOptions = $options;
    if (!isset($filterOptions['status']) || !count($filterOptions['status'])) {
        $filterOptions['status'] = ['enabled' => '启用', 'disabled' => '停用'];
    }
    if ($page['storage'] === 'game_lists') {
        $filterOptions['site_state'] = [1 => '启用', 0 => '停用'];
    }
    $valueLabel = function ($field, $value) use ($options) {
        if (isset($options[$field])) {
            foreach ($options[$field] as $key => $label) {
                if ((string) $key === (string) $value) {
                    return $label;
                }
            }
        }
        if (in_array($field, ['site_state', 'app_state', 'is_hot', 'is_new', 'is_recommend', 'is_top', 'bet_level_sort'], true)) {
            return (int) $value === 1 ? '是' : '否';
        }
        if ($field === 'status') {
            $labels = [
                'enabled' => '启用',
                'disabled' => '停用',
                'pending' => '待处理',
                'published' => '已发布',
                'cancelled' => '已取消',
            ];
            return isset($labels[$value]) ? $labels[$value] : $value;
        }
        return $value === null || $value === '' ? '-' : $value;
    };
@endphp

<style>
    .game-management-page { --gm-border:#e6ebf1; --gm-muted:#7b8794; }
    .game-management-card { background:#fff; border:1px solid var(--gm-border); border-radius:4px; margin-bottom:16px; box-shadow:0 1px 2px rgba(31,45,61,.04); }
    .game-management-card-head { display:flex; align-items:center; justify-content:space-between; gap:12px; padding:14px 16px; border-bottom:1px solid var(--gm-border); }
    .game-management-card-head h4 { margin:0; font-weight:600; }
    .game-management-card-body { padding:16px; }
    .game-management-filter { display:grid; grid-template-columns:repeat(4,minmax(150px,1fr)); gap:12px; align-items:end; }
    .game-management-filter label { display:block; margin-bottom:5px; font-weight:600; color:#4c5a67; }
    .game-management-filter-actions { display:flex; gap:8px; flex-wrap:wrap; }
    .game-management-toolbar { display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; margin-bottom:12px; }
    .game-management-toolbar-group { display:flex; gap:8px; flex-wrap:wrap; }
    .game-management-table-wrap { overflow-x:auto; border:1px solid var(--gm-border); border-radius:4px; }
    .game-management-table { margin:0; min-width:960px; }
    .game-management-table th { background:#f7f9fb; white-space:nowrap; }
    .game-management-table td { vertical-align:middle!important; white-space:nowrap; }
    .game-management-empty { padding:36px!important; text-align:center; color:var(--gm-muted); }
    .game-management-status-on { color:#21a179; font-weight:600; }
    .game-management-status-off { color:#c94f55; font-weight:600; }
    .game-management-pagination { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; padding-top:14px; color:var(--gm-muted); }
    .game-management-record-modal .modal-body { max-height:68vh; overflow:auto; }
    .game-management-form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
    .game-management-form-grid .form-group { margin:0; }
    .game-management-form-grid .form-group-wide { grid-column:1/-1; }
    .game-management-required { color:#d9534f; }
    @media (max-width:992px) {
        .game-management-filter { grid-template-columns:repeat(2,minmax(150px,1fr)); }
    }
    @media (max-width:600px) {
        .game-management-filter, .game-management-form-grid { grid-template-columns:1fr; }
        .game-management-card-head { align-items:flex-start; flex-direction:column; }
        .game-management-toolbar, .game-management-toolbar-group { align-items:stretch; width:100%; }
        .game-management-toolbar-group .btn { flex:1 1 auto; }
    }
</style>

<div class="game-management-page" data-code="{{ $page['code'] }}">
    <div class="game-management-card">
        <div class="game-management-card-head">
            <div>
                <h4>{{ $page['title'] }}</h4>
                <small class="text-muted">数据来源：{{ $page['storage'] }}，所有操作均写入数据库和审计日志。</small>
            </div>
            <span class="label label-primary">页面代码 {{ $page['code'] }}</span>
        </div>
        <div class="game-management-card-body">
            <form method="get" class="game-management-filter">
                @foreach($page['filters'] as $name => $label)
                    <div>
                        <label>{{ $label }}</label>
                        @if(isset($filterOptions[$name]) && count($filterOptions[$name]))
                            <select name="{{ $name }}" class="form-control">
                                <option value="">全部</option>
                                @foreach($filterOptions[$name] as $key => $optionLabel)
                                    <option value="{{ $key }}" {{ (string) request($name) === (string) $key ? 'selected' : '' }}>{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                        @elseif(in_array($name, ['date_from', 'date_to'], true))
                            <input type="date" name="{{ $name }}" class="form-control" value="{{ request($name) }}">
                        @else
                            <input type="text" name="{{ $name }}" class="form-control" value="{{ request($name) }}" placeholder="请输入{{ $label }}">
                        @endif
                    </div>
                @endforeach
                <div class="game-management-filter-actions">
                    <button type="submit" class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                    <a href="{{ request()->url() }}" class="btn btn-default"><i class="fa fa-refresh"></i> 重置</a>
                </div>
            </form>
        </div>
    </div>

    <div class="game-management-card">
        <div class="game-management-card-body">
            <div class="game-management-toolbar">
                <div class="game-management-toolbar-group">
                    @if(in_array('create', $actions, true))
                        <button type="button" class="btn btn-primary game-management-create"><i class="fa fa-plus"></i> 新增</button>
                    @endif
                    @if(in_array('status', $actions, true))
                        <button type="button" class="btn btn-success game-management-status" data-status="enabled"><i class="fa fa-toggle-on"></i> 批量启用</button>
                        <button type="button" class="btn btn-warning game-management-status" data-status="disabled"><i class="fa fa-toggle-off"></i> 批量停用</button>
                    @endif
                    @if(in_array('bulk-delete', $actions, true))
                        <button type="button" class="btn btn-danger game-management-bulk-delete"><i class="fa fa-trash"></i> 批量删除</button>
                    @endif
                </div>
                <div class="game-management-toolbar-group">
                    @if(in_array('import', $actions, true))
                        <button type="button" class="btn btn-default game-management-import"><i class="fa fa-upload"></i> 导入 CSV</button>
                        <input type="file" class="game-management-import-file" accept=".csv,text/csv" style="display:none">
                    @endif
                    @if(in_array('export', $actions, true))
                        <a class="btn btn-success game-management-export" target="_blank" rel="noopener" href="{{ admin_url('tcg/game-management/'.$page['code'].'/export').'?'.http_build_query(request()->query()) }}"><i class="fa fa-download"></i> 导出当前筛选</a>
                    @endif
                </div>
            </div>

            <div class="game-management-table-wrap">
                <table class="table table-hover game-management-table">
                    <thead>
                    <tr>
                        <th style="width:40px"><input type="checkbox" class="game-management-check-all"></th>
                        <th>ID</th>
                        @foreach($page['columns'] as $column => $label)
                            <th>{{ $label }}</th>
                        @endforeach
                        <th style="min-width:170px">操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($records as $record)
                        @php
                            $row = (array) $record;
                            $rawStatus = isset($row[$statusField]) ? $row[$statusField] : null;
                            $enabled = in_array((string) $rawStatus, ['1', 'enabled', 'published', 'pending'], true);
                        @endphp
                        <tr>
                            <td><input type="checkbox" class="game-management-check" value="{{ $record->id }}"></td>
                            <td>{{ $record->id }}</td>
                            @foreach($page['columns'] as $column => $label)
                                <td>
                                    @if($column === $statusField || in_array($column, ['site_state', 'app_state', 'is_hot', 'is_new', 'is_recommend', 'is_top'], true))
                                        @php $cellEnabled = in_array((string) ($row[$column] ?? ''), ['1', 'enabled', 'published', 'pending'], true); @endphp
                                        <span class="{{ $cellEnabled ? 'game-management-status-on' : 'game-management-status-off' }}">{{ $valueLabel($column, $row[$column] ?? null) }}</span>
                                    @else
                                        {{ $valueLabel($column, $row[$column] ?? null) }}
                                    @endif
                                </td>
                            @endforeach
                            <td>
                                @if(in_array('edit', $actions, true))
                                    <button type="button" class="btn btn-xs btn-primary game-management-edit" data-id="{{ $record->id }}"><i class="fa fa-edit"></i> 编辑</button>
                                @endif
                                @if(in_array('status', $actions, true))
                                    <button type="button" class="btn btn-xs {{ $enabled ? 'btn-warning' : 'btn-success' }} game-management-status" data-id="{{ $record->id }}" data-status="{{ $enabled ? 'disabled' : 'enabled' }}">{{ $enabled ? '停用' : '启用' }}</button>
                                @endif
                                @if(in_array('delete', $actions, true))
                                    <button type="button" class="btn btn-xs btn-danger game-management-delete" data-id="{{ $record->id }}"><i class="fa fa-trash"></i> 删除</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($page['columns']) + 3 }}" class="game-management-empty">当前筛选没有业务数据。可通过“新增”或“导入 CSV”建立真实记录。</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="game-management-pagination">
                <span>共 {{ $records->total() }} 条记录，第 {{ $records->currentPage() }} / {{ max(1, $records->lastPage()) }} 页</span>
                <span>{{ $records->links() }}</span>
            </div>
        </div>
    </div>
</div>

<div class="modal fade game-management-record-modal" id="game-management-record-modal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">编辑 {{ $page['title'] }}</h4>
            </div>
            <div class="modal-body">
                <input type="hidden" class="game-management-record-id">
                <div class="game-management-form-grid">
                    @foreach($page['fields'] as $name => $field)
                        <div class="form-group {{ $field['type'] === 'textarea' ? 'form-group-wide' : '' }}">
                            <label>
                                @if(!empty($field['required']))<span class="game-management-required">*</span>@endif
                                {{ $field['label'] }}
                            </label>
                            @if(in_array($field['type'], ['select', 'boolean'], true))
                                <select class="form-control game-management-input" data-field="{{ $name }}" data-required="{{ !empty($field['required']) ? '1' : '0' }}">
                                    <option value="">请选择</option>
                                    @foreach(($options[$name] ?? $field['options'] ?? []) as $key => $optionLabel)
                                        <option value="{{ $key }}">{{ $optionLabel }}</option>
                                    @endforeach
                                </select>
                            @elseif($field['type'] === 'textarea')
                                <textarea rows="4" class="form-control game-management-input" data-field="{{ $name }}" data-required="{{ !empty($field['required']) ? '1' : '0' }}"></textarea>
                            @else
                                @php
                                    $inputType = 'text';
                                    if (in_array($field['type'], ['integer', 'decimal'], true)) $inputType = 'number';
                                    if ($field['type'] === 'datetime') $inputType = 'datetime-local';
                                    if ($field['type'] === 'month') $inputType = 'month';
                                @endphp
                                <input type="{{ $inputType }}" class="form-control game-management-input" data-field="{{ $name }}" data-required="{{ !empty($field['required']) ? '1' : '0' }}" {{ $field['type'] === 'decimal' ? 'step=0.000001' : '' }}>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary game-management-save">保存</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var root = $('.game-management-page[data-code="{{ $page['code'] }}"]');
    var modal = $('#game-management-record-modal');
    var recordMap = @json($recordPayloads);
    var base = '{{ admin_url('tcg/game-management/'.$page['code']) }}';
    var csrf = '{{ csrf_token() }}';

    function notify(type, message) {
        if (window.Dcat && typeof Dcat[type] === 'function') {
            Dcat[type](message);
            return;
        }
        console[type === 'error' ? 'error' : 'log'](message);
    }

    function request(url, method, data, done) {
        $.ajax({
            url: url,
            type: method,
            data: data,
            headers: {'X-CSRF-TOKEN': csrf}
        }).done(function (response) {
            if (!response.status) {
                notify('error', response.message || '操作失败');
                return;
            }
            notify('success', response.message || '操作成功');
            if (done) done(response);
        }).fail(function (xhr) {
            var response = xhr.responseJSON || {};
            notify('error', response.message || '请求失败');
        });
    }

    function selectedIds() {
        return root.find('.game-management-check:checked').map(function () {
            return $(this).val();
        }).get();
    }

    function openEditor(id) {
        var record = id ? (recordMap[String(id)] || {}) : {};
        modal.find('.game-management-record-id').val(id || '');
        modal.find('.modal-title').text((id ? '编辑 ' : '新增 ') + @json($page['title']));
        modal.find('.game-management-input').each(function () {
            var input = $(this);
            var field = input.data('field');
            var value = record[field];
            if (input.attr('type') === 'datetime-local' && value) {
                value = String(value).replace(' ', 'T').slice(0, 16);
            }
            input.val(value === null || typeof value === 'undefined' ? '' : String(value));
        });
        modal.modal('show');
    }

    root.on('change', '.game-management-check-all', function () {
        root.find('.game-management-check').prop('checked', $(this).prop('checked'));
    });

    root.on('click', '.game-management-create', function () {
        openEditor(null);
    });

    root.on('click', '.game-management-edit', function () {
        openEditor($(this).data('id'));
    });

    modal.on('click', '.game-management-save', function () {
        var id = modal.find('.game-management-record-id').val();
        var payload = {};
        var invalid = null;
        modal.find('.game-management-input').each(function () {
            var input = $(this);
            var field = input.data('field');
            var value = input.val();
            if (input.data('required') === 1 && String(value || '').trim() === '' && !invalid) {
                invalid = input;
            }
            payload[field] = value;
        });
        if (invalid) {
            invalid.focus();
            notify('error', '请填写所有必填字段');
            return;
        }
        request(base + '/records' + (id ? '/' + id : ''), id ? 'PUT' : 'POST', payload, function () {
            window.location.reload();
        });
    });

    root.on('click', '.game-management-delete', function () {
        var id = $(this).data('id');
        if (!window.confirm('确认删除这条业务记录？此操作会写入审计日志。')) return;
        request(base + '/records/' + id, 'DELETE', {}, function () {
            window.location.reload();
        });
    });

    root.on('click', '.game-management-status', function () {
        var button = $(this);
        var ids = button.data('id') ? [button.data('id')] : selectedIds();
        if (!ids.length) {
            notify('error', '请先选择记录');
            return;
        }
        request(base + '/status', 'POST', {
            ids: ids,
            status: button.data('status')
        }, function () {
            window.location.reload();
        });
    });

    root.on('click', '.game-management-bulk-delete', function () {
        var ids = selectedIds();
        if (!ids.length) {
            notify('error', '请先选择记录');
            return;
        }
        if (!window.confirm('确认删除选中的 ' + ids.length + ' 条记录？')) return;
        request(base + '/bulk-delete', 'POST', {ids: ids}, function () {
            window.location.reload();
        });
    });

    root.on('click', '.game-management-import', function () {
        root.find('.game-management-import-file').val('').trigger('click');
    });

    root.on('change', '.game-management-import-file', function () {
        var file = this.files && this.files[0];
        if (!file) return;
        var formData = new FormData();
        formData.append('file', file);
        $.ajax({
            url: base + '/import',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {'X-CSRF-TOKEN': csrf}
        }).done(function (response) {
            if (!response.status) {
                notify('error', response.message || '导入失败');
                return;
            }
            notify('success', response.message || '导入完成');
            window.location.reload();
        }).fail(function (xhr) {
            var response = xhr.responseJSON || {};
            notify('error', response.message || '导入失败');
        });
    });
})();
</script>
