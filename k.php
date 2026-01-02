<?php
// =============================================================================
// KELION AI Platform - Single Entry (k.php)
// Version starts at v1.0.0 (set in config.php)
// UI: English. Replies: user's language.
// =============================================================================

require __DIR__ . '/app/bootstrap.php';
require __DIR__ . '/app/views/layout.php';

$r = $_GET['r'] ?? 'home';
traffic_log('view', $r);

function require_policy_consent(): void
{
  $u = current_user();
  if (!$u)
    return;
  if ($u['role'] === 'admin' || $u['role'] === 'demo')
    return;

  $db = db();
  $policyVer = 'v1';
  $has = (int) $db->querySingle("SELECT COUNT(*) FROM consents WHERE user_id=" . (int) $u['id'] . " AND policy_version='" . SQLite3::escapeString($policyVer) . "'");
  $ageOk = (int) $db->querySingle("SELECT age_confirmed FROM users WHERE id=" . (int) $u['id']);

  if ($has <= 0 || $ageOk <= 0)
    redirect('k.php?r=account');
}



// ---------------- Views ----------------
function render_login(?string $err = null): void
{
  page_header('Login');
  echo '<div class="wrap"><div class="card" style="max-width:520px;margin:0 auto">';
  echo '<h2>Login</h2><p class="mut">UI is English. After login, speak/type in your language.</p>';
  if ($err)
    echo '<div class="err">' . h($err) . '</div><div style="height:8px"></div>';
  echo '<form method="post" action="k.php?r=login_post">';
  echo '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
  echo '<label class="mut">Username</label><input name="username" autocomplete="username" required>';
  echo '<div style="height:10px"></div>';
  echo '<label class="mut">Password</label><input type="password" name="password" autocomplete="current-password" required>';
  echo '<div style="height:12px"></div>';
  echo '<button class="btn" type="submit">Login</button>';
  echo '</form>';
  echo '<div style="height:14px"></div>';
  echo '<div class="mut">Demo: <b>demo / demo</b></div>';
  echo '</div></div>';
  page_footer();
}

