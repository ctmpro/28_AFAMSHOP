// Génère les images de contenu AFAMSHOP (illustrations vectorielles → JPG / PNG)
const { chromium } = require('playwright');
const path = require('path');
const OUT = process.argv[2];

const C = { primary: '#0b4f8a', secondary: '#0f2a44', accent: '#e8730c', light: '#eef3f9', cyan: '#0aa5d8', magenta: '#d6247a', yellow: '#f4c20d', black: '#2a2f38' };
const FONT = "'DejaVu Sans', Arial, Helvetica, sans-serif";

// ---------------------------------------------------------------- composants
const g = (x, y, s, inner, rot = 0) => `<g transform="translate(${x} ${y}) rotate(${rot}) scale(${s})">${inner}</g>`;
const shadow = (cx, cy, rx, ry, o = 0.18) => `<ellipse cx="${cx}" cy="${cy}" rx="${rx}" ry="${ry}" fill="#0b1b2d" opacity="${o}" filter="url(#blur8)"/>`;

// Multifonction (boîte locale 300 x 340)
function mfp(opts = {}) {
  const accent = opts.accent || C.accent;
  return `
  ${shadow(150, 336, 150, 14, 0.25)}
  <rect x="22" y="205" width="256" height="125" rx="10" fill="#e4eaf1" stroke="#cfd8e3"/>
  <rect x="232" y="205" width="46" height="125" rx="10" fill="#d5dde7"/>
  <rect x="38" y="232" width="180" height="4" rx="2" fill="#b8c4d2"/>
  <rect x="38" y="276" width="180" height="4" rx="2" fill="#b8c4d2"/>
  <rect x="100" y="244" width="56" height="8" rx="4" fill="#c3cdd9"/>
  <rect x="100" y="290" width="56" height="8" rx="4" fill="#c3cdd9"/>
  <rect x="30" y="328" width="22" height="8" rx="3" fill="#8a97a8"/><rect x="248" y="328" width="22" height="8" rx="3" fill="#8a97a8"/>
  <rect x="10" y="92" width="280" height="118" rx="12" fill="#f8fafc" stroke="#d3dce7"/>
  <rect x="246" y="92" width="44" height="118" rx="12" fill="#e6ecf3"/>
  <rect x="10" y="198" width="280" height="8" fill="${accent}"/>
  <rect x="36" y="124" width="176" height="16" rx="5" fill="#26364a"/>
  <path d="M52 128 L196 128 L204 150 L44 150 Z" fill="#ffffff" stroke="#dbe3ec"/>
  <rect x="62" y="134" width="90" height="3" rx="1.5" fill="#c9d3df"/><rect x="62" y="141" width="120" height="3" rx="1.5" fill="#dbe3ec"/>
  <rect x="20" y="52" width="252" height="44" rx="10" fill="#e1e8f0" stroke="#cdd7e2"/>
  <path d="M40 52 L60 26 L210 26 L222 52 Z" fill="#d3dce7"/>
  <path d="M70 30 L196 30 L204 46 L62 46 Z" fill="#ffffff"/>
  <rect x="80" y="35" width="70" height="3" rx="1.5" fill="#c9d3df"/>
  <g transform="translate(196 60) rotate(-8)">
    <rect width="96" height="54" rx="8" fill="#1b2838"/>
    <rect x="7" y="7" width="60" height="40" rx="4" fill="url(#screen)"/>
    <rect x="12" y="13" width="26" height="4" rx="2" fill="#ffffff" opacity=".85"/>
    <rect x="12" y="22" width="20" height="16" rx="3" fill="#ffffff" opacity=".35"/><rect x="36" y="22" width="20" height="16" rx="3" fill="#ffffff" opacity=".55"/>
    <circle cx="80" cy="18" r="5" fill="#2fd27a"/><circle cx="80" cy="36" r="6" fill="${accent}"/>
  </g>
  <circle cx="30" cy="114" r="4" fill="#2fd27a"/>`;
}

