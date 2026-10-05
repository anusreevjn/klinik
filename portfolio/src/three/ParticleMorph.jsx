import { useEffect, useMemo, useRef } from "react";
import { Canvas, useFrame, useThree } from "@react-three/fiber";
import * as THREE from "three";
import { SHAPES } from "./shapes";

const vertexShader = `
  attribute vec3 aA;
  attribute vec3 aB;
  attribute vec3 aC;
  attribute vec3 aDir;
  attribute float aRand;
  attribute float aScale;

  uniform vec3 uFrom;
  uniform vec3 uTo;
  uniform float uT;
  uniform float uTime;
  uniform float uSize;
  uniform float uPixelRatio;
  uniform float uHover;
  uniform float uWobble;
  uniform vec3 uMouse;
  uniform vec3 uPulseOrigin;
  uniform float uPulseTime;
  uniform vec3 uColorA;
  uniform vec3 uColorB;
  uniform vec3 uColorC;

  varying vec3 vColor;
  varying float vAlpha;

  vec3 pick(vec3 w) {
    return aA * w.x + aB * w.y + aC * w.z;
  }

  void main() {
    vec3 from = pick(uFrom);
    vec3 to = pick(uTo);
    float t = clamp(uT * 1.5 - aRand * 0.5, 0.0, 1.0);
    t = t * t * (3.0 - 2.0 * t);
    vec3 pos = mix(from, to, t);

    float burst = sin(t * 3.14159265);
    pos += aDir * burst * (0.5 + aRand * 1.1);
    pos += aDir * sin(uTime * 0.9 + aRand * 6.2831853) * 0.04 * uWobble;

    vec3 d = pos - uMouse;
    float dist = length(d);
    float f = smoothstep(1.35, 0.0, dist) * uHover;
    pos += normalize(d + vec3(0.0001)) * f * 0.9;

    vec3 pd = pos - uPulseOrigin;
    float pdist = length(pd);
    float wave = uPulseTime * 4.5;
    float ring = exp(-pow(pdist - wave, 2.0) * 5.0) * exp(-uPulseTime * 1.4);
    pos += normalize(pd + vec3(0.0001)) * ring * 0.75;

    vec4 mv = modelViewMatrix * vec4(pos, 1.0);
    gl_Position = projectionMatrix * mv;
    gl_PointSize = uSize * aScale * uPixelRatio * (1.0 + f * 1.3 + ring * 1.6) / -mv.z;

    float h = clamp(pos.y * 0.2 + 0.5, 0.0, 1.0);
    vec3 c = mix(uColorA, uColorB, h);
    c = mix(c, uColorC, smoothstep(0.72, 1.0, aRand));
    c += (f + ring) * 0.4;
    vColor = c;
    vAlpha = 0.5 + aScale * 0.32;
  }
`;

const fragmentShader = `
  varying vec3 vColor;
  varying float vAlpha;

  void main() {
    float d = length(gl_PointCoord - 0.5);
    float a = smoothstep(0.5, 0.0, d);
    a = pow(a, 1.7);
    if (a < 0.01) discard;
    gl_FragColor = vec4(vColor, a * vAlpha);
  }
`;

const ONE_HOT = [new THREE.Vector3(1, 0, 0), new THREE.Vector3(0, 1, 0), new THREE.Vector3(0, 0, 1)];
const HOLD = 4.6;
const MORPH = 2.2;

