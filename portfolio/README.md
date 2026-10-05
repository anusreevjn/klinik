# Anusree Vijayan Portfolio

Personal portfolio for Anusree Vijayan (AV), Backend and Full Stack Developer.

Built with React, Vite, Tailwind CSS v4, Three.js (React Three Fiber), GSAP ScrollTrigger and Lenis smooth scrolling.

## Run locally

```bash
cd portfolio
npm install
npm run dev
```

Production build goes to `portfolio/dist`:

```bash
npm run build
npm run preview
```

## Before launch: fill in the placeholders

All copy and links live in one file: `src/data/content.js`. Anything left empty is hidden on the site, so nothing broken shows up.

| Placeholder from the content brief | Where to set it |
| --- | --- |
| `[EMAIL]` | `links.email` |
| `[LINKEDIN_URL]` | `links.linkedin` |
| `[GITHUB_URL]` (contact) | `links.github` |
| `[RESUME_PDF_URL]` | Put your PDF at `public/resume.pdf`, or change `links.resume` to a full URL |
| `[X]+ client projects delivered` | `numbers[0].value` (shown once it is a number) |
| `[X] technologies used in production` | `numbers[2].value` (shown once it is a number) |
| `[DEMO_URL]`, `[CASE_STUDY_URL]`, `[GITHUB_URL]` (projects) | `demoUrl`, `caseStudyUrl`, `githubUrl` on each item in `featuredProjects` |
| `[MM/YYYY]` start at 404 Web Services | `experience[0].start` |
| `[YYYY] to [YYYY]` at UTHM | `experience[2].start` and `experience[2].end` |
| SignifyMe one-liner | `moreWork`, the `SignifyMe` entry |
| Open Source Clone Series | set `openSource.show` to `true` and fill `openSource.githubUrl` once the first repo is public |
| `[OG_IMAGE]` | `public/og-image.png` is already generated; replace it if you want a different preview |
| `[YEAR]` | Filled automatically with the current year |

The Project 1 case study outline appears inside its details popup as soon as `caseStudyUrl` is set.

## Animations

- Hero: about 7,500 GPU particles (custom shader) that morph between a globe (web), a phone (mobile) and a neural network (ML dashboards). They push away from the cursor, and a click sends a shockwave through them. The buttons under the hero switch shapes.
- Background: a full-page particle field with cursor magnetism and constellation lines that light up near the pointer, plus parallax on scroll.
- Contact: an "AV" monogram drawn in particles that scatter from the cursor, burst on click and re-form.
- Stack: a draggable 3D tag sphere of every technology.
- Also: preloader, custom cursor, magnetic buttons, glow and tilt cards, scroll reveals, a scroll-drawn experience timeline and a scroll progress bar.

All motion respects the system "reduce motion" setting, and the 3D scene stops rendering when it is off screen.

## Deploy

- Netlify: set the base directory to `portfolio`. `netlify.toml` already sets `npm run build` and `dist`.
- Hostinger or any static host: run `npm run build` and upload the contents of `dist`. Paths are relative, so it also works from a subfolder.

## Credits

Structure and ideas adapted from [Naresh-Khatri/3d-portfolio](https://github.com/Naresh-Khatri/3d-portfolio) (MIT) and [adrianhajdin/3d-portfolio](https://github.com/adrianhajdin/3d-portfolio).
