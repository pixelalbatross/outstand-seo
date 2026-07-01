<?php
/**
 * A value codec: translates one canonical field between the editor's canonical
 * value and an engine's native meta storage.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines\Codec;

/**
 * Bidirectional transformer for a single field. Engines compose these in their
 * field maps; the base engine has no codec knowledge of its own.
 */
interface CodecInterface {

	/**
	 * Native meta value -> canonical value.
	 *
	 * @param mixed $raw Native meta value.
	 * @return mixed Canonical value.
	 */
	public function decode( $raw );

	/**
	 * Canonical value -> native meta value.
	 *
	 * @param mixed $value   Canonical value.
	 * @param mixed $current Current native value (for keys shared by several
	 *                       fields, e.g. a CSV token list).
	 * @return mixed Native value to store.
	 */
	public function encode( $value, $current );

	/**
	 * Canonical UI kind, driving the JS schema and REST property type.
	 *
	 * @return string One of 'string' | 'int' | 'bool' | 'robotsTri'.
	 */
	public function kind(): string;
}
