<?php 
include('db.php'); 
include('header.php'); 

// --- SESSION LOGIC ---
$isLoggedIn = isset($_SESSION['auth']) && $_SESSION['auth'] === true;
$current_user_id = $isLoggedIn ? (int)$_SESSION['userID'] : 0;

// --- PAGINATION SETTINGS ---
$limit = 12; 
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// --- FILTER INPUTS ---
$searchTerm    = isset($_GET['q'])           ? trim($_GET['q'])           : '';
$selectedCats  = isset($_GET['categories'])  ? $_GET['categories']        : [];
$selectedTiers = isset($_GET['tiers'])       ? $_GET['tiers']             : [];
$filterBrand   = isset($_GET['brand'])       ? trim($_GET['brand'])       : '';
$filterOS      = isset($_GET['os'])          ? trim($_GET['os'])          : '';
$filterRAM     = isset($_GET['ram'])         ? (int)$_GET['ram']          : 0;
$filterStorage = isset($_GET['storage'])     ? trim($_GET['storage'])     : '';
$filterCPU     = isset($_GET['cpu'])         ? (float)$_GET['cpu']        : 0;
$filterDisplay = isset($_GET['display'])     ? (float)$_GET['display']    : 0;
$filterTouch   = isset($_GET['touch'])       ? trim($_GET['touch'])       : '';
$filterBattery = isset($_GET['battery'])     ? (int)$_GET['battery']      : 0;
$filterConnected    = isset($_GET['connected'])      ? trim($_GET['connected'])     : '';
$filterHeatPump     = isset($_GET['heat_pump'])      ? trim($_GET['heat_pump'])     : '';
$filterVented       = isset($_GET['vented'])         ? trim($_GET['vented'])        : '';
$filterIceMaker     = isset($_GET['ice_maker'])      ? trim($_GET['ice_maker'])     : '';
$filterMaxEnergy    = isset($_GET['max_energy'])     ? (int)$_GET['max_energy']     : 0;
$filterMaxWater     = isset($_GET['max_water'])      ? (int)$_GET['max_water']      : 0;
$filterPlaceSet     = isset($_GET['place_settings']) ? (int)$_GET['place_settings'] : 0;
$filterEcoMin       = isset($_GET['eco_min'])        ? (int)$_GET['eco_min']        : 0;

// ─────────────────────────────────────────────────────────────────
// CATEGORY → DB VALUE MAP
// ─────────────────────────────────────────────────────────────────
$categoryMap = [
    'Computers & Displays' => ['Laptop'],
    'Mobile Phones'        => ['Mobile Phone'],
    'Home Appliances'      => ['Clothes Dryer', 'Dishwasher', 'Refrigerator', 'Washing Machine'],
];

$activeCatGroup = 'all';
if (!empty($selectedCats)) {
    $hasDevice    = in_array('Computers & Displays', $selectedCats) || in_array('Mobile Phones', $selectedCats);
    $hasAppliance = in_array('Home Appliances', $selectedCats);
    if ($hasDevice && !$hasAppliance)     $activeCatGroup = 'device';
    elseif ($hasAppliance && !$hasDevice) $activeCatGroup = 'appliance';
    else                                  $activeCatGroup = 'all';
}

// ─────────────────────────────────────────────────────────────────
// STEP 1 — RECOMMENDER FUNCTION
// ─────────────────────────────────────────────────────────────────
function fetchHybridScores(int $user_id): array {
    $url = "http://localhost:5000/recommend?" . http_build_query([
        'user_id' => $user_id,
        'top_n'   => 9999,
        'alpha'   => 0.5,
        'save'    => 1,
    ]);
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10]);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_reset($ch);
    unset($ch);
    if ($http_code !== 200 || !$response) return [];
    $data = json_decode($response, true);
    return $data['recommendations'] ?? [];
}

// ─────────────────────────────────────────────────────────────────
// STEP 2 — INITIALISE
// ─────────────────────────────────────────────────────────────────
$hybridScoreMap = [];
$useRecommender = false;

// ─────────────────────────────────────────────────────────────────
// STEP 3 — CALL API
// ─────────────────────────────────────────────────────────────────
if ($isLoggedIn) {
    $apiRecs = fetchHybridScores($current_user_id);
    if (!empty($apiRecs)) {
        $useRecommender = true;
        foreach ($apiRecs as $rec) {
            $hybridScoreMap[(int)$rec['productID']] = (float)$rec['hybrid_score'];
        }
    }
}


