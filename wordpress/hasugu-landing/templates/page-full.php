<?php
if (!defined('ABSPATH')) {
    exit;
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<?php wp_head(); ?>
</head>
<body <?php body_class('hsg-landing-body'); ?>>
<?php
if (function_exists('wp_body_open')) {
    wp_body_open();
}

while (have_posts()) {
    the_post();
    $content = get_the_content();
    if (has_shortcode($content, 'hasugu_landing') || has_shortcode($content, 'drainpolice_landing')) {
        echo apply_filters('the_content', $content);
    } else {
        echo do_shortcode('[hasugu_landing]');
    }
}

wp_footer();
?>
</body>
</html>
