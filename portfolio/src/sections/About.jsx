import { about } from "../data/content";
import SectionHeading from "../components/SectionHeading";
import Icon from "../components/Icon";
import { useGlow } from "../hooks/useGlow";

const codeLines = [
  [["k", "const"], ["p", " av "], ["o", "= {"]],
  [["p", "  role"], ["o", ": "], ["s", "\"Backend and Full Stack Developer\""], ["o", ","]],
  [["p", "  studio"], ["o", ": "], ["s", "\"404 Web Services\""], ["o", ","]],
  [["p", "  university"], ["o", ": "], ["s", "\"UTHM\""], ["o", ","]],
  [["p", "  ships"], ["o", ": ["], ["s", "\"web\""], ["o", ", "], ["s", "\"mobile\""], ["o", ", "], ["s", "\"ML dashboards\""], ["o", "],"]],
  [["p", "  openTo"], ["o", ": ["], ["s", "\"internships\""], ["o", ", "], ["s", "\"junior roles\""], ["o", "],"]],
  [["o", "};"]],
];

const tone = {
  k: "text-pink",
  p: "text-mist-50",
  o: "text-mist-400",
  s: "text-teal",
};

export default function About() {
  const glow = useGlow();
  return (
    <section id="about" className="section">
      <div className="container-x grid grid-cols-1 gap-14 lg:grid-cols-[1.15fr_0.85fr] lg:gap-20">
        <div className="min-w-0">
          <SectionHeading index="01" eyebrow="About" title={about.greeting} />
          <div className="space-y-6 text-base leading-relaxed text-mist-200 md:text-lg">
            {about.paragraphs.map((p) => (
              <p key={p.slice(0, 24)} className="reveal">
                {p}
              </p>
            ))}
          </div>
        </div>

        <div className="flex min-w-0 flex-col gap-6 lg:pt-28">
          <div onMouseMove={glow} className="reveal glow-card glass rounded-3xl p-1">
            <div className="flex items-center gap-2 px-4 py-3">
              <span className="h-2.5 w-2.5 rounded-full bg-pink/70" />
              <span className="h-2.5 w-2.5 rounded-full bg-amber/70" />
              <span className="h-2.5 w-2.5 rounded-full bg-teal/70" />
              <span className="ml-3 font-mono text-[11px] text-mist-400">whoami.js</span>
            </div>
            <pre className="overflow-x-auto rounded-[20px] bg-ink-950/80 p-5 font-mono text-[12px] leading-7 md:text-[13px]">
              {codeLines.map((line, i) => (
                <div key={i} className="flex">
                  <span className="mr-4 w-4 select-none text-right text-mist-400/40">{i + 1}</span>
                  <span>
                    {line.map(([t, v], j) => (
                      <span key={j} className={tone[t]}>
                        {v}
                      </span>
                    ))}
                  </span>
                </div>
              ))}
            </pre>
          </div>

          <dl className="grid grid-cols-1 gap-3 sm:grid-cols-2">
            {about.facts.map((f) => (
              <div key={f.label} onMouseMove={glow} className="reveal glow-card glass rounded-2xl p-5">
                <dt className="eyebrow flex items-center gap-2">
                  {f.label === "Based in" ? <Icon name="pin" className="h-3.5 w-3.5 text-teal" /> : <Icon name="spark" className="h-3.5 w-3.5 text-teal" />}
                  {f.label}
                </dt>
                <dd className="mt-2 text-sm font-medium text-mist-50">{f.value}</dd>
              </div>
            ))}
          </dl>
        </div>
      </div>
    </section>
  );
}
