// Scroll reveal island. html.reveal is set by an inline script in <head> only when
// IntersectionObserver exists and the user allows motion, so content is never hidden otherwise.
const root = document.documentElement;
if (root.classList.contains('reveal')) {
  const els = [...document.querySelectorAll<HTMLElement>('[data-reveal]')];
  for (const el of els) {
    const sibs = [...(el.parentElement?.children ?? [])].filter((c) => c.hasAttribute('data-reveal'));
    el.style.setProperty('--reveal-delay', `${Math.min(sibs.indexOf(el), 6) * 90}ms`);
  }
  const io = new IntersectionObserver(
    (entries) => {
      for (const e of entries) {
        if (e.isIntersecting) {
          e.target.classList.add('is-visible');
          io.unobserve(e.target);
        }
      }
    },
    { threshold: 0.12, rootMargin: '0px 0px -40px 0px' },
  );
  els.forEach((el) => io.observe(el));
}
