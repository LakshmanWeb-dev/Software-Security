<?php	
	// Ensure the form was submitted
	if(!isset($_POST['save_change'])){
		echo "Invalid request!";
		exit;
	}

	// Sanitize and validate input
	$isbn = filter_var(trim($_POST['isbn']), FILTER_SANITIZE_STRING);
	$title = filter_var(trim($_POST['title']), FILTER_SANITIZE_STRING);
	$author = filter_var(trim($_POST['author']), FILTER_SANITIZE_STRING);
	$descr = filter_var(trim($_POST['descr']), FILTER_SANITIZE_STRING);
	$price = filter_var(trim($_POST['price']), FILTER_VALIDATE_FLOAT);
	$publisher = filter_var(trim($_POST['publisher']), FILTER_SANITIZE_STRING);

	// Validate price
	if ($price === false || $price <= 0) {
		echo "Invalid price!";
		exit;
	}

	// Handle image upload securely
	$image = null;
	if(isset($_FILES['image']) && $_FILES['image']['name'] != "") {
		$image = basename($_FILES['image']['name']);
		$uploadDirectory = $_SERVER['DOCUMENT_ROOT'] . "/bootstrap/img/";
		$uploadFile = $uploadDirectory . $image;

		// Validate image file type
		$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
		if (!in_array($_FILES['image']['type'], $allowedTypes)) {
			echo "Invalid image type!";
			exit;
		}

		// Move the uploaded file
		if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadFile)) {
			echo "Failed to upload image!";
			exit;
		}
	}

	require_once("./functions/database_functions.php");
	$conn = db_connect();

	// Check if publisher exists, if not, add it
	$publisherId = null;
	$findPubQuery = "SELECT publisherid FROM publisher WHERE publisher_name = ?";
	$stmt = mysqli_prepare($conn, $findPubQuery);
	mysqli_stmt_bind_param($stmt, "s", $publisher);
	mysqli_stmt_execute($stmt);
	$result = mysqli_stmt_get_result($stmt);

	if ($row = mysqli_fetch_assoc($result)) {
		$publisherId = $row['publisherid'];
	} else {
		$insertPubQuery = "INSERT INTO publisher (publisher_name) VALUES (?)";
		$stmt = mysqli_prepare($conn, $insertPubQuery);
		mysqli_stmt_bind_param($stmt, "s", $publisher);
		if (!mysqli_stmt_execute($stmt)) {
			echo "Can't add new publisher: " . mysqli_error($conn);
			exit;
		}
		$publisherId = mysqli_insert_id($conn);
	}

	// Update book details
	$query = "UPDATE books SET 
		book_title = ?, 
		book_author = ?, 
		book_descr = ?, 
		book_price = ?, 
		publisherid = ?";
	if ($image) {
		$query .= ", book_image = ?";
	}
	$query .= " WHERE book_isbn = ?";

	$stmt = mysqli_prepare($conn, $query);
	if ($image) {
		mysqli_stmt_bind_param($stmt, "sssdiss", $title, $author, $descr, $price, $publisherId, $image, $isbn);
	} else {
		mysqli_stmt_bind_param($stmt, "sssdis", $title, $author, $descr, $price, $publisherId, $isbn);
	}

	if (!mysqli_stmt_execute($stmt)) {
		echo "Can't update data: " . mysqli_error($conn);
		exit;
	} else {
		header("Location: admin_edit.php?bookisbn=$isbn");
	}

	// Close the database connection
	mysqli_close($conn);
?>