function Particles({ count, pointer, control, onShape, reduced, scale }) {
  const points = useRef();
  const { camera, gl } = useThree();

  const geometry = useMemo(() => {
    const g = new THREE.BufferGeometry();
    const [a, b, c] = SHAPES.map((s) => s.build(count));
    const dir = new Float32Array(count * 3);
    const rnd = new Float32Array(count);
    const scl = new Float32Array(count);
    for (let i = 0; i < count; i++) {
      const u = Math.random() * 2 - 1;
      const th = Math.random() * Math.PI * 2;
      const s = Math.sqrt(1 - u * u);
      dir[i * 3] = s * Math.cos(th);
      dir[i * 3 + 1] = u;
      dir[i * 3 + 2] = s * Math.sin(th);
      rnd[i] = Math.random();
      scl[i] = 0.45 + Math.random() * 1.0;
    }
    g.setAttribute("position", new THREE.BufferAttribute(a.slice(), 3));
    g.setAttribute("aA", new THREE.BufferAttribute(a, 3));
    g.setAttribute("aB", new THREE.BufferAttribute(b, 3));
    g.setAttribute("aC", new THREE.BufferAttribute(c, 3));
    g.setAttribute("aDir", new THREE.BufferAttribute(dir, 3));
    g.setAttribute("aRand", new THREE.BufferAttribute(rnd, 1));
    g.setAttribute("aScale", new THREE.BufferAttribute(scl, 1));
    g.boundingSphere = new THREE.Sphere(new THREE.Vector3(), 6);
    return g;
  }, [count]);

  const material = useMemo(
    () =>
      new THREE.ShaderMaterial({
        vertexShader,
        fragmentShader,
        transparent: true,
        depthWrite: false,
        blending: THREE.AdditiveBlending,
        uniforms: {
          uFrom: { value: ONE_HOT[0].clone() },
          uTo: { value: ONE_HOT[0].clone() },
          uT: { value: 0 },
          uTime: { value: 0 },
          uSize: { value: 30 },
          uPixelRatio: { value: 1 },
          uHover: { value: 0 },
          uWobble: { value: 1 },
          uMouse: { value: new THREE.Vector3(99, 99, 99) },
          uPulseOrigin: { value: new THREE.Vector3() },
          uPulseTime: { value: 100 },
          uColorA: { value: new THREE.Color("#5eead4") },
          uColorB: { value: new THREE.Color("#818cf8") },
          uColorC: { value: new THREE.Color("#f472b6") },
        },
      }),
    []
  );

  useEffect(() => () => geometry.dispose(), [geometry]);
  useEffect(() => () => material.dispose(), [material]);

  const state = useRef({ current: 0, next: 0, phase: "hold", since: 0 });
  const helpers = useMemo(
    () => ({
      raycaster: new THREE.Raycaster(),
      plane: new THREE.Plane(new THREE.Vector3(0, 0, 1), 0),
      hit: new THREE.Vector3(),
      ndc: new THREE.Vector2(),
    }),
    []
  );

  useFrame((_, delta) => {
    const dt = Math.min(delta, 0.05);
    const u = material.uniforms;
    const s = state.current;
    u.uTime.value += dt;
    u.uPixelRatio.value = gl.getPixelRatio();
    u.uWobble.value = reduced ? 0 : 1;
    s.since += dt;

    const requested = control.current.request;
    if (requested !== null && s.phase === "hold" && requested !== s.current) {
      s.next = requested;
      s.phase = "morph";
      s.since = 0;
      control.current.request = null;
      u.uFrom.value.copy(ONE_HOT[s.current]);
      u.uTo.value.copy(ONE_HOT[s.next]);
      onShape(s.next);
    } else if (requested === s.current) {
      control.current.request = null;
    }

    if (s.phase === "hold" && !reduced && !control.current.paused && s.since > HOLD) {
      s.next = (s.current + 1) % SHAPES.length;
      s.phase = "morph";
      s.since = 0;
      u.uFrom.value.copy(ONE_HOT[s.current]);
      u.uTo.value.copy(ONE_HOT[s.next]);
      onShape(s.next);
    }

    if (s.phase === "morph") {
      const k = Math.min(s.since / MORPH, 1);
      u.uT.value = k;
      if (k >= 1) {
        s.current = s.next;
        s.phase = "hold";
        s.since = 0;
        u.uFrom.value.copy(ONE_HOT[s.current]);
        u.uTo.value.copy(ONE_HOT[s.current]);
        u.uT.value = 0;
      }
    }

    const p = pointer.current;
    u.uHover.value += ((p.inside ? 1 : 0) - u.uHover.value) * Math.min(dt * 5, 1);

    const obj = points.current;
    if (!obj) return;
    const time = u.uTime.value;
    const targetY = (reduced ? 0 : Math.sin(time * 0.22) * 0.45) + p.x * 0.3;
    const targetX = -p.y * 0.18;
    obj.rotation.y += (targetY - obj.rotation.y) * Math.min(dt * 2.5, 1);
    obj.rotation.x += (targetX - obj.rotation.x) * Math.min(dt * 2.5, 1);
    obj.scale.setScalar(scale);
    obj.updateMatrixWorld();

    if (p.inside) {
      helpers.ndc.set(p.x, p.y);
      helpers.raycaster.setFromCamera(helpers.ndc, camera);
      if (helpers.raycaster.ray.intersectPlane(helpers.plane, helpers.hit)) {
        obj.worldToLocal(helpers.hit);
        u.uMouse.value.lerp(helpers.hit, Math.min(dt * 12, 1));
      }
    }

    if (p.pulse) {
      p.pulse = false;
      helpers.ndc.set(p.x, p.y);
      helpers.raycaster.setFromCamera(helpers.ndc, camera);
      if (helpers.raycaster.ray.intersectPlane(helpers.plane, helpers.hit)) {
        obj.worldToLocal(helpers.hit);
        u.uPulseOrigin.value.copy(helpers.hit);
        u.uPulseTime.value = 0;
      }
    }
    u.uPulseTime.value += dt;
  });

  return <points ref={points} geometry={geometry} material={material} frustumCulled={false} />;
}

