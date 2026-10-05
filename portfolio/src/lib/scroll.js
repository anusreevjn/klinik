let lenis = null;

export function setLenis(instance) {
  lenis = instance;
}

export function getLenis() {
  return lenis;
}

export function scrollToId(id) {
  const el = document.getElementById(id);
  if (!el) return;
  if (lenis) {
    lenis.scrollTo(el, { offset: id === "home" ? 0 : -72, duration: 1.4 });
  } else {
    const top = el.getBoundingClientRect().top + window.scrollY - (id === "home" ? 0 : 72);
    window.scrollTo({ top, behavior: "smooth" });
  }
}
