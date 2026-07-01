<?php
/**
 * Resolves the active background SEO engine.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines;

/**
 * Picks the active engine in priority order (first active wins).
 */
class EngineManager {

	/**
	 * Resolved engine cache. `false` means "not yet resolved".
	 *
	 * @var EngineInterface|null|false
	 */
	private static $active = false;

	/**
	 * Candidate engine classes in priority order.
	 *
	 * @return EngineInterface[]
	 */
	private static function candidates(): array {
		return [
			new TSF(),
			new Yoast(),
		];
	}

	/**
	 * The active engine, or null when no supported engine is active.
	 *
	 * @return EngineInterface|null
	 */
	public static function get_active(): ?EngineInterface {

		if ( false !== self::$active ) {
			return self::$active;
		}

		self::$active = null;

		foreach ( self::candidates() as $engine ) {
			if ( $engine->is_active() ) {
				self::$active = $engine;
				break;
			}
		}

		return self::$active;
	}
}
