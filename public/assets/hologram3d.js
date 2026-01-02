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

        // Audio
        this.analyser = null;
        this.dataArray = null;
        this.audioCtx = null;

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

        // Voice intensity calculation
        let voiceInt = 0;
        if (this.state === 'speaking') {
            const t = Date.now() * 0.02;
            voiceInt = (Math.sin(t) * 0.5 + 0.5) * (Math.random() * 0.5 + 0.5);
        }

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
}

// Export for module use
if (typeof window !== 'undefined') {
    window.HologramUnit = HologramUnit;
}

