<?php
/**
 * Yoast SEO engine adapter.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines;

use Outstand\WP\SEO\Engines\Codec\BooleanString;
use Outstand\WP\SEO\Engines\Codec\CsvFlag;
use Outstand\WP\SEO\Engines\Codec\CsvTriState;
use Outstand\WP\SEO\Engines\Codec\TriState;
use Outstand\WP\SEO\Engines\Codec\TwoStateTriState;

/**
 * Adapts Outstand SEO to Yoast SEO (wordpress-seo).
 */
class Yoast extends AbstractEngine {

	/**
	 * {@inheritDoc}
	 */
	public function get_slug(): string {
		return 'yoast';
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_active(): bool {
		return defined( 'WPSEO_VERSION' );
	}

	/**
	 * Disable Yoast's editor surfaces. The `wpseo_enable_editor_features_{pt}`
	 * filter gates both the classic metabox and the block-editor sidebar,
	 * pre-publish, and document panels. Dequeuing the block-editor style is a
	 * belt-and-suspenders for the editor-iframe CSS. Frontend indexable output
	 * reads `_yoast_wpseo_*` postmeta independently, so it is unaffected.
	 */
	public function disable_native_editor_ui(): void {
		add_action( 'init', [ $this, 'filter_editor_features' ], 99 );
		add_action( 'admin_enqueue_scripts', [ $this, 'dequeue_editor_assets' ], 100 );
		add_action( 'enqueue_block_assets', [ $this, 'dequeue_editor_assets' ], 100 );
	}

	/**
	 * Turn off Yoast's editor features for every public post type.
	 */
	public function filter_editor_features(): void {
		foreach ( get_post_types( [ 'public' => true ] ) as $post_type ) {
			add_filter( "wpseo_enable_editor_features_{$post_type}", '__return_false' );
		}
	}

	/**
	 * Dequeue Yoast's editor scripts/styles as a fallback.
	 */
	public function dequeue_editor_assets(): void {
		wp_dequeue_style( 'yoast-seo-block-editor' );
		wp_dequeue_script( 'yoast-seo-post-edit' );
		wp_dequeue_script( 'yoast-seo-block-editor' );
		wp_dequeue_script( 'yoast-seo-post-edit-classic' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_field_map(): array {

		// A boolean token within the shared `_yoast_wpseo_meta-robots-adv` CSV.
		$adv = static fn( string $token ) => [
			'key'   => '_yoast_wpseo_meta-robots-adv',
			'type'  => 'string',
			'codec' => new CsvFlag( $token ),
		];

		return [
			'title'              => $this->str_field( '_yoast_wpseo_title' ),
			'description'        => $this->str_field( '_yoast_wpseo_metadesc' ),
			'canonical'          => $this->str_field( '_yoast_wpseo_canonical' ),
			'focusKw'            => $this->str_field( '_yoast_wpseo_focuskw' ),
			// Yoast noindex: 2 permissive, 0 default, 1 restrictive.
			'noindex'            => [
				'key'   => '_yoast_wpseo_meta-robots-noindex',
				'type'  => 'string',
				'codec' => new TriState( '2', '1', '0' ),
			],
			// Yoast nofollow: only the restrictive '1' persists.
			'nofollow'           => [
				'key'   => '_yoast_wpseo_meta-robots-nofollow',
				'type'  => 'string',
				'codec' => new TwoStateTriState( '1', '0' ),
			],
			// Archiving lives as the `noarchive` token in the shared CSV.
			'noarchive'          => [
				'key'   => '_yoast_wpseo_meta-robots-adv',
				'type'  => 'string',
				'codec' => new CsvTriState( 'noarchive' ),
			],
			'noimageindex'       => $adv( 'noimageindex' ),
			'nosnippet'          => $adv( 'nosnippet' ),
			'cornerstone'        => [
				'key'   => '_yoast_wpseo_is_cornerstone',
				'type'  => 'string',
				'codec' => new BooleanString( '1', 'false' ),
			],
			'redirect'           => $this->str_field( '_yoast_wpseo_redirect' ),
			'ogTitle'            => $this->str_field( '_yoast_wpseo_opengraph-title' ),
			'ogDescription'      => $this->str_field( '_yoast_wpseo_opengraph-description' ),
			'ogImageUrl'         => $this->str_field( '_yoast_wpseo_opengraph-image' ),
			'ogImageId'          => $this->int_field( '_yoast_wpseo_opengraph-image-id' ),
			'twitterTitle'       => $this->str_field( '_yoast_wpseo_twitter-title' ),
			'twitterDescription' => $this->str_field( '_yoast_wpseo_twitter-description' ),
			'twitterImageUrl'    => $this->str_field( '_yoast_wpseo_twitter-image' ),
			'twitterImageId'     => $this->int_field( '_yoast_wpseo_twitter-image-id' ),
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function get_primary_term_key_pattern(): string {
		return '_yoast_wpseo_primary_%s';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Rebuilds the post's indexable in memory from the edited meta with Yoast's
	 * post builder and renders each value through Yoast's own presenter, so
	 * custom fields, templates, fallbacks and filters apply as they do on the
	 * page.
	 *
	 * @param int                 $post_id Current post ID.
	 * @param array<string,mixed> $edits   Unsaved editor state.
	 * @return array<string,mixed>
	 */
	public function get_editor_defaults( int $post_id, array $edits = [] ): array {
		return $this->render_defaults( fn() => $this->render_values( $post_id, $edits ) );
	}


	/**
	 * {@inheritDoc}
	 *
	 * Yoast exposes runtime filters over its crumb list and separator, so every
	 * arg but prefers_taxonomy (whose trail rebuild is too fragile) can be
	 * honored per block instance.
	 *
	 * @return array<string,bool>
	 */
	public function get_breadcrumb_capabilities(): array {
		return [
			self::BREADCRUMB_SEPARATOR        => true,
			self::BREADCRUMB_SHOW_HOME        => true,
			self::BREADCRUMB_SHOW_CURRENT     => true,
			self::BREADCRUMB_HOME             => true,
			self::BREADCRUMB_SHOW_ON_HOME     => true,
			self::BREADCRUMB_PREFERS_TAXONOMY => false,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_schema_graph_filter(): string {
		return 'wpseo_schema_graph';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Wraps yoast_breadcrumb() in transient filters that apply the block's
	 * separator, home label, and home/current visibility, then removes them so
	 * the overrides never leak into other breadcrumb calls.
	 *
	 * @param array<string,mixed> $args Normalized breadcrumb args.
	 * @return string
	 */
	public function get_breadcrumb_html( array $args ): string {
		if ( ! function_exists( 'yoast_breadcrumb' ) ) {
			return '';
		}

		$args = $this->normalize_breadcrumb_args( $args );

		if ( ! $this->should_render_breadcrumbs( $args ) ) {
			return '';
		}

		$separator = static fn() => $args[ self::BREADCRUMB_SEPARATOR ];

		$links = static function ( $crumbs ) use ( $args ) {
			if ( ! is_array( $crumbs ) || empty( $crumbs ) ) {
				return $crumbs;
			}

			$home = $args[ self::BREADCRUMB_HOME ];
			if ( '' !== $home && isset( $crumbs[0]['text'] ) ) {
				$crumbs[0]['text'] = $home;
			}

			if ( ! $args[ self::BREADCRUMB_SHOW_HOME ] ) {
				array_shift( $crumbs );
			}

			if ( ! $args[ self::BREADCRUMB_SHOW_CURRENT ] && ! empty( $crumbs ) ) {
				array_pop( $crumbs );
			}

			return $crumbs;
		};

		add_filter( 'wpseo_breadcrumb_separator', $separator );
		add_filter( 'wpseo_breadcrumb_links', $links );

		$html = (string) yoast_breadcrumb( '', '', false );

		remove_filter( 'wpseo_breadcrumb_separator', $separator );
		remove_filter( 'wpseo_breadcrumb_links', $links );

		return $html;
	}

	/**
	 * The titles and descriptions Yoast renders for the post from the edited
	 * state, each through its own presenter. Empty when Yoast can't build the
	 * post's presentation.
	 *
	 * The saved indexable is looked up before the edited meta applies, so the
	 * lookup only ever stores what Yoast would store for the saved post.
	 *
	 * @param int                 $post_id Post ID.
	 * @param array<string,mixed> $edits   Unsaved editor state.
	 * @return array<string,string>
	 */
	private function render_values( int $post_id, array $edits ): array {
		if ( ! function_exists( 'YoastSEO' ) ) {
			return [];
		}

		$indexable = $this->copy_indexable( $post_id );

		return $this->with_edited_meta(
			$post_id,
			$edits['values'] ?? [],
			fn() => $this->render_presentation( $post_id, $indexable, $edits['postTitle'] ?? null )
		);
	}

	/**
	 * An unsaved copy of the post's indexable, or an empty one when Yoast has
	 * none yet (e.g. an auto-draft).
	 *
	 * @param int $post_id Post ID.
	 * @return \Yoast\WP\SEO\Models\Indexable
	 */
	private function copy_indexable( int $post_id ) {
		$repository = YoastSEO()->classes->get( \Yoast\WP\SEO\Repositories\Indexable_Repository::class );
		$indexable  = $repository->find_by_id_and_type( $post_id, 'post', false );
		$data       = $indexable ? $indexable->as_array() : [
			'object_id'   => $post_id,
			'object_type' => 'post',
		];

		return $repository->query()->create( $data );
	}

	/**
	 * Render the post's values from an indexable copy that Yoast's post builder
	 * refreshes from the current meta.
	 *
	 * @param int                            $post_id    Post ID.
	 * @param \Yoast\WP\SEO\Models\Indexable $indexable  Unsaved indexable copy.
	 * @param string|null                    $post_title Edited post title, or null for the saved one.
	 * @return array<string,string>
	 */
	private function render_presentation( int $post_id, $indexable, ?string $post_title ): array {
		$presentation = $this->build_presentation( $post_id, $indexable );

		if ( null === $presentation ) {
			return [];
		}

		if ( null !== $post_title ) {
			$source               = clone $presentation->source;
			$source->post_title   = $post_title;
			$presentation->source = $source;
		}

		$og_title       = $this->present( \Yoast\WP\SEO\Presenters\Open_Graph\Title_Presenter::class, $presentation );
		$og_description = $this->present( \Yoast\WP\SEO\Presenters\Open_Graph\Description_Presenter::class, $presentation );

		// Yoast leaves out the Twitter tags that would repeat Open Graph, and X
		// then shows og:title and og:description.
		$twitter_title       = $this->present( \Yoast\WP\SEO\Presenters\Twitter\Title_Presenter::class, $presentation );
		$twitter_description = $this->present( \Yoast\WP\SEO\Presenters\Twitter\Description_Presenter::class, $presentation );

		return [
			'title'              => $this->present( \Yoast\WP\SEO\Presenters\Title_Presenter::class, $presentation ),
			'description'        => $this->present( \Yoast\WP\SEO\Presenters\Meta_Description_Presenter::class, $presentation ),
			'ogTitle'            => $og_title,
			'ogDescription'      => $og_description,
			'twitterTitle'       => '' !== $twitter_title ? $twitter_title : $og_title,
			'twitterDescription' => '' !== $twitter_description ? $twitter_description : $og_description,
		];
	}

	/**
	 * The presentation Yoast's frontend renders for the post, built from the
	 * indexable copy. An auto-draft reads as a draft while the copy builds,
	 * since Yoast's builder skips auto-drafts. Null when Yoast doesn't index the
	 * post.
	 *
	 * @param int                            $post_id   Post ID.
	 * @param \Yoast\WP\SEO\Models\Indexable $indexable Unsaved indexable copy.
	 * @return \Yoast\WP\SEO\Presentations\Indexable_Presentation|null
	 */
	private function build_presentation( int $post_id, $indexable ) {
		$read_as_draft = static function ( $status, $post ) use ( $post_id ) {
			$status_post_id = (int) ( $post->ID ?? 0 );

			return 'auto-draft' === $status && $post_id === $status_post_id ? 'draft' : $status;
		};

		add_filter( 'get_post_status', $read_as_draft, 10, 2 );

		try {
			YoastSEO()->classes->get( \Yoast\WP\SEO\Builders\Indexable_Post_Builder::class )->build( $post_id, $indexable );
		} catch ( \Yoast\WP\SEO\Exceptions\Indexable\Indexable_Exception $exception ) {
			return null;
		} finally {
			remove_filter( 'get_post_status', $read_as_draft, 10 );
		}

		// The memoizer caches by indexable ID; clearing around the build keeps
		// the edited copy out of Yoast's output for the saved post.
		$memoizer = YoastSEO()->classes->get( \Yoast\WP\SEO\Memoizers\Meta_Tags_Context_Memoizer::class );
		$memoizer->clear( $indexable );

		try {
			$context = $memoizer->get( $indexable, 'Post_Type' );

			/** This filter is documented in wordpress-seo/src/integrations/front-end-integration.php */
			return apply_filters( 'wpseo_frontend_presentation', $context->presentation, $context ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Yoast filter.
		} finally {
			$memoizer->clear( $indexable );
		}
	}

	/**
	 * Render a presentation value through a Yoast presenter.
	 *
	 * @param string                                             $presenter_class Presenter class name.
	 * @param \Yoast\WP\SEO\Presentations\Indexable_Presentation $presentation    Presentation to render.
	 * @return string
	 */
	private function present( string $presenter_class, $presentation ): string {
		$presenter               = new $presenter_class();
		$presenter->presentation = $presentation;
		$presenter->helpers      = YoastSEO()->helpers;
		$presenter->replace_vars = YoastSEO()->classes->get( \WPSEO_Replace_Vars::class );

		return (string) $presenter->get();
	}
}
