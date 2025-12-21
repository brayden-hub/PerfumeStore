<?php
require '../_base.php';

// Security Check
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin') {
    redirect('/');
}

$_title = 'Chat Management - Nº9 Perfume Admin';
include '../_head.php';
?>

<script>
    $(document).ready(function() {
        window.scrollTo(0, 0);
    });
</script>

<style>
* { margin: 0; padding: 0; box-sizing: border-box; }

.chat-container {
    display: flex;
    height: calc(100vh - 80px);
    max-width: 1400px;
    margin: 20px auto;
    background: white;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
    border-radius: 12px;
    overflow: hidden;
}

/* Sessions List */
.sessions-panel {
    width: 350px;
    border-right: 1px solid #e0e0e0;
    display: flex;
    flex-direction: column;
}

.panel-header {
    padding: 20px;
    background: linear-gradient(135deg, #D4AF37 0%, #F4E4C1 100%);
    color: white;
}

.panel-header h2 {
    font-size: 20px;
    margin-bottom: 5px;
}

.panel-header p {
    font-size: 13px;
    opacity: 0.9;
}

.sessions-list {
    flex: 1;
    overflow-y: auto;
}

.session-item {
    padding: 15px 20px;
    border-bottom: 1px solid #f0f0f0;
    cursor: pointer;
    transition: background 0.2s;
    position: relative;
}

.session-item:hover {
    background: #f8f9fa;
}

.session-item.active {
    background: #fff8e1;
    border-left: 3px solid #D4AF37;
}

.session-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.customer-name {
    font-weight: 600;
    font-size: 15px;
}

.session-time {
    font-size: 12px;
    color: #999;
}

.last-message {
    font-size: 13px;
    color: #666;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.unread-badge {
    position: absolute;
    top: 15px;
    right: 15px;
    background: #dc3545;
    color: white;
    border-radius: 12px;
    padding: 2px 8px;
    font-size: 11px;
    font-weight: bold;
}

/* Chat Area */
.chat-area {
    flex: 1;
    display: flex;
    flex-direction: column;
}

.chat-header-area {
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
    background: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-customer-info {
    display: flex;
    align-items: center;
    gap: 15px;
}

.customer-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: #D4AF37;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 18px;
}

.customer-details h3 {
    font-size: 16px;
    margin-bottom: 3px;
}

.customer-details p {
    font-size: 13px;
    color: #666;
}

.close-chat-btn {
    padding: 8px 16px;
    background: #dc3545;
    color: white;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 14px;
}

.close-chat-btn:hover {
    background: #c82333;
}

.chat-messages-area {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
}

.empty-state {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #999;
}

.empty-state svg {
    width: 80px;
    height: 80px;
    margin-bottom: 20px;
    opacity: 0.5;
}

.message {
    margin-bottom: 20px;
    display: flex;
    gap: 10px;
}

.message.admin {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #D4AF37;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 14px;
    flex-shrink: 0;
}

.message.customer .message-avatar {
    background: #6c757d;
}

.message-content {
    max-width: 60%;
}

.message-bubble {
    padding: 12px 16px;
    border-radius: 18px;
    word-wrap: break-word;
    line-height: 1.5;
}

.message.customer .message-bubble {
    background: white;
    color: #333;
    border-bottom-left-radius: 4px;
}

.message.admin .message-bubble {
    background: #D4AF37;
    color: white;
    border-bottom-right-radius: 4px;
}

.message-meta {
    display: flex;
    gap: 10px;
    margin-top: 5px;
    font-size: 12px;
    color: #999;
}

.message.admin .message-meta {
    justify-content: flex-end;
}

.chat-input-area {
    padding: 20px;
    background: white;
    border-top: 1px solid #e0e0e0;
}

.chat-input-wrapper {
    display: flex;
    gap: 10px;
}

.chat-input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 24px;
    font-size: 14px;
    outline: none;
    resize: none;
    font-family: inherit;
    max-height: 120px;
}

.chat-input:focus {
    border-color: #D4AF37;
}

.send-button {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    background: #D4AF37;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
}

.send-button:hover {
    background: #c19f2f;
}

.send-button svg {
    width: 22px;
    height: 22px;
    fill: white;
}

/* Scrollbar */
.sessions-list::-webkit-scrollbar,
.chat-messages-area::-webkit-scrollbar {
    width: 6px;
}

.sessions-list::-webkit-scrollbar-track,
.chat-messages-area::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.sessions-list::-webkit-scrollbar-thumb,
.chat-messages-area::-webkit-scrollbar-thumb {
    background: #D4AF37;
    border-radius: 3px;
}
</style>

<div class="chat-container">
    <!-- Sessions Panel -->
    <div class="sessions-panel">
        <div class="panel-header">
            <h2>Customer Chats</h2>
            <p id="sessionCount">0 active conversations</p>
        </div>
        <div class="sessions-list" id="sessionsList">
            <div style="padding: 40px 20px; text-align: center; color: #999;">Loading chats...</div>
        </div>
    </div>
    
    <!-- Chat Area -->
    <div class="chat-area">
        <div id="emptyState" class="empty-state">
            <svg viewBox="0 0 24 24" fill="currentColor">
                <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
            </svg>
            <h3>No conversation selected</h3>
            <p>Choose a customer from the list to start chatting</p>
        </div>
        
        <div id="chatView" style="display: none; height: 100%; flex-direction: column;">
            <div class="chat-header-area">
                <div class="chat-customer-info">
                    <div class="customer-avatar" id="currentAvatar">C</div>
                    <div class="customer-details">
                        <h3 id="currentCustomer">Customer Name</h3>
                        <p id="currentEmail">customer@email.com</p>
                    </div>
                </div>
                <button class="close-chat-btn" id="closeChat">Close Chat</button>
            </div>
            
            <div class="chat-messages-area" id="chatMessages">
                <!-- Messages will be loaded here -->
            </div>
            
            <div class="chat-input-area">
                <div class="chat-input-wrapper">
                    <textarea class="chat-input" id="messageInput" rows="1" placeholder="Type your message..."></textarea>
                    <button class="send-button" id="sendButton">
                        <svg viewBox="0 0 24 24">
                            <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentSessionId = null;
