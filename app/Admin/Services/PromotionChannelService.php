<?php

namespace App\Admin\Services;

use InvalidArgumentException;

class PromotionChannelService
{
    private const PAGES = [
        '280000' => [
            'module' => 'links',
            'title' => '投放链接设置',
            'summary' => '投放主站、落地页、测速页链接生成与全局过滤设置。',
            'buttons' => [
                '新增主站投放链接',
                '新增落地页面投放链接',
                '新增测速页投放链接',
                '全局设置',
                '搜索',
                '批量删除',
            ],
            'fields' => [
                '链接类型',
                '推广域名',
                '代理账号',
                '投放工具',
                '应用程序',
                'HTTPS状态',
                '斗篷功能',
                '广告审核通过',
                '执行状态',
                '备注',
            ],
        ],
        '21160' => [
            'module' => 'domains',
            'title' => '推广域名管理',
            'summary' => '管理推广域名、用户归属、DNS 指向、访问统计和启停状态。',
            'buttons' => ['新增', '批量设置', '搜索'],
            'fields' => ['域类型', '推广域名', '用户', '指向', '状态'],
        ],
        '280004' => [
            'module' => 'landing',
            'title' => '落地页配置',
            'summary' => '配置 APP 商店式落地页、素材、评分、描述和评论区内容。',
            'buttons' => ['搜索', '新增'],
            'fields' => [
                'APP名称',
                '公司名称',
                '语系',
                '总评分',
                '评分',
                '下载人数',
                '用户年龄',
                'APP图',
                '滚动图',
                '应用描述标题',
                '应用描述内容',
                '网站图标',
                '评论区设置',
            ],
        ],
        '280008' => [
            'module' => 'seo',
            'title' => 'SEO配置',
            'summary' => '配置 SEO 模板、标题、描述元数据、关键字、预览图片和应用域名。',
            'buttons' => ['搜索', '新增'],
            'fields' => [
                '模板名称',
                '标题',
                '描述元数据',
                '关键字',
                '预览图片',
                '应用域名',
                '状态',
            ],
        ],
        '280015' => [
            'module' => 'push',
            'title' => '未注册推播',
            'summary' => '维护推播模板并建立立即或定时发送队列，等待 Firebase 工作进程处理。',
            'buttons' => ['新增模板', '定时发送', '发送'],
            'fields' => [
                '筛选推播对象',
                '选择推播模板',
                '推播标题',
                '推播内容',
                '排程时间',
            ],
        ],
        '280012' => [
            'module' => 'events',
            'title' => '事件记录',
            'summary' => '查询 Facebook、TikTok 和 Adjust S2S 事件记录、设置映射并导出。',
            'buttons' => ['Facebook活动设置', '导出', '搜索'],
            'fields' => [
                '链接 ID',
                '脸书像素ID',
                '抖音像素ID',
                '用户名',
                '代理账号',
                '事件',
                '活动时间',
                '注册时间',
                '原始记录',
            ],
        ],
    ];

    private const ITEM_MODULES = [
        '280000' => 'links',
        '21160' => 'domains',
        '280004' => 'landing',
        '280008' => 'seo',
        '280015' => 'push-template',
    ];

    private const DATA_FIELDS = [
        '280000' => [
            'https_status',
            'cloak_enabled',
            'ad_review_passed',
            'tool',
            'application',
            'execution_status',
            'downloads_today',
            'total_downloads',
            'visits_today',
            'total_visits',
            'last_visited_at',
            'note',
            'cname',
        ],
        '21160' => [
            'domain_type',
            'total_visits',
            'last_click',
        ],
        '280004' => [
            'company_name',
            'language',
            'total_score',
            'score',
            'download_count',
            'user_age',
            'app_icon',
            'carousel_images',
            'description_title',
            'description_content',
            'favicon',
            'comments',
        ],
        '280008' => [
            'title',
            'meta_description',
            'keywords',
            'preview_image',
            'preview_size',
            'bound_domain',
        ],
        '280015' => [
            'push_title',
            'push_content',
        ],
    ];

    private const SETTING_FIELDS = [
        '280000' => ['filter_non_first_deposit', 'default_cname'],
        '21160' => ['operation', 'old_target', 'new_target'],
        '280015' => ['firebase_permission_note'],
        '280012' => [
            'facebook_pixel_id',
            'facebook_event_name',
            'adjust_app_token',
            'adjust_event',
            'adjust_event_token',
        ],
    ];

    public function pages(): array
    {
        return self::PAGES;
    }

    public function page($code): array
    {
        $code = (string) $code;
        if (!isset(self::PAGES[$code])) {
            throw new InvalidArgumentException('推广渠道页面不存在');
        }

        return self::PAGES[$code];
    }

    public function module($code): string
    {
        return $this->page($code)['module'];
    }

    public function itemModule($code): string
    {
        $code = (string) $code;
        if (!isset(self::ITEM_MODULES[$code])) {
            throw new InvalidArgumentException('当前页面不支持资料项目操作');
        }

        return self::ITEM_MODULES[$code];
    }

    public function filterItemData($code, array $values): array
    {
        $code = (string) $code;
        $allowed = array_flip(self::DATA_FIELDS[$code] ?? []);
        $result = [];

        foreach ($values as $key => $value) {
            if (!isset($allowed[$key]) || is_array($value) || is_object($value)) {
                continue;
            }
            $result[$key] = $this->sanitizeScalar($value);
        }

        return $result;
    }

    public function filterSettings($code, array $values): array
    {
        $code = (string) $code;
        $allowed = array_flip(self::SETTING_FIELDS[$code] ?? []);
        $result = [];

        foreach ($values as $key => $value) {
            if (!isset($allowed[$key]) || is_array($value) || is_object($value)) {
                continue;
            }
            $result[$key] = $this->sanitizeScalar($value);
        }

        return $result;
    }

    private function sanitizeScalar($value): string
    {
        return mb_substr(trim(strip_tags((string) $value)), 0, 10000);
    }
}
