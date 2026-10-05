import { useEffect, useRef } from "react";

export default function Cursor() {
  const dotRef = useRef(null);
  const ringRef = useRef(null);
  const labelRef = useRef(null);

  useEffect(() => {
    const dot = dotRef.current;
    const ring = ringRef.current;
    const label = labelRef.current;
    if (!dot || !ring || !label) return undefined;
    document.body.classList.add("custom-cursor");
    const pos = { x: window.innerWidth / 2, y: window.innerHeight / 2 };
    const ringPos = { x: pos.x, y: pos.y };
    let size = 34;
    let targetSize = 34;
    let visible = false;
    let raf = 0;

    const onMove = (e) => {
      pos.x = e.clientX;
      pos.y = e.clientY;
      if (!visible) {
        visible = true;
        ringPos.x = pos.x;
        ringPos.y = pos.y;
        dot.style.opacity = "1";
        ring.style.opacity = "1";
      }
      const target = e.target instanceof Element ? e.target.closest("a, button, [data-cursor]") : null;
      const text = target ? target.getAttribute("data-cursor") : null;
      if (text) {
        targetSize = 84;
        label.textContent = text;
        label.style.opacity = "1";
        ring.style.background = "rgba(129, 140, 248, 0.22)";
      } else if (target) {
        targetSize = 56;
        label.style.opacity = "0";
        ring.style.background = "rgba(94, 234, 212, 0.08)";
      } else {
        targetSize = 34;
        label.style.opacity = "0";
        ring.style.background = "transparent";
      }
    };
    const onLeave = (e) => {
      if (e.relatedTarget) return;
      visible = false;
      dot.style.opacity = "0";
      ring.style.opacity = "0";
    };
    const onDown = () => {
      targetSize *= 0.8;
    };

    const loop = () => {
      ringPos.x += (pos.x - ringPos.x) * 0.18;
      ringPos.y += (pos.y - ringPos.y) * 0.18;
      size += (targetSize - size) * 0.18;
      dot.style.transform = `translate3d(${pos.x}px, ${pos.y}px, 0) translate(-50%, -50%)`;
      ring.style.transform = `translate3d(${ringPos.x}px, ${ringPos.y}px, 0) translate(-50%, -50%)`;
      ring.style.width = `${size}px`;
      ring.style.height = `${size}px`;
      raf = requestAnimationFrame(loop);
    };
    raf = requestAnimationFrame(loop);

    window.addEventListener("pointermove", onMove, { passive: true });
    window.addEventListener("pointerdown", onDown);
    window.addEventListener("pointerout", onLeave);
    return () => {
      cancelAnimationFrame(raf);
      document.body.classList.remove("custom-cursor");
      window.removeEventListener("pointermove", onMove);
      window.removeEventListener("pointerdown", onDown);
      window.removeEventListener("pointerout", onLeave);
    };
  }, []);

  return (
    <>
      <div
        ref={ringRef}
        className="pointer-events-none fixed left-0 top-0 z-[200] flex items-center justify-center rounded-full border border-teal/60 opacity-0 transition-[background,opacity] duration-300"
        style={{ width: 34, height: 34 }}
      >
        <span ref={labelRef} className="font-mono text-[11px] font-medium uppercase tracking-widest text-white opacity-0 transition-opacity duration-200" />
      </div>
      <div ref={dotRef} className="pointer-events-none fixed left-0 top-0 z-[201] h-1.5 w-1.5 rounded-full bg-white opacity-0" />
    </>
  );
}
