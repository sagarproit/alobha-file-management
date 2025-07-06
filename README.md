📁 Alobha File Management
A secure and scalable web-based system to manage repositories and version-controlled files, with role-based access and JWT authentication.

🚀 Project Setup & Run Instructions

📦 Backend (Laravel API)

Clone the repo:

git clone https://github.com/your-username/alobha-file-management.git
cd alobha-file-management

Install dependencies:

composer install

Environment setup:

cp .env.example .env
php artisan key:generate
Configure .env:

Set up DB_CONNECTION (MySQL)

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=file_versioning
DB_USERNAME=root
DB_PASSWORD=

Set JWT_SECRET:

php artisan jwt:secret

JWT_SECRET=5c91e2f7b03c69a861aedcf494634f25c01fefe2978ada2cb79eaa47dcdcbfb2

Run migrations:

php artisan migrate

Run the server:

php artisan serve




🌐 Frontend (HTML + Bootstrap)

Go to /public folder.

Open login.html in a browser.
http://127.0.0.1:8000/login.html



✅ List of Implemented Features

🔐 Authentication
User registration and login via JWT

Profile info from token

📁 Repository Management
Create repositories (folders)

View list of repositories

📄 File Management
Upload .txt, .md, .json files

Assign custom name (optional)

Store and view file versions

Download any version

🔄 Version Control
Each file update creates a new version

View all versions per file

👥 Role-Based Access Control (RBAC)
Assign roles (admin, write, read) per repository

UI form + API for assignment

Only allowed users can upload/view/download

🔍 Full-Text Search
Search across filenames and contents (for .txt, .md, .json)

📊 Audit & Logs
Log actions like uploads and access control changes



🏗️ Architecture / Design Overview

Backend
Laravel 12 (API-only)

JWT Auth for secure access

MySQL

Clean modular structure: Controllers, Services, Models

File uploads handled via storage/app/repository_x/...

Frontend
HTML5 + Bootstrap 5

Uses simple JS for API calls

Responsive design with a navbar for:

Login / Profile

Repositories

Upload

File listing

Role Assignment

Versioning Logic
Files stored by repository_id/filename-version.ext

Metadata tracked in:

files table

file_versions table

Download links dynamically fetch requested version

RBAC Design
roles table for read, write, admin

repository_user pivot with role_id

Middleware for role checks on API routes