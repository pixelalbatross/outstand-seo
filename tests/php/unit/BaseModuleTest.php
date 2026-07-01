<?php
/**
 * Module base class lifecycle.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\BaseModule;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\BaseModule
 */
class BaseModuleTest extends \WP_UnitTestCase {

	/**
	 * Registration runs and can_register() defaults to true.
	 *
	 * @return void
	 */
	public function test_default_can_register_and_register_runs(): void {
		$module = new class() extends BaseModule {
			/**
			 * Whether register() ran.
			 *
			 * @var bool
			 */
			public bool $ran = false;

			/**
			 * {@inheritDoc}
			 */
			public function register(): void {
				$this->ran = true;
			}
		};

		$this->assertTrue( $module->can_register() );

		$module->register();
		$this->assertTrue( $module->ran );
	}

	/**
	 * Overriding can_register() gates registration.
	 *
	 * @return void
	 */
	public function test_can_register_override(): void {
		$module = new class() extends BaseModule {
			/**
			 * {@inheritDoc}
			 */
			public function register(): void {}

			/**
			 * {@inheritDoc}
			 */
			public function can_register(): bool {
				return false;
			}
		};

		$this->assertFalse( $module->can_register() );
	}
}
