<?php include('header.php'); ?>

<div class="search-page-bg d-flex align-items-center" style="min-height: 90vh;">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 col-md-8">
                
                <div class="card border-0 shadow-lg p-4 p-md-5" style="border-radius: 30px; background: rgba(255, 255, 255, 0.95);">
                    
                    <div class="text-center mb-5">
                        <div class="mb-3 d-inline-block p-3 rounded-circle bg-light">
                            <i class="fas fa-leaf fa-2x text-success"></i>
                        </div>
                        <h2 class="fw-bold text-dark mb-1">Create Account</h2>
                        <p class="text-muted">Join the GreenChoice community</p>
                    </div>

                    <form action="register_process.php" method="POST">
                        
                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold small ps-3">Full Name</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 rounded-pill-start ps-3">
                                    <i class="far fa-user text-muted"></i>
                                </span>
                                <input type="text" name="username" 
                                       class="form-control bg-light border-0 rounded-pill-end p-3" 
                                       placeholder="John Doe" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold small ps-3">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 rounded-pill-start ps-3">
                                    <i class="far fa-envelope text-muted"></i>
                                </span>
                                <input type="email" name="email" 
                                       class="form-control bg-light border-0 rounded-pill-end p-3" 
                                       placeholder="name@example.com" required>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-sm-6 mb-4 mb-sm-0">
                                <label class="form-label text-dark fw-bold small ps-3">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 rounded-pill-start ps-3">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" name="password" 
                                           class="form-control bg-light border-0 rounded-pill-end p-3" 
                                           placeholder="••••••••" required>
                                </div>
                            </div>
                            
                            <div class="col-sm-6">
                                <label class="form-label text-dark fw-bold small ps-3">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 rounded-pill-start ps-3">
                                        <i class="fas fa-shield-alt text-muted"></i>
                                    </span>
                                    <input type="password" name="confirm_password" 
                                           class="form-control bg-light border-0 rounded-pill-end p-3" 
                                           placeholder="••••••••" required>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="register_btn" class="btn btn-dark w-100 py-3 rounded-pill fw-bold shadow-sm mb-4" style="letter-spacing: 1px;">
                            REGISTER
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="text-muted small">
                            Have an account? 
                            <a href="login.php" class="text-success fw-bold text-decoration-none ms-1">Sign In</a>
                        </p>
                    </div>

                </div>
                
                <div class="text-center mt-4">
                    <a href="index.php" class="text-white-50 text-decoration-none small">
                        <i class="fas fa-arrow-left me-2"></i> Back to Home
                    </a>
                </div>

            </div>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>