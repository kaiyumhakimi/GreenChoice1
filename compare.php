<?php 
include('db.php'); 
include('header.php'); 

// 1. Grab the incoming IDs safely
$raw_ids = isset($_GET['ids']) ? $_GET['ids'] : [];

// 2. Normalize input formats (handles array or comma-separated string)
if (is_string($raw_ids)) {
    $ids = array_filter(explode(',', $raw_ids));
} else if (is_array($raw_ids)) {
    $ids = $raw_ids;
} else {
    $ids = [];
}

if (empty($ids)) {
    echo "<div class='search-page-bg text-center py-5' style='background: linear-gradient(180deg, #0b2419 0%, #071710 100%); min-height:100vh; padding-top:120px;'>
            <div class='container'>
                <h2 class='text-white fw-bold'>No products selected for comparison.</h2>
                <a href='search.php' class='btn btn-success rounded-pill mt-3 px-4 shadow'>Back to Search</a>
            </div>
          </div>";
    include('footer.php');
    exit;
}

// 3. Clean up input integers
$idList = implode(',', array_map('intval', $ids));

// 4. Query Products
$sql = "SELECT * FROM product WHERE productID IN ($idList)";
$result = $conn->query($sql);

$products = [];
if ($result) {
    while($row = $result->fetch_assoc()) { 
        $products[] = $row; 
    }
}

// 5. Specification Structural Groups
$spec_groups = [
    'core' => [
        'title' => 'Core Parameters',
        'icon'  => 'fa-box-open',
        'specs' => [
            'Brand'        => 'Brand / Manufacturer',
            'Model_Number' => 'Model Number',
            'Product_Type' => 'Category',
            'Sub_Type'     => 'Sub Type',
            'rating'       => 'Rating',
            'description'  => 'Description'
        ]
    ],
    'eco' => [
        'title' => 'Eco Performance & Tiers',
        'icon'  => 'fa-leaf',
        'specs' => [
            'EPEAT_Tier'             => 'EPEAT Tier',
            'EPEAT_Certified'        => 'EPEAT Certified',
            'EnergyStar_Certified'   => 'EnergyStar Certified',
            'Pct_Better_Federal_Std' => '% Better Than Federal Std',
            'Date_Certified_ES'      => 'Date Certified (ES)',
            'Date_EPEAT_Registered'  => 'Date Registered (EPEAT)'
        ]
    ],
    'computing' => [
        'title' => 'System & Architecture',
        'icon'  => 'fa-microchip',
        'specs' => [
            'Processor_Brand'       => 'Processor Brand',
            'Processor_Name'        => 'Processor Name',
            'CPU_Cores'             => 'CPU Cores',
            'CPU_Base_GHz'          => 'CPU Base Clock',
            'CPU_Max_GHz'           => 'CPU Max Clock',
            'RAM_GB'                => 'RAM Space',
            'Storage_Type'          => 'Storage Architecture',
            'Display_Size_in'       => 'Display Matrix',
            'Display_Resolution_MP' => 'Display Quality',
            'Touch_Screen'          => 'Touch Screen Interface',
            'Battery_Wh'            => 'Battery Mass',
            'OS'                    => 'Operating Ecosystem'
        ]
    ],
    'energy' => [
        'title' => 'Energy Dynamics & Power Metrics',
        'icon'  => 'fa-bolt',
        'specs' => [
            'TEC_kWh'           => 'Typical Energy Consumption',
            'Annual_Energy_kWh' => 'Annual Energy Volume',
            'Off_Mode_W'        => 'Off Mode Power Draw',
            'Sleep_Mode_W'      => 'Sleep Mode Draw',
            'Short_Idle_W'      => 'Short Idle Footprint',
            'Voltage_V'         => 'Voltage Demands'
        ]
    ],
    'appliances' => [
        'title' => 'Appliance Attributes',
        'icon'  => 'fa-blender',
        'specs' => [
            'Drum_Capacity_cuft'      => 'Drum Capacity',
            'Total_Volume_cuft'       => 'Total Net Volume',
            'Volume_cuft'             => 'Chamber Volume',
            'Water_Use_gal_cycle'     => 'Water Use Per Cycle',
            'Annual_Water_gal'        => 'Annual Water Consumption',
            'IMEF'                    => 'IMEF Rating',
            'IWF'                     => 'IWF Factor',
            'CEF'                     => 'CEF Rating',
            'Heat_Pump_Technology'    => 'Heat Pump Module',
            'Vented_or_Ventless'      => 'Exhaust / Venting Profile',
            'Place_Settings'          => 'Place Capacity Setting',
            'Soil_Sensing'            => 'Soil Smart Sensor',
            'Tub_Material'            => 'Tub Element Compound',
            'Drying_Method'           => 'Extraction Drying Method',
            'Ice_Maker'               => 'Integrated Ice Unit',
            'Connected'               => 'Smart Sync Features',
            'Connected_Functionality' => 'Ecosystem Sync Profile'
        ]
    ],
    'dimensions' => [
        'title' => 'Physical Framework & Footprint',
        'icon'  => 'fa-ruler-combined',
        'specs' => [
            'Height_in' => 'Height',
            'Width_in'  => 'Width',
            'Depth_in'  => 'Depth'
        ]
    ]
];
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
    .matrix-wrapper-card {
        background: #ffffff !important;
        border-radius: 20px !important;
        box-shadow: 0 15px 35px rgba(0,0,0,0.3) !important;
        overflow: hidden !important;
        border: none !important;
        margin-bottom: 4rem;
    }

    .table-clean-matrix {
        margin-bottom: 0 !important;
        border-collapse: separate !important;
        border-spacing: 0 !important;
        background: #ffffff !important;
    }

    .table-clean-matrix thead th {
        position: sticky;
        top: 0;
        background: #f8f9fa !important;
        z-index: 10;
        border-bottom: 2px solid #eaedf1 !important;
        padding: 24px 16px !important;
    }

    .table-clean-matrix th:first-child, 
    .table-clean-matrix td:first-child {
        position: sticky;
        left: 0;
        background: #fdfdfd !important;
        z-index: 5;
        border-right: 1px solid #eaedf1 !important;
        font-weight: 600;
        color: #495057 !important;
        box-shadow: 2px 0 5px rgba(0,0,0,0.02);
    }

    .table-clean-matrix thead th:first-child {
        z-index: 11;
        background: #f8f9fa !important;
    }

    .table-clean-matrix td {
        padding: 16px 20px !important;
        color: #212529 !important;
        border-bottom: 1px solid #eff2f5 !important;
        background: #ffffff !important;
    }

    .table-clean-matrix tbody tr:hover td {
        background-color: #fafdfa !important;
    }

    .matrix-section-header {
        background: #f1f5f3 !important;
        color: #1b4d36 !important;
        font-weight: 700 !important;
        text-transform: uppercase;
        font-size: 0.85rem;
        letter-spacing: 1px;
        padding: 12px 20px !important;
        border-bottom: 1px solid #dbe3df !important;
    }

    .matrix-product-box {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.02);
        position: relative; 
    }

    .matrix-img-holder {
        height: 110px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #ffffff;
        margin-bottom: 12px;
    }

    .matrix-badge {
        padding: 6px 12px;
        font-size: 0.78rem;
        font-weight: 600;
        border-radius: 30px;
        display: inline-flex;
        align-items: center;
    }
