# Library Management System

A modern, feature-rich Library Management System built with PHP and MySQL, featuring an elegant Tailwind CSS interface.

## Features

- 📚 **Book Management**
  - Add, edit, and delete books
  - Track available copies
  - Categorize books
  - ISBN and publication details

- 👥 **Member Management**
  - Member registration
  - Contact information
  - Membership status tracking
  - Address management

- 🔄 **Circulation System**
  - Book checkout and return
  - Due date tracking
  - Overdue book alerts
  - Loan history

- 📊 **Reporting**
  - Available books report
  - Overdue books report
  - Member activity
  - Inventory statistics

## Technologies Used

- **Frontend**:
  - Tailwind CSS
  - Font Awesome icons
  - Responsive design

- **Backend**:
  - PHP
  - MySQL
  - PDO for database operations

- **Design Features**:
  - Glassmorphism UI
  - Animated elements
  - Color-coded status indicators
  - Interactive hover effects

## Installation

1. **Prerequisites**:
   - PHP 7.4 or higher
   - MySQL 5.7 or higher
   - Web server (Apache/Nginx)

2. **Setup**:
   ```bash
   # Clone the repository
   git clone https://github.com/yourusername/library-management-system.git
   
   # Import database
   mysql -u username -p library_system < database/library_system.sql
   ```

3. **Configuration**:
   - Update database credentials in the PHP file
   ```php
   $host = 'localhost';
   $dbname = 'library_system';
   $username = 'root';
   $password = '';
   ```

4. **Access**:
   - Open in browser: `http://localhost/library-management-system`

## Usage

1. **Adding Books**:
   - Navigate to Book Catalog
   - Fill in book details
   - Set total copies available

2. **Managing Members**:
   - Register new members
   - Update member information
   - Set active/inactive status

3. **Circulating Books**:
   - Check out books to members
   - Set due dates
   - Process returns
   - Track overdue items

4. **Generating Reports**:
   - View available books
   - Check overdue items
   - Monitor member activity

## Customization

You can easily customize the system by:

1. Changing colors in Tailwind config:
   ```javascript
   module.exports = {
     theme: {
       extend: {
         colors: {
           'library-primary': '#F59E0B',
           'library-secondary': '#3B82F6'
         }
       }
     }
   }
   ```

2. Modifying the database schema in `database/library_system.sql`

## Contact

For questions or support, please contact:
- Your Name - tusarg937@gmail.com
- Project Link: [https://github.com/yourusername/library-management-system](https://github.com/yourusername/library-management-system)

---

**Happy Reading!** 📖✨
