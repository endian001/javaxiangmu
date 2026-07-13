<?php

namespace Tests\Unit\Operational;

use App\Services\TcgBusinessOperationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use stdClass;
use Tests\TestCase;

class TcgBusinessOperationServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.prefix' => '',
        ]);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('tcg_player_limit_rules');
        Schema::dropIfExists('tcg_user_game_restrictions');
        Schema::dropIfExists('tcg_activity_coupons');
        Schema::dropIfExists('tcg_activity_blacklists');

        parent::tearDown();
    }

    public function test_activity_blacklist_hit_matches_active_time_user_and_activity()
    {
        $now = Carbon::now();

        DB::table('tcg_activity_blacklists')->insert([
            [
                'username' => 'alice',
                'user_id' => null,
                'activity_id' => null,
                'reason' => 'global block',
                'starts_at' => $now->copy()->subDay(),
                'ends_at' => $now->copy()->addDay(),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'alice',
                'user_id' => null,
                'activity_id' => 88,
                'reason' => 'wrong activity',
                'starts_at' => $now->copy()->subDay(),
                'ends_at' => $now->copy()->addDay(),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'alice',
                'user_id' => null,
                'activity_id' => 77,
                'reason' => 'inactive',
                'starts_at' => $now->copy()->subDay(),
                'ends_at' => $now->copy()->addDay(),
                'status' => 'disabled',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $hit = (new TcgBusinessOperationService())->activityBlacklistHit($this->user(15, 'alice'), 77);

        $this->assertNotNull($hit);
        $this->assertSame('global block', $hit->reason);
        $this->assertSame('global block', (new TcgBusinessOperationService())->activityBlacklistMessage($hit));
        $this->assertSame('fallback', (new TcgBusinessOperationService())->activityBlacklistMessage(null, 'fallback'));
    }

    public function test_coupon_for_apply_matches_active_or_issued_unexpired_scope_and_user_then_can_mark_used()
    {
        $now = Carbon::now();

        DB::table('tcg_activity_coupons')->insert([
            [
                'coupon_code' => 'GOOD',
                'activity_id' => 77,
                'username' => 'alice',
                'amount' => 10,
                'expires_at' => $now->copy()->addDay(),
                'status' => 'issued',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'coupon_code' => 'GOOD',
                'activity_id' => 88,
                'username' => 'alice',
                'amount' => 20,
                'expires_at' => $now->copy()->addDay(),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'coupon_code' => 'EXPIRED',
                'activity_id' => 77,
                'username' => 'alice',
                'amount' => 30,
                'expires_at' => $now->copy()->subMinute(),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $service = new TcgBusinessOperationService();
        $coupon = $service->couponForApply('GOOD', $this->user(15, 'alice'), 77);

        $this->assertNotNull($coupon);
        $this->assertSame('GOOD', $coupon->coupon_code);
        $this->assertSame('issued', $coupon->status);
        $this->assertNull($service->couponForApply('EXPIRED', $this->user(15, 'alice'), 77));

        $this->assertTrue($service->markCouponUsed($coupon->id, $this->user(15, 'alice')));
        $this->assertSame('used', DB::table('tcg_activity_coupons')->where('id', $coupon->id)->value('status'));
    }

    public function test_coupon_for_apply_honors_optional_user_id_column_when_it_exists()
    {
        Schema::table('tcg_activity_coupons', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->after('username');
        });

        $now = Carbon::now();
        DB::table('tcg_activity_coupons')->insert([
            'coupon_code' => 'USER-SPECIFIC',
            'activity_id' => null,
            'username' => '',
            'user_id' => 15,
            'amount' => 10,
            'expires_at' => $now->copy()->addDay(),
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $service = new TcgBusinessOperationService();

        $this->assertNotNull($service->couponForApply('USER-SPECIFIC', $this->user(15, 'alice'), 77));
        $this->assertNull($service->couponForApply('USER-SPECIFIC', $this->user(16, 'alice'), 77));
    }

    public function test_game_restriction_hit_matches_user_and_supported_game_scopes()
    {
        $now = Carbon::now();

        DB::table('tcg_user_game_restrictions')->insert([
            [
                'username' => 'alice',
                'user_id' => 15,
                'game_scope' => 'SB:FOOTBALL',
                'restriction_type' => 'blocked',
                'starts_at' => $now->copy()->subDay(),
                'ends_at' => $now->copy()->addDay(),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'username' => 'bob',
                'user_id' => 16,
                'game_scope' => '*',
                'restriction_type' => 'blocked',
                'starts_at' => $now->copy()->subDay(),
                'ends_at' => $now->copy()->addDay(),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $service = new TcgBusinessOperationService();
        $hit = $service->gameRestrictionHit($this->user(15, 'alice'), 'SB', 'SPORT', 'FOOTBALL');

        $this->assertNotNull($hit);
        $this->assertSame('SB:FOOTBALL', $hit->game_scope);
        $this->assertNull($service->gameRestrictionHit($this->user(15, 'alice'), 'SB', 'SPORT', 'BASKETBALL'));
    }

    public function test_amount_exceeds_player_limit_returns_latest_matching_rule_only_when_over_max_bet()
    {
        $now = Carbon::now();

        DB::table('tcg_player_limit_rules')->insert([
            [
                'username' => 'alice',
                'user_id' => 15,
                'game_scope' => 'SB',
                'max_bet' => 100,
                'max_payout' => null,
                'starts_at' => $now->copy()->subDay(),
                'ends_at' => $now->copy()->addDay(),
                'status' => 'active',
                'created_at' => $now->copy()->subHour(),
                'updated_at' => $now->copy()->subHour(),
            ],
            [
                'username' => 'alice',
                'user_id' => 15,
                'game_scope' => '*',
                'max_bet' => 50,
                'max_payout' => null,
                'starts_at' => $now->copy()->subDay(),
                'ends_at' => $now->copy()->addDay(),
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);

        $service = new TcgBusinessOperationService();
        $limit = $service->playerLimitFor($this->user(15, 'alice'), 'SB', 'SPORT', 'FOOTBALL');

        $this->assertNotNull($limit);
        $this->assertSame('50.0000', $limit->max_bet);
        $this->assertNull($service->amountExceedsPlayerLimit($this->user(15, 'alice'), 50, 'SB', 'SPORT', 'FOOTBALL'));
        $this->assertSame($limit->id, $service->amountExceedsPlayerLimit($this->user(15, 'alice'), 51, 'SB', 'SPORT', 'FOOTBALL')->id);
    }

    private function createSchema()
    {
        Schema::create('tcg_activity_blacklists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 100)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('activity_id')->nullable()->index();
            $table->string('reason', 255)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('tcg_activity_coupons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('coupon_code', 100)->index();
            $table->unsignedBigInteger('activity_id')->nullable()->index();
            $table->string('username', 100)->nullable()->index();
            $table->decimal('amount', 14, 4)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->timestamp('used_at')->nullable();
            $table->unsignedBigInteger('used_by')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('tcg_user_game_restrictions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 100)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('game_scope', 150)->default('all');
            $table->string('restriction_type', 50)->default('blocked');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->text('remark')->nullable();
            $table->timestamps();
        });

        Schema::create('tcg_player_limit_rules', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username', 100)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('game_scope', 150)->default('all');
            $table->decimal('max_bet', 14, 4)->nullable();
            $table->decimal('max_payout', 14, 4)->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->string('status', 32)->default('active')->index();
            $table->text('remark')->nullable();
            $table->timestamps();
        });
    }

    private function user($id, $username)
    {
        $user = new stdClass();
        $user->id = $id;
        $user->username = $username;

        return $user;
    }
}