// Cartouche de toner verticale (60 x 230)
function toner(color, label = true) {
  return `
  ${shadow(30, 232, 34, 6, 0.2)}
  <rect x="4" y="18" width="52" height="210" rx="12" fill="url(#tonerBody)"/>
  <rect x="10" y="4" width="40" height="22" rx="8" fill="#1d222a"/>
  ${label ? `<rect x="10" y="64" width="40" height="110" rx="6" fill="#f4f6f9"/>
  <rect x="10" y="64" width="40" height="26" rx="6" fill="${color}"/>
  <rect x="16" y="102" width="28" height="4" rx="2" fill="#c3ccd8"/><rect x="16" y="112" width="20" height="4" rx="2" fill="#c3ccd8"/>
  <rect x="16" y="150" width="28" height="12" rx="2" fill="#26364a" opacity=".85"/>` : ''}
  <rect x="8" y="22" width="8" height="200" rx="4" fill="#ffffff" opacity=".12"/>`;
}

// Cartouche d'encre (80 x 100)
function ink(color) {
  return `
  ${shadow(40, 102, 40, 5, 0.18)}
  <rect x="4" y="10" width="72" height="88" rx="10" fill="#2e3440"/>
  <rect x="4" y="10" width="72" height="22" rx="10" fill="${color}"/>
  <rect x="4" y="24" width="72" height="8" fill="${color}"/>
  <rect x="14" y="44" width="52" height="40" rx="5" fill="#f4f6f9"/>
  <rect x="20" y="52" width="34" height="4" rx="2" fill="${color}"/><rect x="20" y="62" width="26" height="4" rx="2" fill="#c3ccd8"/>
  <rect x="30" y="2" width="20" height="10" rx="3" fill="#1d222a"/>`;
}

// Ordinateur portable (320 x 200)
function laptop() {
  return `
  ${shadow(160, 196, 165, 8, 0.2)}
  <rect x="38" y="0" width="244" height="164" rx="10" fill="#1c2532"/>
  <rect x="48" y="10" width="224" height="142" rx="4" fill="url(#screen2)"/>
  <rect x="48" y="10" width="224" height="18" fill="#ffffff" opacity=".12"/>
  <circle cx="58" cy="19" r="3" fill="#ff6b5b"/><circle cx="68" cy="19" r="3" fill="#ffc94a"/><circle cx="78" cy="19" r="3" fill="#2fd27a"/>
  <rect x="60" y="40" width="70" height="8" rx="4" fill="#ffffff" opacity=".9"/>
  <rect x="60" y="54" width="100" height="5" rx="2.5" fill="#ffffff" opacity=".45"/>
  <rect x="60" y="72" width="62" height="64" rx="6" fill="#ffffff" opacity=".18"/>
  <rect x="130" y="72" width="62" height="64" rx="6" fill="#ffffff" opacity=".28"/>
  <rect x="200" y="72" width="60" height="64" rx="6" fill="${C.accent}" opacity=".85"/>
  <path d="M70 124 L84 104 L96 114 L112 92" stroke="#fff" stroke-width="3" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
  <path d="M0 166 L320 166 L304 190 Q300 194 292 194 L28 194 Q20 194 16 190 Z" fill="#cfd8e3"/>
  <rect x="0" y="164" width="320" height="6" rx="3" fill="#e4eaf1"/>
  <rect x="132" y="166" width="56" height="6" rx="3" fill="#b4c0ce"/>`;
}

// Écran (220 x 200)
function monitor() {
  return `
  ${shadow(110, 198, 70, 6, 0.18)}
  <rect x="0" y="0" width="220" height="140" rx="8" fill="#1c2532"/>
  <rect x="8" y="8" width="204" height="118" rx="3" fill="url(#screen)"/>
  <rect x="20" y="22" width="90" height="8" rx="4" fill="#fff" opacity=".85"/>
  <rect x="20" y="40" width="180" height="70" rx="5" fill="#fff" opacity=".15"/>
  <path d="M30 98 L60 70 L90 84 L130 52 L180 66" stroke="#fff" stroke-width="3" fill="none" stroke-linecap="round"/>
  <rect x="96" y="140" width="28" height="44" fill="#9aa8b8"/>
  <rect x="60" y="182" width="100" height="10" rx="5" fill="#b4c0ce"/>`;
}

