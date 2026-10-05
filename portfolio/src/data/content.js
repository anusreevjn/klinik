export const links = {
  email: "",
  linkedin: "",
  github: "",
  discord: "@everettian",
  resume: "resume.pdf",
};

export const navItems = [
  { id: "home", label: "Home" },
  { id: "about", label: "About" },
  { id: "services", label: "Services" },
  { id: "projects", label: "Projects" },
  { id: "stack", label: "Stack" },
  { id: "experience", label: "Experience" },
  { id: "contact", label: "Contact" },
];

export const hero = {
  name: "Anusree Vijayan",
  tagline: "Backend and Full Stack Developer",
  subline: "Software Engineering @ UTHM | Founder, 404 Web Services",
  statement:
    "I build and ship production web apps, APIs, mobile apps and ML dashboards for real clients, from database schema to live deployment.",
  availability:
    "Open to internships and junior roles in backend, frontend and deployment engineering.",
};

export const numbers = [
  { value: null, decimals: 0, suffix: "+", label: "client projects delivered" },
  { value: 3, decimals: 0, suffix: "", label: "platforms shipped: web, mobile, ML dashboards" },
  { value: null, decimals: 0, suffix: "", label: "technologies used in production" },
  { value: 3.45, decimals: 2, prefix: "", suffix: "", label: "CGPA" },
  { value: 3, decimals: 0, prefix: "x", suffix: "", label: "Dean's List" },
];

export const about = {
  greeting: "Hi, I'm AV.",
  paragraphs: [
    "I'm a Software Engineering student at Universiti Tun Hussein Onn Malaysia with a foundation in physics and mathematics. Alongside my degree, I run 404 Web Services, a freelance development studio serving students and SMEs across Malaysia.",
    "At 404 Web Services I own the full cycle of every project: gathering requirements, designing the database, building the backend and frontend, deploying to production, and supporting the client after handover. I've shipped healthcare management systems, cross-platform marketplace apps and machine learning dashboards that real people use.",
    "I care about software that actually works in production, not just in a demo. That means clean schemas, honest benchmarks, safe migrations and documentation a client can follow without me.",
    "Right now I'm looking for roles where I can grow as a backend, frontend or deployment engineer on a team that ships.",
  ],
  facts: [
    { label: "Based in", value: "Batu Pahat, Johor, Malaysia" },
    { label: "Studying", value: "Software Engineering, UTHM" },
    { label: "Running", value: "404 Web Services" },
    { label: "Focus", value: "Backend, frontend, deployment" },
  ],
};

export const services = [
  {
    title: "Backend Development",
    icon: "server",
    body: "REST APIs, authentication, role-based access control and relational database design using PHP (PDO, Laravel) and Python (Flask). MySQL, Firebase and SQLite.",
  },
  {
    title: "Frontend Development",
    icon: "layout",
    body: "Responsive dashboards, Progressive Web Apps and cross-platform mobile apps with Flutter. Interfaces designed for real users, not just screenshots.",
  },
  {
    title: "Deployment and Ops",
    icon: "cloud",
    body: "Production deployments on Hostinger and Netlify, Firebase project setup and security rules, Cloudflare R2 and Cloudinary media storage, idempotent SQL migrations, domain setup and client handover.",
  },
  {
    title: "Machine Learning Integration",
    icon: "brain",
    body: "scikit-learn models with SHAP explainability, served through web dashboards with batch processing and report exports.",
  },
];

export const projectsNote =
  "Client names and identifying details are kept private.";

