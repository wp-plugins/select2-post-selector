<?php
/* 
 * Sample usage
 */

/*
 * Select2 Post Selectors
 *
 * Related articles and resources require Select2 Post Selectors which will go in their own meta box
 */

add_action('admin_init', 'demo_create_post_selects');
function demo_create_post_selects() {
    S2PS_Post_Select::create( 'related-articles', 'related-articles', 'demo_related_articles', 'Related articles', 'post', 'post');
    S2PS_Post_Select::create( 'related-resources', 'related-resources', 'demo_related_resources', 'Related resources', 'post', 'resources' );
}

add_action('add_meta_boxes', 'demo_add_related_items_meta_box');
function demo_add_related_items_meta_box() {
    add_meta_box( 'related_items', 'Related Items', 'demo_print_related_items_meta_box', 'post', 'normal',
         'default' );
}

function demo_print_related_items_meta_box( $post ) {
    S2PS_Post_Select::display( 'related-articles' );
    S2PS_Post_Select::display( 'related-resources' );
}

