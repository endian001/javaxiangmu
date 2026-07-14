<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMultiPlatformTrackingTables extends Migration
{
    public function up()
    {
        Schema::create('promotion_tracking_attributions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('attribution_key', 64)->unique();
            $table->string('browser_id', 100)->nullable()->index();
            $table->string('session_id', 100)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('username', 191)->nullable()->index();
            $table->string('agent_account', 191)->nullable()->index();
            $table->unsignedBigInteger('link_id')->nullable()->index();
            $table->string('landing_url', 1000)->nullable();
            $table->string('registration_url', 1000)->nullable();
            $table->string('referrer', 1000)->nullable();
            $table->string('ip_address', 64)->nullable()->index();
            $table->text('user_agent')->nullable();
            $table->longText('params_json')->nullable();
            $table->longText('click_ids_json')->nullable();
            $table->longText('platforms_json')->nullable();
            $table->timestamp('first_event_at')->nullable()->index();
            $table->timestamp('registered_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('promotion_tracking_conversions', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('event_id', 191)->unique();
            $table->unsignedBigInteger('attribution_id')->nullable()->index();
            $table->string('event_name', 100)->index();
            $table->string('standard_event', 100)->nullable()->index();
            $table->string('source', 60)->nullable()->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('username', 191)->nullable()->index();
            $table->unsignedBigInteger('recharge_id')->nullable()->index();
            $table->string('order_no', 191)->nullable()->index();
            $table->decimal('amount', 18, 4)->default(0);
            $table->string('currency', 20)->default('THB');
            $table->boolean('is_first_deposit')->default(false)->index();
            $table->timestamp('event_at')->nullable()->index();
            $table->longText('payload_json')->nullable();
            $table->timestamps();
        });

        Schema::create('promotion_tracking_postback_logs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('conversion_event_id')->nullable()->index();
            $table->unsignedBigInteger('attribution_id')->nullable()->index();
            $table->string('event_id', 191)->nullable()->index();
            $table->string('platform', 80)->index();
            $table->string('event_name', 100)->index();
            $table->string('platform_event_name', 120)->nullable()->index();
            $table->string('status', 30)->default('pending')->index();
            $table->string('skip_reason', 120)->nullable()->index();
            $table->string('request_method', 10)->nullable();
            $table->text('request_url')->nullable();
            $table->longText('request_headers')->nullable();
            $table->longText('request_payload')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->timestamp('sent_at')->nullable()->index();
            $table->timestamps();

            $table->index(['platform', 'status', 'created_at'], 'tracking_postback_platform_status_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('promotion_tracking_postback_logs');
        Schema::dropIfExists('promotion_tracking_conversions');
        Schema::dropIfExists('promotion_tracking_attributions');
    }
}

return new CreateMultiPlatformTrackingTables();
