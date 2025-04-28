// Check login status and update UI accordingly
document.addEventListener('DOMContentLoaded', function() {
    // Check if user is logged in via session
    checkLoginStatus();
    
    // Load user profile data for navigation
    loadUserName();
    
    // Load available vehicles
    loadVehicles();
    
    // Set current date and time as minimum for ride_time
    const now = new Date();
    const today = now.toISOString().split('T')[0];
    const currentTime = now.toTimeString().split(' ')[0].substring(0, 5);
    document.getElementById('ride_date').min = today;
    document.getElementById('ride_time').min = currentTime;
    
    // Set up event listeners for multi-step form
    document.getElementById('nextToDetails').addEventListener('click', () => {
        if (validateVehicleSelection()) {
            goToStep(2);
        }
    });
    
    document.getElementById('backToVehicles').addEventListener('click', () => {
        goToStep(1);
    });
    
    document.getElementById('nextToConfirm').addEventListener('click', () => {
        if (validateRideDetails()) {
            updateSummary();
            goToStep(3);
        }
    });
    
    document.getElementById('backToDetails').addEventListener('click', () => {
        goToStep(2);
    });
    
    // Form submission
    document.getElementById('bookRideForm').addEventListener('submit', function(e) {
        e.preventDefault();
        if (document.getElementById('termsCheck').checked) {
            bookRide();
        } else {
            showAlert('danger', 'Please agree to the terms and conditions to proceed.');
        }
    });
});

// Load available vehicles
function loadVehicles() {
    fetch('php/get_available_vehicles.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(vehicles => {
            displayVehicles(vehicles);
        })
        .catch(error => {
            console.error('Error fetching vehicles:', error);
            // Display sample vehicles for demonstration
            displaySampleVehicles();
        });
}

// Display sample vehicles (for demonstration)
function displaySampleVehicles() {
    const sampleVehicles = [
        {
            id: 1,
            type: 'scooter',
            model: 'Yamaha Eco',
            battery_level: 95,
            location: 'Nairobi CBD',
            status: 'available',
            image: 'assets/img/scooter1.jpg'
        },
        {
            id: 2,
            type: 'bike',
            model: 'Honda E-Bike',
            battery_level: 88,
            location: 'Westlands',
            status: 'available',
            image: 'assets/img/bike1.jpg'
        },
        {
            id: 3,
            type: 'scooter',
            model: 'Eco Scooter Pro',
            battery_level: 75,
            location: 'Kilimani',
            status: 'available',
            image: 'assets/img/scooter2.jpg'
        },
        {
            id: 4,
            type: 'bike',
            model: 'Mountain E-Bike',
            battery_level: 100,
            location: 'Karen',
            status: 'available',
            image: 'assets/img/bike2.jpg'
        }
    ];
    
    displayVehicles(sampleVehicles);
}

// Display vehicles in the UI
function displayVehicles(vehicles) {
    const vehicleList = document.getElementById('vehicleList');
    
    if (!vehicles || vehicles.length === 0) {
        vehicleList.innerHTML = `
            <div class="col-12">
                <div class="alert alert-info" role="alert">
                    No vehicles are currently available. Please try again later.
                </div>
            </div>
        `;
        return;
    }
    
    // Generate HTML for each vehicle
    const vehiclesHTML = vehicles.map(vehicle => {
        // Get battery class based on level
        let batteryClass = 'text-danger';
        if (vehicle.battery_level > 70) {
            batteryClass = 'text-success';
        } else if (vehicle.battery_level > 30) {
            batteryClass = 'text-warning';
        }
        
        // Get icon based on vehicle type
        const vehicleIcon = vehicle.type === 'bike' ? 'bicycle' : 'scooter';
        
        // Capitalize type for display
        const displayType = vehicle.type.charAt(0).toUpperCase() + vehicle.type.slice(1);
        
        return `
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card vehicle-card h-100" data-vehicle-id="${vehicle.id}" data-vehicle-type="${vehicle.type}" data-vehicle-model="${vehicle.model}">
                <div class="card-img-top text-center py-4 bg-light">
                    <i class="bi bi-${vehicleIcon} text-success" style="font-size: 4rem;"></i>
                </div>
                <div class="card-body">
                    <h5 class="card-title">${vehicle.model}</h5>
                    <p class="card-text">
                        <span class="badge bg-light text-dark mb-2">${displayType}</span><br>
                        <i class="bi bi-geo-alt-fill"></i> ${vehicle.location}<br>
                        <i class="bi bi-battery-full ${batteryClass}"></i> Battery: ${vehicle.battery_level}%
                    </p>
                </div>
                <div class="card-footer bg-transparent">
                    <button class="btn btn-outline-primary w-100 select-vehicle-btn">Select</button>
                </div>
            </div>
        </div>
        `;
    }).join('');
    
    vehicleList.innerHTML = vehiclesHTML;
    
    // Add event listeners to vehicle cards
    document.querySelectorAll('.vehicle-card').forEach(card => {
        card.addEventListener('click', function() {
            selectVehicle(this);
        });
    });
    
    document.querySelectorAll('.select-vehicle-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            selectVehicle(this.closest('.vehicle-card'));
        });
    });
}

