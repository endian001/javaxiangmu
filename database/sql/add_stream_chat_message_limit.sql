-- 添加 Stream Chat 历史消息数量配置
-- 如果已经运行过迁移但缺少此配置项，可以执行此 SQL

-- MySQL 5.7+ / MariaDB 10.2+ 版本
INSERT IGNORE INTO `system_config` (`key`, `value`) VALUES ('stream_chat_message_limit', '50');

-- 兼容所有版本 MySQL（如果上面语句不支持，使用这个）
-- INSERT INTO `system_config` (`key`, `value`) 
-- VALUES ('stream_chat_message_limit', '50') 
-- ON DUPLICATE KEY UPDATE `value` = `value`;

-- 如果需要更新配置值，可以使用：
-- UPDATE `system_config` SET `value` = '100' WHERE `key` = 'stream_chat_message_limit';

