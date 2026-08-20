<?php 
include('db.php'); 
include('header.php');

// 1. SECURITY: Redirect to login if not authenticated
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    echo "<script>alert('Please login to view your profile'); window.location='login.php';</script>";
    exit();
}

// 2. Use session userID matching your primary key structure
$current_user_id = (int)$_SESSION['userID']; 

// 3. Fetch real user information using exact database column names
$email_val = "";
$user_val  = $_SESSION['username'];

$stmt = $conn->prepare("SELECT username, email FROM user WHERE userID = ?");
if ($stmt) {
    $stmt->bind_param("i", $current_user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $user_val  = $row['username'];
        $email_val = $row['email'];
    }
    $stmt->close();
}
?>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />

<div class="search-page-bg">
    <div class="container py-5">
        
        <div class="mb-5 px-xl-5 text-center">
            <h1 class="fw-bold text-white">My Account Settings</h1>
            <p class="text-white-50">
                Hello, <?php echo htmlspecialchars($user_val); ?>. 
                Keep your profile configurations updated here.
            </p>
        </div>

        <div class="container-fluid px-xl-5">
            <div class="row">
                
                <div class="col-lg-6 mb-4">
                    <div class="card search-product-card p-4 shadow-sm text-dark mb-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-4 border-bottom pb-3">
                                <i class="fas fa-user-circle fa-3x text-success"></i>
                                <div>
                                    <h5 class="fw-bold mb-0">Profile Information</h5>
                                    <p class="text-muted small mb-0">Modify account identification data and credentials</p>
                                </div>
                            </div>

                            <div id="alert-status-msg" style="display:none;" class="alert alert-dismissible fade show" role="alert"></div>

                            <form id="profileUpdateForm">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small fw-bold text-uppercase tracking-wider">Username</label>
                                        <input type="text" name="username" class="form-control" value="<?php echo htmlspecialchars($user_val); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label small fw-bold text-uppercase tracking-wider">Email Address</label>
                                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email_val); ?>" required>
                                    </div>
                                </div>

                                <div class="mt-3 p-3 bg-light rounded-3">
                                    <h6 class="fw-bold text-dark mb-2" style="font-size:0.85rem;">
                                        <i class="fas fa-key me-2 text-muted"></i>Change Password
                                    </h6>
                                    <p class="text-muted mb-3" style="font-size:0.75rem;">Leave empty if you do not want to change your current password.</p>
                                    <div class="row">
                                        <div class="col-md-6 mb-2">
                                            <input type="password" name="new_password" class="form-control form-control-sm" placeholder="New Password">
                                        </div>
                                        <div class="col-md-6 mb-2">
                                            <input type="password" name="confirm_password" class="form-control form-control-sm" placeholder="Confirm New Password">
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-dark rounded-pill px-4 btn-sm fw-bold">
                                        <i class="fas fa-save me-2"></i>Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 mb-4">
                    <div class="card search-product-card p-4 shadow-sm text-dark mb-4 h-100 d-flex flex-column justify-content-between">
                        <div>
                            <div class="d-flex align-items-center gap-3 mb-3 border-bottom pb-3">
                                <i class="fas fa-charging-station fa-3x text-primary"></i>
                                <div>
                                    <h5 class="fw-bold mb-0">Nearest E-Waste Disposal Facilities</h5>
                                    <p class="text-muted small mb-0">Responsibly drop off electronic products near you</p>
                                </div>
                            </div>
                            
                            <p class="text-muted small mb-3">
                                <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                Allow location access to view verified electronics recycling centers around your current region.
                            </p>
                        </div>

                        <div id="ewaste-map" class="rounded-3 border bg-light shadow-inner flex-grow-1" style="min-height: 380px; position: relative; z-index: 1;">
                            <div class="d-flex h-100 align-items-center justify-content-center text-muted">
                                <div>
                                    <i class="fas fa-circle-notch fa-spin fa-2x mb-2 d-block text-center"></i>
                                    <span>Retrieving map layout information...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div> <div class="row mt-2">
                <div class="col-12">
                    <div class="card search-product-card p-4 shadow-sm text-dark border-start border-danger border-4">
                        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center gap-3">
                            <div>
                                <h6 class="fw-bold text-danger mb-1"><i class="fas fa-exclamation-triangle me-2"></i>Danger Zone</h6>
                                <p class="text-muted mb-0 small">Permanently wipe your account records, configurations, and bookmarks from GreenChoice.</p>
                            </div>
                            <div>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 fw-bold" data-bs-toggle="modal" data-bs-target="#deleteProfileModal">
                                    Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="deleteProfileModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered text-dark">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-light border-0">
                <h6 class="modal-title fw-bold text-danger"><i class="fas fa-user-slash me-2"></i>Confirm Account Deletion</h6>
                <button type="button" class="btn-close small" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 text-center">
                <i class="fas fa-exclamation-circle fa-3x text-danger opacity-75 mb-3"></i>
                <h5 class="fw-bold">Are you absolutely sure?</h5>
                <p class="text-muted small mb-0 px-3">This system operation completely destroys your account. This action cannot be reverted.</p>
            </div>
            <div class="modal-footer border-0 bg-light d-flex justify-content-between">
                <button type="button" class="btn btn-sm btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmDeleteBtn" class="btn btn-sm btn-danger rounded-pill px-3">Permanently Delete</button>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<script>
