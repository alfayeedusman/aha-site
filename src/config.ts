// Single place for every value that changes per deployment.
// Anything still in [brackets] or on yourdomain.com is a placeholder to replace.

export const config = {
  appName: 'aha! - Where I Put It',
  /** Full App Store link, e.g. https://apps.apple.com/app/id1234567890 (empty until the app is live). */
  appStoreUrl: 'https://apps.apple.com/app/id6839034222',
  /** Numeric App Store id for the Smart App Banner (empty until the app is live). */
  appStoreId: '6839034222',
  minIos: '16',
  /** Real App Store reviews only, copied with the reviewer's display name. The section stays hidden while empty. */
  reviews: [] as { quote: string; author: string; stars: number }[],
  /** App Store average rating, e.g. { value: 4.8, count: 120 }. null hides it. */
  rating: null as null | { value: number; count: number },
  /**
   * Our own cookie-free counter (public/api/hit.php → ~/aha-analytics on Hostinger),
   * dashboard at /admin. Only counts on the production host. false = no script at all.
   */
  analytics: true,
  companyName: 'Yowtech IT Solutions',
  companyUrl: 'https://yowtech.io',
  companyLegalName: 'Yowtech IT Solutions',
  address: 'Gusa, Cagayan de Oro City, 9000 Misamis Oriental, Philippines',
  supportEmail: 'support@yowtech.io',
  privacyEmail: 'privacy@yowtech.io',
  helloEmail: 'support@yowtech.io',
  policyEffective: 'November 25, 2026',
  year: 2026,
} as const;

/** Prefix a site-relative path with the configured base path. */
export function url(path = ''): string {
  const base = import.meta.env.BASE_URL.replace(/\/$/, '');
  return `${base}/${path.replace(/^\//, '')}`;
}

/** App Store link, or the on-page download block while the app isn't live yet. */
export const appStoreHref = config.appStoreUrl || url('#download');
