<?php
require_once '../config/database.php';
require_once '../includes/classes/Auth.php';

$auth = new Auth($conn);

// Redirect to login if not logged in
if (!$auth->isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user = $auth->getUser();
$page_title = 'Saved Vehicles | CAR LISTO';

// Get saved vehicles for the current user
$savedVehicles = [];
try {
    $stmt = $conn->prepare("
        SELECT v.* 
        FROM vehicles v
        JOIN saved_vehicles sv ON v.id = sv.vehicle_id
        WHERE sv.user_id = ? AND v.status = 'available'
        ORDER BY sv.saved_at DESC
    ");
    $stmt->bind_param("i", $user['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $savedVehicles = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching saved vehicles: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="container py-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-lg-3 mb-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <div class="avatar-circle bg-primary text-white d-inline-flex align-items-center justify-content-center">
                            <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                        </div>
                    </div>
                    <h5 class="mb-1"><?php echo htmlspecialchars($user['name']); ?></h5>
                    <p class="text-muted small"><?php echo htmlspecialchars($user['email']); ?></p>
                    <hr>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php"><i class="fas fa-user me-2"></i>My Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="saved-vehicles.php"><i class="fas fa-heart me-2"></i>Saved Vehicles</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="inquiries.php"><i class="fas fa-envelope me-2"></i>My Inquiries</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="test-drives.php"><i class="fas fa-car me-2"></i>Test Drives</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a>
                        </li>
                        <li class="nav-item mt-2">
                            <a class="btn btn-outline-danger w-100" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="h4 mb-0">Saved Vehicles</h2>
                <a href="../inventory.php" class="btn btn-primary">
                    <i class="fas fa-plus me-1"></i> Browse More Vehicles
                </a>
            </div>

            <?php if (empty($savedVehicles)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="fas fa-heart text-muted" style="font-size: 4rem; opacity: 0.3;"></i>
                        </div>
                        <h3 class="h5">No saved vehicles yet</h3>
                        <p class="text-muted mb-4">Save vehicles you're interested in to find them easily later.</p>
                        <a href="../inventory.php" class="btn btn-primary">Browse Inventory</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($savedVehicles as $vehicle): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card h-100 border-0 shadow-sm">
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars($vehicle['image_url'] ?? '../assets/images/placeholder-car.jpg'); ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?>"
                                         style="height: 180px; object-fit: cover;">
                                    <button class="btn btn-danger btn-sm position-absolute top-0 end-0 m-2" 
                                            onclick="removeFromSaved(<?php echo $vehicle['id']; ?>)"
                                            data-bs-toggle="tooltip" 
                                            title="Remove from saved">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="card-title mb-1">
                                            <a href="../car-details.php?id=<?php echo $vehicle['id']; ?>" class="text-dark text-decoration-none">
                                                <?php echo htmlspecialchars($vehicle['year'] . ' ' . $vehicle['make'] . ' ' . $vehicle['model']); ?>
                                            </a>
                                        </h5>
                                        <div class="text-success fw-bold">
                                            $<?php echo number_format($vehicle['price']); ?>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        <span class="me-2">
                                            <i class="fas fa-tachometer-alt me-1"></i> 
                                            <?php echo number_format($vehicle['mileage']); ?> mi
                                        </span>
                                        <span class="me-2">
                                            <i class="fas fa-gas-pump me-1"></i> 
                                            <?php echo htmlspecialchars(ucfirst($vehicle['fuel_type'])); ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-cog me-1"></i> 
                                            <?php echo htmlspecialchars(ucfirst($vehicle['transmission'])); ?>
                                        </span>
                                    </p>
                                    <div class="d-grid gap-2">
                                        <a href="../car-details.php?id=<?php echo $vehicle['id']; ?>" class="btn btn-outline-primary">
                                            <i class="fas fa-eye me-1"></i> View Details
                                        </a>
                                        <button class="btn btn-primary" onclick="inquiryModal(<?php echo $vehicle['id']; ?>, '<?php echo htmlspecialchars($vehicle['make'] . ' ' . $vehicle['model']); ?>')">
                                            <i class="fas fa-envelope me-1"></i> Send Inquiry
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Inquiry Modal -->
<div class="modal fade" id="inquiryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Inquiry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="inquiryForm" method="POST" action="submit-inquiry.php">
                <div class="modal-body">
                    <input type="hidden" name="vehicle_id" id="inquiryVehicleId">
                    <div class="mb-3">
                        <label for="inquiryMessage" class="form-label">Your Message</label>
                        <textarea class="form-control" id="inquiryMessage" name="message" rows="4" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="preferredContact" class="form-label">Preferred Contact Method</label>
                        <select class="form-select" id="preferredContact" name="preferred_contact" required>
                            <option value="">Select...</option>
                            <option value="email">Email</option>
                            <option value="phone">Phone</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="testDriveCheck" name="request_test_drive">
                        <label class="form-check-label" for="testDriveCheck">
                            I'd like to schedule a test drive
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Send Inquiry</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Function to handle vehicle inquiry modal
function inquiryModal(vehicleId, vehicleName) {
    document.getElementById('inquiryVehicleId').value = vehicleId;
    document.querySelector('#inquiryModal .modal-title').textContent = `Inquire About: ${vehicleName}`;
    var inquiryModal = new bootstrap.Modal(document.getElementById('inquiryModal'));
    inquiryModal.show();
}

// Function to remove vehicle from saved
function removeFromSaved(vehicleId) {
    if (confirm('Are you sure you want to remove this vehicle from your saved list?')) {
        fetch('remove-saved-vehicle.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `vehicle_id=${vehicleId}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Remove the vehicle card from the UI
                const vehicleCard = document.querySelector(`[data-vehicle-id="${vehicleId}"]`);
                if (vehicleCard) {
                    vehicleCard.closest('.col-md-6').remove();
                    
                    // Check if no vehicles left
                    if (document.querySelectorAll('.col-md-6').length === 0) {
                        location.reload(); // Reload to show empty state
                    }
                }
                
                // Show success message
                alert('Vehicle removed from your saved list.');
            } else {
                alert('Failed to remove vehicle. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
    }
}

// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl);
});
</script>

<style>
.avatar-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    font-size: 36px;
    font-weight: bold;
    line-height: 1;
    text-align: center;
    display: flex;
    align-items: center;
    justify-content: center;
}
.nav-link {
    color: #495057;
    padding: 0.5rem 1rem;
    border-radius: 0.25rem;
    margin-bottom: 0.25rem;
}
.nav-link:hover, .nav-link.active {
    background-color: #f8f9fa;
    color: #0d6efd;
}
.card {
    transition: transform 0.2s;
}
.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
</style>

<?php include 'includes/footer.php'; ?>
