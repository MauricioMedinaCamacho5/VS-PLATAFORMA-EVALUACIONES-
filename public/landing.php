<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VS Plataforma de Evaluaciones - SENA</title>
    <style>
        :root {
            /* Colors */
            --color-carbon-black: #000000;
            --color-paper-white: #ffffff;
            --color-warm-canvas: #e5e5e5;
            --color-mist-gray: #f3f3f3;
            --color-ash: #c6c6c6;
            --color-smoke: #979797;
            --color-slate: #444444;
            --color-graphite: #2f2f2f;
            --color-mint-chip: #d1ffca;
            --color-voltage-yellow: #fff100;

            /* Typography */
            --font-suisseintlcond: 'Bebas Neue', 'Anton', ui-sans-serif, system-ui;
            --font-suisseintl: 'Inter', ui-sans-serif, system-ui, -apple-system;
            --font-suisseintlmono: 'Monaco', ui-monospace;

            /* Spacing */
            --spacing-8: 8px;
            --spacing-16: 16px;
            --spacing-24: 24px;
            --spacing-40: 40px;
            --spacing-64: 64px;
            --spacing-80: 80px;

            /* Border Radius */
            --radius-md: 4px;
            --radius-lg: 8px;
            --radius-3xl: 24px;
            --radius-full: 48px;
            --radius-full-2: 64px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: var(--font-suisseintl);
            background: var(--color-warm-canvas);
            color: var(--color-carbon-black);
            overflow-x: hidden;
        }

        /* Header */
        header {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            background: var(--color-warm-canvas);
            padding: 1.5rem 2rem;
            height: 8rem;
            display: flex;
            align-items: center;
        }

        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .logo {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--color-carbon-black);
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-family: var(--font-suisseintlcond);
            letter-spacing: -0.03em;
        }

        .nav-buttons {
            display: flex;
            gap: 1rem;
            background: var(--color-paper-white);
            border-radius: var(--radius-full);
            padding: 0.5rem;
        }

        .btn {
            padding: 0.6rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            font-family: var(--font-suisseintl);
        }

        .btn-login {
            background: transparent;
            border: none;
            color: var(--color-slate);
        }

        .btn-login:hover {
            background: var(--color-mist-gray);
        }

        .btn-register {
            background: var(--color-carbon-black);
            color: var(--color-paper-white);
        }

        .btn-register:hover {
            transform: translateY(-2px);
        }

        /* Hero Section */
        .hero {
            min-height: 100vh;
            display: grid;
            grid-template-columns: 1fr 1fr;
            align-items: center;
            position: relative;
            margin-top: 8rem;
            overflow: hidden;
            padding: 0 2rem;
        }

        #canvas-container {
            position: absolute;
            right: 0;
            top: 0;
            width: 55%;
            height: 100%;
            z-index: 1;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            text-align: left;
            max-width: 600px;
            animation: slideInUp 1s ease-out;
        }

        @keyframes slideInUp {
            from {
                opacity: 0;
                transform: translateY(50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero h1 {
            font-size: 130px;
            font-weight: 700;
            line-height: 0.9;
            letter-spacing: -3.9px;
            margin-bottom: 2rem;
            color: var(--color-carbon-black);
            font-family: var(--font-suisseintlcond);
            text-transform: uppercase;
        }

        .hero p {
            font-size: 1.1rem;
            color: var(--color-smoke);
            margin-bottom: 2.5rem;
            line-height: 1.6;
            font-weight: 400;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            animation: slideInUp 1.2s ease-out;
        }

        .btn-hero {
            padding: 1rem 2.5rem;
            font-size: 1rem;
            border-radius: var(--radius-lg);
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            font-family: var(--font-suisseintl);
        }

        .btn-primary {
            background: var(--color-carbon-black);
            color: var(--color-paper-white);
        }

        .btn-primary:hover {
            transform: translateY(-4px);
        }

        .btn-secondary {
            background: transparent;
            border: 1.5px solid var(--color-slate);
            color: var(--color-slate);
        }

        .btn-secondary:hover {
            background: var(--color-slate);
            color: var(--color-paper-white);
            transform: translateY(-4px);
        }

        /* Features Section */
        .features {
            max-width: 1200px;
            margin: 5rem auto;
            padding: 2rem;
            position: relative;
            z-index: 10;
        }

        .features h2 {
            font-size: 80px;
            font-weight: 700;
            line-height: 0.9;
            letter-spacing: -2.4px;
            text-align: center;
            margin-bottom: 4rem;
            color: var(--color-carbon-black);
            font-family: var(--font-suisseintlcond);
            text-transform: uppercase;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: var(--color-paper-white);
            border-radius: var(--radius-3xl);
            padding: 2rem;
            transition: all 0.3s ease;
            border-top-left-radius: var(--radius-full-2);
            border-top-right-radius: var(--radius-full-2);
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .feature-card h3 {
            font-size: 1.3rem;
            margin-bottom: 1rem;
            color: var(--color-carbon-black);
            font-weight: 600;
        }

        .feature-card p {
            color: var(--color-smoke);
            line-height: 1.6;
            font-size: 0.95rem;
        }

        /* Footer */
        footer {
            background: var(--color-carbon-black);
            color: var(--color-smoke);
            padding: 2rem;
            text-align: center;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
        }

        footer p {
            color: var(--color-smoke);
        }

        /* Scroll Indicator */
        .scroll-indicator {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateX(-50%) translateY(0);
            }
            50% {
                transform: translateX(-50%) translateY(-10px);
            }
        }

        .scroll-indicator svg {
            width: 30px;
            height: 30px;
            stroke: var(--color-carbon-black);
            stroke-width: 2;
            fill: none;
        }

        /* Mint Accent */
        .mint-accent {
            color: var(--color-mint-chip);
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .hero {
                grid-template-columns: 1fr;
                margin-top: 6rem;
            }

            #canvas-container {
                width: 100%;
                position: relative;
            }

            .hero h1 {
                font-size: 80px;
            }
        }

        @media (max-width: 768px) {
            header {
                height: auto;
                padding: 1rem;
            }

            .hero {
                margin-top: 0;
                padding: 2rem 1rem;
            }

            .hero h1 {
                font-size: 48px;
                letter-spacing: -1.44px;
            }

            .hero-buttons {
                flex-direction: column;
            }

            .btn-hero {
                width: 100%;
            }

            .nav-buttons {
                flex-direction: column;
                gap: 0.5rem;
            }

            .features h2 {
                font-size: 48px;
            }
        }

        /* Mint Chip Tag */
        .mint-tag {
            background: var(--color-mint-chip);
            color: var(--color-carbon-black);
            border-radius: var(--radius-full);
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
            font-family: var(--font-suisseintlmono);
            display: inline-block;
            margin-bottom: 1rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header>
        <div class="header-content">
            <div class="logo">
                VS EVALUACIONES
            </div>
            <nav class="nav-buttons">
                <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard.php' : 'index.php'; ?>" class="btn btn-login">Ingresar</a>
                <a href="index.php?registrar=1" class="btn btn-register">Registrarse</a>
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div id="canvas-container"></div>

        <div class="hero-content">
            <div class="mint-tag">PLATAFORMA INTELIGENTE</div>
            <h1>EVALUACIONES TRANSFORMADAS</h1>
            <p>Diseñada para modernizar cómo docentes y estudiantes interactúan con la evaluación del conocimiento. Tecnología, diseño y precisión en un solo lugar.</p>
            <div class="hero-buttons">
                <button class="btn-hero btn-primary" onclick="document.getElementById('features').scrollIntoView({behavior: 'smooth'})">
                    Explorar ahora
                </button>
                <a href="index.php?registrar=1" class="btn-hero btn-secondary">Registrarse</a>
            </div>
        </div>

        <div class="scroll-indicator">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M12 2v16M19 19l-7 7-7-7" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <h2>CARACTERÍSTICAS PRINCIPALES</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🎯</div>
                <h3>Evaluaciones Precisas</h3>
                <p>Crea evaluaciones personalizadas con preguntas de múltiple opción, respuesta corta y análisis detallados de desempeño estudiantil.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🤖</div>
                <h3>Inteligencia Artificial</h3>
                <p>Revisión automática de respuestas con IA para retroalimentación inmediata, detallada y personalizada para cada estudiante.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Análisis Avanzados</h3>
                <p>Tableros de control con métricas en tiempo real, reportes detallados y visualización de desempeño académico completo.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">👥</div>
                <h3>Gestión Intuitiva</h3>
                <p>Sistema flexible para administrar docentes, estudiantes y clases de forma intuitiva y eficiente sin necesidad de capacitación.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Seguridad Garantizada</h3>
                <p>Protección de datos con encriptación de nivel empresarial y autenticación segura para todos tus datos y evaluaciones.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Rendimiento Óptimo</h3>
                <p>Plataforma ultra rápida optimizada para usuarios simultáneos con disponibilidad 24/7 y soporte técnico permanente.</p>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <p>&copy; 2024 VS PLATAFORMA DE EVALUACIONES</p>
            <p>Servicio Nacional de Aprendizaje — SENA</p>
        </div>
    </footer>

    <!-- Three.js -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/three@r128/examples/js/controls/DeviceOrientationControls.js"></script>
    <script>
        // Advanced Three.js Scene with Textured Objects
        const container = document.getElementById('canvas-container');
        const scene = new THREE.Scene();
        const camera = new THREE.PerspectiveCamera(
            75,
            container.clientWidth / container.clientHeight,
            0.1,
            1000
        );
        const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });

        renderer.setSize(container.clientWidth, container.clientHeight);
        renderer.setClearColor(0xe5e5e5, 1);
        renderer.shadowMap.enabled = true;
        container.appendChild(renderer.domElement);

        camera.position.z = 25;

        // Create canvas textures for brutalist look
        function createCanvasTexture(color, pattern = 'concrete') {
            const canvas = document.createElement('canvas');
            canvas.width = 512;
            canvas.height = 512;
            const ctx = canvas.getContext('2d');

            // Base color
            ctx.fillStyle = color;
            ctx.fillRect(0, 0, 512, 512);

            // Concrete pattern
            if (pattern === 'concrete') {
                for (let i = 0; i < 2000; i++) {
                    ctx.fillStyle = `rgba(0, 0, 0, ${Math.random() * 0.1})`;
                    ctx.fillRect(
                        Math.random() * 512,
                        Math.random() * 512,
                        Math.random() * 5,
                        Math.random() * 5
                    );
                }
            }

            // Wood grain pattern
            if (pattern === 'wood') {
                for (let i = 0; i < 20; i++) {
                    ctx.strokeStyle = `rgba(0, 0, 0, ${Math.random() * 0.15})`;
                    ctx.lineWidth = Math.random() * 4 + 2;
                    ctx.beginPath();
                    ctx.moveTo(0, Math.random() * 512);
                    ctx.lineTo(512, Math.random() * 512);
                    ctx.stroke();
                }
            }

            const texture = new THREE.CanvasTexture(canvas);
            texture.magFilter = THREE.LinearFilter;
            return texture;
        }

        // Materials
        const concreteTexture = createCanvasTexture('#c6c6c6', 'concrete');
        const woodTexture = createCanvasTexture('#8b6f47', 'wood');
        const mintTexture = createCanvasTexture('#d1ffca', 'concrete');

        const materials = [
            new THREE.MeshPhongMaterial({ 
                map: concreteTexture,
                color: 0xc6c6c6,
                shininess: 30,
                side: THREE.DoubleSide
            }),
            new THREE.MeshPhongMaterial({ 
                map: woodTexture,
                color: 0x8b6f47,
                shininess: 20,
                side: THREE.DoubleSide
            }),
            new THREE.MeshPhongMaterial({ 
                color: 0xd1ffca,
                shininess: 40,
                side: THREE.DoubleSide
            }),
            new THREE.MeshPhongMaterial({ 
                color: 0x000000,
                shininess: 25,
                side: THREE.DoubleSide
            })
        ];

        // Create primary cube (concrete textured)
        const cubeGeometry = new THREE.BoxGeometry(6, 6, 6);
        const cube = new THREE.Mesh(cubeGeometry, materials[0]);
        cube.position.x = -5;
        cube.position.y = 2;
        cube.castShadow = true;
        cube.receiveShadow = true;
        scene.add(cube);

        // Create secondary cube (wood textured)
        const cubeGeometry2 = new THREE.BoxGeometry(4, 6, 4);
        const cube2 = new THREE.Mesh(cubeGeometry2, materials[1]);
        cube2.position.x = 3;
        cube2.position.y = -1;
        cube2.castShadow = true;
        cube2.receiveShadow = true;
        scene.add(cube2);

        // Create accent cube (mint green)
        const cubeGeometry3 = new THREE.BoxGeometry(3, 3, 3);
        const cube3 = new THREE.Mesh(cubeGeometry3, materials[2]);
        cube3.position.x = 7;
        cube3.position.y = 4;
        cube3.castShadow = true;
        cube3.receiveShadow = true;
        scene.add(cube3);

        // Create black accent cube
        const cubeGeometry4 = new THREE.BoxGeometry(2, 5, 2);
        const cube4 = new THREE.Mesh(cubeGeometry4, materials[3]);
        cube4.position.x = -8;
        cube4.position.y = -3;
        cube4.castShadow = true;
        cube4.receiveShadow = true;
        scene.add(cube4);

        // Create torus (ring shape)
        const torusGeometry = new THREE.TorusGeometry(8, 2, 16, 100);
        const torus = new THREE.Mesh(torusGeometry, materials[0]);
        torus.position.x = 0;
        torus.position.y = 0;
        torus.rotation.x = 0.5;
        torus.castShadow = true;
        scene.add(torus);

        // Lighting setup
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.7);
        scene.add(ambientLight);

        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(10, 20, 10);
        directionalLight.castShadow = true;
        directionalLight.shadow.mapSize.width = 2048;
        directionalLight.shadow.mapSize.height = 2048;
        scene.add(directionalLight);

        const pointLight1 = new THREE.PointLight(0xd1ffca, 0.5, 50);
        pointLight1.position.set(-15, 10, 15);
        scene.add(pointLight1);

        const pointLight2 = new THREE.PointLight(0x000000, 0.3, 40);
        pointLight2.position.set(15, -10, -15);
        scene.add(pointLight2);

        // Create plane for shadow
        const planeGeometry = new THREE.PlaneGeometry(100, 100);
        const planeMaterial = new THREE.ShadowMaterial({ opacity: 0.2 });
        const plane = new THREE.Mesh(planeGeometry, planeMaterial);
        plane.receiveShadow = true;
        plane.position.z = -15;
        scene.add(plane);

        // Animation variables
        let time = 0;
        const objects = [cube, cube2, cube3, cube4, torus];

        // Animation loop
        function animate() {
            requestAnimationFrame(animate);
            time += 0.005;

            // Animate each object with different speeds and axes
            cube.rotation.x += 0.003;
            cube.rotation.y += 0.002;
            cube.position.y = 2 + Math.sin(time) * 1.5;

            cube2.rotation.x -= 0.002;
            cube2.rotation.y += 0.003;
            cube2.position.y = -1 + Math.cos(time * 0.8) * 1.2;

            cube3.rotation.x += 0.004;
            cube3.rotation.y -= 0.002;
            cube3.position.y = 4 + Math.sin(time * 1.2) * 0.8;
            cube3.scale.x = 1 + Math.sin(time * 2) * 0.1;
            cube3.scale.y = 1 + Math.cos(time * 2) * 0.1;

            cube4.rotation.x += 0.002;
            cube4.rotation.y += 0.003;
            cube4.position.y = -3 + Math.sin(time * 0.6) * 1.5;

            torus.rotation.x += 0.001;
            torus.rotation.y += 0.002;
            torus.scale.x = 1 + Math.sin(time * 1.5) * 0.05;
            torus.scale.y = 1 + Math.cos(time * 1.5) * 0.05;

            renderer.render(scene, camera);
        }

        animate();

        // Handle window resize
        window.addEventListener('resize', () => {
            const width = container.clientWidth;
            const height = container.clientHeight;
            camera.aspect = width / height;
            camera.updateProjectionMatrix();
            renderer.setSize(width, height);
        });

        // Mouse interaction - subtle rotation
        let mouseX = 0;
        let mouseY = 0;

        document.addEventListener('mousemove', (e) => {
            mouseX = (e.clientX / window.innerWidth) * 2 - 1;
            mouseY = -(e.clientY / window.innerHeight) * 2 + 1;

            // Subtle camera movement based on mouse position
            camera.position.x = mouseX * 5;
            camera.position.y = mouseY * 3;
        });

        // Add some interactivity on click
        document.addEventListener('click', (e) => {
            if (e.target.closest('.hero-content')) {
                // Boost animation speed on click
                objects.forEach(obj => {
                    obj.rotation.x += Math.random() * 0.5;
                    obj.rotation.y += Math.random() * 0.5;
                });
            }
        });
    </script>
</body>
</html>
