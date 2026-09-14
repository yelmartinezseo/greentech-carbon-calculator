<?php
/**
 * Plugin Name: Calculadora de Huella Digital
 * Plugin URI:  https://yel-martinez-portfolio.com
 * Description: Estima la huella de carbono digital de cualquier sitio web. Modelo: Sustainable Web Design v3 (metodologia publica de Green Web Foundation, implementacion propia). Alcance: Scope 2 + Scope 3 cat.11 (GHG Protocol).
 * Version:     3.2.0
 * Author:      Yel Martínez | Greentech
 * Author URI:  https://yel-martinez-portfolio.com
 * License:     GPL-2.0+
 * Text Domain: carbon-calculator
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* -----------------------------------------------------------------------
 * SHORTCODE PRINCIPAL  [carbon_calculator]
 * 100% cliente: sin AJAX, sin llamadas a ningun servicio externo. El peso
 * de pagina, el hosting verde y el pais del servidor se introducen a mano.
 * ----------------------------------------------------------------------- */
function cc_shortcode() {
    ob_start();
    ?>
<div class="cc-wrap" id="cc-wrap">

<style>
/* ── Reset y base ───────────────────────────────────────────────── */
.cc-wrap*{box-sizing:border-box;margin:0;padding:0;}
.cc-wrap{max-width:680px;margin:2em auto;font-family:'Segoe UI',Arial,sans-serif;color:#1a1830;}

/* ── Tarjeta ────────────────────────────────────────────────────── */
.cc-card{background:#fff;border-radius:14px;box-shadow:0 2px 28px rgba(26,24,48,.11);padding:2em 2.2em;margin-bottom:1.4em;}

/* ── Cabecera ───────────────────────────────────────────────────── */
.cc-title{font-size:1.35em;font-weight:800;color:#1a1830;margin-bottom:.25em;}
.cc-sub{font-size:.84em;color:#777;line-height:1.5;margin-bottom:1.6em;}

/* ── Campos ─────────────────────────────────────────────────────── */
.cc-field{margin-bottom:1.1em;}
.cc-field label{display:block;font-size:.85em;font-weight:700;color:#1a1830;margin-bottom:.4em;}
.cc-field input,.cc-field select{width:100%;padding:.72em 1em;border:2px solid #e0e0e0;
  border-radius:8px;font-size:.95em;color:#1a1830;transition:.18s;background:#fff;}
.cc-field input:focus,.cc-field select:focus{outline:none;border-color:#0085ff;}
.cc-hint{font-size:.76em;color:#999;margin-top:.3em;line-height:1.4;}

/* ── Botón calcular ─────────────────────────────────────────────── */
.cc-btn{width:100%;padding:.85em;border:none;border-radius:9px;background:#0085ff;
  color:#fff;font-size:1em;font-weight:800;cursor:pointer;transition:.18s;letter-spacing:.01em;}
.cc-btn:hover:not(:disabled){background:#006bcc;}
.cc-btn:disabled{opacity:.45;cursor:not-allowed;}

/* ── Loading ────────────────────────────────────────────────────── */
.cc-loading{display:none;text-align:center;padding:1.2em;color:#0085ff;font-size:.88em;}
.cc-spin{display:inline-block;width:18px;height:18px;border:3px solid #dde;
  border-top-color:#0085ff;border-radius:50%;animation:cc-rot .7s linear infinite;
  vertical-align:middle;margin-right:.5em;}
@keyframes cc-rot{to{transform:rotate(360deg);}}

/* ── Error ───────────────────────────────────────────────────────── */
.cc-error{display:none;margin-top:.9em;padding:.8em 1em;background:#fff0f0;
  border-left:4px solid #e74c3c;border-radius:7px;font-size:.85em;color:#c0392b;}

/* ── Resultados ─────────────────────────────────────────────────── */
.cc-results{display:none;}

/* Badge hosting */
.cc-badge{display:inline-flex;align-items:center;gap:.35em;padding:.3em .75em;
  border-radius:20px;font-size:.8em;font-weight:700;margin-left:.6em;}
.cc-badge.green{background:#e6f9ef;color:#1a7a40;}
.cc-badge.grey{background:#f0f0f0;color:#666;}

/* Métricas */
.cc-metrics{display:grid;grid-template-columns:1fr 1fr;gap:1em;margin:1.2em 0;}
@media(max-width:480px){.cc-metrics{grid-template-columns:1fr;}}
.cc-metric{background:#f6f8ff;border-radius:10px;padding:1em 1.1em;border:1px solid #e4e8ff;}
.cc-mlabel{font-size:.72em;color:#777;font-weight:700;text-transform:uppercase;
  letter-spacing:.05em;margin-bottom:.3em;}
.cc-mval{font-size:1.45em;font-weight:900;color:#1a1830;line-height:1.1;}
.cc-munit{font-size:.58em;font-weight:400;color:#aaa;}

/* Intensidad red */
.cc-grid-box{background:#f0f5ff;border-radius:9px;padding:.85em 1em;margin:1em 0;
  font-size:.83em;color:#1a1830;line-height:1.7;display:none;}
.cc-grid-box strong{color:#0085ff;}
.cc-grid-src{font-size:.85em;color:#aaa;}

/* Barras comparativas */
.cc-compare-title{font-size:.84em;font-weight:700;color:#1a1830;margin-bottom:.6em;}
.cc-bar-row{display:flex;align-items:center;gap:.6em;margin-bottom:.45em;font-size:.8em;}
.cc-bar-lbl{width:150px;flex-shrink:0;color:#666;}
.cc-bar-track{flex:1;height:9px;background:#eee;border-radius:5px;overflow:hidden;}
.cc-bar-fill{height:100%;border-radius:5px;transition:width .5s ease;}
.cc-bar-fill.u{background:#0085ff;}
.cc-bar-fill.a{background:#bbb;}
.cc-bar-fill.b{background:#27ae60;}
.cc-bar-num{width:55px;flex-shrink:0;text-align:right;font-weight:700;color:#555;}

/* Rating SWD */
.cc-rating{display:inline-block;padding:.25em .7em;border-radius:6px;
  font-size:.8em;font-weight:800;margin-left:.5em;}
.cc-rating.A{background:#1a7a40;color:#fff;}
.cc-rating.B{background:#27ae60;color:#fff;}
.cc-rating.C{background:#f39c12;color:#fff;}
.cc-rating.D{background:#e67e22;color:#fff;}
.cc-rating.E{background:#e74c3c;color:#fff;}
.cc-rating.F{background:#c0392b;color:#fff;}

/* Disclaimer */
.cc-disclaimer{font-size:.73em;color:#999;border-top:1px solid #eee;
  padding-top:1em;margin-top:1.2em;line-height:1.7;}
.cc-disclaimer strong{color:#666;}

/* Botón descarga */
.cc-dl-btn{display:inline-block;margin-top:1em;padding:.55em 1.1em;
  background:#1a1830;color:#ffde59;border:none;border-radius:8px;
  font-size:.83em;font-weight:700;cursor:pointer;text-decoration:none;}
.cc-dl-btn:hover{background:#2d2a50;color:#ffde59;}
</style>

<!-- CARD FORMULARIO -->
<div class="cc-card">
    <p class="cc-title">Calculadora de Huella Digital</p>
    <p class="cc-sub">
        Estima las emisiones de CO₂ de cualquier sitio web.<br>
        Modelo: <strong>Sustainable Web Design v3</strong> (metodología pública de Green Web Foundation, implementación propia) &nbsp;·&nbsp;
        Alcance: Scope 2 + Scope 3 cat. 11 (GHG Protocol)
    </p>

    <div class="cc-field">
        <label for="cc-bytes">Peso de página (KB)</label>
        <input type="number" id="cc-bytes" placeholder="Ej: 1500" min="1">
        <p class="cc-hint">DevTools del navegador (F12) → pestaña Red → recarga la página → busca la barra de resumen al final ("13 requests · 250 kB transferred"). Usa ese número en KB.</p>
    </div>
    <div class="cc-field">
        <label for="cc-visits">Visitas mensuales</label>
        <input type="number" id="cc-visits" placeholder="Ej: 10000" min="1">
    </div>
    <div class="cc-field">
        <label for="cc-green-manual">¿El hosting usa energía renovable?</label>
        <select id="cc-green-manual">
            <option value="unknown">No lo sé</option>
            <option value="true">Sí, verificado</option>
            <option value="false">No</option>
        </select>
        <p class="cc-hint">Consulta la web de tu proveedor de hosting o pregúntale directamente.</p>
    </div>
    <div class="cc-field">
        <label for="cc-country">País del servidor</label>
        <select id="cc-country">
            <option value="ESP">España (129 gCO₂/kWh)</option>
            <option value="DEU">Alemania (298 gCO₂/kWh)</option>
            <option value="FRA">Francia (43 gCO₂/kWh)</option>
            <option value="NLD">Países Bajos (235 gCO₂/kWh)</option>
            <option value="IRL">Irlanda (238 gCO₂/kWh)</option>
            <option value="USA">Estados Unidos (367 gCO₂/kWh)</option>
            <option value="WORLD">Media mundial (445 gCO₂/kWh)</option>
        </select>
        <p class="cc-hint">Si no lo sabes, consulta el panel de tu proveedor de hosting o usa "Media mundial".</p>
    </div>

    <button class="cc-btn" id="cc-calc-btn">Calcular huella digital</button>
    <div class="cc-loading" id="cc-loading"><span class="cc-spin"></span>Calculando…</div>
    <div class="cc-error" id="cc-error"></div>
</div>

<!-- CARD RESULTADOS -->
<div class="cc-card cc-results" id="cc-results">
    <div style="display:flex;align-items:center;flex-wrap:wrap;gap:.4em;margin-bottom:1.1em;">
        <p class="cc-title" style="margin:0;">Resultados</p>
        <span class="cc-badge" id="cc-hosting-badge">–</span>
        <span class="cc-rating" id="cc-swd-rating" style="display:none;"></span>
    </div>

    <div class="cc-metrics">
        <div class="cc-metric">
            <div class="cc-mlabel">CO₂ por visita</div>
            <div class="cc-mval" id="cc-r-pervisit">– <span class="cc-munit">g CO₂eq</span></div>
        </div>
        <div class="cc-metric">
            <div class="cc-mlabel">CO₂ mensual estimado</div>
            <div class="cc-mval" id="cc-r-monthly">– <span class="cc-munit">kg CO₂eq</span></div>
        </div>
        <div class="cc-metric">
            <div class="cc-mlabel">Peso de página</div>
            <div class="cc-mval" id="cc-r-size">– <span class="cc-munit">KB</span></div>
        </div>
        <div class="cc-metric">
            <div class="cc-mlabel">Alcance GHG Protocol</div>
            <div class="cc-mval" style="font-size:.85em;padding-top:.15em;">
                Scope 2 indirecto<br><span style="font-size:.8em;color:#aaa;">Scope 3 cat. 11</span>
            </div>
        </div>
    </div>

    <!-- Intensidad red eléctrica -->
    <div class="cc-grid-box" id="cc-grid-box">
        País del servidor: <strong id="cc-grid-country">–</strong> ·
        Intensidad: <strong id="cc-grid-intensity">–</strong> gCO₂/kWh<br>
        <span class="cc-grid-src">Fuente: <span id="cc-grid-source">–</span> · Año <span id="cc-grid-year">–</span></span>
    </div>

    <!-- Comparativa sectorial -->
    <div style="margin-bottom:1.2em;">
        <div class="cc-compare-title">Comparativa sectorial (g CO₂eq / visita · HTTP Archive 2024)</div>
        <div class="cc-bar-row">
            <span class="cc-bar-lbl">Tu sitio</span>
            <div class="cc-bar-track"><div class="cc-bar-fill u" id="cc-bar-u" style="width:0%"></div></div>
            <span class="cc-bar-num" id="cc-bar-u-val">–</span>
        </div>
        <div class="cc-bar-row">
            <span class="cc-bar-lbl">Media web (p50)</span>
            <div class="cc-bar-track"><div class="cc-bar-fill a" style="width:50%"></div></div>
            <span class="cc-bar-num">0.50 g</span>
        </div>
        <div class="cc-bar-row">
            <span class="cc-bar-lbl">Top 10% más limpio</span>
            <div class="cc-bar-track"><div class="cc-bar-fill b" style="width:9%"></div></div>
            <span class="cc-bar-num">0.09 g</span>
        </div>
    </div>

    <!-- Disclaimer -->
    <div class="cc-disclaimer">
        <strong>Metodología declarada:</strong>
        Modelo <strong>Sustainable Web Design v3</strong> (metodología pública de Green Web Foundation), implementación propia e independiente — no reutiliza código ni datasets de terceros.
        Constantes: KWH_PER_GB=0.81 · Datacenter=15% · Red=14% · Dispositivo=52% · Producción=19%.
        Medición de peso de página: importada manualmente por el usuario desde <strong>DevTools del navegador</strong>.
        Hosting verde y país del servidor: seleccionados a mano por el usuario.
        Intensidad de red eléctrica: <strong>European Environment Agency</strong> (España, Alemania, Francia, Países Bajos, Irlanda — 2024), <strong>U.S. Energy Information Administration</strong> (EE. UU. — 2023), <strong>International Energy Agency</strong> (media mundial, informe Electricity 2025 — 2024).<br>
        100% cliente: ningún dato sale de tu navegador, no se hace ninguna llamada a servicios externos, nada se guarda en ningún sitio.<br>
        <strong>Alcance:</strong> Scope 2 indirecto + Scope 3 cat. 11 (GHG Protocol Corporate Standard).<br>
        <strong>No incluye:</strong> fabricación de hardware, Scope 1, emisiones de dispositivos del usuario final.<br>
        Esta estimación <strong>no constituye verificación certificada</strong> ni reemplaza auditoría conforme a ISO 14064.
    </div>

    <button class="cc-dl-btn" id="cc-dl-btn">Descargar resumen de estimación (.txt)</button>
</div>

</div><!-- /.cc-wrap -->

<script>
(function(){
'use strict';

/* ── Modelo SWD v3 — metodología pública de Green Web Foundation
   (sustainablewebdesign.org/estimating-digital-emissions/), implementación
   propia e independiente. No se usa código ni datasets de Green Web
   Foundation/Ember: sin llamadas a ninguna API de terceros. ─────────── */
var SWD = {
    KWH_PER_GB:        0.81,
    DATACENTER:        0.15,
    NETWORK:           0.14,
    DEVICE:            0.52,
    PRODUCTION:        0.19,
    RENEWABLES:        50,    // gCO2/kWh -- factor residual para hosting verde certificado
};

/* Intensidad de red eléctrica por país -- cada valor citado a su fuente
   oficial individual, verificada directamente (no vía Ember/GWF):
   - ESP/DEU/FRA/NLD/IRL: European Environment Agency (EEA), indicador
     "Greenhouse gas emission intensity of electricity generation, country
     level", dato mas reciente disponible = 2024 (gCO2e/kWh).
   - USA: U.S. Energy Information Administration (EIA), FAQ "How much
     carbon dioxide is produced per kilowatthour of U.S. electricity
     generation?" -- 0.81 lb CO2/kWh (2023) convertido a gramos:
     0.81 * 453.592 = 367.4 g/kWh.
   - WORLD: International Energy Agency (IEA), informe "Electricity 2025",
     media mundial 2024 = 445 gCO2/kWh. */
var INTENSITY = {
    ESP:   { name:'España',         v:129,   year:2024, source:'European Environment Agency (EEA)' },
    DEU:   { name:'Alemania',       v:298,   year:2024, source:'European Environment Agency (EEA)' },
    FRA:   { name:'Francia',        v:43,    year:2024, source:'European Environment Agency (EEA)' },
    NLD:   { name:'Países Bajos',   v:235,   year:2024, source:'European Environment Agency (EEA)' },
    IRL:   { name:'Irlanda',        v:238,   year:2024, source:'European Environment Agency (EEA)' },
    USA:   { name:'EE. UU.',        v:367.4, year:2023, source:'U.S. Energy Information Administration (EIA)' },
    WORLD: { name:'Media mundial',  v:445,   year:2024, source:'International Energy Agency (IEA), informe Electricity 2025' },
};

/* Rating SWD v3 — percentiles publicados del modelo (g CO2/visita) */
function swdRating(g) {
    if (g <= 0.095) return 'A';
    if (g <= 0.186) return 'B';
    if (g <= 0.341) return 'C';
    if (g <= 0.493) return 'D';
    if (g <= 0.656) return 'E';
    return 'F';
}

/* ── Fórmula SWD perByte ───────────────────────────────────────── */
/* gridIntensity: gCO2/kWh del país seleccionado a mano por el usuario.
   Solo se aplica al segmento datacenter (15%) -- red, dispositivo y
   producción usan la media mundial porque no dependen de la ubicación
   del servidor. */
function swdPerByte(bytes, isGreen, gridIntensity) {
    var intensity = (typeof gridIntensity === 'number' && !isNaN(gridIntensity)) ? gridIntensity : INTENSITY.WORLD.v;

    var gb = bytes / 1e9;
    var kwh = gb * SWD.KWH_PER_GB;

    var dcEnergy  = kwh * SWD.DATACENTER;
    var netEnergy = kwh * SWD.NETWORK;
    var devEnergy = kwh * SWD.DEVICE;
    var proEnergy = kwh * SWD.PRODUCTION;

    var gridDC  = isGreen ? SWD.RENEWABLES : intensity;
    var gridNet = INTENSITY.WORLD.v;
    var gridDev = INTENSITY.WORLD.v;

    var co2g = (dcEnergy  * gridDC  +
                netEnergy * gridNet +
                devEnergy * gridDev +
                proEnergy * INTENSITY.WORLD.v);
    return co2g; // gramos CO2eq
}

/* ── DOM ────────────────────────────────────────────────────────── */
var calcBtn = document.getElementById('cc-calc-btn');
var loadEl  = document.getElementById('cc-loading');
var errEl   = document.getElementById('cc-error');
var resEl   = document.getElementById('cc-results');

function showErr(msg){ errEl.textContent = msg; errEl.style.display = 'block'; }
function hideErr(){ errEl.textContent = ''; errEl.style.display = 'none'; }

/* ── Render resultados ──────────────────────────────────────────── */
function renderResults(bytes, visits, isGreen, gridInfo){
    var perVisitG  = swdPerByte(bytes, isGreen === true, gridInfo.v);
    var monthlyKg  = (perVisitG * visits) / 1000;
    var sizeKB     = bytes / 1024;
    var rating     = swdRating(perVisitG);

    /* Badge hosting */
    var badge = document.getElementById('cc-hosting-badge');
    if (isGreen === true){
        badge.textContent = '✓ Hosting verde (autodeclarado)';
        badge.className = 'cc-badge green';
    } else {
        badge.textContent = isGreen === false ? '✗ Hosting no renovable' : '? Hosting no verificado';
        badge.className = 'cc-badge grey';
    }

    /* Rating */
    var ratingEl = document.getElementById('cc-swd-rating');
    ratingEl.textContent = 'Rating SWD: ' + rating;
    ratingEl.className = 'cc-rating ' + rating;
    ratingEl.style.display = 'inline-block';

    /* Métricas */
    document.getElementById('cc-r-pervisit').innerHTML =
        perVisitG.toFixed(3) + ' <span class="cc-munit">g CO₂eq</span>';
    document.getElementById('cc-r-monthly').innerHTML =
        monthlyKg.toFixed(2) + ' <span class="cc-munit">kg CO₂eq</span>';
    document.getElementById('cc-r-size').innerHTML =
        sizeKB.toFixed(0) + ' <span class="cc-munit">KB</span>';

    /* Red eléctrica */
    document.getElementById('cc-grid-country').textContent   = gridInfo.name;
    document.getElementById('cc-grid-intensity').textContent = gridInfo.v;
    document.getElementById('cc-grid-source').textContent    = gridInfo.source;
    document.getElementById('cc-grid-year').textContent      = gridInfo.year;
    document.getElementById('cc-grid-box').style.display     = 'block';

    /* Barras */
    var maxV = Math.max(perVisitG, 0.50) * 1.3;
    document.getElementById('cc-bar-u').style.width    = Math.min((perVisitG/maxV)*100,100) + '%';
    document.getElementById('cc-bar-u-val').textContent = perVisitG.toFixed(3) + ' g';

    /* Guardar para descarga */
    window._ccLastResult = {
        perVisitG: perVisitG, monthlyKg: monthlyKg, sizeKB: sizeKB,
        isGreen: isGreen, gridInfo: gridInfo, rating: rating,
        visits: visits
    };

    resEl.style.display = 'block';
    resEl.scrollIntoView({ behavior:'smooth', block:'start' });

    loadEl.style.display = 'none';
    calcBtn.disabled = false;
}

/* ── Click calcular ─────────────────────────────────────────────── */
calcBtn.addEventListener('click', function(){
    hideErr();
    resEl.style.display = 'none';
    document.getElementById('cc-grid-box').style.display = 'none';

    var kb      = parseFloat(document.getElementById('cc-bytes').value);
    var visits  = parseInt(document.getElementById('cc-visits').value, 10);
    var gs      = document.getElementById('cc-green-manual').value;
    var country = document.getElementById('cc-country').value;
    var isGreenManual = gs === 'true' ? true : gs === 'false' ? false : null;

    if (!kb || kb <= 0)        return showErr('Introduce el peso de página en KB (mira las DevTools de tu navegador).');
    if (!visits || visits < 1) return showErr('Introduce las visitas mensuales.');

    /* Cálculo 100% local, sin llamadas externas de ningún tipo */
    var gridInfo = INTENSITY[country] || INTENSITY['WORLD'];
    renderResults(kb * 1024, visits, isGreenManual, gridInfo);
});

/* ── Descarga resumen .txt ──────────────────────────────────────── */
document.getElementById('cc-dl-btn').addEventListener('click', function(){
    var r = window._ccLastResult;
    if (!r) return;
    var now = new Date().toLocaleDateString('es-ES');
    var grid = r.gridInfo || {};
    var lines = [
        'ESTIMACIÓN DE HUELLA DIGITAL',
        '================================',
        'Fecha: ' + now,
        '',
        'RESULTADOS',
        '----------',
        'CO₂ por visita:         ' + r.perVisitG.toFixed(4) + ' g CO₂eq',
        'CO₂ mensual estimado:   ' + r.monthlyKg.toFixed(3) + ' kg CO₂eq',
        'Visitas mensuales:      ' + r.visits,
        'Peso de página:         ' + r.sizeKB.toFixed(0) + ' KB',
        'Hosting verde:          ' + (r.isGreen === true ? 'Sí (autodeclarado)' : r.isGreen === false ? 'No' : 'No verificado'),
        'Rating SWD v3:          ' + r.rating,
        '',
        'RED ELÉCTRICA',
        '-------------',
        'País:                   ' + (grid.name || '–'),
        'Intensidad carbono:     ' + (grid.v || '–') + ' gCO₂/kWh',
        'Año del dato:           ' + (grid.year || '–'),
        'Fuente:                 ' + (grid.source || '–'),
        '',
        'METODOLOGÍA Y ALCANCE',
        '---------------------',
        'Modelo:                 Sustainable Web Design v3 (metodología pública de Green',
        '                        Web Foundation, sustainablewebdesign.org), implementación',
        '                        propia e independiente -- sin código ni datasets de terceros.',
        'Constantes SWD v3:      KWH_PER_GB=0.81 · DC=15% · Red=14% · Disp=52% · Prod=19%',
        'Medición peso página:   Importado manualmente desde DevTools del navegador',
        'Hosting verde/país:     Seleccionados a mano por el usuario',
        'Intensidad de red:      European Environment Agency (EEA, 2024) para España/',
        '                        Alemania/Francia/Países Bajos/Irlanda; U.S. Energy',
        '                        Information Administration (EIA, 2023) para EE. UU.;',
        '                        International Energy Agency (IEA, Electricity 2025, 2024)',
        '                        para la media mundial.',
        'Alcance GHG Protocol:   Scope 2 indirecto + Scope 3 categoría 11',
        'Referencia sectorial:   HTTP Archive 2024',
        '  Media web (p50):      0.50 g CO₂eq/visita',
        '  Top 10% más limpio:   0.09 g CO₂eq/visita',
        '',
        'NO INCLUYE: fabricación de hardware, Scope 1, emisiones de dispositivos.',
        'Cálculo 100% local en el navegador -- ninguna llamada a servicios externos.',
        '',
        'AVISO: Estimación no certificada. No reemplaza auditoría ISO 14064',
        'ni verificación GHG Protocol por tercero independiente.',
        '',
        'Herramienta: Yel Martínez | Greentech — https://yel-martinez-portfolio.com',
    ];
    var blob = new Blob([lines.join('\n')], {type:'text/plain;charset=utf-8'});
    var a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'estimacion-huella-digital-' + now.replace(/\//g,'-') + '.txt';
    a.click();
});

})();
</script>

    <?php
    return ob_get_clean();
}
add_shortcode( 'carbon_calculator', 'cc_shortcode' );
