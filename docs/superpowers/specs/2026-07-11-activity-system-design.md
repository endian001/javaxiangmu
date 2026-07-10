# TH2W 活动系统设计

## 目标

把现有活动后台、活动分类、申请记录和前台静态入口连接成一套真实可维护的活动系统，覆盖：

- 首页首次打开活动弹窗
- `/activity`、`/activities`、`/promotions` 三个兼容入口
- `/promotions?id={activityId}` 活动详情直达
- 桌面端与手机端响应式活动中心
- 分类、卡片、详情、规则、活动申请和外部行动链接
- Dcat 后台新增、编辑、排序、定时、启停和弹窗配置
- 泰文用户界面与 UTF-8 内容校验

## 现有基础与决策

项目已有 `activities`、`activity_types`、`activity_apply` 三张表、活动 API 和 Dcat 管理页。此次不新增第二套重复活动后台，而是在现有模型上扩展缺失能力。

正式首页是 `public/index.html`，手机首页是 `public/new-h5/index.html`。两端加载同一份活动 CSS、核心逻辑和 UI 脚本，避免桌面端和手机端出现不同数据、不同语言或不同关闭状态。

## 数据模型

### activities 扩展

- `sort_order`: 活动排序，数值越大越靠前
- `starts_at` / `ends_at`: 活动展示有效期
- `is_popup`: 是否可作为首页活动弹窗
- `popup_frequency`: `always`、`session`、`daily`、`once`
- `popup_delay_seconds`: 首页打开后延迟展示秒数
- `popup_image` / `app_popup_image`: 桌面和手机弹窗图
- `detail_image` / `app_detail_image`: 桌面和手机详情长图
- `action_url`: 详情页可选行动入口
- `requires_auth`: 申请或行动是否要求登录

保留并兼容现有 `banner`、`app_img`、`content`、`encontent`、`memo`、`enmemo`、`can_apply`、`state`、`app_state`。

泰文前台优先使用 `entitle`、`encontent`、`enmemo`；后台将这三项明确标注为泰文内容。旧字段仍保留，避免破坏历史数据。

### activity_types 扩展

- `sort_order`: 分类排序

分类名称直接维护为泰文。初始分类覆盖全部、新会员、每日福利、每月福利、VIP、充值、APP 下载、老虎机和其他。

### promotion_exposures

记录活动弹窗或详情曝光：

- 活动 ID
- 登录用户 ID，可空
- 匿名会话标识，可空
- 渠道 `desktop` / `mobile`
- 来源 `home_popup` / `promotion_center` / `direct_link`
- 曝光时间

## API

新增只读 GET API，保留现有 POST API：

- `GET /api/promotions/categories`
- `GET /api/promotions`
- `GET /api/promotions/{id}`
- `GET /api/promotions/popup`
- `POST /api/promotions/{id}/exposure`

列表和弹窗只返回当前渠道已启用且处于有效期内的活动。详情接口支持活动 ID 直达。图片统一转换为绝对上传地址。

## 前端

### 活动中心

- 桌面端采用顶部品牌栏、横向分类和三列活动卡片
- 手机端采用单列/双列自适应卡片、可横向滚动分类和安全区底部间距
- 卡片点击后打开详情弹层并把 URL 更新为 `?id=...`
- 直接打开带 ID 的 URL 时自动读取并打开对应详情
- 关闭详情时移除 URL 中的活动 ID
- 内容缺失或请求失败时显示泰文错误状态，不暴露中文错误

### 活动详情

- 使用卡片横幅、详情长图、富文本内容和规则
- 长图按容器宽度缩放，内容区独立滚动
- 桌面端最大宽度限制，手机端使用接近全屏的底部安全区布局
- 可申请活动调用现有申请 API；外部活动使用 `action_url`

### 首页活动弹窗

- 首页调用同一活动 API 的 popup 端点
- 根据 `popup_frequency` 记录关闭状态
- 弹窗图片点击进入 `/promotions?id=...`
- 桌面和手机使用对应图片
- 弹窗与详情弹层使用独立状态，互不覆盖

## 后台

活动管理增加排序、时间、弹窗、频率、双端弹窗图、双端详情图、行动链接和登录要求字段。列表显示当前发布时间、排序和弹窗状态。

分类管理增加排序和状态字段，后台仍使用中文管理标签，前台只读取泰文业务内容。

## 兼容与安全

- 不删除旧活动 API 和旧字段
- 迁移只新增可空字段和默认值
- 活动申请唯一性按 `(activity_id, user_id)` 控制
- 迁移前备份代码和三张活动表
- 任何富文本输出必须经过前端允许列表清理
- 用户页面禁止中文占位、乱码和调试文本

## 验收标准

- 三个活动入口均打开同一活动中心
- `?id=` 详情直达可刷新、可关闭、可返回
- 首页弹窗在桌面和手机端均可见并按频率关闭
- 后台新增或修改活动后前台无需改代码即可更新
- 泰文页面无中文、无乱码、无控制台错误
- 390×844、768×1024、1366×768 和 1920×1080 均无横向溢出
- 原首页、登录、钱包、充值、提现和会员入口不回退
