@php
    $baseUrl = admin_url('live-chats');
@endphp

<div class="live-chat-admin" data-live-chat-admin>
    <div class="live-chat-toolbar">
        <div>
            <h3>在线客服</h3>
            <p>用户消息会进入实时会话，管理员在这里直接回复，不再走提交工单。</p>
        </div>
        <div class="live-chat-filters">
            <select data-live-chat-status>
                <option value="open">进行中</option>
                <option value="closed">已关闭</option>
                <option value="all">全部</option>
            </select>
            <input type="search" placeholder="搜索会话/会员/内容" data-live-chat-keyword>
            <button type="button" class="btn btn-primary btn-sm" data-live-chat-refresh>刷新</button>
        </div>
    </div>

    <div class="live-chat-layout">
        <aside class="live-chat-sessions" data-live-chat-sessions>
            <div class="live-chat-empty">正在加载会话...</div>
        </aside>
        <section class="live-chat-panel">
            <header class="live-chat-panel-head">
                <div>
                    <strong data-live-chat-title>请选择会话</strong>
                    <small data-live-chat-subtitle>实时聊天记录会显示在这里</small>
                </div>
                <div class="live-chat-actions">
                    <button type="button" class="btn btn-default btn-sm" data-live-chat-reopen hidden>重开</button>
                    <button type="button" class="btn btn-warning btn-sm" data-live-chat-close hidden>关闭</button>
                </div>
            </header>
            <div class="live-chat-messages" data-live-chat-messages>
                <div class="live-chat-empty">左侧选择一个会话开始处理。</div>
            </div>
            <form class="live-chat-composer" data-live-chat-form>
                <textarea name="content" rows="3" placeholder="输入回复内容，Enter 换行，点击发送回复用户" disabled></textarea>
                <button type="submit" class="btn btn-success" disabled>发送</button>
            </form>
        </section>
    </div>
</div>

