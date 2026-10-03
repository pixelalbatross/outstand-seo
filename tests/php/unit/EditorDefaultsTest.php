<?php
/**
 * Engine-rendered editor defaults (placeholders/counters). These call the live
 * TSF / Yoast pipelines, so each case is skipped unless its plugin is active.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\TSF;
use Outstand\WP\SEO\Engines\Yoast;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\AbstractEngine
 * @covers \Outstand\WP\SEO\Engines\TSF
 * @covers \Outstand\WP\SEO\Engines\Yoast
 */
class EditorDefaultsTest extends \WP_UnitTestCase {

	/**
	 * Canonical fields every engine renders a default for.
	 *
	 * @var string[]
	 */
	private const RENDERED_FIELDS = [
		'title',
		'description',
		'ogTitle',
		'ogDescription',
		'twitterTitle',
		'twitterDescription',
	];

	/**
	 * TSF renders every title and description for the saved post.
	 *
	 * @return void
	 */
	public function test_tsf_defaults_render_saved_post(): void {
		$engine = $this->tsf();
		$values = $engine->get_editor_defaults( $this->create_post() )['values'];

		$this->assertSame( self::RENDERED_FIELDS, array_keys( $values ) );
		$this->assertStringStartsWith( 'Hello World', $values['title'] );
		$this->assertStringContainsString( get_bloginfo( 'name' ), $values['title'] );
		$this->assertSame( 'Hello World', $values['ogTitle'] );
	}

	/**
	 * TSF renders the edited post title, with what filters add around it.
	 *
	 * @return void
	 */
	public function test_tsf_defaults_use_edited_post_title(): void {
		$engine       = $this->tsf();
		$prefix_title = static fn( $title ) => "Example Client - {$title}";

		add_filter( 'the_seo_framework_title_from_generation', $prefix_title );

		$values = $engine->get_editor_defaults( $this->create_post(), [ 'postTitle' => 'Edited Title' ] )['values'];

		remove_filter( 'the_seo_framework_title_from_generation', $prefix_title );

		$this->assertStringStartsWith( 'Example Client - Edited Title', $values['title'] );
		$this->assertSame( 'Example Client - Edited Title', $values['ogTitle'] );
	}

	/**
	 * The edited title goes through the title filters TSF's frontend runs.
	 *
	 * @return void
	 */
	public function test_tsf_defaults_filter_edited_post_title(): void {
		$values = $this->tsf()->get_editor_defaults( $this->create_post(), [ 'postTitle' => 'Hello <b>World</b>' ] )['values'];

		$this->assertSame( 'Hello World', $values['ogTitle'] );
	}

	/**
	 * TSF renders "Untitled" for an emptied post title.
	 *
	 * @return void
	 */
	public function test_tsf_defaults_with_empty_post_title(): void {
		$values = $this->tsf()->get_editor_defaults( $this->create_post(), [ 'postTitle' => '' ] )['values'];

		$this->assertStringStartsWith( 'Untitled', $values['title'] );
	}

	/**
	 * TSF social titles and descriptions fall back to the edited custom meta
	 * title and description.
	 *
	 * @return void
	 */
	public function test_tsf_social_defaults_fall_back_to_edited_meta(): void {
		$values = $this->tsf()->get_editor_defaults(
			$this->create_post(),
			[
				'values' => [
					'title'       => 'Custom Title',
					'description' => 'Custom description',
				],
			]
		)['values'];

		$this->assertSame( 'Custom Title', $values['ogTitle'] );
		$this->assertSame( 'Custom Title', $values['twitterTitle'] );
		$this->assertSame( 'Custom description', $values['ogDescription'] );
		$this->assertSame( 'Custom description', $values['twitterDescription'] );
	}

