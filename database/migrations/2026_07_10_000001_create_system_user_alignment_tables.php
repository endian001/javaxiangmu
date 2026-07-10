<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemUserAlignmentTables extends Migration
{
    public function up()
    {
        Schema::create('admin_user_profiles', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('admin_user_id')->unique();
            $table->string('brand', 100)->nullable();
            $table->text('subscribed_brands')->nullable();
            $table->boolean('google_auth_enabled')->default(false);
            $table->boolean('status')->default(true)->index();
            $table->timestamp('last_seen_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->timestamps();
        });

        Schema::create('admin_ip_whitelists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ip_address', 45)->index();
            $table->string('domain', 191)->nullable()->index();
            $table->unsignedInteger('quota')->default(1);
            $table->unsignedInteger('auto_cleanup_days')->default(0);
            $table->boolean('is_important')->default(false)->index();
            $table->boolean('status')->default(true)->index();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('last_modified_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['ip_address', 'domain'], 'admin_ip_domain_unique');
        });

        Schema::create('admin_tasks', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('task_no', 40)->unique();
            $table->string('task_type', 50)->index();
            $table->string('title', 191);
            $table->string('status', 20)->default('pending')->index();
            $table->text('payload')->nullable();
            $table->longText('result')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('admin_audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('admin_user_id')->nullable()->index();
            $table->string('admin_name', 120)->nullable()->index();
            $table->string('action', 100)->index();
            $table->string('module', 100)->index();
            $table->text('content');
            $table->string('ip_address', 45)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->longText('context')->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down()
    {
        Schema::dropIfExists('admin_audit_logs');
        Schema::dropIfExists('admin_tasks');
        Schema::dropIfExists('admin_ip_whitelists');
        Schema::dropIfExists('admin_user_profiles');
    }
}

return new CreateSystemUserAlignmentTables();