// ─────────────────────────────────────────────────────────────────
// STEP 5 — BUILD SQL WHERE CLAUSES
// ─────────────────────────────────────────────────────────────────
$whereClauses = ["1=1"];

if ($searchTerm !== '') {
    $s = $conn->real_escape_string($searchTerm);
    $whereClauses[] = "(Product_Name LIKE '%$s%' OR Brand LIKE '%$s%' OR Model_Number LIKE '%$s%')";
}

if (!empty($selectedCats)) {
    $dbTypes = [];
    foreach ($selectedCats as $label) {
        if (isset($categoryMap[$label])) $dbTypes = array_merge($dbTypes, $categoryMap[$label]);
    }
    if (!empty($dbTypes)) {
        $typeList = "'" . implode("','", array_map([$conn, 'real_escape_string'], $dbTypes)) . "'";
        $whereClauses[] = "Product_Type IN ($typeList)";
    }
}

if (!empty($selectedTiers)) {
    $epeatTiers = array_filter($selectedTiers, fn($t) => $t !== 'EnergyStar');
    $energyStar = in_array('EnergyStar', $selectedTiers);
    $conditions = [];
    if (!empty($epeatTiers)) {
        $tierList     = "'" . implode("','", array_map([$conn, 'real_escape_string'], $epeatTiers)) . "'";
        $conditions[] = "EPEAT_Tier IN ($tierList)";
    }
    if ($energyStar) $conditions[] = "EnergyStar_Certified NOT IN ('No','no','')";
    if (!empty($conditions)) $whereClauses[] = '(' . implode(' OR ', $conditions) . ')';
}

if ($filterBrand   !== '') $whereClauses[] = "Brand = '" . $conn->real_escape_string($filterBrand) . "'";
if ($filterOS      !== '') $whereClauses[] = "OS = '"    . $conn->real_escape_string($filterOS)    . "'";
if ($filterStorage !== '') $whereClauses[] = "Storage_Type = '" . $conn->real_escape_string($filterStorage) . "'";
if ($filterTouch   !== '') $whereClauses[] = "Touch_Screen = '" . $conn->real_escape_string($filterTouch)   . "'";
if ($filterRAM     > 0)    $whereClauses[] = "CAST(RAM_GB AS DECIMAL(10,2)) >= $filterRAM";
if ($filterCPU     > 0)    $whereClauses[] = "CAST(CPU_Base_GHz AS DECIMAL(10,2)) >= $filterCPU";
if ($filterDisplay > 0)    $whereClauses[] = "CAST(Display_Size_in AS DECIMAL(10,2)) >= $filterDisplay";
if ($filterBattery > 0)    $whereClauses[] = "CAST(Battery_Wh AS DECIMAL(10,2)) >= $filterBattery";
if ($filterConnected !== '') $whereClauses[] = "Connected = '" . $conn->real_escape_string($filterConnected) . "'";
if ($filterHeatPump  !== '') $whereClauses[] = "Heat_Pump_Technology = '" . $conn->real_escape_string($filterHeatPump) . "'";
if ($filterVented    !== '') $whereClauses[] = "Vented_or_Ventless = '" . $conn->real_escape_string($filterVented) . "'";
if ($filterIceMaker  !== '') $whereClauses[] = "Ice_Maker = '" . $conn->real_escape_string($filterIceMaker) . "'";
if ($filterPlaceSet  > 0)    $whereClauses[] = "CAST(Place_Settings AS DECIMAL(10,2)) >= $filterPlaceSet";
if ($filterMaxEnergy > 0)    $whereClauses[] = "CAST(Annual_Energy_kWh AS DECIMAL(10,2)) <= $filterMaxEnergy";
if ($filterMaxWater  > 0)    $whereClauses[] = "CAST(Annual_Water_gal AS DECIMAL(10,2)) <= $filterMaxWater";
if ($filterEcoMin    > 0)    $whereClauses[] = "CAST(Pct_Better_Federal_Std AS DECIMAL(10,2)) >= $filterEcoMin";

$whereSql = implode(" AND ", $whereClauses);

