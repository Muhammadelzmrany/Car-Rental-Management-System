<?php
/**
 * Advanced Car Search
 * البحث المتقدم عن السيارات
 */

require_once '../includes/functions.php';
require_once '../includes/db.php';

// Get search parameters
$search = isset($_GET['search']) ? sanitize_input(trim($_GET['search'])) : '';
$branch_id = isset($_GET['branch_id']) ? (int)$_GET['branch_id'] : 0;
$year_min = isset($_GET['year_min']) ? (int)$_GET['year_min'] : 0;
$year_max = isset($_GET['year_max']) ? (int)$_GET['year_max'] : 0;
$price_min = isset($_GET['price_min']) ? (float)$_GET['price_min'] : 0;
$price_max = isset($_GET['price_max']) ? (float)$_GET['price_max'] : 0;
$model = isset($_GET['model']) ? sanitize_input(trim($_GET['model'])) : '';

// Build SQL query
$sql = "SELECT c.id, c.name, c.model, c.year, c.plate_id, c.image_name, c.price_per_day, 
               COALESCE(c.status, CASE WHEN c.isavailable = 1 THEN 'available' ELSE 'rented' END) AS status,
               b.name AS branch_name, b.address AS branch_address
        FROM cars c
        JOIN branches b ON c.branch_id = b.id
        WHERE c.isavailable = 1";

$params = [];
$types = '';

