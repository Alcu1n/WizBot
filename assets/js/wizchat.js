/**
 * WizChat前端脚本
 * 
 * 处理聊天界面交互和API通信
 */
(function($) {
    'use strict';
    
    // 缓存DOM元素
    let $chat, $bubble, $window, $header, $messages, $input, $sendBtn, $typing;
    
    // 聊天状态
    let isOpen = false;
    let isSending = false;
    let conversationHistory = [];
    
    /**
     * 初始化聊天界面
     */
    function init() {
        // 创建聊天元素
        createChatElements();
        
        // 绑定事件
        bindEvents();
        
        // 调整位置
        adjustChatWindowPosition();
        
        // 加载之前的对话
        loadConversation();
        
        // 应用主题色
        applyPrimaryColor();
    }
    
    /**
     * 绑定事件处理程序
     */
    function bindEvents() {
        // 气泡点击事件
        $bubble.on('click', toggleChatWindow);
        
        // 关闭按钮点击事件
        $header.find('.wizchat-close').on('click', closeChatWindow);
        
        // 发送按钮点击事件
        $sendBtn.on('click', sendMessage);
        
        // 输入框回车事件
        $input.on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // 窗口调整大小事件
        $(window).on('resize', adjustChatWindowPosition);
    }
    
    /**
     * 切换聊天窗口显示状态
     */
    function toggleChatWindow() {
        if (isOpen) {
            closeChatWindow();
        } else {
            openChatWindow();
        }
    }
    
    /**
     * 打开聊天窗口
     */
    function openChatWindow() {
        if (!isOpen) {
            $window.removeClass('hidden');
            $bubble.addClass('hidden');
            isOpen = true;
            
            // 滚动到最新消息
            setTimeout(scrollToBottom, 100);
            
            // 自动聚焦输入框
            $input.focus();
        }
    }
    
    /**
     * 关闭聊天窗口
     */
    function closeChatWindow() {
        $window.addClass('hidden');
        $bubble.removeClass('hidden');
        isOpen = false;
    }
    
    /**
     * 调整聊天窗口位置
     */
    function adjustChatWindowPosition() {
        if (wizchatSettings.bubblePosition === 'left') {
            $bubble.addClass('wizchat-left').removeClass('wizchat-right');
            $window.addClass('wizchat-left').removeClass('wizchat-right');
        } else {
            $bubble.addClass('wizchat-right').removeClass('wizchat-left');
            $window.addClass('wizchat-right').removeClass('wizchat-left');
        }
    }
    
    /**
     * 发送用户消息
     */
    function sendMessage() {
        const message = $input.val().trim();
        
        // 检查消息是否为空
        if (!message) {
            return;
        }
        
        // 避免重复发送
        if (isSending) {
            return;
        }
        
        // 设置发送状态
        isSending = true;
        
        // 清空输入框
        $input.val('');
        
        // 添加用户消息到聊天窗口
        addUserMessage(message);
        
        // 显示AI正在输入
        showTypingIndicator();
        
        // 添加到对话历史
        conversationHistory.push({
            role: 'user',
            content: message
        });
        
        // 保存对话历史
        saveConversation();
        
        // 准备发送数据
        const requestData = {
            message: message,
            history: conversationHistory
        };
        
        // 发送API请求
        $.ajax({
            url: wizchatSettings.apiUrl,
            method: 'POST',
            data: JSON.stringify(requestData),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wizchatSettings.nonce);
            },
            success: function(response) {
                // 隐藏输入指示器
                hideTypingIndicator();
                
                // 重置发送状态
                isSending = false;
                
                // 添加AI响应到聊天窗口
                if (response && response.message) {
                    addAIMessage(response.message);
                    
                    // 添加到对话历史
                    conversationHistory.push({
                        role: 'assistant',
                        content: response.message
                    });
                    
                    // 保存对话历史
                    saveConversation();
                } else {
                    showError('收到无效的响应');
                }
            },
            error: function(xhr, status, error) {
                // 隐藏输入指示器
                hideTypingIndicator();
                
                // 重置发送状态
                isSending = false;
                
                // 显示错误消息
                let errorMessage = '请求失败';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    errorMessage = `请求失败: ${status || 'unknown'}`;
                }
                
                showError(errorMessage);
                console.error('API请求失败:', error, xhr.responseText);
            }
        });
    }
    
    /**
     * 创建聊天界面元素
     */
    function createChatElements() {
        // 如果元素已存在，则不重复创建
        if (document.getElementById('wizchat-container')) {
            return;
        }
        
        // 创建聊天容器
        const $container = $('<div id="wizchat-container"></div>');
        $('body').append($container);
        
        // 创建聊天气泡
        $bubble = $(
            `<div class="wizchat-bubble-btn wizchat-primary-bg wizchat-shadow">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                </svg>
            </div>`
        );
        
        // 创建聊天窗口
        $window = $(
            `<div class="wizchat-window wizchat-shadow hidden">
                <div class="wizchat-header wizchat-primary-bg">
                    <div class="wizchat-title font-bold">WizChat 智能助手</div>
                    <div class="wizchat-close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </div>
                <div class="wizchat-messages"></div>
                <div class="wizchat-typing hidden">
                    <div class="wizchat-typing-dot"></div>
                    <div class="wizchat-typing-dot"></div>
                    <div class="wizchat-typing-dot"></div>
                </div>
                <div class="wizchat-input-area">
                    <textarea class="wizchat-input" placeholder="输入您的问题..."></textarea>
                    <button class="wizchat-send-btn wizchat-primary-bg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                        </svg>
                    </button>
                </div>
            </div>`
        );
        
        // 添加元素到容器
        $container.append($bubble);
        $container.append($window);
        
        // 缓存常用元素
        $chat = $container;
        $header = $window.find('.wizchat-header');
        $messages = $window.find('.wizchat-messages');
        $input = $window.find('.wizchat-input');
        $sendBtn = $window.find('.wizchat-send-btn');
        $typing = $window.find('.wizchat-typing');
    }
    
    /**
     * 添加用户消息到聊天窗口
     * 
     * @param {string} message 用户消息内容
     */
    function addUserMessage(message) {
        const $messageElement = $(`
            <div class="wizchat-message wizchat-message-user flex items-start justify-end">
                <div class="wizchat-bubble wizchat-primary-bg text-white rounded-lg p-3 max-w-[85%]">
                    <p>${escapeHtml(message)}</p>
                </div>
            </div>
        `);
        
        $messages.append($messageElement);
        scrollToBottom();
    }
    
    /**
     * 添加AI消息到聊天窗口
     * 
     * @param {string} message AI消息内容
     */
    function addAIMessage(message) {
        const formattedMessage = formatMessage(message);
        
        const $messageElement = $(`
            <div class="wizchat-message wizchat-message-ai flex items-start">
                <div class="wizchat-avatar wizchat-primary-bg w-8 h-8 rounded-full flex items-center justify-center text-white mr-2 flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div class="wizchat-bubble bg-gray-100 rounded-lg p-3 max-w-[85%]">
                    ${formattedMessage}
                </div>
            </div>
        `);
        
        $messages.append($messageElement);
        scrollToBottom();
    }
    
    /**
     * 显示正在输入指示器
     */
    function showTypingIndicator() {
        $typing.removeClass('hidden');
        scrollToBottom();
    }
    
    /**
     * 隐藏正在输入指示器
     */
    function hideTypingIndicator() {
        $typing.addClass('hidden');
    }
    
    /**
     * 显示错误消息
     * 
     * @param {string} message 错误消息
     */
    function showError(message) {
        const $messageElement = $(`
            <div class="wizchat-message wizchat-message-error flex items-start">
                <div class="wizchat-bubble bg-red-100 text-red-700 rounded-lg p-3 max-w-[85%]">
                    <p>❌ ${escapeHtml(message)}</p>
                </div>
            </div>
        `);
        
        $messages.append($messageElement);
        scrollToBottom();
    }
    
    /**
     * 滚动到最新消息
     */
    function scrollToBottom() {
        if ($messages.length) {
            $messages.scrollTop($messages[0].scrollHeight);
        }
    }
    
    /**
     * 应用主题色
     */
    function applyPrimaryColor() {
        const primaryColor = wizchatSettings.primaryColor || '#4F46E5';
        
        // 添加内联样式
        const style = document.createElement('style');
        style.innerHTML = `
            .wizchat-primary-bg { background-color: ${primaryColor} !important; }
            .wizchat-primary-text { color: ${primaryColor} !important; }
            .wizchat-primary-border { border-color: ${primaryColor} !important; }
        `;
        document.head.appendChild(style);
    }
    
    /**
     * 格式化消息内容
     * 
     * @param {string} message 消息内容
     * @return {string} 格式化后的HTML
     */
    function formatMessage(message) {
        // 转义HTML
        let html = escapeHtml(message);
        
        // 处理换行
        html = html.replace(/\n/g, '<br>');
        
        // 处理链接
        html = html.replace(
            /(https?:\/\/[^\s<]+)/g, 
            '<a href="$1" target="_blank" class="text-blue-600 hover:underline">$1</a>'
        );
        
        // 处理加粗文本 **加粗**
        html = html.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        
        // 处理斜体文本 *斜体*
        html = html.replace(/\*(.*?)\*/g, '<em>$1</em>');
        
        return html;
    }
    
    /**
     * 转义HTML特殊字符
     * 
     * @param {string} text 要转义的文本
     * @return {string} 转义后的文本
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * 保存对话历史到本地存储
     */
    function saveConversation() {
        // 限制历史记录长度，避免存储过大
        if (conversationHistory.length > 50) {
            // 保留最新的20条消息
            conversationHistory = conversationHistory.slice(-20);
        }
        
        try {
            // 保存到本地存储
            localStorage.setItem('wizchat_conversation', JSON.stringify({
                timestamp: Date.now(),
                history: conversationHistory
            }));
        } catch (e) {
            console.error('无法保存聊天历史:', e);
        }
    }
    
    /**
     * 从本地存储加载对话历史
     */
    function loadConversation() {
        try {
            // 从本地存储加载
            const savedData = localStorage.getItem('wizchat_conversation');
            
            if (!savedData) {
                // 没有保存的对话
                return;
            }
            
            const data = JSON.parse(savedData);
            
            // 检查会话是否过期
            const sessionDuration = (wizchatSettings.sessionDuration || 24) * 60 * 60 * 1000; // 转换为毫秒
            const now = Date.now();
            
            if (now - data.timestamp > sessionDuration) {
                // 会话已过期，清除存储
                localStorage.removeItem('wizchat_conversation');
                return;
            }
            
            // 恢复对话历史
            conversationHistory = data.history || [];
            
            // 显示历史消息
            if (conversationHistory.length > 0) {
                // 添加分隔线
                $messages.append('<div class="wizchat-history-separator text-center text-xs text-gray-500 my-2">--- 历史对话 ---</div>');
                
                // 显示最多10条历史消息
                const recentHistory = conversationHistory.slice(-10);
                
                recentHistory.forEach(entry => {
                    if (entry.role === 'user') {
                        addUserMessage(entry.content);
                    } else if (entry.role === 'assistant') {
                        addAIMessage(entry.content);
                    }
                });
                
                // 添加当前会话分隔线
                $messages.append('<div class="wizchat-history-separator text-center text-xs text-gray-500 my-2">--- 当前会话 ---</div>');
            }
        } catch (e) {
            console.error('加载聊天历史失败:', e);
            // 出错时重置对话历史
            conversationHistory = [];
        }
    }
    
    // 当文档准备就绪时初始化
    $(document).ready(init);
    
})(jQuery);
