# E-commerce Application Documentation

## Table of Contents
1. [Project Overview](#project-overview)
2. [System Architecture](#system-architecture)
3. [Database Schema](#database-schema)
4. [File Structure](#file-structure)
5. [Features](#features)
6. [Installation Guide](#installation-guide)
7. [User Roles & Authentication](#user-roles--authentication)
8. [API Endpoints](#api-endpoints)
9. [Frontend Components](#frontend-components)
10. [Security Considerations](#security-considerations)
11. [Known Issues](#known-issues)
12. [Future Enhancements](#future-enhancements)

## Project Overview

This is a PHP-based e-commerce web application built with a traditional server-side architecture. The application provides a complete online shopping experience with user authentication, product management, shopping cart functionality, and order processing.

### Tech Stack
- **Backend**: PHP 7.4+ with PDO
- **Database**: MySQL/MariaDB
- **Frontend**: HTML5, CSS3, JavaScript, Tailwind CSS
- **Session Management**: PHP Sessions
- **Architecture**: MVC-like structure with separation of concerns

### Key Features
- User registration and authentication
- Product catalog with categories
- Shopping cart functionality
- Order management system
- Admin panel for content management
- Responsive design
- Security best practices implemented

## System Architecture

The application follows a modular structure with clear separation between:

- **Authentication Layer**: User login/registration system
- **Data Layer**: Database interactions via PDO
- **Business Logic**: Cart operations, order processing
- **Presentation Layer**: HTML templates with embedded PHP
- **Admin Interface**: Separate admin panel for management

### Design Patterns Used
- **Front Controller**: Single entry points for different sections
- **Repository Pattern**: Database abstraction through PDO
- **Session Management**: Centralized session handling
- **Input Validation**: Sanitization and validation throughout

## Database Schema

### Tables Overview

#### Users Table
```sql
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Categories Table
```sql
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

#### Products Table
```sql
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255),
    image_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE CASCADE
);
```

#### Carts Table
```sql
CREATE TABLE carts (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

#### Cart Items Table
```sql
CREATE TABLE cart_items (
    cart_item_id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cart_id) REFERENCES carts(cart_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);
```

#### Orders Table
```sql
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','paid','shipped','delivered','cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);
```

#### Order Details Table
```sql
CREATE TABLE order_details (
    order_detail_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_each DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);
```

### Database Relationships
- **One-to-Many**: User → Orders, Category → Products, Order → Order Details
- **Many-to-Many**: Users ↔ Products (through cart_items and order_details)
- **Cascade Deletes**: Implemented to maintain referential integrity

## File Structure

```
tattet-ecommerce/
├── README.md
├── index.php                    # Main product catalog page
├── cart.php
