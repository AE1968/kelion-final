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

        // Watchdog & Health Check
        this.watchdogId = null;
        this.resourceAudit = { textures: 0, geometries: 0 };

        this.init();
        this.startWatchdog();
    }

    startWatchdog() {
        console.log("Watchdog: System Monitoring ACTIVE.");
        this.watchdogId = setInterval(() => {
            if (!this.renderer || !this.scene) return;

            const info = this.renderer.info;
            const currentGeoms = info.memory.geometries;

            // Detection Logic for 'Remnants'
            if (currentGeoms > 150) {
                console.warn(`Watchdog ALERT: Remnants detected (${currentGeoms} geometries). BRANCH RED.`);
                this.triggerPanic("REMNANTS DETECTED");
            } else if (this.isPanic && currentGeoms <= 150) {
                this.resolvePanic();
            }

            if (this.audioCtx && this.audioCtx.state === 'closed') {
                console.error("Watchdog ALERT: Audio Context is DEAD.");
                this.triggerPanic("AUDIO FAILURE");
            }

            // Keep audit
            this.resourceAudit.geometries = currentGeoms;
        }, 5000); // 5s check
    }

    triggerPanic(reason) {
        if (this.isPanic) return;
        this.isPanic = true;
        document.body.classList.add('system-panic');

        const alertBox = document.getElementById('watchdog-alert');
        if (alertBox) {
            alertBox.innerText = `CRITICAL: ${reason}`;
        }

        // Change Hologram Lights to RED
        if (this.frontLight) this.frontLight.color.setHex(0xff0000);
        if (this.fillLight) this.fillLight.color.setHex(0xaa0000);
        if (this.rimLight) this.rimLight.color.setHex(0xff3300);

        console.error(`!!! SYSTEM PANIC: ${reason} !!!`);
    }

    resolvePanic() {
        this.isPanic = false;
        document.body.classList.remove('system-panic');

        // Restore cyan lights
        if (this.frontLight) this.frontLight.color.setHex(0x00ffff);
        if (this.fillLight) this.fillLight.color.setHex(0x00ccff);
        if (this.rimLight) this.rimLight.color.setHex(0xff00ff);

        console.log("System Status: Normal. Branch Restored.");
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
                // Apply reptile skin material if needed, or keep original textures
                // node.material = this.createReptileSkinMaterial(node.name);
                if (node.morphTargetInfluences) {
                    this.morphMeshes.push(node);
                    console.log("Found Morph Target Mesh:", node.name);
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

        // Try playing 'Idle' or first animation
        this.playAnim('Idle');
        console.log("Hologram Brain: ACTIVE.");
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
```
            emissiveIntensity: 0.2
        });
    }

    playAnim(name) {
        if(!this.mixer || !this.animations) return;
        
        // Find best matching animation from the bought model
        // e.g. "Talking", "Speak", "Listen", "Idle"
        const key = Object.keys(this.animations).find(k => k.toLowerCase().includes(name.toLowerCase()));
        
        if (key && this.animations[key]) {
            if (this.currentAnim === key) return; // Don't restart if same
            
            // Fade out current
            if(this.currentAnim && this.animations[this.currentAnim]) {
                this.animations[this.currentAnim].fadeOut(0.5);
            }
            
            this.animations[key].reset().fadeIn(0.5).play();
            this.currentAnim = key;
            console.log(`Hologram: Playing bought animation '${key}'`);
        } else {
             console.log(`Hologram: Animation '${name}' not found.Available: `, Object.keys(this.animations));
             // Fallback to Idle if specific interaction anim missing
             if(name !== 'Idle') this.playAnim('Idle');
        }
    }

    listen() {
        this.state = 'listening';
        this.baseEmissive = 0.8;
        // Try to play a listening animation if the purchased model has one
        this.playAnim('listen'); 
        // If no 'listen' anim found, playAnim will fallback or keep current
        
        // Tilt head slightly if possible (procedural enhancement)
        if(this.model) {
             // We can Tween this if we had a tween lib, but direct set for now
             // logic in update() will handle smooth return
        }
    }

    update(delta) {
        if (this.mixer && this.mixer.update) this.mixer.update(delta);

        // Update lip sync (analyzes audio and animates mouth)
        this.updateLipSync(delta);
        
        // Procedural Idle / Listening Motion
        if(this.model && this.state === 'listening') {
             // Subtle head tilt for listening
             this.model.rotation.y = THREE.MathUtils.lerp(this.model.rotation.y, 0.2, 0.05);
             this.model.rotation.z = THREE.MathUtils.lerp(this.model.rotation.z, 0.05, 0.05);
        } else if(this.model && this.state === 'idle') {
             // Return to center
             this.model.rotation.y = THREE.MathUtils.lerp(this.model.rotation.y, 0, 0.05);
             this.model.rotation.z = THREE.MathUtils.lerp(this.model.rotation.z, 0, 0.05);
        }

        // Render
        if (this.composer) this.composer.render();
        else this.renderer.render(this.scene, this.camera);
    }

    animate() {
        this.animationFrameId = requestAnimationFrame(() => this.animate());
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
            // Rotate X usually
            // Adjust axis if needed
            this.jawBone.rotation.x = (this.originalJawRotation ? this.originalJawRotation.x : 0) + (intensity * 0.2);
        }

        // Method 2: Morph Targets (Generic)
        this.morphMeshes.forEach(mesh => {
            if (mesh.morphTargetInfluences) {
                // Try to set ALL morphs that look like 'mouth' or just the first few
                // A lot of models have 'mouthOpen' as index 0 or 1
                for (let i = 0; i < Math.min(mesh.morphTargetInfluences.length, 4); i++) {
                    mesh.morphTargetInfluences[i] = intensity;
                }
            }
        });

        // Method 3: Scale Mouth Mesh (Fallback)
        if (this.mouthMesh && !this.jawBone && this.morphMeshes.length === 0) {
            this.mouthMesh.scale.y = 0.1 + (intensity * 0.8);
        }
    // === CLEANUP & GARBAGE COLLECTION ===
    dispose() {
        console.log("Hologram: Running Code Cleanup Protocol...");
        
        // 0. Kill Watchdog
        if (this.watchdogId) clearInterval(this.watchdogId);
        
        // 1. Stop Loop
        if (this.animationFrameId) {
            cancelAnimationFrame(this.animationFrameId);
        }

        // 2. Clear Scene & Resources
        if (this.scene) {
            this.scene.traverse(object => {
                if (object.isMesh) {
                    if (object.geometry) object.geometry.dispose();
                    if (object.material) {
                        if (Array.isArray(object.material)) {
                            object.material.forEach(m => m.dispose());
                        } else {
                            object.material.dispose();
                        }
                    }
                }
            });
        }

        // 3. Kill Renderer
        if (this.renderer) {
            this.renderer.dispose();
            if (this.container && this.renderer.domElement) {
                this.container.removeChild(this.renderer.domElement);
            }
        }

        // 4. Kill Audio
        if (this.audioCtx) {
            this.audioCtx.close();
            this.audioCtx = null;
        }

        console.log("Hologram: Systems Purged. Memory Clean.");
    }
}
