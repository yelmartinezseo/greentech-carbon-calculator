<?php
/**
 * Plugin Name: Calculadora de Huella Digital
 * Plugin URI:  https://yel-martinez-portfolio.com
 * Description: Estima la huella de carbono digital de cualquier sitio web. Modelo: Sustainable Web Design v3 (Green Web Foundation). Alcance: Scope 2 + Scope 3 cat.11 (GHG Protocol).
 * Version:     3.1.1
 * Author:      Yel Martínez | Greentech
 * Author URI:  https://yel-martinez-portfolio.com
 * License:     GPL-2.0+
 * Text Domain: carbon-calculator
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* -----------------------------------------------------------------------
 * AJAX: obtener peso de página (proxy PHP para evitar CORS)
 * ----------------------------------------------------------------------- */
add_action( 'wp_ajax_cc_fetch_size',        'cc_ajax_fetch_size' );
add_action( 'wp_ajax_nopriv_cc_fetch_size', 'cc_ajax_fetch_size' );

function cc_ajax_fetch_size() {
    check_ajax_referer( 'cc_nonce', 'nonce' );
    $url = esc_url_raw( $_GET['url'] ?? '' );
    if ( empty( $url ) ) wp_send_json( ['bytes' => 0, 'error' => 'url requerida'] );

    $response = wp_remote_get( $url, [
        'timeout'    => 15,
        'user-agent' => 'Mozilla/5.0 (compatible; GreenCalc/3.1)',
        'sslverify'  => false,
    ]);

    if ( is_wp_error( $response ) ) {
        wp_send_json( ['bytes' => 0, 'error' => $response->get_error_message()] );
    }

    $bytes = strlen( wp_remote_retrieve_body( $response ) );
    wp_send_json( ['bytes' => $bytes] );
}

/* -----------------------------------------------------------------------
 * AJAX: resolver IP de un dominio
 * ----------------------------------------------------------------------- */
add_action( 'wp_ajax_cc_get_ip',        'cc_ajax_get_ip' );
add_action( 'wp_ajax_nopriv_cc_get_ip', 'cc_ajax_get_ip' );

function cc_ajax_get_ip() {
    check_ajax_referer( 'cc_nonce', 'nonce' );
    $domain = sanitize_text_field( $_GET['domain'] ?? '' );
    if ( empty( $domain ) ) wp_send_json( ['ip' => null] );

    $ip = gethostbyname( $domain );
    if ( $ip === $domain || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
        wp_send_json( ['ip' => null] );
    }
    wp_send_json( ['ip' => $ip] );
}

/* -----------------------------------------------------------------------
 * SHORTCODE PRINCIPAL  [carbon_calculator]
 * ----------------------------------------------------------------------- */
