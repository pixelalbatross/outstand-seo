<?php
/**
 * Content-type nodes injected into the active engine's JSON-LD graph.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Schema;

use Outstand\WP\SEO\BaseModule;
use Outstand\WP\SEO\Engines\EngineManager;

/**
 * Appends the nodes returned by `outstand_seo_schema_nodes` callbacks to the graph the active
 * engine prints, so a page carries one graph in which the added nodes reference the engine's own
 * Organization, WebSite and WebPage entities by `@id`.
 */
class Graph extends BaseModule {

	/**
	 * Entity types whose `@id` is handed to node builders.
	 *
	 * @var string[]
	 */
	public const REFERENCE_TYPES = [ 'Organization', 'Person', 'WebSite', 'WebPage' ];

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'init', [ $this, 'hook_engine_graph' ] );
	}

	/**
	 * Hooks the injection onto the active engine's graph filter, once the engine is resolvable.
	 *
	 * @return void
	 */
	public function hook_engine_graph(): void {

		$engine = EngineManager::get_active();

		if ( null === $engine ) {
			return;
		}

		$filter = $engine->get_schema_graph_filter();

		if ( '' === $filter ) {
			return;
		}

		add_filter( $filter, [ $this, 'inject_nodes' ], 20 );
	}

	/**
	 * Appends the builders' nodes to a graph.
	 *
	 * A node without a `@type` is dropped, and so is one whose `@id` the graph already holds, so
	 * a builder can never restate an engine entity.
	 *
	 * @param  mixed $graph A sequential list of graph entities.
	 * @return mixed
	 */
	public function inject_nodes( $graph ) {

		if ( ! is_array( $graph ) ) {
			return $graph;
		}

		$references = self::get_references( $graph );

		/**
		 * Filters the nodes added to the page's JSON-LD graph.
		 *
		 * @param array[] $nodes      Nodes to add, each an entity array with a `@type`.
		 * @param array   $references `@type` => `[ '@id' => … ]` for the engine entities present
		 *                            in the graph (Organization, Person, WebSite, WebPage).
		 */
		$nodes = apply_filters( 'outstand_seo_schema_nodes', [], $references );

		if ( ! is_array( $nodes ) ) {
			return $graph;
		}

		$ids = self::get_ids( $graph );

		foreach ( $nodes as $node ) {
			if ( ! is_array( $node ) || empty( $node['@type'] ) ) {
				continue;
			}

			$node_id = (string) ( $node['@id'] ?? '' );

			if ( '' !== $node_id && isset( $ids[ $node_id ] ) ) {
				continue;
			}

			$graph[] = $node;

			if ( '' !== $node_id ) {
				$ids[ $node_id ] = true;
			}
		}

		return $graph;
	}

	/**
	 * Finds the first `@id` of each reference type anywhere in a graph, nested entities included
	 * (an engine may embed its Organization as the WebSite's publisher).
	 *
	 * @param  array $graph A graph or any entity within it.
	 * @return array<string, array{'@id': string}>
	 */
	public static function get_references( array $graph ): array {

		$references = [];

		self::walk_entities(
			$graph,
			static function ( array $entity ) use ( &$references ): void {

				$type = $entity['@type'] ?? '';

				if ( ! is_string( $type ) || ! in_array( $type, self::REFERENCE_TYPES, true ) ) {
					return;
				}

				if ( isset( $references[ $type ] ) || empty( $entity['@id'] ) ) {
					return;
				}

				$references[ $type ] = [ '@id' => (string) $entity['@id'] ];
			}
		);

		return $references;
	}

	/**
	 * Collects every `@id` a graph declares on an entity with a `@type`.
	 *
	 * @param  array $graph A graph.
	 * @return array<string, true>
	 */
	private static function get_ids( array $graph ): array {

		$ids = [];

		self::walk_entities(
			$graph,
			static function ( array $entity ) use ( &$ids ): void {
				if ( ! empty( $entity['@type'] ) && ! empty( $entity['@id'] ) ) {
					$ids[ (string) $entity['@id'] ] = true;
				}
			}
		);

		return $ids;
	}

	/**
	 * Calls a visitor on every array within a graph, depth first.
	 *
	 * @param  array    $value   A graph, entity or property value.
	 * @param  callable $visitor Receives each array.
	 * @return void
	 */
	private static function walk_entities( array $value, callable $visitor ): void {

		$visitor( $value );

		foreach ( $value as $child ) {
			if ( is_array( $child ) ) {
				self::walk_entities( $child, $visitor );
			}
		}
	}
}