function render_home(): void
{
  global $CONFIG;
  $version = $CONFIG['app']['version'] ?? 'v1.0.2';
  $u = current_user();
  $username = $u ? h($u['username']) : 'OFFLINE';
  $cloudStatus = $u ? 'CONNECTED' : 'DISCONNECTED';
  $cloudClass = $u ? 'online' : 'offline';
  ?>
  <!doctype html>
  <html lang="en">

  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>KELION AI - Futuristic Hologram Platform</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      href="https://fonts.googleapis.com/css2?family=Rajdhani:wght@400;600;700&family=Orbitron:wght@400;700&display=swap"
      rel="stylesheet">

    <!-- THREE.JS & PLUGINS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/EffectComposer.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/RenderPass.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/ShaderPass.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/postprocessing/UnrealBloomPass.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/shaders/LuminosityHighPassShader.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/shaders/CopyShader.js"></script>

    <style>
      :root {
        --cyan: #00f3ff;
        --pink: #ff00ff;
        --bg: #050505;
        --panel: rgba(0, 15, 30, 0.95);
        --border: rgba(0, 243, 255, 0.35);
        --glow: 0 0 20px rgba(0, 243, 255, 0.25);
      }

      * {
        box-sizing: border-box;
        margin: 0;
        padding: 0;
      }

      body {
        height: 100vh;
        width: 100vw;
        overflow: hidden;
        font-family: 'Rajdhani', sans-serif;
        color: var(--cyan);
        background: radial-gradient(ellipse at center, #0a0a2e 0%, #050510 50%, #000000 100%);
        position: relative;
      }

      /* Supernova Background Effect */
      body::before {
        content: '';
        position: fixed;
        inset: 0;
        background:
          radial-gradient(circle at 30% 20%, rgba(0, 255, 255, 0.15) 0%, transparent 30%),
          radial-gradient(circle at 70% 80%, rgba(255, 0, 255, 0.1) 0%, transparent 25%),
          radial-gradient(circle at 50% 50%, rgba(0, 100, 255, 0.08) 0%, transparent 50%);
        pointer-events: none;
        z-index: -1;
        animation: nebulaPulse 15s ease-in-out infinite;
      }

      @keyframes nebulaPulse {

        0%,
        100% {
          opacity: 0.6;
        }

        50% {
          opacity: 1;
        }
      }

      /* Welcome Overlay - Auto-transitions */
      #welcomeOverlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: radial-gradient(ellipse at center, rgba(0, 20, 40, 0.98) 0%, rgba(0, 0, 0, 1) 100%);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        transition: opacity 1s ease;
      }

      #welcomeOverlay .k-logo {
        font-size: 150px;
        font-family: 'Orbitron', sans-serif;
        font-weight: 700;
        color: var(--cyan);
        text-shadow: 0 0 60px rgba(0, 255, 255, 0.8), 0 0 120px rgba(0, 255, 255, 0.4);
        animation: kPulse 2s ease-in-out infinite;
      }

      @keyframes kPulse {

        0%,
        100% {
          transform: scale(1);
          opacity: 1;
        }

        50% {
          transform: scale(1.05);
          opacity: 0.9;
        }
      }

      #welcomeOverlay h1 {
        color: var(--cyan);
        font-size: 36px;
        font-family: 'Orbitron', sans-serif;
        margin: 20px 0 10px 0;
        text-shadow: 0 0 30px rgba(0, 255, 255, 0.6);
      }

      #welcomeOverlay p {
        color: rgba(255, 255, 255, 0.6);
        font-size: 16px;
      }

      #welcomeOverlay .loading-bar {
        width: 200px;
        height: 3px;
        background: rgba(0, 255, 255, 0.2);
        margin-top: 40px;
        border-radius: 2px;
        overflow: hidden;
      }

      #welcomeOverlay .loading-bar-inner {
        width: 0%;
        height: 100%;
        background: var(--cyan);
        box-shadow: 0 0 10px var(--cyan);
        animation: loadBar 2s ease-in-out forwards;
      }

      @keyframes loadBar {
        0% {
          width: 0%;
        }

        100% {
          width: 100%;
        }
      }

      /* Header Bar (v145 style) */
      #header {
        position: fixed;
        top: 20px;
        left: 0;
        width: 100%;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        padding: 0 30px;
        z-index: 1000;
      }

      #status-display {
        background: rgba(0, 10, 20, 0.9);
        border: 1px solid var(--cyan);
        padding: 10px 20px;
        border-radius: 4px;
        font-family: 'Orbitron', sans-serif;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 8px;
        box-shadow: 0 0 20px rgba(0, 243, 255, 0.15);
        backdrop-filter: blur(8px);
      }

      .status-row {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        width: 100%;
      }

      .status-row.top {
        font-size: 0.85rem;
        letter-spacing: 2px;
        border-bottom: 1px solid rgba(0, 243, 255, 0.15);
        padding-bottom: 5px;
      }

      .status-row.bottom {
        font-size: 0.65rem;
        letter-spacing: 1px;
        color: rgba(0, 243, 255, 0.6);
        display: flex;
        align-items: center;
        gap: 15px;
      }

      #system-clock {
        display: flex;
        align-items: center;
        gap: 2px;
        font-weight: 900;
        font-size: 0.9rem;
        color: var(--cyan);
        text-shadow: 0 0 10px rgba(0, 243, 255, 0.8);
        letter-spacing: 2px;
      }

      #system-date {
        font-weight: 900;
        color: var(--cyan);
        text-shadow: 0 0 10px rgba(0, 243, 255, 0.8);
        font-size: 0.9rem;
        letter-spacing: 2px;
      }

      .stat-val {
        font-weight: 700;
      }

      .offline {
        color: #ff4444;
      }

      .online {
        color: #00ff00;
        text-shadow: 0 0 10px #00ff00;
      }

      .header-actions {
        display: flex;
        gap: 10px;
      }

      #login-btn {
        background: transparent;
        border: 1px solid var(--cyan);
        color: var(--cyan);
        padding: 10px 25px;
        border-radius: 6px;
        font-family: 'Orbitron', sans-serif;
        cursor: pointer;
        transition: 0.3s;
        font-weight: 600;
        text-decoration: none;
      }

      #login-btn:hover {
        background: var(--cyan);
        color: #000;
        box-shadow: 0 0 20px var(--cyan);
      }

      #login-btn.logged {
        border-color: #00ff00;
        color: #00ff00;
      }

      /* Hologram Container - Full Screen */
      #hologram-container {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        z-index: 100;
        pointer-events: none;
      }

      /* Scan lines effect */
      .scan-line {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: linear-gradient(to bottom, transparent 50%, rgba(0, 243, 255, 0.03) 50%);
        background-size: 100% 4px;
        z-index: 9998;
        pointer-events: none;
        opacity: 0.3;
      }

      /* Contact Button */
      #ae-contact-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 30px;
        background: rgba(0, 0, 0, 0.8);
        border: 1px solid var(--cyan);
        border-radius: 4px;
        color: var(--cyan);
        font-family: 'Orbitron', sans-serif;
        font-weight: 600;
        cursor: pointer;
        z-index: 10000;
        transition: all 0.3s ease;
        box-shadow: 0 0 30px rgba(0, 243, 255, 0.5);
        text-transform: uppercase;
        letter-spacing: 2px;
      }

      #ae-contact-btn:hover {
        background: var(--cyan);
        color: #000;
        box-shadow: 0 0 40px var(--cyan);
        transform: scale(1.05);
      }

      /* Contact Modal */
      #ae-contact-modal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.85);
        backdrop-filter: blur(10px);
        z-index: 10001;
        justify-content: center;
        align-items: center;
      }

      #ae-contact-modal.active {
        display: flex;
      }

      .ae-modal-content {
        background: linear-gradient(135deg, #0a0a1f 0%, #1a1a2e 100%);
        border: 2px solid var(--cyan);
        border-radius: 20px;
        padding: 40px;
        max-width: 600px;
        width: 90%;
        position: relative;
        box-shadow: 0 0 50px rgba(0, 255, 255, 0.3);
      }

      .ae-modal-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid rgba(0, 255, 255, 0.3);
      }

      .ae-modal-header h2 {
        color: var(--cyan);
        font-size: 24px;
        margin: 0;
        font-family: 'Orbitron', sans-serif;
      }

      .ae-modal-header p {
        color: #aaa;
        margin: 5px 0 0 0;
        font-size: 14px;
      }

      .ae-close-btn {
        position: absolute;
        top: 20px;
        right: 20px;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: rgba(255, 0, 0, 0.2);
        color: #ff0055;
        border: 1px solid #ff0055;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-weight: bold;
        font-size: 18px;
      }

      .ae-close-btn:hover {
        background: #ff0055;
        color: #fff;
      }

      .ae-form-group {
        margin-bottom: 20px;
      }

      .ae-form-group label {
        display: block;
        color: var(--cyan);
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
      }

      .ae-form-group input,
      .ae-form-group select,
      .ae-form-group textarea {
        width: 100%;
        padding: 12px;
        background: rgba(0, 255, 255, 0.05);
        border: 1px solid rgba(0, 255, 255, 0.3);
        border-radius: 8px;
        color: white;
        font-family: inherit;
        font-size: 14px;
      }

      .ae-form-group input:focus,
      .ae-form-group textarea:focus {
        border-color: var(--cyan);
        outline: none;
      }

      .ae-form-group textarea {
        min-height: 100px;
        resize: vertical;
      }

      .ae-submit-btn {
        width: 100%;
        padding: 15px;
        background: linear-gradient(135deg, var(--cyan) 0%, #0080ff 100%);
        border: none;
        border-radius: 10px;
        color: #0a0a1f;
        font-size: 18px;
        font-weight: 700;
        cursor: pointer;
        margin-top: 10px;
        font-family: 'Orbitron', sans-serif;
        letter-spacing: 2px;
        transition: transform 0.2s, box-shadow 0.2s;
      }

      .ae-submit-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 20px rgba(0, 255, 255, 0.4);
      }

      /* Footer Ticker */
      #footer-ticker {
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 30px;
        background: rgba(0, 243, 255, 0.03);
        border-top: 1px solid rgba(0, 243, 255, 0.2);
        display: flex;
        align-items: center;
        overflow: hidden;
        z-index: 100;
      }

      .ticker-text {
        white-space: nowrap;
        font-family: 'Orbitron', sans-serif;
        font-size: 0.7rem;
        letter-spacing: 4px;
        opacity: 0.7;
        animation: tickerRun 45s linear infinite;
      }

      @keyframes tickerRun {

        0%,
        2% {
          transform: translateX(0);
        }

        100% {
          transform: translateX(-100%);
        }
      }
    </style>
  </head>

  <body>

    <!-- Welcome Overlay with Auto-Enter -->
    <div id="welcomeOverlay">
      <div class="k-logo">K</div>
      <h1>KELION AI</h1>
      <p>Futuristic Hologram Platform</p>
      <div class="loading-bar">
        <div class="loading-bar-inner"></div>
      </div>
    </div>

    <!-- Header Bar (v145 style) -->
    <div id="header">
      <div id="status-display">
        <div class="status-row top">
          <span class="version-tag" style="font-weight:700; color:var(--cyan);"><?= h($version) ?> (Project K)</span>
          <span>USER: <span class="stat-val"><?= $username ?></span></span>
          <span>CLOUD: <span class="stat-val <?= $cloudClass ?>"><?= $cloudStatus ?></span></span>
        </div>
        <div class="status-row bottom">
          <div id="system-clock">
            <span id="clock-h">00</span>:<span id="clock-m">00</span>:<span id="clock-s">00</span>
          </div>
          <span id="system-date">DD-MM-YYYY</span>
        </div>
      </div>
      <div class="header-actions">
        <?php if ($u): ?>
          <a id="login-btn" class="logged" href="k.php?r=app">ENTER APP</a>
        <?php else: ?>
          <a id="login-btn" href="k.php?r=login">LOGIN</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- 3D Hologram Container -->
    <div id="hologram-container"></div>

    <!-- Scan Lines -->
    <div class="scan-line"></div>

    <!-- Contact Button -->
    <button id="ae-contact-btn"
      onclick="document.getElementById('ae-contact-modal').classList.add('active')">CONTACT</button>

    <!-- Contact Modal -->
    <div id="ae-contact-modal">
      <div class="ae-modal-content">
        <div class="ae-close-btn" onclick="document.getElementById('ae-contact-modal').classList.remove('active')">×</div>
        <div class="ae-modal-header">
          <div>
            <h2>Contact Us</h2>
            <p>We're here for you! 💙</p>
          </div>
        </div>
        <form id="ae-contact-form" method="post" action="k.php?r=contact_submit">
          <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
          <div class="ae-form-group">
            <label>📧 Your Email</label>
            <input type="email" name="email" placeholder="example@email.com" required>
          </div>
          <div class="ae-form-group">
            <label>👤 Name (Optional)</label>
            <input type="text" name="name" placeholder="Your name">
          </div>
          <div class="ae-form-group">
            <label>📋 Subject</label>
            <select name="topic" required>
              <option value="">Select a topic...</option>
              <option value="general">💬 General Question</option>
              <option value="technical">🔧 Technical Support</option>
              <option value="business">💼 Business Collaboration</option>
              <option value="feedback">⭐ Feedback & Suggestions</option>
              <option value="other">📋 Other</option>
            </select>
          </div>
          <div class="ae-form-group">
            <label>💭 Your Message</label>
            <textarea name="message" placeholder="Describe your question, issue, or suggestion..." required></textarea>
          </div>
          <button type="submit" class="ae-submit-btn">📨 Send Message</button>
        </form>
      </div>
    </div>

    <!-- Footer Ticker -->
    <div id="footer-ticker">
      <div class="ticker-text">
        ★ KELION AI • Neural Interface Active • Hologram System Online • <?= h($version) ?> • Secure Connection
        Established ★
        KELION AI • Neural Interface Active • Hologram System Online • <?= h($version) ?> • Secure Connection Established
        ★
      </div>
    </div>

    <!-- 3D Hologram Script -->
    <script src="<?= h(asset('public/assets/hologram3d.js')) ?>"></script>
    <script>
      // Clock update
      function updateClock() {
        const now = new Date();
        const pad = n => String(n).padStart(2, '0');
        document.getElementById('clock-h').textContent = pad(now.getHours());
        document.getElementById('clock-m').textContent = pad(now.getMinutes());
        document.getElementById('clock-s').textContent = pad(now.getSeconds());

        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        document.getElementById('system-date').textContent =
          days[now.getDay()] + ' ' + pad(now.getDate()) + ' ' + months[now.getMonth()] + ' ' + now.getFullYear();
      }
      updateClock();
      setInterval(updateClock, 1000);

      // Welcome message
      function speakWelcome() {
        if (!("speechSynthesis" in window)) return;
        const hour = new Date().getHours();
        let greeting = "Good morning";
        if (hour >= 12 && hour < 18) greeting = "Good afternoon";
        else if (hour >= 18 && hour < 22) greeting = "Good evening";
        else if (hour >= 22 || hour < 5) greeting = "Good night";

        speechSynthesis.cancel();
        const u = new SpeechSynthesisUtterance(greeting + ". Welcome to KELION AI.");
        u.lang = "en-GB";
        u.rate = 0.9;
        u.pitch = 0.8;

        if (window.hologram) {
          u.onstart = () => window.hologram.speak();
          u.onend = () => window.hologram.calm();
        }
        speechSynthesis.speak(u);
      }

      // User login state from PHP
      const isLoggedIn = <?= $u ? 'true' : 'false' ?>;

      // Auto-enter after loading animation
      const overlay = document.getElementById('welcomeOverlay');
      setTimeout(() => {
        overlay.style.opacity = '0';
        setTimeout(() => {
          overlay.style.display = 'none';
          // Initialize hologram after overlay fades
          if (typeof HologramUnit !== 'undefined') {
            window.hologram = new HologramUnit('hologram-container', (p) => console.log('Loading:', Math.round(p) + '%'));

            // Wait for model to load, then activate based on login state
            setTimeout(() => {
              if (window.hologram) {
                if (isLoggedIn) {
                  // User is logged in - activate eyes and full mode
                  window.hologram.activateFullMode();
                  console.log("User logged in: Hologram FULLY ACTIVATED");
                } else {
                  // User not logged in - eyes dim
                  window.hologram.deactivateEyes();
                  console.log("User not logged in: Eyes deactivated");
                }
              }
            }, 2000); // Wait for GLB to load
          }
          // Play welcome after a brief delay
          setTimeout(speakWelcome, 500);
        }, 1000);
      }, 2500);
    </script>
  </body>

  </html>
  <?php
  exit; // Don't continue with page_footer since we output full HTML
}


