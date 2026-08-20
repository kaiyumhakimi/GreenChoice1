<?php 
include('db.php'); 
include('header.php'); 

$isLoggedIn      = isset($_SESSION['auth']) && $_SESSION['auth'] === true;
$current_user_id = $isLoggedIn ? (int)$_SESSION['userID'] : 0;

// ─────────────────────────────────────────────────────────────────
// HYBRID RECOMMENDER — Normalized Key Case Fix
// ─────────────────────────────────────────────────────────────────
function fetchHybridRecs($user_id, int $top_n = 9): array {
    $url = "http://localhost:5000/recommend?" . http_build_query([
        'user_id' => $user_id,
        'top_n'   => $top_n,
        'alpha'   => 0.5,
        'save'    => 0,
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || !$response) return [];
    
    $data = json_decode($response, true);
    $raw_recs = $data['recommendations'] ?? [];
    
    // --- FORCE LOWERCASE KEYS FOR PHP COMPLETENESS ---
    $normalized_recs = [];
    foreach ($raw_recs as $rec) {
        $clean_rec = array_change_key_case($rec, CASE_LOWER);
        
        $clean_rec['productID']    = $clean_rec['productid'] ?? '';
        $clean_rec['Product_Name'] = $clean_rec['product_name'] ?? '';
        $clean_rec['Brand']        = $clean_rec['brand'] ?? '';
        $clean_rec['Product_Type'] = $clean_rec['product_type'] ?? '';
        $clean_rec['RAM_GB']       = $clean_rec['ram_gb'] ?? '';
        $clean_rec['CPU_Base_GHz'] = $clean_rec['cpu_base_ghz'] ?? '';
        $clean_rec['OS']           = $clean_rec['os'] ?? '';
        $clean_rec['Annual_Energy_kWh'] = $clean_rec['annual_energy_kwh'] ?? '';
        $clean_rec['EPEAT_Tier']   = $clean_rec['epeat_tier'] ?? '';
        $clean_rec['EnergyStar_Certified'] = $clean_rec['energystar_certified'] ?? '';
        
        $normalized_recs[] = $clean_rec;
    }
    
    return $normalized_recs;
}

$hybridRecs    = [];
$usingHybrid   = false;
$productCards  = [];

if ($isLoggedIn) {
    $hybridRecs = fetchHybridRecs($current_user_id, 9);
    if (!empty($hybridRecs)) {
        $usingHybrid  = true;
        $productCards = $hybridRecs;
    }
}

if (empty($productCards)) {
    $fallbackResult = $conn->query(
        "SELECT * FROM product 
         ORDER BY CAST(Pct_Better_Federal_Std AS DECIMAL(10,4)) DESC,
                  CAST(TEC_kWh AS DECIMAL(10,4)) ASC
         LIMIT 9"
    );
    while ($r = $fallbackResult->fetch_assoc()) {
        $r['hybrid_score'] = null;
        $productCards[] = $r;
    }
}

$hero_result = $conn->query(
    "SELECT * FROM product 
     WHERE EPEAT_Tier IN ('Gold','Silver','Bronze')
     ORDER BY FIELD(EPEAT_Tier,'Gold','Silver','Bronze'),
              CAST(Pct_Better_Federal_Std AS DECIMAL(10,4)) DESC
     LIMIT 3"
);

$brand_result = $conn->query(
    "SELECT Brand, COUNT(*) AS total 
     FROM product 
     WHERE Brand IS NOT NULL AND Brand <> ''
     GROUP BY Brand 
     ORDER BY total DESC 
     LIMIT 5"
);

function ecoScore(array $row): string {
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

<section class="hero-section">
    <div class="container py-5">
        <div class="row align-items-center min-vh-100">
            <div class="col-lg-6 mb-5 mb-lg-0">
                <h1 class="hero-title-custom text-white">
                    GreenChoice recommends and compares electronic products
                    certified by EPEAT and ENERGY STAR.
                </h1>
                <div class="d-flex gap-3 mt-4 flex-wrap">
                    <a href="search.php" class="btn btn-pill-white">Browse Products</a>
                    <a href="about.php"  class="btn btn-outline-light rounded-pill px-4">Learn More</a>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <div class="hero-image-wrapper">
                    <img src="assets/iphone17.webp" class="img-fluid rounded-mockup shadow-lg" alt="Product Mockup">
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <?php if ($usingHybrid): ?>
                <h2 class="fw-bold">Recommended For You</h2>
                <p class="text-muted">Personalised picks based on your bookmarks and similar users.</p>
            <?php else: ?>
                <h2 class="fw-bold">Top Eco Products</h2>
                <p class="text-muted">
                    <?php if ($isLoggedIn): ?>
                        Bookmark some products to get personalised recommendations.
                    <?php else: ?>
                        <a href="login.php" class="text-success fw-bold">Sign in</a> to get personalised recommendations.
                    <?php endif; ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="row">
            <?php foreach ($productCards as $row):
                // EXACT STRING MATCH (NO (INT) CASTING TO AVOID BREAKING COMPATIBILITY WITH SEARCH.PHP)
                $pid = '';
                if (isset($row['productid'])) $pid = $row['productid'];
                elseif (isset($row['productID'])) $pid = $row['productID'];
                elseif (isset($row['product_id'])) $pid = $row['product_id'];
                else {
                    $vals = array_values($row);
                    $pid = isset($vals[0]) ? $vals[0] : '';
                }

                $hybridScore  = isset($row['hybrid_score']) && $row['hybrid_score'] !== null ? (float)$row['hybrid_score'] : null;

                $isBookmarked = false;
                if ($isLoggedIn && !empty($pid)) {
                    $chk = $conn->query("SELECT favoriteID FROM favorite WHERE userID = $current_user_id AND productID = '$pid'");
                    $isBookmarked = ($chk && $chk->num_rows > 0);
                }
            ?>
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card search-product-card h-100 p-3 shadow-sm position-relative" style="border-radius: 20px;">
                    <?php if ($usingHybrid && $hybridScore !== null && $hybridScore > 0): ?>
                    <div class="position-absolute" style="top:10px;right:10px;z-index:10;">
                        <span class="badge bg-success opacity-75" style="font-size:0.65rem;">
                            ★ <?php echo round($hybridScore * 100, 1); ?>% match
                        </span>
                    </div>
                    <?php endif; ?>

                    <div class="text-center bg-light rounded-3 p-3 mb-3" style="height:180px; display:flex; align-items:center; justify-content:center; overflow:hidden;">
                        <?php if (!empty($row['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="<?php echo htmlspecialchars($row['Product_Name'] ?? 'Product'); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <i class="fas <?php echo typeIcon($row['Product_Type'] ?? ''); ?> fa-4x text-muted opacity-25"></i>
                        <?php endif; ?>
                    </div>

                    <div class="card-body p-0 d-flex flex-column text-dark">
                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($row['Product_Name'] ?? ''); ?></h6>
                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($row['Brand'] ?? ''); ?></p>
                        <div class="mb-2"><span class="eco-score">Eco Score: <?php echo ecoScore($row); ?></span></div>

                        <div class="mb-2 d-flex flex-wrap gap-1" style="font-size:0.72rem;">
                            <?php if (!empty($row['RAM_GB']) && is_numeric($row['RAM_GB'])): ?>
                                <span class="badge bg-light text-dark border"><i class="fas fa-memory me-1"></i><?php echo $row['RAM_GB']; ?> GB</span>
                            <?php endif; ?>
                            <?php if (!empty($row['CPU_Base_GHz']) && is_numeric($row['CPU_Base_GHz'])): ?>
                                <span class="badge bg-light text-dark border"><i class="fas fa-microchip me-1"></i><?php echo $row['CPU_Base_GHz']; ?> GHz</span>
                            <?php endif; ?>
                            <?php if (!empty($row['OS'])): ?>
                                <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($row['OS']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($row['Annual_Energy_kWh']) && is_numeric($row['Annual_Energy_kWh'])): ?>
                                <span class="badge bg-light text-dark border"><i class="fas fa-bolt me-1"></i><?php echo $row['Annual_Energy_kWh']; ?> kWh/yr</span>
                            <?php endif; ?>
                        </div>

                        <div class="mb-2 d-flex flex-wrap gap-1">
                            <?php if (!empty($row['EPEAT_Tier'])): ?>
                                <span class="badge bg-success">EPEAT <?php echo htmlspecialchars($row['EPEAT_Tier']); ?></span>
                            <?php endif; ?>
                            <?php if (!empty($row['EnergyStar_Certified']) && strtolower($row['EnergyStar_Certified']) !== 'no'): ?>
                                <span class="badge bg-primary">Energy Star</span>
                            <?php endif; ?>
                        </div>

                        <div class="mt-auto d-flex justify-content-between align-items-center pt-3">
                            <span class="fw-bold text-success small"><?php echo htmlspecialchars($row['Product_Type'] ?? ''); ?></span>
                            <div class="d-flex align-items-center">
                                <?php if ($isLoggedIn): ?>
                                <button type="button" class="btn <?php echo $isBookmarked ? 'btn-success' : 'btn-outline-success'; ?> btn-sm rounded-circle me-2 bookmark-btn" data-id="<?php echo htmlspecialchars($pid); ?>">
                                    <i class="<?php echo $isBookmarked ? 'fas' : 'far'; ?> fa-bookmark"></i>
                                </button>
                                <?php endif; ?>
                                <a href="details.php?id=<?php echo urlencode($pid); ?>" class="btn btn-sm btn-dark rounded-pill px-3">View</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-4">
            <a href="search.php" class="btn btn-success rounded-pill px-5 fw-bold shadow">
                <i class="fas fa-search me-2"></i>Browse All Products
            </a>
        </div>
    </div>
</section>

<style>
    .category-card {
        background: linear-gradient(135deg, #0b2419 0%, #3a805a 100%) !important;
        border-radius: 20px !important; 
        border: 1px solid rgba(255, 255, 255, 0.1) !important;
        padding: 40px 20px !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
        height: 100%;
    }
    .category-card .card-icon {
        color: #4ade80 !important; 
        font-size: 2.5rem !important;
        display: inline-block;
        margin-bottom: 10px;
    }
    .category-card .category-title {
        color: #ffffff !important;
        font-weight: 700 !important;
    }
    .category-card .text-muted {
        color: #cbd5e1 !important; 
        opacity: 0.9;
    }
    .category-card:hover {
        transform: translateY(-8px) !important;
        box-shadow: 0 15px 30px rgba(11, 36, 25, 0.25) !important;
        border-color: rgba(74, 222, 128, 0.4) !important;
    }
</style>
<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="fw-bold mb-4">Browse Categories</h2>
        <div class="row g-4 justify-content-center">
            <div class="col-md-4 col-6">
                <a href="search.php?categories[]=Laptop" class="text-decoration-none">
                    <div class="category-card shadow-sm">
                        <i class="fas fa-laptop card-icon"></i>
                        <h5 class="category-title mt-3">Laptops</h5>
                        <p class="text-muted small">Eco-certified notebooks & ultrabooks</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-6">
                <a href="search.php?categories[]=Mobile+Phone" class="text-decoration-none">
                    <div class="category-card shadow-sm">
                        <i class="fas fa-mobile-alt card-icon"></i>
                        <h5 class="category-title mt-3">Mobile Phones</h5>
                        <p class="text-muted small">Certified eco-friendly smartphones</p>
                    </div>
                </a>
            </div>
            <div class="col-md-4 col-6">
                <a href="search.php?categories[]=Home+Appliances" class="text-decoration-none">
                    <div class="category-card shadow-sm">
                        <i class="fas fa-blender card-icon"></i>
                        <h5 class="category-title mt-3">Home Appliances</h5>
                        <p class="text-muted small">Energy-efficient washers, dryers & more</p>
                    </div>
                </a>
            </div>
        </div>
    </div>
</section>

<style>
    .recommend-card {
        background-color: rgba(58, 128, 90, 0.06) !important;
        border: 1px solid rgba(11, 36, 25, 0.15) !important;
        border-radius: 20px !important;
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1) !important;
    }
    .recommend-card .recommend-img {
        background-color: #ffffff !important;
        border-bottom: 1px solid rgba(11, 36, 25, 0.08) !important;
    }
    .recommend-card h5.fw-bold { color: #0b2419 !important; }
    .recommend-card .text-muted { color: #3a805a !important; opacity: 0.9; }
    .recommend-card:hover {
        transform: translateY(-8px) !important;
        background-color: rgba(58, 128, 90, 0.1) !important;
        box-shadow: 0 15px 35px rgba(11, 36, 25, 0.08) !important; 
        border-color: #3a805a !important;
    }
    .recommend-card hr { border-color: rgba(11, 36, 25, 0.1) !important; }
</style>
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Top Eco-Picks</h2>
            <p class="text-muted">Highest-rated products verified by EPEAT & Energy Star.</p>
        </div>
        <div class="row">
            <?php if ($hero_result && $hero_result->num_rows > 0):
                while ($hero_row = $hero_result->fetch_assoc()):
                    $tierColor = match($hero_row['EPEAT_Tier'] ?? '') {
                        'Gold'   => '#FFD700',
                        'Silver' => '#C0C0C0',
                        default  => '#CD7F32',
                    };
                    
                    // SAFE KEY EXTRACTION WITHOUT INT CASTING
                    $hero_pid = '';
                    if (isset($hero_row['productid'])) $hero_pid = $hero_row['productid'];
                    elseif (isset($hero_row['productID'])) $hero_pid = $hero_row['productID'];
                    elseif (isset($hero_row['product_id'])) $hero_pid = $hero_row['product_id'];
                    else {
                        $values = array_values($hero_row);
                        $hero_pid = isset($values[0]) ? $values[0] : '';
                    }
            ?>
            <div class="col-md-4 mb-4">
                <div class="recommend-card shadow-sm h-100" style="border-radius: 20px; overflow: hidden;">
                    <div class="recommend-badge" style="background:<?php echo $tierColor; ?>;color:#000;">
                        EPEAT <?php echo htmlspecialchars($hero_row['EPEAT_Tier'] ?? ''); ?>
                    </div>
                    <div class="recommend-img d-flex align-items-center justify-content-center" style="height: 220px; padding: 20px; background: #f8f9fa;">
                        <?php if (!empty($hero_row['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($hero_row['image_url']); ?>" alt="<?php echo htmlspecialchars($hero_row['Product_Name'] ?? 'Product'); ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <i class="fas <?php echo typeIcon($hero_row['Product_Type'] ?? ''); ?> fa-4x text-success opacity-25"></i>
                        <?php endif; ?>
                    </div>
                    <div class="p-4 text-center">
                        <h5 class="fw-bold"><?php echo htmlspecialchars($hero_row['Product_Name'] ?? ''); ?></h5>
                        <p class="text-muted small mb-2">
                            <strong><?php echo htmlspecialchars($hero_row['Brand'] ?? ''); ?></strong> &mdash; <?php echo htmlspecialchars($hero_row['Product_Type'] ?? ''); ?>
                        </p>
                        <span class="badge bg-success px-3 py-2 rounded-pill mb-3"><?php echo ecoScore($hero_row); ?></span>
                        <?php if (!empty($hero_row['Source_Label']) && strtolower($hero_row['Source_Label']) !== 'no'): ?>
                            <span class="badge bg-primary px-3 py-2 rounded-pill mb-3">Energy Star</span>
                        <?php endif; ?>
                        <hr>
                        <a href="details.php?id=<?php echo urlencode($hero_pid); ?>" class="btn btn-outline-dark rounded-pill w-100">View Details</a>
                    </div>
                </div>
            </div>
            <?php endwhile; else: ?>
            <div class="col-12 text-center"><p class="text-muted">No products found.</p></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="fw-bold">Green Leaderboard</h2>
            <p class="text-muted">Top brands ranked by their certified product count.</p>
        </div>
        <div class="leaderboard-card shadow-sm p-4">
            <table class="table table-borderless align-middle">
                <thead>
                    <tr class="text-muted small text-uppercase">
                        <th>Rank</th>
                        <th>Brand</th>
                        <th class="text-center">Certified Products</th>
                        <th class="text-end">Browse</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $rank = 1;
                    if ($brand_result && $brand_result->num_rows > 0):
                        while ($brand = $brand_result->fetch_assoc()):
                    ?>
                    <tr>
                        <td><span class="rank-number rank-<?php echo $rank; ?>"><?php echo $rank; ?></span></td>
                        <td><strong><?php echo htmlspecialchars($brand['Brand']); ?></strong></td>
                        <td class="text-center"><?php echo $brand['total']; ?> products</td>
                        <td class="text-end">
                            <a href="search.php?brand=<?php echo urlencode($brand['Brand']); ?>" class="btn btn-sm btn-light rounded-pill">View</a>
                        </td>
                    </tr>
                    <?php $rank++; endwhile; endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container text-center">
        <h2 class="fw-bold mb-5">Why GreenChoice?</h2>
        <div class="row">
            <div class="col-md-4 mb-4">
                <div class="icon-circle-green mx-auto mb-3"><i class="fas fa-leaf fa-2x"></i></div>
                <h5 class="fw-bold">Eco-Friendly</h5>
                <p class="text-muted">Helping users choose greener products that reduce environmental impact.</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="icon-circle-green mx-auto mb-3"><i class="fas fa-robot fa-2x"></i></div>
                <h5 class="fw-bold">Smart Recommendations</h5>
                <p class="text-muted">Hybrid AI using collaborative filtering and content-based algorithms.</p>
            </div>
            <div class="col-md-4 mb-4">
                <div class="icon-circle-green mx-auto mb-3"><i class="fas fa-check-circle fa-2x"></i></div>
                <h5 class="fw-bold">Verified Labels</h5>
                <p class="text-muted">Only ENERGY STAR & EPEAT certified products in our database.</p>
            </div>
        </div>
    </div>
</section>

<style>
    /* Floating Action Trigger Button */
    .ai-chat-trigger {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #115e3b 0%, #3a805a 100%);
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.6rem;
        box-shadow: 0 8px 24px rgba(11, 36, 25, 0.35);
        cursor: pointer;
        z-index: 9999;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .ai-chat-trigger:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 12px 28px rgba(11, 36, 25, 0.45);
        color: #4ade80;
    }
    /* Main Floating Box Interface */
    .ai-chat-window {
        position: fixed;
        bottom: 105px;
        right: 30px;
        width: 360px;
        height: 480px;
        background: #ffffff;
        border-radius: 20px;
        box-shadow: 0 10px 35px rgba(0,0,0,0.15);
        display: none;
        flex-direction: column;
        overflow: hidden;
        z-index: 9999;
        border: 1px solid rgba(11, 36, 25, 0.1);
        font-family: inherit;
    }
    .ai-chat-header {
        background: #0b2419;
        color: #ffffff;
        padding: 15px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .ai-chat-body {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background-color: #f8f9fa;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    /* Chat Bubble Design Lines */
    .chat-msg {
        max-width: 80%;
        padding: 10px 14px;
        border-radius: 16px;
        font-size: 0.85rem;
        line-height: 1.4;
    }
    .chat-msg.assistant {
        background-color: #ffffff;
        color: #2d3748;
        align-self: flex-start;
        border-bottom-left-radius: 4px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        border: 1px solid rgba(0,0,0,0.05);
    }
    .chat-msg.user {
        background: #3a805a;
        color: #ffffff;
        align-self: flex-end;
        border-bottom-right-radius: 4px;
    }
    .ai-chat-footer {
        padding: 12px 15px;
        background: #ffffff;
        border-top: 1px solid #edf2f7;
    }
</style>

<div class="ai-chat-trigger" id="openAiChatBtn">
    <i class="fas fa-robot"></i>
</div>

<div class="ai-chat-window" id="aiChatWindow">
    <div class="ai-chat-header">
        <div class="d-flex align-items-center gap-2">
            <i class="fas fa-circle text-success" style="font-size: 0.65rem;"></i>
            <h6 class="mb-0 fw-bold" style="font-size:0.95rem;">GreenChoice Assistant</h6>
        </div>
        <button type="button" class="btn-close btn-close-white small" id="closeAiChatBtn" style="font-size:0.75rem; opacity:0.8;"></button>
    </div>
    
    <div class="ai-chat-body" id="aiChatBody">
        <div class="chat-msg assistant">
            Hello! I am your GreenChoice eco-assistant. Ask me anything about EPEAT tiers, Energy Star certifications, or how to check product efficiencies! 🌿
        </div>
    </div>
    
    <div class="ai-chat-footer">
        <form id="aiChatForm" class="d-flex gap-2">
            <input type="text" id="aiChatInput" class="form-control form-control-sm rounded-pill px-3" placeholder="Type an eco-question..." autocomplete="off" required>
            <button type="submit" class="btn btn-success btn-sm rounded-circle d-flex align-items-center justify-content-center" style="width:32px; height:32px;">
                <i class="fas fa-paper-plane" style="font-size:0.75rem;"></i>
            </button>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    var isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

    // 1. PRODUCT BOOKMARK HANDLER
    $('.bookmark-btn').on('click', function () {
        if (!isLoggedIn) { window.location.href = 'login.php'; return; }
        var btn = $(this), pid = btn.data('id'), icon = btn.find('i');
        $.ajax({
            url: 'add_favorite.php', method: 'POST', data: { product_id: pid },
            success: function (res) {
                res = res.trim();
                if (res === 'success') {
                    icon.removeClass('far').addClass('fas');
                    btn.removeClass('btn-outline-success').addClass('btn-success');
                } else if (res === 'removed') {
                    icon.removeClass('fas').addClass('far');
                    btn.removeClass('btn-success').addClass('btn-outline-success');
                }
            }
        });
    });

    // 2. AI ASSISTANT WINDOW TOGGLE HANDLERS
    $('#openAiChatBtn').on('click', function() {
        $('#aiChatWindow').css('display', 'flex').fadeIn(250);
        $(this).hide();
    });

    // 3. AI INTERACTION SUBMISSION PROCESSOR
    $('#closeAiChatBtn').on('click', function() {
        $('#aiChatWindow').fadeOut(200, function() {
            $('#openAiChatBtn').fadeIn(200);
        });
    });

    $('#aiChatForm').on('submit', function(e) {
        e.preventDefault();
        var inputField = $('#aiChatInput');
        var userText = inputField.val().trim();
        if(!userText) return;

        // Display user query bubble immediately
        $('#aiChatBody').append('<div class="chat-msg user">' + htmlEntities(userText) + '</div>');
        inputField.val(''); // Empty text field safely
        autoScrollChat();

        // Inject placeholder loader stream dots
        var loaderId = 'loader_' + Date.now();
        $('#aiChatBody').append('<div class="chat-msg assistant" id="'+loaderId+'"><i class="fas fa-ellipsis-h fa-pulse"></i> Analyzing...</div>');
        autoScrollChat();

        // Send payload data directly to backend file processing hub
        $.ajax({
            url: 'ai_chat_process.php',
            method: 'POST',
            data: { message: userText },
            dataType: 'json',
            success: function(res) {
                $('#' + loaderId).remove(); // Drop loading indicator
                $('#aiChatBody').append('<div class="chat-msg assistant">' + res.reply + '</div>');
                autoScrollChat();
            },
            error: function() {
                $('#' + loaderId).remove();
                $('#aiChatBody').append('<div class="chat-msg assistant text-danger"><i class="fas fa-exclamation-triangle me-1"></i> Sorry, system failed to reach AI server core.</div>');
                autoScrollChat();
            }
        });
    });

    function autoScrollChat() {
        var chatBody = document.getElementById('aiChatBody');
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    function htmlEntities(str) {
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
});
</script>

<?php include('footer.php'); ?>