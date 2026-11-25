// Single place for every value that changes per deployment.
// Anything still in [brackets] or on yourdomain.com is a placeholder to replace.

export const config = {
  appName: 'aha! - Where I Put It',
  /** Full App Store link, e.g. https://apps.apple.com/app/id1234567890 (empty until the app is live). */
  appStoreUrl: '',
  /** Numeric App Store id for the Smart App Banner (empty until the app is live). */
  appStoreId: '',
  companyName: 'Yowtech IT Solutions',
  companyUrl: 'https://yowtech.io',
  companyLegalName: '[Your company legal name]',
  address: '[address]',
  supportEmail: 'support@yourdomain.com',
  privacyEmail: 'privacy@yourdomain.com',
  helloEmail: 'hello@yourdomain.com',
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
