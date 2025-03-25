<?php
/**
 * WizChat主类
 *
 * @package WizChat
 */

// 如果直接访问此文件，则退出
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WizChat主类 - 负责初始化插件功能
 */
class WizChat {
    /**
     * 单例实例
     *
     * @var WizChat
     */
    private static $instance = null;

    /**
     * 插件设置
     *
     * @var array
     */
    private $settings;

    /**
     * 获取单例实例
     *
     * @return WizChat
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 构造函数 - 设置钩子和加载必要的组件
     */
    private function __construct() {
        $this->settings = get_option('wizchat_settings', array());
        
        // 加载依赖文件
        $this->load_dependencies();
        
        // 设置钩子
        $this->set_hooks();
        
        // 初始化REST API
        $this->init_rest_api();
    }

    /**
     * 加载依赖文件
     */
    private function load_dependencies() {
        // 加载管理界面类
        require_once WIZCHAT_PLUGIN_DIR . 'includes/class-wizchat-admin.php';
        
        // 加载公共界面类
        require_once WIZCHAT_PLUGIN_DIR . 'includes/class-wizchat-public.php';
        
        // 加载API通信类
        require_once WIZCHAT_PLUGIN_DIR . 'includes/class-wizchat-api.php';
    }

    /**
     * 设置WordPress钩子
     */
    private function set_hooks() {
        // 加载管理界面
        if (is_admin()) {
            $admin = new WizChat_Admin($this->settings);
        }
        
        // 加载前端界面
        $public = new WizChat_Public($this->settings);
        
        // 添加JS和CSS到前端
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * 初始化REST API端点
     */
    private function init_rest_api() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * 注册REST API路由
     */
    public function register_routes() {
        // 注册聊天端点
        register_rest_route('wizchat/v1', '/chat', array(
            'methods' => 'POST',
            'callback' => array($this, 'process_chat_request'),
            'permission_callback' => '__return_true', // 允许所有用户访问
        ));
        
        // 注册API验证端点
        register_rest_route('wizchat/v1', '/verify-api', array(
            'methods' => 'POST',
            'callback' => array($this, 'verify_api_connection'),
            'permission_callback' => function() {
                return current_user_can('manage_options');
            },
        ));
    }

    /**
     * 处理聊天请求
     *
     * @param WP_REST_Request $request 请求对象
     * @return WP_REST_Response
     */
    public function process_chat_request($request) {
        $api = new WizChat_API($this->settings);
        $message = sanitize_text_field($request->get_param('message'));
        $conversation_history = $request->get_param('history');
        
        // 验证和清理历史记录
        if (is_array($conversation_history)) {
            array_walk_recursive($conversation_history, function(&$item) {
                $item = sanitize_text_field($item);
            });
        } else {
            $conversation_history = array();
        }
        
        try {
            $response = $api->send_chat_request($message, $conversation_history);
            return new WP_REST_Response($response, 200);
        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'error' => $e->getMessage()
            ), 500);
        }
    }

    /**
     * 验证API连接
     * 
     * @param WP_REST_Request $request REST请求对象
     * @return WP_REST_Response
     */
    public function verify_api_connection($request) {
        // 获取请求参数
        $params = $request->get_json_params();
        
        // 检查API密钥是否为空
        if (empty($params) || empty($params['api_key'])) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => '请提供有效的API密钥'
            ));
        }
        
        // 获取参数
        $api_key = sanitize_text_field($params['api_key']);
        $base_url = isset($params['base_url']) ? sanitize_text_field($params['base_url']) : '';
        $model = isset($params['model']) ? sanitize_text_field($params['model']) : 'gpt-3.5-turbo';
        
        try {
            // 初始化API实例
            $api = new WizChat_API(array(
                'api_key' => $api_key,
                'base_url' => $base_url,
                'model' => $model
            ));
            
            // 测试连接
            $result = $api->test_connection();
            
            if ($result['success']) {
                return rest_ensure_response(array(
                    'success' => true,
                    'message' => '连接成功: ' . $result['message']
                ));
            } else {
                return rest_ensure_response(array(
                    'success' => false,
                    'message' => '连接失败: ' . $result['message']
                ));
            }
            
        } catch (Exception $e) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => '连接失败: ' . $e->getMessage()
            ));
        }
    }

    /**
     * 注册和加载前端脚本和样式
     */
    public function enqueue_scripts() {
        // 注册TailwindCSS
        wp_register_style(
            'tailwindcss',
            'https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css',
            array(),
            WIZCHAT_VERSION
        );
        
        // 注册插件CSS
        wp_register_style(
            'wizchat-style',
            WIZCHAT_PLUGIN_URL . 'assets/css/wizchat.css',
            array('tailwindcss'),
            WIZCHAT_VERSION
        );
        
        // 注册插件JS
        wp_register_script(
            'wizchat-script',
            WIZCHAT_PLUGIN_URL . 'assets/js/wizchat.js',
            array('jquery'),
            WIZCHAT_VERSION,
            true
        );
        
        // 加载样式和脚本
        wp_enqueue_style('tailwindcss');
        wp_enqueue_style('wizchat-style');
        wp_enqueue_script('wizchat-script');
        
        // 将设置传递给JS
        wp_localize_script('wizchat-script', 'wizchatSettings', array(
            'apiUrl' => rest_url('wizchat/v1/chat'),
            'nonce' => wp_create_nonce('wp_rest'),
            'bubblePosition' => isset($this->settings['bubble_position']) ? $this->settings['bubble_position'] : 'right',
            'primaryColor' => isset($this->settings['primary_color']) ? $this->settings['primary_color'] : '#4F46E5',
        ));
    }
}
