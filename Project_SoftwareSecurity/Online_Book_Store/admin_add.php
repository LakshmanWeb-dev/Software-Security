<?php
	session_start();
	require_once "./functions/admin.php";
	$title = "Add new book";
	require "./template/header.php";
	require "./functions/database_functions.php";
	$conn = db_connect();

	if(isset($_POST['add'])) {
		// Step 1: Validate and sanitize inputs
		$isbn = filter_input(INPUT_POST, 'isbn', FILTER_SANITIZE_STRING);
		$title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
		$author = filter_input(INPUT_POST, 'author', FILTER_SANITIZE_STRING);
		$descr = filter_input(INPUT_POST, 'descr', FILTER_SANITIZE_STRING);
		$price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
		$publisher = filter_input(INPUT_POST, 'publisher', FILTER_SANITIZE_STRING);

		// Check if any required input is invalid
		if (!$isbn || !$title || !$author || !$price || !$publisher) {
			die("Invalid input provided. Please ensure all fields are correctly filled.");
		}

		// Step 2: Handle image upload securely
		$image = null;
		if (isset($_FILES['image']) && $_FILES['image']['name'] != "") {
			$image = basename($_FILES['image']['name']);
			$allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
			$fileExtension = strtolower(pathinfo($image, PATHINFO_EXTENSION));

			if (!in_array($fileExtension, $allowedExtensions)) {
				die("Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.");
			}

			$directory_self = str_replace(basename($_SERVER['PHP_SELF']), '', $_SERVER['PHP_SELF']);
			$uploadDirectory = $_SERVER['DOCUMENT_ROOT'] . $directory_self . "bootstrap/img/";
			$uploadFilePath = $uploadDirectory . $image;

			if (!move_uploaded_file($_FILES['image']['tmp_name'], $uploadFilePath)) {
				die("Failed to upload image.");
			}
		}

		// Step 3: Find or insert publisher using prepared statements
		$publisherid = null;
		$stmt = $conn->prepare("SELECT publisherid FROM publisher WHERE publisher_name = ?");
		$stmt->bind_param("s", $publisher);
		$stmt->execute();
		$result = $stmt->get_result();

		if ($result->num_rows > 0) {
			$row = $result->fetch_assoc();
			$publisherid = $row['publisherid'];
		} else {
			$stmt = $conn->prepare("INSERT INTO publisher (publisher_name) VALUES (?)");
			$stmt->bind_param("s", $publisher);
			if ($stmt->execute()) {
				$publisherid = $stmt->insert_id;
			} else {
				die("Failed to add new publisher: " . $conn->error);
			}
		}
		$stmt->close();

		// Step 4: Insert book record using prepared statements
		$stmt = $conn->prepare("INSERT INTO books (isbn, title, author, image, descr, price, publisherid) VALUES (?, ?, ?, ?, ?, ?, ?)");
		$stmt->bind_param("ssssddi", $isbn, $title, $author, $image, $descr, $price, $publisherid);

		if ($stmt->execute()) {
			header("Location: admin_book.php");
			exit;
		} else {
			die("Failed to add new book: " . $conn->error);
		}
		$stmt->close();
	}

	if(isset($conn)) {mysqli_close($conn);}
	require_once "./template/footer.php";
?>
	<form method="post" action="admin_add.php" enctype="multipart/form-data">
		<table class="table">
			<tr>
				<th>ISBN</th>
				<td><input type="text" name="isbn" required></td>
			</tr>
			<tr>
				<th>Title</th>
				<td><input type="text" name="title" required></td>
			</tr>
			<tr>
				<th>Author</th>
				<td><input type="text" name="author" required></td>
			</tr>
			<tr>
				<th>Image</th>
				<td><input type="file" name="image"></td>
			</tr>
			<tr>
				<th>Description</th>
				<td><textarea name="descr" cols="40" rows="5"></textarea></td>
			</tr>
			<tr>
				<th>Price</th>
				<td><input type="number" step="0.01" name="price" required></td>
			</tr>
			<tr>
				<th>Publisher</th>
				<td><input type="text" name="publisher" required></td>
			</tr>
		</table>
		<input type="submit" name="add" value="Add new book" class="btn btn-primary">
		<input type="reset" value="Cancel" class="btn btn-default">
	</form>
	<br/>
