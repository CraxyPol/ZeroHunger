<?php
// donor_dashboard.php
session_start();
require_once "db.php"; // expects $conn (mysqli) and session login setting $_SESSION['user_id'] and $_SESSION['user_type']

// Require logged-in donor (or admin can view but we'll treat as donor)
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = (int)$_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'Donor';

// Fetch basic user profile for display (non-sensitive)
$stmt = $conn->prepare("SELECT user_id, name, email, phone_number, profile_photo FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
$me = $res->fetch_assoc() ?: [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Donor Dashboard — Zero Hunger</title>

  <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    /* Reuse your enhanced styles with a slight tweak for interactive elements */
    :root{
      --p1:#c7d2fe; --p2:#e9d5ff; --violet:#6d28d9; --muted:#6b7280;
    }
    body{ background: linear-gradient(135deg,var(--p1) 0%, #ede9fe 60%, var(--p2) 100%); font-family: Inter, system-ui, -apple-system, "Segoe UI", Roboto, Arial; color:#0f172a; }
    .glass { background: rgba(255,255,255,0.92); border:1px solid rgba(255,255,255,0.6); backdrop-filter: blur(6px); }
    .fade-in { animation: fadeIn .22s ease both; } @keyframes fadeIn { from{opacity:0;transform:translateY(6px)} to{opacity:1;transform:none} }
    .charity-card-enter { opacity:0; transform: translateY(20px); animation: cardEnter .4s cubic-bezier(.16,1,.3,1) forwards; } @keyframes cardEnter { to{opacity:1;transform:none} }
  </style>
</head>
<body class="antialiased">
  <header class="sticky top-0 z-40 glass shadow-sm">
    <div class="max-w-7xl mx-auto px-5 py-3 flex items-center justify-between">
      <div class="flex items-center gap-3">
        <img src="images/zero.png" alt="logo" class="h-10 w-10 rounded-lg object-cover border-2 border-violet-300 shadow-sm" onerror="this.style.display='none'">
        <div>
          <div class="text-violet-700 font-bold text-lg">Zero Hunger</div>
          <div class="text-xs text-gray-500">Donor Dashboard</div>
        </div>
      </div>
      <div class="flex items-center gap-3">
        <input id="searchCharity" class="border rounded-md px-3 py-2 text-sm w-40 sm:w-64" placeholder="Search charity..." oninput="filterCharities()">
        <select id="charityFilter" class="border rounded-md px-3 py-2 text-sm" onchange="filterCharities()">
          <option value="all">All Status</option>
          <option value="active">Active</option>
          <option value="verified">Verified</option>
          <option value="busy">Busy</option>
        </select>
        <div class="flex items-center gap-3">
          <img id="donorProfilePic" src="<?php echo htmlspecialchars($me['profile_photo']?:'images/profile.jpg'); ?>" class="h-9 w-9 rounded-full object-cover border-2 border-violet-200" title="View profile" style="cursor:pointer" onclick="openViewProfileModal()">
        </div>
      </div>
    </div>
  </header>

  <main class="max-w-7xl mx-auto px-5 py-8">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
      <div class="stat-card glass rounded-xl p-4 cursor-pointer" id="cardTotalDonations">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-600">Total Donations</div>
            <div id="statDonations" class="count-up text-2xl font-bold text-violet-700 mt-1">0</div>
          </div>
          <div class="w-12 h-12 bg-violet-100 rounded-lg flex items-center justify-center">
            <i class="fa-solid fa-hand-holding-heart text-violet-600 text-xl"></i>
          </div>
        </div>
      </div>
      <div class="stat-card glass rounded-xl p-4 cursor-pointer">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-600">Active Requests</div>
            <div id="statRequests" class="count-up text-2xl font-bold text-blue-600 mt-1">0</div>
          </div>
          <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
            <i class="fa-solid fa-clock text-blue-600 text-xl"></i>
          </div>
        </div>
      </div>
      <div class="stat-card glass rounded-xl p-4 cursor-pointer">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-600">People Helped</div>
            <div id="statPeople" class="count-up text-2xl font-bold text-green-600 mt-1">0</div>
          </div>
          <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
            <i class="fa-solid fa-users text-green-600 text-xl"></i>
          </div>
        </div>
      </div>
      <div class="stat-card glass rounded-xl p-4 cursor-pointer">
        <div class="flex items-center justify-between">
          <div>
            <div class="text-sm text-gray-600">Impact Score</div>
            <div id="statImpact" class="count-up text-2xl font-bold text-orange-600 mt-1">0%</div>
          </div>
          <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
            <i class="fa-solid fa-star text-orange-600 text-xl"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
      <section class="lg:col-span-3 space-y-6">
        <div class="glass rounded-xl p-5 flex justify-between items-center">
          <div>
            <h2 class="text-2xl font-extrabold text-violet-700">Charitable Institutions</h2>
            <p class="text-sm text-gray-500 mt-1">Browse and connect with trusted charity partners.</p>
          </div>
          <div>
            <button id="refreshCharities" class="px-4 py-2 bg-violet-100 text-violet-700 rounded-lg">Refresh</button>
          </div>
        </div>

        <div id="charityGrid" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6"></div>

      </section>

      <aside class="space-y-6">
        <div class="glass rounded-xl p-5">
          <h4 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-violet-600"></i>
            Recent Activity
          </h4>
          <div id="activityList" class="space-y-3"></div>
          <div class="mt-4 pt-4 border-t border-gray-200">
            <button id="viewAllActivity" class="text-sm text-gray-600">View All Activity</button>
          </div>
        </div>

        <div class="glass rounded-xl p-5">
          <h4 class="font-bold text-gray-800 text-lg mb-4 flex items-center gap-2">
            <i class="fa-solid fa-bolt text-violet-600"></i>
            Quick Actions
          </h4>
          <div class="space-y-3">
            <button id="createDonationBtn" class="w-full px-4 py-3 bg-green-700 text-white rounded-lg">Create New Donation Request</button>
            <button id="openMessagesBtn" class="w-full px-4 py-3 bg-white border-2 border-gray-300 text-gray-700 rounded-lg">Messages</button>
          </div>
        </div>

        <div class="glass rounded-xl p-4 cursor-pointer" onclick="openViewProfileModal()">
          <h4 class="font-semibold text-violet-700 flex items-center gap-2">
            <i class="fa-solid fa-user"></i> My Profile
          </h4>
          <div class="mt-3 flex items-center gap-3">
            <img id="donorPic" src="<?php echo htmlspecialchars($me['profile_photo']?:'images/profile.jpg'); ?>" class="h-20 w-20 rounded-md object-cover border-2 border-violet-200">
            <div>
              <div id="donorName" class="font-semibold"><?php echo htmlspecialchars($me['name']?:'—'); ?></div>
              <div class="text-sm text-gray-500" id="donorEmail"><?php echo htmlspecialchars($me['email']?:'—'); ?></div>
              <div class="text-sm text-gray-500" id="donorPhone"><?php echo htmlspecialchars($me['phone_number']?:'—'); ?></div>
            </div>
          </div>
        </div>
      </aside>
    </div>
  </main>

  <!-- Minimal Modals (profile, donation form, chat, activity view) -->
  <div id="modals"></div>

  <script>
  // jQuery-based dynamic behavior
  $(function(){
    const meId = <?php echo json_encode($user_id); ?>;

    // helper: show toast
    function toast(msg, timeout = 1800) {
      const t = $('<div class="fixed bottom-6 right-6 bg-violet-700 text-white px-4 py-2 rounded-lg shadow-lg z-50"></div>').text(msg);
      $('body').append(t);
      setTimeout(()=> t.fadeOut(300, ()=> t.remove()), timeout);
    }

    // load charities
    function loadCharities(){
      $('#charityGrid').html('<div class="col-span-full p-6 text-center text-gray-500">Loading…</div>');
      $.getJSON('api/charities.php')
        .done(function(res){
          if(!res.success){ toast('Failed to load charities'); return; }
          renderCharities(res.charities);
        })
        .fail(function(){ toast('Network error'); });
    }

    function renderCharities(list) {
      if(!list || !list.length) {
        $('#charityGrid').html('<p class="text-center text-gray-500 col-span-full py-10">No charities yet</p>');
        return;
      }
      const html = list.map((c,i)=> {
        const statusClass = c.status === 'active' ? 'bg-green-100 text-green-700' : c.status === 'verified' ? 'bg-blue-100 text-blue-700' : 'bg-yellow-100 text-yellow-700';
        return `
          <div class="charity-card-enter glass rounded-xl p-4 cursor-pointer" style="animation-delay:${i*40}ms">
            <div class="flex justify-between items-start">
              <img src="${c.img||'images/som.png'}" class="h-16 w-16 rounded-lg object-cover border border-gray-200">
              <span class="px-3 py-1 pill ${statusClass}">${c.status||'active'}</span>
            </div>
            <div class="mt-3">
              <h4 class="font-semibold text-violet-700">${escapeHtml(c.name)}</h4>
              <p class="text-xs text-gray-500 mt-1 truncate">${escapeHtml(c.about || '').split('. ')[0]}</p>
            </div>
            <div class="mt-3 pt-3 border-t text-sm">
              <div class="flex justify-between items-center">
                <span class="text-xs text-gray-600"><i class="fa-solid fa-star text-yellow-500"></i> ${c.rating.toFixed(1)}</span>
                <span class="text-xs text-gray-600"><i class="fa-solid fa-bullseye text-blue-500"></i> ${escapeHtml(c.focusArea||'')}</span>
              </div>
            </div>
            <div class="mt-4 flex gap-2">
              <button class="profileBtn flex-1 px-3 py-2 bg-violet-100 text-violet-700 rounded-md" data-id="${c.id}"><i class="fa-solid fa-eye"></i> Profile</button>
              <button class="chatBtn flex-1 px-3 py-2 bg-blue-500 text-white rounded-md" data-id="${c.id}"><i class="fa-solid fa-message"></i> Chat</button>
            </div>
          </div>`;
      }).join('');
      $('#charityGrid').html(html);
    }

    // escape html helper
    function escapeHtml(s){ if(!s) return ''; return $('<div>').text(s).html(); }

    // filters
    window.filterCharities = function(){
      const q = $('#searchCharity').val().toLowerCase().trim();
      const status = $('#charityFilter').val();
      $('#charityGrid').children().each(function(){
        const $el = $(this);
        const name = $el.find('h4').text().toLowerCase();
        const about = $el.find('p').text().toLowerCase();
        if( (status === 'all' || $el.find('.pill').text().toLowerCase()==status) && ( !q || name.indexOf(q) !== -1 || about.indexOf(q) !== -1) ) $el.show();
        else $el.hide();
      });
    };

    // initial load
    loadCharities();
    loadStats();
    loadActivity();

    // event handlers (delegated)
    $('#charityGrid').on('click', '.profileBtn', function(){
      const id = $(this).data('id');
      openCharityProfile(id);
    });
    $('#charityGrid').on('click', '.chatBtn', function(){
      const id = $(this).data('id');
      openChat(id);
    });
    $('#refreshCharities').click(loadCharities);
    $('#createDonationBtn').click(()=> openDonationForm());

    // load stats (simple counts)
    function loadStats(){
      $.getJSON('api/donations.php', { action:'stats' })
        .done(function(res){
          if(res.success){
            $('#statDonations').text(res.stats.total_donations || '0');
            $('#statRequests').text(res.stats.active_requests || '0');
            $('#statPeople').text(res.stats.people_helped || '0');
            $('#statImpact').text((res.stats.impact_score||0) + '%');
          }
        }).fail(()=>{});
    }

    // load recent activity
    function loadActivity(){
      $.getJSON('api/activity.php')
        .done(function(res){
          if(!res.success) return;
          const html = res.items.slice(0,5).map(a => `<div class="activity-item p-3 rounded-lg bg-white border">${escapeHtml(a.description)} <div class="text-xs text-gray-400 mt-1">${a.created_at}</div></div>`).join('');
          $('#activityList').html(html || '<div class="text-gray-500">No activity yet</div>');
        });
    }

    // Charity profile modal (simple)
    function openCharityProfile(charityId) {
      $.getJSON('api/charities.php', { id: charityId })
        .done(function(res){
          if(!res.success){ toast('Charity not found'); return; }
          const c = res.charity;
          const html = `
            <div class="fixed inset-0 z-50 flex items-center justify-center">
              <div class="absolute inset-0 bg-black opacity-40"></div>
              <div class="relative bg-white rounded-xl p-6 max-w-2xl w-full z-10">
                <div class="flex justify-between">
                  <h3 class="text-xl font-bold text-violet-700">${escapeHtml(c.name)}</h3>
                  <button class="text-gray-600 closeModal">×</button>
                </div>
                <div class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-4">
                  <img src="${c.img || 'images/som.png'}" class="rounded-md w-full h-36 object-cover md:col-span-1">
                  <div class="md:col-span-2">
                    <p class="text-gray-700">${escapeHtml(c.about)}</p>
                    <p class="mt-2 text-sm text-gray-500">Contact: ${escapeHtml(c.contactName)} • ${escapeHtml(c.phone)} • ${escapeHtml(c.location)}</p>
                    <div class="mt-4 flex gap-2">
                      <button class="px-3 py-2 bg-green-600 text-white rounded-md donateNow">Donate Now</button>
                      <button class="px-3 py-2 bg-blue-500 text-white rounded-md openChatFromModal">Message</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>`;
          const $m = $(html).appendTo('body');
          $m.on('click', '.closeModal', ()=> $m.remove());
          $m.on('click', '.donateNow', ()=> { $m.remove(); openDonationForm(charityId); });
          $m.on('click', '.openChatFromModal', ()=> { $m.remove(); openChat(charityId); });
        });
    }

    // donation form modal
    function openDonationForm(charityId = null){
      // build form
      $.getJSON('api/charities.php')
      .done(function(res){
        const options = res.charities.map(c => `<option value="${c.id}" ${charityId && c.id==charityId ? 'selected' : ''}>${escapeHtml(c.name)}</option>`).join('');
        const html = `
          <div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black opacity-40"></div>
            <div class="relative bg-white rounded-xl p-6 max-w-xl w-full z-10">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-bold text-violet-700">New Donation Request</h3>
                <button class="text-gray-600 closeModal">×</button>
              </div>
              <div class="mt-4 space-y-3">
                <select id="frmCharity" class="w-full border rounded-md p-2"><option value="">Select Charity (optional)</option>${options}</select>
                <textarea id="frmDetails" class="w-full border rounded-md p-2" rows="3" placeholder="Describe your donation"></textarea>
                <input id="frmAddress" class="w-full border rounded-md p-2" placeholder="Pickup address">
                <input id="frmPickup" type="datetime-local" class="w-full border rounded-md p-2">
              </div>
              <div class="mt-4 flex justify-end gap-3">
                <button class="closeModal px-3 py-2 text-sm">Cancel</button>
                <button class="confirm px-4 py-2 bg-violet-700 text-white rounded-md">Submit</button>
              </div>
            </div>
          </div>`;
        const $m = $(html).appendTo('body');
        $m.on('click', '.closeModal', ()=> $m.remove());
        $m.on('click', '.confirm', function(){
          const payload = {
            charity_id: $('#frmCharity').val() || null,
            details: $('#frmDetails').val(),
            pickup_address: $('#frmAddress').val(),
            pickup_time: $('#frmPickup').val()
          };
          if(!payload.details || !payload.pickup_address || !payload.pickup_time){
            alert('Please fill required fields.');
            return;
          }
          $.ajax({
            url: 'api/donations.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action:'create', ...payload }),
            success: function(res){
              if(res.success){
                toast('Donation request submitted');
                $m.remove();
                loadStats();
                loadActivity();
              } else alert('Error: '+(res.error||'Unknown'));
            }
          });
        });
      });
    }

    // open chat (simple)
    function openChat(charityId){
      // create a modal that fetches messages between me and charity (charity owner is represented by a user row)
      $.getJSON('api/messages.php', { action:'fetch', other_id: charityId })
      .done(function(res){
        const messages = res.messages || [];
        const html = `<div class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black opacity-40"></div>
            <div class="relative bg-white rounded-xl p-4 max-w-3xl w-full z-10">
              <div class="flex justify-between items-center">
                <h3 class="text-lg font-bold text-violet-700">Chat</h3>
                <button class="closeModal">×</button>
              </div>
              <div class="mt-3 max-h-80 overflow-y-auto" id="chatBox">${messages.map(m=> `<div class="${m.sender_id==<?php echo $user_id; ?> ? 'text-right' : 'text-left'} p-2"><span class="inline-block p-2 rounded ${m.sender_id==<?php echo $user_id; ?> ? 'bg-violet-600 text-white' : 'bg-gray-100 text-gray-900'}">${escapeHtml(m.message)}</span></div>`).join('')}</div>
              <div class="mt-3 flex gap-2">
                <input id="chatInputBox" class="flex-1 border rounded-md p-2" placeholder="Type a message...">
                <button class="sendBtn px-4 py-2 bg-violet-700 text-white rounded-md">Send</button>
              </div>
            </div></div>`;
        const $m = $(html).appendTo('body');
        $m.on('click', '.closeModal', ()=> $m.remove());
        $m.on('click', '.sendBtn', function(){
          const msg = $('#chatInputBox').val().trim();
          if(!msg) return;
          $.ajax({
            url: 'api/messages.php',
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action:'send', receiver_id: charityId, message: msg }),
            success: function(resp){
              if(resp.success){
                $('#chatBox').append(`<div class="text-right p-2"><span class="inline-block p-2 rounded bg-violet-600 text-white">${escapeHtml(msg)}</span></div>`);
                $('#chatInputBox').val('');
                loadActivity();
              } else alert('Error sending: '+resp.error);
            }
          });
        });
      });
    }

    // open charity chat from modal
    window.openChatFromModal = function() { /* placeholder */ };

    // open & close profile modal (simple)
    window.openViewProfileModal = function(){
      const html = `<div class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="absolute inset-0 bg-black opacity-40"></div>
        <div class="relative bg-white rounded-xl p-6 max-w-md w-full z-10">
          <div class="flex justify-between"><h3 class="text-lg font-bold">My Profile</h3><button class="closeModal">×</button></div>
          <div class="mt-4">
            <img src="<?php echo htmlspecialchars($me['profile_photo']?:'images/profile.jpg'); ?>" class="h-24 w-24 rounded-full object-cover mx-auto">
            <h4 class="text-center mt-3 font-semibold"><?php echo htmlspecialchars($me['name']?:''); ?></h4>
            <p class="text-center text-sm text-gray-500"><?php echo htmlspecialchars($me['email']?:''); ?></p>
            <div class="mt-4 text-sm">
              <p><strong>Phone:</strong> <?php echo htmlspecialchars($me['phone_number']?:''); ?></p>
            </div>
          </div>
        </div></div>`;
      const $m = $(html).appendTo('body');
      $m.on('click', '.closeModal', ()=> $m.remove());
    };

  }); // end jQuery
  </script>
</body>
</html>
