<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePromotionChannelTables extends Migration
{
    public function up()
    {
        Schema::create('promotion_channel_items', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('module', 40)->index();
            $table->string('item_type', 60)->nullable()->index();
            $table->string('name', 191)->nullable()->index();
            $table->string('domain', 191)->nullable()->index();
            $table->string('owner', 191)->nullable()->index();
            $table->string('target', 1000)->nullable();
            $table->boolean('status')->default(true)->index();
            $table->unsignedInteger('position')->default(0)->index();
            $table->longText('data')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });

        Schema::create('promotion_channel_settings', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('module', 40)->index();
            $table->string('setting_key', 100);
            $table->longText('setting_value')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['module', 'setting_key'], 'promotion_module_setting_unique');
        });

        Schema::create('promotion_push_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('template_id')->nullable()->index();
            $table->string('title', 191);
            $table->text('content');
            $table->string('audience_type', 60)->index();
            $table->string('audience_value', 191)->nullable();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->string('status', 30)->default('queued')->index();
            $table->unsignedInteger('total_count')->default(0);
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedBigInteger('requested_by')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('promotion_event_records', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('link_id')->nullable()->index();
            $table->string('facebook_pixel_id', 191)->nullable()->index();
            $table->string('tiktok_pixel_id', 191)->nullable()->index();
            $table->boolean('from_facebook')->default(false)->index();
            $table->timestamp('registered_at')->nullable()->index();
            $table->string('registration_url', 1000)->nullable();
            $table->string('agent_account', 191)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('username', 191)->nullable()->index();
            $table->string('event', 100)->index();
            $table->timestamp('event_at')->nullable()->index();
            $table->decimal('amount', 18, 4)->default(0);
            $table->string('url', 1000)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('raw_record')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('promotion_event_records');
        Schema::dropIfExists('promotion_push_jobs');
        Schema::dropIfExists('promotion_channel_settings');
        Schema::dropIfExists('promotion_channel_items');
    }
}

return new CreatePromotionChannelTables();
