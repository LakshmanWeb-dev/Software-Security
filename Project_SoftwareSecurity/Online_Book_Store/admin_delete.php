<?php
session_start();
require_once "./functions/database_functions.php";
$conn = db_connect();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isbn = filter_input(INPUT_POST, 'isbn', FILTER_SANITIZE_STRING);

    // Validate ISBN exists in the database
    $query = "DELETE FROM books WHERE book_isbn = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $isbn);

    // Output messages with encoding
    if ($stmt->execute()) {
        echo htmlspecialchars("Book deleted successfully.");
    } else {
        echo htmlspecialchars("Error deleting book.");
    }

    $stmt->close();
}

mysqli_close($conn);
?>
