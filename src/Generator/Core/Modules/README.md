# ⚙️ Module Manager (Core)

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![Status](https://img.shields.io/badge/status-essential-red.svg)

The **Module Manager** is the heart of the modular system. It allows you to activate, deactivate, install, and uninstall modules in real-time without touching a single line of code.

---

## ✨ Features

- **🔘 One-Click Activation**: Toggle modules on and off instantly.
- **📥 Local Marketplace**: View and install modules uploaded to your own marketplace.
- **🧱 Registry Integration**: Direct connection with `modules.json` for persistence.
- **🛡️ Safety First**: Prevents uninstallation of active modules to maintain stability.
- **📦 Clean Desinstallation**: Removes all module files recursively.

---

## 🛠️ Usage

### Routes
| URL | Description |
| --- | --- |
| `/system/modules` | Main management interface |
| `/system/modules/install` | Upload or local install interface |

---

## 🏗️ Technical Details

- **Namespace**: `App\Modules\Modules`
- **Logic**: Uses `PackageInstaller` for ZIP handling and `ModuleRegistry` for state management.

---

*Part of the Rahpt Core Modular Ecosystem*
