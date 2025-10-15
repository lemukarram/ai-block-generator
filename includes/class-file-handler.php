<?php
class AI_Block_Generator_File_Handler {
    public function create_block_structure($slug, $files) {
        $block_dir = AI_BLOCK_GENERATOR_UPLOAD_DIR . $slug . '/';
        
        if (!file_exists($block_dir)) {
            mkdir($block_dir, 0755, true);
            mkdir($block_dir . 'css/', 0755, true);
            mkdir($block_dir . 'js/', 0755, true);
        }

        $created_files = [];
        
        foreach ($files as $file_path => $content) {
            $full_path = $block_dir . $file_path;
            $created_files[$file_path] = file_put_contents($full_path, $content);
        }

        return $created_files;
    }

    public function delete_block($slug) {
        $block_dir = AI_BLOCK_GENERATOR_UPLOAD_DIR . $slug . '/';
        
        if (file_exists($block_dir)) {
            $this->rrmdir($block_dir);
            return true;
        }
        
        return false;
    }

    private function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->rrmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }

    // Add this method to the File Handler class
    public function register_block($slug) {
        $block_dir = AI_BLOCK_GENERATOR_UPLOAD_DIR . $slug . '/';
        $block_json = $block_dir . 'block.json';
        $block_functions = $block_dir. 'functions.php';
        if (function_exists('acf_register_block_type') && file_exists($block_json)) {
            // Register with ACF
            acf_register_block_type(array(
                'name'              => $slug,
                'title'             => ucwords(str_replace('-', ' ', $slug))
            ));

            include_once $block_functions;
            // Add error notice if class is missing
            add_action('admin_notices', function() use ($block_functions) {
                echo $block_functions;
            });
             
        } else {
            // Add error notice if class is missing
            add_action('admin_notices', function() {
                echo "AI Block generator : ACF Pro required ";
            });
        }
        
        return false;
    }
}