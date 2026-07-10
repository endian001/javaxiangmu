<?php
namespace App\Admin\Controllers;

use App\Admin\Forms\ClearForm;
use Dcat\Admin\Http\Controllers\AdminController;
use App\Admin\Forms\NavRedirectSetting;
use App\Admin\Forms\PaySetting;
use App\Admin\Forms\SiteSetting;
use App\Http\Controllers\Controller;
use Dcat\Admin\Widgets\Card;
use Dcat\Admin\Layout\Content;

class SystemConfigController extends AdminController
{
    public function index(Content $content)
    {
        return $content
            ->title('支付设置')
            ->body(new Card(new PaySetting()));
    }

    public function siteSetting(Content $content)
    {
        return $content
            ->title('网站设置')
            ->body(new Card(new SiteSetting()));
    }

    public function navRedirectSetting(Content $content)
    {
        return $content
            ->title('导航页设置')
            ->body(new Card(new NavRedirectSetting()));
    }

    public function clear(Content $content)
    {
        return $content->title('数据清理')->body(new Card(new ClearForm()));
    }
}
