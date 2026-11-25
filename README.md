# aha! — marketing site

Static site for **aha! — Where I Put It**: the landing page (`/`), the privacy policy (`/privacy`) and `/support` (redirects to `/#support`).
Built with Astro, plain CSS and self-hosted fonts. No analytics, cookies, trackers or third-party requests. The privacy policy promises this, so keep it that way.

## Run

```sh
npm install
npm run dev       # http://localhost:4321/aha-site/
npm run build     # static output in dist/
npm run preview   # serve dist/ locally
npm run check     # type-check
```

Requires Node 22+.

## Where the site lives

`astro.config.mjs` reads two environment variables:

| Variable | Default | Use |
|---|---|---|
| `SITE_URL` | `https://alfayeedusman.github.io` | Origin for canonical URLs, Open Graph and the sitemap |
| `BASE_PATH` | `/aha-site` | Sub-path the site is served from (`/` for a custom domain) |

For a custom domain at the root: `SITE_URL=https://yourdomain.com BASE_PATH=/ npm run build`.

The build emits `privacy.html` and `support.html` (`build.format: 'file'`). The iOS app links to
`…/aha-site/privacy.html` and `…/aha-site/support.html`, so **keep these paths working**. `/privacy` also resolves on all the hosts below.

## Deploy

Every push to `main` runs `.github/workflows/deploy.yml`, which builds the site twice:

1. **GitHub Pages:** https://alfayeedusman.github.io/aha-site/. This is where the shipped iOS app links (`privacy.html`, `support.html`), so keep it running.
2. **The `hostinger` branch:** a ready-to-serve build for your domain root, including `.htaccess` for clean URLs (`/privacy`) and the 404 page.

### Hostinger (one-time setup)

Hostinger's Git deployment copies a branch into `public_html`. It doesn't run `npm`, so point it at the pre-built `hostinger` branch:

1. In GitHub: Settings → Secrets and variables → Actions → **Variables** → add `SITE_URL` = `https://yourdomain.com` (your real domain), then re-run the Deploy workflow.
2. In hPanel: **Websites → your site → Advanced → Git**.
   - Repository: `https://github.com/alfayeedusman/aha-site.git` (public, so no key is needed)
   - Branch: `hostinger`
   - Directory: leave empty (deploys into `public_html`, which must be empty first)
3. Click **Create**, then **Deploy**.
4. Turn on **Auto deployment** there, copy the webhook URL, and add it in GitHub under Settings → Webhooks (content type `application/json`, push events). Each push to `main` then updates the site.
5. In hPanel, turn on SSL for the domain and force HTTPS.

Once the domain is live, you can point the app's `privacyPolicyUrl` / `supportUrl` (`lib/app/app_config.dart`) at
`https://yourdomain.com/privacy` and `/support` in a later app release. Keep GitHub Pages running for older app versions.

### Other hosts

Netlify, Vercel and Cloudflare Pages can build it themselves: build `npm run build`, output `dist`, env `SITE_URL=https://yourdomain.com` and `BASE_PATH=/`.

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
