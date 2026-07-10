<?php

namespace App\Admin\Forms;

use App\Models\SystemConfig;
use Dcat\Admin\Widgets\Form;

class NavRedirectSetting extends Form
{
    const CONFIG_PATH_KEY = 'nav_redirect_config_path';

    protected $keys = [
        'nav_redirect_title',
        'nav_redirect_subtitle',
        'nav_redirect_final_home',
        'nav_redirect_auto_redirect',
        'nav_redirect_delay_seconds',
        'nav_redirect_domains',
    ];

    public function handle(array $input)
    {
        $data = $this->normalizedInput($input);

        foreach ($data as $key => $value) {
            SystemConfig::updateOrCreate(['key' => $key], ['key' => $key, 'value' => $value]);
        }

        $this->writeNavigationConfig($data);

        return $this
            ->response()
            ->success('导航页配置已保存')
            ->refresh();
    }

    public function form()
    {
        $this->text('nav_redirect_title', '导航页标题')->required();
        $this->text('nav_redirect_subtitle', '导航页副标题');
        $this->text('nav_redirect_final_home', '最终跳转首页')
            ->required()
            ->help('例如：https://wakuang.fakaw.eu.cc/#/');
        $this->radio('nav_redirect_auto_redirect', '自动跳转')
            ->options([1 => '开启', 0 => '关闭'])
            ->default(1);
        $this->number('nav_redirect_delay_seconds', '跳转倒计时秒数')
            ->default(3)
            ->min(0)
            ->max(30);
        $this->textarea('nav_redirect_domains', '跳转域名列表')
            ->rows(6)
            ->help('一行一个，格式：名称|URL。例：跳转线路 01|https://tiaozhuan01.fakaw.eu.cc/');

        $this->html($this->helpHtml());
    }

    public function default()
    {
        $fileConfig = $this->readNavigationConfig();

        return [
            'nav_redirect_title' => $this->valueOrDefault('nav_redirect_title', $fileConfig['title'] ?? 'PG 导航页'),
            'nav_redirect_subtitle' => $this->valueOrDefault('nav_redirect_subtitle', $fileConfig['subtitle'] ?? '正在为你选择可用线路'),
            'nav_redirect_final_home' => $this->valueOrDefault('nav_redirect_final_home', $fileConfig['finalHome'] ?? 'https://wakuang.fakaw.eu.cc/#/'),
            'nav_redirect_auto_redirect' => $this->valueOrDefault(
                'nav_redirect_auto_redirect',
                isset($fileConfig['autoRedirect']) ? (int) (bool) $fileConfig['autoRedirect'] : 1
            ),
            'nav_redirect_delay_seconds' => $this->valueOrDefault('nav_redirect_delay_seconds', $fileConfig['delaySeconds'] ?? 3),
            'nav_redirect_domains' => $this->valueOrDefault('nav_redirect_domains', $this->domainsToText($fileConfig['jumpDomains'] ?? [])),
        ];
    }

    protected function normalizedInput(array $input)
    {
        $data = [];
        foreach ($this->keys as $key) {
            $data[$key] = isset($input[$key]) ? trim((string) $input[$key]) : '';
        }

        $data['nav_redirect_title'] = $data['nav_redirect_title'] ?: 'PG 导航页';
        $data['nav_redirect_subtitle'] = $data['nav_redirect_subtitle'] ?: '正在为你选择可用线路';
        $data['nav_redirect_final_home'] = $this->normalizeUrl($data['nav_redirect_final_home'] ?: 'https://wakuang.fakaw.eu.cc/#/');
        $data['nav_redirect_auto_redirect'] = $data['nav_redirect_auto_redirect'] === '0' ? '0' : '1';
        $delay = (int) $data['nav_redirect_delay_seconds'];
        $data['nav_redirect_delay_seconds'] = (string) max(0, min(30, $delay));

        return $data;
    }

    protected function writeNavigationConfig(array $data)
    {
        $payload = [
            'title' => $data['nav_redirect_title'],
            'subtitle' => $data['nav_redirect_subtitle'],
            'finalHome' => $data['nav_redirect_final_home'],
            'autoRedirect' => $data['nav_redirect_auto_redirect'] === '1',
            'delaySeconds' => (int) $data['nav_redirect_delay_seconds'],
            'jumpDomains' => $this->parseDomains($data['nav_redirect_domains']),
        ];

        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw new \RuntimeException('导航页配置 JSON 生成失败');
        }

        $path = $this->configPath();
        $dir = dirname($path);
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            throw new \RuntimeException('导航页目录无法创建：' . $dir);
        }

        if (file_put_contents($path, $json . PHP_EOL, LOCK_EX) === false) {
            throw new \RuntimeException('导航页配置无法写入：' . $path);
        }
    }

    protected function parseDomains($text)
    {
        $items = [];
        $lines = preg_split('/\r\n|\r|\n/', (string) $text);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            $name = '';
            $url = $line;
            if (strpos($line, '|') !== false) {
                [$name, $url] = array_map('trim', explode('|', $line, 2));
            }

            $url = $this->normalizeUrl($url);
            if ($url === '') {
                continue;
            }

            $items[] = [
                'name' => $name ?: '跳转线路 ' . (count($items) + 1),
                'url' => $url,
            ];
        }

        return $items;
    }

    protected function normalizeUrl($url)
    {
        $url = trim((string) $url);
        if ($url === '') {
            return '';
        }

        if (!preg_match('#^https?://#i', $url)) {
            $url = 'https://' . ltrim($url, '/');
        }

        return $url;
    }

    protected function domainsToText(array $domains)
    {
        $lines = [];
        foreach ($domains as $index => $domain) {
            if (is_string($domain)) {
                $lines[] = '跳转线路 ' . ($index + 1) . '|' . $domain;
                continue;
            }

            if (!is_array($domain)) {
                continue;
            }

            $url = $domain['url'] ?? '';
            if (!$url) {
                continue;
            }

            $lines[] = ($domain['name'] ?? ('跳转线路 ' . ($index + 1))) . '|' . $url;
        }

        return implode("\n", $lines);
    }

    protected function readNavigationConfig()
    {
        $path = $this->configPath();
        if (!is_file($path)) {
            return [];
        }

        $json = json_decode((string) file_get_contents($path), true);
        return is_array($json) ? $json : [];
    }

    protected function configPath()
    {
        return SystemConfig::getValue(self::CONFIG_PATH_KEY) ?: env('NAV_REDIRECT_CONFIG_PATH', '/www/wwwroot/nav-redirect/config.json');
    }

    protected function valueOrDefault($key, $default)
    {
        $value = SystemConfig::getValue($key);
        return $value === '' ? $default : $value;
    }

    protected function helpHtml()
    {
        return <<<HTML
<div style="margin-top:20px;padding:16px;background:#f8f9fa;border-left:4px solid #409eff;border-radius:4px;color:#606266;line-height:1.8;">
    <strong style="color:#409eff;">当前导航页地址</strong>
    <div><a href="https://daoahngye.fakaw.eu.cc/" target="_blank">https://daoahngye.fakaw.eu.cc/</a></div>
    <div><a href="https://tiaozhuan01.fakaw.eu.cc/" target="_blank">https://tiaozhuan01.fakaw.eu.cc/</a></div>
    <div style="margin-top:8px;">保存后会立即更新导航页配置文件，前端使用 no-store，不需要等缓存。</div>
</div>
HTML;
    }
}
