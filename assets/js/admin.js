jQuery(document).ready(function($) {
    // Auto-generate slug from name
    $('#block_name').on('input', function() {
        const name = $(this).val();
        if ($('#block_slug').val() === '') {
            const slug = name.toLowerCase()
                .replace(/\s+/g, '-')
                .replace(/[^a-z0-9\-]/g, '');
            $('#block_slug').val(slug);
        }
    });
    
    // Handle AJAX actions
    $('.ai-block-action').on('click', function(e) {
        e.preventDefault();
        const button = $(this);
        const action = button.data('action');
        const blockId = button.data('id');
        
        button.prop('disabled', true).text('Processing...');
        
        $.post(aiBlockGenerator.ajax_url, {
            action: 'ai_block_generator_action',
            nonce: aiBlockGenerator.nonce,
            block_id: blockId,
            command: action
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                alert('Error: ' + response.data);
                button.prop('disabled', false).text(action === 'regenerate' ? 'Regenerate' : 'Delete');
            }
        });
    });
});