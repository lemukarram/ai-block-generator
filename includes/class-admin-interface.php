<?php
class AI_Block_Generator_Admin_Interface {
    private static $instance;

    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    public function add_admin_menu() {
        add_menu_page(
            'AI Block Generator',
            'AI Blocks',
            'manage_options',
            'ai-block-generator',
            [$this, 'render_main_page'],
            'dashicons-block-default'
        );
        
        add_submenu_page(
            'ai-block-generator',
            'Settings',
            'Settings',
            'manage_options',
            'ai-block-settings',
            [$this, 'render_settings_page']
        );
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'ai-block-generator') === false) return;
        
        wp_enqueue_style(
            'ai-block-admin-css',
            AI_BLOCK_GENERATOR_URL . 'assets/css/admin.css',
            [],
            AI_BLOCK_GENERATOR_VERSION
        );
        
        wp_enqueue_script(
            'ai-block-admin-js',
            AI_BLOCK_GENERATOR_URL . 'assets/js/admin.js',
            ['jquery'],
            AI_BLOCK_GENERATOR_VERSION,
            true
        );
        
        wp_localize_script('ai-block-admin-js', 'aiBlockGenerator', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_block_generator_nonce')
        ]);
    }

    public function render_main_page() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_blocks';
        $blocks = $wpdb->get_results("SELECT * FROM $table_name ORDER BY created_at DESC", ARRAY_A);
        
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_block'])) {
            $this->handle_block_generation();
        }
        
        // Handle delete/regenerate actions
        if (isset($_GET['action'])) {
            $this->handle_block_actions();
        }
        
        include AI_BLOCK_GENERATOR_PATH . 'templates/admin-settings.php';
    }

    public function render_settings_page() {
        // Save settings if submitted
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
            update_option('ai_block_generator_api_key', sanitize_text_field($_POST['api_key']));
            update_option('ai_block_generator_model', sanitize_text_field($_POST['model']));
            echo '<div class="notice notice-success"><p>Settings saved!</p></div>';
        }
        
        $api_key = get_option('ai_block_generator_api_key');
        $model = get_option('ai_block_generator_model', 'gemini-pro');
        ?>
        <div class="wrap">
            <h1>AI Block Generator Settings</h1>
            <form method="post">
                <table class="form-table">
                    <tr>
                        <th><label for="api_key">Gemini API Key</label></th>
                        <td>
                            <input type="password" name="api_key" id="api_key" 
                                value="<?php echo esc_attr($api_key); ?>" class="regular-text">
                            <p class="description">Get your API key from <a href="https://ai.google.dev/" target="_blank">Google AI Studio</a></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="model">Model</label></th>
                        <td>
                            <select name="model" id="model" class="regular-text">
                                <option value="gemini-1.5-flash" <?php selected($model, 'gemini-1.5-flash'); ?>>Gemini 1.5 Flash</option>
                                <option value="gemini-pro" <?php selected($model, 'gemini-pro'); ?>>Gemini Pro</option>
                                <option value="gemini-1.0-pro" <?php selected($model, 'gemini-1.0-pro'); ?>>Gemini 1.0 Pro</option>
                            </select>
                            <p class="description">"Gemini 1.5 Flash" is recommended for best performance</p>
                        </td>
                    </tr>
                                    </table>
                <?php submit_button('Save Settings', 'primary', 'save_settings'); ?>
            </form>
        </div>
        <?php
    }

    private function handle_block_generation() {
        check_admin_referer('generate_block');
        
        $name = sanitize_text_field($_POST['block_name']);
        $slug = sanitize_title($_POST['block_slug']);
        $description = sanitize_textarea_field($_POST['block_description']);
        $template = sanitize_textarea_field($_POST['block_template']);
        
        $generator = AI_Block_Generator::instance();
        $result = $generator->generate_block($name, $slug, $description, $template);
        
        if ($result['success']) {
            add_settings_error(
                'ai_block_generator',
                'block_generated',
                'Block generated successfully!',
                'success'
            );
        } else {
            add_settings_error(
                'ai_block_generator',
                'generation_failed',
                'Block generation failed: ' . $result['message'],
                'error'
            );
        }
    }

    private function handle_block_actions() {
        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) return;
        
        $block_id = intval($_GET['id']);
        $generator = AI_Block_Generator::instance();
        
        if ($_GET['action'] === 'delete') {
            global $wpdb;
            $table_name = $wpdb->prefix . 'ai_blocks';
            $block = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $block_id));
            
            if ($block) {
                $file_handler = new AI_Block_Generator_File_Handler();
                $file_handler->delete_block($block->slug);
                
                $wpdb->delete($table_name, ['id' => $block_id]);
                
                add_settings_error(
                    'ai_block_generator',
                    'block_deleted',
                    'Block deleted successfully!',
                    'success'
                );
            }
        } elseif ($_GET['action'] === 'regenerate') {
            $result = $generator->regenerate_block($block_id);
            
            if ($result['success']) {
                add_settings_error(
                    'ai_block_generator',
                    'block_regenerated',
                    'Block regenerated successfully!',
                    'success'
                );
            } else {
                add_settings_error(
                    'ai_block_generator',
                    'regeneration_failed',
                    'Block regeneration failed: ' . $result['message'],
                    'error'
                );
            }
        }
    }
}