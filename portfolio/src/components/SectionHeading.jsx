export default function SectionHeading({ index, eyebrow, title, intro, align = "left" }) {
  const centered = align === "center";
  return (
    <div className={`reveal mb-14 md:mb-20 ${centered ? "mx-auto max-w-3xl text-center" : "max-w-3xl"}`}>
      <p className={`eyebrow flex items-center gap-3 ${centered ? "justify-center" : ""}`}>
        <span className="text-teal">{index}</span>
        <span className="h-px w-10 bg-mist-400/40" />
        <span>{eyebrow}</span>
      </p>
      <h2 className="mt-5 text-4xl font-semibold leading-[1.05] tracking-tight md:text-6xl">{title}</h2>
      {intro ? <p className="mt-6 text-base leading-relaxed text-mist-200 md:text-lg">{intro}</p> : null}
    </div>
  );
}
