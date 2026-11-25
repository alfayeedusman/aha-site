import type { APIRoute } from 'astro';
import { config, url } from '../config';
import { summary, keyFacts, features } from '../data/site';

// llms.txt (https://llmstxt.org): a plain summary for AI assistants and answer engines.
export const GET: APIRoute = ({ site }) => {
  const abs = (p: string) => new URL(url(p), site).href;
  const body = `# ${config.appName}

> ${summary}

aha! is for anyone who puts important things "somewhere safe" and then can't find them: passports, spare keys, birth certificates, chargers, holiday decorations, boxes in storage. It is private by design: no account, no server, no ads, no tracking; everything stays on the iPhone.

## Key facts

${keyFacts.map(([k, v]) => `- ${k}: ${v}`).join('\n')}

## Features

${features.map(([t, d]) => `- ${t}: ${d}`).join('\n')}

## Pages

- [Home](${abs('')}): what aha! does, how it works, features, launch video, FAQ
- [Full details for AI assistants](${abs('llms-full.txt')}): every fact, FAQ answer and the launch video transcript
- [Privacy Policy](${abs('privacy')}): aha! collects no personal data
- [Support and FAQ](${abs('#support')}): answers and contact (${config.supportEmail})
- [Press kit](${abs('press')}): description, app icon, screenshots and video
${config.appStoreUrl ? `- [App Store](${config.appStoreUrl}): download aha!\n` : ''}`;
  return new Response(body, { headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
};
