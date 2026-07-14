<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class LiveChatController extends Controller
{
    public function index(Content $content)
    {
        return $content
            ->title('在线客服')
            ->description('实时会话 / Live Chat')
            ->body(view('admin.live-chat')->render());
    }

    public function sessions(Request $request)
    {
        if (!$this->tablesReady()) {
            return $this->error('实时客服数据表未创建', 503);
        }

        $status = trim((string) $request->input('status', 'open'));
        $keyword = trim((string) $request->input('keyword', ''));
        $query = DB::table('live_chat_sessions')->whereNull('deleted_at');
        if ($status !== 'all') {
            $query->where('status', $status);
        }
        if ($keyword !== '') {
            $query->where(function ($builder) use ($keyword) {
                $builder->where('session_no', 'like', '%'.$keyword.'%')
                    ->orWhere('username', 'like', '%'.$keyword.'%')
                    ->orWhere('visitor_id', 'like', '%'.$keyword.'%')
                    ->orWhere('last_message', 'like', '%'.$keyword.'%');
            });
        }

        $rows = $query
            ->orderByDesc('admin_unread_count')
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get()
            ->map(function ($session) {
                return $this->formatSession($session);
            })
            ->values()
            ->all();

        return $this->success(['sessions' => $rows]);
    }

    public function messages(Request $request, $id)
    {
        if (!$this->tablesReady()) {
            return $this->error('实时客服数据表未创建', 503);
        }

        $session = DB::table('live_chat_sessions')
            ->whereNull('deleted_at')
            ->where('id', (int) $id)
            ->first();
        if (!$session) {
            return $this->error('会话不存在', 404);
        }

        DB::table('live_chat_sessions')->where('id', $session->id)->update([
            'admin_unread_count' => 0,
            'assigned_admin_id' => $this->currentAdminId(),
            'updated_at' => now(),
        ]);
        DB::table('live_chat_messages')
            ->where('session_id', $session->id)
            ->where('sender_type', 'user')
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $afterId = max(0, (int) $request->input('after_id', 0));
        $messages = DB::table('live_chat_messages')
            ->whereNull('deleted_at')
            ->where('session_id', $session->id)
            ->when($afterId > 0, function ($query) use ($afterId) {
                $query->where('id', '>', $afterId);
            })
            ->orderBy('id')
            ->limit(200)
            ->get()
            ->map(function ($message) {
                return $this->formatMessage($message);
            })
            ->values()
            ->all();

        $freshSession = DB::table('live_chat_sessions')->where('id', $session->id)->first();

        return $this->success([
            'session' => $this->formatSession($freshSession),
            'messages' => $messages,
        ]);
    }

    public function send(Request $request, $id)
    {
        if (!$this->tablesReady()) {
            return $this->error('实时客服数据表未创建', 503);
        }

        $session = DB::table('live_chat_sessions')
            ->whereNull('deleted_at')
            ->where('id', (int) $id)
            ->first();
        if (!$session) {
            return $this->error('会话不存在', 404);
        }

        $content = trim((string) $request->input('content', ''));
        if ($content === '') {
            return $this->error('请输入回复内容', 422);
        }

        $content = mb_substr($content, 0, 1000);
        $admin = Admin::user();
        $now = now();
        $messageId = DB::table('live_chat_messages')->insertGetId([
            'session_id' => $session->id,
            'user_id' => null,
            'admin_id' => $admin ? $admin->getKey() : null,
            'sender_type' => 'admin',
            'content' => $content,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('live_chat_sessions')->where('id', $session->id)->update([
            'status' => 'open',
            'assigned_admin_id' => $admin ? $admin->getKey() : null,
            'last_message' => $content,
            'last_message_at' => $now,
            'last_admin_message_at' => $now,
            'user_unread_count' => DB::raw('user_unread_count + 1'),
            'closed_at' => null,
            'updated_at' => $now,
        ]);

        $message = DB::table('live_chat_messages')->where('id', $messageId)->first();
        $freshSession = DB::table('live_chat_sessions')->where('id', $session->id)->first();

        return $this->success([
            'session' => $this->formatSession($freshSession),
            'message' => $this->formatMessage($message),
        ], '回复已发送');
    }

    public function close($id)
    {
        if (!$this->tablesReady()) {
            return $this->error('实时客服数据表未创建', 503);
        }

        DB::table('live_chat_sessions')
            ->whereNull('deleted_at')
            ->where('id', (int) $id)
            ->update([
                'status' => 'closed',
                'closed_at' => now(),
                'updated_at' => now(),
            ]);

        return $this->success([], '会话已关闭');
    }

    public function reopen($id)
    {
        if (!$this->tablesReady()) {
            return $this->error('实时客服数据表未创建', 503);
        }

        DB::table('live_chat_sessions')
            ->whereNull('deleted_at')
            ->where('id', (int) $id)
            ->update([
                'status' => 'open',
                'closed_at' => null,
                'updated_at' => now(),
            ]);

        return $this->success([], '会话已重新打开');
    }

    private function tablesReady()
    {
        return Schema::hasTable('live_chat_sessions') && Schema::hasTable('live_chat_messages');
    }

    private function formatSession($session)
    {
        return [
            'id' => (int) $session->id,
            'session_no' => (string) $session->session_no,
            'visitor_id' => (string) ($session->visitor_id ?? ''),
            'user_id' => (int) ($session->user_id ?? 0),
            'username' => (string) ($session->username ?? ''),
            'status' => (string) $session->status,
            'last_message' => (string) ($session->last_message ?? ''),
            'last_message_at' => (string) ($session->last_message_at ?? ''),
            'last_user_message_at' => (string) ($session->last_user_message_at ?? ''),
            'last_admin_message_at' => (string) ($session->last_admin_message_at ?? ''),
            'admin_unread_count' => (int) ($session->admin_unread_count ?? 0),
            'user_unread_count' => (int) ($session->user_unread_count ?? 0),
            'updated_at' => (string) ($session->updated_at ?? ''),
            'created_at' => (string) ($session->created_at ?? ''),
        ];
    }

    private function formatMessage($message)
    {
        return [
            'id' => (int) $message->id,
            'session_id' => (int) $message->session_id,
            'sender_type' => (string) $message->sender_type,
            'is_admin' => (string) $message->sender_type === 'admin',
            'content' => (string) $message->content,
            'created_at' => (string) ($message->created_at ?? ''),
        ];
    }

    private function currentAdminId()
    {
        $admin = Admin::user();

        return $admin ? (int) $admin->getKey() : null;
    }

    private function success(array $data = [], $message = 'success')
    {
        return response()->json(['status' => true, 'message' => $message, 'data' => $data]);
    }

    private function error($message, $status)
    {
        return response()->json(['status' => false, 'message' => $message], $status);
    }
}
