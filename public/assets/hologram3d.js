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
        this.loadResources();
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

        // Try to load hologram.glb, fallback to generated head
        loader.load('/hologram.glb', (gltf) => {
            this.setupModel(gltf);
        }, undefined, (e) => {
            console.warn("GLB not found, creating procedural head...");
            this.createProceduralHead();
        });
    }

    setupModel(gltf) {
        this.model = gltf.scene;

        this.model.traverse(node => {
            if (node.isMesh) {
                console.log("Mesh found:", node.name);
                if (node.morphTargetInfluences) this.morphMeshes.push(node);
                node.userData.originalMesh = true;

                // Apply reptile skin material
                node.material = this.createReptileSkinMaterial(node.name);
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
            gltf.animations.forEach(clip => {
                this.animations[clip.name] = this.mixer.clipAction(clip);
                this.animations[clip.name].clampWhenFinished = true;
            });
        }

        this.playAnim('Idle');
        console.log("Hologram Brain: ACTIVE.");
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

        this.scene.add(this.model);
        this.mixer = { update: () => { } };
        console.log("Procedural Hologram: ACTIVE.");
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
        const key = Object.keys(this.animations).find(k => k.toLowerCase().includes(name.toLowerCase()));
        if (key && this.animations[key]) {
            if (this.mixer.stopAllAction) this.mixer.stopAllAction();
            this.animations[key].reset().fadeIn(0.5).play();
        }
    }

    update(delta) {
        if (this.mixer && this.mixer.update) this.mixer.update(delta);

        // Update lip sync (analyzes audio and animates mouth)
        this.updateLipSync(delta);

        // Voice intensity calculation (now handled by updateLipSync)
        let voiceInt = this.voiceIntensity || 0;

        if (this.autoActive) {
            // Breathing animation
            this.breathingPhase += delta * 1.5;
            const breath = Math.sin(this.breathingPhase) * 0.3;
            const currentEmissive = this.baseEmissive + breath;

            // Apply subtle pulsing to materials
            if (this.model) {
                this.model.traverse(node => {
                    if (node.isMesh && node.material && node.material.emissiveIntensity !== undefined) {
                        node.material.emissiveIntensity = currentEmissive + 0.3;
                    }
                });
            }

            // Subtle idle rotation
            if (this.model && this.state === 'idle') {
                const t = Date.now() * 0.0005;
                this.model.rotation.y = Math.sin(t) * 0.1;
            }
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
            this.model.scale.set(1, 1, 1);
            const box = new THREE.Box3().setFromObject(this.model);
            const size = box.getSize(new THREE.Vector3());

            const center = box.getCenter(new THREE.Vector3());
            this.model.position.x = -center.x;
            this.model.position.y = -center.y;
            this.model.position.z = -center.z;

            const dist = this.camera.position.z || 1.8;
            const vFOV = THREE.MathUtils.degToRad(this.camera.fov);
            const visibleHeight = 2 * Math.tan(vFOV / 2) * dist;

            const desiredH = visibleHeight * 0.7;
            const scale = desiredH / (size.y || 1);

            this.model.scale.set(scale, scale, scale);
            this.model.position.y -= visibleHeight * 0.15;
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
    }

    intensify() {
        this.state = 'thinking';
        this.baseEmissive = 2.0;
    }

    // Activate eyes with intense glow (called on login)
    activateEyes() {
        if (!this.model) return;
        this.model.traverse(node => {
            if (node.isMesh && node.name.toLowerCase().includes('eye')) {
                node.material.emissive = new THREE.Color(0x00ffff);
                node.material.emissiveIntensity = 2.0;
                node.material.color = new THREE.Color(0x00ff88);
            }
        });
        console.log("Hologram: Eyes ACTIVATED");
    }

    // Deactivate eyes (for logged out state)
    deactivateEyes() {
        if (!this.model) return;
        this.model.traverse(node => {
            if (node.isMesh && node.name.toLowerCase().includes('eye')) {
                node.material.emissive = new THREE.Color(0x003322);
                node.material.emissiveIntensity = 0.3;
                node.material.color = new THREE.Color(0x334433);
            }
        });
        console.log("Hologram: Eyes deactivated");
    }

    // Full activation mode (all functions enabled)
    activateFullMode() {
        this.activateEyes();
        this.baseEmissive = 1.0;
        this.autoActive = true;

        // Increase glow on all materials
        if (this.model) {
            this.model.traverse(node => {
                if (node.isMesh && node.material) {
                    if (!node.name.toLowerCase().includes('eye')) {
                        node.material.emissiveIntensity = 0.5;
                    }
                }
            });
        }

        // Increase bloom
        if (this.bloomPass) {
            this.bloomPass.strength = 1.2;
        }

        console.log("Hologram: FULL MODE ACTIVATED");
    }

    // === LIP SYNC SYSTEM ===

    /**
     * Connect an audio element for lip sync
     * @param {HTMLAudioElement} audioElement - The audio element to analyze
     */
    connectAudio(audioElement) {
        if (!audioElement) {
            console.warn("LipSync: No audio element provided");
            return;
        }

        try {
            // Create Audio Context if not exists
            if (!this.audioCtx) {
                this.audioCtx = new (window.AudioContext || window.webkitAudioContext)();
            }

            // Resume context if suspended (browser autoplay policy)
            if (this.audioCtx.state === 'suspended') {
                this.audioCtx.resume();
            }

            // Disconnect previous source if exists
            if (this.audioSource) {
                try {
                    this.audioSource.disconnect();
                } catch (e) { }
            }

            // Create analyzer
            this.analyser = this.audioCtx.createAnalyser();
            this.analyser.fftSize = 256;
            this.analyser.smoothingTimeConstant = 0.8;

            const bufferLength = this.analyser.frequencyBinCount;
            this.dataArray = new Uint8Array(bufferLength);

            // Connect audio source
            this.audioSource = this.audioCtx.createMediaElementSource(audioElement);
            this.audioSource.connect(this.analyser);
            this.analyser.connect(this.audioCtx.destination);

            this.currentAudioElement = audioElement;
            this.state = 'speaking';

            console.log("LipSync: Audio connected successfully");

            // Find mouth/jaw mesh for animation
            this.findMouthComponents();

        } catch (error) {
            console.error("LipSync: Error connecting audio:", error);
            // Fallback to simple speaking mode without audio analysis
            this.state = 'speaking';
        }
    }

    /**
     * Disconnect audio and stop lip sync
     */
    disconnectAudio() {
        if (this.audioSource) {
            try {
                this.audioSource.disconnect();
            } catch (e) { }
            this.audioSource = null;
        }

        this.currentAudioElement = null;
        this.analyser = null;
        this.voiceIntensity = 0;
        this.mouthOpenAmount = 0;
        this.targetMouthOpen = 0;
        this.state = 'idle';

        // Reset mouth position
        this.resetMouth();

        console.log("LipSync: Audio disconnected");
    }

    /**
     * Find mouth-related meshes and bones in the model
     */
    findMouthComponents() {
        if (!this.model) return;

        this.model.traverse(node => {
            const name = node.name.toLowerCase();

            // Look for jaw bone
            if (name.includes('jaw') || name.includes('chin')) {
                if (node.isBone || node.isObject3D) {
                    this.jawBone = node;
                    this.originalJawRotation = node.rotation.clone();
                    console.log("LipSync: Found jaw bone:", node.name);
                }
            }

            // Look for mouth mesh
            if (name.includes('mouth') || name.includes('lip') || name.includes('teeth')) {
                if (node.isMesh) {
                    this.mouthMesh = node;
                    console.log("LipSync: Found mouth mesh:", node.name);
                }
            }
        });
    }

    /**
     * Update lip sync - called every frame
     * Analyzes audio and animates mouth accordingly
     */
    updateLipSync(delta) {
        if (!this.lipSyncEnabled) return;

        // Get audio intensity
        if (this.analyser && this.dataArray && this.state === 'speaking') {
            this.analyser.getByteFrequencyData(this.dataArray);

            // Calculate average volume from frequency data (focus on voice frequencies)
            let sum = 0;
            const voiceRange = Math.floor(this.dataArray.length * 0.4); // Lower frequencies = voice
            for (let i = 0; i < voiceRange; i++) {
                sum += this.dataArray[i];
            }
            const average = sum / voiceRange;

            // Normalize to 0-1 range
            this.voiceIntensity = Math.min(average / 128, 1.0);
            this.peakIntensity = Math.max(this.peakIntensity * 0.95, this.voiceIntensity);

            // Set target mouth opening based on voice intensity
            this.targetMouthOpen = this.voiceIntensity * 0.8 + (Math.random() * 0.1); // Add slight randomness

        } else if (this.state === 'speaking') {
            // Fallback: simulate lip movement without audio analysis
            const t = Date.now() * 0.015;
            this.targetMouthOpen = (Math.sin(t) * 0.5 + 0.5) * (Math.sin(t * 1.7) * 0.3 + 0.5) * 0.6;
            this.voiceIntensity = this.targetMouthOpen;
        } else {
            this.targetMouthOpen = 0;
            this.voiceIntensity *= 0.9; // Fade out
        }

        // Smooth mouth movement
        this.mouthOpenAmount += (this.targetMouthOpen - this.mouthOpenAmount) * this.smoothingFactor;

        // Apply mouth animation
        this.animateMouth(this.mouthOpenAmount);

        // Apply visual effects based on voice intensity
        this.applyVoiceVisuals(this.voiceIntensity);
    }

    /**
     * Animate mouth opening based on intensity (0-1)
     */
    animateMouth(intensity) {
        if (!this.model) return;

        // Method 1: Animate jaw bone if available
        if (this.jawBone && this.originalJawRotation) {
            const maxJawRotation = 0.15; // Max rotation in radians
            this.jawBone.rotation.x = this.originalJawRotation.x + (intensity * maxJawRotation);
        }

        // Method 2: Use morph targets if available
        this.morphMeshes.forEach(mesh => {
            if (mesh.morphTargetInfluences && mesh.morphTargetDictionary) {
                // Look for mouth-related morph targets
                const morphNames = ['mouthOpen', 'jawOpen', 'viseme_aa', 'viseme_O', 'A', 'O', 'mouth'];

                for (const name of morphNames) {
                    const index = mesh.morphTargetDictionary[name];
                    if (index !== undefined) {
                        mesh.morphTargetInfluences[index] = intensity;
                        break;
                    }
                }
            }
        });

        // Method 3: Scale/move mouth mesh if found
        if (this.mouthMesh) {
            const baseScale = 1.0;
            const maxStretch = 0.3;
            this.mouthMesh.scale.y = baseScale + (intensity * maxStretch);
        }

        // Method 4: Fallback - animate entire head subtly
        if (!this.jawBone && !this.mouthMesh && this.morphMeshes.length === 0 && this.model) {
            // Subtle head movement when speaking
            const speakIntensity = intensity * 0.02;
            this.model.position.y += Math.sin(Date.now() * 0.01) * speakIntensity * 0.5;
        }
    }

    /**
     * Apply visual effects during speech (glow, color changes)
     */
    applyVoiceVisuals(intensity) {
        if (!this.model) return;

        this.model.traverse(node => {
            if (!node.isMesh || !node.material) return;

            const name = node.name.toLowerCase();

            // Mouth glow during speech
            if (name.includes('mouth') || name.includes('lip')) {
                node.material.emissiveIntensity = 0.3 + (intensity * 1.0);
                node.material.emissive = new THREE.Color().lerpColors(
                    new THREE.Color(0x200020),
                    new THREE.Color(0xff0066),
                    intensity
                );
            }

            // Eye intensity increases slightly when speaking
            if (name.includes('eye')) {
                node.material.emissiveIntensity = 0.8 + (intensity * 0.5);
            }

            // Subtle skin glow
            if (!name.includes('eye') && !name.includes('mouth')) {
                node.material.emissiveIntensity = 0.3 + (intensity * 0.3);
            }
        });

        // Adjust bloom intensity based on voice
        if (this.bloomPass) {
            this.bloomPass.strength = 0.8 + (intensity * 0.6);
        }
    }

    /**
     * Reset mouth to closed position
     */
    resetMouth() {
        this.mouthOpenAmount = 0;
        this.targetMouthOpen = 0;

        if (this.jawBone && this.originalJawRotation) {
            this.jawBone.rotation.copy(this.originalJawRotation);
        }

        this.morphMeshes.forEach(mesh => {
            if (mesh.morphTargetInfluences) {
                mesh.morphTargetInfluences.fill(0);
            }
        });

        if (this.mouthMesh) {
            this.mouthMesh.scale.y = 1.0;
        }
    }

    /**
     * Speak with audio - connects audio and starts lip sync
     * @param {HTMLAudioElement} audioElement - Audio to sync with
     */
    speakWithAudio(audioElement) {
        this.state = 'speaking';
        this.baseEmissive = 1.5;
        this.playAnim('Speak');

        if (audioElement) {
            this.connectAudio(audioElement);

            // Auto-disconnect when audio ends
            audioElement.addEventListener('ended', () => {
                this.onSpeakEnd();
            }, { once: true });

            audioElement.addEventListener('pause', () => {
                this.onSpeakEnd();
            }, { once: true });
        }

        console.log("Hologram: Speaking with lip sync ACTIVE");
    }

    /**
     * Called when speaking ends
     */
    onSpeakEnd() {
        this.disconnectAudio();
        this.calm();
        console.log("Hologram: Speech ended, returning to idle");
    }

    /**
     * Get current voice intensity (0-1) for external use
     */
    getVoiceIntensity() {
        return this.voiceIntensity;
    }
}

// Export for module use
if (typeof window !== 'undefined') {
    window.HologramUnit = HologramUnit;
}

