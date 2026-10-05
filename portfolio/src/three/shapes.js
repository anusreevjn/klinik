const TAU = Math.PI * 2;

const rand = (min, max) => min + Math.random() * (max - min);

function write(out, i, x, y, z) {
  out[i * 3] = x;
  out[i * 3 + 1] = y;
  out[i * 3 + 2] = z;
}

function rotateXZ(x, y, z, ax, az) {
  const cy = Math.cos(ax);
  const sy = Math.sin(ax);
  const y1 = y * cy - z * sy;
  const z1 = y * sy + z * cy;
  const cz = Math.cos(az);
  const sz = Math.sin(az);
  return [x * cz - y1 * sz, x * sz + y1 * cz, z1];
}

export function globe(count) {
  const out = new Float32Array(count * 3);
  const r = 2.05;
  for (let i = 0; i < count; i++) {
    const pick = Math.random();
    if (pick < 0.22) {
      const a = rand(0, TAU);
      const rr = 2.95 + rand(-0.04, 0.04);
      const [x, y, z] = rotateXZ(Math.cos(a) * rr, 0, Math.sin(a) * rr, 1.2, 0.35);
      write(out, i, x, y + rand(-0.02, 0.02), z);
    } else if (pick < 0.5) {
      const m = Math.floor(Math.random() * 10);
      const lon = (m / 10) * Math.PI;
      const lat = rand(0, TAU);
      const x = Math.cos(lat) * Math.cos(lon) * r;
      const z = Math.cos(lat) * Math.sin(lon) * r;
      const y = Math.sin(lat) * r;
      write(out, i, x, y, z);
    } else if (pick < 0.7) {
      const p = Math.floor(Math.random() * 7);
      const lat = ((p + 1) / 8) * Math.PI - Math.PI / 2;
      const a = rand(0, TAU);
      const pr = Math.cos(lat) * r;
      write(out, i, Math.cos(a) * pr, Math.sin(lat) * r, Math.sin(a) * pr);
    } else {
      const u = rand(-1, 1);
      const a = rand(0, TAU);
      const s = Math.sqrt(1 - u * u);
      const rr = r + rand(-0.03, 0.03);
      write(out, i, s * Math.cos(a) * rr, u * rr, s * Math.sin(a) * rr);
    }
  }
  return out;
}

function roundedRectPoint(w, h, rad, s) {
  const sw = w - 2 * rad;
  const sh = h - 2 * rad;
  const arc = (Math.PI / 2) * rad;
  const total = 2 * sw + 2 * sh + 4 * arc;
  let d = s * total;
  const hw = w / 2;
  const hh = h / 2;
  if (d < sw) return [-hw + rad + d, hh];
  d -= sw;
  if (d < arc) {
    const a = Math.PI / 2 - d / rad;
    return [hw - rad + Math.cos(a) * rad, hh - rad + Math.sin(a) * rad];
  }
  d -= arc;
  if (d < sh) return [hw, hh - rad - d];
  d -= sh;
  if (d < arc) {
    const a = -d / rad;
    return [hw - rad + Math.cos(a) * rad, -hh + rad + Math.sin(a) * rad];
  }
  d -= arc;
  if (d < sw) return [hw - rad - d, -hh];
  d -= sw;
  if (d < arc) {
    const a = -Math.PI / 2 - d / rad;
    return [-hw + rad + Math.cos(a) * rad, -hh + rad + Math.sin(a) * rad];
  }
  d -= arc;
  if (d < sh) return [-hw, -hh + rad + d];
  d -= sh;
  const a = Math.PI - d / rad;
  return [-hw + rad + Math.cos(a) * rad, hh - rad + Math.sin(a) * rad];
}

