# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-07-02

### Changed

- Moved the primary-term selector out of the SEO sidebar and into the core
  taxonomy panel (Categories, etc.), matching where Yoast and The SEO Framework
  place theirs, via the `editor.PostTaxonomyType` filter.
- The primary-term control now prefills the engine's resolved primary term (for
  The SEO Framework, its own fallback resolver) instead of showing an empty
  selection, and no longer offers a "none" option — one term is always primary
  once two or more are assigned, mirroring Yoast and TSF.

### Fixed

- Primary-term label now uses the taxonomy singular name (e.g. "Primary
  Category" instead of "Primary Categories").

## [1.0.0] - 2026-07-01

### Added

- Engine-agnostic block-editor SEO sidebar that drives the active SEO engine
  (The SEO Framework or Yoast SEO) in the background.
- Engine adapter layer (`includes/Engines/`) that disables each engine's native
  editor UI and maps canonical fields to the engine's native meta.
- Engine-aware breadcrumb block.
- Lightweight on-page content analysis (focus keyphrase + checks).
