<?php 
include('db.php'); 
include('header.php');

// 1. SECURITY: Redirect to login if not authenticated
if (!isset($_SESSION['auth']) || $_SESSION['auth'] !== true) {
    echo "<script>alert('Please login to view your bookmarks'); window.location='login.php';</script>";
    exit();
}

// 2. Use session userID
$current_user_id = (int)$_SESSION['userID']; 

// 3. Fetch all bookmarked products for this user
$sql = "SELECT p.*, f.favoriteID, f.created_at AS date_saved
        FROM favorite f 
        JOIN product p ON f.productID = p.productID
        WHERE f.userID = $current_user_id 
        ORDER BY f.created_at DESC";

$result = $conn->query($sql);

function ecoDisplayScore(array $row): string {
    $pct = isset($row['Pct_Better_Federal_Std']) ? trim($row['Pct_Better_Federal_Std']) : '';
    if ($pct !== '' && is_numeric($pct)) return round((float)$pct, 1) . '% better than std';
    $tec = isset($row['TEC_kWh']) ? trim($row['TEC_kWh']) : '';
    if ($tec !== '' && is_numeric($tec)) return round((float)$tec, 2) . ' kWh TEC';
    return 'N/A';
}

function typeIcon(string $type): string {
    return match($type) {
        'Laptop'                           => 'fa-laptop',
        'Mobile Phone'                     => 'fa-mobile-alt',
        'Clothes Dryer', 'Washing Machine' => 'fa-tshirt',
        'Dishwasher'                       => 'fa-sink',
        'Refrigerator'                     => 'fa-snowflake',
        default                            => 'fa-box',
    };
}
?>

<div class="search-page-bg">
    <div class="container py-5">
        
        <div class="mb-5 px-xl-5 text-center">
            <h1 class="fw-bold text-white">My Bookmarks</h1>
            <p class="text-white-50">
                Hello, <?php echo htmlspecialchars($_SESSION['username']); ?>.
                Here are your saved choices.
            </p>
        </div>

        <div class="container">
            <div class="row px-xl-5">
                <?php 
                if ($result && $result->num_rows > 0): 
                    while ($row = $result->fetch_assoc()):
                        $pid     = (int)$row['productID'];
                        $favId   = (int)$row['favoriteID'];
                        $icon    = typeIcon($row['Product_Type'] ?? '');
                ?>
                    <div class="col-lg-4 col-md-6 mb-4" id="fav-card-<?php echo $favId; ?>">
                        <div class="card search-product-card h-100 p-3 shadow-sm">

                            <!-- Product image — shows image_url if available, icon fallback -->
                            <div class="text-center bg-light rounded-3 p-3 mb-3"
                                 style="height:180px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                                <?php if (!empty($row['image_url'])): ?>
                                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>"
                                         class="img-fluid"
                                         style="max-height:140px;object-fit:contain;"
                                         alt="<?php echo htmlspecialchars($row['Product_Name'] ?? ''); ?>">
                                <?php else: ?>
                                    <i class="fas <?php echo $icon; ?> fa-4x text-muted opacity-25"></i>
                                <?php endif; ?>
                            </div>

                            <div class="card-body p-0 d-flex flex-column text-dark">
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($row['Product_Name'] ?? ''); ?></h6>
                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($row['Brand'] ?? ''); ?></p>

                                <div class="mb-2">
                                    <span class="eco-score">Eco Score: <?php echo ecoDisplayScore($row); ?></span>
                                </div>

                                <!-- Spec pills -->
                                <div class="mb-2 d-flex flex-wrap gap-1" style="font-size:0.72rem;">
                                    <?php if (!empty($row['RAM_GB']) && is_numeric($row['RAM_GB'])): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="fas fa-memory me-1"></i><?php echo $row['RAM_GB']; ?> GB
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($row['CPU_Base_GHz']) && is_numeric($row['CPU_Base_GHz'])): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="fas fa-microchip me-1"></i><?php echo $row['CPU_Base_GHz']; ?> GHz
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($row['OS'])): ?>
                                        <span class="badge bg-light text-dark border">
                                            <?php echo htmlspecialchars($row['OS']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <?php if (!empty($row['Annual_Energy_kWh']) && is_numeric($row['Annual_Energy_kWh'])): ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="fas fa-bolt me-1"></i><?php echo $row['Annual_Energy_kWh']; ?> kWh/yr
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <!-- Certification badges -->
                                <div class="mb-2 d-flex flex-wrap gap-1">
                                    <?php if (!empty($row['EPEAT_Tier'])): ?>
                                        <span class="badge bg-success">EPEAT <?php echo htmlspecialchars($row['EPEAT_Tier']); ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($row['EnergyStar_Certified']) && strtolower($row['EnergyStar_Certified']) !== 'no'): ?>
                                        <span class="badge bg-primary">Energy Star</span>
                                    <?php endif; ?>
                                </div>

                                <!-- Saved date -->
                                <?php if (!empty($row['date_saved'])): ?>
                                <p class="text-muted" style="font-size:0.7rem;">
                                    Saved <?php echo date('M d, Y', strtotime($row['date_saved'])); ?>
                                </p>
                                <?php endif; ?>

                                <div class="mt-auto d-flex justify-content-between align-items-center pt-3">
                                    <span class="fw-bold text-success small">
                                        <?php echo htmlspecialchars($row['Product_Type'] ?? ''); ?>
                                    </span>
                                    <div class="d-flex align-items-center">
                                        <button class="btn btn-outline-danger btn-sm rounded-circle me-2 remove-fav" 
                                                data-id="<?php echo $favId; ?>" 
                                                title="Remove Bookmark">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                        <a href="details.php?id=<?php echo $pid; ?>" 
                                           class="btn btn-sm btn-dark rounded-pill px-3">View</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="far fa-bookmark fa-4x text-white-50 mb-3"></i>
                        <h4 class="text-white">No bookmarks yet</h4>
                        <p class="text-white-50">Products you bookmark will appear here.</p>
                        <a href="search.php" class="btn btn-pill-white mt-3 border border-white text-white">Browse Products</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    $('.remove-fav').on('click', function() {
        if (!confirm('Remove this item from your bookmarks?')) return;
        
        var btn   = $(this);
        var favId = btn.data('id');

        $.ajax({
            url: 'remove_favorite.php', 
            method: 'POST',
            data: { favorite_id: favId },
            success: function(response) {
                if (response.trim() === "success") {
                    $('#fav-card-' + favId).fadeOut(300, function() { $(this).remove(); });
                } else {
                    alert('Error removing bookmark.');
                }
            }
        });
    });
});
</script>

<?php include('footer.php'); ?>