# 🙏 CHRIST GOSPEL MESSENGERS STUDENTS FELLOWSHIP (CGMSF) – Church Management Website

<div align="center">

![PHP](https://img.shields.io/badge/PHP-7.4+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)
![Status](https://img.shields.io/badge/Status-Production_Ready-00C853?style=for-the-badge)

**A fully dynamic, feature‑rich website for modern church management**  
*Built with PHP, MySQL, Bootstrap 5 – 100% customizable by admin*

[Live Demo](#) · [Report Bug](#) · [Request Feature](#)

</div>

---

## 📖 Overview

**CGMSF Website** is a complete digital solution for **Christ Gospel Messengers Students Fellowship** – a campus‑based Christian ministry. The platform enables:

- ✝️ **Share the gospel** through events, media, and sermons.
- 👥 **Manage members** (students, leaders, alumni) with profiles and CVs.
- 📅 **Coordinate events** (worship nights, Bible studies, outreach) with registration.
- 🖼️ **Showcase media** (photos, videos) in a beautiful gallery.
- 📊 **Generate reports** (attendance, member lists, CV submissions).
- 🎨 **Full customisation** – colours, logo, homepage content, footer HTML – all editable via admin panel.

---

## ✨ Key Features

### 👤 For Visitors & Members
- **Modern, responsive homepage** with:
  - Full‑width image slider (admin controlled).
  - Vision & mission statements.
  - Animated counters (members, events, media, ministry years).
  - Upcoming events grid (with “Registered” badges for logged‑in users).
  - Latest media preview (images/videos with lightbox).
  - Ministries showcase.
  - Testimonials carousel.
  - Service times + Google Maps.
  - Call‑to‑action banner & newsletter signup.
- **About, Contact, Events (list/calendar), Media Gallery** pages.
- **Member dashboard**:
  - Edit profile & upload profile picture.
  - Change password.
  - Upload / view / delete CV (PDF/DOCX).
- **Event registration** with phone number & SweetAlert confirmation.
- **Secure login / signup** (password hashing, CSRF protection).

### 🔧 For Admin
- **Centralised admin panel** (responsive sidebar layout).
- **Dashboard** with:
  - Real‑time statistics (members, events, media, unread messages, CVs, registrations).
  - Line & bar charts (member growth, event registrations over 6 months).
  - Recent activity (latest members, upcoming events, recent messages).
- **Manage members** – add/edit/delete, promote to admin, activate/deactivate.
- **Manage events** – create/edit/delete, upload image, view registrations per event.
- **Manage media gallery** – upload images/videos, edit titles/descriptions, delete.
- **Manage hero slider** – add/edit/delete slides, set order, active/inactive.
- **Contact messages** – view, mark as read, delete, search.
- **Reports** – export members, events, CVs, event‑specific registrations as CSV or PDF (using Dompdf).
- **Site settings** – change site name, primary/secondary colours, logo, footer HTML, about content, contact info, service times, CTA, social links, Google Maps embed.
- **Full HTML support** in footer, about, and homepage text sections.

### 🔐 Security
- Password hashing (BCRYPT).
- Prepared statements (SQL injection protection).
- CSRF tokens on all forms.
- XSS protection with `htmlspecialchars()` (except trusted admin HTML fields).
- Role‑based access control (admin / member).

---

## 🛠️ Technology Stack

| Component       | Technology                          |
|----------------|-------------------------------------|
| Backend        | PHP 7.4+ (native OOP)              |
| Database       | MySQL 8.0 / MariaDB 10.4+          |
| Frontend       | HTML5, CSS3, Bootstrap 5, JavaScript |
| Libraries      | Chart.js, FullCalendar, SweetAlert2, AOS (animate on scroll), Dompdf |
| Server         | Apache (XAMPP / WAMP / LAMP)        |
| Dependencies   | Composer (optional for Dompdf)      |

---

## 📁 Project Structure (Simplified)
church_site/
├── public/ # Web root
│ ├── index.php # Homepage (dynamic)
│ ├── admin/ # Admin panel
│ ├── api/ # JSON endpoints (events, chart data, registration)
│ ├── assets/ # CSS, JS, uploads (slider images, CVs, media, logo)
├── app/ # Application core (outside webroot)
│ ├── config/ # Database, env loading
│ ├── controllers/ # Business logic
│ ├── models/ # Database interactions (User, Event, Media, Slider…)
│ ├── views/ # Reusable templates (header, footer)
│ ├── helpers/ # Auth, Security, CSRF
├── logs/ # PHP error logs
├── vendor/ # Composer packages (Dompdf)
├── .env # Environment variables (DB credentials, mail)
└── README.md

text

---

## 🚀 Installation Guide

### Prerequisites
- PHP 7.4 or higher (with PDO, GD, fileinfo extensions)
- MySQL / MariaDB
- Composer (only for PDF export – Dompdf)
- Apache server (XAMPP / WAMP / LAMP recommended)

### Step‑by‑Step

1. **Clone the repository**
   ```bash
   git clone https://github.com/your-username/church-site.git
   cd church-site
Set up database

Create a database (e.g., church_db).

Import the schema from database/schema.sql (provided in the code – see earlier phases).

Configure .env
Copy .env.example to .env and fill in your credentials:

env
DB_HOST=127.0.0.1
DB_NAME=church_db
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
APP_URL=http://localhost/church_site/public
APP_ENV=development
Install Composer dependencies (for PDF export)

bash
composer install
Set document root to public/ folder (for security).
Apache example:

apache
DocumentRoot "C:/xampp/htdocs/church_site/public"
Create upload directories (or they will be auto‑created):

public/assets/uploads/slides/

public/assets/uploads/events/

public/assets/uploads/media/

public/assets/uploads/cvs/

public/assets/uploads/profiles/

public/assets/uploads/settings/

Create an admin user
Run this SQL (change password hash):

sql
INSERT INTO `users` (email, password_hash, full_name, role) VALUES 
('admin@cgmsf.org', '$2y$10$...', 'Super Admin', 'admin');
Generate a password hash using:

php
echo password_hash('YourAdminPass', PASSWORD_BCRYPT);
Access the website

Frontend: http://localhost/

Admin panel: http://localhost/admin (login with admin credentials)

Customise via Admin → Site Settings:

Upload logo, set colours, edit footer HTML, add slider images, etc.

🧑‍💻 Usage Guide
For Members
Sign up → verify email (optional, can be auto‑approved).

Login → go to Dashboard.

Update profile (name, profile picture).

Upload your CV (PDF/DOCX) – visible to admin only.

Browse events → click “Register” → enter phone number → confirm.

View media (images, videos) in gallery.

For Admin
Dashboard – see stats, charts, recent activity.

Manage members – add, edit, delete, change role.

Manage events – create, edit, delete, view registration list.

Manage media – upload, edit titles, delete.

Manage slider – add/remove slides, reorder.

View messages – from contact form.

Generate reports – export to CSV or PDF (members, events, registrations, CVs).

Site settings – change everything visual/textual.

🎨 Customisation Tips
Colours & logo – update in Admin → Site Settings; CSS variables apply globally.

Footer – paste any HTML to create multi‑column, social links, etc.

Homepage sections (vision, mission, ministries, testimonials) – all editable via settings.

Slider – add up to 10 slides; each can have title, subtitle, button link.

Google Maps – embed iframe from Google Maps into the “Google Maps Embed Code” field.

📄 License
Distributed under the MIT License. See LICENSE for more information.

🙌 Acknowledgements
Bootstrap 5 – responsive framework.

Chart.js – for beautiful statistics.

FullCalendar – event calendar view.

SweetAlert2 – modern modal dialogs.

Dompdf – PDF report generation.

FontAwesome – icons.

AOS – scroll animations.

📧 Contact
Christ Gospel Messengers Students Fellowship
For support or feature requests:
