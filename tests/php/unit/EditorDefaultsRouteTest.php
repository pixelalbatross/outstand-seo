<?php
/**
 * The REST route that returns the active engine's rendered titles and
 * descriptions for a post's unsaved editor state.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\EditorBridge;
use Outstand\WP\SEO\Engines\EngineManager;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\EditorBridge
 */
class EditorDefaultsRouteTest extends \WP_UnitTestCase {

	/**
	 * Post fixture.
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * Set up a fresh REST server.
	 *
	 * @return void
	 */
	public function set_up(): void {
		global $wp_rest_server;

		parent::set_up();

		$wp_rest_server = new \WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );

		$this->post_id = self::factory()->post->create( [ 'post_title' => 'Hello World' ] );
	}

	/**
	 * Tear down the REST server.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		global $wp_rest_server;

		$wp_rest_server = null;

		parent::tear_down();
	}

	/**
	 * The route is registered under the plugin's namespace.
	 *
	 * @return void
	 */
	public function test_route_registered(): void {
		$this->require_engine();

		$routes = rest_get_server()->get_routes( 'outstand-seo/v1' );

		$this->assertArrayHasKey( '/outstand-seo/v1/defaults/(?P<id>\d+)', $routes );
	}

	/**
	 * Logged-out requests are refused.
	 *
	 * @return void
	 */
	public function test_requires_login(): void {
		$this->require_engine();

		$response = $this->request( $this->post_id );

		$this->assertSame( 401, $response->get_status() );
	}

	/**
	 * Users who can't edit the post are refused.
	 *
	 * @return void
	 */
	public function test_requires_edit_capability(): void {
		$this->require_engine();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$response = $this->request( $this->post_id );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Posts of a type the sidebar doesn't support are refused.
	 *
	 * @return void
	 */
	public function test_refuses_unsupported_post_type(): void {
		$this->require_engine();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );

		$block_id = self::factory()->post->create( [ 'post_type' => 'wp_block' ] );
		$response = $this->request( $block_id );

		$this->assertSame( 403, $response->get_status() );
	}

	/**
	 * Without edits, the response renders the saved post.
	 *
	 * @return void
	 */
	public function test_renders_saved_post(): void {
		$this->require_engine();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$response = $this->request( $this->post_id );

		$this->assertSame( 200, $response->get_status() );
		$this->assertStringStartsWith( 'Hello World', $response->get_data()['values']['title'] );
	}

	/**
	 * The response renders the edited post title and SEO values.
	 *
	 * @return void
	 */
	public function test_renders_edits(): void {
		$this->require_engine();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$response = $this->request(
			$this->post_id,
			[
				'postTitle' => 'Edited Title',
				'values'    => [ 'description' => 'Custom description' ],
			]
		);
		$values   = $response->get_data()['values'];

		$this->assertSame( 200, $response->get_status() );
		$this->assertStringStartsWith( 'Edited Title', $values['title'] );
		$this->assertSame( 'Custom description', $values['ogDescription'] );
	}

	/**
	 * Values that don't match the canonical schema are rejected.
	 *
	 * @return void
	 */
	public function test_rejects_invalid_values(): void {
		$this->require_engine();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$response = $this->request( $this->post_id, [ 'values' => [ 'description' => [ 'Not a string' ] ] ] );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Strings longer than the route's limit are rejected.
	 *
	 * @return void
	 */
	public function test_rejects_overlong_strings(): void {
		$this->require_engine();

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$response = $this->request( $this->post_id, [ 'postTitle' => str_repeat( 'a', EditorBridge::REST_MAX_LENGTH + 1 ) ] );

		$this->assertSame( 400, $response->get_status() );
	}

	/**
	 * Without an active engine, the route isn't registered.
	 *
	 * @return void
	 */
	public function test_route_not_registered_without_engine(): void {
		$engine = EngineManager::get_active();

		if ( null !== $engine ) {
			$this->markTestSkipped( 'An SEO engine is active in this environment.' );
		}

		$this->assertSame( [], rest_get_server()->get_routes( 'outstand-seo/v1' ) );
	}

	/**
	 * Skip the test unless an engine (TSF or Yoast) is active.
	 *
	 * @return void
	 */
	private function require_engine(): void {
		$engine = EngineManager::get_active();

		if ( null === $engine ) {
			$this->markTestSkipped( 'No SEO engine active in this environment.' );
		}
	}

	/**
	 * Dispatch a defaults request for a post.
	 *
	 * @param int                 $post_id Post ID.
	 * @param array<string,mixed> $body    JSON body.
	 * @return \WP_REST_Response
	 */
	private function request( int $post_id, array $body = [] ): \WP_REST_Response {
		$request = new \WP_REST_Request( 'POST', "/outstand-seo/v1/defaults/{$post_id}" );
		$request->set_header( 'Content-Type', 'application/json' );
		$request->set_body( (string) wp_json_encode( $body ) );

		return rest_get_server()->dispatch( $request );
	}
}
