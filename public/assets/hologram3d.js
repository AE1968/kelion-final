// ========================================
// PROJECT K: HOLOGRAM CORE (HologramUnit)
// Three.js + GLB Model + Realistic Skin with Reptile Eyes
// ========================================

class HologramUnit {
    constructor(containerId, onProgress) {
        console.log("Hologram Unit: Initializing Core...");
        this.container = document.getElementById(containerId);
        this.onProgress = onProgress || function () { };
        if (!this.container) return;

        this.width = this.container.clientWidth || window.innerWidth;
        this.height = this.container.clientHeight || window.innerHeight;

        // Core Three.js components
        this.scene = null;
        this.camera = null;
        this.renderer = null;
        this.composer = null;
        this.bloomPass = null;

        // Resources
        this.model = null;
        this.mixer = null;
        this.animations = {};
        this.morphMeshes = [];
        this.clock = new THREE.Clock();

        // Mode state - REALISTIC mode with reptile eyes
        this.isRealisticMode = true;

        // AI States
        this.autoActive = true;
        this.baseEmissive = 0.0;
        this.state = 'idle';

        // Timers
        this.lastBlinkTime = 0;
        this.nextBlinkDelay = 3000;
        this.breathingPhase = 0;

        // Audio & Lip Sync
        this.analyser = null;
        this.dataArray = null;
        this.audioCtx = null;
        this.audioSource = null;
        this.currentAudioElement = null;

        // Lip Sync state
        this.lipSyncEnabled = true;
        this.mouthOpenAmount = 0;
        this.targetMouthOpen = 0;
        this.smoothingFactor = 0.3;  // Smoothing for mouth movement
        this.mouthMesh = null;
        this.jawBone = null;
        this.originalJawRotation = null;

        // Voice intensity tracking
        this.voiceIntensity = 0;
        this.peakIntensity = 0;

        this.init();
    }

    init() {
        this.setupScene();
        this.setupRenderer();
        try {
            this.loadResources();
        } catch (e) {
            console.error("Init Error:", e);
        }
        this.setupEvents();
        this.animate();
    }

    setupScene() {
        this.scene = new THREE.Scene();
        // Dark background for reptile hologram
        this.scene.background = new THREE.Color(0x0a0a15);

        // Camera portrait setup
        this.camera = new THREE.PerspectiveCamera(35, this.width / this.height, 0.1, 1000);
        this.camera.position.set(0, 0.1, 1.8);

        // Realistic lighting for skin visibility
        this.ambientLight = new THREE.AmbientLight(0x00aaff, 0.3);
        this.scene.add(this.ambientLight);

        // Key light (main frontal) - cyan tint
        this.frontLight = new THREE.DirectionalLight(0x00ffff, 1.5);
        this.frontLight.position.set(0, 1, 3);
        this.scene.add(this.frontLight);

        // Fill light (softer, from side)
        this.fillLight = new THREE.DirectionalLight(0x00ccff, 0.8);
        this.fillLight.position.set(-2, 0.5, 2);
        this.scene.add(this.fillLight);

        // Rim/back light for edge definition
        this.rimLight = new THREE.PointLight(0xff00ff, 1.0, 15);
        this.rimLight.position.set(2, 1, -1);
        this.scene.add(this.rimLight);
    }

    setupRenderer() {
        this.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
        this.renderer.setSize(this.width, this.height);
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
        this.renderer.outputEncoding = THREE.sRGBEncoding;
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.container.innerHTML = '';
        this.container.appendChild(this.renderer.domElement);

        // Setup Bloom if available
        if (typeof THREE.EffectComposer !== 'undefined') {
            this.composer = new THREE.EffectComposer(this.renderer);
            const renderPass = new THREE.RenderPass(this.scene, this.camera);
            this.composer.addPass(renderPass);

            this.bloomPass = new THREE.UnrealBloomPass(
                new THREE.Vector2(this.width, this.height),
                0.8,    // Strength
                0.4,    // Radius
                0.35    // Threshold
            );
            this.composer.addPass(this.bloomPass);
        }
    }