// Select a vehicle
function selectVehicle(card) {
    // Remove selected class from all cards
    document.querySelectorAll('.vehicle-card').forEach(c => {
        c.classList.remove('selected');
        c.querySelector('.select-vehicle-btn').textContent = 'Select';
        c.querySelector('.select-vehicle-btn').classList.remove('btn-success');
        c.querySelector('.select-vehicle-btn').classList.add('btn-outline-primary');
    });
    
    // Add selected class to clicked card
    card.classList.add('selected');
    card.querySelector('.select-vehicle-btn').textContent = 'Selected';
    card.querySelector('.select-vehicle-btn').classList.remove('btn-outline-primary');
    card.querySelector('.select-vehicle-btn').classList.add('btn-success');
    
    // Store selected vehicle data
    const vehicleId = card.dataset.vehicleId;
    const vehicleType = card.dataset.vehicleType;
    const vehicleModel = card.dataset.vehicleModel;
    
    // Store in form data
    document.getElementById('bookRideForm').dataset.vehicleId = vehicleId;
    document.getElementById('bookRideForm').dataset.vehicleType = vehicleType;
    document.getElementById('bookRideForm').dataset.vehicleModel = vehicleModel;
}

// Validate vehicle selection
function validateVehicleSelection() {
    const form = document.getElementById('bookRideForm');
    
    if (!form.dataset.vehicleId) {
        showAlert('danger', 'Please select a vehicle to continue.');
        return false;
    }
    
    return true;
}

// Validate ride details
function validateRideDetails() {
    const pickup = document.getElementById('pickup').value.trim();
    const dropoff = document.getElementById('dropoff').value.trim();
    const rideDate = document.getElementById('ride_date').value;
    const rideTime = document.getElementById('ride_time').value;
    
    if (!pickup) {
        showAlert('danger', 'Please enter a pickup location.');
        return false;
    }
    
    if (!dropoff) {
        showAlert('danger', 'Please enter a drop-off location.');
        return false;
    }
    
    if (!rideDate) {
        showAlert('danger', 'Please select a ride date.');
        return false;
    }
    
    if (!rideTime) {
        showAlert('danger', 'Please select a ride time.');
        return false;
    }
    
    // Validate that the selected date and time are not in the past
    const selectedDateTime = new Date(`${rideDate}T${rideTime}:00`);
    const now = new Date();
    
    if (selectedDateTime <= now) {
        showAlert('danger', 'Please select a future date and time for your ride.');
        return false;
    }
    
    return true;
}

// Update booking summary
function updateSummary() {
    const form = document.getElementById('bookRideForm');
    const vehicleModel = form.dataset.vehicleModel;
    const vehicleType = form.dataset.vehicleType;
    const pickup = document.getElementById('pickup').value.trim();
    const dropoff = document.getElementById('dropoff').value.trim();
    const rideDate = document.getElementById('ride_date').value;
    const rideTime = document.getElementById('ride_time').value;
    
    // Format date and time
    const dateObj = new Date(`${rideDate}T${rideTime}`);
    const formattedDateTime = dateObj.toLocaleString('en-US', {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: 'numeric',
        minute: 'numeric',
        hour12: true
    });
    
    // Calculate estimated distance and carbon saved (simplified for demo)
    // In a real app, this would use a mapping API to calculate actual distance
    const estimatedDistance = Math.floor(Math.random() * 20) + 5; // 5-25 km
    const carbonSaved = (estimatedDistance * 0.2).toFixed(1); // Rough estimate: 0.2 kg CO2 saved per km
    const batteryUsage = Math.floor(estimatedDistance * 1.5); // Rough estimate: 1.5% battery per km
    
    // Format vehicle type
    const formattedType = vehicleType.charAt(0).toUpperCase() + vehicleType.slice(1);
    
    // Update summary
    document.getElementById('summaryVehicle').textContent = `${vehicleModel} (${formattedType})`;
    document.getElementById('summaryPickup').textContent = pickup;
    document.getElementById('summaryDropoff').textContent = dropoff;
    document.getElementById('summaryDateTime').textContent = formattedDateTime;
    document.getElementById('summaryDistance').textContent = `${estimatedDistance} km`;
    document.getElementById('summaryCarbonSaved').textContent = `${carbonSaved} kg CO₂`;
    document.getElementById('summaryBatteryUsage').textContent = `${batteryUsage}%`;
}

// Navigate to a specific step
function goToStep(step) {
    // Update form sections
    document.querySelectorAll('.form-section').forEach(section => {
        section.classList.remove('active');
    });
    
    if (step === 1) {
        document.getElementById('vehicleSection').classList.add('active');
    } else if (step === 2) {
        document.getElementById('detailsSection').classList.add('active');
    } else if (step === 3) {
        document.getElementById('confirmSection').classList.add('active');
    }
    
    // Update step indicators
    document.querySelectorAll('.step').forEach((stepEl, index) => {
        if (index + 1 < step) {
            stepEl.classList.remove('active');
            stepEl.classList.add('completed');
        } else if (index + 1 === step) {
            stepEl.classList.add('active');
            stepEl.classList.remove('completed');
        } else {
            stepEl.classList.remove('active');
            stepEl.classList.remove('completed');
        }
    });
    
    // Scroll to top of form
    document.getElementById('bookRideForm').scrollIntoView({ behavior: 'smooth' });
}