</style>

<div class="search-page-bg" style="background: linear-gradient(180deg, #0b2419 0%, #071710 100%); min-height: 100vh; padding-top: 100px;">
    <div class="container-fluid px-4 py-4">
        
        <div class="text-center mb-4">
            <h2 class="fw-bold text-white mb-1" style="font-size: 2.3rem;">Product Matrix Comparison</h2>
            <p class="text-white-50 small">Detailed technical evaluation matrix</p>
        </div>

        <?php if (!empty($products)): ?>
            <div class="matrix-wrapper-card">
                <div class="table-responsive">
                    <table class="table table-clean-matrix align-middle">
                        <thead>
                            <tr>
                                <th style="min-width: 250px; width: 250px;">
                                    <div class="text-muted small text-uppercase fw-bold tracking-wider"><i class="fas fa-sliders-h me-2 text-success"></i>Specifications</div>
                                </th>
                                <?php foreach($products as $p): ?>
                                    <th class="text-center" style="min-width: 290px;">
                                        <div class="matrix-product-box">

                                            <div class="matrix-img-holder">
                                                <?php if (!empty($p['image_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($p['image_url']); ?>" style="max-height: 100%; max-width: 100%; object-fit: contain;" alt="Product Asset">
                                                <?php else: ?>
                                                    <i class="fas fa-box text-muted opacity-25" style="font-size:2.5rem;"></i>
                                                <?php endif; ?>
                                            </div>
                                            <span class="text-success small fw-bold text-uppercase d-block mb-1" style="font-size:0.75rem;"><?php echo htmlspecialchars($p['Brand'] ?? ''); ?></span>
                                            <span class="d-block fw-bold text-dark mb-3 text-truncate" style="font-size:0.95rem;" title="<?php echo htmlspecialchars($p['Product_Name'] ?? ''); ?>">
                                                <?php echo htmlspecialchars($p['Product_Name'] ?? 'Unnamed Product'); ?>
                                            </span>
                                            <a href="details.php?id=<?php echo $p['productID']; ?>" class="btn btn-sm btn-success rounded-pill px-4 w-100 fw-semibold text-white" style="font-size:0.8rem;">
                                                View Profile <i class="fas fa-arrow-right ms-1" style="font-size:0.7rem;"></i>
                                            </a>
                                        </div>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            
                            <?php foreach ($spec_groups as $groupKey => $groupMeta): 
                                $validRowsInGroup = [];
                                foreach ($groupMeta['specs'] as $column => $label) {
                                    $has_data = false;
                                    foreach ($products as $p) {
                                        if (isset($p[$column]) && trim((string)$p[$column]) !== '' && strtolower(trim((string)$p[$column])) !== 'null') {
                                            $has_data = true;
                                            break;
                                        }
                                    }
                                    if ($has_data) {
                                        $validRowsInGroup[$column] = $label;
                                    }
                                }

                                if (empty($validRowsInGroup)) continue;
                            ?>
                                
                                <tr>
                                    <td colspan="<?php echo count($products) + 1; ?>" class="matrix-section-header">
                                        <i class="fas <?php echo $groupMeta['icon']; ?> me-2"></i><?php echo $groupMeta['title']; ?>
                                    </td>
                                </tr>

                                <?php foreach ($validRowsInGroup as $column => $label): ?>
                                    <tr>
                                        <td class="p-3 text-secondary" style="font-size:0.88rem;"><?php echo htmlspecialchars($label); ?></td>
                                        
                                        <?php foreach($products as $p): ?>
                                            <td class="p-3 text-center" style="font-size:0.9rem;">
                                                <?php 
                                                $value = isset($p[$column]) ? trim((string)$p[$column]) : '';
                                                
                                                if ($value === '' || strtolower($value) === 'null'): ?>
                                                    <span class="text-muted opacity-50">—</span>
                                                <?php else: 
                                                    if (str_contains($column, 'GHz'))  $value .= ' GHz';
                                                    if (str_contains($column, 'GB'))   $value .= ' GB';
                                                    if (str_contains($column, '_in'))  $value .= '"';
                                                    if (str_contains($column, '_W'))   $value .= ' W';
                                                    if (str_contains($column, '_V'))   $value .= ' V';
                                                    if (str_contains($column, 'Wh'))   $value .= ' Wh';
                                                    if (str_contains($column, 'cuft')) $value .= ' cu ft';
                                                    if (str_contains($column, 'gal'))  $value .= ' gallons';
                                                    if (str_contains($column, 'kWh'))  $value .= ' kWh';
                                                    if (str_contains($column, 'MP'))   $value .= ' MP';

                                                    if (strtoupper($value) === 'TRUE' || $value === '1')  $value = 'Yes';
                                                    if (strtoupper($value) === 'FALSE' || $value === '0') $value = 'No';

                                                    if ($column === 'EPEAT_Tier'): ?>
                                                        <span class="matrix-badge border text-dark" style="background:<?php echo match($value) { 'Gold' => '#FFF9E6', 'Silver' => '#F1F3F5', default => '#FDF4EE' }; ?>; border-color:<?php echo match($value) { 'Gold' => '#FFE082', 'Silver' => '#CED4DA', default => '#F5CBA7' }; ?> !important;">
                                                            <i class="fas fa-certificate me-1 <?php echo match($value) { 'Gold' => 'text-warning', 'Silver' => 'text-secondary', default => 'text-danger' }; ?>"></i><?php echo htmlspecialchars($value); ?>
                                                        </span>
                                                    <?php elseif (($column === 'EnergyStar_Certified' || $column === 'EPEAT_Certified' || $column === 'Touch_Screen' || $column === 'Connected' || $column === 'Soil_Sensing' || $column === 'Ice_Maker' || $column === 'Heat_Pump_Technology') && strtolower($value) === 'yes'): ?>
                                                        <span class="matrix-badge bg-success text-white px-2.5 py-1"><i class="fas fa-check me-1"></i> Yes</span>
                                                    <?php elseif (($column === 'EnergyStar_Certified' || $column === 'EPEAT_Certified' || $column === 'Touch_Screen' || $column === 'Connected' || $column === 'Soil_Sensing' || $column === 'Ice_Maker' || $column === 'Heat_Pump_Technology') && strtolower($value) === 'no'): ?>
                                                        <span class="matrix-badge bg-light text-muted border px-2.5 py-1"><i class="fas fa-times me-1"></i> No</span>
                                                    <?php elseif ($column === 'rating'): ?>
                                                        <span class="matrix-badge bg-warning text-dark px-2.5 py-1 fw-bold"><i class="fas fa-star me-1 text-dark"></i><?php echo htmlspecialchars($value); ?></span>
                                                    <?php elseif (filter_var($p[$column], FILTER_VALIDATE_URL) || str_contains($column, 'URL')): ?>
                                                        <a href="<?php echo htmlspecialchars($p[$column]); ?>" target="_blank" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-0.5" style="font-size:0.75rem;">
                                                            View Asset <i class="fas fa-external-link-alt ms-1" style="font-size:0.6rem;"></i>
                                                        </a>
                                                    <?php elseif ($column === 'Pct_Better_Federal_Std'): ?>
                                                        <span class="fw-bold text-success" style="font-size:1rem;">+<?php echo htmlspecialchars($value); ?>%</span>
                                                    <?php else: ?>
                                                        <span class="fw-bold text-dark"><?php echo htmlspecialchars($value); ?></span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                            
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center pt-3 pb-5">
            <a href="search.php" class="btn btn-outline-light rounded-pill px-5 py-2.5 fw-bold shadow-sm" style="border: 1px solid rgba(255,255,255,0.25); text-decoration:none;">
                <i class="fas fa-plus me-2 text-success"></i>Compare Another Product
            </a>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>