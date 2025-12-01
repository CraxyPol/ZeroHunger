<?php
session_start();
require_once "db.php"; // expects $conn (mysqli) and login session that sets $_SESSION['user_id'] and $_SESSION['user_type']

// Only allow admin
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'Admin') {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>ZeroHunger — Admin Dashboard</title>

  <!-- Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>

  <!-- Font Awesome -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

  <!-- SweetAlert2 for nice confirmations / toasts -->
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    :root{
      --bgA: #dbeafe; --bgB: #efe8ff; --primary: #6d28d9; --muted: #6b7280;
      --glass: rgba(255,255,255,0.92); --glass-border: rgba(255,255,255,0.6);
      --shadow-sm: 0 8px 28px rgba(16,24,40,0.06); --shadow-lg: 0 20px 60px rgba(16,24,40,0.12);
      --maxw: 1200px;
    }
    body{ font-family: "Poppins", system-ui, -apple-system, "Segoe UI", Roboto, Arial; margin:0; min-height:100vh;
      background: linear-gradient(135deg, var(--bgA) 0%, var(--bgB) 60%); color:#0b1220; -webkit-font-smoothing:antialiased;}
    .glass{ background:var(--glass); border-radius:12px; border:1px solid var(--glass-border); box-shadow:var(--shadow-sm); padding:1rem; backdrop-filter: blur(6px);}
    header{ position:sticky; top:0; z-index:50; background: rgba(255,255,255,0.85); backdrop-filter: blur(6px); border-bottom: 1px solid rgba(16,24,40,0.04);}
    .nav{ max-width:var(--maxw); margin:0 auto; padding:.7rem 1rem; display:flex; justify-content:space-between; align-items:center; gap:1rem;}
    .brand{ display:flex; gap:.6rem; align-items:center; color:var(--primary); font-weight:700; }
    .container{ max-width: var(--maxw); margin: 1rem auto; padding: 0 1rem; }
    .grid-cols-main{ display:grid; grid-template-columns: 1fr; gap:1rem; }
    @media(min-width:1100px){ .grid-cols-main{ grid-template-columns: 1fr 360px; } }
    .metrics-grid{ display:grid; gap:.8rem; grid-template-columns: repeat(2,1fr); }
    @media(min-width:900px){ .metrics-grid{ grid-template-columns: repeat(4,1fr); } }
    .metric .label{ color:var(--muted); font-size:.85rem; } .metric .value{ font-weight:800; font-size:1.4rem; color:var(--primary); }
    table{ width:100%; border-collapse:collapse; font-size:.95rem; }
    th, td{ padding:.6rem .6rem; border-bottom:1px solid rgba(16,24,40,0.04); text-align:left; vertical-align:middle; }
    th{ color:var(--muted); font-weight:700; font-size:.85rem; }
    .user-avatar{ width:44px; height:44px; border-radius:8px; overflow:hidden; background:#fff; display:block; }
    .badge{ display:inline-block; padding:.28rem .6rem; border-radius:999px; font-weight:700; font-size:.8rem; }
    .status-pending{ background:#fff7ed; color:#92400e; } .status-verified{ background:#ecfdf5; color:#065f46; } .status-rejected{ background:#fff1f2; color:#9f1239; }
    .btn{ display:inline-flex; align-items:center; gap:.5rem; padding:.5rem .8rem; border-radius:999px; font-weight:600; cursor:pointer; border:0; }
    .btn-primary{ background: linear-gradient(90deg, var(--primary), #8b5cf6); color:white; box-shadow: 0 8px 30px rgba(109,40,217,0.12); }
    .btn-ghost{ background: transparent; border: 1px solid rgba(16,24,40,0.06); color: #0b1220; }
    .modal-backdrop{ position:fixed; inset:0; display:none; align-items:center; justify-content:center; background: rgba(6,6,10,0.45); z-index:60; padding:1rem; }
    .modal{ width:100%; max-width:920px; display:grid; grid-template-columns: 320px 1fr; background:var(--glass); border-radius:12px; border:1px solid var(--glass-border); box-shadow:var(--shadow-lg); overflow:hidden; }
    @media(max-width:920px){ .modal{ grid-template-columns: 1fr; } .modal .left{ order:2 } }
    .reveal{ opacity:0; transform: translateY(12px); transition: opacity .55s ease, transform .55s ease; } .reveal.visible{ opacity:1; transform:none; }
    footer{ text-align:center;padding:1rem;color:var(--muted); }
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="nav">
      <div class="brand">
        <img src="images/zero.png" alt="ZeroHunger logo" class="rounded-md w-11 h-11 object-cover shadow" onerror="this.style.display='none'">
        <div>
          <div style="font-size:1rem">ZeroHunger Admin</div>
          <div style="font-size:.85rem; color:var(--muted)">Platform moderation & verification</div>
        </div>
      </div>

      <div style="display:flex;gap:.6rem;align-items:center">
        <button id="seedDemo" class="btn btn-ghost"><i class="fa-solid fa-database"></i> Seed Demo</button>
        <button id="exportUsers" class="btn btn-ghost"><i class="fa-solid fa-file-export"></i> Export JSON</button>
        <button id="logoutBtn" class="btn btn-ghost"><i class="fa-solid fa-right-from-bracket"></i> Sign out</button>
      </div>
    </div>
  </header>

  <!-- Main -->
  <main class="container">
    <div class="grid-cols-main">
      <!-- LEFT -->
      <div>
        <!-- Overview -->
        <section class="glass reveal" id="overview">
          <div class="flex justify-between items-center">
            <div>
              <h2 class="text-lg font-bold" style="color:var(--primary)">Platform Overview</h2>
              <p class="text-sm text-gray-500">Quick snapshot of user counts and verifications</p>
            </div>
            <div class="text-sm text-gray-500">Updated <span id="lastUpdated">—</span></div>
          </div>

          <div class="metrics-grid mt-4">
            <div class="metric glass">
              <div class="label">Total Users</div>
              <div class="value" id="cntTotal" data-value="0">0</div>
            </div>
            <div class="metric glass">
              <div class="label">Verified Accounts</div>
              <div class="value" id="cntVerified" data-value="0">0</div>
            </div>
            <div class="metric glass">
              <div class="label">Pending Verifications</div>
              <div class="value" id="cntPending" data-value="0">0</div>
            </div>
            <div class="metric glass">
              <div class="label">Total Donations (demo)</div>
              <div class="value" id="cntDonations" data-value="0">0</div>
            </div>
          </div>

          <!-- charts -->
          <div class="charts mt-4 grid gap-3 grid-cols-1 lg:grid-cols-[1fr_360px]">
            <div class="glass p-4">
              <h3 class="font-bold text-md" style="color:var(--primary)">Users by Role (Donor · Charity)</h3>
              <canvas id="barUsers" height="120"></canvas>
            </div>
            <div class="glass p-4">
              <h3 class="font-bold text-md" style="color:var(--primary)">Verification Status</h3>
              <canvas id="pieStatus" height="200"></canvas>
            </div>
          </div>
        </section>

        <!-- Users list -->
        <section class="reveal mt-4">
          <div class="flex justify-between items-center mb-2">
            <div>
              <h3 class="text-lg font-bold" style="color:var(--primary)">Registered Users</h3>
              <p class="text-sm text-gray-500">Search, filter, and verify user IDs</p>
            </div>
            <div class="flex items-center gap-2">
              <input id="searchUsers" class="border rounded-md px-3 py-2 text-sm" placeholder="Search name or email..." />
              <select id="filterRole" class="border rounded-md px-3 py-2 text-sm">
                <option value="">All roles</option>
                <option value="donor">Donor</option>
                <option value="charity">Charity</option>
                <option value="admin">Admin</option>
              </select>
              <select id="filterStatus" class="border rounded-md px-3 py-2 text-sm">
                <option value="">All statuses</option>
                <option value="pending">Pending</option>
                <option value="verified">Verified</option>
                <option value="rejected">Rejected</option>
              </select>
            </div>
          </div>

          <div class="glass">
            <div class="overflow-auto">
              <table>
                <thead>
                  <tr>
                    <th>User</th>
                    <th>Role</th>
                    <th>Contact</th>
                    <th>Status</th>
                    <th>Registered</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody id="usersTbody"><tr><td colspan="6" class="text-gray-500 p-4">Loading…</td></tr></tbody>
              </table>
            </div>
          </div>
        </section>
      </div>

      <!-- RIGHT rail -->
      <aside>
        <div class="glass reveal">
          <h4 class="font-bold" style="color:var(--primary)">Verification Queue</h4>
          <div id="queue" class="mt-3 grid gap-2"></div>
        </div>

        <div class="glass reveal mt-3">
          <h4 class="font-bold" style="color:var(--primary)">Quick Actions</h4>
          <div class="flex flex-col gap-2 mt-3">
            <button id="verifyAll" class="btn btn-primary"><i class="fa-solid fa-user-check"></i> Verify All Pending</button>
            <button id="exportJson" class="btn btn-ghost"><i class="fa-solid fa-file-arrow-down"></i> Download users.json</button>
            <button id="clearAll" class="btn btn-ghost"><i class="fa-solid fa-trash"></i> Clear demo data</button>
          </div>
        </div>

        <div class="glass reveal mt-3">
          <h4 class="font-bold" style="color:var(--primary)">Reports Summary</h4>
          <div id="reports" class="mt-3"></div>
        </div>
      </aside>
    </div>
  </main>

  <!-- Profile modal -->
  <div id="profileModal" class="modal-backdrop" aria-hidden="true">
    <div class="modal">
      <div class="left p-4">
        <div class="text-center">
          <div class="img-frame mb-3" style="height:200px">
            <img id="profileAvatar" src="samplepic.jpg" alt="avatar" class="w-full h-full object-cover">
          </div>
          <div class="flex justify-center gap-2">
            <button id="changeAvatarBtn" class="btn btn-ghost"><i class="fa-solid fa-camera"></i> Change photo</button>
            <input id="avatarFile" type="file" accept="image/*" class="hidden">
          </div>
        </div>

        <hr class="my-3">

        <div>
          <div class="text-sm text-gray-500">Valid ID</div>
          <div class="img-frame mt-2" style="height:150px">
            <img id="profileID" src="samplepic.jpg" alt="valid id" class="w-full h-full object-cover">
          </div>
          <div class="mt-3 flex justify-center gap-2">
            <button id="verifyBtn" class="btn btn-primary"><i class="fa-solid fa-check"></i> Verify</button>
            <button id="rejectBtn" class="btn btn-ghost"><i class="fa-solid fa-xmark"></i> Reject</button>
          </div>
        </div>
      </div>

      <div class="right p-4">
        <div class="flex justify-between items-start">
          <div>
            <h3 id="profileName" class="text-xl font-bold">Name</h3>
            <div id="profileRole" class="text-gray-500">role</div>
          </div>
          <div>
            <button id="closeProfile" class="btn btn-ghost"><i class="fa-solid fa-times"></i></button>
          </div>
        </div>

        <div class="mt-4 grid gap-3">
          <div>
            <div class="text-sm text-gray-500">Email</div>
            <div id="profileEmail" class="font-bold"></div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Phone</div>
            <div id="profilePhone" class="font-bold"></div>
          </div>
          <div>
            <div class="text-sm text-gray-500">Status</div>
            <div id="profileStatus"></div>
          </div>

          <div>
            <div class="text-sm text-gray-500">Notes</div>
            <textarea id="profileNotes" rows="3" class="w-full border rounded-md p-2"></textarea>
          </div>

          <div class="flex justify-end gap-2">
            <button id="saveProfile" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            <button id="closeProfile2" class="btn btn-ghost">Close</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <footer>© <?php echo date("Y"); ?> ZeroHunger — Admin</footer>

<script>
/* ---------- Utilities ---------- */
const $ = s => document.querySelector(s);
const $$ = s => Array.from(document.querySelectorAll(s));

let usersCache = {}; // email->user object
let currentProfileEmail = null;

function toast(type, msg){
  Swal.fire({ toast:true, position:'bottom-end', icon:type, title: msg, showConfirmButton:false, timer:1600, timerProgressBar:true });
}

/* ---------- Fetch helpers ---------- */
async function apiGET(path){
  const res = await fetch(path, { credentials:'same-origin' });
  if(!res.ok) throw new Error('Network error');
  return res.json();
}
async function apiPOST(path, payload){
  const res = await fetch(path, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  });
  if(!res.ok) throw new Error('Network error');
  return res.json();
}

/* ---------- Load users from server (fixed: uses refresh functions) ---------- */
async function loadFromServer(){
  try {
    const data = await apiGET('api/users.php');
    usersCache = {};
    data.users.forEach(u => {
      const key = (u.email && u.email.length) ? u.email : (u.username || ('user_'+u.user_id));
      usersCache[key] = u;
      usersCache[key].created = Number(u.created) || Date.now();
      usersCache[key].role = (u.user_type || u.role || 'donor').toLowerCase();
      const st = (u.account_status || u.status || '').toLowerCase();
      usersCache[key].status =
        st.includes('active') || st === 'verified' ? 'verified' :
        st.includes('declined') || st === 'rejected' ? 'rejected' :
        'pending';
      usersCache[key].email = u.email || u.username || '';
    });

    // Fixed: use the proper render/refresh flow
    renderOverview();
    renderUsersTable();
    renderQueue();
    updateCharts();

  } catch (err){
    console.error(err);
    Swal.fire('Error', 'Failed to load users from server: ' + err.message, 'error');
  }
}

/* ---------- Rendering ---------- */
function formatDate(ts){
  const d = new Date(Number(ts));
  if (!d || isNaN(d)) return '-';
  return d.toLocaleDateString() + ' ' + d.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'});
}

function statusBadge(s){
  if(s === 'verified') return `<span class="badge status-verified">Verified</span>`;
  if(s === 'rejected') return `<span class="badge status-rejected">Rejected</span>`;
  return `<span class="badge status-pending">Pending</span>`;
}

function renderOverview(){
  const list = Object.values(usersCache);
  const total = list.length;
  const verified = list.filter(u => u.status === 'verified').length;
  const pending = list.filter(u => u.status === 'pending').length;
  const donations = Math.max(0, Math.floor(total * 12.3));

  $('#lastUpdated').textContent = new Date().toLocaleString();
  $('#cntTotal').dataset.value = total; $('#cntTotal').textContent = total.toLocaleString();
  $('#cntVerified').dataset.value = verified; $('#cntVerified').textContent = verified.toLocaleString();
  $('#cntPending').dataset.value = pending; $('#cntPending').textContent = pending.toLocaleString();
  $('#cntDonations').dataset.value = donations; $('#cntDonations').textContent = donations.toLocaleString();

  updateCharts();
}

let barChart=null, pieChart=null;
function initCharts(){
  const barCtx = document.getElementById('barUsers').getContext('2d');
  const pieCtx = document.getElementById('pieStatus').getContext('2d');

  barChart = new Chart(barCtx, {
    type:'bar',
    data:{ labels:['Donor','Charity'], datasets:[{ label:'Users by Role', data:[0,0], backgroundColor:['#93c5fd','#c7b2ff'], borderRadius:6 }]},
    options:{ responsive:true, plugins:{ legend:{ display:false } }, scales:{ y:{ beginAtZero:true, ticks:{ precision:0 } } } }
  });

  pieChart = new Chart(pieCtx, {
    type:'doughnut',
    data:{ labels:['Verified','Pending','Rejected'], datasets:[{ data:[0,0,0], backgroundColor:['#10b981','#f59e0b','#ef4444'] }]},
    options:{ responsive:true, plugins:{ legend:{ position:'bottom' } } }
  });
}

function updateCharts(){
  const list = Object.values(usersCache);
  const donors = list.filter(u => u.role === 'donor').length;
  const charities = list.filter(u => u.role === 'charity').length;
  const verified = list.filter(u => u.status === 'verified').length;
  const pending = list.filter(u => u.status === 'pending').length;
  const rejected = list.filter(u => u.status === 'rejected').length;

  if(barChart){ barChart.data.datasets[0].data = [donors, charities]; barChart.update(); }
  if(pieChart){ pieChart.data.datasets[0].data = [verified, pending, rejected]; pieChart.update(); }
}

function renderUsersTable(){
  const tbody = $('#usersTbody');
  const q = $('#searchUsers').value.trim().toLowerCase();
  const roleFilter = $('#filterRole').value;
  const statusFilter = $('#filterStatus').value;
  tbody.innerHTML = '';

  const arr = Object.values(usersCache).sort((a,b)=> (b.created||0) - (a.created||0));
  if(arr.length === 0){
    tbody.innerHTML = '<tr><td colspan="6" class="text-gray-500 p-4">No users yet</td></tr>';
    return;
  }

  arr.forEach(u => {
    if(q && !((u.name||'') + ' ' + (u.email||'') + ' ' + (u.phone_number||'')).toLowerCase().includes(q)) return;
    if(roleFilter && (u.role||'') !== roleFilter) return;
    if(statusFilter && (u.status||'') !== statusFilter) return;

    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>
        <div class="flex items-center gap-3">
          <div class="user-avatar"><img src="${u.selfie || 'samplepic.jpg'}" alt="avatar" class="w-full h-full object-cover"></div>
          <div>
            <div class="font-bold">${escapeHtml(u.name || u.username || '—')}</div>
            <div class="text-gray-500 small">${escapeHtml(u.email || '')}</div>
          </div>
        </div>
      </td>
      <td class="text-sm text-gray-600">${escapeHtml(u.role || u.user_type || 'donor')}</td>
      <td><div class="text-sm">${escapeHtml(u.phone_number || '-')}</div><div class="text-gray-500 small">${escapeHtml(u.email || '')}</div></td>
      <td>${statusBadge(u.status)}</td>
      <td class="text-gray-500 small">${formatDate(u.created || Date.now())}</td>
      <td class="text-sm">
        <button class="btn btn-ghost view-user" data-email="${escapeHtml(u.email||u.username||u.user_id)}"><i class="fa-solid fa-eye"></i></button>
        <button class="btn btn-ghost report-user" data-email="${escapeHtml(u.email||u.username||u.user_id)}"><i class="fa-solid fa-flag"></i></button>
      </td>
    `;
    tbody.appendChild(tr);
  });

  $$('.view-user').forEach(b => b.addEventListener('click', e => openProfileModal(b.dataset.email)));
  $$('.report-user').forEach(b => b.addEventListener('click', e => {
    const em = b.dataset.email;
    apiPOST('api/user_action.php', { action:'report', email: em })
      .then(r => { toast('info','Report logged'); refreshAll(); })
      .catch(err => Swal.fire('Error', err.message, 'error'));
  }));
}

function renderQueue(){
  const pending = Object.values(usersCache).filter(u => u.status === 'pending').sort((a,b)=> (a.created||0) - (b.created||0));
  const el = $('#queue');
  el.innerHTML = '';
  if(pending.length === 0) { el.innerHTML = '<div class="text-gray-500 small">No pending verifications</div>'; return; }
  pending.slice(0,6).forEach(u => {
    const div = document.createElement('div');
    div.className = 'flex justify-between items-center bg-white p-2 rounded';
    div.innerHTML = `<div><strong>${escapeHtml(u.name)}</strong><div class="text-gray-500 small">${escapeHtml(u.email)}</div></div>
      <div><button class="btn btn-ghost open-queue" data-email="${escapeHtml(u.email)}">Open</button></div>`;
    el.appendChild(div);
  });
  $$('.open-queue').forEach(b => b.addEventListener('click', e => openProfileModal(b.dataset.email)));
}

function renderReports(list){
  const rs = list || [];
  const el = $('#reports'); el.innerHTML = '';
  if(!rs.length){ el.innerHTML = '<div class="text-gray-500 small">No reports</div>'; return; }
  rs.slice(0,6).forEach(r => {
    const d = document.createElement('div');
    d.className = 'bg-white p-2 rounded mb-2';
    d.innerHTML = `<div class="font-bold">${escapeHtml(r.text)}</div><div class="text-gray-500 small">${formatDate(r.created)}</div>`;
    el.appendChild(d);
  });
}

/* ---------- Modal ---------- */
function openProfileModal(emailKey){
  const u = usersCache[emailKey];
  if(!u){ Swal.fire('Not found','User not found','error'); return; }
  currentProfileEmail = emailKey;
  $('#profileAvatar').src = u.selfie || 'samplepic.jpg';
  $('#profileID').src = u.id_image || 'samplepic.jpg';
  $('#profileName').textContent = u.name || u.username;
  $('#profileRole').textContent = (u.role || u.user_type || 'donor');
  $('#profileEmail').textContent = u.email || u.username || '';
  $('#profilePhone').textContent = u.phone_number || '-';
  $('#profileStatus').innerHTML = statusBadge(u.status);
  $('#profileNotes').value = u.notes || '';
  $('#profileModal').style.display = 'flex';
  $('#profileModal').setAttribute('aria-hidden','false');
  document.body.style.overflow = 'hidden';
}

function closeProfileModal(){
  $('#profileModal').style.display = 'none';
  $('#profileModal').setAttribute('aria-hidden','true');
  document.body.style.overflow = '';
  currentProfileEmail = null;
}

/* ---------- Actions (verify / reject / save notes) ---------- */
$('#verifyBtn').addEventListener('click', async ()=> {
  if(!currentProfileEmail) return;
  const payload = { action:'verify', email: currentProfileEmail };
  try {
    const res = await apiPOST('api/user_action.php', payload);
    if(res.success){ toast('success','User verified'); closeProfileModal(); refreshAll(); }
    else Swal.fire('Error', res.error || 'Unknown');
  } catch (err) { Swal.fire('Error', err.message, 'error'); }
});

$('#rejectBtn').addEventListener('click', async ()=> {
  if(!currentProfileEmail) return;
  const { value } = await Swal.fire({ title:'Reject verification?', input:'text', inputLabel:'Reason (optional)', showCancelButton:true });
  if(value === null) return;
  try {
    const res = await apiPOST('api/user_action.php', { action:'reject', email: currentProfileEmail, reason: value });
    if(res.success){ toast('success','User rejected'); closeProfileModal(); refreshAll(); }
    else Swal.fire('Error', res.error || 'Unknown');
  } catch (err) { Swal.fire('Error', err.message, 'error'); }
});

$('#saveProfile').addEventListener('click', async ()=> {
  if(!currentProfileEmail) return;
  const notes = $('#profileNotes').value;
  const file = $('#avatarFile').files[0];
  if(file){
    const fr = new FileReader();
    fr.onload = async e => {
      const base64 = e.target.result;
      const res = await apiPOST('api/user_action.php', { action:'update', email: currentProfileEmail, notes, selfie: base64 });
      if(res.success){ toast('success','Saved'); closeProfileModal(); refreshAll(); } else Swal.fire('Error', res.error || 'Unknown');
    };
    fr.readAsDataURL(file);
  } else {
    try {
      const res = await apiPOST('api/user_action.php', { action:'update', email: currentProfileEmail, notes });
      if(res.success){ toast('success','Saved'); closeProfileModal(); refreshAll(); } else Swal.fire('Error', res.error || 'Unknown');
    } catch (err) { Swal.fire('Error', err.message, 'error'); }
  }
});

/* ---------- Other buttons ---------- */
$('#seedDemo').addEventListener('click', async ()=> {
  const r = await Swal.fire({ title:'Seed demo data?', text:'This inserts demo users into DB (demo only)', icon:'question', showCancelButton:true, confirmButtonText:'Seed' });
  if(!r.isConfirmed) return;
  try {
    const res = await apiPOST('api/user_action.php', { action:'seed_demo' });
    if(res.success){ toast('success','Demo seeded'); refreshAll(); } else Swal.fire('Error', res.error || 'Unknown');
  } catch (err) { Swal.fire('Error', err.message, 'error'); }
});

$('#exportUsers').addEventListener('click', ()=> {
  const data = JSON.stringify(Object.values(usersCache), null, 2);
  const blob = new Blob([data], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a'); a.href = url; a.download = 'users.json'; document.body.appendChild(a); a.click(); a.remove();
  URL.revokeObjectURL(url);
  toast('info','Export ready');
});
$('#exportJson').addEventListener('click', ()=> $('#exportUsers').click());

$('#verifyAll').addEventListener('click', async ()=> {
  const r = await Swal.fire({ title:'Verify all pending?', text:'Set all pending accounts to Verified', icon:'question', showCancelButton:true, confirmButtonText:'Verify all' });
  if(!r.isConfirmed) return;
  try {
    const res = await apiPOST('api/user_action.php', { action:'verify_all' });
    if(res.success){ toast('success','Verified all pending'); refreshAll(); } else Swal.fire('Error', res.error || 'Unknown');
  } catch (err) { Swal.fire('Error', err.message, 'error'); }
});

$('#clearAll').addEventListener('click', async ()=> {
  const r = await Swal.fire({ title:'Clear demo data?', text:'Removes demo users (demo only)', icon:'warning', showCancelButton:true, confirmButtonText:'Clear' });
  if(!r.isConfirmed) return;
  try {
    const res = await apiPOST('api/user_action.php', { action:'clear_demo' });
    if(res.success){ toast('success','Demo cleared'); refreshAll(); } else Swal.fire('Error', res.error || 'Unknown');
  } catch (err) { Swal.fire('Error', err.message, 'error'); }
});

$('#logoutBtn').addEventListener('click', ()=> { location.href = 'logout.php'; });

/* ---------- Helpers ---------- */
function escapeHtml(s){ if(!s) return ''; return (s+'').replaceAll && s.replaceAll('&','&amp;').replaceAll('<','&lt;').replaceAll('>','&gt;'); }

/* ---------- Boot / refresh flow ---------- */
function refreshAll(){
  loadFromServer(); // loadFromServer will call renderOverview/renderUsersTable/renderQueue/updateCharts
  // fetch reports & show them when available
  apiGET('api/users.php').then(d => renderReports(d.reports || [])).catch(()=>{});
  setTimeout(()=> $$('.reveal').forEach(n => n.classList.add('visible')), 120);
}

function init(){
  initCharts();
  refreshAll();
  // filters
  $('#searchUsers').addEventListener('input', renderUsersTable);
  $('#filterRole').addEventListener('change', renderUsersTable);
  $('#filterStatus').addEventListener('change', renderUsersTable);

  // modal close handlers
  $('#closeProfile').addEventListener('click', closeProfileModal);
  $('#closeProfile2').addEventListener('click', closeProfileModal);
  $('#profileModal').addEventListener('click', (e)=> { if(e.target === $('#profileModal')) closeProfileModal(); });
  document.addEventListener('keydown', e => { if(e.key === 'Escape') closeProfileModal(); });
}

init();

</script>
</body>
</html>
