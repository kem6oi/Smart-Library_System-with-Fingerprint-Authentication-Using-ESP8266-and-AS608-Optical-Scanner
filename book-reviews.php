<?php
session_start();
error_reporting(0);
include('includes/config.php');
include('includes/reviews.php');

// Check authentication
if (strlen($_SESSION['login']) == 0) {
    header('location:index.php');
    exit();
}

$studentId = $_SESSION['stdid'];
$bookId = isset($_GET['bookid']) ? intval($_GET['bookid']) : 0;

if ($bookId == 0) {
    header('location:dashboard.php');
    exit();
}

// Get book details
$sql = "SELECT b.*, a.AuthorName, c.CategoryName
        FROM tblbooks b
        LEFT JOIN tblauthors a ON a.id = b.AuthorId
        LEFT JOIN tblcategory c ON c.id = b.CatId
        WHERE b.id = :bookid";
$query = $dbh->prepare($sql);
$query->bindParam(':bookid', $bookId, PDO::PARAM_INT);
$query->execute();
$book = $query->fetch(PDO::FETCH_ASSOC);

if (!$book) {
    header('location:dashboard.php');
    exit();
}

// Handle review submission
if (isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating']);
    $reviewText = trim($_POST['review_text']);

    $result = addBookReview($dbh, $bookId, $studentId, $rating, $reviewText);

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }

    header("location:book-reviews.php?bookid=$bookId");
    exit();
}

// Handle review vote
if (isset($_GET['vote']) && isset($_GET['reviewid'])) {
    $reviewId = intval($_GET['reviewid']);
    $voteType = $_GET['vote'] === 'helpful' ? 'helpful' : 'not_helpful';

    $result = voteOnReview($dbh, $reviewId, $studentId, $voteType);

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }

    header("location:book-reviews.php?bookid=$bookId");
    exit();
}

// Get rating summary
$ratingSummary = getBookRatingSummary($dbh, $bookId);

// Get reviews
$reviews = getBookReviews($dbh, $bookId, 20);

