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
            
            throw new Exception('API错误: ' . $error_message);
        }

        if (!isset($response['choices'][0]['message']['content'])) {
            throw new Exception('无效的API响应格式: 缺少内容字段');
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

        $response = wp_remote_request($api_url, $args);

        if (is_wp_error($response)) {
            throw new Exception('API请求失败: ' . $response->get_error_message());
        }

        $body = wp_remote_retrieve_body($response);
        $status = wp_remote_retrieve_response_code($response);

        if ($status < 200 || $status >= 300) {
            $error_message = '状态码: ' . $status;
            $decoded_body = json_decode($body, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($decoded_body['error']['message'])) {
                $error_message .= ' - ' . $decoded_body['error']['message'];
            }
            
            throw new Exception('API请求返回错误: ' . $error_message);
        }

        $decoded_body = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('无法解析API响应: ' . json_last_error_msg());
        }

        return $decoded_body;
    }

    /**
     * 测试API连接
     *
     * @return array 包含连接测试结果的数组
     */
    public function test_connection() {
        // 检查API密钥是否为空
        if (empty($this->settings['api_key'])) {
            return array(
                'success' => false,
                'message' => 'API密钥未设置'
            );
        }
        
        try {
            // 构建测试请求参数
            $endpoint = '/chat/completions';
            $request_url = $this->settings['base_url'] . $endpoint;
            
            $request_body = array(
                'model' => $this->settings['model'],
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => 'Hello, this is a test message from WizChat plugin.'
                    )
                ),
                'max_tokens' => 5, // 仅需要一个小的回复
                'temperature' => 0.1 // 设置低温度以获得确定性回复
            );
            
            $headers = array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->settings['api_key']
            );
            
            // 初始化cURL
            $ch = curl_init($request_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, wp_json_encode($request_body));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30); // 超时设置为30秒
            
            // 执行请求并获取响应
            $response = curl_exec($ch);
            $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            
            curl_close($ch);
            
            // 检查错误
            if ($curl_error) {
                return array(
                    'success' => false,
                    'message' => '网络错误: ' . $curl_error
                );
            }
            
            // 解析响应
            $result = json_decode($response, true);
            
            // 检查HTTP状态码
            if ($http_status >= 400) {
                $error_message = isset($result['error']['message']) 
                    ? $result['error']['message'] 
                    : '服务器返回错误状态码: ' . $http_status;
                
                return array(
                    'success' => false,
                    'message' => $error_message
                );
            }
            
            // 验证响应格式
            if (isset($result['choices']) && is_array($result['choices']) && !empty($result['choices'])) {
                // 响应有效
                $model_used = isset($result['model']) ? $result['model'] : $this->settings['model'];
                
                return array(
                    'success' => true,
                    'message' => '已成功连接到API，使用模型: ' . $model_used
                );
            } else {
                return array(
                    'success' => false,
                    'message' => '收到响应但格式无效'
                );
            }
        } catch (Exception $e) {
            return array(
                'success' => false,
                'message' => '异常: ' . $e->getMessage()
            );
        }
    }

    /**
     * 记录调试信息
     *
     * @param string $title 日志标题
     * @param mixed $data 日志数据
     */
    private function log_debug($title, $data) {
        // 使用WordPress的错误日志功能记录调试信息
        error_log(sprintf(
            '[WizChat Debug] %s: %s',
            $title,
            is_array($data) || is_object($data) ? wp_json_encode($data) : $data
        ));
    }
}
