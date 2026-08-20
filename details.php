<?php
include('db.php'); 
include('header.php'); 

$pid = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$product = null;
if ($pid > 0) {
    // Secure parameterized statement to safely load item data
    $stmt = $conn->prepare("SELECT * FROM product WHERE productID = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $product = $result->fetch_assoc();
    }
    $stmt->close();
}

if (!$product) {
    echo "<div class='container mt-5 text-white'><h2>Product not found!</h2><a href='search.php' class='btn btn-success'>Go Back</a></div>";
    include('footer.php');
    exit;
}

// Determine product category group for fallback icons
$productType  = $product['Product_Type'] ?? '';

// Type icon fallback
$typeIcon = match($productType) {
    'Laptop'                           => 'fa-laptop',
    'Mobile Phone'                     => 'fa-mobile-alt',
    'Clothes Dryer', 'Washing Machine' => 'fa-tshirt',
    'Dishwasher'                       => 'fa-sink',
    'Refrigerator'                     => 'fa-snowflake',
    default                            => 'fa-box',
};

// Bookmark status
$isLoggedIn   = isset($_SESSION['auth']) && $_SESSION['auth'] === true;
$isBookmarked = false;
if ($isLoggedIn) {
    $uid      = (int)$_SESSION['userID'];
    $favStmt  = $conn->prepare("SELECT favoriteID FROM favorite WHERE userID = ? AND productID = ?");
    $favStmt->bind_param("ii", $uid, $pid);
    $favStmt->execute();
    $chkFav   = $favStmt->get_result();
    $isBookmarked = ($chkFav && $chkFav->num_rows > 0);
    $favStmt->close();
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="search-page-bg" style="background:#0b2419; min-height:100vh; padding-top:100px; color:white;">
    <div class="container py-5">

        <div class="row align-items-start g-5">

            <div class="col-lg-5 text-center">
                <div class="bg-white rounded-4 shadow-lg d-flex align-items-center justify-content-center p-4"
                     style="height:380px; overflow: hidden;">
                    <?php 
                    $imgFile = trim($product['image_url'] ?? ''); 
                    if (!empty($imgFile)): 
                    ?>
                        <img src="<?php echo htmlspecialchars($imgFile); ?>" 
                             alt="<?php echo htmlspecialchars($product['Product_Name'] ?? 'Product Image'); ?>"
                             class="img-fluid" 
                             style="max-height: 100%; max-width: 100%; object-fit: contain;">
                    <?php else: ?>
                        <i class="fas <?php echo $typeIcon; ?> text-muted" style="font-size:8rem; opacity:0.2;"></i>
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-3 justify-content-center mt-4">
                    <a href="search.php" class="btn btn-outline-light rounded-pill px-4">
                        <i class="fas fa-arrow-left me-2"></i>Back
                    </a>
                    <?php if ($isLoggedIn): ?>
                    <button id="bookmarkBtn"
                            class="btn <?php echo $isBookmarked ? 'btn-success' : 'btn-outline-success'; ?> rounded-pill px-4"
                            data-id="<?php echo $pid; ?>">
                        <i class="<?php echo $isBookmarked ? 'fas' : 'far'; ?> fa-bookmark me-2"></i>
                        <?php echo $isBookmarked ? 'Bookmarked' : 'Bookmark'; ?>
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-7">
                <p class="text-success fw-bold mb-1 text-uppercase"><?php echo htmlspecialchars($product['Brand'] ?? ''); ?></p>
                <h1 class="fw-bold mb-2" style="font-size:2rem;"><?php echo htmlspecialchars($product['Product_Name'] ?? ''); ?></h1>
                <p class="text-white-50 mb-3"><?php echo htmlspecialchars($product['Model_Number'] ?? ''); ?></p>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <?php if (!empty($product['EPEAT_Tier'])): ?>
                        <span class="badge px-3 py-2 rounded-pill"
                              style="background:<?php echo match($product['EPEAT_Tier']) { 'Gold' => '#FFD700', 'Silver' => '#C0C0C0', default => '#CD7F32' }; ?>;color:#000;">
                            <i class="fas fa-certificate me-1"></i>EPEAT <?php echo htmlspecialchars($product['EPEAT_Tier']); ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($product['EnergyStar_Certified']) && strtolower($product['EnergyStar_Certified']) !== 'no'): ?>
                        <span class="badge bg-primary px-3 py-2 rounded-pill">
                            <i class="fas fa-star me-1"></i>Energy Star
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($product['EPEAT_Certified']) && strtolower($product['EPEAT_Certified']) !== 'no'): ?>
                        <span class="badge bg-success px-3 py-2 rounded-pill">
                            <i class="fas fa-leaf me-1"></i>EPEAT Certified
                        </span>
                    <?php endif; ?>
                    <span class="badge bg-secondary px-3 py-2 rounded-pill"><?php echo htmlspecialchars($productType); ?></span>
                    <?php if (!empty($product['Sub_Type'])): ?>
                        <span class="badge bg-dark border border-secondary px-3 py-2 rounded-pill"><?php echo htmlspecialchars($product['Sub_Type']); ?></span>
                    <?php endif; ?>
                    <?php if (!empty($product['rating'])): ?>
                        <span class="badge bg-info text-dark px-3 py-2 rounded-pill"><i class="fas fa-star me-1"></i><?php echo htmlspecialchars($product['rating']); ?> / 5</span>
                    <?php endif; ?>
                </div>

                <div class="bg-white text-dark rounded-4 p-4 mb-4 shadow">
                    <h5 class="text-success fw-bold mb-3"><i class="fas fa-leaf me-2"></i>Eco Performance</h5>
                    <div class="row g-3">
                        <?php if (!empty($product['Pct_Better_Federal_Std']) && is_numeric($product['Pct_Better_Federal_Std'])): ?>
                        <div class="col-6">
                            <div class="text-center p-3 rounded-3" style="background:rgba(40,167,69,0.1);">
                                <div class="fs-3 fw-bold text-success"><?php echo round((float)$product['Pct_Better_Federal_Std'], 1); ?>%</div>
                                <div class="small text-muted">Better than federal std</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($product['TEC_kWh']) && is_numeric($product['TEC_kWh'])): ?>
                        <div class="col-6">
                            <div class="text-center p-3 rounded-3" style="background:rgba(255,193,7,0.15);">
                                <div class="fs-3 fw-bold text-warning" style="color: #b58100 !important;"><?php echo round((float)$product['TEC_kWh'], 2); ?></div>
                                <div class="small text-muted">kWh (TEC)</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($product['Annual_Energy_kWh']) && is_numeric($product['Annual_Energy_kWh'])): ?>
                        <div class="col-6">
                            <div class="text-center p-3 rounded-3" style="background:rgba(23,162,184,0.1);">
                                <div class="fs-3 fw-bold text-info"><?php echo round((float)$product['Annual_Energy_kWh'], 1); ?></div>
                                <div class="small text-muted">kWh/year</div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($product['Annual_Water_gal']) && is_numeric($product['Annual_Water_gal'])): ?>
                        <div class="col-6">
                            <div class="text-center p-3 rounded-3" style="background:rgba(0,123,255,0.1);">
                                <div class="fs-3 fw-bold text-primary"><?php echo round((float)$product['Annual_Water_gal']); ?></div>
                                <div class="small text-muted">gal water/year</div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <?php 
                $desc = $product['description'] ?? $product['descriptionlong'] ?? '';
                if (!empty($desc)): 
                ?>
                <div class="rounded-4 p-4" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.08);">
                    <h6 class="fw-bold mb-2">About this product</h6>
                    <p class="text-white-50 mb-0"><?php echo nl2br(htmlspecialchars($desc)); ?></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <hr class="my-5 opacity-25">

        <h3 class="fw-bold mb-4"><i class="fas fa-sliders-h me-2 text-success"></i>Full Specifications</h3>
        
        <?php
        $excludedKeys = [
            'productID', 'image_url', 'description', 'descriptionlong', 
            'Product_Name', 'Brand', 'Model_Number', 'rating'
        ];

        // Core data structure bucket mappings
        $groups = [
            'performance' => ['title' => 'System & Performance', 'icon' => 'fa-microchip', 'data' => []],
            'appliance'   => ['title' => 'Appliance Features',   'icon' => 'fa-blender',   'data' => []],
            'eco'         => ['title' => 'Energy & Environment', 'icon' => 'fa-bolt',      'data' => []],
            'general'     => ['title' => 'General Parameters',   'icon' => 'fa-info-circle','data' => []]
        ];

        $totalSpecsFound = 0;

        foreach ($product as $columnName => $value) {
            if ($value === null) continue;
            
            $trimmedVal = trim((string)$value);

            if (in_array($columnName, $excludedKeys)) continue;
            if ($trimmedVal === '' || strtolower($trimmedVal) === 'null' || strtolower($trimmedVal) === 'n/a') continue;

            $displayLabel = ucwords(str_replace('_', ' ', $columnName));

            // Normalize booleans and numerical switches safely
            if (strtoupper($trimmedVal) === 'TRUE' || $trimmedVal === '1') {
                $trimmedVal = 'Yes';
            } elseif (strtoupper($trimmedVal) === 'FALSE' || $trimmedVal === '0') {
                if (!preg_match('/(certified|star|connected|screen|touch|sensing|maker)/', strtolower($columnName))) {
                    $trimmedVal = '0'; 
                } else {
                    $trimmedVal = 'No';
                }
            }

            // Metric unit appending formatting logic safely
            if (str_contains($columnName, 'GHz') && !str_contains($trimmedVal, 'GHz'))   $trimmedVal .= ' GHz';
            if (str_contains($columnName, 'GB') && !str_contains($trimmedVal, 'GB'))     $trimmedVal .= ' GB';
            if (str_contains($columnName, '_in') && !str_contains($trimmedVal, '"'))    $trimmedVal .= '"';
            if (str_contains($columnName, '_W') && !str_contains($trimmedVal, 'W'))       $trimmedVal .= ' W';
            if (str_contains($columnName, '_V') && !str_contains($trimmedVal, 'V'))       $trimmedVal .= ' V';
            if (str_contains($columnName, 'Wh') && !str_contains($trimmedVal, 'Wh'))     $trimmedVal .= ' Wh';
            if (str_contains($columnName, 'cuft') && !str_contains($trimmedVal, 'cu ft')) $trimmedVal .= ' cu ft';
            if (str_contains($columnName, 'gal') && !str_contains($trimmedVal, 'gallon')) $trimmedVal .= ' gallons';
            if (str_contains($columnName, 'kWh') && !str_contains($trimmedVal, 'kWh'))   $trimmedVal .= ' kWh';
            if (str_contains($columnName, 'MP') && !str_contains($trimmedVal, 'MP'))     $trimmedVal .= ' MP';

            // Advanced Categorization Logic Matchers
            $lowerKey = strtolower($columnName);
            if (preg_match('/(processor|cpu|ram|os|storage|display|screen|resolution|battery|intel|amd|core)/', $lowerKey)) {
                $targetGroup = 'performance';
            } elseif (preg_match('/(volume|drum|capacity|voltage|vent|pump|connect|setting|soil|tub|dry|ice|maker|cef|imef|iwf|height|width|depth)/', $lowerKey)) {
                $targetGroup = 'appliance';
            } elseif (preg_match('/(epeat|energystar|certified|power|draw|idle|sleep|off_mode|tier|std)/', $lowerKey)) {
                $targetGroup = 'eco';
            } else {
                $targetGroup = 'general';
            }

            $isUrl = (filter_var($value, FILTER_VALIDATE_URL) !== false || str_contains($lowerKey, 'url'));

            $groups[$targetGroup]['data'][] = [
                'label' => $displayLabel, 
                'value' => $trimmedVal,
                'is_url' => $isUrl,
                'raw_val' => $value
            ];
            $totalSpecsFound++;
        }
        ?>

        <?php if ($totalSpecsFound > 0): ?>
            <div class="row g-4">
                <?php foreach ($groups as $key => $group): ?>
                    <?php if (empty($group['data'])) continue; ?>
                    
                    <div class="col-lg-6">
                        <div class="bg-white text-dark rounded-4 p-4 h-100 shadow-sm border border-light">
                            <h5 class="text-success fw-bold mb-3 border-bottom pb-2">
                                <i class="fas <?php echo $group['icon']; ?> me-2 text-success"></i><?php echo $group['title']; ?>
                            </h5>
                            
                            <div class="row g-2">
                                <?php foreach ($group['data'] as $item): ?>
                                    <div class="col-12 d-flex justify-content-between align-items-center py-2 border-bottom border-light" style="min-height: 45px;">
                                        <span class="text-muted small fw-semibold text-capitalize pe-2"><?php echo htmlspecialchars($item['label']); ?></span>
                                        <span class="text-end fw-bold text-dark text-break" style="max-width: 60%;">
                                            <?php if ($item['is_url']): ?>
                                                <a href="<?php echo htmlspecialchars($item['raw_val']); ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 font-monospace" style="font-size:0.75rem;">
                                                    Link <i class="fas fa-external-link-alt ms-1"></i>
                                                </a>
                                            <?php else: ?>
                                                <?php echo htmlspecialchars($item['value']); ?>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white text-dark rounded-4 p-4 text-center shadow-sm">
                <p class="text-muted my-3">No custom parameters recorded for this item.</p>
            </div>
        <?php endif; ?>

        <hr class="my-5 opacity-25">

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h3 class="mb-4 fw-bold"><i class="fas fa-star me-2 text-warning"></i>Customer Reviews</h3>

                <?php
                $review_sql = "SELECT f.*, u.username 
                               FROM feedback f 
                               JOIN user u ON f.userID = u.userID 
                               WHERE f.productID = ? 
                               ORDER BY f.feedbackDate DESC";
                $revStmt = $conn->prepare($review_sql);
                $revStmt->bind_param("i", $pid);
                $revStmt->execute();
                $reviews = $revStmt->get_result();

                if ($reviews && $reviews->num_rows > 0):
                    while ($rev = $reviews->fetch_assoc()): ?>
                    <div class="card mb-3 border-0 text-white p-3 shadow-sm"
                         style="background:#1a3a2a; border:1px solid #3a805a !important;">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="fw-bold text-success mb-0"><?php echo htmlspecialchars($rev['username']); ?></h6>
                                    <small class="opacity-50">
                                        <?php echo !empty($rev['feedbackDate']) ? date('M d, Y', strtotime($rev['feedbackDate'])) : ''; ?>
                                    </small>
                                </div>

                                <?php if ($isLoggedIn && $_SESSION['userID'] == $rev['userID']): ?>
                                <div class="dropdown">
                                    <button class="btn btn-link text-white opacity-75 p-0" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="#"
                                               data-bs-toggle="modal" data-bs-target="#editReview<?php echo $rev['feedbackID']; ?>">
                                                <i class="fas fa-edit me-2"></i>Edit
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item text-danger"
                                               href="handle_review.php?delete=<?php echo $rev['feedbackID']; ?>&pid=<?php echo $pid; ?>"
                                               onclick="return confirm('Delete this review?')">
                                                <i class="fas fa-trash me-2"></i>Delete
                                            </a>
                                        </li>
                                    </ul>
                                </div>

                                <div class="modal fade" id="editReview<?php echo $rev['feedbackID']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-dialog-centered text-dark">
                                        <div class="modal-content">
                                            <form action="handle_review.php" method="POST">
                                                <div class="modal-header">
                                                    <h5 class="modal-title fw-bold">Edit Review</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <input type="hidden" name="feedbackID" value="<?php echo $rev['feedbackID']; ?>">
                                                    <input type="hidden" name="pid"        value="<?php echo $pid; ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Rating</label>
                                                        <select name="rating" class="form-select">
                                                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                                            <option value="<?php echo $i; ?>" <?php echo ($rev['rating'] == $i) ? 'selected' : ''; ?>>
                                                                <?php echo $i; ?> Stars
                                                            </option>
                                                            <?php endfor; ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Comments</label>
                                                        <textarea name="comment" class="form-control" rows="3" required><?php echo htmlspecialchars($rev['comment']); ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="update_review" class="btn btn-success rounded-pill px-4">Save Changes</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <div class="text-warning my-2">
                                <?php echo str_repeat('⭐', min((int)$rev['rating'], 5)); ?>
                                <span class="text-white-50 small ms-1"><?php echo $rev['rating']; ?>/5</span>
                            </div>
                            <p class="mb-0"><em>"<?php echo htmlspecialchars($rev['comment'] ?? ''); ?>"</em></p>
                        </div>
                    </div>
                    <?php endwhile;
                    $revStmt->close();
                else: ?>
                    <p class="text-white-50 text-center py-4 border border-secondary rounded-3">
                        No reviews yet. Be the first to share!
                    </p>
                <?php endif; ?>

                <?php if ($isLoggedIn): ?>
                <div class="mt-5 p-4 bg-white text-dark rounded-4 shadow">
                    <h5 class="fw-bold mb-3">Leave a Review</h5>
                    <form action="submit_review.php" method="POST">
                        <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Rating</label>
                            <select name="rating" class="form-select border-success">
                                <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                <option value="4">⭐⭐⭐⭐ Good</option>
                                <option value="3">⭐⭐⭐ Average</option>
                                <option value="2">⭐⭐ Poor</option>
                                <option value="1">⭐ Terrible</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Your Comments</label>
                            <textarea name="comment" class="form-control border-success" rows="3" required
                                      placeholder="Share your experience with this product..."></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100 rounded-pill fw-bold">
                            <i class="fas fa-paper-plane me-2"></i>Submit Review
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <p class="text-center mt-4">
                    Please <a href="login.php" class="text-success fw-bold">Login</a> to write a review.
                </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    // Bookmark dynamic handler
    $('#bookmarkBtn').on('click', function () {
        var btn       = $(this);
        var productId = btn.data('id');
        $.ajax({
            url: 'add_favorite.php', method: 'POST', data: { product_id: productId },
            success: function (response) {
                var res = response.trim();
                if (res === 'success') {
                    btn.removeClass('btn-outline-success').addClass('btn-success');
                    btn.html('<i class="fas fa-bookmark me-2"></i>Bookmarked');
                } else if (res === 'removed') {
                    btn.removeClass('btn-success').addClass('btn-outline-success');
                    btn.html('<i class="far fa-bookmark me-2"></i>Bookmark');
                }
            }
        });
    });
});
</script>

<?php include('footer.php'); ?>