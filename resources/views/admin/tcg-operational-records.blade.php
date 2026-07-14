@php
    $baseUrl = admin_url('tcg/ops/'.$page['code']);
    $recordsUrl = admin_url('tcg/ops/'.$page['code'].'/records');
    $exportUrl = admin_url('tcg/ops/'.$page['code'].'/export');
    $queryString = http_build_query(request()->query());
    if ($queryString !== '') {
        $exportUrl .= '?'.$queryString;
    }

    $fields = $schema['fields'];
    $columns = $schema['columns'];
    $statusOptions = $schema['status_options'];
    $emptyState = $schema['empty_state'] ?? '暂无记录';
    $actionLabel = $schema['action_label'] ?? '新增记录';
    $editLabel = $schema['edit_label'] ?? '编辑记录';
    $keywordLabels = collect($schema['keyword_fields'] ?? [])
        ->map(function ($name) use ($fields) {
            foreach ($fields as $field) {
                if ($field['name'] === $name) {
                    return $field['label'];
                }
            }
            return $name;
        })
        ->take(4)
        ->implode(' / ');
@endphp

<style>
    .tcg-ops-card{background:#fff;border-radius:6px;padding:16px;margin-bottom:16px;box-shadow:0 1px 3px rgba(0,0,0,.06)}
    .tcg-ops-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap}
    .tcg-ops-heading h4{margin:0 0 8px}
    .tcg-ops-badge{display:inline-block;padding:4px 8px;border-radius:4px;background:#eef5ff;color:#1f5fa8;font-size:12px}
    .tcg-ops-toolbar{display:flex;gap:8px;align-items:flex-end;flex-wrap:wrap;margin:14px 0 12px}
    .tcg-ops-toolbar .form-control{min-width:170px}
    .tcg-ops-table{width:100%;border-collapse:collapse;background:#fff}
    .tcg-ops-table th,.tcg-ops-table td{border:1px solid #e7eaef;padding:8px;vertical-align:top}
    .tcg-ops-table th{background:#f7f8fa;font-weight:600;white-space:nowrap}
    .tcg-ops-actions{display:flex;gap:6px;flex-wrap:wrap}
    .tcg-ops-muted{color:#7b8494}
    .tcg-ops-modal{position:fixed;inset:0;z-index:2050;background:rgba(0,0,0,.35);display:none;align-items:center;justify-content:center;padding:18px}
    .tcg-ops-modal.is-open{display:flex}
    .tcg-ops-dialog{width:min(820px,100%);max-height:calc(100vh - 36px);display:flex;flex-direction:column;background:#fff;border-radius:8px;box-shadow:0 18px 55px rgba(0,0,0,.25)}
    .tcg-ops-dialog header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #eef0f3}
    .tcg-ops-dialog main{padding:18px;display:grid;grid-template-columns:1fr 1fr;gap:12px;overflow:auto}
    .tcg-ops-dialog footer{display:flex;justify-content:flex-end;gap:8px;padding:14px 18px;border-top:1px solid #eef0f3}
    .tcg-ops-dialog textarea{min-height:96px;resize:vertical}
    .tcg-ops-wide{grid-column:1 / -1}
    .tcg-ops-required{color:#d9534f;margin-left:2px}
    @media(max-width:760px){.tcg-ops-dialog main{grid-template-columns:1fr}.tcg-ops-toolbar .form-control{min-width:100%}}
</style>

<div class="tcg-ops-card">
    <div class="tcg-ops-heading">
        <div>
            <h4>{{ $page['title'] }}</h4>
            <p class="text-muted">{{ $schema['description'] }}</p>
        </div>
        <span class="tcg-ops-badge">{{ $schema['mode_label'] }}</span>
    </div>

    @if(!$tableReady)
        <div class="alert alert-warning">
            业务表 {{ $schema['table'] }} 尚未创建，请先执行数据库迁移。
        </div>
    @endif

    <form class="tcg-ops-toolbar" method="get" action="{{ $baseUrl }}">
        <div>
            <label>关键词</label>
            <input class="form-control" name="keyword" value="{{ $keyword }}" placeholder="{{ $keywordLabels ?: '标题 / 用户 / 标识' }}">
        </div>
        <div>
            <label>状态</label>
            <select class="form-control" name="status">
                <option value="">全部</option>
                @foreach($statusOptions as $value => $label)
                    <option value="{{ $value }}" @if($status === $value) selected @endif>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button class="btn btn-primary" type="submit">搜索</button>
        <a class="btn btn-default" href="{{ $baseUrl }}">重置</a>
        @if($canWrite)
            <button class="btn btn-success" type="button" data-ops-new @if(!$tableReady) disabled @endif>{{ $actionLabel }}</button>
        @endif
        @if($canExport)
            <a class="btn btn-success" href="{{ $exportUrl }}">导出</a>
        @endif
    </form>

    <div class="table-responsive">
        <table class="tcg-ops-table">
            <thead>
            <tr>
                @foreach($columns as $column)
                    <th>{{ $column['label'] }}</th>
                @endforeach
                <th>操作</th>
            </tr>
            </thead>
            <tbody>
            @forelse($records as $record)
                @php
                    $rowData = ['id' => (int) $record->id];
                    foreach ($fields as $field) {
                        $rowData[$field['name']] = $record->{$field['name']} ?? '';
                    }
                @endphp
                <tr data-row data-record="{{ e(json_encode($rowData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) }}">
                    @foreach($columns as $column)
                        @php
                            $name = $column['name'];
                            $value = $record->{$name} ?? '';
                            if ($name === 'status') {
                                $value = $statusOptions[$value] ?? $value;
                            }
                        @endphp
                        <td>{{ \Illuminate\Support\Str::limit((string) $value, 80) }}</td>
                    @endforeach
                    <td>
                        <div class="tcg-ops-actions">
                            @if($canWrite)
                                <button class="btn btn-xs btn-primary" type="button" data-ops-edit>{{ $editLabel }}</button>
                            @endif
                            @if($canDelete)
                                <button class="btn btn-xs btn-danger" type="button" data-ops-delete>删除</button>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($columns) + 1 }}" class="text-center text-muted">{{ $emptyState }}</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div style="margin-top:12px">{{ $records->appends(request()->query())->links() }}</div>
</div>

@if($canWrite)
<div class="tcg-ops-modal" data-ops-modal>
    <div class="tcg-ops-dialog">
        <header>
            <strong data-ops-modal-title>{{ $actionLabel }}</strong>
            <button class="btn btn-xs btn-default" type="button" data-ops-close>关闭</button>
        </header>
        <main>
            <input type="hidden" data-field="id">
            @foreach($fields as $field)
                @php
                    $type = $field['type'] ?? 'text';
                    $wide = in_array($type, ['textarea'], true);
                    $fieldOptions = $field['options'] ?? [];
                @endphp
                <div class="{{ $wide ? 'tcg-ops-wide' : '' }}">
                    <label>
                        {{ $field['label'] }}
                        @if(!empty($field['required']))<span class="tcg-ops-required">*</span>@endif
                    </label>
                    @if($type === 'textarea')
                        <textarea class="form-control" data-field="{{ $field['name'] }}" @if(!empty($field['required'])) required @endif></textarea>
                    @elseif($type === 'select' && $field['name'] === 'status')
                        <select class="form-control" data-field="{{ $field['name'] }}" @if(!empty($field['required'])) required @endif>
                            @foreach($statusOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @elseif($type === 'select')
                        <select class="form-control" data-field="{{ $field['name'] }}" @if(!empty($field['required'])) required @endif>
                            @foreach($fieldOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    @elseif($type === 'number')
                        <input class="form-control" data-field="{{ $field['name'] }}" type="number" step="0.0001" @if(!empty($field['required'])) required @endif>
                    @elseif($type === 'integer')
                        <input class="form-control" data-field="{{ $field['name'] }}" type="number" step="1" @if(!empty($field['required'])) required @endif>
                    @elseif($type === 'datetime')
                        <input class="form-control" data-field="{{ $field['name'] }}" type="datetime-local" @if(!empty($field['required'])) required @endif>
                    @else
                        <input class="form-control" data-field="{{ $field['name'] }}" @if(!empty($field['required'])) required @endif>
                    @endif
                </div>
            @endforeach
        </main>
        <footer>
            <button class="btn btn-default" type="button" data-ops-close>取消</button>
            <button class="btn btn-primary" type="button" data-ops-save>保存</button>
        </footer>
    </div>
</div>
@endif

<script>
(function(){
    var modal = document.querySelector('[data-ops-modal]');
    var newButton = document.querySelector('[data-ops-new]');
    var saveButton = document.querySelector('[data-ops-save]');
    var saveUrl = @json($recordsUrl);
    var token = @json(csrf_token());
    var schemaFields = @json($fields);

    function field(name){
        return modal ? modal.querySelector('[data-field="' + name + '"]') : null;
    }

    function parseRow(row){
        try {
            return JSON.parse(row.getAttribute('data-record') || '{}');
        } catch (error) {
            return {};
        }
    }

    function datetimeForInput(value){
        value = (value || '').toString().trim();
        if (!value) return '';
        return value.replace(' ', 'T').slice(0, 16);
    }

    function datetimeForSubmit(value){
        value = (value || '').toString().trim();
        if (!value) return '';
        value = value.replace('T', ' ');
        return value.length === 16 ? value + ':00' : value;
    }

    function openModal(row){
        if (!modal) return;
        var record = row ? parseRow(row) : {};
        modal.classList.add('is-open');
        field('id').value = record.id || '';
        schemaFields.forEach(function(item){
            var input = field(item.name);
            if (!input) return;
            var value = record[item.name] == null ? '' : record[item.name];
            if ((item.type || 'text') === 'datetime') {
                value = datetimeForInput(value);
            }
            input.value = value || item.default || (item.name === 'status' ? 'active' : '');
        });
        document.querySelector('[data-ops-modal-title]').textContent = row ? @json($editLabel).' #' + record.id : @json($actionLabel);
    }

    function closeModal(){
        if (modal) modal.classList.remove('is-open');
    }

    function request(url, method, data){
        data = data || {};
        data._token = token;
        if (method !== 'POST') {
            data._method = method;
        }

        var body = new URLSearchParams();
        Object.keys(data).forEach(function(key){
            body.append(key, data[key] == null ? '' : data[key]);
        });

        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': token
            },
            body: body.toString()
        }).then(function(response){
            return response.json().catch(function(){
                return {status: false, message: '请求失败，请稍后重试'};
            }).then(function(payload){
                if(!response.ok || !payload.status) throw payload;
                return payload;
            });
        });
    }

    if (newButton) {
        newButton.addEventListener('click', function(){ openModal(null); });
    }
    document.querySelectorAll('[data-ops-close]').forEach(function(button){
        button.addEventListener('click', closeModal);
    });
    document.querySelectorAll('[data-ops-edit]').forEach(function(button){
        button.addEventListener('click', function(){ openModal(button.closest('[data-row]')); });
    });
    document.querySelectorAll('[data-ops-delete]').forEach(function(button){
        button.addEventListener('click', function(){
            var row = button.closest('[data-row]');
            var record = parseRow(row);
            if(!confirm('确认删除记录 #' + record.id + '？')) return;
            request(saveUrl + '/' + record.id, 'DELETE', {}).then(function(){ location.reload(); }).catch(function(error){ alert(error.message || '删除失败'); });
        });
    });
    if (saveButton) {
        saveButton.addEventListener('click', function(){
            var id = field('id').value;
            var data = {};
            schemaFields.forEach(function(item){
                var input = field(item.name);
                if (!input) return;
                data[item.name] = (item.type || 'text') === 'datetime' ? datetimeForSubmit(input.value) : input.value;
            });
            request(saveUrl + (id ? '/' + id : ''), id ? 'PUT' : 'POST', data).then(function(){ location.reload(); }).catch(function(error){ alert(error.message || '保存失败'); });
        });
    }
})();
</script>
