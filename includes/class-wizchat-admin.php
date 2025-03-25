<?php
/**
 * WizChat管理界面类
 *
 * @package WizChat
 */

// 如果直接访问此文件，则退出
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WizChat_Admin类 - 处理插件的管理界面功能
 */
class WizChat_Admin {
    /**
     * 插件设置
     *
     * @var array
     */
    private $settings;

    /**
     * 构造函数
     *
     * @param array $settings 插件设置
     */
    public function __construct($settings) {
        $this->settings = $settings;
        
        // 添加管理菜单
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // 注册设置
        add_action('admin_init', array($this, 'register_settings'));
        
        // 加载管理界面的脚本和样式
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    /**
     * 添加管理菜单
     */
    public function add_admin_menu() {
        add_menu_page(
            'WizChat设置',        // 页面标题
            'WizChat',           // 菜单标题
            'manage_options',     // 权限
            'wizchat-settings',   // 菜单别名
            array($this, 'display_settings_page'), // 回调函数
            'dashicons-format-chat', // 图标
            30 // 位置
        );
    }

    /**
     * 注册插件设置
     */
    public function register_settings() {
        register_setting(
            'wizchat_settings_group',  // 选项组
            'wizchat_settings',        // 选项名称
            array($this, 'sanitize_settings') // 消毒回调
        );

        // 添加设置区块 - API设置
        add_settings_section(
            'wizchat_api_settings',   // ID
            '基础API设置',            // 标题
            array($this, 'api_settings_section_callback'), // 回调
            'wizchat-settings'        // 页面
        );

        // 添加设置区块 - 聊天界面设置
        add_settings_section(
            'wizchat_chat_settings',   // ID
            '聊天界面设置',            // 标题
            array($this, 'chat_settings_section_callback'), // 回调
            'wizchat-settings'        // 页面
        );

        // 添加字段 - API密钥
        add_settings_field(
            'api_key',                                 // ID
            'API密钥',                                 // 标题
            array($this, 'api_key_field_callback'),    // 回调
            'wizchat-settings',                        // 页面
            'wizchat_api_settings'                     // 区块
        );

        // 添加字段 - 基础URL
        add_settings_field(
            'base_url',                                // ID
            '基础URL',                                 // 标题
            array($this, 'base_url_field_callback'),   // 回调
            'wizchat-settings',                        // 页面
            'wizchat_api_settings'                     // 区块
        );

        // 添加字段 - 模型
        add_settings_field(
            'model',                                   // ID
            'AI模型',                                  // 标题
            array($this, 'model_field_callback'),      // 回调
            'wizchat-settings',                        // 页面
            'wizchat_api_settings'                     // 区块
        );

        // 添加字段 - 气泡位置
        add_settings_field(
            'bubble_position',                              // ID
            '聊天气泡位置',                                 // 标题
            array($this, 'bubble_position_field_callback'), // 回调
            'wizchat-settings',                             // 页面
            'wizchat_chat_settings'                         // 区块
        );

        // 添加字段 - 会话持续时间
        add_settings_field(
            'session_duration',                              // ID
            '会话持续时间(小时)',                            // 标题
            array($this, 'session_duration_field_callback'), // 回调
            'wizchat-settings',                              // 页面
            'wizchat_chat_settings'                          // 区块
        );

        // 添加字段 - 主题颜色
        add_settings_field(
            'primary_color',                              // ID
            '主题颜色',                                   // 标题
            array($this, 'primary_color_field_callback'), // 回调
            'wizchat-settings',                           // 页面
            'wizchat_chat_settings'                       // 区块
        );

        // 添加字段 - 启用向量搜索
        add_settings_field(
            'enable_vector_search',                              // ID
            '启用向量知识库搜索',                                // 标题
            array($this, 'enable_vector_search_field_callback'), // 回调
            'wizchat-settings',                                  // 页面
            'wizchat_chat_settings'                              // 区块
        );
    }

    /**
     * API设置区块回调
     */
    public function api_settings_section_callback() {
        echo '<p>设置与OpenAI API通信所需的信息</p>';
    }

    /**
     * 聊天设置区块回调
     */
    public function chat_settings_section_callback() {
        echo '<p>配置聊天界面的外观和行为</p>';
    }

    /**
     * API密钥字段回调
     */
    public function api_key_field_callback() {
        $api_key = isset($this->settings['api_key']) ? $this->settings['api_key'] : '';
        ?>
        <input type="password" name="wizchat_settings[api_key]" value="<?php echo esc_attr($api_key); ?>" class="regular-text">
        <p class="description">输入您的OpenAI API密钥</p>
        <?php
    }

    /**
     * 基础URL字段回调
     */
    public function base_url_field_callback() {
        $base_url = isset($this->settings['base_url']) ? $this->settings['base_url'] : 'https://api.openai.com/v1';
        ?>
        <input type="text" name="wizchat_settings[base_url]" value="<?php echo esc_url($base_url); ?>" class="regular-text">
        <p class="description">API通信的基础URL，默认为OpenAI官方API</p>
        <?php
    }

    /**
     * 模型字段回调
     */
    public function model_field_callback() {
        $model = isset($this->settings['model']) ? $this->settings['model'] : 'gpt-4o';
        $custom_model = isset($this->settings['custom_model']) ? $this->settings['custom_model'] : '';
        
        // 预定义的模型列表
        $models = array(
            'gpt-4o' => 'GPT-4o',
            'gpt-4o-mini' => 'GPT-4o-mini',
            'gpt-3.5-turbo' => 'GPT-3.5-turbo',
            'gpt-4' => 'GPT-4',
            'gpt-4-turbo' => 'GPT-4-turbo',
            'gpt-4-vision-preview' => 'GPT-4-vision',
            'custom' => '自定义模型'
        );
        
        // 检查当前模型是否在预定义列表中，如果不在，设置为自定义
        $is_custom = !array_key_exists($model, $models) && $model !== 'custom';
        if ($is_custom) {
            $custom_model = $model;
            $model = 'custom';
        }
        ?>
        <div class="wizchat-model-selection">
            <select name="wizchat_settings[model]" id="wizchat-model-select">
                <?php foreach ($models as $key => $label) : ?>
                    <option value="<?php echo esc_attr($key); ?>" <?php selected($model, $key); ?>><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
            
            <div id="wizchat-custom-model-container" style="margin-top: 10px; <?php echo $model !== 'custom' ? 'display:none;' : ''; ?>">
                <input 
                    type="text" 
                    name="wizchat_settings[custom_model]" 
                    id="wizchat-custom-model" 
                    value="<?php echo esc_attr($custom_model); ?>" 
                    placeholder="输入自定义模型名称，例如：gpt-4-1106-preview" 
                    class="regular-text"
                >
            </div>
            
            <p class="description">选择使用的AI模型或输入自定义模型名称</p>
        </div>
        
        <script>
            jQuery(document).ready(function($) {
                // 处理模型选择变化
                $('#wizchat-model-select').on('change', function() {
                    if ($(this).val() === 'custom') {
                        $('#wizchat-custom-model-container').show();
                    } else {
                        $('#wizchat-custom-model-container').hide();
                    }
                });
            });
        </script>
        <?php
    }

    /**
     * 气泡位置字段回调
     */
    public function bubble_position_field_callback() {
        $position = isset($this->settings['bubble_position']) ? $this->settings['bubble_position'] : 'right';
        ?>
        <select name="wizchat_settings[bubble_position]">
            <option value="right" <?php selected($position, 'right'); ?>>右下角</option>
            <option value="left" <?php selected($position, 'left'); ?>>左下角</option>
        </select>
        <p class="description">聊天气泡在页面中的位置</p>
        <?php
    }

    /**
     * 会话持续时间字段回调
     */
    public function session_duration_field_callback() {
        $duration = isset($this->settings['session_duration']) ? intval($this->settings['session_duration']) : 24;
        ?>
        <input type="number" name="wizchat_settings[session_duration]" value="<?php echo esc_attr($duration); ?>" class="small-text" min="1" max="720">
        <p class="description">会话数据保留的时间长度（小时）</p>
        <?php
    }

    /**
     * 主题颜色字段回调
     */
    public function primary_color_field_callback() {
        $color = isset($this->settings['primary_color']) ? $this->settings['primary_color'] : '#4F46E5';
        ?>
        <input type="color" name="wizchat_settings[primary_color]" value="<?php echo esc_attr($color); ?>">
        <p class="description">聊天界面的主题颜色</p>
        <?php
    }

    /**
     * 启用向量搜索字段回调
     */
    public function enable_vector_search_field_callback() {
        $enabled = isset($this->settings['enable_vector_search']) ? $this->settings['enable_vector_search'] : 'no';
        ?>
        <select name="wizchat_settings[enable_vector_search]">
            <option value="no" <?php selected($enabled, 'no'); ?>>否</option>
            <option value="yes" <?php selected($enabled, 'yes'); ?>>是</option>
        </select>
        <p class="description">是否启用向量知识库搜索功能（基于网站内容提供更精准的回答）</p>
        <?php
    }

    /**
     * 验证和消毒设置数据
     *
     * @param array $input 用户输入的设置
     * @return array 消毒后的设置
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // API密钥
        if (isset($input['api_key'])) {
            $sanitized['api_key'] = sanitize_text_field($input['api_key']);
        }
        
        // 基础URL
        if (isset($input['base_url'])) {
            $sanitized['base_url'] = esc_url_raw($input['base_url']);
        }
        
        // 模型
        if (isset($input['model'])) {
            $sanitized['model'] = sanitize_text_field($input['model']);
        }
        
        // 自定义模型
        if (isset($input['custom_model'])) {
            $sanitized['custom_model'] = sanitize_text_field($input['custom_model']);
        }
        
        // 气泡位置
        if (isset($input['bubble_position']) && in_array($input['bubble_position'], array('right', 'left'))) {
            $sanitized['bubble_position'] = $input['bubble_position'];
        }
        
        // 会话持续时间
        if (isset($input['session_duration'])) {
            $sanitized['session_duration'] = intval($input['session_duration']);
            // 确保时间在合理范围内
            if ($sanitized['session_duration'] < 1) {
                $sanitized['session_duration'] = 1;
            } elseif ($sanitized['session_duration'] > 720) { // 最大30天
                $sanitized['session_duration'] = 720;
            }
        }
        
        // 主题颜色
        if (isset($input['primary_color'])) {
            $sanitized['primary_color'] = sanitize_hex_color($input['primary_color']);
        }
        
        // 启用向量搜索
        if (isset($input['enable_vector_search']) && in_array($input['enable_vector_search'], array('yes', 'no'))) {
            $sanitized['enable_vector_search'] = $input['enable_vector_search'];
        }
        
        return $sanitized;
    }

    /**
     * 显示设置页面
     */
    public function display_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('wizchat_settings_group');
                do_settings_sections('wizchat-settings');
                submit_button('保存设置');
                ?>
                
                <hr>
                
                <h2>验证API连接</h2>
                <p>点击下面的按钮测试您的API配置是否正确</p>
                <div class="wizchat-api-test" style="margin-bottom: 20px;">
                    <button type="button" id="wizchat-test-api" class="button button-secondary">测试API连接</button>
                    <span id="wizchat-api-test-result" style="margin-left: 10px; display: inline-block; min-height: 30px; padding: 5px 0;"></span>
                </div>
            </form>
        </div>
        <?php
    }

    /**
     * 加载管理界面的脚本和样式
     *
     * @param string $hook_suffix 当前管理页面的钩子后缀
     */
    public function enqueue_admin_scripts($hook_suffix) {
        // 由于我们已将菜单从settings移到了顶级菜单，钩子名称变更为toplevel_page_wizchat-settings
        if ($hook_suffix !== 'settings_page_wizchat-settings' && $hook_suffix !== 'toplevel_page_wizchat-settings') {
            return;
        }
        
        // 注册管理界面脚本
        wp_register_script(
            'wizchat-admin-script',
            WIZCHAT_PLUGIN_URL . 'assets/js/wizchat-admin.js',
            array('jquery'),
            WIZCHAT_VERSION,
            true
        );
        
        // 加载管理界面脚本
        wp_enqueue_script('wizchat-admin-script');
        
        // 将设置传递给JS
        wp_localize_script('wizchat-admin-script', 'wizchatAdmin', array(
            'apiTestUrl' => rest_url('wizchat/v1/verify-api'),
            'nonce' => wp_create_nonce('wp_rest'),
        ));
    }
}
