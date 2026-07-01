<?php
/**
 * Focus-keyword codec for the TSF "Focus" extension's shared serialized blob.
 *
 * @package OutstandSEO
 */

namespace Outstand\WP\SEO\Engines\Codec;

/**
 * Reads/writes the first focus keyword inside The SEO Framework Extension
 * Manager's shared `_tsfem-extension-post-meta` blob, preserving every other
 * extension's data and the other keyword slots. When the keyword changes, the
 * slot's derived analysis (inflections/synonyms/scores) is reset so a re-enabled
 * Focus UI recomputes cleanly. Frontend output is unaffected (editor-only data).
 */
class TsfemFocus implements CodecInterface {

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $raw Native blob value.
	 * @return string The keyword, or '' when unset.
	 */
	public function decode( $raw ) {
		$blob = $this->unserialize_blob( $raw );

		return (string) ( $blob['focus']['kw'][0]['keyword'] ?? '' );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param mixed $value   New focus keyword.
	 * @param mixed $current Current native blob value.
	 * @return string TSFEM-serialized blob (addslashes(serialize()), as TSFEM stores it).
	 */
	public function encode( $value, $current ) {
		$keyword = (string) $value;
		$blob    = $this->unserialize_blob( $current );

		if ( ! isset( $blob['focus']['kw'] ) || ! is_array( $blob['focus']['kw'] ) ) {
			$blob['focus']['kw'] = [];
		}

		if ( ! isset( $blob['focus']['kw'][0] ) || ! is_array( $blob['focus']['kw'][0] ) ) {
			$blob['focus']['kw'][0] = $this->slot_defaults();
		}

		$slot    = &$blob['focus']['kw'][0];
		$changed = (string) ( $slot['keyword'] ?? '' ) !== $keyword;

		$slot['keyword'] = $keyword;

		if ( $changed ) {
			$slot = array_merge( $slot, $this->derived_reset() );
		}

		unset( $slot );

		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_serialize -- Must match TSFEM's exact storage format for interop.
		return addslashes( serialize( $blob ) );
	}

	/**
	 * {@inheritDoc}
	 *
	 * @return string
	 */
	public function kind(): string {
		return 'string';
	}

	/**
	 * Unserialize the TSFEM shared blob, mirroring its own read logic (a caching
	 * plugin may hand back an array; otherwise it is a serialized string).
	 *
	 * @param mixed $raw Native meta value.
	 * @return array<string,mixed>
	 */
	private function unserialize_blob( $raw ): array {
		if ( is_array( $raw ) ) {
			return $raw;
		}

		if ( is_string( $raw ) && is_serialized( $raw ) ) {
			// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- Restricted to stdClass, mirrors TSFEM.
			$data = unserialize( $raw, [ 'allowed_classes' => [ 'stdClass' ] ] );
			return is_array( $data ) ? $data : [];
		}

		return [];
	}

	/**
	 * Default structure for a Focus keyword slot, matching the extension's own
	 * `$pm_defaults`, used when seeding a previously-empty slot.
	 *
	 * @return array<string,mixed>
	 */
	private function slot_defaults(): array {
		return array_merge( [ 'keyword' => '' ], $this->derived_reset() );
	}

	/**
	 * The derived (non-keyword) fields of a Focus keyword slot in their empty
	 * state, used to clear stale analysis when the keyword changes.
	 *
	 * @return array<string,mixed>
	 */
	private function derived_reset(): array {
		return [
			'lexical_form'         => '',
			'lexical_data'         => [],
			'definition_selection' => '',
			'inflection_data'      => [],
			'synonym_data'         => [],
			'active_inflections'   => '',
			'active_synonyms'      => '',
			'score'                => 0,
			'scores'               => [],
		];
	}
}
