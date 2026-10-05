import { useCallback, useEffect, useState } from "react";
import gsap from "gsap";
import { ScrollTrigger } from "gsap/ScrollTrigger";
import Lenis from "lenis";
import { setLenis } from "./lib/scroll";
import { useFinePointer, useReducedMotion } from "./hooks/useMedia";
import Preloader from "./components/Preloader";
import Cursor from "./components/Cursor";
import ScrollProgress from "./components/ScrollProgress";
import ParticleField from "./components/ParticleField";
import Navbar from "./components/Navbar";
import Hero from "./sections/Hero";
import Numbers from "./sections/Numbers";
import About from "./sections/About";
import Services from "./sections/Services";
import Projects from "./sections/Projects";
import Stack from "./sections/Stack";
import Experience from "./sections/Experience";
import Process from "./sections/Process";
import Contact from "./sections/Contact";
import Footer from "./sections/Footer";

gsap.registerPlugin(ScrollTrigger);

export default function App() {
  const reduced = useReducedMotion();
  const fine = useFinePointer();
  const [loading, setLoading] = useState(true);
  const done = useCallback(() => setLoading(false), []);

  useEffect(() => {
    if (reduced) return undefined;
    const lenis = new Lenis({ duration: 1.15, smoothWheel: true });
    setLenis(lenis);
    lenis.on("scroll", ScrollTrigger.update);
    const raf = (time) => lenis.raf(time * 1000);
    gsap.ticker.add(raf);
    gsap.ticker.lagSmoothing(0);
    return () => {
      gsap.ticker.remove(raf);
      lenis.destroy();
      setLenis(null);
    };
  }, [reduced]);

  useEffect(() => {
    if (loading) return undefined;
    if (reduced) return undefined;
    const ctx = gsap.context(() => {
      gsap.set(".reveal", { opacity: 0, y: 40 });
      ScrollTrigger.batch(".reveal", {
        start: "top 88%",
        once: true,
        onEnter: (batch) =>
          gsap.to(batch, { opacity: 1, y: 0, duration: 0.9, ease: "power3.out", stagger: 0.08, overwrite: true }),
      });
    });
    const refresh = () => ScrollTrigger.refresh();
    const t = window.setTimeout(refresh, 300);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(refresh);
    return () => {
      window.clearTimeout(t);
      ctx.revert();
    };
  }, [loading, reduced]);

  return (
    <>
      {loading ? <Preloader onDone={done} reduced={reduced} /> : null}
      {fine && !reduced ? <Cursor /> : null}
      <ScrollProgress />
      <ParticleField reduced={reduced} />
      <div className="noise" aria-hidden="true" />
      <Navbar ready={!loading} />
      <main className="relative">
        <Hero ready={!loading} reduced={reduced} />
        <Numbers reduced={reduced} />
        <About />
        <Services />
        <Projects />
        <Stack reduced={reduced} />
        <Experience reduced={reduced} />
        <Process />
        <Contact reduced={reduced} />
      </main>
      <Footer />
    </>
  );
}
