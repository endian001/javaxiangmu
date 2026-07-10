<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Api;
use App\Models\GameRecord;
use App\Models\Message;
use App\Models\Recharge;
use App\Models\TransferLog;
use App\Models\UserOperateLog;
use App\Models\Withdraw;
use App\Services\GamereportService;
use App\Services\TgService;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;  
use App\Models\SystemConfig;

class IndexController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $child = User::getChild($user->id);
        $list = User::whereIn('id',$child)->get();
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        $all_recharge = 0;
        $all_withdraw= 0;
        $all_valid_bet= 0;
        $all_win_loss= 0;
        foreach ($list as $k => $v) {
            $all_recharge += User::rechargeSum($v->id,$start,$end); //总存款
            $all_withdraw += User::withdrawSum($v->id,$start,$end); //总提款
            $all_valid_bet += User::vaildBetSum($v->id,$start,$end); //总有效投注
            $all_win_loss += User::totalfanhui($v->id,$start,$end); //总输赢
        }
        // 首页显示最新的6条消息（包括通知、活动和公告）
        $list = Message::orderBy('id', 'desc')->paginate(6);

        // 生成推广链接
        $pc_url = env('PC_URL')."/#/register?pid=".$user->id;
        $wap_url = env('WAP_URL')."/#/register?pid=".$user->id;
        
        // 生成推广二维码（使用自动适配的链接）
        $str = env('APP_URL')."/#/register?pid=".$user->id;
        $filename = public_path('uploads/agent/qrcode/'.$user->id.'.png');
        // 确保目录存在
        $directory = dirname($filename);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        // 生成二维码
        QrCode::encoding('UTF-8')->format('png')->size(500)->generate($str,$filename);

        return view('agent.index', compact('user','list','all_recharge','all_withdraw','all_valid_bet','all_win_loss','pc_url','wap_url'));
    }

    public function getuserdata(){
        $user = Auth::user();
        $ret = User::getBetDayDta($user->id,6);
        echo json_encode($ret);
    }

    public function notice()
    {
        // 只显示公告（type=3）
        $list = Message::where('type', 3)->orderBy('id', 'desc')->paginate(10);
        return view('agent.notice.notice', compact('list'));
    }
    public function message()
    {
        // 只显示站内信（type=1通知 和 type=2活动）
        $list = Message::whereIn('type', [1, 2])->orderBy('id', 'desc')->paginate(10);
        return view('agent.notice.notice', compact('list'));
    }
    public function noticeDetail($id)
    {
        $item = Message::find($id);
        return view('agent.notice.notice_detail', compact('item'));
    }

    /**
     * 图表
     *
     * @return void
     */
    public function chart()
    {
        return view('agent.report.chart');
    }

    /**
     * 今日概况
     *
     * @return void
     */
    public function todayData()
    {
        $user = Auth::user();
        // 下级会员数
        $child_member = User::getChildMember($user->id);
        $child_member_count = count($child_member);
        // 下级代理
        $child_agent = User::getChildAgent($user->id);
        $child_agent_count = count($child_agent);
        // 直属会员
        $directly_member_count = User::where('pid', $user->id)->where('isagent', 0)->count();
        // 直属代理数
        $directly_agent_count = User::where('pid', $user->id)->where('isagent', 1)->count();
        // 今日新增会员数
        $add_member_count = User::where('pid', $user->id)->whereDate('created_at', date('Y-m-d'))->count();
        // 今日总存款
        $all_child = User::getChild($user->id);
        $all_recharge = Recharge::whereIn('user_id', $all_child)->whereDate('created_at', date('Y-m-d'))->where('state', 2)->sum('amount');
        // 今日总提款
        $all_withdraw = Withdraw::whereIn('user_id', $all_child)->whereDate('created_at', date('Y-m-d'))->where('state', 2)->sum('amount');
        // 今日投注
        $all_bet = GameRecord::whereIn('user_id', $all_child)->whereDate('created_at', date('Y-m-d'))->sum('bet_amount');
        // 今日有效投注
        $all_valid_bet = GameRecord::whereIn('user_id', $all_child)->whereDate('created_at', date('Y-m-d'))->sum('valid_amount');
        // 今日输赢
        $win_loss =  GameRecord::whereIn('user_id', $all_child)->whereDate('created_at', date('Y-m-d'))->sum('win_loss');
        return view('agent.report.today_data',compact('child_member_count','child_agent_count','directly_member_count','directly_agent_count','add_member_count','all_recharge','all_withdraw','all_bet','all_valid_bet','win_loss'));
    }

    /**
     * 盈亏报表
     *
     * @return void
     */
    public function teamReportApi(Request $request)
    {
        $user = $this->apiTeamUser($request);
        if (!$user) {
            return $this->returnMsg(401, [], '认证失败');
        }

        $childIds = $this->childIdArray($user->id);
        $dates = $this->teamReportDateRange($request);
        $summary = $this->teamReportStats($childIds, $dates['start'], $dates['end']);
        $summary['teamMembers'] = count($childIds);
        $summary['directMembers'] = User::where('pid', $user->id)->where('isagent', 0)->count();
        $summary['directAgents'] = User::where('pid', $user->id)->where('isagent', 1)->count();
        $summary['recharge'] = $summary['rechargeTotal'];
        $summary['withdrawal'] = $summary['withdrawTotal'];
        $summary['validBet'] = $summary['validBetTotal'];
        $summary['gameRebate'] = $summary['rebateTotal'];
        $summary['gameWinLoss'] = $summary['winLossTotal'];
        $summary['activityBonus'] = 0;
        $summary['agentCommission'] = $summary['commissionSettled'];
        $summary['waitCommission'] = $summary['commissionPending'];
        $summary['total_commission'] = $summary['commissionSettled'] + $summary['commissionPending'];
        $summary['settled_commission'] = $summary['commissionSettled'];
        $summary['unsettled_commission'] = $summary['commissionPending'];

        if ($request->isMethod('get')) {
            return $this->returnMsg(200, $summary, '成功');
        }

        $pageSize = max(1, min(100, (int) $request->input('page_size', $request->input('limit', 10))));
        $members = User::whereIn('id', $childIds)
            ->orderBy('id', 'desc')
            ->paginate($pageSize);

        $rows = [];
        foreach ($members as $member) {
            $stats = $this->teamReportStats([$member->id], $dates['start'], $dates['end']);
            $rows[] = [
                'id' => $member->id,
                'username' => $member->username,
                'realname' => $member->realname,
                'isagent' => $member->isagent,
                'is_agent' => (int) $member->isagent,
                'created_at' => (string) $member->created_at,
                'status' => (int) $member->status,
                'balance' => (float) $member->balance,
                'money' => $stats['commissionSettled'],
                'yl_money' => $stats['winLossTotal'],
                'remark' => '团队报表',
                'fd_money' => $stats['rebateTotal'],
                'bet_amount' => $stats['validBetTotal'],
                'game_type_text' => '全部',
                'agent_member_rate' => $member->agent_member_rate ?? 0,
                'fanshuifee' => (float) ($member->fanshuifee ?: 0),
                'rechargeTotal' => $stats['rechargeTotal'],
                'withdrawTotal' => $stats['withdrawTotal'],
                'validBetTotal' => $stats['validBetTotal'],
                'winLossTotal' => $stats['winLossTotal'],
                'rebateTotal' => $stats['rebateTotal'],
            ];
        }

        return $this->returnMsg(200, [
            'current_page' => $members->currentPage(),
            'data' => $rows,
            'per_page' => $members->perPage(),
            'total' => $members->total(),
            'last_page' => $members->lastPage(),
            'totalPages' => $members->lastPage(),
            'summary' => $summary,
        ], '成功');
    }

    protected function apiBearerUser(Request $request)
    {
        $cached = $request->attributes->get('api_auth_user');
        if ($cached instanceof User) {
            return $cached;
        }

        $token = $request->header('Authorization', $request->header('authorization', ''));
        $token = trim(preg_replace('/^Bearer\s+/i', '', (string) $token));
        if ($token === '') {
            return null;
        }

        return User::where('api_token', $token)->first();
    }

    protected function apiTeamUser(Request $request)
    {
        $user = $this->apiBearerUser($request);
        return $this->isValidAgentUser($user) ? $user : null;
    }

    protected function requestAgentUser(Request $request)
    {
        $user = $this->apiTeamUser($request);
        if ($user) {
            return $user;
        }

        $authUser = Auth::user();
        return $this->isValidAgentUser($authUser) ? $authUser : null;
    }

    protected function isValidAgentUser($user)
    {
        if (!$user || (int) $user->isagent !== 1) {
            return false;
        }
        if (isset($user->status) && (int) $user->status <= 0) {
            return false;
        }
        if (isset($user->isdel) && (int) $user->isdel === 1) {
            return false;
        }
        if (isset($user->isblack) && (int) $user->isblack === 1) {
            return false;
        }

        return true;
    }

    protected function writeAgentOperateLog(User $agent, $desc, array $info = [])
    {
        try {
            UserOperateLog::insertLog(
                $agent->id,
                3,
                request()->header('User-Agent', ''),
                request()->ip(),
                request()->ip(),
                $desc,
                json_encode($info, JSON_UNESCAPED_UNICODE)
            );
        } catch (\Throwable $e) {
            Log::warning('agent operate log failed: '.$e->getMessage());
        }
    }

    protected function childIdArray($userId)
    {
        return collect(User::getChild($userId))
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function teamReportDateRange(Request $request)
    {
        $start = trim((string) $request->input('start_date', $request->input('start', $request->input('start_time', ''))));
        $end = trim((string) $request->input('end_date', $request->input('end', $request->input('end_time', ''))));
        if ($start === '' && $end === '' && $request->has('date')) {
            $days = [1 => 0, 2 => 7, 3 => 15, 4 => 30];
            $dateKey = (int) $request->input('date');
            if (array_key_exists($dateKey, $days)) {
                $from = $days[$dateKey] === 0 ? time() : time() - ($days[$dateKey] * 86400);
                $start = date('Y-m-d 00:00:00', $from);
                $end = date('Y-m-d 23:59:59');
            }
        }
        if ($start !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) {
            $start .= ' 00:00:00';
        }
        if ($end !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $end)) {
            $end .= ' 23:59:59';
        }

        return [
            'start' => $start,
            'end' => $end,
        ];
    }

    protected function teamReportStats(array $userIds, $start = '', $end = '')
    {
        if (empty($userIds)) {
            return [
                'rechargeTotal' => 0,
                'withdrawTotal' => 0,
                'betTotal' => 0,
                'validBetTotal' => 0,
                'winLossTotal' => 0,
                'rebateTotal' => 0,
                'commissionSettled' => 0,
                'commissionPending' => 0,
            ];
        }

        $recharge = Recharge::whereIn('user_id', $userIds)->where('state', 2);
        $withdraw = Withdraw::whereIn('user_id', $userIds)->where('state', 2);
        $records = GameRecord::whereIn('user_id', $userIds);
        $rebate = TransferLog::whereIn('user_id', $userIds)->where('transfer_type', 6);
        $commissionSettled = TransferLog::whereIn('user_id', $userIds)->where('transfer_type', 20)->where('state', 1);
        $commissionPending = TransferLog::whereIn('user_id', $userIds)->where('transfer_type', 20)->where('state', 2);

        foreach ([$recharge, $withdraw, $records, $rebate, $commissionSettled, $commissionPending] as $query) {
            if ($start !== '') {
                $query->where('created_at', '>=', $start);
            }
            if ($end !== '') {
                $query->where('created_at', '<=', $end);
            }
        }

        return [
            'rechargeTotal' => (float) $recharge->sum('amount'),
            'withdrawTotal' => (float) $withdraw->sum('amount'),
            'betTotal' => (float) $records->sum('bet_amount'),
            'validBetTotal' => (float) $records->sum('valid_amount'),
            'winLossTotal' => (float) $records->sum('win_loss'),
            'rebateTotal' => (float) $rebate->sum('real_money'),
            'commissionSettled' => (float) $commissionSettled->sum('money'),
            'commissionPending' => (float) $commissionPending->sum('money'),
        ];
    }

    public function teamFdInfoApi(Request $request)
    {
        $user = $this->apiTeamUser($request);
        if (!$user) {
            return $this->returnMsg(401, [], '认证失败');
        }

        $childIds = $this->childIdArray($user->id);
        $dates = $this->teamReportDateRange($request);
        $records = GameRecord::whereIn('user_id', $childIds);
        $rebates = TransferLog::whereIn('user_id', $childIds)->where('transfer_type', 6);
        $this->applyTeamDateRange($records, $dates['start'], $dates['end']);
        $this->applyTeamDateRange($rebates, $dates['start'], $dates['end']);

        $totalValidBet = (float) $records->sum('valid_amount');
        $totalFd = (float) $rebates->sum('real_money');
        $settledFd = (float) (clone $rebates)->where('state', 1)->sum('real_money');
        $unsettledFd = (float) (clone $rebates)->where('state', 2)->sum('real_money');
        $rate = $totalValidBet > 0 ? round($totalFd / $totalValidBet * 100, 2) : 0;

        return $this->returnMsg(200, [
            'totalFd' => $totalFd,
            'total_fd' => $totalFd,
            'fd_money' => $totalFd,
            'settled_fd' => $settledFd,
            'unsettled_fd' => $unsettledFd,
            'totalValidBet' => $totalValidBet,
            'total_valid_bet' => $totalValidBet,
            'validBet' => $totalValidBet,
            'avgFdRate' => $rate,
            'rate' => $rate,
        ], 'success');
    }

    public function teamFdListApi(Request $request)
    {
        $user = $this->apiTeamUser($request);
        if (!$user) {
            return $this->returnMsg(401, [], '认证失败');
        }

        $childIds = $this->childIdArray($user->id);
        $dates = $this->teamReportDateRange($request);
        $query = TransferLog::whereIn('user_id', $childIds)->where('transfer_type', 6)->orderBy('id', 'desc');
        $this->applyTeamDateRange($query, $dates['start'], $dates['end']);
        $pageSize = max(1, min(100, (int) $request->input('page_size', $request->input('limit', 10))));
        $list = $query->paginate($pageSize);
        $usernames = User::whereIn('id', collect($list->items())->pluck('user_id')->all())->pluck('username', 'id');

        $rows = [];
        foreach ($list as $item) {
            $validBet = (float) ($item->bet_money ?: 0);
            $amount = (float) ($item->real_money ?: $item->money);
            $rows[] = [
                'id' => $item->id,
                'date' => (string) $item->created_at,
                'created_at' => (string) $item->created_at,
                'user_id' => (int) $item->user_id,
                'username' => $usernames[$item->user_id] ?? '',
                'gameType' => $item->platform_type ?: ($item->api_type ?: 'all'),
                'platform_type' => $item->platform_type ?: ($item->api_type ?: ''),
                'validBet' => $validBet,
                'valid_bet' => $validBet,
                'amount' => $amount,
                'money' => $amount,
                'rate' => $validBet > 0 ? round($amount / $validBet * 100, 2) : 0,
                'status' => (int) $item->state,
            ];
        }

        return $this->returnMsg(200, [
            'current_page' => $list->currentPage(),
            'data' => $rows,
            'per_page' => $list->perPage(),
            'totalPages' => $list->lastPage(),
            'last_page' => $list->lastPage(),
            'total' => $list->total(),
        ], 'success');
    }

    public function teamSetFdApi(Request $request)
    {
        $user = $this->apiTeamUser($request);
        if (!$user) {
            return $this->returnMsg(401, [], '认证失败');
        }

        $rate = $request->input('rate', $request->input('fanshuifee', $request->input('fd_rate')));
        if (!is_numeric($rate) || $rate < 0 || $rate > 100) {
            return $this->returnMsg(400, [], 'invalid rebate rate');
        }
        $rate = round((float) $rate, 2);

        $memberId = (int) $request->input('user_id', $request->input('member_id', $request->input('id', 0)));
        $username = trim((string) $request->input('username', ''));

        $result = DB::transaction(function () use ($user, $rate, $memberId, $username) {
            $member = $memberId > 0 ? User::where('id', $memberId)->lockForUpdate()->first() : null;
            if (!$member && $username !== '') {
                $member = User::where('username', $username)->lockForUpdate()->first();
            }
            if (!$member || !in_array((int) $member->id, $this->childIdArray($user->id), true)) {
                return ['code' => 400, 'data' => [], 'message' => 'member not in team'];
            }

            $oldRate = (float) ($member->fanshuifee ?: 0);
            $member->fanshuifee = $rate;
            $member->save();

            $this->writeAgentOperateLog($user, 'agent set fd rate', [
                'member_id' => (int) $member->id,
                'member_username' => $member->username,
                'old_rate' => $oldRate,
                'new_rate' => $rate,
            ]);

            return [
                'code' => 200,
                'data' => [
                    'id' => (int) $member->id,
                    'username' => $member->username,
                    'rate' => (float) $member->fanshuifee,
                    'fanshuifee' => (float) $member->fanshuifee,
                ],
                'message' => 'success',
            ];
        }, 3);

        return $this->returnMsg($result['code'], $result['data'], $result['message']);
    }

    public function teamInviteListApi(Request $request)
    {
        $user = $this->apiTeamUser($request);
        if (!$user) {
            return $this->returnMsg(401, [], '认证失败');
        }

        return $this->returnMsg(200, $this->teamInviteSettings($user), 'success');
    }

    public function teamInviteUpdateApi(Request $request)
    {
        $user = $this->apiTeamUser($request);
        if (!$user) {
            return $this->returnMsg(401, [], '认证失败');
        }

        $settings = $this->teamInviteSettings($user);
        $settings['readonly'] = 1;

        return $this->returnMsg(200, $settings, 'read only');
    }

    public function teamCommissionListApi(Request $request)
    {
        $user = $this->apiTeamUser($request);
        if (!$user) {
            return $this->returnMsg(401, [], '认证失败');
        }

        $childIds = $this->childIdArray($user->id);
        $agentIds = User::whereIn('id', $childIds)
            ->where('isagent', 1)
            ->pluck('id')
            ->map(function ($id) {
                return (int) $id;
            })
            ->all();
        array_unshift($agentIds, (int) $user->id);
        $agentIds = array_values(array_unique($agentIds));
        $dates = $this->teamReportDateRange($request);
        $query = TransferLog::whereIn('user_id', $agentIds)->where('transfer_type', 20)->orderBy('id', 'desc');
        $this->applyTeamDateRange($query, $dates['start'], $dates['end']);
        $pageSize = max(1, min(100, (int) $request->input('page_size', $request->input('limit', 10))));
        $list = $query->paginate($pageSize);
        $usernames = User::whereIn('id', collect($list->items())->pluck('user_id')->all())->pluck('username', 'id');

        $rows = [];
        foreach ($list as $item) {
            $rows[] = [
                'id' => $item->id,
                'date' => (string) $item->created_at,
                'created_at' => (string) $item->created_at,
                'user_id' => (int) $item->user_id,
                'username' => $usernames[$item->user_id] ?? '',
                'agent_id' => (int) $item->user_id,
                'agent_username' => $usernames[$item->user_id] ?? '',
                'validBet' => (float) ($item->bet_money ?: 0),
                'valid_bet' => (float) ($item->bet_money ?: 0),
                'amount' => (float) ($item->money ?: $item->real_money),
                'money' => (float) ($item->money ?: $item->real_money),
                'real_money' => (float) ($item->real_money ?: $item->money),
                'remark' => (string) $item->remark,
                'status' => (int) $item->state,
                'status_text' => $item->state == 2 ? 'pending' : 'settled',
            ];
        }

        $summaryQuery = TransferLog::whereIn('user_id', $agentIds)->where('transfer_type', 20);
        $this->applyTeamDateRange($summaryQuery, $dates['start'], $dates['end']);
        $settled = (float) (clone $summaryQuery)->where('state', 1)->sum('money');
        $pending = (float) (clone $summaryQuery)->where('state', 2)->sum('money');

        return $this->returnMsg(200, [
            'current_page' => $list->currentPage(),
            'data' => $rows,
            'per_page' => $list->perPage(),
            'totalPages' => $list->lastPage(),
            'last_page' => $list->lastPage(),
            'total' => $list->total(),
            'summary' => [
                'total_commission' => $settled + $pending,
                'settled_commission' => $settled,
                'unsettled_commission' => $pending,
                'waitCommission' => $pending,
            ],
        ], 'success');
    }

    protected function applyTeamDateRange($query, $start = '', $end = '')
    {
        if ($start !== '') {
            $query->where('created_at', '>=', $start);
        }
        if ($end !== '') {
            $query->where('created_at', '<=', $end);
        }
    }

    protected function teamInviteSettings(User $user)
    {
        $defaultPrefix = $this->appPublicUrl();
        $pcUrl = $this->firstUsablePublicUrlFrom([SystemConfig::getValue('agent_pc_uri'), env('PC_URL'), $defaultPrefix]);
        $wapUrl = $this->firstUsablePublicUrlFrom([SystemConfig::getValue('agent_wap_uri'), env('WAP_URL'), $defaultPrefix]);
        $agentUriPre = $this->firstUsablePublicUrlFrom([SystemConfig::getValue('agent_uri_pre'), $wapUrl, $defaultPrefix]);
        $pcInviteUrl = $this->makeInviteUrl($pcUrl ?: $defaultPrefix, $user->id);
        $wapInviteUrl = $this->makeInviteUrl($wapUrl ?: $defaultPrefix, $user->id);

        return [
            'agent_uri_pre' => $agentUriPre,
            'agent_pc_uri' => $pcUrl,
            'agent_wap_uri' => $wapUrl,
            'agent_url' => $this->agentPublicUrl(),
            'pc_invite_url' => $pcInviteUrl,
            'wap_invite_url' => $wapInviteUrl,
            'pcInviteUrl' => $pcInviteUrl,
            'wapInviteUrl' => $wapInviteUrl,
            'invite_url' => $wapInviteUrl,
            'inviteUrl' => $wapInviteUrl,
            'invite_code' => (string) $user->id,
            'inviteCode' => (string) $user->id,
            'pid' => (int) $user->id,
        ];
    }

    protected function makeInviteUrl($prefix, $userId)
    {
        return $this->invitePublicUrl($prefix, $userId);
    }

    public function profit(Request $request)
    {
        $data = $request->all();
        $username = $data['username'] ?? '';

        $user = Auth::user();
        $child = User::getChild($user->id);
        //array_push($child,$user->id);
        
        if ($username) {
            $search_user = User::where('username',$username)->first();
            if (!$search_user) {
                return back()->with('opMsg','用户不存在');
            }
            if (!in_array($search_user->id,$child)) {
                return back()->with('opMsg','用户不在您的下级列表中');
            }
        }
        
        $list = User::whereIn('id',$child)->paginate(10);
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        foreach ($list as $k => $v) {
            $rechage_times = User::rechargeTimes($v->id,$start,$end); //充值次数
            $withdraw_times = User::withdrawTimes($v->id,$start,$end); //提现次数
            $all_recharge = User::rechargeSum($v->id,$start,$end); //总存款
            $all_withdraw = User::withdrawSum($v->id,$start,$end); //总提款
            $all_valid_bet = User::vaildBetSum($v->id,$start,$end); //总有效投注
            $all_win_loss = User::winLoss($v->id,$start,$end); //总输赢
            $list[$k]->rechage_times = $rechage_times;
            $list[$k]->withdraw_times = $withdraw_times;
            $list[$k]->all_recharge = $all_recharge;
            $list[$k]->all_withdraw = $all_withdraw;
            $list[$k]->all_valid_bet = $all_valid_bet;
            $list[$k]->all_win_loss = $all_win_loss;
        }
        return view('agent.report.profit',compact('list','start','end','username'));
    }


    /**
     * 佣金报表
     *
     * @return void
     */
    public function commission(Request $request)
    {
        $data = $request->all();
        $username = $data['username'] ?? '';
        $user = Auth::user();
        $child = User::getChild($user->id);
        array_push($child,$user->id);
        $lists = User::whereIn('id',$child)->get();
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        $rechage_times =0;
        $withdraw_times =0;
        $all_recharge =0;
        $all_withdraw =0;
        $all_valid_bet =0;
        $all_win_loss =0;
        $usersum =0;
        $agentsum =0;
        $all_fanshui = 0;
        $all_redpacket = 0;
        $all_valid_betsum = 0;
        $yongjinsum =0;
        $waityongjinsum = 0;
        foreach ($lists as $k => $v) {
            $rechage_times += User::rechargeTimes($v->id,$start,$end); //充值次数
            $withdraw_times += User::withdrawTimes($v->id,$start,$end); //提现次数
            $all_recharge += User::rechargeSum($v->id,$start,$end); //总存款
            $all_withdraw += User::withdrawSum($v->id,$start,$end); //总提款
            $all_valid_bet += User::vaildBetSum($v->id,$start,$end); //总有效投注
            $all_valid_betsum += User::vaildBetCount($v->id,$start,$end); //总有效投注


            $all_win_loss += User::winLoss($v->id,$start,$end); //总输赢
            $all_fanshui += User::totalfanhui($v->id,$start,$end); //总输赢
            $all_redpacket += User::redpacketSum($v->id,$start,$end); //总输赢

            $usersum += User::UserSum($v->id,$start,$end); //下级会员

            $agentsum += User::AgentSum($v->id,$start,$end); //下级代理

            $yongjinsum += User::Agentyongjin($v->id,$start,$end); //已结算佣金统计

            $waityongjinsum +=User::Agentyongjinwait($v->id,$start,$end); //未结算佣金统计

        }



        $list = array();
        $list[0]['username'] = $user->username;
        $list[0]['realname'] = $user->realname;
        $list[0]['isagent'] = $user->isagent;
        $list[0]['rechage_times'] = $rechage_times;
        $list[0]['withdraw_times'] = $withdraw_times;
        $list[0]['all_recharge'] = $all_recharge;
        $list[0]['all_withdraw'] = $all_withdraw;
        $list[0]['all_valid_bet'] = $all_valid_bet;
        $list[0]['all_win_loss'] = $all_win_loss;
        $list[0]['all_fanshui'] = $all_fanshui;
        $list[0]['all_redpacket'] = $all_redpacket;
        $list[0]['all_valid_betsum'] = $all_valid_betsum;

        // $list[0]['usersum'] = $usersum;
        // $list[0]['agentsum'] = $agentsum;
        // $list[0]['yongjinsum'] = $yongjinsum;
        // $list[0]['waityongjinsum'] = $waityongjinsum;
        // $list[0]['rechage_times'] = $rechage_times + User::rechargeTimes($user->id,$start,$end);
        // $list[0]['withdraw_times'] = $withdraw_times+ User::withdrawTimes($user->id,$start,$end);
        // $list[0]['all_recharge'] = $all_recharge+ User::rechargeSum($user->id,$start,$end);
        // $list[0]['all_withdraw'] = $all_withdraw+ User::withdrawSum($user->id,$start,$end);
        // $list[0]['all_valid_bet'] = $all_valid_bet+ User::vaildBetSum($user->id,$start,$end);
        // $list[0]['all_win_loss'] = $all_win_loss+ User::winLoss($user->id,$start,$end);
        // $list[0]['all_fanshui'] = $all_fanshui+ User::totalfanhui($user->id,$start,$end);
        // $list[0]['all_redpacket'] = $all_redpacket+ User::redpacketSum($user->id,$start,$end);
        // $list[0]['all_valid_betsum'] = $all_valid_betsum+ User::vaildBetCount($user->id,$start,$end);
        $list[0]['usersum'] = $usersum;
        $list[0]['agentsum'] = $agentsum;
        $list[0]['yongjinsum'] = $yongjinsum;
        $list[0]['waityongjinsum'] = $this->getwaityongjinsum($user->id);
        $list = self::arrayToObject($list);

        return view('agent.report.commission',compact('list','start','end','username'));
    }
    
    protected function getwaityongjinsum($user_id)
    {
        $id = $user_id;
        $money = 0;
        $settlementday = intval(SystemConfig::getValue('settlement'));
        $diffday = strtotime(date('Y-m-d'))-$settlementday*60*60*24;
        $val = User::where('isagent','=',1)->where('id','=',$id)->first();
        if ($val){
            $transfermoney = TransferLog::where("state",2)->where('user_id',$val->id)->where('transfer_type',20)->sum('money');
            $money = $transfermoney;

            // $child = User::getChild($val->id);
            // $list = User::whereIn('id',$child)->get();
            // $totalfanhui = 0;
            // $totalredpacketSum =0;
            // $totalRechargeredpacketSum =0;
            // foreach ($list as $k => $v) {
            //     //反水
            //     $totalfanhui += User::totalfanhui($v->id, date('Y-m-d', $diffday) . ' 00:00:00', date('Y-m-d', time()) . ' 23:59:59');
            //     //紅包
            //     $totalredpacketSum +=   User::redpacketSum($v->id, date('Y-m-d', $diffday) . ' 00:00:00', date('Y-m-d', time()) . ' 23:59:59');
            //     // 充值送红包
            //     $totalRechargeredpacketSum +=   User::RechargeredpacketSum($v->id, date('Y-m-d', $diffday) . ' 00:00:00', date('Y-m-d', time()) . ' 23:59:59');
            // }
            // $user = User::where('id',$val->id)->first();
            // $money =  $transfermoney -  $totalfanhui - $totalredpacketSum - $totalRechargeredpacketSum;
        }
        return $money > 0 ? $money : 0;
    }
    
    
    function arrayToObject($e){
        if( gettype($e)!='array' ) return;
        foreach($e as $k=>$v){
            if( gettype($v)=='array' || getType($v)=='object' )
                $e[$k]=(object)self::arrayToObject($v);
        }
        return (object)$e;
    }

    /**
     * 佣金报表
     *
     * @return void
     */
    public function subordinate(Request $request)
    {
        $data = $request->all();
        $username = $data['username'] ?? '';

        $user = Auth::user();
        $child = User::getChild($user->id);
        if ($username) {
            $search_user = User::where('username',$username)->first();
            if (!$search_user) {
                return back()->with('opMsg','用户不存在');
            }
            if (!in_array($search_user->id,$child->toArray())) {
                return back()->with('opMsg','用户不在您的下级列表中');
            }
        }
        $list = User::whereIn('id',$child)->where('isagent',1)->paginate(10);
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        foreach ($list as $k => $v) {

            $res = self::agentcommission($v->id,$start,$end);

            $list[$k]->rechage_times = $res['rechage_times'];
            $list[$k]->withdraw_times = $res['withdraw_times'];
            $list[$k]->all_recharge = $res['all_recharge'];
            $list[$k]->all_withdraw = $res['all_withdraw'];
            $list[$k]->all_valid_bet = $res['all_valid_bet'];
            $list[$k]->all_win_loss = $res['all_win_loss'];
            $list[$k]->all_fanshui = $res['all_fanshui'];
            $list[$k]->all_redpacket = $res['all_redpacket'];


            $list[$k]->usersum = User::UserSum($v->id,$start,$end);;
            $list[$k]->agentsum = User::AgentSum($v->id,$start,$end);
            // $list[$k]->yongjinsum = $res['yongjinsum'];
            $list[$k]->yongjinsum = $this->getwaityongjinsum($v->id);
        }
        return view('agent.report.subordinate',compact('list','start','end','username'));
    }



    /**
     * 佣金报表
     *
     * @return void
     */
    public function agentcommission($user_id,$start,$end)
    {

        $child = User::getChild($user_id);
        $lists = User::whereIn('id',$child)->get();
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        $rechage_times =0;
        $withdraw_times =0;
        $all_recharge =0;
        $all_withdraw =0;
        $all_valid_bet =0;
        $all_win_loss =0;
        $usersum =0;
        $agentsum =0;
        $all_fanshui=0;
        $all_redpacket = 0;
        foreach ($lists as $k => $v) {
            $rechage_times += User::rechargeTimes($v->id,$start,$end); //充值次数
            $withdraw_times += User::withdrawTimes($v->id,$start,$end); //提现次数
            $all_recharge += User::rechargeSum($v->id,$start,$end); //总存款
            $all_withdraw += User::withdrawSum($v->id,$start,$end); //总提款
            $all_valid_bet += User::vaildBetSum($v->id,$start,$end); //总有效投注
            $all_win_loss += User::winLoss($v->id,$start,$end); //总输赢

            $all_fanshui += User::totalfanhui($v->id,$start,$end); //总输赢
            $all_redpacket += User::redpacketSum($v->id,$start,$end); //总输赢
            //
            $usersum += User::UserSum($v->id,$start,$end); //下级会员

            $agentsum += User::AgentSum($v->id,$start,$end); //下级代理


        }


        $yongjinsum = User::Agentyongjin($user_id,$start,$end); //佣金统计
        $list = array();
        $list['rechage_times'] = $rechage_times;
        $list['withdraw_times'] = $withdraw_times;
        $list['all_recharge'] = $all_recharge;
        $list['all_withdraw'] = $all_withdraw;
        $list['all_valid_bet'] = $all_valid_bet;
        $list['all_win_loss'] = $all_win_loss;
        $list['all_fanshui'] = $all_fanshui;
        $list['all_redpacket'] = $all_redpacket;
        $list['yongjinsum'] = $yongjinsum;
        $list['usersum'] = $yongjinsum;
        $list['agentsum'] = $yongjinsum;


        return $list;
    }

    /**
     * 添加下级会员
     */
    public function addMember(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            if (strlen($data['username']) < 6) return response()->json(['code' => 400, 'message' => '用户名至少6位']);
            $user = User::where('username',$data['username'])->first();
            
            // 使用与中间件相同的方式获取当前用户
            $puser = $this->requestAgentUser($request);
            
            if (!$puser) return response()->json(['code' => 401, 'message' => '认证失败']);
            if ($user) return response()->json(['code' => 400, 'message' => '用户名已存在']);
            $is_agent = 1;
            $pp_user = User::where('id',$puser->pid)->first();
            if ($pp_user && $pp_user->allowagent == 0) $is_agent = 0;
            $arr = [
                'username' => $data['username'],
                'pid' => $puser->id,
                'password' => Hash::make($data['password']),
                'realname' => $data['realname'],
                'paypwd' => Hash::make('123456'),
                'vip' => 1,
                'isagent' => $is_agent
            ];

            // 不再调用第三方API接口，直接创建本地用户
            $createdUser = User::create($arr);
            $this->writeAgentOperateLog($puser, 'agent add member', [
                'member_id' => (int) $createdUser->id,
                'member_username' => $createdUser->username,
                'isagent' => (int) $createdUser->isagent,
            ]);

/*            if($puser->id){
                $puser = User::where('id',$puser->pid)->first();
                $Gamereport = new GamereportService();
                $data['uid'] = $puser->id;
                $data['pid'] = $puser->pid;
                $data['isagent'] = $puser->isagent;
                $data['recnum'] =  1;
                $Gamereport->add($data);
            }*/

             return response()->json(['code' => 200, 'message' => '添加成功']);
        }
        return view('agent.agent.add_member');
    }

    /**
     * 设置代理
     */
    public function setAgent(Request $request)
    {
        if ($request->isMethod('post')) {
            $data = $request->all();
            $user_id = $data['user_id'] ?? 0;
            if (!$user_id) return response()->json(['code' => 400, 'message' => '请选择用户']);
            
            $user = User::find($user_id);
            if (!$user) return response()->json(['code' => 400, 'message' => '用户不存在']);
            
            // 使用与中间件相同的方式获取当前用户
            $puser = $this->requestAgentUser($request);
            
            if (!$puser) return response()->json(['code' => 401, 'message' => '认证失败']);
            
            $child = User::getChild($puser->id);
            if (!in_array($user_id, $child)) return response()->json(['code' => 400, 'message' => '用户不在您的下级列表中']);
            
            // 设置为代理
            $oldIsAgent = (int) $user->isagent;
            $user->isagent = 1;
            $user->save();
            $this->writeAgentOperateLog($puser, 'agent set child agent', [
                'member_id' => (int) $user->id,
                'member_username' => $user->username,
                'old_isagent' => $oldIsAgent,
                'new_isagent' => 1,
            ]);
            
            return response()->json(['code' => 200, 'message' => '设置代理成功']);
        }
        return response()->json(['code' => 400, 'message' => '请求方法错误']);
    }

    /**
     * 获取团队成员列表
     */
    public function getChildList(Request $request)
    {
        $puser = $this->apiTeamUser($request);
        if (!$puser) {
            return response()->json(['code' => 401, 'message' => '认证失败']);
        }

        $page = (int) $request->input('page', 1);
        $perPage = max(1, min(100, (int) $request->input('page_size', $request->input('limit', 10))));
        $username = trim((string) $request->input('username', ''));
        $child = $this->childIdArray($puser->id);
        $query = User::whereIn('id', $child)->where('status', 1);

        if ($username !== '') {
            $query->where('username', 'like', '%' . $username . '%');
        }
        if ($request->has('isagent')) {
            $query->where('isagent', (int) $request->input('isagent'));
        }

        $list = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        $rows = [];
        foreach ($list as $v) {
            $rows[] = [
                'id' => (int) $v->id,
                'username' => (string) $v->username,
                'realname' => (string) $v->realname,
                'isagent' => (int) $v->isagent,
                'is_agent' => (int) $v->isagent,
                'status' => (int) $v->status,
                'balance' => (float) $v->balance,
                'created_at' => (string) $v->created_at,
                'pid' => (int) $v->pid,
                'is_direct' => (int) ($v->pid == $puser->id),
                'fanshuifee' => (float) $v->fanshuifee,
                'agent_member_rate' => (float) ($v->agent_member_rate ?: 0),
            ];
        }

        return response()->json([
            'code' => 200,
            'data' => [
                'current_page' => $list->currentPage(),
                'data' => $rows,
                'per_page' => $list->perPage(),
                'total' => $list->total(),
                'last_page' => $list->lastPage(),
                'totalPages' => $list->lastPage()
            ]
        ]);
    }

    /**
     * 获取团队业绩统计
     */
    public function getPerformance(Request $request)
    {
        $puser = $this->apiTeamUser($request);
        if (!$puser) {
            return response()->json(['code' => 401, 'message' => '认证失败']);
        }

        $child_member = User::getChildMember($puser->id);
        $totalMembers = count($child_member);
        $child_agent = User::getChildAgent($puser->id);
        $totalAgents = count($child_agent);
        $directMembers = User::where('pid', $puser->id)->where('isagent', 0)->count();
        $directAgents = User::where('pid', $puser->id)->where('isagent', 1)->count();
        $childIds = $this->childIdArray($puser->id);
        $stats = $this->teamReportStats($childIds);
        $activeMembers = empty($childIds) ? 0 : User::whereIn('id', $childIds)->where('status', 1)->count();

        return response()->json([
            'code' => 200,
            'data' => [
                'totalMembers' => $totalMembers,
                'totalAgents' => $totalAgents,
                'directMembers' => $directMembers,
                'directAgents' => $directAgents,
                'total_members' => $totalMembers,
                'total_agents' => $totalAgents,
                'direct_members' => $directMembers,
                'direct_agents' => $directAgents,
                'active_members' => $activeMembers,
                'total_commission' => $stats['commissionSettled'] + $stats['commissionPending'],
                'settled_commission' => $stats['commissionSettled'],
                'unsettled_commission' => $stats['commissionPending'],
                'rechargeTotal' => $stats['rechargeTotal'],
                'withdrawTotal' => $stats['withdrawTotal'],
                'validBetTotal' => $stats['validBetTotal'],
                'winLossTotal' => $stats['winLossTotal'],
                'rebateTotal' => $stats['rebateTotal']
            ]
        ]);
    }

    /**
     * 代理给下级会员充值
     */
    public function rechargeApi(Request $request)
    {
        return $this->safeTeamRechargeApi($request);
    }

    /**
     * 获取用户信息
     */
    protected function safeTeamRechargeApi(Request $request)
    {
        if (!$request->isMethod('post')) {
            return $this->returnMsg(400, [], 'invalid request method');
        }

        $user = $this->apiTeamUser($request);
        if (!$user) {
            return $this->returnMsg(401, [], 'authentication failed');
        }

        $childId = (int) $request->input('user_id', 0);
        $amount = $this->normalizeTeamRechargeAmount($request->input('amount'));
        $clientOrderNo = trim((string) $request->input('client_order_no', ''));

        if ($childId <= 0) {
            return $this->returnMsg(400, [], 'invalid member');
        }
        if ($amount === null) {
            return $this->returnMsg(400, [], 'invalid amount');
        }
        if (!$this->validTeamRechargeClientOrderNo($clientOrderNo)) {
            return $this->returnMsg(400, [], 'client_order_no is required');
        }

        try {
            $result = $this->performTeamRecharge($user, $childId, $amount, $clientOrderNo);
        } catch (\Throwable $e) {
            Log::error('team recharge failed: '.$e->getMessage(), [
                'agent_id' => (int) $user->id,
                'child_id' => $childId,
                'client_order_no' => $clientOrderNo,
            ]);
            return $this->returnMsg(500, [], 'team recharge failed');
        }

        return $this->returnMsg($result['code'], $result['data'], $result['message']);
    }

    protected function performTeamRecharge(User $user, $childId, $amount, $clientOrderNo)
    {
        $childId = (int) $childId;
        $amount = $this->normalizeTeamRechargeAmount($amount);
        $clientOrderNo = trim((string) $clientOrderNo);

        if ($childId <= 0) {
            return ['code' => 400, 'data' => [], 'message' => 'invalid member'];
        }
        if ($amount === null) {
            return ['code' => 400, 'data' => [], 'message' => 'invalid amount'];
        }
        if (!$this->validTeamRechargeClientOrderNo($clientOrderNo)) {
            return ['code' => 400, 'data' => [], 'message' => 'client_order_no is required'];
        }

        $outTradeNo = $this->teamRechargeOutTradeNo($user->id, $childId, $clientOrderNo);
        $existingResult = function (Recharge $existing) use ($childId, $amount) {
            if (abs((float) $existing->amount - $amount) > 0.001 || (int) $existing->user_id !== $childId || (int) $existing->state !== 2) {
                return [
                    'code' => 409,
                    'data' => [
                        'order_no' => $existing->order_no,
                        'out_trade_no' => $existing->out_trade_no,
                        'state' => (int) $existing->state,
                    ],
                    'message' => 'client_order_no was already used for another recharge',
                ];
            }

            return [
                'code' => 200,
                'data' => [
                    'order_no' => $existing->order_no,
                    'out_trade_no' => $existing->out_trade_no,
                    'amount' => (float) $existing->amount,
                    'duplicate' => 1,
                ],
                'message' => 'success',
            ];
        };

        return DB::transaction(function () use ($user, $childId, $amount, $clientOrderNo, $outTradeNo, $existingResult) {
            $existing = Recharge::where('out_trade_no', $outTradeNo)->lockForUpdate()->first();
            if ($existing) {
                return $existingResult($existing);
            }

            $agent = User::where('id', $user->id)->lockForUpdate()->first();
            $child = User::where('id', $childId)->lockForUpdate()->first();

            if (!$agent || !$child) {
                return ['code' => 400, 'data' => [], 'message' => 'member not found'];
            }

            $childList = $this->childIdArray($agent->id);
            if (!in_array($childId, $childList, true)) {
                return ['code' => 400, 'data' => [], 'message' => 'member not in team'];
            }

            $existingAfterLocks = Recharge::where('out_trade_no', $outTradeNo)->lockForUpdate()->first();
            if ($existingAfterLocks) {
                return $existingResult($existingAfterLocks);
            }

            if ((float) $agent->balance < $amount) {
                return ['code' => 400, 'data' => [], 'message' => 'insufficient balance'];
            }

            $agentBefore = (float) $agent->balance;
            $childBefore = (float) $child->balance;
            $orderNo = $this->makeTeamRechargeOrderNo($agent->id, $child->id);

            $agent->balance = round($agentBefore - $amount, 2);
            $agent->save();

            $child->balance = round($childBefore + $amount, 2);
            $child->save();

            $recharge = Recharge::create([
                'order_no' => $orderNo,
                'out_trade_no' => $outTradeNo,
                'user_id' => $child->id,
                'amount' => $amount,
                'cash_fee' => 0,
                'real_money' => $amount,
                'pay_way' => 11,
                'info' => 'agent recharge '.$agent->username.' client_order_no='.$clientOrderNo,
                'state' => 2,
            ]);

            $note = 'client_order_no='.$clientOrderNo.' recharge_order_no='.$recharge->order_no.' agent_id='.$agent->id.' child_id='.$child->id;
            TransferLog::create([
                'order_no' => $outTradeNo.'_A',
                'api_type' => 'agent',
                'user_id' => $agent->id,
                'transfer_type' => 4,
                'money' => -$amount,
                'cash_fee' => 0,
                'real_money' => $amount,
                'before_money' => $agentBefore,
                'after_money' => (float) $agent->balance,
                'state' => 1,
                'remark' => 'agent_recharge_debit',
                'reconcile_note' => $note,
            ]);
            TransferLog::create([
                'order_no' => $outTradeNo.'_M',
                'api_type' => 'agent',
                'user_id' => $child->id,
                'transfer_type' => 3,
                'money' => $amount,
                'cash_fee' => 0,
                'real_money' => $amount,
                'before_money' => $childBefore,
                'after_money' => (float) $child->balance,
                'state' => 1,
                'remark' => 'agent_recharge_credit',
                'reconcile_note' => $note,
            ]);

            $this->writeAgentOperateLog($agent, 'agent team recharge', [
                'member_id' => (int) $child->id,
                'member_username' => $child->username,
                'amount' => $amount,
                'order_no' => $recharge->order_no,
                'out_trade_no' => $recharge->out_trade_no,
                'client_order_no' => $clientOrderNo,
            ]);

            return [
                'code' => 200,
                'data' => [
                    'order_no' => $recharge->order_no,
                    'out_trade_no' => $recharge->out_trade_no,
                    'amount' => (float) $recharge->amount,
                    'duplicate' => 0,
                    'agent_balance' => (float) $agent->balance,
                    'member_balance' => (float) $child->balance,
                ],
                'message' => 'success',
            ];
        }, 3);
    }

    protected function normalizeTeamRechargeAmount($amount)
    {
        $amount = trim((string) $amount);
        if (!preg_match('/^\d+(\.\d{1,2})?$/', $amount)) {
            return null;
        }

        $amount = round((float) $amount, 2);
        if ($amount <= 0 || $amount > 99999999.99) {
            return null;
        }

        return $amount;
    }

    protected function validTeamRechargeClientOrderNo($clientOrderNo)
    {
        return is_string($clientOrderNo)
            && preg_match('/^[A-Za-z0-9._:-]{8,80}$/', $clientOrderNo);
    }

    protected function teamRechargeOutTradeNo($agentId, $childId, $clientOrderNo)
    {
        return 'AR'.$agentId.'_'.$childId.'_'.substr(hash('sha256', $clientOrderNo), 0, 32);
    }

    protected function makeTeamRechargeOrderNo($agentId, $childId)
    {
        return 'AR'.date('YmdHis').$agentId.$childId.random_int(100000, 999999);
    }

    public function getUserInfo(Request $request, $id)
    {
        $puser = $this->apiTeamUser($request);
        if (!$puser) {
            return response()->json(['code' => 401, 'message' => '认证失败']);
        }

        $id = (int) $id;
        if (!in_array($id, $this->childIdArray($puser->id), true)) {
            return response()->json(['code' => 400, 'message' => '用户不在您的下级列表中']);
        }

        $user = User::find($id);
        if (!$user) {
            return response()->json(['code' => 400, 'message' => '用户不存在']);
        }

        return response()->json([
            'code' => 200,
            'data' => [
                'id' => (int) $user->id,
                'username' => (string) $user->username,
                'realname' => (string) $user->realname,
                'isagent' => (int) $user->isagent,
                'is_agent' => (int) $user->isagent,
                'status' => (int) $user->status,
                'created_at' => (string) $user->created_at,
                'pid' => (int) $user->pid,
                'balance' => (float) $user->balance,
                'fanshuifee' => (float) ($user->fanshuifee ?: 0),
                'agent_member_rate' => (float) ($user->agent_member_rate ?: 0),
            ]
        ]);
    }

    /**
     * 获取会员下注记录
     */
    public function getMemberBetRecord(Request $request, $id)
    {
        // 使用与中间件相同的方式获取当前用户
        $puser = $this->apiTeamUser($request);
        
        if (!$puser) return response()->json(['code' => 401, 'message' => '认证失败']);
        
        // 检查用户是否在代理的下级列表中
        $childList = User::getChild($puser->id);
        if (!in_array($id, $childList)) return response()->json(['code' => 400, 'message' => '用户不在您的下级列表中']);
        
        $page = $request->input('page', 1);
        $start = $request->input('start', '');
        $end = $request->input('end', '');
        $perPage = 10;
        
        $query = GameRecord::where('user_id', $id);
        
        if ($start) {
            $query->where('created_at', '>=', $start . ' 00:00:00');
        }
        if ($end) {
            $query->where('created_at', '<=', $end . ' 23:59:59');
        }
        
        $list = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'code' => 200,
            'data' => [
                'data' => $list->items(),
                'totalPages' => $list->lastPage()
            ]
        ]);
    }

    /**
     * 获取会员充值记录
     */
    public function getMemberRechargeRecord(Request $request, $id)
    {
        // 使用与中间件相同的方式获取当前用户
        $puser = $this->apiTeamUser($request);
        
        if (!$puser) return response()->json(['code' => 401, 'message' => '认证失败']);
        
        // 检查用户是否在代理的下级列表中
        $childList = User::getChild($puser->id);
        if (!in_array($id, $childList)) return response()->json(['code' => 400, 'message' => '用户不在您的下级列表中']);
        
        $page = $request->input('page', 1);
        $start = $request->input('start', '');
        $end = $request->input('end', '');
        $perPage = 10;
        
        $query = Recharge::where('user_id', $id);
        
        if ($start) {
            $query->where('created_at', '>=', $start . ' 00:00:00');
        }
        if ($end) {
            $query->where('created_at', '<=', $end . ' 23:59:59');
        }
        
        $list = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'code' => 200,
            'data' => [
                'data' => $list->items(),
                'totalPages' => $list->lastPage()
            ]
        ]);
    }

    /**
     * 获取会员提现记录
     */
    public function getMemberWithdrawRecord(Request $request, $id)
    {
        // 使用与中间件相同的方式获取当前用户
        $puser = $this->apiTeamUser($request);
        
        if (!$puser) return response()->json(['code' => 401, 'message' => '认证失败']);
        
        // 检查用户是否在代理的下级列表中
        $childList = User::getChild($puser->id);
        if (!in_array($id, $childList)) return response()->json(['code' => 400, 'message' => '用户不在您的下级列表中']);
        
        $page = $request->input('page', 1);
        $start = $request->input('start', '');
        $end = $request->input('end', '');
        $perPage = 10;
        
        $query = Withdraw::where('user_id', $id);
        
        if ($start) {
            $query->where('created_at', '>=', $start . ' 00:00:00');
        }
        if ($end) {
            $query->where('created_at', '<=', $end . ' 23:59:59');
        }
        
        $list = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'code' => 200,
            'data' => [
                'data' => $list->items(),
                'totalPages' => $list->lastPage()
            ]
        ]);
    }

    /**
     * 获取会员盈亏记录
     */
    public function getMemberProfitRecord(Request $request, $id)
    {
        // 使用与中间件相同的方式获取当前用户
        $puser = $this->apiTeamUser($request);
        
        if (!$puser) return response()->json(['code' => 401, 'message' => '认证失败']);
        
        // 检查用户是否在代理的下级列表中
        $childList = User::getChild($puser->id);
        if (!in_array($id, $childList)) return response()->json(['code' => 400, 'message' => '用户不在您的下级列表中']);
        
        $page = $request->input('page', 1);
        $start = $request->input('start', '');
        $end = $request->input('end', '');
        $perPage = 10;
        
        // 计算总充值
        $totalRecharge = Recharge::where('user_id', $id)->where('state', 2)->sum('amount');
        // 计算总提现
        $totalWithdraw = Withdraw::where('user_id', $id)->where('state', 2)->sum('amount');
        // 计算总投注
        $totalBet = GameRecord::where('user_id', $id)->sum('bet_amount');
        // 计算总盈亏
        $totalProfit = GameRecord::where('user_id', $id)->sum('win_loss');
        
        // 获取每日盈亏记录
        $query = GameRecord::select(DB::raw('DATE(created_at) as date, SUM(bet_amount) as bet_amount, SUM(win_loss) as win_loss'))
            ->where('user_id', $id)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date', 'desc');
        
        if ($start) {
            $query->where('created_at', '>=', $start . ' 00:00:00');
        }
        if ($end) {
            $query->where('created_at', '<=', $end . ' 23:59:59');
        }
        
        $list = $query->paginate($perPage, ['*'], 'page', $page);
        
        return response()->json([
            'code' => 200,
            'data' => [
                'data' => $list->items(),
                'totalPages' => $list->lastPage(),
                'summary' => [
                    'totalRecharge' => $totalRecharge,
                    'totalWithdraw' => $totalWithdraw,
                    'totalBet' => $totalBet,
                    'totalProfit' => $totalProfit
                ]
            ]
        ]);
    }

    /**
     * 会员列表
     *
     * @param Request $request
     * @return void
     */
    public function memberList(Request $request)
    {
        $user = Auth::user();
        $username = $request->input('username') ?? '';
        $child = User::getChild($user->id);
        $list = User::whereIn('id',$child)->where('status',1)
            ->when($username,function ($query) use ($username){
                return $query->where('username',$username);
            })->paginate(10);
        foreach ($list as $k =>$v) {
            $parent = User::find($v->pid);
            $list[$k]->parent = $parent ? $parent->username : '';
            $list[$k]->is_direct = $v['pid'] == $user->id ? 1 : 0;
        }
        return view('agent.agent.member',compact('list','user'));
    }

    /**
     * 下注记录
     *
     * @param Request $request
     * @return void
     */
    public function betLog(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        $username = $request->input('username') ?? '';
        $child = User::getChild($user->id);
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        $list = GameRecord::whereIn('user_id',$child)
            ->when($username,function ($query) use ($username){
                return $query->where('username',$username);
            })->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->orderBy('id','desc')->paginate(10);
        return view('agent.agent.bet_log',compact('list'));
    }

    /**
     * 充值记录
     *
     * @param Request $request
     * @return void
     */
    public function rechargeLog(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        $username = $request->input('username') ?? '';
        $user_id = User::where('username',$username)->value('id') ?? '';
        $child = User::getChild($user->id);
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        $list = Recharge::whereIn('user_id',$child)
            ->when($user_id,function ($query) use ($user_id){
                return $query->where('user_id',$user_id);
            })->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->orderBy('id','desc')->paginate(10);
        return view('agent.agent.recharge_log',compact('list'));
    }

    /**
     * 提现记录
     *
     * @param Request $request
     * @return void
     */
    public function withdrawLog(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        $username = $request->input('username') ?? '';
        $user_id = User::where('username',$username)->value('id') ?? '';
        $child = User::getChild($user->id);
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        $list = Withdraw::whereIn('user_id',$child)
            ->when($user_id,function ($query) use ($user_id){
                return $query->where('user_id',$user_id);
            })->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->orderBy('id','desc')->paginate(10);
        return view('agent.agent.recharge_log',compact('list'));
    }

    /**
     * 转账记录
     *
     * @param Request $request
     * @return void
     */
    public function transferLog(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        $username = $request->input('username') ?? '';
        $user_id = User::where('username',$username)->value('id') ?? '';
        $child = User::getChild($user->id);
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        $list = TransferLog::whereIn('user_id',$child)->whereIn('transfer_type',[0,1])
            ->when($user_id,function ($query) use ($user_id){
                return $query->where('user_id',$user_id);
            })->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->orderBy('id','desc')->paginate(10);
        return view('agent.agent.transfer_log',compact('list'));
    }


    /**
     * 提现记录
     *
     * @param Request $request
     * @return void
     */
    public function releasewaterLog(Request $request)
    {
        $data = $request->all();
        $user = Auth::user();
        $username = $request->input('username') ?? '';
        $user_id = User::where('username',$username)->value('id') ?? '';
        $child = User::getChild($user->id);
        $start = $data['start'] ?? '';
        $end = $data['end'] ?? '';
        $list = TransferLog::whereIn('user_id',$child)->where('transfer_type',6)
            ->when($user_id,function ($query) use ($user_id){
                return $query->where('user_id',$user_id);
            })->when($start, function ($query) use ($start) {
                $start = date('Y-m-d 00:00:00', strtotime($start));
                return $query->where('created_at', '>', $start);
            })->when($end, function ($query) use ($end) {
                $end = date('Y-m-d 23:59:59', strtotime($end));
                return $query->where('created_at', '<=', $end);
            })->orderBy('id','desc')->paginate(10);
        return view('agent.agent.releasewater_log',compact('list'));
    }
    
    public function generateQrcode()
    {
        $user = Auth::user();
        $str = env('PC_URL')."/#/register?pid=".$user->id;
        // $folder = '/uploads/agent/qrcode';
        // if (!is_dir($folder)) mkdir($folder,0777,true);
        // $filename = $folder.'/'.$user->id.'.png';
        $filename = public_path('uploads/agent/qrcode/'.$user->id.'.png');
        // if (!file_exists($filename)) {
            QrCode::encoding('UTF-8')->format('png')->size(500)->generate($str,$filename); 
        // }
        return response()->download($filename,uniqid().'.png');
        
    }
    
    //下级充值
    public function recharge(Request $request)
    {
        if ($request->isMethod('post')) {
            $user = Auth::user();
            $childId = (int) $request->input('user_id', 0);
            $amount = $this->normalizeTeamRechargeAmount($request->input('amount'));
            $clientOrderNo = trim((string) $request->input('client_order_no', ''));

            if (!$user) return back()->with('opMsg', '认证失败');
            if ($childId <= 0) return back()->with('opMsg', '用户不存在');
            if ($amount === null) return back()->with('opMsg', '请输入正确的金额');
            if (!$this->validTeamRechargeClientOrderNo($clientOrderNo)) return back()->with('opMsg', '充值订单号无效，请刷新后重试');

            try {
                $result = $this->performTeamRecharge($user, $childId, $amount, $clientOrderNo);
            } catch (\Throwable $e) {
                return back()->with('opMsg', '充值失败');
            }

            if ((int) $result['code'] === 200) {
                return redirect('/memberlist')->with('opMsg', '充值成功');
            }

            return back()->with('opMsg', $result['message'] ?? '充值失败');
        }
        $user_id = $request->input('user_id');
        $client_order_no = 'web_agent_'.(Auth::id() ?: 0).'_'.((int) $user_id).'_'.date('YmdHis').'_'.random_int(100000, 999999);
        return view('agent.agent.recharge',compact('user_id','client_order_no'));
    }
}