// Ramette de papier (200 x 100)
function ream(color = C.primary) {
  return `
  ${shadow(100, 100, 104, 6, 0.18)}
  <path d="M10 30 L40 6 L196 6 L170 30 Z" fill="#ffffff" stroke="#dde4ec"/>
  <rect x="10" y="30" width="160" height="66" fill="#f4f6f9" stroke="#dde4ec"/>
  <path d="M170 30 L196 6 L196 72 L170 96 Z" fill="#e3e9f0"/>
  <rect x="10" y="44" width="160" height="34" fill="${color}"/>
  <path d="M170 44 L196 20 L196 54 L170 78 Z" fill="${color}" opacity=".75"/>
  <text x="26" y="68" font-family="${FONT}" font-weight="700" font-size="20" fill="#fff">A4</text>
  <rect x="70" y="56" width="80" height="4" rx="2" fill="#fff" opacity=".7"/><rect x="70" y="64" width="56" height="4" rx="2" fill="#fff" opacity=".5"/>`;
}

// Feuille de document (120 x 160)
function sheet(kind = 'text', accent = C.primary) {
  let content = `<rect x="16" y="20" width="60" height="7" rx="3.5" fill="${accent}"/>`;
  for (let i = 0; i < 6; i++) content += `<rect x="16" y="${40 + i * 13}" width="${88 - (i % 3) * 14}" height="5" rx="2.5" fill="#d3dbe5"/>`;
  if (kind === 'chart') {
    content = `<rect x="16" y="20" width="60" height="7" rx="3.5" fill="${accent}"/>`
      + [30, 52, 40, 70].map((h, i) => `<rect x="${20 + i * 22}" y="${130 - h}" width="14" height="${h}" rx="3" fill="${i === 3 ? C.accent : accent}" opacity="${0.55 + i * 0.12}"/>`).join('')
      + `<rect x="16" y="136" width="88" height="3" fill="#d3dbe5"/>`;
  }
  if (kind === 'check') {
    content = `<rect x="16" y="20" width="60" height="7" rx="3.5" fill="${accent}"/>`;
    for (let i = 0; i < 5; i++) {
      content += `<rect x="16" y="${42 + i * 22}" width="12" height="12" rx="3" fill="${i < 3 ? '#2fb36b' : '#e3e9f0'}"/>`
        + (i < 3 ? `<path d="M19 ${48 + i * 22} l3 3 l5 -6" stroke="#fff" stroke-width="2" fill="none"/>` : '')
        + `<rect x="36" y="${45 + i * 22}" width="${66 - (i % 2) * 18}" height="5" rx="2.5" fill="#d3dbe5"/>`;
    }
  }
  return `${shadow(60, 162, 56, 5, 0.12)}<rect x="0" y="0" width="120" height="160" rx="8" fill="#ffffff" stroke="#e1e7ee"/>${content}`;
}

// Engrenage
function gear(r, color, teeth = 10) {
  let d = '';
  const outer = r, inner = r * 0.78;
  for (let i = 0; i < teeth * 2; i++) {
    const a = (Math.PI * 2 * i) / (teeth * 2);
    const a2 = (Math.PI * 2 * (i + 1)) / (teeth * 2);
    const rr = i % 2 === 0 ? outer : inner;
    d += `${i === 0 ? 'M' : 'L'}${(Math.cos(a) * rr).toFixed(1)} ${(Math.sin(a) * rr).toFixed(1)} L${(Math.cos(a2) * rr).toFixed(1)} ${(Math.sin(a2) * rr).toFixed(1)} `;
  }
  return `<path d="${d}Z" fill="${color}"/><circle r="${r * 0.36}" fill="#ffffff" opacity=".9"/>`;
}

