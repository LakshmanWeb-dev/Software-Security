<?php
require_once "./functions/database_functions.php";
require "./template/header.php";
$conn = db_connect();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Book Page</title>
    <!-- Link to Bootstrap CSS -->
    <link href="./bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="./bootstrap/css/bootstrap-theme.min.css" rel="stylesheet">
    <link href="./bootstrap/css/jumbotron.css" rel="stylesheet">
<body>

<?php
// Validate GET parameters
$page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT, [
    "options" => ["default" => 1, "min_range" => 1]
]);

$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_STRING);

// Ensure $search is not null
if ($search === null) {
    $search = '';
}

// Use prepared statements to fetch data
$query = "SELECT * FROM books WHERE book_title LIKE ? LIMIT ?, 10";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("Prepare statement failed: " . $conn->error);
}

$search_param = '%' . $search . '%';
$offset = ($page - 1) * 10;

$stmt->bind_param("si", $search_param, $offset);

if (!$stmt->execute()) {
    die("Execution failed: " . $stmt->error);
}

$result = $stmt->get_result();

if (!$result) {
    die("Getting result failed: " . $stmt->error);
}
?>

<p class="lead"><a href="admin_add.php">Add new book</a></p>
<a href="admin_signout.php" class="btn btn-primary">Sign out!</a>
<table class="table" style="margin-top: 20px">
    <tr>
        <th>ISBN</th>
        <th>Title</th>
        <th>Author</th>
        <th>Image</th>
        <th>Description</th>
        <th>Price</th>
        <th>Publisher</th>
        <th>&nbsp;</th>
        <th>&nbsp;</th>
    </tr>
    <?php while ($row = $result->fetch_assoc()) { ?>
        <tr>
            <td><?php echo htmlspecialchars($row['book_isbn']); ?></td>
            <td><?php echo htmlspecialchars($row['book_title']); ?></td>
            <td><?php echo htmlspecialchars($row['book_author']); ?></td>
            <td><?php echo htmlspecialchars($row['book_image']); ?></td>
            <td><?php echo htmlspecialchars($row['book_descr']); ?></td>
            <td><?php echo htmlspecialchars($row['book_price']); ?></td>
            <td><?php echo htmlspecialchars(getPubName($conn, $row['publisherid'])); ?></td>
            <td><a href="admin_edit.php?bookisbn=<?php echo htmlspecialchars($row['book_isbn']); ?>">Edit</a></td>
            <td><a href="admin_delete.php?bookisbn=<?php echo htmlspecialchars($row['book_isbn']); ?>">Delete</a></td>
        </tr>
    <?php } ?>
</table>

<?php
$stmt->close();
if (isset($conn)) {
    mysqli_close($conn); // Close the connection only after all operations
}
require_once "./template/footer.php";
?>

</body>
</html>
