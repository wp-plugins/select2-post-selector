=== Select 2 Post Selector ===
Contributors: magicroundabout
Tags: posts, custom-post-types, select2, relationships
Requires at least: 4.0
Tested up to: 4.2.1
Stable tag: trunk
License: GPLv2 or later (Select2 is MIT licensed)
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Provides developers with a simple means of creating AJAX-powered Select 2 post select meta boxes

== Description ==

This plugin provides a library for developers to use to create AJAX-powered drop-down post select boxes.

This is best explained with an example. Say you have a plugin or theme that sets up a custom post type called "Resources".  You want users to be able to manually create, for each post, "Related posts" and "Related resources" using meta boxes with data (the IDs of the related posts/resources) saved to custom fields.

One way to do this is to use a select box. This would be a big long list of ALL the posts or resources on your site. This is not very user friendly, but also, with a large number of posts and resources to list, can be a real performance hog. I've even had this approach blow the PHP memory_limit and break the admin of sites.

This plugin improves both the user experience and performance of creating selectable post lists by enhancing them with the functionality of AJAX-powered [Select2](https://github.com/select2/select2) select boxes.

The plugin:

* gives a simple way to select posts from a specified post type based on a partial, case-insensitive title search
* saves selected post IDs to custom post meta variables
* requires minimal coding (but, sadly, does require some coding in your plugin or theme)

Select2 is MIT Licensed and Copyright (c) 2012-2015 Kevin Brown, Igor Vaynberg, and Select2 contributors,

== Installation ==

This section describes how to install the plugin and get it working.

e.g.

1. Upload `select2-post-selector.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Add code as explained in the Other Notes tab

== Configuration ==

This plugin provides a library for you to use in Themes and Plugins that you create.  To use it you will need to add some code to your plugin or theme. Not much, but you will need to add some.

The basic steps are: 

1. initialise the post selectors
2. create a meta box that displays on your custom post type's edit page
3. populate the meta box with the post selectors
4. getting your data out

_Note: all code here is prefixed with 'demo_' - please change this to an appropriate prefix for your own project or put the calls in a wrapper class or namespace of some sort._

## Initialise Post Selectors

You should initialise the Post Selectors on admin_init - they aren't required in the front end.

You should call S2PS_Post_Select::create() for each post selector that you want to create.

The parameters to the create method are:

*   `$field_id` - this is the 'name' of the field - used to identify it for printing or saving - it must be unique for your post selector
*   `$meta_key` - the meta_key to fetch/save data to/from
*   `$form_field_name` - the name attribute of the select form field to be created
*   `$form_field_label` - the label text for the select form field
*   `$post_post_type` - the post type of the posts which we want the select box to appear for
*   `$item_post_type` - the post type of the things to appear in the select list
*   `$additional_query_params` - any additional query params for generating the list (if you want to filter what appears in the selector); this is an array of parameters that you would pass to `WP_Query` - you can pass pretty much anything, I think, except post_type. Using things like pagination parameters probably isn't recommended.

The first parameter, `$field_id`, is needed when adding the code to display the post selector, so remember what they are.

Here's some code that initialises "Related posts" and "Related resources" (of "resources" custom post type) to be displayed on the edit screen of posts.

`
add_action('admin_init', 'demo_create_post_selects');
function demo_create_post_selects() {
    S2PS_Post_Select::create( 'related-articles', 'related-articles', 'demo_related_articles', 'Related articles', 'post', 'post');
    S2PS_Post_Select::create( 'related-resources', 'related-resources', 'demo_related_resources', 'Related resources', 'post', 'resources' );
}
`

## Create meta boxes

This just uses a standard WordPress add_meta_box call to add a meta box to the post type that you want to display the selectors in.  The callback to print the content of the meta box (`demo_print_related_items_meta_box`) will use the code in the next section to display the post selectors.

`
add_action('add_meta_boxes', 'demo_add_related_items_meta_box');
function demo_add_related_items_meta_box() {
    add_meta_box( 'related_items', 'Related Items', 'demo_print_related_items_meta_box', 'post', 'normal',
         'default' );
}
`

## Add post selectors to meta boxes

This is the callback that is called to display the contents of the meta box.  Here we need out `$field_id`'s from the first section of code. 

`
function demo_print_related_items_meta_box( $post ) {
    S2PS_Post_Select::display( 'related-articles' );
    S2PS_Post_Select::display( 'related-resources' );
}
`

## Getting data out

Data is stored as regular post meta, so you can retrieve it with `get_post_meta()` calls (or other functions that get post meta for you).

One important thing to note is that if multiple posts are selected in a given field then these are stored as multiple post meta entries with the same key.  So, when you call `get_post_meta()`, ensure that the `$single` parameter is set to `false` (it is by default).

== Frequently Asked Questions ==

= Do I have to write code to use this? =

Yes, it's really a developer library conveniently wrapped up in a plugin.

= How do I make this work with my theme/plugin? =

I'm really sorry but I am unable to provide support on a per-project basis for this plugin.

== Screenshots ==

1. 

== Changelog ==

= 1.0.2 =
* Fixed bug where AJAX results were unexpectedly being sorted by the browser. Who knew? You did? Oh, OK.
* Tested on WordPress v4.2 and v4.2.1

= 1.0.1 =
* Fixed bug with saving empty lists

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.0.2 =
* Fixed bug where AJAX results were unexpectedly being sorted by the browser. Who knew? You did? Oh, OK.
* Tested on WordPress v4.2 and v4.2.1

= 1.0.1 =
* Fixed bug with saving empty lists

= 1.0 =
Initial release