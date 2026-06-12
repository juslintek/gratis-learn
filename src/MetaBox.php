<?php declare(strict_types=1);

namespace Gratis\Learn;

defined('ABSPATH') || exit;

/**
 * Lesson meta box: assigns a lesson to a course and sets its order.
 */
final class MetaBox {

    private const NONCE_ACTION = 'gratis_learn_save_meta';
    private const NONCE_FIELD  = 'gratis_learn_meta_nonce';

    /** Register the lesson meta box. */
    public static function register(): void {
        add_meta_box(
            'gratis-learn-lesson',
            __('Lesson Settings', 'gratis-learn'),
            [__CLASS__, 'renderLesson'],
            CoursePostType::LESSON_TYPE,
            'side',
            'high'
        );
    }

    /** Render the lesson meta box. */
    public static function renderLesson(\WP_Post $post): void {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);

        $course = (int) get_post_meta($post->ID, '_course_id', true);
        $order  = (int) get_post_meta($post->ID, '_order', true);

        echo '<p><label for="gl_course"><strong>' . esc_html__('Course', 'gratis-learn') . '</strong></label><br>';
        echo '<select id="gl_course" name="gl_course" class="widefat">';
        echo '<option value="0">' . esc_html__('— unassigned —', 'gratis-learn') . '</option>';
        // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- bounded course list for a select.
        foreach (get_posts(['post_type' => CoursePostType::COURSE_TYPE, 'posts_per_page' => 200]) as $c) {
            echo '<option value="' . absint($c->ID) . '"' . selected($course, $c->ID, false) . '>' . esc_html($c->post_title) . '</option>';
        }
        echo '</select></p>';

        echo '<p><label for="gl_order"><strong>' . esc_html__('Order', 'gratis-learn') . '</strong></label><br>';
        echo '<input type="number" min="0" id="gl_order" name="gl_order" class="widefat" value="' . esc_attr((string) $order) . '"></p>';
    }

    /** Persist lesson meta. */
    public static function saveLesson(int $post_id, \WP_Post $post): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        $nonce = isset($_POST[self::NONCE_FIELD])
            ? sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD]))
            : '';
        if ('' === $nonce || !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }
        if (!current_user_can('edit_post', $post_id) || CoursePostType::LESSON_TYPE !== $post->post_type) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified above.
        update_post_meta($post_id, '_course_id', isset($_POST['gl_course']) ? absint(wp_unslash($_POST['gl_course'])) : 0);
        update_post_meta($post_id, '_order', isset($_POST['gl_order']) ? absint(wp_unslash($_POST['gl_order'])) : 0);
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }
}