    loadResources() {
        const manager = new THREE.LoadingManager();
        manager.onProgress = (url, itemsLoaded, itemsTotal) => {
            const percent = (itemsLoaded / itemsTotal) * 100;
            if (this.onProgress) this.onProgress(percent);
        };

        const loader = new THREE.GLTFLoader(manager);

        // Try to load hologram.glb (Softul Original)
        // Trying explicit path relative to web root
        const modelPath = 'public/hologram.glb';

        console.log("Hologram: Loading model from", modelPath);
        loader.load(modelPath, (gltf) => {
            console.log("Hologram: Model loaded successfully!");
            this.setupModel(gltf);
        }, (xhr) => {
            // Progress
            // if(this.onProgress) this.onProgress((xhr.loaded / xhr.total) * 100);
        }, (e) => {
            console.error("Hologram: Error loading GLB:", e);
            // Retry with alternate path if first fails
            console.log("Hologram: Retrying with alternate path /hologram.glb ...");
            loader.load('/hologram.glb', (gltf) => this.setupModel(gltf), undefined, () => {
                console.warn("GLB failed loading from both paths. Fallback to procedural.");
                this.createProceduralHead();
            });
        });
    }

    setupModel(gltf) {
        this.model = gltf.scene;

        this.model.traverse(node => {
            if (node.isMesh) {
                // Keep original textures from the purchased model
                // Enhance materials for holographic effect
                if (node.material) {
                    node.material.transparent = false;
                    node.material.side = THREE.FrontSide;
                    // Add subtle emissive glow
                    if (node.material.emissive) {
                        node.material.emissive.setHex(0x001122);
                        node.material.emissiveIntensity = 0.3;
                    }
                }
                if (node.morphTargetInfluences) {
                    this.morphMeshes.push(node);
                    console.log("Found Morph Target Mesh:", node.name, "with", node.morphTargetInfluences.length, "targets");
                }
            }
        });

        // Center & Scale
        const box = new THREE.Box3().setFromObject(this.model);
        const center = box.getCenter(new THREE.Vector3());
        this.model.position.sub(center);

        this.updateLayout();
        this.scene.add(this.model);

        // Setup Mixer
        this.mixer = new THREE.AnimationMixer(this.model);
        if (gltf.animations) {
            console.log("Animations found:", gltf.animations.map(a => a.name));
            gltf.animations.forEach(clip => {
                this.animations[clip.name] = this.mixer.clipAction(clip);
                this.animations[clip.name].clampWhenFinished = true;
            });
        }

        // Try to find mouth parts for lip sync
        this.findMouthComponents();

        // Start Blink animation loop (from purchased model)
        this.startBlinkLoop();

        // Play Idle or first animation
        this.playAnim('Idle');

        // Signal that model is fully loaded
        this.isLoaded = true;
        if (this.onReady) this.onReady();

        console.log("Hologram Brain: ACTIVE. Using purchased model animations.");
    }

    // Auto-blink using the purchased model's Blink animation
    startBlinkLoop() {
        if (!this.animations['Blink']) {
            console.log("Blink animation not found in model.");
            return;
        }

        const blinkAction = this.animations['Blink'];
        blinkAction.setLoop(THREE.LoopOnce);
        blinkAction.clampWhenFinished = true;

        const doBlink = () => {
            blinkAction.reset().play();
            // Random next blink between 2-6 seconds
            const nextDelay = 2000 + Math.random() * 4000;
            setTimeout(doBlink, nextDelay);
        };

        // Start blinking after 1 second
        setTimeout(doBlink, 1000);
        console.log("Hologram: Auto-blink loop ACTIVE.");
    }

    findMouthComponents() {
        if (!this.model) return;

        console.log("LipSync: Scanning model for mouth components...");
        let foundMorphs = false;

        this.model.traverse(node => {
            const name = node.name.toLowerCase();

            // Method 1: Jaw Bone
            if (name.includes('jaw') || name.includes('chin') || name.includes('mandible')) {
                if (node.isBone || node.isObject3D) {
                    this.jawBone = node;
                    this.originalJawRotation = node.rotation.clone();
                    console.log("LipSync: Found jaw bone:", node.name);
                }
            }

            // Method 2: Mouth/Lip Mesh
            if (name.includes('mouth') || name.includes('lip') || name.includes('teeth') || name.includes('geo')) {
                if (node.isMesh) {
                    this.mouthMesh = node;
                    console.log("LipSync: Found potential mouth mesh:", node.name);
                }
            }

            // Method 3: ANY Mesh with Morph Targets (Best bet for detailed heads)
            if (node.isMesh && node.morphTargetInfluences && node.morphTargetInfluences.length > 0) {
                console.log("LipSync: Found mesh with Morph Targets:", node.name);
                this.morphMeshes.push(node);
                foundMorphs = true;
            }
        });
    }

