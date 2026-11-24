<?php declare(strict_types=1);

namespace Gratis\Learn;

defined('ABSPATH') || exit;

/**
 * Registers the course/lesson/quiz post types, lesson meta, the progress REST
 * endpoint and the `[gratis_course]` outline shortcode.
 */
final class CoursePostType {

    public const COURSE_TYPE = 'gratis_course';
    public const LESSON_TYPE = 'gratis_lesson';
    public const QUIZ_TYPE   = 'gratis_quiz';

    public const PROGRESS_META = '_gratis_learn_progress';

    /** Register post types, meta and shortcodes. */
    public static function register(): void {
        register_post_type(self::COURSE_TYPE, [
            'labels'      => [
                'name'          => __('Courses', 'gratis-learn'),
                'singular_name' => __('Course', 'gratis-learn'),
            ],
            'public'       => true,
            'show_in_rest' => true,
            'menu_icon'    => 'dashicons-welcome-learn-more',
            'supports'     => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields'],
            'has_archive'  => true,
            'rewrite'      => ['slug' => 'courses'],
        ]);

        register_post_type(self::LESSON_TYPE, [
            'labels'       => [
                'name'          => __('Lessons', 'gratis-learn'),
                'singular_name' => __('Lesson', 'gratis-learn'),
            ],
            'public'       => true,
            'show_in_rest' => true,
            'show_in_menu' => 'edit.php?post_type=' . self::COURSE_TYPE,
            'supports'     => ['title', 'editor', 'thumbnail', 'custom-fields', 'page-attributes'],
        ]);

        register_post_type(self::QUIZ_TYPE, [
            'labels'       => ['name' => __('Quizzes', 'gratis-learn')],
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'show_in_menu' => 'edit.php?post_type=' . self::COURSE_TYPE,
            'supports'     => ['title', 'editor', 'custom-fields'],
        ]);

        register_post_meta(self::LESSON_TYPE, '_course_id', [
            'type'          => 'integer',
            'single'        => true,
            'show_in_rest'  => true,
            'auth_callback' => static fn() => current_user_can('edit_posts'),
        ]);
        register_post_meta(self::LESSON_TYPE, '_order', [
            'type'          => 'integer',
            'single'        => true,
            'show_in_rest'  => true,
            'auth_callback' => static fn() => current_user_can('edit_posts'),
        ]);

        add_shortcode('gratis_course', [__CLASS__, 'shortcode']);
        add_shortcode('gratis-course', [__CLASS__, 'shortcode']);
    }

    /** Register the progress-tracking REST route. */
    public static function registerAPI(): void {
        register_rest_route('gratis-learn/v1', '/progress/(?P<lesson_id>\d+)', [
            'methods'             => 'POST',
            'permission_callback' => 'is_user_logged_in',
            'callback'            => [__CLASS__, 'restProgress'],
        ]);
    }

    /** Mark a lesson complete for the current user. */
    public static function restProgress(\WP_REST_Request $req): \WP_REST_Response {
        $user_id   = get_current_user_id();
        $lesson_id = (int) $req['lesson_id'];

        if (self::LESSON_TYPE !== get_post_type($lesson_id)) {
            return new \WP_REST_Response(['error' => __('Unknown lesson.', 'gratis-learn')], 404);
        }

        $progress = get_user_meta($user_id, self::PROGRESS_META, true);
        $progress = is_array($progress) ? $progress : [];
        $progress[$lesson_id] = ['completed' => true, 'date' => gmdate('c')];
        update_user_meta($user_id, self::PROGRESS_META, $progress);

        do_action('gratis_learn_lesson_completed', $lesson_id, $user_id);

        return new \WP_REST_Response([
            'success'   => true,
            'completed' => count($progress),
        ]);
    }

