# Calculadora de Huella Digital

Esta herramienta de **Yel Martínez** estima la huella de carbono digital de cualquier sitio web con el modelo **Sustainable Web Design v3** — metodología pública de Green Web Foundation ([sustainablewebdesign.org/estimating-digital-emissions](https://sustainablewebdesign.org/estimating-digital-emissions/)), implementación propia e independiente — en el alcance Scope 2 indirecto + Scope 3 categoría 11 del GHG Protocol.

Esta herramienta **no usa código ni datasets de terceros**: no depende de la librería CO2.js ni de los datasets de intensidad de carbono de Ember/Green Web Foundation. Toda la lógica es propia, y la intensidad de red eléctrica por país se toma de fuentes oficiales citadas individualmente (ver más abajo).

## Características principales

- Cálculo de CO₂eq por visita y huella mensual estimada, con Rating SWD v3 por percentiles (A–F).
- Comparativa sectorial frente a la media web y el top 10% más eficiente (HTTP Archive 2024).
- Resumen de la estimación descargable en `.txt`, con metodología y fuentes declaradas — pensado para acompañar reporting CSRD (ESRS E1) o GRI 305.
- 100% cliente, sin registro, sin coste, sin datos guardados en ningún servidor: **ninguna llamada a ninguna API de terceros**. El peso de página, el hosting verde y el país del servidor se introducen/seleccionan siempre a mano.

## Dos formas de usarla

**`index.html` / `carbon-calculator.js` / `carbon-calculator.css`** — versión estática, sin backend. Copia los tres archivos, enlázalos en cualquier página HTML y funciona: todo el cálculo ocurre en el navegador, sin llamadas de red de ningún tipo.

**`wordpress-plugin/carbon-calculator.php`** — el mismo cálculo como shortcode de WordPress, `[carbon_calculator]`. Sin endpoints AJAX, sin llamadas externas — idéntico en comportamiento a la versión estática.

Ambas versiones implementan el mismo modelo SWD v3 y dan el mismo resultado ante los mismos datos.

## Metodología

Constantes del modelo SWD v3: `KWH_PER_GB=0.81` · datacenter 15% · red 14% · dispositivo 52% · producción 19%. El segmento datacenter usa la intensidad de carbono del país del servidor (elegido a mano) cuando el hosting no es verde, o un factor de renovables cuando sí lo es; red, dispositivo y producción usan la media mundial por no depender de la ubicación del servidor.

### Fuentes de la intensidad de red eléctrica (por país, citadas individualmente)

| País | Valor | Año | Fuente |
|---|---|---|---|
| España | 129 gCO₂/kWh | 2024 | [European Environment Agency (EEA)](https://www.eea.europa.eu/en/analysis/indicators/greenhouse-gas-emission-intensity-of-1/greenhouse-gas-emission-intensity-of-electricity-generation) |
| Alemania | 298 gCO₂/kWh | 2024 | European Environment Agency (EEA) |
| Francia | 43 gCO₂/kWh | 2024 | European Environment Agency (EEA) |
| Países Bajos | 235 gCO₂/kWh | 2024 | European Environment Agency (EEA) |
| Irlanda | 238 gCO₂/kWh | 2024 | European Environment Agency (EEA) |
| Estados Unidos | 367.4 gCO₂/kWh | 2023 | [U.S. Energy Information Administration (EIA)](https://www.eia.gov/tools/faqs/faq.php?id=74&t=11) — 0.81 lb CO₂/kWh convertido a gramos |
| Media mundial | 445 gCO₂/kWh | 2024 | [International Energy Agency (IEA), informe "Electricity 2025"](https://www.iea.org/reports/electricity-2025/emissions) |

No cubre: fabricación de hardware, Scope 1, ni emisiones de los dispositivos del usuario final más allá del consumo eléctrico durante la visita. Es una estimación con metodología declarada, no un certificado ni una auditoría ISO 14064.

## Changelog

- **3.2.0** — elimina toda dependencia de terceros: quita las llamadas a la API de Green Web Foundation (Greencheck v3 e IP-to-CO2-intensity) y sustituye el dataset de intensidad de red (antes de Ember/GWF) por valores citados individualmente de EEA, EIA e IEA. Corrige la atribución de licencia: ya no se menciona "Apache 2.0" (no aplica a esta implementación). El plugin de WordPress pasa de 3 modos (URL/manual/mixto) a un único modo manual, sin AJAX, para tener el mismo comportamiento 100% cliente que la versión estática.
- **3.1.1** — corrige un bug del modelo: el país del servidor no llegaba a afectar al resultado calculado, que siempre usaba la media mundial para el segmento datacenter. Ahora si el hosting no es verde, ese segmento usa la intensidad real del país elegido.
- **3.1.0** — versión base, modelo Sustainable Web Design v3.

## Autoría

Desarrollada por [Yel Martínez](https://yel-martinez-portfolio.com/wikipedia-profesional/), Digital Strategist & Tecnóloga, Greentech. Parte de un ecosistema de herramientas ESG gratuitas — en uso en [yel-martinez-portfolio.com/recursos/carbon-calculator](https://yel-martinez-portfolio.com/recursos/carbon-calculator/).

## Licencia

GPL-2.0-or-later — ver [LICENSE](LICENSE). Es la licencia del código de esta herramienta (heredada del plugin de WordPress original de Yel Martínez); no reclama ni hereda ninguna licencia de terceros sobre metodología, código o datos ajenos.
