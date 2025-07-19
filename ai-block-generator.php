<?php
/**
 * Plugin Name: AI Block Generator
 * Description: Generate custom WordPress blocks using Gemini AI
 * Version: 1.0.0
 * Author: Mukarram Hussain
 * License: GPL-2.0+
 */

defined('ABSPATH') || exit;

// Define plugin constants
define('AI_BLOCK_GENERATOR_VERSION', '1.0.0');
define('AI_BLOCK_GENERATOR_PATH', plugin_dir_path(__FILE__));
define('AI_BLOCK_GENERATOR_URL', plugin_dir_url(__FILE__));
define('AI_BLOCK_GENERATOR_UPLOAD_DIR', get_stylesheet_directory() . '/ai-blocks/');
// Create upload directory if not exists
if (!file_exists(AI_BLOCK_GENERATOR_UPLOAD_DIR)) {
    mkdir(AI_BLOCK_GENERATOR_UPLOAD_DIR, 0755, true);
}

// Include required files
require_once AI_BLOCK_GENERATOR_PATH . 'includes/class-api-handler.php';
require_once AI_BLOCK_GENERATOR_PATH . 'includes/class-file-handler.php';
require_once AI_BLOCK_GENERATOR_PATH . 'includes/class-block-generator.php';
require_once AI_BLOCK_GENERATOR_PATH . 'includes/class-admin-interface.php';

// Initialize plugin
add_action('plugins_loaded', function() {
    AI_Block_Generator_Admin_Interface::instance();
    AI_Block_Generator::instance();
});

// Register activation/deactivation hooks
register_activation_hook(__FILE__, 'ai_block_generator_activate');
register_deactivation_hook(__FILE__, 'ai_block_generator_deactivate');

function ai_block_generator_activate() {
    // Create database table for storing blocks
    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_blocks';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        slug varchar(100) NOT NULL,
        description text NOT NULL,
        template text NOT NULL,
        prompt text NOT NULL,
        created_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        updated_at datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY slug (slug)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Add default options
    add_option('ai_block_generator_api_key', '');
    // Update default model to gemini-1.5-flash
    add_option('ai_block_generator_model', 'gemini-1.5-flash');
}

function ai_block_generator_deactivate() {
    // Clean up if needed
}

// // Register all blocks after themes and plugins are fully loaded
// add_action('wp_loaded', function() {
//     if (class_exists('AI_Block_Generator')) {
//         $generator = AI_Block_Generator::instance();
        
//         global $wpdb;
//         $table_name = $wpdb->prefix . 'ai_blocks';
//         $blocks = $wpdb->get_results("SELECT slug FROM $table_name", ARRAY_A);
        
//         foreach ($blocks as $block) {
//             $generator->register_single_block($block['slug']);
//         }
//     } else {
//         // Add error notice if class is missing
//         add_action('admin_notices', function() {
//             echo '
//             <div class="notice notice-error">
//                 <p>❌ AI Block Generator class not found! Ensure the required plugin is active.</p>
//             </div>';
//         });
//     }
// }, 5); // Priority 5 ensures early execution after wp_loaded starts


// 1. Include block function files EARLY (in theme's functions.php)
function include_ai_block_functions() {
    if (is_dir(AI_BLOCK_GENERATOR_UPLOAD_DIR)) {
        $block_functions = glob(AI_BLOCK_GENERATOR_UPLOAD_DIR . '*/functions.php');
        
        foreach ($block_functions as $file) {
            if (file_exists($file) && is_readable($file)) {
                include_once $file;
                
                // Add admin notice for included files
                add_action('admin_notices', function() use ($file) {
                    $block_name = basename(dirname($file));
                    ?>
                    <div class="notice notice-success is-dismissible">
                        <p>✅ Included block functions: <code><?php echo esc_html($block_name); ?></code></p>
                    </div>
                    <?php
                });
            }
        }
    }
}
add_action('after_setup_theme', 'include_ai_block_functions', 5); // Early priority

// 2. Register blocks PROPERLY with ACF
add_action('acf/init', 'register_ai_blocks_with_acf');
function register_ai_blocks_with_acf() {
    if (!function_exists('acf_register_block_type')) {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p>❌ ACF Pro is required for block registration!</p>
            </div>
            <?php
        });
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'ai_blocks';
    $blocks = $wpdb->get_results("SELECT slug FROM $table_name", ARRAY_A);
    
    foreach ($blocks as $block) {
        $slug = $block['slug'];
        
        acf_register_block_type(array(
            'name'              => $slug,
            'title'             => ucwords(str_replace('-', ' ', $slug)),
            'render_template'   => AI_BLOCK_GENERATOR_UPLOAD_DIR . "{$slug}/{$slug}.php",
            'category'          => 'ai-blocks',
            'icon'              => 'block-default',
            'keywords'          => array($slug, 'ai block'),
            'supports'          => array(
                'align' => true,
                'mode'  => true,
                'jsx'   => true
            )
        ));
    }
}