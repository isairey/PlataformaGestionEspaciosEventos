# SPHERE

## CSPC Space Facility and Equipment Rental & Event Management System

> An enterprise-grade facility management system for educational institutions and corporate spaces

[![PHP](https://img.shields.io/badge/PHP-8.1+-blue)](https://php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4-red)](https://codeigniter.com/)
[![MySQL](https://img.shields.io/badge/MySQL-8.0+-blue)](https://www.mysql.com/)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## Table of Contents

- [Overview](#overview)
- [Quick Start](#-quick-start)
- [Screenshots](#-screenshots)
- [Key Features](#key-features)
- [Technology Stack](#technology-stack)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [System Architecture](#system-architecture)
- [User Roles & Permissions](#user-roles--permissions)
- [API Documentation](#api-documentation)
- [Database Schema](#database-schema)
- [Security](#security)
- [Project Structure](#project-structure)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)
- [Support](#support)

---

## Overview

**SPHERE** streamlines facility booking, scheduling, and administration through an intuitive interface designed for multi-user environments. Whether managing educational facilities, corporate spaces, or event venues, SPHERE provides real-time availability tracking, intelligent conflict detection, and comprehensive management tools.

### Core Use Cases

- **Facility Administrators** – Manage inventory, pricing, and availability across multiple facilities
- **Facility Managers** – Schedule maintenance, track usage, and monitor operations
- **Event Organizers** – Book spaces with integrated equipment and services
- **Users** – Browse, book, and manage reservations with real-time availability
- **Guests** – Limited access to public facilities and information

### Key Advantages

✓ **Prevent Double-Bookings** – Real-time conflict detection and validation  
✓ **Automate Operations** – Workflows and approval systems reduce manual overhead  
✓ **Optimize Revenue** – Dynamic pricing with add-ons, equipment, and fee calculations  
✓ **Mobile-First Design** – Responsive interface, QR codes, and Google OAuth integration  
✓ **Data-Driven Decisions** – Comprehensive analytics and reporting capabilities

---

## 🚀 Quick Start

Get SPHERE running in **5 minutes**:

```bash
# 1. Clone and navigate
git clone https://github.com/SHIROxcx/SPHERE-CSPC-Space-Facility-and-Equipment-Rental-and-Event-Management-System.git
cd SPHERE-CSPC-Space-Facility-and-Equipment-Rental-and-Event-Management-System

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
cp .env.example .env
# Edit .env with your database credentials

# 4. Initialize database
mysql -u root -p -e "CREATE DATABASE sphere_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p sphere_db < capsdb.sql

# 5. Start the application
php spark serve
# Access at http://localhost:8080
```

**Default Credentials:**

- Email: `admin@sphere.local`
- Password: See database seed data

See [Installation](#installation) section for detailed setup instructions.

---

## 📸 Screenshots

### Dashboard Overview

The admin dashboard provides real-time insights with:

- System metrics and usage analytics
- Upcoming bookings and events
- Revenue tracking
- User activity logs

### Facility Booking Interface

- Real-time availability calendar
- Equipment add-ons selection
- Dynamic pricing calculation
- Booking confirmation workflow

### Equipment Management

- Inventory tracking dashboard
- Stock level monitoring
- Rental rate configuration
- Schedule-based availability

### Admin Controls

- User management interface
- Facility configuration panel
- Report generation tools
- System settings and maintenance

_Screenshots to be added to repository during first production release_

---

## Key Features

### Reservation Management

- Real-time availability checking with dynamic date/time booking
- Automatic conflict detection and double-booking prevention
- Equipment allocation with stock tracking
- Add-ons system for catering, services, and equipment rentals
- Flexible booking extensions with automatic cost recalculation
- Complete cancellation workflow and tracking

### Facility Management

- Multi-facility support with unlimited facilities
- Tiered pricing plans and multiple booking packages per facility
- Feature sets and inclusions per plan
- Maintenance mode tracking
- Gallery management for facility images
- QR code generation for promotion and access

### Equipment Management

- Inventory tracking with real-time stock monitoring
- Category-based organization
- Customizable rental rates per unit
- Schedule-based availability tracking
- Batch operations for efficient management

### User Management

- Six role-based access levels (Admin, Faculty, Student, Employee, Facilitator, Guest)
- Granular permission management
- Google OAuth 2.0 and email authentication
- Phone contact integration

### Billing & Pricing

- Intelligent cost calculation engine
- Support for multiple pricing models (per-unit, per-hour, flat-rate)
- Configurable fees (additional hours: ₱500/hour | maintenance: ₱2,000)
- Real-time transaction tracking

### Admin Dashboard

- System analytics and usage metrics
- User administration (create, edit, deactivate)
- Facility and equipment configuration
- Report generation (Excel/Word exports)
- Event management and billing overview

### Communications

- Automated email notifications (confirmations, reminders, cancellations)
- User feedback collection surveys
- Admin contact form management
- Real-time alerts for pending approvals

### Additional Features

- QR code support for facilities and access management
- Document export (Excel and Word formats)
- Guest registration with limited access
- Responsive mobile-friendly design
- Secure session management

---

## Technology Stack

| Layer              | Technologies                                     |
| ------------------ | ------------------------------------------------ |
| **Frontend**       | HTML5, CSS3, JavaScript, QR Code Library         |
| **Backend**        | PHP 8.1+, CodeIgniter 4 Framework                |
| **Database**       | MySQL 8.0+ with Stored Procedures                |
| **Authentication** | Email/Password + Google OAuth 2.0                |
| **Storage**        | File-based (uploads, documents, logs)            |
| **Export**         | PHPSpreadsheet (Excel), PHPWord (Word documents) |

---

## System Architecture

For detailed architecture information, see the project documentation included in the repository.

---

## Requirements

### System Requirements

- **PHP** 8.1 or newer
- **MySQL** 8.0+ or **MariaDB** 10.5+
- **Composer** (latest version)
- **Node.js** 14.0+ (for npm dependencies)
- **Apache** or **Nginx** (or PHP built-in server for development)

### Required PHP Extensions

- `intl` – Internationalization support
- `mbstring` – Multi-byte string handling
- `mysqli` – MySQL database connection
- `curl` – HTTP requests
- `gd` – Image manipulation

---

## Installation

### 1. Clone the Repository

```bash
git clone https://github.com/SHIROxcx/SPHERE-CSPC-Space-Facility-and-Equipment-Rental-and-Event-Management-System.git
cd SPHERE-CSPC-Space-Facility-and-Equipment-Rental-and-Event-Management-System
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Configure Environment

Copy the example environment file and configure your settings:

```bash
cp .env.example .env
```

Update `.env` with your configuration:

```env
app.baseURL = http://localhost:8080
app.environment = development

database.default.hostname = localhost
database.default.database = sphere_db
database.default.username = root
database.default.password = your_password
database.default.DBDriver = MySQLi
```

### 4. Set Up Database

Create the database and import the schema:

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE sphere_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import schema
mysql -u root -p sphere_db < capsdb.sql

# Run migrations
php spark migrate
```

### 5. Set File Permissions

Ensure the writable directory has proper permissions:

```bash
chmod -R 755 writable/
chmod -R 755 public/
```

### 6. Start Development Server

```bash
php spark serve
```

Access the application at [http://localhost:8080](http://localhost:8080)

---

## Configuration

### Database Setup

Configure your database connection in `.env`:

```env
database.default.hostname = localhost
database.default.database = sphere_db
database.default.username = root
database.default.password = your_password
database.default.DBDriver = MySQLi
```

### Google OAuth Setup

To enable Google authentication:

1. Visit [Google Cloud Console](https://console.cloud.google.com/)
2. Create OAuth 2.0 credentials (Web application)
3. Add authorized redirect URIs
4. Configure in `.env`:

```env
GOOGLE_CLIENT_ID = your_client_id
GOOGLE_CLIENT_SECRET = your_client_secret
GOOGLE_REDIRECT_URI = http://localhost:8080/auth/google/callback
```

---

## User Roles & Permissions

| Role            | Access Level | Primary Functions                                              |
| --------------- | ------------ | -------------------------------------------------------------- |
| **Admin**       | Full         | System administration, user management, facility configuration |
| **Faculty**     | High         | Book facilities, view reports, manage events                   |
| **Employee**    | High         | Facility operations, equipment tracking, maintenance           |
| **Facilitator** | Medium       | Facility management, equipment handling                        |
| **Student**     | Medium       | Browse facilities, request bookings, view availability         |
| **Guest**       | Low          | Browse public facilities and information only                  |

---

## API Documentation

### Base URL

```
http://localhost:8080/api
```

### Core Endpoints

**Facilities**

```http
GET  /api/facilities/list              # List all facilities
GET  /api/facilities/data/:id          # Get facility details
```

**Bookings**

```http
POST /api/bookings                     # Create new booking
POST /api/bookings/checkDateConflict   # Check availability
POST /api/bookings/equipment-availability  # Check equipment availability
```

---

## Database Schema

### Core Tables

- `users` – User accounts and authentication
- `facilities` – Facility information and details
- `plans` – Booking plans and pricing tiers
- `bookings` – Reservation records
- `equipment` – Equipment inventory
- `addons` – Additional services and items
- `events` – Event management and tracking
- `plan_features` – Features included in each plan
- `plan_equipment` – Equipment allocations per plan
- `booking_equipment` – Equipment assigned to bookings
- `booking_addons` – Add-ons assigned to bookings
- `equipment_schedule` – Equipment availability schedule

### Stored Procedures

- `sp_calculate_booking_cost()` – Calculate total booking cost with fees
- `sp_check_equipment_availability()` – Verify equipment availability
- `sp_get_plan_full_details()` – Retrieve complete plan information

---

## Security

The application implements industry-standard security practices:

✓ **Authentication** – Email/password and Google OAuth 2.0  
✓ **Authorization** – Role-based access control (RBAC)  
✓ **Input Validation** – Comprehensive server-side validation  
✓ **CSRF Protection** – Cross-site request forgery tokens  
✓ **Password Security** – bcrypt hashing with salt  
✓ **SQL Injection Prevention** – Parameterized queries  
✓ **XSS Protection** – Output encoding and escaping  
✓ **Session Security** – Secure session management

---

## Project Structure

```
SPHERE/
├── app/                     # Application code
│   ├── Config/             # Configuration files
│   ├── Controllers/        # Request handlers
│   ├── Models/             # Data models
│   ├── Services/           # Business logic layer
│   ├── Filters/            # Route filters
│   ├── Views/              # View templates
│   └── Database/           # Database configurations
├── public/                  # Web root (document root)
│   ├── index.php           # Application entry point
│   ├── assets/             # Static assets
│   ├── css/                # Stylesheets
│   ├── js/                 # JavaScript
│   └── images/             # Image files
├── system/                  # CodeIgniter framework core
├── writable/               # Application writable directories
│   ├── cache/              # Cache files
│   ├── logs/               # Log files
│   ├── session/            # Session data
│   └── uploads/            # User uploads
├── vendor/                  # Composer packages
├── composer.json           # PHP dependencies
├── package.json            # Node.js dependencies
├── capsdb.sql              # Database schema
└── .env                    # Environment configuration
```

---

## Testing

Run the test suite:

```bash
php spark test
```

---

## Contributing

We welcome contributions to improve SPHERE. To contribute:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/YourFeature`)
3. Commit your changes (`git commit -m 'Add YourFeature'`)
4. Push to the branch (`git push origin feature/YourFeature`)
5. Open a Pull Request

---

## License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

---

## Support

For issues, questions, or suggestions:

- **Email** – [jomeltienes00@gmail.com](mailto:jomeltienes00@gmail.com)
- **GitHub Issues** – [Report bugs](https://github.com/SHIROxcx/SPHERE-CSPC-Space-Facility-and-Equipment-Rental-and-Event-Management-System/issues)
- **GitHub Discussions** – [Ask questions](https://github.com/SHIROxcx/SPHERE-CSPC-Space-Facility-and-Equipment-Rental-and-Event-Management-System/discussions)
- **Documentation** – See project comments and inline code documentation

---

<div align="center">

**Built with CodeIgniter 4**

[↑ Back to top](#sphere)

</div>
