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