    /**
     * Lessons that belong to a course, ordered by menu order then `_order`.
     *
     * @return \WP_Post[]
     */
    public static function lessonsFor(int $course_id): array {
        return get_posts([
            'post_type'      => self::LESSON_TYPE,
            // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- bounded lesson list per course.
            'posts_per_page' => 500,
            'orderby'        => ['menu_order' => 'ASC', 'meta_value_num' => 'ASC'],
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- secondary ordering key.
            'meta_key'       => '_order',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- lessons-by-course lookup.
            'meta_query'     => [['key' => '_course_id', 'value' => $course_id]],
        ]);
    }

    /** Render a course outline, or a course catalog when no course ID is active. */
    public static function shortcode($atts): string {
        $atts      = shortcode_atts(['id' => 0], $atts, 'gratis_course');
        $course_id = absint($atts['id']) ?: get_the_ID();

        if (!$course_id || self::COURSE_TYPE !== get_post_type($course_id)) {
            return self::renderCatalog();
        }

        $lessons = self::lessonsFor($course_id);
        if (empty($lessons)) {
            return '<p class="gratis-course-empty">' . esc_html__('No lessons yet.', 'gratis-learn') . '</p>';
        }

        $progress = [];
        if (is_user_logged_in()) {
            $stored   = get_user_meta(get_current_user_id(), self::PROGRESS_META, true);
            $progress = is_array($stored) ? $stored : [];
        }

        $out = '<ol class="gratis-course-outline">';
        foreach ($lessons as $lesson) {
            $done = !empty($progress[$lesson->ID]['completed']);
            $out .= '<li class="gratis-lesson' . ($done ? ' is-complete' : '') . '">';
            $out .= '<a href="' . esc_url((string) get_permalink($lesson->ID)) . '">' . esc_html($lesson->post_title) . '</a>';
            if ($done) {
                $out .= ' <span class="gratis-lesson__badge" aria-label="' . esc_attr__('Completed', 'gratis-learn') . '">✓</span>';
            }
            $out .= '</li>';
        }
        $out .= '</ol>';

        return $out;
    }

    /** Render a grid of all published courses with lesson counts and outlines. */
    private static function renderCatalog(): string {
        // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- bounded course catalog.
        $courses = get_posts([
            'post_type'      => self::COURSE_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ]);

        if (empty($courses)) {
            return '<p class="gratis-course-empty">' . esc_html__('No courses published yet.', 'gratis-learn') . '</p>';
        }

        $out = '<div class="gratis-course-catalog">';
        foreach ($courses as $course) {
            $lessons = self::lessonsFor($course->ID);
            $count   = count($lessons);
            $summary = '' !== trim((string) $course->post_excerpt)
                ? wp_strip_all_tags((string) $course->post_excerpt)
                : wp_trim_words(wp_strip_all_tags((string) $course->post_content), 24);

            $out .= '<article class="gratis-course-card">';
            $out .= '<div class="gratis-course-card__head">';
            $out .= '<h3 class="gratis-course-card__title"><a href="' . esc_url((string) get_permalink($course->ID)) . '">' . esc_html($course->post_title) . '</a></h3>';
            $out .= '<span class="gratis-course-card__count">' . esc_html(
                sprintf(
                    /* translators: %d: number of lessons. */
                    _n('%d lesson', '%d lessons', $count, 'gratis-learn'),
                    $count
                )
            ) . '</span>';
            $out .= '</div>';
            if ('' !== trim($summary)) {
                $out .= '<p class="gratis-course-card__desc">' . esc_html($summary) . '</p>';
            }
            if (!empty($lessons)) {
                $out .= '<ol class="gratis-course-outline">';
                foreach (array_slice($lessons, 0, 5) as $lesson) {
                    $out .= '<li class="gratis-lesson"><a href="' . esc_url((string) get_permalink($lesson->ID)) . '">' . esc_html($lesson->post_title) . '</a></li>';
                }
                $out .= '</ol>';
            }
            $out .= '</article>';
        }
        $out .= '</div>';

        return $out;
    }
}
