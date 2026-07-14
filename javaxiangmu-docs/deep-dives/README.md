# Deep Dives 目录说明

本目录收录有足够源码证据支撑的复杂专题。每个专题都围绕一个高风险或高复杂度机制展开，而不是重复项目总览。

已生成专题：

| 文档 | 主题 |
|---|---|
| `auth-and-permission-model.md` | 玩家 API token、后台 Dcat 权限、OperationPermission 能力权限、代理身份边界 |
| `middleware-chain.md` | Laravel HTTP 中间件链、CORS、locale、API 鉴权、Web session 与后台 middleware |
| `wallet-and-game-transfer-consistency.md` | 主钱包、游戏平台余额、转账流水、WXGame 回调和资金一致性 |
| `promotion-and-operations-workflow.md` | 活动展示、弹窗、曝光、申请、黑名单、活动券和后台运营 |
| `customer-service-and-live-chat.md` | 外部客服、内部实时客服、工单 fallback、访客会话和后台接待 |
| `admin-page-contract-architecture.md` | TCG 页面 code、页面契约、通用后台服务、旧表适配和权限审计 |
| `async-processing-and-scheduler.md` | 队列默认 sync、Laravel Scheduler、游戏记录抓取、代理返佣和运营审计 |

未生成专题及原因：

| 候选专题 | 原因 |
|---|---|
| 缓存与一致性 | 只看到 Laravel Cache、WXGame nonce 去重和 Redis 配置，证据不足以形成完整缓存架构文档 |
| 事件总线 | 未看到独立事件总线或消息系统设计 |
| 插件化架构 | 未看到插件化机制；Dcat Admin 扩展配置存在，但不是本项目核心插件架构 |
| 前端状态管理 | 前端是静态脚本和本地存储，未使用独立状态管理框架 |
| 文件或媒体处理 | 只看到上传配置和图片/文件字段，证据不足以形成独立媒体处理专题 |
| 部署基础设施 | 仓库缺少 Docker、CI/CD、Web 服务器和进程管理配置，已在运维文档中说明证据边界 |
