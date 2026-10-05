import { stack } from "../data/content";
import SectionHeading from "../components/SectionHeading";
import TagSphere from "../components/TagSphere";
import { useGlow } from "../hooks/useGlow";

const sphereItems = [...new Set(stack.flatMap((g) => g.items))];

export default function Stack({ reduced }) {
  const glow = useGlow();
  return (
    <section id="stack" className="section">
      <div className="container-x">
        <SectionHeading index="04" eyebrow="Stack" title="Tech Stack" />
        <div className="grid grid-cols-1 items-center gap-12 lg:grid-cols-[0.95fr_1.05fr]">
          <div className="reveal relative mx-auto aspect-square w-full max-w-[520px]">
            <div className="pointer-events-none absolute inset-[12%] rounded-full bg-indigo/10 blur-3xl" />
            <div className="pointer-events-none absolute inset-[6%] rounded-full border border-mist-400/10" />
            <div className="pointer-events-none absolute inset-[22%] rounded-full border border-dashed border-mist-400/10" />
            <TagSphere items={sphereItems} reduced={reduced} className="h-full w-full" />
          </div>

          <div className="grid min-w-0 gap-4 sm:grid-flow-dense sm:grid-cols-2">
            {stack.map((g, i) => (
              <div
                key={g.group}
                onMouseMove={glow}
                className={`reveal glow-card glass rounded-2xl p-6 ${i === 1 || i === 3 || i === 4 ? "sm:col-span-2" : ""}`}
              >
                <h3 className="eyebrow">{g.group}</h3>
                <div className="mt-4 flex flex-wrap gap-2">
                  {g.items.map((t) => (
                    <span key={t} className="tag !text-[0.74rem] !text-mist-50">
                      {t}
                    </span>
                  ))}
                </div>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  );
}
