# Gatherly

A comprehensive web-based event management system that allows users to create, manage, and track events with RSVP functionality, guest list management, and real-time weather forecasts.

## 🌐 Live Demo

**URL:** [https://gatherly-demo.space](https://gatherly-demo.space)

**Admin Credentials (functionality to be added):**
- Email: `admin@gatherly.com`
- Password: `admin123`

**Create User Accounts:**
- Register new accounts at [/register.php](https://gatherly-demo.space/register.php)

## ✨ Features

- 🎉 Event creation and management (3-step creation process)
- 📧 Email invitations and notifications
- ✅ RSVP tracking and guest list management
- 🌦️ Weather forecasts for event locations
- 🗺️ Google Maps integration for location selection
- 📊 Dashboard analytics and event statistics

## 🛠️ Technologies Used

### **Backend**
- PHP 8.x (Object-Oriented Programming)
- MySQL Database
- PDO for database interactions
- Composer for dependency management

### **Frontend**
- HTML5, CSS3, JavaScript
- Bootstrap 5.3 (Responsive UI framework)
- Bootstrap Icons
- Custom CSS

### **APIs & Services**
- Google Maps API (Location selection and display)
- OpenWeather API (Weather forecasts)
- SendGrid API (Email)
- PHPMailer (SMTP email)

### **Libraries & Dependencies**
- `vlucas/phpdotenv` - Environment variable management
- `phpmailer/phpmailer` - Email functionality
- `sendgrid/sendgrid` - SendGrid integration

### **Server Requirements**
- Apache
- PHP 8.0
- MySQL 5.7
- Composer

## 📁 Project Structure

```
gatherly/
├── admin/                    # Admin-specific pages
│   └── dashboard.php        # Admin dashboard
├── app/                     # Application core
│   ├── config/             # Database configuration
│   ├── helpers/            # Helper functions
│   ├── models/             # Data models
│   └── views/              # Reusable view components
├── assets/                  # Static assets
├── config/                  # Application configuration
│   └── config.php          # Main configuration file
├── database/               # Database-related files
├── handlers/               # Form submission handlers
│   ├── login_handler.php
│   ├── register_handler.php
│   ├── create_event_handler.php
│   ├── rsvp_handler.php
│   └── ...
├── logs/                   # Application logs
├── public/                 # Public assets
│   ├── css/               # Stylesheets
│   └── js/                # JavaScript files
├── vendor/                 # Composer dependencies
├── .env                    # Environment variables (not in repo)
├── .env.example           # Example environment file
├── index.php              # Application entry point
├── login.php              # Login page
├── register.php           # Registration page
├── dashboard.php          # User dashboard
├── events.php             # Events listing
├── create_event.php       # Event creation
├── view_event.php         # Event details
├── edit_event.php         # Event editing
├── guest_list.php         # Guest list management
├── invitations.php        # Invitations page
├── rsvps.php              # RSVP management
├── profile.php            # User profile
└── composer.json          # Composer dependencies
```