const phoneBlocks = [
  { kind: "rect", x0: -0.78, y0: 1.5, x1: 0.78, y1: 1.54, w: 2 },
  { kind: "rect", x0: -0.78, y0: 0.55, x1: 0.78, y1: 1.3, w: 14 },
  { kind: "circle", cx: -0.58, cy: 0.12, r: 0.17, w: 3 },
  { kind: "rect", x0: -0.3, y0: 0.18, x1: 0.78, y1: 0.24, w: 2.5 },
  { kind: "rect", x0: -0.3, y0: 0.0, x1: 0.4, y1: 0.06, w: 1.6 },
  { kind: "circle", cx: -0.58, cy: -0.4, r: 0.17, w: 3 },
  { kind: "rect", x0: -0.3, y0: -0.34, x1: 0.78, y1: -0.28, w: 2.5 },
  { kind: "rect", x0: -0.3, y0: -0.52, x1: 0.4, y1: -0.46, w: 1.6 },
  { kind: "circle", cx: -0.58, cy: -0.92, r: 0.17, w: 3 },
  { kind: "rect", x0: -0.3, y0: -0.86, x1: 0.78, y1: -0.8, w: 2.5 },
  { kind: "rect", x0: -0.3, y0: -1.04, x1: 0.4, y1: -0.98, w: 1.6 },
  { kind: "circle", cx: -0.57, cy: -1.5, r: 0.08, w: 1 },
  { kind: "circle", cx: -0.19, cy: -1.5, r: 0.08, w: 1 },
  { kind: "circle", cx: 0.19, cy: -1.5, r: 0.08, w: 1 },
  { kind: "circle", cx: 0.57, cy: -1.5, r: 0.08, w: 1 },
];

const blockTotal = phoneBlocks.reduce((sum, b) => sum + b.w, 0);

function pickBlock() {
  let n = Math.random() * blockTotal;
  for (const b of phoneBlocks) {
    n -= b.w;
    if (n <= 0) return b;
  }
  return phoneBlocks[phoneBlocks.length - 1];
}

export function phone(count) {
  const out = new Float32Array(count * 3);
  const W = 2.1;
  const H = 3.9;
  for (let i = 0; i < count; i++) {
    const pick = Math.random();
    if (pick < 0.3) {
      const [x, y] = roundedRectPoint(W, H, 0.34, Math.random());
      write(out, i, x, y, Math.random() < 0.5 ? -0.12 : 0.12);
    } else if (pick < 0.38) {
      const [x, y] = roundedRectPoint(W - 0.24, H - 0.24, 0.24, Math.random());
      write(out, i, x, y, 0.13);
    } else if (pick < 0.4) {
      const [x, y] = roundedRectPoint(0.6, 0.12, 0.06, Math.random());
      write(out, i, x, y + 1.72, 0.13);
    } else {
      const b = pickBlock();
      if (b.kind === "rect") {
        write(out, i, rand(b.x0, b.x1), rand(b.y0, b.y1), 0.13 + rand(-0.01, 0.01));
      } else {
        const a = rand(0, TAU);
        const rr = Math.sqrt(Math.random()) * b.r;
        write(out, i, b.cx + Math.cos(a) * rr, b.cy + Math.sin(a) * rr, 0.13);
      }
    }
  }
  return out;
}

export function network(count) {
  const out = new Float32Array(count * 3);
  const layers = [4, 6, 6, 3];
  const xs = [-2.6, -0.87, 0.87, 2.6];
  const gap = 0.72;
  const nodes = layers.map((n, li) =>
    Array.from({ length: n }, (_, k) => [xs[li], (k - (n - 1) / 2) * gap, (k % 2 === 0 ? 1 : -1) * 0.18])
  );
  const edges = [];
  for (let l = 0; l < nodes.length - 1; l++) {
    for (const a of nodes[l]) {
      for (const b of nodes[l + 1]) edges.push([a, b]);
    }
  }
  const flat = nodes.flat();
  for (let i = 0; i < count; i++) {
    if (Math.random() < 0.42) {
      const n = flat[Math.floor(Math.random() * flat.length)];
      const u = rand(-1, 1);
      const a = rand(0, TAU);
      const s = Math.sqrt(1 - u * u);
      const rr = rand(0.1, 0.19);
      write(out, i, n[0] + s * Math.cos(a) * rr, n[1] + u * rr, n[2] + s * Math.sin(a) * rr);
    } else {
      const [a, b] = edges[Math.floor(Math.random() * edges.length)];
      const t = Math.random();
      write(
        out,
        i,
        a[0] + (b[0] - a[0]) * t + rand(-0.012, 0.012),
        a[1] + (b[1] - a[1]) * t + rand(-0.012, 0.012),
        a[2] + (b[2] - a[2]) * t + rand(-0.012, 0.012)
      );
    }
  }
  return out;
}

export const SHAPES = [
  { key: "web", label: "Web", build: globe },
  { key: "mobile", label: "Mobile", build: phone },
  { key: "ml", label: "ML dashboards", build: network },
];
