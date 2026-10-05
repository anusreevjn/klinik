import { useEffect, useRef } from "react";

const COLORS = ["94, 234, 212", "129, 140, 248", "244, 114, 182", "238, 242, 255"];

export default function ParticleField({ reduced = false }) {
  const canvasRef = useRef(null);

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return undefined;
    const ctx = canvas.getContext("2d");
    const dpr = Math.min(window.devicePixelRatio || 1, 2);
    const mouse = { x: -9999, y: -9999, tx: -9999, ty: -9999, active: false };
    let w = 0;
    let h = 0;
    let parts = [];
    let raf = 0;
    let lastScroll = window.scrollY;

    const make = () => {
      const depth = Math.random();
      return {
        x: Math.random() * w,
        y: Math.random() * h,
        vx: (Math.random() - 0.5) * 0.18,
        vy: (Math.random() - 0.5) * 0.18,
        ox: 0,
        oy: 0,
        r: 0.6 + depth * 1.6,
        depth,
        a: 0,
        ta: 0.25 + depth * 0.55,
        c: COLORS[Math.floor(Math.random() * COLORS.length)],
        mag: 0.4 + Math.random() * 3.5,
      };
    };

    const resize = () => {
      const nextW = window.innerWidth;
      const widthChanged = nextW !== w;
      w = nextW;
      h = window.innerHeight;
      canvas.width = w * dpr;
      canvas.height = h * dpr;
      canvas.style.width = `${w}px`;
      canvas.style.height = `${h}px`;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const target = Math.round(Math.min(110, Math.max(36, (w * h) / 16000)));
      if (widthChanged || parts.length === 0) parts = Array.from({ length: target }, make);
    };

    const onMove = (e) => {
      mouse.tx = e.clientX;
      mouse.ty = e.clientY;
      if (!mouse.active) {
        mouse.x = mouse.tx;
        mouse.y = mouse.ty;
      }
      mouse.active = true;
    };
    const onOut = (e) => {
      if (!e.relatedTarget) mouse.active = false;
    };
    const onUp = (e) => {
      if (e.pointerType !== "mouse") mouse.active = false;
    };

    const draw = () => {
      const sy = window.scrollY;
      const dScroll = sy - lastScroll;
      lastScroll = sy;
      mouse.x += (mouse.tx - mouse.x) * 0.15;
      mouse.y += (mouse.ty - mouse.y) * 0.15;
      ctx.clearRect(0, 0, w, h);
      const link = 120;
      const reach = 180;

      for (const p of parts) {
        p.x += p.vx;
        p.y += p.vy - dScroll * (0.05 + p.depth * 0.25);
        if (p.x < -20) p.x = w + 20;
        if (p.x > w + 20) p.x = -20;
        if (p.y < -20) p.y = h + 20;
        if (p.y > h + 20) p.y = -20;
        let tx = 0;
        let ty = 0;
        if (mouse.active) {
          const dx = mouse.x - p.x;
          const dy = mouse.y - p.y;
          const d = Math.hypot(dx, dy);
          if (d < reach) {
            const f = (1 - d / reach) * p.mag * 6;
            tx = (dx / (d || 1)) * f;
            ty = (dy / (d || 1)) * f;
          }
        }
        p.ox += (tx - p.ox) * 0.06;
        p.oy += (ty - p.oy) * 0.06;
        p.a += (p.ta - p.a) * 0.02;
      }

      for (let i = 0; i < parts.length; i++) {
        const a = parts[i];
        const ax = a.x + a.ox;
        const ay = a.y + a.oy;
        for (let j = i + 1; j < parts.length; j++) {
          const b = parts[j];
          const bx = b.x + b.ox;
          const by = b.y + b.oy;
          const d = Math.hypot(ax - bx, ay - by);
          if (d < link) {
            let alpha = (1 - d / link) * 0.12;
            if (mouse.active) {
              const md = Math.hypot((ax + bx) / 2 - mouse.x, (ay + by) / 2 - mouse.y);
              if (md < reach) alpha += (1 - md / reach) * 0.35 * (1 - d / link);
            }
            ctx.strokeStyle = `rgba(129, 140, 248, ${alpha})`;
            ctx.lineWidth = 0.6;
            ctx.beginPath();
            ctx.moveTo(ax, ay);
            ctx.lineTo(bx, by);
            ctx.stroke();
          }
        }
        if (mouse.active) {
          const md = Math.hypot(ax - mouse.x, ay - mouse.y);
          if (md < reach * 0.85) {
            ctx.strokeStyle = `rgba(94, 234, 212, ${(1 - md / (reach * 0.85)) * 0.4})`;
            ctx.lineWidth = 0.7;
            ctx.beginPath();
            ctx.moveTo(ax, ay);
            ctx.lineTo(mouse.x, mouse.y);
            ctx.stroke();
          }
        }
      }

      for (const p of parts) {
        ctx.beginPath();
        ctx.arc(p.x + p.ox, p.y + p.oy, p.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${p.c}, ${p.a})`;
        ctx.fill();
      }

      raf = requestAnimationFrame(draw);
    };

    const onVisibility = () => {
      cancelAnimationFrame(raf);
      if (!document.hidden && !reduced) raf = requestAnimationFrame(draw);
    };

    const drawStatic = () => {
      ctx.clearRect(0, 0, w, h);
      for (const p of parts) {
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(${p.c}, ${p.ta})`;
        ctx.fill();
      }
    };

    const onResize = () => {
      resize();
      if (reduced) drawStatic();
    };

    onResize();
    if (!reduced) raf = requestAnimationFrame(draw);

    window.addEventListener("resize", onResize);
    window.addEventListener("pointermove", onMove, { passive: true });
    window.addEventListener("pointerout", onOut, { passive: true });
    window.addEventListener("pointerup", onUp, { passive: true });
    window.addEventListener("pointercancel", onUp, { passive: true });
    document.addEventListener("visibilitychange", onVisibility);
    return () => {
      cancelAnimationFrame(raf);
      window.removeEventListener("resize", onResize);
      window.removeEventListener("pointermove", onMove);
      window.removeEventListener("pointerout", onOut);
      window.removeEventListener("pointerup", onUp);
      window.removeEventListener("pointercancel", onUp);
      document.removeEventListener("visibilitychange", onVisibility);
    };
  }, [reduced]);

  return <canvas ref={canvasRef} aria-hidden="true" className="pointer-events-none fixed inset-0 z-0" />;
}