// ─────────────────────────────────────────────────────────────────
// STEP 6 — FETCH + SORT
// ─────────────────────────────────────────────────────────────────
if ($useRecommender) {
    $allResult = $conn->query("SELECT * FROM product WHERE $whereSql");
    $allRows   = [];
    while ($r = $allResult->fetch_assoc()) {
        $pid               = (int)$r['productID'];
        $r['hybrid_score'] = $hybridScoreMap[$pid] ?? 0.0;
        $allRows[]         = $r;
    }
    usort($allRows, fn($a, $b) => $b['hybrid_score'] <=> $a['hybrid_score']);
    $totalRows  = count($allRows);
    $totalPages = ceil($totalRows / $limit);
    $pageRows   = array_slice($allRows, $offset, $limit);
} else {
    $countQuery = $conn->query("SELECT COUNT(*) FROM product WHERE $whereSql");
    $totalRows  = $countQuery->fetch_row()[0];
    $totalPages = ceil($totalRows / $limit);
    $result     = $conn->query("SELECT * FROM product WHERE $whereSql ORDER BY CAST(Pct_Better_Federal_Std AS DECIMAL(10,4)) DESC, CAST(TEC_kWh AS DECIMAL(10,4)) ASC LIMIT $limit OFFSET $offset");
    $pageRows   = [];
    while ($r = $result->fetch_assoc()) { $r['hybrid_score'] = null; $pageRows[] = $r; }
}

// ─────────────────────────────────────────────────────────────────
// FETCH DISTINCT VALUES FOR DROPDOWNS
// ─────────────────────────────────────────────────────────────────
function getDistinct(mysqli $conn, string $col, string $where = "1=1"): array {
    $res  = $conn->query("SELECT DISTINCT $col FROM product WHERE $where AND $col IS NOT NULL AND $col <> '' ORDER BY $col");
    $vals = [];
    while ($r = $res->fetch_row()) $vals[] = $r[0];
    return $vals;
}
$deviceWhere    = "Product_Type IN ('Laptop','Mobile Phone')";
$applianceWhere = "Product_Type IN ('Clothes Dryer','Dishwasher','Refrigerator','Washing Machine')";

$brandOptions   = getDistinct($conn, 'Brand');
$osOptions      = getDistinct($conn, 'OS',                   $deviceWhere);
$storageOptions = getDistinct($conn, 'Storage_Type',         $deviceWhere);
$touchOptions   = getDistinct($conn, 'Touch_Screen',         $deviceWhere);
$connOptions    = getDistinct($conn, 'Connected',            $applianceWhere);
$heatOptions    = getDistinct($conn, 'Heat_Pump_Technology', $applianceWhere);
$ventOptions    = getDistinct($conn, 'Vented_or_Ventless',   $applianceWhere);
$iceOptions     = getDistinct($conn, 'Ice_Maker',            $applianceWhere);

function ecoDisplayScore(array $row): string {
    $pct = isset($row['Pct_Better_Federal_Std']) ? trim($row['Pct_Better_Federal_Std']) : '';
    if ($pct !== '' && is_numeric($pct)) return round((float)$pct, 1) . '% better than std';
    $tec = isset($row['TEC_kWh']) ? trim($row['TEC_kWh']) : '';
    if ($tec !== '' && is_numeric($tec)) return round((float)$tec, 2) . ' kWh TEC';
    return 'N/A';
}

function selectFilter(string $name, string $label, array $options, string $current, string $anyLabel = 'Any'): void {
    echo "<label class='form-label small fw-semibold mb-1'>$label</label>";
    echo "<select name='$name' class='form-select form-select-sm mb-3'>";
    echo "<option value=''>$anyLabel</option>";
    foreach ($options as $opt) {
        $sel = ($opt === $current) ? 'selected' : '';
        echo "<option value='" . htmlspecialchars($opt) . "' $sel>" . htmlspecialchars($opt) . "</option>";
    }
    echo "</select>";
}

function minFilter(string $name, string $label, array $options, float|int $current, string $unit = ''): void {
    echo "<label class='form-label small fw-semibold mb-1'>$label</label>";
    echo "<select name='$name' class='form-select form-select-sm mb-3'>";
    echo "<option value='0'>Any</option>";
    foreach ($options as $val) {
        $sel = ($current == $val) ? 'selected' : '';
        echo "<option value='$val' $sel>$val$unit+</option>";
    }
    echo "</select>";
}

