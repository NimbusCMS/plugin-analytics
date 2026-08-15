# NimbusCMS Analytics

Privacy-first analytics for [NimbusCMS](https://github.com/NimbusCMS/nimbus) — an
**official plugin**, and the one that grew the plugin system from a single
capability into a real application platform.

It does two independent things; use either or both:

## 1. First-party analytics

Every public page view is recorded server-side into a table this plugin owns —
**no cookies, no third-party requests, no personal data**. Just a path, the
referring host, and a timestamp. An **Analytics** page appears in the admin with:

- total pageviews over the last 30 days,
- a per-day chart (server-rendered SVG — no JavaScript),
- your top pages and top referrers.

Bots and your own internal navigation are filtered out. Nothing here slows a
page down for visitors: recording happens *after* the response is sent, and can
never break it.

## 2. Third-party agent

Prefer a hosted analytics service? Set two environment variables and this plugin
injects its snippet into every public page's `<head>`:

```bash
ANALYTICS_PROVIDER=plausible  ANALYTICS_DOMAIN=example.com          # cookieless
ANALYTICS_PROVIDER=fathom     ANALYTICS_SITE_ID=ABCDEF              # cookieless
ANALYTICS_PROVIDER=ga         ANALYTICS_MEASUREMENT_ID=G-XXXXXXX    # Google Analytics
```

## Install

```bash
composer require nimbuscms/analytics
php bin/nimbus migrate      # creates the analytics table
```

Discovery is automatic. Disable it without uninstalling in `config/plugins.php`:

```php
return ['nimbuscms.analytics' => false];
```

## What it demonstrates

This plugin exercises **six** NimbusCMS plugin capabilities at once — migrations,
storage, events, admin pages, and (for the agent) head contributions — using
only the public plugin contract. It is the reference for building an
"observe → store → show an admin view" plugin.

## Requirements

- PHP 8.2+, ext-json
- NimbusCMS (the events, migrations, storage and admin-page capabilities)

## License

MIT.
