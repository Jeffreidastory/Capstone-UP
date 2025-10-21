<?php
// Backup of Menu Creation Section from admin_pg.php - Created on October 18, 2025

// Menu Creation Functionality
if(isset($_POST['add_product'])){
   $p_name = $_POST['p_name'];
   $p_category = $_POST['p_category'];
   $p_price = $_POST['p_price'];
   $p_stock = $_POST['p_stock'];
   $p_uom = $_POST['p_umo'];
   $p_expiry = $_POST['p_expiry'];
   $p_image = $_FILES['p_image']['name'];
   $p_image_tmp_name = $_FILES['p_image']['tmp_name'];
   $p_image_folder = '../uploaded_img/'.$p_image;

   $insert_query = mysqli_query($conn, "INSERT INTO `products`(name, category, price, stock, uom, expiry_date, image) VALUES('$p_name', '$p_category', '$p_price', '$p_stock', '$p_uom', '$p_expiry', '$p_image')") or die('query failed');

   if($insert_query){
      move_uploaded_file($p_image_tmp_name, $p_image_folder);
      $message[] = 'product added successfully';
   }else{
      $message[] = 'could not add the product';
   }
}

if(isset($_GET['delete'])){
   $delete_id = $_GET['delete'];
   $delete_query = mysqli_query($conn, "DELETE FROM `products` WHERE id = $delete_id ") or die('query failed');
   if($delete_query){
      $message[] = 'product has been deleted';
   }else{
      $message[] = 'product could not be deleted';
   }
}

if(isset($_POST['update_product'])){
   $update_p_id = $_POST['update_p_id'];
   $update_p_name = $_POST['update_p_name'];
   $update_p_price = $_POST['update_p_price'];
   
   // Check if a new image was uploaded
   if(!empty($_FILES['update_p_image']['name'])){
      $update_p_image = $_FILES['update_p_image']['name'];
      $update_p_image_tmp_name = $_FILES['update_p_image']['tmp_name'];
      $update_p_image_folder = '../uploaded_img/'.$update_p_image;
      
      // Include image in update
      $update_query = mysqli_query($conn, "UPDATE `products` SET name = '$update_p_name', category = '$update_p_category', price = '$update_p_price', image = '$update_p_image' WHERE id = '$update_p_id'");
      
      if($update_query){
         move_uploaded_file($update_p_image_tmp_name, $update_p_image_folder);
         header('Location: admin_pg.php?section=menu-creation&action=update');
         exit();
      }else{
         echo "<script>alert('Error: Product could not be updated');</script>";
      }
   } else {
      // Update without changing the image
      $update_query = mysqli_query($conn, "UPDATE `products` SET name = '$update_p_name', price = '$update_p_price' WHERE id = '$update_p_id'");
      
      if($update_query){
         header('Location: admin_pg.php?section=menu-creation&action=update');
         exit();
      }else{
         header('Location: admin_pg.php?section=menu-creation&action=error');
         exit();
      }
   }
}

// CSS Styles and HTML Structure for Menu Creation
?>

<!-- Menu Creation HTML Section -->
<section class="menu-creation-container">
    <div class="add-product-form">
        <div class="form-header">
            <h2>Add New Product</h2>
            <p class="form-subtitle">Fill in the product details below to add a new item to your menu</p>
        </div>
        
        <form action="" method="post" enctype="multipart/form-data">
            <div class="form-section">
                <h3>Basic Information</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-tag"></i> Product Name</label>
                        <input type="text" name="p_name" placeholder="Enter product name" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-list"></i> Category</label>
                        <select name="p_category" required>
                            <option value="" disabled selected>Select category</option>
                            <!-- Add your categories here -->
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-money-bill"></i> Price (₱)</label>
                        <input type="number" name="p_price" step="0.01" placeholder="Enter price" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-boxes"></i> Stock Quantity</label>
                        <input type="number" name="p_stock" placeholder="Enter stock quantity" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-balance-scale"></i> Unit of Measurement</label>
                        <select name="p_umo" required>
                            <option value="" disabled selected>Select UOM</option>
                            <!-- Add your UOM options here -->
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="fas fa-calendar"></i> Expiration Date</label>
                        <input type="date" name="p_expiry" required>
                    </div>
                </div>
            </div>

            <div class="form-section">
                <h3>Product Image</h3>
                <div class="form-row">
                    <div class="form-group full-width">
                        <div class="file-input-container">
                            <input type="file" name="p_image" accept="image/png, image/jpg, image/jpeg" required>
                            <div class="file-input-ui">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <span>Click to upload product image</span>
                                <small>Supported formats: PNG, JPG, JPEG</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" name="add_product" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add Product
                </button>
            </div>
        </form>
    </div>
</section>

<!-- Add these styles to your CSS -->
<style>
    /* Menu Creation Styles */
    .menu-creation-container {
        width: 100%;
        margin: 0 auto;
        padding: 1.5rem;
        background: rgba(255, 255, 255, 0.02);
    }

    .add-product-form {
        width: 100%;
        background: #ffffff;
        border-radius: 16px;
        padding: 2.5rem;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
        border: 1px solid rgba(238, 240, 245, 0.5);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    [data-theme="dark"] .add-product-form {
        background: #2a2d3a;
        border-color: rgba(255, 255, 255, 0.1);
    }

    .form-header {
        margin-bottom: 2rem;
        padding-bottom: 1rem;
        border-bottom: 1px solid #eef0f5;
    }

    .form-header h2 {
        font-size: 1.5rem;
        color: #1a1a1a;
        margin-bottom: 0.5rem;
        font-weight: 600;
    }

    .form-subtitle {
        color: #666;
        font-size: 0.9rem;
    }

    .form-section {
        margin-bottom: 2.5rem;
        padding: 2rem;
        background: rgba(255, 127, 80, 0.02);
        border-radius: 16px;
        border: 1px solid rgba(255, 127, 80, 0.1);
    }

    .form-row {
        display: flex;
        gap: 1.5rem;
        margin-bottom: 1.75rem;
    }

    .form-group {
        flex: 1;
    }

    .form-group label {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        color: #1a1a1a;
        font-weight: 600;
    }

    .form-group input,
    .form-group select {
        width: 100%;
        padding: 1rem;
        border: 2px solid #e0e0e0;
        border-radius: 8px;
        font-size: 0.95rem;
    }

    .file-input-container {
        border: 2px dashed #FF7F50;
        padding: 2rem;
        border-radius: 12px;
        text-align: center;
    }

    .file-input-ui {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 1rem;
    }

    .form-actions {
        margin-top: 2rem;
        display: flex;
        justify-content: flex-end;
    }

    .btn-primary {
        background: #FF7F50;
        color: white;
        padding: 1rem 2rem;
        border: none;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    /* Dark mode styles */
    [data-theme="dark"] .form-header h2 {
        color: #ffffff;
    }

    [data-theme="dark"] .form-subtitle {
        color: rgba(255, 255, 255, 0.6);
    }

    [data-theme="dark"] .form-group label {
        color: #ffffff;
    }

    [data-theme="dark"] .form-group input,
    [data-theme="dark"] .form-group select {
        background: #32364a;
        border-color: rgba(255, 255, 255, 0.1);
        color: #ffffff;
    }
</style>