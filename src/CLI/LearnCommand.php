<?php declare(strict_types=1);

namespace Gratis\Learn\CLI;

defined('ABSPATH') || exit;

use Gratis\Learn\CoursePostType;

/**
 * WP-CLI commands for Gratis Learn.
 */
final class LearnCommand {

    /**
     * List courses and their lesson counts.
     *
     * @when after_wp_load
     */
    public function courses($args, $assoc_args): void {
        unset($args, $assoc_args);

        // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- bounded CLI listing.
        $courses = get_posts(['post_type' => CoursePostType::COURSE_TYPE, 'posts_per_page' => 200]);
        if (empty($courses)) {
            \WP_CLI::log('No courses.');
            return;
        }
        $rows = array_map(static fn($c) => [
            'ID'      => $c->ID,
            'Title'   => $c->post_title,
            'Lessons' => count(CoursePostType::lessonsFor((int) $c->ID)),
        ], $courses);
        \WP_CLI\Utils\format_items('table', $rows, ['ID', 'Title', 'Lessons']);
    }

    /**
     * Show a student's lesson completion count.
     *
     * [--user=<id>]
     * : User ID. Defaults to the current user.
     *
     * @when after_wp_load
     */
    public function progress($args, $assoc_args): void {
        unset($args);

        $user_id  = (int) ($assoc_args['user'] ?? get_current_user_id());
        $stored   = get_user_meta($user_id, CoursePostType::PROGRESS_META, true);
        $progress = is_array($stored) ? $stored : [];
        \WP_CLI::log("User #{$user_id}: " . count($progress) . ' lessons completed.');
    }
}
