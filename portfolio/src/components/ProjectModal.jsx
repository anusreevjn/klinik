import { useEffect, useRef } from "react";
import { createPortal } from "react-dom";
import gsap from "gsap";
import { getLenis } from "../lib/scroll";
import ProjectVisual from "./ProjectVisual";
import Icon from "./Icon";

export default function ProjectModal({ project, onClose }) {
  const panel = useRef(null);
  const backdrop = useRef(null);
  const closeBtn = useRef(null);

  useEffect(() => {
    const lenis = getLenis();
    if (lenis) lenis.stop();
    document.body.style.overflow = "hidden";
    const prev = document.activeElement;
    gsap.fromTo(backdrop.current, { opacity: 0 }, { opacity: 1, duration: 0.35 });
    gsap.fromTo(panel.current, { y: 60, opacity: 0, scale: 0.97 }, { y: 0, opacity: 1, scale: 1, duration: 0.6, ease: "power4.out" });
    if (closeBtn.current) closeBtn.current.focus();
    const onKey = (e) => {
      if (e.key === "Escape") onClose();
      if (e.key === "Tab" && panel.current) {
        const f = panel.current.querySelectorAll("a[href], button");
        if (!f.length) return;
        const first = f[0];
        const last = f[f.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first.focus();
        }
      }
    };
    window.addEventListener("keydown", onKey);
    return () => {
      window.removeEventListener("keydown", onKey);
      if (lenis) lenis.start();
      document.body.style.overflow = "";
      if (prev && prev.focus) prev.focus();
    };
  }, [onClose]);

  const linkList = [
    { href: project.demoUrl, label: "Live demo" },
    { href: project.caseStudyUrl, label: "Case study" },
    { href: project.githubUrl, label: "GitHub" },
  ].filter((l) => l.href);

  return createPortal(
    <div className="fixed inset-0 z-[150] flex items-end justify-center sm:items-center sm:p-6" role="dialog" aria-modal="true" aria-labelledby={`pm-${project.id}`}>
      <div ref={backdrop} className="absolute inset-0 bg-ink-950/80 backdrop-blur-md" onClick={onClose} />
      <div
        ref={panel}
        className="modal-scroll glass relative max-h-[92svh] w-full max-w-3xl overflow-y-auto rounded-t-3xl sm:rounded-3xl"
        data-lenis-prevent
      >
        <div className="relative h-44 border-b border-mist-400/10 bg-ink-950/60 sm:h-56">
          <ProjectVisual id={`m-${project.id}`} kind={project.visual} accent={project.accent} />
          <button
            ref={closeBtn}
            type="button"
            onClick={onClose}
            className="absolute right-4 top-4 flex h-10 w-10 items-center justify-center rounded-full border border-mist-400/20 bg-ink-900/80 text-mist-50 transition-colors hover:border-teal/60"
            aria-label="Close project details"
          >
            <Icon name="close" className="h-5 w-5" />
          </button>
        </div>

        <div className="p-6 sm:p-10">
          <p className="eyebrow">
            <span className="text-teal">{project.number}</span> / {project.type}
          </p>
          <h3 id={`pm-${project.id}`} className="mt-3 text-2xl font-semibold leading-tight tracking-tight sm:text-4xl">
            {project.title}
          </h3>
          <p className="mt-5 leading-relaxed text-mist-200 sm:text-lg">{project.summary}</p>

          <div className="mt-8 flex flex-wrap gap-2">
            {project.stack.map((t) => (
              <span key={t} className="tag">
                {t}
              </span>
            ))}
          </div>

          <h4 className="eyebrow mt-10">Highlights</h4>
          <ul className="mt-4 space-y-3">
            {project.highlights.map((h) => (
              <li key={h} className="flex gap-3 leading-relaxed text-mist-200">
                <Icon name="check" className="mt-1 h-4 w-4 shrink-0 text-teal" strokeWidth={2.2} />
                <span>{h}</span>
              </li>
            ))}
          </ul>

          {project.caseStudyUrl && project.caseStudySections.length ? (
            <>
              <h4 className="eyebrow mt-10">Case study</h4>
              <ol className="mt-4 grid gap-2 sm:grid-cols-2">
                {project.caseStudySections.map((c, i) => (
                  <li key={c} className="flex items-center gap-3 rounded-xl border border-mist-400/10 px-4 py-3 text-sm text-mist-200">
                    <span className="font-mono text-xs text-teal">{i + 1}</span>
                    {c}
                  </li>
                ))}
              </ol>
            </>
          ) : null}

          {linkList.length ? (
            <div className="mt-10 flex flex-wrap gap-3">
              {linkList.map((l, i) => (
                <a key={l.label} href={l.href} target="_blank" rel="noreferrer" className={`btn ${i === 0 ? "btn-primary" : "btn-ghost"}`}>
                  {l.label}
                  <Icon name="arrowUpRight" className="h-4 w-4" />
                </a>
              ))}
            </div>
          ) : null}
        </div>
      </div>
    </div>,
    document.body
  );
}
