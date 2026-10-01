# AcademicHub Nepal - Admin Hierarchy System Documentation

## Overview

This document describes the strict admin hierarchy system implemented for AcademicHub Nepal. The system ensures that only Super Administrators have authority over Admin and Staff account management.

## Roles and Permissions

### Super Administrator (role: `superadmin`)
- **Full system access** to all modules and features
- Can create, edit, delete, and manage all Administrator accounts
- Can assign and revoke permissions
- Can activate/deactivate accounts
- Can view all activity logs
- Protected: Cannot be deleted or demoted (root account: superadmin@gmail.com)

### Administrator (role: `admin`)
- **Limited access** based on assigned permissions
- Cannot create new admin accounts
- Cannot create staff accounts
- Cannot delete admins or staff
- Cannot change admin permissions
- Cannot promote users to admin roles
- Cannot access `manage-admins.php`

### Staff (role: `admin` with specific permissions)
- Same as Administrator but with more restricted permissions
- Access only to modules explicitly granted via permissions

## Available Permissions

Permissions are stored as JSON in the `users.permissions` column:

```json
{
  "manage_students_success": true,
  "manage_colleges": true,
  "manage_gallery": true,
  "manage_resources": true,
  "manage_orders": true,
  "manage_students": true,
  "manage_team": true,
  "view_reports": true,
  "system_settings": true,
  "manage_admins": true
}
```

### Permission Definitions

- `manage_students_success` - Manage Successful Student Stories
- `manage_colleges` - Manage College Profiles
- `manage_gallery` - Manage Society Gallery
- `manage_resources` - Manage Academic Resources (Upload, Edit, Delete)
- `manage_orders` - Manage Orders & Payment Verification
- `manage_students` - Manage Student Profiles & Directory
- `manage_team` - Manage Team Members & Faculty
- `view_reports` - Access Sales & Analytics Reports
- `system_settings` - Configure System & Operational Settings
- `manage_admins` - Super Admin Governance & Role Assignments (Super Admin only)

## Security Guards

### Authentication Functions (located in `config/auth_check.php`)

#### `require_superadmin()`
- Ensures the current user is a Super Administrator
- Redirects to dashboard with error message if not authorized
- Used on: `admin/manage-admins.php`

#### `require_admin()`
- Ensures the current user is either an Admin or Super Admin
- Redirects to login page if not authorized
- Used on all admin pages

#### `require_permission($permission)`
- Checks if the current admin has a specific permission
- Returns 403 Access Denied if permission not granted
- Super Admin automatically passes all permission checks
- Used on: manage-resources, manage-orders, manage-students, sales-reports, etc.

#### `require_admin_permission($permission)`
- Alias for `require_permission()` with additional admin check
- Used on: content management pages

## Database Schema

### users table

```sql
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `campus` VARCHAR(100) DEFAULT NULL,
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('student', 'admin', 'superadmin') NOT NULL DEFAULT 'student',
  `admin_role` VARCHAR(60) DEFAULT NULL,
  `permissions` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_role` (`role`),
  INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### admin_activity_logs table

