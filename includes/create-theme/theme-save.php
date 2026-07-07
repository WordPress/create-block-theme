<?php
/**
 * Theme Save
 *
 * Service that persists user changes from the Editor to the active theme on
 * disk. Shared by the REST endpoint at `/create-block-theme/v1/save` and any
 * future non-REST callers (e.g. WP-CLI). The service does not perform
 * capability or input-sanitization checks on its own — callers are
 * responsible for those before invoking `run()`.
 *
 * @package Create_Block_Theme
 */
class CBT_Theme_Save {

	/**
	 * Persist user changes from the Editor to the active theme on disk.
	 *
	 * Orchestrates the four optional save steps — fonts, templates, styles,
	 * patterns — gated by truthy flags on the options array. Templates and
	 * styles select scope ('user' / 'current' / 'all') based on
	 * `processOnlySavedTemplates` and `is_child_theme()`. After all steps run,
	 * the theme cache is invalidated once.
	 *
	 * Callers are responsible for capability enforcement (e.g.
	 * `current_user_can( 'edit_theme_options' )`) and for validating any
	 * non-flag values in `$options` that flow into downstream services such
	 * as `CBT_Theme_Patterns::add_patterns_to_theme()` and
	 * `CBT_Theme_Templates::add_templates_to_local()`. The REST endpoint
	 * relies solely on its `permission_callback`; the `/save` route does not
	 * declare `args`, so no framework-level sanitization is applied. CLI and
	 * other callers must sanitize equivalently.
	 *
	 * @param array $options Options array with keys:
	 *                       - saveFonts (bool)
	 *                       - saveTemplates (bool)
	 *                       - processOnlySavedTemplates (bool)
	 *                       - saveStyle (bool)
	 *                       - savePatterns (bool)
	 *                       Plus any nested options consumed by downstream services.
	 *
	 * @return true|WP_Error true on success, WP_Error if the patterns step fails.
	 */
	public static function run( array $options ) {
		$flags = self::normalize_flags( $options );

		if ( $flags['saveFonts'] ) {
			CBT_Theme_Fonts::persist_font_settings();
		}

		if ( $flags['saveTemplates'] ) {
			$scope = $flags['processOnlySavedTemplates']
				? 'user'
				: ( is_child_theme() ? 'current' : 'all' );
			CBT_Theme_Templates::add_templates_to_local( $scope, null, null, $options );
			CBT_Theme_Templates::clear_user_templates_customizations();
			CBT_Theme_Templates::clear_user_template_parts_customizations();
		}

		if ( $flags['saveStyle'] ) {
			$scope = is_child_theme() ? 'current' : 'all';
			CBT_Theme_JSON::add_theme_json_to_local( $scope, null, null, $options );
			CBT_Theme_Styles::clear_user_styles_customizations();
		}

		if ( $flags['savePatterns'] ) {
			$result = CBT_Theme_Patterns::add_patterns_to_theme( $options );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		wp_get_theme()->cache_delete();

		return true;
	}

	/**
	 * Normalize the save flags through wp_validate_boolean().
	 *
	 * Collapses two prior issues: undefined-index notices when a flag was
	 * read without isset() (notably processOnlySavedTemplates), and strict
	 * `true ===` checks that rejected `1` / `"true"` / `"1"` from non-JS
	 * callers (CLI, form-encoded REST clients).
	 *
	 * Uses wp_validate_boolean() rather than ! empty() so the string
	 * "false" (commonly sent by query-string and form-encoded callers) is
	 * treated as falsy, not truthy.
	 *
	 * @param array $options Raw options array.
	 * @return array<string,bool> Normalized boolean flags.
	 */
	private static function normalize_flags( array $options ) {
		return array(
			'saveFonts'                 => wp_validate_boolean( $options['saveFonts'] ?? false ),
			'saveTemplates'             => wp_validate_boolean( $options['saveTemplates'] ?? false ),
			'processOnlySavedTemplates' => wp_validate_boolean( $options['processOnlySavedTemplates'] ?? false ),
			'saveStyle'                 => wp_validate_boolean( $options['saveStyle'] ?? false ),
			'savePatterns'              => wp_validate_boolean( $options['savePatterns'] ?? false ),
		);
	}
}
