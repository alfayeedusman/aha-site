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

Every push to `main` runs `.github/workflows/deploy.yml`:

1. **Hostinger:** builds the site and uploads `dist/` to aha.yowtech.io over FTP. The same build is also pushed to the `hostinger` branch.
2. **GitHub Pages (redirects only):** iOS app 1.0.0 links to `alfayeedusman.github.io/aha-site/privacy.html` and `support.html`. GitHub Pages now serves only tiny redirect pages that send those links (and the old home page) to aha.yowtech.io. Keep it on until no app version in use links there. New app builds should use `https://aha.yowtech.io/privacy` and `https://aha.yowtech.io/support` (`lib/app/app_config.dart`).

### Hostinger FTP setup (one time)

1. In hPanel: **Websites → aha.yowtech.io → Files → FTP Accounts**. Create an FTP account (or use the main one) and note the host, username and password.
2. In GitHub: aha-site → Settings → Secrets and variables → Actions:
   - **Secrets:** `FTP_SERVER` (e.g. `ftp.yowtech.io` or the IP hPanel shows), `FTP_USERNAME`, `FTP_PASSWORD`
   - **Variable** `FTP_DIR`: the site's folder as seen by that FTP account, ending in `/`. For an account limited to the site's own folder, leave it unset. For the main account, use something like `domains/aha.yowtech.io/public_html/`.
3. Run the **Deploy** workflow (Actions → Deploy → Run workflow).
4. Delete Hostinger's default "Coming soon" `index.php` from the site folder. (`.htaccess` already prefers `index.html`.)

**Without FTP:** upload the contents of a `dist/` build (or `../aha-yowtech-io-site.zip`) with hPanel's File Manager into the site's `public_html`, and extract it there. Or use hPanel → Advanced → **Git** with this repo and branch `hostinger`.

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
