$(document).ready(function() {
    // Toggle sidebar
    $('#sidebarCollapse').on('click', function() {
        $('#sidebar').toggleClass('active');
    });

    // Load initial data
    loadMaintenanceTasks();
    loadVehicles();
    loadTechnicians();

    // Event listeners
    $('#applyFilters').on('click', loadMaintenanceTasks);
    $('#saveMaintenance').on('click', addMaintenanceTask);
    $('#updateMaintenance').on('click', updateMaintenanceTask);

    // Initialize datetime inputs to current date/time
    $('input[type="datetime-local"]').val(new Date().toISOString().slice(0, 16));
});

function loadMaintenanceTasks() {
    const filters = {
        status: $('#statusFilter').val(),
        priority: $('#priorityFilter').val(),
        search: $('#searchInput').val()
    };

    $.ajax({
        url: '../php/admin/get_maintenance.php',
        method: 'GET',
        data: filters,
        success: function(response) {
            const data = JSON.parse(response);
            updateMaintenanceStats(data.stats);
            populateMaintenanceTable(data.tasks);
        },
        error: function() {
            showAlert('danger', 'Error loading maintenance tasks');
        }
    });
}

function updateMaintenanceStats(stats) {
    $('#totalTasks').text(stats.total || 0);
    $('#pendingTasks').text(stats.pending || 0);
    $('#completedTasks').text(stats.completed || 0);
    $('#overdueTasks').text(stats.overdue || 0);
}

