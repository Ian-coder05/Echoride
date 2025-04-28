$(document).ready(function() {
    // Toggle sidebar
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
    });

    // Load initial data
    loadPayments();
    loadUsers();
    loadRides();

    // Event listeners
    $('#applyFilters').on('click', loadPayments);
    $('#savePayment').on('click', addPayment);

    // Initialize form
    $('input[name="amount"]').val('');
});

// Function to load users for the dropdown
function loadUsers() {
    console.log('Loading users for dropdown');
    $.ajax({
        url: '../php/admin/get_users_for_payments.php',
        method: 'GET',
        dataType: 'json',
        success: function(users) {
            console.log('Received users:', users);
            const userSelect = $('select[name="user_id"]');
            userSelect.empty();
            
            userSelect.append('<option value="">Select user</option>');
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

// Function to load rides for the dropdown
function loadRides() {
    console.log('Loading rides for dropdown');
    $.ajax({
        url: '../php/admin/get_rides_for_payments.php',
        method: 'GET',
        dataType: 'json',
        success: function(rides) {
            console.log('Received rides:', rides);
            const rideSelect = $('select[name="ride_id"]');
            rideSelect.empty();
            
            rideSelect.append('<option value="">Select ride</option>');
            rides.forEach(ride => {
                rideSelect.append(`<option value="${ride.id}">${ride.display_text}</option>`);
            });
        },
        error: function(xhr, status, error) {
            console.error('Error loading rides:', error);
            console.log('Response text:', xhr.responseText);
            showAlert('danger', 'Error loading rides');
        }
    });
}

function loadPayments() {
    const filters = {
        status: $('#statusFilter').val(),
        method: $('#methodFilter').val(),
        search: $('#searchInput').val()
    };

    console.log('Loading payments with filters:', filters);
    $.ajax({
        url: '../php/admin/get_payments.php',
        method: 'GET',
        data: filters,
        dataType: 'json',
        success: function(data) {
            console.log('Received data:', data);
            updatePaymentStats(data.stats);
            populatePaymentsTable(data.payments);
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.log('Response text:', xhr.responseText);
            showAlert('danger', 'Error loading payments: ' + error);
        }
    });
}

function updatePaymentStats(stats) {
    $('#totalPayments').text(stats.total || 0);
    $('#completedPayments').text(stats.completed || 0);
    $('#pendingPayments').text(stats.pending || 0);
    $('#failedPayments').text(stats.failed || 0);
}

function populatePaymentsTable(payments) {
    const tbody = $('#paymentsTable tbody');
    tbody.empty();

    payments.forEach(payment => {
        const row = `
            <tr>
                <td>${payment.id}</td>
                <td>${payment.transaction_id}</td>
                <td>${payment.user_name}</td>
                <td>KES ${parseFloat(payment.amount).toLocaleString()}</td>
                <td>${formatPaymentMethod(payment.payment_method)}</td>
                <td><span class="badge bg-${getPaymentStatusColor(payment.payment_status)}">${formatPaymentStatus(payment.payment_status)}</span></td>
                <td>${new Date(payment.created_at).toLocaleString()}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="viewPaymentDetails(${payment.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-warning" onclick="editPayment(${payment.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function addPayment() {
    const form = $('#addPaymentForm');
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }

    // Get a random ride ID if not provided
    if (!$('select[name="ride_id"]').val()) {
        // For demo purposes, just use a random ID between 1-10
        $('select[name="ride_id"]').val(Math.floor(Math.random() * 10) + 1);
    }

    const formData = form.serializeArray();
    const data = {};
    formData.forEach(item => {
        data[item.name] = item.value;
    });

    console.log('Adding payment with data:', data);

    $.ajax({
        url: '../php/admin/add_payment.php',
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(result) {
            console.log('Add payment response:', result);
            if (result.success) {
                showAlert('success', 'Payment added successfully');
                $('#addPaymentModal').modal('hide');
                form[0].reset();
                loadPayments();
            } else {
                showAlert('danger', result.message || 'Error adding payment');
            }
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.log('Response text:', xhr.responseText);
            showAlert('danger', xhr.responseText || 'Error adding payment: ' + error);
        }
    });
}

function viewPaymentDetails(id) {
    console.log('Viewing payment details for ID:', id);
    $.ajax({
        url: '../php/admin/get_payment_details.php',
        method: 'GET',
        data: { id: id },
        dataType: 'json',
        success: function(data) {
            console.log('Received payment details:', data);
            const payment = data.payment;
            
            // Update payment information
            $('#detailTransactionId').text(payment.transaction_id);
            $('#detailUser').text(payment.user_name);
            $('#detailAmount').text(payment.amount_formatted);
            $('#detailMethod').text(payment.payment_method_formatted);
            $('#detailStatus').text(payment.payment_status_formatted);
            $('#detailDate').text(payment.date_formatted);
            $('#detailNotes').text(payment.notes || 'No notes available');
            
            $('#paymentDetailsModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.error('AJAX error:', status, error);
            console.log('Response text:', xhr.responseText);
            showAlert('danger', 'Error loading payment details: ' + error);
        }
    });
}

function loadUsers() {
    $.ajax({
        url: '../php/admin/get_users_for_payments.php',
        method: 'GET',
        success: function(response) {
            const users = JSON.parse(response);
            const userSelects = $('select[name="user_id"]');
            userSelects.empty();
            
            userSelects.append('<option value="">Select user</option>');
            users.forEach(user => {
                const option = `<option value="${user.id}">${user.display_text}</option>`;
                userSelects.append(option);
            });
        },
        error: function() {
            showAlert('danger', 'Error loading users');
        }
    });
}

// Utility functions
function formatPaymentMethod(method) {
    const methods = {
        credit_card: 'Credit Card',
        mpesa: 'M-Pesa',
        paypal: 'PayPal',
        bank_transfer: 'Bank Transfer'
    };
    return methods[method] || method;
}

function formatPaymentStatus(status) {
    const statuses = {
        completed: 'Completed',
        pending: 'Pending',
        failed: 'Failed'
    };
    return statuses[status] || status;
}

function getPaymentStatusColor(status) {
    const colors = {
        completed: 'success',
        pending: 'warning',
        failed: 'danger'
    };
    return colors[status] || 'secondary';
}

function showAlert(type, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Remove any existing alerts
    $('.alert').remove();
    
    // Add new alert at the top of the content
    $('#content').prepend(alertHtml);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        $('.alert').alert('close');
    }, 5000);
}