function render_reconnect(?string $msg = null): void
{
  require_login();
  page_header('Reconnect Subscription');

  $u = current_user();
  echo '<div class="wrap"><div class="grid">';
  echo '<div class="card">';
  echo '<h2>Reconnect Subscription</h2>';
  echo '<div class="mut">Your subscription is not active. Choose a plan and complete payment.</div>';
  if ($msg)
    echo '<div style="height:10px"></div><div class="err">' . h($msg) . '</div>';

  $plans = [];
  $res = db()->query("SELECT id,name,duration_days,price_minor,currency FROM plans WHERE active=1 ORDER BY duration_days ASC");
  while ($r = $res->fetchArray(SQLITE3_ASSOC))
    $plans[] = $r;

  echo '<div style="height:12px"></div>';
  echo '<form method="post" action="k.php?r=reconnect_start">';
  echo '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
  echo '<label class="mut">Plan</label><select name="plan_id">';
  foreach ($plans as $p) {
    $price = number_format(((int) $p['price_minor']) / 100, 2);
    echo '<option value="' . h((string) $p['id']) . '">' . h($p['name']) . ' — ' . $p['currency'] . ' ' . $price . '</option>';
  }
  echo '</select>';
  echo '<div style="height:12px"></div>';
  echo '<label class="mut">Payment method</label>
        <select name="method">
          <option value="bank">Bank transfer</option>
          <option value="paypal" disabled>PayPal (enable in config)</option>
        </select>';
  echo '<div style="height:12px"></div>';
  echo '<button class="btn btn2" type="submit">Continue to payment</button>';
  echo '</form>';

  echo '<div style="height:14px"></div>';
  echo '<a class="btn" href="k.php?r=home" style="display:inline-block;text-align:center">Back</a>';
  echo '</div>';

  echo '<div class="card">';
  echo '<h2>Bank details</h2>';
  global $CONFIG;
  $bank = $CONFIG['payments']['bank'];
  echo '<div class="mut">Fill these in config.php for production.</div><div style="height:10px"></div>';
  echo '<div class="mut"><b>Account name:</b> ' . h($bank['account_name']) . '</div>';
  echo '<div class="mut"><b>IBAN:</b> ' . h($bank['iban']) . '</div>';
  echo '<div class="mut"><b>Sort code:</b> ' . h($bank['sort_code']) . '</div>';
  echo '<div class="mut"><b>Account number:</b> ' . h($bank['account_number']) . '</div>';
  echo '<div style="height:10px"></div>';
  echo '<div class="mut">You will receive a unique reference code during checkout.</div>';
  echo '</div>';

  echo '</div></div>';
  page_footer();
}

