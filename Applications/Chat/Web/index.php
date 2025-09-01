<?php
include __DIR__ . '/../../../config.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <title>⚡ Chat Futuriste ⚡</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <style>
    :root{
      --bg:#0f172a; --panel:#1e293b; --muted:#334155; --muted-2:#475569;
      --accent:#0ea5e9; --text:#f8fafc; --sub:#94a3b8;
      --ok:#22c55e; --busy:#f97316; --away:#ef4444; --warn:#facc15;
    }
    *{box-sizing:border-box}
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Inter,Arial,sans-serif;background:var(--bg);color:var(--text);display:flex;min-height:100vh;height:100dvh;overflow:hidden}
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
    .chat{flex:1;display:flex;flex-direction:column}
    .tabs{display:flex;align-items:center;gap:.5rem;background:var(--panel);padding:.5rem 1rem;flex-wrap:wrap}
    .tab{background:var(--muted);border-radius:6px;padding:.28rem .55rem;display:flex;align-items:center;gap:.4rem;cursor:pointer}
    .tab.active{background:var(--accent);color:#fff}
    .tab.blink{animation:blink 1s infinite}
    .tab .actions{display:flex;align-items:center;gap:.25rem}
    .icon-btn{background:transparent;border:none;color:inherit;cursor:pointer;font-size:14px;opacity:.9}
    .icon-btn:hover{opacity:1}
    .messages{flex:1;overflow-y:auto;padding:1rem;background:#0b1220}
    .msg{margin-bottom:12px;max-width:72%}
    .msg.me{margin-left:auto;text-align:right}
    .msg small{display:block;color:var(--sub);font-size:.72rem;margin-bottom:2px}
    .input{display:flex;gap:.5rem;background:var(--panel);padding:.6rem}
    .input textarea{flex:1;min-height:42px;max-height:160px;resize:vertical;background:#0b1220;border:1px solid #203244;color:#e5e7eb;padding:.6rem;border-radius:8px}
    .input button{background:var(--accent);border:none;color:#fff;border-radius:8px;padding:.55rem 1rem;cursor:pointer}
    .dot{width:10px;height:10px;border-radius:50%}
    .dot.ok{background:var(--ok)} .dot.busy{background:var(--busy)} .dot.away{background:var(--away)}
    .select{width:100%;background:#0b1220;border:1px solid #203244;color:#e5e7eb;padding:.45rem .5rem;border-radius:6px}
    .hint{font-size:.8rem;color:#a3b2c7}
    @media (max-width:768px){
      body{flex-direction:column;height:auto;overflow:auto}
      .chat{order:1;width:100%}
      .sidebar{flex:0 0 100%;width:100%;height:auto}
      .sidebar.rooms{order:2}
      .sidebar.users{order:3}
    }
  </style>
</head>
<body>
  <!-- Salles -->
  <aside class="sidebar rooms">
    <details open>
      <summary class="h2">🛰️ Salles</summary>
      <div id="rooms" class="list"></div>
      <div class="row">
        <input id="newRoomName" type="text" placeholder="Nom de la salle" />
      </div>
      <div class="row" style="align-items:center">
        <label><input id="isPrivate" type="checkbox" /> Privée (via lien)</label>
        <button class="btn" onclick="createRoom()">Créer</button>
      </div>
      <div class="hint">Les salles privées n’apparaissent pas publiquement. Partage le lien 🔗 pour y inviter quelqu’un.</div>
    </details>
  </aside>

  <!-- Chat -->
  <main class="chat">
    <div class="tabs" id="tabs"></div>
    <div class="messages" id="messages"></div>
    <div class="input">
      <textarea id="input" placeholder="Écris un message..."></textarea>
      <button onclick="onSubmit()">Envoyer</button>
    </div>
  </main>

  <!-- Utilisateurs -->
  <aside class="sidebar users">
    <details open>
      <summary class="h2">👥 Utilisateurs</summary>
      <div id="users" class="list"></div>
      <select id="statusSelect" class="select" onchange="changeStatus()">
        <option value="online">🟢 En ligne</option>
        <option value="busy">🟠 Occupé</option>
        <option value="away">🔴 Absent</option>
      </select>
    </details>
  </aside>

<script>
if (window.matchMedia('(max-width:768px)').matches) {
  document.querySelectorAll('.sidebar details').forEach(d => d.removeAttribute('open'));
}
/* =========================
   ÉTAT APP
========================= */
let ws, name, client_id = null, status = 'online';

// conversations: "room_<roomId>" ou "dm_<clientId>"
let currentKey = 'room_general';
let messages   = { room_general: [] };
let tabs       = { room_general: 'Salle générale' };

// liste d’utilisateurs de la room active : { id: {name, status} }
let clients = {};

// liste des rooms visibles côté UI
// value = {visibility: 'public'|'private', creator_id: number|null}
let rooms = new Map([['general', {visibility:'public', creator_id:null}]]);

// rejoindre via lien ?room=xxx
const q = new URLSearchParams(location.search);
if (q.get('room')) rooms.set(q.get('room'), {visibility:'private', creator_id:null});

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
      echo "ws = new WebSocket('wss://' + document.domain + ':7272');";
  }
  ?>


  ws.onopen = () => {
    if (!name) name = localStorage.getItem('chatName') || 'Invité';
    // login première room : soit "general", soit celle de l'URL si présente
    loginRoom([...rooms.keys()][0]);
  };

  ws.onmessage = onmessage;
  ws.onclose   = () => setTimeout(connect, 1500);
}

function loginRoom(roomId){
  // reset UI utilisateurs (évite l'affichage d'une ancienne liste avant le "welcome")
  clients = {};
  renderUsers();
  ws.send(JSON.stringify({type:'login', client_name:name, room_id:roomId, status, ua: navigator.userAgent}));
}

function changeStatus(){
  status = document.getElementById('statusSelect').value;
  // update immédiat côté UI (optimiste)
  if (client_id && clients[client_id]) {
    clients[client_id].status = status;
    renderUsers();
  }
  // et propagation serveur
  ws.send(JSON.stringify({type:'status', status}));
}

function chooseName(){
  if (name === 'Invité') {
    const newName = prompt('Votre pseudo ?') || 'Invité';
    name = newName;
    localStorage.setItem('chatName', name);
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
      if (!client_id) client_id = data.self_id;
      clients = data.client_list || {};
      renderUsers();

      // S’assure que l’onglet de la room existe et devient actif
      ensureRoomTab(data.room_id);
      renderRooms();
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
      } else if (data.client_id && data.client_name) {
        // Sécurité : merge si pas de client_list
        clients[data.client_id] = {name: data.client_name, status: data.status || 'online'};
        renderUsers();
      }
      break;
    }

    case 'status': {
      // MAJ statut en temps réel
      if (!clients[data.client_id]) clients[data.client_id] = {name:'Utilisateur', status:data.status};
      clients[data.client_id].status = data.status;
      renderUsers();
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
      }
      renderUsers();
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
      currentKey = 'room_' + room_id;
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
          currentKey = 'room_general';
          loginRoom('general');
        }
        renderRooms(); renderTabs(); renderMessages();
      }
      break;
    }

    case 'say': {
      // Stocke ET rafraîchit si la conversation visible est concernée
      const key = storeMessage(data);
      if (currentKey === key) renderMessages(); else blinkTab(key);
      break;
    }

    case 'logout': {
      delete clients[data.from_client_id];
      renderUsers();
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
  currentKey = key;
  renderTabs(); renderMessages();
}

function renderRooms(){
  const cont = document.getElementById('rooms');
  cont.innerHTML = '';
  for (const [id, meta] of rooms.entries()){
    const key = 'room_' + id;
    const div = document.createElement('div');
    div.className = 'item' + (currentKey === key ? ' active' : '');
    div.textContent = (meta.visibility === 'private' ? '🔒 ' : '') + 'Salle ' + id;
    div.onclick = () => {
      currentKey = key;
      ensureRoomTab(id);
      renderTabs(); renderMessages();
      loginRoom(id);
    };
    cont.appendChild(div);
  }
}

function renderTabs(){
  const t = document.getElementById('tabs'); t.innerHTML = '';
  Object.keys(tabs).forEach(k => {
    const div   = document.createElement('div');
    const label = document.createElement('span');
    div.className = 'tab' + (k === currentKey ? ' active' : '');
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
    }

    div.onclick = () => { currentKey = k; renderTabs(); renderMessages(); clearBlink(k); };
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
    currentKey = 'room_' + roomId;
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
    dot.className = 'dot ' + (info.status === 'busy' ? 'busy' : (info.status === 'away' ? 'away' : 'ok'));
    div.appendChild(dot);

    const label = document.createElement('span');
    label.textContent = (info.name || 'Invité') + (id == client_id ? ' (moi)' : '');
    div.appendChild(label);

    if (id == client_id) div.onclick = chooseName;
    else div.onclick = () => openDM(id, info.name || 'Invité');

    u.appendChild(div);
  });
}

