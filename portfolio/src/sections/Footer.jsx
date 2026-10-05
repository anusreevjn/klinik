import { scrollToId } from "../lib/scroll";
import Icon from "../components/Icon";

export default function Footer() {
  return (
    <footer className="relative z-[2] px-4 pb-10 md:px-10">
      <div className="container-x flex flex-col items-center justify-between gap-6 border-t border-mist-400/10 pt-8 sm:flex-row">
        <p className="text-sm text-mist-400">© {new Date().getFullYear()} Anusree Vijayan. Built and deployed by me.</p>
        <button
          type="button"
          onClick={() => scrollToId("home")}
          className="group inline-flex items-center gap-2 font-mono text-xs uppercase tracking-widest text-mist-400 transition-colors hover:text-white"
        >
          Back to top
          <span className="flex h-8 w-8 items-center justify-center rounded-full border border-mist-400/20 transition-colors group-hover:border-teal/60">
            <Icon name="arrowDown" className="h-4 w-4 rotate-180" />
          </span>
        </button>
      </div>
    </footer>
  );
}
