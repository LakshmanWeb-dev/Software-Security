<?php
	session_start();

	// Check for empty input fields
	$_SESSION['err'] = 1;
	foreach ($_POST as $key => $value) {
		if (trim($value) == '') {
			$_SESSION['err'] = 0;
			break; // Break the loop on the first empty value
		}
	}

	if ($_SESSION['err'] == 0) {
		header("Location: purchase.php");
		exit;
	} else {
		unset($_SESSION['err']);
	}

	require_once "./functions/database_functions.php";
	$title = "Purchase Process";
	require "./template/header.php";
	$conn = db_connect();

	// Sanitize and validate input data from the session and POST request
	$name = htmlspecialchars(trim($_SESSION['ship']['name'] ?? ''));
	$address = htmlspecialchars(trim($_SESSION['ship']['address'] ?? ''));
	$city = htmlspecialchars(trim($_SESSION['ship']['city'] ?? ''));
	$zip_code = htmlspecialchars(trim($_SESSION['ship']['zip_code'] ?? ''));
	$country = htmlspecialchars(trim($_SESSION['ship']['country'] ?? ''));

	$card_number = filter_input(INPUT_POST, 'card_number', FILTER_SANITIZE_STRING);
	$card_PID = filter_input(INPUT_POST, 'card_PID', FILTER_SANITIZE_STRING);
	$card_expire = strtotime(filter_input(INPUT_POST, 'card_expire', FILTER_SANITIZE_STRING));
	$card_owner = htmlspecialchars(trim($_POST['card_owner']));

	// Validate card information
	if (!preg_match('/^\d{16}$/', $card_number)) {
		die("Invalid card number. Please enter a valid 16-digit card number.");
	}
	if (!preg_match('/^\d{4}$/', $card_PID)) {
		die("Invalid card PID. Please enter a valid 4-digit PID.");
	}
	if ($card_expire === false || $card_expire < time()) {
		die("Invalid card expiry date. Please ensure it is in the future.");
	}
	if (empty($card_owner) || !preg_match('/^[a-zA-Z\s]+$/', $card_owner)) {
		die("Invalid card owner name. Please enter a valid name.");
	}

	// Find customer ID or create a new one
	$customerid = getCustomerId($name, $address, $city, $zip_code, $country);
	if ($customerid == null) {
		$customerid = setCustomerId($name, $address, $city, $zip_code, $country);
	}

	$date = date("Y-m-d H:i:s");
	insertIntoOrder($conn, $customerid, $_SESSION['total_price'], $date, $name, $address, $city, $zip_code, $country);

	// Get order ID for inserting order items
	$orderid = getOrderId($conn, $customerid);

	// Add items to the order
	foreach ($_SESSION['cart'] as $isbn => $qty) {
		$bookprice = getbookprice($isbn);

		// Use prepared statements for secure database insertion
		$query = "INSERT INTO order_items (orderid, book_isbn, book_price, quantity) VALUES (?, ?, ?, ?)";
		$stmt = $conn->prepare($query);
		$stmt->bind_param("isdi", $orderid, $isbn, $bookprice, $qty);
		if (!$stmt->execute()) {
			die("Insert value failed: " . $stmt->error);
		}
		$stmt->close();
	}

	// Clear session
	session_unset();
?>
	<p class="lead text-success">
		Your order has been processed successfully. Please check your email to get your order confirmation and shipping detail! 
		Your cart has been emptied.
	</p>

<?php
	// Close database connection
	if (isset($conn)) {
		mysqli_close($conn);
	}
	require_once "./template/footer.php";
?>
