# Calculadora de Huella Digital

Esta herramienta de **Yel Martínez** estima la huella de carbono digital de cualquier sitio web con el modelo **Sustainable Web Design v3** (Green Web Foundation, Apache 2.0), en el alcance Scope 2 indirecto + Scope 3 categoría 11 del GHG Protocol.

## Características principales

- Cálculo de CO₂eq por visita y huella mensual estimada, con Rating SWD v3 por percentiles (A–F).
- Verificación de hosting verde vía **Greencheck API v3** (Green Web Foundation) y de la intensidad real de la red eléctrica del país del servidor vía **IP to CO2 Intensity API** (Green Web Foundation / Ember, CC BY-SA 4.0).
- Comparativa sectorial frente a la media web y el top 10% más eficiente (HTTP Archive 2024).
- Resumen de la estimación descargable en `.txt`, con metodología y fuentes declaradas — pensado para acompañar reporting CSRD (ESRS E1) o GRI 305.
- Sin registro, sin coste, sin datos guardados en ningún servidor: todas las consultas a las APIs públicas se hacen directamente desde el navegador del usuario.

## Dos formas de usarla

**`index.html` / `carbon-calculator.js` / `carbon-calculator.css`** — versión estática, sin backend. Copia los tres archivos, enlázalos en cualquier página HTML y funciona: el peso de página se introduce a mano (desde las DevTools del navegador) y, si se indica una URL, el hosting verde y la intensidad de red se resuelven en el cliente vía Cloudflare DNS-over-HTTPS + las APIs públicas de Green Web Foundation. No depende de PHP ni de ningún backend propio.

**`wordpress-plugin/carbon-calculator.php`** — el plugin de WordPress original, shortcode `[carbon_calculator]`. Añade tres modos (URL automático, manual, mixto) apoyándose en dos endpoints AJAX de WordPress: uno hace de proxy para obtener el peso real de la página (evita el bloqueo CORS de pedir el HTML de un dominio ajeno desde el navegador) y otro resuelve la IP de un dominio en el servidor. Útil si necesitas el modo "solo URL, sin que el usuario mida nada a mano".

Ambas versiones implementan el mismo modelo SWD v3 y dan el mismo resultado ante los mismos datos.

## Metodología

Constantes del modelo SWD v3: `KWH_PER_GB=0.81` · datacenter 15% · red 14% · dispositivo 52% · producción 19%. El segmento datacenter usa la intensidad de carbono real del país del servidor (detectada o elegida) cuando el hosting no es verde, o un factor de renovables cuando sí lo es; red, dispositivo y producción usan la media mundial (Ember, vía GWF) por no depender de la ubicación del servidor.

No cubre: fabricación de hardware, Scope 1, ni emisiones de los dispositivos del usuario final más allá del consumo eléctrico durante la visita. Es una estimación con metodología declarada, no un certificado ni una auditoría ISO 14064.

## Changelog

- **3.1.1** — corrige un bug del modelo: el país del servidor (elegido a mano o detectado vía API) no llegaba a afectar al resultado calculado, que siempre usaba la media mundial para el segmento datacenter. Ahora si el hosting no es verde, ese segmento usa la intensidad real del país detectado.
- **3.1.0** — versión base, modelo Sustainable Web Design v3.

## Autoría

Desarrollada por [Yel Martínez](https://yel-martinez-portfolio.com/wikipedia-profesional/), Digital Strategist & Tecnóloga, Greentech. Parte de un ecosistema de herramientas ESG gratuitas — en uso en [yel-martinez-portfolio.com/recursos/carbon-calculator](https://yel-martinez-portfolio.com/recursos/carbon-calculator/).

## Licencia

GPL-2.0-or-later — ver [LICENSE](LICENSE).
