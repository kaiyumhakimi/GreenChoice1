<?php include('header.php'); ?>

<div class="search-page-bg d-flex align-items-center" style="min-height: 90vh;">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-5 col-md-7">
                
                <div class="card border-0 shadow-lg p-4 p-md-5" style="border-radius: 30px; background: rgba(255, 255, 255, 0.95);">
                    
                    <div class="text-center mb-5">
                        <div class="mb-3 d-inline-block p-3 rounded-circle bg-light">
                            <i class="fas fa-leaf fa-2x text-success"></i>
                        </div>
                        <h2 class="fw-bold text-dark mb-1">Welcome Back</h2>
                        <p class="text-muted">Sign in to your GreenChoice account</p>
                    </div>

                    <form action="login_process.php" method="POST">
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

                        <div class="mb-4">
                            <label class="form-label text-dark fw-bold small ps-3">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-0 rounded-pill-start ps-3">
                                    <i class="fas fa-lock text-muted"></i>
                                </span>
                                <input type="password" name="password" id="passwordInput"
                                       class="form-control bg-light border-0 p-3" 
                                       placeholder="••••••••" required>
                                <button class="btn btn-light border-0 rounded-pill-end pe-3" type="button" id="togglePassword">
                                    <i class="far fa-eye text-muted" id="eyeIcon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="text-end mb-4">
                            <a href="#" class="text-success small text-decoration-none fw-bold">Forgot Password?</a>
                        </div>

                        <button type="submit" name="login_btn" class="btn btn-dark w-100 py-3 rounded-pill fw-bold shadow-sm mb-4" style="letter-spacing: 1px;">
                            SIGN IN
                        </button>
                    </form>

                    <div class="text-center">
                        <p class="text-muted small">
                            Don't have an account? 
                            <a href="register.php" class="text-success fw-bold text-decoration-none ms-1">Sign Up Now</a>
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

<script>
    const togglePassword = document.querySelector('#togglePassword');
    const passwordInput = document.querySelector('#passwordInput');
    const eyeIcon = document.querySelector('#eyeIcon');

    togglePassword.addEventListener('click', function () {
        // Toggle the type attribute
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        // Toggle the eye icon between regular and slash
        eyeIcon.classList.toggle('fa-eye');
        eyeIcon.classList.toggle('fa-eye-slash');
    });
</script>

<?php include('footer.php'); ?>