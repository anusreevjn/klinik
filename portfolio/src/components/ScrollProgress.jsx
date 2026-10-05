import { useEffect, useRef } from "react";

export default function ScrollProgress() {
  const bar = useRef(null);
  useEffect(() => {
    let raf = 0;
    const tick = () => {
      const max = document.documentElement.scrollHeight - window.innerHeight;
      const p = max > 0 ? window.scrollY / max : 0;
      if (bar.current) bar.current.style.transform = `scaleX(${p})`;
      raf = requestAnimationFrame(tick);
    };
    raf = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(raf);
  }, []);
  return (
    <div className="fixed inset-x-0 top-0 z-[120] h-[2px]">
      <div ref={bar} className="h-full origin-left" style={{ background: "var(--grad)", transform: "scaleX(0)" }} />
    </div>
  );
}
