import { useEffect, useRef } from "react";
import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { numbers, stack } from "../data/content";

gsap.registerPlugin(ScrollTrigger);

const marqueeItems = stack.flatMap((g) => g.items);

export default function Numbers({ reduced }) {
  const root = useRef(null);
  const items = numbers.filter((n) => n.value !== null && n.value !== undefined);

  useEffect(() => {
    const ctx = gsap.context(() => {
      root.current.querySelectorAll("[data-count]").forEach((el) => {
        const end = parseFloat(el.dataset.count);
        const decimals = parseInt(el.dataset.decimals, 10) || 0;
        if (reduced) {
          el.textContent = end.toFixed(decimals);
          return;
        }
        const o = { v: 0 };
        gsap.to(o, {
          v: end,
          duration: 2,
          ease: "power3.out",
          scrollTrigger: { trigger: el, start: "top 85%", once: true },
          onUpdate: () => {
            el.textContent = o.v.toFixed(decimals);
          },
        });
      });
    }, root);
    return () => ctx.revert();
  }, [reduced]);

  return (
    <section id="numbers" ref={root} className="relative z-[2] px-4 pb-10 md:px-10">
      <div className="container-x">
        <div className="glass grid grid-cols-1 divide-y divide-mist-400/10 overflow-hidden rounded-3xl sm:grid-cols-[repeat(auto-fit,minmax(180px,1fr))] sm:divide-x sm:divide-y-0">
          {items.map((n) => (
            <div key={n.label} className="reveal flex flex-col justify-center px-7 py-8">
              <p className="text-5xl font-semibold tracking-tight md:text-6xl">
                <span className="text-grad">
                  {n.prefix || ""}
                  <span data-count={n.value} data-decimals={n.decimals}>
                    {(0).toFixed(n.decimals)}
                  </span>
                  {n.suffix || ""}
                </span>
              </p>
              <p className="mt-3 text-sm leading-snug text-mist-200">{n.label}</p>
            </div>
          ))}
        </div>
      </div>

      <div className="marquee-mask mt-14 overflow-hidden" aria-hidden="true">
        <div className="marquee gap-3">
          {[...marqueeItems, ...marqueeItems].map((t, i) => (
            <span key={`${t}-${i}`} className="mx-1.5 whitespace-nowrap rounded-full border border-mist-400/15 px-4 py-2 font-mono text-xs text-mist-400">
              {t}
            </span>
          ))}
        </div>
      </div>
    </section>
  );
}
