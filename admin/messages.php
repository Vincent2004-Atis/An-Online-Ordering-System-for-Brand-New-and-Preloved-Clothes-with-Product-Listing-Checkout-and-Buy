<?php
require_once '../includes/security.php';
require_once '../middleware/auth.php';
requireAdmin();
require_once '../config/database.php';
$db = getDB();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Customer Messages — OrderSync Admin</title>
<link rel="stylesheet" href="../css/style.css">
<link rel="stylesheet" href="../css/admin.css">
<style>
.messages-layout {
  display: grid;
  grid-template-columns: 320px 1fr;
  gap: 0;
  height: calc(100vh - 64px);
  overflow: hidden;
}
.conv-sidebar {
  background: #fff;
  border-right: 1px solid var(--border);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.conv-sidebar-head {
  padding: 16px 20px;
  border-bottom: 1px solid var(--border);
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-shrink: 0;
}
.conv-sidebar-title { font-weight: 700; font-size: .88rem; color: var(--text); }
.conv-list { overflow-y: auto; flex: 1; }
.conv-item {
  padding: 14px 20px;
  border-bottom: 1px solid var(--border);
  cursor: pointer;
  transition: background .15s;
}
.conv-item:hover  { background: #f8fafc; }
.conv-item.active { background: #eff6ff; border-left: 3px solid var(--blue); padding-left: 17px; }
.conv-item.unread { background: #fefce8; }
.conv-item-name    { font-weight: 700; font-size: .85rem; color: var(--text); margin-bottom: 2px; }
.conv-item-subject { font-size: .76rem; color: var(--text-2); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.conv-item-meta    { display: flex; justify-content: space-between; align-items: center; margin-top: 5px; }
.conv-item-time    { font-size: .7rem; color: var(--text-3); }
.conv-item-badge   { background: var(--blue); color: #fff; font-size: .62rem; font-weight: 800; padding: 1px 7px; border-radius: 999px; }
.conv-empty        { padding: 40px 20px; text-align: center; color: var(--text-3); font-size: .85rem; }

.chat-main {
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #f8fafc;
}
.chat-placeholder {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  color: var(--text-3);
  gap: 12px;
}
.chat-placeholder-icon { font-size: 3rem; opacity: .4; }
.chat-header {
  background: #fff;
  border-bottom: 1px solid var(--border);
  padding: 14px 24px;
  display: flex;
  align-items: center;
  gap: 12px;
  flex-shrink: 0;
}
.chat-header-info { flex: 1; }
.chat-header-name { font-weight: 700; font-size: .92rem; color: var(--text); }
.chat-header-sub  { font-size: .76rem; color: var(--text-3); margin-top: 2px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.chat-order-tag   { background: #eff6ff; color: var(--blue); font-size: .7rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; }
.chat-status-tag  { font-size: .7rem; font-weight: 700; padding: 2px 8px; border-radius: 6px; }
.chat-status-open   { background: #dcfce7; color: #16a34a; }
.chat-status-closed { background: #f1f5f9; color: var(--text-3); }

/* Clickable name link style */
.customer-name-link {
  color: inherit;
  text-decoration: none;
  border-bottom: 1.5px dashed rgba(0,0,0,.2);
  transition: border-color .15s, color .15s;
}
.customer-name-link:hover {
  color: var(--blue);
  border-bottom-color: var(--blue);
}

/* Avatar photo */
.bubble-avatar img {
  width: 100%; height: 100%;
  object-fit: cover; border-radius: 50%; display: block;
}
.bubble-avatar.clickable {
  cursor: pointer;
  transition: transform .15s, box-shadow .15s;
}
.bubble-avatar.clickable:hover {
  transform: scale(1.1);
  box-shadow: 0 2px 8px rgba(0,0,0,.2);
}

/* Sidebar avatar */
.conv-avatar {
  width: 36px; height: 36px; border-radius: 50%; flex-shrink: 0;
  display: flex; align-items: center; justify-content: center;
  font-size: .8rem; font-weight: 800;
  background: linear-gradient(135deg,#2e6ee6,#7c3aed); color: #fff;
  overflow: hidden; cursor: pointer;
  transition: transform .15s;
}
.conv-avatar:hover { transform: scale(1.08); }
.conv-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }

.chat-messages {
  flex: 1;
  overflow-y: auto;
  padding: 20px 24px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.bubble-row { display: flex; gap: 8px; }
.bubble-row.admin { flex-direction: row-reverse; }
.bubble-avatar {
  width: 32px; height: 32px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  font-size: .72rem; font-weight: 800; flex-shrink: 0;
  margin-top: auto;
}
.bubble-avatar.customer { background: linear-gradient(135deg,#2e6ee6,#7c3aed); color: #fff; }
.bubble-avatar.admin    { background: linear-gradient(135deg,#0a1628,#1a2a4a); color: #f0a500; }
.bubble-body { max-width: 65%; }
.bubble {
  padding: 10px 14px;
  border-radius: 16px;
  font-size: .84rem;
  line-height: 1.55;
  word-break: break-word;
}
.bubble.customer {
  background: #fff;
  color: var(--text);
  border-bottom-left-radius: 4px;
  box-shadow: 0 1px 4px rgba(0,0,0,.06);
}
.bubble.admin {
  background: linear-gradient(135deg,#1a2a4a,#243660);
  color: #fff;
  border-bottom-right-radius: 4px;
}
.bubble-time { font-size: .68rem; color: var(--text-3); margin-top: 4px; padding: 0 4px; }
.bubble-row.admin .bubble-time { text-align: right; }
.bubble-sender { font-size: .68rem; font-weight: 700; color: var(--text-3); margin-bottom: 3px; padding: 0 4px; }
.bubble-row.admin .bubble-sender { text-align: right; }

.chat-input-wrap {
  background: #fff;
  border-top: 1px solid var(--border);
  padding: 14px 20px;
  display: flex;
  gap: 10px;
  align-items: flex-end;
  flex-shrink: 0;
}
.chat-textarea {
  flex: 1;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  padding: 10px 14px;
  font-family: inherit;
  font-size: .85rem;
  resize: none;
  outline: none;
  transition: border-color .2s;
  max-height: 120px;
  line-height: 1.5;
}
.chat-textarea:focus { border-color: var(--blue); box-shadow: 0 0 0 3px rgba(37,99,235,.08); }
.chat-send {
  background: linear-gradient(135deg,#1a2a4a,#2e6ee6);
  color: #fff;
  border: none;
  border-radius: 10px;
  padding: 10px 22px;
  font-weight: 700;
  font-size: .85rem;
  cursor: pointer;
  transition: opacity .2s;
  white-space: nowrap;
  min-width: 90px;
}
.chat-send:hover    { opacity: .88; }
.chat-send:disabled { opacity: .5; cursor: not-allowed; }
.sending-dot {
  display: inline-block; width: 6px; height: 6px;
  border-radius: 50%; background: #fff;
  animation: blink 1.2s infinite; margin: 0 2px;
}
.sending-dot:nth-child(2) { animation-delay: .2s; }
.sending-dot:nth-child(3) { animation-delay: .4s; }
@keyframes blink { 0%,80%,100%{opacity:.2} 40%{opacity:1} }
</style>
</head>
<body>
<div class="admin-layout">
  <?php include 'includes/sidebar.php'; ?>
  <div class="admin-content" style="display:flex;flex-direction:column;overflow:hidden;">

    <div class="admin-topbar">
      <span class="admin-topbar-title">💬 Customer Messages</span>
      <div class="admin-topbar-actions">
        <span class="badge badge-blue" id="totalUnreadBadge" style="display:none;">0 unread</span>
      </div>
    </div>

    <div class="messages-layout">
      <!-- Sidebar -->
      <div class="conv-sidebar">
        <div class="conv-sidebar-head">
          <span class="conv-sidebar-title">Conversations</span>
          <span class="badge badge-gray" id="convCount">0</span>
        </div>
        <div class="conv-list" id="convList">
          <div class="conv-empty">Loading conversations...</div>
        </div>
      </div>

      <!-- Chat area -->
      <div class="chat-main" id="chatMain">
        <div class="chat-placeholder">
          <div class="chat-placeholder-icon">💬</div>
          <div style="font-weight:600;color:var(--text-2);">Select a conversation</div>
          <div style="font-size:.82rem;">Click a customer on the left to reply</div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
const API = '/Marguax_Collection/api/chat.php';
let activeConvoId = null;
let pollTimer     = null;

// ── POST helper (form-encoded, XAMPP-safe) ────────────────────────
async function chatPost(params) {
  const res = await fetch(API, {
    method:  'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body:    new URLSearchParams(params).toString()
  });
  return res.json();
}

// ── Load sidebar conversations ────────────────────────────────────
async function loadConversations() {
  try {
    const res  = await fetch(API + '?action=get_conversations');
    const data = await res.json();
    if (!data.success) return;

    const list        = document.getElementById('convList');
    const badge       = document.getElementById('totalUnreadBadge');
    const countBadge  = document.getElementById('convCount');
    const totalUnread = data.conversations.reduce((s,c) => s + (parseInt(c.unread_count)||0), 0);

    countBadge.textContent = data.conversations.length;
    badge.textContent      = totalUnread + ' unread';
    badge.style.display    = totalUnread > 0 ? 'inline' : 'none';

    if (!data.conversations.length) {
      list.innerHTML = '<div class="conv-empty">No customer messages yet.</div>';
      return;
    }

    list.innerHTML = data.conversations.map(c => {
      const initial = (c.customer_name||'?').charAt(0).toUpperCase();
      const avatarInner = c.profile_photo
        ? `<img src="/Marguax_Collection/${esc(c.profile_photo)}" alt="${esc(c.customer_name)}">`
        : initial;
      return `
      <div class="conv-item ${c.conversation_id == activeConvoId ? 'active' : ''} ${parseInt(c.unread_count) > 0 ? 'unread' : ''}"
           onclick="openConvo(${c.conversation_id})"
           style="display:flex;gap:10px;align-items:flex-start;">
        <a href="view_user.php?id=${c.user_id}" onclick="event.stopPropagation()" title="View profile">
          <div class="conv-avatar">${avatarInner}</div>
        </a>
        <div style="flex:1;min-width:0;">
          <div class="conv-item-name">
            <a href="view_user.php?id=${c.user_id}"
               class="customer-name-link"
               onclick="event.stopPropagation()"
               title="View ${esc(c.customer_name)}'s profile">
              ${esc(c.customer_name)}
            </a>
          </div>
          <div class="conv-item-subject">${esc(c.subject)}${c.order_id ? ' · Order #'+c.order_id : ''}</div>
          <div class="conv-item-meta">
            <span class="conv-item-time">${timeAgo(c.last_message_at || c.created_at)}</span>
            ${parseInt(c.unread_count) > 0 ? `<span class="conv-item-badge">${c.unread_count} new</span>` : ''}
          </div>
        </div>
      </div>`;
    }).join('');
  } catch(e) { console.error('loadConversations error:', e); }
}

// ── Open a conversation ───────────────────────────────────────────
async function openConvo(cid) {
  activeConvoId = cid;
  clearInterval(pollTimer);
  loadConversations();

  try {
    const res  = await fetch(API + '?action=get_messages&conversation_id=' + cid);
    const data = await res.json();
    if (!data.success) {
      alert('Could not load messages: ' + (data.message || 'Unknown error'));
      return;
    }

    const c    = data.conversation;
    const main = document.getElementById('chatMain');
    const headerAvatarInner = c.profile_photo
      ? `<img src="/Marguax_Collection/${esc(c.profile_photo)}" alt="${esc(c.customer_name)}">`
      : (c.customer_name||'?').charAt(0).toUpperCase();

    main.innerHTML = `
      <div class="chat-header">
        <a href="view_user.php?id=${c.user_id}" title="View profile">
          <div class="bubble-avatar customer clickable" style="width:40px;height:40px;font-size:.9rem;">
            ${headerAvatarInner}
          </div>
        </a>
        <div class="chat-header-info">
          <div class="chat-header-name">
            <a href="view_user.php?id=${c.user_id}"
               class="customer-name-link"
               title="View profile">
               ${esc(c.customer_name)}
            </a>
          </div>
          <div class="chat-header-sub">
            <span>${esc(c.subject)}</span>
            ${c.order_id ? `<span class="chat-order-tag">Order #${c.order_id}</span>` : ''}
            <span class="chat-status-tag chat-status-${c.status}">${c.status}</span>
            <span style="color:var(--text-3);">📧 ${esc(c.customer_email || '')}</span>
          </div>
        </div>
      </div>
      <div class="chat-messages" id="chatMessages"></div>
      <div class="chat-input-wrap">
        <textarea class="chat-textarea" id="adminChatInput" rows="2"
          placeholder="Reply to ${esc(c.customer_name)}… (Enter = send, Shift+Enter = new line)"></textarea>
        <button class="chat-send" id="adminSendBtn" onclick="sendAdminMessage()">Send ➤</button>
      </div>
    `;

    document.getElementById('adminChatInput').addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendAdminMessage(); }
    });

    renderMessages(data.messages, c.profile_photo, c.user_id);

    // Poll every 5 s for new customer messages
    pollTimer = setInterval(async () => {
      try {
        const r = await fetch(API + '?action=get_messages&conversation_id=' + cid);
        const d = await r.json();
        if (d.success) renderMessages(d.messages, d.conversation.profile_photo, d.conversation.user_id);
      } catch(e) {}
    }, 5000);

  } catch(e) { console.error('openConvo error:', e); }
}

// ── Render messages ───────────────────────────────────────────────
function renderMessages(messages, profilePhoto, userId) {
  const container = document.getElementById('chatMessages');
  if (!container) return;

  const nearBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 80;

  if (!messages.length) {
    container.innerHTML = '<div style="text-align:center;color:var(--text-3);font-size:.82rem;padding:30px;">No messages yet.</div>';
    return;
  }

  const customerAvatarInner = profilePhoto
    ? `<img src="/Marguax_Collection/${esc(profilePhoto)}" alt="Customer">`
    : '👤';

  container.innerHTML = messages.map(m => {
    const isCustomer = m.sender_type === 'customer';
    const avatarInner = isCustomer ? customerAvatarInner : '⚙';
    const avatarLink  = isCustomer && userId
      ? `<a href="view_user.php?id=${userId}" title="View profile">
           <div class="bubble-avatar customer clickable">${avatarInner}</div>
         </a>`
      : `<div class="bubble-avatar admin">${avatarInner}</div>`;

    return `
    <div class="bubble-row ${m.sender_type}">
      ${avatarLink}
      <div class="bubble-body">
        <div class="bubble-sender">${isCustomer ? 'Customer' : 'You (Admin)'}</div>
        <div class="bubble ${m.sender_type}">${esc(m.message)}</div>
        <div class="bubble-time">${timeAgo(m.created_at)}</div>
      </div>
    </div>`;
  }).join('');

  if (nearBottom) container.scrollTop = container.scrollHeight;
}

// ── Send admin reply ──────────────────────────────────────────────
async function sendAdminMessage() {
  const input = document.getElementById('adminChatInput');
  const btn   = document.getElementById('adminSendBtn');
  if (!input || !btn) return;

  const msg = input.value.trim();
  if (!msg)            { input.focus(); return; }
  if (!activeConvoId)  { alert('No conversation selected.'); return; }

  btn.disabled  = true;
  btn.innerHTML = '<span class="sending-dot"></span><span class="sending-dot"></span><span class="sending-dot"></span>';
  const saved   = input.value;
  input.value   = '';
  input.focus();

  try {
    const data = await chatPost({
      action:          'send_message',
      conversation_id: activeConvoId,
      message:         msg
    });

    if (data.success) {
      const r = await fetch(API + '?action=get_messages&conversation_id=' + activeConvoId);
      const d = await r.json();
      if (d.success) renderMessages(d.messages, d.conversation.profile_photo, d.conversation.user_id);
      loadConversations();
    } else {
      alert('Send failed: ' + (data.message || 'Unknown error'));
      input.value = saved;
    }
  } catch(e) {
    console.error('sendAdminMessage error:', e);
    alert('Network error. Please try again.');
    input.value = saved;
  } finally {
    btn.disabled  = false;
    btn.innerHTML = 'Send ➤';
  }
}

// ── Helpers ───────────────────────────────────────────────────────
function esc(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/\n/g,'<br>');
}
function timeAgo(dateStr) {
  if (!dateStr) return '';
  const diff = Math.floor((Date.now() - new Date(dateStr)) / 1000);
  if (diff < 60)    return 'Just now';
  if (diff < 3600)  return Math.floor(diff/60) + 'm ago';
  if (diff < 86400) return Math.floor(diff/3600) + 'h ago';
  return Math.floor(diff/86400) + 'd ago';
}

// ── Init ──────────────────────────────────────────────────────────
loadConversations();
setInterval(loadConversations, 10000);
</script>
</body>
</html> 
