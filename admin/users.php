<?php
/**
 * X Business Grant - Admin User Management
 * Manage admin users (add, edit, delete)
 */

session_start();

require_once __DIR__ . '/../includes/Database.php';

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Only super_admin can manage users
if ($_SESSION['admin_role'] !== 'super_admin') {
    header('Location: index.php?error=unauthorized');
    exit;
}

$db = \App\Database::getInstance();
$message = '';
$messageType = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'add_user') {
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'reviewer';
            
            // Validate
            if (empty($username) || empty($email) || empty($fullName) || empty($password)) {
                $message = 'All fields are required';
                $messageType = 'error';
            } elseif (strlen($password) < 6) {
                $message = 'Password must be at least 6 characters';
                $messageType = 'error';
            } else {
                // Check if username or email exists
                $exists = $db->fetchOne(
                    "SELECT id FROM admin_users WHERE username = ? OR email = ?",
                    [$username, $email]
                );
                
                if ($exists) {
                    $message = 'Username or email already exists';
                    $messageType = 'error';
                } else {
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                    $db->query(
                        "INSERT INTO admin_users (username, email, full_name, password_hash, role, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())",
                        [$username, $email, $fullName, $passwordHash, $role]
                    );
                    $message = 'User added successfully!';
                    $messageType = 'success';
                }
            }
        } elseif ($_POST['action'] === 'edit_user') {
            $id = (int)($_POST['user_id'] ?? 0);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $fullName = trim($_POST['full_name'] ?? '');
            $role = $_POST['role'] ?? 'reviewer';
            $password = $_POST['password'] ?? '';
            $isActive = isset($_POST['is_active']) ? 1 : 0;
            
            if ($id && !empty($username) && !empty($email) && !empty($fullName)) {
                // Check if username/email exists for other users
                $exists = $db->fetchOne(
                    "SELECT id FROM admin_users WHERE (username = ? OR email = ?) AND id != ?",
                    [$username, $email, $id]
                );
                
                if ($exists) {
                    $message = 'Username or email already exists for another user';
                    $messageType = 'error';
                } else {
                    if (!empty($password)) {
                        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
                        $db->query(
                            "UPDATE admin_users SET username = ?, email = ?, full_name = ?, password_hash = ?, role = ?, is_active = ?, updated_at = NOW() WHERE id = ?",
                            [$username, $email, $fullName, $passwordHash, $role, $isActive, $id]
                        );
                    } else {
                        $db->query(
                            "UPDATE admin_users SET username = ?, email = ?, full_name = ?, role = ?, is_active = ?, updated_at = NOW() WHERE id = ?",
                            [$username, $email, $fullName, $role, $isActive, $id]
                        );
                    }
                    $message = 'User updated successfully!';
                    $messageType = 'success';
                }
            }
        } elseif ($_POST['action'] === 'delete_user') {
            $id = (int)($_POST['user_id'] ?? 0);
            
            if ($id && $id !== $_SESSION['admin_id']) {
                $db->query("DELETE FROM admin_users WHERE id = ?", [$id]);
                $message = 'User deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Cannot delete your own account';
                $messageType = 'error';
            }
        }
    }
}

// Get all admin users
$adminUsers = $db->fetchAll("SELECT * FROM admin_users ORDER BY created_at DESC");