// Clé plate (wrench) 200 x 60
function wrench(color = '#8fa0b4') {
  return `<path d="M30 10 a26 26 0 1 0 24 34 L190 44 a10 10 0 0 0 0 -20 L54 24 A26 26 0 0 0 30 10 Z" fill="${color}"/>
  <path d="M14 26 L30 20 L40 32 L28 44 Z" fill="#ffffff" opacity=".85"/>
  <rect x="70" y="30" width="110" height="4" rx="2" fill="#ffffff" opacity=".3"/>`;
}

// Clé (key) 200 x 80
function key(color = C.accent) {
  return `<circle cx="40" cy="40" r="34" fill="${color}"/><circle cx="40" cy="40" r="13" fill="#ffffff"/>
  <rect x="68" y="32" width="126" height="16" rx="5" fill="${color}"/>
  <rect x="150" y="46" width="12" height="20" rx="3" fill="${color}"/><rect x="172" y="46" width="12" height="28" rx="3" fill="${color}"/>
  <circle cx="26" cy="26" r="6" fill="#ffffff" opacity=".35"/>`;
}

// Calendrier (140 x 140)
function calendar() {
  let cells = '';
  for (let r = 0; r < 4; r++) for (let c = 0; c < 5; c++) {
    const hl = r === 2 && c === 3;
    cells += `<rect x="${16 + c * 23}" y="${50 + r * 20}" width="17" height="14" rx="3" fill="${hl ? C.accent : '#e3e9f0'}"/>`;
  }
  return `${shadow(70, 142, 64, 5, 0.12)}<rect x="0" y="0" width="140" height="140" rx="12" fill="#fff" stroke="#e1e7ee"/>
  <rect x="0" y="0" width="140" height="36" rx="12" fill="${C.primary}"/><rect x="0" y="24" width="140" height="12" fill="${C.primary}"/>
  <rect x="30" y="-8" width="10" height="22" rx="5" fill="#26364a"/><rect x="100" y="-8" width="10" height="22" rx="5" fill="#26364a"/>${cells}`;
}

// Plante de bureau (100 x 170)
function plant() {
  return `${shadow(50, 168, 40, 5, 0.15)}
  <path d="M50 110 C20 80 10 40 26 10 C40 40 52 70 50 110 Z" fill="#2f9e5b"/>
  <path d="M50 110 C80 84 92 50 82 18 C64 44 52 76 50 110 Z" fill="#3cb46b"/>
  <path d="M50 112 C34 96 6 92 0 70 C24 72 42 86 50 112 Z" fill="#268a4e"/>
  <path d="M50 112 C66 98 92 98 100 78 C76 78 58 90 50 112 Z" fill="#2f9e5b"/>
  <path d="M22 108 L78 108 L70 166 L30 166 Z" fill="${C.accent}"/><rect x="18" y="102" width="64" height="12" rx="4" fill="#f08a2c"/>`;
}

// Tasse (70 x 80)
function mug() {
  return `${shadow(34, 80, 34, 4, 0.15)}<rect x="0" y="12" width="56" height="66" rx="10" fill="#ffffff" stroke="#e1e7ee"/>
  <path d="M56 28 a16 16 0 0 1 0 32" stroke="#e1e7ee" stroke-width="8" fill="none"/>
  <rect x="0" y="34" width="56" height="12" fill="${C.primary}"/>
  <path d="M18 0 q6 6 0 12 M32 0 q6 6 0 12" stroke="#c3ccd8" stroke-width="3" fill="none"/>`;
}

// Feuille éco
function leaf(color = '#2fb36b') {
  return `<path d="M0 100 C0 40 40 0 110 0 C110 70 70 110 0 100 Z" fill="${color}"/><path d="M8 94 C40 64 70 34 100 10" stroke="#fff" stroke-width="5" fill="none" stroke-linecap="round" opacity=".85"/>`;
}