// Get student's existing review
$myReview = getStudentReview($dbh, $bookId, $studentId);
?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    <title><?php echo htmlspecialchars($book['BookName']); ?> - Reviews</title>
    <link href="assets/css/bootstrap.css" rel="stylesheet" />
    <link href="assets/css/font-awesome.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
        .book-header {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .book-cover {
            width: 200px;
            height: 300px;
            object-fit: cover;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        .rating-summary {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .rating-bar {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }
        .rating-bar .stars {
            width: 80px;
            text-align: right;
            margin-right: 10px;
        }
        .rating-bar .bar {
            flex: 1;
            height: 20px;
            background: #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
        }
        .rating-bar .bar-fill {
            height: 100%;
            background: #f39c12;
        }
        .rating-bar .count {
            width: 50px;
            text-align: right;
            margin-left: 10px;
        }
        .star-rating {
            color: #f39c12;
            font-size: 2em;
        }
        .review-form {
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .review-item {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        .review-author {
            font-weight: bold;
            color: #333;
        }
        .review-date {
            color: #999;
            font-size: 0.9em;
        }
        .review-rating {
            color: #f39c12;
        }
        .review-text {
            line-height: 1.6;
            color: #555;
        }
        .review-votes {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .vote-btn {
            background: none;
            border: 1px solid #ddd;
            padding: 5px 15px;
            border-radius: 20px;
            cursor: pointer;
            margin-right: 10px;
            transition: all 0.3s;
        }
        .vote-btn:hover {
            background: #f5f5f5;
        }
        .rating-input {
            font-size: 2em;
            cursor: pointer;
            color: #ddd;
        }
        .rating-input.selected {
            color: #f39c12;
        }
    </style>
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="content-wrapper">
        <div class="container">
            <!-- Success/Error Messages -->
            <?php if (isset($_SESSION['success'])) { ?>
                <div class="alert alert-success">
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
            <?php } ?>
            <?php if (isset($_SESSION['error'])) { ?>
                <div class="alert alert-danger">
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php } ?>

            <!-- Book Header -->
            <div class="book-header">
                <div class="row">
                    <div class="col-md-3">
                        <?php if ($book['bookImage']) { ?>
                            <img src="admin/bookimg/<?php echo htmlspecialchars($book['bookImage']); ?>"
                                 class="book-cover" alt="Book Cover">
                        <?php } else { ?>
                            <div class="book-cover" style="background: #ddd; display: flex; align-items: center; justify-content: center;">
                                <i class="fa fa-book" style="font-size: 4em; color: #999;"></i>
                            </div>
                        <?php } ?>
                    </div>
                    <div class="col-md-9">
                        <h2><?php echo htmlspecialchars($book['BookName']); ?></h2>
                        <p><strong>Author:</strong> <?php echo htmlspecialchars($book['AuthorName']); ?></p>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($book['CategoryName']); ?></p>
                        <p><strong>ISBN:</strong> <?php echo htmlspecialchars($book['ISBNNumber']); ?></p>
                        <p><strong>Price:</strong> $<?php echo htmlspecialchars($book['BookPrice']); ?></p>

                        <?php if ($ratingSummary['total_reviews'] > 0) { ?>
                            <div class="star-rating">
                                <?php
                                $avgRating = round($ratingSummary['avg_rating'], 1);
                                $fullStars = floor($avgRating);
                                $hasHalfStar = ($avgRating - $fullStars) >= 0.5;

                                for ($i = 0; $i < $fullStars; $i++) {
                                    echo '★';
                                }
                                if ($hasHalfStar) {
                                    echo '☆';
                                }
                                ?>
                                <span style="font-size: 0.6em; color: #666;">
                                    <?php echo $avgRating; ?> out of 5 (<?php echo $ratingSummary['total_reviews']; ?> reviews)
                                </span>
                            </div>
                        <?php } else { ?>
                            <p><em>No reviews yet. Be the first to review!</em></p>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Rating Summary -->
            <?php if ($ratingSummary['total_reviews'] > 0) { ?>
                <div class="rating-summary">
                    <h4>Rating Breakdown</h4>
                    <div class="rating-bar">
                        <div class="stars">5 ★</div>
                        <div class="bar">
                            <div class="bar-fill" style="width: <?php echo $ratingSummary['five_star_pct']; ?>%;"></div>
                        </div>
                        <div class="count"><?php echo $ratingSummary['five_star']; ?></div>
                    </div>
                    <div class="rating-bar">
                        <div class="stars">4 ★</div>
                        <div class="bar">
                            <div class="bar-fill" style="width: <?php echo $ratingSummary['four_star_pct']; ?>%;"></div>
                        </div>
                        <div class="count"><?php echo $ratingSummary['four_star']; ?></div>
                    </div>
                    <div class="rating-bar">
                        <div class="stars">3 ★</div>
                        <div class="bar">
                            <div class="bar-fill" style="width: <?php echo $ratingSummary['three_star_pct']; ?>%;"></div>
                        </div>
                        <div class="count"><?php echo $ratingSummary['three_star']; ?></div>
                    </div>
                    <div class="rating-bar">
                        <div class="stars">2 ★</div>
                        <div class="bar">
                            <div class="bar-fill" style="width: <?php echo $ratingSummary['two_star_pct']; ?>%;"></div>
                        </div>
                        <div class="count"><?php echo $ratingSummary['two_star']; ?></div>
                    </div>
                    <div class="rating-bar">
                        <div class="stars">1 ★</div>
                        <div class="bar">
                            <div class="bar-fill" style="width: <?php echo $ratingSummary['one_star_pct']; ?>%;"></div>
                        </div>
                        <div class="count"><?php echo $ratingSummary['one_star']; ?></div>
                    </div>
                </div>
            <?php } ?>

            <!-- Review Form -->
            <div class="review-form">
                <h4><?php echo $myReview ? 'Update Your Review' : 'Write a Review'; ?></h4>
                <form method="post" onsubmit="return validateReview()">
                    <div class="form-group">
                        <label>Rating *</label>
                        <div id="rating-stars">
                            <?php
                            $currentRating = $myReview ? $myReview['rating'] : 0;
                            for ($i = 1; $i <= 5; $i++) {
                                $class = $i <= $currentRating ? 'selected' : '';
                                echo "<span class='rating-input $class' data-rating='$i' onclick='selectRating($i)'>★</span>";
                            }
                            ?>
                        </div>
                        <input type="hidden" name="rating" id="rating-value" value="<?php echo $currentRating; ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Review (Optional)</label>
                        <textarea name="review_text" class="form-control" rows="5"
                                  placeholder="Share your thoughts about this book..."><?php echo $myReview ? htmlspecialchars($myReview['review_text']) : ''; ?></textarea>
                    </div>
                    <button type="submit" name="submit_review" class="btn btn-primary">
                        <?php echo $myReview ? 'Update Review' : 'Submit Review'; ?>
                    </button>
                </form>
            </div>

            <!-- Reviews List -->
            <div class="reviews-list">
                <h4>Customer Reviews (<?php echo count($reviews); ?>)</h4>
                <?php
                if (!empty($reviews)) {
                    foreach ($reviews as $review) {
                        ?>
                        <div class="review-item">
                            <div class="review-header">
                                <div>
                                    <div class="review-author"><?php echo htmlspecialchars($review['student_name']); ?></div>
                                    <div class="review-date"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></div>
                                </div>
                                <div class="review-rating">
                                    <?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?>
                                </div>
                            </div>
                            <?php if ($review['review_text']) { ?>
                                <div class="review-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></div>
                            <?php } ?>
                            <div class="review-votes">
                                <a href="?bookid=<?php echo $bookId; ?>&vote=helpful&reviewid=<?php echo $review['id']; ?>"
                                   class="vote-btn">
                                    <i class="fa fa-thumbs-up"></i> Helpful (<?php echo $review['helpful_count']; ?>)
                                </a>
                                <a href="?bookid=<?php echo $bookId; ?>&vote=not_helpful&reviewid=<?php echo $review['id']; ?>"
                                   class="vote-btn">
                                    <i class="fa fa-thumbs-down"></i> Not Helpful (<?php echo $review['not_helpful_count']; ?>)
                                </a>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<p class="text-center" style="padding: 40px; color: #999;">No reviews yet. Be the first to review this book!</p>';
                }
                ?>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>

    <script src="assets/js/jquery-1.10.2.js"></script>
    <script src="assets/js/bootstrap.js"></script>

    <script>
        function selectRating(rating) {
            document.getElementById('rating-value').value = rating;
            const stars = document.querySelectorAll('.rating-input');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.classList.add('selected');
                } else {
                    star.classList.remove('selected');
                }
            });
        }

        function validateReview() {
            const rating = document.getElementById('rating-value').value;
            if (!rating || rating == 0) {
                alert('Please select a rating');
                return false;
            }
            return true;
        }
    </script>
</body>
</html>
