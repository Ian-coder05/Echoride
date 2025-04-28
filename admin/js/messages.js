$(document).ready(function() {
    // Toggle sidebar
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
    });

    // Load initial data
    loadMessages();
    loadUsers();

    // Event listeners
    $('#applyFilters').on('click', loadMessages);
    $('#sendMessage').on('click', sendMessage);
    $('#sendReply').on('click', sendReply);
    $('#replyMessage').on('click', openReplyModal);
    $('#toggleFlag').on('click', toggleMessageFlag);
    $('#markAsRead').on('click', markMessageAsRead);
});

// Function to load users for the recipient dropdown
function loadUsers() {
    console.log('Loading users for dropdown');
    $.ajax({
        url: '../php/admin/get_users_for_messages.php',
        method: 'GET',
        dataType: 'json',
        success: function(users) {
            console.log('Received users:', users);
            const userSelect = $('#recipientId');
            userSelect.empty();
            
            userSelect.append('<option value="">Select recipient</option>');
            users.forEach(user => {
                userSelect.append(`<option value="${user.id}">${user.display_text}</option>`);
            });
        },
        error: function(xhr, status, error) {
            console.error('Error loading users:', error);
            console.log('Response text:', xhr.responseText);
            showAlert('danger', 'Error loading users');
        }
    });
}

// Function to load messages with filters
function loadMessages() {
    const filters = {
        status: $('#statusFilter').val(),
        type: $('#typeFilter').val(),
        search: $('#searchInput').val()
    };
    
    console.log('Loading messages with filters:', filters);
    
    $.ajax({
        url: '../php/admin/get_messages.php',
        method: 'GET',
        data: filters,
        dataType: 'json',
        success: function(data) {
            console.log('Received messages data:', data);
            updateMessageStats(data.stats);
            populateMessagesTable(data.messages);
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.log('Response text:', xhr.responseText);
            showAlert('danger', 'Error loading messages');
        }
    });
}

// Update message statistics
function updateMessageStats(stats) {
    $('#totalMessages').text(stats.total || 0);
    $('#readMessages').text(stats.read || 0);
    $('#unreadMessages').text(stats.unread || 0);
    $('#flaggedMessages').text(stats.flagged || 0);
}

// Populate messages table
function populateMessagesTable(messages) {
    const tableBody = $('#messagesTableBody');
    tableBody.empty();
    
    if (messages.length === 0) {
        tableBody.append('<tr><td colspan="8" class="text-center">No messages found</td></tr>');
        return;
    }
    
    messages.forEach(message => {
        const row = $('<tr>');
        
        // Add class for unread messages
        if (message.status === 'unread') {
            row.addClass('message-unread');
        }
        
        row.append(`<td>${message.id}</td>`);
        row.append(`<td>${message.from_name}</td>`);
        row.append(`<td>${message.to_name}</td>`);
        row.append(`<td>${message.subject}</td>`);
        row.append(`<td>${formatMessageType(message.message_type)}</td>`);
        row.append(`<td>${message.date_formatted}</td>`);
        row.append(`<td>${formatMessageStatus(message.status)}</td>`);
        
        // Action buttons
        const actions = $('<td>');
        actions.append(`<button class="btn btn-sm btn-info me-1" onclick="viewMessageDetails(${message.id})"><i class="bi bi-eye"></i></button>`);
        actions.append(`<button class="btn btn-sm btn-primary me-1" onclick="openReplyModal(${message.id})"><i class="bi bi-reply"></i></button>`);
        
        if (message.is_flagged) {
            actions.append(`<button class="btn btn-sm btn-warning me-1" onclick="toggleMessageFlag(${message.id}, false)"><i class="bi bi-flag-fill"></i></button>`);
        } else {
            actions.append(`<button class="btn btn-sm btn-outline-warning me-1" onclick="toggleMessageFlag(${message.id}, true)"><i class="bi bi-flag"></i></button>`);
        }
        
        if (message.status === 'unread') {
            actions.append(`<button class="btn btn-sm btn-success me-1" onclick="markMessageAsRead(${message.id})"><i class="bi bi-check-circle"></i></button>`);
        }
        
        actions.append(`<button class="btn btn-sm btn-danger" onclick="deleteMessage(${message.id})"><i class="bi bi-trash"></i></button>`);
        
        row.append(actions);
        tableBody.append(row);
    });
}

// View message details
function viewMessageDetails(id) {
    console.log('Viewing message details for ID:', id);
    $.ajax({
        url: '../php/admin/get_message_details.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(data) {
            console.log('Received message details:', data);
            const message = data.message;
            
            // Update message information
            $('#detailFrom').text(message.from_name);
            $('#detailTo').text(message.to_name);
            $('#detailDate').text(message.date_formatted);
            $('#detailType').text(formatMessageType(message.message_type));
            $('#detailSubject').text(message.subject);
            $('#detailContent').html(message.content.replace(/\n/g, '<br>'));
            
            // Store message ID for reply and other actions
            $('#replyToId').val(message.id);
            $('#replyRecipientId').val(message.from_id);
            $('#replySubject').val('RE: ' + message.subject);
            
            // Update button states based on message status
            if (message.status === 'unread') {
                $('#markAsRead').show();
            } else {
                $('#markAsRead').hide();
            }
            
            if (message.is_flagged) {
                $('#toggleFlag').html('Unflag');
                $('#toggleFlag').removeClass('btn-outline-warning').addClass('btn-warning');
            } else {
                $('#toggleFlag').html('Flag');
                $('#toggleFlag').removeClass('btn-warning').addClass('btn-outline-warning');
            }
            
            // Show the modal
            $('#viewMessageModal').modal('show');
            
            // If message is unread, mark it as read
            if (message.status === 'unread') {
                markMessageAsRead(message.id, false);
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.log('Response text:', xhr.responseText);
            showAlert('danger', 'Error loading message details');
        }
    });
}

