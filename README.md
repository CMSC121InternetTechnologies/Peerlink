# Developer Guide
to start coding on the project clone the repository then navigate to:

## Prerequisites: 
* php
* laravel 
* composer


Please intsall the following on your machine before proceeding. 

## Something to note 
* when running the terminal in VS code please avoid using Powershell 

## Start Coding
to start coding on the project clone the repository then inside the Peerlink folder download laravel:

 ```bash
   composer create-project laravel/laravel [NAME]  
   ```
then navigate into the laravel project

## Install Laravel Dependencies 
This reads the composer.json file and redownloads Laravel, Breeze, and any other PHP packages.
```bash
    composer install
```

## Install Javascript Dependencies
This reads the package.json file and redownloads Vite, Axios, and any other frontend tools
```bash
    npm install
```

## Recreate the Evironment File 
For security reasons, the .env file (which holds your database passwords) is never pushed to GitHub. Laravel provides a safe template called .env.example that you must copy and rename to .env.
**(On Windows Command Prompt, run this:)**

```bash
copy .env.example .env

```

**(On Mac/Linux/Git Bash, run this:)**
```bash
cp .env.example .env
```

## Generate the Application Key
Every Laravel app needs a unique cryptographic key for security (which gets stored in your newly created .env file).

```bash
php artisan key:generate
```

## Set Up Database 
Finally, open the .env file, ensure that the  database name is correct, and run the migrations to rebuild the database tables.
```bash
    php artisan migrate 
```

## Start the Local Developmnent Server
To boot up Laravel's built-in server enter the following command. Please use this instead of XMAPP or other development tool

```bash
    php artisan serve
```

## Opening you application locally
Since we are using @vite, we need Node.js to bundle those assets. We must run a second background process alongside your php artisan serve command. 

Open a new separate terminal (DO NOT CLOSE THE TERMINAL WHERE **php artisan serve** WAS RUNNING) and navigate to the application folder. Run the following commands: 

```bash
# Start the Vite server (you do this every time you code)
npm run dev
```

When you run **npm run dev**, your terminal will show a message indicating that Vite is running (usually on port 5173).

The terminal will usually indicate on what port your server is running. Click the link or copy past the address to your browser. 

## Coding in Laravel 
The standard workflow for adding features or pages in this app are as follows:

**1 Define the Route** 
navigate to 
```bash 
    routes/web.php
```
to tell Laravel what URL the user is allowed to visit

**2 Create the Controller** 
This is where the PHP logic will reside. To create a new controller using artisan: 
```bash
    php artisan make:controller [Controller-Name.php]
```
then navigate to 
```bash
    app/Http/Controllers/[Controller-Name.php]
```
Open it then write the method that you have references in your route. 

**3. Create the Model and Migration**
If your feature needs to save or read data you need a Database Table and a Model to interact with it. Again, use Artisan to generate both at the same time by adding the -m flag:
```bash
    php artisan make:model Course -m
```

**4. Build the View**
Create the HTML that the user actually sees. Because Laravel uses the "Blade" templating engine, your file must end in .blade.php.

Create your files at the 
```bash 
    resources/[css][js][views]
```
folder. 


# 🔗 PeerLink
**Democratizing Academic Support Through Peer-to-Peer Tutoring**

PeerLink is a peer-to-peer matching web application designed specifically for college students. It allows students to offer tutoring in subjects they excel at while simultaneously requesting help in subjects they struggle with—all from a single, unified account. Utilizing a secure “request-and-approve” workflow, PeerLink ensures that both parties consent and understand learning objectives before a match is finalized.

---

## 🎯 Goal Alignment

* **UN Sustainable Development Goal: SDG 4 (Quality Education)**
    Promotes inclusive and equitable quality education by democratizing academic support. It significantly reduces financial and structural barriers by ensuring students have access to free, peer-arranged assistance.
* **Philippine Development Goal: Improve Education and Lifelong Learning**
    Fosters a collaborative campus culture that supports local academic retention and student well-being by empowering students to uplift one another.

---

## 💡 Rationale

The current learning environment often leaves a gap between formal lectures and student comprehension. Professional tutoring is often cost-prohibitive, and informal study groups can lack structure. 

In the **University of the Philippines Tacloban College**, organizations like the Interactive Society, Applied Mathematics Alliance, and UP DOST-Scientifico currently run manual peer tutoring programs (like *Ideathon*). PeerLink is designed to **digitize, streamline, and scale** these efforts. By leveraging the "Protégé Effect"—where students master subjects by teaching them—PeerLink creates a mutually beneficial, high-quality knowledge exchange ecosystem.

---

## ✨ Core Features

* **Dual-Role Dashboard:** A unified, tabbed interface to toggle between “Find a Tutor” and “My Requests” without separate accounts.
* **Dynamic Tutor Search:** Real-time search (via AJAX) to filter available tutors by specific university subject codes.
* **Request-and-Approve Workflow:** Tutors receive detailed notifications for help requests and can review specific pain points before dynamically accepting/declining.
* **Privacy-First Matching:** Contact information remains completely hidden until a tutor explicitly approves a session request.
* **Subject Tagging System:** Standardized tags based on university course codes (e.g., CMSC 11, MATH 18) for precise matching.
* **Rating & Feedback Loop:** A post-session review system to maintain instructional quality and build community trust.

