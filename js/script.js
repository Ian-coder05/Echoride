// Loader animation
window.addEventListener("load", function () {
    const loader = document.getElementById("loader");
    loader.style.display = "none";
  });
  
  // Back to top button
  let backToTop = document.getElementById("backToTop");
  
  window.onscroll = function () {
    if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
      backToTop.style.display = "block";
    } else {
      backToTop.style.display = "none";
    }
  };
  
  backToTop.onclick = function () {
    window.scrollTo({ top: 0, behavior: "smooth" });
  };
  document.addEventListener('DOMContentLoaded', () => {
    fetch('php/get_dashboard.php')
      .then(res => res.json())
      .then(data => {
        const rideList = document.getElementById('rideList');
        rideList.innerHTML = data.map(ride => `
          <div class="col-md-6">
            <div class="card shadow-sm hover-card">
              <div class="card-body">
                <h5 class="card-title">${ride.pickup} ➔ ${ride.dropoff}</h5>
                <p class="card-text">
                  <strong>Start:</strong> ${ride.ride_time}<br>
                  <strong>End:</strong> ${ride.end_time}<br>
                  <strong>Duration:</strong> ${ride.duration}<br>
                  <strong>Cost:</strong> ${ride.cost}<br>
                  <strong>Status:</strong> ${ride.status}
                </p>
              </div>
            </div>
          </div>
        `).join('');
      });
  });