// Open reply modal
function openReplyModal(id) {
    // If id is provided, we're replying to a specific message
    if (id) {
        // Get message details first if not already viewing
        if (!$('#viewMessageModal').hasClass('show')) {
            viewMessageDetails(id);
        }
    }
    
    // Close view modal if open
    $('#viewMessageModal').modal('hide');
    
    // Show reply modal
    $('#replyMessageModal').modal('show');
}

// Send a new message
function sendMessage() {
    const form = $('#composeMessageForm');
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }
    
    const formData = form.serializeArray();
    const data = {};
    formData.forEach(item => {
        data[item.name] = item.value;
    });
    
    // Add flag value
    data.is_flagged = $('#flagMessage').is(':checked') ? 1 : 0;
    
    console.log('Sending message:', data);
    
    $.ajax({
        url: '../php/admin/add_message.php',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(result) {
            console.log('Message send response:', result);
            if (result.success) {
                showAlert('success', 'Message sent successfully');
                $('#composeMessageModal').modal('hide');
                form[0].reset();
                loadMessages();
            } else {
                showAlert('danger', result.message || 'Error sending message');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.log('Response text:', xhr.responseText);
            showAlert('danger', 'Error sending message');
        }
    });
}

// Send a reply to a message
function sendReply() {
    const form = $('#replyMessageForm');
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }
    
    const formData = form.serializeArray();
    const data = {};
    formData.forEach(item => {
        data[item.name] = item.value;
    });
    
    console.log('Sending reply:', data);
    
    $.ajax({
        url: '../php/admin/add_message.php',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(result) {
            console.log('Reply send response:', result);
            if (result.success) {
                showAlert('success', 'Reply sent successfully');
                $('#replyMessageModal').modal('hide');
                form[0].reset();
                loadMessages();
            } else {
                showAlert('danger', result.message || 'Error sending reply');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.log('Response text:', xhr.responseText);
            showAlert('danger', 'Error sending reply');
        }
    });
}

// Toggle message flag status
function toggleMessageFlag(id, flag) {
    console.log('Toggling flag for message ID:', id, 'to', flag);
    $.ajax({
        url: '../php/admin/update_message.php',
        method: 'POST',
        data: {
            id: id,
            action: 'toggle_flag',
            flag: flag ? 1 : 0
        },
        dataType: 'json',
        success: function(result) {
            console.log('Toggle flag response:', result);
            if (result.success) {
                showAlert('success', flag ? 'Message flagged' : 'Message unflagged');
                loadMessages();
                
                // Close modal if open
                if ($('#viewMessageModal').hasClass('show')) {
                    $('#viewMessageModal').modal('hide');
                }
            } else {
                showAlert('danger', result.message || 'Error updating message flag');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.log('Response text:', xhr.responseText);
            showAlert('danger', 'Error updating message flag');
        }
    });
}

// Mark message as read
function markMessageAsRead(id, showAlert = true) {
    console.log('Marking message ID as read:', id);
    $.ajax({
        url: '../php/admin/update_message.php',
        method: 'POST',
        data: {
            id: id,
            action: 'mark_read'
        },
        dataType: 'json',
        success: function(result) {
            console.log('Mark as read response:', result);
            if (result.success) {
                if (showAlert) {
                    showAlert('success', 'Message marked as read');
                }
                loadMessages();
                
                // Update button in modal
                $('#markAsRead').hide();
            } else {
                if (showAlert) {
                    showAlert('danger', result.message || 'Error updating message status');
                }
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.log('Response text:', xhr.responseText);
            if (showAlert) {
                showAlert('danger', 'Error updating message status');
            }
        }
    });
}

// Delete a message
function deleteMessage(id) {
    if (!confirm('Are you sure you want to delete this message?')) {
        return;
    }
    
    console.log('Deleting message ID:', id);
    $.ajax({
        url: '../php/admin/update_message.php',
        method: 'POST',
        data: {
            id: id,
            action: 'delete'
        },
        dataType: 'json',
        success: function(result) {
            console.log('Delete message response:', result);
            if (result.success) {
                showAlert('success', 'Message deleted successfully');
                loadMessages();
            } else {
                showAlert('danger', result.message || 'Error deleting message');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.log('Response text:', xhr.responseText);
            showAlert('danger', 'Error deleting message');
        }
    });
}

// Format message type for display
function formatMessageType(type) {
    switch (type) {
        case 'support':
            return '<span class="badge bg-primary">Support</span>';
        case 'feedback':
            return '<span class="badge bg-success">Feedback</span>';
        case 'complaint':
            return '<span class="badge bg-danger">Complaint</span>';
        case 'inquiry':
            return '<span class="badge bg-info">Inquiry</span>';
        case 'notification':
            return '<span class="badge bg-secondary">Notification</span>';
        default:
            return '<span class="badge bg-dark">' + (type || 'Unknown') + '</span>';
    }
}

// Format message status for display
function formatMessageStatus(status) {
    switch (status) {
        case 'read':
            return '<span class="badge bg-success">Read</span>';
        case 'unread':
            return '<span class="badge bg-warning">Unread</span>';
        default:
            return '<span class="badge bg-secondary">' + (status || 'Unknown') + '</span>';
    }
}

// Show alert message
function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    $('#alertContainer').append(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}
