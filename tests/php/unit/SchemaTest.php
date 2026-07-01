<?php
/**
 * JS config + REST schema derivation from the field map.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\TSF;
use Outstand\WP\SEO\Engines\Yoast;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Engines\AbstractEngine
 */
class SchemaTest extends \WP_UnitTestCase {

	/**
	 * JS config exposes engine slug, per-field kinds, and primaryTerms support.
	 *
	 * @return void
	 */
	public function test_js_config_shape(): void {
		$config = ( new TSF() )->get_js_config();

		$this->assertSame( 'tsf', $config['engine'] );
		$this->assertTrue( $config['primaryTerms'] );

		$this->assertSame( 'string', $config['fields']['title']['kind'] );
		$this->assertSame( 'robotsTri', $config['fields']['noindex']['kind'] );
		$this->assertSame( 'bool', $config['fields']['titleNoBlogname']['kind'] );
		$this->assertSame( 'int', $config['fields']['ogImageId']['kind'] );

		// No native storage details leak to JS.
		$this->assertArrayNotHasKey( 'key', $config['fields']['title'] );
		$this->assertArrayNotHasKey( 'codec', $config['fields']['title'] );
	}

	/**
	 * Yoast tri-state / bool kinds derive correctly.
	 *
	 * @return void
	 */
	public function test_yoast_kinds(): void {
		$fields = ( new Yoast() )->get_js_config()['fields'];

		$this->assertSame( 'robotsTri', $fields['noindex']['kind'] );
		$this->assertSame( 'robotsTri', $fields['nofollow']['kind'] );
		$this->assertSame( 'robotsTri', $fields['noarchive']['kind'] );
		$this->assertSame( 'bool', $fields['noimageindex']['kind'] );
		$this->assertSame( 'bool', $fields['cornerstone']['kind'] );
	}

	/**
	 * REST schema maps kinds to JSON-schema property types.
	 *
	 * @return void
	 */
	public function test_rest_schema_property_types(): void {
		$schema = ( new TSF() )->get_rest_schema();

		$this->assertSame( 'object', $schema['type'] );
		$this->assertSame( [ 'edit' ], $schema['context'] );

		$props = $schema['properties'];
		$this->assertSame( 'string', $props['title']['type'] );
		$this->assertSame( 'string', $props['noindex']['type'] );
		$this->assertSame( 'boolean', $props['titleNoBlogname']['type'] );
		$this->assertSame( 'integer', $props['ogImageId']['type'] );
		$this->assertSame( 'object', $props['primaryTerms']['type'] );
		$this->assertSame( 'integer', $props['primaryTerms']['additionalProperties']['type'] );
	}
}