$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Users - X Business Grant Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#3b82f6',
                        accent: '#f59e0b'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="fixed left-0 top-0 h-screen w-64 bg-gray-900 text-white">
            <div class="p-6">
                <div class="flex items-center space-x-2 mb-8">
                    <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center">
                        <span class="text-white font-bold text-xl">X</span>
                    </div>
                    <span class="text-xl font-bold">Business Grant</span>
                </div>
                <nav class="space-y-2">
                    <a href="index.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                    </a>
                    <a href="applications.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-file-alt"></i>
                        <span>Applications</span>
                    </a>
                    <a href="reports.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                    </a>
                    <a href="settings.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="users.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg bg-primary transition">
                        <i class="fas fa-user-shield"></i>
                        <span>Admin Users</span>
                    </a>
                    <div class="border-t border-gray-700 my-4"></div>
                    <a href="logout.php" class="flex items-center space-x-3 px-4 py-3 rounded-lg hover:bg-gray-800 transition text-red-400">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </nav>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Admin Users</h1>
                    <p class="text-gray-600">Manage administrator accounts</p>
                </div>
                <button onclick="openAddModal()" class="px-4 py-2 bg-primary text-white rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-plus mr-2"></i>Add User
                </button>
            </div>

            <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 border-green-200 text-green-800' : 'bg-red-50 border-red-200 text-red-800'; ?> border">
                <p><i class="fas fa-<?php echo $messageType === 'success' ? 'check-circle' : 'exclamation-circle'; ?> mr-2"></i><?php echo htmlspecialchars($message); ?></p>
            </div>
            <?php endif; ?>

            <!-- Users Table -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">User</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Username</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Role</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Status</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Last Login</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Created</th>
                                <th class="text-left py-3 px-4 font-semibold text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($adminUsers as $user): ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-primary rounded-full flex items-center justify-center mr-3">
                                            <span class="text-white font-bold"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                                        </div>
                                        <div>
                                            <p class="font-medium"><?php echo htmlspecialchars($user['full_name']); ?></p>
                                            <p class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <span class="font-mono text-sm"><?php echo htmlspecialchars($user['username']); ?></span>
                                </td>
                                <td class="py-3 px-4">
                                    <?php
                                        $roleColors = [
                                            'super_admin' => 'bg-purple-100 text-purple-800',
                                            'admin' => 'bg-blue-100 text-blue-800',
                                            'reviewer' => 'bg-gray-100 text-gray-800'
                                        ];
                                        $roleColor = $roleColors[$user['role']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 py-1 rounded text-xs font-medium <?php echo $roleColor; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $user['role'])); ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($user['is_active']): ?>
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-green-100 text-green-800">Active</span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 rounded text-xs font-medium bg-red-100 text-red-800">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-500">
                                    <?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex space-x-2">
                                        <button onclick="editUser(<?php echo htmlspecialchars(json_encode($user)); ?>)"
                                            class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($user['id'] !== $_SESSION['admin_id']): ?>
                                        <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')"
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Add User Modal -->
    <div id="addModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Add New Admin User</h3>
                <button onclick="closeAddModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_user">
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Full Name *</label>
                    <input type="text" name="full_name" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="John Doe">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Username *</label>
                    <input type="text" name="username" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="johndoe">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Email *</label>
                    <input type="email" name="email" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="john@example.com">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Password *</label>
                    <input type="password" name="password" required minlength="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="Min. 6 characters">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Role</label>
                    <select name="role"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="reviewer">Reviewer</option>
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeAddModal()"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700">
                        Add User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-gray-800">Edit Admin User</h3>
                <button onclick="closeEditModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Full Name *</label>
                    <input type="text" name="full_name" id="edit_full_name" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Username *</label>
                    <input type="text" name="username" id="edit_username" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Email *</label>
                    <input type="email" name="email" id="edit_email" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">New Password</label>
                    <input type="password" name="password" minlength="6"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                        placeholder="Leave blank to keep current">
                    <p class="text-xs text-gray-500 mt-1">Only fill if you want to change the password</p>
                </div>
                
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Role</label>
                    <select name="role" id="edit_role"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                        <option value="reviewer">Reviewer</option>
                        <option value="admin">Admin</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                
                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="edit_is_active" value="1" class="w-4 h-4 text-primary border-gray-300 rounded">
                    <label for="edit_is_active" class="ml-2 text-gray-700">Active</label>
                </div>
                
                <div class="flex justify-end space-x-3 pt-4">
                    <button type="button" onclick="closeEditModal()"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:bg-blue-700">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-xl p-6 w-full max-w-md mx-4">
            <div class="text-center">
                <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-trash text-red-600 text-2xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-800 mb-2">Delete User?</h3>
                <p class="text-gray-500 mb-6">Are you sure you want to delete <span id="delete_user_name" class="font-medium"></span>? This action cannot be undone.</p>
                
                <form method="POST" class="flex justify-center space-x-3">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="delete_user_id">
                    
                    <button type="button" onclick="closeDeleteModal()"
                        class="px-6 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-50">
                        Cancel
                    </button>
                    <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openAddModal() {
            document.getElementById('addModal').classList.remove('hidden');
            document.getElementById('addModal').classList.add('flex');
        }
        
        function closeAddModal() {
            document.getElementById('addModal').classList.add('hidden');
            document.getElementById('addModal').classList.remove('flex');
        }
        
        function editUser(user) {
            document.getElementById('edit_user_id').value = user.id;
            document.getElementById('edit_full_name').value = user.full_name;
            document.getElementById('edit_username').value = user.username;
            document.getElementById('edit_email').value = user.email;
            document.getElementById('edit_role').value = user.role;
            document.getElementById('edit_is_active').checked = user.is_active == 1;
            
            document.getElementById('editModal').classList.remove('hidden');
            document.getElementById('editModal').classList.add('flex');
        }
        
        function closeEditModal() {
            document.getElementById('editModal').classList.add('hidden');
            document.getElementById('editModal').classList.remove('flex');
        }
        
        function confirmDelete(userId, userName) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_user_name').textContent = userName;
            
            document.getElementById('deleteModal').classList.remove('hidden');
            document.getElementById('deleteModal').classList.add('flex');
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
            document.getElementById('deleteModal').classList.remove('flex');
        }
        
        // Close modals on outside click
        ['addModal', 'editModal', 'deleteModal'].forEach(id => {
            document.getElementById(id).addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.add('hidden');
                    this.classList.remove('flex');
                }
            });
        });
    </script>
</body>
</html>
