import fs from 'node:fs/promises';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const siteDir = path.resolve(scriptDir, '..');
const docsDir = path.resolve(siteDir, '../docs');
const pages = await Promise.all(['introduction.md', 'product-tour.md'].map(async (file) => ({
  slug: file === 'introduction.md' ? 'about' : 'tour',
  content: await fs.readFile(path.join(docsDir, file), 'utf8'),
})));

await fs.mkdir(path.join(siteDir, 'src'), { recursive: true });
await fs.writeFile(path.join(siteDir, 'src/content.json'), `${JSON.stringify(pages, null, 2)}\n`);
await fs.rm(path.join(siteDir, 'public/docs/images'), { recursive: true, force: true });
await fs.mkdir(path.join(siteDir, 'public/docs'), { recursive: true });
await fs.cp(path.join(docsDir, 'images'), path.join(siteDir, 'public/docs/images'), { recursive: true });
