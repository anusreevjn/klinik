import { Suspense, lazy, useCallback, useEffect, useRef, useState } from "react";
import gsap from "gsap";
import { hero, links } from "../data/content";
import { SHAPES } from "../three/shapes";
import { scrollToId } from "../lib/scroll";
import { useMediaQuery } from "../hooks/useMedia";
import Magnetic from "../components/Magnetic";
import Icon from "../components/Icon";

const ParticleMorph = lazy(() => import("../three/ParticleMorph"));

export default function Hero({ ready, reduced }) {
  const root = useRef(null);
  const control = useRef({ request: null, paused: false });
  const resumeTimer = useRef(0);
  const [shape, setShape] = useState(0);
  const isMobile = useMediaQuery("(max-width: 1023px)");
  const isSmall = useMediaQuery("(max-width: 640px)");

  const onShape = useCallback((i) => setShape(i), []);

  useEffect(() => {
    if (!ready) return undefined;
    const ctx = gsap.context(() => {
      if (reduced) {
        gsap.set([".h-badge", ".h-tag", ".h-sub", ".h-statement", ".h-cta > *", ".h-canvas", ".h-meta"], { opacity: 1 });
        return;
      }
      const tl = gsap.timeline({ defaults: { ease: "power4.out" } });
      tl.fromTo(".h-badge", { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8 })
        .fromTo(".h-name .reveal-line > span", { yPercent: 115 }, { yPercent: 0, duration: 1.2, stagger: 0.08 }, "-=0.5")
        .fromTo(".h-tag", { y: 24, opacity: 0 }, { y: 0, opacity: 1, duration: 0.9 }, "-=0.8")
        .fromTo(".h-sub", { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.8 }, "-=0.6")
        .fromTo(".h-statement", { y: 20, opacity: 0, filter: "blur(8px)" }, { y: 0, opacity: 1, filter: "blur(0px)", duration: 1 }, "-=0.6")
        .fromTo(".h-cta > *", { y: 20, opacity: 0 }, { y: 0, opacity: 1, duration: 0.7, stagger: 0.08 }, "-=0.6")
        .fromTo(".h-canvas", { opacity: 0, scale: 0.9 }, { opacity: 1, scale: 1, duration: 1.6, ease: "power2.out" }, 0.1)
        .fromTo(".h-meta", { opacity: 0 }, { opacity: 1, duration: 0.8 }, "-=0.6");
      gsap.to(".h-canvas", {
        yPercent: 18,
        ease: "none",
        scrollTrigger: { trigger: root.current, start: "top top", end: "bottom top", scrub: true },
      });
      gsap.to(".h-copy", {
        yPercent: -10,
        opacity: 0.2,
        ease: "none",
        scrollTrigger: { trigger: root.current, start: "top top", end: "bottom top", scrub: true },
      });
    }, root);
    return () => ctx.revert();
  }, [ready, reduced]);

  useEffect(() => () => window.clearTimeout(resumeTimer.current), []);

  const pick = (i) => {
    control.current.request = i;
    control.current.paused = true;
    window.clearTimeout(resumeTimer.current);
    resumeTimer.current = window.setTimeout(() => {
      control.current.paused = false;
    }, 9000);
  };

  const nameParts = hero.name.split(" ");

  return (
    <section id="home" ref={root} className="relative z-[2] flex min-h-[100svh] items-center overflow-hidden px-4 pb-20 pt-28 md:px-10">
      <div className="pointer-events-none absolute inset-0 -z-10">
        <div className="absolute -left-40 top-10 h-[480px] w-[480px] rounded-full bg-teal/10 blur-[120px]" />
        <div className="absolute -right-20 bottom-0 h-[520px] w-[520px] rounded-full bg-indigo/15 blur-[140px]" />
      </div>

      <div className={`absolute ${isMobile ? "inset-x-0 top-16 h-[60svh] opacity-60" : "right-0 top-0 h-full w-[58%]"}`}>
        <div className="h-canvas h-full w-full opacity-0">
          <Suspense fallback={null}>
            <ParticleMorph
              className="h-full w-full"
              count={isSmall ? 3200 : isMobile ? 4800 : 7500}
              control={control}
              onShape={onShape}
              reduced={reduced}
              scale={isSmall ? 0.72 : isMobile ? 0.85 : 1}
            />
          </Suspense>
        </div>
      </div>

      <div className="container-x relative">
        <div className="h-copy max-w-2xl lg:max-w-[52%]">
          <div className="h-badge glass inline-flex max-w-full items-start gap-3 rounded-2xl px-4 py-2.5 text-left text-xs leading-snug text-mist-200 opacity-0 sm:items-center sm:rounded-full sm:text-sm">
            <span className="pulse-dot mt-1 h-2 w-2 shrink-0 rounded-full bg-teal sm:mt-0" />
            <span>{hero.availability}</span>
          </div>

          <h1 className="h-name mt-8 text-[clamp(3rem,10vw,7.5rem)] font-bold leading-[0.92] tracking-[-0.04em]">
            {nameParts.map((part, i) => (
              <span key={part} className="reveal-line">
                <span className={i === 1 ? "text-grad" : ""}>{part}</span>
              </span>
            ))}
          </h1>

          <p className="h-tag mt-6 text-xl font-medium text-mist-50 opacity-0 md:text-3xl">{hero.tagline}</p>
          <p className="h-sub mt-3 font-mono text-xs text-mist-400 opacity-0 md:text-sm">{hero.subline}</p>

          <p className="h-statement mt-8 max-w-xl text-base leading-relaxed text-mist-200 opacity-0 md:text-lg">{hero.statement}</p>

          <div className="h-cta mt-10 flex flex-wrap items-center gap-3">
            <Magnetic>
              <a
                href="#projects"
                onClick={(e) => {
                  e.preventDefault();
                  scrollToId("projects");
                }}
                className="btn btn-primary"
              >
                View Projects
                <Icon name="arrowRight" className="h-4 w-4" />
              </a>
            </Magnetic>
            <Magnetic>
              <a href={links.resume} target="_blank" rel="noreferrer" className="btn btn-ghost">
                <Icon name="download" className="h-4 w-4" />
                Download Resume
              </a>
            </Magnetic>
            <Magnetic>
              <a
                href="#contact"
                onClick={(e) => {
                  e.preventDefault();
                  scrollToId("contact");
                }}
                className="btn btn-link underline-grow"
              >
                Get in Touch
              </a>
            </Magnetic>
          </div>
        </div>

        <div className="h-meta mt-16 flex flex-wrap items-end justify-between gap-6 opacity-0 lg:mt-24">
          <button
            type="button"
            onClick={() => scrollToId("numbers")}
            className="hidden items-center gap-3 text-xs text-mist-400 transition-colors hover:text-white sm:flex"
            aria-label="Scroll down"
          >
            <span className="flex h-9 w-5 justify-center rounded-full border border-mist-400/40 pt-1.5">
              <span className="scroll-dot h-1.5 w-1 rounded-full bg-teal" />
            </span>
            <span className="font-mono tracking-widest">SCROLL</span>
          </button>

          <div className="flex items-center gap-2 lg:absolute lg:bottom-0 lg:right-0" role="group" aria-label="Particle shape">
            {SHAPES.map((s, i) => (
              <button
                key={s.key}
                type="button"
                onClick={() => pick(i)}
                className={`rounded-full border px-3 py-1.5 font-mono text-[11px] uppercase tracking-wider transition-colors ${
                  shape === i ? "border-teal/60 bg-teal/10 text-white" : "border-mist-400/20 text-mist-400 hover:text-white"
                }`}
                aria-pressed={shape === i}
              >
                {s.label}
              </button>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
