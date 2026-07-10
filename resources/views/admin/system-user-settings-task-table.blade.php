<div class="sus-panel">
    @if(!$history)
        <div class="sus-panel-body">
            <form method="get" class="sus-toolbar">
                <div class="sus-field"><label>任务号</label><input name="task_no" class="form-control" value="{{ request('task_no') }}"></div>
                <div class="sus-field"><label>执行人</label><input name="requested_by" class="form-control" value="{{ request('requested_by') }}"></div>
                <div class="sus-field"><label>状态</label>
                    <select name="status" class="form-control">
                        <option value="">全部</option>
                        <option value="running" {{ request('status') === 'running' ? 'selected' : '' }}>执行中</option>
                        <option value="success" {{ request('status') === 'success' ? 'selected' : '' }}>成功</option>
                        <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>失败</option>
                    </select>
                </div>
                <button class="btn btn-primary"><i class="fa fa-search"></i> 搜索</button>
                <a class="btn btn-default" href="{{ request()->url() }}">重置</a>
            </form>
        </div>
    @endif
    <div class="table-responsive">
        <table class="table table-hover sus-table">
            <thead><tr><th>任务号</th><th>任务类型</th><th>任务名称</th><th>执行人</th><th>状态</th><th>开始时间</th><th>结束时间</th><th>结果 / 错误</th><th>操作</th></tr></thead>
            <tbody>
            @forelse($tasks as $task)
                @php
                    $badge = $task->status === 'success' ? 'sus-badge-ok' : ($task->status === 'failed' ? 'sus-badge-danger' : 'sus-badge-warn');
                    $statusText = ['running' => '执行中', 'pending' => '等待中', 'success' => '成功', 'failed' => '失败'][$task->status] ?? $task->status;
                @endphp
                <tr>
                    <td><strong>{{ $task->task_no }}</strong></td><td>{{ $task->task_type }}</td><td>{{ $task->title }}</td>
                    <td>{{ $task->requested_by_name ?: '-' }}</td><td><span class="sus-badge {{ $badge }}">{{ $statusText }}</span></td>
                    <td>{{ $task->started_at ?: '-' }}</td><td>{{ $task->finished_at ?: '-' }}</td>
                    <td class="sus-result">{{ $task->error_message ?: ($task->result ?: '-') }}</td>
                    <td>@if($task->status === 'failed')<button type="button" class="btn btn-xs btn-warning js-task-retry" data-id="{{ $task->id }}"><i class="fa fa-repeat"></i> 重试</button>@else - @endif</td>
                </tr>
            @empty
                <tr><td colspan="9" class="sus-empty">暂无任务记录</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="sus-panel-body">{{ $tasks->links() }}</div>
</div>
