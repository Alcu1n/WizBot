<?php
/**
 * WizChat前端显示类
 *
 * @package WizChat
 */

// 如果直接访问此文件，则退出
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WizChat_Public类 - 处理插件前端显示功能
 */
class WizChat_Public {
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
        
        // 添加聊天界面到前端
        add_action('wp_footer', array($this, 'render_chat_interface'));
    }

    /**
     * 渲染聊天界面到前端
     */
    public function render_chat_interface() {
        // 获取气泡位置和主题颜色
        $bubble_position = isset($this->settings['bubble_position']) ? $this->settings['bubble_position'] : 'right';
        $primary_color = isset($this->settings['primary_color']) ? $this->settings['primary_color'] : '#4F46E5';
        
        // 气泡位置样式
        $position_class = ($bubble_position === 'left') ? 'left-4' : 'right-4';
        
        // 生成内联样式，用于自定义颜色
        $custom_styles = "
            .wizchat-primary-bg { background-color: {$primary_color}; }
            .wizchat-primary-text { color: {$primary_color}; }
            .wizchat-primary-border { border-color: {$primary_color}; }
        ";
        
        // 输出内联样式
        echo '<style>' . $custom_styles . '</style>';
        
        // 输出聊天界面HTML
        ?>
        <div id="wizchat-container" class="wizchat-container">
            <!-- 聊天气泡 -->
            <button id="wizchat-bubble" class="wizchat-bubble wizchat-primary-bg fixed bottom-4 <?php echo esc_attr($position_class); ?> w-14 h-14 rounded-full flex items-center justify-center shadow-lg z-50 transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            
            <!-- 聊天界面 -->
            <div id="wizchat-chat-window" class="wizchat-chat-window fixed bottom-20 <?php echo esc_attr($position_class); ?> w-80 md:w-96 bg-white rounded-lg shadow-xl z-40 flex flex-col transition-all duration-300 transform scale-0 origin-bottom-right" style="height: 480px; max-height: 70vh;">
                <!-- 聊天标题栏 -->
                <div class="wizchat-header wizchat-primary-bg px-4 py-3 rounded-t-lg flex justify-between items-center">
                    <h3 class="text-white font-medium">WizChat 智能客服</h3>
                    <button id="wizchat-close" class="text-white focus:outline-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- 聊天消息区域 -->
                <div id="wizchat-messages" class="wizchat-messages flex-1 p-4 overflow-y-auto flex flex-col space-y-3">
                    <!-- 欢迎消息 -->
                    <div class="wizchat-message wizchat-message-ai flex items-start">
                        <div class="wizchat-avatar wizchat-primary-bg w-8 h-8 rounded-full flex items-center justify-center text-white mr-2 flex-shrink-0">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="wizchat-bubble bg-gray-100 rounded-lg p-3 max-w-[85%]">
                            <p>您好！我是WizChat智能客服，有什么可以帮您的吗？</p>
                        </div>
                    </div>
                    <!-- 消息将动态添加到这里 -->
                </div>
                
                <!-- 输入区域 -->
                <div class="wizchat-input-area p-3 border-t border-gray-200">
                    <form id="wizchat-message-form" class="flex items-center">
                        <input type="text" id="wizchat-message-input" class="flex-1 border border-gray-300 rounded-l-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="请输入您的问题..." autocomplete="off">
                        <button type="submit" class="wizchat-primary-bg text-white rounded-r-lg px-4 py-2 focus:outline-none">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>
                    </form>
                </div>
                
                <!-- 提示区域 -->
                <div class="wizchat-typing p-2 text-xs text-gray-500 hidden">
                    <span class="wizchat-typing-indicator">AI正在思考...</span>
                </div>
                
                <!-- 错误消息 -->
                <div id="wizchat-error" class="wizchat-error p-2 text-xs text-red-500 hidden"></div>
                
                <!-- 底部版权信息 -->
                <div class="wizchat-footer p-2 text-xs text-center text-gray-400 border-t border-gray-100">
                    由 <a href="https://lrai.studio" target="_blank" class="wizchat-primary-text underline">WizChat</a> 提供支持
                </div>
            </div>
        </div>
        <?php
    }
}
