<?php
session_start();
require_once '../../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: /carshowroom/login.php');
    exit();
}

// Set page title
$page_title = 'Category Management | Admin Panel';

// Include header
require_once '../includes/header.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'add':
            $name = trim($_POST['name']);
            $slug = strtolower(str_replace(' ', '-', $name)) . '-' . substr(md5(uniqid()), 0, 8);
            
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, status, created_at) VALUES (?, ?, 'active', NOW())");
            if ($stmt->execute([$name, $slug])) {
                echo json_encode(['success' => true, 'message' => 'Category added successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add category']);
            }
            exit();
            
        case 'edit':
            $id = $_POST['id'];
            $name = trim($_POST['name']);
            $slug = strtolower(str_replace(' ', '-', $name)) . '-' . substr(md5(uniqid()), 0, 8);
            
            $stmt = $conn->prepare("UPDATE categories SET name = ?, slug = ? WHERE id = ?");
            if ($stmt->execute([$name, $slug, $id])) {
                echo json_encode(['success' => true, 'message' => 'Category updated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update category']);
            }
            exit();
            
        case 'delete':
            $id = $_POST['id'];
            $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
            if ($stmt->execute([$id])) {
                echo json_encode(['success' => true, 'message' => 'Category deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete category']);
            }
            exit();
    }
}
?>

<!-- Content Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Category Management</h1>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
        <i class="fas fa-plus"></i> Add New Category
    </button>
</div>

<!-- Categories Table -->
<div class="card shadow mb-4">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered datatable" width="100%" cellspacing="0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Fetch categories from database
                    $stmt = $conn->query("SELECT * FROM categories ORDER BY created_at DESC");
                    while ($category = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($category['id']) . "</td>";
                        echo "<td>" . htmlspecialchars($category['name']) . "</td>";
                        echo "<td>" . htmlspecialchars($category['slug']) . "</td>";
                        echo "<td><span class='badge bg-" . ($category['status'] == 'active' ? 'success' : 'danger') . "'>" . ucfirst($category['status']) . "</span></td>";
                        echo "<td>" . date('M d, Y', strtotime($category['created_at'])) . "</td>";
                        echo "<td>";
                        echo "<button class='btn btn-sm btn-primary edit-category' data-id='" . $category['id'] . "' data-name='" . htmlspecialchars($category['name'], ENT_QUOTES) . "'><i class='fas fa-edit'></i></button> ";
                        echo "<button class='btn btn-sm btn-danger delete-category' data-id='" . $category['id'] . "'><i class='fas fa-trash'></i></button>";
                        echo "</td>";
                        echo "</tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCategoryModalLabel">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCategoryForm">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="categoryName" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCategoryModalLabel">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editCategoryForm">
                <input type="hidden" id="editCategoryId" name="id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="editCategoryName" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="editCategoryName" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this category? This action cannot be undone.
                <input type="hidden" id="deleteCategoryId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
$(document).ready(function() {
    // Initialize DataTable
    $('.datatable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]]
    });

    // Handle add category form submission
    $('#addCategoryForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'new_categories.php',
            type: 'POST',
            data: formData + '&action=add',
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            },
            error: function() {
                alert('An error occurred while adding the category.');
            }
        });
    });

    // Handle edit button click
    $(document).on('click', '.edit-category', function() {
        const id = $(this).data('id');
        const name = $(this).data('name');
        
        $('#editCategoryId').val(id);
        $('#editCategoryName').val(name);
        $('#editCategoryModal').modal('show');
    });

    // Handle edit category form submission
    $('#editCategoryForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'new_categories.php',
            type: 'POST',
            data: formData + '&action=edit',
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            },
            error: function() {
                alert('An error occurred while updating the category.');
            }
        });
    });

    // Handle delete button click
    $(document).on('click', '.delete-category', function() {
        const id = $(this).data('id');
        $('#deleteCategoryId').val(id);
        $('#deleteCategoryModal').modal('show');
    });

    // Handle delete confirmation
    $('#confirmDelete').on('click', function() {
        const id = $('#deleteCategoryId').val();
        
        $.ajax({
            url: 'new_categories.php',
            type: 'POST',
            data: { id: id, action: 'delete' },
            success: function(response) {
                const result = JSON.parse(response);
                if (result.success) {
                    location.reload();
                } else {
                    alert('Error: ' + result.message);
                }
            },
            error: function() {
                alert('An error occurred while deleting the category.');
            },
            complete: function() {
                $('#deleteCategoryModal').modal('hide');
            }
        });
    });
});
</script>

<?php
// Include footer
require_once '../includes/footer.php';
?>