// Jauge d'économie
function gauge() {
  return `${shadow(80, 112, 70, 5, 0.12)}<rect x="0" y="0" width="160" height="110" rx="14" fill="#fff" stroke="#e1e7ee"/>
  <path d="M30 86 A50 50 0 0 1 130 86" stroke="#e3e9f0" stroke-width="14" fill="none" stroke-linecap="round"/>
  <path d="M30 86 A50 50 0 0 1 112 50" stroke="#2fb36b" stroke-width="14" fill="none" stroke-linecap="round"/>
  <line x1="80" y1="86" x2="110" y2="56" stroke="${C.secondary}" stroke-width="5" stroke-linecap="round"/><circle cx="80" cy="86" r="8" fill="${C.secondary}"/>`;
}

// Mallette (160 x 120)
function briefcase() {
  return `${shadow(80, 122, 76, 5, 0.15)}<rect x="56" y="0" width="48" height="26" rx="8" fill="none" stroke="#26364a" stroke-width="8"/>
  <rect x="0" y="20" width="160" height="100" rx="14" fill="${C.secondary}"/><rect x="0" y="54" width="160" height="10" fill="#0a1d30"/>
  <rect x="68" y="48" width="24" height="22" rx="4" fill="${C.accent}"/>`;
}

// Stylos & crayons (pot 90 x 150)
function pens() {
  return `${shadow(45, 150, 42, 5, 0.15)}
  <rect x="18" y="0" width="10" height="90" rx="3" fill="${C.accent}" transform="rotate(-10 23 90)"/><path d="M18 0 L23 -14 L28 0 Z" fill="#26364a" transform="rotate(-10 23 90)"/>
  <rect x="40" y="-14" width="10" height="100" rx="3" fill="${C.primary}"/><rect x="40" y="-14" width="10" height="16" rx="3" fill="#26364a"/>
  <rect x="60" y="4" width="10" height="86" rx="3" fill="${C.yellow}" transform="rotate(12 65 90)"/>
  <rect x="8" y="64" width="74" height="84" rx="10" fill="#26364a"/><rect x="8" y="64" width="74" height="14" rx="6" fill="#3a4b60"/>`;
}

// Classeur (70 x 180)
function binder(color) {
  return `${shadow(35, 182, 34, 4, 0.15)}<rect x="0" y="0" width="70" height="180" rx="6" fill="${color}"/>
  <rect x="14" y="20" width="42" height="56" rx="4" fill="#ffffff"/><rect x="20" y="30" width="30" height="4" rx="2" fill="#c3ccd8"/><rect x="20" y="40" width="22" height="4" rx="2" fill="#c3ccd8"/>
  <circle cx="35" cy="140" r="12" fill="#ffffff" opacity=".8"/><circle cx="35" cy="140" r="6" fill="${color}"/>`;
}

// Enveloppe (160 x 104)
function envelope() {
  return `${shadow(80, 106, 74, 4, 0.12)}<rect x="0" y="0" width="160" height="104" rx="8" fill="#f2e6d0" stroke="#e2d2b4"/>
  <path d="M0 6 L80 62 L160 6" stroke="#d8c39c" stroke-width="5" fill="none"/>`;
}

// Souris & clavier
function keyboard() {
  let keys = '';
  for (let r = 0; r < 3; r++) for (let c = 0; c < 12; c++) keys += `<rect x="${10 + c * 19}" y="${10 + r * 16}" width="15" height="12" rx="3" fill="#f4f6f9"/>`;
  return `${shadow(122, 72, 120, 5, 0.15)}<rect x="0" y="0" width="244" height="68" rx="10" fill="#cfd8e3"/>${keys}`;
}
function mouse() {
  return `${shadow(26, 82, 26, 4, 0.15)}<rect x="0" y="0" width="52" height="80" rx="26" fill="#26364a"/><line x1="26" y1="4" x2="26" y2="30" stroke="#5a6b80" stroke-width="3"/>`;
}

