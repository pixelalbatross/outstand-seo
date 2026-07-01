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
	 * @param int $post_id Current post ID.
	 * @return array<string,mixed>
	 */
	public function get_editor_defaults( int $post_id ): array {
		// The generators below are TSF 5.0+ namespaced APIs; is_active() only
		// checks for tsf(), so guard against older cores lacking them.
		if ( ! class_exists( '\The_SEO_Framework\Meta\Title' ) ) {
			return [
				'values'        => [],
				'titleTemplate' => null,
			];
		}

		$args = [ 'id' => $post_id ];

		$title       = \The_SEO_Framework\Meta\Title::get_bare_generated_title( $args );
		$description = \The_SEO_Framework\Meta\Description::get_generated_description( $args );

		$values = [
			'title'              => $title,
			'description'        => $description,
			'ogTitle'            => \The_SEO_Framework\Meta\Open_Graph::get_generated_title( $args ),
			'ogDescription'      => \The_SEO_Framework\Meta\Open_Graph::get_generated_description( $args ),
			'twitterTitle'       => \The_SEO_Framework\Meta\Twitter::get_generated_title( $args ),
			'twitterDescription' => \The_SEO_Framework\Meta\Twitter::get_generated_description( $args ),
		];

		return [
			'values'        => array_map( [ $this, 'decode_entities' ], $values ),
			'titleTemplate' => $this->build_title_template( $args ),
		];
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param array<string,mixed> $args Block attributes.
	 * @return string
	 */
	public function get_breadcrumb_html( array $args ): string {

		if ( ! function_exists( 'tsf_breadcrumb' ) ) {
			return '';
		}

		$atts = [];
		if ( ! empty( $args['home'] ) ) {
			$atts['home'] = $args['home'];
		}

		return (string) tsf_breadcrumb( $atts );
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
	 * Build the { prefix, suffix } that wraps the live post title, mirroring how
	 * TSF appends the blogname addition with the configured separator and
	 * placement. Returns an empty-affix template when branding is off, so the
	 * title still tracks the live post title.
	 *
	 * @param array<string,mixed> $args TSF generator args (e.g. [ 'id' => 123 ]).
	 * @return array{prefix:string,suffix:string}
	 */
	private function build_title_template( array $args ): array {
		$addition = $this->decode_entities( \The_SEO_Framework\Meta\Title::get_addition() );

		if ( ! \The_SEO_Framework\Meta\Title\Conditions::use_branding( $args ) || '' === $addition ) {
			return [
				'prefix' => '',
				'suffix' => '',
			];
		}

		$separator = $this->decode_entities( \The_SEO_Framework\Meta\Title::get_separator() );
		$location  = \The_SEO_Framework\Meta\Title::get_addition_location();

		if ( 'left' === $location ) {
			return [
				'prefix' => "{$addition} {$separator} ",
				'suffix' => '',
			];
		}

		return [
			'prefix' => '',
			'suffix' => " {$separator} {$addition}",
		];
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
