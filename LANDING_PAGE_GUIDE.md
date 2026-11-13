# 🎨 VS Plataforma - Landing Page Guide

## 📋 Descripción General

He creado un **landing page profesional y moderno** siguiendo la guía de diseño **brutalist editorial** que compartiste. La página incluye:

### ✨ Características Principales:

1. **Three.js Hero Section** 
   - Objetos 3D animados (cubos texturizados, torus ring)
   - Texturas de hormigón, madera y acentos mint
   - Iluminación dinámica con sombras
   - Interactividad con el mouse (rotación de cámara)
   - Animación fluida y performance optimizado

2. **Diseño Brutalist Editorial**
   - Paleta de colores: Blanco, gris cálido, negro
   - Acentos mínimos en mint (#d1ffca)
   - Tipografía masiva (130px para display)
   - Sin gradientes, sin sombras innecesarias
   - Espaciado generoso

3. **Layout Split Hero**
   - 50% contenido de texto (izquierda)
   - 50% Three.js 3D scene (derecha)
   - Completamente responsive

4. **Secciones**
   - Header fijo con navegación en pill shape
   - Hero section con CTA buttons
   - Features grid (6 tarjetas)
   - Footer dark (inverted)

---

## 🚀 Cómo Usar

### Acceso a la página:
```
http://localhost/VS-PLATAFORMA-EVALUACIONES-/public/landing.php
```

### O reemplaza tu index.html con este landing:
Renombra o copia el archivo a la raíz de `/public/`

---

## 🎯 Estructura del Three.js

### Objetos 3D:
- **Cube 1**: 6x6x6 - Textura de hormigón (gris)
- **Cube 2**: 4x6x4 - Textura de madera (marrón)
- **Cube 3**: 3x3x3 - Mint verde (acento)
- **Cube 4**: 2x5x2 - Negro puro
- **Torus Ring**: Anillo texturizado

### Materiales:
- **Phong Material** para realismo
- **Canvas Textures** generadas dinámicamente
- Patrones de concreto y madera

### Interactividad:
- **Mouse Move**: La cámara sigue sutilmente al mouse
- **Click**: Acelera la animación de objetos
- **Auto-rotation**: Los cubos rotan continuamente

---

## 🎨 Customización

### Cambiar colores:
Edita las CSS Custom Properties en `:root`:
```css
--color-mint-chip: #d1ffca;  /* Cambiar verde mint */
--color-carbon-black: #000000;  /* Cambiar negro */
```

### Modificar velocidades de animación:
En el script Three.js, busca:
```javascript
time += 0.005;  // Aumentar = más rápido
```

### Agregar más objetos 3D:
Copia el patrón:
```javascript
const cubeGeometry5 = new THREE.BoxGeometry(5, 5, 5);
const cube5 = new THREE.Mesh(cubeGeometry5, materials[0]);
cube5.position.set(x, y, z);
scene.add(cube5);
```

### Cambiar materiales:
Modifica las texturas en `createCanvasTexture()` o usa imágenes:
```javascript
const textureLoader = new THREE.TextureLoader();
const texture = textureLoader.load('path/to/image.jpg');
```

---

## 🔧 Técnicas Three.js Utilizadas

1. **Canvas Textures** - Generación de texturas procedurales
2. **Lighting**:
   - Ambient Light (general)
   - Directional Light (shadows)
   - Point Lights (acentos de color)

3. **Shadows** - Mapas de sombra en tiempo real
4. **Material Properties**:
   - Phong Shading
   - Shininess/Specular reflection
5. **Animation Loop** - RequestAnimationFrame
6. **Responsive Resize** - Window listeners

---

## 📐 Paleta de Diseño

| Elemento | Color | Variable |
|----------|-------|----------|
| Fondo | #e5e5e5 | `--color-warm-canvas` |
| Texto | #000000 | `--color-carbon-black` |
| Cards | #ffffff | `--color-paper-white` |
| Acentos | #d1ffca | `--color-mint-chip` |
| Bordes | #c6c6c6 | `--color-ash` |
| Footer | #000000 | `--color-carbon-black` |

---

## 📱 Responsive Breakpoints

- **Desktop**: Full layout (grid 2 columnas)
- **Tablet (1024px)**: Hero en 1 columna
- **Mobile (768px)**: Stack vertical, fuentes reducidas

---

## 🎬 Next Steps

1. ✅ **Landing page creada**
2. 📍 Integrar con tu sistema de autenticación
3. 🎨 Personalizar colores/contenido
4. 📊 Agregar más secciones (testimonios, pricing, etc)
5. 🔗 Conectar con rutas de login/registro

---

## 📝 Notas Técnicas

- **Performance**: El Three.js está optimizado con shadow maps eficientes
- **Compatibilidad**: Funciona en todos los navegadores modernos
- **Accesibilidad**: Incluye ARIA labels y estructura semántica
- **SEO**: Meta tags básicos incluidos

---

## 🤝 Soporte

Si necesitas:
- Agregar más efectos 3D
- Cambiar la composición del hero
- Integrar con tu backend
- Agregar animaciones adicionales

¡Avísame y lo hacemos! 🚀
