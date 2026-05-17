# Budapest Community Budget – PHP Web Application

## 🚀 Live Demo

Deployed on Render:
👉 [https://budapest-community-budget-vrae.onrender.com/](https://budapest-community-budget-vrae.onrender.com/)

## Overview

This project is a **community project submission and voting website** inspired by the Budapest Community Budget initiative.

Users can propose their own project ideas, browse projects submitted by others, and vote on them.  
Each user can cast **up to 3 votes per category**, and voting is open for **two weeks after a project is published**.

Administrators manage the workflow by approving, rejecting, or sending projects back for rework with comments.  
The system is designed to be user-friendly, robust, and visually clear.

---

## Main Features

### 👤 User Features

- User registration, login, and logout
- Secure authentication using hashed passwords
- Submit new project proposals
- Edit and resubmit projects that were returned for rework
- Vote on published projects
  - Maximum **3 votes per category**
  - Only **one vote per project**
  - Voting allowed for **two weeks after publication**
- Withdraw votes within the voting period

### 🛠 Admin Features

- Review submitted projects
- Approve, reject, or send projects back for rework with comments
- View all pending projects grouped by category
- Access statistics and insights

### 📊 Statistics & Visualization

- Overall top-voted project
- Top 3 projects per category
- Number of projects grouped by **category and status**
- Statistics are visualized using **JavaScript charts** for better readability

---

## Technical Details

- **PHP-based dynamic web application**
- Session-based authentication
- Data persistence using PHP files
- Careful **input validation** for:
  - Usernames
  - Passwords
  - Email addresses
  - Project submission forms
- AJAX-based voting (no page reload required)
- Clean and user-friendly UI with custom CSS
- JavaScript used for interactive features and data visualization

---

## Project Structure (Simplified)

/data - Stored data (users, projects, votes)

/lib - Authentication, validation, helper

/api - AJAX endpoints (e.g. voting)

/index.php - Homepage & project listing

/project.php - Project detail page

/projects-own.php

/projects-admin.php

/statistics.php

/login.php

/register.php

/style.css

---

## How to Run the Project in Local Environment

1. Clone the repository

   git clone <repository-url>

2. Navigate to the project directory:

   cd <project-folder>

3. Start a local PHP server:

   php -S localhost:8000

4. Open your browser and visit:

   http://localhost:8000

⚠️ A PHP environment is required (PHP 8.x recommended).

## Notes

This project focuses on functionality, correctness, and usability rather than database integration.

All validation and access control are handled carefully to ensure stable operation.

The project demonstrates core web programming concepts using PHP and JavaScript.

This application was developed as a coursework project for:

> **Web Programming**  
> **Course code: IP-18fWPEG**
