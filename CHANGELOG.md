# Changelog

All notable changes to this extension are documented here. The format
is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [1.1.5] - 2026-10-03

### Fixed
- A cached suggestion response now reports the casing of the current query, and its "view all results" link uses that query, instead of repeating the casing of the first query that filled the cache.
- Did-you-mean suggestions no longer offer a numeric word (for example 2024) back to itself.
- Typo matching counts edit distance in characters instead of bytes, so words with accented or non-Latin letters get the same typo tolerance as plain ASCII words.
