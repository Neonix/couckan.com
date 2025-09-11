<?php
include __DIR__ . '/../../../config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>⚡ Chat Futuriste ⚡</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="description" content="Chat futuriste pour se connecter et converser en temps réel sur une carte interactive." />
  <link rel="canonical" href="https://couckan.com/" />
  <link rel="icon" href="/favicon.svg" type="image/svg+xml" />
  <meta property="og:title" content="Chat Futuriste" />
  <meta property="og:description" content="Rejoignez Couckan pour discuter en temps réel sur une carte interactive mondiale." />
  <meta property="og:type" content="website" />
  <meta property="og:url" content="https://couckan.com/" />
  <meta name="twitter:card" content="summary_large_image" />
  <meta name="twitter:title" content="Chat Futuriste" />
  <meta name="twitter:description" content="Rejoignez Couckan pour discuter en temps réel sur une carte interactive mondiale." />
  <link href="https://cesium.com/downloads/cesiumjs/releases/1.111/Build/Cesium/Widgets/widgets.css" rel="stylesheet" />
  <script src="https://cesium.com/downloads/cesiumjs/releases/1.111/Build/Cesium/Cesium.js"></script>
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "Couckan Chat",
    "url": "https://couckan.com/"
  }
  </script>
  <style>
    :root{
      --bg:#0f172a; --panel:#1e293b; --muted:#334155; --muted-2:#475569;
      --accent:#0ea5e9; --text:#f8fafc; --sub:#94a3b8;
      --ok:#22c55e; --busy:#f97316; --away:#ef4444; --warn:#facc15; --invisible:#6b7280;
      --nav-h:0px;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Inter,Arial,sans-serif;background:var(--bg);color:var(--text);overflow:hidden}
    #cesiumContainer{position:fixed;top:0;left:0;right:0;bottom:0}
    #chatWrapper{position:fixed;right:1rem;bottom:calc(1rem + env(safe-area-inset-bottom));width:clamp(300px,30vw,420px);height:40vh;max-height:400px;display:flex;flex-direction:column;overflow:hidden;z-index:10;background:rgba(15,23,42,.7);backdrop-filter:blur(4px);border-radius:8px}
    #usersPanel{position:fixed;top:calc(44px + env(safe-area-inset-top));right:0;bottom:env(safe-area-inset-bottom);display:none;width:clamp(200px,20vw,340px);background:transparent;flex-direction:column;overflow-y:auto;z-index:20;padding:1rem;padding-bottom:calc(1rem + env(safe-area-inset-bottom))}
    #usersPanel.active{display:flex}
    .cesium-viewer-toolbar{z-index:30}
    .cesium-toolbar-button{margin:2px}
    .cesium-viewer .cesium-widget-credits{display:none!important}
    .sidebar{flex:0 0 clamp(200px,20vw,340px);background:var(--panel);display:flex;flex-direction:column;padding:0;overflow-y:auto;transition:width .2s ease,flex-basis .2s ease}
    .sidebar details{display:flex;flex-direction:column;gap:.75rem;padding:1rem}
    .sidebar summary{list-style:none;cursor:pointer}
    .sidebar summary::-webkit-details-marker{display:none}
    .h2{font-size:14px;font-weight:700;color:#38bdf8;display:flex;align-items:center;gap:.5rem;margin:0}
    .list{display:flex;flex-direction:column;gap:.4rem}
    .item{background:var(--muted);padding:.5rem .6rem;border-radius:8px;cursor:pointer;display:flex;align-items:center;gap:.5rem}
    .item:hover{background:var(--muted-2)}
    .item.active{background:var(--accent);color:#fff}
    .item.blink{animation:blink 1s infinite}
    @keyframes blink{0%,50%{background:var(--accent)}25%,75%{background:var(--warn)}}
    .row{display:flex;gap:.5rem}
    .row input[type=text]{flex:1;background:#0b1220;border:1px solid #214055;color:#cbd5e1;padding:.45rem .6rem;border-radius:6px;outline:none}
    .row label{display:flex;align-items:center;gap:.35rem;color:#cbd5e1;font-size:.9rem}
    .btn{background:var(--accent);border:none;color:#fff;border-radius:8px;padding:.4rem .65rem;cursor:pointer}
    .btn.secondary{background:var(--muted);color:#e5e7eb}
    .chat{flex:1;display:flex;flex-direction:column;min-height:0}
    .tabs{display:flex;align-items:center;gap:.5rem;background:var(--panel);padding:.5rem 1rem}
    #tabs{display:flex;align-items:center;gap:.5rem;flex-wrap:wrap}
    .tab{background:var(--muted);border-radius:6px;padding:.28rem .55rem;display:flex;align-items:center;gap:.4rem;cursor:pointer}
    .tab.active{background:var(--accent);color:#fff}
    .tab.blink{animation:blink 1s infinite}
    .cesium-toolbar-button.blink{animation:blink 1s infinite}
    #chatToggle.blink{animation:blink 1s infinite}
    .tab .actions{display:flex;align-items:center;gap:.25rem}
    .icon-btn{background:transparent;border:none;color:inherit;cursor:pointer;font-size:14px;opacity:.9}
    .icon-btn:hover{opacity:1}
    .messages{flex:1;overflow-y:auto;padding:1rem;background:rgba(11,18,32,.6);min-height:0}
    .msg{margin-bottom:12px;max-width:72%}
    .msg.me{margin-left:auto;text-align:right}
    .msg small{display:block;color:var(--sub);font-size:.72rem;margin-bottom:2px}
    .input{display:flex;gap:.5rem;background:rgba(30,41,59,.6);padding:.6rem;}
    .input textarea{flex:1;min-height:42px;max-height:160px;resize:vertical;background:#0b1220;border:1px solid #203244;color:#e5e7eb;padding:.6rem;border-radius:8px}
    .input button{background:var(--accent);border:none;color:#fff;border-radius:8px;padding:.55rem 1rem;cursor:pointer}
    .dot{width:10px;height:10px;border-radius:50%}
    .dot.ok{background:var(--ok)} .dot.busy{background:var(--busy)} .dot.away{background:var(--away)} .dot.invisible{background:var(--invisible)} .dot.offline{background:var(--invisible);opacity:.4}
    .dm-status{display:flex;align-items:center;gap:.35rem;margin-bottom:.5rem;font-size:.85rem;color:var(--sub)}
    .typing-indicator{font-size:.85rem;color:var(--sub);padding:.25rem 1rem;}
    .typing-indicator.hidden{display:none;}
    .select{width:100%;background:#0b1220;border:1px solid #203244;color:#e5e7eb;padding:.45rem .5rem;border-radius:6px}
    .hint{font-size:.8rem;color:#a3b2c7}
    #toastContainer{position:fixed;top:calc(1rem + env(safe-area-inset-top));left:50%;transform:translateX(-50%);display:flex;flex-direction:column;gap:.5rem;z-index:100;align-items:center}
    .toast{background:rgba(30,41,59,.9);color:var(--text);padding:.5rem 1rem;border-radius:6px;box-shadow:0 2px 4px rgba(0,0,0,.3);opacity:1;transition:opacity .5s}
    .toast.hide{opacity:0}
    #chatWrapper.hidden{display:none}
    #chatToggle{position:fixed;right:1rem;bottom:calc(1rem + env(safe-area-inset-bottom));background:var(--panel);color:var(--text);border:none;border-radius:50%;width:44px;height:44px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 4px rgba(0,0,0,.4);z-index:25;cursor:pointer}
    .profile-popup{position:absolute;display:none;flex-direction:column;gap:.25rem;padding:.5rem;background:var(--panel);border:1px solid var(--muted-2);border-radius:8px;z-index:40;min-width:160px}
    .profile-popup.active{display:flex}
    .profile-popup .title{font-weight:700;margin-bottom:.25rem}
    .profile-popup button{background:var(--muted);color:var(--text);border:none;border-radius:6px;padding:.3rem .5rem;cursor:pointer}
    .profile-popup button:hover{background:var(--muted-2)}
    #callOverlay{position:fixed;top:0;left:0;right:0;bottom:0;display:none;flex-direction:column;align-items:center;justify-content:center;background:rgba(0,0,0,.8);z-index:50}
    #callOverlay.active{display:flex}
    #callOverlay video{max-width:90%;max-height:80%;background:#000;border-radius:8px;margin:.5rem}
    #callOverlay.with-remote #localVideo{position:absolute;bottom:1rem;right:1rem;width:25%;max-width:200px;border:2px solid #fff;z-index:60}
    #callOverlay.with-remote #remoteVideos{flex:1;width:100%;height:100%;display:flex;align-items:center;justify-content:center}
    #callOverlay.with-remote #remoteVideos video{max-width:100%;max-height:100%}
    #callOverlay .error{color:#f87171;margin-top:.5rem;text-align:center}
    #remoteVideos{display:flex;flex-wrap:wrap;justify-content:center}
    #callControls{display:flex;gap:.5rem;flex-wrap:wrap;justify-content:center;margin-top:.5rem}
    #startOverlay{position:fixed;top:0;left:0;right:0;bottom:0;display:flex;align-items:center;justify-content:center;gap:2rem;background:rgba(0,0,0,.5);z-index:200}
    #startOverlay .choice{background:var(--panel);color:var(--text);padding:2rem 3rem;border-radius:12px;text-align:center;font-size:1.5rem;cursor:pointer;display:flex;flex-direction:column;align-items:center;gap:.5rem}
    #startOverlay .choice span{font-size:2.5rem}
    @media (max-width:768px){
      #chatWrapper{width:95%;right:2.5%;max-height:60vh}
      .sidebar{position:absolute;top:0;bottom:0;flex:none;width:80%;max-width:320px;background:var(--panel);height:100%;overflow-y:auto;transform:translateX(-100%);transition:transform .3s;z-index:20}
      .sidebar.open{transform:translateX(0)}
      .cesium-viewer-toolbar{display:flex;flex-wrap:wrap;gap:.4rem}
      #usersPanel{top:calc(44px + env(safe-area-inset-top));bottom:env(safe-area-inset-bottom);width:100%;max-width:none;padding-bottom:calc(1rem + env(safe-area-inset-bottom));transform:translateX(100%);transition:transform .3s}
      #usersPanel.active{transform:translateX(0)}
    }
  </style>
</head>
<body>
  <div id="cesiumContainer"></div>
  <div id="startOverlay">
    <div class="choice" id="watchBtn"><span>👁️</span>Regarder</div>
    <div class="choice" id="playBtn"><span>📍</span>Jouer</div>
  </div>
  <div id="chatWrapper">
  <!-- Chat -->
  <main class="chat">
    <div class="tabs">
      <div id="tabs"></div>
    </div>
    <div class="messages" id="messages"></div>
    <div class="typing-indicator hidden" id="typingIndicator"></div>
    <div class="input">
      <textarea id="input" placeholder="Écris un message..."></textarea>
      <button onclick="onSubmit()">Envoyer</button>
    </div>
  </main>
  </div>

  <button id="chatToggle" class="chat-toggle" onclick="toggleChat()" title="Chat">⬇️</button>

  <!-- Utilisateurs -->
  <aside id="usersPanel" class="sidebar users">
      <div id="users" class="list"></div>
      <select id="statusSelect" class="select" onchange="changeStatus()">
        <option value="online">🟢 En ligne</option>
        <option value="away">🔴 Absent</option>
        <option value="busy">🟠 Occupé</option>
        <option value="invisible">⚫ Invisible</option>
      </select>
  </aside>
  <div id="profilePopup" class="profile-popup"></div>
  <div id="callOverlay">
    <video id="localVideo" autoplay muted playsinline></video>
    <div id="remoteVideos"></div>
    <div id="callError" class="error"></div>
    <div id="callControls">
      <button class="btn secondary" id="micBtn" onclick="toggleMic()" title="Couper micro">🔇</button>
      <button class="btn secondary" id="videoBtn" onclick="toggleVideo()" title="Désactiver vidéo">🎥</button>
      <button class="btn secondary" onclick="toggleFullscreen()" title="Plein écran">⛶</button>
      <button class="btn secondary" onclick="recall()" title="Rappeler">↻</button>
      <button class="btn" onclick="hangup()" title="Raccrocher">📴</button>
    </div>
  </div>
  <div id="toastContainer"></div>

<script>
/* =========================
   ÉTAT APP
========================= */
// Compatibilité des navigators
navigator.getUserMedia = navigator.getUserMedia ||
  navigator.webkitGetUserMedia ||
  navigator.mozGetUserMedia ||
  navigator.msGetUserMedia;

navigator.geolocation = navigator.geolocation ||
  navigator.webkitGeolocation ||
  navigator.mozGeolocation ||
  navigator.msGeolocation;

function getUserMedia(constraints){
  if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia){
    return navigator.mediaDevices.getUserMedia(constraints);
  }
  return new Promise((resolve, reject)=>{
    if (navigator.getUserMedia){
      navigator.getUserMedia(constraints, resolve, reject);
    } else {
      reject(new Error('getUserMedia not supported'));
    }
  });
}

const storedId      = localStorage.getItem('chatUid');
const storedKey     = localStorage.getItem('currentChatKey') || 'room_general';
const storedDmPeers = JSON.parse(localStorage.getItem('dmPeers') || '{}');
let ws, name, client_id = storedId, status = 'online', clients = {};
const typingUsers = {}; let lastTypingSent = 0;
let locationWatchId = null, hasFlownToLocation = false, pendingLocationMsg = null, myZoom = 1000000;
let notifState = (typeof Notification !== 'undefined' && Notification.permission === 'granted') ? 'all' : 'none',
    locationState = 'none',
    viewState = 'new';
const mutedUsers = new Set();
const locationShared = {}; // track which users already triggered a location toast
let signal, callRoom = null, peers = {}, localStream = null, callVideo = false;
let lastCallRoom = null, lastCallVideo = false, lastCallAction = null;
let micEnabled = true, videoEnabled = true;
let wakeLock = null;
let lastCallError = '';
const shownIceErrors = new Set();

function showCallError(msg){
  document.getElementById('callError').textContent = msg || '';
  if (msg && msg !== lastCallError){
    showToast('Erreur: ' + msg);
    lastCallError = msg;
  }
  if (!msg) lastCallError = '';
}

function translateIceError(text){
  if (!text) return '';
  const cleaned = text.replace('Addess','Address');
  const map = {
    'Address not associated with the desired network interface': "Adresse non associée à l'interface réseau souhaitée"
  };
  return map[cleaned] || cleaned;
}

function mediaErrorMessage(err){
  switch(err.name){
    case 'NotAllowedError':
    case 'SecurityError':
      return "Accès à la caméra ou au micro refusé.";
    case 'NotFoundError':
      return "Aucun périphérique audio/vidéo détecté.";
    case 'NotReadableError':
      return "Impossible d'accéder aux périphériques. Ils sont peut-être utilisés par une autre application.";
    case 'OverconstrainedError':
      return "Aucun périphérique ne correspond aux contraintes demandées.";
    default:
      return "Erreur lors de l'accès à la caméra/micro (" + err.message + ").";
  }
}

  <?php
  // Use the signaling URL computed from configuration to avoid hard coded ports
  echo "const SIGNALING_URL = '" . $SIGNALING_ADDRESS . "';";
  echo "\nconst ICE_SERVERS = " . json_encode($_config['ice_servers']) . ";";
  ?>
  
const CESIUM_ION_TOKEN = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiJjNmM4NjEwYy01MjZkLTQ2YmYtYmI2ZC1kNzg4MjdhNjUxODIiLCJpZCI6NjI5OTEsImlhdCI6MTYyNzYzNDAyMn0.hAoXjLhK-PqlsdJUcZH083NqaUeg04WtA3jFkNfGi-M';
Cesium.Ion.defaultAccessToken = CESIUM_ION_TOKEN;

const viewer = new Cesium.Viewer('cesiumContainer', {
    geocoder:false, homeButton:false, baseLayerPicker:false, timeline:false, animation:false,
    sceneModePicker:false, navigationHelpButton:false, shouldAnimate:true
  });

viewer.scene.moon.show = true;
viewer.scene.sun.show = true;
viewer.scene.globe.enableLighting = true;
viewer.clock.clockStep = Cesium.ClockStep.SYSTEM_CLOCK;
viewer.clock.multiplier = 1;
viewer.clock.currentTime = Cesium.JulianDate.now();
viewer.clock.shouldAnimate = true;
  
const locationEntities = {};
const announcementEntities = {};
const API_BASE = `${location.protocol}//${location.hostname}:8002`;
const statusColors = {
  online: Cesium.Color.fromCssColorString('#22c55e'),
  busy: Cesium.Color.fromCssColorString('#f97316'),
  away: Cesium.Color.fromCssColorString('#ef4444'),
  invisible: Cesium.Color.fromCssColorString('#6b7280')
};
const statusLabels = {
  online: 'En ligne',
  busy: 'Occupé',
  away: 'Absent',
  invisible: 'Invisible',
  offline: 'Déconnecté'
};
const chatWrapper = document.getElementById('chatWrapper');
const toolbar = document.querySelector('.cesium-viewer-toolbar');
const profilePopup = document.getElementById('profilePopup');
let justOpenedProfile = false;
const usersPanel = document.getElementById('usersPanel');
const toastContainer = document.getElementById('toastContainer');
const chatToggle = document.getElementById('chatToggle');
const inputWrapper = document.querySelector('.input');
chatToggle.textContent = '⬇️';
function showToast(msg, onClick){
  const div = document.createElement('div');
  div.className = 'toast';
  div.textContent = msg;
  if (onClick){
    div.style.cursor = 'pointer';
    div.addEventListener('click', e=>{ e.stopPropagation(); onClick(); div.remove(); });
  }
  toastContainer.appendChild(div);
  setTimeout(()=>div.classList.add('hide'),4000);
  setTimeout(()=>div.remove(),4500);
}
function toggleChat(){
  chatWrapper.classList.toggle('hidden');
  const hidden = chatWrapper.classList.contains('hidden');
  chatToggle.textContent = hidden ? '💬' : '⬇️';
  if (!hidden) {
    chatToggle.classList.remove('blink');
    if (notifKey) {
      setCurrentKey(notifKey);
      renderTabs();
      renderMessages();
      clearBlink(notifKey);
      notifKey = null;
    } else {
      clearBlink(currentKey);
    }
  }
}
// place Cesium toolbar above chat overlay so buttons stay visible
toolbar.style.zIndex = 30;
const usersBtn = document.createElement('button');
usersBtn.className = 'cesium-button cesium-toolbar-button';
function updateUsersBtn(){
  const count = Object.keys(clients).length;
  usersBtn.textContent = `👥 (${count})`;
}
updateUsersBtn();
usersBtn.onclick = () => {
  if (!localStorage.getItem('chatName')) {
    const newName = prompt('Choisis un pseudo') || 'Invité';
    name = newName;
    localStorage.setItem('chatName', name);
    ws && ws.send(JSON.stringify({type:'rename', client_name:name}));
  }
  usersPanel.classList.toggle('active');
};
toolbar.appendChild(usersBtn);
const notifBtn = document.createElement('button');
notifBtn.className = 'cesium-button cesium-toolbar-button';
function updateNotifBtn(){
  if (notifState === 'all') { notifBtn.textContent = '🔔'; notifBtn.title = 'Toutes les notifications activées'; }
  else if (notifState === 'friends') { notifBtn.textContent = '🔔👥'; notifBtn.title = 'Notifications uniquement des amis'; }
  else { notifBtn.textContent = '🔕'; notifBtn.title = 'Notifications désactivées'; }
}
updateNotifBtn();
notifBtn.onclick = () => {
  notifState = notifState === 'all' ? 'friends' : notifState === 'friends' ? 'none' : 'all';
  if (notifState !== 'none' && typeof Notification !== 'undefined' && Notification.permission !== 'granted') {
    // Safari utilise encore un callback alors que d'autres navigateurs renvoient une promesse
    if (Notification.requestPermission.length === 0) {
      Notification.requestPermission().then(p => { notificationsAllowed = (p === 'granted'); });
    } else {
      Notification.requestPermission(p => { notificationsAllowed = (p === 'granted'); });
    }
  }
  updateNotifBtn();
  showToast(notifBtn.title);
};
toolbar.appendChild(notifBtn);
const locBtn = document.createElement('button');
locBtn.className = 'cesium-button cesium-toolbar-button';
function updateLocBtn(){
  if (locationState === 'all') { locBtn.textContent = '📍'; locBtn.title = 'Localisation partagée à tous'; }
  else if (locationState === 'friends') { locBtn.textContent = '📍👥'; locBtn.title = 'Localisation partagée aux amis'; }
  else { locBtn.textContent = '🚫📍'; locBtn.title = 'Localisation désactivée'; }
}
updateLocBtn();
locBtn.onclick = () => {
  locationState = locationState === 'all' ? 'friends' : locationState === 'friends' ? 'none' : 'all';
  if (locationState === 'none') { stopLocation(); }
  else { pendingLocationMsg = 'Localisation activée'; shareLocation(); }
  updateLocBtn();
};
toolbar.appendChild(locBtn);
const viewBtn = document.createElement('button');
viewBtn.className = 'cesium-button cesium-toolbar-button';
function updateViewBtn(){
  if (viewState === 'home') { viewBtn.textContent = '🏠'; viewBtn.title = 'Vue initiale'; }
  else if (viewState === 'new') { viewBtn.textContent = '🛸'; viewBtn.title = 'Voler vers les nouveaux utilisateurs localisés'; }
  else { viewBtn.textContent = '👤'; viewBtn.title = 'Voler vers ma position'; }
}
updateViewBtn();
viewBtn.onclick = () => {
  viewState = viewState === 'home' ? 'new' : viewState === 'new' ? 'me' : 'home';
  if (viewState === 'home') {
    viewer.trackedEntity = null;
    viewer.camera.flyHome(1);
    showToast('Vue initiale');
  } else if (viewState === 'me') {
    viewer.trackedEntity = null;
    if (locationEntities[client_id]) {
      const pos = locationEntities[client_id].position.getValue(Cesium.JulianDate.now());
      const carto = Cesium.Cartographic.fromCartesian(pos);
      viewer.camera.flyTo({
        destination: Cesium.Cartesian3.fromRadians(
          carto.longitude,
          carto.latitude,
          myZoom
        )
      });
    }
    showToast('Voler vers ma position');
  } else {
    showToast('Voler vers les nouveaux utilisateurs localisés');
  }
  updateViewBtn();
};
toolbar.appendChild(viewBtn);

const wakeBtn = document.createElement('button');
wakeBtn.className = 'cesium-button cesium-toolbar-button';
function updateWakeBtn(){
  if (wakeLock) { wakeBtn.textContent = '🔆'; wakeBtn.title = "Écran toujours allumé"; }
  else { wakeBtn.textContent = '😴'; wakeBtn.title = "Laisser l'écran s'éteindre"; }
}
updateWakeBtn();

async function requestWakeLock(){
  try {
    wakeLock = await navigator.wakeLock.request('screen');
    wakeLock.addEventListener('release', () => { wakeLock = null; updateWakeBtn(); });
    updateWakeBtn();
    showToast("Écran toujours allumé activé");
  } catch(err){
    console.error('wakeLock', err);
    showToast('Wake Lock non disponible');
  }
}
function releaseWakeLock(){
  if (wakeLock){
    wakeLock.release();
    wakeLock = null;
  }
  updateWakeBtn();
  showToast("Écran toujours allumé désactivé");
}
wakeBtn.onclick = () => {
  if (!('wakeLock' in navigator)) { showToast('Wake Lock non supporté'); return; }
  if (wakeLock) releaseWakeLock();
  else requestWakeLock();
};
document.addEventListener('visibilitychange', () => {
  if (wakeLock && document.visibilityState === 'visible') requestWakeLock();
});
toolbar.appendChild(wakeBtn);

const startOverlay = document.getElementById('startOverlay');
document.getElementById('watchBtn').onclick = () => {
  showToast('Vous allez être posé sur Null Island (0 latitude, 0 longitude)');
  startOverlay.style.display = 'none';
  myZoom = 1000000;
  viewer.trackedEntity = null;
  viewer.camera.flyTo({destination: Cesium.Cartesian3.fromDegrees(0,0,30000000)});
};
document.getElementById('playBtn').onclick = () => {
  pendingLocationMsg = 'Vous allez être posé sur la Terre';
  startOverlay.style.display = 'none';
  myZoom = 5000;
  locationState = 'all';
  shareLocation();
};
document.addEventListener('click', (e) => {
  if (justOpenedProfile) { justOpenedProfile = false; return; }
  const path = e.composedPath();
  const insideChat = path.includes(chatWrapper);
  const onToggle = path.includes(chatToggle);
  if (!chatWrapper.classList.contains('hidden') && !insideChat && !onToggle) {
    chatWrapper.classList.add('hidden');
    chatToggle.textContent = '💬';
  }
  const insideUsers = path.includes(usersPanel);
  const onUsersBtn = path.includes(usersBtn);
  if (usersPanel.classList.contains('active') && !insideUsers && !onUsersBtn) {
    usersPanel.classList.remove('active');
  }
  if (profilePopup.classList.contains('active') && !path.includes(profilePopup)) {
    hideProfilePopup();
  }
});

const handler = new Cesium.ScreenSpaceEventHandler(viewer.scene.canvas);
handler.setInputAction(function(click){
  const picked = viewer.scene.pick(click.position);
  if (Cesium.defined(picked) && picked.id && picked.id.properties && picked.id.properties.client_id) {
    const id = picked.id.properties.client_id.getValue();
    const uname = picked.id.properties.name.getValue();
    followGps(id);
    showProfilePopup(id, uname, click.position);
  } else {
    hideProfilePopup();
  }
}, Cesium.ScreenSpaceEventType.LEFT_CLICK);

function showProfilePopup(id, uname, position){
  justOpenedProfile = true;
  profilePopup.innerHTML = '';
  const title = document.createElement('div');
  title.className = 'title';
  title.textContent = uname;
  profilePopup.appendChild(title);
  const addBtn = (label, handler) => {
    const b = document.createElement('button');
    b.textContent = label;
    b.onclick = (ev) => { ev.stopPropagation(); handler(); hideProfilePopup(); };
    profilePopup.appendChild(b);
  };
  addBtn('Chat', () => { openDM(id, uname); });
  addBtn('Suivre le pts GPS', () => { followGps(id); });
  addBtn(mutedUsers.has(id) ? 'Activer les notifications' : 'Désactiver les notifications', () => { toggleUserNotif(id); });
  addBtn('Wizz', () => { wizz(id); });
  addBtn('Appel WebRTC', () => { startCall(id, false); });
  addBtn('Visio WebRTC', () => { startCall(id, true); });
  addBtn('Rejoindre un groupe', () => { joinGroup(id); });
  profilePopup.style.left = position.x + 'px';
  profilePopup.style.top = position.y + 'px';
  profilePopup.classList.add('active');
}

function hideProfilePopup(){
  profilePopup.classList.remove('active');
  justOpenedProfile = false;
}

function followGps(id){
  if (locationEntities[id]) {
    viewer.trackedEntity = locationEntities[id];
    viewer.selectedEntity = locationEntities[id];
    const title = document.querySelector('.cesium-infoBox-title');
    if (title) title.textContent = locationEntities[id].name || '';
  }
}

function focusUser(id, uname){
  showToast('Centrage sur ' + uname);
  const ent = locationEntities[id];
  usersPanel.classList.remove('active');
  if (ent){
    const pos = ent.position.getValue(Cesium.JulianDate.now());
    const carto = Cesium.Cartographic.fromCartesian(pos);
    viewer.trackedEntity = null;
    viewer.camera.flyTo({
      destination: Cesium.Cartesian3.fromRadians(carto.longitude, carto.latitude, 5000),
      complete: () => {
        const win = Cesium.SceneTransforms.wgs84ToWindowCoordinates(viewer.scene, pos);
        if (win) showProfilePopup(id, uname, win);
      }
    });
  } else {
    showProfilePopup(id, uname, {x:window.innerWidth/2, y:window.innerHeight/2});
  }
}

function toggleUserNotif(id){
  if (mutedUsers.has(id)) mutedUsers.delete(id);
  else mutedUsers.add(id);
}

function wizz(id){
  if (ws) ws.send(JSON.stringify({type:'wizz', to:id}));
}

function startCall(id, video){
  if (!client_id) return;
  lastCallAction = {type:'user', id, video};
  const room = client_id < id ? `call_${client_id}_${id}` : `call_${id}_${client_id}`;
  ws.send(JSON.stringify({type:'call_invite', to:id, room, video}));
  joinCall(room, video);
}

function joinGroup(id){
  lastCallAction = {type:'group', id};
  const room = `group_${id}`;
  joinCall(room, true);
}

function joinCall(room, video){
  if (callRoom) hangup();
  callRoom = room; callVideo = video;
  lastCallRoom = room; lastCallVideo = video;
  micEnabled = true; videoEnabled = video;
  const micBtn = document.getElementById('micBtn');
  micBtn.textContent = '🔇';
  micBtn.title = 'Couper micro';
  const videoBtn = document.getElementById('videoBtn');
  videoBtn.textContent = video ? '🎥' : '📷';
  videoBtn.title = video ? 'Désactiver vidéo' : 'Activer vidéo';
  document.getElementById('callOverlay').classList.add('active');
  showCallError('');
  shownIceErrors.clear();
  getUserMedia({audio:true, video:video}).then(stream => {
    localStream = stream;
    document.getElementById('localVideo').srcObject = stream;
    connectSignal(room);
  }).catch(err => {
    console.error('media', err);
    showCallError(mediaErrorMessage(err));
    hangup();
  });
}

function connectSignal(room){
  signal = new WebSocket(SIGNALING_URL);
  signal.onopen = () => {
    signal.send(JSON.stringify({cmd:'register', roomid:room}));
    // include room in join to ensure peers only react to participants in the same call
    signalSend({type:'join', room:room, from:client_id});
  };
  signal.onmessage = e => {
    const data = JSON.parse(e.data);
    handleSignal(data.msg);
  };
  signal.onerror = () => showCallError('Erreur de connexion au serveur de signalisation.');
  signal.onclose = () => {
    if (callRoom) {
      showCallError('Connexion au serveur de signalisation perdue.');
      hangup();
    }
  };
}

function signalSend(msg){
  if (signal && signal.readyState === 1) {
    signal.send(JSON.stringify({cmd:'send', roomid:callRoom, msg:msg}));
  }
}

async function handleSignal(msg){
  switch(msg.type){
    case 'join':
      // ignore join messages from ourselves or from other rooms
      if (msg.from === client_id || msg.room !== callRoom) return;
      // L'offre initiale sera générée par le gestionnaire `onnegotiationneeded`
      // déclenché lors de l'ajout des pistes locales dans `createPeer`.
      createPeer(msg.from);
      break;
    case 'offer':
      if (msg.to !== client_id) return;
      const pc2 = createPeer(msg.from);
      const offerCollision = pc2._makingOffer || pc2.signalingState !== 'stable';
      pc2._ignoreOffer = !pc2._isPolite && offerCollision;
      if (pc2._ignoreOffer) return;
      try {
        await pc2.setRemoteDescription(new RTCSessionDescription(msg.sdp));
        await pc2.setLocalDescription(await pc2.createAnswer());
        signalSend({type:'answer', from:client_id, to:msg.from, sdp:pc2.localDescription});
      } catch(err){
        console.error('offer', err);
      }
      break;
    case 'answer':
      if (msg.to !== client_id) return;
      const pc3 = peers[msg.from];
      if (!pc3) return;
      try {
        await pc3.setRemoteDescription(new RTCSessionDescription(msg.sdp));
      } catch(err){
        console.error('answer', err);
      }
      break;
    case 'candidate':
      if (msg.to !== client_id) return;
      const pc4 = peers[msg.from];
      if (!pc4 || pc4._ignoreOffer) return;
      try {
        await pc4.addIceCandidate(new RTCIceCandidate(msg.candidate));
      } catch(err){
        console.error('candidate', err);
        showCallError("Candidat ICE invalide ou bloqué par le réseau.");
      }
      break;
    case 'leave':
      if (msg.from === client_id) return;
      peers[msg.from]?.close();
      delete peers[msg.from];
      document.getElementById('remote_'+msg.from)?.remove();
      if (!document.getElementById('remoteVideos').hasChildNodes()) {
        document.getElementById('callOverlay').classList.remove('with-remote');
      }
      break;
  }
}

function createPeer(id){
  if (peers[id]) return peers[id];
  const pc = new RTCPeerConnection({iceServers: ICE_SERVERS});

  // Propriétés pour la négociation parfaite
  const selfIdNum = parseInt(client_id, 16);
  const peerIdNum = parseInt(id, 16);
  pc._isPolite = selfIdNum < peerIdNum;
  pc._makingOffer = false;
  pc._ignoreOffer = false;

  peers[id] = pc;
  if (localStream) localStream.getTracks().forEach(t=>pc.addTrack(t, localStream));
  pc.onicecandidate = e => { if (e.candidate) signalSend({type:'candidate', from:client_id, to:id, candidate:e.candidate}); };
  pc.onicecandidateerror = e => {
    const txt = translateIceError(e.errorText);
    const key = `${e.errorCode}|${txt}`;
    if (shownIceErrors.has(key)) return;
    shownIceErrors.add(key);
    console.error('icecandidateerror', e);
    showCallError('Erreur ICE (' + e.errorCode + '): ' + txt + '. Vérifiez votre configuration réseau/NAT.');
  };
  // Ajoute directement le premier flux reçu (audio+vidéo) au lecteur distant
  pc.ontrack = e => addRemoteStream(id, e.streams[0]);
  pc.onnegotiationneeded = async () => {
    try {
      pc._makingOffer = true;
      await pc.setLocalDescription(await pc.createOffer());
      signalSend({type:'offer', from:client_id, to:id, sdp:pc.localDescription});
    } catch(err){
      console.error('negotiation', err);
    } finally {
      pc._makingOffer = false;
    }
  };
  pc.onconnectionstatechange = () => {
    if (pc.connectionState === 'failed'){
      showCallError('Connexion entre pairs échouée. Vérifiez votre connexion réseau ou la configuration NAT/pare-feu.');
    }
    if (['disconnected','failed','closed'].includes(pc.connectionState)){
      document.getElementById('remote_'+id)?.remove();
      if (!document.getElementById('remoteVideos').hasChildNodes()) {
        document.getElementById('callOverlay').classList.remove('with-remote');
      }
      delete peers[id];
    }
  };
  return pc;
}

function addRemoteStream(id, stream){
  let v = document.getElementById('remote_'+id);
  if (!v) {
    v = document.createElement('video');
    v.id = 'remote_'+id;
    v.autoplay = true; v.playsInline = true;
    document.getElementById('remoteVideos').appendChild(v);
  }
  if (v.srcObject !== stream) {
    v.srcObject = stream;
  }
  // Tente de démarrer la lecture pour éviter l'écran noir
  const playPromise = v.play();
  if (playPromise !== undefined) {
    playPromise.catch(()=>{});
  }
  document.getElementById('callOverlay').classList.add('with-remote');
}

function toggleMic(){
  if (!localStream) return;
  const track = localStream.getAudioTracks()[0];
  if (!track) return;
  micEnabled = !track.enabled;
  track.enabled = micEnabled;
  const btn = document.getElementById('micBtn');
  if (micEnabled){
    btn.textContent = '🔇';
    btn.title = 'Couper micro';
  } else {
    btn.textContent = '🎤';
    btn.title = 'Activer micro';
  }
}

function toggleVideo(){
  if (!localStream) return;
  let track = localStream.getVideoTracks()[0];
  if (track){
    videoEnabled = !track.enabled;
    track.enabled = videoEnabled;
    const btn = document.getElementById('videoBtn');
    if (videoEnabled){
      btn.textContent = '🎥';
      btn.title = 'Désactiver vidéo';
    } else {
      btn.textContent = '📷';
      btn.title = 'Activer vidéo';
    }
  } else {
    getUserMedia({video:true}).then(stream=>{
      track = stream.getVideoTracks()[0];
      localStream.addTrack(track);
      Object.values(peers).forEach(pc=>pc.addTrack(track, localStream));
      videoEnabled = true;
      const btn = document.getElementById('videoBtn');
      btn.textContent = '🎥';
      btn.title = 'Désactiver vidéo';
    }).catch(err=>{
      console.error('video', err);
      showCallError(mediaErrorMessage(err));
    });
  }
}

function toggleFullscreen(){
  const overlay = document.getElementById('callOverlay');
  if (!document.fullscreenElement){
    (overlay.requestFullscreen || overlay.webkitRequestFullscreen || overlay.mozRequestFullScreen || overlay.msRequestFullscreen)?.call(overlay);
  } else {
    (document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen)?.call(document);
  }
}

function recall(){
  if (lastCallAction){
    if (lastCallAction.type === 'user') startCall(lastCallAction.id, lastCallAction.video);
    else if (lastCallAction.type === 'group') joinGroup(lastCallAction.id);
  } else if (lastCallRoom) {
    joinCall(lastCallRoom, lastCallVideo);
  }
}

function hangup(){
  signalSend({type:'leave', from:client_id});
  Object.values(peers).forEach(pc=>pc.close());
  peers = {};
  if (localStream) {
    localStream.getTracks().forEach(t=>t.stop());
    document.getElementById('localVideo').srcObject = null;
    localStream = null;
  }
  if (signal) { signal.close(); signal = null; }
  if (document.fullscreenElement){
    (document.exitFullscreen || document.webkitExitFullscreen || document.mozCancelFullScreen || document.msExitFullscreen)?.call(document);
  }
  callRoom = null;
  document.getElementById('remoteVideos').innerHTML = '';
  const overlay = document.getElementById('callOverlay');
  overlay.classList.remove('active','with-remote');
  showCallError('');
  shownIceErrors.clear();
}

function isFriend(id){
  // TODO: déterminer si l'utilisateur est un ami
  return false;
}

function shouldNotify(id){
  if (mutedUsers.has(id)) return false;
  if (notifState === 'none') return false;
  if (notifState === 'friends') return isFriend(id);
  return true;
}

function setCurrentKey(k){
  currentKey = k;
  localStorage.setItem('currentChatKey', k);
}

// conversations: "room_<roomId>" ou "dm_<clientId>"
let currentKey = storedKey;
let messages   = { room_general: [] };
let tabs       = { room_general: 'Salle générale' };
let dmPeers    = storedDmPeers;
Object.entries(dmPeers).forEach(([id, username]) => {
  const key = 'dm_' + id;
  tabs[key] = 'DM avec ' + username;
  messages[key] = [];
});
let lastRenderedKey = null;
let notifKey = null;
let notificationsAllowed = (typeof Notification !== 'undefined' && Notification.permission === 'granted');
// Sur certains navigateurs (ex. Safari), la demande de permission de notification
// doit être déclenchée par un geste utilisateur. On évite donc de la lancer
// automatiquement ici ; l'utilisateur pourra l'activer via le bouton dédié.

// liste d’utilisateurs de la room active : { id: {name, status} }

// liste des rooms visibles côté UI
// value = {visibility: 'public'|'private', creator_id: number|null}
let rooms = new Map([['general', {visibility:'public', creator_id:null}]]);

// rejoindre via lien ?room=xxx
const q = new URLSearchParams(location.search);
if (q.get('room')) rooms.set(q.get('room'), {visibility:'private', creator_id:null});

function addOrUpdateLocation(loc){
  const id = loc.client_id;
  let ent = locationEntities[id];
  const st = (clients[id] && clients[id].status) || 'online';
  const col = statusColors[st] || Cesium.Color.CYAN;
  const wasReal = !!(ent && ent.properties && ent.properties.real && ent.properties.real.getValue(Cesium.JulianDate.now()));
  const isReal = !!loc.real;
  if (!clients[id]) clients[id] = {name: loc.client_name || 'Invité', status: 'online'};
  clients[id].located = isReal;
  if (!ent){
    ent = viewer.entities.add({
      name: loc.client_name,
      position: Cesium.Cartesian3.fromDegrees(loc.lon, loc.lat),
      point: {pixelSize:10, color: col},
      label: {text: loc.client_name, font:'14px sans-serif', verticalOrigin: Cesium.VerticalOrigin.BOTTOM},
      properties: {client_id: id, name: loc.client_name, real: isReal}
    });
    locationEntities[id] = ent;
  } else {
    ent.position = Cesium.Cartesian3.fromDegrees(loc.lon, loc.lat);
    ent.label.text = loc.client_name;
    ent.properties.name = loc.client_name;
    ent.point.color = col;
    ent.properties.real = isReal;
    ent.name = loc.client_name;
  }
  if (viewState === 'new' && isReal && id !== client_id && (!wasReal)) {
    viewer.camera.flyTo({destination: Cesium.Cartesian3.fromDegrees(loc.lon, loc.lat, 1000000)});
  }
}

function removeLocation(id){
  if (locationEntities[id]) {
    viewer.entities.remove(locationEntities[id]);
    delete locationEntities[id];
  }
  if (clients[id]) clients[id].located = false;
}

function shareLocation(){
  if (!navigator.geolocation) return;
  if (locationWatchId !== null) {
    navigator.geolocation.clearWatch(locationWatchId);
    locationWatchId = null;
  }
  hasFlownToLocation = false;
  if ('wakeLock' in navigator && !wakeLock) {
    requestWakeLock();
  }

  const options = {
    enableHighAccuracy: true,
    maximumAge: 60000,
    timeout: 15000
  };

  const sendPosition = pos => {
    const {latitude, longitude} = pos.coords;
    if (!hasFlownToLocation) {
      viewer.camera.flyTo({destination: Cesium.Cartesian3.fromDegrees(longitude, latitude, myZoom)});
      hasFlownToLocation = true;
    }
    if (pendingLocationMsg && (latitude !== 0 || longitude !== 0)) {
      showToast(pendingLocationMsg);
      pendingLocationMsg = null;
    }
    ws.send(JSON.stringify({type:'location', lat: latitude, lon: longitude}));
  };

  const errorHandler = err => {
    console.warn('Erreur de localisation :', err.message);
    pendingLocationMsg = null;
    locationState = 'none';
    updateLocBtn();
    showToast('Erreur localisation: ' + err.message);
  };

  // iOS Safari requires getCurrentPosition to be called directly from a user gesture
  navigator.geolocation.getCurrentPosition(pos => {
    sendPosition(pos);
    locationWatchId = navigator.geolocation.watchPosition(sendPosition, errorHandler, options);
    updateLocBtn();
  }, errorHandler, options);
}

function stopLocation(){
  if (locationWatchId !== null) {
    navigator.geolocation.clearWatch(locationWatchId);
    locationWatchId = null;
  }
  if (wakeLock) {
    releaseWakeLock();
  }
  if (ws) ws.send(JSON.stringify({type:'location_remove'}));
  removeLocation(client_id);
  showToast('Localisation désactivée');
}

/* =========================
   SOCKET
========================= */

//  ws = new WebSocket('wss://' + document.domain + ':7272');
function connect(){

  <?php
  if ($_config['docker']) {
    echo "ws = new WebSocket(
      (location.protocol === 'https:' ? 'wss://' : 'ws://') +
      location.host +
      '/ws/'
    );";
  } else {
      // WebServer
      if ($_config['ssl']) 
        echo "ws = new WebSocket('wss://' + document.domain + ':7272');";
      else
        echo "ws = new WebSocket('ws://' + document.domain + ':7272');";
  }
  ?>


  ws.onopen = () => {
    showToast('Connecté au chat');
    if (!name) name = localStorage.getItem('chatName') || 'Invité';
    // login première room : soit "general", soit celle de l'URL si présente
    loginRoom([...rooms.keys()][0]);
  };

  ws.onmessage = onmessage;
  ws.onerror   = () => showToast('Erreur de connexion');
  ws.onclose   = () => { showToast('Déconnecté du chat'); setTimeout(connect, 1500); };
}

function loginRoom(roomId){
  // reset UI utilisateurs (évite l'affichage d'une ancienne liste avant le "welcome")
  clients = {};
  renderUsers();
  ws.send(JSON.stringify({type:'login', client_name:name, room_id:roomId, status, ua: navigator.userAgent, client_uuid: client_id}));
}

function changeStatus(){
  status = document.getElementById('statusSelect').value;
  if (client_id && clients[client_id]) {
    clients[client_id].status = status;
    renderUsers();
    if (locationEntities[client_id]) {
      locationEntities[client_id].point.color = statusColors[status] || Cesium.Color.CYAN;
    }
  }
  ws.send(JSON.stringify({type:'status', status}));
}

function chooseName(){
  if (name === 'Invité') {
    const newName = prompt('Votre pseudo ?') || 'Invité';
    name = newName;
    localStorage.setItem('chatName', name);
    updateUsersBtn();
    ws && ws.send(JSON.stringify({type:'rename', client_name:name}));
  }
}

/* =========================
   RÉCEPTION EVENTS
========================= */
function onmessage(e){
  const data = JSON.parse(e.data);

  switch (data.type){
    case 'ping':
      ws.send(JSON.stringify({type:'pong'}));
      break;

    case 'welcome': {
      // Fixe mon id et remplace la liste utilisateurs par celle de la room
      client_id = data.self_id;
      localStorage.setItem('chatUid', client_id);
      clients = data.client_list || {};
      renderUsers();

      // S’assure que l’onglet de la room existe et devient actif
      ensureRoomTab(data.room_id);
      renderRooms();

      // restaure les conversations privées de la session précédente
      Object.entries(dmPeers).forEach(([id, username]) => {
        const key = 'dm_' + id;
        if (!tabs[key]) tabs[key] = 'DM avec ' + username;
        if (!messages[key]) messages[key] = [];
      });
      setCurrentKey(storedKey);
      renderTabs();
      renderMessages();
      if (currentKey.startsWith('dm_') && messages[currentKey].length === 0) {
        const id = currentKey.slice(3);
        ws.send(JSON.stringify({type: 'dm_history', to_client_id: id}));
      }
      break;
    }
    case 'history': {
      (data.messages || []).forEach(m => storeMessage(m));
      renderMessages();
      break;
    }

    case 'login': {
      // Quand quelqu’un arrive, on reçoit une liste à jour pour la room
      if (data.client_list) {
        clients = data.client_list;
        renderUsers();
        renderTabs();
        renderMessages();
      } else if (data.client_id && data.client_name) {
        // Sécurité : merge si pas de client_list
        clients[data.client_id] = {name: data.client_name, status: data.status || 'online'};
        renderUsers();
        renderTabs();
        renderMessages();
        if (data.client_id !== client_id) showToast(`${data.client_name} s'est connecté`);
      }
      break;
    }

    case 'status': {
      // MAJ statut en temps réel
      if (!clients[data.client_id]) clients[data.client_id] = {name:'Utilisateur', status:data.status};
      clients[data.client_id].status = data.status;
      renderUsers();
      renderTabs();
      renderMessages();
      if (locationEntities[data.client_id]) {
        locationEntities[data.client_id].point.color = statusColors[data.status] || Cesium.Color.CYAN;
      }
      break;
    }

      case 'typing': {
        if (!clients[data.from_client_id]) clients[data.from_client_id] = {name: data.from_client_name || 'Utilisateur', status: 'online'};
        const key = data.dm ? 'dm_' + data.from_client_id : 'room_' + data.room_id;
        if (!typingUsers[key]) typingUsers[key] = {};
        const id = data.from_client_id;
        if (typingUsers[key][id]) clearTimeout(typingUsers[key][id]);
        typingUsers[key][id] = setTimeout(() => {
          delete typingUsers[key][id];
          renderTyping();
        }, 3000);
        renderTyping();
        break;
      }
    case 'rename': {
      if (!clients[data.client_id]) {
        clients[data.client_id] = {name: data.client_name || 'Invité', status: 'online'};
      } else {
        clients[data.client_id].name = data.client_name || 'Invité';
      }
      if (client_id && data.client_id == client_id) {
        name = data.client_name;
        localStorage.setItem('chatName', name);
        updateUsersBtn();
        if (locationEntities[client_id]) {
          locationEntities[client_id].label.text = name;
          locationEntities[client_id].properties.name = name;
        }
      }
      renderUsers();
      break;
    }

    case 'locations': {
      (data.locations || []).forEach(l => addOrUpdateLocation(l));
      renderUsers();
      break;
    }

    case 'location': {
      addOrUpdateLocation(data);
      renderUsers();
      if (data.client_id !== client_id) {
        const uname = clients[data.client_id]?.name || data.client_name || 'Utilisateur';
        if (!locationShared[data.client_id]) {
          showToast(`${uname} a partagé sa localisation`);
          locationShared[data.client_id] = true;
        }
      }
      break;
    }

    case 'location_remove': {
      removeLocation(data.client_id);
      renderUsers();
      if (data.client_id !== client_id) {
        const uname = clients[data.client_id]?.name || 'Utilisateur';
        if (locationShared[data.client_id]) {
          showToast(`${uname} a retiré sa localisation`);
          locationShared[data.client_id] = false;
        }
      }
      break;
    }

    case 'new_room': { // room publique => visible pour tous
      const {room_id, visibility, creator_id} = data;
      if (!rooms.has(room_id)) {
        rooms.set(room_id, {visibility, creator_id});
        renderRooms();
      }
      break;
    }

    case 'room_created': { // ack créateur pour room privée
      const {room_id, visibility, creator_id} = data;
      rooms.set(room_id, {visibility, creator_id});
      renderRooms();

      // créer onglet, basculer, join
      setCurrentKey('room_' + room_id);
      tabs[currentKey] = 'Salle ' + room_id;
      if (!messages[currentKey]) messages[currentKey] = [];
      renderTabs(); renderMessages();
      loginRoom(room_id);

      // propose le partage du lien
      copyRoomLink(room_id, true);
      break;
    }

    case 'room_closed': {
      const roomId = data.room_id;
      if (rooms.has(roomId)) {
        rooms.delete(roomId);
        const key = 'room_' + roomId;
        delete tabs[key];
        delete messages[key];
        if (currentKey === key) {
          setCurrentKey('room_general');
          loginRoom('general');
        }
        renderRooms(); renderTabs(); renderMessages();
      }
      break;
    }

    case 'say': {
      // Stocke ET rafraîchit si la conversation visible est concernée
      const key = storeMessage(data);
      if (currentKey === key) {
        renderMessages();
      } else {
        blinkTab(key);
      }
      if (chatWrapper.classList.contains('hidden') || currentKey !== key) {
        chatToggle.classList.add('blink');
        notifKey = key;
      }
      if (String(data.from_client_id) !== String(client_id)) {
        const uname = data.from_client_name || clients[data.from_client_id]?.name || 'Utilisateur';
        showToast(`${uname}: ${data.content}`, ()=>{
          setCurrentKey(key);
          renderTabs();
          renderMessages();
          clearBlink(key);
          chatWrapper.classList.remove('hidden');
          chatToggle.textContent = '⬇️';
          chatToggle.classList.remove('blink');
          notifKey = null;
        });
        if (data.dm && notificationsAllowed && shouldNotify(data.from_client_id)) {
          new Notification('Message privé de ' + (data.from_client_name || 'Utilisateur'), {
            body: data.content || ''
          }).onclick = ()=>{
            setCurrentKey(key);
            renderTabs();
            renderMessages();
            clearBlink(key);
            chatWrapper.classList.remove('hidden');
            chatToggle.textContent = '⬇️';
            chatToggle.classList.remove('blink');
            notifKey = null;
            window.focus();
          };
        }
      }
      break;
    }

    case 'logout': {
      const uname = clients[data.from_client_id]?.name || 'Utilisateur';
      delete clients[data.from_client_id];
      renderUsers();
      renderTabs();
      renderMessages();
      delete locationShared[data.from_client_id];
      showToast(`${uname} s'est déconnecté`);
      break;
    }
    case 'wizz': {
      alert('Wizz de ' + (clients[data.from]?.name || 'Utilisateur') + '!');
      break;
    }
    case 'call_invite': {
      lastCallAction = {type:'user', id:data.from, video:data.video};
      if (confirm('Rejoindre l\'appel ' + (data.video ? 'video' : 'audio') + ' de ' + (clients[data.from]?.name || 'Utilisateur') + ' ?')) {
        joinCall(data.room, data.video);
      }
      break;
    }
  }
}

/* =========================
   ROOMS / TABS / DM
========================= */
function ensureRoomTab(roomId){
  const key = 'room_' + roomId;
  if (!tabs[key])     tabs[key]     = (rooms.get(roomId)?.visibility === 'private' ? '🔒 ' : '') + 'Salle ' + roomId;
  if (!messages[key]) messages[key] = [];
  if (!rooms.has(roomId)) rooms.set(roomId, {visibility:'public', creator_id:null});
  setCurrentKey(key);
  renderTabs(); renderMessages();
}

function renderRooms(){
  if (currentKey === 'rooms') renderMessages();
}

function renderTabs(){
  const t = document.getElementById('tabs'); t.innerHTML = '';
  // Onglet Salles
  const roomsTab = document.createElement('div');
  roomsTab.className = 'tab' + (currentKey === 'rooms' ? ' active' : '');
  roomsTab.textContent = 'Salles';
  roomsTab.onclick = () => { setCurrentKey('rooms'); renderTabs(); renderMessages(); chatToggle.classList.remove('blink'); if (notifKey === 'rooms') notifKey = null; };
  roomsTab.id = 'tab_rooms';
  t.appendChild(roomsTab);

  Object.keys(tabs).forEach(k => {
    const div   = document.createElement('div');
    div.className = 'tab' + (k === currentKey ? ' active' : '');
    if (k.startsWith('dm_')) {
      const partnerId = k.substring(3);
      const st = clients[partnerId]?.status || 'offline';
      const dot = document.createElement('span');
      dot.className = 'dot ' + (st === 'online' ? 'ok' : st);
      dot.title = statusLabels[st] || st;
      div.appendChild(dot);
    }
    const label = document.createElement('span');
    label.textContent = tabs[k];
    div.appendChild(label);

    if (k.startsWith('room_')) {
      const roomId = k.substring(5);
      const meta   = rooms.get(roomId);
      if (meta) {
        const actions = document.createElement('span');
        actions.className = 'actions';

        // lien partage (private uniquement)
        if (meta.visibility === 'private') {
          const share = document.createElement('button');
          share.className = 'icon-btn';
          share.title     = 'Partager le lien';
          share.textContent = '🔗';
          share.onclick = (e) => { e.stopPropagation(); copyRoomLink(roomId); };
          actions.appendChild(share);
        }
        // close si créateur
        if (meta.creator_id && client_id && meta.creator_id == client_id) {
          const close = document.createElement('button');
          close.className = 'icon-btn';
          close.title     = 'Fermer cette salle';
          close.textContent = '×';
          close.onclick = (e) => { e.stopPropagation(); closeRoom(roomId); };
          actions.appendChild(close);
        }
        if (actions.childNodes.length) div.appendChild(actions);
      }
    } else if (k.startsWith('dm_')) {
      const partnerId = k.substring(3);
      const actions = document.createElement('span');
      actions.className = 'actions';
      const close = document.createElement('button');
      close.className = 'icon-btn';
      close.title     = 'Fermer cette conversation';
      close.textContent = '×';
      close.onclick = (e) => { e.stopPropagation(); closeDM(partnerId); };
      actions.appendChild(close);
      div.appendChild(actions);
    }

    div.onclick = () => { setCurrentKey(k); renderTabs(); renderMessages(); clearBlink(k); chatToggle.classList.remove('blink'); if (notifKey === k) notifKey = null; };
    div.id = 'tab_' + k;
    t.appendChild(div);
  });
}

function createRoom(){
  const name      = document.getElementById('newRoomName').value.trim();
  const isPrivate = document.getElementById('isPrivate').checked;
  const roomId    = name || (isPrivate ? ('private_' + Math.random().toString(36).slice(2,8))
                                       : ('room_' + Math.random().toString(36).slice(2,6)));

  // UI immédiate (feedback)
  rooms.set(roomId, {visibility: isPrivate ? 'private' : 'public', creator_id: client_id});
  renderRooms();

  // Serveur
  ws.send(JSON.stringify({
    type: 'create_room',
    room_id: roomId,
    visibility: isPrivate ? 'private' : 'public'
  }));

  // Publique : on bascule tout de suite
  if (!isPrivate) {
    setCurrentKey('room_' + roomId);
    tabs[currentKey] = 'Salle ' + roomId;
    if (!messages[currentKey]) messages[currentKey] = [];
    renderTabs(); renderMessages();
    loginRoom(roomId);
  }

  document.getElementById('newRoomName').value = '';
  document.getElementById('isPrivate').checked = false;
}

function closeRoom(roomId){
  const meta = rooms.get(roomId);
  if (!meta || !client_id || meta.creator_id != client_id) return;
  ws.send(JSON.stringify({type:'close_room', room_id:roomId, creator_id:client_id}));

  // remove room locally so the tab closes immediately
  const key = 'room_' + roomId;
  delete tabs[key];
  delete messages[key];
  rooms.delete(roomId);
  if (currentKey === key) {
    setCurrentKey('room_general');
    renderMessages();
  }
  renderTabs();
}

function closeDM(id){
  const key = 'dm_' + id;
  delete tabs[key];
  delete messages[key];
  delete dmPeers[id];
  localStorage.setItem('dmPeers', JSON.stringify(dmPeers));
  if (currentKey === key) {
    setCurrentKey('room_general');
    renderMessages();
  }
  renderTabs();
}

function copyRoomLink(roomId, notify = false){
  const link = location.origin + location.pathname + '?room=' + encodeURIComponent(roomId);
  if (navigator.clipboard?.writeText) {
    navigator.clipboard.writeText(link).then(()=>{
      if (notify) alert('Lien copié !\n' + link);
    }).catch(()=> alert(link));
  } else {
    prompt('Copiez ce lien :', link);
  }
}

/* =========================
   UTILISATEURS / STATUTS
========================= */
function renderUsers(){
  const u = document.getElementById('users');
  u.innerHTML = '';
  // assure un affichage correct même si welcome/login arrive avant qu'on ait status local
  if (client_id && clients[client_id] && clients[client_id].status !== status) {
    clients[client_id].status = status;
  }
  Object.keys(clients).forEach(id => {
    const info = clients[id] || {};
    const div  = document.createElement('div');
    div.className = 'item';

    const dot = document.createElement('span');
    let cls = 'ok';
    switch(info.status){
      case 'busy': cls = 'busy'; break;
      case 'away': cls = 'away'; break;
      case 'invisible': cls = 'invisible'; break;
    }
    dot.className = 'dot ' + cls;
    div.appendChild(dot);

    const label = document.createElement('span');
    label.textContent = (info.name || 'Invité') + (id == client_id ? ' (moi)' : '');
    if (info.located) label.textContent += ' 📍';
    div.appendChild(label);

    if (id == client_id) div.onclick = chooseName;
    else div.onclick = () => focusUser(id, info.name || 'Invité');

    u.appendChild(div);
  });
  updateUsersBtn();
}

/* =========================
   MESSAGES
========================= */
function storeMessage(m){
  const key = getMessageKey(m);
  if (!messages[key]) messages[key] = [];
  messages[key].push(m);

  if (m.dm) {
    const partner = (String(m.from_client_id) === String(client_id)) ? m.to_client_id : m.from_client_id;
    const partnerName = (String(m.from_client_id) === String(client_id))
      ? (clients[m.to_client_id]?.name || dmPeers[partner] || 'Utilisateur')
      : (m.from_client_name || 'Utilisateur');
    dmPeers[partner] = partnerName;
    localStorage.setItem('dmPeers', JSON.stringify(dmPeers));
  }

  // créer onglet si besoin
  if (!tabs[key]) {
    tabs[key] = m.dm ? ('DM avec ' + m.from_client_name) : ('Salle ' + (m.room_id || 'general'));
    renderTabs();
  }
  return key;
}

function getMessageKey(m){
  if (m.dm) {
    // clé = partenaire (pas moi)
    return (String(m.from_client_id) === String(client_id)) ? ('dm_' + m.to_client_id)
                                                            : ('dm_' + m.from_client_id);
  }
  return 'room_' + (m.room_id || 'general');
}

function renderMessages(){
  const box = document.getElementById('messages');
  const atBottom = box.scrollTop + box.clientHeight >= box.scrollHeight - 5;
  box.innerHTML = '';
  if (currentKey === 'rooms') {
    inputWrapper.style.display = 'none';
    for (const [id, meta] of rooms.entries()){
      const key = 'room_' + id;
      const div = document.createElement('div');
      div.className = 'item' + (currentKey === key ? ' active' : '');
      div.textContent = (meta.visibility === 'private' ? '🔒 ' : '') + 'Salle ' + id;
      div.onclick = () => {
        setCurrentKey(key);
        ensureRoomTab(id);
        renderTabs(); renderMessages();
        loginRoom(id);
      };
      box.appendChild(div);
    }
    const row1 = document.createElement('div');
    row1.className = 'row';
    const input = document.createElement('input');
    input.id = 'newRoomName';
    input.type = 'text';
    input.placeholder = 'Nom de la salle';
    row1.appendChild(input);
    box.appendChild(row1);

    const row2 = document.createElement('div');
    row2.className = 'row';
    row2.style.alignItems = 'center';
    const label = document.createElement('label');
    const checkbox = document.createElement('input');
    checkbox.id = 'isPrivate';
    checkbox.type = 'checkbox';
    label.appendChild(checkbox);
    label.appendChild(document.createTextNode(' Privée (via lien)'));
    row2.appendChild(label);
    const btn = document.createElement('button');
    btn.className = 'btn';
    btn.textContent = 'Créer';
    btn.onclick = createRoom;
    row2.appendChild(btn);
    box.appendChild(row2);

    const hint = document.createElement('div');
    hint.className = 'hint';
    hint.textContent = 'Les salles privées n’apparaissent pas publiquement. Partage le lien 🔗 pour y inviter quelqu’un.';
    box.appendChild(hint);
    lastRenderedKey = currentKey;
    return;
  }
  inputWrapper.style.display = 'flex';
  if (currentKey.startsWith('dm_')) {
    const partnerId = currentKey.slice(3);
    const uname = clients[partnerId]?.name || dmPeers[partnerId] || 'Utilisateur';
    const st = clients[partnerId]?.status || 'offline';
    const statusDiv = document.createElement('div');
    statusDiv.className = 'dm-status';
    const dot = document.createElement('span');
    dot.className = 'dot ' + (st === 'online' ? 'ok' : st);
    statusDiv.appendChild(dot);
    statusDiv.appendChild(document.createTextNode(`${uname} - ${statusLabels[st] || st}`));
    box.appendChild(statusDiv);
  }
  const list = messages[currentKey] || [];
  for (const m of list) {
    const div = document.createElement('div');
    div.className = 'msg ' + (String(m.from_client_id) === String(client_id) ? 'me' : 'other');
    div.innerHTML = `<small>${m.from_client_name} • ${m.time || ''}</small>${m.content}`;
    box.appendChild(div);
  }
  if (currentKey !== lastRenderedKey || atBottom) {
    box.scrollTop = box.scrollHeight;
  }
  lastRenderedKey = currentKey;
  renderTyping();
}

function renderTyping(){
  const indicator = document.getElementById('typingIndicator');
  const conv = typingUsers[currentKey] || {};
  const ids = Object.keys(conv);
  if (ids.length === 0) {
    indicator.textContent = '';
    indicator.classList.add('hidden');
    return;
  }
  const names = ids.map(id => clients[id]?.name || 'Utilisateur');
  indicator.textContent = names.length === 1 ? `${names[0]} est en train d’écrire…` : `${names.join(', ')} sont en train d’écrire…`;
  indicator.classList.remove('hidden');
}
/* =========================
   ENVOI / DM
========================= */
function openDM(id, username){
  const key = 'dm_' + id;
  if (!messages[key]) messages[key] = [];
  tabs[key] = 'DM avec ' + username;
  dmPeers[id] = username;
  localStorage.setItem('dmPeers', JSON.stringify(dmPeers));
  setCurrentKey(key);
  renderTabs();
  renderMessages();
  if (messages[key].length === 0) {
    ws.send(JSON.stringify({type: 'dm_history', to_client_id: id}));
  }
  if (chatWrapper.classList.contains('hidden')) {
    chatWrapper.classList.remove('hidden');
    chatToggle.textContent = '⬇️';
  }
  chatToggle.classList.remove('blink');
  clearBlink(key);
  if (notifKey === key) notifKey = null;
}

function onSubmit(){
  const ta = document.getElementById('input');
  const text = ta.value.trim();
  if (!text) return;

  const msg = { type: 'say', content: text };

  if (currentKey.startsWith('dm_')) {
    const partner = currentKey.split('_')[1];
    // garde-fou si liste locale pas à jour
    msg.to_client_id   = partner;
    msg.to_client_name = (clients[partner]?.name) || 'Utilisateur';
    msg.dm             = true;
  } else {
    msg.room_id = currentKey.replace('room_', '');
  }

  ws.send(JSON.stringify(msg));

  // pas d’ajout local ici -> serveur renvoie UNE fois (évite doublons)
  ta.value = '';
}

async function loadAnnouncements(){
  try{
    const res = await fetch(`${API_BASE}/announce`);
    if(!res.ok) return;
    const list = await res.json();
    list.forEach(a=>{
      if(a.latitude !== null && a.longitude !== null){
        const id = `announce_${a.id}`;
        announcementEntities[id] = viewer.entities.add({
          id,
          position: Cesium.Cartesian3.fromDegrees(a.longitude, a.latitude),
          point: { pixelSize: 8, color: Cesium.Color.YELLOW },
          name: a.title || 'Annonce',
          description:
            (a.description?`<p>${a.description}</p>`:'')+
            (a.area?`<p>Zone: ${a.area}</p>`:'')+
            (a.landmarks?`<p>Repères: ${a.landmarks}</p>`:'')+
            (a.contact?`<p>Contact: ${a.contact}</p>`:'')
        });
        if(a.range_km){
          viewer.entities.add({
            position: Cesium.Cartesian3.fromDegrees(a.longitude, a.latitude),
            ellipse:{
              semiMajorAxis:a.range_km*1000,
              semiMinorAxis:a.range_km*1000,
              material:Cesium.Color.YELLOW.withAlpha(0.2),
              outline:true,
              outlineColor:Cesium.Color.YELLOW
            }
          });
        }
      }
    });
  }catch(e){
    console.error('announces', e);
  }
}

/* Enter -> envoyer ; Ctrl+Enter -> retour ligne */
document.getElementById('input').addEventListener('keydown', (e)=>{
  if (e.key === 'Enter' && !e.ctrlKey) { e.preventDefault(); onSubmit(); }
  else if (e.key === 'Enter' && e.ctrlKey) { e.preventDefault(); e.target.value += '\n'; }
});
document.getElementById('input').addEventListener('input', () => {
  const now = Date.now();
  if (now - lastTypingSent < 1000) return;
  const msg = {type: 'typing'};
  if (currentKey.startsWith('dm_')) msg.to_client_id = currentKey.slice(3);
  ws.send(JSON.stringify(msg));
  lastTypingSent = now;
});

/* Blink onglets */
function blinkTab(key){
  const el = document.getElementById('tab_' + key);
  if (el && !el.classList.contains('active')) el.classList.add('blink');
}
function clearBlink(key){
  const el = document.getElementById('tab_' + key);
  if (el) el.classList.remove('blink');
}

/* Boot UI */
loadAnnouncements();
connect();
renderTabs();
renderMessages();
</script>
</body>
</html>
