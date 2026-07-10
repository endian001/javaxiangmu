-- Stream Chat 配置脚本
-- 执行此 SQL 脚本会在 system_config 表中插入 Stream Chat 相关的配置项
-- 注意：如果配置已存在，请手动更新，此脚本使用 INSERT IGNORE 避免重复插入

-- 插入 Stream Chat API Key 配置（请在后台设置中填写实际值）
INSERT IGNORE INTO `system_config` (`key`, `value`) VALUES ('stream_chat_api_key', '');

-- 插入 Stream Chat Secret 配置（请在后台设置中填写实际值）
INSERT IGNORE INTO `system_config` (`key`, `value`) VALUES ('stream_chat_secret', '');

-- 插入 Stream Chat 开关配置（0=关闭，1=开启，默认关闭）
INSERT IGNORE INTO `system_config` (`key`, `value`) VALUES ('stream_chat_enabled', '0');

-- 插入 Stream Chat 历史消息数量配置（范围：10-500，默认：50）
INSERT IGNORE INTO `system_config` (`key`, `value`) VALUES ('stream_chat_message_limit', '50');

-- 如果需要更新已存在的配置，可以使用以下 UPDATE 语句：
-- UPDATE `system_config` SET `value` = 'your_api_key_here' WHERE `key` = 'stream_chat_api_key';
-- UPDATE `system_config` SET `value` = 'your_secret_here' WHERE `key` = 'stream_chat_secret';
-- UPDATE `system_config` SET `value` = '1' WHERE `key` = 'stream_chat_enabled';
-- UPDATE `system_config` SET `value` = '100' WHERE `key` = 'stream_chat_message_limit';


