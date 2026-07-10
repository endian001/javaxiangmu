<?php

use App\Admin\Services\KycSettingsService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateKycSettingsTables extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('kyc_profile_fields')) {
            Schema::create('kyc_profile_fields', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('field_key', 80)->unique();
                $table->string('default_label', 191);
                $table->string('custom_label', 191)->nullable();
                $table->string('input_type', 30)->default('input');
                $table->string('category', 30)->default('identity');
                $table->boolean('kyc_enabled')->default(false);
                $table->boolean('frontend_visible')->default(false);
                $table->boolean('required')->default(false);
                $table->boolean('player_editable')->default(false);
                $table->boolean('unique_value')->default(false);
                $table->string('format_rule', 50)->default('any');
                $table->unsignedSmallInteger('min_length')->default(0);
                $table->unsignedSmallInteger('max_length')->default(255);
                $table->string('mask_mode', 20)->default('masked');
                $table->text('options')->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->boolean('is_system')->default(true);
                $table->boolean('status')->default(true);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kyc_rule_groups')) {
            Schema::create('kyc_rule_groups', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name', 100);
                $table->boolean('is_default')->default(false);
                $table->boolean('enabled')->default(false);
                $table->string('review_mode', 20)->default('manual');
                $table->boolean('force_enabled')->default(false);
                $table->boolean('tag_internal')->default(false);
                $table->boolean('tag_operation')->default(false);
                $table->boolean('scenario_login')->default(false);
                $table->boolean('scenario_deposit')->default(false);
                $table->boolean('scenario_withdraw')->default(false);
                $table->boolean('scenario_game')->default(false);
                $table->boolean('require_id_type')->default(false);
                $table->boolean('require_id_number')->default(false);
                $table->boolean('require_withdraw_name')->default(false);
                $table->boolean('require_document_images')->default(true);
                $table->unsignedTinyInteger('image_count')->default(6);
                $table->text('image_titles')->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('kyc_frontend_contents')) {
            Schema::create('kyc_frontend_contents', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('platform', 20)->default('mobile');
                $table->string('language', 10)->default('EN');
                $table->unsignedTinyInteger('step');
                $table->string('title', 191)->nullable();
                $table->text('body')->nullable();
                $table->string('button_text', 191)->nullable();
                $table->string('secondary_button_text', 191)->nullable();
                $table->string('background_image', 1000)->nullable();
                $table->boolean('force_verify')->default(false);
                $table->boolean('status')->default(true);
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
                $table->unique(
                    ['platform', 'language', 'step'],
                    'kyc_content_platform_language_step_unique'
                );
            });
        }

        $this->seedDefaults();
    }

    public function down()
    {
        Schema::dropIfExists('kyc_frontend_contents');
        Schema::dropIfExists('kyc_rule_groups');
        Schema::dropIfExists('kyc_profile_fields');
    }

    private function seedDefaults()
    {
        $now = date('Y-m-d H:i:s');
        if (DB::table('kyc_profile_fields')->count() === 0) {
            $service = new KycSettingsService();
            $rows = [];
            foreach ($service->defaultFields() as $field) {
                $field['options'] = json_encode(
                    $field['options'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                );
                $field['is_system'] = 1;
                $field['created_at'] = $now;
                $field['updated_at'] = $now;
                $rows[] = $field;
            }
            DB::table('kyc_profile_fields')->insert($rows);
        }

        if (DB::table('kyc_rule_groups')->count() === 0) {
            DB::table('kyc_rule_groups')->insert([
                'name' => '默认',
                'is_default' => 1,
                'enabled' => 0,
                'review_mode' => 'manual',
                'force_enabled' => 0,
                'tag_internal' => 0,
                'tag_operation' => 0,
                'scenario_login' => 0,
                'scenario_deposit' => 0,
                'scenario_withdraw' => 0,
                'scenario_game' => 0,
                'require_id_type' => 0,
                'require_id_number' => 0,
                'require_withdraw_name' => 0,
                'require_document_images' => 1,
                'image_count' => 6,
                'image_titles' => json_encode(
                    ['front', 'back', 'third', 'fourth', 'fifth', 'sixth'],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ),
                'position' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        if (DB::table('kyc_frontend_contents')->count() === 0) {
            $contents = [
                [
                    'step' => 1,
                    'title' => 'Verify Your Identity',
                    'body' => 'Dear user, your identity verification is not yet complete. Please submit the relevant information to complete the identity verification process.',
                    'button_text' => 'Verify Now',
                    'secondary_button_text' => 'Not verified yet',
                    'force_verify' => 1,
                ],
                [
                    'step' => 2,
                    'title' => 'Identity Information',
                    'body' => 'Please complete the required identity information.',
                    'button_text' => 'Next',
                    'secondary_button_text' => 'Back',
                    'force_verify' => 0,
                ],
                [
                    'step' => 3,
                    'title' => 'Submit Documents',
                    'body' => 'Upload clear identity documents and confirm the submitted information.',
                    'button_text' => 'Submit',
                    'secondary_button_text' => 'Back',
                    'force_verify' => 0,
                ],
                [
                    'step' => 4,
                    'title' => 'Pending Review',
                    'body' => 'Your identity verification is under review. Please wait for the result.',
                    'button_text' => 'Confirm',
                    'secondary_button_text' => '',
                    'force_verify' => 0,
                ],
            ];
            foreach ($contents as &$content) {
                $content['platform'] = 'mobile';
                $content['language'] = 'EN';
                $content['background_image'] = null;
                $content['status'] = 1;
                $content['created_at'] = $now;
                $content['updated_at'] = $now;
            }
            unset($content);
            DB::table('kyc_frontend_contents')->insert($contents);
        }
    }
}
