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

  // Three.js for hologram
  echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>';
  echo '<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>';

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

  // Chat Toggle Button (visible only when logged in)
  if ($u) {
    echo '<button id="chat-toggle-btn" onclick="toggleChat()">💬</button>';
  }

  // Contact Button (visible on all pages)
  echo '<button id="global-contact-btn" onclick="window.location.href=\'k.php?r=home#contact\'">CONTACT</button>';

  // Chat Panel (only for logged in users)
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

  // Global JavaScript
  echo '<script>
  const CSRF_TOKEN = "' . h($csrfToken) . '";
  const IS_LOGGED_IN = ' . $isLoggedIn . ';
  let chatOpen = false;
  let isRecording = false;
  let recognition = null;
  
  // Clock update
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
  
  // Toggle Chat Panel
  function toggleChat() {
    chatOpen = !chatOpen;
    const panel = document.getElementById("chat-panel");
    if (panel) {
      panel.classList.toggle("active", chatOpen);
    }
  }
  
  // Show Subtitle
  function showSubtitle(speaker, text) {
    const sub = document.getElementById("live-subtitle");
    if (sub) {
      sub.querySelector(".speaker").textContent = speaker;
      sub.querySelector(".text").textContent = text;
      sub.classList.add("visible");
      setTimeout(() => sub.classList.remove("visible"), 4000);
    }
  }
  
  // Add Message to Chat
  function addMessage(text, isUser, lang = "en") {
    const container = document.getElementById("chat-messages");
    if (!container) return;
    
    const now = new Date();
    const time = pad(now.getHours()) + ":" + pad(now.getMinutes()) + ":" + pad(now.getSeconds());
    
    const msg = document.createElement("div");
    msg.className = "chat-msg " + (isUser ? "user-msg" : "bot-msg");
    msg.innerHTML = text + (lang !== "en" ? "<span class=\"lang-indicator\">" + lang.toUpperCase() + "</span>" : "") + 
                    "<span class=\"msg-time\">" + time + "</span>";
    container.appendChild(msg);
    container.scrollTop = container.scrollHeight;
    
    // Show as subtitle
    showSubtitle(isUser ? "YOU" : "KELION", text);
  }
  
  // Send Message
  async function sendMessage() {
    const input = document.getElementById("chat-input");
    if (!input || !input.value.trim()) return;
    
    const text = input.value.trim();
    input.value = "";
    
    // Add user message
    addMessage(text, true);
    
    // Show thinking state
    showSubtitle("KELION", "Processing...");
    
    try {
      const response = await fetch("k.php?r=api_ask", {
        method: "POST",
        headers: { "Content-Type": "application/x-www-form-urlencoded" },
        body: "csrf=" + encodeURIComponent(CSRF_TOKEN) + "&q=" + encodeURIComponent(text)
      });
      
      const data = await response.json();
      
      if (data.ok) {
        addMessage(data.answer, false, data.lang || "en");
        
        // Trigger TTS
        if (data.auto_voice) {
          playTTS(data.answer);
        }
      } else {
        addMessage("Error: " + (data.error || "Unknown error"), false);
      }
    } catch (err) {
      addMessage("Connection error: " + err.message, false);
    }
  }
  
  // Play TTS Audio
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
        audio.play();
      }
    } catch (err) {
      console.error("TTS Error:", err);
    }
  }
  
  // Voice Recognition
  function toggleVoice() {
    const btn = document.getElementById("voice-btn");
    
    if (!("webkitSpeechRecognition" in window) && !("SpeechRecognition" in window)) {
      alert("Voice recognition not supported in this browser.");
      return;
    }
    
    if (isRecording) {
      // Stop recording
      if (recognition) recognition.stop();
      isRecording = false;
      btn.classList.remove("recording");
      btn.textContent = "🎤";
    } else {
      // Start recording
      const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
      recognition = new SpeechRecognition();
      recognition.continuous = false;
      recognition.interimResults = true;
      recognition.lang = "en-US"; // Will auto-detect language
      
      recognition.onstart = function() {
        isRecording = true;
        btn.classList.add("recording");
        btn.textContent = "⏺";
        showSubtitle("YOU", "Listening...");
      };
      
      recognition.onresult = function(event) {
        let transcript = "";
        for (let i = event.resultIndex; i < event.results.length; i++) {
          transcript += event.results[i][0].transcript;
        }
        document.getElementById("chat-input").value = transcript;
        showSubtitle("YOU", transcript);
      };
      
      recognition.onend = function() {
        isRecording = false;
        btn.classList.remove("recording");
        btn.textContent = "🎤";
        // Auto-send after voice recognition
        const input = document.getElementById("chat-input");
        if (input && input.value.trim()) {
          sendMessage();
        }
      };
      
      recognition.onerror = function(event) {
        console.error("Speech recognition error:", event.error);
        isRecording = false;
        btn.classList.remove("recording");
        btn.textContent = "🎤";
      };
      
      recognition.start();
    }
  }
  
  // Enter key to send
  document.addEventListener("DOMContentLoaded", function() {
    const input = document.getElementById("chat-input");
    if (input) {
      input.addEventListener("keypress", function(e) {
        if (e.key === "Enter") {
          sendMessage();
        }
      });
    }
  });
  
  // Initialize Mini Hologram
  setTimeout(function() {
    const container = document.getElementById("mini-hologram");
    if (container && typeof THREE !== "undefined") {
      initMiniHologram(container);
    }
  }, 500);
  
  function initMiniHologram(container) {
    const scene = new THREE.Scene();
    scene.background = null;
    
    const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 100);
    camera.position.set(0, 0, 2);
    
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setSize(150, 150);
    renderer.setClearColor(0x000000, 0);
    container.appendChild(renderer.domElement);
    
    const ambient = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(ambient);
    const front = new THREE.DirectionalLight(0xffeedd, 1.0);
    front.position.set(0, 1, 3);
    scene.add(front);
    
    let model = null;
    
    const loader = new THREE.GLTFLoader();
    loader.load("public/hologram.glb", function(gltf) {
      model = gltf.scene;
      model.traverse(function(node) {
        if (node.isMesh) {
          node.material = new THREE.MeshPhysicalMaterial({
            color: new THREE.Color(0xe8b89d),
            roughness: 0.55,
            metalness: 0.0,
            clearcoat: 0.15,
            emissive: new THREE.Color(0x331111),
            emissiveIntensity: 0.1
          });
        }
      });
      
      const box = new THREE.Box3().setFromObject(model);
      const center = box.getCenter(new THREE.Vector3());
      model.position.sub(center);
      model.scale.set(0.8, 0.8, 0.8);
      scene.add(model);
    }, undefined, function(err) {
      const geo = new THREE.SphereGeometry(0.4, 32, 32);
      const mat = new THREE.MeshPhysicalMaterial({ color: 0xe8b89d, roughness: 0.5 });
      model = new THREE.Mesh(geo, mat);
      scene.add(model);
    });
    
    function animate() {
      requestAnimationFrame(animate);
      if (model) model.rotation.y += 0.005;
      renderer.render(scene, camera);
    }
    animate();
  }
  </script>';
}

function page_footer(): void
{
  echo '<footer class="footer">KELION AI • Futuristic Hologram Platform • <a href="k.php?r=privacy">Privacy & GDPR</a> • <a href="k.php?r=terms">Terms</a> • <a href="k.php?r=safety">Safety</a></footer></body></html>';
}
