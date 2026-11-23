<?php
/**
 * Plugin Name: Gratis Learn
 * Plugin URI: https://github.com/juslintek/gratis-learn
 * Description: LMS with courses, lessons, quizzes, progress tracking.
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * Author URI: https://github.com/juslintek
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gratis-learn
 *
 * @package Gratis\Learn
 */

defined('ABSPATH') || exit;

define('GRATIS_LEARN_VERSION', '1.0.0');
define('GRATIS_LEARN_FILE', __FILE__);
define('GRATIS_LEARN_DIR', plugin_dir_path(__FILE__));
define('GRATIS_LEARN_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/autoload.php';

add_action('init', [\Gratis\Learn\CoursePostType::class, 'register']);
add_action('rest_api_init', [\Gratis\Learn\CoursePostType::class, 'registerAPI']);

add_action('add_meta_boxes', [\Gratis\Learn\MetaBox::class, 'register']);
add_action('save_post_gratis_lesson', [\Gratis\Learn\MetaBox::class, 'saveLesson'], 10, 2);

add_action('admin_menu', [\Gratis\Learn\Settings::class, 'register_menu']);
add_action('admin_init', [\Gratis\Learn\Settings::class, 'register_settings']);

add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-learn', \Gratis\Learn\CLI\LearnCommand::class);
    }
});

/**
 * Inject Course JSON-LD on the courses archive page via the gratis-seo graph filter.
 * Emits a schema.org/Course ItemList for all published courses.
 */
add_filter('gratis_seo_schema_graph', function (array $graph): array {
    if (!is_post_type_archive(\Gratis\Learn\CoursePostType::COURSE_TYPE)) {
        return $graph;
    }

    $courses = get_posts([
        'post_type'      => \Gratis\Learn\CoursePostType::COURSE_TYPE,
        'posts_per_page' => 50,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
    ]);

    if (empty($courses)) {
        return $graph;
    }

    $items = [];
    foreach ($courses as $position => $course) {
        $url  = (string) get_permalink($course->ID);
        $item = [
            '@type'       => 'Course',
            'name'        => get_the_title($course->ID),
            'url'         => $url,
            'description' => wp_strip_all_tags(get_the_excerpt($course->ID)),
            'provider'    => [
                '@type' => 'Organization',
                'name'  => (string) get_bloginfo('name'),
                'url'   => home_url('/'),
            ],
            'isAccessibleForFree' => true,
            'offers'              => [
                '@type' => 'Offer',
                'price' => '0',
                'priceCurrency' => 'USD',
                'availability'  => 'https://schema.org/InStock',
            ],
        ];

        if (has_post_thumbnail($course->ID)) {
            $img = get_the_post_thumbnail_url($course->ID, 'large');
            if (is_string($img) && '' !== $img) {
                $item['image'] = $img;
            }
        }

        $items[] = [
            '@type'    => 'ListItem',
            'position' => $position + 1,
            'item'     => $item,
        ];
    }

    $graph[] = [
        '@type'           => 'ItemList',
        'name'            => __('Free WordPress Courses by GRATIS', 'gratis-learn'),
        'itemListElement' => $items,
    ];

    return $graph;
});
