<div class="platform-online-panel">
    <div class="online-page-heading">
        <h3>在线人数面板</h3>
        <span>统计玩家在线状态、最近活跃和登录来源</span>
    </div>

    <div class="online-stat-grid">
        <div class="online-stat">
            <span>在线总人数</span>
            <strong>{{ $stats['onlineTotal'] }}</strong>
            <small>会员 + 代理</small>
        </div>
        <div class="online-stat">
            <span>在线会员</span>
            <strong>{{ $stats['onlineMembers'] }}</strong>
            <small>非代理玩家</small>
        </div>
        <div class="online-stat">
            <span>在线代理</span>
            <strong>{{ $stats['onlineAgents'] }}</strong>
            <small>代理账号</small>
        </div>
        <div class="online-stat">
            <span>今日登录</span>
            <strong>{{ $stats['todayLogins'] }}</strong>
            <small>按登录日志去重</small>
        </div>
        <div class="online-stat">
            <span>近15分钟活跃</span>
            <strong>{{ $stats['active15Minutes'] }}</strong>
            <small>按最后登录时间</small>
        </div>
        <div class="online-stat">
            <span>玩家总数</span>
            <strong>{{ $stats['totalPlayers'] }}</strong>
            <small>未删除玩家</small>
        </div>
    </div>

    <form method="GET" class="online-filter">
        <div class="form-group">
            <label>关键词</label>
            <input
                type="text"
                name="keyword"
                class="form-control"
                value="{{ $filters['keyword'] }}"
                placeholder="用户名 / 姓名 / IP"
            >
        </div>
        <div class="form-group">
            <label>在线状态</label>
            <select name="online_status" class="form-control">
                <option value="">全部</option>
                <option value="online" {{ $filters['online_status'] === 'online' ? 'selected' : '' }}>在线</option>
                <option value="offline" {{ $filters['online_status'] === 'offline' ? 'selected' : '' }}>离线</option>
            </select>
        </div>
        <div class="form-group">
            <label>账号类型</label>
            <select name="role" class="form-control">
                <option value="">全部</option>
                <option value="member" {{ $filters['role'] === 'member' ? 'selected' : '' }}>会员</option>
                <option value="agent" {{ $filters['role'] === 'agent' ? 'selected' : '' }}>代理</option>
            </select>
        </div>
        <div class="form-actions">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-search"></i> 搜索
            </button>
            <a href="{{ admin_url('tcg/12660') }}" class="btn btn-light">重置</a>
            <a href="{{ admin_url('tcg/12660') }}?{{ http_build_query(request()->query()) }}" class="btn btn-success">
                <i class="fa fa-refresh"></i> 刷新
            </a>
        </div>
    </form>

    <div class="online-table-wrap">
        <div class="online-table-title">
            <h4>玩家明细</h4>
            <span>最多显示 100 条，优先显示在线玩家</span>
        </div>
        <div class="table-responsive">
            <table class="table table-bordered table-hover online-player-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>姓名</th>
                        <th>账号类型</th>
                        <th>在线状态</th>
                        <th>账号状态</th>
                        <th>账户余额</th>
                        <th>游戏余额</th>
                        <th>最后登录IP</th>
                        <th>最后登录时间</th>
                        <th>登录次数</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($players as $player)
                        <tr>
                            <td>{{ $player->id }}</td>
                            <td>{{ $player->username }}</td>
                            <td>{{ $player->realname ?: '-' }}</td>
                            <td>{{ $player->role_label }}</td>
                            <td>
                                <span class="online-badge {{ (int) $player->isonline === 1 ? 'online-badge-on' : 'online-badge-off' }}">
                                    {{ $player->online_label }}
                                </span>
                            </td>
                            <td>{{ $player->status_label }}</td>
                            <td>{{ $player->balance }}</td>
                            <td>{{ $player->mbalance }}</td>
                            <td>{{ $player->last_login_ip }}</td>
                            <td>{{ $player->last_login_at }}</td>
                            <td>{{ $player->loginsum }}</td>
                            <td>
                                <a class="btn btn-xs btn-primary" href="{{ admin_url('users/'.$player->id) }}">查看玩家</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center text-muted">当前条件下没有玩家记录</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.platform-online-panel {
    color: #1f2937;
}
.online-page-heading {
    align-items: baseline;
    display: flex;
    gap: 10px;
    margin-bottom: 12px;
}
.online-page-heading h3 {
    font-size: 18px;
    margin: 0;
}
.online-page-heading span {
    color: #6b7280;
}
.online-stat-grid {
    display: grid;
    gap: 12px;
    grid-template-columns: repeat(6, minmax(0, 1fr));
    margin-bottom: 16px;
}
.online-stat {
    background: #fff;
    border: 1px solid #e8edf4;
    border-radius: 4px;
    padding: 14px;
}
.online-stat span,
.online-stat small {
    color: #6b7280;
    display: block;
}
.online-stat strong {
    color: #1677d2;
    display: block;
    font-size: 26px;
    line-height: 1.2;
    margin: 6px 0;
}
.online-filter {
    align-items: flex-end;
    background: #fff;
    border: 1px solid #e8edf4;
    border-radius: 4px;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 16px;
    padding: 14px;
}
.online-filter .form-group {
    margin-bottom: 0;
    min-width: 180px;
}
.online-filter label {
    color: #4b5563;
    font-weight: 600;
}
.online-filter .form-actions {
    display: flex;
    gap: 8px;
}
.online-table-wrap {
    background: #fff;
    border: 1px solid #e8edf4;
    border-radius: 4px;
}
.online-table-title {
    align-items: baseline;
    border-bottom: 1px solid #e8edf4;
    display: flex;
    gap: 10px;
    padding: 12px 14px;
}
.online-table-title h4 {
    font-size: 16px;
    margin: 0;
}
.online-table-title span {
    color: #6b7280;
}
.online-player-table {
    margin-bottom: 0;
}
.online-badge {
    border-radius: 3px;
    display: inline-block;
    min-width: 42px;
    padding: 3px 8px;
    text-align: center;
}
.online-badge-on {
    background: #e8f7ef;
    color: #168a4a;
}
.online-badge-off {
    background: #f3f4f6;
    color: #6b7280;
}
@media (max-width: 1200px) {
    .online-stat-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr));
    }
}
@media (max-width: 768px) {
    .online-stat-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }
    .online-filter {
        display: block;
    }
    .online-filter .form-group,
    .online-filter .form-actions {
        margin-bottom: 10px;
    }
}
</style>
