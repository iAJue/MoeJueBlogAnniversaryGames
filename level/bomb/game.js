(() => {
  const $ = (sel, root = document) => root.querySelector(sel);
  const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));

  const wiresSvg = $("#wiresSvg");
  const wireGroups = $$(".wire-group", wiresSvg);
  const timerEl = $("#timer");
  const statusEl = $("#status");
  const noteBodyEl = $(".note-body");
  const fxCanvas = $("#fx");
  const cursorEl = $("#scissorCursor");

  const clamp = (v, lo, hi) => Math.max(lo, Math.min(hi, v));
  const pad2 = (n) => String(n).padStart(2, "0");

  const AudioEngine = (() => {
    const api = {
      muted: false,
      ctx: null,
      master: null,
      ensure() {
        if (api.muted) return null;
        if (api.ctx) return api.ctx;
        const Ctx = window.AudioContext || window.webkitAudioContext;
        if (!Ctx) return null;
        api.ctx = new Ctx();
        api.master = api.ctx.createGain();
        api.master.gain.value = 0.28;
        api.master.connect(api.ctx.destination);
        return api.ctx;
      },
      _tone({ freq = 880, type = "sine", dur = 0.06, gain = 0.08, detune = 0, when = 0 }) {
        const ctx = api.ensure();
        if (!ctx) return;
        const t0 = ctx.currentTime + when;
        const osc = ctx.createOscillator();
        const g = ctx.createGain();
        osc.type = type;
        osc.frequency.value = freq;
        osc.detune.value = detune;
        g.gain.setValueAtTime(0.0001, t0);
        g.gain.exponentialRampToValueAtTime(gain, t0 + 0.01);
        g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
        osc.connect(g);
        g.connect(api.master);
        osc.start(t0);
        osc.stop(t0 + dur + 0.02);
      },
      _noise({ dur = 0.35, gain = 0.22, when = 0, lp = 1200 }) {
        const ctx = api.ensure();
        if (!ctx) return;
        const t0 = ctx.currentTime + when;
        const len = Math.max(1, Math.floor(ctx.sampleRate * dur));
        const buf = ctx.createBuffer(1, len, ctx.sampleRate);
        const data = buf.getChannelData(0);
        for (let i = 0; i < len; i++) data[i] = (Math.random() * 2 - 1) * (1 - i / len);
        const src = ctx.createBufferSource();
        src.buffer = buf;
        const filter = ctx.createBiquadFilter();
        filter.type = "lowpass";
        filter.frequency.value = lp;
        const g = ctx.createGain();
        g.gain.setValueAtTime(0.0001, t0);
        g.gain.exponentialRampToValueAtTime(gain, t0 + 0.02);
        g.gain.exponentialRampToValueAtTime(0.0001, t0 + dur);
        src.connect(filter);
        filter.connect(g);
        g.connect(api.master);
        src.start(t0);
        src.stop(t0 + dur + 0.02);
      },
      beep() {
        api._tone({ freq: 980, type: "square", dur: 0.04, gain: 0.06 });
      },
      snip() {
        api._tone({ freq: 1500, type: "triangle", dur: 0.03, gain: 0.09 });
        api._tone({ freq: 900, type: "triangle", dur: 0.045, gain: 0.06, when: 0.02 });
      },
      success() {
        api._tone({ freq: 640, type: "sine", dur: 0.08, gain: 0.06 });
        api._tone({ freq: 820, type: "sine", dur: 0.11, gain: 0.07, when: 0.07 });
        api._tone({ freq: 1040, type: "sine", dur: 0.12, gain: 0.08, when: 0.14 });
      },
      boom() {
        api._noise({ dur: 0.55, gain: 0.28, lp: 2400 });
        api._noise({ dur: 0.9, gain: 0.18, lp: 900, when: 0.08 });
        api._tone({ freq: 62, type: "sawtooth", dur: 0.45, gain: 0.16 });
        api._tone({ freq: 38, type: "sine", dur: 0.85, gain: 0.12, when: 0.05 });
      },
    };
    return api;
  })();

  const Fx = (() => {
    const ctx = fxCanvas.getContext("2d", { alpha: true });
    const particles = [];
    let raf = 0;
    let flash = 0;

    function resize() {
      const dpr = window.devicePixelRatio || 1;
      fxCanvas.width = Math.floor(window.innerWidth * dpr);
      fxCanvas.height = Math.floor(window.innerHeight * dpr);
      fxCanvas.style.width = `${window.innerWidth}px`;
      fxCanvas.style.height = `${window.innerHeight}px`;
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    }

    function spawn({ x, y, count = 120, power = 1 }) {
      for (let i = 0; i < count; i++) {
        const a = Math.random() * Math.PI * 2;
        const s = (0.9 + Math.random() * 2.2) * 240 * power;
        particles.push({
          x,
          y,
          vx: Math.cos(a) * s,
          vy: Math.sin(a) * s,
          life: 0,
          ttl: 0.55 + Math.random() * 0.55,
          size: 1.6 + Math.random() * 2.8 * power,
          hue: 22 + Math.random() * 25,
          hot: Math.random(),
        });
      }
      flash = Math.min(1, flash + 0.85);
      start();
    }

    function start() {
      if (raf) return;
      raf = requestAnimationFrame(tick);
    }

    function tick() {
      raf = requestAnimationFrame(tick);
      ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
      ctx.save();
      ctx.globalCompositeOperation = "lighter";

      const dt = 1 / 60;
      for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];
        p.life += dt;
        const t = p.life / p.ttl;
        if (t >= 1) {
          particles.splice(i, 1);
          continue;
        }
        p.vx *= 0.985;
        p.vy = p.vy * 0.985 + 520 * dt;
        p.x += p.vx * dt;
        p.y += p.vy * dt;

        const a = (1 - t) * (0.55 + p.hot * 0.45);
        ctx.fillStyle = `hsla(${p.hue}, 95%, ${55 + p.hot * 20}%, ${a})`;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size * (1 - t * 0.35), 0, Math.PI * 2);
        ctx.fill();
      }
      ctx.restore();

      if (flash > 0.001) {
        flash *= 0.9;
        ctx.fillStyle = `rgba(255, 235, 210, ${flash * 0.22})`;
        ctx.fillRect(0, 0, window.innerWidth, window.innerHeight);
      }

      if (particles.length === 0 && flash < 0.01) {
        cancelAnimationFrame(raf);
        raf = 0;
        ctx.clearRect(0, 0, window.innerWidth, window.innerHeight);
      }
    }

    window.addEventListener("resize", resize, { passive: true });
    resize();

    return { spawn };
  })();

  function randomId(len) {
    const chars = "ABCDEFGHJKMNPQRSTUVWXYZ23456789";
    let s = "";
    for (let i = 0; i < len; i++) s += chars[Math.floor(Math.random() * chars.length)];
    return s;
  }

  function formatTime(s) {
    const mm = Math.floor(s / 60);
    const ss = Math.max(0, s % 60);
    return `${pad2(mm)}:${pad2(ss)}`;
  }

  function clientToSvgPoint(svg, clientX, clientY) {
    const ctm = svg.getScreenCTM();
    if (!ctm) return { x: 0, y: 0 };
    const pt = new DOMPoint(clientX, clientY);
    const p = pt.matrixTransform(ctm.inverse());
    return { x: p.x, y: p.y };
  }

  function dist2(a, b) {
    const dx = a.x - b.x;
    const dy = a.y - b.y;
    return dx * dx + dy * dy;
  }

  function closestLengthOnPath(path, target, samples = 140) {
    const total = path.getTotalLength();
    let bestLen = 0;
    let bestD = Infinity;
    for (let i = 0; i <= samples; i++) {
      const len = (total * i) / samples;
      const p = path.getPointAtLength(len);
      const d = dist2(p, target);
      if (d < bestD) {
        bestD = d;
        bestLen = len;
      }
    }

    const span = total / samples;
    let lo = clamp(bestLen - span, 0, total);
    let hi = clamp(bestLen + span, 0, total);
    for (let iter = 0; iter < 14; iter++) {
      const m1 = lo + (hi - lo) / 3;
      const m2 = hi - (hi - lo) / 3;
      const d1 = dist2(path.getPointAtLength(m1), target);
      const d2 = dist2(path.getPointAtLength(m2), target);
      if (d1 < d2) hi = m2;
      else lo = m1;
    }
    return (lo + hi) / 2;
  }

  const game = {
    status: "armed", // armed | disarmed | boom
    timeLeft: 45,
    tickHandle: 0,
  };

  function setBodyState(s) {
    document.body.classList.remove("armed", "disarmed", "boom");
    document.body.classList.add(s);
  }

  function stopTimer() {
    if (game.tickHandle) window.clearInterval(game.tickHandle);
    game.tickHandle = 0;
  }

  function explode(reason) {
    if (game.status !== "armed") return;
    game.status = "boom";
    stopTimer();
    setBodyState("boom");

    if (noteBodyEl) {
      noteBodyEl.classList.add("note-body--boom");
      noteBodyEl.textContent = "你给咱的珍藏图册炸成碎片了,你赔给咱-`д´-";
    }

    statusEl.innerHTML =
      '<a class="status-link" href="https://AGX.MoeJue.cn" target="_blank" rel="noopener noreferrer">开始汝的旅程为咱找回画册</a>';
    AudioEngine.boom();
    scatterAlbum();

    const rect = document.querySelector(".device").getBoundingClientRect();
    Fx.spawn({ x: rect.left + rect.width * 0.52, y: rect.top + rect.height * 0.45, count: 220, power: 1.05 });
  }

  function scatterAlbum() {
    const sources = ["/level/bomb/1.png", "/level/bomb/2.png", "/level/bomb/3.png"];
    const vw = window.innerWidth;
    const vh = window.innerHeight;
    const margin = 24;
    const deviceRect = document.querySelector(".device")?.getBoundingClientRect?.() ?? null;
    const avoid = deviceRect
      ? {
          left: deviceRect.left - 28,
          right: deviceRect.right + 28,
          top: deviceRect.top - 28,
          bottom: deviceRect.bottom + 28,
        }
      : {
          left: vw * 0.2,
          right: vw * 0.8,
          top: vh * 0.2,
          bottom: vh * 0.8,
        };

    const existing = document.getElementById("scatter");
    if (existing) existing.remove();

    const container = document.createElement("div");
    container.id = "scatter";
    container.className = "scatter";
    container.setAttribute("aria-hidden", "true");
    document.body.appendChild(container);

    const points = [];
    function farEnough(p) {
      for (const q of points) {
        const dx = p.x - q.x;
        const dy = p.y - q.y;
        if (dx * dx + dy * dy < 160 * 160) return false;
      }
      return true;
    }

    function pickPoint() {
      for (let tries = 0; tries < 140; tries++) {
        const x = margin + Math.random() * (vw - margin * 2);
        const y = margin + Math.random() * (vh - margin * 2);
        const outside = x < avoid.left || x > avoid.right || y < avoid.top || y > avoid.bottom;
        if (!outside) continue;
        const p = { x, y };
        if (!farEnough(p)) continue;
        return p;
      }
      return { x: vw * (0.18 + Math.random() * 0.64), y: vh * (0.18 + Math.random() * 0.64) };
    }

    for (const src of sources) {
      const p = pickPoint();
      points.push(p);

      const rot = -28 + Math.random() * 56;
      const scale = 0.78 + Math.random() * 0.34;

      const img = document.createElement("img");
      img.className = "scatter-img";
      img.src = src;
      img.alt = "";
      img.decoding = "async";
      img.loading = "eager";
      img.style.left = `${p.x}px`;
      img.style.top = `${p.y}px`;
      img.style.transform = `translate(-50%, -50%) rotate(${rot}deg) scale(${scale})`;
      container.appendChild(img);
    }
  }

  function updateDisplay() {
    timerEl.textContent = formatTime(game.timeLeft);
  }

  function tick() {
    if (game.status !== "armed") return;
    game.timeLeft -= 1;
    if (game.timeLeft < 0) game.timeLeft = 0;
    updateDisplay();

    if (!AudioEngine.muted) {
      const fast = game.timeLeft <= 10;
      if (fast) AudioEngine._tone({ freq: 1240, type: "square", dur: 0.03, gain: 0.06 });
      else AudioEngine.beep();
    }

    if (game.timeLeft <= 0) explode("倒计时归零");
  }

  function clearCuts() {
    for (const g of wireGroups) {
      g.classList.remove("cut");
      const p = g.querySelector("path.wire");
      p.style.strokeDasharray = "";
      p.style.strokeDashoffset = "";
      const ends = $$(".cut-end", g);
      for (const c of ends) {
        c.removeAttribute("cx");
        c.removeAttribute("cy");
      }
    }
  }

  function startGame() {
    stopTimer();
    clearCuts();

    setBodyState("armed");
    game.status = "armed";
    game.timeLeft = 45 + Math.floor(Math.random() * 16); // 45–60
    updateDisplay();
    statusEl.textContent = "移动剪刀，剪去正确的引线。";

    $("#serial").textContent = `SN-${randomId(6)}-${randomId(4)}`;
    $("#checksum").textContent = `CHK:${randomId(5)}`;

    game.tickHandle = window.setInterval(tick, 1000);
  }

  function sparkAtSvg(svg, x, y) {
    const g = document.createElementNS("http://www.w3.org/2000/svg", "g");
    g.setAttribute("opacity", "1");
    g.setAttribute("transform", `translate(${x} ${y})`);
    const n = 7;
    for (let i = 0; i < n; i++) {
      const a = (Math.PI * 2 * i) / n + Math.random() * 0.2;
      const r0 = 2;
      const r1 = 10 + Math.random() * 8;
      const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
      line.setAttribute("x1", String(Math.cos(a) * r0));
      line.setAttribute("y1", String(Math.sin(a) * r0));
      line.setAttribute("x2", String(Math.cos(a) * r1));
      line.setAttribute("y2", String(Math.sin(a) * r1));
      line.setAttribute("stroke", "rgba(255,245,220,0.95)");
      line.setAttribute("stroke-width", "1.6");
      line.setAttribute("stroke-linecap", "round");
      g.appendChild(line);
    }
    const core = document.createElementNS("http://www.w3.org/2000/svg", "circle");
    core.setAttribute("r", "3.2");
    core.setAttribute("fill", "rgba(255,230,180,0.95)");
    g.appendChild(core);

    svg.appendChild(g);
    const t0 = performance.now();
    const dur = 220;
    function anim(t) {
      const k = clamp((t - t0) / dur, 0, 1);
      g.setAttribute("opacity", String(1 - k));
      g.setAttribute("transform", `translate(${x} ${y}) scale(${1 + k * 0.9})`);
      if (k < 1) requestAnimationFrame(anim);
      else g.remove();
    }
    requestAnimationFrame(anim);
  }

  function cutWire(group, ev) {
    if (game.status !== "armed") return;
    if (group.classList.contains("cut")) return;

    const path = group.querySelector("path.wire");
    if (!path) return;

    const svgP = clientToSvgPoint(wiresSvg, ev.clientX, ev.clientY);
    const total = path.getTotalLength();
    const gap = 18;
    const len = clamp(closestLengthOnPath(path, svgP), gap / 2, total - gap / 2);

    const a = len - gap / 2;
    const c = total - (len + gap / 2);
    path.style.strokeDasharray = `${a} ${gap} ${c}`;
    path.style.strokeDashoffset = "0";

    const ends = $$(".cut-end", group);
    if (ends.length >= 2) {
      const p1 = path.getPointAtLength(len - gap / 2);
      const p2 = path.getPointAtLength(len + gap / 2);
      ends[0].setAttribute("cx", String(p1.x));
      ends[0].setAttribute("cy", String(p1.y));
      ends[1].setAttribute("cx", String(p2.x));
      ends[1].setAttribute("cy", String(p2.y));
    }

    group.classList.add("cut");
    AudioEngine.snip();
    sparkAtSvg(wiresSvg, svgP.x, svgP.y);

    const name = group.getAttribute("data-name") || "未知线";
    explode(`剪断 ${name}`);
  }

  function wireFromEventTarget(t) {
    if (!t) return null;
    const g = t.closest?.(".wire-group");
    if (!g) return null;
    if (!wireGroups.includes(g)) return null;
    return g;
  }

  let cursorSnipUntil = 0;

  function updateCursor(ev) {
    if (!cursorEl) return;
    if (!document.body.classList.contains("has-cursor")) return;
    if (!document.body.classList.contains("armed")) return;

    const x = ev.clientX;
    const y = ev.clientY;
    const now = performance.now();
    const snip = now < cursorSnipUntil ? 0.94 : 1;
    cursorEl.style.transform = `translate3d(${x}px, ${y}px, 0) scale(${snip}) translate(-50%, -50%)`;
  }

  function onPointerDown(ev) {
    const g = wireFromEventTarget(ev.target);
    if (!g) return;
    ev.preventDefault();
    cursorSnipUntil = performance.now() + 110;
    cutWire(g, ev);
  }

  function setup() {
    const fine = window.matchMedia?.("(pointer: fine)")?.matches ?? true;
    if (fine) document.body.classList.add("has-cursor");

    window.addEventListener("pointermove", updateCursor, { passive: true });
    wiresSvg.addEventListener("pointerdown", onPointerDown);

    const kickAudio = () => AudioEngine.ensure();
    window.addEventListener("pointerdown", kickAudio, { once: true });
    window.addEventListener("keydown", kickAudio, { once: true });

    startGame();
  }

  setup();
})();
