<?php
// Start session to access userID and username
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Helper variables
$isLoggedIn = isset($_SESSION['auth']) && $_SESSION['auth'] === true;
$current_user = $isLoggedIn ? $_SESSION['username'] : 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>GreenChoice</title>

<link rel="icon" href="assets/favicon.ico">
<link href="https://fonts.googleapis.com/css?family=Montserrat:400,700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="css/styles.css" rel="stylesheet">

<script src="https://use.fontawesome.com/releases/v6.3.0/js/all.js"></script>
</head>

<body>

<?php include('sidebar.php'); ?>

<nav id="mainNav" class="custom-navbar">
<div class="container d-flex align-items-center justify-content-between">

    <div class="d-flex align-items-center">
        <button class="btn text-white border-0 p-0 me-4" onclick="openNav()">
            <i class="fas fa-bars fa-2x"></i>
        </button>
        <a href="index.php" class="navbar-brand text-white">
            GREENCHOICE
        </a>
    </div>

    <div class="d-flex align-items-center">

    <form action="search.php" method="GET">
        <div class="search-box-custom d-none d-md-flex me-4">
            <i class="fas fa-search search-icon"></i>
            <input type="text" name="q" placeholder="Search..." value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
            <button type="submit" style="display: none;"></button>
        </div>
    </form>

    <a href="bookmarks.php" class="text-white me-4">
        <i class="far fa-bookmark fa-2x"></i>
    </a>

    <?php if($isLoggedIn): ?>
        <div class="dropdown">
            <a href="#" class="text-white dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-user-circle fa-2x"></i>
            </a>
            <ul class="dropdown-menu dropdown-menu-end shadow">
                <li class="px-3 py-2 border-bottom">
                    <small class="text-muted d-block">Logged in as:</small>
                    <strong><?php echo htmlspecialchars($current_user); ?></strong>
                </li>
                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user id-card me-2"></i>View Profile</a></li>
                <li><a class="dropdown-item" href="bookmarks.php"><i class="fas fa-heart me-2"></i>My Favorites</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
            </ul>
        </div>
    <?php else: ?>
        <a href="login.php" class="text-white">
            <i class="far fa-user-circle fa-2x"></i>
        </a>
    <?php endif; ?>

</div>

</div>
</nav>