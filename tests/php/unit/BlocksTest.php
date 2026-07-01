<?php
/**
 * Block registration from the compiled output.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Blocks
 */
class BlocksTest extends \WP_UnitTestCase {

	/**
	 * The breadcrumbs block is registered by bootstrap (Blocks module on init).
	 *
	 * @return void
	 */
	public function test_breadcrumbs_block_registered(): void {
		$this->assertTrue(
			\WP_Block_Type_Registry::get_instance()->is_registered( 'outstand-seo/breadcrumbs' )
		);
	}

	/**
	 * The block renders a server-side callback (render.php).
	 *
	 * @return void
	 */
	public function test_breadcrumbs_block_is_dynamic(): void {
		$block = \WP_Block_Type_Registry::get_instance()->get_registered( 'outstand-seo/breadcrumbs' );

		$this->assertNotNull( $block );
		$this->assertTrue( is_callable( $block->render_callback ) );
	}
}
