<?php declare(strict_types=1);
namespace Gratis\Learn\CLI;

final class LearnCommand
{
    /** @when after_wp_load */
    public function courses($args, $assoc_args): void
    {
        $courses = get_posts(['post_type' => 'gratis_course', 'numberposts' => -1]);
        if (empty($courses)) { \WP_CLI::log("No courses."); return; }
        $rows = array_map(fn($c) => [
            'ID' => $c->ID, 'Title' => $c->post_title,
            'Lessons' => count(get_posts(['post_type' => 'gratis_lesson', 'meta_key' => '_course_id', 'meta_value' => $c->ID, 'numberposts' => -1])),
        ], $courses);
        \WP_CLI\Utils\format_items('table', $rows, ['ID', 'Title', 'Lessons']);
    }

    /**
     * Show student progress.
     * [--user=<id>] : User ID.
     * @when after_wp_load
     */
    public function progress($args, $assoc_args): void
    {
        $user_id = (int) ($assoc_args['user'] ?? get_current_user_id());
        $progress = get_user_meta($user_id, '_gratis_learn_progress', true) ?: [];
        \WP_CLI::log("User #{$user_id}: " . count($progress) . " lessons completed.");
    }
}
