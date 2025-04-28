$(document).ready(function () {
    // Toggle sidebar
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
    });

    // Load vehicles on page load
    loadVehicles();

    // Handle filter application
    $('#applyFilters').click(loadVehicles);

    // Handle add vehicle form submission
    $('#saveVehicle').click(function() {
        const formData = new FormData($('#addVehicleForm')[0]);
        
        $.ajax({
            url: '../php/admin/add_vehicle.php',
            method: 'POST',
            data: Object.fromEntries(formData),
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    $('#addVehicleModal').modal('hide');
                    $('#addVehicleForm')[0].reset();
                    loadVehicles();
                    showAlert('success', 'Vehicle added successfully!');
                } else {
                    showAlert('danger', data.error || 'Error adding vehicle');
                }
            },
            error: function() {
                showAlert('danger', 'Error adding vehicle');
            }
        });
    });

    // Handle edit vehicle form submission
    $('#updateVehicle').click(function() {
        const formData = new FormData($('#editVehicleForm')[0]);
        
        $.ajax({
            url: '../php/admin/update_vehicle.php',
            method: 'POST',
            data: Object.fromEntries(formData),
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    $('#editVehicleModal').modal('hide');
                    loadVehicles();
                    showAlert('success', 'Vehicle updated successfully!');
                } else {
                    showAlert('danger', data.error || 'Error updating vehicle');
                }
            },
            error: function() {
                showAlert('danger', 'Error updating vehicle');
            }
        });
    });
});

function loadVehicles() {
    const filters = {
        type: $('#typeFilter').val(),
        status: $('#statusFilter').val(),
        search: $('#searchInput').val()
    };

    $.ajax({
        url: '../php/admin/get_vehicles.php',
        method: 'GET',
        data: filters,
        success: function(response) {
            const vehicles = JSON.parse(response);
            const tbody = $('#vehiclesTable tbody');
            tbody.empty();
            
            vehicles.forEach(vehicle => {
                tbody.append(`
                    <tr>
                        <td>${vehicle.id}</td>
                        <td>${vehicle.type}</td>
                        <td>${vehicle.model}</td>
                        <td><span class="badge bg-${getStatusColor(vehicle.status)}">${vehicle.status}</span></td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar ${getBatteryColor(vehicle.battery_level)}" 
                                     role="progressbar" 
                                     style="width: ${vehicle.battery_level}%">
                                    ${vehicle.battery_level}%
                                </div>
                            </div>
                        </td>
                        <td>${vehicle.location}</td>
                        <td>${formatDate(vehicle.last_maintenance_date)}</td>
                        <td>
                            <button class="btn btn-sm btn-primary" onclick="editVehicle(${vehicle.id})">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-danger" onclick="deleteVehicle(${vehicle.id})">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `);
            });
        },
        error: function() {
            showAlert('danger', 'Error loading vehicles');
        }
    });
}

function editVehicle(id) {
    $.ajax({
        url: '../php/admin/get_vehicle.php',
        method: 'GET',
        data: { id: id },
        success: function(response) {
            const vehicle = JSON.parse(response);
            const form = $('#editVehicleForm');
            
            form.find('[name="id"]').val(vehicle.id);
            form.find('[name="type"]').val(vehicle.type);
            form.find('[name="model"]').val(vehicle.model);
            form.find('[name="status"]').val(vehicle.status);
            form.find('[name="location"]').val(vehicle.location);
            form.find('[name="battery_level"]').val(vehicle.battery_level);
            
            $('#editVehicleModal').modal('show');
        },
        error: function() {
            showAlert('danger', 'Error loading vehicle details');
        }
    });
}

function deleteVehicle(id) {
    if (confirm('Are you sure you want to delete this vehicle?')) {
        $.ajax({
            url: '../php/admin/delete_vehicle.php',
            method: 'POST',
            data: { id: id },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    loadVehicles();
                    showAlert('success', 'Vehicle deleted successfully!');
                } else {
                    showAlert('danger', data.error || 'Error deleting vehicle');
                }
            },
            error: function() {
                showAlert('danger', 'Error deleting vehicle');
            }
        });
    }
}

function getStatusColor(status) {
    switch(status.toLowerCase()) {
        case 'available': return 'success';
        case 'in_use': return 'primary';
        case 'maintenance': return 'warning';
        case 'charging': return 'info';
        default: return 'secondary';
    }
}

function getBatteryColor(level) {
    if (level <= 20) return 'bg-danger';
    if (level <= 50) return 'bg-warning';
    return 'bg-success';
}

function formatDate(dateString) {
    if (!dateString) return 'Never';
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
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
