<?php
/**
 * Engine-generated editor defaults (placeholders/counters). These call the live
 * TSF / Yoast generators, so each case is skipped unless its plugin is active.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\TSF;
use Outstand\WP\SEO\Engines\Yoast;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\TSF
 * @covers \Outstand\WP\SEO\Engines\Yoast
 */
class EditorDefaultsTest extends \WP_UnitTestCase {

	/**
	 * TSF defaults expose title/description snapshots and a title template.
	 *
	 * @return void
	 */
	public function test_tsf_defaults(): void {
		$engine = new TSF();
		if ( ! $engine->is_active() ) {
			$this->markTestSkipped( 'The SEO Framework is not active.' );
		}

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$defaults = $engine->get_editor_defaults( $post_id );

		$this->assertArrayHasKey( 'values', $defaults );
		$this->assertArrayHasKey( 'title', $defaults['values'] );
		$this->assertArrayHasKey( 'description', $defaults['values'] );
		$this->assertIsString( $defaults['values']['title'] );

		// titleTemplate is either { prefix, suffix, untitled } or null.
		if ( null !== $defaults['titleTemplate'] ) {
			$this->assertArrayHasKey( 'prefix', $defaults['titleTemplate'] );
			$this->assertArrayHasKey( 'suffix', $defaults['titleTemplate'] );
			$this->assertArrayHasKey( 'untitled', $defaults['titleTemplate'] );
		}
	}

	/**
	 * The TSF title template keeps what a filter adds around the post title, and
	 * wrapping the post title in it gives the generated title.
	 *
	 * @dataProvider tsf_title_provider
	 *
	 * @param string $post_title     Post title.
	 * @param string $rendered_title Post title as it appears in the generated title.
	 * @return void
	 */
	public function test_tsf_title_template_keeps_filtered_affixes( string $post_title, string $rendered_title ): void {
		$engine = new TSF();
		if ( ! $engine->is_active() ) {
			$this->markTestSkipped( 'The SEO Framework is not active.' );
		}

		$prefix_title = static fn( $title ) => "Example Client - {$title}";

		add_filter( 'the_seo_framework_title_from_generation', $prefix_title );

		$post_id  = self::factory()->post->create( [ 'post_title' => $post_title ] );
		$defaults = $engine->get_editor_defaults( $post_id );

		remove_filter( 'the_seo_framework_title_from_generation', $prefix_title );

		$template = $defaults['titleTemplate'];

		$this->assertNotNull( $template );
		$this->assertStringEndsWith( 'Example Client - ', $template['prefix'] );
		$this->assertSame(
			$defaults['values']['title'],
			$template['prefix'] . $rendered_title . $template['suffix']
		);
		$this->assertSame( $template['prefix'] . 'Untitled' . $template['suffix'], $template['untitled'] );
	}

	/**
	 * Post titles for the TSF title template test.
	 *
	 * @return array<string,array{string,string}>
	 */
	public function tsf_title_provider(): array {
		return [
			'plain'      => [ 'Hello World', 'Hello World' ],
			'apostrophe' => [ "Don't Panic", "Don\u{2019}t Panic" ],
			'empty'      => [ '', 'Untitled' ],
		];
	}

	/**
	 * Yoast defaults resolve templates via wpseo_replace_vars.
	 *
	 * @return void
	 */
	public function test_yoast_defaults(): void {
		$engine = new Yoast();
		if ( ! $engine->is_active() ) {
			$this->markTestSkipped( 'Yoast SEO is not active.' );
		}

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$defaults = $engine->get_editor_defaults( $post_id );

		$this->assertArrayHasKey( 'values', $defaults );
		$this->assertArrayHasKey( 'title', $defaults['values'] );
		$this->assertIsString( $defaults['values']['title'] );
	}

	/**
	 * The Yoast title template keeps the spacing around the post title and what
	 * `wpseo_title` adds, and wrapping the post title in it gives the title.
	 *
	 * @return void
	 */
	public function test_yoast_title_template_keeps_filtered_affixes(): void {
		$engine = new Yoast();
		if ( ! $engine->is_active() ) {
			$this->markTestSkipped( 'Yoast SEO is not active.' );
		}

		$prefix_title = static fn( $title ) => "Example Client - {$title}";

		add_filter( 'wpseo_title', $prefix_title );

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$defaults = $engine->get_editor_defaults( $post_id );

		remove_filter( 'wpseo_title', $prefix_title );

		$template = $defaults['titleTemplate'];

		$this->assertNotNull( $template );
		$this->assertStringStartsWith( 'Example Client - ', $template['prefix'] );
		$this->assertSame( $defaults['values']['title'], $template['prefix'] . 'Hello World' . $template['suffix'] );
		$this->assertStringNotContainsString( 'Example Client', $defaults['values']['ogTitle'] );
		$this->assertStringNotContainsString( 'OUTSTANDSEOPOSTTITLE', $template['untitled'] );
	}

