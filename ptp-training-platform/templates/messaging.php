<?php
/**
 * Template: Messaging - PTP Style v23.5
 * Full messaging interface with AJAX support
 */
defined('ABSPATH') || exit;

// Redirect if not logged in
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/?redirect_to=' . urlencode(home_url('/messages/'))));
    exit;
}

$user = wp_get_current_user();
$is_trainer = PTP_User::is_trainer();

// Get conversations
$conversations = array();
if (class_exists('PTP_Messaging') && method_exists('PTP_Messaging', 'get_conversations_for_user')) {
    $conversations = PTP_Messaging::get_conversations_for_user(get_current_user_id());
}

// Get active conversation if specified
$active_conversation = null;
$messages = array();
$conversation_id = isset($_GET['conversation']) ? intval($_GET['conversation']) : 0;

if ($conversation_id && class_exists('PTP_Messaging')) {
    // Get conversation details
    global $wpdb;
    $active_conversation = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}ptp_conversations WHERE id = %d",
        $conversation_id
    ));
    
    if ($active_conversation) {
        // Get messages
        $messages = PTP_Messaging::get_messages($conversation_id);
        
        // Mark as read
        PTP_Messaging::mark_as_read($conversation_id, get_current_user_id());
    }
}
?>

<style>
html, body { overflow-x: hidden !important; max-width: 100vw; }
.ptp-msg-wrap { min-height: 100vh; background: #F8F9FA; overflow-x: hidden; }
.ptp-msg-header { background: linear-gradient(135deg, #0E0F11 0%, #1a1a1a 100%); padding: 30px 20px; }
.ptp-msg-header-inner { max-width: 1200px; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; }
.ptp-msg-title { font-family: 'Oswald', sans-serif; font-size: 24px; font-weight: 700; color: #fff; margin: 0; display: flex; align-items: center; gap: 12px; }
@media (min-width: 600px) { .ptp-msg-title { font-size: 28px; } }
.ptp-msg-back { color: rgba(255,255,255,0.7); text-decoration: none; font-size: 14px; display: flex; align-items: center; gap: 8px; }
.ptp-msg-back:hover { color: #fff; }
.ptp-msg-container { max-width: 1200px; margin: 0 auto; padding: 24px 16px; }
@media (min-width: 600px) { .ptp-msg-container { padding: 24px 20px; } }
.ptp-msg-layout { display: flex; flex-direction: column; height: calc(100vh - 140px); min-height: 400px; }
@media (min-width: 900px) { .ptp-msg-layout { display: grid; grid-template-columns: 340px 1fr; gap: 24px; } }
.ptp-msg-list { background: #fff; border-radius: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; display: flex; flex-direction: column; flex: 1; }
.ptp-msg-list.mobile-hidden { display: none; }
@media (min-width: 900px) { .ptp-msg-list { flex: none; } .ptp-msg-list.mobile-hidden { display: flex; } }
.ptp-msg-list-header { padding: 20px 24px; border-bottom: 1px solid #F3F4F6; }
.ptp-msg-list-title { font-family: 'Oswald', sans-serif; font-size: 18px; font-weight: 600; color: #111827; margin: 0; }
.ptp-msg-list-scroll { flex: 1; overflow-y: auto; -webkit-overflow-scrolling: touch; }
.ptp-convo-item { display: flex; align-items: center; gap: 14px; padding: 16px 20px; border-bottom: 1px solid #F3F4F6; text-decoration: none; transition: all 0.2s; cursor: pointer; }
@media (min-width: 600px) { .ptp-convo-item { padding: 16px 24px; } }
.ptp-convo-item:hover { background: #F9FAFB; }
.ptp-convo-item.active { background: #FEF3C7; border-left: 4px solid #FCB900; }
.ptp-convo-avatar { width: 44px; height: 44px; border-radius: 50%; object-fit: cover; flex-shrink: 0; }
@media (min-width: 600px) { .ptp-convo-avatar { width: 50px; height: 50px; } }
.ptp-convo-info { flex: 1; min-width: 0; }
.ptp-convo-name { font-weight: 600; font-size: 14px; color: #111827; margin: 0 0 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (min-width: 600px) { .ptp-convo-name { font-size: 15px; } }
.ptp-convo-preview { font-size: 12px; color: #6B7280; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
@media (min-width: 600px) { .ptp-convo-preview { font-size: 13px; } }
.ptp-convo-meta { text-align: right; flex-shrink: 0; }
.ptp-convo-time { font-size: 11px; color: #9CA3AF; }
@media (min-width: 600px) { .ptp-convo-time { font-size: 12px; } }
.ptp-convo-badge { background: #FCB900; color: #0E0F11; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 10px; margin-top: 4px; display: inline-block; }
.ptp-chat-panel { background: #fff; border-radius: 20px; box-shadow: 0 2px 12px rgba(0,0,0,0.06); overflow: hidden; display: none; flex-direction: column; flex: 1; }
.ptp-chat-panel.mobile-show { display: flex; }
@media (min-width: 900px) { .ptp-chat-panel { display: flex; } .ptp-chat-panel.mobile-show { display: flex; } }
.ptp-chat-header { padding: 12px 16px; border-bottom: 1px solid #F3F4F6; display: flex; align-items: center; gap: 12px; }
@media (min-width: 600px) { .ptp-chat-header { padding: 16px 24px; gap: 14px; } }
.ptp-chat-back { display: flex; align-items: center; justify-content: center; width: 36px; height: 36px; background: #F3F4F6; border: none; border-radius: 50%; cursor: pointer; flex-shrink: 0; }
@media (min-width: 900px) { .ptp-chat-back { display: none; } }
.ptp-chat-avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; }
@media (min-width: 600px) { .ptp-chat-avatar { width: 44px; height: 44px; } }
.ptp-chat-name { font-weight: 600; font-size: 15px; color: #111827; margin: 0; }
@media (min-width: 600px) { .ptp-chat-name { font-size: 16px; } }
.ptp-chat-status { font-size: 12px; color: #22C55E; display: flex; align-items: center; gap: 6px; }
@media (min-width: 600px) { .ptp-chat-status { font-size: 13px; } }
.ptp-chat-status::before { content: ''; width: 8px; height: 8px; background: #22C55E; border-radius: 50%; }
.ptp-chat-messages { flex: 1; overflow-y: auto; padding: 16px; display: flex; flex-direction: column; gap: 12px; -webkit-overflow-scrolling: touch; }
@media (min-width: 600px) { .ptp-chat-messages { padding: 24px; } }
.ptp-bubble { max-width: 85%; padding: 10px 14px; border-radius: 18px; font-size: 14px; line-height: 1.5; }
@media (min-width: 600px) { .ptp-bubble { max-width: 70%; padding: 12px 16px; font-size: 15px; } }
.ptp-bubble.sent { background: linear-gradient(135deg, #FCB900 0%, #F59E0B 100%); color: #0E0F11; align-self: flex-end; border-bottom-right-radius: 4px; }
.ptp-bubble.received { background: #F3F4F6; color: #111827; align-self: flex-start; border-bottom-left-radius: 4px; }
.ptp-bubble-time { font-size: 10px; color: #9CA3AF; margin-top: 4px; }
@media (min-width: 600px) { .ptp-bubble-time { font-size: 11px; } }
.ptp-bubble.sent .ptp-bubble-time { color: rgba(0,0,0,0.5); text-align: right; }
.ptp-chat-input { padding: 12px 16px; border-top: 1px solid #F3F4F6; display: flex; gap: 8px; padding-bottom: max(12px, env(safe-area-inset-bottom)); }
@media (min-width: 600px) { .ptp-chat-input { padding: 16px 24px; gap: 12px; } }
.ptp-chat-input input { flex: 1; padding: 12px 16px; border: 2px solid #E5E7EB; border-radius: 25px; font-size: 16px; transition: all 0.2s; min-height: 44px; }
@media (min-width: 600px) { .ptp-chat-input input { padding: 14px 18px; font-size: 15px; } }
.ptp-chat-input input:focus { outline: none; border-color: #FCB900; }
.ptp-chat-input button { 
    background: linear-gradient(135deg, #FCB900 0%, #F59E0B 100%); 
    color: #0E0F11; 
    border: none; 
    padding: 12px 20px; 
    border-radius: 25px; 
    font-weight: 700; 
    font-size: 14px; 
    cursor: pointer; 
    transition: all 0.2s; 
    min-height: 44px;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}
.ptp-chat-input button:disabled {
    opacity: 0.7;
    cursor: not-allowed;
}
.ptp-chat-input button:hover:not(:disabled) { transform: scale(1.02); box-shadow: 0 4px 12px rgba(252, 185, 0, 0.3); }
@media (min-width: 600px) { .ptp-chat-input button { padding: 14px 28px; font-size: 15px; } }
.ptp-chat-empty { flex: 1; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; padding: 40px 20px; }
.ptp-chat-empty-icon { width: 80px; height: 80px; background: #F3F4F6; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 36px; margin-bottom: 20px; }
.ptp-chat-empty-title { font-family: 'Oswald', sans-serif; font-size: 20px; font-weight: 600; color: #111827; margin: 0 0 8px; }
.ptp-chat-empty-text { font-size: 14px; color: #6B7280; margin: 0; }
.ptp-no-convos { padding: 60px 24px; text-align: center; }
.ptp-no-convos-icon { font-size: 48px; margin-bottom: 16px; }
.ptp-no-convos-title { font-family: 'Oswald', sans-serif; font-size: 18px; font-weight: 600; color: #111827; margin: 0 0 8px; }
.ptp-no-convos-text { font-size: 14px; color: #6B7280; margin: 0 0 20px; }
.ptp-btn-find { background: linear-gradient(135deg, #FCB900 0%, #F59E0B 100%); color: #0E0F11; padding: 12px 24px; border-radius: 10px; font-weight: 700; font-size: 14px; text-decoration: none; display: inline-block; }
</style>

<div class="ptp-msg-wrap">
    <div class="ptp-msg-header">
        <div class="ptp-msg-header-inner">
            <h1 class="ptp-msg-title">💬 Messages</h1>
            <a href="<?php echo $is_trainer ? home_url('/trainer-dashboard/') : home_url('/my-training/'); ?>" class="ptp-msg-back">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                Back to Dashboard
            </a>
        </div>
    </div>
    
    <div class="ptp-msg-container">
        <div class="ptp-msg-layout">
            <!-- Conversations List -->
            <div class="ptp-msg-list <?php echo $active_conversation ? 'mobile-hidden' : ''; ?>" id="conversations-list">
                <div class="ptp-msg-list-header">
                    <h2 class="ptp-msg-list-title">Conversations</h2>
                </div>
                <div class="ptp-msg-list-scroll">
                    <?php if (empty($conversations)): ?>
                        <div class="ptp-no-convos">
                            <div class="ptp-no-convos-icon">💬</div>
                            <h3 class="ptp-no-convos-title">No Messages Yet</h3>
                            <p class="ptp-no-convos-text">Start a conversation with a <?php echo $is_trainer ? 'parent' : 'trainer'; ?></p>
                            <?php if (!$is_trainer): ?>
                                <a href="<?php echo home_url('/find-trainers/'); ?>" class="ptp-btn-find">Find Trainers</a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php foreach ($conversations as $convo): 
                            $avatar = !empty($convo->other_photo) ? $convo->other_photo : 'https://ui-avatars.com/api/?name=' . urlencode($convo->other_name) . '&size=100&background=FCB900&color=0A0A0A&bold=true';
                            $is_active = $active_conversation && $active_conversation->id == $convo->id;
                        ?>
                            <a href="<?php echo home_url('/messages/?conversation=' . $convo->id); ?>" class="ptp-convo-item <?php echo $is_active ? 'active' : ''; ?>">
                                <img src="<?php echo esc_url($avatar); ?>" alt="" class="ptp-convo-avatar">
                                <div class="ptp-convo-info">
                                    <div class="ptp-convo-name"><?php echo esc_html($convo->other_name); ?></div>
                                    <div class="ptp-convo-preview"><?php echo $convo->last_message ? esc_html(substr($convo->last_message, 0, 40)) . '...' : 'Start chatting'; ?></div>
                                </div>
                                <div class="ptp-convo-meta">
                                    <?php if ($convo->last_message_time): ?>
                                        <div class="ptp-convo-time"><?php echo human_time_diff(strtotime($convo->last_message_time)); ?></div>
                                    <?php endif; ?>
                                    <?php if ($convo->unread_count > 0): ?>
                                        <span class="ptp-convo-badge"><?php echo $convo->unread_count; ?></span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Chat Panel -->
            <div class="ptp-chat-panel <?php echo $active_conversation ? 'mobile-show' : ''; ?>" <?php echo $active_conversation ? 'data-conversation="' . $active_conversation->id . '"' : ''; ?>>
                <?php if ($active_conversation): 
                    $trainer = PTP_Trainer::get($active_conversation->trainer_id);
                    $parent = PTP_Parent::get($active_conversation->parent_id);
                    $other_name = $is_trainer ? ($parent ? $parent->display_name : 'Parent') : ($trainer ? $trainer->display_name : 'Trainer');
                    $other_photo = $is_trainer ? null : ($trainer ? $trainer->photo_url : null);
                    if (!$other_photo) $other_photo = 'https://ui-avatars.com/api/?name=' . urlencode($other_name) . '&size=100&background=FCB900&color=0A0A0A&bold=true';
                ?>
                    <div class="ptp-chat-header">
                        <button class="ptp-chat-back" onclick="window.location.href='<?php echo home_url('/messages/'); ?>'">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>
                        </button>
                        <img src="<?php echo esc_url($other_photo); ?>" alt="" class="ptp-chat-avatar">
                        <div>
                            <div class="ptp-chat-name"><?php echo esc_html($other_name); ?></div>
                            <div class="ptp-chat-status">Online</div>
                        </div>
                    </div>
                    
                    <div class="ptp-chat-messages" id="chat-messages">
                        <?php if (empty($messages)): ?>
                            <div style="text-align: center; color: #9CA3AF; padding: 40px;">Start the conversation!</div>
                        <?php else: ?>
                            <?php foreach ($messages as $msg): 
                                $is_sent = $msg->sender_id == get_current_user_id();
                            ?>
                                <div class="ptp-bubble <?php echo $is_sent ? 'sent' : 'received'; ?>">
                                    <?php echo esc_html($msg->message); ?>
                                    <div class="ptp-bubble-time"><?php echo date('g:i A', strtotime($msg->created_at)); ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    
                    <form class="ptp-chat-input ptp-chat-form" data-conversation="<?php echo $active_conversation->id; ?>">
                        <input type="text" name="message" placeholder="Type a message..." autocomplete="off" autofocus id="message-input">
                        <button type="submit" id="send-btn">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                            <span>Send</span>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="ptp-chat-empty">
                        <div class="ptp-chat-empty-icon">💬</div>
                        <h3 class="ptp-chat-empty-title">Select a Conversation</h3>
                        <p class="ptp-chat-empty-text">Choose a conversation from the list to start messaging</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
const ptpNonce = '<?php echo wp_create_nonce('ptp_ajax_nonce'); ?>';
const currentUserId = <?php echo get_current_user_id(); ?>;
let conversationId = <?php echo $active_conversation ? $active_conversation->id : 'null'; ?>;
let lastMessageId = 0;
let pollInterval = null;

// Auto-scroll to bottom
function scrollToBottom() {
    const chatMessages = document.getElementById('chat-messages');
    if (chatMessages) {
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }
}

// Format time
function formatTime(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
}

// Add message to chat
function addMessage(msg, isSent) {
    const chatMessages = document.getElementById('chat-messages');
    if (!chatMessages) return;
    
    const bubble = document.createElement('div');
    bubble.className = 'ptp-bubble ' + (isSent ? 'sent' : 'received');
    bubble.innerHTML = `
        ${escapeHtml(msg.message)}
        <div class="ptp-bubble-time">${formatTime(msg.created_at)}</div>
    `;
    chatMessages.appendChild(bubble);
    scrollToBottom();
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Send message
async function sendMessage(message) {
    if (!message.trim() || !conversationId) return;
    
    const sendBtn = document.getElementById('send-btn');
    const input = document.getElementById('message-input');
    
    // Disable button
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<span>Sending...</span>';
    
    try {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=ptp_send_message&nonce=${ptpNonce}&conversation_id=${conversationId}&message=${encodeURIComponent(message)}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Add message to chat
            addMessage({
                message: message,
                created_at: new Date().toISOString(),
                sender_id: currentUserId
            }, true);
            
            // Clear input
            input.value = '';
            
            // Update last message ID
            if (data.data && data.data.message_id) {
                lastMessageId = data.data.message_id;
            }
        } else {
            alert(data.data?.message || 'Failed to send message');
        }
    } catch (e) {
        console.error('Send error:', e);
        alert('Network error. Please try again.');
    }
    
    // Re-enable button
    sendBtn.disabled = false;
    sendBtn.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg><span>Send</span>';
    input.focus();
}

// Poll for new messages
async function pollMessages() {
    if (!conversationId) return;
    
    try {
        const response = await fetch(ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=ptp_get_new_messages&nonce=${ptpNonce}&conversation_id=${conversationId}&last_id=${lastMessageId}`
        });
        
        const data = await response.json();
        
        if (data.success && data.data.messages && data.data.messages.length > 0) {
            data.data.messages.forEach(msg => {
                if (msg.sender_id != currentUserId) {
                    addMessage(msg, false);
                }
                if (msg.id > lastMessageId) {
                    lastMessageId = msg.id;
                }
            });
        }
    } catch (e) {
        console.error('Poll error:', e);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    scrollToBottom();
    
    // Get last message ID
    const messages = document.querySelectorAll('.ptp-bubble');
    if (messages.length > 0) {
        // Set a reasonable starting ID
        lastMessageId = messages.length;
    }
    
    // Form submit
    const form = document.querySelector('.ptp-chat-form');
    if (form) {
        form.addEventListener('submit', e => {
            e.preventDefault();
            const input = document.getElementById('message-input');
            sendMessage(input.value);
        });
        
        // Enter to send
        const input = document.getElementById('message-input');
        if (input) {
            input.addEventListener('keypress', e => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage(input.value);
                }
            });
        }
    }
    
    // Start polling for new messages (every 3 seconds)
    if (conversationId) {
        pollInterval = setInterval(pollMessages, 3000);
    }
});

// Cleanup on page unload
window.addEventListener('beforeunload', () => {
    if (pollInterval) clearInterval(pollInterval);
});
</script>