function render_app(): void
{
  require_login();
  require_policy_consent();
  require_active_subscription();

  global $CONFIG;
  $u = current_user();
  page_header('App');

  $db = db();
  $cid = (int) $db->querySingle("SELECT id FROM conversations WHERE user_id=" . (int) $u['id'] . " ORDER BY id DESC LIMIT 1");
  if (!$cid) {
    $stmt = $db->prepare("INSERT INTO conversations(user_id,title) VALUES(:u,'Conversation')");
    $stmt->bindValue(':u', (int) $u['id'], SQLITE3_INTEGER);
    $stmt->execute();
    $cid = (int) $db->lastInsertRowID();
  }

  $hist = [];
  $stmt = $db->prepare("SELECT role,text,lang,created_at FROM conversation_messages WHERE conversation_id=:c ORDER BY id DESC LIMIT 50");
  $stmt->bindValue(':c', $cid, SQLITE3_INTEGER);
  $res = $stmt->execute();
  while ($r = $res->fetchArray(SQLITE3_ASSOC))
    $hist[] = $r;
  $hist = array_reverse($hist);

  $voices = $CONFIG['openai']['voices'] ?? ['cedar'];
  $selectedVoice = (string) ($_SESSION['voice'] ?? ($CONFIG['openai']['voice_default'] ?? 'cedar'));

  echo '<div class="wrap"><div class="grid">';
  echo '<div class="card">';
  echo '<h2>Conversation Task</h2>';
  echo '<div class="mut">Voice-first conversation with hologram.</div>';
  echo '<canvas class="canvas" id="holo"></canvas>';

  echo '<div class="row" style="margin-top:12px">';
  echo '<button class="btn" id="btnListen">🎧 Live Mic</button>';
  echo '<button class="btn" id="btnStopAll">Stop Voice</button>';
  echo '<a class="btn" href="k.php?r=vault" style="text-align:center">Vault</a>';
  if ($u['role'] === 'admin')
    echo '<a class="btn" href="k.php?r=admin" style="text-align:center">Admin</a>';
  echo '<form method="post" action="k.php?r=logout" style="margin:0">';
  echo '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
  echo '<button class="btn btnBad" type="submit">Logout</button></form>';
  echo '</div>';

  echo '<div style="height:10px"></div>';
  echo '<div class="row">';
  echo '<div><label class="mut">Voice</label><select id="voice">';
  foreach ($voices as $v) {
    $sel = ($v === $selectedVoice) ? ' selected' : '';
    echo '<option value="' . h($v) . '"' . $sel . '>' . h($v) . '</option>';
  }
  echo '</select></div>';
  echo '<div><label class="mut">Models (info)</label><input value="' . h($CONFIG['openai']['chat_model']) . ' / ' . h($CONFIG['openai']['tts_model']) . '" readonly></div>';
  echo '</div>';

  echo '</div>';

  echo '<div class="card">';
  echo '<h2>Chat</h2>';
  echo '<div class="chat" id="chat">';
  foreach ($hist as $m) {
    $cls = ($m['role'] === 'user') ? 'msg user' : 'msg bot';
    echo '<div class="' . $cls . '"><div class="mut" style="font-size:12px;margin-bottom:6px">' . h($m['role']) . ' • ' . h($m['created_at']) . '</div>' . nl2br(h($m['text'])) . '</div>';
  }
  echo '</div>';
  echo '<div style="height:10px"></div>';
  echo '<label class="mut">Message</label><textarea id="q" placeholder="Type or use microphone..."></textarea>';
  echo '<div class="row" style="margin-top:10px">';
  echo '<button class="btn btn2" id="btnAsk">Send</button>';
  echo '<button class="btn" id="btnDictate">🎤 Dictate</button>';
  echo '<button class="btn" id="btnClear">Clear</button>';
  echo '</div>';
  echo '<audio id="player" controls style="width:100%; margin-top:12px;"></audio>';
  echo '</div>';

  echo '</div></div>';

  echo '<script type="module">
    import { initHologram } from "' . h(asset('public/assets/hologram.js')) . '";
    const canvas = document.getElementById("holo");
    initHologram(canvas);

    const csrf = ' . json_encode(csrf_token()) . ';
    const chat = document.getElementById("chat");
    const q = document.getElementById("q");
    const player = document.getElementById("player");
    const voiceSel = document.getElementById("voice");

    async function setVoice(v){
      const fd = new FormData();
      fd.append("csrf", csrf);
      fd.append("voice", v);
      await fetch("k.php?r=api_set_voice", { method:"POST", body: fd }).catch(()=>null);
    }
    voiceSel.addEventListener("change", ()=>{ setVoice(voiceSel.value); });

    function addMsg(role, text){
      const d = document.createElement("div");
      d.className = "msg " + (role==="user" ? "user":"bot");
      d.innerHTML = `<div class="mut" style="font-size:12px;margin-bottom:6px">${role} • now</div>` + (text||"").replace(/\n/g,"<br>");
      chat.appendChild(d);
      chat.scrollTop = chat.scrollHeight;
    }

    async function ask(){
      const text = q.value.trim();
      if(!text) return;
      addMsg("user", text);
      q.value = "";
      window.HOLOGRAM.state="thinking"; window.HOLOGRAM.emotion="calm"; window.HOLOGRAM.focus=0.7;

      const fd = new FormData();
      fd.append("csrf", csrf);
      fd.append("q", text);

      const r = await fetch("k.php?r=api_ask", { method:"POST", body: fd });
      const j = await r.json().catch(()=>({ok:false,error:"Bad JSON"}));
      if(!j.ok){ addMsg("assistant", "Error: " + (j.error||"unknown")); window.HOLOGRAM.state="idle"; window.HOLOGRAM.focus=0; return; }
      addMsg("assistant", j.answer);
      await tts(j.answer);
    }

    async function tts(text){
      const fd = new FormData();
      fd.append("csrf", csrf);
      fd.append("text", text);
      fd.append("voice", voiceSel.value);

      const r = await fetch("k.php?r=api_tts", { method:"POST", body: fd });
      if(!r.ok){
        const msg = await r.text();
        addMsg("assistant", "TTS error: " + msg);
        window.HOLOGRAM.state="idle"; window.HOLOGRAM.focus=0;
        return;
      }
      const blob = await r.blob();
      const url = URL.createObjectURL(blob);
      player.src = url;

      let audioCtx, analyser, srcNode, data;
      function ensureAnalyser(){
        if(analyser) return;
        audioCtx = new (window.AudioContext||window.webkitAudioContext)();
        analyser = audioCtx.createAnalyser();
        analyser.fftSize = 512;
        data = new Uint8Array(analyser.fftSize);
        srcNode = audioCtx.createMediaElementSource(player);
        srcNode.connect(analyser);
        analyser.connect(audioCtx.destination);
      }
      function rms(u8){
        let sum=0;
        for(let i=0;i<u8.length;i++){
          const v=(u8[i]-128)/128; sum += v*v;
        }
        return Math.sqrt(sum/u8.length);
      }
      function tick(){
        if(!analyser) return;
        analyser.getByteTimeDomainData(data);
        const level = Math.min(1, rms(data)*6);
        window.HOLOGRAM.voiceLevel = level;
        window.HOLOGRAM.state = player.paused ? "idle" : "speaking";
        requestAnimationFrame(tick);
      }
      player.onplay = async ()=>{
        ensureAnalyser();
        try{ await audioCtx.resume(); }catch(e){}
        window.HOLOGRAM.state="speaking";
        tick();
      };
      player.onended = ()=>{ window.HOLOGRAM.voiceLevel=0; window.HOLOGRAM.state="idle"; window.HOLOGRAM.focus=0; };
      player.onpause = ()=>{ window.HOLOGRAM.voiceLevel=0; window.HOLOGRAM.state="idle"; };

      try{ await player.play(); }catch(e){}
    }

    document.getElementById("btnAsk").onclick = ask;
    document.getElementById("btnClear").onclick = ()=>q.value="";

    let rec=null;
    document.getElementById("btnDictate").onclick = ()=>{
      const SR = window.SpeechRecognition || window.webkitSpeechRecognition;
      if(!SR){ alert("SpeechRecognition not supported"); return; }
      if(rec){ rec.stop(); rec=null; return; }
      rec = new SR();
      rec.continuous=false; rec.interimResults=false;
      rec.lang = (navigator.language || "en-GB");
      window.HOLOGRAM.state="listening"; window.HOLOGRAM.emotion="alert"; window.HOLOGRAM.focus=0.8;
      rec.onresult = (e)=>{
        const t = e.results?.[0]?.[0]?.transcript || "";
        q.value = (q.value ? (q.value+" ") : "") + t;
      };
      rec.onend = ()=>{ rec=null; window.HOLOGRAM.state="idle"; window.HOLOGRAM.focus=0; };
      rec.start();
    };

    let micStream=null, micCtx=null, micAnalyser=null, micData=null, micSrc=null, micRAF=null;
    async function startLiveMic(){
      if(micStream) return;
      try{ micStream = await navigator.mediaDevices.getUserMedia({audio:true}); }catch(e){ alert("Mic permission denied"); return; }
      micCtx = new (window.AudioContext||window.webkitAudioContext)();
      micAnalyser = micCtx.createAnalyser(); micAnalyser.fftSize=512;
      micData = new Uint8Array(micAnalyser.fftSize);
      micSrc = micCtx.createMediaStreamSource(micStream);
      micSrc.connect(micAnalyser);
      window.HOLOGRAM.state="listening"; window.HOLOGRAM.emotion="alert"; window.HOLOGRAM.focus=0.85;

      function rms(u8){ let sum=0; for(let i=0;i<u8.length;i++){ const v=(u8[i]-128)/128; sum+=v*v; } return Math.sqrt(sum/u8.length); }
      function tick(){
        if(!micAnalyser) return;
        micAnalyser.getByteTimeDomainData(micData);
        const level = Math.min(1, rms(micData)*6);
        window.HOLOGRAM.voiceLevel = level;
        window.HOLOGRAM.emotion = level>0.15 ? "active" : "alert";
        micRAF = requestAnimationFrame(tick);
      }
      tick();
    }
    function stopLiveMic(){
      if(micRAF) cancelAnimationFrame(micRAF); micRAF=null;
      if(micStream){ micStream.getTracks().forEach(t=>t.stop()); micStream=null; }
      if(micCtx){ try{ micCtx.close(); }catch(e){} micCtx=null; }
      micAnalyser=null; micData=null; micSrc=null;
      window.HOLOGRAM.voiceLevel=0; window.HOLOGRAM.state="idle"; window.HOLOGRAM.emotion="calm"; window.HOLOGRAM.focus=0;
    }
    document.getElementById("btnListen").onclick = async ()=>{ if(!micStream) await startLiveMic(); else stopLiveMic(); };

    document.getElementById("btnStopAll").onclick = ()=>{
      try{ speechSynthesis.cancel(); }catch(e){}
      try{ player.pause(); }catch(e){}
      window.HOLOGRAM.voiceLevel=0; window.HOLOGRAM.state="idle"; window.HOLOGRAM.focus=0;
    };

    q.addEventListener("keydown",(e)=>{
      if(e.key==="Enter" && (e.ctrlKey||e.metaKey)){ e.preventDefault(); ask(); }
    });
  </script>';

  page_footer();
}

