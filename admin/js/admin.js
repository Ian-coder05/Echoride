$(document).ready(function () {
    // Toggle sidebar
    $('#sidebarCollapse').on('click', function () {
        $('#sidebar').toggleClass('active');
    });

    // Load dashboard data
    loadDashboardData();

    // Refresh dashboard data every 30 seconds
    setInterval(loadDashboardData, 30000);
    
    // Log to console that the page is ready
    console.log('Admin dashboard initialized');
});

function loadDashboardData() {
    console.log('Loading dashboard data...');
    
    // Load statistics
    $.ajax({
        url: '../php/admin/get_dashboard_stats.php',
        method: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log('Dashboard stats loaded:', data);
            $('#totalVehicles').text(data.totalVehicles);
            $('#activeRides').text(data.activeRides);
            $('#totalUsers').text(data.totalUsers);
            $('#carbonSaved').text(parseFloat(data.carbonSaved).toFixed(2));
        },
        error: function(xhr, status, error) {
            console.error('Error loading dashboard stats:', error);
            console.error('Response:', xhr.responseText);
            // Show error message in stats cards
            $('#totalVehicles, #activeRides, #totalUsers, #carbonSaved').text('Error');
        }
    });

    // Load recent rides
    $.ajax({
        url: '../php/admin/get_recent_rides.php',
        method: 'GET',
        dataType: 'json',
        success: function(rides) {
            console.log('Recent rides loaded:', rides);
            const tbody = $('#recentRidesTable tbody');
            tbody.empty();
            
            if (rides.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="4" class="text-center">No rides found</td>
                    </tr>
                `);
                return;
            }
            
            rides.forEach(ride => {
                tbody.append(`
                    <tr>
                        <td>${ride.id}</td>
                        <td>${ride.user_name}</td>
                        <td>${ride.vehicle_type} #${ride.vehicle_id}</td>
                        <td><span class="badge bg-${getStatusColor(ride.status)}">${ride.status}</span></td>
                    </tr>
                `);
            });
        },
        error: function(xhr, status, error) {
            console.error('Error loading recent rides:', error);
            console.error('Response:', xhr.responseText);
            // Show error message in table
            $('#recentRidesTable tbody').html(`
                <tr>
                    <td colspan="4" class="text-center text-danger">Error loading rides</td>
                </tr>
            `);
        }
    });

    // Load vehicle status
    $.ajax({
        url: '../php/admin/get_vehicle_status.php',
        method: 'GET',
        dataType: 'json',
        success: function(vehicles) {
            console.log('Vehicle status loaded:', vehicles);
            const tbody = $('#vehicleStatusTable tbody');
            tbody.empty();
            
            if (vehicles.length === 0) {
                tbody.append(`
                    <tr>
                        <td colspan="4" class="text-center">No vehicles found</td>
                    </tr>
                `);
                return;
            }
            
            vehicles.forEach(vehicle => {
                tbody.append(`
                    <tr>
                        <td>${vehicle.id}</td>
                        <td>${vehicle.type}</td>
                        <td><span class="badge bg-${getVehicleStatusColor(vehicle.status)}">${vehicle.status}</span></td>
                        <td>
                            <div class="progress">
                                <div class="progress-bar ${getBatteryColor(vehicle.battery_level)}" 
                                     role="progressbar" 
                                     style="width: ${vehicle.battery_level}%"
                                     aria-valuenow="${vehicle.battery_level}" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    ${vehicle.battery_level}%
                                </div>
                            </div>
                        </td>
                    </tr>
                `);
            });
        },
        error: function(xhr, status, error) {
            console.error('Error loading vehicle status:', error);
            console.error('Response:', xhr.responseText);
            // Show error message in table
            $('#vehicleStatusTable tbody').html(`
                <tr>
                    <td colspan="4" class="text-center text-danger">Error loading vehicles</td>
                </tr>
            `);
        }
    });
}

function getStatusColor(status) {
    switch(status.toLowerCase()) {
        case 'ongoing': return 'primary';
        case 'completed': return 'success';
        case 'cancelled': return 'danger';
        default: return 'secondary';
    }
}

function getVehicleStatusColor(status) {
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
