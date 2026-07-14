<?php

namespace App\Admin\Controllers;

use Dcat\Admin\Layout\Content;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PlayerOnlinePanelController extends Controller
{
    public function index(Content $content, Request $request)
    {
        $filters = [
            'keyword' => trim((string) $request->input('keyword', '')),
            'online_status' => trim((string) $request->input('online_status', '')),
            'role' => trim((string) $request->input('role', '')),
        ];

        $stats = $this->stats();
        $players = $this->players($filters);

        return $content
            ->title('在线人数面板')
            ->description('玩家管理 / 12660')
            ->body(view('admin.player-online-panel', compact('stats', 'players', 'filters'))->render());
    }

    private function stats(): array
    {
        $base = $this->memberBaseQuery();
        $onlineMembers = (clone $base)->where('isonline', 1)->where(function ($query) {
            $query->where('isagent', '<>', 1)->orWhereNull('isagent');
        })->count();
        $onlineAgents = (clone $base)->where('isonline', 1)->where('isagent', 1)->count();
        $onlineTotal = $onlineMembers + $onlineAgents;

        return [
            'onlineTotal' => $onlineTotal,
            'onlineMembers' => $onlineMembers,
            'onlineAgents' => $onlineAgents,
            'todayLogins' => $this->todayLoginUsers(),
            'active15Minutes' => (clone $base)->where('logintime', '>=', time() - 900)->count(),
            'totalPlayers' => (clone $base)->count(),
        ];
    }

    private function players(array $filters)
    {
        $query = $this->memberBaseQuery()
            ->select([
                'id',
                'username',
                'realname',
                'isagent',
                'status',
                'isonline',
                'balance',
                'mbalance',
                'lastip',
                'last_login_ip_address',
                'logintime',
                'loginsum',
                'updated_at',
            ]);

        if ($filters['keyword'] !== '') {
            $keyword = '%'.$filters['keyword'].'%';
            $query->where(function ($query) use ($keyword) {
                $query->where('username', 'like', $keyword)
                    ->orWhere('realname', 'like', $keyword)
                    ->orWhere('lastip', 'like', $keyword)
                    ->orWhere('last_login_ip_address', 'like', $keyword);
            });
        }

        if ($filters['online_status'] === 'online') {
            $query->where('isonline', 1);
        } elseif ($filters['online_status'] === 'offline') {
            $query->where(function ($query) {
                $query->where('isonline', '<>', 1)->orWhereNull('isonline');
            });
        }

        if ($filters['role'] === 'agent') {
            $query->where('isagent', 1);
        } elseif ($filters['role'] === 'member') {
            $query->where(function ($query) {
                $query->where('isagent', '<>', 1)->orWhereNull('isagent');
            });
        }

        return $query
            ->orderByDesc('isonline')
            ->orderByDesc('logintime')
            ->orderByDesc('id')
            ->limit(100)
            ->get()
            ->map(function ($player) {
                $player->online_label = (int) $player->isonline === 1 ? '在线' : '离线';
                $player->role_label = (int) $player->isagent === 1 ? '代理' : '会员';
                $player->status_label = (int) $player->status === 1 ? '正常' : '禁用';
                $player->last_login_at = $this->formatLoginTime($player->logintime);
                $player->last_login_ip = $player->lastip ?: ($player->last_login_ip_address ?: '-');

                return $player;
            });
    }

    private function memberBaseQuery()
    {
        $query = DB::table('users');

        if (Schema::hasColumn('users', 'isdel')) {
            $query->where(function ($query) {
                $query->where('isdel', 0)->orWhereNull('isdel');
            });
        }

        return $query;
    }

    private function todayLoginUsers(): int
    {
        if (!Schema::hasTable('user_operate_logs')) {
            return 0;
        }

        return (int) DB::table('user_operate_logs')
            ->where('type', 1)
            ->whereDate('created_at', date('Y-m-d'))
            ->distinct()
            ->count('user_id');
    }

    private function formatLoginTime($value): string
    {
        $timestamp = (int) $value;
        if ($timestamp <= 0) {
            return '-';
        }

        return date('Y-m-d H:i:s', $timestamp);
    }
}
