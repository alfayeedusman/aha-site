// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

// Where the site is served. Defaults to the GitHub Pages project site that the
// iOS app already links to (privacy.html / support.html). For a custom domain
// at the root, build with SITE_URL=https://yourdomain.com BASE_PATH=/
const site = process.env.SITE_URL ?? 'https://alfayeedusman.github.io';
const base = process.env.BASE_PATH ?? '/aha-site';

export default defineConfig({
  site,
  base,
  output: 'static',
  // "file" emits privacy.html / support.html, so the URLs baked into the
  // shipped app keep working, and /privacy still resolves on every static host.
  build: { format: 'file', inlineStylesheets: 'always' },
  trailingSlash: 'ignore',
  integrations: [
    sitemap({
      filter: (page) => !/\/(support|404)(\.html)?$/.test(page),
    }),
  ],
});
