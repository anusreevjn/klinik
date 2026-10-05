import { useEffect, useRef, useState } from "react";
import gsap from "gsap";
import { links, navItems } from "../data/content";
import { scrollToId, getLenis } from "../lib/scroll";
import Icon from "./Icon";

export default function Navbar({ ready }) {
  const [scrolled, setScrolled] = useState(false);
  const [active, setActive] = useState("home");
  const [open, setOpen] = useState(false);
  const header = useRef(null);
  const menu = useRef(null);

  useEffect(() => {
    const onScroll = () => setScrolled(window.scrollY > 24);
    onScroll();
    window.addEventListener("scroll", onScroll, { passive: true });
    return () => window.removeEventListener("scroll", onScroll);
  }, []);

  useEffect(() => {
    const sections = navItems.map((n) => document.getElementById(n.id)).filter(Boolean);
    const io = new IntersectionObserver(
      (entries) => {
        entries.forEach((e) => {
          if (e.isIntersecting) setActive(e.target.id);
        });
      },
      { rootMargin: "-45% 0px -50% 0px" }
    );
    sections.forEach((s) => io.observe(s));
    return () => io.disconnect();
  }, []);

  useEffect(() => {
    if (!ready || !header.current) return;
    gsap.fromTo(header.current, { y: -40, opacity: 0 }, { y: 0, opacity: 1, duration: 0.9, ease: "power3.out", delay: 0.2 });
  }, [ready]);

  useEffect(() => {
    const lenis = getLenis();
    if (open) {
      if (lenis) lenis.stop();
      document.body.style.overflow = "hidden";
      if (menu.current) {
        gsap.fromTo(menu.current, { clipPath: "circle(0% at 100% 0%)" }, { clipPath: "circle(150% at 100% 0%)", duration: 0.7, ease: "power3.inOut" });
        gsap.fromTo(menu.current.querySelectorAll(".m-item"), { y: 40, opacity: 0 }, { y: 0, opacity: 1, stagger: 0.05, delay: 0.25, duration: 0.5, ease: "power3.out" });
      }
    } else {
      if (lenis) lenis.start();
      document.body.style.overflow = "";
    }
  }, [open]);

  useEffect(() => {
    const onKey = (e) => {
      if (e.key === "Escape") setOpen(false);
    };
    window.addEventListener("keydown", onKey);
    return () => window.removeEventListener("keydown", onKey);
  }, []);

  const go = (id) => (e) => {
    e.preventDefault();
    setOpen(false);
    window.setTimeout(() => scrollToId(id), open ? 50 : 0);
  };

  return (
    <>
      <header
        ref={header}
        className={`fixed inset-x-0 top-0 z-[110] opacity-0 transition-[background,border-color,padding] duration-500 ${
          scrolled ? "border-b border-mist-400/10 bg-ink-950/70 py-3 backdrop-blur-xl" : "border-b border-transparent py-5"
        }`}
      >
        <div className="container-x flex items-center justify-between px-4 md:px-10">
          <a href="#home" onClick={go("home")} className="group flex items-center gap-3" aria-label="Anusree Vijayan, back to top">
            <span className="grad-border flex h-10 w-10 items-center justify-center rounded-xl bg-ink-900 text-sm font-extrabold">
              <span className="text-grad">AV</span>
            </span>
            <span className="hidden text-sm font-semibold tracking-tight text-mist-50 sm:block">Anusree Vijayan</span>
          </a>

          <nav className="hidden items-center gap-7 lg:flex" aria-label="Primary">
            {navItems.map((n) => (
              <a
                key={n.id}
                href={`#${n.id}`}
                onClick={go(n.id)}
                className={`underline-grow text-sm transition-colors ${active === n.id ? "active text-white" : "text-mist-200 hover:text-white"}`}
              >
                {n.label}
              </a>
            ))}
            <a href={links.resume} target="_blank" rel="noreferrer" className="btn btn-ghost !py-2 !px-4 text-sm">
              Resume
              <Icon name="arrowUpRight" className="h-4 w-4" />
            </a>
          </nav>

          <button
            type="button"
            className="grad-border flex h-11 w-11 items-center justify-center rounded-full bg-ink-900 lg:hidden"
            onClick={() => setOpen((v) => !v)}
            aria-label={open ? "Close menu" : "Open menu"}
            aria-expanded={open}
          >
            <Icon name={open ? "close" : "menu"} className="h-5 w-5" />
          </button>
        </div>
      </header>

      {open ? (
        <div ref={menu} className="fixed inset-0 z-[105] flex flex-col bg-ink-950/95 px-6 pb-10 pt-28 backdrop-blur-xl lg:hidden" data-lenis-prevent>
          <nav className="flex flex-1 flex-col gap-2" aria-label="Mobile">
            {navItems.map((n, i) => (
              <a
                key={n.id}
                href={`#${n.id}`}
                onClick={go(n.id)}
                className={`m-item flex items-baseline gap-4 border-b border-mist-400/10 py-3 text-3xl font-semibold ${active === n.id ? "text-white" : "text-mist-200"}`}
              >
                <span className="font-mono text-xs text-teal">{String(i + 1).padStart(2, "0")}</span>
                {n.label}
              </a>
            ))}
            <a href={links.resume} target="_blank" rel="noreferrer" className="m-item flex items-baseline gap-4 py-3 text-3xl font-semibold text-mist-200">
              <span className="font-mono text-xs text-teal">{String(navItems.length + 1).padStart(2, "0")}</span>
              Resume
            </a>
          </nav>
          <p className="m-item font-mono text-xs text-mist-400">Discord {links.discord}</p>
        </div>
      ) : null}
    </>
  );
}
