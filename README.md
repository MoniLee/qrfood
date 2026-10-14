# QRFood — Velvet Plate Restaurant Ordering System

QRFood is a QR-code-based table ordering solution. Velvet Plate is its Node.js/Express restaurant ordering and management app with EJS server-rendered pages, the existing browser UI, and MySQL/MariaDB persistence. The project can be edited and run from any folder; it does **not** need to be copied into XAMPP `htdocs`, and PHP/Apache are not used.

## Requirements

- Node.js 18 or newer and npm
- XAMPP (or another MySQL/MariaDB server)
- A modern browser

## Run locally with XAMPP

1. Start **MySQL** in the XAMPP Control Panel. Apache is not required.
2. Create the `restaurant_system` database and import `database/schema.sql`. For example, use phpMyAdmin or the MySQL command line. The schema creates and seeds the tables and demo staff accounts.
3. In the project directory, copy `.env.example` to `.env` if you need to change the defaults. The defaults target the local XAMPP database at `127.0.0.1:3306`, database `restaurant_system`, user `root`, and an empty password.
4. Install packages and start Express:

   ```powershell
   npm install
   npm start
   ```

5. Open <http://127.0.0.1:3000/>.

The optional delivery-address and service-zone features use tables not included in the base schema. Import `database/location_schema.sql` after `database/schema.sql` to enable them. If those tables are absent, their APIs return a setup message instead of crashing the server.

## Guest ordering with one shared QR code

- Start Node.js and XAMPP MySQL, then sign in as the admin.
- Open **Order QR** from the admin navigation and print the QR code.
- Customers scan it to open the public menu, select dine-in or delivery, add items, provide their details, and place an order without registering or signing in. Dine-in customers choose an available table.
- For local testing, phones must use the same Wi-Fi as the computer running Node.js. Allow Node.js through Windows Firewall if prompted. The server listens on all interfaces and chooses a private LAN address for the QR.
- Set `QR_PUBLIC_URL` in `.env` if automatic LAN address selection is wrong, or when the deployed site has a public URL, e.g. `https://restaurant.example.com`. Public use outside the local Wi-Fi requires the app to be reachable at that URL.

## Configuration

| Variable | Default | Purpose |
| --- | --- | --- |
| `HOST` | `0.0.0.0` | Interface for the Express server; required for phone access on the same LAN |
| `PORT` | `3000` | HTTP port |
| `BASE_URL` | empty | Optional path prefix when deployed under a URL subpath |
| `QR_PUBLIC_URL` | auto-detected local network origin | Origin encoded in the shared ordering QR |
| `DB_HOST` | `127.0.0.1` | MySQL/MariaDB host |
| `DB_PORT` | `3306` | MySQL/MariaDB port |
| `DB_NAME` | `restaurant_system` | Database name |
| `DB_USER` | `root` | Database user |
| `DB_PASS` | empty | Database password |
| `GST_DEFAULT_RATE` | `18` | GST percentage applied to new orders |
| `MAX_UPLOAD_BYTES` | `2097152` | Maximum uploaded menu image size |
| `SESSION_SECRET` | random per server start in development | Express session signing secret; required in production and should be long and random |
| `NODE_ENV` | `development` | Set to `production` to use secure session cookies (requires HTTPS) |

Do not commit `.env`; it contains machine-specific configuration. Change the demo passwords and use a dedicated least-privilege database account before deployment.

## Demo accounts

All seeded demo accounts use the password **`password`**:

| Email | Role |
| --- | --- |
| `admin@velvetplate.com` | Admin dashboard, POS, kitchen, menu, tables, and orders |
| `kitchen@velvetplate.com` | Kitchen board |
| `staff@velvetplate.com` | POS, orders, and tables |

Customers can register from the home page. Passwords are stored as bcrypt hashes; the Node server verifies the existing hashes in the SQL seed data.

## Application structure

```text
backend/server.js          Express app and API routes
backend/config/database.js XAMPP MySQL connection pool
backend/controllers/       QR page controller and backend page logic
backend/routes/            Express route modules
frontend/views/             EJS pages and shared layout partials
frontend/css/               Existing stylesheet
frontend/js/                Existing browser application and API helpers
frontend/assets/            Existing images and menu uploads
database/schema.sql         MySQL/MariaDB schema and demo seed data
database/location_schema.sql Optional address and delivery-zone schema
legacy-php/                 Archived PHP implementation; not used by npm start
```

The app retains the existing `/api/...` endpoints used by the frontend, including menu/category and upload management, tables, order placement/status, kitchen, dashboard statistics, location/address, and delivery zones. File uploads are size- and image-signature-checked and stored in `frontend/assets/uploads/menu/`.

`frontend/ts/srms-api.ts` is the optional TypeScript reference; the browser loads `frontend/js/srms-api.js`.
