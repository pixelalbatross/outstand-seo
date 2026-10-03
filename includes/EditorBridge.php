<?php
/**
 * Editor bridge: wires the block-editor sidebar to the active SEO engine.
 *
 * Resolves the active engine, suppresses that engine's native editor UI, and
 * exposes its post meta to REST. When no supported engine is active the plugin
 * is dormant and shows an admin notice.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO;

use Outstand\WP\SEO\Engines\EngineManager;

/**
 * Connects the editor UI to whichever SEO engine is active.
 */
class EditorBridge extends BaseModule {

	/**
	 * REST namespace for the editor's routes.
	 *
	 * @var string
	 */
	public const REST_NAMESPACE = 'outstand-seo/v1';

	/**
	 * Longest string the defaults route accepts for a post title or SEO value.
	 *
	 * @var int
	 */
	public const REST_MAX_LENGTH = 5000;

	/**
	 * Register hooks. Engine resolution is deferred to `init` so engines that
	 * load after this plugin (e.g. Yoast, loaded alphabetically later) are
	 * detected.
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'detect_and_disable' ], 5 );
		add_action( 'init', [ $this, 'register_native_meta' ], 20 );
		add_action( 'init', [ $this, 'register_rest_field' ], 20 );
		add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
	}

	/**
	 * Resolve the active engine and disable its native editor UI, or surface a
	 * notice when none is active.
	 */
	public function detect_and_disable(): void {
		$engine = EngineManager::get_active();

		if ( null === $engine ) {
			add_action( 'admin_notices', [ $this, 'render_no_engine_notice' ] );
			return;
		}

		/**
		 * Filters whether to suppress the active engine's native editor UI.
		 *
		 * @param bool   $disable Whether to disable the native UI. Default true.
		 * @param string $engine  The active engine slug (e.g. "tsf", "yoast").
		 */
		if ( ! apply_filters( 'outstand_seo_disable_native_editor_ui', true, $engine->get_slug() ) ) {
			return;
		}

		$engine->disable_native_editor_ui();
	}

	/**
	 * Register the active engine's native meta keys (not REST-exposed).
	 */
	public function register_native_meta(): void {
		$engine = EngineManager::get_active();

		if ( null === $engine ) {
			return;
		}

		$engine->register_native_meta( $this->get_post_types() );
	}

	/**
	 * Register the canonical `outstand_seo` object field on every supported post
	 * type. The editor reads/writes this single normalized field; the active
	 * engine translates it to/from its native meta.
	 */
	public function register_rest_field(): void {
		$engine = EngineManager::get_active();

		if ( null === $engine ) {
			return;
		}

		$get_callback = static function ( array $post ) use ( $engine ) {
			// Defense in depth (the schema is 'edit'-only): never compute or
			// return SEO data for a user who cannot edit the post.
			if ( ! current_user_can( 'edit_post', (int) $post['id'] ) ) {
				return null;
			}

			return $engine->normalize( (int) $post['id'] );
		};

		$update_callback = static function ( $value, \WP_Post $post ) use ( $engine ) {
			if ( ! current_user_can( 'edit_post', $post->ID ) ) {
				return new \WP_Error(
					'outstand_seo_forbidden',
					__( 'You are not allowed to edit SEO data for this post.', 'outstand-seo' ),
					[ 'status' => rest_authorization_required_code() ]
				);
			}

			if ( is_array( $value ) ) {
				$engine->denormalize( $value, $post->ID );
			}

			return true;
		};

		foreach ( $this->get_post_types() as $post_type ) {
			register_rest_field(
				$post_type,
				'outstand_seo',
				[
					'get_callback'    => $get_callback,
					'update_callback' => $update_callback,
					'schema'          => $engine->get_rest_schema(),
				]
			);
		}
	}

	/**
	 * Register the route that returns the active engine's rendered titles and
	 * descriptions for a post's unsaved editor state.
	 */
	public function register_rest_routes(): void {
		$engine = EngineManager::get_active();

		if ( null === $engine ) {
			return;
		}

		$post_types    = $this->get_post_types();
		$values_schema = $engine->get_rest_schema();
		unset( $values_schema['context'] );

		// Caps the text each request makes the engine process.
		foreach ( $values_schema['properties'] as $name => $property ) {
			if ( 'string' === $property['type'] ) {
				$values_schema['properties'][ $name ]['maxLength'] = self::REST_MAX_LENGTH;
			}
		}

		$permission_callback = static function ( \WP_REST_Request $request ) use ( $post_types ) {
			$post_id   = (int) $request['id'];
			$post_type = get_post_type( $post_id );

			if ( ! $post_type || ! in_array( $post_type, $post_types, true ) ) {
				return false;
			}

			return current_user_can( 'edit_post', $post_id );
		};

		$callback = static function ( \WP_REST_Request $request ) use ( $engine ) {
			$edits = [
				'values' => (array) $request['values'],
			];

			if ( null !== $request['postTitle'] ) {
				$edits['postTitle'] = $request['postTitle'];
			}

			return rest_ensure_response( $engine->get_editor_defaults( (int) $request['id'], $edits ) );
		};

		register_rest_route(
			self::REST_NAMESPACE,
			'/defaults/(?P<id>\\d+)',
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => $callback,
				'permission_callback' => $permission_callback,
				'args'                => [
					'id'        => [
						'type'     => 'integer',
						'required' => true,
					],
					'postTitle' => [
						'type'      => 'string',
						'maxLength' => self::REST_MAX_LENGTH,
					],
					'values'    => $values_schema + [ 'default' => [] ],
				],
			]
		);
	}

	/**
	 * Admin notice shown when no supported SEO engine is active.
	 */
	public function render_no_engine_notice(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		?>
		<div class="notice notice-warning">
			<p>
				<?php
				esc_html_e(
					'Outstand SEO needs a supported SEO engine active: The SEO Framework or Yoast SEO.',
					'outstand-seo'
				);
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * Post types the SEO field applies to.
	 *
	 * @return string[]
	 */
	private function get_post_types(): array {
		return PostTypes::get();
	}
}
