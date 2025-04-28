$(document).ready(function () {
    // Toggle sidebar
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
    });

    // Load initial data
    loadRideStats();
    loadRides();

    // Handle filter application
    $('#applyFilters').click(loadRides);
});

function loadRideStats() {
    $.ajax({
        url: '../php/admin/get_ride_stats.php',
        method: 'GET',
        success: function(response) {
            const stats = JSON.parse(response);
            $('#totalRides').text(stats.totalRides);
            $('#activeRides').text(stats.activeRides);
            $('#totalDistance').text(stats.totalDistance.toFixed(2) + ' km');
            $('#carbonSaved').text(stats.carbonSaved.toFixed(2) + ' kg');
        },
        error: function() {
            showAlert('danger', 'Error loading ride statistics');
        }
    });
}

function loadRides() {
    const filters = {
        status: $('#statusFilter').val(),
        vehicleType: $('#vehicleTypeFilter').val(),
        search: $('#searchInput').val()
    };

    $.ajax({
        url: '../php/admin/get_rides.php',
        method: 'GET',
        data: filters,
        success: function(response) {
            const rides = JSON.parse(response);
            const tbody = $('#ridesTable tbody');
            tbody.empty();
            
            rides.forEach(ride => {
                tbody.append(`
                    <tr>
                        <td>${ride.id}</td>
                        <td>${ride.user_name}</td>
                        <td>${ride.vehicle_type} #${ride.vehicle_id}</td>
                        <td>${ride.pickup_location}</td>
                        <td>${ride.dropoff_location}</td>
                        <td>${ride.distance.toFixed(2)} km</td>
                        <td>${ride.carbon_saved.toFixed(2)} kg</td>
                        <td>${formatDate(ride.start_time)}</td>
                        <td>${ride.end_time ? formatDate(ride.end_time) : 'Ongoing'}</td>
                        <td>
                            <span class="badge bg-${getRideStatusColor(ride.status)}">
                                ${ride.status}
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewRideDetails(${ride.id})">
                                <i class="bi bi-info-circle"></i>
                            </button>
                            ${ride.status === 'ongoing' ? `
                                <button class="btn btn-sm btn-success" onclick="completeRide(${ride.id})">
                                    <i class="bi bi-check-circle"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="cancelRide(${ride.id})">
                                    <i class="bi bi-x-circle"></i>
                                </button>
                            ` : ''}
                        </td>
                    </tr>
                `);
            });
        },
        error: function() {
            showAlert('danger', 'Error loading rides');
        }
    });
}

function viewRideDetails(id) {
    $.ajax({
        url: '../php/admin/get_ride_details.php',
        method: 'GET',
        data: { id: id },
        success: function(response) {
            const details = JSON.parse(response);
            
            // Fill user information
            $('#rideUserName').text(details.user_name);
            $('#rideUserEmail').text(details.user_email);
            $('#rideUserTotalRides').text(details.user_total_rides);
            
            // Fill vehicle information
            $('#rideVehicleType').text(details.vehicle_type);
            $('#rideVehicleModel').text(details.vehicle_model);
            $('#rideVehicleBattery').text(details.battery_level + '%');
            
            // Fill ride information
            $('#ridePickup').text(details.pickup_location);
            $('#rideDropoff').text(details.dropoff_location);
            $('#rideDistance').text(details.distance.toFixed(2) + ' km');
            $('#rideCarbonSaved').text(details.carbon_saved.toFixed(2) + ' kg');
            $('#rideDuration').text(formatDuration(details.duration));
            $('#rideCost').text('$' + details.cost.toFixed(2));
            
            $('#rideDetailsModal').modal('show');
        },
        error: function() {
            showAlert('danger', 'Error loading ride details');
        }
    });
}

function completeRide(id) {
    if (confirm('Are you sure you want to mark this ride as completed?')) {
        $.ajax({
            url: '../php/admin/complete_ride.php',
            method: 'POST',
            data: { id: id },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    loadRides();
                    loadRideStats();
                    showAlert('success', 'Ride marked as completed');
                } else {
                    showAlert('danger', data.error || 'Error completing ride');
                }
            },
            error: function() {
                showAlert('danger', 'Error completing ride');
            }
        });
    }
}

function cancelRide(id) {
    if (confirm('Are you sure you want to cancel this ride?')) {
        $.ajax({
            url: '../php/admin/cancel_ride.php',
            method: 'POST',
            data: { id: id },
            success: function(response) {
                const data = JSON.parse(response);
                if (data.success) {
                    loadRides();
                    loadRideStats();
                    showAlert('success', 'Ride cancelled successfully');
                } else {
                    showAlert('danger', data.error || 'Error cancelling ride');
                }
            },
            error: function() {
                showAlert('danger', 'Error cancelling ride');
            }
        });
    }
}

function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
}

function formatDuration(minutes) {
    if (minutes < 60) {
        return minutes + ' minutes';
    }
    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;
    return hours + ' hours ' + remainingMinutes + ' minutes';
}

function getRideStatusColor(status) {
    switch(status.toLowerCase()) {
        case 'completed': return 'success';
        case 'ongoing': return 'primary';
        case 'cancelled': return 'danger';
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
