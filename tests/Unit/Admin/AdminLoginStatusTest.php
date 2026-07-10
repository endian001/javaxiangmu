<?php

namespace Tests\Unit\Admin;

use App\Admin\Controllers\AuthController;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AdminLoginStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('admin_users', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username')->unique();
        });
        Schema::create('admin_user_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('admin_user_id')->unique();
            $table->boolean('status')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
        });
    }

    public function test_legacy_administrator_without_profile_remains_enabled()
    {
        DB::table('admin_users')->insert([
            'id' => 1,
            'username' => 'legacy-admin',
        ]);

        $controller = new TestableAdminAuthController();

        $this->assertTrue($controller->administratorEnabled('legacy-admin'));
    }

    public function test_disabled_administrator_is_rejected()
    {
        DB::table('admin_users')->insert([
            'id' => 2,
            'username' => 'disabled-admin',
        ]);
        DB::table('admin_user_profiles')->insert([
            'admin_user_id' => 2,
            'status' => 0,
        ]);

        $controller = new TestableAdminAuthController();

        $this->assertFalse($controller->administratorEnabled('disabled-admin'));
    }
}

class TestableAdminAuthController extends AuthController
{
    public function administratorEnabled($username)
    {
        return $this->administratorIsEnabled($username);
    }
}
