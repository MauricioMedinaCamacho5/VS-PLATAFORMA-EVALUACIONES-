<?php
declare(strict_types=1);

// Plantilla de configuración de la IA. Cópiala como config/config.php y escribe tu clave.
// config/config.php está en .gitignore: tu clave nunca se sube al repositorio
// y el servidor nunca la envía al navegador.
//
// Clave gratuita: https://aistudio.google.com/apikey

define('GEMINI_API_KEY', 'TU_CLAVE_AQUI');

// gemini-2.0-flash fue retirado por Google el 1 de junio de 2026 (la API responde 404).
// Modelos vigentes: https://ai.google.dev/gemini-api/docs/models
define('GEMINI_MODELO', 'gemini-3.1-flash-lite');

// Segundos máximos de espera por respuesta de Gemini.
define('GEMINI_TIEMPO_LIMITE', 90);