    createProceduralHead() {
        // Create a procedural head if GLB not available
        const geometry = new THREE.SphereGeometry(0.5, 64, 64);
        const material = this.createReptileSkinMaterial('head');
        this.model = new THREE.Mesh(geometry, material);

        // Add eyes
        const eyeGeo = new THREE.SphereGeometry(0.08, 32, 32);
        const eyeMat = this.createReptileEyeMaterial();

        const leftEye = new THREE.Mesh(eyeGeo, eyeMat);
        leftEye.position.set(-0.15, 0.1, 0.42);
        this.model.add(leftEye);

        const rightEye = new THREE.Mesh(eyeGeo, eyeMat);
        rightEye.position.set(0.15, 0.1, 0.42);
        this.model.add(rightEye);

        // ADD MOUTH (So it can speak in fallback mode)
        const mouthGeo = new THREE.SphereGeometry(0.08, 32, 32);
        const mouthMat = new THREE.MeshBasicMaterial({ color: 0x000000 }); // Black mouth
        this.mouthMesh = new THREE.Mesh(mouthGeo, mouthMat);
        this.mouthMesh.position.set(0, -0.2, 0.42);
        this.mouthMesh.scale.set(1, 0.1, 0.5); // Closed mouth shape
        this.model.add(this.mouthMesh);

        this.scene.add(this.model);
        this.mixer = { update: () => { } };
        console.log("Procedural Hologram: ACTIVE (With Mouth).");
    }

    createReptileSkinMaterial(meshName = '') {
        const name = meshName.toLowerCase();
        if (name.includes('eye')) {
            return this.createReptileEyeMaterial();
        } else if (name.includes('mouth')) {
            return this.createMouthMaterial();
        }

        // Reptile-like skin with scales pattern
        return new THREE.MeshPhysicalMaterial({
            color: new THREE.Color(0x1a3a3a),  // Dark teal-green
            roughness: 0.4,
            metalness: 0.3,
            clearcoat: 0.6,
            clearcoatRoughness: 0.2,
            transparent: false,
            side: THREE.FrontSide,
            emissive: new THREE.Color(0x002222),
            emissiveIntensity: 0.3
        });
    }

    createReptileEyeMaterial() {
        // Slit-pupil reptile eye with glowing iris
        return new THREE.MeshPhysicalMaterial({
            color: new THREE.Color(0x00ff88),  // Bright reptile green
            roughness: 0.05,
            metalness: 0.2,
            clearcoat: 1.0,
            clearcoatRoughness: 0.05,
            transparent: false,
            side: THREE.FrontSide,
            emissive: new THREE.Color(0x00ff44),
            emissiveIntensity: 0.8
        });
    }

    createMouthMaterial() {
        return new THREE.MeshPhysicalMaterial({
            color: new THREE.Color(0x402030),
            roughness: 0.3,
            metalness: 0.0,
            clearcoat: 0.4,
            clearcoatRoughness: 0.3,
            transparent: false,
            side: THREE.FrontSide,
            emissive: new THREE.Color(0x200020),
            emissiveIntensity: 0.2
        });
    }

    playAnim(name) {
        if (!this.mixer || !this.animations) return;

        // Find best matching animation from the bought model
        // e.g. "Talking", "Speak", "Listen", "Idle"
        const key = Object.keys(this.animations).find(k => k.toLowerCase().includes(name.toLowerCase()));

        if (key && this.animations[key]) {
            if (this.currentAnim === key) return; // Don't restart if same

            // Fade out current
            if (this.currentAnim && this.animations[this.currentAnim]) {
                this.animations[this.currentAnim].fadeOut(0.5);
            }

            this.animations[key].reset().fadeIn(0.5).play();
            this.currentAnim = key;
            console.log(`Hologram: Playing bought animation '${key}'`);
        } else {
            console.log(`Hologram: Animation '${name}' not found.Available: `, Object.keys(this.animations));
            // Fallback to Idle if specific interaction anim missing
            if (name !== 'Idle') this.playAnim('Idle');
        }
    }

