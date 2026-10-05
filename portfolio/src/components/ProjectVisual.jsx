function Defs({ id, a, b }) {
  return (
    <defs>
      <linearGradient id={`g-${id}`} x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" stopColor={a} />
        <stop offset="1" stopColor={b} />
      </linearGradient>
      <radialGradient id={`r-${id}`} cx="0.5" cy="0.5" r="0.6">
        <stop offset="0" stopColor={a} stopOpacity="0.25" />
        <stop offset="1" stopColor={a} stopOpacity="0" />
      </radialGradient>
    </defs>
  );
}

function Ml({ id }) {
  const bars = [
    { y: 46, w: 128, pos: true },
    { y: 76, w: 96, pos: true },
    { y: 106, w: 70, pos: false },
    { y: 136, w: 58, pos: true },
    { y: 166, w: 40, pos: false },
    { y: 196, w: 26, pos: true },
  ];
  return (
    <g>
      <line x1="200" y1="30" x2="200" y2="214" stroke="rgba(199,205,234,0.25)" strokeDasharray="3 5" />
      {bars.map((b, i) => (
        <rect
          key={i}
          x={b.pos ? 200 : 200 - b.w}
          y={b.y}
          width={b.w}
          height="16"
          rx="4"
          fill={`url(#g-${id})`}
          opacity={b.pos ? 0.95 : 0.5}
          className="pv-bar"
          style={{ transformOrigin: "200px 0", animationDelay: `${i * 0.08}s` }}
        />
      ))}
      {bars.map((b, i) => (
        <rect key={`l-${i}`} x="44" y={b.y + 5} width={b.pos ? 60 : 44} height="6" rx="3" fill="rgba(199,205,234,0.18)" />
      ))}
      <text x="356" y="28" textAnchor="end" fontFamily="JetBrains Mono, monospace" fontSize="11" fill="rgba(199,205,234,0.6)">
        SHAP
      </text>
    </g>
  );
}

function Roles({ id }) {
  const nodes = [
    { x: 80, y: 70, t: "Admin" },
    { x: 320, y: 70, t: "Nurse" },
    { x: 200, y: 196, t: "Patient" },
  ];
  return (
    <g>
      {nodes.map((n) => (
        <line key={`l-${n.t}`} x1="200" y1="118" x2={n.x} y2={n.y} stroke={`url(#g-${id})`} strokeWidth="1.5" strokeDasharray="4 6" className="pv-dash" />
      ))}
      <circle cx="200" cy="118" r="30" fill="rgba(5,6,15,0.9)" stroke={`url(#g-${id})`} strokeWidth="1.5" />
      <ellipse cx="200" cy="108" rx="13" ry="5" fill="none" stroke="#eef2ff" strokeOpacity="0.8" />
      <path d="M187 108v18c0 2.8 5.8 5 13 5s13-2.2 13-5v-18" fill="none" stroke="#eef2ff" strokeOpacity="0.8" />
      <path d="M187 117c0 2.8 5.8 5 13 5s13-2.2 13-5" fill="none" stroke="#eef2ff" strokeOpacity="0.5" />
      {nodes.map((n) => (
        <g key={n.t}>
          <rect x={n.x - 40} y={n.y - 17} width="80" height="34" rx="17" fill="rgba(17,20,42,0.95)" stroke="rgba(199,205,234,0.25)" />
          <text x={n.x} y={n.y + 4} textAnchor="middle" fontFamily="JetBrains Mono, monospace" fontSize="12" fill="#eef2ff">
            {n.t}
          </text>
        </g>
      ))}
    </g>
  );
}

function Mobile({ id }) {
  return (
    <g>
      <rect x="146" y="20" width="108" height="204" rx="20" fill="rgba(5,6,15,0.9)" stroke={`url(#g-${id})`} strokeWidth="1.5" />
      <rect x="182" y="28" width="36" height="6" rx="3" fill="rgba(199,205,234,0.25)" />
      <rect x="158" y="44" width="84" height="44" rx="8" fill={`url(#g-${id})`} opacity="0.85" className="pv-pulse" />
      {[0, 1, 2, 3].map((i) => (
        <g key={i}>
          <rect x={158 + (i % 2) * 44} y={96 + Math.floor(i / 2) * 54} width="40" height="46" rx="7" fill="rgba(199,205,234,0.1)" />
          <rect x={163 + (i % 2) * 44} y={128 + Math.floor(i / 2) * 54} width="22" height="4" rx="2" fill="rgba(199,205,234,0.35)" />
        </g>
      ))}
      <rect x="158" y="206" width="84" height="8" rx="4" fill="rgba(199,205,234,0.12)" />
      <g fontFamily="JetBrains Mono, monospace" fontSize="12" fill="#eef2ff">
        <rect x="52" y="70" width="64" height="30" rx="15" fill="rgba(17,20,42,0.95)" stroke="rgba(199,205,234,0.25)" />
        <text x="84" y="89" textAnchor="middle">BM</text>
        <rect x="284" y="140" width="64" height="30" rx="15" fill="rgba(17,20,42,0.95)" stroke="rgba(199,205,234,0.25)" />
        <text x="316" y="159" textAnchor="middle">EN</text>
      </g>
    </g>
  );
}

