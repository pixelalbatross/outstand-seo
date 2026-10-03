<?php
/**
 * The SEO Framework engine adapter.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines;

use Outstand\WP\SEO\Engines\Codec\BooleanInt;
use Outstand\WP\SEO\Engines\Codec\TriState;
use Outstand\WP\SEO\Engines\Codec\TsfemFocus;

/**
 * Adapts Outstand SEO to The SEO Framework (autodescription).
 */
class TSF extends AbstractEngine {

	/**
	 * Shared post-meta key used by all TSF Extension Manager extensions, incl.
	 * the "Focus" extension that stores focus keywords.
	 *
	 * @var string
	 */
	private const FOCUS_EXT_META_KEY = '_tsfem-extension-post-meta';

	/**
	 * {@inheritDoc}
	 */
	public function get_slug(): string {
		return 'tsf';
	}

	/**
	 * {@inheritDoc}
	 */
	public function is_active(): bool {
		return function_exists( 'tsf' );
	}

	/**
	 * Remove TSF's classic SEO metabox; the frontend engine is untouched.
	 */
	public function disable_native_editor_ui(): void {
		add_filter( 'the_seo_framework_seobox_output', '__return_false' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public function get_field_map(): array {

		$bool = static fn( string $key ) => [
			'key'   => $key,
			'type'  => 'integer',
			'codec' => new BooleanInt(),
		];

		// TSF robots: -1 permissive, 0 default, 1 restrictive.
		$robots = static fn( string $key ) => [
			'key'   => $key,
			'type'  => 'integer',
			'codec' => new TriState( -1, 1, 0 ),
		];

		return [
			'title'              => $this->str_field( '_genesis_title' ),
			'description'        => $this->str_field( '_genesis_description' ),
			'canonical'          => $this->str_field( '_genesis_canonical_uri' ),
			'focusKw'            => $this->focus_kw_field(),
			'noindex'            => $robots( '_genesis_noindex' ),
			'nofollow'           => $robots( '_genesis_nofollow' ),
			'noarchive'          => $robots( '_genesis_noarchive' ),
			'titleNoBlogname'    => $bool( '_tsf_title_no_blogname' ),
			'excludeLocalSearch' => $bool( 'exclude_local_search' ),
			'excludeFromArchive' => $bool( 'exclude_from_archive' ),
			'redirect'           => $this->str_field( 'redirect' ),
			'ogTitle'            => $this->str_field( '_open_graph_title' ),
			'ogDescription'      => $this->str_field( '_open_graph_description' ),
			'ogImageUrl'         => $this->str_field( '_social_image_url' ),
			'ogImageId'          => $this->int_field( '_social_image_id' ),
			'twitterTitle'       => $this->str_field( '_twitter_title' ),
			'twitterDescription' => $this->str_field( '_twitter_description' ),
			'twitterCardType'    => $this->str_field( '_tsf_twitter_card_type' ),
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function get_primary_term_key_pattern(): string {
		return '_primary_term_%s';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Each value comes from the TSF generator its frontend output uses, so custom
	 * fields, fallbacks, branding and filters apply as they do on the page.
	 *
	 * @param int                 $post_id Current post ID.
	 * @param array<string,mixed> $edits   Unsaved editor state.
	 * @return array<string,mixed>
	 */
	public function get_editor_defaults( int $post_id, array $edits = [] ): array {
		// The generators below are TSF 5.0+ namespaced APIs; is_active() only
		// checks for tsf(), so guard against older cores lacking them.
		if ( ! class_exists( '\The_SEO_Framework\Meta\Title' ) ) {
			return [
				'values' => [],
			];
		}

		return $this->render_defaults( fn() => $this->render_values( $post_id, $edits ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * The `the_seo_framework_breadcrumb_list` filter exposes the crumb array
	 * before HTML, so home/current visibility can be honored alongside the
	 * separator and home label; prefers_taxonomy stays unsupported because TSF
	 * chooses the trail via its own logic with no per-call toggle.
	 *
	 * @return array<string,bool>
	 */
	public function get_breadcrumb_capabilities(): array {
		return [
			self::BREADCRUMB_SEPARATOR        => true,
			self::BREADCRUMB_HOME             => true,
			self::BREADCRUMB_SHOW_ON_HOME     => true,
			self::BREADCRUMB_SHOW_HOME        => true,
			self::BREADCRUMB_SHOW_CURRENT     => true,
			self::BREADCRUMB_PREFERS_TAXONOMY => false,
		];
	}

	/**
	 * {@inheritDoc}
	 */
	public function get_schema_graph_filter(): string {
		return 'the_seo_framework_schema_graph_data';
	}

	/**
	 * {@inheritDoc}
	 *
	 * Resolves the crumb list itself (rebuilding from the post ID under the REST
	 * block-renderer, where the main query isn't singular), applies the home and
	 * current visibility, then forces that list into tsf_breadcrumb() via a
	 * transient the_seo_framework_breadcrumb_list filter so the engine still owns
	 * the markup, CSS, and its own output filters.
	 *
	 * @param array<string,mixed> $args Normalized breadcrumb args.
	 * @return string
	 */
	public function get_breadcrumb_html( array $args ): string {

		if ( ! function_exists( 'tsf_breadcrumb' ) || ! class_exists( '\The_SEO_Framework\Meta\Breadcrumbs' ) ) {
			return '';
		}

		$args = $this->normalize_breadcrumb_args( $args );

		if ( ! $this->should_render_breadcrumbs( $args ) ) {
			return '';
		}

		// Under the REST block-renderer (editor preview) the main query isn't
		// singular, so the query-based trail is empty — build it from the block's
		// post ID instead. On the front end the real query drives the trail.
		$post_id   = $args[ self::BREADCRUMB_POST_ID ];
		$list_args = ( $post_id > 0 && defined( 'REST_REQUEST' ) && REST_REQUEST ) ? [ 'id' => $post_id ] : null;
		$crumbs    = \The_SEO_Framework\Meta\Breadcrumbs::get_breadcrumb_list( $list_args );

		if ( ! $args[ self::BREADCRUMB_SHOW_HOME ] ) {
			array_shift( $crumbs );
		}

		if ( ! $args[ self::BREADCRUMB_SHOW_CURRENT ] && ! empty( $crumbs ) ) {
			array_pop( $crumbs );
		}

		if ( empty( $crumbs ) ) {
			return '';
		}

		$atts = [ 'sep' => $args[ self::BREADCRUMB_SEPARATOR ] ];

		// tsf_breadcrumb() always relabels the first crumb with its 'home' att
		// (default "Home"). Use the block's home label when the home crumb is
		// shown; otherwise pin the att to the surviving first crumb's name so a
		// dropped-home trail isn't mislabeled "Home".
		$home = $args[ self::BREADCRUMB_HOME ];
		if ( $args[ self::BREADCRUMB_SHOW_HOME ] ) {
			if ( '' !== $home ) {
				$atts['home'] = $home;
			}
		} else {
			$atts['home'] = $crumbs[0]['name'] ?? '';
		}

		$force_list = static fn() => $crumbs;

		add_filter( 'the_seo_framework_breadcrumb_list', $force_list );

		$html = (string) tsf_breadcrumb( $atts );

		remove_filter( 'the_seo_framework_breadcrumb_list', $force_list );

		return $html;
	}

	/**
	 * Pre-fill the primary-term controls the way TSF's own selector does. When no
	 * primary term is stored, TSF resolves a fallback (lowest-ID assigned term,
	 * then its deepest descendant) and seeds the control with it — and persists
	 * that on the next save. Reuse TSF's resolver so our sidebar shows the same
	 * default instead of "— none —"; fall back to raw meta on older cores that
	 * lack the namespaced resolver.
	 *
	 * @param string $pattern  Primary-term meta key pattern (unused; TSF's
	 *                         resolver owns the key).
	 * @param int    $post_id  Current post ID.
	 * @return array<string,int>
	 */
	protected function read_primary_terms( string $pattern, int $post_id ): array {

		if ( ! class_exists( '\The_SEO_Framework\Data\Plugin\Post' ) ) {
			return parent::read_primary_terms( $pattern, $post_id );
		}

		$terms = [];

		foreach ( get_object_taxonomies( get_post_type( $post_id ) ) as $taxonomy ) {
			if ( ! is_taxonomy_hierarchical( $taxonomy ) ) {
				continue;
			}

			$terms[ $taxonomy ] = (int) \The_SEO_Framework\Data\Plugin\Post::get_primary_term_id( $post_id, $taxonomy );
		}

		return $terms;
	}

	/**
	 * The titles and descriptions TSF renders for the post from the edited
	 * state, each from the generator its frontend output uses.
	 *
	 * @param int                 $post_id Post ID.
	 * @param array<string,mixed> $edits   Unsaved editor state.
	 * @return array<string,string>
	 */
	private function render_values( int $post_id, array $edits ): array {
		$args       = [ 'id' => $post_id ];
		$post_title = $edits['postTitle'] ?? null;

		// Swapped in first, so the title filters TSF's frontend runs (e.g.
		// strip_tags) process the edited title too.
		$use_post_title = static function ( $title, $post ) use ( $post_id, $post_title ) {
			$title_post_id = (int) ( $post->ID ?? 0 );

			return $post_id === $title_post_id ? (string) $post_title : $title;
		};

		if ( [] !== $edits ) {
			// TSF memoizes its generators by args; the extra arg keeps output for
			// these edits apart from the saved post's.
			$args['outstand_seo_edits'] = md5( (string) wp_json_encode( $edits ) );
		}

		try {
			if ( null !== $post_title ) {
				add_filter( 'single_post_title', $use_post_title, PHP_INT_MIN, 2 );
			}

			// TSF memoizes post meta per post; refreshing around the computation
			// makes it read the edited meta and keeps the edits out of later reads.
			\The_SEO_Framework\Data\Plugin\Post::refresh_static_properties();

			return $this->with_edited_meta(
				$post_id,
				$edits['values'] ?? [],
				fn() => [
					'title'              => \The_SEO_Framework\Meta\Title::get_title( $args ),
					'description'        => \The_SEO_Framework\Meta\Description::get_description( $args ),
					'ogTitle'            => \The_SEO_Framework\Meta\Open_Graph::get_title( $args ),
					'ogDescription'      => \The_SEO_Framework\Meta\Open_Graph::get_description( $args ),
					'twitterTitle'       => \The_SEO_Framework\Meta\Twitter::get_title( $args ),
					'twitterDescription' => $this->get_twitter_description( $args ),
				]
			);
		} finally {
			remove_filter( 'single_post_title', $use_post_title, PHP_INT_MIN );
			\The_SEO_Framework\Data\Plugin\Post::refresh_static_properties();
		}
	}

	/**
	 * The Twitter description TSF renders. With Open Graph output off, TSF 5.1's
	 * generator for explicit args calls a missing `Title::get_custom_description()`
	 * once the custom Twitter description is empty, so that case follows its
	 * frontend chain: the custom Twitter description (the homepage setting first
	 * on a static front page), then the custom meta description, then the
	 * generated Twitter description.
	 *
	 * @param array<string,mixed> $args TSF generator args (e.g. [ 'id' => 123 ]).
	 * @return string
	 */
	private function get_twitter_description( array $args ): string {
		if ( \The_SEO_Framework\Meta\Twitter::fallback_to_open_graph() ) {
			return \The_SEO_Framework\Meta\Twitter::get_description( $args );
		}

		$post_id             = (int) $args['id'];
		$twitter_description = '';

		if ( \The_SEO_Framework\Helper\Query::is_static_front_page( $post_id ) ) {
			$twitter_description = (string) \The_SEO_Framework\Data\Plugin::get_option( 'homepage_twitter_description' );
		}

		if ( '' === $twitter_description ) {
			$twitter_description = (string) \The_SEO_Framework\Data\Plugin\Post::get_meta_item( '_twitter_description', $post_id );
		}

		if ( '' !== $twitter_description ) {
			return \The_SEO_Framework\Data\Filter\Sanitize::metadata_content( $twitter_description );
		}

		$description = \The_SEO_Framework\Meta\Description::get_custom_description( $args );

		if ( '' !== $description ) {
			return $description;
		}

		return \The_SEO_Framework\Meta\Twitter::get_generated_description( $args );
	}

	/**
	 * Focus-keyphrase field descriptor. When the paid TSF "Focus" extension is
	 * active, bind bidirectionally to its shared post-meta blob (via the
	 * `TsfemFocus` codec) so the keyword interops without a migration; otherwise
	 * store in the plugin-owned scalar key.
	 *
	 * @return array<string,mixed>
	 */
	private function focus_kw_field(): array {

		if ( defined( 'TSFEM_E_FOCUS_VERSION' ) ) {
			return [
				'key'      => self::FOCUS_EXT_META_KEY,
				'type'     => 'string',
				'codec'    => new TsfemFocus(),
				// TSFEM owns this serialized blob key; don't register a
				// sanitize_callback that would corrupt it.
				'register' => false,
			];
		}

		return $this->str_field( self::DEFAULT_FOCUS_KW_KEY );
	}
}