	/**
	 * Edited values render as saving would store them: sanitized as text.
	 *
	 * @dataProvider engine_provider
	 *
	 * @param string $engine_class Engine class name.
	 * @return void
	 */
	public function test_edited_values_render_sanitized( string $engine_class ): void {
		$engine = 'tsf' === $engine_class ? $this->tsf() : $this->yoast();
		$values = $engine->get_editor_defaults(
			$this->create_post(),
			[ 'values' => [ 'description' => '<b>Bold</b> description' ] ]
		)['values'];

		$this->assertSame( 'Bold description', $values['ogDescription'] );
	}

	/**
	 * Edited values with backslashes render as each engine outputs them: TSF
	 * keeps them, and Yoast's description presenters strip them.
	 *
	 * @dataProvider backslash_provider
	 *
	 * @param string $engine_class Engine class name.
	 * @param string $expected     Rendered Open Graph description.
	 * @return void
	 */
	public function test_edited_values_render_backslashes( string $engine_class, string $expected ): void {
		$engine = 'tsf' === $engine_class ? $this->tsf() : $this->yoast();
		$values = $engine->get_editor_defaults(
			$this->create_post(),
			[ 'values' => [ 'description' => 'Install to C:\\Example\\Path' ] ]
		)['values'];

		$this->assertSame( $expected, $values['ogDescription'] );
	}

	/**
	 * Engines and the Open Graph description each renders for a backslashed
	 * description.
	 *
	 * @return array<string,array{string,string}>
	 */
	public function backslash_provider(): array {
		return [
			'tsf'   => [ 'tsf', 'Install to C:\\Example\\Path' ],
			'yoast' => [ 'yoast', 'Install to C:ExamplePath' ],
		];
	}

	/**
	 * Engines for the engine-agnostic tests.
	 *
	 * @return array<string,array{string}>
	 */
	public function engine_provider(): array {
		return [
			'tsf'   => [ 'tsf' ],
			'yoast' => [ 'yoast' ],
		];
	}

	/**
	 * TSF Twitter values fall back to the edited Open Graph values first.
	 *
	 * @return void
	 */
	public function test_tsf_twitter_defaults_fall_back_to_edited_open_graph(): void {
		$values = $this->tsf()->get_editor_defaults(
			$this->create_post(),
			[
				'values' => [
					'description'   => 'Custom description',
					'ogTitle'       => 'Custom social title',
					'ogDescription' => 'Custom social description',
				],
			]
		)['values'];

		$this->assertSame( 'Custom social title', $values['twitterTitle'] );
		$this->assertSame( 'Custom social description', $values['twitterDescription'] );
	}

	/**
	 * TSF drops the site name when the edited state removes it.
	 *
	 * @return void
	 */
	public function test_tsf_defaults_honor_edited_title_no_blogname(): void {
		$values = $this->tsf()->get_editor_defaults(
			$this->create_post(),
			[ 'values' => [ 'titleNoBlogname' => true ] ]
		)['values'];

		$this->assertSame( 'Hello World', $values['title'] );
	}

	/**
	 * Edits apply to the computation only: nothing is saved, and the saved post
	 * renders as before afterwards.
	 *
	 * @return void
	 */
	public function test_tsf_edits_are_not_kept(): void {
		$engine  = $this->tsf();
		$post_id = $this->create_post();

		$engine->get_editor_defaults(
			$post_id,
			[
				'postTitle' => 'Edited Title',
				'values'    => [ 'title' => 'Custom Title' ],
			]
		);

		$values = $engine->get_editor_defaults( $post_id )['values'];

		$this->assertSame( '', get_post_meta( $post_id, '_genesis_title', true ) );
		$this->assertStringStartsWith( 'Hello World', $values['title'] );
		$this->assertSame( 'Hello World', $values['ogTitle'] );
	}

	/**
	 * With Open Graph output off, the TSF Twitter description follows TSF's
	 * frontend chain. Runs isolated so the Open Graph setting doesn't leak.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @return void
	 */
	public function test_tsf_twitter_description_without_open_graph(): void {
		$engine = $this->tsf();

		// TSF memoizes the front page ID per request, so it is set up first.
		$front_page_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $front_page_id );

