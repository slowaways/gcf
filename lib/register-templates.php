<?php
/**
 *
 * Register the gutenberg custom templates
 */
function register_gutenberg_custom_templates($post_id, $post) {
    $templates = get_posts( array( 'post_type' => 'gcf-template') );

    foreach ( $templates as $template ) {
        $template_post_type = get_post_meta( $template->ID, 'post_type', true );
        $post_type_object = get_post_type_object($template_post_type);

        if ( $post_type_object && ($template_post_type === $post->post_type or $post->post_type === 'gcf-template') ) {
            // Computing the template.
            $gutenberg_template = array();
            $fields_config = json_decode( get_post_meta( $template->ID, 'fields', true ) );

            foreach( $fields_config as $field_config ) {
                $gutenberg_template[] = array(
                    sprintf( 'gcf/gcf-%s', $field_config->id )
                );

                register_meta( 'post', $field_config->name, array(
                    'show_in_rest' => true,
                    'single' => true,
                    'type' => 'string',
                ) );
            }
            $post_type_object->template = $gutenberg_template;

            // Computing the lock config.
            $lock = get_post_meta($template->ID, 'lock', true);
            if ( $lock && $lock !== 'none' ) {
                $post_type_object->template_lock = $lock;
            }
        }
    }
}
add_action('save_post', 'register_gutenberg_custom_templates', 10, 2);

/**
 *
 * Register the private blocks used in gutenberg custom templates
 */
function register_gutenberg_custom_templates_blocks() {
    global $post_type;

    $templates = get_posts( array('post_type' => 'gcf-template') );
    $fields = array();

    foreach( $templates as $template ) {
        $gcf_post_type = get_post_meta( $template->ID, 'post_type', true );

        if ( $gcf_post_type === $post_type ) {
            $fields_config = json_decode( get_post_meta( $template->ID, 'fields', true ) );
            $fields = array_merge($fields, $fields_config);
        }
    }

    if ( !empty( $fields ) ) {
        wp_enqueue_script('gcf-fields');
        wp_add_inline_script( 'gcf-fields', sprintf(
            'gcf.fields.registerBlocksForFields(%s)',
            json_encode($fields)
        ) );
        wp_enqueue_style( 'gcf-fields' );   
    }
}
add_action( 'enqueue_block_editor_assets', 'register_gutenberg_custom_templates_blocks' );