$(document).ready(function() {
    // ==========================================
    // 1. MAP DISCOVERY SYSTEM INITIALIZATION
    // ==========================================
    var defaultLat = 2.2500; // Default fallback latitude coordinate (Malacca region context)
    var defaultLng = 102.4300; 
    
    // Set up Leaflet Map instance container
    $('#ewaste-map').html(''); 
    var map = L.map('ewaste-map').setView([defaultLat, defaultLng], 12);

    // Apply high-quality OpenStreetMap raster tiles skin layer
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    // Forces Leaflet to recalculate rendering container bounds to fit layout properly
    setTimeout(function() {
        map.invalidateSize();
    }, 200);

    // DYNAMIC RETRIEVAL PIPELINE (Option B)
    // Make an AJAX request stream to fetch verified facility locations from the DB
    $.ajax({
        url: 'fetch_facilities.php',
        method: 'GET',
        dataType: 'json',
        success: function(facilities) {
            if(facilities && facilities.length > 0) {
                facilities.forEach(function(loc) {
                    // Assemble a comprehensive pop-up text bubble layout via template literals
                    var popupContent = `
                        <div style="font-family: inherit; font-size: 0.85rem; color:#333; line-height: 1.4;">
                            <b class="text-success d-block mb-1" style="font-size:0.95rem;"><i class="fas fa-charging-station me-1"></i>${loc.name}</b>
                            <p class="mb-1 text-muted" style="font-size:0.8rem;"><b>Address:</b> ${loc.addr}</p>
                            <p class="mb-1" style="font-size:0.8rem;"><b>Accepts:</b> <span class="badge bg-light text-dark border">${loc.items}</span></p>
                            <small class="text-secondary d-block mt-1" style="font-size:0.75rem;"><i class="far fa-clock me-1"></i>${loc.hours}</small>
                        </div>
                    `;

                    // Generate a standard Leaflet pin marker dropped on the coordinates
                    L.marker([loc.lat, loc.lng]).addTo(map).bindPopup(popupContent);
                });
            }
        },
        error: function(err) {
            console.error("Failed to dynamically populate e-waste markers from backend service map stream.", err);
        }
    });

    // Extract native browser hardware geolocation coordinates
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(position) {
            var userLat = position.coords.latitude;
            var userLng = position.coords.longitude;
            
            // Re-center tracking viewport directly over user coordinates
            map.setView([userLat, userLng], 13);
            map.invalidateSize();
            
            // Render a custom high-visibility circular tracking marker node for user coordinates
            var userMarker = L.circleMarker([userLat, userLng], {
                radius: 10,
                fillColor: "#0d6efd",
                color: "#fff",
                weight: 3,
                opacity: 1,
                fillOpacity: 0.8
            }).addTo(map).bindPopup("<b>Your Current Location</b>").openPopup();
            
        }, function() {
            console.log("Geolocation access denied by user. Using default regional view.");
        });
    }

    // ==========================================
    // 2. PROFILE UPDATE HANDLER via AJAX
    // ==========================================
    $('#profileUpdateForm').on('submit', function(e) {
        e.preventDefault();
        var alertBox = $('#alert-status-msg');
        alertBox.hide().removeClass('alert-success alert-danger');

        $.ajax({
            url: 'profile_process.php',
            method: 'POST',
            data: $(this).serialize() + '&action=update',
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    alertBox.addClass('alert-success').text(res.message).fadeIn(300);
                    if(res.new_username) {
                        $('.text-white-50 strong, .mb-5 h1').next().html('Hello, ' + res.new_username + '. Keep your profile configurations updated here.');
                    }
                } else {
                    alertBox.addClass('alert-danger').text(res.message).fadeIn(300);
                }
            },
            error: function() {
                alertBox.addClass('alert-danger').text('Connection error processing changes.').fadeIn(300);
            }
        });
    });

    // ==========================================
    // 3. DELETION OPERATION HANDLER via AJAX
    // ==========================================
    $('#confirmDeleteBtn').on('click', function() {
        var btn = $(this);
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');

        $.ajax({
            url: 'profile_process.php',
            method: 'POST',
            data: { action: 'delete' },
            dataType: 'json',
            success: function(res) {
                if(res.status === 'success') {
                    alert('Account removed successfully.');
                    window.location.href = 'logout.php';
                } else {
                    alert('Error: ' + res.message);
                    btn.prop('disabled', false).text('Permanently Delete');
                    $('#deleteProfileModal').modal('hide');
                }
            },
            error: function() {
                alert('Connection error handling account termination.');
                btn.prop('disabled', false).text('Permanently Delete');
            }
        });
    });
});
</script>

<?php include('footer.php'); ?>