export const featuredProjects = [
  {
    id: "dropout",
    number: "01",
    title: "Student Dropout Prediction Platform",
    type: "Client project (details anonymized)",
    stack: ["Python", "Flask", "scikit-learn (Random Forest)", "SHAP", "Google OAuth", "Groq API", "Gmail SMTP"],
    summary:
      "A web platform that predicts which students are at risk of dropping out and explains why, so educators can act early.",
    highlights: [
      "Prediction model reaching about 98.9% accuracy on a 466-student dataset",
      "SHAP explanations for every individual prediction",
      "AI advisor that suggests interventions per student",
      "Batch CSV screening for whole cohorts plus cohort analytics",
      "Per-student PDF report export and email notifications",
      "Google OAuth sign-in",
      "19 UI iterations driven by client feedback",
    ],
    stat: { value: "~98.9%", label: "accuracy on a 466-student dataset" },
    caseStudySections: [
      "The Problem",
      "The Data",
      "The Model and Explainability",
      "Architecture",
      "Results",
      "What I'd Improve Next",
    ],
    demoUrl: "",
    caseStudyUrl: "",
    githubUrl: "",
    accent: ["#5eead4", "#818cf8"],
    visual: "ml",
  },
  {
    id: "rehab",
    number: "02",
    title: "Rehabilitation Care Management System",
    type: "Client project (details anonymized)",
    stack: ["PHP 8", "PDO", "MySQL", "PWA", "Cloudflare R2", "Google Calendar API", "SMTP", "Hostinger"],
    summary:
      "A healthcare web system that connects admins, nurses and patients to manage rehabilitation programmes, assessments and pain monitoring.",
    highlights: [
      "Three role portals (admin, nurse, patient) with session authentication and hashed passwords",
      "Full CRUD across nurses, patients, exercises, appointments, assessments and pain logs",
      "Exercise video library supporting three media sources: Cloudflare R2 upload, YouTube link and local upload",
      "Patient pain monitoring based on the DOSH ergonomics body discomfort chart",
      "Google Calendar sync and email reminders for appointments",
      "Installable as a Progressive Web App",
      "Install script plus idempotent migration SQL for safe upgrades",
      "Deployed to production on Hostinger",
    ],
    stat: { value: "3", label: "role portals: admin, nurse, patient" },
    caseStudySections: [],
    demoUrl: "",
    caseStudyUrl: "",
    githubUrl: "",
    accent: ["#818cf8", "#f472b6"],
    visual: "roles",
  },
  {
    id: "epasar",
    number: "03",
    title: "ePasar, Local Products Marketplace App",
    type: "Client project",
    stack: ["Flutter", "Dart", "Firebase Auth", "Cloud Firestore", "Cloudinary", "Node.js (Firebase Admin SDK)", "React (prototype)"],
    summary:
      "A cross-platform app for discovering local products and businesses in a Malaysian town, with separate experiences for buyers, sellers and admins.",
    highlights: [
      "Three roles (buyer, seller, admin) with email/password and Google sign-in",
      "Built a clickable React web prototype first for fast client sign-off, then the production Flutter app",
      "Bilingual interface in Malay and English",
      "Firestore database in the asia-southeast1 region with production security rules",
      "Image storage on Cloudinary to keep the project on Firebase's free tier",
      "Node.js seeding script using the Firebase Admin SDK to populate demo data",
      "Post-launch features: role switching between buyer and seller, and admin promotion and demotion",
      "Delivered release APK, full source, README, documentation and a client setup guide",
    ],
    stat: { value: "2", label: "languages: Malay and English" },
    caseStudySections: [],
    demoUrl: "",
    caseStudyUrl: "",
    githubUrl: "",
    accent: ["#f472b6", "#fbbf24"],
    visual: "mobile",
  },
  {
    id: "expense",
    number: "04",
    title: "SME Expense Tracker",
    type: "Client project (details anonymized)",
    stack: ["Laravel", "PHP", "SQLite", "Blade", "Alpine.js", "Chart.js", "Laravel Socialite (Google OAuth)", "Gmail SMTP", "Groq API"],
    summary:
      "A full expense management system for small businesses, covering budgets, reports, analytics and an AI financial advisor.",
    highlights: [
      "Admin and staff roles with Google OAuth sign-in, password reset by email and admin user management",
      "Multi-criteria expense filtering (search, category, vendor, amount range, date range), sortable columns, bulk delete and inline quick edit",
      "Duplicate expense detection and subcategory support",
      "Dashboard KPIs with month-over-month change, budget utilization, pie and bar charts, top categories and a predictive spending forecast",
      "Monthly summary page with budget-exceeded alerts and full-year CSV export",
      "Receipt preview modal and managed payment methods seeded with common Malaysian options",
      "AI advisor chatbot powered by Groq with the user's financial data injected as context",
      "Three full UI redesigns based on client feedback, plus customer and developer setup guides",
    ],
    stat: { value: "3", label: "full UI redesigns from client feedback" },
    caseStudySections: [],
    demoUrl: "",
    caseStudyUrl: "",
    githubUrl: "",
    accent: ["#fbbf24", "#5eead4"],
    visual: "chart",
  },
  {
    id: "oncoscan",
    number: "05",
    title: "OncoScan, Quantum Autoencoder Brain MRI Anomaly Screening",
    type: "Hackathon project, Quant-A-Thon 2026 (Team Quanters, QT-5 track)",
    stack: ["Python", "PennyLane", "PCA", "Quantum circuits (8 qubits, 4 layers)", "Streamlit"],
    summary:
      "An anomaly detection system that screens brain MRI scans by learning only what healthy scans look like, comparing a classical bottleneck against a quantum one.",
    highlights: [
      "Trained only on healthy scans; tumour images never used for training or threshold calibration",
      "Evaluated on a real 7,200-image brain MRI dataset",
      "Honest benchmarking: classical AUC 0.895 vs quantum AUC 0.769, with no claim of quantum advantage",
      "Diagnosed why an early patch-based prototype scored 0.99 on synthetic data but failed on real MRI, then redesigned it around whole-image representation",
      "Interactive Streamlit dashboard for live demos",
    ],
    stat: { value: "0.895 / 0.769", label: "classical vs quantum AUC" },
    caseStudySections: [],
    demoUrl: "",
    caseStudyUrl: "",
    githubUrl: "",
    accent: ["#a78bfa", "#22d3ee"],
    visual: "quantum",
  },
  {
    id: "automation",
    number: "06",
    title: "Business Automation for 404 Web Services",
    type: "Personal / business",
    stack: ["Google Apps Script", "Google Forms", "Telegram Bot API", "DOCX templating"],
    summary:
      "Internal tooling that automates client intake and paperwork for my freelance studio.",
    highlights: [
      "Automatic generation of invoices, deposit receipts and final receipts from DOCX templates with payment QR codes",
      "Telegram to Google Form client intake pipeline",
      "Telegram bot for automated group messaging",
    ],
    stat: { value: "3", label: "document types generated automatically" },
    caseStudySections: [],
    demoUrl: "",
    caseStudyUrl: "",
    githubUrl: "",
    accent: ["#22d3ee", "#818cf8"],
    visual: "flow",
  },
];

