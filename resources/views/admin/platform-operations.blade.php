@php
    $mode = $page['mode'];
    $canCreate = in_array('create', $page['actions'], true);
    $canDelete = in_array('delete', $page['actions'], true);
    $canBulkDelete = in_array('bulk-delete', $page['actions'], true);
    $canStatus = in_array('status', $page['actions'], true);
    $canImport = in_array('import', $page['actions'], true);
    $canExport = in_array('export', $page['actions'], true);
    $fieldLabels = [
        'keyword' => '关键字',
        'status' => '状态',
        'section' => '配置分组',
        'date_from' => '开始日期',
        'date_to' => '结束日期',
        'transaction_type' => '交易类型',
        'business_no' => '业务编号',
        'title' => '标题',
        'account_type' => '账号类型',
        'account_name' => '账户名称',
        'account_no' => '账户账号',
        'bank_id' => '银行 ID',
        'bank_name' => '银行名称',
        'bank_no' => '银行账号',
        'bank_owner' => '开户姓名',
        'bank_address' => '开户支行',
        'platform_name' => '游戏平台',
        'category' => '业务分类',
        'line_type' => '线路类型',
        'service_type' => '服务类型',
        'domain' => '域名地址',
        'endpoint' => '接口地址',
        'description' => '说明',
        'name' => '名称',
        'enname' => '英文名称',
        'cateid' => '帮助分类 ID',
        'content' => '内容',
        'encontent' => '英文内容',
        'stor' => '排序',
        'sort_order' => '排序',
        'bonus_ratio' => '赠送比例',
        'merchant_no' => '商户号',
        'merchant_key' => '商户密钥',
        'merchant_url' => '支付地址',
        'merchant_identifier' => '支付标识',
        'merchant_code' => '支付代码',
        'pay_type_id' => '支付类型 ID',
        'mch_id' => '商户号',
        'key' => '密钥',
        'wallet_address' => '钱包地址',
        'exchange_rate' => '兑换汇率',
        'min_price' => '最低金额',
        'max_price' => '最高金额',
        'download_name' => '下载名称',
        'download_url' => '下载地址',
        'type' => '结算类型',
        'realperson' => '真人比例',
        'electron' => '电子比例',
        'joker' => '棋牌比例',
        'sport' => '体育比例',
        'fish' => '捕鱼比例',
        'lottery' => '彩票比例',
        'e_sport' => '电竞比例',
        'member_fs' => '会员返水',
        'required_new_members' => '新增会员要求',
        'amount' => '金额',
        'balance_before' => '变更前余额',
        'balance_after' => '变更后余额',
        'currency' => '币种',
        'occurred_at' => '发生时间',
        'remark' => '备注',
        'info' => '说明',
        'state' => '状态',
        'site_state' => '网站状态',
        'app_state' => 'APP 状态',
        'game_count' => '游戏数量',
        'updated_at' => '更新时间',
        'site_name' => '站点名称',
        'site_title' => '站点标题',
        'site_keyword' => '站点关键字',
        'site_logo' => '站点 Logo',
        'safe_domain' => '安全域名',
        'customer_service_enabled' => '客服开关',
        'customer_service_url' => '客服地址',
        'online_service_url' => '在线客服地址',
        'gameorder' => '游戏排序',
        'service_url' => '服务地址',
        'daily_withdraw_times' => '每日提现次数',
        'min_withdraw_money' => '最低提现金额',
        'max_withdraw_money' => '最高提现金额',
        'withdraw_cash_fee' => '现金提现手续费',
        'withdraw_fee' => '提现手续费',
        'withdraw_fee_usdt_erc' => 'ERC20 提现手续费',
        'withdraw_fee_usdt_trc' => 'TRC20 提现手续费',
        'withdraw_begin_time' => '提现开始时间',
        'withdraw_end_time' => '提现结束时间',
        'withdraw_apply_audio' => '提现申请提示音',
        'agent_apply_audio' => '代理申请提示音',
        'agent_pc_uri' => '代理 PC 路径',
        'agent_uri_pre' => '代理路径前缀',
        'agent_url' => '代理地址',
        'agent_wap_uri' => '代理 WAP 路径',
        'agentday' => '代理结算日',
        'sms_provider' => '短信服务商',
        'sms_api_url' => '短信接口地址',
        'sms_sender' => '短信签名',
        'sms_daily_limit' => '每日发送上限',
        'sms_enabled' => '短信开关',
    ];
    $fieldLabel = function ($field) use ($fieldLabels) {
        return $fieldLabels[$field] ?? str_replace('_', ' ', $field);
    };
    $textareaFields = ['content', 'encontent', 'description', 'remark', 'info'];
    $numberFields = [
        'bank_id', 'cateid', 'stor', 'sort_order', 'type', 'required_new_members',
        'amount', 'balance_before', 'balance_after', 'bonus_ratio', 'exchange_rate',
        'min_price', 'max_price', 'realperson', 'electron', 'joker', 'sport',
        'fish', 'lottery', 'e_sport', 'member_fs',
    ];
    $editorFields = $mode === 'transactions'
        ? ($page['transaction_fields'] ?? [])
        : ($mode === 'legacy'
            ? ($page['legacy_fields'] ?? [])
            : ($page['record_fields'] ?? []));
