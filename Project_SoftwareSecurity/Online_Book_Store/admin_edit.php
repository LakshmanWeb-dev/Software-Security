<?php
    session_start();
    require_once "./functions/admin.php";
    $title = "Edit book";
    require_once "./template/header.php";
    require_once "./functions/database_functions.php";
    $conn = db_connect();

    // Validate and sanitize GET parameter
    if (isset($_GET['bookisbn'])) {
        $book_isbn = filter_var($_GET['bookisbn'], FILTER_SANITIZE_STRING);
    } else {
        echo "Empty query!";
        exit;
    }

    if (empty($book_isbn)) {
        echo "Invalid ISBN! Check again!";
        exit;
    }

    // Get book data securely
    $query = "SELECT * FROM books WHERE book_isbn = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "s", $book_isbn);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result || mysqli_num_rows($result) == 0) {
        echo "Can't retrieve data. " . mysqli_error($conn);
        exit;
    }
    $row = mysqli_fetch_assoc($result);
?>
    <form method="post" action="edit_book.php" enctype="multipart/form-data">
        <table class="table">
            <tr>
                <th>ISBN</th>
                <td><input type="text" name="isbn" value="<?php echo htmlspecialchars($row['book_isbn']); ?>" readOnly="true"></td>
            </tr>
            <tr>
                <th>Title</th>
                <td><input type="text" name="title" value="<?php echo htmlspecialchars($row['book_title']); ?>" required></td>
            </tr>
            <tr>
                <th>Author</th>
                <td><input type="text" name="author" value="<?php echo htmlspecialchars($row['book_author']); ?>" required></td>
            </tr>
            <tr>
                <th>Image</th>
                <td><input type="file" name="image"></td>
            </tr>
            <tr>
                <th>Description</th>
                <td><textarea name="descr" cols="40" rows="5"><?php echo htmlspecialchars($row['book_descr']); ?></textarea></td>
            </tr>
            <tr>
                <th>Price</th>
                <td><input type="text" name="price" value="<?php echo htmlspecialchars($row['book_price']); ?>" required></td>
            </tr>
            <tr>
                <th>Publisher</th>
                <td><input type="text" name="publisher" value="<?php echo htmlspecialchars(getPubName($conn, $row['publisherid'])); ?>" required></td>
            </tr>
        </table>
        <input type="submit" name="save_change" value="Change" class="btn btn-primary">
        <input type="reset" value="cancel" class="btn btn-default">
    </form>
    <br/>
    <a href="admin_book.php" class="btn btn-success">Confirm</a>
<?php
    // Close the database connection
    if (isset($conn)) { mysqli_close($conn); }
    require "./template/footer.php";
?>
