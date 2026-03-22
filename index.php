<?php

// Intentionally vulnerable redirect - DAST demonstration
if (isset($_GET['redirect'])) {
    header("Location: " . $_GET['redirect']);
    exit;
}

// Basic front controller for a small CRUD application.
$config = require __DIR__ . '/config.php';
require __DIR__ . '/Database.php';

$db = new Database($config);

$action = $_GET['action'] ?? 'list';
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$errors = [];
$flash = null;
$currentUser = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if (!$errors) {
            $db->createUser(['name' => $name, 'email' => $email]);
            $flash = 'User created successfully.';
            $action = 'list';
        }
    } elseif ($action === 'edit' && $id !== null) {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name === '') {
            $errors[] = 'Name is required.';
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'A valid email address is required.';
        }

        if (!$errors) {
            $updated = $db->updateUser($id, ['name' => $name, 'email' => $email]);
            if ($updated === null) {
                $errors[] = 'User not found.';
                $action = 'list';
            } else {
                $flash = 'User updated successfully.';
                $action = 'list';
            }
        }
    } elseif ($action === 'delete' && $id !== null) {
        $db->deleteUser($id);
        $flash = 'User deleted successfully.';
        $action = 'list';
    }
}

if ($action === 'edit' && $id !== null) {
    $currentUser = $db->getUserById($id);
    if ($currentUser === null) {
        $errors[] = 'User not found.';
        $action = 'list';
    }
}

