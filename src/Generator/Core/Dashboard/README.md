# 📊 Dashboard Module (Core)

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Status](https://img.shields.io/badge/status-core-green.svg)

The **Dashboard** is the command center of your application. It provides the initial landing area for authenticated users and hosts the main application widgets.

---

## ✨ Features

- **🏠 Home Base**: The default redirect after login.
- **🛠️ Extensible**: Designed to allow other modules to register widgets and cards.
- **📱 Responsive**: Fluid grid system for all devices.
- **🎨 Premium Visuals**: Optimized for the AdminLTE theme.

---

## 🏗️ Structure

- **Controller**: `DashboardController`
- **Route**: `/dashboard`
- **Namespace**: `App\Modules\Dashboard`

### 🔀 Route Disambiguation
This module implements an automatic routing logic:
- **Admin Access**: If the user has `admin.access`, they stay in the `/dashboard` view.
- **Regular User**: If the user lacks admin privileges, they are automatically redirected to `/{uid}/panel`.

This ensures that other modules can link to `/dashboard` safely, and the system will handle the appropriate landing for each user type.

---

*Part of the Rahpt Core Modular Ecosystem*
