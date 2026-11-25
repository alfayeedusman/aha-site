import type { APIRoute } from 'astro';
import { config, url } from '../config';
import { longDescription, keyFacts, features, faqs, video } from '../data/site';

export const GET: APIRoute = ({ site }) => {
  const abs = (p: string) => new URL(url(p), site).href;
  const body = `# ${config.appName}: full details

${longDescription.join('\n\n')}

Website: ${abs('')}
${config.appStoreUrl ? `App Store: ${config.appStoreUrl}\n` : ''}Privacy Policy: ${abs('privacy')}
Press kit: ${abs('press')}

## Key facts

${keyFacts.map(([k, v]) => `- ${k}: ${v}`).join('\n')}

## How it works

1. Remember: snap a photo and tap to pin the exact spot, say a sentence ("the spare key is in the junk drawer") or type it. aha! suggests the item and place; you confirm before saving.
2. Ask: type or say "where's my passport?". Typos and nicknames work. aha! answers with the photo and the pin, and never invents a location; if nothing matches, it offers to remember it.
3. Find: tap Found it!, or update the spot if you moved it. aha! keeps the history.

## Features

${features.map(([t, d]) => `- ${t}: ${d}`).join('\n')}

## Frequently asked questions

${faqs.map(([q, a]) => `### ${q}\n\n${a}`).join('\n\n')}

## Launch video transcript (${abs(video.path)})

${video.transcript.join('\n')}
`;
  return new Response(body, { headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
};