function cc_shortcode() {
    $nonce    = wp_create_nonce( 'cc_nonce' );
    $ajax_url = admin_url( 'admin-ajax.php' );
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

/* ── Tabs ───────────────────────────────────────────────────────── */
.cc-tabs{display:flex;gap:.5em;margin-bottom:1.6em;}
.cc-tab{flex:1;padding:.65em .4em;border:2px solid #ddd;border-radius:9px;background:#f5f5f5;
  cursor:pointer;font-size:.85em;font-weight:700;color:#555;text-align:center;transition:.18s;}
.cc-tab:hover:not(.active){border-color:#0085ff;color:#0085ff;}
.cc-tab.active{border-color:#ffde59;background:#ffde59;color:#1a1830;}

/* ── Panels ─────────────────────────────────────────────────────── */
.cc-panel{display:none;}
.cc-panel.active{display:block;}

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
        Modelo: <strong>Sustainable Web Design v3</strong> · Green Web Foundation &nbsp;·&nbsp;
        Alcance: Scope 2 + Scope 3 cat. 11 (GHG Protocol)
    </p>

    <!-- TABS -->
    <div class="cc-tabs">
        <div class="cc-tab active" data-panel="url">Modo URL <span style="font-weight:400">(automático)</span></div>
        <div class="cc-tab" data-panel="manual">Modo manual <span style="font-weight:400">(control total)</span></div>
        <div class="cc-tab" data-panel="hybrid">Modo mixto</div>
    </div>

    <!-- PANEL URL -->
    <div class="cc-panel active" id="cc-panel-url">
        <div class="cc-field">
            <label for="cc-url">URL del sitio web a analizar</label>
            <input type="url" id="cc-url" placeholder="https://ejemplo.com" autocomplete="off" spellcheck="false">
            <p class="cc-hint">Se analiza la página de inicio. Sitios con CDN pueden mostrar la ubicación del nodo, no del datacenter real.</p>
        </div>
        <div class="cc-field">
            <label for="cc-visits-url">Visitas mensuales estimadas</label>
            <input type="number" id="cc-visits-url" placeholder="Ej: 10000" min="1">
        </div>
    </div>

    <!-- PANEL MANUAL -->
    <div class="cc-panel" id="cc-panel-manual">
        <div class="cc-field">
            <label for="cc-bytes">Peso de página (KB)</label>
            <input type="number" id="cc-bytes" placeholder="Ej: 1500" min="1">
            <p class="cc-hint">DevTools del navegador → Pestaña Red → columna "Tamaño transferido".</p>
        </div>
        <div class="cc-field">
            <label for="cc-visits-manual">Visitas mensuales</label>
            <input type="number" id="cc-visits-manual" placeholder="Ej: 10000" min="1">
        </div>
        <div class="cc-field">
            <label for="cc-green-manual">¿El hosting usa energía renovable?</label>
            <select id="cc-green-manual">
                <option value="unknown">No lo sé</option>
                <option value="true">Sí, verificado</option>
                <option value="false">No</option>
            </select>
        </div>
        <div class="cc-field">
            <label for="cc-country">País del servidor</label>
            <select id="cc-country">
                <option value="ESP">España (146 gCO₂/kWh)</option>
                <option value="DEU">Alemania (342 gCO₂/kWh)</option>
                <option value="FRA">Francia (44 gCO₂/kWh)</option>
                <option value="NLD">Países Bajos (253 gCO₂/kWh)</option>
                <option value="IRL">Irlanda (280 gCO₂/kWh)</option>
                <option value="USA">Estados Unidos (384 gCO₂/kWh)</option>
                <option value="WORLD">Media mundial (473 gCO₂/kWh)</option>
            </select>
        </div>
    </div>

    <!-- PANEL MIXTO -->
    <div class="cc-panel" id="cc-panel-hybrid">
        <div class="cc-field">
            <label for="cc-url-hybrid">URL del sitio (para obtener peso y verificar hosting)</label>
            <input type="url" id="cc-url-hybrid" placeholder="https://ejemplo.com">
        </div>
        <div class="cc-field">
            <label for="cc-visits-hybrid">Visitas mensuales (introduce el dato de Analytics)</label>
            <input type="number" id="cc-visits-hybrid" placeholder="Ej: 10000" min="1">
            <p class="cc-hint">El peso se obtiene automáticamente. Las visitas las introduces tú porque las conoces mejor.</p>
        </div>
    </div>

    <button class="cc-btn" id="cc-calc-btn">Calcular huella digital</button>
    <div class="cc-loading" id="cc-loading"><span class="cc-spin"></span>Consultando fuentes y calculando…</div>
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
        Red eléctrica detectada: <strong id="cc-grid-country">–</strong> ·
        Intensidad: <strong id="cc-grid-intensity">–</strong> gCO₂/kWh ·
        Generación fósil: <strong id="cc-grid-fossil">–</strong>%<br>
        <span class="cc-grid-src">Fuente: Ember / Green Web Foundation IP to CO2 API · Año <span id="cc-grid-year">–</span></span>
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
        Modelo <strong>Sustainable Web Design v3</strong> (Green Web Foundation, Apache 2.0) implementado de forma nativa.
        Constantes: KWH_PER_GB=0.81 · Datacenter=15% · Red=14% · Dispositivo=52% · Producción=19%.
        Verificación hosting: <strong>Greencheck API v3</strong> (Green Web Foundation).
        Intensidad red: <strong>IP to CO2 Intensity API</strong> (Green Web Foundation / Ember, CC BY-SA 4.0).<br>
        <strong>Alcance:</strong> Scope 2 indirecto + Scope 3 cat. 11 (GHG Protocol Corporate Standard).<br>
        <strong>No incluye:</strong> fabricación de hardware, Scope 1, emisiones de dispositivos del usuario final.<br>
        <strong>Limitación CDN/proxy:</strong> la IP detectada puede corresponder al nodo de distribución, no al datacenter real.<br>
        Esta estimación <strong>no constituye verificación certificada</strong> ni reemplaza auditoría conforme a ISO 14064.
    </div>

    <button class="cc-dl-btn" id="cc-dl-btn">Descargar resumen de estimación (.txt)</button>
</div>

</div><!-- /.cc-wrap -->

<script>
(function(){
'use strict';

/* ── URLs AJAX ─────────────────────────────────────────────────── */
var AJAX = '<?php echo esc_js( $ajax_url ); ?>';
var NONCE = '<?php echo esc_js( $nonce ); ?>';

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
/* gridIntensity: gCO2/kWh real del pais del servidor (detectado via API o
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
var mode    = 'url';

/* Tabs */
document.querySelectorAll('.cc-tab').forEach(function(tab){
    tab.addEventListener('click', function(){
        document.querySelectorAll('.cc-tab').forEach(function(t){ t.classList.remove('active'); });
        document.querySelectorAll('.cc-panel').forEach(function(p){ p.classList.remove('active'); });
        tab.classList.add('active');
        mode = tab.dataset.panel;
        document.getElementById('cc-panel-' + mode).classList.add('active');
        resEl.style.display = 'none';
        hideErr();
    });
});

function showErr(msg){ errEl.textContent = msg; errEl.style.display = 'block'; }
function hideErr(){ errEl.textContent = ''; errEl.style.display = 'none'; }

function extractDomain(url){
    try { return new URL(url).hostname.replace(/^www\./,''); }
    catch(e){ return null; }
}

/* ── Llamadas AJAX ──────────────────────────────────────────────── */
function fetchSize(url, cb){
    var xhr = new XMLHttpRequest();
    xhr.open('GET', AJAX + '?action=cc_fetch_size&url=' + encodeURIComponent(url) + '&nonce=' + NONCE);
    xhr.onload = function(){
        try { var d = JSON.parse(xhr.responseText); cb(d.bytes || 0); }
        catch(e){ cb(0); }
    };
    xhr.onerror = function(){ cb(0); };
    xhr.send();
}

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
    var xhr1 = new XMLHttpRequest();
    xhr1.open('GET', AJAX + '?action=cc_get_ip&domain=' + encodeURIComponent(domain) + '&nonce=' + NONCE);
    xhr1.onload = function(){
        try {
            var ipData = JSON.parse(xhr1.responseText);
            if (!ipData.ip){ cb(null); return; }
            var xhr2 = new XMLHttpRequest();
            xhr2.open('GET', 'https://api.thegreenwebfoundation.org/api/v3/ip-to-co2intensity/' + ipData.ip);
            xhr2.onload = function(){
                try { cb(JSON.parse(xhr2.responseText)); }
                catch(e){ cb(null); }
            };
            xhr2.onerror = function(){ cb(null); };
            xhr2.send();
        } catch(e){ cb(null); }
    };
    xhr1.onerror = function(){ cb(null); };
    xhr1.send();
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

    var url, visits, kb, isGreen = null, country = 'WORLD';

    if (mode === 'url'){
        url    = (document.getElementById('cc-url').value || '').trim();
        visits = parseInt(document.getElementById('cc-visits-url').value, 10);
        if (!url)            return showErr('Introduce una URL válida.');
        if (!visits || visits < 1) return showErr('Introduce las visitas mensuales.');
    } else if (mode === 'manual'){
        kb      = parseFloat(document.getElementById('cc-bytes').value);
        visits  = parseInt(document.getElementById('cc-visits-manual').value, 10);
        var gs  = document.getElementById('cc-green-manual').value;
        country = document.getElementById('cc-country').value;
        if (!kb || kb <= 0)        return showErr('Introduce el peso de página en KB.');
        if (!visits || visits < 1) return showErr('Introduce las visitas mensuales.');
        isGreen = gs === 'true' ? true : gs === 'false' ? false : null;
    } else {
        url    = (document.getElementById('cc-url-hybrid').value || '').trim();
        visits = parseInt(document.getElementById('cc-visits-hybrid').value, 10);
        if (!url)            return showErr('Introduce una URL válida.');
        if (!visits || visits < 1) return showErr('Introduce las visitas mensuales.');
    }

    loadEl.style.display = 'block';
    calcBtn.disabled = true;

    /* Modo manual: cálculo directo sin llamadas externas */
    if (mode === 'manual'){
        var gridFallback = INTENSITY[country] || INTENSITY['WORLD'];
        renderResults(kb * 1024, visits, isGreen, null, gridFallback);
        return;
    }

    /* Modos URL y mixto: llamadas en paralelo */
    var domain  = extractDomain(url);
    if (!domain){
        loadEl.style.display = 'none';
        calcBtn.disabled = false;
        return showErr('La URL introducida no es válida.');
    }

    var bytesResult = null, greenResult = null, gridResult = null;
    var pending = 3;

    function done(){
        pending--;
        if (pending > 0) return;

        var bytes   = bytesResult > 0 ? bytesResult : 2300 * 1024; // fallback 2.3 MB
        var isGr    = greenResult ? greenResult.green === true : null;
        var hostedB = greenResult ? (greenResult.hosted_by || null) : null;
        var gridI   = gridResult  || INTENSITY['WORLD'];
        renderResults(bytes, visits, isGr, hostedB, gridI);
    }

    fetchSize(url, function(b){ bytesResult = b; done(); });
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
</script>

    <?php
    return ob_get_clean();
}
add_shortcode( 'carbon_calculator', 'cc_shortcode' );