function Chart({ id }) {
  const bars = [60, 92, 74, 120, 98, 140];
  return (
    <g>
      {[60, 110, 160].map((y) => (
        <line key={y} x1="40" y1={y} x2="360" y2={y} stroke="rgba(199,205,234,0.08)" />
      ))}
      {bars.map((h, i) => (
        <rect
          key={i}
          x={58 + i * 50}
          y={206 - h}
          width="26"
          height={h}
          rx="5"
          fill="rgba(199,205,234,0.12)"
          className="pv-grow"
          style={{ animationDelay: `${i * 0.07}s` }}
        />
      ))}
      <path
        d="M71 170 C 110 140, 140 120, 171 132 S 230 96, 271 104 S 320 60, 333 58"
        fill="none"
        stroke={`url(#g-${id})`}
        strokeWidth="3"
        strokeLinecap="round"
        className="pv-draw"
      />
      <path d="M333 58 C 345 50, 352 46, 360 40" fill="none" stroke={`url(#g-${id})`} strokeWidth="3" strokeDasharray="4 6" strokeLinecap="round" opacity="0.7" />
      <circle cx="333" cy="58" r="5" fill="#05060f" stroke={`url(#g-${id})`} strokeWidth="2.5" />
    </g>
  );
}

function Quantum({ id }) {
  const ys = [50, 82, 114, 146, 178, 210];
  const gates = [
    [0, 90], [1, 90], [2, 150], [3, 150], [4, 210], [5, 210], [0, 270], [2, 270], [4, 300], [1, 330],
  ];
  return (
    <g>
      {ys.map((y) => (
        <line key={y} x1="40" y1={y} x2="360" y2={y} stroke="rgba(199,205,234,0.22)" />
      ))}
      {[120, 180, 240].map((x, i) => (
        <g key={x}>
          <line x1={x} y1={ys[i * 2]} x2={x} y2={ys[i * 2 + 1]} stroke={`url(#g-${id})`} strokeWidth="1.5" />
          <circle cx={x} cy={ys[i * 2]} r="4" fill={`url(#g-${id})`} />
          <circle cx={x} cy={ys[i * 2 + 1]} r="8" fill="none" stroke={`url(#g-${id})`} strokeWidth="1.5" />
        </g>
      ))}
      {gates.map(([q, x], i) => (
        <rect
          key={i}
          x={x - 11}
          y={ys[q] - 11}
          width="22"
          height="22"
          rx="5"
          fill="rgba(5,6,15,0.95)"
          stroke={`url(#g-${id})`}
          strokeWidth="1.5"
          className="pv-pulse"
          style={{ animationDelay: `${i * 0.15}s` }}
        />
      ))}
      <circle r="4" fill="#eef2ff" className="pv-travel">
        <animateMotion dur="3.2s" repeatCount="indefinite" path="M40 114 L360 114" />
      </circle>
    </g>
  );
}

function Flow({ id }) {
  const nodes = [
    { x: 70, t: "Telegram" },
    { x: 200, t: "Form" },
    { x: 330, t: "DOCX" },
  ];
  return (
    <g>
      <path id={`p-${id}`} d="M70 120 L330 120" fill="none" stroke={`url(#g-${id})`} strokeWidth="1.5" strokeDasharray="4 6" className="pv-dash" />
      {nodes.map((n) => (
        <g key={n.t}>
          <rect x={n.x - 46} y="92" width="92" height="56" rx="14" fill="rgba(17,20,42,0.95)" stroke="rgba(199,205,234,0.25)" />
          <text x={n.x} y="125" textAnchor="middle" fontFamily="JetBrains Mono, monospace" fontSize="12" fill="#eef2ff">
            {n.t}
          </text>
        </g>
      ))}
      <circle r="5" fill={`url(#g-${id})`}>
        <animateMotion dur="2.6s" repeatCount="indefinite" path="M116 120 L154 120 M246 120 L284 120" />
      </circle>
      {[0, 1, 2].map((i) => (
        <rect key={i} x={300 + i * 8} y={170 + i * 6} width="56" height="34" rx="6" fill="rgba(5,6,15,0.95)" stroke={`url(#g-${id})`} strokeOpacity={0.4 + i * 0.25} />
      ))}
    </g>
  );
}

const map = { ml: Ml, roles: Roles, mobile: Mobile, chart: Chart, quantum: Quantum, flow: Flow };

export default function ProjectVisual({ id, kind, accent }) {
  const Comp = map[kind] || Ml;
  return (
    <svg viewBox="0 0 400 240" className="h-full w-full" role="presentation" aria-hidden="true">
      <Defs id={id} a={accent[0]} b={accent[1]} />
      <rect width="400" height="240" fill={`url(#r-${id})`} />
      <Comp id={id} />
    </svg>
  );
}
