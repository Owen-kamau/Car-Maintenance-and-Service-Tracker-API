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
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap');

body {
    font-family: 'Poppins', sans-serif;
    background-color: #fff7fa;
    margin: 0;
    padding: 0;
    color: #4a3c3c;
}

/* ===== HEADER ===== */
header {
    background-color: #f8c7d8;
    color: #3a2b2b;
    padding: 15px 30px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    border-bottom: 3px solid #f1a7b9;
}

header h1 {
    margin: 0;
    font-size: 22px;
    font-weight: 600;
}

header a.logout {
    background-color: #ff8fab;
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    transition: 0.3s ease;
}

header a.logout:hover {
    background-color: #ff7ca0;
}

/* ===== MAIN CONTAINER ===== */
.container {
    width: 90%;
    max-width: 1100px;
    background: white;
    margin: 40px auto;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    padding: 30px;
}

.container h2 {
    margin-top: 0;
    text-align: center;
    color: #e06b8c;
    font-weight: 600;
}

/* ===== ACTION BAR ===== */
.actions {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    flex-wrap: wrap;
}

.actions button {
    background-color: #ff8fab;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: 500;
}

.actions button:hover {
    background-color: #ff7ca0;
    transform: translateY(-1px);
}

#searchInput {
    width: 250px;
    padding: 10px;
    border: 1px solid #f4c2d7;
    border-radius: 25px;
    outline: none;
    transition: 0.3s;
}

#searchInput:focus {
    border-color: #ff8fab;
    box-shadow: 0 0 5px rgba(255, 143, 171, 0.4);
}

/* ===== TABLE STYLING ===== */
table {
    width: 100%;
    border-collapse: collapse;
    border-radius: 10px;
    overflow: hidden;
    margin-top: 10px;
}

th {
    background: #f8d7e3;
    color: #3b2d2d;
    padding: 12px;
    text-align: left;
    font-weight: 600;
}

td {
    border-bottom: 1px solid #f0e0e5;
    padding: 10px;
    color: #5c4a4a;
}

tr:hover {
    background-color: #fff3f6;
}

td.actions {
    display: flex;
    gap: 8px;
}

/* ===== ACTION BUTTONS ===== */
button.edit {
    background: #ffda9e;
    color: #3b2d2d;
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    transition: 0.3s;
}

button.delete {
    background: #f57b89;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 8px;
    transition: 0.3s;
}

button.edit:hover {
    background: #fcd277;
}

button.delete:hover {
    background: #f45b70;
}

.no-users {
    text-align: center;
    color: #999;
    padding: 20px 0;
}

/* ===== MODAL ===== */
.modal {
    display: none;
    position: fixed;
    z-index: 10;
    left: 0; top: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.4);
}

.modal-content {
    background: #fffafc;
    margin: 8% auto;
    padding: 25px;
    border-radius: 15px;
    width: 400px;
    position: relative;
    box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    border: 2px solid #ffd7e0;
}

.modal-content h3 {
    margin-top: 0;
    text-align: center;
    color: #e06b8c;
    font-weight: 600;
}

.modal-content input,
.modal-content select {
    width: 100%;
    padding: 10px;
    margin: 8px 0;
    border: 1px solid #f0bcd2;
    border-radius: 10px;
    outline: none;
    transition: 0.3s;
    font-family: 'Poppins', sans-serif;
}

.modal-content input:focus,
.modal-content select:focus {
    border-color: #ff8fab;
    box-shadow: 0 0 5px rgba(255, 143, 171, 0.4);
}

.modal-content button {
    background: #ff8fab;
    color: white;
    border: none;
    padding: 10px;
    border-radius: 25px;
    width: 100%;
    cursor: pointer;
    transition: 0.3s;
    font-weight: 500;
}

.modal-content button:hover {
    background: #ff7ca0;
}

.close {
    position: absolute;
    top: 10px;
    right: 15px;
    color: #888;
    font-size: 20px;
    cursor: pointer;
}

.close:hover {
    color: #000;
}

/* ===== RESPONSIVE ===== */
@media (max-width: 768px) {
    .actions {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }

    #searchInput {
        width: 100%;
    }

    .modal-content {
        width: 90%;
    }
}


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
