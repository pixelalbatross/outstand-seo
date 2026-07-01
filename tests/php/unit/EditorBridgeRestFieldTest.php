<?php
/**
 * The canonical `outstand_seo` REST field: registration, get/update callbacks,
 * and the edit-post permission guard.
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
class EditorBridgeRestFieldTest extends \WP_UnitTestCase {

	/**
	 * Post fixture.
	 *
	 * @var int
	 */
	private int $post_id;

	/**
	 * Set up. Requires an active engine (TSF or Yoast).
	 *
	 * @return void
	 */
	public function set_up(): void {
		parent::set_up();

		if ( null === EngineManager::get_active() ) {
			$this->markTestSkipped( 'No SEO engine active in this environment.' );
		}

		$this->post_id = self::factory()->post->create();
		( new EditorBridge() )->register_rest_field();
	}

	/**
	 * The registered field args, or null.
	 *
	 * @return array|null
	 */
	private function field_args(): ?array {
		return $GLOBALS['wp_rest_additional_fields']['post']['outstand_seo'] ?? null;
	}

	/**
	 * The field is registered on the post type with an object schema.
	 *
	 * @return void
	 */
	public function test_field_registered(): void {
		$args = $this->field_args();

		$this->assertNotNull( $args );
		$this->assertSame( 'object', $args['schema']['type'] );
		$this->assertIsCallable( $args['get_callback'] );
		$this->assertIsCallable( $args['update_callback'] );
	}

	/**
	 * The get_callback returns the normalized canonical object for the post.
	 *
	 * @return void
	 */
	public function test_get_callback_returns_canonical(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'editor' ] ) );

		$value = call_user_func( $this->field_args()['get_callback'], [ 'id' => $this->post_id ] );

		$this->assertIsArray( $value );
		$this->assertArrayHasKey( 'noindex', $value );
		$this->assertArrayHasKey( 'title', $value );
	}

	/**
	 * The get_callback returns null for a user who cannot edit the post.
	 *
	 * @return void
	 */
	public function test_get_callback_denies_without_capability(): void {
		wp_set_current_user( self::factory()->user->create( [ 'role' => 'subscriber' ] ) );

		$value = call_user_func( $this->field_args()['get_callback'], [ 'id' => $this->post_id ] );

		$this->assertNull( $value );
	}

	/**
	 * The update_callback writes for a user who can edit the post.
	 *
	 * @return void
	 */
	public function test_update_callback_writes_for_editor(): void {
		$editor = self::factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $editor );

		$result = call_user_func(
			$this->field_args()['update_callback'],
			[ 'noindex' => 'off' ],
			get_post( $this->post_id )
		);

		$this->assertTrue( $result );
		$this->assertSame(
			'off',
			EngineManager::get_active()->normalize( $this->post_id )['noindex']
		);
	}

	/**
	 * The update_callback denies a user who cannot edit the post.
	 *
	 * @return void
	 */
	public function test_update_callback_denies_without_capability(): void {
		$subscriber = self::factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $subscriber );

		$result = call_user_func(
			$this->field_args()['update_callback'],
			[ 'noindex' => 'off' ],
			get_post( $this->post_id )
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
	}
}
