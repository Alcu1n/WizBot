/**
 * WizChat前端脚本
 * 
 * 处理聊天界面交互和API通信
 */
(function($) {
    'use strict';
    
    // 缓存DOM元素
    let $chat, $bubble, $window, $header, $messages, $input, $sendBtn, $typing, $form, $error;
    
    // 聊天状态
    let isOpen = false;
    let isSending = false;
    let conversationHistory = [];
    
    /**
     * 初始化聊天界面
     */
    function init() {
        // 缓存现有DOM元素
        cacheElements();
        
        // 绑定事件
        bindEvents();
        
        // 加载之前的对话
        loadConversation();
        
        // 应用主题色
        applyPrimaryColor();

        console.log("WizChat初始化完成!");
    }
    
    /**
     * 缓存DOM元素
     */
    function cacheElements() {
        $chat = $('#wizchat-container');
        $bubble = $('#wizchat-bubble');
        $window = $('#wizchat-chat-window');
        $header = $window.find('.wizchat-header');
        $messages = $('#wizchat-messages');
        $input = $('#wizchat-message-input');
        $form = $('#wizchat-message-form');
        $sendBtn = $form.find('button[type="submit"]');
        $typing = $('.wizchat-typing');
        $error = $('#wizchat-error');

        console.log("DOM元素缓存完成", {
            chat: $chat.length > 0,
            bubble: $bubble.length > 0,
            window: $window.length > 0,
            messages: $messages.length > 0,
            form: $form.length > 0
        });
    }
    
    /**
     * 绑定事件处理程序
     */
    function bindEvents() {
        // 气泡点击事件
        $bubble.on('click', toggleChatWindow);
        
        // 关闭按钮点击事件
        $('#wizchat-close').on('click', closeChatWindow);
        
        // 发送按钮点击事件 - 使用表单提交事件
        $form.on('submit', function(e) {
            e.preventDefault();
            sendMessage();
        });
        
        // 输入框回车事件
        $input.on('keypress', function(e) {
            if (e.which === 13 && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
        
        // 窗口大小变化事件
        $(window).on('resize', adjustChatWindowPosition);
    }
    
    /**
     * 切换聊天窗口显示状态
     */
    function toggleChatWindow() {
        console.log("切换聊天窗口");
        
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
        console.log("打开聊天窗口");
        
        if (!isOpen) {
            $window.removeClass('scale-0').addClass('scale-100');
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
        if (isOpen) {
            $window.removeClass('scale-100').addClass('scale-0');
            $bubble.removeClass('hidden');
            isOpen = false;
        }
    }
    
    /**
     * 调整聊天窗口位置
     */
    function adjustChatWindowPosition() {
        // 获取窗口尺寸
        const windowWidth = $(window).width();
        
        if (windowWidth < 640) {
            // 移动设备上窗口位置居中
            $window.css({
                'width': 'calc(100% - 32px)',
                'left': '16px',
                'right': 'auto'
            });
        }
    }
    
    /**
     * 发送用户消息
     */
    function sendMessage() {
        const message = $input.val().trim();
        
        // 如果消息为空或正在发送中，则不处理
        if (!message || isSending) {
            return;
        }
        
        // 标记为发送中状态
        isSending = true;
        
        // 清空输入框
        $input.val('');
        
        // 添加用户消息到聊天窗口
        addUserMessage(message);
        
        // 保存消息到历史记录
        conversationHistory.push({role: 'user', content: message});
        
        // 限制历史记录长度，避免超出token限制
        if (conversationHistory.length > 10) {
            // 保留最新的10条消息
            conversationHistory = conversationHistory.slice(-10);
        }
        
        // 显示AI正在输入指示器
        showTypingIndicator();
        
        // 发送到API
        $.ajax({
            url: wizchatSettings.apiUrl,
            method: 'POST',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wizchatSettings.nonce);
            },
            data: {
                message: message,
                history: conversationHistory
            },
            success: function(response) {
                // 隐藏输入指示器
                hideTypingIndicator();
                
                if (response && response.message) {
                    // 添加AI回复到聊天窗口
                    addAIMessage(response.message);
                    
                    // 保存到历史记录
                    conversationHistory.push({role: 'assistant', content: response.message});
                    
                    // 保存对话到本地存储
                    saveConversation();
                } else {
                    showError('接收到无效的响应');
                }
                
                // 重置发送状态
                isSending = false;
            },
            error: function(xhr, status, error) {
                // 隐藏输入指示器
                hideTypingIndicator();
                
                let errorMessage = '发送消息时出错';
                
                // 尝试解析响应中的错误信息
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.error) {
                        errorMessage = response.error;
                    }
                } catch (e) {
                    console.error('解析错误响应失败', e);
                }
                
                // 显示错误信息
                showError(errorMessage);
                
                // 记录到控制台以便调试
                console.error('API请求失败', {status, error, xhr});
                
                // 重置发送状态
                isSending = false;
            }
        });
    }
    
    /**
     * 添加用户消息到聊天窗口
     * 
     * @param {string} message 用户消息内容
     */
    function addUserMessage(message) {
        const messageHtml = `
            <div class="wizchat-message wizchat-message-user flex items-end justify-end">
                <div class="wizchat-bubble wizchat-primary-bg text-white rounded-lg p-3 max-w-[85%]">
                    <p>${escapeHtml(message)}</p>
                </div>
            </div>
        `;
        
        $messages.append(messageHtml);
        scrollToBottom();
    }
    
    /**
     * 添加AI消息到聊天窗口
     * 
     * @param {string} message AI消息内容
     */
    function addAIMessage(message) {
        const formattedMessage = formatMessage(message);
        
        const messageHtml = `
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
        `;
        
        $messages.append(messageHtml);
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
        
        // 5秒后自动隐藏错误消息
        setTimeout(function() {
            $error.addClass('hidden');
        }, 5000);
    }
    
    /**
     * 滚动到最新消息
     */
    function scrollToBottom() {
        $messages.scrollTop($messages[0].scrollHeight);
    }
    
    /**
     * 应用主题色
     */
    function applyPrimaryColor() {
        if (typeof wizchatSettings !== 'undefined' && wizchatSettings.primaryColor) {
            const primaryColor = wizchatSettings.primaryColor;
            
            // 使用CSS变量设置主题色
            document.documentElement.style.setProperty('--wizchat-primary-color', primaryColor);
            
            console.log('应用主题色:', primaryColor);
        }
    }
    
    /**
     * 格式化消息内容
     * 
     * @param {string} message 消息内容
     * @return {string} 格式化后的HTML
     */
    function formatMessage(message) {
        if (!message) return '';
        
        // 转义HTML
        let formatted = escapeHtml(message);
        
        // 将URL转换为链接
        formatted = formatted.replace(
            /(https?:\/\/[^\s]+)/g, 
            '<a href="$1" target="_blank" class="wizchat-primary-text underline">$1</a>'
        );
        
        // 将**粗体**转换为<strong>
        formatted = formatted.replace(
            /\*\*(.*?)\*\*/g,
            '<strong>$1</strong>'
        );
        
        // 将*斜体*转换为<em>
        formatted = formatted.replace(
            /\*(.*?)\*/g,
            '<em>$1</em>'
        );
        
        // 替换换行符为<br>
        formatted = formatted.replace(/\n/g, '<br>');
        
        return formatted;
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
        // 准备会话数据
        const sessionData = {
            messages: conversationHistory,
            timestamp: new Date().getTime()
        };
        
        try {
            // 保存到localStorage
            localStorage.setItem('wizchat_conversation', JSON.stringify(sessionData));
        } catch (e) {
            console.error('保存对话历史失败', e);
        }
    }
    
    /**
     * 从本地存储加载对话历史
     */
    function loadConversation() {
        try {
            // 从localStorage获取数据
            const saved = localStorage.getItem('wizchat_conversation');
            
            if (saved) {
                const sessionData = JSON.parse(saved);
                
                // 检查会话是否过期
                const now = new Date().getTime();
                const sessionDuration = 24 * 60 * 60 * 1000; // 默认24小时
                
                // 如果有设置会话持续时间，则使用设置值
                if (typeof wizchatSettings !== 'undefined' && wizchatSettings.sessionDuration) {
                    sessionDuration = parseInt(wizchatSettings.sessionDuration) * 60 * 60 * 1000;
                }
                
                // 检查会话是否过期
                if (now - sessionData.timestamp > sessionDuration) {
                    // 会话已过期，清除存储
                    localStorage.removeItem('wizchat_conversation');
                    console.log('会话已过期，已清除存储');
                    return;
                }
                
                // 恢复历史记录
                if (Array.isArray(sessionData.messages)) {
                    conversationHistory = sessionData.messages;
                    
                    // 限制加载的消息数量，避免过多
                    if (conversationHistory.length > 10) {
                        conversationHistory = conversationHistory.slice(-10);
                    }
                    
                    // 在聊天界面显示历史消息
                    conversationHistory.forEach(msg => {
                        if (msg.role === 'user') {
                            addUserMessage(msg.content);
                        } else if (msg.role === 'assistant') {
                            addAIMessage(msg.content);
                        }
                    });
                    
                    console.log('成功加载历史对话', conversationHistory.length);
                }
            }
        } catch (e) {
            console.error('加载对话历史失败', e);
        }
    }
    
    // 当文档准备就绪时初始化
    $(document).ready(init);
    
})(jQuery);
