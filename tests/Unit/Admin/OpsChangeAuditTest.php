<?php

namespace Tests\Unit\Admin;

use App\Admin\Support\OpsChangeAudit;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OpsChangeAuditTest extends TestCase
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

        Schema::create('user_operate_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('user_id');
            $table->tinyInteger('type');
            $table->text('login_ua')->nullable();
            $table->string('login_ip', 100)->nullable();
            $table->string('ip_address', 100)->nullable();
            $table->string('desc', 255)->nullable();
            $table->string('info', 1000)->nullable();
            $table->timestamps();
        });

        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->string('admin_name', 120)->nullable();
            $table->string('action', 100);
            $table->string('module', 100);
            $table->text('content');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('context')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('user_operate_logs');

        parent::tearDown();
    }

    public function test_insert_writes_legacy_user_log_and_admin_audit_log()
    {
        OpsChangeAudit::insert('activity.config.update', 88, 'Invite Bonus', [
            'status' => [
                'label' => 'Status',
                'old' => 'disabled',
                'new' => 'enabled',
            ],
        ]);

        $this->assertSame(1, DB::table('user_operate_logs')->count());
        $this->assertSame(1, DB::table('admin_audit_logs')->count());

        $audit = DB::table('admin_audit_logs')->first();
        $this->assertSame('activity.config.update', $audit->action);
        $this->assertSame('activity', $audit->module);
        $this->assertStringContainsString('Invite Bonus', $audit->content);

        $context = json_decode($audit->context, true);
        $this->assertSame(88, $context['target_id']);
        $this->assertSame('disabled', $context['changes']['status']['old']);
        $this->assertSame('enabled', $context['changes']['status']['new']);
    }

    public function test_write_admin_audit_can_be_used_by_non_form_actions()
    {
        OpsChangeAudit::writeAdminAudit('agent.commission.settle', 'agent_commission', 'Settle commission for agent007', [
            'agent_id' => 7,
            'amount' => 188.8,
            'before_balance' => 100,
            'after_balance' => 288.8,
        ]);

        $this->assertSame(0, DB::table('user_operate_logs')->count());
        $this->assertSame(1, DB::table('admin_audit_logs')->count());

        $audit = DB::table('admin_audit_logs')->first();
        $this->assertSame('agent.commission.settle', $audit->action);
        $this->assertSame('agent_commission', $audit->module);
        $this->assertStringContainsString('agent007', $audit->content);

        $context = json_decode($audit->context, true);
        $this->assertSame(7, $context['agent_id']);
        $this->assertSame(188.8, $context['amount']);
    }
}
