# Engine field support

Outstand SEO exposes a single set of **canonical fields** in the editor. Each
engine adapter maps those to its own native post meta (see each engine's
`get_field_map()` in `includes/Engines/`). A field's control renders only when
the active engine supports it — `field.supported` gating — so engine-specific
fields simply don't appear on the other engine, and no data is lost or faked.

Legend: **codec** is the transformer class in `includes/Engines/Codec/` the
engine injects for the field; **kind** (from the codec) is the canonical value
type surfaced to the editor.

## Shared fields (both engines)

Full parity — carried across an engine switch (the values live in different
native keys, but the sidebar edits the same canonical field).

| Canonical | Kind | TSF native key | Yoast native key |
| --- | --- | --- | --- |
| `title` | string | `_genesis_title` | `_yoast_wpseo_title` |
| `description` | string | `_genesis_description` | `_yoast_wpseo_metadesc` |
| `canonical` | string | `_genesis_canonical_uri` | `_yoast_wpseo_canonical` |
| `focusKw` | string | `_outstand_seo_focus_kw` (or Focus ext. blob — see below) | `_yoast_wpseo_focuskw` |
| `noindex` | robotsTri | `_genesis_noindex` (`TriState`) | `_yoast_wpseo_meta-robots-noindex` (`TriState`) |
| `nofollow` | robotsTri | `_genesis_nofollow` (`TriState`) | `_yoast_wpseo_meta-robots-nofollow` (`TwoStateTriState`) |
| `noarchive` | robotsTri | `_genesis_noarchive` (`TriState`) | `_yoast_wpseo_meta-robots-adv` token `noarchive` (`CsvTriState`) |
| `redirect` | string | `redirect` | `_yoast_wpseo_redirect` (Premium reads it) |
| `ogTitle` | string | `_open_graph_title` | `_yoast_wpseo_opengraph-title` |
| `ogDescription` | string | `_open_graph_description` | `_yoast_wpseo_opengraph-description` |
| `ogImageUrl` | string | `_social_image_url` | `_yoast_wpseo_opengraph-image` |
| `ogImageId` | int | `_social_image_id` | `_yoast_wpseo_opengraph-image-id` |
| `twitterTitle` | string | `_twitter_title` | `_yoast_wpseo_twitter-title` |
| `twitterDescription` | string | `_twitter_description` | `_yoast_wpseo_twitter-description` |
| `primaryTerms` (per hierarchical taxonomy) | int map | `_primary_term_%s` | `_yoast_wpseo_primary_%s` |

**Robots tri-state** is canonicalized to `'default' | 'on' | 'off'` (permissive /
restrictive). TSF stores `-1 / 0 / 1`; Yoast uses per-directive keys and the
comma-separated `…-robots-adv` value. `noarchive`, `noimageindex`, and `nosnippet`
all share Yoast's `_yoast_wpseo_meta-robots-adv` key; writes merge tokens so one
directive never clobbers another.

## TSF-only fields

No per-post Yoast equivalent, so hidden on Yoast.

| Canonical | Kind | TSF native key | Why TSF-only |
| --- | --- | --- | --- |
| `titleNoBlogname` | bool | `_tsf_title_no_blogname` | Yoast controls the site name through its title template, not a per-post flag |
| `excludeLocalSearch` | bool | `exclude_local_search` | TSF feature: hide from the site's search results |
| `excludeFromArchive` | bool | `exclude_from_archive` | TSF feature: hide from archive listings |
| `twitterCardType` | string | `_tsf_twitter_card_type` | Yoast exposes only a global default card type, no per-post meta |

## Yoast-only fields

No per-post TSF equivalent, so hidden on TSF.

| Canonical | Kind | Yoast native key | Why Yoast-only |
| --- | --- | --- | --- |
| `noimageindex` | bool | `_yoast_wpseo_meta-robots-adv` token `noimageindex` (`CsvFlag`) | TSF per-post robots covers only index/follow/archive |
| `nosnippet` | bool | `_yoast_wpseo_meta-robots-adv` token `nosnippet` (`CsvFlag`) | same |
| `cornerstone` | bool | `_yoast_wpseo_is_cornerstone` (`BooleanString`) | no TSF equivalent |
| `twitterImageUrl` | string | `_yoast_wpseo_twitter-image` | TSF reuses one social image (`ogImageUrl`) for OG + Twitter |
| `twitterImageId` | int | `_yoast_wpseo_twitter-image-id` | same |

## Focus keyphrase

TSF core has no focus keyword. The `focusKw` field:

- **Default:** stored in the plugin-owned scalar `_outstand_seo_focus_kw`
  (`AbstractEngine::DEFAULT_FOCUS_KW_KEY`), so it survives switching between
  keyword-less engines.
- **TSF "Focus" extension active** (`TSFEM_E_FOCUS_VERSION` defined): binds
  bidirectionally to the extension's shared `_tsfem-extension-post-meta` blob via
  the `TsfemFocus` codec — no migration. The codec preserves every other
  extension's data and the other keyword slots; when the keyword changes it
  clears that slot's derived analysis (inflections/synonyms/scores) so the
  extension recomputes cleanly. (Focus data is editor-only; frontend is
  unaffected.)

## Adding an engine

A new adapter (Rank Math, SEOPress, AIOSEO…) implements
`Outstand\WP\SEO\Engines\EngineInterface` — declare its `get_field_map()` (reuse
the shared codecs, add a new one only for a novel encoding) and register it in
`EngineManager::candidates()`. Cover the **shared** canonical fields for parity;
map only the engine-only fields the engine natively supports. No JS changes are
needed for engines that reuse existing field kinds.