$users = $db->getUsers();

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Directory</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            background: #0f172a;
            color: #e5e7eb;
            margin: 0;
            padding: 2rem;
            display: flex;
            justify-content: center;
        }
        .app {
            max-width: 800px;
            width: 100%;
            background: #020617;
            border-radius: 0.75rem;
            padding: 2rem;
            box-shadow: 0 20px 45px rgba(15, 23, 42, 0.9);
            border: 1px solid #1f2937;
        }
        h1 {
            margin-top: 0;
            font-size: 1.7rem;
        }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.1rem 0.55rem;
            border-radius: 999px;
            font-size: 0.75rem;
            background: rgba(56, 189, 248, 0.15);
            color: #7dd3fc;
            border: 1px solid rgba(56, 189, 248, 0.3);
        }
        .badge span {
            width: 0.35rem;
            height: 0.35rem;
            border-radius: 999px;
            background: #22d3ee;
        }
        .section {
            margin-top: 1.75rem;
        }
        .section-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #9ca3af;
            margin-bottom: 0.75rem;
        }
        .card {
            background: radial-gradient(circle at top left, rgba(59, 130, 246, 0.18), transparent 55%),
                        radial-gradient(circle at bottom right, rgba(16, 185, 129, 0.22), transparent 55%),
                        #020617;
            border-radius: 0.75rem;
            border: 1px solid rgba(75, 85, 99, 0.7);
            padding: 1.1rem 1.2rem;
        }
        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 0.85rem;
        }
        .kv {
            display: grid;
            grid-template-columns: 130px 1fr;
            gap: 0.4rem 1rem;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        .kv-label {
            color: #9ca3af;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.5rem;
            font-size: 0.9rem;
        }
        th, td {
            padding: 0.5rem 0.65rem;
            text-align: left;
        }
        th {
            color: #9ca3af;
            font-weight: 500;
            border-bottom: 1px solid #4b5563;
        }
        tr:nth-child(even) td {
            background: rgba(15, 23, 42, 0.7);
        }
        tr:nth-child(odd) td {
            background: rgba(2, 6, 23, 0.8);
        }
        .hint {
            font-size: 0.8rem;
            color: #9ca3af;
            margin-top: 0.75rem;
        }
        .pill {
            display: inline-flex;
            align-items: center;
            padding: 0.15rem 0.55rem;
            border-radius: 999px;
            background: rgba(55, 65, 81, 0.7);
            font-size: 0.75rem;
            color: #e5e7eb;
        }
        .pill strong {
            color: #a5b4fc;
            margin-right: 0.25rem;
        }
    </style>
</head>
<body>
    
<div class="app">

<!-- Intentionally vulnerable link for DAST demonstration -->
<a href="?redirect=http://example.com">Click here</a>

    <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem;">
        <div>
            <h1>User Directory</h1>
            <p style="margin: 0; color: #9ca3af; font-size: 0.95rem;">
                Manage users for this application.
            </p>
        </div>
        <div>
            <a href="?action=list" style="color: #e5e7eb; text-decoration: none; font-size: 0.85rem;">All users</a>
            <span style="color: #4b5563; margin: 0 0.4rem;">·</span>
            <a href="?action=create" style="color: #a5b4fc; text-decoration: none; font-size: 0.85rem;">New user</a>
        </div>
    </div>

    <?php if ($flash): ?>
        <div class="section">
            <div class="card" style="border-color: #16a34a; background: rgba(22, 163, 74, 0.15);">
                <p style="margin: 0; font-size: 0.9rem;"><?php echo htmlspecialchars($flash, ENT_QUOTES, 'UTF-8'); ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($errors): ?>
        <div class="section">
            <div class="card" style="border-color: #dc2626; background: rgba(220, 38, 38, 0.15);">
                <p style="margin: 0 0 0.5rem 0; font-size: 0.9rem;">Please fix the following:</p>
                <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.9rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <?php if ($action === 'create' || $action === 'edit'): ?>
        <div class="section">
            <div class="section-title"><?php echo $action === 'create' ? 'Create user' : 'Edit user'; ?></div>
            <div class="card">
            <form method="post" action="?action=<?php echo htmlentities($action); ?><?php echo htmlentities($action === 'edit' && $id !== null ? '&id=' . (int) $id : ''); ?>">
                    <div style="display: grid; gap: 0.75rem; max-width: 420px;">
                        <label style="font-size: 0.9rem;">
                            <div style="margin-bottom: 0.25rem;">Name</div>
                            <input
                                type="text"
                                name="name"
                                value="<?php echo htmlspecialchars($_POST['name'] ?? ($currentUser['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                style="width: 100%; padding: 0.4rem 0.55rem; border-radius: 0.4rem; border: 1px solid #4b5563; background: #020617; color: #e5e7eb;"
                            >
                        </label>
                        <label style="font-size: 0.9rem;">
                            <div style="margin-bottom: 0.25rem;">Email</div>
                            <input
                                type="email"
                                name="email"
                                value="<?php echo htmlspecialchars($_POST['email'] ?? ($currentUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                style="width: 100%; padding: 0.4rem 0.55rem; border-radius: 0.4rem; border: 1px solid #4b5563; background: #020617; color: #e5e7eb;"
                            >
                        </label>
                        <div style="display: flex; gap: 0.5rem; margin-top: 0.5rem;">
                            <button type="submit" style="padding: 0.4rem 0.9rem; border-radius: 0.4rem; border: none; background: #4f46e5; color: #e5e7eb; font-size: 0.9rem; cursor: pointer;">
                                <?php echo $action === 'create' ? 'Create user' : 'Save changes'; ?>
                            </button>
                            <a href="?action=list" style="padding: 0.4rem 0.9rem; border-radius: 0.4rem; border: 1px solid #4b5563; color: #e5e7eb; font-size: 0.9rem; text-decoration: none;">
                                Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php else: ?>
        <div class="section">
            <div class="section-title">Users</div>
            <div class="card">
                <?php if (!$users): ?>
                    <p class="hint">No users found. Create the first one.</p>
                <?php else: ?>
                    <table>
                        <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo (int) $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td>
                                    <a href="?action=edit&id=<?php echo (int) $user['id']; ?>" style="color: #a5b4fc; font-size: 0.8rem; text-decoration: none; margin-right: 0.6rem;">Edit</a>
                                    <a href="?action=delete&id=<?php echo (int) $user['id']; ?>"
                                       style="color: #f97373; font-size: 0.8rem; text-decoration: none;"
                                       onclick="return confirm('Delete this user?');">
                                        Delete
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="section">
        <span class="pill">
            <strong>Status</strong> Application is running normally.
        </span>
    </div>
</div>
</body>
</html>

