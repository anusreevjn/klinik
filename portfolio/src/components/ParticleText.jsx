import { useEffect, useRef } from "react";

const PALETTE = [
  [94, 234, 212],
  [129, 140, 248],
  [244, 114, 182],
];

function mixColor(t) {
  const seg = t * (PALETTE.length - 1);
  const i = Math.min(Math.floor(seg), PALETTE.length - 2);
  const k = seg - i;
  const a = PALETTE[i];
  const b = PALETTE[i + 1];
  return `rgb(${Math.round(a[0] + (b[0] - a[0]) * k)}, ${Math.round(a[1] + (b[1] - a[1]) * k)}, ${Math.round(a[2] + (b[2] - a[2]) * k)})`;
}

export default function ParticleText({ text = "AV", className = "", reduced = false }) {
  const wrapRef = useRef(null);
  const canvasRef = useRef(null);

  useEffect(() => {
    const wrap = wrapRef.current;
    const canvas = canvasRef.current;
    if (!wrap || !canvas) return undefined;
    const ctx = canvas.getContext("2d");
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const pointer = { x: -9999, y: -9999, down: false };
    let parts = [];
    let w = 0;
    let h = 0;
    let raf = 0;
    let running = false;

    const build = () => {
      w = wrap.clientWidth;
      h = wrap.clientHeight;
      canvas.width = w * dpr;
      canvas.height = h * dpr;
      canvas.style.width = `${w}px`;
      canvas.style.height = `${h}px`;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

      const off = document.createElement("canvas");
      off.width = w;
      off.height = h;
      const o = off.getContext("2d");
      let size = h * 0.78;
      o.font = `800 ${size}px "Mona Sans", Arial, sans-serif`;
      const measured = o.measureText(text).width;
      if (measured > w * 0.9) size *= (w * 0.9) / measured;
      o.font = `800 ${size}px "Mona Sans", Arial, sans-serif`;
      o.textAlign = "center";
      o.textBaseline = "middle";
      o.fillStyle = "#fff";
      o.fillText(text, w / 2, h / 2 + size * 0.04);
      const data = o.getImageData(0, 0, w, h).data;
      const gap = w < 360 ? 4 : 5;
      const next = [];
      for (let y = 0; y < h; y += gap) {
        for (let x = 0; x < w; x += gap) {
          if (data[(y * w + x) * 4 + 3] > 128) {
            next.push({
              hx: x,
              hy: y,
              x: reduced ? x : Math.random() * w,
              y: reduced ? y : Math.random() * h,
              vx: 0,
              vy: 0,
              r: 1 + Math.random() * 1.1,
              c: mixColor(x / w),
            });
          }
        }
      }
      parts = next;
    };

    const draw = () => {
      ctx.clearRect(0, 0, w, h);
      const radius = 70;
      for (const p of parts) {
        const dx = p.x - pointer.x;
        const dy = p.y - pointer.y;
        const d = Math.hypot(dx, dy);
        if (d < radius) {
          const f = (1 - d / radius) * 6;
          p.vx += (dx / (d || 1)) * f;
          p.vy += (dy / (d || 1)) * f;
        }
        p.vx += (p.hx - p.x) * 0.045;
        p.vy += (p.hy - p.y) * 0.045;
        p.vx *= 0.84;
        p.vy *= 0.84;
        p.x += p.vx;
        p.y += p.vy;
        ctx.fillStyle = p.c;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fill();
      }
      raf = requestAnimationFrame(draw);
    };

    const drawStatic = () => {
      ctx.clearRect(0, 0, w, h);
      for (const p of parts) {
        ctx.fillStyle = p.c;
        ctx.beginPath();
        ctx.arc(p.hx, p.hy, p.r, 0, Math.PI * 2);
        ctx.fill();
      }
    };

    const start = () => {
      if (running || reduced) return;
      running = true;
      raf = requestAnimationFrame(draw);
    };
    const stop = () => {
      running = false;
      cancelAnimationFrame(raf);
    };

    const local = (e) => {
      const r = canvas.getBoundingClientRect();
      pointer.x = e.clientX - r.left;
      pointer.y = e.clientY - r.top;
    };
    const onLeave = () => {
      pointer.x = -9999;
      pointer.y = -9999;
    };
    const onDown = (e) => {
      local(e);
      for (const p of parts) {
        const dx = p.x - pointer.x;
        const dy = p.y - pointer.y;
        const d = Math.hypot(dx, dy) || 1;
        const f = 18 + Math.random() * 14;
        p.vx += (dx / d) * f;
        p.vy += (dy / d) * f;
      }
    };

    const init = () => {
      build();
      if (reduced) drawStatic();
    };

    let ready = false;
    const fontsReady = document.fonts && document.fonts.ready ? document.fonts.ready : Promise.resolve();
    fontsReady.then(() => {
      ready = true;
      init();
    });

    const io = new IntersectionObserver(([entry]) => {
      if (entry.isIntersecting) start();
      else stop();
    });
    io.observe(wrap);

    let lastW = wrap.clientWidth;
    const ro = new ResizeObserver(() => {
      if (!ready || wrap.clientWidth === lastW) return;
      lastW = wrap.clientWidth;
      init();
    });
    ro.observe(wrap);

    canvas.addEventListener("pointermove", local);
    canvas.addEventListener("pointerleave", onLeave);
    canvas.addEventListener("pointerdown", onDown);
    return () => {
      stop();
      io.disconnect();
      ro.disconnect();
      canvas.removeEventListener("pointermove", local);
      canvas.removeEventListener("pointerleave", onLeave);
      canvas.removeEventListener("pointerdown", onDown);
    };
  }, [text, reduced]);

  return (
    <div ref={wrapRef} className={className}>
      <canvas ref={canvasRef} className="block h-full w-full touch-pan-y" aria-hidden="true" />
    </div>
  );
}
