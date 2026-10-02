<?php
/**
 * @package Create_Block_Theme
 */
class Test_Create_Block_Theme_Utils extends WP_UnitTestCase {

	public function test_replace_namespace_in_pattern() {
		$pattern_string = '<?php
/**
 * Title: index
 * Slug: old-slug/index
 * Inserter: no
 */
?>
<!-- wp:template-part {"slug":"header-minimal","tagName":"header"} /-->
';

		$updated_pattern_string = CBT_Theme_Utils::replace_namespace( $pattern_string, 'old-slug', 'new-slug', 'Old Name', 'New Name' );
		$this->assertStringContainsString( 'Slug: new-slug/index', $updated_pattern_string );
		$this->assertStringNotContainsString( 'old-slug', $updated_pattern_string );
	}

	public function test_replace_namespace_in_code() {
		$code_string = "<?php
/**
 * old-slug functions and definitions
 *
 * @package old-slug
 * @since old-slug 1.0
 */

if ( ! function_exists( 'old_slug_support' ) ) :

	function old_slug_support() {
";

		$updated_code_string = CBT_Theme_Utils::replace_namespace( $code_string, 'old-slug', 'new-slug', 'Old Name', 'New Name' );
		$this->assertStringContainsString( '@package new-slug', $updated_code_string );
		$this->assertStringNotContainsString( 'old-slug', $updated_code_string );
		$this->assertStringContainsString( 'function new_slug_support', $updated_code_string );
		$this->assertStringContainsString( "function_exists( 'new_slug_support' )", $updated_code_string );
	}

	public function test_replace_namespace_in_code_with_single_word_slug() {
		$code_string = "<?php
/**
 * oldslug functions and definitions
 *
 * @package oldslug
 * @since oldslug 1.0
 */

if ( ! function_exists( 'oldslug_support' ) ) :

	function oldslug_support() {
";

		$updated_code_string = CBT_Theme_Utils::replace_namespace( $code_string, 'oldslug', sanitize_title( 'New Slug' ), 'OldSlug', 'New Slug' );
		$this->assertStringContainsString( '@package new-slug', $updated_code_string );
		$this->assertStringNotContainsString( 'old-slug', $updated_code_string );
		$this->assertStringContainsString( 'function new_slug_support', $updated_code_string );
		$this->assertStringContainsString( "function_exists( 'new_slug_support' )", $updated_code_string );
	}

	/**
	 * @dataProvider get_php_identifier_replacements
	 */
	public function test_replace_namespace_in_php_code_uses_valid_identifiers( $new_name, $new_slug, $expected_identifier, $expected_namespace ) {
		$code_string = "<?php
/**
 * This file adds functions to the Ollie WordPress theme.
 */
namespace Ollie;

class Ollie_Setup {
}

function ollie_setup() {
	\$ollie_setting = true;
}

if ( ! function_exists( 'ollie_setup' ) ) {
}

use Ollie\Setup;
";

		$updated_code_string = CBT_Theme_Utils::replace_namespace( $code_string, 'ollie', $new_slug, 'Ollie', $new_name );

		$this->assertStringContainsString( "namespace {$expected_namespace};", $updated_code_string );
		$this->assertStringContainsString( "class {$expected_namespace}_Setup", $updated_code_string );
		$this->assertStringContainsString( "function {$expected_identifier}_setup", $updated_code_string );
		$this->assertStringContainsString( "\${$expected_identifier}_setting", $updated_code_string );
		$this->assertStringContainsString( "function_exists( '{$expected_identifier}_setup' )", $updated_code_string );
		$this->assertStringContainsString( "use {$expected_namespace}\\Setup;", $updated_code_string );
		$this->assertStringContainsString( "functions to the {$new_name} WordPress theme", $updated_code_string );
	}

	/**
	 * @dataProvider get_javascript_identifier_replacements
	 */
	public function test_replace_namespace_in_javascript_uses_valid_identifiers( $new_name, $new_slug, $expected_identifier, $expected_global ) {
		$code_string = <<<'JS'
const extendable = window.ExtendableAnimations;
window.extendableOpenAnimationModal = () => true;
window.addEventListener( 'extendableAnimationSettingsChanged', () => true );
const textDomain = 'extendable';
const cssClass = 'extendable-animation-modal';
const displayName = 'Extendable';
JS;

		$updated_code_string = CBT_Theme_Utils::replace_namespace(
			$code_string,
			'extendable',
			$new_slug,
			'Extendable',
			$new_name,
			'js'
		);

		$this->assertStringContainsString( "const {$expected_identifier} = window.{$expected_global}Animations;", $updated_code_string );
		$this->assertStringContainsString( "window.{$expected_identifier}OpenAnimationModal = () => true;", $updated_code_string );
		$this->assertStringContainsString( "'{$expected_identifier}AnimationSettingsChanged'", $updated_code_string );
		$this->assertStringContainsString( "const textDomain = '{$new_slug}';", $updated_code_string );
		$this->assertStringContainsString( "const cssClass = '{$new_slug}-animation-modal';", $updated_code_string );
		$this->assertStringContainsString( "const displayName = '{$new_name}';", $updated_code_string );
		$this->assertStringNotContainsString( "window.{$new_slug}", $updated_code_string );
		$this->assertStringNotContainsString( "window.{$new_name}", $updated_code_string );
	}

	public function test_replace_namespace_in_javascript_recognizes_camel_case_source_slug() {
		$code_string         = "const spectraOne = window.SpectraOneSettings; const textDomain = 'spectra-one';";
		$updated_code_string = CBT_Theme_Utils::replace_namespace(
			$code_string,
			'spectra-one',
			'my-theme',
			'Spectra One',
			'My Theme',
			'js'
		);

		$this->assertStringContainsString( 'const myTheme = window.MyThemeSettings;', $updated_code_string );
		$this->assertStringContainsString( "const textDomain = 'my-theme';", $updated_code_string );
	}

	public function test_replace_namespace_updates_javascript_identifier_references_in_php_strings() {
		$code_string = <<<'PHP'
<?php
wp_localize_script( 'extendable-animations', 'ExtendableAnimations', array() );
PHP;

		$updated_code_string = CBT_Theme_Utils::replace_namespace(
			$code_string,
			'extendable',
			'my-theme',
			'Extendable',
			'My Theme',
			'php'
		);

		$this->assertStringContainsString(
			"wp_localize_script( 'my-theme-animations', 'MyThemeAnimations', array() );",
			$updated_code_string
		);
	}

	public function get_php_identifier_replacements() {
		return array(
			'theme name with spaces' => array( 'My Theme', 'my-theme', 'my_theme', 'My_Theme' ),
			'theme name with hyphen' => array( 'my-theme', 'my-theme', 'my_theme', 'My_Theme' ),
			'theme name with symbol' => array( 'FE & Ollie', 'fe-ollie', 'fe_ollie', 'Fe_Ollie' ),
			'theme name with number' => array( '2024 Clone', '2024-clone', 'theme_2024_clone', 'Theme_2024_Clone' ),
		);
	}

	public function get_javascript_identifier_replacements() {
		return array(
			'theme name with spaces' => array( 'My Theme', 'my-theme', 'myTheme', 'MyTheme' ),
			'theme name with symbol' => array( 'FE & Ollie', 'fe-ollie', 'feOllie', 'FeOllie' ),
			'theme name with number' => array( '2024 Clone', '2024-clone', 'theme2024Clone', 'Theme2024Clone' ),
		);
	}
}
