<?php
session_start();
include("DBConn.php");

// Only admin can access
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Manage Users - Full CRUD</title>
<link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
<style>
/* Metallic / Matrix admin style */
body { font-family: 'Roboto', sans-serif; margin: 0; background:#1e1e1e; color:#f0f0f0; }
header { background:#2f2f2f; padding:15px 30px; display:flex; justify-content:space-between; align-items:center; box-shadow:0 2px 8px #000; }
header h1 { margin:0; color:#00ff7f; }
header a.logout { color:#fff; background:#ff4b5c; padding:8px 16px; border-radius:6px; text-decoration:none; font-weight:500; }
header a.logout:hover { box-shadow:0 0 10px #ff4b5c; transform:translateY(-2px); }
.container { padding:20px 30px; }
input[type="text"] { padding:10px; width:100%; margin-bottom:15px; border:none; border-radius:5px; background:#2f2f2f; color:#f0f0f0; }
table { width:100%; border-collapse: collapse; background:#2f2f2f; border-radius:10px; overflow:hidden; box-shadow:0 0 10px #000; }
table th, table td { padding:12px 15px; text-align:left; }
table th { background:#111; color:#00ff7f; }
table tr:hover { background:#00ff7f33; transform:scale(1.02); cursor:pointer; transition:0.2s; }
.actions a { padding:6px 12px; border-radius:5px; text-decoration:none; font-weight:500; color:#f0f0f0; margin-right:5px; }
.actions a.edit { background:#3a9ad9; }
.actions a.delete { background:#ff4b5c; }
.actions a.add { background:#00ff7f; color:#000; }
.actions a:hover { box-shadow:0 0 10px #00ff7f; transform:translateY(-2px); }

/* Modal */
.modal { display: none; position: fixed; top:0; left:0; right:0; bottom:0; background: rgba(0,0,0,0.85); justify-content:center; align-items:center; z-index:1000; }
.modal-content { background:#2f2f2f; padding:20px; border-radius:10px; width:350px; box-shadow:0 0 20px #000; }
.modal-content label { display:block; margin-top:10px; color:#00ff7f; }
.modal-content input, .modal-content select { width:100%; padding:8px; margin-top:5px; border:none; border-radius:5px; background:#111; color:#f0f0f0; }
.modal-content button { margin-top:15px; padding:8px 15px; border:none; border-radius:5px; cursor:pointer; font-weight:500; }
#saveBtn { background:#00ff7f; color:#000; margin-right:10px; }
#cancelBtn { background:#ff4b5c; color:#fff; }
</style>
</head>
<body>

<header>
    <h1>Manage Users</h1>
    <a href="logout.php" class="logout">Logout</a>
</header>

<div class="container">
    <button class="actions add" onclick="openAdd()">➕ Add New User</button>
    <input type="text" id="searchInput" placeholder="Search users...">
    <table id="userTable">
        <thead>
            <tr>
                <th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Created At</th><th>Actions</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Modal for Add/Edit -->
<div id="userModal" class="modal">
    <div class="modal-content">
        <h3 id="modalTitle">Edit User</h3>
        <input type="hidden" id="userId">
        <label>Username</label>
        <input type="text" id="username">
        <label>Email</label>
        <input type="email" id="email">
        <label>Role</label>
        <select id="role">
            <option value="user">User</option>
            <option value="admin">Admin</option>
        </select>
        <div style="text-align:right;">
            <button id="saveBtn">Save</button>
            <button id="cancelBtn">Cancel</button>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Load users
function loadUsers() {
    fetch('api/manage_users.php')
    .then(res=>res.json())
    .then(data=>{
        const tbody=document.querySelector('#userTable tbody');
        tbody.innerHTML='';
        data.forEach(u=>{
            const tr=document.createElement('tr');
            tr.innerHTML=`
            <td>${u.id}</td>
            <td>${u.username}</td>
            <td>${u.email}</td>
            <td>${u.role}</td>
            <td>${u.created_at}</td>
            <td class="actions">
                <a href="#" class="edit" onclick="openEdit(${u.id},'${u.username}','${u.email}','${u.role}')">✏ Edit</a>
                <a href="#" class="delete" onclick="confirmDelete(${u.id})">🗑 Delete</a>
            </td>`;
            tbody.appendChild(tr);
        });
    });
}

// Delete user
function confirmDelete(id){
    Swal.fire({
        title:'Delete this user?',
        icon:'warning',
        showCancelButton:true,
        confirmButtonText:'Yes, delete!',
        confirmButtonColor:'#ff4b5c'
    }).then(result=>{
        if(result.isConfirmed){
            fetch(`api/manage_users.php?delete=${id}`).then(()=>loadUsers());
        }
    });
}

// Open modal for edit
function openEdit(id, username, email, role){
    document.getElementById('modalTitle').innerText='Edit User';
    document.getElementById('userId').value=id;
    document.getElementById('username').value=username;
    document.getElementById('email').value=email;
    document.getElementById('role').value=role.toLowerCase();
    document.getElementById('userModal').style.display='flex';
}

// Open modal for add
function openAdd(){
    document.getElementById('modalTitle').innerText='Add New User';
    document.getElementById('userId').value='';
    document.getElementById('username').value='';
    document.getElementById('email').value='';
    document.getElementById('role').value='user';
    document.getElementById('userModal').style.display='flex';
}

// Close modal
document.getElementById('cancelBtn').addEventListener('click',()=>{
    document.getElementById('userModal').style.display='none';
});

// Save Add/Edit
document.getElementById('saveBtn').addEventListener('click',()=>{
    const id=document.getElementById('userId').value;
    const username=document.getElementById('username').value;
    const email=document.getElementById('email').value;
    const role=document.getElementById('role').value;

    fetch('api/manage_users.php',{
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body:JSON.stringify({id, username, email, role})
    }).then(res=>res.json()).then(resp=>{
        if(resp.success){
            Swal.fire('Success','User saved','success');
            document.getElementById('userModal').style.display='none';
            loadUsers();
        }else{
            Swal.fire('Error','Operation failed','error');
        }
    });
});

// Live search
document.getElementById('searchInput').addEventListener('keyup', function(){
    const filter=this.value.toLowerCase();
    document.querySelectorAll('#userTable tbody tr').forEach(row=>{
        row.style.display=[...row.cells].some(cell=>cell.textContent.toLowerCase().includes(filter))?'':'none';
    });
});

loadUsers();
</script>
</body>
</html>
