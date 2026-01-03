<?php
function page_header(string $title): void
{
  global $CONFIG;
  $u = current_user();
  $name = $u ? h($u['username']) . ' <span class="pill">' . h($u['role']) . '</span>' : 'Guest';
  $ver = h((string) ($CONFIG['app']['version'] ?? 'v1'));

  echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
  echo '<title>' . h($title) . ' - KELION AI</title>';
  echo '<link rel="stylesheet" href="' . h(asset('public/assets/style.css')) . '">';
  echo '<link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;900&family=Rajdhani:wght@300;400;500;600;700&display=swap" rel="stylesheet">';

  // Three.js for mini hologram
  echo '<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>';
  echo '<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/loaders/GLTFLoader.js"></script>';

  echo '</head><body>';

  echo '<div class="hud">';
  echo '<div class="hudLeft"><div class="hudTitle">KELION AI</div><div class="hudMeta">' . $ver . ' • <span id="hudDate"></span></div></div>';
  echo '<div class="hudClock"><div class="clockFace"><span id="hudTime"></span></div></div>';
  echo '</div>';

  echo '<header class="topbar"><div class="brand">KELION<span>AI</span></div><div class="who">' . $name . '</div></header>';

  // Mini Hologram Container (fixed position, visible on all pages)
  echo '<div id="mini-hologram" style="position:fixed;bottom:80px;left:20px;width:150px;height:150px;z-index:9000;pointer-events:none;"></div>';

  // Contact Button (visible on all pages)
  echo '<button id="global-contact-btn" onclick="window.location.href=\'k.php?r=home#contact\'" style="position:fixed;bottom:20px;right:20px;padding:10px 20px;background:rgba(0,10,20,0.9);border:1px solid #00f3ff;border-radius:4px;color:#00f3ff;font-family:Orbitron,sans-serif;font-weight:600;cursor:pointer;z-index:9999;">CONTACT</button>';

  echo '<script>
  (function(){
    function pad(n){ return String(n).padStart(2,"0"); }
    function tick(){
      const d = new Date();
      const days=["Sun","Mon","Tue","Wed","Thu","Fri","Sat"];
      const months=["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"];
      const t = pad(d.getHours())+":"+pad(d.getMinutes())+":"+pad(d.getSeconds());
      const date = days[d.getDay()]+" "+pad(d.getDate())+" "+months[d.getMonth()]+" "+d.getFullYear();
      const elT=document.getElementById("hudTime"); if(elT) elT.textContent=t;
      const elD=document.getElementById("hudDate"); if(elD) elD.textContent=date;
    }
    tick(); setInterval(tick, 250);
    
    // Initialize Mini Hologram on all pages
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
      
      // Lighting
      const ambient = new THREE.AmbientLight(0xffffff, 0.5);
      scene.add(ambient);
      const front = new THREE.DirectionalLight(0xffeedd, 1.0);
      front.position.set(0, 1, 3);
      scene.add(front);
      
      let model = null;
      
      // Load model
      const loader = new THREE.GLTFLoader();
      loader.load("public/hologram.glb", function(gltf) {
        model = gltf.scene;
        
        // Apply human skin material
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
        
        // Center and scale
        const box = new THREE.Box3().setFromObject(model);
        const center = box.getCenter(new THREE.Vector3());
        model.position.sub(center);
        model.scale.set(0.8, 0.8, 0.8);
        
        scene.add(model);
      }, undefined, function(err) {
        // Fallback: simple sphere
        const geo = new THREE.SphereGeometry(0.4, 32, 32);
        const mat = new THREE.MeshPhysicalMaterial({ color: 0xe8b89d, roughness: 0.5 });
        model = new THREE.Mesh(geo, mat);
        scene.add(model);
      });
      
      // Animation
      function animate() {
        requestAnimationFrame(animate);
        if (model) {
          model.rotation.y += 0.005;
        }
        renderer.render(scene, camera);
      }
      animate();
    }
  })();
  </script>';
}
function page_footer(): void
{
  echo '<footer class="footer">KELION AI • Futuristic Hologram Platform • <a href="k.php?r=privacy">Privacy & GDPR</a> • <a href="k.php?r=terms">Terms</a> • <a href="k.php?r=safety">Safety</a></footer></body></html>';
}
