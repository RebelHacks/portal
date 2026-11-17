/*
 * The shell around every page: the skin, the ribbon frame, the account menu and the sidebar groups.
 * Pages are ordinary server-rendered routes, so there's no page switching here.
 */
const root = document.documentElement;
const params = new URLSearchParams(location.search);
const remember = (key, value) => { try { localStorage.setItem(key, value); } catch {} };
const recall = (key) => { try { return localStorage.getItem(key); } catch { return null; } };

// ---------- skin: ?skin= wins, then the saved choice, then the device setting ----------
const dark = matchMedia('(prefers-color-scheme: dark)');
let skinChoice = params.get('skin') || recall('skin') || 'auto';
function applySkin() {
  root.dataset.skin = skinChoice === 'auto' ? (dark.matches ? 'neon' : 'day') : skinChoice;
  for (const item of document.querySelectorAll('[data-skin-choice]')) {
    item.setAttribute('aria-checked', String(item.dataset.skinChoice === skinChoice));
  }
}
applySkin();
dark.addEventListener('change', () => { if (skinChoice === 'auto') { applySkin(); replay(); } });

// ---------- ribbons: three stripes around the frame, drawn from the CSS variables ----------
const svg = document.querySelector('.ribbons');
const group = svg?.querySelector('g');
const field = document.querySelector('.hexfield');
const NS = 'http://www.w3.org/2000/svg';
const px = (name) => parseFloat(getComputedStyle(root).getPropertyValue(name));
const phone = () => matchMedia('(max-width: 760px)').matches;

// Each stripe is the same bent shape, shifted toward the inside by its offset.
// Both halves start at the bend beside the logo, so the ribbon flows out from there.
function desktopPaths(o, W, H) {
  const x = px('--xV') + o, y = px('--yT') + o;
  const xr = W - px('--right') - o, yb = H - px('--bottom') + o;
  const r1 = px('--R') - o, r2 = px('--R2') - o, r3 = px('--Rb') + o;
  return [
    `M ${x} ${y + r1} V ${yb - r3} A ${r3} ${r3} 0 0 1 ${x - r3} ${yb} H -20`,
    `M ${x} ${y + r1} A ${r1} ${r1} 0 0 1 ${x + r1} ${y} H ${xr - r2} A ${r2} ${r2} 0 0 1 ${xr} ${y + r2} V ${H + 20}`,
  ];
}
function phonePaths(o, W, H) {
  const y = px('--yT') + o;
  const yb = H - px('--yB') - o;
  return [`M 60 ${y} H ${W + 20}`, `M -20 ${yb} H ${W + 20}`];
}
// The same shape, moved past the inner stripe and closed along the window's bottom: the honeycomb's clip.
function fieldPath(W, H) {
  const edge = px('--gap') + px('--stripe') / 2;
  if (phone()) {
    return `M 0 ${px('--yT') + edge + 8} H ${W} V ${H - px('--yB') - edge - 2} H 0 Z`;
  }
  const e = edge + px('--inset');
  const x = px('--xV') + e, y = px('--yT') + e;
  const xr = W - px('--right') - e, yb = H - px('--bottom') + e;
  const r1 = px('--R') - e, r2 = px('--R2') - e, r3 = px('--Rb') + e;
  return `M 0 ${yb} H ${x - r3} A ${r3} ${r3} 0 0 0 ${x} ${yb - r3} V ${y + r1} A ${r1} ${r1} 0 0 1 ${x + r1} ${y} H ${xr - r2} A ${r2} ${r2} 0 0 1 ${xr} ${y + r2} V ${H} H 0 Z`;
}
function draw() {
  if (!svg) return;
  const W = innerWidth, H = innerHeight, gap = px('--gap');
  field.style.clipPath = `path('${fieldPath(W, H)}')`;
  group.replaceChildren();
  [['red', -gap], ['gold', 0], ['blue', gap]].forEach(([color, o]) => {
    for (const d of phone() ? phonePaths(o, W, H) : desktopPaths(o, W, H)) {
      const p = document.createElementNS(NS, 'path');
      p.setAttribute('d', d);
      p.setAttribute('pathLength', '1');
      p.setAttribute('class', color);
      group.append(p);
    }
  });
}
function replay() {
  if (!svg) return;
  draw();
  svg.classList.remove('enter');
  void svg.getBoundingClientRect();
  svg.classList.add('enter');
}
replay();
addEventListener('resize', draw);

// ---------- account menu: arrow keys move, Escape closes ----------
const accountButton = document.querySelector('.account-button');
const menu = document.getElementById('account-menu');
if (accountButton && menu) {
  const menuItems = () => [...menu.querySelectorAll('.menu-item')];
  const openMenu = (focusFirst) => {
    menu.hidden = false;
    accountButton.setAttribute('aria-expanded', 'true');
    if (focusFirst) menuItems()[0].focus();
  };
  const closeMenu = (refocus) => {
    menu.hidden = true;
    accountButton.setAttribute('aria-expanded', 'false');
    if (refocus) accountButton.focus();
  };
  accountButton.addEventListener('click', () => (menu.hidden ? openMenu(false) : closeMenu(false)));
  accountButton.addEventListener('keydown', (e) => {
    if (e.key === 'ArrowDown') { e.preventDefault(); openMenu(true); }
  });
  menu.addEventListener('keydown', (e) => {
    const list = menuItems(), i = list.indexOf(document.activeElement);
    if (e.key === 'ArrowDown') { e.preventDefault(); list[(i + 1) % list.length].focus(); }
    if (e.key === 'ArrowUp') { e.preventDefault(); list[(i - 1 + list.length) % list.length].focus(); }
    if (e.key === 'Escape') closeMenu(true);
    if (e.key === 'Tab') closeMenu(false);
  });
  document.addEventListener('pointerdown', (e) => {
    if (!menu.hidden && !e.target.closest('.account')) closeMenu(false);
  });
  for (const item of document.querySelectorAll('[data-skin-choice]')) {
    item.addEventListener('click', () => {
      skinChoice = item.dataset.skinChoice;
      remember('skin', skinChoice);
      applySkin();
      closeMenu(true);
      replay();
    });
  }
}

// ---------- sidebar groups: open on wide screens, the active one always, the caret flips any ----------
const wide = matchMedia('(min-width: 1200px)').matches;
for (const g of document.querySelectorAll('.nav-group[data-group]')) {
  const active = g.querySelector('[aria-current]') !== null;
  if (wide || active) g.dataset.open = '';
  g.querySelector('.nav-toggle')?.setAttribute('aria-expanded', String('open' in g.dataset));
}
for (const t of document.querySelectorAll('.nav-toggle')) {
  t.addEventListener('click', (event) => {
    event.preventDefault(); event.stopPropagation();
    const g = t.closest('.nav-group');
    if ('open' in g.dataset) delete g.dataset.open; else g.dataset.open = '';
    t.setAttribute('aria-expanded', String('open' in g.dataset));
  });
}
