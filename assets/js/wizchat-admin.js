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
        const modelSelect = $('select[name="wizchat_settings[model]"]').val();
        
        // 处理自定义模型
        let model = modelSelect;
        if (modelSelect === 'custom') {
            const customModel = $('input[name="wizchat_settings[custom_model]"]').val();
            if (customModel) {
                model = customModel;
            } else {
                $result.html('<span style="color: red;">请输入自定义模型名称</span>');
                return;
            }
        }
        
        // 检查API密钥是否设置
        if (!apiKey) {
            $result.html('<span style="color: red;">请先输入API密钥</span>');
            return;
        }
        
        // 更新UI状态
        $button.prop('disabled', true).text('测试中...');
        $result.html('<span style="color: blue;">正在测试连接...</span>');
        
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
                    $result.html(`<span style="color: green;">${response.message}</span>`);
                } else {
                    $result.html(`<span style="color: red;">${response.message}</span>`);
                }
            },
            error: function(xhr, status, error) {
                // 恢复按钮状态
                $button.prop('disabled', false).text('测试API连接');
                
                // 显示错误信息
                let errorMessage = '请求失败: ' + status;
                
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response && response.message) {
                        errorMessage = response.message;
                    }
                } catch (e) {
                    errorMessage = '无法解析错误响应';
                }
                
                $result.html(`<span style="color: red;">${errorMessage}</span>`);
            }
        });
    }
    
    // 当文档准备就绪时初始化
    $(document).ready(init);
    
})(jQuery);
