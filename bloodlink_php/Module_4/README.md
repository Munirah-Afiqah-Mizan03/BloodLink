# BloodLink — Module 4: Donation Record Management

## Files
```
module4/
├── index.php           → Donation records list (search, filter, table)
├── add_record.php      → Add new donation record form
├── edit_record.php     → Edit existing donation record form
├── get_donor.php       → AJAX endpoint: fetch donor details by ID
├── database.sql        → MySQL schema + sample data
├── includes/
│   ├── db.php          → Database connection config
│   ├── sidebar.php     → Sidebar + logo + HTML <head>
│   └── footer.php      → Closing tags + JS include
└── assets/
    ├── css/style.css   → Full BloodLink pastel stylesheet
    └── js/main.js      → Donor lookup, toast, validation
```

## Setup Steps

### 1. Install XAMPP
Download from https://apachefriends.org and start Apache + MySQL.

### 2. Create the database
Open http://localhost/phpmyadmin → New → name it `bloodlink` → Go.
Then click Import → choose `database.sql` → Go.

### 3. Configure DB connection
Open `includes/db.php` and update:
- DB_USER → your MySQL username (default: root)
- DB_PASS → your MySQL password (default: empty)

### 4. Place files in XAMPP
Copy the entire `module4/` folder to:
  C:/xampp/htdocs/bloodlink/module4/

### 5. Open in browser
http://localhost/bloodlink/module4/index.php

## Pages Overview

| Page | URL | Description |
|------|-----|-------------|
| Records list | index.php | View all records, search & filter |
| Add record | add_record.php | Form to add new donation record |
| Edit record | edit_record.php?id=1 | Edit existing record + audit log |
| Donor lookup | get_donor.php?donor_id=D-0021 | AJAX JSON endpoint |

## Test Donor IDs
- D-0021 → Ahmad Haziq (A+)
- D-0019 → Nurul Fatihah (O+)
- D-0015 → Muhammad Razif (B+)
- D-0012 → Siti Khadijah (AB+)
- D-0009 → Farid Khairul (O-)
