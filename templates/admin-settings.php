<div class="wrap">
    <h1>AI Block Generator</h1>
    
    <?php settings_errors('ai_block_generator'); ?>
    
    <div class="ai-block-generator-container">
        <div class="ai-block-form">
            <h2>Create New Block</h2>
            <form method="post">
                <?php wp_nonce_field('generate_block'); ?>
                <table class="form-table">
                    <tr>
                        <th><label for="block_name">Block Name</label></th>
                        <td>
                            <input type="text" name="block_name" id="block_name" class="regular-text" required>
                            <p class="description">Human-readable name (e.g., "Hero Banner")</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="block_slug">Block Slug</label></th>
                        <td>
                            <input type="text" name="block_slug" id="block_slug" class="regular-text" required>
                            <p class="description">Machine-readable slug (e.g., "hero-banner")</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="block_description">Description</label></th>
                        <td>
                            <textarea name="block_description" id="block_description" rows="3" class="regular-text" required></textarea>
                            <p class="description">Brief description of the block's purpose</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="block_template">Template/Design</label></th>
                        <td>
                            <textarea name="block_template" id="block_template" rows="6" class="large-text" required></textarea>
                            <p class="description">
                                Describe the block design in detail. Include:<br>
                                - Layout requirements<br>
                                - Content fields needed<br>
                                - Styling preferences<br>
                                - Interactive elements
                            </p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Generate Block', 'primary', 'generate_block'); ?>
            </form>
        </div>
        
        <div class="ai-block-list">
            <h2>Generated Blocks</h2>
            <?php if (empty($blocks)) : ?>
                <p>No blocks generated yet.</p>
            <?php else : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Slug</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocks as $block) : ?>
                            <tr>
                                <td><?php echo esc_html($block['name']); ?></td>
                                <td><?php echo esc_html($block['slug']); ?></td>
                                <td><?php echo date('M j, Y @ H:i', strtotime($block['created_at'])); ?></td>
                                <td>
                                    <a href="<?php echo admin_url("admin.php?page=ai-block-generator&action=regenerate&id={$block['id']}"); ?>" 
                                       class="button button-secondary">Regenerate</a>
                                    <a href="<?php echo admin_url("admin.php?page=ai-block-generator&action=delete&id={$block['id']}"); ?>" 
                                       class="button button-link-delete" 
                                       onclick="return confirm('Are you sure you want to delete this block?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>