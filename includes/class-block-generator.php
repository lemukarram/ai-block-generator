<?php
class AI_Block_Generator {
    private static $instance;
    private $file_handler;
    private $api_handler;

    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->file_handler = new AI_Block_Generator_File_Handler();
        $api_key = get_option('ai_block_generator_api_key');
        $this->api_handler = new AI_Block_Generator_API_Handler($api_key);
        
        add_action('init', [$this, 'register_generated_blocks']);
    }

    public function generate_block($name, $slug, $description, $template) {
        // Prepare the AI prompt
        $prompt = $this->prepare_prompt($name, $slug, $description, $template);
        
        // Send request to Gemini
        $response = $this->api_handler->generate_block($prompt);
        
        if (!$response['success']) {
            return $response;
        }
        
        // Parse AI response
        $files = $this->parse_ai_response($response['content'], $slug);
        
        // Create files
        $file_results = $this->file_handler->create_block_structure($slug, $files);
        
        // Save to database
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_blocks';
        
        $data = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'template' => $template,
            'prompt' => $prompt,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        ];
        
        $wpdb->replace($table_name, $data);
        
        return [
            'success' => true,
            'files' => $file_results
        ];
    }

    public function regenerate_block($block_id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_blocks';
        $block = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $block_id), ARRAY_A);
        
        if (!$block) {
            return [
                'success' => false,
                'message' => 'Block not found'
            ];
        }
        
        // Delete existing files
        $this->file_handler->delete_block($block['slug']);
        
        // Regenerate with same data
        return $this->generate_block(
            $block['name'],
            $block['slug'],
            $block['description'],
            $block['template']
        );
    }

    private function prepare_prompt($name, $slug, $description, $template) {
        return <<<PROMPT
    Create a production-ready ACF WordPress block with these specifications:
    
    ## BLOCK REQUIREMENTS
    - Block Name: $name
    - Block Slug: $slug
    - Description: $description
    - Template/Design: $template
    
    ## FILE STRUCTURE
    Generate these files with exact content:
    
    1. block.json - WordPress block registration (JSON format)
       - Must include ACF configuration
       - Must specify style/script paths
       - Example structure:
         {
           "name": "$slug",
           "title": "$name",
           "description": "$description",
           "category": "ai-blocks",
           "icon": "block-default",
           "acf": {
             "mode": "edit",
             "renderTemplate": "$slug.php"
           },
           "style": "css/$slug.css",
           "script": "js/$slug.js"
         }
    
    2. functions.php - ACF field registration
       - Must use unique function prefixes ({$slug}_)
       - Must include proper field group configuration
       - Must use sanitization functions where needed
       - Must include the following field types based on template:
         - Text, Wysiwyg, Image, Color Picker, Repeater (if needed)
       - Must include location rules for the block
    
    3. $slug.php - Main template file
       - Must use proper escaping (esc_html(), esc_attr(), etc.)
       - Must include ACF field output
       - Must include preview image handling
       - Must include proper HTML structure
    
    4. css/$slug.css - CSS styles
       - Must use modern CSS (flexbox/grid)
       - Must include responsive styles
       - Must use BEM naming convention
    
    5. js/$slug.js - JavaScript functionality
       - Must use vanilla JavaScript (no jQuery)
       - Must include proper event handling
       - Must include comments for functionality
    
    ## CODING STANDARDS
    - Use WordPress and ACF coding standards
    - Add DocBlocks for all functions
    - Use strict comparisons (=== instead of ==)
    - Sanitize all inputs and escape all outputs
    - Use wp_enqueue_style/wp_enqueue_script in functions.php if needed
    - Prefix all functions with "{$slug}_"
    - Use get_stylesheet_directory_uri() for asset URLs
    
    ## RESPONSE FORMAT
    Return ONLY the file contents in this exact format with NO additional text:
    
    === block.json ===
    {json content}
    
    === functions.php ===
    <?php
    {php code}
    
    === $slug.php ===
    {php/html code}
    
    === css/$slug.css ===
    {css code}
    
    === js/$slug.js ===
    {javascript code}
    
    DO NOT include any explanations, comments, or markdown outside the code blocks.
    PROMPT;
    }

    private function prepare_prompt00($name, $slug, $description, $template) {
        return <<<PROMPT
    Create an ACF WordPress block with these specifications:
    
    ## BLOCK DETAILS
    - Name: $name
    - Slug: $slug
    - Description: $description
    - Template/Design: $template
    
    ## FILE STRUCTURE
    Generate these files with exact content:
    
    1. $slug/block.json - Block configuration (JSON format)
       {
         "name": "$slug",
         "title": "$name",
         "description": "$description",
         "category": "ai-blocks",
         "icon": "block-default",
         "acf": {
           "mode": "preview",
           "renderTemplate": "$slug.php"
         },
         "style": "css/$slug.css",
         "script": "js/$slug.js"
       }
    
    2. $slug/functions.php - PHP for ACF field registration
       add_action('acf/init', '{$slug}_register_fields');
       function {$slug}_register_fields() {
           // ACF field group configuration
           acf_add_local_field_group(array(
               'key' => 'group_{$slug}',
               'title' => '$name Fields',
               'fields' => array(/* fields array */),
               'location' => array(
                   array(
                       array(
                           'param' => 'block',
                           'operator' => '==',
                           'value' => 'acf/{$slug}',
                       ),
                   ),
               ),
           ));
       }
    
    3. $slug/$slug.php - Main template file (PHP/HTML)
    
    4. $slug/css/$slug.css - CSS styles
       /* Styles for $name block */
    
    5. $slug/js/$slug.js - JavaScript functionality
       // JavaScript for $name block
    
    ## CODING STANDARDS
    - Use WordPress and ACF coding standards
    - Proper sanitization/escaping for all outputs
    - Modern CSS (flexbox/grid)
    - Vanilla JavaScript (no jQuery)
    - Add helpful comments
    
    ## RESPONSE FORMAT
    Return ONLY the file contents in this exact format:
    
    === block.json ===
    {json content}
    
    === functions.php ===
    <?php
    {php code}
    
    === $slug.php ===
    {php/html code}
    
    === css/$slug.css ===
    {css code}
    
    === js/$slug.js ===
    {javascript code}
    
    DO NOT include any other text or explanations.
    PROMPT;
    }
    
    private function prepare_prompt0($name, $slug, $description, $template) {
        return <<<PROMPT
        Create a WordPress block with these specifications:
        
        ## BLOCK DETAILS
        - Name: $name
        - Slug: $slug
        - Description: $description
        - Template/Design: $template
        
        ## FILE STRUCTURE
        Generate these files with exact content:
        
        1. $slug/block.json - Block configuration (JSON format)
        2. $slug/functions.php - PHP for ACF registration and helpers
        3. $slug/$slug.php - Main template file (PHP/HTML)
        4. $slug/css/$slug.css - CSS styles
        5. $slug/js/$slug.js - JavaScript functionality
        
        ## CODING STANDARDS
        - WordPress coding standards
        - Proper sanitization/escaping
        - Modern CSS (flexbox/grid)
        - Vanilla JavaScript (no jQuery)
        - Add helpful comments
        
        ## RESPONSE FORMAT
        Return ONLY the file contents in this exact format:
        
        === block.json ===
        {json content}
        
        === functions.php ===
        <?php
        {php code}
        
        === $slug.php ===
        {php/html code}
        
        === css/$slug.css ===
        {css code}
        
        === js/$slug.js ===
        {javascript code}
        
        DO NOT include any other text or explanations.
        PROMPT;
    }
    private function parse_ai_response($response, $slug) {
        $files = [];
        $sections = preg_split('/=== ?(.*?) ===/', $response, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        
        for ($i = 0; $i < count($sections); $i += 2) {
            $file_name = trim($sections[$i]);
            $content = trim($sections[$i+1]);
            
            // Map generic names to actual file paths
            if ($file_name === 'block.json') {
                $files['block.json'] = $content;
            } elseif ($file_name === 'functions.php') {
                $files['functions.php'] = $content;
            } elseif ($file_name === "$slug.php") {
                $files["$slug.php"] = $content;
            } elseif ($file_name === "css/$slug.css") {
                $files["css/$slug.css"] = $content;
            } elseif ($file_name === "js/$slug.js") {
                $files["js/$slug.js"] = $content;
            }
        }
        
        return $files;
    }

    // Add this method to the Block Generator class
    public function register_single_block($slug) {
        $file_handler = new AI_Block_Generator_File_Handler();
        return $file_handler->register_block($slug);
    }

    public function register_generated_blocks() {

        
        global $wpdb;
        $table_name = $wpdb->prefix . 'ai_blocks';
        $blocks = $wpdb->get_results("SELECT slug FROM $table_name", ARRAY_A);
        
        foreach ($blocks as $block) {
            $slug = $block['slug'];
            $block_dir = AI_BLOCK_GENERATOR_UPLOAD_DIR . $slug . '/';
            
            // Include functions.php if exists
            $functions_file = $block_dir . 'functions.php';
            if (file_exists($functions_file)) {
                
                //include_once $functions_file;
            }


            
        }
    }
}