// ---------------------------------------------------------------- fonds
function defs() {
  return `<defs>
    <filter id="blur8" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="8"/></filter>
    <filter id="blur60" x="-50%" y="-50%" width="200%" height="200%"><feGaussianBlur stdDeviation="60"/></filter>
    <linearGradient id="screen" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#2a7fd0"/><stop offset="1" stop-color="${C.primary}"/></linearGradient>
    <linearGradient id="screen2" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${C.primary}"/><stop offset="1" stop-color="${C.secondary}"/></linearGradient>
    <linearGradient id="tonerBody" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#3a414d"/><stop offset=".6" stop-color="#2a2f38"/><stop offset="1" stop-color="#1c2027"/></linearGradient>
    <linearGradient id="bgDark" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="${C.secondary}"/><stop offset="1" stop-color="${C.primary}"/></linearGradient>
    <linearGradient id="bgAccent" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#9c4a05"/><stop offset=".55" stop-color="${C.accent}"/><stop offset="1" stop-color="#f6a04d"/></linearGradient>
    <linearGradient id="desk" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#ffffff" stop-opacity=".16"/><stop offset="1" stop-color="#ffffff" stop-opacity=".02"/></linearGradient>
    <pattern id="dots" width="26" height="26" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.6" fill="#ffffff" opacity=".12"/></pattern>
    <pattern id="dotsDark" width="26" height="26" patternUnits="userSpaceOnUse"><circle cx="2" cy="2" r="1.6" fill="${C.primary}" opacity=".10"/></pattern>
  </defs>`;
}
function lightBg(w, h, tint = C.primary, tint2 = C.accent) {
  return `<rect width="${w}" height="${h}" fill="#f3f6fa"/>
  <circle cx="${w * 0.78}" cy="${h * 0.25}" r="${h * 0.42}" fill="${tint}" opacity=".16" filter="url(#blur60)"/>
  <circle cx="${w * 0.18}" cy="${h * 0.85}" r="${h * 0.36}" fill="${tint2}" opacity=".14" filter="url(#blur60)"/>
  <rect width="${w}" height="${h}" fill="url(#dotsDark)"/>
  <rect x="0" y="${h * 0.78}" width="${w}" height="${h * 0.22}" fill="#e3e9f1"/>
  <rect x="0" y="${h * 0.78}" width="${w}" height="4" fill="#d2dbe6"/>`;
}
function darkBg(w, h, grad = 'bgDark') {
  return `<rect width="${w}" height="${h}" fill="url(#${grad})"/>
  <circle cx="${w * 0.82}" cy="${h * 0.2}" r="${h * 0.5}" fill="${grad === 'bgAccent' ? '#ffd2a8' : '#2a7fd0'}" opacity=".28" filter="url(#blur60)"/>
  <circle cx="${w * 0.55}" cy="${h * 1.0}" r="${h * 0.45}" fill="${grad === 'bgAccent' ? '#ffd2a8' : '#2a7fd0'}" opacity=".25" filter="url(#blur60)"/>
  <rect width="${w}" height="${h}" fill="url(#dots)"/>`;
}

