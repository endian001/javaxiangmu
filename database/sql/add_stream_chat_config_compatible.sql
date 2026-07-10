-- Stream Chat 配置脚本（兼容版本）
-- 适用于不支持 INSERT IGNORE 的数据库（如旧版 MySQL）
-- 如果配置已存在则更新，不存在则插入

-- Stream Chat API Key 配置
INSERT INTO `system_config` (`key`, `value`) 
VALUES ('stream_chat_api_key', '') 
ON DUPLICATE KEY UPDATE `value` = `value`;

-- Stream Chat Secret 配置
INSERT INTO `system_config` (`key`, `value`) 
VALUES ('stream_chat_secret', '') 
ON DUPLICATE KEY UPDATE `value` = `value`;

-- Stream Chat 开关配置（0=关闭，1=开启，默认关闭）
INSERT INTO `system_config` (`key`, `value`) 
VALUES ('stream_chat_enabled', '0') 
ON DUPLICATE KEY UPDATE `value` = `value`;

-- Stream Chat 历史消息数量配置（范围：10-500，默认：50）
INSERT INTO `system_config` (`key`, `value`) 
VALUES ('stream_chat_message_limit', '50') 
ON DUPLICATE KEY UPDATE `value` = `value`;


