<div
    class="platform-settings-page"
    id="platformSettingsPage"
    data-current-tab="{{ $tab }}"
    data-tab-labels="平台配置|下载链接|用户信息|前台显示样式|APP 打包|APP 下载设置|WXGAME"
>
    <style>
        .platform-settings-page { color:#303133; }
        .ps-panel { background:#fff; border:1px solid #e8eaed; margin-bottom:16px; box-shadow:0 1px 2px rgba(0,0,0,.03); }
        .ps-panel-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:15px 18px; border-bottom:1px solid #eceff3; }
        .ps-panel-head h4 { margin:0; font-size:16px; font-weight:600; }
        .ps-panel-head p { margin:4px 0 0; color:#909399; font-size:12px; }
        .ps-panel-body { padding:18px; }
        .ps-tabs { display:flex; flex-wrap:wrap; background:#fff; border:1px solid #e8eaed; margin-bottom:16px; }
        .ps-tabs a { position:relative; padding:13px 20px; color:#606266; border-right:1px solid #eef0f2; }
        .ps-tabs a:last-child { border-right:0; }
        .ps-tabs a:hover { background:#f7faff; color:#1769aa; }
        .ps-tabs a.active { color:#1769aa; background:#eef5ff; font-weight:600; }
        .ps-tabs a.active:after { content:""; position:absolute; left:16px; right:16px; bottom:0; height:2px; background:#1769aa; }
        .ps-form-row { display:grid; grid-template-columns:240px minmax(260px,660px) minmax(180px,1fr); gap:18px; align-items:start; padding:15px 0; border-bottom:1px solid #f0f1f3; }
        .ps-form-row:last-child { border-bottom:0; }
        .ps-label { padding-top:7px; font-weight:600; color:#4b5563; }
        .ps-control input[type="text"],
        .ps-control input[type="url"],
        .ps-control input[type="number"],
        .ps-control input[type="datetime-local"],
        .ps-control input[type="color"],
        .ps-control textarea,
        .ps-control select { max-width:660px; }
        .ps-control input[type="color"] { width:96px; height:38px; padding:3px; }
        .ps-control textarea { min-height:90px; resize:vertical; }
        .ps-switch { display:flex; align-items:center; gap:9px; min-height:34px; }
        .ps-switch input { width:17px; height:17px; margin:0; }
        .ps-help { color:#909399; font-size:12px; line-height:1.6; padding-top:7px; }
        .ps-file-current { margin-top:8px; padding:8px 10px; background:#f7f8fa; color:#606266; word-break:break-all; }
        .ps-file-current img { display:block; max-width:180px; max-height:90px; margin-top:8px; border:1px solid #e5e7eb; background:#fff; }
        .ps-actions { display:flex; align-items:center; gap:10px; padding-top:18px; }
        .ps-table { margin-bottom:0; }
        .ps-table thead th { background:#f7f8fa; color:#606266; font-weight:600; white-space:nowrap; }
        .ps-table td { vertical-align:middle!important; }
        .ps-empty { padding:34px!important; color:#909399; text-align:center; }
        .ps-muted { color:#909399; }
        .ps-badge { display:inline-block; min-width:58px; padding:3px 8px; border-radius:3px; text-align:center; font-size:12px; }
        .ps-badge-ok { color:#16794b; background:#e8f7ef; }
        .ps-badge-off { color:#8b9098; background:#f1f2f4; }
        .ps-badge-warn { color:#9a6500; background:#fff4d6; }
        .ps-badge-danger { color:#b42318; background:#feeceb; }
        .ps-actions-cell { white-space:nowrap; }
        .ps-build-bar { display:flex; flex-wrap:wrap; align-items:center; gap:14px; }
        .ps-build-note { color:#606266; line-height:1.7; }
        .ps-required { color:#c00000; margin-left:3px; }
        @media (max-width: 1100px) {
            .ps-form-row { grid-template-columns:200px minmax(220px,1fr); }
            .ps-help { grid-column:2; padding-top:0; }
        }
        @media (max-width: 768px) {
            .ps-tabs { display:block; }
            .ps-tabs a { display:block; border-right:0; border-bottom:1px solid #eef0f2; }
            .ps-form-row { grid-template-columns:1fr; gap:8px; }
            .ps-label,.ps-help { grid-column:1; padding-top:0; }
            .ps-panel-head { align-items:flex-start; flex-direction:column; }
        }
    </style>

    <nav class="ps-tabs" aria-label="平台基本配置标签">
        @foreach($tabs as $tabKey => $tabLabel)
            <a
                href="{{ admin_url('tcg/90400').'?tab='.$tabKey }}"
                class="{{ $tab === $tabKey ? 'active' : '' }}"
            >{{ $tabLabel }}</a>
        @endforeach
    </nav>

    <div class="ps-panel">
        <div class="ps-panel-head">
            <div>
                <h4>{{ $tabs[$tab] }}</h4>
                <p>当前页面只保存本标签的设置，不会覆盖其他标签或原后台配置。</p>
            </div>
        </div>
        <div class="ps-panel-body">
            <form
                class="js-platform-settings-form"
                method="post"
                enctype="multipart/form-data"
                action="{{ admin_url('tcg/platform-settings/'.$tab) }}"
            >
                @csrf
                @foreach($fields as $field)
                    @php
                        $key = $field['key'];
                        $type = $field['type'];
                        $value = isset($values[$key]) ? $values[$key] : '';
                        $displayValue = $type === 'datetime' && $value
                            ? str_replace(' ', 'T', mb_substr($value, 0, 16))
                            : $value;
                        $fileUrl = '';
                        if ($value && in_array($type, ['image', 'file'], true)) {
                            $fileUrl = preg_match('/^https?:\/\//i', $value)
                                ? $value
                                : asset('storage/'.ltrim($value, '/'));
                        }
                    @endphp
                    <div class="ps-form-row">
                        <label class="ps-label" for="{{ $key }}">{{ $field['label'] }}</label>
                        <div class="ps-control">
                            @if($type === 'switch')
                                <div class="ps-switch">
                                    <input type="hidden" name="{{ $key }}" value="0">
                                    <input
                                        id="{{ $key }}"
                                        type="checkbox"
                                        name="{{ $key }}"
                                        value="1"
                                        {{ (string) $value === '1' ? 'checked' : '' }}
                                    >
                                    <span>启用</span>
                                </div>
                            @elseif($type === 'textarea')
                                <textarea id="{{ $key }}" name="{{ $key }}" class="form-control">{{ $value }}</textarea>
                            @elseif($type === 'number')
                                <input id="{{ $key }}" type="number" name="{{ $key }}" class="form-control" value="{{ $value }}" min="0">
                            @elseif($type === 'datetime')
                                <input id="{{ $key }}" type="datetime-local" name="{{ $key }}" class="form-control" value="{{ $displayValue }}">
                            @elseif($type === 'color')
                                <input id="{{ $key }}" type="color" name="{{ $key }}" class="form-control" value="{{ $value ?: '#000000' }}">
                            @elseif(in_array($type, ['image', 'file'], true))
                                <input
                                    id="{{ $key }}"
                                    type="file"
                                    name="{{ $key }}"
                                    class="form-control"
                                    {{ $type === 'image' ? 'accept=image/*' : '' }}
                                >
                                @if($value)
                                    <div class="ps-file-current">
                                        当前文件：
                                        <a href="{{ $fileUrl }}" target="_blank" rel="noopener">{{ $value }}</a>
                                        @if($type === 'image')
                                            <img src="{{ $fileUrl }}" alt="{{ $field['label'] }}">
                                        @endif
                                    </div>
                                @endif
                            @else
                                <input
                                    id="{{ $key }}"
                                    type="{{ $type === 'url' ? 'url' : 'text' }}"
                                    name="{{ $key }}"
                                    class="form-control"
                                    value="{{ $value }}"
                                    maxlength="5000"
                                >
                            @endif
                        </div>
                        <div class="ps-help">
                            @if($type === 'switch')
                                开启后立即写入平台配置，关闭时保存为停用状态。
                            @elseif($type === 'image')
                                上传新图片后替换当前文件；不选择文件则保留原图。
                            @elseif($type === 'file')
                                上传新文件后替换当前文件；不选择文件则保留原文件。
                            @elseif($type === 'textarea')
                                支持多行内容，按对标后台的栏位语义保存。
                            @elseif($type === 'url')
                                请输入完整链接，建议以 https:// 开头。
                            @else
                                修改后点击本页底部“保存当前标签”生效。
                            @endif
                        </div>
                    </div>
                @endforeach
                <div class="ps-actions">
                    <button type="submit" class="btn btn-primary js-platform-settings-save">
                        <i class="fa fa-save"></i> 保存当前标签
                    </button>
                    <span class="ps-muted">只保存“{{ $tabs[$tab] }}”中的字段</span>
                </div>
            </form>
        </div>
    </div>

    @if($tab === 'platform')
        <div class="ps-panel">
            <div class="ps-panel-head">
                <div>
                    <h4>客服链接管理</h4>
                    <p>按排序和最低用户等级控制前台可见的客服入口。</p>
                </div>
                <button type="button" class="btn btn-primary btn-sm js-customer-service-create">
                    <i class="fa fa-plus"></i> 新增客服链接
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover ps-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>客服类型</th>
                        <th>显示名称</th>
                        <th>客服链接</th>
                        <th>排序</th>
                        <th>最低等级</th>
                        <th>状态</th>
                        <th>更新时间</th>
                        <th>操作</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($customerServices as $service)
                        <tr>
                            <td>{{ $service->id }}</td>
                            <td>{{ $service->service_type }}</td>
                            <td><strong>{{ $service->display_name }}</strong></td>
                            <td><a href="{{ $service->service_url }}" target="_blank" rel="noopener">{{ $service->service_url }}</a></td>
                            <td>{{ $service->position }}</td>
                            <td>{{ $service->min_player_level }}</td>
                            <td>
                                <span class="ps-badge {{ $service->status ? 'ps-badge-ok' : 'ps-badge-off' }}">
                                    {{ $service->status ? '启用' : '停用' }}
                                </span>
                            </td>
                            <td>{{ $service->updated_at ?: '-' }}</td>
                            <td class="ps-actions-cell">
                                <button
                                    type="button"
                                    class="btn btn-xs btn-primary js-customer-service-edit"
                                    data-id="{{ $service->id }}"
                                    data-type="{{ e($service->service_type) }}"
                                    data-name="{{ e($service->display_name) }}"
                                    data-url="{{ e($service->service_url) }}"
                                    data-position="{{ $service->position }}"
                                    data-level="{{ $service->min_player_level }}"
                                    data-status="{{ $service->status ? 1 : 0 }}"
                                ><i class="fa fa-edit"></i> 编辑</button>
                                <button
                                    type="button"
                                    class="btn btn-xs btn-danger js-customer-service-delete"
                                    data-id="{{ $service->id }}"
                                    data-name="{{ e($service->display_name) }}"
                                ><i class="fa fa-trash"></i> 删除</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="ps-empty">暂无客服链接，请点击右上角新增。</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="modal fade" id="customerServiceModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">客服链接</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="customerServiceId">
                        <div class="form-group">
                            <label for="customerServiceType">客服类型<span class="ps-required">*</span></label>
                            <select id="customerServiceType" class="form-control">
                                <option value="livechat">Livechat</option>
                                <option value="telegram">Telegram</option>
                                <option value="whatsapp">WhatsApp</option>
                                <option value="line">LINE</option>
                                <option value="facebook">Facebook</option>
                                <option value="custom">自定义</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="customerServiceName">显示名称<span class="ps-required">*</span></label>
                            <input id="customerServiceName" class="form-control" maxlength="100">
                        </div>
                        <div class="form-group">
                            <label for="customerServiceUrl">客服链接<span class="ps-required">*</span></label>
                            <input id="customerServiceUrl" type="url" class="form-control" maxlength="1000" placeholder="https://">
                        </div>
                        <div class="form-group">
                            <label for="customerServicePosition">排序</label>
                            <input id="customerServicePosition" type="number" class="form-control" min="0" max="100000" value="0">
                        </div>
                        <div class="form-group">
                            <label for="customerServiceLevel">最低用户等级</label>
                            <input id="customerServiceLevel" type="number" class="form-control" min="0" max="100000" value="0">
                        </div>
                        <div class="form-group">
                            <label for="customerServiceStatus">状态</label>
                            <select id="customerServiceStatus" class="form-control">
                                <option value="1">启用</option>
                                <option value="0">停用</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary js-customer-service-save">保存</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($tab === 'app-package')
        <div class="ps-panel">
            <div class="ps-panel-head">
                <div>
                    <h4>APP 打包请求</h4>
                    <p>使用本标签保存的桌面名称、包名后缀和固定域名生成打包任务。</p>
                </div>
            </div>
            <div class="ps-panel-body">
                <div class="ps-build-bar">
                    <label class="checkbox-inline">
                        <input type="checkbox" id="syncDownloadLinks" value="1"> 打包完成后同步 APP 下载链接
                    </label>
                    <button type="button" class="btn btn-success js-app-build-request">
                        <i class="fa fa-cubes"></i> 提交打包请求
                    </button>
                </div>
                <div class="ps-build-note">
                    请求会进入真实打包队列表，并保留 7 天有效期。当前服务器尚未接入 Android/iOS 编译工作进程，
                    因此任务会保持“等待处理”，不会伪造下载地址或完成状态。
                </div>
            </div>
        </div>

        <div class="ps-panel">
            <div class="ps-panel-head">
                <div>
                    <h4>打包历史</h4>
                    <p>显示最近 50 条打包请求及真实处理状态。</p>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover ps-table">
                    <thead>
                    <tr>
                        <th>任务号</th>
                        <th>包名后缀</th>
                        <th>固定域名</th>
                        <th>状态</th>
                        <th>申请人</th>
                        <th>申请时间</th>
                        <th>有效期</th>
                        <th>下载地址</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($appBuilds as $build)
                        @php
                            $statusClass = $build->status === 'completed'
                                ? 'ps-badge-ok'
                                : ($build->status === 'failed' ? 'ps-badge-danger' : 'ps-badge-warn');
                            $statusLabel = [
                                'pending' => '等待处理',
                                'processing' => '打包中',
                                'completed' => '已完成',
                                'failed' => '失败',
                                'expired' => '已过期',
                            ][$build->status] ?? $build->status;
                        @endphp
                        <tr>
                            <td><strong>{{ $build->build_no }}</strong></td>
                            <td>{{ $build->package_name }}</td>
                            <td>{{ $build->domain ?: '-' }}</td>
                            <td><span class="ps-badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                            <td>{{ $build->requested_by_name ?: '-' }}</td>
                            <td>{{ $build->requested_at ?: '-' }}</td>
                            <td>{{ $build->expires_at ?: '-' }}</td>
                            <td>
                                @if($build->android_url)
                                    <a href="{{ $build->android_url }}" target="_blank" rel="noopener">Android</a>
                                @endif
                                @if($build->ios_url)
                                    {{ $build->android_url ? ' / ' : '' }}
                                    <a href="{{ $build->ios_url }}" target="_blank" rel="noopener">iOS</a>
                                @endif
                                @if(!$build->android_url && !$build->ios_url)
                                    <span class="ps-muted">尚未生成</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="ps-empty">暂无打包请求。</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<script>
(function ($) {
    var page = $('#platformSettingsPage');
    if (!page.length) return;

    var token = '{{ csrf_token() }}';

    function notify(type, message) {
        if (window.Dcat && typeof window.Dcat[type] === 'function') {
            window.Dcat[type](message);
            return;
        }
        window.alert(message);
    }

    function errorMessage(xhr) {
        var response = xhr.responseJSON || {};
        if (response.errors) {
            var key = Object.keys(response.errors)[0];
            if (key && response.errors[key] && response.errors[key][0]) {
                return response.errors[key][0];
            }
        }
        return response.message || '操作失败，请检查填写内容后重试。';
    }

    function ajax(options) {
        options = $.extend(true, {
            headers: {'X-CSRF-TOKEN': token},
            dataType: 'json'
        }, options);

        return $.ajax(options).fail(function (xhr) {
            notify('error', errorMessage(xhr));
        });
    }

    function reloadAfter(message) {
        notify('success', message);
        window.setTimeout(function () {
            window.location.reload();
        }, 600);
    }

    page.on('submit', '.js-platform-settings-form', function (event) {
        event.preventDefault();
        var form = $(this);
        var button = form.find('.js-platform-settings-save').prop('disabled', true);
        var data = new FormData(this);

        ajax({
            url: form.attr('action'),
            method: 'POST',
            data: data,
            processData: false,
            contentType: false
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    function openCustomerService(button) {
        var editing = !!button;
        $('#customerServiceId').val(editing ? button.data('id') : '');
        $('#customerServiceType').val(editing ? String(button.data('type')) : 'livechat');
        $('#customerServiceName').val(editing ? button.data('name') : '');
        $('#customerServiceUrl').val(editing ? button.data('url') : '');
        $('#customerServicePosition').val(editing ? button.data('position') : 0);
        $('#customerServiceLevel').val(editing ? button.data('level') : 0);
        $('#customerServiceStatus').val(editing ? String(button.data('status')) : '1');
        $('#customerServiceModal').modal('show');
    }

    page.on('click', '.js-customer-service-create', function () {
        openCustomerService(null);
    });

    page.on('click', '.js-customer-service-edit', function () {
        openCustomerService($(this));
    });

    page.on('click', '.js-customer-service-save', function () {
        var button = $(this).prop('disabled', true);
        var id = $('#customerServiceId').val();

        ajax({
            url: '{{ admin_url('tcg/platform-customer-services') }}' + (id ? '/' + id : ''),
            method: id ? 'PUT' : 'POST',
            data: {
                service_type: $('#customerServiceType').val(),
                display_name: $('#customerServiceName').val(),
                service_url: $('#customerServiceUrl').val(),
                position: $('#customerServicePosition').val(),
                min_player_level: $('#customerServiceLevel').val(),
                status: $('#customerServiceStatus').val()
            }
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    page.on('click', '.js-customer-service-delete', function () {
        var button = $(this);
        if (!window.confirm('确认删除客服链接“' + button.data('name') + '”吗？')) return;

        button.prop('disabled', true);
        ajax({
            url: '{{ admin_url('tcg/platform-customer-services') }}/' + button.data('id'),
            method: 'DELETE'
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        }).always(function () {
            button.prop('disabled', false);
        });
    });

    page.on('click', '.js-app-build-request', function () {
        var button = $(this);
        if (!window.confirm('确认使用当前 APP 配置提交新的打包请求吗？')) return;

        button.prop('disabled', true);
        ajax({
            url: '{{ admin_url('tcg/platform-app-builds') }}',
            method: 'POST',
            data: {
                sync_download_links: $('#syncDownloadLinks').is(':checked') ? 1 : 0
            }
        }).done(function (response) {
            if (response.status) {
                reloadAfter(response.message + '：' + response.data.build_no);
            }
        }).always(function () {
            button.prop('disabled', false);
        });
    });
})(jQuery);
</script>
