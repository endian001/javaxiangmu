# Stream Chat 数据库配置说明

## 配置项说明

Stream Chat 功能需要在 `system_config` 表中添加以下配置项：

1. **stream_chat_api_key** - Stream Chat API Key
   - 类型：字符串
   - 说明：从 Stream Dashboard 获取的 API Key
   - 默认值：空字符串

2. **stream_chat_secret** - Stream Chat Secret
   - 类型：字符串
   - 说明：从 Stream Dashboard 获取的 Secret，用于生成用户 Token
   - 默认值：空字符串

3. **stream_chat_enabled** - 聊天室开关
   - 类型：整数
   - 说明：0=关闭，1=开启
   - 默认值：0（关闭）

4. **stream_chat_message_limit** - 历史消息数量
   - 类型：整数
   - 说明：每次加载的历史消息数量
   - 范围：10-500
   - 默认值：50

## 安装方式

### 方式一：使用 Laravel 迁移（推荐）

```bash
cd admin
php artisan migrate
```

迁移文件位置：`database/migrations/2024_01_01_000000_add_stream_chat_config.php`

### 方式二：直接执行 SQL 脚本

1. **MySQL 5.7+ / MariaDB 10.2+**：
   ```bash
   mysql -u用户名 -p数据库名 < database/sql/add_stream_chat_config.sql
   ```

2. **兼容所有版本 MySQL**：
   ```bash
   mysql -u用户名 -p数据库名 < database/sql/add_stream_chat_config_compatible.sql
   ```

### 方式三：手动插入（通过后台管理）

1. 登录后台管理系统
2. 进入"系统设置" → "聊天室设置"
3. 填写 Stream Chat API Key 和 Secret
4. 开启聊天室开关

## 配置 Stream Chat

1. 访问 [Stream Dashboard](https://getstream.io/dashboard/)
2. 注册账号并创建应用
3. 获取 API Key 和 Secret
4. 在后台"系统设置" → "聊天室设置"中填写：
   - Stream Chat API Key
   - Stream Chat Secret
   - 开启聊天室开关

## 验证配置

执行以下 SQL 查询验证配置是否已添加：

```sql
SELECT * FROM system_config WHERE `key` IN ('stream_chat_api_key', 'stream_chat_secret', 'stream_chat_enabled', 'stream_chat_message_limit');
```

## 回滚配置（删除配置项）

如果需要删除这些配置项：

### 使用 Laravel 迁移回滚
```bash
php artisan migrate:rollback --step=1
```

### 使用 SQL 删除
```sql
DELETE FROM `system_config` WHERE `key` IN ('stream_chat_api_key', 'stream_chat_secret', 'stream_chat_enabled', 'stream_chat_message_limit');
```

## 注意事项

- API Key 和 Secret 必须从 Stream Dashboard 获取，不能留空
- 只有在 `stream_chat_enabled` 设置为 `1` 时，聊天室功能才会启用
- Secret 用于在后端生成用户 Token，请妥善保管，不要泄露
- Token 有效期为 24 小时


