import { useEffect, useState } from 'react';
import ReactMarkdown from 'react-markdown';
import remarkGfm from 'remark-gfm';
import { ArrowRight, ArrowUpRight, Boxes, ClipboardList, Menu, PackageCheck, Search, ShieldCheck, ShoppingBasket, X } from 'lucide-react';
import { Link, Route, Routes, useLocation } from 'react-router-dom';
import pages from './content.json';

const intro = pages.find((page) => page.slug === 'about').content;
const tour = pages.find((page) => page.slug === 'tour').content;
const imageRoot = `${import.meta.env.BASE_URL}docs/images/`;
const repoUrl = 'https://github.com/LBDistrictScouts/DistrictBadges';

function Brand() {
  return <Link className="brand" to="/" aria-label="District Badges home"><span className="brand-mark"><ShieldCheck size={19} strokeWidth={2.1} /></span><span>district<span className="brand-light">badges</span></span></Link>;
}

function Header() {
  const [open, setOpen] = useState(false);
  const location = useLocation();
  useEffect(() => setOpen(false), [location.pathname]);

  return <header className="site-header"><div className="header-inner"><Brand /><button className="menu-button" aria-label={open ? 'Close menu' : 'Open menu'} aria-expanded={open} onClick={() => setOpen(!open)}>{open ? <X /> : <Menu />}</button><nav className={open ? 'main-nav is-open' : 'main-nav'} aria-label="Main navigation"><div className="nav-links"><Link to="/" className={location.pathname === '/' ? 'nav-link active' : 'nav-link'}>Overview</Link><Link to="/tour" className={location.pathname === '/tour' ? 'nav-link active' : 'nav-link'}>Product tour</Link><Link to="/about" className={location.pathname === '/about' ? 'nav-link active' : 'nav-link'}>Introduction</Link></div><a className="nav-cta" href={repoUrl} target="_blank" rel="noreferrer">Explore the project <ArrowUpRight size={15} /></a></nav></div></header>;
}

function Footer() {
  return <footer className="site-footer"><div className="footer-inner"><Brand /><p>More time for Scouting.</p><a href={repoUrl} target="_blank" rel="noreferrer">Open source on GitHub <ArrowUpRight size={14} /></a></div></footer>;
}

function Markdown({ children }) {
  return <ReactMarkdown remarkPlugins={[remarkGfm]} components={{
    img: ({ src, alt, ...props }) => <img src={src?.startsWith('http') ? src : `${imageRoot}${src?.replace(/^\.\.\//, '').replace(/^images\//, '')}`} alt={alt || ''} loading="lazy" {...props} />,
    a: ({ href = '', children: linkChildren, ...props }) => {
      if (href === 'product-tour.md') return <Link to="/tour">{linkChildren}</Link>;
      if (href === 'introduction.md') return <Link to="/about">{linkChildren}</Link>;
      const githubPath = href === '../README.md' ? `${repoUrl}/blob/main/README.md` : href.endsWith('.md') ? `${repoUrl}/blob/main/docs/${href.replace(/^\.\//, '')}` : href;
      return <a href={githubPath} {...(githubPath.startsWith('http') ? { target: '_blank', rel: 'noreferrer' } : {})} {...props}>{linkChildren}</a>;
    },
  }}>{children}</ReactMarkdown>;
}

