<?php
session_start();
include("DBConn.php");

// ✅ Allow only admin access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        body {
            font-family: "Segoe UI", Arial, sans-serif;
            background-color: #f3f4f6;
            margin: 0;
            padding: 0;
        }
        header {
            background-color: #0d6efd;
            color: white;
            padding: 15px 30px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        header h1 {
            margin: 0;
            font-size: 22px;
        }
        header a.logout {
            color: white;
            text-decoration: none;
            font-weight: bold;
        }
        .container {
            width: 90%;
            max-width: 1100px;
            background: white;
            margin: 30px auto;
            border-radius: 10px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
            padding: 25px;
        }
        .container h2 {
            margin-top: 0;
            text-align: center;
            color: #333;
        }
        .actions {
            margin-bottom: 15px;
            text-align: right;
        }
        .actions button {
            background-color: #0d6efd;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            cursor: pointer;
        }
        .actions button:hover {
            background-color: #0b5ed7;
        }
        #searchInput {
            width: 250px;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #0d6efd;
            color: white;
            padding: 10px;
            text-align: left;
        }
        td {
            border-bottom: 1px solid #ddd;
            padding: 10px;
        }
        td.actions button {
            margin-right: 5px;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        button.edit {
            background: #ffc107;
            color: #333;
        }
        button.delete {
            background: #dc3545;
            color: white;
        }
        button.edit:hover { background: #e0a800; }
        button.delete:hover { background: #c82333; }
        .no-users {
            text-align: center;
            color: #888;
            padding: 20px 0;
        }

        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 10;
            left: 0; top: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
        }
        .modal-content {
            background: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 10px;
            width: 400px;
            position: relative;
        }
        .modal-content h3 {
            margin-top: 0;
            text-align: center;
        }
        .modal-content input, 
        .modal-content select {
            width: 100%;
            padding: 8px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .modal-content button {
            background: #0d6efd;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            width: 100%;
            cursor: pointer;
        }
        .modal-content button:hover {
            background: #0b5ed7;
        }
        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            color: #666;
            font-size: 18px;
            cursor: pointer;
        }
        .close:hover { color: #000; }
    </style>
</head>
<body>

<header>
    <h1>Admin Dashboard - Manage Users</h1>
    <a href="logout.php" class="logout">Logout</a>
</header>

<div class="container">
    <div class="actions">
        <button onclick="openAddModal()">➕ Add User</button>
        <input type="text" id="searchInput" placeholder="Search user...">
    </div>

    <table id="userTable">
        <thead>
            <tr>
                <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created At</th><th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
    <p class="no-users" style="display:none;">No users found.</p>
</div>

<!-- Modal -->
 <div id="userModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 id="modalTitle">Add New User</h3>
        <form id="userForm">
            <input type="hidden" id="user_id">

            <label>Username</label>
            <input type="text" id="username" placeholder="Username" required>

            <label>Email</label>
            <input type="email" id="email" placeholder="Email" required>

            <label>Password <small id="passwordHelp">(required for new users)</small></label>
            <input type="password" id="password" placeholder="Enter password">

            <label>Role</label>
            <select id="role" required>
                <option value="owner">Owner</option>
                <option value="admin">Admin</option>
                <option value="mechanic">User</option>
            </select>

            <button type="submit">Save</button>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const modal = document.getElementById('userModal');
const form = document.getElementById('userForm');
const tableBody = document.querySelector('#userTable tbody');
const noUsersMsg = document.querySelector('.no-users');
const searchInput = document.getElementById('searchInput');
let editingUser = null;

function openAddModal() {
    document.getElementById('modalTitle').textContent = 'Add New User';
    form.reset();
    document.getElementById('user_id').value = '';
    modal.style.display = 'block';
}
function closeModal() { modal.style.display = 'none'; }

window.onclick = function(e) {
    if (e.target == modal) closeModal();
};

function loadUsers(search = '') {
    fetch(`api/manage_users.php?search=${encodeURIComponent(search)}`)
        .then(res => res.json())
        .then(data => {
            tableBody.innerHTML = '';
            if (data.length > 0) {
                data.forEach(u => {
                    const row = `
                        <tr>
                            <td>${u.id}</td>
                            <td>${u.username}</td>
                            <td>${u.email}</td>
                            <td>${u.role}</td>
                            <td>${u.created_at}</td>
                            <td class="actions">
                                <button class="edit" onclick="editUser(${u.id}, '${u.username}', '${u.email}', '${u.role}')">Edit</button>
                                <button class="delete" onclick="deleteUser(${u.id})">Delete</button>
                            </td>
                        </tr>`;
                    tableBody.insertAdjacentHTML('beforeend', row);
                });
                noUsersMsg.style.display = 'none';
            } else {
                noUsersMsg.style.display = 'block';
            }
        });
}
searchInput.addEventListener('keyup', () => loadUsers(searchInput.value));
loadUsers();

// Handle Add/Edit
form.addEventListener('submit', (e) => {
    e.preventDefault();

    const id = document.getElementById('user_id').value;
    const username = document.getElementById('username').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const role = document.getElementById('role').value;

    // Only require password for adding a new user
    if (!id && password === '') {
        Swal.fire('Error', 'Password is required for new users', 'error');
        return;
    }

    const payload = { id, username, email, password, role };
    const method = id ? 'PUT' : 'POST';

    fetch('api/manage_users.php', {
        method: method,
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(resp => {
        if (resp.status === 'success') {
            Swal.fire('Success', resp.message, 'success');
            closeModal();
            loadUsers();
        } else {
            Swal.fire('Error', resp.message || 'Operation failed', 'error');
        }
    })
    .catch(() => Swal.fire('Error', 'Something went wrong', 'error'));
});

function editUser(id, username, email, role) {
    document.getElementById('modalTitle').textContent = 'Edit User';
    document.getElementById('user_id').value = id;
    document.getElementById('username').value = username;
    document.getElementById('email').value = email;
    document.getElementById('role').value = role;
    document.getElementById('password').value = '';
    modal.style.display = 'block';
}

function deleteUser(id) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This will permanently remove the user.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete'
    }).then((result) => {
        if (result.isConfirmed) {
            fetch('api/manage_users.php', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            })
            .then(res => res.json())
            .then(resp => {
                Swal.fire('Deleted', resp.message, 'success');
                loadUsers();
            });
        }
    });
}
</script>

</body>
</html>
