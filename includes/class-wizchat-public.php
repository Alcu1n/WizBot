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
        <!-- WizChat聊天界面容器 -->
        <div id="wizchat-container" class="wizchat-container">
            <!-- 聊天按钮 -->
            <button id="wizchat-bubble" class="wizchat-bubble-btn shadow-lg z-50 transition-all duration-300">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-white hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
            
            <!-- 聊天窗口 -->
            <div id="wizchat-chat-window" class="wizchat-chat-window fixed bottom-20 <?php echo esc_attr($position_class); ?> w-80 md:w-96 rounded-xl shadow-2xl z-40 flex flex-col transition-all duration-300 transform scale-0 origin-bottom-right overflow-hidden" style="position: fixed !important; bottom: 5rem !important; <?php echo $bubble_position === 'left' ? 'left: 1rem !important;' : 'right: 1rem !important;' ?>">
                <!-- 聊天消息区域 - 放在最底层 -->
                <div id="wizchat-messages-container" class="absolute inset-0 z-0 pt-16 pb-16">
                    <div id="wizchat-messages" class="wizchat-messages h-full p-4 overflow-y-auto flex flex-col space-y-4">
                        <!-- 欢迎消息 -->
                        <div class="wizchat-message wizchat-message-ai flex items-start">
                            <div class="wizchat-avatar w-9 h-9 rounded-full flex items-center justify-center text-white mr-2 flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                </svg>
                            </div>
                            <div class="wizchat-bubble p-3 max-w-[85%]">
                                <p>您好！我是WizChat智能助手，有什么可以帮您的吗？</p>
                            </div>
                        </div>
                        <!-- 消息将动态添加到这里 -->
                    </div>
                </div>
                
                <!-- 聊天标题栏 - 毛玻璃效果 -->
                <div class="wizchat-header px-4 py-3 flex justify-between items-center z-10 absolute top-0 left-0 right-0">
                    <div class="flex items-center">
                        <div class="mr-2 w-8 h-8 rounded-full flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <h3 class="text-white font-medium">WizChat-@dev版本开发中</h3>
                    </div>
                    <button id="wizchat-close" class="text-white focus:outline-none hover:opacity-80 transition-opacity">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <!-- 输入区域 - 毛玻璃效果 -->
                <div class="wizchat-input-area z-10 absolute bottom-0 left-0 right-0">
                    <form id="wizchat-message-form" class="flex items-center">
                        <input type="text" id="wizchat-message-input" class="w-full" placeholder="请输入您的问题..." autocomplete="off">
                        <button type="submit" class="flex-shrink-0 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 5l7 7-7 7M5 5l7 7-7 7" />
                            </svg>
                        </button>
                    </form>
                </div>
                
                <!-- 提示区域 -->
                <div class="wizchat-typing p-2 text-center text-xs hidden z-10 absolute bottom-16 left-0 right-0">
                    <span class="wizchat-typing-indicator inline-block py-1 px-3 rounded-full bg-opacity-10 bg-white text-white">
                        AI正在回复<span class="wizchat-dot"></span>
                    </span>
                </div>
                
                <!-- 底部版权信息 -->
                <div class="wizchat-footer text-center text-xs py-1 px-4 z-5 absolute bottom-0 left-0 right-0">
                    由 <a href="https://wizchat.io" target="_blank" rel="nofollow">WizChat</a> 提供支持
                </div>
            </div>
        </div>
        <?php
    }
}