    listen() {
        this.state = 'listening';
        this.baseEmissive = 0.8;

        // Use the purchased model's look animation for attentive listening
        // Plays LookLeft or TurnLeft for subtle engagement
        if (this.animations['LookLeft']) {
            this.animations['LookLeft'].reset().fadeIn(0.3).play();
        } else if (this.animations['TurnLeft']) {
            this.animations['TurnLeft'].reset().fadeIn(0.3).play();
        }
        console.log("Hologram: Listening mode ACTIVE.");
    }

    // Look in a specific direction using purchased animations
    lookAt(direction) {
        const dirMap = {
            'up': 'LookUp',
            'down': 'LookDown',
            'left': 'LookLeft',
            'right': 'LookRight'
        };
        const animName = dirMap[direction.toLowerCase()];
        if (animName && this.animations[animName]) {
            this.animations[animName].reset().fadeIn(0.3).play();
        }
    }

    // Turn head in a specific direction
    turnHead(direction) {
        const dirMap = {
            'up': 'TurnUp',
            'down': 'TurnDown',
            'left': 'TurnLeft',
            'right': 'TurnRight'
        };
        const animName = dirMap[direction.toLowerCase()];
        if (animName && this.animations[animName]) {
            this.animations[animName].reset().fadeIn(0.3).play();
        }
    }

    update(delta) {
        if (this.mixer && this.mixer.update) this.mixer.update(delta);

        // Update lip sync (analyzes audio and animates mouth)
        this.updateLipSync(delta);

        // Procedural Idle / Listening Motion
        if (this.model && this.state === 'listening') {
            // Subtle head tilt for listening
            this.model.rotation.y = THREE.MathUtils.lerp(this.model.rotation.y, 0.2, 0.05);
            this.model.rotation.z = THREE.MathUtils.lerp(this.model.rotation.z, 0.05, 0.05);
        } else if (this.model && this.state === 'idle') {
            // Return to center
            this.model.rotation.y = THREE.MathUtils.lerp(this.model.rotation.y, 0, 0.05);
            this.model.rotation.z = THREE.MathUtils.lerp(this.model.rotation.z, 0, 0.05);
        }

        // Render
        if (this.composer) this.composer.render();
        else this.renderer.render(this.scene, this.camera);
    }

    animate() {
        requestAnimationFrame(() => this.animate());
        this.update(this.clock.getDelta());
    }

    setupEvents() {
        window.addEventListener('resize', () => this.updateLayout());
    }

    updateLayout() {
        this.width = this.container.clientWidth || window.innerWidth;
        this.height = this.container.clientHeight || window.innerHeight;

        if (this.renderer) {
            this.renderer.setSize(this.width, this.height);
        }
        if (this.composer) this.composer.setSize(this.width, this.height);

        if (this.camera) {
            this.camera.aspect = this.width / this.height;
            this.camera.updateProjectionMatrix();
        }

        if (this.model && this.camera) {
            // Adjust scale heavily depending on model size
            // Heuristic: scale to fit screen height
            const box = new THREE.Box3().setFromObject(this.model);
            const size = box.getSize(new THREE.Vector3());
            const center = box.getCenter(new THREE.Vector3());

            // Re-center
            this.model.position.x = -center.x;
            this.model.position.y = -center.y - (size.y * 0.1); // Lower bits
            this.model.position.z = -center.z;

            // Scale logic
            // ... simple uniform scale ...
            // let h = size.y || 1;
            // let s = 2.5 / h; 
            // this.model.scale.set(s,s,s);
        }
    }

    // === PUBLIC API ===
    speak(text) {
        this.state = 'speaking';
        this.baseEmissive = 1.5;
        this.playAnim('Speak');
    }

    calm() {
        this.state = 'idle';
        this.baseEmissive = 0.5;
        this.playAnim('Idle');
        this.voiceIntensity = 0;
        this.targetMouthOpen = 0;
    }

