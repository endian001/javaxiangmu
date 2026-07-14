<?php

namespace Tests\Unit\Operational;

use PHPUnit\Framework\TestCase;

class GameSwitchSourceTest extends TestCase
{
    private function root()
    {
        return getenv('OPERATIONAL_PROJECT_ROOT') ?: dirname(__DIR__, 3);
    }

    public function test_member_game_entry_requires_public_and_enabled_game()
    {
        $source = file_get_contents($this->root().'/app/Http/Controllers/Member/MemberController.php');
        $method = $this->methodBody($source, 'game');

        $this->assertStringContainsString("where('is_top',1)", $method);
        $this->assertStringContainsString("where('site_state',1)", $method);
        $this->assertStringContainsString('noentergame', $method);
    }

    public function test_api_game_lists_filter_all_publish_switches()
    {
        $api = file_get_contents($this->root().'/app/Http/Controllers/Api/IndexController.php');
        $app = file_get_contents($this->root().'/app/Http/Controllers/Api/AppController.php');

        foreach ([
            $this->methodBody($api, 'getGameList'),
            $this->methodBody($api, 'getAllPlat'),
            $this->methodBody($api, 'getAllGameList'),
            $this->methodBody($api, 'gamelistBycode'),
            $this->methodBody($app, 'hall_list'),
        ] as $method) {
            $this->assertStringContainsString("where('is_top',1)", $method);
            $this->assertStringContainsString("where('site_state',1)", $method);
            $this->assertStringContainsString("where('app_state',1)", $method);
        }
    }

    public function test_web_game_category_pages_use_shared_visible_query()
    {
        $source = file_get_contents($this->root().'/app/Http/Controllers/Web/IndexController.php');

        $this->assertStringContainsString('function visibleGameQuery(', $source);
        foreach (['sport', 'realbet', 'joker', 'gaming', 'lottery', 'concise'] as $method) {
            $body = $this->methodBody($source, $method);
            $this->assertStringContainsString('visibleGameQuery()', $body);
        }
    }

    private function methodBody($source, $method)
    {
        $needle = 'function '.$method.'(';
        $start = strpos($source, $needle);
        $this->assertNotFalse($start, "Missing method {$method}");

        $open = strpos($source, '{', $start);
        $this->assertNotFalse($open, "Missing method body for {$method}");

        $depth = 0;
        $length = strlen($source);
        for ($i = $open; $i < $length; $i++) {
            if ($source[$i] === '{') {
                $depth++;
            } elseif ($source[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($source, $open, $i - $open + 1);
                }
            }
        }

        $this->fail("Unclosed method body for {$method}");
    }
}
