<?php include('header.php'); ?>

<div class="search-page-bg d-flex align-items-center flex-column justify-content-center" style="min-height: calc(100vh - 56px); width: 100%;">    <div class="container py-5">
        
        <div class="text-center text-white mb-5">
            <h1 class="display-4 fw-bold mb-2">Categories</h1>
            <p class="lead text-white-50">Select a category to find sustainable electronic options.</p>
        </div>

        <div class="row justify-content-center px-xl-5">
            
            <div class="col-lg-4 col-md-6 mb-4">
                <a href="search.php?categories[]=Mobile+Phones" class="text-decoration-none">
                    <div class="card border-0 shadow-lg p-4 text-center h-100 transition-card" style="border-radius: 30px; background: rgba(255, 255, 255, 0.95);">
                        <div class="my-3 d-inline-block p-3 rounded-circle bg-light mx-auto" style="width: fit-content;">
                            <i class="fas fa-mobile-alt fa-2x text-success"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-2">Phones</h3>
                        <p class="text-muted small mb-3">Compare the latest EPEAT-certified smartphones and mobile devices.</p>
                    </div>
                </a>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
                <a href="search.php?categories[]=Computers+%26+Displays" class="text-decoration-none">
                    <div class="card border-0 shadow-lg p-4 text-center h-100 transition-card" style="border-radius: 30px; background: rgba(255, 255, 255, 0.95);">
                        <div class="my-3 d-inline-block p-3 rounded-circle bg-light mx-auto" style="width: fit-content;">
                            <i class="fas fa-laptop fa-2x text-success"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-2">Laptops</h3>
                        <p class="text-muted small mb-3">High-performance laptops and displays with low energy consumption.</p>
                    </div>
                </a>
            </div>

            <div class="col-lg-4 col-md-6 mb-4">
    <a href="search.php?categories[]=Imaging+Equipment" class="text-decoration-none">
        <div class="card border-0 shadow-lg p-4 text-center h-100 transition-card" style="border-radius: 30px; background: rgba(255, 255, 255, 0.95);">
            <div class="my-3 d-inline-block p-3 rounded-circle bg-light mx-auto" style="width: fit-content;">
                <!-- Updated classes to match the other icons perfectly -->
                <i class="fas fa-blender fa-2x text-success"></i>
            </div>
            <h3 class="fw-bold text-dark mb-2">Home Appliances</h3>
            <p class="text-muted small mb-3">Energy-efficient washers, dryers & more</p>
        </div>
    </a>
</div>

        </div>
    </div>
</div>

<?php include('footer.php'); ?>