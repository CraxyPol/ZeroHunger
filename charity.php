<?php
session_start();
require_once __DIR__ . '/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$me = (int)$_SESSION['user_id'];
$me_type = $_SESSION['user_type'] ?? 'Donor';

if (!in_array($me_type, ['CharitableInstitution', 'Admin'])) {
    header("Location: ../donor_dashboard.php");
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Charity Dashboard — ZeroHunger</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<script src="https://cdn.tailwindcss.com"></script>
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">

<style>
:root { --violet:#6d28d9; --glass:rgba(255,255,255,0.92); }
body { font-family: Inter, system-ui; background: linear-gradient(135deg,#c7d2fe,#ede9fe,#e9d5ff);}
.glass { background:var(--glass); border-radius:12px; border:1px solid rgba(255,255,255,0.6); padding:1rem; }
</style>
</head>

<body class="antialiased">

<header class="sticky top-0 bg-white/80 backdrop-blur-sm border-b z-40">
  <div class="max-w-7xl mx-auto px-6 py-3 flex justify-between items-center">
    <div class="flex items-center gap-3">
      <img src="../images/zero.png" class="h-10 w-10 rounded-md" onerror="this.style.display='none'">
      <div>
        <div class="text-xl font-bold text-violet-700">ZeroHunger</div>
        <div class="text-sm text-gray-500">Charity Dashboard</div>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <button id="btnNotifications" class="relative p-2 hover:bg-gray-100 rounded-md">
        <i class="fa-solid fa-bell"></i>
        <span id="notifBadge"
              class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs px-1 rounded-full">0</span>
      </button>
      <button id="btnMessages" class="p-2 hover:bg-gray-100 rounded-md">
        <i class="fa-solid fa-message"></i>
      </button>

      <img src="<?php
        $photo = 'images/profile.jpg';
        $q = $conn->prepare("SELECT profile_photo FROM users WHERE user_id=? LIMIT 1");
        $q->bind_param("i",$me);
        $q->execute();
        $r = $q->get_result()->fetch_assoc();
        if (!empty($r['profile_photo'])) $photo = $r['profile_photo'];
        echo htmlspecialchars($photo);
      ?>" class="h-9 w-9 rounded-full border object-cover">
    </div>
  </div>
</header>

<main class="max-w-7xl mx-auto px-6 py-8 grid grid-cols-1 lg:grid-cols-3 gap-6">

  <!-- LEFT COLUMN -->
  <section class="lg:col-span-2 space-y-6">
    <div class="glass flex justify-between items-center">
      <div>
        <h2 class="text-2xl font-bold text-violet-700">Incoming Donations</h2>
        <p class="text-gray-500 text-sm">Review and manage donor requests.</p>
      </div>
      <div class="flex gap-3">
        <input id="searchBox" placeholder="Search…" class="border px-3 py-2 rounded-md text-sm">
        <button id="refreshBtn" class="px-4 py-2 bg-violet-700 text-white rounded-md">Refresh</button>
      </div>
    </div>

    <div id="donationList" class="grid grid-cols-1 gap-4"></div>

    <div class="text-center">
      <button id="loadMoreBtn" class="px-5 py-3 border bg-white hover:bg-gray-100 rounded-md">
        Load more
      </button>
    </div>
  </section>

  <!-- RIGHT COLUMN -->
  <aside class="space-y-6">

    <div class="glass">
      <h4 class="font-semibold text-gray-700">Snapshot</h4>
      <div class="grid grid-cols-3 text-center mt-4">
        <div><div class="text-xs">Incoming</div><div id="statIncoming" class="text-2xl font-bold">0</div></div>
        <div><div class="text-xs">Pending</div><div id="statPending" class="text-2xl font-bold">0</div></div>
        <div><div class="text-xs">Recent</div><div id="statRecent" class="text-2xl font-bold">0</div></div>
      </div>
    </div>

    <div class="glass">
      <h4 class="font-semibold text-gray-700">Quick Actions</h4>
      <div class="mt-3 flex flex-col gap-2">
        <button class="px-3 py-2 text-white bg-blue-500 rounded-md">Create Event</button>
        <button class="px-3 py-2 border rounded-md">Open Messages</button>
      </div>
    </div>

    <div class="glass">
      <h4 class="font-semibold text-gray-700">Recent Activity</h4>
      <div id="recentActivity" class="mt-2 text-sm text-gray-600"></div>
    </div>

  </aside>

</main>

<div id="modals"></div>

<script>
/* --------------------------
   ROUTER FUNCTIONS
----------------------------*/
function apiGET(api, params={}) {
    params.api = api;
    return $.getJSON("router.php?" + $.param(params));
}
function apiPOST(api, data={}) {
    data.api = api;
    return $.ajax({
        url: "router.php",
        type: "POST",
        data: JSON.stringify(data),
        dataType: "json",
        contentType: "application/json"
    });
}

/* --------------------------
   LOAD DONATIONS
----------------------------*/
let offset = 0, limit = 10;

function loadDonations(reset=false) {
    if (reset) {
        offset = 0;
        $("#donationList").html("");
    }

    apiGET("list_incoming", { offset, limit }).done(res => {
        if (!res.success) return;
        res.donations.forEach(d => $("#donationList").append(renderDonationCard(d)));
        offset += res.donations.length;
    });
}

function renderDonationCard(d) {
    return `
    <div class="glass p-4 rounded-md">
      <div class="flex justify-between">
        <div>
          <div class="font-bold text-violet-700">${d.donor_name}</div>
          <div class="text-xs text-gray-500">${d.donor_email}</div>
        </div>
        <button class="text-sm px-3 py-1 bg-violet-600 text-white rounded-md"
                onclick="openDonationDetails(${d.donation_id})">View</button>
      </div>
      <div class="mt-2 text-gray-700 text-sm">${d.details}</div>
    </div>`;
}

/* --------------------------
   LOAD STATS
----------------------------*/
function loadStats() {
    apiGET("stats").done(res => {
        if (!res.success) return;
        let s = res.stats;
        $("#statIncoming").text(s.incoming_total);
        $("#statPending").text(s.incoming_pending);
        $("#statRecent").text(s.recent_donations.length);

        $("#recentActivity").html(
            s.recent_donations.map(r => `<div>${r.donor_name} — ${r.details}</div>`).join("")
        );
    });

    apiGET("notifications").done(res => {
        if (!res.success) return;
        const unread = res.notifications.filter(n => n.is_read == 0).length;
        if (unread > 0) $("#notifBadge").text(unread).removeClass("hidden");
        else $("#notifBadge").addClass("hidden");
    });
}

/* --------------------------
   DONATION DETAILS MODAL
----------------------------*/
function openDonationDetails(id) {
    apiGET("donation_details", { donation_id:id }).done(res => {
        if (!res.success) return alert(res.error);
        const d = res.donation;

        const html = `
        <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
            <div class="bg-white p-6 rounded-xl max-w-xl w-full">
                <div class="flex justify-between">
                    <h3 class="text-lg font-bold">Donation #${d.donation_id}</h3>
                    <button onclick="this.parentElement.parentElement.parentElement.remove()">✖</button>
                </div>

                <p class="mt-4">${d.details}</p>

                <div class="mt-4 text-right">
                    <button onclick="openStatusModal(${d.donation_id}, '${d.status}')"
                            class="px-4 py-2 bg-violet-700 text-white rounded-md">
                        Change Status
                    </button>
                </div>
            </div>
        </div>`;

        $("body").append(html);
    });
}

/* --------------------------
   STATUS MODAL
----------------------------*/
function openStatusModal(id, current) {
    const html = `
    <div class="fixed inset-0 bg-black/40 flex items-center justify-center z-50">
      <div class="bg-white p-6 rounded-xl max-w-sm w-full">
        <h3 class="font-bold text-lg">Update Donation Status</h3>

        <select id="statusSel" class="w-full mt-4 border p-2 rounded-md">
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="in_progress">In Progress</option>
          <option value="completed">Completed</option>
          <option value="rejected">Rejected</option>
        </select>

        <div class="mt-4 text-right">
          <button onclick="$(this).closest('.fixed').remove()" class="px-3 py-2">Cancel</button>
          <button class="px-4 py-2 bg-violet-700 text-white rounded-md"
                  onclick="saveStatus(${id})">
              Save
          </button>
        </div>
      </div>
    </div>`;

    $("body").append(html);
    $("#statusSel").val(current);
}

function saveStatus(id) {
    const status = $("#statusSel").val();

    apiPOST("update_status", { donation_id:id, status }).done(res => {
        if (!res.success) return alert(res.error);
        $(".fixed").remove();
        loadDonations(true);
        loadStats();
    });
}

/* --------------------------
   EVENTS
----------------------------*/
$("#refreshBtn").on("click", ()=> loadDonations(true));
$("#loadMoreBtn").on("click", ()=> loadDonations());
$(document).ready(()=> {
    loadDonations(true);
    loadStats();
});
</script>

</body>
</html>
