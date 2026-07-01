# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.0] - 2026-07-01

### Added

- Engine-agnostic block-editor SEO sidebar that drives the active SEO engine
  (The SEO Framework or Yoast SEO) in the background.
- Engine adapter layer (`includes/Engines/`) that disables each engine's native
  editor UI and maps canonical fields to the engine's native meta.
- Engine-aware breadcrumb block.
- Lightweight on-page content analysis (focus keyphrase + checks).
