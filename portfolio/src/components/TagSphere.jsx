import { useEffect, useRef } from "react";

export default function TagSphere({ items, reduced = false, className = "" }) {
  const wrap = useRef(null);
  const tags = useRef([]);

  useEffect(() => {
    const el = wrap.current;
    if (!el) return undefined;
    const n = items.length;
    const base = items.map((_, i) => {
      const y = 1 - (i / (n - 1)) * 2;
      const r = Math.sqrt(1 - y * y);
      const th = Math.PI * (3 - Math.sqrt(5)) * i;
      return [Math.cos(th) * r, y, Math.sin(th) * r];
    });
    let radius = 0;
    let ax = 0.0016;
    let ay = 0.0034;
    let rotX = 0;
    let rotY = 0;
    let raf = 0;
    let dragging = false;
    let last = { x: 0, y: 0 };
    let hover = false;
    let running = false;

    const measure = () => {
      radius = Math.min(el.clientWidth, el.clientHeight) * (el.clientWidth < 480 ? 0.34 : 0.4);
    };

    const render = () => {
      const cx = Math.cos(rotX);
      const sx = Math.sin(rotX);
      const cy = Math.cos(rotY);
      const sy = Math.sin(rotY);
      for (let i = 0; i < n; i++) {
        const node = tags.current[i];
        if (!node) continue;
        const [x0, y0, z0] = base[i];
        const x1 = x0 * cy + z0 * sy;
        const z1 = -x0 * sy + z0 * cy;
        const y2 = y0 * cx - z1 * sx;
        const z2 = y0 * sx + z1 * cx;
        const s = (z2 + 2) / 3;
        node.style.transform = `translate(-50%, -50%) translate3d(${(x1 * radius).toFixed(1)}px, ${(y2 * radius).toFixed(1)}px, 0) scale(${s.toFixed(3)})`;
        node.style.opacity = (0.2 + ((z2 + 1) / 2) * 0.8).toFixed(3);
        node.style.zIndex = String(Math.round((z2 + 1) * 100));
      }
    };

    const loop = () => {
      if (!dragging) {
        const k = hover ? 0.35 : 1;
        rotX += ax * k;
        rotY += ay * k;
        ax += (0.0016 - ax) * 0.02;
        ay += (0.0034 - ay) * 0.02;
      }
      render();
      raf = requestAnimationFrame(loop);
    };

    const start = () => {
      if (running || reduced) return;
      running = true;
      raf = requestAnimationFrame(loop);
    };
    const stop = () => {
      running = false;
      cancelAnimationFrame(raf);
    };

    const down = (e) => {
      dragging = true;
      last = { x: e.clientX, y: e.clientY };
      el.setPointerCapture(e.pointerId);
    };
    const move = (e) => {
      if (!dragging) return;
      const dx = e.clientX - last.x;
      const dy = e.clientY - last.y;
      last = { x: e.clientX, y: e.clientY };
      rotY += dx * 0.006;
      rotX -= dy * 0.006;
      ay = dx * 0.0016;
      ax = -dy * 0.0016;
      if (reduced) render();
    };
    const up = (e) => {
      dragging = false;
      if (el.hasPointerCapture(e.pointerId)) el.releasePointerCapture(e.pointerId);
    };
    const enter = () => {
      hover = true;
    };
    const leave = () => {
      hover = false;
    };

    measure();
    render();
    const ro = new ResizeObserver(() => {
      measure();
      render();
    });
    ro.observe(el);
    const io = new IntersectionObserver(([entry]) => {
      if (entry.isIntersecting) start();
      else stop();
    });
    io.observe(el);

    el.addEventListener("pointerdown", down);
    el.addEventListener("pointermove", move);
    el.addEventListener("pointerup", up);
    el.addEventListener("pointercancel", up);
    el.addEventListener("pointerenter", enter);
    el.addEventListener("pointerleave", leave);
    return () => {
      stop();
      ro.disconnect();
      io.disconnect();
      el.removeEventListener("pointerdown", down);
      el.removeEventListener("pointermove", move);
      el.removeEventListener("pointerup", up);
      el.removeEventListener("pointercancel", up);
      el.removeEventListener("pointerenter", enter);
      el.removeEventListener("pointerleave", leave);
    };
  }, [items, reduced]);

  return (
    <div
      ref={wrap}
      className={`relative cursor-grab touch-pan-y select-none active:cursor-grabbing ${className}`}
      data-cursor="Drag"
      aria-hidden="true"
    >
      <div className="absolute left-1/2 top-1/2">
        {items.map((t, i) => (
          <span
            key={t}
            ref={(node) => {
              tags.current[i] = node;
            }}
            className="absolute left-0 top-0 whitespace-nowrap rounded-full border border-mist-400/15 bg-ink-900/70 px-3 py-1.5 font-mono text-[11px] text-mist-50 md:text-xs"
          >
            {t}
          </span>
        ))}
      </div>
    </div>
  );
}
