# aha! - marketing site

Static site for **aha! - Where I Put It**: the landing page (`/`), the privacy policy (`/privacy`) and `/support` (redirects to `/#support`).
Built with Astro, plain CSS and self-hosted fonts. No analytics, cookies, trackers or third-party requests. The privacy policy promises this, so keep it that way.

## Run

```sh
npm install
npm run dev       # http://localhost:4321/
npm run build     # static output in dist/
npm run preview   # serve dist/ locally
npm run check     # type-check
```

Requires Node 22+.

## Where the site lives

The public website is **https://aha.yowtech.io** on Hostinger, served from the domain root. GitHub only stores the code. It does not host the site.

`astro.config.mjs` reads two optional environment variables:

| Variable | Default | Use |
|---|---|---|
| `SITE_URL` | `https://aha.yowtech.io` | Origin for canonical URLs, Open Graph and the sitemap |
| `BASE_PATH` | `/` | Sub-path the site is served from |

The build emits `privacy.html` and `support.html` (`build.format: 'file'`). The `.htaccess` file makes `/privacy` and `/support` work without `.html`.

## Deploy

```sh
./deploy/deploy.sh             # build → upload over SSH → switch aha.yowtech.io → check live URLs
./deploy/deploy.sh --rollback  # go back to the previous release
```

How it works: each deploy is uploaded to `~/releases/aha-site-<date>-<commit>/` on the Hostinger account (SSH port 65002, key `~/.ssh/yowtobooth_hostinger`). Then `~/domains/yowtech.io/public_html/aha`, the aha.yowtech.io document root, is pointed at it with a symlink. Your other subdomains use the same pattern. The last 5 releases are kept. The SSH host, user, port and key can be overridden with `AHA_SSH_HOST`, `AHA_SSH_USER`, `AHA_SSH_PORT` and `AHA_SSH_KEY`.

Hostinger's CDN returns 403 to headless browsers (e.g. `HeadlessChrome`). Real browsers and curl get through. Use a normal user agent for automated checks.

### GitHub Pages (redirects only)

iOS app 1.0.0 links to `alfayeedusman.github.io/aha-site/privacy.html` and `support.html`. On every push to `main`, `.github/workflows/deploy.yml` publishes tiny redirect pages there that send visitors to aha.yowtech.io. Keep it running until no app version in use links there. New app builds should use `https://aha.yowtech.io/privacy` and `https://aha.yowtech.io/support` (`lib/app/app_config.dart`).

## Editing content

- **Company, emails, App Store link and id, policy effective date:** `src/config.ts`. Values still in `[brackets]` or on `yourdomain.com` are placeholders.
- **Privacy policy text:** `src/content/privacy.md` (plain Markdown). `%%companyLegalName%%`, `%%address%%` and `%%privacyEmail%%` are filled from the config. When the policy changes, update `policyEffective` and keep the in-app policy (`lib/features/settings/privacy_policy_text.dart` in the app repo) saying the same thing.
- **Landing page copy:** the components in `src/components/` (one per section).
- **Screenshots:** replace the files in `src/assets/screens/` (same names). Astro produces AVIF/WebP at build time.
- **Social image:** `public/og.png` (1200×630).

## App Store badge (TODO)

`src/assets/badges/app-store-badge-PLACEHOLDER.svg` is a stand-in. Download Apple's official black "Download on the App Store" badge from
https://developer.apple.com/app-store/marketing/guidelines/, save it as `src/assets/badges/app-store-badge.svg`, and change the import in
`src/components/AppStoreBadge.astro`. Don't redraw or modify it.

## Before launch

- [ ] Fill in `src/config.ts`: company name and legal name, address, support/privacy/hello emails, `appStoreUrl`, `appStoreId` (turns on the Smart App Banner)
- [ ] Replace the App Store badge placeholder
- [ ] Make sure the support and privacy inboxes exist
