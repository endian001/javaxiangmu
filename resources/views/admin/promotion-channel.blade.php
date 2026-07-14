@php
    $items = $items ?? null;
    $events = $events ?? null;
    $postbackLogs = $postbackLogs ?? null;
    $settings = $settings ?? [];
    $itemRows = $items ? $items->getCollection() : collect();
@endphp
<div
    class="promotion-channel-page"
    id="promotionChannelPage"
    data-code="{{ $code }}"
    data-module="{{ $module }}"
    data-page-labels="投放链接设置|推广域名管理|落地页配置|SEO配置|未注册推播|事件记录"
>
    <style>
        .promotion-channel-page { color:#303133; }
        .pc-panel { background:#fff; border:1px solid #e8eaed; margin-bottom:16px; box-shadow:0 1px 2px rgba(0,0,0,.03); }
        .pc-panel-head { display:flex; align-items:center; justify-content:space-between; gap:16px; padding:15px 18px; border-bottom:1px solid #eceff3; }
        .pc-panel-head h4 { margin:0; font-size:16px; font-weight:600; }
        .pc-panel-head p { margin:4px 0 0; color:#909399; font-size:12px; }
        .pc-panel-body { padding:16px 18px; }
        .pc-toolbar { display:flex; flex-wrap:wrap; align-items:flex-end; gap:12px; }
        .pc-field { min-width:150px; }
        .pc-field label { display:block; color:#606266; font-size:12px; margin-bottom:5px; }
        .pc-field input,.pc-field select { height:34px; }
        .pc-table { margin-bottom:0; }
        .pc-table thead th { background:#f7f8fa; color:#606266; font-weight:600; white-space:nowrap; }
        .pc-table td { vertical-align:middle!important; }
        .pc-empty { padding:34px!important; color:#909399; text-align:center; }
        .pc-muted { color:#909399; }
        .pc-badge { display:inline-block; min-width:58px; padding:3px 8px; border-radius:3px; text-align:center; font-size:12px; }
        .pc-badge-ok { color:#16794b; background:#e8f7ef; }
        .pc-badge-off { color:#8b9098; background:#f1f2f4; }
        .pc-badge-warn { color:#9a6500; background:#fff4d6; }
        .pc-badge-danger { color:#b42318; background:#feeceb; }
        .pc-actions { white-space:nowrap; }
        .pc-tabs { display:flex; flex-wrap:wrap; border-bottom:1px solid #dfe3e8; margin-bottom:16px; }
        .pc-tabs a { padding:10px 18px; color:#606266; border-bottom:2px solid transparent; }
        .pc-tabs a.active { color:#1769aa; border-bottom-color:#1769aa; font-weight:600; }
        .pc-info-list { margin:0; padding-left:18px; color:#606266; line-height:1.8; }
        .pc-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        .pc-form-grid .full { grid-column:1 / -1; }
        .pc-required { color:#c00000; }
        @media (max-width: 900px) {
            .pc-form-grid { grid-template-columns:1fr; }
            .pc-panel-head { align-items:flex-start; flex-direction:column; }
        }
    </style>

    <div class="pc-panel">
        <div class="pc-panel-head">
            <div>
                <h4>{{ $page['title'] }}</h4>
                <p>{{ $page['summary'] }}</p>
            </div>
            @if($module === 'links')
                <div>
                    <button class="btn btn-primary btn-sm js-promotion-create" data-type="main">新增主站投放链接</button>
                    <button class="btn btn-info btn-sm js-promotion-create" data-type="landing">新增落地页面投放链接</button>
                    <button class="btn btn-default btn-sm js-promotion-create" data-type="speed">新增测速页投放链接</button>
                    <button class="btn btn-warning btn-sm js-settings-open">全局设置</button>
                </div>
            @elseif(in_array($module, ['domains', 'landing', 'seo'], true))
                <button class="btn btn-primary btn-sm js-promotion-create" data-type="{{ $module === 'domains' ? 'promotion' : $module }}">
                    <i class="fa fa-plus"></i> 新增
                </button>
            @elseif($module === 'push')
                <div>
                    <button class="btn btn-primary btn-sm js-promotion-create" data-type="template">新增模板</button>
                    <button class="btn btn-success btn-sm js-push-open" data-mode="immediate">发送</button>
                    <button class="btn btn-warning btn-sm js-push-open" data-mode="scheduled">定时发送</button>
                </div>
            @elseif($module === 'events')
                <div>
                    <button class="btn btn-warning btn-sm js-settings-open">Facebook活动设置</button>
                    <a class="btn btn-success btn-sm" href="{{ admin_url('tcg/promotion/'.$code.'/events/export').'?'.http_build_query(request()->query()) }}">
                        <i class="fa fa-download"></i> 导出
                    </a>
                </div>
            @endif
        </div>
        @if($module !== 'events')
            <div class="pc-panel-body">
                <form method="get" class="pc-toolbar">
                    <div class="pc-field"><label>关键字</label><input name="keyword" class="form-control" value="{{ request('keyword') }}" placeholder="名称 / 域名 / 用户 / 备注"></div>
                    @if(in_array($module, ['links', 'domains'], true))
                        <div class="pc-field"><label>推广域名</label><input name="domain" class="form-control" value="{{ request('domain') }}"></div>
                        <div class="pc-field"><label>用户 / 代理账号</label><input name="owner" class="form-control" value="{{ request('owner') }}"></div>
                    @endif
                    @if($module === 'domains')
                        <div class="pc-field"><label>指向</label><input name="target" class="form-control" value="{{ request('target') }}"></div>
                    @endif
                    <div class="pc-field"><label>状态</label><select name="status" class="form-control"><option value="">全部</option><option value="1" {{ request('status') === '1' ? 'selected' : '' }}>启用</option><option value="0" {{ request('status') === '0' ? 'selected' : '' }}>停用</option></select></div>
                    <button class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                    <a class="btn btn-default" href="{{ request()->url() }}">重置</a>
                    @if($module === 'links')
                        <button type="button" class="btn btn-danger js-promotion-bulk-delete">批量删除</button>
                    @endif
                    @if($module === 'domains')
                        <button type="button" class="btn btn-warning js-settings-open">批量设置</button>
                    @endif
                </form>
            </div>
        @endif
    </div>

    @if($module === 'push')
        <div class="alert alert-warning">
            <strong>需要 Firebase 权限。</strong>
            当前页面会建立真实推播队列；服务器接入 Firebase 工作进程后才会更新发送数量和成功数。
        </div>
        <div class="pc-tabs">
            <a class="{{ $tab === 'settings' ? 'active' : '' }}" href="{{ request()->url() }}?tab=settings">推播设置</a>
            <a class="{{ $tab === 'records' ? 'active' : '' }}" href="{{ request()->url() }}?tab=records">推播记录</a>
        </div>
    @elseif($module === 'events')
        <div class="pc-tabs">
            <a class="{{ $tab === 'pixel-events' ? 'active' : '' }}" href="{{ request()->url() }}?tab=pixel-events">像素事件</a>
            <a class="{{ $tab === 'adjust-s2s' ? 'active' : '' }}" href="{{ request()->url() }}?tab=adjust-s2s">调整S2S事件上报</a>
            <a class="{{ $tab === 'postback-logs' ? 'active' : '' }}" href="{{ request()->url() }}?tab=postback-logs">回传日志</a>
        </div>
    @endif

    @if($module === 'events' && $tab === 'postback-logs')
        <div class="pc-panel">
            <div class="pc-panel-body">
                <form method="get" class="pc-toolbar">
                    <input type="hidden" name="tab" value="postback-logs">
                    <div class="pc-field"><label>平台</label><input name="platform" class="form-control" value="{{ request('platform') }}"></div>
                    <div class="pc-field"><label>状态</label><input name="status" class="form-control" value="{{ request('status') }}" placeholder="pending / sent / failed / skipped"></div>
                    <div class="pc-field"><label>事件</label><input name="event" class="form-control" value="{{ request('event') }}"></div>
                    <div class="pc-field"><label>事件ID</label><input name="event_id" class="form-control" value="{{ request('event_id') }}"></div>
                    <div class="pc-field"><label>跳过原因</label><input name="skip_reason" class="form-control" value="{{ request('skip_reason') }}"></div>
                    <div class="pc-field"><label>开始</label><input type="date" name="start_at" class="form-control" value="{{ request('start_at') }}"></div>
                    <div class="pc-field"><label>结束</label><input type="date" name="end_at" class="form-control" value="{{ request('end_at') }}"></div>
                    <button class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                    <a class="btn btn-default" href="{{ request()->url() }}?tab=postback-logs">重置</a>
                </form>
            </div>
            <div class="table-responsive"><table class="table table-hover pc-table">
                <thead><tr><th>时间</th><th>平台</th><th>状态</th><th>跳过原因</th><th>事件</th><th>平台事件</th><th>事件ID</th><th>请求地址</th><th>响应</th><th>重试</th></tr></thead>
                <tbody>
                @if($postbackLogs)
                    @forelse($postbackLogs as $log)
                        <tr>
                            <td>{{ $log->created_at }}</td>
                            <td>{{ $log->platform }}</td>
                            <td><span class="pc-badge {{ $log->status === 'sent' ? 'pc-badge-ok' : ($log->status === 'failed' ? 'pc-badge-danger' : ($log->status === 'skipped' ? 'pc-badge-off' : 'pc-badge-warn')) }}">{{ $log->status }}</span></td>
                            <td>{{ $log->skip_reason ?: '-' }}</td>
                            <td>{{ $log->event_name }}</td>
                            <td>{{ $log->platform_event_name ?: '-' }}</td>
                            <td>{{ $log->event_id ?: '-' }}</td>
                            <td style="max-width:360px;word-break:break-all;">{{ $log->request_url ?: '-' }}</td>
                            <td>{{ $log->response_status ?: '-' }}</td>
                            <td>{{ $log->attempts }}{{ $log->next_retry_at ? ' / '.$log->next_retry_at : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="pc-empty">暂无回传日志</td></tr>
                    @endforelse
                @else
                    <tr><td colspan="10" class="pc-empty">回传日志表尚未迁移</td></tr>
                @endif
                </tbody>
            </table></div>
            @if($postbackLogs)
                <div class="pc-panel-body">{{ $postbackLogs->links() }}</div>
            @endif
        </div>
    @elseif($module === 'events')
        <div class="pc-panel">
            <div class="pc-panel-body">
                <form method="get" class="pc-toolbar">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <div class="pc-field"><label>链接 ID</label><input name="link_id" class="form-control" value="{{ request('link_id') }}"></div>
                    <div class="pc-field"><label>脸书像素ID</label><input name="facebook_pixel_id" class="form-control" value="{{ request('facebook_pixel_id') }}"></div>
                    <div class="pc-field"><label>抖音像素ID</label><input name="tiktok_pixel_id" class="form-control" value="{{ request('tiktok_pixel_id') }}"></div>
                    <div class="pc-field"><label>用户名</label><input name="username" class="form-control" value="{{ request('username') }}"></div>
                    <div class="pc-field"><label>代理账号</label><input name="agent_account" class="form-control" value="{{ request('agent_account') }}"></div>
                    <div class="pc-field"><label>事件</label><input name="event" class="form-control" value="{{ request('event') }}"></div>
                    <div class="pc-field"><label>活动开始</label><input type="date" name="start_at" class="form-control" value="{{ request('start_at') }}"></div>
                    <div class="pc-field"><label>活动结束</label><input type="date" name="end_at" class="form-control" value="{{ request('end_at') }}"></div>
                    <div class="pc-field"><label>原始记录</label><input name="raw_record" class="form-control" value="{{ request('raw_record') }}"></div>
                    <button class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                    <a class="btn btn-default" href="{{ request()->url() }}?tab={{ $tab }}">重置</a>
                </form>
            </div>
            <div class="table-responsive"><table class="table table-hover pc-table">
                <thead><tr><th>脸书像素ID</th><th>抖音像素ID</th><th>来自FB广告</th><th>注册时间</th><th>注册网址</th><th>代理账号</th><th>用户ID</th><th>用户名</th><th>事件</th><th>活动时间</th><th>钱</th><th>网址</th><th>用户代理</th><th>原始记录</th></tr></thead>
                <tbody>
                @forelse($events as $event)
                    <tr>
                        <td>{{ $event->facebook_pixel_id ?: '-' }}</td>
                        <td>{{ $event->tiktok_pixel_id ?: '-' }}</td>
                        <td>{{ $event->from_facebook ? '是' : '否' }}</td>
                        <td>{{ $event->registered_at ?: '-' }}</td>
                        <td>{{ $event->registration_url ?: '-' }}</td>
                        <td>{{ $event->agent_account ?: '-' }}</td>
                        <td>{{ $event->user_id ?: '-' }}</td>
                        <td>{{ $event->username ?: '-' }}</td>
                        <td>{{ $event->event }}</td>
                        <td>{{ $event->event_at ?: '-' }}</td>
                        <td>{{ $event->amount }}</td>
                        <td>{{ $event->url ?: '-' }}</td>
                        <td>{{ $event->user_agent ?: '-' }}</td>
                        <td>{{ $event->raw_record ?: '-' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="14" class="pc-empty">暂无事件记录</td></tr>
                @endforelse
                </tbody>
            </table></div>
            <div class="pc-panel-body">{{ $events->links() }}</div>
        </div>
    @else
        @if($module === 'push' && $tab === 'records')
            <div class="pc-panel">
                <div class="table-responsive"><table class="table table-hover pc-table">
                    <thead><tr><th>发送时间</th><th>推播标题</th><th>推播对象</th><th>推播数量</th><th>成功数</th><th>排程状态</th></tr></thead>
                    <tbody>
                    @forelse($pushJobs as $job)
                        <tr><td>{{ $job->scheduled_at ?: $job->created_at }}</td><td>{{ $job->title }}</td><td>{{ $job->audience_type }}</td><td>{{ $job->total_count }}</td><td>{{ $job->success_count }}</td><td><span class="pc-badge pc-badge-warn">{{ $job->status }}</span></td></tr>
                    @empty
                        <tr><td colspan="6" class="pc-empty">暂无推播记录</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        @else
            <div class="pc-panel">
                <div class="table-responsive"><table class="table table-hover pc-table">
                    <thead>
                    @if($module === 'links')
                        <tr><th><input type="checkbox" class="js-promotion-check-all"></th><th>ID</th><th>链接类型</th><th>推广域名</th><th>HTTPS状态</th><th>斗篷功能</th><th>广告审核通过</th><th>代理账号</th><th>投放工具</th><th>应用程序</th><th>状态</th><th>执行状态</th><th>今日/总下载次数</th><th>今日/总访问量</th><th>最后访问时间</th><th>备注</th><th>更新</th><th>操作</th></tr>
                    @elseif($module === 'domains')
                        <tr><th>域类型</th><th>推广域名</th><th>用户</th><th>指向</th><th>总访问量</th><th>最后点击</th><th>状态</th><th>操作</th></tr>
                    @elseif($module === 'landing')
                        <tr><th>ID</th><th>APP名称</th><th>语系</th><th>APP图</th><th>总评分</th><th>评分</th><th>下载人数</th><th>滚动图</th><th>应用域名</th><th>操作</th></tr>
                    @elseif($module === 'seo')
                        <tr><th>ID</th><th>模板名称</th><th>标题</th><th>描述元数据</th><th>关键字</th><th>预览图片</th><th>应用域名</th><th>状态</th><th>操作</th></tr>
                    @else
                        <tr><th>ID</th><th>模板名称</th><th>推播标题</th><th>推播内容</th><th>状态</th><th>操作</th></tr>
                    @endif
                    </thead>
                    <tbody>
                    @forelse($itemRows as $item)
                        @php $meta = $item->meta ?? []; @endphp
                        <tr>
                            @if($module === 'links')
                                <td><input type="checkbox" class="js-promotion-row-check" value="{{ $item->id }}"></td>
                                <td>{{ $item->id }}</td><td>{{ $item->item_type ?: '-' }}</td><td>{{ $item->domain }}</td><td>{{ $meta['https_status'] ?? '-' }}</td><td>{{ ($meta['cloak_enabled'] ?? '') === '1' ? '开启' : '关闭' }}</td><td>{{ ($meta['ad_review_passed'] ?? '') === '1' ? '通过' : '未通过' }}</td><td>{{ $item->owner ?: '-' }}</td><td>{{ $meta['tool'] ?? '-' }}</td><td>{{ $meta['application'] ?? '-' }}</td><td><span class="pc-badge {{ $item->status ? 'pc-badge-ok' : 'pc-badge-off' }}">{{ $item->status ? '启用' : '停用' }}</span></td><td>{{ $meta['execution_status'] ?? '-' }}</td><td>{{ ($meta['downloads_today'] ?? 0).' / '.($meta['total_downloads'] ?? 0) }}</td><td>{{ ($meta['visits_today'] ?? 0).' / '.($meta['total_visits'] ?? 0) }}</td><td>{{ $meta['last_visited_at'] ?? '-' }}</td><td>{{ $meta['note'] ?? '-' }}</td><td>{{ $item->updated_at }}</td>
                            @elseif($module === 'domains')
                                <td>{{ ($meta['domain_type'] ?? $item->item_type) ?: '-' }}</td><td>{{ $item->domain }}</td><td>{{ $item->owner ?: '-' }}</td><td>{{ $item->target ?: '-' }}</td><td>{{ $meta['total_visits'] ?? 0 }}</td><td>{{ $meta['last_click'] ?? '-' }}</td><td><span class="pc-badge {{ $item->status ? 'pc-badge-ok' : 'pc-badge-off' }}">{{ $item->status ? '启用' : '停用' }}</span></td>
                            @elseif($module === 'landing')
                                <td>{{ $item->id }}</td><td>{{ $item->name }}</td><td>{{ $meta['language'] ?? '-' }}</td><td>{{ $meta['app_icon'] ?? '-' }}</td><td>{{ $meta['total_score'] ?? '-' }}</td><td>{{ $meta['score'] ?? '-' }}</td><td>{{ $meta['download_count'] ?? '-' }}</td><td>{{ $meta['carousel_images'] ?? '-' }}</td><td>{{ $item->domain ?: '-' }}</td>
                            @elseif($module === 'seo')
                                <td>{{ $item->id }}</td><td>{{ $item->name }}</td><td>{{ $meta['title'] ?? '-' }}</td><td>{{ $meta['meta_description'] ?? '-' }}</td><td>{{ $meta['keywords'] ?? '-' }}</td><td>{{ $meta['preview_image'] ?? '-' }}</td><td>{{ ($meta['bound_domain'] ?? $item->domain) ?: '-' }}</td><td><span class="pc-badge {{ $item->status ? 'pc-badge-ok' : 'pc-badge-off' }}">{{ $item->status ? '启用' : '停用' }}</span></td>
                            @else
                                <td>{{ $item->id }}</td><td>{{ $item->name }}</td><td>{{ $meta['push_title'] ?? '-' }}</td><td>{{ $meta['push_content'] ?? '-' }}</td><td><span class="pc-badge {{ $item->status ? 'pc-badge-ok' : 'pc-badge-off' }}">{{ $item->status ? '启用' : '停用' }}</span></td>
                            @endif
                            <td class="pc-actions">
                                <button class="btn btn-xs btn-primary js-promotion-edit"
                                    data-id="{{ $item->id }}"
                                    data-type="{{ $item->item_type }}"
                                    data-name="{{ $item->name }}"
                                    data-domain="{{ $item->domain }}"
                                    data-owner="{{ $item->owner }}"
                                    data-target="{{ $item->target }}"
                                    data-status="{{ $item->status ? 1 : 0 }}"
                                    data-position="{{ $item->position }}"
                                    data-meta="{{ json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}">编辑</button>
                                <button class="btn btn-xs btn-danger js-promotion-delete" data-id="{{ $item->id }}">删除</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="18" class="pc-empty">暂无资料，请点击新增。</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
                <div class="pc-panel-body">{{ $items->links() }}</div>
            </div>
        @endif
    @endif

    <div class="modal fade" id="promotionItemModal" tabindex="-1">
        <div class="modal-dialog modal-lg"><div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">推广渠道资料</h4></div>
            <div class="modal-body">
                <input type="hidden" id="promotionItemId">
                <input type="hidden" id="promotionItemType">
                <div class="pc-form-grid">
                    <div class="form-group"><label>名称 / APP名称 / 模板名称<span class="pc-required js-name-required">*</span></label><input id="promotionName" class="form-control" maxlength="191"></div>
                    <div class="form-group"><label>推广域名 / 应用域名<span class="pc-required js-domain-required">*</span></label><input id="promotionDomain" class="form-control" maxlength="191"></div>
                    <div class="form-group"><label>用户 / 代理账号</label><input id="promotionOwner" class="form-control" maxlength="191"></div>
                    <div class="form-group"><label>指向 / 链接</label><input id="promotionTarget" class="form-control" maxlength="1000"></div>
                    <div class="form-group"><label>排序</label><input id="promotionPosition" type="number" min="0" class="form-control" value="0"></div>
                    <div class="form-group"><label>状态</label><select id="promotionStatus" class="form-control"><option value="1">启用</option><option value="0">停用</option></select></div>
                    @foreach([
                        'https_status' => 'HTTPS状态',
                        'cloak_enabled' => '斗篷功能',
                        'ad_review_passed' => '广告审核通过',
                        'tool' => '投放工具',
                        'application' => '应用程序',
                        'execution_status' => '执行状态',
                        'domain_type' => '域类型',
                        'company_name' => '公司名称',
                        'language' => '语系',
                        'total_score' => '总评分',
                        'score' => '评分',
                        'download_count' => '下载人数',
                        'user_age' => '用户年龄',
                        'app_icon' => 'APP图 512x512',
                        'carousel_images' => '滚动图 720x1280',
                        'description_title' => '应用描述标题 0/40',
                        'description_content' => '应用描述内容 0/1200',
                        'favicon' => '网站图标 64x64',
                        'comments' => '评论区设置',
                        'title' => '标题 0/60',
                        'meta_description' => '描述元数据 0/160',
                        'keywords' => '关键字',
                        'preview_image' => '预览图片 大图/小图',
                        'preview_size' => '上传',
                        'bound_domain' => '应用域名',
                        'push_title' => '推播标题 30-40 chars',
                        'push_content' => '推播内容 100-150 chars',
                        'note' => '备注',
                    ] as $field => $label)
                        <div class="form-group pc-extra-field" data-field="{{ $field }}"><label>{{ $label }}</label><input class="form-control js-meta-field" data-key="{{ $field }}"></div>
                    @endforeach
                </div>
                <ul class="pc-info-list">
                    @if($module === 'links')
                        <li>新增主站投放链接：域名设置、参数设置、完成，CNAME 指向 f5-sea.53923992.com。</li>
                        <li>新增落地页面投放链接：域名设置、参数设置、落地页设置、完成。</li>
                        <li>新增测速页投放链接：域名设置、完成。</li>
                    @elseif($module === 'landing')
                        <li>评论区添加：评论者头像 50x50、评论者姓名 0/50、评论内容 0/500、评论日期。</li>
                    @endif
                </ul>
            </div>
            <div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">取消</button><button class="btn btn-primary js-promotion-item-save">保存</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="promotionSettingsModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">全局 / 批量设置</h4></div>
            <div class="modal-body">
                <div class="form-group"><label>过滤非首存日注册玩家</label><select class="form-control js-setting-field" data-key="filter_non_first_deposit"><option value="0">关闭</option><option value="1">开启</option></select></div>
                <div class="form-group"><label>默认 CNAME 指向</label><input class="form-control js-setting-field" data-key="default_cname" value="{{ $settings['default_cname'] ?? 'f5-sea.53923992.com' }}"></div>
                <div class="form-group"><label>批量更新操作</label><select class="form-control js-setting-field" data-key="operation"><option value="">不执行</option><option value="replace_target">替换指向</option></select></div>
                <div class="form-group"><label>旧的指向</label><input class="form-control js-setting-field" data-key="old_target"></div>
                <div class="form-group"><label>新的指向</label><input class="form-control js-setting-field" data-key="new_target"></div>
                <div class="form-group"><label>Facebook 像素 ID</label><input class="form-control js-setting-field" data-key="facebook_pixel_id" value="{{ $settings['facebook_pixel_id'] ?? '' }}"></div>
                <div class="form-group"><label>设置上报事件名称</label><input class="form-control js-setting-field" data-key="facebook_event_name" value="{{ $settings['facebook_event_name'] ?? '' }}"></div>
                <div class="form-group"><label>Adjust 应用令牌</label><input class="form-control js-setting-field" data-key="adjust_app_token" value="{{ $settings['adjust_app_token'] ?? '' }}"></div>
                <div class="form-group"><label>Adjust 事件</label><input class="form-control js-setting-field" data-key="adjust_event" value="{{ $settings['adjust_event'] ?? '' }}"></div>
                <div class="form-group"><label>Adjust 事件代币</label><input class="form-control js-setting-field" data-key="adjust_event_token" value="{{ $settings['adjust_event_token'] ?? '' }}"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">取消</button><button class="btn btn-primary js-promotion-settings-save">保存设置</button></div>
        </div></div>
    </div>

    <div class="modal fade" id="promotionPushModal" tabindex="-1">
        <div class="modal-dialog"><div class="modal-content">
            <div class="modal-header"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">推播任务</h4></div>
            <div class="modal-body">
                <input type="hidden" id="pushSendMode" value="immediate">
                <div class="form-group"><label>选择推播模板</label><select id="pushTemplateId" class="form-control"><option value="">不使用模板</option>@foreach($itemRows as $item)<option value="{{ $item->id }}">{{ $item->name }}</option>@endforeach</select></div>
                <div class="form-group"><label>筛选推播对象</label><select id="pushAudienceType" class="form-control"><option value="all">全部未注册用户</option><option value="never">从未推播过</option><option value="today">今日未推播</option><option value="installed_3_days">安装时间3日内</option><option value="companion_days">N日内陪伴推播</option></select></div>
                <div class="form-group"><label>预计赠送 N 名用户</label><input id="pushAudienceValue" class="form-control"></div>
                <div class="form-group"><label>推播标题 30-40 chars</label><input id="pushTitle" class="form-control"></div>
                <div class="form-group"><label>推播内容 100-150 chars</label><textarea id="pushContent" class="form-control" rows="4"></textarea></div>
                <div class="form-group"><label>排程时间</label><input id="pushScheduledAt" type="datetime-local" class="form-control"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-default" data-dismiss="modal">取消</button><button class="btn btn-success js-promotion-push-submit">提交推播任务</button></div>
        </div></div>
    </div>
</div>

<script>
(function ($) {
    var page = $('#promotionChannelPage');
    if (!page.length) return;
    var code = page.data('code');
    var module = page.data('module');
    var token = '{{ csrf_token() }}';
    var activeMetaByModule = {
        links: ['https_status','cloak_enabled','ad_review_passed','tool','application','execution_status','downloads_today','total_downloads','visits_today','total_visits','last_visited_at','note'],
        domains: ['domain_type','total_visits','last_click'],
        landing: ['company_name','language','total_score','score','download_count','user_age','app_icon','carousel_images','description_title','description_content','favicon','comments'],
        seo: ['title','meta_description','keywords','preview_image','preview_size','bound_domain'],
        push: ['push_title','push_content']
    };
    function notify(type, message) {
        if (window.Dcat && typeof window.Dcat[type] === 'function') { window.Dcat[type](message); return; }
        window.alert(message);
    }
    function ajax(options) {
        return $.ajax($.extend(true, {headers:{'X-CSRF-TOKEN':token}, dataType:'json'}, options)).fail(function(xhr){
            var r = xhr.responseJSON || {};
            var msg = r.message || '操作失败';
            if (r.errors) { var k = Object.keys(r.errors)[0]; if (k) msg = r.errors[k][0]; }
            notify('error', msg);
        });
    }
    function reloadAfter(message) {
        notify('success', message);
        window.setTimeout(function(){ window.location.reload(); }, 500);
    }
    function setExtraVisibility() {
        var allowed = activeMetaByModule[module] || [];
        page.find('.pc-extra-field').hide().each(function(){
            if (allowed.indexOf($(this).data('field')) >= 0) $(this).show();
        });
        page.find('.js-name-required').toggle(module !== 'links' && module !== 'domains');
        page.find('.js-domain-required').toggle(module === 'links' || module === 'domains');
    }
    function openItem(button, type) {
        setExtraVisibility();
        var meta = {};
        if (button) { try { meta = JSON.parse(button.attr('data-meta') || '{}'); } catch (e) {} }
        $('#promotionItemId').val(button ? button.data('id') : '');
        $('#promotionItemType').val(button ? button.data('type') : type);
        $('#promotionName').val(button ? button.data('name') : '');
        $('#promotionDomain').val(button ? button.data('domain') : '');
        $('#promotionOwner').val(button ? button.data('owner') : '');
        $('#promotionTarget').val(button ? button.data('target') : '');
        $('#promotionPosition').val(button ? button.data('position') : 0);
        $('#promotionStatus').val(button ? String(button.data('status')) : '1');
        page.find('.js-meta-field').each(function(){ $(this).val(meta[$(this).data('key')] || ''); });
        $('#promotionItemModal').modal('show');
    }
    page.on('click', '.js-promotion-create', function(){ openItem(null, $(this).data('type')); });
    page.on('click', '.js-promotion-edit', function(){ openItem($(this), $(this).data('type')); });
    page.on('click', '.js-promotion-item-save', function(){
        var id = $('#promotionItemId').val();
        var data = {
            item_type: $('#promotionItemType').val(),
            name: $('#promotionName').val(),
            domain: $('#promotionDomain').val(),
            owner: $('#promotionOwner').val(),
            target: $('#promotionTarget').val(),
            position: $('#promotionPosition').val(),
            status: $('#promotionStatus').val()
        };
        page.find('.pc-extra-field:visible .js-meta-field').each(function(){ data[$(this).data('key')] = $(this).val(); });
        ajax({url: '{{ admin_url('tcg/promotion') }}/' + code + '/items' + (id ? '/' + id : ''), method: id ? 'PUT' : 'POST', data: data}).done(function(r){ if (r.status) reloadAfter(r.message); });
    });
    page.on('click', '.js-promotion-delete', function(){
        if (!window.confirm('确认删除这条资料吗？')) return;
        ajax({url: '{{ admin_url('tcg/promotion') }}/' + code + '/items/' + $(this).data('id'), method:'DELETE'}).done(function(r){ if (r.status) reloadAfter(r.message); });
    });
    page.on('change', '.js-promotion-check-all', function(){ page.find('.js-promotion-row-check').prop('checked', $(this).is(':checked')); });
    page.on('click', '.js-promotion-bulk-delete', function(){
        var ids = [];
        page.find('.js-promotion-row-check:checked').each(function(){ ids.push($(this).val()); });
        if (!ids.length) { notify('error', '请先选择要删除的资料'); return; }
        if (!window.confirm('确认批量删除已选择资料吗？')) return;
        ajax({url:'{{ admin_url('tcg/promotion') }}/' + code + '/bulk-delete', method:'POST', data:{ids:ids}}).done(function(r){ if (r.status) reloadAfter(r.message); });
    });
    page.on('click', '.js-settings-open', function(){ $('#promotionSettingsModal').modal('show'); });
    page.on('click', '.js-promotion-settings-save', function(){
        var settings = {};
        $('#promotionSettingsModal .js-setting-field').each(function(){ settings[$(this).data('key')] = $(this).val(); });
        ajax({url:'{{ admin_url('tcg/promotion') }}/' + code + '/settings', method:'POST', data:{settings:settings}}).done(function(r){ if (r.status) reloadAfter(r.message); });
    });
    page.on('click', '.js-push-open', function(){
        $('#pushSendMode').val($(this).data('mode'));
        $('#pushScheduledAt').closest('.form-group').toggle($(this).data('mode') === 'scheduled');
        $('#promotionPushModal').modal('show');
    });
    page.on('click', '.js-promotion-push-submit', function(){
        ajax({url:'{{ admin_url('tcg/promotion') }}/' + code + '/push-jobs', method:'POST', data:{
            template_id: $('#pushTemplateId').val(),
            title: $('#pushTitle').val(),
            content: $('#pushContent').val(),
            audience_type: $('#pushAudienceType').val(),
            audience_value: $('#pushAudienceValue').val(),
            send_mode: $('#pushSendMode').val(),
            scheduled_at: $('#pushScheduledAt').val()
        }}).done(function(r){ if (r.status) reloadAfter(r.message); });
    });
    setExtraVisibility();
})(jQuery);
</script>
