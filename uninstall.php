<?php
/**
 * WizChat插件卸载处理
 *
 * 当插件被删除时运行的代码
 *
 * @package WizChat
 */

// 如果没有通过WordPress调用此文件，则退出
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 删除插件选项
delete_option('wizchat_settings');

// 如果有创建自定义表，可以在这里删除
// global $wpdb;
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}wizchat_conversations");

// 删除可能创建的transients
delete_transient('wizchat_api_verification');