// ---------------------------------------------------------------- scènes
const scenes = {
  // Bannière d'accueil (fond, le texte du site se superpose à gauche)
  'hero': [1920, 860, (w, h) => darkBg(w, h)
    + `<rect x="900" y="640" width="1020" height="220" fill="url(#desk)"/><rect x="900" y="640" width="1020" height="3" fill="#ffffff" opacity=".25"/>`
    + g(860, 470, 0.8, sheet('chart'), -10) + g(1700, 150, 0.8, sheet('text'), 12)
    + g(1180, 230, 1.3, mfp())
    + g(1010, 420, 0.95, toner(C.cyan)) + g(1072, 430, 0.92, toner(C.magenta)) + g(1134, 440, 0.9, toner(C.black))
    + g(1560, 500, 1.05, laptop())
    + g(1500, 690, 0.8, ream()) + g(1520, 640, 0.8, ream(C.accent))
    + g(1830, 520, 0.9, plant())],

  'home-pro': [1200, 750, (w, h) => lightBg(w, h)
    + g(140, 190, 1.0, sheet('chart')) + g(260, 150, 0.95, sheet('text'), 8)
    + g(470, 250, 1.05, mfp())
    + g(820, 420, 0.95, laptop())
    + g(250, 470, 0.85, briefcase()) + g(1080, 470, 0.8, mug())],

  'home-sharp': [1200, 750, (w, h) => lightBg(w, h, C.primary, '#2fb36b')
    + g(390, 130, 1.55, mfp())
    + g(140, 120, 0.9, leaf()) + g(880, 160, 1.0, gauge())
    + g(900, 380, 0.85, toner(C.black)) + g(960, 390, 0.82, toner(C.cyan)) + g(1020, 400, 0.8, toner(C.yellow))
    + g(120, 470, 0.8, plant())],

  'home-location': [1200, 750, (w, h) => lightBg(w, h)
    + g(420, 170, 1.35, mfp())
    + g(120, 230, 1.0, sheet('check')) + g(140, 470, 0.8, calendar())
    + g(860, 200, 1.1, key(), -20) + g(900, 420, 0.95, sheet('text'), 6)],

  'home-maintenance': [1200, 750, (w, h) => lightBg(w, h, C.primary, C.accent)
    + g(420, 170, 1.35, mfp({ accent: '#2fb36b' }))
    + g(160, 160, 1, gear(70, C.primary)) + g(250, 300, 1, gear(44, C.accent, 8))
    + g(880, 230, 1.15, wrench('#7d8ea3'), -35) + g(900, 380, 1.0, sheet('check'))
    + g(120, 470, 0.85, toner(C.black))],

  'cat-impression': [800, 500, (w, h) => lightBg(w, h)
    + g(250, 70, 1.05, mfp()) + g(90, 210, 0.75, sheet('text'), -8) + g(610, 230, 0.75, sheet('chart'), 8)],

  'cat-consommables': [800, 500, (w, h) => lightBg(w, h, C.cyan, C.magenta)
    + g(170, 160, 1.05, toner(C.cyan)) + g(250, 150, 1.1, toner(C.magenta)) + g(335, 140, 1.15, toner(C.yellow)) + g(425, 150, 1.1, toner(C.black))
    + g(540, 300, 1.0, ink(C.cyan)) + g(630, 300, 1.0, ink(C.magenta))],

  'cat-informatique': [800, 500, (w, h) => lightBg(w, h)
    + g(80, 170, 0.95, monitor()) + g(320, 210, 1.1, laptop())
    + g(140, 380, 0.8, keyboard()) + g(660, 330, 0.8, mouse())],

  'cat-papeterie': [800, 500, (w, h) => lightBg(w, h, C.accent, C.primary)
    + g(80, 296, 1.0, ream()) + g(86, 230, 1.0, ream(C.accent)) + g(92, 164, 1.0, ream('#2fb36b'))
    + g(380, 214, 1.0, binder(C.primary)) + g(456, 214, 1.0, binder(C.accent)) + g(532, 214, 1.0, binder('#2fb36b'))
    + g(640, 246, 1.0, pens()) + g(600, 410, 0.6, envelope())],

  // Bannières promotionnelles (texte du site superposé en bas à gauche)
  'promo-toners': [1000, 420, (w, h) => darkBg(w, h, 'bgAccent')
    + g(560, 90, 1.15, toner(C.cyan)) + g(640, 80, 1.2, toner(C.magenta)) + g(725, 70, 1.25, toner(C.yellow)) + g(815, 80, 1.2, toner(C.black))],

  'promo-location': [1000, 420, (w, h) => darkBg(w, h)
    + g(560, 60, 0.95, mfp()) + g(840, 150, 0.7, key(), -25)],

  'service-location': [800, 450, (w, h) => lightBg(w, h)
    + g(270, 70, 1.0, mfp()) + g(80, 150, 0.8, sheet('check')) + g(600, 140, 0.75, key(), -20) + g(620, 260, 0.6, calendar())],

  'service-maintenance': [800, 450, (w, h) => lightBg(w, h)
    + g(270, 70, 1.0, mfp({ accent: '#2fb36b' })) + g(130, 140, 1, gear(54, C.primary)) + g(620, 170, 0.8, wrench('#7d8ea3'), -35) + g(640, 260, 0.6, sheet('check'))],

  'service-solutions': [800, 450, (w, h) => lightBg(w, h, C.primary, '#2fb36b')
    + g(90, 110, 0.9, sheet('chart')) + g(250, 200, 0.85, laptop()) + g(560, 120, 1.0, gauge()) + g(620, 270, 0.6, leaf())],

  'service-vente': [800, 450, (w, h) => lightBg(w, h)
    + g(90, 100, 0.95, mfp()) + g(440, 210, 0.75, laptop()) + g(690, 200, 0.6, toner(C.black)) + g(730, 205, 0.58, toner(C.cyan))],

  // Diaporama d'accueil (sous la bannière)
  'slide-sharp': [1280, 360, (w, h) => darkBg(w, h)
    + g(760, 40, 0.88, mfp()) + g(1060, 150, 0.6, toner(C.black)) + g(1110, 155, 0.58, toner(C.cyan)) + g(600, 120, 0.9, leaf())],
  'slide-informatique': [1280, 360, (w, h) => darkBg(w, h)
    + g(720, 110, 0.9, laptop()) + g(1020, 120, 0.8, monitor()) + g(600, 260, 0.6, mouse())],
};

