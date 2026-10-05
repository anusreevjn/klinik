import { useEffect, useRef, useState } from "react";
import gsap from "gsap";

export default function Preloader({ onDone, reduced = false }) {
  const root = useRef(null);
  const [pct, setPct] = useState(0);

  useEffect(() => {
    document.body.classList.add("loading");
    const counter = { v: 0 };
    const ctx = gsap.context(() => {
      const tl = gsap.timeline({
        onComplete: () => {
          document.body.classList.remove("loading");
          onDone();
        },
      });
      tl.from(".pl-letter", { yPercent: 110, duration: reduced ? 0 : 0.8, stagger: 0.08, ease: "power4.out" })
        .to(counter, {
          v: 100,
          duration: reduced ? 0 : 1.5,
          ease: "power2.inOut",
          onUpdate: () => setPct(Math.round(counter.v)),
        }, 0)
        .to(".pl-bar", { scaleX: 1, duration: reduced ? 0 : 1.5, ease: "power2.inOut" }, 0)
        .to(".pl-letter", { yPercent: -110, duration: reduced ? 0 : 0.6, stagger: 0.06, ease: "power3.in" }, "+=0.15")
        .to(root.current, { yPercent: -100, duration: reduced ? 0 : 0.9, ease: "power4.inOut" }, "-=0.25");
    }, root);
    return () => {
      ctx.revert();
      document.body.classList.remove("loading");
    };
  }, [onDone, reduced]);

  return (
    <div ref={root} className="fixed inset-0 z-[300] flex flex-col items-center justify-center bg-ink-950">
      <div className="flex overflow-hidden text-7xl font-extrabold tracking-tight md:text-9xl">
        <span className="pl-letter text-grad inline-block">A</span>
        <span className="pl-letter text-grad inline-block">V</span>
      </div>
      <div className="mt-8 h-px w-48 overflow-hidden bg-ink-700">
        <div className="pl-bar h-full origin-left scale-x-0" style={{ background: "var(--grad)" }} />
      </div>
      <p className="mt-4 font-mono text-xs tracking-[0.3em] text-mist-400">{String(pct).padStart(3, "0")}</p>
    </div>
  );
}
