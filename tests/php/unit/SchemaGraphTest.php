<?php
/**
 * JSON-LD graph node injection.
 *
 * @package Outstand\WP\SEO\Tests\Unit
 */

namespace Outstand\WP\SEO\Tests\Unit;

use Outstand\WP\SEO\Engines\TSF;
use Outstand\WP\SEO\Engines\Yoast;
use Outstand\WP\SEO\Schema\Graph;

/**
 * Test case.
 *
 * @covers \Outstand\WP\SEO\Schema\Graph
 */
class SchemaGraphTest extends \WP_UnitTestCase {

	/**
	 * Tear down the test case.
	 *
	 * @return void
	 */
	public function tear_down(): void {
		remove_all_filters( 'outstand_seo_schema_nodes' );
		parent::tear_down();
	}

	/**
	 * Each engine names the filter its graph passes through.
	 *
	 * @return void
	 */
	public function test_engines_name_their_graph_filter(): void {
		$this->assertSame( 'the_seo_framework_schema_graph_data', ( new TSF() )->get_schema_graph_filter() );
		$this->assertSame( 'wpseo_schema_graph', ( new Yoast() )->get_schema_graph_filter() );
	}

	/**
	 * References resolve nested entities, keeping the first `@id` per type.
	 *
	 * @return void
	 */
	public function test_references_include_nested_entities(): void {

		$references = Graph::get_references( self::engine_graph() );

		$this->assertSame(
			[
				'WebSite'      => [ '@id' => 'https://example.org/#website' ],
				'Organization' => [ '@id' => 'https://example.org/#organization' ],
				'WebPage'      => [ '@id' => 'https://example.org/page/' ],
			],
			$references
		);
	}

	/**
	 * Builder nodes are appended and receive the references.
	 *
	 * @return void
	 */
	public function test_nodes_are_appended_with_references(): void {

		add_filter(
			'outstand_seo_schema_nodes',
			static function ( array $nodes, array $references ): array {
				$nodes[] = [
					'@type'     => 'Article',
					'@id'       => 'https://example.org/page/#article',
					'publisher' => $references['Organization'],
				];

				return $nodes;
			},
			10,
			2
		);

		$graph = ( new Graph() )->inject_nodes( self::engine_graph() );

		$this->assertCount( 3, $graph );
		$this->assertSame( 'Article', $graph[2]['@type'] );
		$this->assertSame( [ '@id' => 'https://example.org/#organization' ], $graph[2]['publisher'] );
	}

	/**
	 * Nodes without a type, and nodes restating an `@id` the graph holds, are dropped.
	 *
	 * @return void
	 */
	public function test_untyped_and_duplicate_nodes_are_dropped(): void {

		add_filter(
			'outstand_seo_schema_nodes',
			static fn (): array => [
				[ 'name' => 'EXAMPLE' ],
				[
					'@type' => 'Organization',
					'@id'   => 'https://example.org/#organization',
				],
				'not an entity',
			]
		);

		$graph = ( new Graph() )->inject_nodes( self::engine_graph() );

		$this->assertSame( self::engine_graph(), $graph );
	}

	/**
	 * A graph that is not an array passes through untouched.
	 *
	 * @return void
	 */
	public function test_non_array_graph_passes_through(): void {
		$this->assertNull( ( new Graph() )->inject_nodes( null ) );
	}

	/**
	 * A graph shaped like an engine's: the Organization nested as the WebSite's publisher.
	 *
	 * @return array[]
	 */
	private static function engine_graph(): array {
		return [
			[
				'@type'     => 'WebSite',
				'@id'       => 'https://example.org/#website',
				'publisher' => [
					'@type' => 'Organization',
					'@id'   => 'https://example.org/#organization',
					'name'  => 'EXAMPLE',
				],
			],
			[
				'@type'    => 'WebPage',
				'@id'      => 'https://example.org/page/',
				'isPartOf' => [ '@id' => 'https://example.org/#website' ],
			],
		];
	}
}
