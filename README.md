# MorphTrack

Track and document changes in your Laravel project with ease.  
This package detects updates in:
- API endpoints (Request/Resource)
- Migrations, seeders, and Artisan commands

## 🛠️ Installation
- Install Package
```bash
composer require om/morph-track
```
- Vendor Publish
```bash 
php artisan vendor:publish --tag=endpoints_config
```
- Commands
```bash 
php artisan analyze:endpoints
php artisan generate:instructions
```
---
## Common Usages
### 🌐 Language Support - field_change_locale
This option sets the language used to describe endpoint changes in the diff output.
Supported languages out of the box: ```en, de, es, fr, uk, pt```

---
## Usage API endpoints detect
### 🖨️ Built-in Output Formats
You can switch or extend output styles in the pretty_print.types config:
- group_by_prefix
- flat_verbose
- table_markdown

### 🧯 Post-processing Pipelines
Use route_pipelines.post_filtered. to apply custom filters or transformations after the diff is calculated.

### 🔍 Scramble Dedoc Support
- Supports generating OpenAPI specifications using [Scramble](https://github.com/dedoc/scramble).
- Simply enable it wherever you define your morph_track_config.php (e.g., in `scramble.use`).
You can configure server-specific OpenAPI documentation using `scramble.server`. The key you pass must match one of the server keys defined in your `scramble.php` config under the servers array.