<style>
    .live-chat-admin{font-size:13px}
    .live-chat-toolbar{display:flex;align-items:center;justify-content:space-between;gap:16px;background:#fff;border:1px solid #edf0f4;border-radius:6px;padding:16px;margin-bottom:14px}
    .live-chat-toolbar h3{margin:0 0 5px;font-size:18px;font-weight:700}
    .live-chat-toolbar p{margin:0;color:#6b7280}
    .live-chat-filters{display:flex;gap:8px;align-items:center}
    .live-chat-filters select,.live-chat-filters input{height:34px;border:1px solid #d9dee7;border-radius:4px;padding:0 10px;background:#fff}
    .live-chat-layout{display:grid;grid-template-columns:330px minmax(0,1fr);gap:14px;min-height:620px}
    .live-chat-sessions,.live-chat-panel{background:#fff;border:1px solid #edf0f4;border-radius:6px;overflow:hidden}
    .live-chat-sessions{max-height:720px;overflow-y:auto}
    .live-chat-session{display:block;width:100%;border:0;border-bottom:1px solid #edf0f4;background:#fff;text-align:left;padding:12px 14px;cursor:pointer}
    .live-chat-session:hover,.live-chat-session.active{background:#f5f9ff}
    .live-chat-session-head{display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:6px}
    .live-chat-session-title{font-weight:700;color:#1f2937}
    .live-chat-session-time{font-size:12px;color:#8a94a6}
    .live-chat-session-msg{color:#5f6b7a;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .live-chat-badge{display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:20px;border-radius:10px;background:#e53e3e;color:#fff;font-size:12px;padding:0 7px}
    .live-chat-status{display:inline-block;border-radius:10px;padding:2px 7px;font-size:12px;background:#eef2f7;color:#526071}
    .live-chat-status.open{background:#e6f7ee;color:#16794a}
    .live-chat-status.closed{background:#f2f4f7;color:#777}
    .live-chat-panel{display:grid;grid-template-rows:auto 1fr auto}
    .live-chat-panel-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:14px 16px;border-bottom:1px solid #edf0f4;background:#fbfcfe}
    .live-chat-panel-head strong{display:block;font-size:16px}
    .live-chat-panel-head small{display:block;color:#8a94a6;margin-top:3px}
    .live-chat-actions{display:flex;gap:8px}
    .live-chat-messages{padding:16px;overflow-y:auto;background:#f6f8fb}
    .live-chat-message{display:flex;margin-bottom:12px}
    .live-chat-message.admin{justify-content:flex-end}
    .live-chat-bubble{max-width:72%;border-radius:14px;padding:10px 12px;background:#fff;border:1px solid #e6eaf0;box-shadow:0 1px 2px rgba(15,23,42,.04)}
    .live-chat-message.admin .live-chat-bubble{background:#e9f7ef;border-color:#caead8}
    .live-chat-bubble p{margin:0;color:#253142;white-space:pre-wrap;word-break:break-word}
    .live-chat-bubble small{display:block;margin-top:6px;color:#8a94a6}
    .live-chat-composer{display:grid;grid-template-columns:1fr 96px;gap:10px;padding:14px;border-top:1px solid #edf0f4;background:#fff}
    .live-chat-composer textarea{resize:vertical;border:1px solid #d9dee7;border-radius:4px;padding:10px}
    .live-chat-empty{padding:18px;text-align:center;color:#8a94a6}
    @media(max-width:900px){.live-chat-toolbar,.live-chat-filters{display:grid;align-items:stretch}.live-chat-layout{grid-template-columns:1fr}.live-chat-sessions{max-height:280px}.live-chat-composer{grid-template-columns:1fr}}
</style>

<script>
    (function () {
        var root = document.querySelector('[data-live-chat-admin]');
        if (!root || root.getAttribute('data-bound') === '1') return;
        root.setAttribute('data-bound', '1');

        var base = @json($baseUrl);
        var csrf = @json(csrf_token());
        var state = { sessions: [], activeId: 0, lastMessageId: 0 };
        var list = root.querySelector('[data-live-chat-sessions]');
        var messages = root.querySelector('[data-live-chat-messages]');
        var title = root.querySelector('[data-live-chat-title]');
        var subtitle = root.querySelector('[data-live-chat-subtitle]');
        var statusFilter = root.querySelector('[data-live-chat-status]');
        var keyword = root.querySelector('[data-live-chat-keyword]');
        var form = root.querySelector('[data-live-chat-form]');
        var textarea = form.querySelector('textarea');
        var sendButton = form.querySelector('button');
        var closeButton = root.querySelector('[data-live-chat-close]');
        var reopenButton = root.querySelector('[data-live-chat-reopen]');

        function ajax(options) {
            options.headers = Object.assign({'X-CSRF-TOKEN': csrf}, options.headers || {});
            return $.ajax($.extend({dataType: 'json'}, options)).fail(function (xhr) {
                var message = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : '请求失败';
                if (window.Dcat && Dcat.error) Dcat.error(message); else alert(message);
            });
        }

        function loadSessions() {
            ajax({
                url: base + '/sessions',
                method: 'GET',
                data: {status: statusFilter.value, keyword: keyword.value}
            }).done(function (response) {
                state.sessions = response.data && response.data.sessions ? response.data.sessions : [];
                renderSessions();
            });
        }

        function renderSessions() {
            if (!state.sessions.length) {
                list.innerHTML = '<div class="live-chat-empty">暂无会话</div>';
                return;
            }
            list.innerHTML = state.sessions.map(function (item) {
                var unread = Number(item.admin_unread_count || 0);
                return '<button type="button" class="live-chat-session ' + (Number(item.id) === state.activeId ? 'active' : '') + '" data-session-id="' + esc(item.id) + '">' +
                    '<div class="live-chat-session-head"><span class="live-chat-session-title">' + esc(item.username || item.session_no) + '</span><span class="live-chat-session-time">' + esc(shortTime(item.updated_at)) + '</span></div>' +
                    '<div class="live-chat-session-head"><span class="live-chat-status ' + esc(item.status) + '">' + statusText(item.status) + '</span>' + (unread ? '<span class="live-chat-badge">' + unread + '</span>' : '') + '</div>' +
                    '<div class="live-chat-session-msg">' + esc(item.last_message || '新会话') + '</div>' +
                    '</button>';
            }).join('');
        }

        function selectSession(id) {
            state.activeId = Number(id || 0);
            state.lastMessageId = 0;
            renderSessions();
            loadMessages(false);
        }

        function loadMessages(appendOnly) {
            if (!state.activeId) return;
            ajax({
                url: base + '/' + state.activeId + '/messages',
                method: 'GET',
                data: appendOnly ? {after_id: state.lastMessageId} : {}
            }).done(function (response) {
                var data = response.data || {};
                var session = data.session || {};
                var rows = data.messages || [];
                updateHeader(session);
                if (!appendOnly) messages.innerHTML = '';
                if (!rows.length && !appendOnly) {
                    messages.innerHTML = '<div class="live-chat-empty">暂无消息，等待用户发起咨询。</div>';
                } else if (rows.length) {
                    if (messages.querySelector('.live-chat-empty')) messages.innerHTML = '';
                    rows.forEach(function (item) {
                        state.lastMessageId = Math.max(state.lastMessageId, Number(item.id || 0));
                        messages.insertAdjacentHTML('beforeend', renderMessage(item));
                    });
                    messages.scrollTop = messages.scrollHeight;
                }
                loadSessions();
            });
        }

        function updateHeader(session) {
            title.textContent = (session.username || session.session_no || '实时会话');
            subtitle.textContent = (session.session_no || '') + (session.user_id ? ' · 用户ID ' + session.user_id : ' · 游客');
            textarea.disabled = false;
            sendButton.disabled = false;
            closeButton.hidden = session.status === 'closed';
            reopenButton.hidden = session.status !== 'closed';
        }

        function renderMessage(item) {
            return '<div class="live-chat-message ' + (item.is_admin ? 'admin' : 'user') + '">' +
                '<div class="live-chat-bubble"><p>' + esc(item.content) + '</p><small>' + (item.is_admin ? '客服' : '用户') + ' · ' + esc(item.created_at || '') + '</small></div>' +
                '</div>';
        }

        list.addEventListener('click', function (event) {
            var button = event.target.closest('[data-session-id]');
            if (button) selectSession(button.getAttribute('data-session-id'));
        });
        root.querySelector('[data-live-chat-refresh]').addEventListener('click', loadSessions);
        statusFilter.addEventListener('change', loadSessions);
        keyword.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                loadSessions();
            }
        });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            var content = textarea.value.trim();
            if (!state.activeId || !content) return;
            ajax({
                url: base + '/' + state.activeId + '/messages',
                method: 'POST',
                data: {content: content}
            }).done(function () {
                textarea.value = '';
                loadMessages(false);
            });
        });
        closeButton.addEventListener('click', function () {
            if (!state.activeId) return;
            ajax({url: base + '/' + state.activeId + '/close', method: 'POST'}).done(function () {
                loadMessages(false);
            });
        });
        reopenButton.addEventListener('click', function () {
            if (!state.activeId) return;
            ajax({url: base + '/' + state.activeId + '/reopen', method: 'POST'}).done(function () {
                loadMessages(false);
            });
        });

        function statusText(value) {
            return value === 'closed' ? '已关闭' : '进行中';
        }
        function shortTime(value) {
            return String(value || '').replace(/^\d{4}-/, '');
        }
        function esc(value) {
            return String(value == null ? '' : value).replace(/[&<>"']/g, function (char) {
                return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[char];
            });
        }

        loadSessions();
        window.setInterval(loadSessions, 5000);
        window.setInterval(function () { loadMessages(true); }, 2500);
    }());
</script>
