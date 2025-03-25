<?php
/**
 * WizChat_API类 - 处理OpenAI API的通信
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
     * 是否启用调试
     *
     * @var bool
     */
    private $debug = false;

    /**
     * 构造函数
     *
     * @param array $settings 插件设置
     */
    public function __construct($settings) {
        $this->settings = $settings;
        $this->debug = defined('WP_DEBUG') && WP_DEBUG;
    }

    /**
     * 发送聊天请求到OpenAI API
     *
     * @param string $message 用户消息
     * @param array $conversation_history 对话历史
     * @return array 响应数据
     * @throws Exception 如果请求失败
     */
    public function send_chat_request($message, $conversation_history = array()) {
        if (empty($this->settings['api_key'])) {
            throw new Exception('API密钥未设置，请在插件设置中配置API密钥');
        }

        // 准备发送的消息
        $messages = $this->prepare_messages($message, $conversation_history);

        // 发送API请求
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

        // 添加历史消息（限制对话长度以避免超出token限制）
        if (!empty($conversation_history) && is_array($conversation_history)) {
            // 如果历史消息太多，只保留最近的10条
            if (count($conversation_history) > 10) {
                $conversation_history = array_slice($conversation_history, -10);
            }
            
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
            $error_message = isset($response['error']['message']) ? $response['error']['message'] : '未知API错误';
            $error_type = isset($response['error']['type']) ? $response['error']['type'] : '未知';
            $error_code = isset($response['error']['code']) ? $response['error']['code'] : '';
            
            $this->log_debug('API错误响应', array(
                'message' => $error_message,
                'type' => $error_type,
                'code' => $error_code
            ));
            
            throw new Exception('API错误: ' . $error_message);
        }

        if (!isset($response['choices'][0]['message']['content'])) {
            $this->log_debug('无效的API响应格式', $response);
            throw new Exception('无效的API响应: 缺少内容字段');
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
        $api_url = rtrim($this->settings['base_url'], '/') . '/' . $endpoint;
        
        $args = array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->settings['api_key'],
                'Content-Type' => 'application/json',
            ),
            'body' => wp_json_encode($data),
            'method' => 'POST',
            'data_format' => 'body',
        );

        // 添加调试日志
        $this->log_debug('API请求', array(
            'url' => $api_url,
            'endpoint' => $endpoint,
            'model' => isset($data['model']) ? $data['model'] : 'N/A',
        ));

        $response = wp_remote_request($api_url, $args);

        if (is_wp_error($response)) {
            $this->log_debug('API请求失败', array(
                'error' => $response->get_error_message(),
                'code' => $response->get_error_code(),
            ));
            throw new Exception('API请求失败: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);

        if ($status < 200 || $status >= 300) {
            $this->log_debug('API请求返回非200状态码', array(
                'status' => $status,
                'body' => $body,
                'headers' => wp_remote_retrieve_headers($response),
            ));
            
            // 尝试从响应中获取更详细的错误信息
            $error_message = '状态码: ' . $status;
            $decoded_body = json_decode($body, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded_body['error']['message'])) {
                $error_message .= ' - ' . $decoded_body['error']['message'];
            }
            
            throw new Exception('API请求返回错误: ' . $error_message);
        }

        $decoded_body = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->log_debug('API响应JSON解析失败', array(
                'error' => json_last_error_msg(),
                'body' => substr($body, 0, 1000), // 记录部分响应内容，避免日志过大
            ));
            throw new Exception('无法解析API响应: ' . json_last_error_msg());
        }

        $this->log_debug('API响应成功', array(
            'status' => $status,
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

        // 发送简单的聊天完成请求来测试连接
        // 使用经典的"Hello, World"测试OpenAI API模型
        try {
            $test_data = array(
                'model' => $this->settings['model'],
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Say "Hello, WizChat API test successful!"',
                    ),
                ),
                'max_tokens' => 20,
            );
            
            $response = $this->make_api_request('chat/completions', $test_data);
            
            // 验证响应格式
            if (!isset($response['choices'][0]['message']['content'])) {
                throw new Exception('API响应格式无效，请检查API版本和权限');
            }
            
            return true;
        } catch (Exception $e) {
            // 重新抛出异常，但提供更友好的错误信息
            throw new Exception('API连接测试失败: ' . $e->getMessage());
        }
    }

    /**
     * 记录调试信息
     *
     * @param string $title 日志标题
     * @param mixed $data 日志数据
     */
    private function log_debug($title, $data) {
        if (!$this->debug) {
            return;
        }

        // 使用WordPress的错误日志功能记录调试信息
        error_log(sprintf(
            '[WizChat Debug] %s: %s',
            $title,
            is_array($data) || is_object($data) ? wp_json_encode($data) : $data
        ));
    }
}
