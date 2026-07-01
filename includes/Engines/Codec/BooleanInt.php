<?php
/**
 * Boolean codec backed by an integer 0/1 native value.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines\Codec;

/**
 * Canonical boolean stored as 0/1 (The SEO Framework's boolean meta).
 */
class BooleanInt implements CodecInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $raw Native meta value.
	 * @return bool
	 */
	public function decode( $raw ) {
		return (bool) (int) $raw;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $value   Canonical value.
	 * @param mixed $current Current native value (unused).
	 * @return int
	 */
	public function encode( $value, $current ) {
		return $value ? 1 : 0;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function kind(): string {
		return 'bool';
	}
}
