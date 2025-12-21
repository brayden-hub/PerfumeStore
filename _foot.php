</main>

<footer class="footer-main">
    <div class="footer-container">
        <div class="copyright-left">
            <p>Copyright © <?= date('Y') ?> N°9 Perfume All Rights Reserved.<br>
            Contact us - <a href="mailto:n9perfumestr@gmail.com" class="email">n9perfumestr@gmail.com</a>
            </p>
        </div>  
        <div class="company-middle">
            <h2>Company Information</h2>
            <p>Company Name: N°9 Perfume</p>
            <p>Head Office Address: Jalan Genting Kelang, Setapak, 53300 Kuala Lumpur</p>
            <p>Ph.No.: <a href="tel:+60341450123">(6)03-41450123</a></p>
            <p>Company Name: N°9 Perfume JOHOR BRANCH</p>
            <p>Branch Address: Jalan Segamat / Labis 85000 Segamat, Johor, Malaysia</p>
            <p>Ph.No.: <a href="tel:+6079270801">(6)07-9270801/3</a></p>
            <p>Company Name: N°9 Perfume SABAH BRANCH</p>
            <p>Branch Address: Lot 1, Ground Floor, Jalan Alamesra, 88450 Kota Kinabalu, Sabah, Malaysia</p>
            <p>Ph.No.: <a href="tel:+601110825619">(6)011-10825619</a></p>
        </div>

        <div class="subscribe-right">
            <h2>Subscribe to our newsletter</h2>
            <form action="subscribe.php" method="POST">
                <input type="email" name="email" placeholder="input your email" required>
                <button type="submit">Subscribe</button>
            </form>
            <div class="social-btns">
                <a class="btn facebook" href="https://www.facebook.com/profile.php?id=61583924067649"><i class="fa fa-facebook"></i></a>
                <a class="btn twitter" href="https://x.com/n9perfume96103"><i class="fa fa-twitter"></i></a>
                <a class="btn instagram" href="https://www.instagram.com/n9perfumestr"><i class="fa fa-instagram"></i></a>
            </div>
        </div>
    </div>
</footer>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">

<!-- Chat Widget - Add this before </body> in _foot.php -->
<?php if (isset($_SESSION['user_id']) && (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'Admin')): ?>
<style>
/* Chat Button */
.chat-button {
    position: fixed;
    bottom: 30px;
    right: 30px;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #D4AF37 0%, #F4E4C1 100%);
    border: none;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9998;
    transition: transform 0.3s;
}

.chat-button:hover {
    transform: scale(1.1);
}

.chat-button svg {
    width: 30px;
    height: 30px;
    fill: #fff;
}

.chat-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    display: none;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.chat-badge.active {
    display: flex;
}

/* Chat Window */
.chat-window {
    position: fixed;
    bottom: 100px;
    right: 30px;
    width: 380px;
    height: 550px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.2);
    display: none;
    flex-direction: column;
    z-index: 9999;
    overflow: hidden;
}

.chat-window.active {
    display: flex;
    animation: slideUp 0.3s ease-out;
}