function render_vault(): void
{
  require_login();
  require_policy_consent();
  require_active_subscription();
  $u = current_user();
  page_header('Vault');

  $db = db();
  $convos = [];
  $res = $db->query("SELECT id,title,created_at FROM conversations WHERE user_id=" . (int) $u['id'] . " ORDER BY id DESC LIMIT 20");
  while ($r = $res->fetchArray(SQLITE3_ASSOC))
    $convos[] = $r;

  echo '<div class="wrap"><div class="grid">';
  echo '<div class="card">';
  echo '<h2>Your Conversation Vault</h2>';
  echo '<div class="mut">Stored text history.</div>';
  echo '<div style="height:12px"></div>';
  foreach ($convos as $c) {
    echo '<div class="card" style="margin-bottom:10px">';
    echo '<div class="row"><div><b>' . h($c['title'] ?: 'Conversation') . '</b><div class="mut">' . h($c['created_at']) . '</div></div>';
    echo '<div><a class="btn" href="k.php?r=vault_view&id=' . (int) $c['id'] . '" style="text-align:center;display:block">Open</a></div></div>';
    echo '</div>';
  }
  echo '<a class="btn" href="k.php?r=app" style="display:inline-block;text-align:center">Back</a>';
  echo '</div>';

  echo '<div class="card">';
  echo '<h2>Account</h2>';
  echo '<div class="mut">Username: ' . h($u['username']) . '</div>';
  echo '<div class="mut">Role: ' . h($u['role']) . '</div>';
  echo '</div>';

  echo '</div></div>';
  page_footer();
}

function render_vault_view(int $id): void
{
  require_login();
  require_active_subscription();
  $u = current_user();
  $db = db();
  $cid = $id;
  $owner = (int) $db->querySingle("SELECT user_id FROM conversations WHERE id=" . (int) $cid);
  if ($owner !== (int) $u['id'] && $u['role'] !== 'admin') {
    http_response_code(403);
    exit("Forbidden");
  }

  page_header('Vault View');
  $msgs = [];
  $stmt = $db->prepare("SELECT role,text,lang,created_at FROM conversation_messages WHERE conversation_id=:c ORDER BY id ASC");
  $stmt->bindValue(':c', $cid, SQLITE3_INTEGER);
  $res = $stmt->execute();
  while ($r = $res->fetchArray(SQLITE3_ASSOC))
    $msgs[] = $r;

  echo '<div class="wrap"><div class="card">';
  echo '<h2>Conversation</h2>';
  echo '<div class="chat">';
  foreach ($msgs as $m) {
    $cls = ($m['role'] === 'user') ? 'msg user' : 'msg bot';
    echo '<div class="' . $cls . '"><div class="mut" style="font-size:12px;margin-bottom:6px">' . h($m['role']) . ' • ' . h($m['created_at']) . '</div>' . nl2br(h($m['text'])) . '</div>';
  }
  echo '</div>';
  echo '<div style="height:12px"></div><a class="btn" href="k.php?r=vault" style="display:inline-block;text-align:center">Back</a>';
  echo '</div></div>';
  page_footer();
}

function render_admin(?string $msg = null): void
{
  require_admin();
  page_header('Admin');

  $db = db();
  $users = [];
  $res = $db->query("SELECT id,username,role,email,last_login_at,created_at FROM users ORDER BY id DESC LIMIT 50");
  while ($r = $res->fetchArray(SQLITE3_ASSOC))
    $users[] = $r;

  $activeSubs = (int) $db->querySingle("SELECT COUNT(*) FROM subscriptions WHERE status='active'");
  $events24 = (int) $db->querySingle("SELECT COUNT(*) FROM traffic_events WHERE created_at >= datetime('now','-1 day')");

  echo '<div class="wrap"><div class="grid">';
  echo '<div class="card">';
  echo '<h2>Admin Dashboard</h2>';
  if ($msg)
    echo '<div class="ok">' . h($msg) . '</div>';
  echo '<div class="mut">Traffic (last 24h): <b>' . $events24 . '</b> • Active subs: <b>' . $activeSubs . '</b></div>';
  echo '<div style="height:12px"></div>';

  echo '<h3>Create user</h3>';
  echo '<form method="post" action="k.php?r=admin_create_user">';
  echo '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
  echo '<div class="row"><div><label class="mut">Username</label><input name="username" required></div>';
  echo '<div><label class="mut">Password</label><input name="password" type="password" required></div></div>';
  echo '<div style="height:10px"></div>';
  echo '<div class="row"><div><label class="mut">Role</label>
        <select name="role"><option>user</option><option>admin</option><option>demo</option></select></div>
        <div><label class="mut">Email (optional)</label><input name="email" type="email"></div></div>';
  echo '<div style="height:12px"></div><button class="btn btn2" type="submit">Create</button>';
  echo '</form>';

  echo '<hr style="border:0;border-top:1px solid rgba(255,255,255,.10);margin:14px 0">';
  echo '<h3>Manual: confirm bank payment</h3>';
  echo '<form method="post" action="k.php?r=admin_confirm_bank">';
  echo '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
  echo '<label class="mut">Reference code</label><input name="ref" placeholder="KEL-XXXXXX" required>';
  echo '<div style="height:12px"></div><button class="btn" type="submit">Mark as PAID + Activate</button>';
  echo '</form>';

  echo '</div>';

  echo '<div class="card">';
  echo '<h2>Users</h2><div class="mut">Top 50</div><div style="height:10px"></div>';
  echo '<div style="overflow:auto;border:1px solid rgba(255,255,255,.10);border-radius:14px;">';
  echo '<table style="width:100%;border-collapse:collapse">';
  echo '<thead><tr class="mut"><th style="text-align:left;padding:10px;border-bottom:1px solid rgba(255,255,255,.10)">ID</th>
        <th style="text-align:left;padding:10px;border-bottom:1px solid rgba(255,255,255,.10)">User</th>
        <th style="text-align:left;padding:10px;border-bottom:1px solid rgba(255,255,255,.10)">Role</th>
        <th style="text-align:left;padding:10px;border-bottom:1px solid rgba(255,255,255,.10)">Email</th>
        <th style="text-align:left;padding:10px;border-bottom:1px solid rgba(255,255,255,.10)">Last login</th></tr></thead><tbody>';
  foreach ($users as $u) {
    echo '<tr><td style="padding:10px;border-bottom:1px solid rgba(255,255,255,.06)">' . (int) $u['id'] . '</td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,.06)">' . h($u['username']) . '</td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,.06)"><span class="pill">' . h($u['role']) . '</span></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,.06)">' . h((string) $u['email']) . '</td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,.06)"><span class="mut">' . h((string) $u['last_login_at']) . '</span></td></tr>';
  }
  echo '</tbody></table></div>';
  echo '<div style="height:12px"></div><a class="btn" href="k.php?r=app" style="display:inline-block;text-align:center">Back to App</a>';
  echo '</div>';

  echo '</div></div>';
  page_footer();
}



