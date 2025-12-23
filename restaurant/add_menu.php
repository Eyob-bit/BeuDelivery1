<h3>Add Menu Item</h3>

<form action="save_menu.php" method="POST" enctype="multipart/form-data">
    <input type="text" name="name" placeholder="Food name" required><br><br>
    <textarea name="description" placeholder="Description"></textarea><br><br>
    <input type="number" step="0.01" name="price" placeholder="Price" required><br><br>
    <input type="file" name="image"><br><br>
    <button type="submit">Save</button>
</form>
