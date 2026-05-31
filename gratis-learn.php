<?php
/**
 * Plugin Name: Gratis Learn
 * Description: LMS with courses, lessons, quizzes, progress tracking. Replaces LearnDash ($199/yr).
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/autoload.php';
add_action('init', [\Gratis\Learn\CoursePostType::class, 'register']);
add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) \WP_CLI::add_command('gratis-learn', \Gratis\Learn\CLI\LearnCommand::class);
});
