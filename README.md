# himalayan_yoga_api
## Overview
The Himalayan Yoga API backend powers a web-based yoga booking and content system with partial advance payments, lead tracking, and SEO-friendly content management. It exposes APIs for service browsing, bookings, payments, blogs, reviews, and admin/editor dashboards.

Core capabilities:

- Yoga service listing and detailed service pages
- Online booking with 25% advance payment using 2Checkout
- Lead management for all booking submissions
- Role-based access control for Admin and Editor
- Blog and review management with approval workflows
- SEO content management for major pages

## Tech Stack
- **Language**: PHP (Laravel 12)
- **Database**: MySQL (relational)
- **Authentication**: Laravel Sanctum
- **API Documentation**: L5-Swagger (OpenAPI/Swagger)
- **Payment**: 2Checkout API (Verifone) for card/online payments
- **Notifications**: Email (SMTP/API) and optional SMS gateway

## Features
- Public endpoints for services, blogs, reviews, and booking submission
- Secure admin dashboard for managing services, bookings, content, offers, and SEO
- Editor dashboard with restricted permissions for blogs and reviews only
- Lead-centric booking workflow (Pending → Approved/Rejected)
- Partial payment (25%) enforcement and refund workflow for rejected bookings
- SEO metadata per page (title, description, schema JSON, slug)
- RESTful API with comprehensive Swagger documentation



## Installation

1. Clone the repository:
```bash
git clone https://github.com/bishowshrestha9/himalayan_yoga_api.git
cd himalayan_yoga_api
```

2. Install dependencies:
```bash
composer install
npm install
```

3. Set up environment:
```bash
cp .env.example .env
php artisan key:generate
```

4. Configure database in `.env` file

5. Run migrations:
```bash
php artisan migrate
```

6. Generate Swagger documentation:
```bash
php artisan l5-swagger:generate
```

## API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/logout` - User logout (requires authentication)

## License
The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
