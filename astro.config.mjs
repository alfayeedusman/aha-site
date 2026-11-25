// @ts-check
import { defineConfig } from 'astro/config';
import sitemap from '@astrojs/sitemap';

// The public site: https://aha.yowtech.io (Hostinger), served from the domain root.
const site = process.env.SITE_URL ?? 'https://aha.yowtech.io';
const base = process.env.BASE_PATH ?? '/';

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