function render_safety(): void
{
  page_header('Safety & Non-Aggression');
  echo '<div class="wrap"><div class="card">';
  echo '<h2>Safety & Non‑Aggression Policy</h2>';
  echo '<div class="mut">This platform is designed for non‑aggressive, helpful, respectful interaction.</div>';
  echo '<div style="height:12px"></div>';
  echo '<ul class="mut" style="line-height:1.7">
    <li>No instructions for violence, weapons, threats, harassment, hate, or illegal wrongdoing.</li>
    <li>No self‑harm encouragement or instructions. If you feel unsafe, contact emergency services.</li>
    <li>No sexual content involving minors (zero tolerance). No explicit sexual content or pornography.</li>
    <li>We may refuse or redirect requests that could cause harm.</li>
  </ul>';
  echo '<div style="height:12px"></div>';
  echo '<div class="mut">Contact: <b>contact@kelionai.app</b></div>';
  echo '</div></div>';
  page_footer();
}

function render_terms(): void
{
  page_header('Terms');
  echo '<div class="wrap"><div class="card">';
  echo '<h2>Terms (Summary)</h2>';
  echo '<div class="mut" style="line-height:1.7">
    By using KELION AI you agree to use it lawfully and safely. This service is intended for users aged 18+. You are responsible for any content you submit.
    Service availability and accuracy are not guaranteed. Paid plans unlock access to the conversation task.
    For a production deployment, add full legal Terms drafted for your jurisdiction.
  </div>';
  echo '<div style="height:12px"></div>';
  echo '<div class="mut">Contact: <b>contact@kelionai.app</b></div>';
  echo '</div></div>';
  page_footer();
}

function render_privacy(): void
{
  page_header('Privacy & GDPR');
  echo '<div class="wrap"><div class="grid">';
  echo '<div class="card">';
  echo '<h2>Privacy & GDPR (UK/EU) – Practical Summary</h2>';
  echo '<div class="mut" style="line-height:1.7">
    KELION AI stores the minimum data needed to operate accounts and subscriptions: username, optional email, login timestamps, subscriptions/payments status, and conversation text (Vault).
    Audio is generated on demand and not stored by default.
  </div>';

  echo '<div style="height:12px"></div>';
  echo '<h3>Your rights</h3>';
  echo '<ul class="mut" style="line-height:1.7">
    <li>Right to be informed (what we collect and why)</li>
    <li>Right of access (export your data)</li>
    <li>Right to rectification (update inaccurate data)</li>
    <li>Right to erasure (delete your account)</li>
    <li>Right to restrict processing / object (ask us to stop certain processing)</li>
    <li>Right to data portability (download your data in a usable format)</li>
  </ul>';

  echo '<div style="height:12px"></div>';
  echo '<div class="mut">Requests: <b>contact@kelionai.app</b> (include your username).</div>';
  echo '<div style="height:12px"></div>';
  echo '<div class="mut"><b>Security:</b> CSRF protection, secure cookies, basic security headers, rate limiting for login/AI (configurable).</div>';
  echo '</div>';

  echo '<div class="card">';
  echo '<h2>Self‑service GDPR tools</h2>';
  $u = current_user();
  if (!$u) {
    echo '<div class="mut">Login to export or delete your data.</div>';
    echo '<div style="height:12px"></div><a class="btn btn2" href="k.php?r=login" style="display:inline-block;text-align:center">Login</a>';
  } else {
    echo '<div class="mut">Logged in as <b>' . h($u['username']) . '</b></div>';
    echo '<div style="height:12px"></div>';
    echo '<div class="row">';
    echo '<a class="btn btn2" href="k.php?r=gdpr_export" style="text-align:center;display:block">Export my data (JSON)</a>';
    echo '<a class="btn" href="k.php?r=account" style="text-align:center;display:block">Account settings</a>';
    echo '</div>';
  }
  echo '</div>';

  echo '</div></div>';
  page_footer();
}

function render_account(?string $msg = null, ?string $err = null): void
{
  require_login();
  page_header('Account');
  $u = current_user();
  echo '<div class="wrap"><div class="grid">';
  echo '<div class="card">';
  echo '<h2>Account settings</h2>';
  if ($msg)
    echo '<div class="ok">' . h($msg) . '</div><div style="height:10px"></div>';
  if ($err)
    echo '<div class="err">' . h($err) . '</div><div style="height:10px"></div>';

  echo '<h3>Rectify (update email)</h3>';
  echo '<form method="post" action="k.php?r=account_update_email">';
  echo '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
  echo '<label class="mut">Email</label><input type="email" name="email" value="' . h((string) $u['email']) . '" placeholder="name@example.com">';
  echo '<div style="height:12px"></div><button class="btn btn2" type="submit">Save</button>';
  echo '</form>';

  echo '<hr style="border:0;border-top:1px solid rgba(255,255,255,.10);margin:14px 0">';

  echo '<h3>Age requirement</h3><div class="mut">This service is intended for users aged <b>18+</b>.</div><form method="post" action="k.php?r=account_set_age" style="margin-top:10px"><input type="hidden" name="csrf" value="' . h(csrf_token()) . '"><label class="mut"><input type="checkbox" name="age" value="1" required> I confirm I am 18 years or older.</label><div style="height:12px"></div><button class="btn btn2" type="submit">Confirm age</button></form><hr style="border:0;border-top:1px solid rgba(255,255,255,.10);margin:14px 0"><h3>Consent</h3>';
  $db = db();
  $policyVer = 'v1';
  $hasConsent = (int) $db->querySingle("SELECT COUNT(*) FROM consents WHERE user_id=" . (int) $u['id'] . " AND policy_version='" . SQLite3::escapeString($policyVer) . "'");
  if ($hasConsent > 0) {
    echo '<div class="mut">Status: <b>Accepted</b> (policy ' . $policyVer . ')</div>';
  } else {
    echo '<div class="mut">Status: <b>Not accepted</b> (policy ' . $policyVer . ')</div>';
  }
  echo '<form method="post" action="k.php?r=account_accept_policy" style="margin-top:10px">';
  echo '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
  echo '<button class="btn" type="submit">Accept current policy</button>';
  echo '</form>';

  echo '<hr style="border:0;border-top:1px solid rgba(255,255,255,.10);margin:14px 0">';

  echo '<h3>Right to erasure (Delete account)</h3>';
  echo '<div class="mut">This will permanently delete your account and your conversation vault.</div>';
  echo '<form method="post" action="k.php?r=gdpr_delete" style="margin-top:10px">';
  echo '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
  echo '<label class="mut">Confirm password</label><input type="password" name="password" required>';
  echo '<div style="height:12px"></div><button class="btn btnBad" type="submit">Delete my account</button>';
  echo '</form>';

  echo '<div style="height:12px"></div><a class="btn" href="k.php?r=app" style="display:inline-block;text-align:center">Back</a>';
  echo '</div>';

  echo '<div class="card">';
  echo '<h2>Notes</h2>';
  echo '<div class="mut" style="line-height:1.7">
    For production: add full privacy notice (controller details, lawful basis, retention, subprocessors),
    implement email verification + password reset, and automate expiry reminders.
  </div>';
  echo '</div>';

  echo '</div></div>';
  page_footer();
}