// Add search filter
if ($search) {
    $sql .= " AND (c.name LIKE ? OR c.model LIKE ? OR c.plate_id LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

// Add branch filter
if ($branch_id > 0) {
    $sql .= " AND c.branch_id = ?";
    $params[] = $branch_id;
    $types .= 'i';
}

// Add year filters
if ($year_min > 0) {
    $sql .= " AND c.year >= ?";
    $params[] = $year_min;
    $types .= 'i';
}
if ($year_max > 0) {
    $sql .= " AND c.year <= ?";
    $params[] = $year_max;
    $types .= 'i';
}

// Add price filters
if ($price_min > 0) {
    $sql .= " AND c.price_per_day >= ?";
    $params[] = $price_min;
    $types .= 'd';
}
if ($price_max > 0) {
    $sql .= " AND c.price_per_day <= ?";
    $params[] = $price_max;
    $types .= 'd';
}

// Add model filter
if ($model) {
    $sql .= " AND c.model LIKE ?";
    $model_param = '%' . $model . '%';
    $params[] = $model_param;
    $types .= 's';
}

$sql .= " ORDER BY c.price_per_day ASC";

// Execute query
$stmt = $conn->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get branches for dropdown
$branches_stmt = $conn->prepare("SELECT id, name FROM branches ORDER BY name");
$branches_stmt->execute();
$branches = $branches_stmt->get_result();

// Get unique models for dropdown
$models_stmt = $conn->prepare("SELECT DISTINCT model FROM cars WHERE model IS NOT NULL AND model != '' ORDER BY model");
$models_stmt->execute();
$models = $models_stmt->get_result();

// Get year range
$year_range_stmt = $conn->prepare("SELECT MIN(year) AS min_year, MAX(year) AS max_year FROM cars WHERE year IS NOT NULL");
$year_range_stmt->execute();
$year_range = $year_range_stmt->get_result()->fetch_assoc();

// Get price range
$price_range_stmt = $conn->prepare("SELECT MIN(price_per_day) AS min_price, MAX(price_per_day) AS max_price FROM cars");
$price_range_stmt->execute();
$price_range = $price_range_stmt->get_result()->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Car Search</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
    <style>
        .search-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .search-form input, .search-form select {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .search-form button {
            padding: 10px 20px;
            background: #4a90e2;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            grid-column: 1 / -1;
        }
        .search-form button:hover {
            background: #357abd;
        }
        .results-count {
            margin: 20px 0;
            font-size: 18px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <header>
        <a href="../index.php" class="logo"><img src="../img/icon b copy.png" alt=""></a>
        <div class="bx bx-menu" id="menu-icon"></div>
        <ul class="navbar">
            <li><a href="index.php#home">HOME</a></li>
            <li><a href="index.php#services">SERVICES</a></li>
            <li><a href="advanced_search.php">ADVANCED SEARCH</a></li>
        </ul>
        <?php if (isset($_SESSION["id"])): ?>
            <div class="head-btn">
                <label class="welcome">Welcome: <?= escape_output($_SESSION["userName"]) ?></label>
                <a href="logout.php" class="sign-in">Sign out</a>
            </div>
        <?php else: ?>
            <div class="head-btn">
                <a href="loginview.php?show=signup" class="sign-up">Sign up</a>
                <a href="loginview.php?show=signin" class="sign-in">Sign In</a>
            </div>
        <?php endif; ?>
    </header>

    <section class="services-car" id="services" style="padding-top: 100px;">
        <div class="search-container">
            <h2 style="margin-bottom: 20px;">Advanced Car Search</h2>
            
            <form method="GET" action="" class="search-form">
                <div>
                    <label>Search (Name, Model, Plate ID):</label>
                    <input type="text" name="search" placeholder="Search..." value="<?= escape_output($search) ?>">
                </div>
                
                <div>
                    <label>Branch:</label>
                    <select name="branch_id">
                        <option value="">All Branches</option>
                        <?php while ($branch = $branches->fetch_assoc()): ?>
                            <option value="<?= (int)$branch['id'] ?>" <?= $branch_id == $branch['id'] ? 'selected' : '' ?>>
                                <?= escape_output($branch['name']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label>Model:</label>
                    <select name="model">
                        <option value="">All Models</option>
                        <?php while ($model_row = $models->fetch_assoc()): ?>
                            <option value="<?= escape_output($model_row['model']) ?>" <?= $model == $model_row['model'] ? 'selected' : '' ?>>
                                <?= escape_output($model_row['model']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div>
                    <label>Year From:</label>
                    <input type="number" name="year_min" placeholder="Min Year" value="<?= $year_min > 0 ? $year_min : '' ?>" 
                           min="<?= $year_range['min_year'] ?? 1900 ?>" max="<?= $year_range['max_year'] ?? date('Y') ?>">
                </div>
                
                <div>
                    <label>Year To:</label>
                    <input type="number" name="year_max" placeholder="Max Year" value="<?= $year_max > 0 ? $year_max : '' ?>" 
                           min="<?= $year_range['min_year'] ?? 1900 ?>" max="<?= $year_range['max_year'] ?? date('Y') ?>">
                </div>
                
                <div>
                    <label>Price From ($):</label>
                    <input type="number" name="price_min" placeholder="Min Price" value="<?= $price_min > 0 ? $price_min : '' ?>" 
                           min="0" step="0.01">
                </div>
                
                <div>
                    <label>Price To ($):</label>
                    <input type="number" name="price_max" placeholder="Max Price" value="<?= $price_max > 0 ? $price_max : '' ?>" 
                           min="0" step="0.01">
                </div>
                
                <button type="submit">Search</button>
            </form>
            
            <div class="results-count">
                Found <?= $result->num_rows ?> car(s)
            </div>
        </div>

        <div class="heading">
            <h1>Search Results</h1>
        </div>

        <div class="services-container">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
            ?>
                    <div class="box">
                        <div class="box-img">
                            <img src="../uploads/<?= escape_output($row['image_name'] ?? 'default.jpg') ?>" alt="<?= escape_output($row['name']) ?>">
                        </div>
                        <p><?= escape_output($row['year']) ?></p>
                        <h3><?= escape_output($row['name'] . " " . $row['model']) ?></h3>
                        <p><strong>Plate ID:</strong> <?= escape_output($row['plate_id']) ?></p>
                        <p><strong>Branch:</strong> <?= escape_output($row['branch_name']) ?></p>
                        <h2>$<?= number_format($row['price_per_day'], 2) ?> <span>/day</span></h2>
                        <?php if (isset($_SESSION["id"])): ?>
                            <a href="rent.php?car_id=<?= (int)$row['id'] ?>" class="btn">Rent Now</a>
                        <?php else: ?>
                            <a href="loginview.php?show=signin" class="btn">Login to Rent</a>
                        <?php endif; ?>
                    </div>
            <?php
                }
            } else {
                echo "<p style='text-align: center; padding: 40px;'>No cars found matching your search criteria.</p>";
            }
            ?>
        </div>
    </section>

    <?php
    $stmt->close();
    $branches_stmt->close();
    $models_stmt->close();
    $year_range_stmt->close();
    $price_range_stmt->close();
    ?>
</body>
</html>