@keyframes slideUp {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

/* Chat Header */
.chat-header {
    background: linear-gradient(135deg, #D4AF37 0%, #F4E4C1 100%);
    color: white;
    padding: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.chat-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
}

.chat-close {
    background: none;
    border: none;
    color: white;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Chat Messages */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px;
    background: #f8f9fa;
}

.message {
    margin-bottom: 15px;
    display: flex;
    align-items: flex-end;
    gap: 10px;
}

.message.sent {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 32px;
    height: 32px;
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

.message.received .message-avatar {
    background: #6c757d;
}

.message-content {
    max-width: 70%;
}

.message-bubble {
    padding: 12px 16px;
    border-radius: 18px;
    word-wrap: break-word;
    line-height: 1.4;
}

.message.received .message-bubble {
    background: white;
    color: #333;
    border-bottom-left-radius: 4px;
}

.message.sent .message-bubble {
    background: #D4AF37;
    color: white;
    border-bottom-right-radius: 4px;
}

.message-time {
    font-size: 11px;
    color: #999;
    margin-top: 4px;
}

/* Chat Input */
.chat-input-section {
    padding: 20px;
    background: white;
    border-top: 1px solid #e0e0e0;
    display: flex;
    gap: 10px;
}

.chat-input-section input {
    flex: 1;
    padding: 12px 16px;
    border: 1px solid #ddd;
    border-radius: 24px;
    font-size: 14px;
    outline: none;
}

.chat-input-section input:focus {
    border-color: #D4AF37;
}

.chat-send {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #D4AF37;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.3s;
}

.chat-send:hover {
    background: #c19f2f;
}

.chat-send svg {
    width: 20px;
    height: 20px;
    fill: white;
}

.chat-messages::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.chat-messages::-webkit-scrollbar-thumb {
    background: #D4AF37;
    border-radius: 3px;
}
</style>

<!-- Chat Button -->
<button class="chat-button" id="chatButton">
    <svg viewBox="0 0 24 24">
        <path d="M20 2H4c-1.1 0-2 .9-2 2v18l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm0 14H6l-2 2V4h16v12z"/>
    </svg>
    <span class="chat-badge" id="chatBadge">0</span>
</button>

<!-- Chat Window -->
<div class="chat-window" id="chatWindow">
    <div class="chat-header">
        <h3>Customer Support</h3>
        <button class="chat-close" id="chatClose">&times;</button>
    </div>
    
    <div class="chat-messages" id="chatMessages">
        <!-- Messages will be loaded here -->
    </div>
    
    <div class="chat-input-section">
        <input type="text" id="chatInput" placeholder="Type your message..." />
        <button class="chat-send" id="chatSend">
            <svg viewBox="0 0 24 24">
                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
            </svg>
        </button>
    </div>
</div>

<script>
let chatSessionId = null;
let lastMessageId = 0;
let chatPollInterval = null;
let isChatOpen = false;

$(document).ready(function() {
    // Check unread count on page load
    checkUnreadCount();
    
    // Toggle chat window
    $('#chatButton').click(function() {
        isChatOpen = !isChatOpen;
        $('#chatWindow').toggleClass('active');
        
        if (isChatOpen) {
            startChat();
            $('#chatInput').focus();
        } else {
            stopChatPolling();
        }
    });
    
    $('#chatClose').click(function() {
        isChatOpen = false;
        $('#chatWindow').removeClass('active');
        stopChatPolling();
    });
    
    // Send message
    $('#chatSend').click(sendChatMessage);
    $('#chatInput').keypress(function(e) {
        if (e.which === 13) {
            sendChatMessage();
        }
    });
    
    // Poll for unread count every 10 seconds
    setInterval(checkUnreadCount, 10000);
});

function checkUnreadCount() {
    if (isChatOpen) return; // Don't check if chat is open
    
    $.get('/api/chat_unread_count.php', function(res) {
        if (res.success && res.unread > 0) {
            $('#chatBadge').text(res.unread).addClass('active');
        } else {
            $('#chatBadge').removeClass('active');
        }
    }, 'json');
}

function startChat() {
    $.post('/api/chat_start.php', function(res) {
        if (res.success) {
            chatSessionId = res.session_id;
            loadChatMessages();
            startChatPolling();
        } else {
            alert('Failed to start chat: ' + (res.message || 'Unknown error'));
        }
    }, 'json').fail(function() {
        alert('Connection error. Please try again.');
    });
}

function sendChatMessage() {
    const message = $('#chatInput').val().trim();
    if (!message || !chatSessionId) return;
    
    $.post('/api/chat_send.php', {
        session_id: chatSessionId,
        message: message
    }, function(res) {
        if (res.success) {
            $('#chatInput').val('');
            loadChatMessages();
        } else {
            alert('Failed to send message: ' + (res.message || 'Unknown error'));
        }
    }, 'json').fail(function() {
        alert('Connection error. Please try again.');
    });
}

function loadChatMessages() {
    if (!chatSessionId) return;
    
    $.get('/api/chat_get_messages.php', {
        session_id: chatSessionId,
        last_id: lastMessageId
    }, function(res) {
        if (res.success && res.messages && res.messages.length > 0) {
            res.messages.forEach(function(msg) {
                appendChatMessage(msg);
                lastMessageId = Math.max(lastMessageId, msg.MessageID);
            });
            scrollChatToBottom();
            $('#chatBadge').removeClass('active'); // Clear badge
        }
    }, 'json');
}

function appendChatMessage(msg) {
    const isOwn = msg.SenderType === 'customer';
    const time = new Date(msg.CreatedAt).toLocaleTimeString('en-US', {
        hour: '2-digit',
        minute: '2-digit'
    });
    
    const initial = msg.SenderName ? msg.SenderName.charAt(0).toUpperCase() : 'U';
    
    const messageHtml = `
        <div class="message ${isOwn ? 'sent' : 'received'}">
            <div class="message-avatar">${initial}</div>
            <div class="message-content">
                <div class="message-bubble">${escapeHtml(msg.Message)}</div>
                <div class="message-time">${time}</div>
            </div>
        </div>
    `;
    
    $('#chatMessages').append(messageHtml);
}

function startChatPolling() {
    stopChatPolling();
    chatPollInterval = setInterval(loadChatMessages, 3000); // Poll every 3 seconds
}

function stopChatPolling() {
    if (chatPollInterval) {
        clearInterval(chatPollInterval);
        chatPollInterval = null;
    }
}

function scrollChatToBottom() {
    const container = $('#chatMessages');
    container.scrollTop(container[0].scrollHeight);
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
<?php endif; ?>

</body>
</html>