// ------------------- API -------------------
if ($r === 'api_set_voice' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  require_login();
  csrf_check();
  $voice = trim((string) ($_POST['voice'] ?? ''));
  global $CONFIG;
  $allowed = $CONFIG['openai']['voices'] ?? [];
  if ($voice !== '' && in_array($voice, $allowed, true))
    $_SESSION['voice'] = $voice;
  json_out(['ok' => true, 'voice' => ($_SESSION['voice'] ?? null)]);
}

if ($r === 'api_ask' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  global $CONFIG;
  $uRate = current_user();
  $limit = (int) ($CONFIG['security']['rate_limit']['ai_per_user_per_day'] ?? 500);
  if ($uRate && !rate_limit_hit('ai:' . $uRate['id'], $limit, 86400)) {
    json_out(['ok' => false, 'error' => 'Daily AI limit reached.'], 429);
  }

  require_login();
  require_active_subscription();
  csrf_check();
  $u = current_user();
  $q = trim((string) ($_POST['q'] ?? ''));
  if ($q === '')
    json_out(['ok' => false, 'error' => 'Empty message'], 400);

  $db = db();
  $cid = (int) $db->querySingle("SELECT id FROM conversations WHERE user_id=" . (int) $u['id'] . " ORDER BY id DESC LIMIT 1");
  if (!$cid) {
    $stmt = $db->prepare("INSERT INTO conversations(user_id,title) VALUES(:u,'Conversation')");
    $stmt->bindValue(':u', (int) $u['id'], SQLITE3_INTEGER);
    $stmt->execute();
    $cid = (int) $db->lastInsertRowID();
  }
  $stmt = $db->prepare("INSERT INTO conversation_messages(conversation_id,role,text,lang) VALUES(:c,'user',:t,'AUTO')");
  $stmt->bindValue(':c', $cid, SQLITE3_INTEGER);
  $stmt->bindValue(':t', $q, SQLITE3_TEXT);
  $stmt->execute();

  $ans = openai_answer($q, 'AUTO');
  if (!$ans['ok']) {
    $err = $ans['error'] ?? 'AI error';
    $stmt = $db->prepare("INSERT INTO conversation_messages(conversation_id,role,text,lang) VALUES(:c,'assistant',:t,'AUTO')");
    $stmt->bindValue(':c', $cid, SQLITE3_INTEGER);
    $stmt->bindValue(':t', $err, SQLITE3_TEXT);
    $stmt->execute();
    json_out(['ok' => false, 'error' => $err], 500);
  }

  $text = $ans['text'];
  $stmt = $db->prepare("INSERT INTO conversation_messages(conversation_id,role,text,lang) VALUES(:c,'assistant',:t,'AUTO')");
  $stmt->bindValue(':c', $cid, SQLITE3_INTEGER);
  $stmt->bindValue(':t', $text, SQLITE3_TEXT);
  $stmt->execute();

  json_out(['ok' => true, 'answer' => $text]);
}

if ($r === 'api_tts' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  require_login();
  require_active_subscription();
  csrf_check();
  $text = trim((string) ($_POST['text'] ?? ''));
  if ($text === '') {
    http_response_code(400);
    exit("Empty text");
  }
  if (mb_strlen($text) > 1200)
    $text = mb_substr($text, 0, 1200);

  $voice = trim((string) ($_POST['voice'] ?? ''));
  if ($voice === '')
    $voice = (string) ($_SESSION['voice'] ?? '');

  $tts = openai_tts_mp3($text, $voice ?: null);
  if (!$tts['ok']) {
    http_response_code(400);
    exit("TTS error: " . ($tts['error'] ?? 'unknown'));
  }

  header('Content-Type: audio/mpeg');
  header('Content-Disposition: inline; filename="kelion_tts.mp3"');
  echo $tts['bin'];
  exit;
}

// ------------------- POST handlers -------------------
if ($r === 'login_post' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  // Rate limit login attempts per IP
  global $CONFIG;
  $limit = (int) ($CONFIG['security']['rate_limit']['login_per_ip_per_10min'] ?? 25);
  if (!rate_limit_hit('login:' . ip_hash(), $limit, 600)) {
    render_login('Too many attempts. Try again later.');
    exit;
  }

  csrf_check();
  $u = trim((string) ($_POST['username'] ?? ''));
  $p = (string) ($_POST['password'] ?? '');
  if (login_attempt($u, $p))
    redirect('k.php?r=app');
  render_login("Invalid username or password.");
  exit;
}
if ($r === 'logout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  logout_now();
  redirect('k.php?r=home');
}

if ($r === 'reconnect_start' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  require_login();
  csrf_check();
  $u = current_user();
  $planId = (int) ($_POST['plan_id'] ?? 0);
  $method = (string) ($_POST['method'] ?? 'bank');

  $db = db();
  $plan = $db->querySingle("SELECT id,name,duration_days,price_minor,currency FROM plans WHERE id=" . $planId . " AND active=1", true);
  if (!$plan) {
    render_reconnect("Invalid plan.");
    exit;
  }

  $stmt = $db->prepare("INSERT INTO subscriptions(user_id,plan_id,status) VALUES(:u,:p,'past_due')");
  $stmt->bindValue(':u', (int) $u['id'], SQLITE3_INTEGER);
  $stmt->bindValue(':p', (int) $plan['id'], SQLITE3_INTEGER);
  $stmt->execute();
  $sid = (int) $db->lastInsertRowID();

  $reference = null;
  if ($method === 'bank') {
    global $CONFIG;
    $prefix = $CONFIG['payments']['bank']['reference_prefix'] ?? 'KEL';
    $reference = $prefix . '-' . strtoupper(bin2hex(random_bytes(3)));
  }

  $stmt = $db->prepare("INSERT INTO payments(user_id,subscription_id,method,amount_minor,currency,status,reference_code) VALUES(:u,:s,:m,:a,:c,'pending',:r)");
  $stmt->bindValue(':u', (int) $u['id'], SQLITE3_INTEGER);
  $stmt->bindValue(':s', $sid, SQLITE3_INTEGER);
  $stmt->bindValue(':m', $method, SQLITE3_TEXT);
  $stmt->bindValue(':a', (int) $plan['price_minor'], SQLITE3_INTEGER);
  $stmt->bindValue(':c', (string) $plan['currency'], SQLITE3_TEXT);
  $stmt->bindValue(':r', $reference, $reference ? SQLITE3_TEXT : SQLITE3_NULL);
  $stmt->execute();

  if ($method === 'bank')
    redirect('k.php?r=pay_bank&sid=' . $sid);
  render_reconnect("PayPal is disabled in config. Choose bank transfer.");
  exit;
}