    intensify() {
        this.state = 'thinking';
        this.baseEmissive = 2.0;
    }

    // Alias for compatibility with k.php
    speakWithAudio(audioElement) {
        this.connectAudio(audioElement);
    }

    activateFullMode() {
        console.log("Full mode active");
    }

    // === LIP SYNC SYSTEM ===

    connectAudio(audioElement) {
        if (!audioElement) return;

        try {
            if (!this.audioCtx) {
                this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }
            if (this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }

            // Disconnect old
            if (this.audioSource) { try { this.audioSource.disconnect(); } catch (e) { } }

            this.analyser = this.audioCtx.createAnalyser();
            this.analyser.fftSize = 256;
            this.analyser.smoothingTimeConstant = 0.8;
            this.dataArray = new Uint8Array(this.analyser.frequencyBinCount);

            this.audioSource = this.audioCtx.createMediaElementSource(audioElement);
            this.audioSource.connect(this.analyser);
            this.analyser.connect(this.audioCtx.destination);

            this.currentAudioElement = audioElement;
            this.state = 'speaking';
            console.log("LipSync: Audio connected.");

            // Re-scan if needed
            if (!this.jawBone && !this.mouthMesh && this.morphMeshes.length === 0) {
                this.findMouthComponents();
            }

        } catch (error) {
            console.error("LipSync: Error connecting audio:", error);
            this.state = 'speaking'; // Fake it
        }
    }

    updateLipSync(delta) {
        if (!this.lipSyncEnabled) return;

        // Get audio intensity
        if (this.analyser && this.dataArray && this.state === 'speaking') {
            this.analyser.getByteFrequencyData(this.dataArray);
            let sum = 0;
            const voiceRange = Math.floor(this.dataArray.length * 0.4);
            for (let i = 0; i < voiceRange; i++) { sum += this.dataArray[i]; }
            const average = sum / (voiceRange || 1);
            this.voiceIntensity = Math.min(average / 100, 1.0); // Boosted sensitivity
            this.targetMouthOpen = this.voiceIntensity;
        } else if (this.state === 'speaking') {
            // Fallback fake
            this.voiceIntensity = (Math.sin(Date.now() * 0.02) + 1) * 0.5;
            this.targetMouthOpen = this.voiceIntensity;
        } else {
            this.targetMouthOpen = 0;
            this.voiceIntensity *= 0.8;
        }

        // Smooth
        this.mouthOpenAmount += (this.targetMouthOpen - this.mouthOpenAmount) * 0.4;

        this.animateMouth(this.mouthOpenAmount);
    }

    animateMouth(intensity) {
        if (!this.model) return;

        // Method 1: Jaw Bone Rotation
        if (this.jawBone) {
            this.jawBone.rotation.x = (this.originalJawRotation ? this.originalJawRotation.x : 0) + (intensity * 0.2);
        }

        // Method 2: Morph Targets (Generic)
        this.morphMeshes.forEach(mesh => {
            if (mesh.morphTargetInfluences) {
                for (let i = 0; i < Math.min(mesh.morphTargetInfluences.length, 4); i++) {
                    mesh.morphTargetInfluences[i] = intensity;
                }
            }
        });

        // Method 3: Scale Mouth Mesh (Fallback)
        if (this.mouthMesh && !this.jawBone && this.morphMeshes.length === 0) {
            this.mouthMesh.scale.y = 0.1 + (intensity * 0.8);
        }
    }

    // === LIFECYCLE & CLEANUP ===

    dispose() {
        console.log("Hologram: Disposing resources...");
        if (this.animationFrameId) cancelAnimationFrame(this.animationFrameId);
        if (this.watchdogId) clearInterval(this.watchdogId);

        this.scene.traverse(node => {
            if (node.isMesh) {
                node.geometry.dispose();
                if (Array.isArray(node.material)) {
                    node.material.forEach(mat => mat.dispose());
                } else {
                    node.material.dispose();
                }
            }
        });

        if (this.renderer) {
            this.renderer.dispose();
            this.renderer.renderLists.dispose();
        }

        if (this.audioCtx) {
            this.audioCtx.close();
        }

        if (this.container && this.renderer.domElement) {
            this.container.removeChild(this.renderer.domElement);
        }
    }

