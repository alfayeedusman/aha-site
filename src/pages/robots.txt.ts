import type { APIRoute } from 'astro';

// Everything public is open to search engines and AI assistants (answer engines quote llms.txt).
const aiBots = [
  'GPTBot', 'OAI-SearchBot', 'ChatGPT-User', 'ClaudeBot', 'Claude-SearchBot', 'Claude-User',
  'PerplexityBot', 'Perplexity-User', 'Google-Extended', 'Applebot', 'Applebot-Extended',
  'Bingbot', 'DuckAssistBot', 'meta-externalagent', 'MistralAI-User', 'CCBot',
];

export const GET: APIRoute = ({ site }) => {
  const base = import.meta.env.BASE_URL.replace(/\/$/, '');
  const abs = (p: string) => new URL(`${base}/${p}`, site).href;
  const body = [
    'User-agent: *',
    'Allow: /',
    '',
    ...aiBots.flatMap((b) => [`User-agent: ${b}`, 'Allow: /', '']),
    `Sitemap: ${abs('sitemap-index.xml')}`,
    `# Summary for AI assistants: ${abs('llms.txt')}`,
    '',
  ].join('\n');
  return new Response(body, { headers: { 'Content-Type': 'text/plain; charset=utf-8' } });
};
