
# ⚙️ OOSettings — Smart Settings Manager for Laravel

**OOSettings** is a flexible key-value settings manager for Laravel applications, enabling you to easily manage both **global settings** and **polymorphic model-specific settings**.

---

## 🧱 Features

✅ Global system-wide settings (e.g. site title, logo, default language)  
✅ Model-specific polymorphic settings (e.g. user notification preferences, project configurations)  
✅ Dot-notated key support (`settings.email.enabled`)  
✅ JSON-based value structure  
✅ Service + Trait based usage  
✅ Includes `oo_setting()` and `oo_setting_m()` helper functions

---

## ⚙️ Installation

### 1. Require the package

```bash
composer require onaonbir/oosettings
````

### 2. Publish and run the migration

```bash
php artisan vendor:publish --tag=oosettings-migrations
php artisan migrate
```

---

## 🔑 Database Structure

| Column             | Description                            |
| ------------------ | -------------------------------------- |
| `id`               | Primary key                            |
| `key`              | Setting key (e.g. `site.title`)        |
| `value`            | JSON value                             |
| `name`             | (Optional) Human-readable name         |
| `description`      | (Optional) Description of the setting  |
| `settingable_id`   | Polymorphic ID (e.g. User, Project...) |
| `settingable_type` | Polymorphic model class                |
| `timestamps`       | Created / Updated timestamps           |

---

## 🚀 Usage

### 📌 1. Global Settings (Using the `OOSettings` Service)

```php
use OnaOnbir\OOSettings\OOSettings;

OOSettings::set('site.title', 'Intraworkzone');
OOSettings::set('features.search.enabled', true);

$title = OOSettings::get('site.title', 'Default Title');
$isSearchEnabled = OOSettings::get('features.search.enabled', false);

OOSettings::forget('features.search.enabled');
```

---

### 👤 2. Model-Based Settings (Using Trait + Service)

Attach the `HasSettings` trait to your model:

```php
use OnaOnbir\OOSettings\Traits\HasSettings;

class User extends Model
{
    use HasSettings;
}
```

Now, you can manage settings directly:

```php
$user->setOOSetting('notifications.email', true);
$user->getOOSetting('notifications.email'); // true
$user->forgetOOSetting('notifications.email');
```

---

## 🧠 Helper Functions

### 🌐 For Global Settings:

```php
oo_setting('site.title', 'Default Title');
```

### 🧍 For Model-Based Settings:

```php
oo_setting_m($user, 'notifications.email', true);
```

---

## 🎯 Example Use Cases

* Admin panel customizable settings
* Tenant-based or customer-specific configurations
* User notification preferences
* Theme or UI mode selection
* Feature/module toggling per model

---

## 🛠️ Developer Notes

### The `HasSettings` trait provides:

* A `settings()` morphMany relationship
* `getOOSetting()`, `setOOSetting()`, `forgetOOSetting()` methods
* Isolated per-model settings management with minimal effort

---

## 🛠️ License

MIT © OnaOnbir
Made with ☕ and late nights.