function HomePage() {
  return <main>
    <section className="hero section-wrap"><div className="hero-copy"><div className="eyebrow"><span className="eyebrow-dot" />A DISTRICT RUN BADGE SHOP</div><h1>More time<br />for <em>Scouting.</em></h1><p className="hero-description">A welcoming badge shop for your groups. A practical workspace for your district team.</p><p className="hero-detail">Every badge marks something a young person has learned, tried or achieved. District Badges helps the volunteers behind those moments organise ordering, stock and paperwork in one place.</p><div className="hero-actions"><Link className="button button-dark" to="/tour">Take the product tour <ArrowRight size={16} /></Link><a className="text-link" href={repoUrl} target="_blank" rel="noreferrer">Explore the project <ArrowUpRight size={15} /></a></div><div className="hero-proof"><span className="proof-icon"><ShoppingBasket size={15} /></span><span>One connected journey, from catalogue to invoice</span></div></div><div className="hero-art"><img src={`${imageRoot}district-badges-hero.svg`} alt="District Badges, a local badge shop for your district" /><div className="hero-art-caption"><span><i /> For groups and district volunteers</span><span>BUILT FOR THE WORK BEHIND THE BADGES</span></div></div></section>
    <section className="intro-band"><div className="section-wrap intro-inner"><span className="eyebrow">A clearer way to run the shop</span><div className="intro-prose"><p>Groups get a clear ordering journey. Shop volunteers get one shared place to manage the work behind it.</p></div><span className="intro-mark">✳</span></div></section>
    <section className="features section-wrap"><div className="section-heading"><div><div className="eyebrow"><span className="eyebrow-dot" />MADE FOR DISTRICT BADGE SHOPS</div><h2>From a badge search<br /><span>to the stock cupboard.</span></h2></div><p>Bring the group-facing shop and the district team’s day-to-day operations together, with the information each volunteer needs close at hand.</p></div><div className="feature-grid"><article className="feature-card"><div className="feature-icon feature-icon-1"><Search size={20} /></div><span className="feature-number">01</span><h3>A straightforward shop for groups</h3><p>Find badges in a visual catalogue, filter by section or type, choose quantities and place an order without payment at checkout.</p><img className="feature-image" src={`${imageRoot}screenshots/02-catalogue.jpg`} alt="Badge catalogue with section and badge-type filters" loading="lazy" /><Link className="feature-link" to="/tour">See the ordering journey <ArrowRight size={14} /></Link></article><article className="feature-card"><div className="feature-icon feature-icon-2"><Boxes size={20} /></div><span className="feature-number">02</span><h3>A shared view of district stock</h3><p>Follow quantities on hand, pending, reserved, received, fulfilled and invoiced, then trace changes in the stock ledger.</p><img className="feature-image" src={`${imageRoot}screenshots/09-stock-overview.jpg`} alt="District stock overview showing quantities for each badge" loading="lazy" /><Link className="feature-link" to="/tour">Explore stock tools <ArrowRight size={14} /></Link></article><article className="feature-card"><div className="feature-icon feature-icon-3"><ClipboardList size={20} /></div><span className="feature-number">03</span><h3>Operations connected to orders</h3><p>Manage replenishments, prepare fulfilments, compare stock counts and invoice groups for dispatched badges.</p><img className="feature-image" src={`${imageRoot}screenshots/15-invoices.jpg`} alt="District invoice list for group accounts" loading="lazy" /><Link className="feature-link" to="/tour">See the back office <ArrowRight size={14} /></Link></article></div></section>
    <section className="workflow-band"><div className="section-wrap workflow-inner"><div className="workflow-copy"><div className="eyebrow"><span className="eyebrow-dot" />ONE CONNECTED WORKFLOW</div><h2>Good ordering starts with a clear picture.</h2><p>Groups place orders through the webstore. District volunteers manage fulfilment, stock and billing through the back office. The system keeps the details together, so volunteers can focus on helping young people get their badges.</p><Link className="text-link" to="/tour">Walk through all 20 screens <ArrowRight size={15} /></Link></div><img className="workflow-image" src={`${imageRoot}district-workflow.svg`} alt="Workflow from a Scout group placing a badge order to the district team fulfilling it" loading="lazy" /></div></section>
    <section className="capabilities section-wrap"><div className="capability-heading"><div className="eyebrow">A practical toolkit</div><h2>Built around how a district shop works.</h2></div><div className="capability-list"><div><PackageCheck /><span><strong>Orders and fulfilment</strong><small>Keep group orders and dispatch work moving together.</small></span></div><div><Boxes /><span><strong>Stock and replenishment</strong><small>Record received quantities and follow each stock movement.</small></span></div><div><ClipboardList /><span><strong>Counts and invoicing</strong><small>Compare shelf counts and prepare invoices for dispatched badges.</small></span></div></div></section>
    <section className="quote-band"><div className="quote-inner section-wrap"><span className="quote-mark">✳</span><div><div className="eyebrow">FOR THE PEOPLE BEHIND THE BADGES</div><blockquote>Give your district a badge shop that connects the catalogue, the stock cupboard and the paperwork.</blockquote></div><Link className="quote-link" to="/about">Read the introduction <ArrowRight size={15} /></Link></div></section>
    <section className="closing section-wrap"><div className="closing-stamp"><ShieldCheck size={27} /></div><div><div className="eyebrow">SEE HOW IT FITS TOGETHER</div><h2>Take a closer look<br />at District <em>Badges.</em></h2></div><Link className="button button-light" to="/tour">Explore the product tour <ArrowRight size={16} /></Link></section>
  </main>;
}

function ArticlePage({ content, label }) {
  return <main className="article-wrap"><Link className="back-link" to="/"><ArrowRight size={15} className="back-arrow" /> Back to overview</Link><article className="article-content"><div className="eyebrow"><span className="eyebrow-dot" />{label}</div><div className="markdown-body"><Markdown>{content}</Markdown></div><div className="article-next"><span>KEEP EXPLORING</span><Link to="/">Overview <ArrowRight size={15} /></Link>{label !== 'PRODUCT TOUR' && <Link to="/tour">Product tour <ArrowRight size={15} /></Link>}</div></article></main>;
}

export default function App() {
  const location = useLocation();
  useEffect(() => { window.scrollTo(0, 0); }, [location.pathname]);
  return <div className="app-shell"><Header /><Routes><Route path="/" element={<HomePage />} /><Route path="/tour" element={<ArticlePage content={tour} label="PRODUCT TOUR" />} /><Route path="/about" element={<ArticlePage content={intro} label="INTRODUCTION" />} /><Route path="*" element={<HomePage />} /></Routes><Footer /></div>;
}
