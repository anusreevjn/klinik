import { services } from "../data/content";
import SectionHeading from "../components/SectionHeading";
import Icon from "../components/Icon";
import { useGlow } from "../hooks/useGlow";

const accents = ["text-teal", "text-indigo", "text-pink", "text-amber"];

export default function Services() {
  const glow = useGlow();
  return (
    <section id="services" className="section">
      <div className="container-x">
        <SectionHeading index="02" eyebrow="Services" title="What I Do" />
        <div className="grid gap-5 md:grid-cols-2">
          {services.map((s, i) => (
            <article
              key={s.title}
              onMouseMove={glow}
              className="reveal glow-card glass group rounded-3xl p-7 transition-transform duration-500 hover:-translate-y-1 md:p-9"
            >
              <div className="flex items-start justify-between">
                <span className={`grad-border flex h-14 w-14 items-center justify-center rounded-2xl bg-ink-900 ${accents[i % accents.length]}`}>
                  <Icon name={s.icon} className="h-6 w-6" />
                </span>
                <span className="font-mono text-xs text-mist-400">{String(i + 1).padStart(2, "0")}</span>
              </div>
              <h3 className="mt-8 text-2xl font-semibold tracking-tight md:text-3xl">{s.title}</h3>
              <p className="mt-4 leading-relaxed text-mist-200">{s.body}</p>
            </article>
          ))}
        </div>
      </div>
    </section>
  );
}