@endphp

<div class="platform-operations" data-page-code="{{ $page['code'] }}">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $page['title'] }}</h3>
            <div class="card-tools">
                @if ($canImport)
                    <form
                        class="platform-operation-import-form d-inline-flex align-items-center mr-2"
                        enctype="multipart/form-data"
                    >
                        <input
                            type="file"
                            name="csv"
                            accept=".csv,text/csv"
                            class="form-control-file form-control-sm mr-1"
                            required
                        >
                        <button type="submit" class="btn btn-sm btn-success platform-operation-import">
                            <i class="fa fa-upload"></i> 导入 CSV
                        </button>
                    </form>
                @endif
                @if ($canExport)
                    <a
                        class="btn btn-sm btn-primary platform-operation-export"
                        href="{{ admin_url('tcg/platform-operations/'.$page['code'].'/export') }}?{{ http_build_query(request()->query()) }}"
                    >
                        <i class="fa fa-download"></i> 导出当前结果
                    </a>
                @endif
            </div>
        </div>

        <div class="card-body">
            <form method="GET" class="form-inline platform-operations-filter mb-3">
                @foreach ($page['filters'] as $filter)
                    <div class="form-group mr-2 mb-2">
                        <label class="mr-1">{{ $fieldLabel($filter) }}</label>
                        @if ($filter === 'status')
                            <select class="form-control form-control-sm" name="status">
                                <option value="">全部</option>
                                <option value="enabled" {{ request('status') === 'enabled' ? 'selected' : '' }}>启用</option>
                                <option value="disabled" {{ request('status') === 'disabled' ? 'selected' : '' }}>停用</option>
                                <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>待处理</option>
                                <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>已完成</option>
                            </select>
                        @elseif (in_array($filter, ['date_from', 'date_to'], true))
                            <input
                                type="date"
                                class="form-control form-control-sm"
                                name="{{ $filter }}"
                                value="{{ request($filter) }}"
                            >
                        @else
                            <input
                                type="text"
                                class="form-control form-control-sm"
                                name="{{ $filter }}"
                                value="{{ request($filter) }}"
                            >
                        @endif
                    </div>
                @endforeach
                <button type="submit" class="btn btn-sm btn-primary mb-2">
                    <i class="fa fa-search"></i> 筛选
                </button>
                <a href="{{ url()->current() }}" class="btn btn-sm btn-light ml-2 mb-2">重置</a>
            </form>

            @if ($mode === 'settings')
                <form class="platform-operation-settings-form">
                    <input type="hidden" name="section" value="general">
                    <div class="row">
                        @foreach (($page['setting_fields'] ?? []) as $field)
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>{{ $fieldLabel($field) }}</label>
                                    <input
                                        type="text"
                                        class="form-control"
                                        name="{{ $field }}"
                                        value="{{ $values[$field] ?? '' }}"
                                        autocomplete="off"
                                    >
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fa fa-save"></i> 保存配置
                    </button>
                </form>
            @else
                <div class="d-flex align-items-center mb-3">
                    @if ($canCreate)
                        <button type="button" class="btn btn-primary platform-operation-create">
                            <i class="fa fa-plus"></i> 新增
                        </button>
                    @endif
                    @if ($canStatus)
                        <select class="form-control ml-2 platform-operation-status" style="width: 130px;">
                            <option value="">批量状态</option>
                            <option value="enabled">启用</option>
                            <option value="disabled">停用</option>
                            <option value="pending">待处理</option>
                            <option value="completed">已完成</option>
                        </select>
                    @endif
                    @if ($canBulkDelete)
                        <button type="button" class="btn btn-danger ml-2 platform-operation-bulk-delete">
                            <i class="fa fa-trash"></i> 批量删除
                        </button>
                    @endif
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover platform-operations-table">
                        <thead>
                            <tr>
                                <th style="width: 42px;">
                                    <input type="checkbox" class="platform-operation-check-all">
                                </th>
                                @foreach ($page['columns'] as $column)
                                    <th>{{ $fieldLabel($column) }}</th>
                                @endforeach
                                <th style="width: 150px;">操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($records ?? []) as $row)
                                @php
                                    $rowData = (array) $row;
                                    $businessData = [];
                                    if (!empty($rowData['business_data'])) {
                                        $businessData = json_decode($rowData['business_data'], true) ?: [];
                                    }
                                    $merged = array_merge($businessData, $rowData);
                                @endphp
                                <tr data-record='@json($merged)'>
                                    <td>
                                        <input
                                            type="checkbox"
                                            class="platform-operation-check"
                                            value="{{ $merged['id'] ?? '' }}"
                                        >
                                    </td>
                                    @foreach ($page['columns'] as $column)
                                        <td>{{ $merged[$column] ?? '-' }}</td>
                                    @endforeach
                                    <td>
                                        @if (in_array('edit', $page['actions'], true))
                                            <button
                                                type="button"
                                                class="btn btn-xs btn-primary platform-operation-edit"
                                            >
                                                编辑
                                            </button>
                                        @endif
                                        @if ($canDelete)
                                            <button
                                                type="button"
                                                class="btn btn-xs btn-danger platform-operation-delete"
                                                data-id="{{ $merged['id'] ?? '' }}"
                                            >
                                                删除
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ count($page['columns']) + 2 }}" class="text-center text-muted">
                                        当前筛选条件下没有记录
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="platform-operations-pagination">
                    @if ($records && method_exists($records, 'links'))
                        {{ $records->links() }}
                    @endif
                </div>
            @endif
        </div>
    </div>

    <div class="modal fade platform-operation-editor" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form class="platform-operation-editor-form">
                    <div class="modal-header">
                        <h5 class="modal-title">编辑 {{ $page['title'] }}</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id">
                        @if ($mode === 'records')
                            <div class="form-group">
                                <label>标题</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="form-group">
                                <label>排序</label>
                                <input type="number" class="form-control" name="sort_order" value="0">
                            </div>
                        @elseif ($mode === 'transactions')
                            <div class="form-group">
                                <label>交易类型</label>
                                <input type="text" class="form-control" name="transaction_type" required>
                            </div>
                        @endif

                        @foreach ($editorFields as $field)
                            <div class="form-group">
                                <label>{{ $fieldLabel($field) }}</label>
                                @if (in_array($field, $textareaFields, true))
                                    <textarea class="form-control" name="{{ $field }}" rows="4"></textarea>
                                @elseif ($field === 'account_type')
                                    <select class="form-control" name="account_type">
                                        <option value="bank">银行卡</option>
                                        <option value="code">扫码支付</option>
                                        <option value="usdt">USDT</option>
                                    </select>
                                @else
                                    <input
                                        type="{{ in_array($field, $numberFields, true) ? 'number' : (in_array($field, ['domain', 'endpoint', 'merchant_url', 'download_url'], true) ? 'url' : 'text') }}"
                                        step="{{ in_array($field, $numberFields, true) ? '0.0001' : '' }}"
                                        class="form-control"
                                        name="{{ $field }}"
                                    >
                                @endif
                            </div>
                        @endforeach

                        <div class="form-group">
                            <label>状态</label>
                            <select class="form-control" name="status">
                                <option value="enabled">启用</option>
                                <option value="disabled">停用</option>
                                <option value="pending">待处理</option>
                                <option value="completed">已完成</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">取消</button>
                        <button type="submit" class="btn btn-primary">保存</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
