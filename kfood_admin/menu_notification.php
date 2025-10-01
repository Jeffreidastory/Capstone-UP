// Add at the start of the menu-creation section
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    if ($action === 'update') {
        echo "<script>alert('Product updated successfully');</script>";
    } else if ($action === 'error') {
        echo "<script>alert('Error: Product could not be updated');</script>";
    }
}