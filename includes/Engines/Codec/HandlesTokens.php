<?php
/**
 * Shared comma-separated token-list helpers for CSV codecs.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines\Codec;

/**
 * Split/merge helpers for codecs that live inside one comma-separated meta value
 * shared by several fields (e.g. Yoast's `_yoast_wpseo_meta-robots-adv`).
 */
trait HandlesTokens {

	/**
	 * Split a comma-separated token string into a trimmed, non-empty list.
	 *
	 * @param mixed $raw CSV string.
	 * @return string[]
	 */
	protected function split_tokens( $raw ): array {
		if ( empty( $raw ) ) {
			return [];
		}

		return array_values( array_filter( array_map( 'trim', explode( ',', (string) $raw ) ) ) );
	}

	/**
	 * Add or remove a token from a comma-separated token string.
	 *
	 * @param mixed  $raw     CSV string.
	 * @param string $token   Token to toggle.
	 * @param bool   $present Whether the token should be present.
	 * @return string Updated CSV string.
	 */
	protected function with_token( $raw, string $token, bool $present ): string {
		$tokens = array_diff( $this->split_tokens( $raw ), [ $token ] );

		if ( $present ) {
			$tokens[] = $token;
		}

		return implode( ',', $tokens );
	}
}