function populateMaintenanceTable(tasks) {
    const tbody = $('#maintenanceTable tbody');
    tbody.empty();

    tasks.forEach(task => {
        const row = `
            <tr>
                <td>${task.id}</td>
                <td>${task.vehicle_name} (${task.vehicle_type})</td>
                <td>${formatMaintenanceType(task.type)}</td>
                <td>${task.description}</td>
                <td><span class="badge bg-${getPriorityColor(task.priority)}">${task.priority}</span></td>
                <td><span class="badge bg-${getStatusColor(task.status)}">${formatStatus(task.status)}</span></td>
                <td>${formatDate(task.due_date)}</td>
                <td>${task.assigned_to_name || 'Unassigned'}</td>
                <td>
                    <button class="btn btn-sm btn-info" onclick="viewMaintenanceDetails(${task.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-primary" onclick="editMaintenanceTask(${task.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteMaintenanceTask(${task.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

function loadVehicles() {
    $.ajax({
        url: '../php/admin/get_vehicles.php',
        method: 'GET',
        success: function(response) {
            const vehicles = JSON.parse(response);
            const vehicleSelects = $('select[name="vehicle_id"]');
            vehicleSelects.empty();
            
            vehicles.forEach(vehicle => {
                const option = `<option value="${vehicle.id}">${vehicle.model} (${vehicle.type})</option>`;
                vehicleSelects.append(option);
            });
        },
        error: function() {
            showAlert('danger', 'Error loading vehicles');
        }
    });
}

function loadTechnicians() {
    $.ajax({
        url: '../php/admin/get_technicians.php',
        method: 'GET',
        success: function(response) {
            const technicians = JSON.parse(response);
            const technicianSelects = $('select[name="assigned_to"]');
            technicianSelects.empty();
            
            technicianSelects.append('<option value="">Unassigned</option>');
            technicians.forEach(tech => {
                const option = `<option value="${tech.id}">${tech.name}</option>`;
                technicianSelects.append(option);
            });
        },
        error: function() {
            showAlert('danger', 'Error loading technicians');
        }
    });
}

function addMaintenanceTask() {
    const form = $('#addMaintenanceForm');
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }

    const formData = form.serializeArray();
    const data = {};
    formData.forEach(item => {
        data[item.name] = item.value;
    });

    $.ajax({
        url: '../php/admin/add_maintenance.php',
        method: 'POST',
        data: data,
        success: function(response) {
            showAlert('success', 'Maintenance task added successfully');
            $('#addMaintenanceModal').modal('hide');
            form[0].reset();
            loadMaintenanceTasks();
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseText || 'Error adding maintenance task');
        }
    });
}

function editMaintenanceTask(id) {
    $.ajax({
        url: '../php/admin/get_maintenance.php',
        method: 'GET',
        data: { id: id },
        success: function(response) {
            const data = JSON.parse(response);
            const task = data.tasks.find(t => t.id === id);
            if (!task) {
                showAlert('danger', 'Maintenance task not found');
                return;
            }

            const form = $('#editMaintenanceForm');
            form.find('input[name="id"]').val(task.id);
            form.find('select[name="vehicle_id"]').val(task.vehicle_id);
            form.find('select[name="type"]').val(task.type);
            form.find('textarea[name="description"]').val(task.description);
            form.find('select[name="priority"]').val(task.priority);
            form.find('select[name="status"]').val(task.status);
            form.find('input[name="due_date"]').val(task.due_date.slice(0, 16));
            form.find('select[name="assigned_to"]').val(task.assigned_to);
            form.find('textarea[name="notes"]').val(task.notes);

            $('#editMaintenanceModal').modal('show');
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseText || 'Error loading maintenance task');
        }
    });
}

function updateMaintenanceTask() {
    const form = $('#editMaintenanceForm');
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }

    const formData = form.serializeArray();
    const data = {};
    formData.forEach(item => {
        data[item.name] = item.value;
    });

    $.ajax({
        url: '../php/admin/update_maintenance.php',
        method: 'POST',
        data: data,
        success: function(response) {
            showAlert('success', 'Maintenance task updated successfully');
            $('#editMaintenanceModal').modal('hide');
            loadMaintenanceTasks();
        },
        error: function(xhr) {
            showAlert('danger', xhr.responseText || 'Error updating maintenance task');
        }
    });
}

function deleteMaintenanceTask(id) {
    if (confirm('Are you sure you want to delete this maintenance task?')) {
        $.ajax({
            url: '../php/admin/delete_maintenance.php',
            method: 'POST',
            data: { id: id },
            success: function(response) {
                showAlert('success', 'Maintenance task deleted successfully');
                loadMaintenanceTasks();
            },
            error: function() {
                showAlert('danger', 'Error deleting maintenance task');
            }
        });
    }
}

function viewMaintenanceDetails(id) {
    $.ajax({
        url: '../php/admin/get_maintenance_details.php',
        method: 'GET',
        data: { id: id },
        success: function(response) {
            const details = JSON.parse(response);
            
            // Update vehicle information
            $('#detailVehicleId').text(details.vehicle_id);
            $('#detailVehicleType').text(details.vehicle_type);
            $('#detailVehicleModel').text(details.vehicle_model);
            $('#detailVehicleStatus').text(details.vehicle_status);

            // Update maintenance history
            const historyTable = $('#maintenanceHistoryTable tbody');
            historyTable.empty();
            
            details.history.forEach(record => {
                const row = `
                    <tr>
                        <td>${formatDate(record.date)}</td>
                        <td>${formatMaintenanceType(record.type)}</td>
                        <td><span class="badge bg-${getStatusColor(record.status)}">${formatStatus(record.status)}</span></td>
                    </tr>
                `;
                historyTable.append(row);
            });

            // Update timeline
            const timeline = $('.timeline');
            timeline.empty();
            
            details.timeline.forEach(event => {
                const item = `
                    <div class="timeline-item">
                        <div class="timeline-date">${formatDate(event.date)}</div>
                        <div class="timeline-content">
                            <h6>${event.title}</h6>
                            <p>${event.description}</p>
                        </div>
                    </div>
                `;
                timeline.append(item);
            });

            $('#maintenanceDetailsModal').modal('show');
        },
        error: function() {
            showAlert('danger', 'Error loading maintenance details');
        }
    });
}

// Utility functions
function formatMaintenanceType(type) {
    const types = {
        routine: 'Routine Check',
        repair: 'Repair',
        battery: 'Battery Service',
        tire: 'Tire Service',
        software: 'Software Update'
    };
    return types[type] || type;
}

function formatStatus(status) {
    const statuses = {
        pending: 'Pending',
        in_progress: 'In Progress',
        completed: 'Completed',
        overdue: 'Overdue'
    };
    return statuses[status] || status;
}

function getPriorityColor(priority) {
    const colors = {
        high: 'danger',
        medium: 'warning',
        low: 'success'
    };
    return colors[priority] || 'secondary';
}

function getStatusColor(status) {
    const colors = {
        pending: 'warning',
        in_progress: 'info',
        completed: 'success',
        overdue: 'danger'
    };
    return colors[status] || 'secondary';
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleString();
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
