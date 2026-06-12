<?php declare(strict_types=1);

namespace Gratis\Learn;

defined('ABSPATH') || exit;

/**
 * Global settings for Gratis Learn.
 */
final class Settings {

    private const GROUP = 'gratis_learn_settings_group';
    private const PAGE  = 'gratis-learn-settings';

    private const OPT_REQUIRE_LOGIN = 'gratis_learn_require_login';
    private const OPT_PASS_MARK     = 'gratis_learn_pass_mark';

    /** Register the settings submenu under the Courses menu. */
    public static function register_menu(): void {
        add_submenu_page(
            'edit.php?post_type=' . CoursePostType::COURSE_TYPE,
            __('Learn Settings', 'gratis-learn'),
            __('Settings', 'gratis-learn'),
            'manage_options',
            self::PAGE,
            [__CLASS__, 'render_page']
        );
    }

    /** Register options. */
    public static function register_settings(): void {
        register_setting(self::GROUP, self::OPT_REQUIRE_LOGIN, [
            'type'              => 'boolean',
            'sanitize_callback' => static fn($v) => (bool) $v,
            'default'           => true,
        ]);
        register_setting(self::GROUP, self::OPT_PASS_MARK, [
            'type'              => 'integer',
            'sanitize_callback' => static fn($v) => max(0, min(100, absint($v))),
            'default'           => 70,
        ]);
    }

    /** Whether lessons require a logged-in user to view. */
    public static function requireLogin(): bool {
        return (bool) get_option(self::OPT_REQUIRE_LOGIN, true);
    }

    /** Quiz pass mark percentage. */
    public static function passMark(): int {
        return max(0, min(100, (int) get_option(self::OPT_PASS_MARK, 70)));
    }

    /** Render the settings screen. */
    public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'gratis-learn'));
        }

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Learn Settings', 'gratis-learn') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row">' . esc_html__('Require login', 'gratis-learn') . '</th><td>';
        echo '<label><input type="checkbox" name="' . esc_attr(self::OPT_REQUIRE_LOGIN) . '" value="1"' . checked(self::requireLogin(), true, false) . '> ';
        echo esc_html__('Students must be logged in for progress to be tracked.', 'gratis-learn') . '</label></td></tr>';

        echo '<tr><th scope="row"><label for="gl_pass">' . esc_html__('Quiz pass mark (%)', 'gratis-learn') . '</label></th><td>';
        echo '<input type="number" min="0" max="100" id="gl_pass" class="small-text" name="' . esc_attr(self::OPT_PASS_MARK) . '" value="' . esc_attr((string) self::passMark()) . '"></td></tr>';

        echo '</tbody></table>';
        submit_button(__('Save settings', 'gratis-learn'));
        echo '</form>';
        echo '</div>';
    }
}
