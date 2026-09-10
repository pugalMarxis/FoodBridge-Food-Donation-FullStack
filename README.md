# 🍲 FoodBridge — Food Donation & Distribution Platform

FoodBridge is a full-stack web platform that connects surplus food from donors — restaurants, households, and businesses — with people and organizations who need it. Built as a final-year HNDIT capstone project, it focuses on reducing food waste and fighting hunger in local communities through real-time coordination between donors, recipients, and volunteers.

---

## 📖 Table of Contents

- [About the Project](#about-the-project)
- [Key Features](#key-features)
- [Tech Stack](#tech-stack)
- [Project Structure](#project-structure)
- [Getting Started](#getting-started)
- [Database Setup](#database-setup)
- [Usage](#usage)
- [Roadmap](#roadmap)
- [Contributing](#contributing)
- [License](#license)
- [Contact](#contact)

---

## 📌 About the Project

Every day, large amounts of edible food go to waste while many people struggle with food insecurity. **FoodBridge** bridges that gap by giving donors a simple way to list surplus food and giving recipients and volunteers an easy way to find, claim, and deliver it — all in one platform.

The system supports three core roles:

- **Donors** — list available food donations with details and pickup location
- **Recipients** — browse and request available food
- **Volunteers** — accept delivery tasks and coordinate pickups/drop-offs
- **Admin** — oversees users, donations, requests, and platform activity

---

## ✨ Key Features

- 🔐 **User Authentication** — secure registration and login for donors, recipients, and volunteers
- 🍱 **Browse & List Food** — donors can post food donations; recipients can browse available listings
- 📩 **Request System** — recipients can request food items and track request status
- 🚚 **Delivery & Task Management** — volunteers can view and manage delivery tasks
- 🗺️ **Live Map Integration** — GPS-based map view for locating donations and coordinating pickups
- 🔔 **Notifications** — real-time alerts for donation, request, and delivery updates
- 📱 **SMS Alerts** — SMS-based registration and notifications via Twilio for users without constant internet access
- ⭐ **Ratings & Feedback** — recipients and volunteers can rate donations/deliveries
- 🧑‍💼 **Admin Dashboard** — centralized panel to manage users, donations, and reports
- 👤 **Profile & Settings Management** — users can manage their personal details and preferences

---

## 🛠️ Tech Stack

| Layer            | Technology            |
|-------------------|------------------------|
| Frontend          | HTML, CSS, JavaScript |
| Backend           | PHP                   |
| Database          | MySQL                 |
| SMS Integration   | Twilio API            |
| Maps / Geolocation| GPS-based live mapping |

---

## 📂 Project Structure

```
FoodBridge-Food-Donation-FullStack/
├── admin/            # Admin dashboard & management scripts
├── assets/           # CSS, JS, images, and static assets
├── database/         # SQL schema and database files
├── includes/         # Shared PHP includes (config, header, footer, db connection)
├── php/              # Core PHP logic / backend handlers
├── index.php         # Landing page
├── login.php         # User login
├── register.php      # User registration
├── sms-register.php  # SMS-based registration
├── sms-reply.php     # SMS reply handler (Twilio webhook)
├── browse-food.php   # Browse available food donations
├── food-details.php  # Individual donation details
├── donate.php        # Create a food donation
├── request.php       # Request food
├── help-request.php  # Request assistance
├── my-donations.php  # Donor's donation history
├── my-requests.php   # Recipient's request history
├── my-deliveries.php # Volunteer's delivery history
├── tasks.php         # Volunteer task management
├── map.php           # Live map view
├── dashboard.php     # User dashboard
├── notifications.php # Notifications center
├── ratings.php       # Ratings & feedback
├── profile.php        # User profile
├── settings.php       # Account settings
└── contact.php         # Contact page
```

---

## 🚀 Getting Started

### Prerequisites

Make sure you have the following installed:

- [XAMPP](https://www.apachefriends.org/) / WAMP / any local server with **PHP 7.4+** and **MySQL**
- A code editor (VS Code recommended)
- (Optional) A [Twilio](https://www.twilio.com/) account for SMS features

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/pugalMarxis/FoodBridge-Food-Donation-FullStack.git
   ```

2. **Move the project into your server's root directory**
   ```bash
   # Example for XAMPP
   mv FoodBridge-Food-Donation-FullStack C:/xampp/htdocs/foodbridge
   ```

3. **Start Apache and MySQL** from your XAMPP/WAMP control panel.

4. **Configure the database connection**
   Update the database credentials in the config file inside `includes/` to match your local MySQL setup.

5. **Import the database**
   See [Database Setup](#database-setup) below.

6. **Run the project**
   Open your browser and navigate to:
   ```
   http://localhost/foodbridge/index.php
   ```

---

## 🗄️ Database Setup

1. Open **phpMyAdmin** (or your preferred MySQL client).
2. Create a new database, e.g. `foodbridge_db`.
3. Import the SQL file located in the `database/` folder.
4. Update your database name/credentials in `includes/` accordingly.

---

## ▶️ Usage

1. Register as a **Donor**, **Recipient**, or **Volunteer**.
2. **Donors** list surplus food with quantity, location, and pickup time.
3. **Recipients** browse listings on the map or list view and submit requests.
4. **Volunteers** accept delivery tasks and update status as they complete pickups/drop-offs.
5. **Admins** monitor and manage all activity from the admin dashboard.

---

## 🗺️ Roadmap

- [ ] Real-time chat between donors, recipients, and volunteers
- [ ] Push notifications (web/mobile)
- [ ] Analytics dashboard for donation impact tracking
- [ ] Mobile app companion

---

## 🤝 Contributing

Contributions, issues, and feature requests are welcome!

1. Fork the project
2. Create your feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

---

## 📄 License

This project is developed for academic purposes as part of the HNDIT final-year capstone project. Feel free to fork and adapt with attribution.

---

## 📬 Contact

**Pugal** — [GitHub: @pugalMarxis](https://github.com/pugalMarxis)

Project Link: [FoodBridge-Food-Donation-FullStack](https://github.com/pugalMarxis/FoodBridge-Food-Donation-FullStack)

---

<p align="center">Made with ❤️ to fight food waste and hunger</p>