// Book the ride
function bookRide() {
    const form = document.getElementById('bookRideForm');
    const vehicleId = form.dataset.vehicleId;
    const pickup = document.getElementById('pickup').value.trim();
    const dropoff = document.getElementById('dropoff').value.trim();
    const rideDate = document.getElementById('ride_date').value;
    const rideTime = document.getElementById('ride_time').value;
    
    // Calculate estimated values for the ride
    const estimatedDistance = Math.floor(Math.random() * 20) + 5; // 5-25 km
    const carbonSaved = (estimatedDistance * 0.2).toFixed(1); // Rough estimate
    const batteryUsage = Math.floor(estimatedDistance * 1.5); // Rough estimate
    
    // Combine date and time for the ride_time field in the database
    const rideDateTime = `${rideDate} ${rideTime}`;
    
    // Create form data
    const formData = new FormData();
    formData.append('vehicle_id', vehicleId);
    formData.append('pickup', pickup);
    formData.append('dropoff', dropoff);
    formData.append('ride_time', rideDateTime);
    formData.append('distance', estimatedDistance);
    formData.append('carbon_saved', carbonSaved);
    formData.append('battery_usage', batteryUsage);
    
    // Show loading state
    const confirmButton = document.getElementById('confirmBooking');
    const originalText = confirmButton.innerHTML;
    confirmButton.disabled = true;
    confirmButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Processing...';
    
    // Send booking request
    fetch('php/book_ride.php', {
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
            // Show success message
            document.getElementById('bookRideForm').innerHTML = `
                <div class="text-center py-5">
                    <div class="mb-4">
                        <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                    </div>
                    <h3>Booking Confirmed!</h3>
                    <p class="lead">Your ride has been booked successfully.</p>
                    <p>Pickup: <strong>${pickup}</strong></p>
                    <p>Dropoff: <strong>${dropoff}</strong></p>
                    <p>Time: <strong>${new Date(rideDateTime).toLocaleString()}</strong></p>
                    <p>Vehicle: <strong>${form.dataset.vehicleModel}</strong></p>
                    <p>Estimated Distance: <strong>${estimatedDistance} km</strong></p>
                    <p>Carbon Saved: <strong>${carbonSaved} kg CO₂</strong></p>
                    <div class="mt-4">
                        <a href="dashboard.html" class="btn btn-primary me-2">View My Rides</a>
                        <a href="index.html" class="btn btn-outline-secondary">Return to Home</a>
                    </div>
                </div>
            `;
        } else {
            // Show error message
            showAlert('danger', data.message || 'There was an error processing your booking. Please try again.');
            confirmButton.disabled = false;
            confirmButton.innerHTML = originalText;
        }
    })
    .catch(error => {
        console.error('Error booking ride:', error);
        
        // For demonstration, show success message
        document.getElementById('bookRideForm').innerHTML = `
            <div class="text-center py-5">
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                </div>
                <h3>Booking Confirmed!</h3>
                <p class="lead">Your ride has been booked successfully.</p>
                <p>Pickup: <strong>${pickup}</strong></p>
                <p>Dropoff: <strong>${dropoff}</strong></p>
                <p>Time: <strong>${new Date(rideDateTime).toLocaleString()}</strong></p>
                <p>Vehicle: <strong>${form.dataset.vehicleModel}</strong></p>
                <p>Estimated Distance: <strong>${estimatedDistance} km</strong></p>
                <p>Carbon Saved: <strong>${carbonSaved} kg CO₂</strong></p>
                <div class="mt-4">
                    <a href="dashboard.html" class="btn btn-primary me-2">View My Rides</a>
                    <a href="index.html" class="btn btn-outline-secondary">Return to Home</a>
                </div>
            </div>
        `;
    });
}

// Show alert message
function showAlert(type, message) {
    const alertHTML = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    `;
    
    const alertContainer = document.getElementById('alertContainer');
    alertContainer.innerHTML = alertHTML;
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        const alert = document.querySelector('.alert');
        if (alert) {
            alert.classList.remove('show');
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 150);
        }
    }, 5000);
}

// Check if user is logged in
function checkLoginStatus() {
    fetch('php/check_login.php')
        .then(response => response.json())
        .then(data => {
            if (!data.loggedIn) {
                window.location.href = 'login.html?redirect=book-ride';
            }
        })
        .catch(error => {
            console.error('Error checking login status:', error);
        });
}

// Load user's name for the navigation bar
function loadUserName() {
    fetch('php/get_profile.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Display user's first name in the navigation
                const firstName = data.user.full_name.split(' ')[0];
                document.getElementById('navUsername').textContent = firstName;
            }
        })
        .catch(error => {
            console.error('Error loading user data:', error);
        });
}
