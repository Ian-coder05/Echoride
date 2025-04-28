$(document).ready(function () {
    // Toggle sidebar
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
    });

    // Load initial data
    loadUserStats();
    loadUsers();

    // Handle filter application
    $('#applyFilters').click(loadUsers);

    // Handle add user form submission
    $('#saveUser').click(function() {
        const formData = new FormData($('#addUserForm')[0]);
        
        $.ajax({
            url: '../php/admin/add_user.php',
            method: 'POST',
            data: Object.fromEntries(formData),
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    $('#addUserModal').modal('hide');
                    $('#addUserForm')[0].reset();
                    loadUsers();
                    loadUserStats();
                    showAlert('success', 'User added successfully!');
                } else {
                    showAlert('danger', data.error || 'Error adding user');
                }
            },
            error: function() {
                showAlert('danger', 'Error adding user');
            }
        });
    });

    // Handle edit user form submission
    $('#updateUser').click(function() {
        const formData = new FormData($('#editUserForm')[0]);
        
        $.ajax({
            url: '../php/admin/update_user.php',
            method: 'POST',
            data: Object.fromEntries(formData),
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    $('#editUserModal').modal('hide');
                    loadUsers();
                    loadUserStats();
                    showAlert('success', 'User updated successfully!');
                } else {
                    showAlert('danger', data.error || 'Error updating user');
                }
            },
            error: function() {
                showAlert('danger', 'Error updating user');
            }
        });
    });
});

function loadUserStats() {
    $.ajax({
        url: '../php/admin/get_user_stats.php',
        method: 'GET',
        success: function(response) {
            const stats = JSON.parse(response);
            $('#totalUsers').text(stats.totalUsers);
            $('#activeUsers').text(stats.activeUsers);
            $('#totalRides').text(stats.totalRides);
            $('#totalCarbonSaved').text(stats.totalCarbonSaved.toFixed(2) + ' kg');
        },
        error: function() {
            showAlert('danger', 'Error loading user statistics');
        }
    });
}

function loadUsers() {
    const filters = {
        search: $('#searchInput').val(),
        role: $('#roleFilter').val(),
        sort: $('#sortBy').val()
    };

    $.ajax({
        url: '../php/admin/get_users.php',
        method: 'GET',
        data: filters,
        success: function(response) {
            const users = JSON.parse(response);
            const tbody = $('#usersTable tbody');
            tbody.empty();
            
            users.forEach(user => {
                tbody.append(`
                    <tr>
                        <td>${user.id}</td>
                        <td>${user.full_name}</td>
                        <td>${user.email}</td>
                        <td>
                            <span class="badge bg-${user.is_admin ? 'primary' : 'secondary'}">
                                ${user.is_admin ? 'Admin' : 'User'}
                            </span>
                        </td>
                        <td>${user.total_rides || 0}</td>
                        <td>${(user.total_carbon_saved || 0).toFixed(2)} kg</td>
                        <td>${formatDate(user.created_at)}</td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewUserStats(${user.id})">
                                <i class="bi bi-graph-up"></i>
                            </button>
                            <button class="btn btn-sm btn-primary" onclick="editUser(${user.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteUser(${user.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        },
        error: function() {
            showAlert('danger', 'Error loading users');
        }
    });
}

function editUser(id) {
    $.ajax({
        url: '../php/admin/get_user.php',
        method: 'GET',
        data: { id: id },
        success: function(response) {
            const user = JSON.parse(response);
            const form = $('#editUserForm');
            
            form.find('[name="id"]').val(user.id);
            form.find('[name="full_name"]').val(user.full_name);
            form.find('[name="email"]').val(user.email);
            form.find('[name="is_admin"]').val(user.is_admin ? "1" : "0");
            form.find('[name="password"]').val(''); // Clear password field
            
            $('#editUserModal').modal('show');
        },
        error: function() {
            showAlert('danger', 'Error loading user details');
        }
    });
}

function deleteUser(id) {
    if (confirm('Are you sure you want to delete this user? This will also delete all associated rides and payments.')) {
        $.ajax({
            url: '../php/admin/delete_user.php',
            method: 'POST',
            data: { id: id },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    loadUsers();
                    loadUserStats();
                    showAlert('success', 'User deleted successfully!');
                } else {
                    showAlert('danger', data.error || 'Error deleting user');
                }
            },
            error: function() {
                showAlert('danger', 'Error deleting user');
            }
        });
    }
}

function viewUserStats(id) {
    // Load user rides
    $.ajax({
        url: '../php/admin/get_user_rides.php',
        method: 'GET',
        data: { id: id },
        success: function(response) {
            const rides = JSON.parse(response);
            const tbody = $('#userRidesTable tbody');
            tbody.empty();
            
            rides.forEach(ride => {
                tbody.append(`
                    <tr>
                        <td>${formatDate(ride.ride_time)}</td>
                        <td>${ride.vehicle_type} #${ride.vehicle_id}</td>
                        <td>${ride.distance.toFixed(2)} km</td>
                        <td>${ride.carbon_saved.toFixed(2)} kg</td>
                    </tr>
                `);
            });
        }
    });

    // Load user payments
    $.ajax({
        url: '../php/admin/get_user_payments.php',
        method: 'GET',
        data: { id: id },
        success: function(response) {
            const payments = JSON.parse(response);
            const tbody = $('#userPaymentsTable tbody');
            tbody.empty();
            
            payments.forEach(payment => {
                tbody.append(`
                    <tr>
                        <td>${formatDate(payment.created_at)}</td>
                        <td>$${payment.amount.toFixed(2)}</td>
                        <td>
                            <span class="badge bg-${getPaymentStatusColor(payment.status)}">
                                ${payment.status}
                            </span>
                        </td>
                    </tr>
                `);
            });
        }
    });

    $('#userStatsModal').modal('show');
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function getPaymentStatusColor(status) {
    switch(status.toLowerCase()) {
        case 'completed': return 'success';
        case 'pending': return 'warning';
        case 'failed': return 'danger';
        default: return 'secondary';
    }
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