let lastMessageId = 0;
let pollInterval = null;

$(document).ready(function() {
    loadSessions();
    startSessionPolling();
    
    $('#sendButton').click(sendMessage);
    $('#messageInput').keypress(function(e) {
        if (e.which === 13 && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    
    $('#closeChat').click(function() {
        if (!currentSessionId) return;
        
        if (confirm('Are you sure you want to close this chat?')) {
            $.post('/api/chat_close.php', { session_id: currentSessionId }, function(res) {
                if (res.success) {
                    $('#emptyState').show();
                    $('#chatView').hide();
                    currentSessionId = null;
                    loadSessions();
                }
            }, 'json');
        }
    });
    
    // Auto-resize textarea
    $('#messageInput').on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});

function loadSessions() {
    $.get('/api/chat_get_sessions.php', function(res) {
        if (res.success) {
            renderSessions(res.sessions);
            $('#sessionCount').text(`${res.sessions.length} active conversation${res.sessions.length !== 1 ? 's' : ''}`);
        }
    }, 'json');
}

function renderSessions(sessions) {
    const container = $('#sessionsList');
    container.empty();
    
    if (sessions.length === 0) {
        container.html('<div style="padding: 40px 20px; text-align: center; color: #999;">No active chats</div>');
        return;
    }
    
    sessions.forEach(function(session) {
        const time = formatTime(session.LastMessageAt);
        const unreadBadge = session.UnreadCount > 0 ? 
            `<span class="unread-badge">${session.UnreadCount}</span>` : '';
        
        const html = `
            <div class="session-item ${currentSessionId === session.SessionID ? 'active' : ''}" 
                 data-session-id="${session.SessionID}"
                 data-customer-name="${escapeHtml(session.CustomerName)}"
                 data-customer-email="${escapeHtml(session.CustomerEmail)}">
                <div class="session-header">
                    <span class="customer-name">${escapeHtml(session.CustomerName)}</span>
                    <span class="session-time">${time}</span>
                </div>
                <div class="last-message">${escapeHtml(session.LastMessage || 'No messages yet')}</div>
                ${unreadBadge}
            </div>
        `;
        
        container.append(html);
    });
    
    $('.session-item').click(function() {
        const sessionId = $(this).data('session-id');
        const customerName = $(this).data('customer-name');
        const customerEmail = $(this).data('customer-email');
        selectSession(sessionId, customerName, customerEmail);
    });
}

function selectSession(sessionId, customerName, customerEmail) {
    currentSessionId = sessionId;
    lastMessageId = 0;
    
    $('.session-item').removeClass('active');
    $(`.session-item[data-session-id="${sessionId}"]`).addClass('active');
    
    $('#emptyState').hide();
    $('#chatView').show().css('display', 'flex');
    
    $('#currentCustomer').text(customerName);
    $('#currentEmail').text(customerEmail);
    $('#currentAvatar').text(customerName.charAt(0).toUpperCase());
    
    $('#chatMessages').empty();
    loadMessages();
    startMessagePolling();
}

function sendMessage() {
    const message = $('#messageInput').val().trim();
    if (!message || !currentSessionId) return;
    
    $.post('/api/chat_send.php', {
        session_id: currentSessionId,
        message: message
    }, function(res) {
        if (res.success) {
            $('#messageInput').val('').css('height', 'auto');
            loadMessages();
        } else {
            alert('Failed to send: ' + (res.message || 'Unknown error'));
        }
    }, 'json');
}

function loadMessages() {
    if (!currentSessionId) return;
    
    $.get('/api/chat_get_messages.php', {
        session_id: currentSessionId,
        last_id: lastMessageId
    }, function(res) {
        if (res.success && res.messages && res.messages.length > 0) {
            res.messages.forEach(function(msg) {
                appendMessage(msg);
                lastMessageId = Math.max(lastMessageId, msg.MessageID);
            });
            scrollToBottom();
            loadSessions(); // Refresh session list
        }
    }, 'json');
}

function appendMessage(msg) {
    const isAdmin = msg.SenderType === 'admin';
    const time = new Date(msg.CreatedAt).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const messageHtml = `
        <div class="message ${isAdmin ? 'admin' : 'customer'}">
            <div class="message-avatar">${msg.SenderName.charAt(0).toUpperCase()}</div>
            <div class="message-content">
                <div class="message-bubble">${escapeHtml(msg.Message)}</div>
                <div class="message-meta">
                    <span>${msg.SenderName}</span>
                    <span>${time}</span>
                </div>
            </div>
        </div>
    `;
    
    $('#chatMessages').append(messageHtml);
}

function startMessagePolling() {
    stopMessagePolling();
    pollInterval = setInterval(loadMessages, 3000);
}

function stopMessagePolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

function startSessionPolling() {
    setInterval(loadSessions, 5000); // Refresh sessions every 5 seconds
}

function scrollToBottom() {
    const container = $('#chatMessages');
    container.scrollTop(container[0].scrollHeight);
}

function formatTime(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) return 'Just now';
    if (diff < 3600000) return `${Math.floor(diff / 60000)}m ago`;
    if (diff < 86400000) return `${Math.floor(diff / 3600000)}h ago`;
    return date.toLocaleDateString();
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<?php include '../_foot.php'; ?>