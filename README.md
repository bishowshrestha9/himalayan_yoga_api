# himalayan_yogini_api
## Overview
The Himalayan Yogini Yoga Service Platform backend powers a web-based yoga booking and content system with partial advance payments, lead tracking, and SEO-friendly content management. It exposes APIs (or web routes) for service browsing, bookings, payments, blogs, reviews, and admin/editor dashboards.​

Core capabilities:

Yoga service listing and detailed service pages

Online booking with 25% advance payment using 2Checkout

Lead management for all booking submissions

Role-based access control for Admin and Editor

Blog and review management with approval workflows

SEO content management for major pages

## Tech Stack
Language: PHP (Laravel) 

Database: MySQL (relational)

Payment: 2Checkout API (Verifone) for card/online payments​

Notifications: Email (SMTP/API) and optional SMS gateway

## Features
Public endpoints for services, blogs, reviews, and booking submission

Secure admin dashboard for managing services, bookings, content, offers, and SEO

Editor dashboard with restricted permissions for blogs and reviews only

Lead-centric booking workflow (Pending → Approved/Rejected)

Partial payment (25%) enforcement and refund workflow for rejected bookings

SEO metadata per page (title, description, schema JSON, slug)
