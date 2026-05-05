# Hospital Management System (HMS)

A comprehensive, role-based Hospital Management System built with PHP, MySQL, and Bootstrap 5. This system streamlines hospital operations, patient lifecycles, clinical workflows, and administrative tasks.

## 🚀 Features & Modules

The system provides specialized workflows based on user roles, ensuring secure and efficient management of hospital tasks:

- **Admin Control**: Full oversight of hospital operations. Manage system users, permissions, and staff (including Nurses).
- **Reception**: Handle patient registration, appointments, admissions, and assign patients to available rooms or doctors.
- **Doctor**: Manage patient consultations, write prescriptions, request lab tests, and update diagnosis notes.
- **Nurse**: Track and monitor patient vitals (temperature, blood pressure, pulse rate) for admitted patients. *(Note: Nurse profiles are managed directly by Admins).*
- **Lab Technician**: Manage laboratory test requests, input test results, and track lab fees.
- **Pharmacy**: Complete pharmacy inventory management. Issue medicines against prescriptions, track stock quantity, and monitor reorder levels.
- **Billing**: Consolidated billing module. Generate comprehensive invoices covering consultation fees, lab fees, pharmacy expenses, and room charges.
- **Rooms & Admissions**: Track room availability (General/Private), daily charges, and manage patient admission and discharge dates.

## 🛠️ Technology Stack

- **Backend**: PHP 8+
- **Database**: MySQL (PDO)
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5.3
- **Icons**: Font Awesome 6
- **Typography**: Google Fonts (Poppins)

## 📁 Project Structure

- `/admin` - Administrator dashboard and management tools
- `/admissions` - Patient admission handling and room allocation
- `/auth` - Secure login, authentication, and session management
- `/billing` - Financial operations, invoicing, and payments
- `/config` - System configuration and database connection scripts
- `/doctor` - Clinical tools, prescriptions, and diagnosis management
- `/includes` - Shared functions, helpers, and layout components
- `/lab` - Laboratory module for test tracking and results
- `/nurse` - Interface for tracking patient vitals
- `/pharmacy` - Drug inventory and dispensing
- `/receptionist` - Front desk operations and patient registration
- `/assets` - CSS, JavaScript, and images

## ⚙️ Setup Instructions

1. **Prerequisites**: Ensure you have a web server (like XAMPP, WAMP, or LAMP) running PHP and MySQL.
2. **Database Setup**:
   - Create a MySQL database (e.g., `hospital_management`).
   - Import the provided `database.sql` file to create the tables and insert default data.
3. **Configuration**:
   - Update your database connection settings in the `/config` directory (likely `config/database.php` or similar) to match your local database credentials.
4. **Access the System**:
   - Navigate to the project folder via your localhost server (e.g., `http://localhost/HMS_M/`).
   - Use the pre-configured credentials from the SQL file to log in.

### Default Test Accounts
- **Admin**: Username: `admin` | Password: (Check initialization script/hash, usually `password` or `admin123`)
- **Receptionist**: Username: `recep1`
- **Doctor**: Username: `doc1`
- **Lab Technician**: Username: `lab1`
- **Pharmacist**: Username: `pharm1`

## 🎨 UI/UX Highlights
- Fully responsive, mobile-first design using Bootstrap 5.
- Modern, clean, and colorful SaaS-style login page.
- Secure password handling with "hidden by default" toggles.
- Unified dashboard interface with sidebars and quick-access stat cards.

## 🔐 Security
- Role-based Access Control (RBAC) enforced on all modules.
- Secure session handling.
- Passwords hashed securely using `password_hash()` via PHP.
- Prevented direct Nurse login (managed by Admin for security constraints).
