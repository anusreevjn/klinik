import { useCallback, useState } from "react";
import { featuredProjects, moreWork, openSource, projectsNote } from "../data/content";
import SectionHeading from "../components/SectionHeading";
import ProjectVisual from "../components/ProjectVisual";
import ProjectModal from "../components/ProjectModal";
import Icon from "../components/Icon";
import { useGlow } from "../hooks/useGlow";

function ProjectCard({ project, onOpen, wide }) {
  const glow = useGlow();
  const onMove = (e) => {
    glow(e);
    if (e.pointerType !== "mouse") return;
    const el = e.currentTarget;
    const r = el.getBoundingClientRect();
    const px = (e.clientX - r.left) / r.width - 0.5;
    const py = (e.clientY - r.top) / r.height - 0.5;
    el.style.transform = `perspective(1100px) rotateX(${(-py * 4).toFixed(2)}deg) rotateY(${(px * 5).toFixed(2)}deg) translateY(-4px)`;
  };
  const onLeave = (e) => {
    e.currentTarget.style.transform = "";
  };
  const shown = project.stack.slice(0, 4);
  const extra = project.stack.length - shown.length;

  return (
    <article
      onPointerMove={onMove}
      onPointerLeave={onLeave}
      className={`reveal glow-card glass group flex flex-col overflow-hidden rounded-3xl transition-transform duration-300 ease-out will-change-transform ${
        wide ? "lg:col-span-2 lg:flex-row" : ""
      }`}
    >
      <button
        type="button"
        onClick={() => onOpen(project)}
        className={`relative block shrink-0 overflow-hidden border-mist-400/10 bg-ink-950/50 text-left ${
          wide ? "aspect-[5/3] border-b lg:aspect-auto lg:w-[52%] lg:border-b-0 lg:border-r" : "aspect-[5/3] border-b"
        }`}
        data-cursor="Open"
        aria-label={`Open details for ${project.title}`}
      >
        <div className="absolute inset-0 transition-transform duration-700 ease-out group-hover:scale-[1.04]">
          <ProjectVisual id={project.id} kind={project.visual} accent={project.accent} />
        </div>
        <span className="absolute left-5 top-5 font-mono text-xs text-mist-400">{project.number}</span>
      </button>

      <div className={`flex flex-1 flex-col p-6 md:p-8 ${wide ? "lg:justify-center lg:p-10" : ""}`}>
        <p className="eyebrow !text-[0.68rem]">{project.type}</p>
        <h3 className={`mt-3 font-semibold leading-tight tracking-tight ${wide ? "text-2xl md:text-4xl" : "text-2xl md:text-[1.7rem]"}`}>
          {project.title}
        </h3>
        <p className="mt-4 leading-relaxed text-mist-200">{project.summary}</p>

        <div className="mt-6 flex items-baseline gap-3 border-t border-mist-400/10 pt-5">
          <span className="text-grad text-3xl font-semibold tracking-tight">{project.stat.value}</span>
          <span className="text-sm text-mist-400">{project.stat.label}</span>
        </div>

        <div className="mt-6 flex flex-wrap gap-2">
          {shown.map((t) => (
            <span key={t} className="tag">
              {t}
            </span>
          ))}
          {extra > 0 ? <span className="tag !text-mist-400">+{extra}</span> : null}
        </div>

        <div className="mt-auto pt-8">
          <button
            type="button"
            onClick={() => onOpen(project)}
            className="inline-flex items-center gap-2 text-sm font-semibold text-mist-50 transition-colors hover:text-teal"
          >
            View details
            <Icon name="arrowRight" className="h-4 w-4 transition-transform group-hover:translate-x-1" />
          </button>
        </div>
      </div>
    </article>
  );
}

export default function Projects() {
  const [open, setOpen] = useState(null);
  const glow = useGlow();
  const close = useCallback(() => setOpen(null), []);

  return (
    <section id="projects" className="section">
      <div className="container-x">
        <SectionHeading index="03" eyebrow="Projects" title="Featured Projects" intro={projectsNote} />

        <div className="grid gap-6 lg:grid-cols-2">
          {featuredProjects.map((p, i) => (
            <ProjectCard key={p.id} project={p} onOpen={setOpen} wide={i === 0} />
          ))}
          {openSource.show ? (
            <article className="reveal glass flex flex-col justify-between rounded-3xl p-8 lg:col-span-2">
              <div>
                <p className="eyebrow">{openSource.type}</p>
                <h3 className="mt-3 text-2xl font-semibold tracking-tight md:text-3xl">{openSource.title}</h3>
                <p className="mt-4 max-w-2xl leading-relaxed text-mist-200">{openSource.summary}</p>
              </div>
              {openSource.githubUrl ? (
                <a href={openSource.githubUrl} target="_blank" rel="noreferrer" className="btn btn-ghost mt-6 self-start">
                  <Icon name="github" className="h-4 w-4" />
                  GitHub
                </a>
              ) : null}
            </article>
          ) : null}
        </div>

        <div className="mt-28">
          <div className="reveal mb-10 flex flex-wrap items-end justify-between gap-4">
            <h3 className="text-3xl font-semibold tracking-tight md:text-4xl">More Client Work</h3>
            <p className="font-mono text-xs text-mist-400">{moreWork.length} projects</p>
          </div>
          <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            {moreWork.map((w) => (
              <article key={w.name} onMouseMove={glow} className="reveal glow-card glass flex flex-col rounded-2xl p-6 transition-transform duration-300 hover:-translate-y-1">
                <h4 className="text-lg font-semibold tracking-tight">{w.name}</h4>
                <p className="mt-2 flex-1 text-sm leading-relaxed text-mist-200">{w.line}</p>
                <div className="mt-5 flex flex-wrap gap-2">
                  {w.tags.map((t) => (
                    <span key={t} className="tag">
                      {t}
                    </span>
                  ))}
                </div>
              </article>
            ))}
          </div>
        </div>
      </div>

      {open ? <ProjectModal project={open} onClose={close} /> : null}
    </section>
  );
}