```sql
CREATE TABLE `admin_activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `admin_id` INT NOT NULL,
  `action` VARCHAR(100) NOT NULL,
  `details` TEXT DEFAULT NULL,
  `target_type` VARCHAR(50) DEFAULT NULL,
  `target_id` INT DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_admin_id` (`admin_id`),
  INDEX `idx_action` (`action`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Admin Management Features

### manage-admins.php (Super Admin Only)

**Location:** `admin/manage-admins.php`

**Access Control:** 
- Protected by `require_superadmin()`
- Normal admins receive 403 redirect to dashboard

**Features:**

1. **Create New Administrator**
   - Name, Email, Phone, Password fields
   - Role selection: Admin or Super Admin
   - Role preset selection with auto-fill permissions
   - Granular permission checkboxes
   - CSRF protection

2. **Edit Admin Role & Permissions**
   - Update display name
   - Change authority tier (admin/superadmin)
   - Modify role title
   - Add/remove individual permissions
   - Root Super Admin tier locked

3. **Toggle Active/Suspended Status**
   - Activate or suspend admin accounts
   - Root Super Admin cannot be suspended
   - Current session cannot be suspended

4. **Reset Password**
   - Set new password for any admin
   - Minimum 6 characters requirement

5. **Delete Admin Account**
   - Permanently remove admin账户
   - Confirmation dialog: "Are you sure you want to permanently remove this administrator?"
   - Root Super Admin cannot be deleted
   - Current session cannot delete itself
   - Action logged in admin_activity_logs

6. **Activity Audit Trail**
   - View recent administrative operations
   - Shows: Timestamp, Admin, Action, Details, IP Address
   - Last 20 operations displayed

## Sidebar Navigation

**Location:** `admin/sidebar.php`

The sidebar automatically hides the "Admins & Roles" link from normal administrators:

```php
<?php if ($is_sa): ?>
    <div class="small text-uppercase fw-bold text-warning mb-2 mt-4">
        <i class="bi bi-shield-shaded me-1"></i>Role Governance
    </div>
    <a href="<?= BASE_URL ?>/admin/manage-admins.php" class="<?= $active_page === 'manage-admins' ? 'active' : '' ?> text-warning">
        <i class="bi bi-person-gear"></i> Admins &amp; Roles
    </a>
<?php endif; ?>
```

## Permission-Based Access Control

All admin management pages use permission guards:

### Resource Management
- `admin/add-resource.php` - `require_admin_permission('manage_resources')`
- `admin/edit-resource.php` - `require_admin_permission('manage_resources')`
- `admin/manage-resource.php` - `require_admin_permission('manage_resources')`

### Order Management
- `admin/manage-orders.php` - `require_admin_permission('manage_orders')`

### Student Management
- `admin/manage-students.php` - `require_admin_permission('manage_students')`

### Reports
- `admin/sales-reports.php` - `require_admin_permission('view_reports')`

### Content Management
- `admin/cms-content.php` - `require_admin_permission('manage_resources')`
- `admin/content.php` - Dynamic permission based on module

## Default Accounts

### Super Admin (Root)
- **Email:** superadmin@gmail.com
- **Password:** 123456
- **Role:** superadmin
- **Permissions:** All permissions granted
- **Protection:** Cannot be deleted or demoted

### Demo Admin
- **Email:** admin@academichub.com
- **Password:** admin123
- **Role:** admin
- **Permissions:** manage_resources, manage_orders, manage_students, view_reports

### Demo Student
- **Email:** student@demo.com
- **Password:** password123
- **Role:** student

## Activity Logging

All critical administrative actions are logged in `admin_activity_logs`:

- **CREATE_ADMIN** - New admin account created
- **UPDATE_ADMIN_ROLE** - Admin role/permissions modified
- **TOGGLE_ADMIN_STATUS** - Admin activated/suspended
- **RESET_ADMIN_PWD** - Admin password reset
- **DELETE_ADMIN** - Admin account deleted

Each log entry includes:
- Admin ID who performed the action
- Action type
- Details of the action
- Target user ID (if applicable)
- IP address
- Timestamp

## Security Features

1. **CSRF Protection** - All POST requests require valid CSRF token
2. **Session Refresh** - Admin permissions refreshed on each page load
3. **IP Logging** - All admin actions logged with IP address
4. **Password Hashing** - All passwords hashed with PASSWORD_BCRYPT
5. **Permission Checks** - Granular permission checks on all admin pages
6. **Root Protection** - Primary Super Admin account cannot be deleted/demoted
7. **Self Protection** - Admins cannot delete or suspend their own session

## Role Presets

Pre-configured role templates for quick admin creation:

1. **Super Administrator** - All permissions + superadmin role
2. **General Administrator** - manage_resources, manage_orders, manage_students, view_reports
3. **Content & Resource Manager** - manage_resources only
4. **Finance & Orders Admin** - manage_orders, view_reports
5. **Student Support Specialist** - manage_students only

## Implementation Notes

- Staff accounts are implemented as regular `admin` role users with restricted permissions
- The system does not have a separate "staff" role in the database enum
- All admin management is centralized in `manage-admins.php`
- Permission checks are centralized in `config/auth_check.php`
- The sidebar automatically adapts based on user role and permissions