export const openSource = {
  show: false,
  title: "Open Source Clone Series (Coming Soon)",
  type: "Open source",
  summary:
    "A public GitHub series rebuilding well-known paid products as free open-source tools. First up: a voice dictation tool.",
  githubUrl: "",
};

export const moreWork = [
  { name: "PKU Digital Care", line: "Clinic health records system. Deployed on Hostinger.", tags: ["PHP", "MySQL", "Hostinger"] },
  { name: "SafeSitePro", line: "Construction site safety management system.", tags: ["PHP", "MySQL"] },
  {
    name: "KKJ E-Learning Portal",
    line: "Three-role e-learning platform (admin, lecturer, student). Added a lecturer portal, assignment and notes uploads, three quiz modes (manual MCQ, PDF, parsed MCQ from text file) and announcement filtering by level and course.",
    tags: ["PHP", "MySQL"],
  },
  { name: "Wakaf Digital (Jom Waqf)", line: "Laravel web app deployed to production on Hostinger at wakafdigital.my.", tags: ["Laravel", "MySQL"] },
  { name: "SignifyMe", line: "Laravel and MySQL web system.", tags: ["Laravel", "MySQL"] },
  { name: "eTalki", line: "User-to-user platform with booking, wallet and messaging.", tags: ["PHP", "MySQL"] },
  { name: "VishGuard", line: "Voice phishing detection module for a phishing simulation system.", tags: ["Python"] },
  { name: "DoINeedHelp", line: "Mental health screening mobile app.", tags: ["Flutter"] },
  { name: "FriendsHub", line: "Social app built with .NET MAUI and Blazor.", tags: ["C#", ".NET"] },
  { name: "Access Movement Dashboard", line: "Power BI dashboard built from raw access control system exports.", tags: ["Power BI", "Excel"] },
  { name: "University Student Portal", line: "Production bug fixes on a live student portal.", tags: ["PHP"] },
];

export const stack = [
  { group: "Languages", items: ["PHP", "Python", "Java", "JavaScript", "Dart", "C#", "SQL", "HTML", "CSS"] },
  {
    group: "Frameworks and Libraries",
    items: ["Laravel", "Alpine.js", "Chart.js", "Flask", "Flutter", "React", ".NET MAUI / Blazor", "Streamlit", "scikit-learn", "SHAP", "PennyLane"],
  },
  { group: "Databases", items: ["MySQL", "Firebase Firestore", "SQLite"] },
  {
    group: "Cloud and Deployment",
    items: ["Hostinger", "Netlify", "Firebase", "Cloudflare R2", "Cloudinary", "Laragon", "XAMPP", "Composer", "Git", "GitHub"],
  },
  {
    group: "APIs and Integrations",
    items: ["Google OAuth", "Google Calendar API", "Gmail SMTP", "Telegram Bot API", "Google Apps Script", "Groq API"],
  },
  { group: "Data and BI", items: ["Power BI", "Excel"] },
  { group: "Design", items: ["Figma", "Canva"] },
];

export const experience = [
  {
    role: "Founder and Full Stack Developer",
    org: "404 Web Services",
    start: "",
    end: "Present",
    body: "Freelance development studio serving students and SMEs in Malaysia. Delivered web apps, mobile apps and ML dashboards end to end, from requirements to deployment and post-launch support.",
    kind: "work",
  },
  {
    role: "Team Member, OncoScan",
    org: "Team Quanters, Quant-A-Thon 2026",
    start: "2026",
    end: "",
    body: "Built and benchmarked a quantum autoencoder anomaly detection pipeline for brain MRI screening.",
    kind: "hackathon",
  },
  {
    role: "Bachelor of Computer Science (Software Engineering)",
    org: "Universiti Tun Hussein Onn Malaysia (UTHM)",
    start: "",
    end: "",
    body: "CGPA 3.45. Dean's List: Semester 1, Semester 2 and Semester 4. Coursework: Database Systems, Software Engineering Principles, Requirement Engineering, Software Testing, Computer Networking, .NET Programming, Visual Programming.",
    kind: "education",
  },
];

export const process = [
  { title: "Understand first.", body: "Clear requirements and agreed scope before any code." },
  { title: "Design the data.", body: "Schema and roles come before screens." },
  { title: "Prototype fast.", body: "Clickable prototypes for early feedback when the design is uncertain." },
  { title: "Ship to production.", body: "Real deployments, real security rules, safe migrations." },
  { title: "Hand over properly.", body: "Documentation and setup guides so the client is never stuck." },
];

export const contact = {
  heading: "Let's build something.",
  text: "I'm open to internships and junior roles in backend development, frontend development and deployment engineering. I also take on freelance projects through 404 Web Services.",
};
