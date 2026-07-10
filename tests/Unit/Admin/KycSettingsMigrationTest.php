<?php

namespace Tests\Unit\Admin;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class KycSettingsMigrationTest extends TestCase
{
    public function test_it_creates_kyc_storage_tables()
    {
        $path = database_path(
            'migrations/2026_07_10_000004_create_kyc_settings_tables.php'
        );
        $this->assertFileExists($path);

        require_once $path;
        (new \CreateKycSettingsTables())->up();

        $this->assertTrue(Schema::hasColumns('kyc_profile_fields', [
            'field_key',
            'default_label',
            'custom_label',
            'input_type',
            'category',
            'kyc_enabled',
            'frontend_visible',
            'required',
            'player_editable',
            'unique_value',
            'format_rule',
            'min_length',
            'max_length',
            'mask_mode',
            'options',
            'position',
        ]));
        $this->assertTrue(Schema::hasColumns('kyc_rule_groups', [
            'name',
            'is_default',
            'enabled',
            'review_mode',
            'force_enabled',
            'tag_internal',
            'tag_operation',
            'scenario_login',
            'scenario_deposit',
            'scenario_withdraw',
            'scenario_game',
            'require_id_type',
            'require_id_number',
            'require_withdraw_name',
            'require_document_images',
            'image_count',
            'image_titles',
        ]));
        $this->assertTrue(Schema::hasColumns('kyc_frontend_contents', [
            'platform',
            'language',
            'step',
            'title',
            'body',
            'button_text',
            'secondary_button_text',
            'background_image',
            'force_verify',
            'status',
        ]));
    }
}