		$options                                 = (array) get_option( THE_SEO_FRAMEWORK_SITE_OPTIONS, [] );
		$options['og_tags']                      = 0;
		$options['homepage_twitter_description'] = 'Homepage Twitter description';
		update_option( THE_SEO_FRAMEWORK_SITE_OPTIONS, $options );
		\The_SEO_Framework\Data\Plugin::refresh_static_properties();

		$custom = $engine->get_editor_defaults(
			$this->create_post(),
			[
				'values' => [
					'description'        => 'Custom description',
					'twitterDescription' => 'Custom Twitter description',
				],
			]
		)['values'];

		$meta = $engine->get_editor_defaults(
			$this->create_post(),
			[ 'values' => [ 'description' => 'Custom description' ] ]
		)['values'];

		$generated = $engine->get_editor_defaults( $this->create_post( 'Example excerpt for the generated description.' ) )['values'];

		$front_page = $engine->get_editor_defaults( $front_page_id, [ 'values' => [ 'description' => 'Custom description' ] ] )['values'];

		$this->assertSame( 'Custom Twitter description', $custom['twitterDescription'] );
		$this->assertSame( 'Homepage Twitter description', $front_page['twitterDescription'] );
		$this->assertSame( 'Custom description', $meta['twitterDescription'] );
		$this->assertStringContainsString( 'Example excerpt', $generated['twitterDescription'] );
	}

	/**
	 * TSF defaults are empty on a TSF core without the namespaced generators.
	 *
	 * @return void
	 */
	public function test_tsf_defaults_without_title_generator(): void {
		if ( class_exists( '\The_SEO_Framework\Meta\Title' ) ) {
			$this->markTestSkipped( 'The SEO Framework is active.' );
		}

		$defaults = ( new TSF() )->get_editor_defaults( $this->create_post() );

		$this->assertSame( [ 'values' => [] ], $defaults );
	}

	/**
	 * Yoast renders every title and description for the saved post.
	 *
	 * @return void
	 */
	public function test_yoast_defaults_render_saved_post(): void {
		$values = $this->yoast()->get_editor_defaults( $this->create_post() )['values'];

		$this->assertSame( self::RENDERED_FIELDS, array_keys( $values ) );
		$this->assertStringStartsWith( 'Hello World', $values['title'] );
		$this->assertStringContainsString( get_bloginfo( 'name' ), $values['title'] );
	}

	/**
	 * Yoast renders the edited post title, with what `wpseo_title` adds.
	 *
	 * @return void
	 */
	public function test_yoast_defaults_use_edited_post_title(): void {
		$engine       = $this->yoast();
		$prefix_title = static fn( $title ) => "Example Client - {$title}";

		add_filter( 'wpseo_title', $prefix_title );

		$values = $engine->get_editor_defaults( $this->create_post(), [ 'postTitle' => 'Edited Title' ] )['values'];

		remove_filter( 'wpseo_title', $prefix_title );

		$this->assertStringStartsWith( 'Example Client - Edited Title', $values['title'] );
		$this->assertStringStartsWith( 'Edited Title', $values['ogTitle'] );
	}

	/**
	 * Yoast social titles and descriptions fall back to the edited SEO title and
	 * meta description, with their variables resolved.
	 *
	 * @return void
	 */
	public function test_yoast_social_defaults_fall_back_to_edited_meta(): void {
		$values = $this->yoast()->get_editor_defaults(
			$this->create_post(),
			[
				'postTitle' => 'Edited Title',
				'values'    => [
					'title'       => 'Custom %%title%%',
					'description' => 'Custom description',
				],
			]
		)['values'];

		$this->assertSame( 'Custom Edited Title', $values['ogTitle'] );
		$this->assertSame( 'Custom Edited Title', $values['twitterTitle'] );
		$this->assertSame( 'Custom description', $values['ogDescription'] );
		$this->assertSame( 'Custom description', $values['twitterDescription'] );
	}

	/**
	 * Yoast leaves out Twitter tags that repeat Open Graph, so the Twitter
	 * values are the edited Open Graph ones.
	 *
	 * @return void
	 */
	public function test_yoast_twitter_defaults_fall_back_to_edited_open_graph(): void {
		$values = $this->yoast()->get_editor_defaults(
			$this->create_post(),
			[
				'values' => [
					'ogTitle'       => 'Custom social title',
					'ogDescription' => 'Custom social description',
				],
			]
		)['values'];

		$this->assertSame( 'Custom social title', $values['twitterTitle'] );
		$this->assertSame( 'Custom social description', $values['twitterDescription'] );
	}

	/**
	 * Yoast social descriptions fall back to the excerpt without a description
	 * template.
	 *
	 * @return void
	 */
	public function test_yoast_social_description_falls_back_to_excerpt(): void {
		$engine      = $this->yoast();
		$desc_format = \WPSEO_Options::get( 'metadesc-post' );

		\WPSEO_Options::set( 'metadesc-post', '' );

		$post_id = self::factory()->post->create(
			[
				'post_title'   => 'Hello World',
				'post_excerpt' => 'Example excerpt',
			]
		);
		$values  = $engine->get_editor_defaults( $post_id )['values'];

		\WPSEO_Options::set( 'metadesc-post', $desc_format );

		$this->assertSame( 'Example excerpt', $values['ogDescription'] );
		$this->assertSame( 'Example excerpt', $values['twitterDescription'] );
	}

	/**
	 * Edits never reach Yoast's stored indexable: a missing indexable stays
	 * missing, and an outdated one is rebuilt from the saved post only.
	 *
	 * @return void
	 */
	public function test_yoast_edits_never_reach_stored_indexable(): void {
		$engine     = $this->yoast();
		$repository = YoastSEO()->classes->get( \Yoast\WP\SEO\Repositories\Indexable_Repository::class );
		$edits      = [
			'postTitle' => 'Edited Title',
			'values'    => [
				'title'   => 'Custom Title',
				'noindex' => 'off',
			],
		];

		$missing_id = $this->create_post();
		$indexable  = $repository->find_by_id_and_type( $missing_id, 'post', false );

		if ( $indexable ) {
			$indexable->delete();
		}

		$engine->get_editor_defaults( $missing_id, $edits );

		$outdated_id                 = $this->create_post();
		$outdated                    = $repository->find_by_id_and_type( $outdated_id, 'post' );
		$outdated->version           = 0;
		$outdated->title             = null;
		$outdated->is_robots_noindex = null;
		$outdated->save();

		$engine->get_editor_defaults( $outdated_id, $edits );

		$stored = $repository->find_by_id_and_type( $outdated_id, 'post', false );

		$this->assertFalse( $repository->find_by_id_and_type( $missing_id, 'post', false ) );
		$this->assertNull( $stored->title );
		$this->assertNotTrue( $stored->is_robots_noindex );
		$this->assertSame( '', get_post_meta( $outdated_id, '_yoast_wpseo_title', true ) );
	}

	/**
	 * Yoast renders an auto-draft as the draft it becomes, so a new post has
	 * defaults before its first save.
	 *
	 * @return void
	 */
	public function test_yoast_defaults_for_auto_draft(): void {
		$post_id = self::factory()->post->create(
			[
				'post_title'  => 'Auto Draft',
				'post_status' => 'auto-draft',
			]
		);
		$values  = $this->yoast()->get_editor_defaults( $post_id, [ 'postTitle' => 'Edited Title' ] )['values'];

		$this->assertStringStartsWith( 'Edited Title', $values['title'] );
		$this->assertSame( 'auto-draft', get_post_status( $post_id ) );
	}

	/**
	 * Yoast renders the presentation its frontend filters.
	 *
	 * @return void
	 */
	public function test_yoast_defaults_apply_frontend_presentation_filter(): void {
		$engine = $this->yoast();
		$filter = static function ( $presentation ) {
			$presentation->open_graph_title = 'Filtered social title';

			return $presentation;
		};

		add_filter( 'wpseo_frontend_presentation', $filter );

		$values = $engine->get_editor_defaults( $this->create_post() )['values'];

		remove_filter( 'wpseo_frontend_presentation', $filter );

		$this->assertSame( 'Filtered social title', $values['ogTitle'] );
	}


	/**
	 * Yoast defaults are empty for a post Yoast doesn't index.
	 *
	 * @return void
	 */
	public function test_yoast_defaults_for_excluded_post_type(): void {
		$engine  = $this->yoast();
		$exclude = static fn( $post_types ) => array_merge( (array) $post_types, [ 'page' ] );

		add_filter( 'wpseo_indexable_excluded_post_types', $exclude );

		$post_id  = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$defaults = $engine->get_editor_defaults( $post_id );

		remove_filter( 'wpseo_indexable_excluded_post_types', $exclude );

		$this->assertSame( [ 'values' => [] ], $defaults );
	}

	/**
	 * Yoast defaults are empty without Yoast.
	 *
	 * @return void
	 */
	public function test_yoast_defaults_without_yoast(): void {
		if ( function_exists( 'YoastSEO' ) ) {
			$this->markTestSkipped( 'Yoast SEO is active.' );
		}

		$defaults = ( new Yoast() )->get_editor_defaults( $this->create_post() );

		$this->assertSame( [ 'values' => [] ], $defaults );
	}

	/**
	 * Yoast defaults are empty once Yoast stops indexing a post it has an
	 * indexable for.
	 *
	 * @return void
	 */
	public function test_yoast_defaults_when_post_is_no_longer_indexed(): void {
		$engine  = $this->yoast();
		$post_id = self::factory()->post->create( [ 'post_type' => 'page' ] );
		$exclude = static fn( $post_types ) => array_merge( (array) $post_types, [ 'page' ] );

		$engine->get_editor_defaults( $post_id );

		add_filter( 'wpseo_indexable_excluded_post_types', $exclude );

		$defaults = $engine->get_editor_defaults( $post_id );

		remove_filter( 'wpseo_indexable_excluded_post_types', $exclude );

		$this->assertSame( [ 'values' => [] ], $defaults );
	}

	/**
	 * An engine that doesn't render titles and descriptions has no defaults.
	 *
	 * @return void
	 */
	public function test_base_engine_renders_no_defaults(): void {
		$engine = new class() extends \Outstand\WP\SEO\Engines\AbstractEngine {

			/**
			 * {@inheritDoc}
			 */
			public function get_slug(): string {
				return 'example';
			}

			/**
			 * {@inheritDoc}
			 */
			public function is_active(): bool {
				return true;
			}

			/**
			 * {@inheritDoc}
			 */
			public function disable_native_editor_ui(): void {}

			/**
			 * {@inheritDoc}
			 */
			public function get_field_map(): array {
				return [];
			}

			/**
			 * {@inheritDoc}
			 */
			public function get_primary_term_key_pattern(): string {
				return '';
			}

			/**
			 * {@inheritDoc}
			 *
			 * @param array<string,mixed> $args Normalized breadcrumb args.
			 */
			public function get_breadcrumb_html( array $args ): string {
				return '';
			}

			/**
			 * {@inheritDoc}
			 */
			public function get_schema_graph_filter(): string {
				return '';
			}
		};

		$this->assertSame( [ 'values' => [] ], $engine->get_editor_defaults( $this->create_post() ) );
	}

	/**
	 * When TSF's internals fail, the defaults are empty, a debug warning names
	 * the engine, and no temporary filter is left behind.
	 *
	 * @return void
	 */
	public function test_tsf_defaults_fail_safe(): void {
		$engine = $this->tsf();
		$fail   = static function () {
			throw new \RuntimeException( 'Example engine failure' );
		};

		add_filter( 'the_seo_framework_title_from_generation', $fail );

		$result = $this->capture_warnings( fn() => $engine->get_editor_defaults( $this->create_post(), [ 'postTitle' => 'Edited Title' ] ) );

		remove_filter( 'the_seo_framework_title_from_generation', $fail );

		$this->assertSame( [ 'values' => [] ], $result['return'] );
		$this->assertSame( [ TSF::class . '::get_editor_defaults: Example engine failure' ], $result['warnings'] );
		$this->assertSame( [], $this->callbacks_at_max_priority( 'single_post_title' ) );
		$this->assertSame( [], $this->callbacks_at_max_priority( 'get_post_metadata' ) );
	}

	/**
	 * When Yoast's internals fail, the defaults are empty, a debug warning
	 * names the engine, and no temporary filter is left behind.
	 *
	 * @return void
	 */
	public function test_yoast_defaults_fail_safe(): void {
		$engine = $this->yoast();
		$fail   = static function () {
			throw new \RuntimeException( 'Example engine failure' );
		};

		add_filter( 'wpseo_title', $fail );

		$result = $this->capture_warnings( fn() => $engine->get_editor_defaults( $this->create_post() ) );

		remove_filter( 'wpseo_title', $fail );

		$this->assertSame( [ 'values' => [] ], $result['return'] );
		$this->assertSame( [ Yoast::class . '::get_editor_defaults: Example engine failure' ], $result['warnings'] );
		$this->assertSame( [], $this->callbacks_at_max_priority( 'get_post_metadata' ) );
	}

	/**
	 * The TSF engine, skipping the test when TSF is not active.
	 *
	 * @return TSF
	 */
	private function tsf(): TSF {
		$engine = new TSF();
		if ( ! $engine->is_active() ) {
			$this->markTestSkipped( 'The SEO Framework is not active.' );
		}

		return $engine;
	}

	/**
	 * The Yoast engine, skipping the test when Yoast is not active.
	 *
	 * @return Yoast
	 */
	private function yoast(): Yoast {
		$engine = new Yoast();
		if ( ! $engine->is_active() ) {
			$this->markTestSkipped( 'Yoast SEO is not active.' );
		}

		return $engine;
	}

	/**
	 * Run a callback while recording `wp_trigger_error()` warnings in place of
	 * raising them.
	 *
	 * @param callable $callback Code under test.
	 * @return array{return:mixed,warnings:string[]}
	 */
	private function capture_warnings( callable $callback ): array {
		$warnings = [];
		$record   = static function ( $function_name, $message ) use ( &$warnings ) {
			$warnings[] = "{$function_name}: {$message}";
		};

		add_action( 'wp_trigger_error_always_run', $record, 10, 2 );
		add_filter( 'wp_trigger_error_trigger_error', '__return_false' );

		$return = $callback();

		remove_action( 'wp_trigger_error_always_run', $record );
		remove_filter( 'wp_trigger_error_trigger_error', '__return_false' );

		return [
			'return'   => $return,
			'warnings' => $warnings,
		];
	}

	/**
	 * Callbacks on a hook at `PHP_INT_MAX`, the priority the engines' temporary
	 * filters use.
	 *
	 * @param string $hook Hook name.
	 * @return array<string,mixed>
	 */
	private function callbacks_at_max_priority( string $hook ): array {
		return $GLOBALS['wp_filter'][ $hook ]->callbacks[ PHP_INT_MAX ] ?? [];
	}

	/**
	 * A published post titled "Hello World".
	 *
	 * @param string $excerpt Post excerpt.
	 * @return int
	 */
	private function create_post( string $excerpt = 'Example excerpt' ): int {
		return self::factory()->post->create(
			[
				'post_title'   => 'Hello World',
				'post_excerpt' => $excerpt,
			]
		);
	}
}
