// Check login status and update UI accordingly
document.addEventListener('DOMContentLoaded', function() {
    const isLoggedIn = localStorage.getItem('isLoggedIn') === 'true';
    
    // Redirect to login if not logged in
    if (!isLoggedIn) {
        window.location.href = 'login.html';
        return;
    }
    
    // Update navigation visibility
    document.querySelectorAll('.logged-in-only').forEach(el => {
        el.style.display = '';
    });
    document.querySelectorAll('.logged-out-only').forEach(el => {
        el.style.display = 'none';
    });
    
    // Load messages
    loadMessages();
    
    // Set up event listeners
    document.getElementById('applyFilters').addEventListener('click', loadMessages);
    document.getElementById('sendMessage').addEventListener('click', sendMessage);
    document.getElementById('replyMessage').addEventListener('click', openReplyModal);
    document.getElementById('sendReply').addEventListener('click', sendReply);
});

// Load messages with optional filters
function loadMessages() {
    const statusFilter = document.getElementById('statusFilter').value;
    const typeFilter = document.getElementById('typeFilter').value;
    
    const filters = {};
    if (statusFilter) filters.status = statusFilter;
    if (typeFilter) filters.type = typeFilter;
    
    // Convert filters object to query string
    const queryString = Object.keys(filters)
        .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(filters[key])}`)
        .join('&');
    
    // Show loading indicator
    document.getElementById('messageList').innerHTML = `
        <div class="col-12 text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    `;
    
    // Fetch messages from server
    fetch(`php/user/get_messages.php${queryString ? '?' + queryString : ''}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            displayMessages(data.messages);
            updateUnreadBadge(data.unread_count);
        })
        .catch(error => {
            console.error('Error fetching messages:', error);
            document.getElementById('messageList').innerHTML = `
                <div class="col-12">
                    <div class="alert alert-danger" role="alert">
                        Error loading messages. Please try again later.
                    </div>
                </div>
            `;
        });
}

// Display messages in the UI
function displayMessages(messages) {
    const messageList = document.getElementById('messageList');
    
    if (!messages || messages.length === 0) {
        messageList.innerHTML = `
            <div class="col-12">
                <div class="card text-center p-5">
                    <div class="card-body">
                        <i class="bi bi-envelope-open text-muted" style="font-size: 4rem;"></i>
                        <h4 class="mt-3">No Messages</h4>
                        <p class="text-muted">You don't have any messages yet.</p>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeMessageModal">
                            Send a Message
                        </button>
                    </div>
                </div>
            </div>
        `;
        return;
    }
    
    // Generate HTML for each message
    const messagesHTML = messages.map(message => {
        const isUnread = message.status === 'unread';
        const cardClass = isUnread ? 'message-unread' : '';
        
        return `
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card message-card ${cardClass} h-100" data-id="${message.id}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span class="badge ${getBadgeClass(message.message_type)}">${formatMessageType(message.message_type)}</span>
                        <small class="text-muted">${message.date_formatted}</small>
                    </div>
                    <div class="card-body" style="cursor: pointer;" onclick="viewMessage(${message.id})">
                        <h5 class="card-title">${message.subject}</h5>
                        <p class="card-text text-truncate">${message.content}</p>
                    </div>
                    <div class="card-footer d-flex justify-content-between align-items-center">
                        <small class="text-muted">From: ${message.from_name}</small>
                        <div>
                            <button class="btn btn-sm btn-outline-primary" onclick="viewMessage(${message.id})">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" onclick="replyToMessage(${message.id})">
                                <i class="bi bi-reply"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    messageList.innerHTML = messagesHTML;
}

// Update the unread badge count
function updateUnreadBadge(count) {
    const badge = document.getElementById('unreadBadge');
    
    if (count > 0) {
        badge.textContent = count;
        badge.classList.remove('d-none');
    } else {
        badge.classList.add('d-none');
    }
}

// View message details
function viewMessage(id) {
    fetch(`php/user/get_message_details.php?id=${id}`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            const message = data.message;
            
            // Update modal with message details
            document.getElementById('detailFrom').textContent = message.from_name;
            document.getElementById('detailDate').textContent = message.date_formatted;
            document.getElementById('detailType').innerHTML = `<span class="badge ${getBadgeClass(message.message_type)}">${formatMessageType(message.message_type)}</span>`;
            document.getElementById('detailStatus').innerHTML = `<span class="badge ${message.status === 'read' ? 'bg-success' : 'bg-warning'}">${message.status}</span>`;
            document.getElementById('detailSubject').textContent = message.subject;
            document.getElementById('detailContent').innerHTML = message.content.replace(/\n/g, '<br>');
            
            // Set up reply data
            document.getElementById('replyToId').value = message.id;
            document.getElementById('replyRecipientId').value = message.from_id;
            document.getElementById('replySubject').value = `RE: ${message.subject}`;
            
            // Display thread if available
            const threadContainer = document.getElementById('messageThread');
            const threadMessages = document.getElementById('threadMessages');
            
            if (data.thread && data.thread.length > 0) {
                threadContainer.classList.remove('d-none');
                
                // Generate HTML for thread messages
                const threadHTML = data.thread.map(threadMsg => `
                    <div class="mb-3 p-3 bg-light rounded">
                        <div class="d-flex justify-content-between">
                            <strong>${threadMsg.from_name}</strong>
                            <small class="text-muted">${threadMsg.date_formatted}</small>
                        </div>
                        <div class="mt-2">
                            ${threadMsg.content.replace(/\n/g, '<br>')}
                        </div>
                    </div>
                `).join('');
                
                threadMessages.innerHTML = threadHTML;
            } else {
                threadContainer.classList.add('d-none');
                threadMessages.innerHTML = '';
            }
            
            // Show the modal
            new bootstrap.Modal(document.getElementById('viewMessageModal')).show();
            
            // Mark as read if unread
            if (message.status === 'unread') {
                markAsRead(id);
            }
        })
        .catch(error => {
            console.error('Error fetching message details:', error);
            showAlert('danger', 'Error loading message details. Please try again.');
        });
}

