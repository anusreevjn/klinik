import { useEffect, useRef } from "react";
import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import { experience } from "../data/content";
import SectionHeading from "../components/SectionHeading";
import { useGlow } from "../hooks/useGlow";

gsap.registerPlugin(ScrollTrigger);

const kindLabel = { work: "Work", hackathon: "Hackathon", education: "Education" };

function period(e) {
  if (e.start && e.end) return `${e.start} to ${e.end}`;
  return e.start || e.end || "";
}

export default function Experience({ reduced }) {
  const root = useRef(null);
  const glow = useGlow();

  useEffect(() => {
    if (reduced) return undefined;
    const ctx = gsap.context(() => {
      gsap.fromTo(
        ".tl-fill",
        { scaleY: 0 },
        {
          scaleY: 1,
          ease: "none",
          scrollTrigger: { trigger: ".tl-track", start: "top 70%", end: "bottom 60%", scrub: true },
        }
      );
      gsap.utils.toArray(".tl-dot").forEach((dot) => {
        gsap.fromTo(
          dot,
          { scale: 0.4, opacity: 0.3 },
          { scale: 1, opacity: 1, duration: 0.5, scrollTrigger: { trigger: dot, start: "top 70%", toggleActions: "play none none reverse" } }
        );
      });
    }, root);
    return () => ctx.revert();
  }, [reduced]);

  return (
    <section id="experience" ref={root} className="section">
      <div className="container-x">
        <SectionHeading index="05" eyebrow="Experience" title="Experience Timeline" />
        <div className="tl-track relative">
          <div className="absolute bottom-0 left-[11px] top-0 w-px bg-mist-400/15 md:left-1/2 md:-translate-x-1/2" />
          <div className="tl-fill timeline-line absolute bottom-0 left-[11px] top-0 w-px origin-top md:left-1/2 md:-translate-x-1/2" />

          <div className="space-y-14 md:space-y-24">
            {experience.map((e, i) => {
              const right = i % 2 === 1;
              return (
                <div key={e.role} className="relative grid md:grid-cols-2 md:gap-16">
                  <span className="tl-dot absolute left-0 top-8 flex h-[23px] w-[23px] items-center justify-center rounded-full border border-teal/50 bg-ink-950 md:left-1/2 md:-translate-x-1/2">
                    <span className="h-2 w-2 rounded-full bg-teal" />
                  </span>
                  <div className={`pl-12 md:pl-0 ${right ? "md:col-start-2" : "md:text-right"}`}>
                    <div onMouseMove={glow} className="reveal glow-card glass rounded-3xl p-7 text-left md:p-9">
                      <div className="flex flex-wrap items-center gap-3">
                        <span className="tag">{kindLabel[e.kind]}</span>
                        {period(e) ? <span className="font-mono text-xs text-mist-400">{period(e)}</span> : null}
                      </div>
                      <h3 className="mt-5 text-xl font-semibold leading-snug tracking-tight md:text-2xl">{e.role}</h3>
                      <p className="mt-1 text-grad font-medium">{e.org}</p>
                      <p className="mt-4 leading-relaxed text-mist-200">{e.body}</p>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
        </div>
      </div>
    </section>
  );
}
