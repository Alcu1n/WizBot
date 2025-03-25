/**
 * WizChat管理界面脚本
 * 
 * 处理管理界面中的交互，特别是API连接测试
 */
(function($) {
    'use strict';

    /**
     * 初始化管理界面功能
     */
    function init() {
        // 绑定API测试按钮事件
        $('#wizchat-test-api').on('click', testApiConnection);
        
        console.log('WizChat管理界面已初始化');
    }

    /**
     * 测试API连接
     */
    function testApiConnection() {
        const $button = $('#wizchat-test-api');
        const $result = $('#wizchat-api-test-result');
        
        // 获取当前表单中的API设置
        const apiKey = $('input[name="wizchat_settings[api_key]"]').val();
        const baseUrl = $('input[name="wizchat_settings[base_url]"]').val();
        const model = $('select[name="wizchat_settings[model]"]').val();
        
        // 检查API密钥是否设置
        if (!apiKey) {
            $result.html('<span class="text-red-500">请先输入API密钥</span>');
            return;
        }
        
        // 更新UI状态
        $button.prop('disabled', true).text('测试中...');
        $result.html('<span class="text-blue-500">正在测试连接...</span>');
        
        // 发送API测试请求
        $.ajax({
            url: wizchatAdmin.apiTestUrl,
            method: 'POST',
            data: JSON.stringify({
                api_key: apiKey,
                base_url: baseUrl,
                model: model
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', wizchatAdmin.nonce);
            },
            success: function(response) {
                // 恢复按钮状态
                $button.prop('disabled', false).text('测试API连接');
                
                // 显示测试结果
                if (response.success) {
                    $result.html(`<span class="text-green-500">${response.message}</span>`);
                } else {
                    $result.html(`<span class="text-red-500">${response.message}</span>`);
                }
                
                // 输出调试信息
                console.log('API测试响应:', response);
            },
            error: function(xhr, status, error) {
                // 恢复按钮状态
                $button.prop('disabled', false).text('测试API连接');
                
                // 显示错误信息
                $result.html(`<span class="text-red-500">请求失败: ${status}</span>`);
                
                // 输出调试信息
                console.error('API测试请求失败:', error, xhr.responseText);
            }
        });
    }
    
    // 当文档准备就绪时初始化
    $(document).ready(init);
    
})(jQuery);
