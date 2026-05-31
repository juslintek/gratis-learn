<?php declare(strict_types=1);
namespace Gratis\Learn;

final class CoursePostType
{
    public static function register(): void
    {
        register_post_type('gratis_course', [
            'labels' => ['name' => 'Courses', 'singular_name' => 'Course'],
            'public' => true, 'show_in_rest' => true, 'menu_icon' => 'dashicons-welcome-learn-more',
            'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'has_archive' => true, 'rewrite' => ['slug' => 'courses'],
        ]);
        register_post_type('gratis_lesson', [
            'labels' => ['name' => 'Lessons', 'singular_name' => 'Lesson'],
            'public' => true, 'show_in_rest' => true, 'show_in_menu' => 'edit.php?post_type=gratis_course',
            'supports' => ['title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes'],
        ]);
        register_post_type('gratis_quiz', [
            'labels' => ['name' => 'Quizzes'], 'public' => false, 'show_ui' => true,
            'show_in_rest' => true, 'show_in_menu' => 'edit.php?post_type=gratis_course',
            'supports' => ['title', 'editor', 'custom-fields'],
        ]);
        register_post_meta('gratis_lesson', '_course_id', ['type' => 'integer', 'single' => true, 'show_in_rest' => true]);
        register_post_meta('gratis_lesson', '_order', ['type' => 'integer', 'single' => true, 'show_in_rest' => true]);

        // Track progress
        add_action('rest_api_init', function () {
            register_rest_route('gratis-learn/v1', '/progress/(?P<lesson_id>\d+)', [
                'methods' => 'POST', 'permission_callback' => 'is_user_logged_in',
                'callback' => function ($req) {
                    $user_id = get_current_user_id();
                    $lesson_id = (int) $req['lesson_id'];
                    $progress = get_user_meta($user_id, '_gratis_learn_progress', true) ?: [];
                    $progress[$lesson_id] = ['completed' => true, 'date' => gmdate('c')];
                    update_user_meta($user_id, '_gratis_learn_progress', $progress);
                    return new \WP_REST_Response(['success' => true]);
                },
            ]);
        });
    }
}
