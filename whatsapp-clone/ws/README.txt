WHATSAPP CLONE — PHP + MYSQL + JAVASCRIPT

WHAT THIS VERSION INCLUDES
- Registration and login using phone number + password
- Secure password hashing with password_hash/password_verify
- PHP sessions and logout
- CSRF protection on state-changing forms/API requests
- User search by name or phone number
- One-to-one conversations between users of this application
- Real-time-style AJAX message polling every 1.5 seconds
- Sent and read indicators
- Unread message counts
- Online/last-seen indicator
- Profile/about editing
- Profile photo upload (JPG/PNG/WebP, max 3 MB)
- Password change
- Responsive desktop/mobile layout
- MySQL relational database
- XSS-safe message rendering

IMPORTANT
This is a standalone WhatsApp-style application. It does NOT connect to the real WhatsApp network, WhatsApp accounts, or WhatsApp's private authentication system.

INSTALL WITH XAMPP
1. Copy the 'mehnn' folder into:
   C:\xampp\htdocs\

2. Start Apache and MySQL in XAMPP.

3. Open:
   http://localhost/phpmyadmin

4. Import:
   database.sql

   The SQL creates a database named whatsapp_clone.

5. Open:
   http://localhost/mehnn/

DEMO ACCOUNTS
Phone: 08000000001
Password: password123

Phone: 08000000002
Password: password123

Open two browser windows (or one normal window + incognito), log in as the two accounts, and send messages between them.

DATABASE SETTINGS
Default config in simplechat.php:
Host: localhost
User: root
Password: empty
Database: whatsapp_clone

If your XAMPP MySQL root password is different, edit those four properties.

SECURITY / PRODUCTION NOTES
- Use HTTPS in production.
- Put database credentials in environment variables outside the web root.
- Add rate limiting and account lockout for login.
- For true large-scale real-time messaging, replace polling with WebSockets/SSE.
- For real end-to-end encryption, implement a vetted E2EE protocol; this project does not claim to implement WhatsApp's Signal protocol.
- Add moderation, abuse reporting, attachment scanning and stronger session/device management before public deployment.
