<div class="sus-page" id="systemUserSettingsPage" data-module="{{ $module }}">
    <style>
        .sus-page { color:#303133; }
        .sus-panel { background:#fff; border:1px solid #e8eaed; margin-bottom:16px; box-shadow:0 1px 2px rgba(0,0,0,.03); }
        .sus-panel-head { display:flex; align-items:center; justify-content:space-between; padding:15px 18px; border-bottom:1px solid #eceff3; }
        .sus-panel-head h4 { margin:0; font-size:16px; font-weight:600; }
        .sus-panel-body { padding:16px 18px; }
        .sus-toolbar { display:flex; align-items:flex-end; flex-wrap:wrap; gap:12px; }
        .sus-field { min-width:150px; }
        .sus-field label { display:block; color:#606266; font-size:12px; margin-bottom:5px; }
        .sus-field input,.sus-field select { height:34px; }
        .sus-table { margin-bottom:0; }
        .sus-table thead th { background:#f7f8fa; color:#606266; font-weight:600; white-space:nowrap; }
        .sus-table td { vertical-align:middle!important; }
        .sus-muted { color:#909399; }
        .sus-badge { display:inline-block; min-width:52px; padding:3px 8px; border-radius:3px; text-align:center; font-size:12px; }
        .sus-badge-ok { color:#16794b; background:#e8f7ef; }
        .sus-badge-off { color:#8b9098; background:#f1f2f4; }
        .sus-badge-warn { color:#9a6500; background:#fff4d6; }
        .sus-badge-danger { color:#b42318; background:#feeceb; }
        .sus-actions { white-space:nowrap; }
        .sus-empty { padding:34px!important; color:#909399; text-align:center; }
        .sus-role-grid { display:grid; grid-template-columns:260px minmax(0,1fr); gap:16px; }
        .sus-role-list { border:1px solid #e8eaed; }
        .sus-role-item { display:block; padding:12px 14px; border-bottom:1px solid #eef0f2; color:#303133; }
        .sus-role-item:last-child { border-bottom:0; }
        .sus-role-item.active { background:#eef5ff; color:#1769aa; font-weight:600; }
        .sus-role-count { float:right; color:#909399; }
        .sus-permission-list { border:1px solid #e8eaed; max-height:620px; overflow:auto; }
        .sus-permission-row { display:grid; grid-template-columns:38px 220px 180px 100px minmax(220px,1fr); gap:8px; align-items:center; padding:9px 12px; border-bottom:1px solid #f0f1f3; }
        .sus-permission-row:nth-child(even) { background:#fafbfc; }
        .sus-permission-row.is-child .sus-permission-name { padding-left:20px; }
        .sus-result { max-width:420px; white-space:pre-wrap; word-break:break-word; }
        .sus-tabs { display:flex; border-bottom:1px solid #dfe3e8; margin-bottom:16px; }
        .sus-tabs a { padding:10px 18px; color:#606266; border-bottom:2px solid transparent; }
        .sus-tabs a.active { color:#1769aa; border-bottom-color:#1769aa; font-weight:600; }
        .sus-modal-note { color:#909399; font-size:12px; margin-top:5px; }
        @media (max-width: 992px) {
            .sus-role-grid { grid-template-columns:1fr; }
            .sus-permission-row { grid-template-columns:36px 1fr; }
            .sus-permission-row span:nth-child(n+3) { grid-column:2; }
        }
    </style>

    @if($module === 'users')
        <div class="sus-panel">
            <div class="sus-panel-head">
                <h4>系统用户查询</h4>
                <span class="sus-muted">管理员账号、品牌订阅、验证和在线状态</span>
            </div>
            <div class="sus-panel-body">
                <form method="get" class="sus-toolbar">
                    <div class="sus-field">
                        <label>账号 / 名称 / 品牌</label>
                        <input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}">
                    </div>
                    <div class="sus-field">
                        <label>启用状态</label>
                        <select name="status" class="form-control">
                            <option value="">全部</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>启用</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>停用</option>
                        </select>
                    </div>
                    <button class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                    <a class="btn btn-default" href="{{ request()->url() }}">重置</a>
                </form>
            </div>
        </div>
        <div class="sus-panel">
            <div class="table-responsive">
                <table class="table table-hover sus-table">
                    <thead><tr>
                        <th>ID</th><th>账号</th><th>显示名称</th><th>品牌</th><th>订阅品牌</th>
                        <th>角色</th><th>Google 验证</th><th>在线状态</th><th>启用状态</th>
                        <th>最后 IP / 时间</th><th>操作</th>
                    </tr></thead>
                    <tbody>
                    @forelse($users as $user)
                        @php
                            $isOnline = $user->last_seen_at && strtotime($user->last_seen_at) >= time() - 600;
                        @endphp
                        <tr>
                            <td>{{ $user->id }}</td>
                            <td><strong>{{ $user->username }}</strong></td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->brand ?: '默认品牌' }}</td>
                            <td>{{ $user->subscribed_brands_list ? implode('、', $user->subscribed_brands_list) : '-' }}</td>
                            <td>{{ $user->roles ?: '-' }}</td>
                            <td><span class="sus-badge {{ $user->google_auth_enabled ? 'sus-badge-ok' : 'sus-badge-off' }}">{{ $user->google_auth_enabled ? '已开启' : '未开启' }}</span></td>
                            <td><span class="sus-badge {{ $isOnline ? 'sus-badge-ok' : 'sus-badge-off' }}">{{ $isOnline ? '在线' : '离线' }}</span></td>
                            <td><span class="sus-badge {{ $user->status ? 'sus-badge-ok' : 'sus-badge-danger' }}">{{ $user->status ? '启用' : '停用' }}</span></td>
                            <td>
                                <div>{{ $user->last_login_ip ?: '-' }}</div>
                                <small class="sus-muted">{{ $user->last_seen_at ?: '-' }}</small>
                            </td>
                            <td class="sus-actions">
                                <button type="button" class="btn btn-xs btn-primary js-user-edit"
                                    data-id="{{ $user->id }}"
                                    data-username="{{ e($user->username) }}"
                                    data-name="{{ e($user->name) }}"
                                    data-brand="{{ e($user->brand) }}"
                                    data-brands="{{ e(implode(', ', $user->subscribed_brands_list)) }}"
                                    data-google="{{ $user->google_auth_enabled ? 1 : 0 }}"
                                    data-status="{{ $user->status ? 1 : 0 }}">
                                    <i class="fa fa-edit"></i> 编辑
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="sus-empty">暂无系统用户</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div class="sus-panel-body">{{ $users->links() }}</div>
        </div>

        <div class="modal fade" id="userEditorModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                        <h4 class="modal-title">编辑系统用户</h4>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="userEditorId">
                        <div class="form-group"><label>账号</label><input id="userEditorUsername" class="form-control" disabled></div>
                        <div class="form-group"><label>显示名称</label><input id="userEditorName" class="form-control" maxlength="191"></div>
                        <div class="form-group"><label>当前品牌</label><input id="userEditorBrand" class="form-control" maxlength="100"></div>
                        <div class="form-group">
                            <label>订阅品牌</label>
                            <textarea id="userEditorBrands" class="form-control" rows="3"></textarea>
                            <div class="sus-modal-note">使用逗号或换行分隔，重复项会自动合并。</div>
                        </div>
                        <div class="form-group"><label>Google 登录验证</label>
                            <select id="userEditorGoogle" class="form-control"><option value="1">开启</option><option value="0">关闭</option></select>
                        </div>
                        <div class="form-group"><label>启用状态</label>
                            <select id="userEditorStatus" class="form-control"><option value="1">启用</option><option value="0">停用</option></select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">取消</button>
                        <button type="button" class="btn btn-primary js-user-save">保存</button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if($module === 'roles')
        <div class="sus-panel">
            <div class="sus-panel-head">
                <h4>角色与权限解析</h4>
                <span class="sus-muted">搜索真实权限项并保存角色授权</span>
            </div>
            <div class="sus-panel-body">
                <form method="get" class="sus-toolbar">
                    <div class="sus-field"><label>角色</label>
                        <select name="role_id" class="form-control" onchange="this.form.submit()">
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ $selectedRoleId === (int) $role->id ? 'selected' : '' }}>{{ $role->name }} ({{ $role->permission_count }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="sus-field"><label>权限名称 / 标识 / 路径</label><input type="text" name="permission_keyword" class="form-control" value="{{ request('permission_keyword') }}"></div>
                    <button class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                    <a class="btn btn-default" href="{{ request()->url() }}?role_id={{ $selectedRoleId }}">重置</a>
                </form>
            </div>
        </div>
        <div class="sus-role-grid">
            <div class="sus-panel">
                <div class="sus-panel-head"><h4>角色列表</h4></div>
                <div class="sus-role-list">
                    @forelse($roles as $role)
                        <a class="sus-role-item {{ $selectedRoleId === (int) $role->id ? 'active' : '' }}" href="{{ request()->url() }}?role_id={{ $role->id }}">
                            {{ $role->name }}<span class="sus-role-count">{{ $role->permission_count }}</span>
                            <small class="sus-muted"><br>{{ $role->slug }}</small>
                        </a>
                    @empty
                        <div class="sus-empty">暂无角色</div>
                    @endforelse
                </div>
            </div>
            <div class="sus-panel">
                <div class="sus-panel-head">
                    <h4>权限项</h4>
                    <div>
                        <button type="button" class="btn btn-xs btn-default js-permission-all">全选</button>
                        <button type="button" class="btn btn-xs btn-default js-permission-none">清空</button>
                        <button type="button" class="btn btn-sm btn-primary js-permission-save" {{ $selectedRoleId ? '' : 'disabled' }}><i class="fa fa-save"></i> 保存权限</button>
                    </div>
                </div>
                <div class="sus-permission-list" data-role-id="{{ $selectedRoleId }}">
                    @forelse($permissions as $permission)
                        <label class="sus-permission-row {{ $permission->depth ? 'is-child' : '' }}">
                            <span><input type="checkbox" class="js-permission-checkbox" value="{{ $permission->id }}" {{ in_array((int) $permission->id, $selectedPermissionIds, true) ? 'checked' : '' }}></span>
                            <span class="sus-permission-name"><strong>{{ $permission->name }}</strong></span>
                            <span>{{ $permission->slug }}</span>
                            <span>{{ $permission->http_method ?: 'ALL' }}</span>
                            <span class="sus-muted">{{ $permission->http_path ?: '-' }}</span>
                        </label>
                    @empty
                        <div class="sus-empty">没有匹配的权限项</div>
                    @endforelse
                </div>
            </div>
        </div>
    @endif

    @if($module === 'ip-whitelist')
        <div class="sus-panel">
            <div class="sus-panel-head">
                <h4>IP 白名单查询</h4>
                <button type="button" class="btn btn-primary btn-sm js-ip-create"><i class="fa fa-plus"></i> 新增</button>
            </div>
            <div class="sus-panel-body">
                <form method="get" class="sus-toolbar">
                    <div class="sus-field"><label>IP / 域名</label><input type="text" name="keyword" class="form-control" value="{{ request('keyword') }}"></div>
                    <div class="sus-field"><label>状态</label><select name="status" class="form-control"><option value="">全部</option><option value="1" {{ request('status') === '1' ? 'selected' : '' }}>启用</option><option value="0" {{ request('status') === '0' ? 'selected' : '' }}>停用</option></select></div>
                    <div class="sus-field"><label>重要标记</label><select name="important" class="form-control"><option value="">全部</option><option value="1" {{ request('important') === '1' ? 'selected' : '' }}>重要</option><option value="0" {{ request('important') === '0' ? 'selected' : '' }}>普通</option></select></div>
                    <button class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                    <a class="btn btn-default" href="{{ request()->url() }}">重置</a>
                </form>
            </div>
        </div>
        <div class="sus-panel">
            <div class="table-responsive"><table class="table table-hover sus-table">
                <thead><tr><th>ID</th><th>IP</th><th>域名</th><th>配额</th><th>自动清理</th><th>重要</th><th>状态</th><th>最后登录</th><th>最后修改</th><th>操作</th></tr></thead>
                <tbody>
                @forelse($whitelists as $item)
                    <tr>
                        <td>{{ $item->id }}</td><td><strong>{{ $item->ip_address }}</strong></td><td>{{ $item->domain ?: '-' }}</td>
                        <td>{{ $item->quota }}</td><td>{{ $item->auto_cleanup_days ? $item->auto_cleanup_days.' 天' : '不自动清理' }}</td>
                        <td><span class="sus-badge {{ $item->is_important ? 'sus-badge-warn' : 'sus-badge-off' }}">{{ $item->is_important ? '重要' : '普通' }}</span></td>
                        <td><span class="sus-badge {{ $item->status ? 'sus-badge-ok' : 'sus-badge-off' }}">{{ $item->status ? '启用' : '停用' }}</span></td>
                        <td>{{ $item->last_login_at ?: '-' }}</td><td>{{ $item->last_modified_at ?: $item->updated_at }}</td>
                        <td class="sus-actions">
                            <button type="button" class="btn btn-xs btn-primary js-ip-edit"
                                data-id="{{ $item->id }}" data-ip="{{ e($item->ip_address) }}" data-domain="{{ e($item->domain) }}"
                                data-quota="{{ $item->quota }}" data-cleanup="{{ $item->auto_cleanup_days }}"
                                data-important="{{ $item->is_important ? 1 : 0 }}" data-status="{{ $item->status ? 1 : 0 }}"><i class="fa fa-edit"></i> 编辑</button>
                            <button type="button" class="btn btn-xs btn-danger js-ip-delete" data-id="{{ $item->id }}"><i class="fa fa-trash"></i> 删除</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="sus-empty">暂无 IP 白名单</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="sus-panel-body">{{ $whitelists->links() }}</div>
        </div>
        <div class="modal fade" id="ipEditorModal" tabindex="-1">
            <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">IP 白名单</h4></div>
                <div class="modal-body">
                    <input type="hidden" id="ipEditorId">
                    <div class="form-group"><label>IP 地址</label><input id="ipEditorAddress" class="form-control" placeholder="例如 203.0.113.10"></div>
                    <div class="form-group"><label>域名</label><input id="ipEditorDomain" class="form-control" placeholder="可留空，或填写 admin.example.com"></div>
                    <div class="form-group"><label>IP 配额</label><input id="ipEditorQuota" type="number" min="1" max="100000" class="form-control" value="1"></div>
                    <div class="form-group"><label>自动清理天数</label><input id="ipEditorCleanup" type="number" min="0" max="3650" class="form-control" value="0"><div class="sus-modal-note">0 表示不自动清理。</div></div>
                    <div class="form-group"><label>重要标记</label><select id="ipEditorImportant" class="form-control"><option value="0">普通</option><option value="1">重要</option></select></div>
                    <div class="form-group"><label>状态</label><select id="ipEditorStatus" class="form-control"><option value="1">启用</option><option value="0">停用</option></select></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">取消</button><button type="button" class="btn btn-primary js-ip-save">保存</button></div>
            </div></div>
        </div>
    @endif

    @if($module === 'tasks')
        <div class="sus-panel">
            <div class="sus-panel-head"><h4>新建安全任务</h4><span class="sus-muted">仅允许固定后台维护任务，不能执行任意命令</span></div>
            <div class="sus-panel-body">
                <div class="sus-toolbar">
                    <div class="sus-field"><label>任务类型</label><select id="taskType" class="form-control">@foreach($taskTypes as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                    <div class="sus-field" style="min-width:280px"><label>任务名称</label><input id="taskTitle" class="form-control" placeholder="可留空，自动使用任务类型名称"></div>
                    <button type="button" class="btn btn-primary js-task-run"><i class="fa fa-play"></i> 立即执行</button>
                </div>
            </div>
        </div>
        @include('admin.system-user-settings-task-table', ['history' => false])
    @endif

    @if($module === 'task-history')
        <div class="sus-panel">
            <div class="sus-panel-head"><h4>历史任务查询</h4><a class="btn btn-success btn-sm" href="{{ admin_url('tcg/task-history/export').'?'.http_build_query(request()->query()) }}"><i class="fa fa-download"></i> 导出</a></div>
            <div class="sus-panel-body">
                <form method="get" class="sus-toolbar">
                    <div class="sus-field"><label>任务号</label><input name="task_no" class="form-control" value="{{ request('task_no') }}"></div>
                    <div class="sus-field"><label>执行人</label><input name="requested_by" class="form-control" value="{{ request('requested_by') }}"></div>
                    <div class="sus-field"><label>状态</label><select name="status" class="form-control"><option value="">全部</option><option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>成功</option><option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>失败</option></select></div>
                    <div class="sus-field"><label>开始日期</label><input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}"></div>
                    <div class="sus-field"><label>结束日期</label><input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}"></div>
                    <button class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                    <a class="btn btn-default" href="{{ request()->url() }}">重置</a>
                </form>
            </div>
        </div>
        @include('admin.system-user-settings-task-table', ['history' => true])
    @endif

    @if($module === 'logs')
        <div class="sus-panel">
            <div class="sus-panel-head"><h4>系统用户日志</h4><a class="btn btn-success btn-sm" href="{{ admin_url('tcg/system-logs/export').'?'.http_build_query(request()->query()) }}"><i class="fa fa-download"></i> 导出</a></div>
            <div class="sus-panel-body">
                <div class="sus-tabs">
                    <a class="{{ $tab === 'admin' ? 'active' : '' }}" href="{{ request()->url() }}?tab=admin">品牌 / 后台日志</a>
                    <a class="{{ $tab === 'user' ? 'active' : '' }}" href="{{ request()->url() }}?tab=user">用户日志</a>
                </div>
                <form method="get" class="sus-toolbar">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <div class="sus-field"><label>{{ $tab === 'admin' ? '管理员 / 功能 / 内容' : '用户名 / 内容' }}</label><input name="keyword" class="form-control" value="{{ request('keyword') }}"></div>
                    <div class="sus-field"><label>动作类型</label><input name="action" class="form-control" value="{{ request('action') }}"></div>
                    <div class="sus-field"><label>开始日期</label><input type="date" name="start_date" class="form-control" value="{{ request('start_date') }}"></div>
                    <div class="sus-field"><label>结束日期</label><input type="date" name="end_date" class="form-control" value="{{ request('end_date') }}"></div>
                    <button class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                    <a class="btn btn-default" href="{{ request()->url() }}?tab={{ $tab }}">重置</a>
                </form>
            </div>
        </div>
        <div class="sus-panel">
            <div class="table-responsive"><table class="table table-hover sus-table">
                <thead><tr><th>ID</th><th>{{ $tab === 'admin' ? '品牌 / 管理员' : '用户名' }}</th><th>动作类型</th><th>功能</th><th>日志内容</th><th>IP</th><th>时间</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    <tr><td>{{ $log->id }}</td><td>{{ $log->username }}</td><td>{{ $log->type }}</td><td>{{ $log->module }}</td><td>{{ $log->content }}</td><td>{{ $log->ip_address ?: '-' }}</td><td>{{ $log->created_at }}</td></tr>
                @empty
                    <tr><td colspan="7" class="sus-empty">暂无日志</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="sus-panel-body">{{ $logs->links() }}</div>
        </div>
    @endif
</div>

<script>
(function ($) {
    var page = $('#systemUserSettingsPage');
    if (!page.length) return;
    var token = '{{ csrf_token() }}';

    function notify(type, message) {
        if (window.Dcat && typeof window.Dcat[type] === 'function') {
            window.Dcat[type](message);
        } else {
            window.alert(message);
        }
    }
    function ajax(options) {
        options = $.extend(true, {
            headers: {'X-CSRF-TOKEN': token},
            dataType: 'json'
        }, options);
        return $.ajax(options).fail(function (xhr) {
            var response = xhr.responseJSON || {};
            var message = response.message || '操作失败';
            if (response.errors) {
                var first = Object.keys(response.errors)[0];
                if (first) message = response.errors[first][0];
            }
            notify('error', message);
        });
    }
    function reloadAfter(message) {
        notify('success', message);
        window.setTimeout(function () { window.location.reload(); }, 350);
    }

    page.on('click', '.js-user-edit', function () {
        var button = $(this);
        $('#userEditorId').val(button.data('id'));
        $('#userEditorUsername').val(button.data('username'));
        $('#userEditorName').val(button.data('name'));
        $('#userEditorBrand').val(button.data('brand'));
        $('#userEditorBrands').val(button.data('brands'));
        $('#userEditorGoogle').val(String(button.data('google')));
        $('#userEditorStatus').val(String(button.data('status')));
        $('#userEditorModal').modal('show');
    });
    page.on('click', '.js-user-save', function () {
        var id = $('#userEditorId').val();
        ajax({
            url: '{{ admin_url('tcg/system-users') }}/' + id,
            method: 'PUT',
            data: {
                name: $('#userEditorName').val(),
                brand: $('#userEditorBrand').val(),
                subscribed_brands: $('#userEditorBrands').val(),
                google_auth_enabled: $('#userEditorGoogle').val(),
                status: $('#userEditorStatus').val()
            }
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        });
    });

    page.on('click', '.js-permission-all', function () { page.find('.js-permission-checkbox').prop('checked', true); });
    page.on('click', '.js-permission-none', function () { page.find('.js-permission-checkbox').prop('checked', false); });
    page.on('click', '.js-permission-save', function () {
        var list = page.find('.sus-permission-list');
        var ids = [];
        list.find('.js-permission-checkbox:checked').each(function () { ids.push($(this).val()); });
        ajax({
            url: '{{ admin_url('tcg/system-roles') }}/' + list.data('role-id') + '/permissions',
            method: 'POST',
            data: {permission_ids: ids}
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        });
    });

    function openIpEditor(button) {
        var editing = !!button;
        $('#ipEditorId').val(editing ? button.data('id') : '');
        $('#ipEditorAddress').val(editing ? button.data('ip') : '');
        $('#ipEditorDomain').val(editing ? button.data('domain') : '');
        $('#ipEditorQuota').val(editing ? button.data('quota') : 1);
        $('#ipEditorCleanup').val(editing ? button.data('cleanup') : 0);
        $('#ipEditorImportant').val(editing ? String(button.data('important')) : '0');
        $('#ipEditorStatus').val(editing ? String(button.data('status')) : '1');
        $('#ipEditorModal').modal('show');
    }
    page.on('click', '.js-ip-create', function () { openIpEditor(null); });
    page.on('click', '.js-ip-edit', function () { openIpEditor($(this)); });
    page.on('click', '.js-ip-save', function () {
        var id = $('#ipEditorId').val();
        ajax({
            url: '{{ admin_url('tcg/ip-whitelists') }}' + (id ? '/' + id : ''),
            method: id ? 'PUT' : 'POST',
            data: {
                ip_address: $('#ipEditorAddress').val(),
                domain: $('#ipEditorDomain').val(),
                quota: $('#ipEditorQuota').val(),
                auto_cleanup_days: $('#ipEditorCleanup').val(),
                is_important: $('#ipEditorImportant').val(),
                status: $('#ipEditorStatus').val()
            }
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        });
    });
    page.on('click', '.js-ip-delete', function () {
        if (!window.confirm('确认删除这条 IP 白名单吗？')) return;
        ajax({
            url: '{{ admin_url('tcg/ip-whitelists') }}/' + $(this).data('id'),
            method: 'DELETE'
        }).done(function (response) {
            if (response.status) reloadAfter(response.message);
        });
    });

    page.on('click', '.js-task-run', function () {
        var button = $(this).prop('disabled', true);
        ajax({
            url: '{{ admin_url('tcg/tasks/run') }}',
            method: 'POST',
            data: {task_type: $('#taskType').val(), title: $('#taskTitle').val()}
        }).done(function (response) {
            if (response.status) reloadAfter(response.message + '：' + response.data.task_no);
        }).always(function () { button.prop('disabled', false); });
    });
    page.on('click', '.js-task-retry', function () {
        if (!window.confirm('确认重新执行这项任务吗？')) return;
        ajax({
            url: '{{ admin_url('tcg/tasks') }}/' + $(this).data('id') + '/retry',
            method: 'POST'
        }).done(function (response) {
            if (response.status) reloadAfter(response.message + '：' + response.data.task_no);
        });
    });
})(jQuery);
</script>