Dcat.ready(function () {
    var root = $('.platform-operations[data-page-code="{{ $page['code'] }}"]');
    var code = root.data('page-code');
    var csrf = '{{ csrf_token() }}';
    var base = '{{ admin_url('tcg/platform-operations') }}/' + code;
    var editor = root.find('.platform-operation-editor');
    var form = root.find('.platform-operation-editor-form');

    function appendRequestField(data, name, value) {
        if ($.isArray(data)) {
            data.push({name: name, value: value});
            return;
        }
        data[name] = value;
    }

    function request(url, method, data, success) {
        data = data || {};
        appendRequestField(data, '_token', csrf);
        if (method !== 'POST') {
            appendRequestField(data, '_method', method);
        }
        $.ajax({
            url: url,
            method: 'POST',
            data: data
        })
            .done(function (response) {
                if (!response.status) {
                    Dcat.error(response.message || '操作失败');
                    return;
                }
                Dcat.success(response.message || '操作成功');
                if (success) {
                    success(response);
                }
            })
            .fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : '请求失败';
                Dcat.error(message);
            });
    }

    root.find('.platform-operation-import-form').on('submit', function (event) {
        event.preventDefault();
        var importForm = $(this);
        var data = new FormData(this);
        data.append('_token', csrf);
        importForm.find('button').prop('disabled', true);
        $.ajax({
            url: base + '/import',
            method: 'POST',
            data: data,
            processData: false,
            contentType: false
        })
            .done(function (response) {
                if (!response.status) {
                    Dcat.error(response.message || '导入失败');
                    return;
                }
                Dcat.success(response.message || '导入成功');
                window.location.reload();
            })
            .fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : '导入失败';
                Dcat.error(message);
            })
            .always(function () {
                importForm.find('button').prop('disabled', false);
            });
    });

    root.find('.platform-operation-settings-form').on('submit', function (event) {
        event.preventDefault();
        request(base + '/settings', 'POST', $(this).serializeArray(), function () {
            window.location.reload();
        });
    });

    root.find('.platform-operation-create').on('click', function () {
        form[0].reset();
        form.find('[name=id]').val('');
        editor.modal('show');
    });

    root.find('.platform-operation-edit').on('click', function () {
        form[0].reset();
        var data = $(this).closest('tr').data('record') || {};
        Object.keys(data).forEach(function (key) {
            form.find('[name="' + key + '"]').val(data[key]);
        });
        editor.modal('show');
    });

    form.on('submit', function (event) {
        event.preventDefault();
        var id = form.find('[name=id]').val();
        request(base + '/records' + (id ? '/' + id : ''), id ? 'PUT' : 'POST', form.serializeArray(), function () {
            window.location.reload();
        });
    });

    root.find('.platform-operation-delete').on('click', function () {
        var id = $(this).data('id');
        if (!id || !window.confirm('确认删除这条记录？')) {
            return;
        }
        request(base + '/records/' + id, 'DELETE', {}, function () {
            window.location.reload();
        });
    });

    root.find('.platform-operation-check-all').on('change', function () {
        root.find('.platform-operation-check').prop('checked', this.checked);
    });

    function selectedIds() {
        return root.find('.platform-operation-check:checked').map(function () {
            return $(this).val();
        }).get();
    }

    root.find('.platform-operation-status').on('change', function () {
        var status = $(this).val();
        var ids = selectedIds();
        if (!status || !ids.length) {
            return;
        }
        request(base + '/status', 'POST', {ids: ids, status: status}, function () {
            window.location.reload();
        });
    });

    root.find('.platform-operation-bulk-delete').on('click', function () {
        var ids = selectedIds();
        if (!ids.length || !window.confirm('确认删除选中的记录？')) {
            return;
        }
        request(base + '/bulk-delete', 'POST', {ids: ids}, function () {
            window.location.reload();
        });
    });
});
</script>