if ($r === 'pay_bank') {
  require_login();
  $sid = (int) ($_GET['sid'] ?? 0);
  $u = current_user();
  $db = db();

  $pay = $db->querySingle("
    SELECT pay.*, p.name as plan_name
    FROM payments pay
    JOIN subscriptions s ON s.id=pay.subscription_id
    JOIN plans p ON p.id=s.plan_id
    WHERE pay.subscription_id=" . $sid . " AND pay.user_id=" . (int) $u['id'] . "
    ORDER BY pay.id DESC LIMIT 1
  ", true);

  if (!$pay) {
    render_reconnect("Payment not found.");
    exit;
  }

  page_header('Bank Transfer Payment');
  echo '<div class="wrap"><div class="grid">';
  echo '<div class="card">';
  echo '<h2>Bank Transfer</h2>';
  echo '<div class="mut">Send the amount and include the unique reference code.</div>';
  echo '<div style="height:12px"></div>';
  echo '<div class="mut"><b>Plan:</b> ' . h($pay['plan_name']) . '</div>';
  echo '<div class="mut"><b>Amount:</b> ' . h($pay['currency']) . ' ' . number_format(((int) $pay['amount_minor']) / 100, 2) . '</div>';
  echo '<div style="height:10px"></div>';
  echo '<div class="card"><div class="mut">Reference code (IMPORTANT):</div><h2 style="margin:8px 0">' . h((string) $pay['reference_code']) . '</h2></div>';
  echo '<div class="mut" style="margin-top:10px">Admin confirms payment in Admin Dashboard.</div>';
  echo '<div style="height:14px"></div>';
  echo '<a class="btn" href="k.php?r=home" style="display:inline-block;text-align:center">Back</a>';
  echo '</div>';
  echo '<div class="card"><h2>Pending status</h2><div class="mut">Awaiting confirmation.</div></div>';
  echo '</div></div>';
  page_footer();
  exit;
}

if ($r === 'admin_create_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  require_admin();
  csrf_check();
  $username = trim((string) ($_POST['username'] ?? ''));
  $password = (string) ($_POST['password'] ?? '');
  $role = (string) ($_POST['role'] ?? 'user');
  $email = trim((string) ($_POST['email'] ?? ''));

  if ($username === '' || $password === '') {
    render_admin("Missing username/password.");
    exit;
  }
  if (!in_array($role, ['user', 'admin', 'demo'], true))
    $role = 'user';

  $hash = password_hash($password, PASSWORD_DEFAULT);
  $stmt = db()->prepare("INSERT INTO users(username,email,passhash,role,email_verified) VALUES(:u,:e,:p,:r,1)");
  $stmt->bindValue(':u', $username, SQLITE3_TEXT);
  $stmt->bindValue(':e', $email === '' ? null : $email, $email === '' ? SQLITE3_NULL : SQLITE3_TEXT);
  $stmt->bindValue(':p', $hash, SQLITE3_TEXT);
  $stmt->bindValue(':r', $role, SQLITE3_TEXT);
  try {
    $stmt->execute();
    render_admin("User created.");
  } catch (Throwable $e) {
    render_admin("Error: " . $e->getMessage());
  }
  exit;
}

if ($r === 'admin_confirm_bank' && $_SERVER['REQUEST_METHOD'] === 'POST') {
  require_admin();
  csrf_check();
  $ref = strtoupper(trim((string) ($_POST['ref'] ?? '')));
  if ($ref === '') {
    render_admin("Missing reference code.");
    exit;
  }

  $db = db();
  $pay = $db->querySingle("SELECT * FROM payments WHERE method='bank' AND status='pending' AND reference_code='" . SQLite3::escapeString($ref) . "' ORDER BY id DESC LIMIT 1", true);
  if (!$pay) {
    render_admin("No pending bank payment found for that reference.");
    exit;
  }

  $stmt = $db->prepare("UPDATE payments SET status='paid', paid_at=datetime('now') WHERE id=:i");
  $stmt->bindValue(':i', (int) $pay['id'], SQLITE3_INTEGER);
  $stmt->execute();

  $sub = $db->querySingle("SELECT * FROM subscriptions WHERE id=" . (int) $pay['subscription_id'], true);
  $plan = $db->querySingle("SELECT * FROM plans WHERE id=" . (int) $sub['plan_id'], true);
  $days = (int) $plan['duration_days'];
  $stmt = $db->prepare("UPDATE subscriptions SET status='active', starts_at=datetime('now'), ends_at=datetime('now','+" . $days . " day') WHERE id=:i");
  $stmt->bindValue(':i', (int) $sub['id'], SQLITE3_INTEGER);
  $stmt->execute();

  render_admin("Payment confirmed and subscription activated for " . $ref . ".");
  exit;
}


if ($r === 'gdpr_export') {
  require_login();
  $u = current_user();
  $db = db();

  $user = $db->querySingle("SELECT id,username,email,role,status,created_at,last_login_at FROM users WHERE id=" . (int) $u['id'], true);

  $subs = [];
  $res = $db->query("SELECT s.*, p.name as plan_name, p.duration_days, p.price_minor, p.currency FROM subscriptions s JOIN plans p ON p.id=s.plan_id WHERE s.user_id=" . (int) $u['id'] . " ORDER BY s.id DESC");
  while ($r2 = $res->fetchArray(SQLITE3_ASSOC))
    $subs[] = $r2;

  $pays = [];
  $res = $db->query("SELECT id,method,amount_minor,currency,status,reference_code,provider_ref,created_at,paid_at FROM payments WHERE user_id=" . (int) $u['id'] . " ORDER BY id DESC");
  while ($r2 = $res->fetchArray(SQLITE3_ASSOC))
    $pays[] = $r2;

  $convos = [];
  $res = $db->query("SELECT id,title,created_at FROM conversations WHERE user_id=" . (int) $u['id'] . " ORDER BY id DESC");
  while ($c = $res->fetchArray(SQLITE3_ASSOC)) {
    $msgs = [];
    $stmt = $db->prepare("SELECT role,text,lang,created_at FROM conversation_messages WHERE conversation_id=:c ORDER BY id ASC");
    $stmt->bindValue(':c', (int) $c['id'], SQLITE3_INTEGER);
    $mres = $stmt->execute();
    while ($m = $mres->fetchArray(SQLITE3_ASSOC))
      $msgs[] = $m;
    $c['messages'] = $msgs;
    $convos[] = $c;
  }

  $data = [
    'exported_at' => gmdate('c'),
    'user' => $user,
    'subscriptions' => $subs,
    'payments' => $pays,
    'conversations' => $convos,
  ];

  header('Content-Type: application/json; charset=utf-8');
  header('Content-Disposition: attachment; filename="kelion_export_user_' . $u['id'] . '.json"');
  echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
  exit;
}

// --------------- Routes ---------------
switch ($r) {
  case 'home':
    render_home();
    break;
  case 'login':
    render_login();
    break;
  case 'reconnect':
    render_reconnect();
    break;
  case 'app':
    render_app();
    break;
  case 'vault':
    render_vault();
    break;
  case 'vault_view':
    render_vault_view((int) ($_GET['id'] ?? 0));
    break;
  case 'admin':
    render_admin();
    break;
  case 'privacy':
    render_privacy();
    break;
  case 'terms':
    render_terms();
    break;
  case 'safety':
    render_safety();
    break;
  case 'account':
    render_account();
    break;
  default:
    render_home();
}
