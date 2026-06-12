<?php
/**
 * Plugin Name: Gratis Learn
 * Plugin URI: https://github.com/juslintek/gratis-learn
 * Description: LMS with courses, lessons, quizzes, progress tracking. Replaces LearnDash ($199/yr).
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
