/**
 * WizChat前端脚本
 * 
 * 处理聊天界面交互和API通信
 */
(function($) {
    'use strict';

    // 聊天界面元素
    const $bubble = $('#wizchat-bubble');
    const $chatWindow = $('#wizchat-chat-window');
    const $closeButton = $('#wizchat-close');
    const $messages = $('#wizchat-messages');
    const $form = $('#wizchat-message-form');
    const $input = $('#wizchat-message-input');
    const $error = $('#wizchat-error');
    const $typing = $('.wizchat-typing');

    // 聊天历史记录
    let conversationHistory = [];
    
    // 对话持久化相关
    const STORAGE_KEY = 'wizchat_conversation';
    const SESSION_DURATION = wizchatSettings.sessionDuration || 24; // 小时
    
    /**
     * 初始化聊天界面
     */
    function init() {
        // 绑定事件
        bindEvents();
        
        // 加载历史对话
        loadConversation();
        
        // 调整聊天界面位置
        adjustChatWindowPosition();
        
        // 输出调试信息
        console.log('WizChat初始化完成, 设置:', wizchatSettings);
    }
    
    /**
     * 绑定事件处理程序
     */
    function bindEvents() {
        // 点击气泡打开聊天窗口
        $bubble.on('click', toggleChatWindow);
        
        // 点击关闭按钮关闭聊天窗口
        $closeButton.on('click', closeChatWindow);
        
        // 提交表单发送消息
        $form.on('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
        
        // 窗口调整大小时重新计算位置
        $(window).on('resize', adjustChatWindowPosition);
    }
    
    /**
     * 切换聊天窗口显示状态
     */
    function toggleChatWindow() {
        const isOpen = $chatWindow.hasClass('scale-100');
        
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
        $chatWindow.removeClass('scale-0').addClass('scale-100');
        $bubble.find('svg').toggleClass('hidden');
        
        // 滚动到最新消息
        scrollToBottom();
    }
    
    /**
     * 关闭聊天窗口
     */
    function closeChatWindow() {
        $chatWindow.removeClass('scale-100').addClass('scale-0');
        $bubble.find('svg').toggleClass('hidden');
    }
    
    /**
     * 调整聊天窗口位置
     */
    function adjustChatWindowPosition() {
        const position = wizchatSettings.bubblePosition || 'right';
        const originClass = position === 'right' ? 'origin-bottom-right' : 'origin-bottom-left';
        
        $chatWindow.removeClass('origin-bottom-right origin-bottom-left').addClass(originClass);
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
        
        // 清空输入框
        $input.val('');
        
        // 添加用户消息到聊天窗口
        addUserMessage(message);
        
        // 显示AI正在输入
        showTypingIndicator();
        
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
                
                // 显示错误消息
                let errorMessage = '请求失败';
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    errorMessage = `请求失败: ${status}`;
                }
                
                showError(errorMessage);
                console.error('API请求失败:', error, xhr.responseText);
            }
        });
        
        // 添加到对话历史
        conversationHistory.push({
            role: 'user',
            content: message
        });
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
        $error.text(message).removeClass('hidden');
        
        // 3秒后自动隐藏
        setTimeout(function() {
            $error.addClass('hidden');
        }, 3000);
    }
    
    /**
     * 滚动到最新消息
     */
    function scrollToBottom() {
        $messages.scrollTop($messages[0].scrollHeight);
    }
    
    /**
     * 格式化消息内容
     * 
     * @param {string} message 消息内容
     * @return {string} 格式化后的HTML
     */
    function formatMessage(message) {
        // 转义HTML
        let formattedMessage = escapeHtml(message);
        
        // 将URL转换为链接
        formattedMessage = formattedMessage.replace(
            /((http|https):\/\/[^\s]+)/g, 
            '<a href="$1" target="_blank" class="wizchat-primary-text underline">$1</a>'
        );
        
        // 将换行符转换为<br>
        formattedMessage = formattedMessage.replace(/\n/g, '<br>');
        
        return `<p>${formattedMessage}</p>`;
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
        if (conversationHistory.length === 0) {
            return;
        }
        
        const data = {
            timestamp: Date.now(),
            history: conversationHistory
        };
        
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
            console.log('对话历史已保存', conversationHistory.length);
        } catch (e) {
            console.error('保存对话历史失败', e);
        }
    }
    
    /**
     * 从本地存储加载对话历史
     */
    function loadConversation() {
        try {
            const data = JSON.parse(localStorage.getItem(STORAGE_KEY));
            
            if (!data || !data.timestamp || !data.history) {
                return;
            }
            
            // 检查会话是否过期
            const now = Date.now();
            const expirationTime = SESSION_DURATION * 60 * 60 * 1000; // 转换为毫秒
            
            if (now - data.timestamp > expirationTime) {
                // 会话已过期，清除存储
                localStorage.removeItem(STORAGE_KEY);
                return;
            }
            
            // 加载历史对话
            conversationHistory = data.history;
            
            // 在界面上显示历史消息
            conversationHistory.forEach(function(entry) {
                if (entry.role === 'user') {
                    addUserMessage(entry.content);
                } else if (entry.role === 'assistant') {
                    addAIMessage(entry.content);
                }
            });
            
            console.log('已加载对话历史', conversationHistory.length);
        } catch (e) {
            console.error('加载对话历史失败', e);
        }
    }
    
    // 当文档准备就绪时初始化
    $(document).ready(init);
    
})(jQuery);
