# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project aims
to follow [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- First-party analytics: a `request.handled` listener records public page views
  (path, referrer host, timestamp — no cookies or PII, bots filtered) into a
  table the plugin owns, and an admin dashboard charts them with a server-rendered
  SVG plus top pages and referrers.
- Third-party agent injection: a head contributor emits the snippet for a
  configured provider (Plausible, Fathom, or Google Analytics) from environment
  variables.
- Retention: a `nimbus prune` maintenance task drops page views older than
  `ANALYTICS_RETENTION_DAYS` (default 90; `0` keeps everything).