// ---------------------------------------------------------------- logos (PNG transparents)
function logo(w, h, dark = true) {
  const text = dark ? C.secondary : '#ffffff';
  return `<g transform="translate(6 10)">
    <rect width="100" height="100" rx="24" fill="url(#bgDark)"/>
    <rect x="20" y="40" width="60" height="34" rx="8" fill="#ffffff"/>
    <rect x="30" y="22" width="40" height="22" rx="4" fill="#dbe5f0"/>
    <rect x="30" y="62" width="40" height="22" rx="3" fill="#ffffff" stroke="#dbe5f0" stroke-width="3"/>
    <rect x="20" y="52" width="60" height="5" fill="${C.accent}"/>
    <circle cx="70" cy="46" r="3.5" fill="#2fd27a"/>
  </g>
  <text x="128" y="83" font-family="${FONT}" font-weight="800" font-size="62" letter-spacing="-1" fill="${text}">AFAM<tspan fill="${C.accent}">SHOP</tspan></text>`;
}

(async () => {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  const render = async (name, w, h, svgInner, type) => {
    await page.setViewportSize({ width: w, height: h });
    await page.setContent(`<html><body style="margin:0;background:transparent"><svg xmlns="http://www.w3.org/2000/svg" width="${w}" height="${h}" viewBox="0 0 ${w} ${h}">${defs()}${svgInner}</svg></body></html>`);
    const file = path.join(OUT, `${name}.${type === 'png' ? 'png' : 'jpg'}`);
    await page.screenshot({ path: file, type: type === 'png' ? 'png' : 'jpeg', quality: type === 'png' ? undefined : 84, omitBackground: type === 'png' });
    console.log(file);
  };
  for (const [name, [w, h, fn]] of Object.entries(scenes)) await render(name, w, h, fn(w, h), 'jpg');
  await render('logo', 520, 120, logo(520, 120, true), 'png');
  await render('logo-blanc', 520, 120, logo(520, 120, false), 'png');
  await render('favicon', 112, 120, `<g transform="translate(0 4)">${logo(0, 0, true).split('<text')[0]}</g>`, 'png');
  await browser.close();
})();
