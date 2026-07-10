<?php

namespace App\Console\Commands;

use App\Models\GameRecord;
use App\Services\TgService;
use Illuminate\Console\Command;
use App\User;

class CrawGameRecord extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'CrawGameRecord';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '抓取游戏下注记录';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $tg = new TgService;
        $data = $tg->allRecords();
        if ($data['code'] == 200) {
            $record = $data['data'];
            foreach ($record as $k => $v) {
                $user = User::where('username',$v['username'])->first();
                $arr = [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'bet_id' => $v['bet_id'],
                    'bet_time' => $v['bet_time'],
                    'platform_type' => $v['platform_type'],
                    'game_type' => $v['game_type'],
                    'bet_amount' => $v['bet_amount'],
                    'valid_amount' => $v['valid_amount'],
                    'win_loss' => $v['win_loss'],
                    'status' => $v['status']
                ];
                GameRecord::create($arr);
            }
        }
    }
}
