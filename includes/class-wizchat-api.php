<?php
/**
 * WizChat API通信类
 *
 * @package WizChat
 */

// 如果直接访问此文件，则退出
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WizChat_API类 - 处理与OpenAI API的通信
 */
class WizChat_API {
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
    }

    /**
     * 发送聊天请求到OpenAI API
     *
     * @param string $message 用户消息
     * @param array $conversation_history 对话历史
     * @return array 处理后的响应
     * @throws Exception 如果API请求失败
     */
    public function send_chat_request($message, $conversation_history = array()) {
        // 检查API密钥是否设置
        if (empty($this->settings['api_key'])) {
            throw new Exception('API密钥未设置，请在插件设置中配置API密钥。');
        }

        // 准备消息格式
        $messages = $this->prepare_messages($message, $conversation_history);

        // 准备API请求
        $response = $this->make_api_request('chat/completions', array(
            'model' => $this->settings['model'],
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000,
        ));

        // 处理响应
        return $this->process_response($response);
    }

    /**
     * 准备发送给API的消息数组
     *
     * @param string $message 当前用户消息
     * @param array $conversation_history 对话历史
     * @return array 格式化的消息数组
     */
    private function prepare_messages($message, $conversation_history) {
        $messages = array();

        // 添加系统消息
        $messages[] = array(
            'role' => 'system',
            'content' => '你是WizChat，一个由OpenAI驱动的WordPress网站智能客服助手。你的任务是友好地回答用户关于网站内容的问题。保持回答简洁专业，如果不确定答案，可以坦诚说明。',
        );

        // 添加历史消息
        if (!empty($conversation_history) && is_array($conversation_history)) {
            foreach ($conversation_history as $entry) {
                if (isset($entry['role']) && isset($entry['content'])) {
                    $messages[] = array(
                        'role' => sanitize_text_field($entry['role']),
                        'content' => sanitize_text_field($entry['content']),
                    );
                }
            }
        }

        // 添加当前用户消息
        $messages[] = array(
            'role' => 'user',
            'content' => $message,
        );

        return $messages;
    }

    /**
     * 处理API响应
     *
     * @param array $response API响应
     * @return array 处理后的响应
     * @throws Exception 如果响应无效
     */
    private function process_response($response) {
        if (isset($response['error'])) {
            throw new Exception('API错误: ' . $response['error']['message']);
        }

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('无效的API响应');
        }

        return array(
            'message' => $response['choices'][0]['message']['content'],
            'role' => 'assistant',
        );
    }

    /**
     * 发起API请求
     *
     * @param string $endpoint API端点
     * @param array $data 请求数据
     * @return array 响应数据
     * @throws Exception 如果请求失败
     */
    private function make_api_request($endpoint, $data) {
        $api_url = trailingslashit($this->settings['base_url']) . $endpoint;
        
        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->settings['api_key'],
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($data),
            'method' => 'POST',
        );

        // 添加调试日志
        $this->log_debug('API请求', array(
            'url' => $api_url,
            'data' => $data,
        ));

        $response = wp_remote_request($api_url, $args);

        if (is_wp_error($response)) {
            $this->log_debug('API请求失败', array(
                'error' => $response->get_error_message(),
            ));
            throw new Exception('API请求失败: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);

        if ($status !== 200) {
            $this->log_debug('API请求返回非200状态码', array(
                'status' => $status,
                'body' => $body,
            ));
            throw new Exception('API请求返回错误状态码: ' . $status);
        }

        $decoded_body = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_debug('API响应JSON解析失败', array(
                'error' => json_last_error_msg(),
                'body' => $body,
            ));
            throw new Exception('无法解析API响应: ' . json_last_error_msg());
        }

        $this->log_debug('API响应成功', array(
            'response' => $decoded_body,
        ));

        return $decoded_body;
    }

    /**
     * 测试API连接
     *
     * @return bool 连接是否成功
     * @throws Exception 如果连接失败
     */
    public function test_connection() {
        // 检查API密钥是否设置
        if (empty($this->settings['api_key'])) {
            throw new Exception('API密钥未设置');
        }

        // 发送简单的模型列表请求来测试连接
        try {
            $response = $this->make_api_request('models', array());
            return isset($response['data']) && is_array($response['data']);
        } catch (Exception $e) {
            throw new Exception('API连接测试失败: ' . $e->getMessage());
        }
    }

    /**
     * 记录调试信息（如果启用）
     *
     * @param string $title 日志标题
     * @param mixed $data 要记录的数据
     */
    private function log_debug($title, $data) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('WizChat调试 - ' . $title . ': ' . wp_json_encode($data));
        }
    }
}
