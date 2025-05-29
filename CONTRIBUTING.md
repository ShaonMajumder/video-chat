# Contributing to Video Chat

Thank you for your interest in contributing to the Video Chat platform! This document outlines the guidelines for contributing to the open-source Free tier, which includes One-to-One Live Chat, Real-Time Messaging, and Online User Indicator, licensed under the [GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE). Premium features (Message History, Group Chat Support, Video Call Integration, Custom WebSocket Scaling Support) are proprietary and not open for contributions. We welcome contributions to the Free tier to enhance its functionality, fix bugs, or improve performance, while ensuring the platform remains scalable, secure, and reliable.

## Table of Contents
- [Code of Conduct](#code-of-conduct)
- [What Can You Contribute?](#what-can-you-contribute)
- [How to Contribute](#how-to-contribute)
  - [Setting Up the Development Environment](#setting-up-the-development-environment)
  - [Submitting a Pull Request](#submitting-a-pull-request)
  - [Coding Standards](#coding-standards)
  - [Testing Requirements](#testing-requirements)
- [Premium Features and Commercial Licensing](#premium-features-and-commercial-licensing)
- [Contact](#contact)

## Code of Conduct
We are committed to fostering an open and inclusive community. All contributors are expected to adhere to the [Contributor Covenant Code of Conduct](https://www.contributor-covenant.org/version/2/0/code_of_conduct.html). Please report any unacceptable behavior to [Shaon Majumder](https://linkedin.com/in/shaonmajumder).

## What Can You Contribute?
The Video Chat platform operates under a freemium model, with the Free tier being open-source under AGPL-3.0. Contributions are welcome for the following Free tier features:
- **One-to-One Live Chat**: Real-time messaging using Laravel WebSockets and Redis.
- **Real-Time Messaging**: Instant message broadcasting and delivery.
- **Online User Indicator**: Real-time presence channel showing active users (up to 50 users in the Free tier).
- **Bug Fixes**: Addressing issues in the Free tier codebase (e.g., authentication, UI, WebSocket performance).
- **Performance Improvements**: Optimizations for scalability (e.g., Redis caching, WebSocket efficiency).
- **Documentation**: Enhancing README, code comments, or setup guides for the Free tier.
- **Testing**: Adding or improving unit, integration, or end-to-end tests for Free tier features.

**Premium Features Are Proprietary**: The following features are closed-source and not open for contributions:
- Message History (Persistent Storage)
- Group Chat Support
- Video Call Integration
- Custom WebSocket Scaling Support

If you’re interested in these features or custom deployments, see [Premium Features and Commercial Licensing](#premium-features-and-commercial-licensing).

## How to Contribute
Follow these steps to contribute to the Free tier of the Video Chat platform.

### Setting Up the Development Environment
1. **Fork the Repository**:
   - Fork the [Video Chat repository](https://github.com/ShaonMajumder/video-chat) on GitHub.
   - Clone your fork: `git clone https://github.com/your-username/video-chat.git`

2. **Install Dependencies**:
   - Ensure you have PHP, MySQL, Redis, and Docker installed.
   - Run:
     ```bash
     composer install
     npm install
     ```
3. **Configure Environment:**
    - Copy .env.example to .env and configure your database, Redis, and WebSocket settings.
    - Run migrations: php artisan migrate
4. **Start Development Servers:**
    - Laravel: php artisan serve
    - WebSockets: php artisan websockets:serve
    - Frontend: npm run dev
5. **Verify Setup:**
    - Ensure the Free tier features (One-to-One Live Chat, Real-Time Messaging, Online User Indicator) work locally.
    - Check the README for detailed setup instructions.

### Submitting a Pull Request
1. **Create a Branch:**
    - Create a new branch for your changes: git checkout -b feature/your-feature-name
2. **Make Changes:**
    - Focus on Free tier features only (see What Can You Contribute?).
    - Follow the Coding Standards and Testing Requirements.
3. **Commit Changes:**
    - Use clear, descriptive commit messages: git commit -m "Add feature X to One-to-One Live Chat"
    - Ensure your changes are licensed under AGPL-3.0.
4. **Run Tests:**
    - Run: XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text
    - Ensure tests pass and maintain >90% coverage for critical paths (auth, chat).
5. **Push and Create Pull Request:**
    - Push to your fork: git push origin feature/your-feature-name
    - Submit a pull request to the main branch of the Video Chat repository.
    - Include a detailed description of your changes, referencing any related issues.
6. **Review Process:**
    - Maintainers will review your pull request, checking for code quality, test coverage, and alignment with Free tier goals.
    - Address feedback promptly to ensure timely merging.

### Coding Standards
- Follow Laravel conventions and Domain-Driven Design (DDD) principles, as outlined in the README.
- Use PHPStan for static analysis (run: vendor/bin/phpstan analyse).
- Write clean, modular code with clear comments.
- Ensure compatibility with the tech stack: Laravel (PHP), MySQL, Redis, Laravel WebSockets, Blade, JavaScript, Axios, Pusher.js.
- Avoid including Premium feature code (Message History, Group Chat, Video Calls, Custom WebSocket Scaling) in contributions.

### Testing Requirements
- All contributions must include tests to maintain >90% code coverage for critical paths (auth, chat).
- Write unit tests for new functionality (e.g., AuthController, NewMessage event) using PHPUnit.
- Include integration tests for API endpoints (/api/login, /api/me, /api/logout) with real MySQL/Redis services.
- Mock dependencies (e.g., Redis, WebSockets) for isolation in unit tests.
- Run tests locally: XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text.
- Contributions failing tests or reducing coverage below 84% will not be accepted.

### Premium Features and Commercial Licensing
Premium features—Message History (Persistent Storage), Group Chat Support, Video Call Integration, and Custom WebSocket Scaling Support—are proprietary and not open for contributions. These features are available through a paid subscription or commercial license to support the platform’s business model.

- **Interested in Premium Features?** Contact Shaon Majumder for subscription details.
- **Need Custom Deployments?** A commercial license is available for businesses seeking to bypass AGPL-3.0 obligations, deploy proprietary versions, or require custom integrations (e.g., white-labeling, dedicated clusters). Reach out via LinkedIn or GitHub issues.

### Contact
For questions about contributions, open an issue on the Video Chat repository or contact Shaon Majumder for inquiries about Premium features, commercial licensing, or other opportunities.

Thank you for helping make Video Chat better for everyone!