// Open reply modal
function openReplyModal() {
    // Hide view modal
    bootstrap.Modal.getInstance(document.getElementById('viewMessageModal')).hide();
    
    // Show reply modal
    new bootstrap.Modal(document.getElementById('replyMessageModal')).show();
}

// Reply to a specific message
function replyToMessage(id) {
    viewMessage(id);
    
    // Wait for view modal to finish loading
    setTimeout(() => {
        openReplyModal();
    }, 500);
}

// Send a new message
function sendMessage() {
    const form = document.getElementById('composeMessageForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Get form data
    const messageType = document.getElementById('messageType').value;
    const subject = document.getElementById('messageSubject').value;
    const content = document.getElementById('messageContent').value;
    
    // Create form data object
    const formData = new FormData();
    formData.append('message_type', messageType);
    formData.append('subject', subject);
    formData.append('content', content);
    
    // Send message to server
    fetch('php/user/send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Reset form and close modal
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('composeMessageModal')).hide();
            
            // Show success message and reload messages
            showAlert('success', 'Message sent successfully!');
            loadMessages();
        } else {
            showAlert('danger', data.message || 'Error sending message. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        showAlert('danger', 'Error sending message. Please try again.');
    });
}

// Send a reply to a message
function sendReply() {
    const form = document.getElementById('replyMessageForm');
    
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    // Get form data
    const replyToId = document.getElementById('replyToId').value;
    const recipientId = document.getElementById('replyRecipientId').value;
    const subject = document.getElementById('replySubject').value;
    const content = document.getElementById('replyContent').value;
    
    // Create form data object
    const formData = new FormData();
    formData.append('reply_to_id', replyToId);
    formData.append('recipient_id', recipientId);
    formData.append('subject', subject);
    formData.append('content', content);
    
    // Send reply to server
    fetch('php/user/send_message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Reset form and close modal
            form.reset();
            bootstrap.Modal.getInstance(document.getElementById('replyMessageModal')).hide();
            
            // Show success message and reload messages
            showAlert('success', 'Reply sent successfully!');
            loadMessages();
        } else {
            showAlert('danger', data.message || 'Error sending reply. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error sending reply:', error);
        showAlert('danger', 'Error sending reply. Please try again.');
    });
}

// Mark a message as read
function markAsRead(id) {
    fetch('php/user/update_message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `id=${id}&action=mark_read`
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Reload messages to update UI
            loadMessages();
        }
    })
    .catch(error => {
        console.error('Error marking message as read:', error);
    });
}

// Format message type for display
function formatMessageType(type) {
    switch (type) {
        case 'support':
            return 'Support';
        case 'feedback':
            return 'Feedback';
        case 'complaint':
            return 'Complaint';
        case 'inquiry':
            return 'Inquiry';
        case 'notification':
            return 'Notification';
        default:
            return type ? type.charAt(0).toUpperCase() + type.slice(1) : 'Unknown';
    }
}

// Get badge class based on message type
function getBadgeClass(type) {
    switch (type) {
        case 'support':
            return 'bg-primary';
        case 'feedback':
            return 'bg-success';
        case 'complaint':
            return 'bg-danger';
        case 'inquiry':
            return 'bg-info';
        case 'notification':
            return 'bg-secondary';
        default:
            return 'bg-dark';
    }
}

// Show alert message
function showAlert(type, message) {
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    // Create alert container if it doesn't exist
    let alertContainer = document.querySelector('.alert-container');
    if (!alertContainer) {
        alertContainer = document.createElement('div');
        alertContainer.className = 'alert-container position-fixed top-0 end-0 p-3';
        alertContainer.style.zIndex = '1050';
        document.body.appendChild(alertContainer);
    }
    
    // Add alert to container
    const alertElement = document.createElement('div');
    alertElement.innerHTML = alertHTML;
    alertContainer.appendChild(alertElement.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        if (alerts.length > 0) {
            alerts[0].remove();
        }
    }, 5000);
}
