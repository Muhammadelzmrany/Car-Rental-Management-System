<?php
session_start();
include('includes/db.php');

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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>car rental web</title>
    <!--link css-->
    <link rel="stylesheet" href="css/style.css">
    <!--box icon-->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/boxicons@latest/css/boxicons.min.css">
</head>

<body>
    <!--header-->
    <header>
        <a href="#" class="logo"><img src="img/icon b copy.png" alt=""></a>

        <div class="bx bx-menu" id="menu-icon"></div>
        <ul class="navbar">
            <il><a href="#home">HOME</a></il>
            <il><a href="#ride">RIDE</a></il>
            <il><a href="#services">SERVICES</a></il>
            <il><a href="#about">ABOUT</a></il>
            <il><a href="#reviewS">REVIEWS</a></il>
        </ul>
        <?php
        if (isset($_SESSION["id"])) {
        ?>
            <div class="head-btn">
                <label class="welcome">Welcome: <?= $_SESSION["userName"] ?></label>
                <a href="php/logout.php" class="sign-in">Sign out</a>
            </div>
        <?php
        } else {
        ?>
            <div class="head-btn">
                <a href="php/loginview.php?show=signup" class="sign-up">Sign up</a>
                <a href="php/loginview.php?show=signin" class="sign-in">Sign In</a>
            </div>
        <?php } ?>
    </header>

    <!--home-->
    <section class="home" id="home">
        <div class="text">
            <h1><span>LOOKING TO<br>RENT A CAR</span></h1>
            <p> website allows users to search, compare, and book vehicles online,<br> offering various car categories, pricing options, and secure reservations.<br> It typically includes filters, reviews, and customer support for a seamless experience.</p>
            <div class="app-stores">
                <img src="img/ios.png" alt="">
                <img src="img/512x512.png" alt="">
            </div>
        </div>
    </section>
     <!--ride-->
     <section class="ride"  id="ride">
        <div class="heading">
            <span>How It's Work</span>
        <h1>Rental With 3  Steps</h1>

        </div>
        <div class="ride-container">
            <div class="box">
                <i class='bx bx-map'></i>
                <h2>Choose A Location</h2>
                <p>allows users to search, compare, and book vehicles online, offering various car categories, pricing options, and secure reservations. It typically includes filters, reviews, and customer support for a seamless experience.</p>
                
            </div>
            <div class="box">
                <i class='bx bx-calendar' ></i>
                <h2>Pick-Up Date</h2>
                <p>allows users to search, compare, and book vehicles online, offering various car categories, pricing options, and secure reservations. It typically includes filters, reviews, and customer support for a seamless experience.</p>
            </div>

            <div class="box">
                <i class='bx bxs-calendar-star'></i>
                <h2>Book A Car</h2>
                <p>allows users to search, compare, and book vehicles online, offering various car categories, pricing options, and secure reservations. It typically includes filters, reviews, and customer support for a seamless experience.</p>
                
            </div>
        </div>
    </section>


    <!--services-car section-->
    <section class="services-car" id="services">
    <form method="GET" action="">
        <input type="text" name="search" placeholder="Search by car name or model" value="<?= htmlspecialchars($search) ?>">
        <select name="branch_id">
            <option value="">Select Branch</option>
            <?php while ($branch = $branches->fetch_assoc()) { ?>
                <option value="<?= $branch['id'] ?>" <?= $branch_id == $branch['id'] ? 'selected' : '' ?>>
                    <?= $branch['name'] ?>
                </option>
            <?php } ?>
        </select>
        <button type="submit">Search</button>
    </form>
</section>


        <div class="heading">
            <span>Best SERVICES</span>
            <h1>Rent Cars Now!!</h1>
        </div>

        <div class="services-container">
            <?php
            try {
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
            ?>
                        <div class="box">
                            <div class="box-img">
                                <img src="uploads/<?= $row['image_name'] ?? '' ?>" alt="">
                            </div>
                            <p><?= $row['year'] ?? '' ?></p>
                            <h3><?= $row['name'] . " " . $row['model'] ?></h3>
                            <h2>$<?= $row['price_per_day'] ?> <span>/day</span></h2>
                            <a href="php/rent.php?car_id=<?= (int)$row['id'] ?>" class="btn">Rent Now</a>
                        </div>
            <?php
                    }
                } else {
                    echo "<p>No cars available for the selected search criteria.</p>";
                }
            } catch (Exception $ex) {
                echo "<p>Error: " . $ex->getMessage() . "</p>";
            }
            ?>
        </div>
    </section>

    <!--about-->
    <section class="about" id="about">
        <div class="heading">
            <span>About Us</span>
            <h1>Best Customer Experience</h1>
        </div>
        <div class="about-container">
            <div class="about-img">
                <img src="img/icon b copy.png" alt="">
            </div>
            <div class="about-text">
                <span>About Us</span>
                <p>Lorem ipsum dolor sit amet consectetur adipisicing elit. Placeat maxime repellat autem nesciunt possimus cupiditate perferendis totam nam saepe velit dolorem vero labore, dolores necessitatibus id modi suscipit, inventore iure.</p>
                <p>Lorem ipsum dolor sit amet consectetur, adipisicing elit. Odit doloremque totam harum fugit vel dignissimos nesciunt sint excepturi.</p>
                <a href="#" class="btn">Learn More</a>
            </div>
        </div>
    </section>

    </section>
    <!--nwsltter-->
    <section class="newsletter">
        <h2>Subscribe to Newsletter</h2>
        <div class="box">
            <input type="text" placeholder="Enter Your Email">
            <a href="$" class="btn">Subscribe</a>
        </div>
    </section>
    <div class="copyright">
        <p>&#169; carpoolVenom All Right Reserved</p>
        <div class="social">
            <a href="#"><i class='bx bxl-facebook-circle' ></i></a>
            <a href="#"><i class='bx bxl-twitter' ></i></a>
            <a href="#"><i class='bx bxl-instagram' ></i></a>
        </div>
    </div>
    <!--scrollreveal-->
    <script src="https://unpkg.com/scrollreveal"></script>
    <!--link to js-->
    <script src="js/main.js"></script>
</body>
</html>