export default function ParticleMorph({ count = 7000, onShape, control, reduced = false, scale = 1, className = "" }) {
  const wrap = useRef(null);
  const pointer = useRef({ x: 0, y: 0, inside: false, pulse: false });
  const frameloop = useRef("always");
  const setFrameloop = useRef(null);

  useEffect(() => {
    const el = wrap.current;
    if (!el) return undefined;
    const update = (e) => {
      const r = el.getBoundingClientRect();
      const x = ((e.clientX - r.left) / r.width) * 2 - 1;
      const y = -(((e.clientY - r.top) / r.height) * 2 - 1);
      pointer.current.x = x;
      pointer.current.y = y;
      pointer.current.inside = x >= -1 && x <= 1 && y >= -1 && y <= 1;
    };
    const down = (e) => {
      update(e);
      if (pointer.current.inside) pointer.current.pulse = true;
    };
    const out = (e) => {
      if (!e.relatedTarget) pointer.current.inside = false;
    };
    const up = (e) => {
      if (e.pointerType !== "mouse") pointer.current.inside = false;
    };
    window.addEventListener("pointermove", update, { passive: true });
    window.addEventListener("pointerdown", down, { passive: true });
    window.addEventListener("pointerup", up, { passive: true });
    window.addEventListener("pointercancel", up, { passive: true });
    window.addEventListener("pointerout", out, { passive: true });
    const io = new IntersectionObserver(
      ([entry]) => {
        const next = entry.isIntersecting ? "always" : "never";
        if (frameloop.current !== next && setFrameloop.current) {
          frameloop.current = next;
          setFrameloop.current(next);
        }
      },
      { threshold: 0 }
    );
    io.observe(el);
    return () => {
      window.removeEventListener("pointermove", update);
      window.removeEventListener("pointerdown", down);
      window.removeEventListener("pointerup", up);
      window.removeEventListener("pointercancel", up);
      window.removeEventListener("pointerout", out);
      io.disconnect();
    };
  }, []);

  return (
    <div ref={wrap} className={className} aria-hidden="true">
      <Canvas
        dpr={[1, 1.75]}
        camera={{ position: [0, 0, 8], fov: 45 }}
        gl={{ antialias: false, alpha: true, powerPreference: "high-performance" }}
        onCreated={(st) => {
          setFrameloop.current = st.setFrameloop;
        }}
      >
        <Particles count={count} pointer={pointer} control={control} onShape={onShape} reduced={reduced} scale={scale} />
      </Canvas>
    </div>
  );
}
