# Arpon MVC Framework Core

A high-performance, lightweight PHP MVC framework core designed for simplicity and speed.

## Features

- **Lightweight & Fast**: Minimal overhead for maximum performance.
- **Routing**: Clean and expressive routing engine.
- **Eloquent-like ORM**: Intuitive database interactions.
- **Dependency Injection**: Powerful IoC container.
- **Console**: Built-in CLI for scaffolding and tasks.
- **Auth & Security**: Robust authentication and encryption systems.
- **Cache & Session**: Multiple drivers for efficient data handling.
- **Mailing**: SMTP and Mailgun support out of the box.

## Requirements

- PHP 8.1 or higher
- JSON Extension
- MBString Extension
- OpenSSL Extension
- PDO Extension
- CURL Extension (for Mailgun)

## Installation

Install via Composer:

```bash
composer require arponascension1/arpon-framework
```

## Usage

Example of a basic route:

```php
use Arpon\Support\Facades\Route;

Route::get('/', function () {
    return 'Hello, Arpon!';
});
```

## Author

**Arpon** - [GitHub](https://github.com/arponascension1)

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.
