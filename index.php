<?php
require_once __DIR__ . '/includes/functions.php';
car_rental_start_session();
require_once __DIR__ . '/includes/db.php';

// جلب السيارات المتاحة فقط
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;

// Generate CSRF token for actions
$csrf_token = generate_csrf_token();

// Build the base SQL query with placeholders
$sql = "SELECT c.id, c.name, c.model, c.year, c.image_name, c.price_per_day, b.name AS branch_name 
        FROM cars c
        JOIN branches b ON c.branch_id = b.id
        WHERE c.isavailable = 1";

$params = [];
$types = '';

// Add search filter if provided
if ($search) {
    $sql .= " AND (c.name LIKE ? OR c.model LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

// Add branch filter if provided
if ($branch_id) {
    $sql .= " AND c.branch_id = ?";
    $params[] = $branch_id;
    $types .= 'i';
}

// Execute prepared statement
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get branches using prepared statement
$branches_stmt = $conn->prepare("SELECT id, name FROM branches");
$branches_stmt->execute();
$branches = $branches_stmt->get_result();
?>

<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Car Rental Management System portfolio demo: discover vehicles, compare daily rates and explore a simulated booking workflow.">
  <title>Car Rental | Find your next ride</title>
  <link rel="stylesheet" href="css/ui-site.css?v=4">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
</head>
<body class="ui-site">
  <header class="site-header">
    <div class="site-header-inner">
      <a href="index.php" class="site-logo" aria-label="Car Rental home"><span class="site-logo-mark">C</span> CAR<span>RENTAL</span></a>
      <button type="button" class="site-menu-button" id="menu-icon" aria-controls="primary-nav" aria-expanded="false" aria-label="Toggle navigation">
        <i class="bx bx-menu" aria-hidden="true"></i>
      </button>
      <nav class="navbar" id="primary-nav" aria-label="Main navigation">
        <a href="#home">Home</a>
        <a href="#how-it-works">How it works</a>
        <a href="#services">Explore cars</a>
        <a href="#about">About</a>
        <a href="php/advanced_search.php">Advanced search</a>
      </nav>
      <div class="site-auth">
      <?php if (isset($_SESSION['id'])): ?>
        <span class="site-welcome">Hi, <?= escape_output($_SESSION['userName'] ?? 'Driver') ?></span>
        <?php if (!empty($_SESSION['isAdmin'])): ?><a class="site-link" href="admin/index.php">Dashboard</a><?php endif; ?>
        <a class="site-btn site-btn-outline" href="php/logout.php">Sign out</a>
      <?php else: ?>
        <a class="site-link" href="php/loginview.php?show=signin">Sign in</a>
        <a class="site-btn site-btn-solid" href="php/loginview.php?show=signup">Create account</a>
      <?php endif; ?>
      </div>
    </div>
  </header>
  <main>
    <section class="site-hero" id="home">
      <div class="site-hero-overlay"></div>
      <div class="site-container site-hero-inner">
        <div class="site-hero-content">
          <span class="site-eyebrow site-eyebrow-light"><i class="bx bx-car"></i> SMART RENTAL EXPERIENCE</span>
          <h1>Find the right car.<br><em>Enjoy the journey.</em></h1>
          <p>Discover vehicles, compare daily rates and explore an easy rental workflow in our interactive portfolio demo.</p>
          <div class="site-hero-actions">
            <a class="site-btn site-btn-solid site-btn-lg" href="#services">Browse available cars <i class="bx bx-right-arrow-alt" aria-hidden="true"></i></a>
            <a class="site-btn site-btn-glass site-btn-lg" href="#how-it-works">How it works</a>
          </div>
          <p class="site-demo-label"><i class="bx bx-info-circle" aria-hidden="true"></i> Demo only: no real vehicles or payments are provided.</p>
        </div>
        <div class="site-hero-floating" aria-hidden="true">
          <span class="site-float-icon"><i class="bx bx-check-shield"></i></span>
          <span><strong>Simple booking journey</strong><small>Search · Reserve · Simulate</small></span>
        </div>
      </div>
    </section>

    <section class="site-steps" id="how-it-works">
      <div class="site-container">
        <div class="site-section-header">
          <span class="site-eyebrow">HOW IT WORKS</span>
          <h2>Three steps to your next drive</h2>
          <p>Everything you need to explore a rental experience, presented clearly.</p>
        </div>
        <div class="site-steps-grid">
          <article class="site-step"><span class="site-step-icon"><i class="bx bx-search-alt"></i></span><span class="site-step-number">01</span><h3>Discover</h3><p>Browse the fleet and filter by car name, model or pickup branch.</p></article>
          <article class="site-step"><span class="site-step-icon"><i class="bx bx-calendar-check"></i></span><span class="site-step-number">02</span><h3>Reserve</h3><p>Choose your pickup date and duration to see the estimated total.</p></article>
          <article class="site-step"><span class="site-step-icon"><i class="bx bx-check-circle"></i></span><span class="site-step-number">03</span><h3>Confirm a demo</h3><p>Review the booking summary and simulate payment without a card.</p></article>
        </div>
      </div>
    </section>

    <section class="site-catalog" id="services">
      <div class="site-container">
        <div class="site-section-header site-section-header-row">
          <div><span class="site-eyebrow">EXPLORE OUR FLEET</span><h2>Available cars</h2><p>Find a vehicle for your next journey.</p></div>
          <a class="site-text-link" href="php/advanced_search.php">Advanced filters <i class="bx bx-right-arrow-alt" aria-hidden="true"></i></a>
        </div>
        <form class="site-filter" method="get" action="index.php#services" role="search">
          <div class="site-field"><label for="car-search">Car name or model</label><input id="car-search" type="search" name="search" placeholder="e.g. Toyota Corolla" value="<?= escape_output($search) ?>"></div>
          <div class="site-field"><label for="branch-filter">Pickup branch</label><select id="branch-filter" name="branch_id"><option value="">All branches</option>
          <?php while ($branch = $branches->fetch_assoc()): ?>
            <option value="<?= (int)$branch['id'] ?>" <?= $branch_id === (int)$branch['id'] ? 'selected' : '' ?>><?= escape_output($branch['name']) ?></option>
          <?php endwhile; ?>
          </select></div>
          <button class="site-btn site-btn-solid site-filter-submit" type="submit"><i class="bx bx-search"></i> Search cars</button>
          <?php if ($search !== '' || $branch_id > 0): ?><a class="site-reset" href="index.php#services">Clear filters</a><?php endif; ?>
        </form>
        <div class="site-results-meta"><span><?= (int)$result->num_rows ?> vehicle<?= $result->num_rows === 1 ? '' : 's' ?> found</span><span>Prices are illustrative</span></div>
        <div class="services-container">
          <?php if ($result->num_rows): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <?php $photo = !empty($row['image_name']) ? basename($row['image_name']) : 'placeholder_car.png'; ?>
              <article class="site-car-card">
                <div class="site-car-photo">
                  <img src="uploads/<?= escape_output($photo) ?>" alt="<?= escape_output(($row['name'] ?? '') . ' ' . ($row['model'] ?? '')) ?>" loading="lazy" onerror="this.onerror=null;this.src='uploads/placeholder_car.png'">
                  <span class="site-available"><i class="bx bx-check-circle"></i> Available</span>
                </div>
                <div class="site-car-body">
                  <span class="site-car-year"><?= (int)($row['year'] ?? 0) ?></span>
                  <h3><?= escape_output(trim(($row['name'] ?? '') . ' ' . ($row['model'] ?? ''))) ?></h3>
                  <p class="site-car-branch"><i class="bx bx-map-pin"></i> <?= escape_output($row['branch_name']) ?></p>
                  <div class="site-car-bottom">
                    <p class="site-price"><strong>$<?= number_format((float)$row['price_per_day'], 2) ?></strong><span>/day</span></p>
                    <a class="site-btn site-btn-solid" href="php/rent.php?car_id=<?= (int)$row['id'] ?>">View &amp; reserve</a>
                  </div>
                </div>
              </article>
            <?php endwhile; ?>
          <?php else: ?>
            <div class="site-empty"><i class="bx bx-search-alt"></i><h3>No cars found</h3><p>Try another model or select a different branch.</p><a class="site-btn site-btn-outline" href="index.php#services">Show all cars</a></div>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <section class="site-about" id="about">
      <div class="site-container site-about-inner">
        <div><span class="site-eyebrow">BUILT TO BE SIMPLE</span><h2>Car rentals, made easier to explore.</h2><p>This portfolio project brings vehicle listings, reservation tracking, branch management and an administrative dashboard together in one PHP and MySQL application.</p><p class="site-small-note">Interactive demonstration only — bookings, prices and payment records are not real-world transactions.</p><a href="php/advanced_search.php" class="site-btn site-btn-solid">Explore the fleet <i class="bx bx-right-arrow-alt"></i></a></div>
        <div class="site-about-image"><img src="img/carhomephoto.webp" alt="Car photography for the portfolio rental demo" loading="lazy"></div>
      </div>
    </section>
  </main>
  <footer class="site-footer"><div class="site-container site-footer-inner"><a class="site-logo" href="index.php"><span class="site-logo-mark">C</span> CAR<span>RENTAL</span></a><p>Car Rental Management System · Portfolio demonstration</p><a href="#home" class="site-text-link">Back to top ↑</a></div></footer>
  <script src="js/main.js"></script>
</body>
</html>