/* =========================
   MESSAGES
========================= */
function storeMessage(m){
  const key = getMessageKey(m);
  if (!messages[key]) messages[key] = [];
  messages[key].push(m);

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
  box.innerHTML = '';
  const list = messages[currentKey] || [];
  for (const m of list) {
    const div = document.createElement('div');
    div.className = 'msg ' + (String(m.from_client_id) === String(client_id) ? 'me' : 'other');
    div.innerHTML = `<small>${m.from_client_name} • ${m.time || ''}</small>${m.content}`;
    box.appendChild(div);
  }
  box.scrollTop = box.scrollHeight;
}

/* =========================
   ENVOI / DM
========================= */
function openDM(id, username){
  const key = 'dm_' + id;
  if (!messages[key]) messages[key] = [];
  tabs[key] = 'DM avec ' + username;
  currentKey = key;
  renderTabs(); renderMessages();
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

/* Enter -> envoyer ; Ctrl+Enter -> retour ligne */
document.getElementById('input').addEventListener('keydown', (e)=>{
  if (e.key === 'Enter' && !e.ctrlKey) { e.preventDefault(); onSubmit(); }
  else if (e.key === 'Enter' && e.ctrlKey) { e.preventDefault(); e.target.value += '\n'; }
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
connect();
renderTabs();
renderRooms();
</script>
</body>
</html>