    // === WATCHDOG & HEALTH ===

    startWatchdog() {
        console.log("Watchdog: Monitor ACTIVE.");
        this.watchdogId = setInterval(() => {
            let remnants = 0;
            this.scene.traverse(n => { if (n.isMesh) remnants++; });

            if (remnants > 200) {
                this.triggerPanic(`EXCESSIVE_REMNANTS: ${remnants} meshes detected.`);
            }

            if (this.audioCtx && this.audioCtx.state === 'closed') {
                this.triggerPanic("AUDIO_CONTEXT_LOST: Core audio connection closed.");
            }
        }, 5000);
    }

    triggerPanic(reason) {
        console.error("!!! SYSTEM PANIC !!!", reason);
        document.body.classList.add('system-panic');
        const alertBox = document.getElementById('watchdog-alert');
        if (alertBox) {
            alertBox.textContent = "CRITICAL: " + reason;
            alertBox.style.display = 'block';
        }
        this.baseEmissive = 3.0;
        this.frontLight.color.set(0xff0000);
    }

    resolvePanic() {
        document.body.classList.remove('system-panic');
        const alertBox = document.getElementById('watchdog-alert');
        if (alertBox) alertBox.style.display = 'none';
        this.baseEmissive = 1.0;
        this.frontLight.color.set(0x00ffff);
    }

    // === PUBLIC API & ROUTINES ===

    activateFullMode() {
        this.state = 'idle';
        this.playAnim('Idle');
        this.baseEmissive = 1.2;
    }

    intensify(level = 2.0) {
        this.baseEmissive = level;
    }

    calm() {
        this.state = 'idle';
        this.baseEmissive = 0.8;
    }

    speak(text) {
        this.state = 'speaking';
        this.playAnim('Speak');
    }

    say(text, emotion = 'normal') {
        if (!("speechSynthesis" in window)) return;

        // Cancel any ongoing speech
        window.speechSynthesis.cancel();

        const u = new SpeechSynthesisUtterance(text);

        // Correct speech rates for natural hologram voice
        // 0.85 is optimal for clear AI speech
        u.rate = (emotion === 'energetic') ? 0.9 : (emotion === 'calm' ? 0.75 : 0.85);
        u.pitch = (emotion === 'calm') ? 0.75 : 0.9;
        u.lang = 'en-GB';
        u.volume = 1.0;

        // Show subtitle bar
        const subtitleBar = document.getElementById('subtitle-bar');
        if (subtitleBar) {
            subtitleBar.textContent = text;
            subtitleBar.classList.add('visible');
        }

        u.onstart = () => {
            this.speak(text);
            this.playAnim('Speak');
            if (emotion === 'energetic') this.intensify(2.5);
            else if (emotion === 'calm') this.intensify(0.6);
            else this.intensify(1.5);
            console.log("Hologram Speaking:", text);
        };

        u.onend = () => {
            this.calm();
            this.playAnim('Idle');
            // Hide subtitle bar after short delay
            setTimeout(() => {
                if (subtitleBar) subtitleBar.classList.remove('visible');
            }, 1500);
            if (this.onSpeechEnd) this.onSpeechEnd();
        };

        window.speechSynthesis.speak(u);
    }

    routineMorning() {
        this.say("Good morning. Systems are awakening. Energy levels at maximum. Ready for terminal access.", "energetic");
    }

    routineNoon() {
        this.say("Good afternoon. All neural nodes are synchronized. Processing capacity optimized.", "normal");
    }

    routineEvening() {
        this.say("Good evening. Transitioning to high-efficiency night cycle. Standing by for commands.", "calm");
    }

    routineGreet() {
        const h = new Date().getHours();
        if (h >= 5 && h < 12) this.routineMorning();
        else if (h >= 12 && h < 18) this.routineNoon();
        else this.routineEvening();
    }

    enableAudioInteraction() {
        const unlock = async () => {
            if (this.audioCtx && this.audioCtx.state === 'suspended') {
                await this.audioCtx.resume();
            }
        };
        document.addEventListener('click', unlock, { once: true });
        document.addEventListener('touchstart', unlock, { once: true });
    }
}
