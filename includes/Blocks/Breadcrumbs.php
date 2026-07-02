<?php

namespace Outstand\WP\SEO\Blocks;

use Outstand\WP\SEO\BaseModule;
use Outstand\WP\SEO\Engines\EngineInterface;
use Outstand\WP\SEO\Engines\EngineManager;

/**
 * Overrides the core/breadcrumbs block output with the active SEO engine's
 * breadcrumb trail. The engine also emits the matching BreadcrumbList JSON-LD
 * (in wp_head), so no schema is added here.
 */
class Breadcrumbs extends BaseModule {

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_filter( 'register_block_type_args', [ $this, 'register_block_attributes' ], 10, 2 );
		add_filter( 'render_block_core/breadcrumbs', [ $this, 'render' ], 10, 3 );
	}

	/**
	 * Register the engine `home` label attribute on core/breadcrumbs server-side
	 * so the REST block-renderer accepts it — the editor adds the same attribute
	 * client-side and passes it to ServerSideRender. Without this the renderer
	 * rejects the request with "Invalid parameter(s): attributes".
	 *
	 * @param array  $args Block type registration args.
	 * @param string $name Block name.
	 * @return array
	 */
	public function register_block_attributes( array $args, string $name ): array {

		if ( 'core/breadcrumbs' !== $name ) {
			return $args;
		}

		$args['attributes']['home'] = [
			'type'    => 'string',
			'default' => '',
		];

		return $args;
	}

	/**
	 * Replace core breadcrumb HTML with the active engine's trail.
	 *
	 * @param string    $content  Core-rendered block HTML.
	 * @param array     $block    Parsed block (explicit attributes only).
	 * @param \WP_Block $instance Block instance (attributes merged with defaults).
	 * @return string
	 */
	public function render( string $content, array $block, \WP_Block $instance ): string {

		$engine = EngineManager::get_active();

		// No engine → leave core's own trail untouched.
		if ( null === $engine ) {
			return $content;
		}

		$args = $this->map_attributes( $instance->attributes );

		// The REST block-renderer (editor preview) only calls setup_postdata(),
		// so the main query isn't singular and query-based engines produce an
		// empty trail. Pass the post ID from block context (falling back to the
		// global post) so engines can rebuild the trail for that specific post.
		$args[ EngineInterface::BREADCRUMB_POST_ID ] = (int) ( $instance->context['postId'] ?? get_the_ID() );

		$html = $engine->get_breadcrumb_html( $args );

		// Engine produced nothing (e.g. suppressed on the front page) → hide
		// entirely rather than falling back to core's differing trail.
		if ( '' === trim( $html ) ) {
			return '';
		}

		return $html;
	}

	/**
	 * Translate core/breadcrumbs attributes into the engine-neutral arg set.
	 *
	 * @param array<string,mixed> $attributes Merged core block attributes.
	 * @return array<string,mixed>
	 */
	private function map_attributes( array $attributes ): array {
		return [
			EngineInterface::BREADCRUMB_SEPARATOR        => $attributes['separator'] ?? '/',
			EngineInterface::BREADCRUMB_SHOW_HOME        => $attributes['showHomeItem'] ?? true,
			EngineInterface::BREADCRUMB_SHOW_CURRENT     => $attributes['showCurrentItem'] ?? true,
			EngineInterface::BREADCRUMB_PREFERS_TAXONOMY => $attributes['prefersTaxonomy'] ?? false,
			EngineInterface::BREADCRUMB_SHOW_ON_HOME     => $attributes['showOnHomePage'] ?? false,
			EngineInterface::BREADCRUMB_HOME             => $attributes['home'] ?? '',
		];
	}
}
