<?php
class AI_Block_Generator_API_Handler
{
    private $api_key;
    private $api_url = 'https://generativelanguage.googleapis.com/v1/models/';

    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }

    public function generate_block($prompt)
    {
        $model = get_option('ai_block_generator_model', 'gemini-1.5-flash');
        $model = 'gemini-2.5-flash';
        $url = $this->api_url . $model . ':generateContent?key=' . $this->api_key;

        $data = [
            "contents" => [
                [
                    "role" => "user",
                    "parts" => [["text" => $prompt]]
                ]
            ]
        ];

        $args = [
            'body' => json_encode($data),
            'headers' => [
                'Content-Type' => 'application/json'
            ],
            'timeout' => 120
        ];

        $response = wp_remote_post($url, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => $response->get_error_message()
            ];
        }

        $status = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($status !== 200) {
            $error_msg = 'API Error: ' . $status;
            if (isset($body['error']['message'])) {
                $error_msg .= ' - ' . $body['error']['message'];
            }
            return [
                'success' => false,
                'message' => $error_msg
            ];
        }

        if (isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            return [
                'success' => true,
                'content' => $body['candidates'][0]['content']['parts'][0]['text']
            ];
        }

        return [
            'success' => false,
            'message' => 'Unexpected API response: ' . json_encode($body)
        ];
    }
}