$urlParams = $_GET;
unset($urlParams['page']);
$queryStringBase = http_build_query($urlParams);
$pageUrlPrefix   = !empty($queryStringBase) ? '?' . $queryStringBase . '&' : '?';
?>

<style>
    .product-img-container img { max-width:100%; max-height:100%; object-fit:contain; }
    .search-page-bg {
        background: linear-gradient(135deg, #0b2419 0%, #3a805a 45%, #89c2a1 100%) !important;
        min-height: 100vh;
        padding-bottom: 80px;
    }
    .filter-card-custom {
        background: #0b2419 !important;
        border: 1px solid rgba(255,255,255,0.15) !important;
        padding: 20px;
        border-radius: 12px;
        color: white;
    }
    .compare-bar-sticky {
        position: fixed; bottom: 0; left: 0; right: 0;
        background: #0b2419;
        border-top: 3px solid #28a745;
        box-shadow: 0 -5px 25px rgba(0,0,0,0.5);
        z-index: 1050;
        transform: translateY(100%);
        transition: transform 0.3s ease-in-out;
    }
    .compare-bar-sticky.show { transform: translateY(0); }
    .pagination .page-item .page-link {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 42px; height: 42px; padding: 0 16px;
        border-radius: 50px !important; margin: 0 4px;
        background-color: #265138; color: #fff; border: none;
        font-size: 0.9rem; font-weight: 500;
        transition: all 0.2s ease-in-out;
    }
    .pagination .page-item.active .page-link {
        background-color: #fff !important; color: #0b2419 !important;
        font-weight: 700; box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    }
    .pagination .page-item.disabled .page-link {
        background-color: rgba(255,255,255,0.08) !important;
        color: rgba(255,255,255,0.35) !important; cursor: not-allowed;
    }
    .pagination .page-item:not(.active):not(.disabled) .page-link:hover {
        background-color: #336d4b; color: #fff;
    }
</style>

<div class="search-page-bg">
    <div class="container-fluid pt-5">

        <div class="mb-5 px-xl-5">
            <h2 class="fw-bold text-white">
                <?php echo ($useRecommender && $searchTerm === '' && empty($selectedCats) && empty($selectedTiers))
                    ? 'Recommended For You' : 'Search Results'; ?>
            </h2>
            <p class="text-white-50">Showing <b><?php echo number_format($totalRows); ?></b> eco-certified products</p>
        </div>

        <form id="compareForm" action="compare.php" method="GET">
            <div class="row px-xl-5">

                <!-- ── FILTER SIDEBAR ── -->
                <div class="col-lg-3 col-md-4 mb-4">
                    <div class="filter-card-custom">
                        <h4 class="fw-bold mb-4">Filters</h4>

                        <h6 class="fw-bold mb-2"><i class="fas fa-th-large me-1"></i>Category</h6>
                        <div class="mb-4">
                            <?php
                            $catIcons = [
                                'Computers & Displays' => 'fa-laptop',
                                'Mobile Phones'        => 'fa-mobile-alt',
                                'Home Appliances'      => 'fa-blender',
                            ];
                            foreach ($catIcons as $label => $icon):
                                $checked = in_array($label, $selectedCats) ? 'checked' : '';
                            ?>
                            <div class="form-check">
                                <input type="checkbox" name="categories[]" value="<?php echo $label; ?>"
                                       class="form-check-input cat-checkbox" <?php echo $checked; ?>>
                                <label class="form-check-label small">
                                    <i class="fas <?php echo $icon; ?> me-1"></i><?php echo $label; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <h6 class="fw-bold mb-2"><i class="fas fa-tag me-1"></i>Brand</h6>
                        <div class="mb-4">
                            <?php selectFilter('brand', '', $brandOptions, $filterBrand, 'All Brands'); ?>
                        </div>

                        <div id="device-filters" <?php echo $activeCatGroup === 'appliance' ? 'style="display:none"' : ''; ?>>
                            <h6 class="fw-bold mb-2"><i class="fas fa-microchip me-1"></i>Device Specs</h6>
                            <div class="mb-4">
                                <?php
                                selectFilter('os',      'Operating System', $osOptions,      $filterOS);
                                selectFilter('storage', 'Storage Type',     $storageOptions, $filterStorage);
                                selectFilter('touch',   'Touch Screen',     $touchOptions,   $filterTouch);
                                minFilter('ram',     'Min RAM (GB)',        [4, 8, 16, 32, 64],        $filterRAM,     ' GB');
                                minFilter('cpu',     'Min CPU (GHz)',       [1.5, 2.0, 2.5, 3.0, 3.5], $filterCPU,     ' GHz');
                                minFilter('display', 'Min Display (inch)',  [11, 13, 14, 15, 17],       $filterDisplay, '"');
                                minFilter('battery', 'Min Battery (Wh)',    [30, 50, 70, 90, 100],      $filterBattery, ' Wh');
                                ?>
                            </div>
                        </div>

                        <div id="appliance-filters" <?php echo $activeCatGroup === 'device' ? 'style="display:none"' : ''; ?>>
                            <h6 class="fw-bold mb-2"><i class="fas fa-plug me-1"></i>Appliance Specs</h6>
                            <div class="mb-4">
                                <?php
                                selectFilter('connected',  'Connectivity',    $connOptions, $filterConnected);
                                selectFilter('heat_pump',  'Heat Pump',       $heatOptions, $filterHeatPump);
                                selectFilter('vented',     'Vented/Ventless', $ventOptions, $filterVented);
                                selectFilter('ice_maker',  'Ice Maker',       $iceOptions,  $filterIceMaker);
                                minFilter('place_settings', 'Min Place Settings', [4, 6, 8, 12, 14], $filterPlaceSet, ' pl');
                                ?>
                                <label class="form-label small fw-semibold mb-1">Max Annual Energy (kWh)</label>
                                <select name="max_energy" class="form-select form-select-sm mb-3">
                                    <option value="0">Any</option>
                                    <?php foreach ([100, 200, 300, 500, 700] as $v): ?>
                                    <option value="<?php echo $v; ?>" <?php echo $filterMaxEnergy == $v ? 'selected' : ''; ?>>
                                        &le; <?php echo $v; ?> kWh
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <label class="form-label small fw-semibold mb-1">Max Annual Water (gal)</label>
                                <select name="max_water" class="form-select form-select-sm mb-3">
                                    <option value="0">Any</option>
                                    <?php foreach ([500, 1000, 2000, 3000, 5000] as $v): ?>
                                    <option value="<?php echo $v; ?>" <?php echo $filterMaxWater == $v ? 'selected' : ''; ?>>
                                        &le; <?php echo number_format($v); ?> gal
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                       <h6 class="fw-bold mb-2"><i class="fas fa-leaf me-1"></i>Eco Performance</h6>

<div class="mb-3 p-2 rounded" style="background: rgba(255,255,255,0.08); border-left: 3px solid #28a745;">
    <small class="text-white-50" style="line-height:1.4;">
        Measures how much better a product performs compared to federal energy efficiency standards.
        Higher percentages indicate greater energy savings and lower environmental impact.
    </small>
</div>

<div class="mb-4">
    <label class="form-label small fw-semibold mb-1">Min % Better Than Federal Std</label>
    <select name="eco_min" class="form-select form-select-sm mb-3">
        <option value="0">Any</option>
        <?php foreach ([10, 20, 30, 50, 75] as $v): ?>
        <option value="<?php echo $v; ?>" <?php echo $filterEcoMin == $v ? 'selected' : ''; ?>>
            <?php echo $v; ?>%+
        </option>
        <?php endforeach; ?>
    </select>
</div>

                        <h6 class="fw-bold mb-2"><i class="fas fa-certificate me-1"></i>Certification</h6>
                        <div class="mb-4">
                            <?php
                            $tiers = ['Gold' => '#FFD700', 'Silver' => '#C0C0C0', 'Bronze' => '#CD7F32'];
                            foreach ($tiers as $tier => $color):
                                $checked = in_array($tier, $selectedTiers) ? 'checked' : '';
                            ?>
                            <div class="form-check">
                                <input type="checkbox" name="tiers[]" value="<?php echo $tier; ?>"
                                       class="form-check-input" <?php echo $checked; ?>>
                                <label class="form-check-label small">
                                    <span style="color:<?php echo $color; ?>;">●</span> EPEAT <?php echo $tier; ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                            <div class="form-check mt-1">
                                <input type="checkbox" name="tiers[]" value="EnergyStar"
                                       class="form-check-input" <?php echo in_array('EnergyStar', $selectedTiers) ? 'checked' : ''; ?>>
                                <label class="form-check-label small">
                                    <span style="color:#1a73e8;">★</span> Energy Star
                                </label>
                            </div>
                        </div>

                        <button type="button" id="submitFiltersBtn"
                                class="btn btn-light w-100 rounded-pill fw-bold py-2 shadow-sm mt-2">
                            Apply Filters
                        </button>
                        <div class="text-center mt-3">
                            <a href="search.php" class="text-white-50 small text-decoration-none">Clear All Filters</a>
                        </div>
                    </div>
                </div>

                <!-- ── PRODUCT CARDS ── -->
                <div class="col-lg-9 col-md-8">
                    <div class="row">
                        <?php 
                        if (!empty($pageRows)): 
                            foreach ($pageRows as $row):
                                $pid = (int)$row['productID'];
                                $isBookmarked = false;
                                if ($isLoggedIn) {
                                    $chk = $conn->query("SELECT favoriteID FROM favorite WHERE userID = $current_user_id AND productID = $pid");
                                    $isBookmarked = ($chk && $chk->num_rows > 0);
                                }
                                $typeIcon = match($row['Product_Type'] ?? '') {
                                    'Laptop'                           => 'fa-laptop',
                                    'Mobile Phone'                     => 'fa-mobile-alt',
                                    'Clothes Dryer','Washing Machine'  => 'fa-tshirt',
                                    'Dishwasher'                       => 'fa-sink',
                                    'Refrigerator'                     => 'fa-snowflake',
                                    default                            => 'fa-box',
                                };
                        ?>
                            <div class="col-lg-4 col-md-6 mb-4">
                                <div class="card search-product-card h-100 p-3 shadow-sm position-relative">

                                    <div class="position-absolute" style="top:10px;left:10px;z-index:10;">
                                        <input type="checkbox" name="ids[]" value="<?php echo $pid; ?>"
                                               class="compare-checkbox form-check-input border-success"
                                               style="width:22px;height:22px;cursor:pointer;">
                                    </div>

                                    <?php if ($useRecommender && !empty($row['hybrid_score']) && $row['hybrid_score'] > 0): ?>
                                    <div class="position-absolute" style="top:10px;right:10px;z-index:10;">
                                        <span class="badge bg-success opacity-75" style="font-size:0.65rem;">
                                            ★ <?php echo round((float)$row['hybrid_score'] * 100, 1); ?>% match
                                        </span>
                                    </div>
                                    <?php endif; ?>

                                    <div class="text-center bg-white rounded-3 p-3 mb-3 product-img-container"
                                         style="height:180px;display:flex;align-items:center;justify-content:center;overflow:hidden;">
                                        <?php if (!empty($row['image_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($row['image_url']); ?>" alt="Product Image">
                                        <?php else: ?>
                                            <i class="fas <?php echo $typeIcon; ?> fa-4x text-muted opacity-25"></i>
                                        <?php endif; ?>
                                    </div>

                                    <div class="card-body p-0 d-flex flex-column text-dark">
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($row['Product_Name'] ?? ''); ?></h6>
                                        <p class="text-muted small mb-2"><?php echo htmlspecialchars($row['Brand'] ?? ''); ?></p>

                                        <div class="mb-2">
                                            <span class="eco-score">Eco Score: <?php echo ecoDisplayScore($row); ?></span>
                                        </div>

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
                                                <button type="button"
                                                        class="btn <?php echo $isBookmarked ? 'btn-success' : 'btn-outline-success'; ?> btn-sm rounded-circle me-2 bookmark-btn"
                                                        data-id="<?php echo $pid; ?>">
                                                    <i class="<?php echo $isBookmarked ? 'fas' : 'far'; ?> fa-bookmark"></i>
                                                </button>
                                                <a href="details.php?id=<?php echo $pid; ?>"
                                                   class="btn btn-sm btn-dark rounded-pill px-3">View</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12 text-center py-5 text-white">
                                <i class="fas fa-search fa-3x mb-3 text-white-50"></i>
                                <h5>No Eco Products Found</h5>
                                <p class="text-white-50">Try broadening your filter selection.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ── PAGINATION ── -->
                    <?php if ($totalPages > 1): ?>
                    <nav class="mt-5 d-flex justify-content-center">
                        <ul class="pagination align-items-center">
                            <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $pageUrlPrefix . 'page=' . ($page - 1); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php
                            $range     = 2;
                            $startPage = max(1, $page - $range);
                            $endPage   = min($totalPages, $page + $range);
                            if ($startPage > 1) {
                                echo "<li class='page-item'><a class='page-link' href='{$pageUrlPrefix}page=1'>1</a></li>";
                                if ($startPage > 2) echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                            }
                            for ($p = $startPage; $p <= $endPage; $p++) {
                                $active = $p === $page ? 'active' : '';
                                echo "<li class='page-item $active'><a class='page-link' href='{$pageUrlPrefix}page=$p'>$p</a></li>";
                            }
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
                                echo "<li class='page-item'><a class='page-link' href='{$pageUrlPrefix}page=$totalPages'>$totalPages</a></li>";
                            }
                            ?>
                            <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo $pageUrlPrefix . 'page=' . ($page + 1); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        </ul>
                    </nav>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ── COMPARE BAR ── -->
            <div id="compareBar" class="compare-bar-sticky py-3 text-white">
                <div class="container d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="mb-1 fw-bold">
                            <i class="fas fa-exchange-alt me-2 text-success"></i>Product Comparison
                        </h5>
                        <p class="mb-0 small text-white-50">Select 2–4 products to compare side-by-side.</p>
                    </div>
                    <div>
                        <span class="badge bg-light text-dark px-3 py-2 rounded-pill fw-bold me-3"
                              id="compareCountBadge" style="font-size:0.9rem;">0 Selected</span>
                        <button type="submit" id="compareSubmitBtn"
                                class="btn btn-success px-4 rounded-pill fw-bold shadow-sm" disabled>
                            Compare Selected
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function () {
    var isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

    // ── Compare bar ──
    function updateCompareBar() {
        var count = $('.compare-checkbox:checked').length;
        $('#compareCountBadge').text(count + ' Selected');
        if (count > 0) { $('#compareBar').addClass('show'); } 
        else           { $('#compareBar').removeClass('show'); }
        if (count >= 2 && count <= 4) { $('#compareSubmitBtn').prop('disabled', false); }
        else                          { $('#compareSubmitBtn').prop('disabled', true); }
    }

    $('.compare-checkbox').on('change', function () {
        var count = $('.compare-checkbox:checked').length;
        if (count > 4) { this.checked = false; alert('Maximum 4 products for comparison.'); return; }
        updateCompareBar();
    });

    $('#compareSubmitBtn').on('click', function () {
        $('#compareForm').attr('action', 'compare.php').submit();
    });

    // ── Apply Filters ──
    $('#submitFiltersBtn').on('click', function () {
        var form = $('#compareForm');
        $('.compare-checkbox').prop('disabled', true);
        form.attr('action', 'search.php').attr('method', 'GET').submit();
    });

    // ── Category show/hide spec sections ──
    function updateSpecVisibility() {
        var selected = $('.cat-checkbox:checked').map(function(){ return $(this).val(); }).get();
        if (selected.length === 0) {
            $('#device-filters, #appliance-filters').show(); return;
        }
        var hasDevice    = selected.includes('Computers & Displays') || selected.includes('Mobile Phones');
        var hasAppliance = selected.includes('Home Appliances');
        $('#device-filters').toggle(hasDevice || (!hasDevice && !hasAppliance));
        $('#appliance-filters').toggle(hasAppliance || (!hasDevice && !hasAppliance));
    }
    $('.cat-checkbox').on('change', updateSpecVisibility);

    // ── Bookmark toggle — calls add_favorite.php (plain text response) ──
    $('.bookmark-btn').on('click', function (e) {
        e.preventDefault();
        if (!isLoggedIn) {
            alert('Please sign in to bookmark items!');
            window.location.href = 'login.php';
            return;
        }
        var btn = $(this), pid = btn.data('id'), icon = btn.find('i');
        $.ajax({
            url: 'add_favorite.php',
            method: 'POST',
            data: { product_id: pid },
            success: function (res) {
                res = res.trim();
                if (res === 'success') {
                    icon.removeClass('far').addClass('fas');
                    btn.removeClass('btn-outline-success').addClass('btn-success');
                } else if (res === 'removed') {
                    icon.removeClass('fas').addClass('far');
                    btn.removeClass('btn-success').addClass('btn-outline-success');
                } else if (res === 'unauthenticated') {
                    window.location.href = 'login.php';
                }
            }
        });
    });
});
</script>

<?php include('footer.php'); ?>