---

## 🛠 Technical Specifications

### Architecture & Security
* **Authentication:** Passwords securely hashed using native `password_hash()`. Secure native PHP `$_SESSION` management. Access control checks on all API endpoints and core pages.
* **Database Security:** Complete SQL injection prevention utilizing prepared statements for all database queries handling user input.
* **Dynamic UI (AJAX):** * *Search:* AJAX `GET` requests fetch matching tutors and update the DOM dynamically without page reloads.
    * *Approval:* AJAX `PATCH` requests update session statuses, dynamically removing items from pending queues upon success.

### Database Schema

#### `Users` Table
| Column | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `user_id` | INT | PK, Auto-increment | Unique identifier for each student |
| `first_name` | VARCHAR | Not Null | Student's legal/registered first name |
| `middle_name` | VARCHAR | Nullable | Student's legal/registered middle name |
| `last_name` | VARCHAR | Not Null | Student's legal/registered last name |
| `email` | VARCHAR | Not Null, Unique | Must be a verified `@edu.ph` address |
| `password` | VARCHAR | Not Null | Hashed password string |
| `rating_avg` | DECIMAL | Nullable | Aggregate score from post-session feedback |
| `created_at` | DATETIME | | Date of account creation |

#### `Courses` Table
| Column | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `course_code` | VARCHAR | PK | Unique university subject code |
| `course_title`| VARCHAR | Not Null | Full title of the course |

#### `Skills` Table
| Column | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `user_id` | INT | PK, FK | References `Users.user_id` |
| `course_code` | VARCHAR | PK, FK | References `Courses.course_code` |

#### `Sessions` Table
| Column | Type | Attributes | Description |
| :--- | :--- | :--- | :--- |
| `session_id` | INT | PK, Auto-increment | Unique identifier for the tutoring match |
| `tutor_id` | INT | FK | Reference to the teaching user |
| `student_id` | INT | FK | Reference to the learning user |
| `course_code` | VARCHAR| FK | The specific course being tutored |
| `message` | TEXT | | Details/pain points about the session |
| `status` | ENUM | | `Pending`, `Approved`, `Completed`, `Declined` |
| `started_at` | DATETIME| | Date when the session started |

---

## 📡 API Endpoints

| Method | Endpoint | Payload Example | Description |
| :--- | :--- | :--- | :--- |
| `POST` | `/api/auth/register` | `{}` | Hashes password & creates a new user record. |
| `POST` | `/api/auth/login` | `{ email, password }` | Validates credentials & initializes PHP `$_SESSION`. |
| `POST` | `/api/auth/logout` | `{}` | Destroys active session & redirects. |
| `GET` | `/api/search_tutors.php?subject={code}`| `None` | Retrieves a JSON list of tutors for the queried subject. |
| `POST` | `/api/requests/create` | `{ student_id, tutor_id, subject_code, message }` | Inserts a new session request with a `Pending` status. |
| `PATCH`| `/api/requests/update-status` | `{ session_id, status: "Approved" }` | Updates an existing session's status (e.g., Approve/Decline). |

---

## 👤 User Stories

| ID | As a... | I want to... | So that I can... | API Route |
| :--- | :--- | :--- | :--- | :--- |
| **US_01** | New user | Create a new account with a username and password. | Securely save and manage my tutor-tutee schedule. | `POST /api/auth/register` |
| **US_02** | Returning user| Log in to my account using my email and password. | Access my account and data. | `POST /api/auth/login` |
| **US_03** | User | Log out of the application. | Protect my account information and end my session securely. | `POST /api/auth/logout` |
| **US_04** | User | List at least one course code I am comfortable tutoring during onboarding. | Be immediately visible to others in the "Find My Tutor" tab. | `POST /api/tutors/code` |
| **US_05** | Learner | Filter available tutors by specific university codes using a real-time search. | Easily find someone capable of teaching my specific subject. | `GET /api/search_tutors.php` |
| **US_06** | Learner | Send a direct tutoring request to a specific tutor's profile. | Seek help from someone whose expertise/teaching style I prefer. | `POST /api/requests/create` |
| **US_07** | Learner | Publish a general help request onto a specific course wall. | Let any available tutor for that subject see my needs and offer help. | `POST /api/request/createGeneral` |
| **US_08** | Learner | View a centralized list of my sent and received requests. | Keep track of upcoming sessions and pending invitations in one place. | `GET /api/search_sessions.php` |
| **US_09** | Tutor | Announce an upcoming tutoring session on a specific course wall. | Allow multiple struggling learners to see availability and join. | `POST /api/sessions/announce` |
| **US_10** | Tutor | Review incoming direct requests and accept or decline them. | Effectively manage my workload and academic schedule. | `POST /api/requests/{id}/status` |
| **US_11** | Tutor | Specify time, meeting mode, and location when accepting/posting a session. | Clearly establish logistical parameters for the session. | `PUT /api/sessions/{id}/logistics` |
