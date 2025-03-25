<?php
/**
 * WizChat - WordPress AI智能客服插件
 *
 * @package           WizChat
 * @author            Lemon
 * @copyright         2025 lrai.studio
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       WizChat
 * Plugin URI:        https://lrai.studio/wizchat
 * Description:       AI智能客服，基于OpenAI的智能聊天客服控件
 * Version:           1.0.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Lemon
 * Author URI:        https://lrai.studio
 * Text Domain:       wizchat
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// 如果直接访问此文件，则退出
if (!defined('ABSPATH')) {
    exit;
}

// 定义插件常量
define('WIZCHAT_VERSION', '1.0.0');
define('WIZCHAT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WIZCHAT_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WIZCHAT_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * 插件激活时运行的代码
 */
function wizchat_activate() {
    // 添加默认设置
    $default_options = array(
        'api_key' => '',
        'base_url' => 'https://api.openai.com/v1',
        'model' => 'gpt-4o',
        'bubble_position' => 'right',
        'session_duration' => 24, // 小时
        'primary_color' => '#4F46E5',
        'enable_vector_search' => 'no',
    );
    
    add_option('wizchat_settings', $default_options);
    
    // 清除重写规则并重新保存
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wizchat_activate');

/**
 * 插件停用时运行的代码
 */
function wizchat_deactivate() {
    // 清除重写规则
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wizchat_deactivate');

/**
 * 加载插件的类文件
 */
function wizchat_load_classes() {
    // 包含核心类
    require_once WIZCHAT_PLUGIN_DIR . 'includes/class-wizchat.php';
}
add_action('plugins_loaded', 'wizchat_load_classes');

/**
 * 插件主要实例化函数
 */
function wizchat_init() {
    if (class_exists('WizChat')) {
        return WizChat::get_instance();
    }
    return null;
}

// 初始化插件
$GLOBALS['wizchat'] = wizchat_init();
