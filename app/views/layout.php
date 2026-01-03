<?php
function page_header(string $title): void
{
  global $CONFIG;
  $u = current_user();
  $name = $u ? h($u['username']) . ' <span class="pill">' . h($u['role']) . '</span>' : 'Guest';
  $ver = h((string) ($CONFIG['app']['version'] ?? 'v1'));
  $isLoggedIn = $u ? 'true' : 'false';
  $csrfToken = csrf_token();

  echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
  echo '<title>' . h($title) . ' - KELION AI</title>';
  echo '<link rel="stylesheet" href="' . h(asset('public/assets/style.css')) . '">';
  echo '<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">';

  // Libraries
  echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>';
  echo '<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>';
  echo '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>'; // Charting engine

  echo '</head><body>';

  // HUD Header
  echo '<div class="hud">';
  echo '<div class="hudLeft"><div class="hudTitle">KELION AI</div><div class="hudMeta">' . $ver . ' • <span id="hudDate"></span></div></div>';
  echo '<div class="hudClock"><div class="clockFace"><span id="hudTime"></span></div></div>';
  echo '</div>';

  echo '<header class="topbar"><div class="brand">KELION<span>AI</span></div><div class="who">' . $name . '</div></header>';

  // Mini Hologram Container
  echo '<div id="mini-hologram" style="position:fixed;bottom:80px;left:20px;width:150px;height:150px;z-index:9000;pointer-events:none;"></div>';

  // Live Subtitle Display
  echo '<div id="live-subtitle"><span class="speaker"></span><span class="text"></span></div>';

  // Chat Toggle Button
  if ($u) {
    echo '<button id="chat-toggle-btn" onclick="toggleChat()">💬</button>';
  }

  // Contact Button
  echo '<button id="global-contact-btn" onclick="window.location.href=\'k.php?r=home#contact\'">CONTACT</button>';

  // Chat Panel
  if ($u) {
    echo '<div id="chat-panel">';
    echo '<div class="chat-header"><h3>KELION CHAT</h3><span class="chat-timestamp" id="chat-time"></span></div>';
    echo '<div class="chat-messages" id="chat-messages"></div>';
    echo '<div class="chat-input-area">';
    echo '<input type="text" id="chat-input" placeholder="Type or speak..." autocomplete="off">';
    echo '<button id="voice-btn" onclick="toggleVoice()">🎤</button>';
    echo '<button id="send-btn" onclick="sendMessage()">➤</button>';
    echo '</div>';
    echo '</div>';
  }

  echo '<script>
  const CSRF_TOKEN = "' . h($csrfToken) . '";
  const IS_LOGGED_IN = ' . $isLoggedIn . ';
  let chatOpen = false;
  let isRecording = false;
  let recognition = null;
  let miniHoloModel = null;
  let audioContext = null;
  let analyser = null;
  let dataArray = null;

  // Clock
  function pad(n){ return String(n).padStart(2,"0"); }
  function tick(){
    const d = new Date();
    const days=["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
    const months=["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
    const t = pad(d.getHours())+":"+pad(d.getMinutes())+":"+pad(d.getSeconds());
    const date = days[d.getDay()]+" "+pad(d.getDate())+" "+months[d.getMonth()]+" "+d.getFullYear();
    const elT=document.getElementById("hudTime"); if(elT) elT.textContent=t;
    const elD=document.getElementById("hudDate"); if(elD) elD.textContent=date;
    const chatTime = document.getElementById("chat-time"); if(chatTime) chatTime.textContent=t;
  }
  tick(); setInterval(tick, 250);

  function toggleChat() {
    chatOpen = !chatOpen;
    const panel = document.getElementById("chat-panel");
    if (panel) panel.classList.toggle("active", chatOpen);
  }

  function showSubtitle(speaker, text) {
    const sub = document.getElementById("live-subtitle");
    if (sub) {
      sub.querySelector(".speaker").textContent = speaker;
      sub.querySelector(".text").textContent = text;
      sub.classList.add("visible");
      setTimeout(() => sub.classList.remove("visible"), 4000);
    }
  }

  function addMessage(text, isUser, lang = "en") {
    const container = document.getElementById("chat-messages");
    if (!container) return;
    const now = new Date();
    const time = pad(now.getHours()) + ":" + pad(now.getMinutes());
    const msg = document.createElement("div");
    msg.className = "chat-msg " + (isUser ? "user-msg" : "bot-msg");
    let langTag = lang !== "en" ? `<span class="lang-indicator">${lang.toUpperCase()}</span>` : "";
    msg.innerHTML = text + langTag + `<span class="msg-time">${time}</span>`;
    container.appendChild(msg);
    container.scrollTop = container.scrollHeight;
    showSubtitle(isUser ? "YOU" : "KELION", text);
  }

  async function sendMessage() {
    const input = document.getElementById("chat-input");
    if (!input || !input.value.trim()) return;
    const text = input.value.trim();
    input.value = "";
    addMessage(text, true);
    try {
      const response = await fetch("k.php?r=api_ask", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "csrf=" + encodeURIComponent(CSRF_TOKEN) + "&q=" + encodeURIComponent(text)
      });
      const data = await response.json();
      if (data.ok) {
        addMessage(data.answer, false, data.lang || "en");
        if (data.auto_voice) playTTS(data.answer);
      }
    } catch (err) { console.error(err); }
  }

  async function playTTS(text) {
    try {
      const response = await fetch("k.php?r=api_tts", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "csrf=" + encodeURIComponent(CSRF_TOKEN) + "&text=" + encodeURIComponent(text)
      });
      if (response.ok) {
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const audio = new Audio(url);
        connectLipSync(audio);
        audio.play();
      }
    } catch (err) { console.error(err); }
  }

  function connectLipSync(audio) {
    if (!audioContext) audioContext = new (window.AudioContext || window.webkitAudioContext)();
    if (!analyser) {
      analyser = audioContext.createAnalyser();
      analyser.fftSize = 256;
      dataArray = new Uint8Array(analyser.frequencyBinCount);
    }
    const source = audioContext.createMediaElementSource(audio);
    source.connect(analyser);
    analyser.connect(audioContext.destination);
  }

  function toggleVoice() {
    const btn = document.getElementById("voice-btn");
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (!SpeechRecognition) return alert("STT not supported.");
    if (isRecording) { recognition.stop(); return; }
    recognition = new SpeechRecognition();
    recognition.onstart = () => { isRecording = true; btn.classList.add("recording"); btn.textContent = "⏺"; };
    recognition.onresult = (e) => { 
        const transcript = e.results[0][0].transcript;
        document.getElementById("chat-input").value = transcript;
    };
    recognition.onend = () => { isRecording = false; btn.classList.remove("recording"); btn.textContent = "🎤"; sendMessage(); };
    recognition.start();
  }

  function initMiniHologram(container) {
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 100);
    camera.position.set(0, 0, 2);
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(150, 150);
    container.appendChild(renderer.domElement);
    scene.add(new THREE.AmbientLight(0xffffff, 0.6));
    const pl = new THREE.PointLight(0x00f3ff, 1, 10); pl.position.set(2, 2, 2); scene.add(pl);
    
    new THREE.GLTFLoader().load("public/hologram.glb", (gltf) => {
      miniHoloModel = gltf.scene;
      miniHoloModel.traverse(n => {
        if (n.isMesh) n.material = new THREE.MeshPhysicalMaterial({ color: 0xe8b89d, roughness: 0.5, emissive: 0x331111, emissiveIntensity: 0.1 });
      });
      const box = new THREE.Box3().setFromObject(miniHoloModel);
      miniHoloModel.position.sub(box.getCenter(new THREE.Vector3()));
      miniHoloModel.scale.set(0.8, 0.8, 0.8);
      scene.add(miniHoloModel);
    }, null, () => {
      const g = new THREE.SphereGeometry(0.4, 32, 32);
      const m = new THREE.MeshPhysicalMaterial({ color: 0xe8b89d });
      miniHoloModel = new THREE.Mesh(g, m);
      scene.add(miniHoloModel);
    });

    function animate() {
      requestAnimationFrame(animate);
      if (miniHoloModel) {
        miniHoloModel.rotation.y += 0.005;
        if (analyser && dataArray) {
          analyser.getByteFrequencyData(dataArray);
          let sum = 0; for(let i=0; i<dataArray.length; i++) sum += dataArray[i];
          let avg = sum / dataArray.length;
          // Lip sync effect (scale y)
          let s = 0.8 + (avg / 255) * 0.2;
          miniHoloModel.scale.set(0.8, s, 0.8);
        }
      }
      renderer.render(scene, camera);
    }
    animate();
  }

  document.addEventListener("DOMContentLoaded", () => {
    const cin = document.getElementById("chat-input");
    if (cin) cin.addEventListener("keypress", (e) => { if(e.key==="Enter") sendMessage(); });
    setTimeout(() => {
      const c = document.getElementById("mini-hologram");
      if (c && typeof THREE !== "undefined") initMiniHologram(c);
    }, 500);
  });
  </script>';
}

function page_footer(): void
{
  echo '<footer class="footer">KELION AI • v1.1.0 • <a href="k.php?r=privacy">Privacy</a> • <a href="k.php?r=terms">Terms</a></footer></body></html>';
}
