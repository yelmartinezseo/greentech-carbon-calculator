(function(){
'use strict';

/* ── URLs de servicios publicos usados (ninguno de Google) ───────── */
var DOH_ENDPOINT = 'https://cloudflare-dns.com/dns-query';

/* ── Modelo SWD v3 — constantes oficiales Green Web Foundation ─── */
var SWD = {
    KWH_PER_GB:        0.81,
    DATACENTER:        0.15,
    NETWORK:           0.14,
    DEVICE:            0.52,
    PRODUCTION:        0.19,
    GRID_WORLD:        472.94,   // gCO2/kWh — Ember via GWF dataset
    RENEWABLES:        50,
    FIRST_VISIT:       0.75,
    RETURN_VISIT:      0.25,
    RETURN_DATA_RATIO: 0.02,
};

/* Intensidades por país — dataset oficial GWF/Ember */
var INTENSITY = {
    ESP:   { name:'España',         v:146.15, fossil:31.2, year:2023 },
    DEU:   { name:'Alemania',       v:342.06, fossil:44.5, year:2023 },
    FRA:   { name:'Francia',        v:44.18,  fossil:7.0,  year:2023 },
    NLD:   { name:'Países Bajos',   v:252.7,  fossil:55.0, year:2023 },
    IRL:   { name:'Irlanda',        v:279.79, fossil:54.0, year:2023 },
    USA:   { name:'EE. UU.',        v:383.55, fossil:60.3, year:2023 },
    WORLD: { name:'Media mundial',  v:472.94, fossil:61.0, year:2023 },
};

/* Rating SWD v3 — percentiles oficiales (g CO2/visita) */
function swdRating(g) {
    if (g <= 0.095) return 'A';
    if (g <= 0.186) return 'B';
    if (g <= 0.341) return 'C';
    if (g <= 0.493) return 'D';
    if (g <= 0.656) return 'E';
    return 'F';
}

/* ── Fórmula SWD perByte ───────────────────────────────────────── */
/* gridIntensity: gCO2/kWh real del país del servidor (detectado via API o
   elegido a mano). Solo se aplica al segmento datacenter (15%) -- red,
   dispositivo y produccion usan la media mundial porque no dependen de la
   ubicacion del servidor. Bug corregido 2026-09-11: antes el datacenter
   ignoraba este dato y usaba siempre GRID_WORLD, asi que el pais elegido
   o detectado no cambiaba el resultado (verificado: España 146 gCO2/kWh y
   EE.UU. 384 gCO2/kWh daban el mismo g CO2/visita). */
function swdPerByte(bytes, isGreen, gridIntensity) {
    var intensity = (typeof gridIntensity === 'number' && !isNaN(gridIntensity)) ? gridIntensity : SWD.GRID_WORLD;

    var gb = bytes / 1e9;
    var kwh = gb * SWD.KWH_PER_GB;

    var dcEnergy  = kwh * SWD.DATACENTER;
    var netEnergy = kwh * SWD.NETWORK;
    var devEnergy = kwh * SWD.DEVICE;
    var proEnergy = kwh * SWD.PRODUCTION;

    var gridDC  = isGreen ? SWD.RENEWABLES : intensity;
    var gridNet = SWD.GRID_WORLD;
    var gridDev = SWD.GRID_WORLD;

    var co2g = (dcEnergy  * gridDC  +
                netEnergy * gridNet +
                devEnergy * gridDev +
                proEnergy * SWD.GRID_WORLD);
    return co2g; // gramos CO2eq
}

/* ── DOM ────────────────────────────────────────────────────────── */
var calcBtn = document.getElementById('cc-calc-btn');
var loadEl  = document.getElementById('cc-loading');
var errEl   = document.getElementById('cc-error');
var resEl   = document.getElementById('cc-results');

function showErr(msg){ errEl.textContent = msg; errEl.style.display = 'block'; }
function hideErr(){ errEl.textContent = ''; errEl.style.display = 'none'; }

function extractDomain(url){
    try { return new URL(url).hostname.replace(/^www\./,''); }
    catch(e){ return null; }
}

/* ── Llamadas AJAX ──────────────────────────────────────────────── */
function fetchGreen(domain, cb){
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'https://api.thegreenwebfoundation.org/api/v3/greencheck/' + encodeURIComponent(domain));
    xhr.onload = function(){
        try { var d = JSON.parse(xhr.responseText); cb(d); }
        catch(e){ cb(null); }
    };
    xhr.onerror = function(){ cb(null); };
    xhr.send();
}

function fetchGrid(domain, cb){
    var dohUrl = DOH_ENDPOINT + '?name=' + encodeURIComponent(domain) + '&type=A';
    fetch(dohUrl, { headers: { accept: 'application/dns-json' } })
        .then(function(r){ return r.json(); })
        .then(function(dnsData){
            var answers = dnsData.Answer || [];
            var aRecord = null;
            for (var i = 0; i < answers.length; i++){
                if (answers[i].type === 1){ aRecord = answers[i]; break; } // type 1 = registro A
            }
            if (!aRecord || !aRecord.data){ cb(null); return; }
            var xhr2 = new XMLHttpRequest();
            xhr2.open('GET', 'https://api.thegreenwebfoundation.org/api/v3/ip-to-co2intensity/' + aRecord.data);
            xhr2.onload = function(){
                try { cb(JSON.parse(xhr2.responseText)); }
                catch(e){ cb(null); }
            };
            xhr2.onerror = function(){ cb(null); };
            xhr2.send();
        })
        .catch(function(){ cb(null); });
}

/* ── Render resultados ──────────────────────────────────────────── */
function renderResults(bytes, visits, isGreen, hostedBy, gridInfo){
    var intensity  = gridInfo ? parseFloat(gridInfo.carbon_intensity != null ? gridInfo.carbon_intensity : gridInfo.v) : NaN;
    var perVisitG  = swdPerByte(bytes, isGreen === true, intensity);
    var monthlyKg  = (perVisitG * visits) / 1000;
    var sizeKB     = bytes / 1024;
    var rating     = swdRating(perVisitG);

    /* Badge hosting */
    var badge = document.getElementById('cc-hosting-badge');
    if (isGreen === true){
        badge.textContent = '✓ Hosting verde' + (hostedBy ? ' · ' + hostedBy : '');
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
    if (gridInfo){
        document.getElementById('cc-grid-country').textContent   = gridInfo.country_name || gridInfo.name;
        document.getElementById('cc-grid-intensity').textContent = (gridInfo.carbon_intensity || gridInfo.v || '–');
        document.getElementById('cc-grid-fossil').textContent    = (gridInfo.generation_from_fossil || gridInfo.fossil || '–');
        document.getElementById('cc-grid-year').textContent      = gridInfo.year || '–';
        document.getElementById('cc-grid-box').style.display     = 'block';
    }

    /* Barras */
    var maxV = Math.max(perVisitG, 0.50) * 1.3;
    document.getElementById('cc-bar-u').style.width    = Math.min((perVisitG/maxV)*100,100) + '%';
    document.getElementById('cc-bar-u-val').textContent = perVisitG.toFixed(3) + ' g';

    /* Guardar para descarga */
    window._ccLastResult = {
        perVisitG: perVisitG, monthlyKg: monthlyKg, sizeKB: sizeKB,
        isGreen: isGreen, hostedBy: hostedBy, gridInfo: gridInfo, rating: rating,
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

    var url     = (document.getElementById('cc-url').value || '').trim();
    var kb      = parseFloat(document.getElementById('cc-bytes').value);
    var visits  = parseInt(document.getElementById('cc-visits').value, 10);
    var gs      = document.getElementById('cc-green-manual').value;
    var country = document.getElementById('cc-country').value;
    var isGreenManual = gs === 'true' ? true : gs === 'false' ? false : null;

    if (!kb || kb <= 0)        return showErr('Introduce el peso de página en KB (mira las DevTools de tu navegador).');
    if (!visits || visits < 1) return showErr('Introduce las visitas mensuales.');

    loadEl.style.display = 'block';
    calcBtn.disabled = true;

    /* Sin URL: calculo directo, todo manual, sin llamadas externas */
    if (!url){
        var gridFallback = INTENSITY[country] || INTENSITY['WORLD'];
        loadEl.style.display = 'none';
        calcBtn.disabled = false;
        renderResults(kb * 1024, visits, isGreenManual, null, gridFallback);
        return;
    }

    /* Con URL: verificamos hosting verde e intensidad de red automaticamente
       (Green Web Foundation + Cloudflare DNS). El peso siempre viene del input. */
    var domain = extractDomain(url);
    if (!domain){
        loadEl.style.display = 'none';
        calcBtn.disabled = false;
        return showErr('La URL introducida no es válida.');
    }

    var greenResult = null, gridResult = null;
    var pending = 2;

    function done(){
        pending--;
        if (pending > 0) return;

        loadEl.style.display = 'none';
        calcBtn.disabled = false;

        var isGr    = greenResult ? greenResult.green === true : null;
        var hostedB = greenResult ? (greenResult.hosted_by || null) : null;
        var gridI   = gridResult  || INTENSITY['WORLD'];
        renderResults(kb * 1024, visits, isGr, hostedB, gridI);
    }

    fetchGreen(domain, function(g){ greenResult = g; done(); });
    fetchGrid(domain, function(g){ gridResult = g; done(); });
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
        'Hosting verde:          ' + (r.isGreen === true ? 'Sí · ' + (r.hostedBy||'') : r.isGreen === false ? 'No' : 'No verificado'),
        'Rating SWD v3:          ' + r.rating,
        '',
        'RED ELÉCTRICA',
        '-------------',
        'País:                   ' + (grid.country_name || grid.name || '–'),
        'Intensidad carbono:     ' + (grid.carbon_intensity || grid.v || '–') + ' gCO₂/kWh',
        'Generación fósil:       ' + (grid.generation_from_fossil || grid.fossil || '–') + '%',
        'Año del dato:           ' + (grid.year || '–'),
        'Fuente:                 Ember / Green Web Foundation IP to CO2 API',
        '',
        'METODOLOGÍA Y ALCANCE',
        '---------------------',
        'Modelo:                 Sustainable Web Design v3 (Green Web Foundation)',
        'Licencia modelo:        Apache 2.0',
        'Constantes SWD v3:      KWH_PER_GB=0.81 · DC=15% · Red=14% · Disp=52% · Prod=19%',
        'Medición peso página:   Importado manualmente desde DevTools del navegador',
        'Resolución de dominio:  Cloudflare DNS over HTTPS',
        'Verificación hosting:   Greencheck API v3 (Green Web Foundation)',
        'Intensidad de red:      IP to CO2 Intensity API (GWF/Ember, CC BY-SA 4.0)',
        'Alcance GHG Protocol:   Scope 2 indirecto + Scope 3 categoría 11',
        'Referencia sectorial:   HTTP Archive 2024',
        '  Media web (p50):      0.50 g CO₂eq/visita',
        '  Top 10% más limpio:   0.09 g CO₂eq/visita',
        '',
        'NO INCLUYE: fabricación de hardware, Scope 1, emisiones de dispositivos.',
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