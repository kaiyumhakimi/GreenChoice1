<?php
include('header.php');

// =====================
// NEWS API CONFIG
// =====================
$apiKey = "23a95f592ef0472f9192c59250549038";

// BROAD QUERY (IMPORTANT: ensures results always exist)
$query = urlencode("technology electronics gadgets smartphones laptops AI");
$url = "https://newsapi.org/v2/everything?q=$query&language=en&sortBy=publishedAt&pageSize=20&apiKey=$apiKey";

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "User-Agent: GreenChoiceApp/1.0"
]);

$response = curl_exec($ch);

curl_close($ch);

$data = json_decode($response, true);

$articles = $data['articles'] ?? [];

// =====================
// ECO FILTER (KEY PART)
// =====================

// Limit to 6 cards
$articles = array_slice($articles, 0, 6);

// fallback image
$defaultImage = "https://via.placeholder.com/600x400?text=Eco+News";
?>

<!-- ===================== -->
<!-- UI (MATCH YOUR STYLE) -->
<!-- ===================== -->

<div class="search-page-bg d-flex align-items-center flex-column justify-content-center"
     style="min-height: calc(100vh - 56px); width: 100%;">

    <div class="container py-5">

        <!-- HEADER -->
        <div class="text-center text-white mb-5">
            <h1 class="display-4 fw-bold mb-2">Eco Awareness</h1>
            <p class="lead text-white-50">
                Sustainability & eco-friendly electronics news.
            </p>
        </div>

        <!-- NEWS GRID -->
        <div class="row">

            <?php if (!empty($articles)): ?>

                <?php foreach ($articles as $article): ?>

                    <?php $image = $article['urlToImage'] ?? $defaultImage; ?>

                    <div class="col-lg-4 col-md-6 mb-4">

                        <a href="<?= $article['url'] ?>" target="_blank" class="text-decoration-none">

                            <div class="card border-0 shadow-lg h-100"
                                 style="border-radius: 25px; overflow:hidden; background: rgba(255,255,255,0.95);">

                                <!-- IMAGE -->
                                <img src="<?= htmlspecialchars($image) ?>"
                                     style="height:200px; width:100%; object-fit:cover;"
                                     onerror="this.src='<?= $defaultImage ?>'">

                                <!-- CONTENT -->
                                <div class="p-4 text-center">

                                    <h5 class="fw-bold text-dark mb-2">
                                        <?= htmlspecialchars($article['title'] ?? 'No title') ?>
                                    </h5>

                                    <p class="text-muted small">
                                        <?= htmlspecialchars(substr($article['description'] ?? 'No description available.', 0, 120)) ?>...
                                    </p>

                                    <span class="text-success fw-bold">Read More →</span>

                                </div>

                            </div>

                        </a>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <div class="col-12 text-center text-white">
                    <p>No eco-related news found at the moment.</p>
                </div>

            <?php endif; ?>

        </div>

    </div>
</div>

<?php include('footer.php'); ?>