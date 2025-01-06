<?php
	// Start session
	session_start();

	// Retrieve and sanitize inputs
	$email = filter_input(INPUT_POST, 'inputEmail', FILTER_SANITIZE_EMAIL);
	$pswd = filter_input(INPUT_POST, 'inputPasswd', FILTER_SANITIZE_STRING);

	// Validate email format
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		die("Invalid email format. Please enter a valid email.");
	}

	// Validate password (minimum 8 characters for demonstration purposes)
	if (empty($pswd) || strlen($pswd) < 8) {
		die("Invalid password. Ensure it has at least 8 characters.");
	}

	// Connect to the database
	$conn = mysqli_connect("localhost", "root", "", "www_project");
	if (!$conn) {
		die("Cannot connect to the database: " . mysqli_connect_error());
	}

	// Use prepared statement to fetch user data
	$query = "SELECT username, password FROM admin WHERE username = ?";
	$stmt = $conn->prepare($query);
	$stmt->bind_param("s", $email);
	$stmt->execute();
	$result = $stmt->get_result();

	// Check if a user exists
	if ($result->num_rows > 0) {
		$row = $result->fetch_assoc();
		// Verify hashed password
		if (password_verify($pswd, $row['password'])) {
			echo "Welcome admin! Long time no see.";
			// Optionally set session variables for logged-in user
			$_SESSION['admin'] = $row['username'];
		} else {
			die("Invalid credentials. Please try again.");
		}
	} else {
		die("Invalid credentials. Please try again.");
	}

	// Close connections
	$stmt->close();
	$conn->close();
?>