	/**
	 * The TSF title template finds the post title when a filter adds the same
	 * text in front of it.
	 *
	 * @return void
	 */
	public function test_tsf_title_template_with_post_title_repeated_in_prefix(): void {
		$engine = new TSF();
		if ( ! $engine->is_active() ) {
			$this->markTestSkipped( 'The SEO Framework is not active.' );
		}

		$prefix_title = static fn( $title ) => "Hello World - {$title}";

		add_filter( 'the_seo_framework_title_from_generation', $prefix_title );

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$defaults = $engine->get_editor_defaults( $post_id );

		remove_filter( 'the_seo_framework_title_from_generation', $prefix_title );

		$template = $defaults['titleTemplate'];

		$this->assertNotNull( $template );
		$this->assertStringEndsWith( 'Hello World - ', $template['prefix'] );
		$this->assertStringStartsWith( 'Hello World - Untitled', $template['untitled'] );
		$this->assertStringContainsString( 'Hello World - Hello World', $defaults['values']['title'] );
	}

	/**
	 * The Yoast title template finds the post title when `wpseo_title` adds the
	 * same text in front of it.
	 *
	 * @return void
	 */
	public function test_yoast_title_template_with_post_title_repeated_in_prefix(): void {
		$engine = new Yoast();
		if ( ! $engine->is_active() ) {
			$this->markTestSkipped( 'Yoast SEO is not active.' );
		}

		$prefix_title = static fn( $title ) => "Hello World - {$title}";

		add_filter( 'wpseo_title', $prefix_title );

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$defaults = $engine->get_editor_defaults( $post_id );

		remove_filter( 'wpseo_title', $prefix_title );

		$template = $defaults['titleTemplate'];

		$this->assertNotNull( $template );
		$this->assertSame( 'Hello World - ', $template['prefix'] );
		$this->assertStringStartsWith( 'Hello World - ', $template['untitled'] );
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

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$defaults = ( new TSF() )->get_editor_defaults( $post_id );

		$this->assertSame( [], $defaults['values'] );
		$this->assertNull( $defaults['titleTemplate'] );
	}

	/**
	 * The TSF title template is null when the generated title has no post title
	 * in it.
	 *
	 * @return void
	 */
	public function test_tsf_title_template_is_null_without_post_title(): void {
		$engine = new TSF();
		if ( ! $engine->is_active() ) {
			$this->markTestSkipped( 'The SEO Framework is not active.' );
		}

		$replace_title = static fn() => 'Example Fixed Title';

		add_filter( 'the_seo_framework_title_from_generation', $replace_title );

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$defaults = $engine->get_editor_defaults( $post_id );

		remove_filter( 'the_seo_framework_title_from_generation', $replace_title );

		$this->assertNull( $defaults['titleTemplate'] );
		$this->assertStringContainsString( 'Example Fixed Title', $defaults['values']['title'] );
	}

	/**
	 * Yoast defaults are empty when Yoast's replacement API is unavailable.
	 *
	 * @return void
	 */
	public function test_yoast_defaults_without_replace_vars(): void {
		if ( function_exists( 'wpseo_replace_vars' ) ) {
			$this->markTestSkipped( 'Yoast SEO is active.' );
		}

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$defaults = ( new Yoast() )->get_editor_defaults( $post_id );

		$this->assertSame( [], $defaults['values'] );
		$this->assertNull( $defaults['titleTemplate'] );
	}

	/**
	 * The Yoast title template is null when `wpseo_title` replaces the whole
	 * title.
	 *
	 * @return void
	 */
	public function test_yoast_title_template_is_null_without_post_title(): void {
		$engine = new Yoast();
		if ( ! $engine->is_active() ) {
			$this->markTestSkipped( 'Yoast SEO is not active.' );
		}

		$replace_title = static fn() => 'Example Fixed Title';

		add_filter( 'wpseo_title', $replace_title );

		$post_id  = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
		$defaults = $engine->get_editor_defaults( $post_id );

		remove_filter( 'wpseo_title', $replace_title );

		$this->assertNull( $defaults['titleTemplate'] );
		$this->assertSame( 'Example Fixed Title', $defaults['values']['title'] );
	}

	/**
	 * Without a presentation, the Yoast title skips `wpseo_title` and is still
	 * stripped of tags and trimmed.
	 *
	 * @return void
	 */
	public function test_yoast_filter_title_skips_filter_without_presentation(): void {
		$prefix_title = static fn( $title ) => "Example Client - {$title}";

		add_filter( 'wpseo_title', $prefix_title );

		$filter_title = new \ReflectionMethod( Yoast::class, 'filter_title' );
		$title        = $filter_title->invoke( new Yoast(), ' <b>Hello World</b> ', null );

		remove_filter( 'wpseo_title', $prefix_title );

		$this->assertSame( 'Hello World', $title );
	}
}
