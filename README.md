📦 mycouriers — Courier Management System (PHP)

mycouriers is a PHP & MySQL-powered courier management platform that enables customers to book parcels, staff to manage deliveries, and admins to oversee entire operations.
This project includes fully functional roles with separate dashboards for Admin, Staff, and Customer.

📁 Project Structure
    find project structure in Structure.txt file 

🚀 Features
👤 Customer Features

Register & Login

Book courier

Cancel courier

Track parcel using tracking number

View all parcels

View & update profile

🧑‍💼 Staff Features
View assigned parcels
Update parcel delivery status
Book parcel on behalf of customer
Access customer information
Manage profile

👨‍💼 Admin Features
Dashboard with statistics
Manage customers
Manage staff
Manage branches
View all parcels
Generate delivery reports
Update parcel status

🔐 Security
Password hashing (bcrypt)
Role-based access (Admin / Staff / Customer)
Unauthorized access handled (unauthorized.php)
Prepared statements for SQL injection prevention

🧰 Technologies Used
Backend
  PHP 8+
  MySQL
Frontend
  HTML5
  CSS3 (Custom + Bootstrap concepts)  
  JavaScript
Minimal jQuery

⚙️ Installation Guide
1️⃣ Clone the repository
git clone https://github.com/your-username/mycouriers.git

2️⃣ Create database
Open phpMyAdmin
Create DB: courier_db

Import SQL file (if included or exported manually)

3️⃣ Configure database

Edit config.php:

$host = "localhost";
$username = "root";
$password = "";
$database = "mycouriers";

4️⃣ Run project

Place folder inside:
htdocs/ → XAMPP
www/ → WAMP

Start Apache & MySQL
Visit:http://localhost/MyCouriers/

📡 Core Functionality Flow
Parcel Status Flow
Booked → Received → In Transit → Out for Delivery → Delivered
Tracking System
Enter tracking number in track.php
Shows real-time courier status
Authentication Pages
login.php
register.php
forgot_password.php
reset_admin.php

📊 Admin Dashboard Overview
Displays:
Total Parcels
Delivered Parcels
Pending Parcels
Registered Customers
Registered Staff
Branch Count

🤝 Contributing
Feel free to fork & improve the project.
Pull requests are welcome.

for any queiries feel free to reach 
praveenkori77@gmail.com
