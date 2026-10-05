import { process } from "../data/content";
import SectionHeading from "../components/SectionHeading";
import { useGlow } from "../hooks/useGlow";

export default function Process() {
  const glow = useGlow();
  return (
    <section id="process" className="section">
      <div className="container-x">
        <SectionHeading index="06" eyebrow="Process" title="How I Work" />
        <ol className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
          {process.map((p, i) => (
            <li key={p.title} onMouseMove={glow} className="reveal glow-card glass group relative flex flex-col rounded-3xl p-6 lg:min-h-[280px]">
              <span className="text-grad text-5xl font-bold tracking-tight opacity-90 transition-opacity group-hover:opacity-100">{i + 1}</span>
              <h3 className="mt-auto pt-10 text-lg font-semibold tracking-tight">{p.title}</h3>
              <p className="mt-2 text-sm leading-relaxed text-mist-200">{p.body}</p>
            </li>
          ))}
        </ol>
      </div>
    </section>
  );
}
