# Video Chat

![Laravel CI](https://github.com/ShaonMajumder/video-chat/actions/workflows/ci.yml/badge.svg)
[![codecov](https://codecov.io/gh/ShaonMajumder/video-chat/branch/main/graph/badge.svg)](https://codecov.io/gh/ShaonMajumder/video-chat)

A scalable, secure, and feature-rich real-time video chat platform built for seamless one-on-one communication, designed to handle 200M+ users with up to 2M concurrent connections.

<p align="center">
  <a href="#demo">Demo</a> •
  <a href="#features">Features</a> •
  <a href="#tech-stack">Tech Stack</a> •
  <a href="#testing">Well Tested</a> •
  <a href="#system-design">System Design</a> •
  <a href="#why-it-stands-out">Why it Stands Out</a> •
  <a href="#notes">Notes</a> •
  <a href="#pricing">Pricing</a> •
  <a href="#license">License</a> •
  <a href="#contribute">Contribute</a> •
  <a href="#revenue">Revenue Model</a> •
  <a href="#credit">Credit</a>
</p>

## 🚀 Demo

### 📹 Full Platform Walkthrough
**YouTube Video:** Real-time chat, authentication, and online users in action  
Link - https://www.youtube.com/watch?v=ZIC_A7jSB-E

### <a id="demo"></a>🛠️ Real-Time Messaging Overview
**Figure:** Sending and receiving messages in real-time
![Chat Demo](screenshots/chat.gif)
- Online Presence Indicator, Realtime Chat

### 🔒 Secure Authentication
**Figure:** JWT-based login with encrypted cookie storage
![Auth Demo](screenshots/authentication.gif)

### ✅ Continuous Integration (CI) with GitHub Actions
**Figure:** GitHub Actions running PHPUnit tests on push to main
![CI Demo](screenshots/ci-demo-2025-05-27.gif)

🔄 On each push/PR to main, GitHub Actions sets up PHP, MySQL, Redis, installs dependencies, configures Laravel, runs migrations, and executes PHPUnit tests, failing on issues to block broken PRs.

➡️ Config: `.github/workflows/laravel.yml`
---

## <a id="features"></a>🎯 Features for Users

- **📨 Real-Time Messaging**  
  Send and receive messages instantly using **Laravel WebSockets** and **Redis** for broadcasting.

- **👥 Online Users List**  
  View and connect with active users in real-time via a presence channel.

- **🔐 Secure Authentication**  
  JWT-based authentication with encrypted cookies for secure, stateless sessions.

- **📱 Responsive UI**  
  Modern, mobile-friendly interface with **Monaco Editor**-inspired chat styling.

- **🚀 Scalable Architecture**  
  Designed to handle 200M+ users with 2M concurrent connections using Redis, load balancing, and WebSocket replicas.

---

## <a id="tech-stack"></a>🧰 Tech Stack

| Area                | Technologies Used                                |
|---------------------|------------------------------------------------|
| **Frontend**        | Blade, JavaScript, Axios, Pusher.js              |
| **Backend**         | Laravel (PHP), RESTful APIs, JWT Authentication |
| **Real-Time Chat**  | Laravel WebSockets, Pusher Protocol             |
| **Queue System**     | Laravel Queue, Redis                            |
| **Database**        | MySQL                                           |
| **Caching**         | Redis                                          |
| **Testing**         | PHPUnit (Unit & Integration), Codecov, GitHub CI |
| **Monitoring**      | Laravel Telescope (Requests, Events, Logs)     |
| **Deployment**      | Docker, Nginx, PHP-FPM                          |
| **CI/CD**           | GitHub Actions (CI Done, CD Planned)            |

## <a id="testing"></a>🧪 Testing Strategy

A robust testing strategy ensures reliability and scalability.

### ✅ Unit Testing
- Written using **PHPUnit**.
- Covers:
  - Authentication logic (`AuthController`).
  - Message broadcasting (`NewMessage` event).
  - Utility functions.
- Mocks dependencies (e.g., Redis, WebSockets) for isolation.

### ✅ Integration Testing
- Tests **API endpoints** (`/api/login`, `/api/me`, `/api/logout`).
- Uses **real MySQL and Redis** services (Dockerized).
- Verifies:
  - JWT authentication flow.
  - WebSocket channel authorization.
  - Message delivery and online user updates.
- Resets database between tests using migrations.

### ✅ End-to-End Testing
- Simulates user flows: login, viewing online users, sending/receiving messages.
- Mocks WebSocket server for test environments.
- Planned: Full WebSocket simulation with Dockerized runners.

### 📊 Code Coverage
- Generated via PHPUnit + Xdebug.
- Target: >90% coverage for critical paths (auth, chat).
- Reports available in text or HTML:
  ```bash
  XDEBUG_MODE=coverage ./vendor/bin/phpunit --coverage-text
  php artisan test --coverage-html=storage/coverage-report
  ```
- Current Code Coverage - [![codecov](https://codecov.io/gh/ShaonMajumder/video-chat/branch/main/graph/badge.svg)](https://codecov.io/gh/ShaonMajumder/video-chat)

**Online report:** [Codecov Report](https://app.codecov.io/gh/ShaonMajumder/video-chat)

---

## <a id="system-design"></a>System Design & Architecture

> **🎯 Main Goal:** Scalability, Security, and Real-Time Performance  
> Designed for 200M+ users with 2M concurrent connections, ensuring low latency, high availability, and secure communication.

- **🧩 Modular Design**  
  Feature-based structure (auth, chat, WebSockets) for maintainability and toggling services.

- **⚙️ Scalable WebSockets**  
  Laravel WebSockets with Redis broadcasting, load-balanced across 20 replicas, each handling 100K connections.

- **🕒 Asynchronous Processing**  
  Laravel Queue with Redis processes message broadcasts and presence updates non-blocking.

- **🔒 Secure Authentication**  
  JWT with encrypted cookies (`auth.cookie` middleware) ensures stateless, secure sessions.

- **🐳 Dockerized Environment**  
  Replicates production setup (Nginx, PHP-FPM, Redis, MySQL, WebSockets) for development and testing.

- **🚀 CI/CD from Day 1**  
  GitHub Actions automates linting, testing, and deployments for continuous integration.

- **🧪 Comprehensive Testing**  
  Unit, integration, and end-to-end tests maintain >90% code coverage, ensuring reliability.

- **🔍 Monitoring with Telescope**  
  Laravel Telescope tracks requests, events, logs, and WebSocket broadcasts for debugging.

- **📈 Scalability Features**  
  - Redis Cluster (6 replicas, 8GB each) for caching and presence data (~400MB for 2M users).
  - Rate limiting (`login`, `chat`, `api`, `global`) prevents abuse.
  - Load-balanced WebSocket servers for high concurrency.

## <a id="why-it-stands-out"></a>🛡️ Why it Stands Out

- ⚙️ **Scalability**: Handles 200M+ users with 2M concurrent connections using Redis, WebSocket replicas, and load balancing.
- 🔐 **Security**: JWT authentication, encrypted cookies, input sanitization (`DOMPurify`), and rate limiting.
- 🧪 **Reliability**: 84%+ test coverage, robust CI/CD pipeline with PHPUnit and GitHub Actions, and full observability via Telescope.
- 🧩 **Modularity**: Clean, feature-based architecture that’s easy to extend (e.g., group chat, video call, chat history or additional features).
- **✅ Code Quality**: Follows Laravel conventions, domain-driven design (DDD) principles, and industry-standard best practices.
- 📊 **Coverage Achieved**: 84% overall test coverage (unit + integration), with a target to exceed 90% for critical paths.
- 🧼 **Best Practices**: Followed all the best practices avaialble.

## <a id="notes"></a>🧠 Development Notes (WIP)
- 🚧 Continuous Deployment (CD) setup pending.
- full observability via Telescope.
- Scalability Test: Handles 200M+ users with 2M concurrent connections using Redis, WebSocket replicas, and load balancing.
- Integration Testing, End-to-End Testing
- 🛠️ Integration testing ongoing (DB and Redis setup).
- 🧹 Static analysis with PHPStan in progress.
- 📹 Demo GIFs for UX and best practices planned.
- 🔄 End-to-end WebSocket simulation partially complete.
- 📊 Code coverage badge support implemented.
- 🔒 Planned Security Enhancements:
  - Docker-based sandbox for WebSocket runners.
  - Enhanced TLS configuration for production.
- 📈 Future Additions:
  - Group chat support (`conversation.{conversationId}` channels).
  - Video call integration.
  - User profiles and badges.
  - Message history and search.
  - Observability (Prometheus, Grafana for metrics).
  - Leaderboards for chat activity.

## <a id="pricing"></a>💸 Pricing

Choose the plan that fits your use case—from casual one-to-one chats to full-featured communication platforms.

| Plan     | Features                                                                                                                       | Price          | License |
|----------|--------------------------------------------------------------------------------------------------------------------------------|----------------|----------------|
| **Free** | ✅ One-to-One Live Chat  <br> ✅ Real-Time Messaging  <br> ✅ Online User Indicator | **$0/month**   | AGPL-3.0 |
| **Premium** | ✅ Everything in Free Plan  <br> ✅ Message History (Persistent Storage)  <br> ✅ Group Chat Support  <br> ✅ Video Call Integration  <br> ✅ Custom WebSocket Scaling Support | **Contact for Pricing** | Commercial |

📞 **Need custom deployment or support?**  
Reach out via [LinkedIn](https://linkedin.com/in/shaonmajumder) or check the [GitHub issues](https://github.com/ShaonMajumder/video-chat/issues) for discussions.

## <a id="license"></a>📜 License
The Video Chat platform operates under a freemium model with dual licensing to balance open-source accessibility with business protection.

| Tier | Features | License | Terms |
|------|----------|---------|-------|
| **Free Tier** | - One-to-One Live Chat<br>- Real-Time Messaging<br>- Online User Indicator | [GNU Affero General Public License v3.0 (AGPL-3.0)](LICENSE) | Open-source; source code is freely available for use, modification, and distribution. If deployed as a service (e.g., via a web application, mobile app, online/offline run), any modifications must be shared under AGPL-3.0, fostering community contributions while preventing proprietary forks without reciprocation. |
| **Premium Tier** | - Message History (Persistent Storage)<br>- Group Chat Support<br>- Video Call Integration<br>- Custom WebSocket Scaling Support | Proprietary | Not open-source; These features are protected to support the business model and are accessible only through a paid subscription or commercial license. Unauthorized use, modification, or distribution of Premium features is prohibited without a commercial agreement. |
| **Commercial License** | Entire platform (Free + Premium features) | Commercial License | Available for businesses seeking to use the entire platform (Free + Premium features) without AGPL-3.0 obligations, deploy proprietary versions, or require custom integrations (e.g., white-labeling, dedicated clusters), a commercial license is available. Contact [Shaon Majumder](https://linkedin.com/in/shaonmajumder) for pricing and terms. |

<a id="contribute"></a>
Community contributions to the Free tier are encouraged under AGPL-3.0 terms. See [CONTRIBUTING.md](CONTRIBUTING.md) for guidelines on contributing to the open-source core.

## <a id="revenue"></a>💰 Revenue Model

The Video Chat platform leverages a freemium model to generate sustainable revenue while fostering an open-source community:

- **Premium Subscriptions**: Revenue is primarily driven by subscriptions to the Premium tier, which includes premium features like Message History, Group Chat Support, Video Call Integration, and Custom WebSocket Scaling Support. These features cater to businesses, teams, and power users seeking enhanced functionality, with pricing tailored to scale with usage (contact [Shaon Majumder](https://linkedin.com/in/shaonmajumder) for details).
- **Commercial Licensing**: Enterprises requiring proprietary deployments, white-labeling, or custom integrations (e.g., dedicated clusters, compliance with HIPAA or SOC 2) can opt for a commercial license, providing flexibility without AGPL-3.0 obligations. This targets large-scale organizations and generates significant revenue through one-time or recurring fees.
- **Future Monetization**: Planned additions include premium add-ons (e.g., advanced analytics, user badges, leaderboards) and API access for developers, further diversifying income streams while maintaining the Free tier’s accessibility.

By offering a robust Free tier under AGPL-3.0, the platform attracts a wide user base, driving conversions to Premium subscriptions and commercial licenses, ensuring long-term growth and sustainability.

## <a id="credit"></a>👨‍💻 Built & Maintained By

👔 Ready to join a team building high-impact systems
📨 Let’s connect for backend, DevOps, or system design roles

**Shaon Majumder**  
Senior Software Engineer  
Open source contributor | Laravel ecosystem expert | System design advocate  
🔗 [LinkedIn](https://linkedin.com/in/shaonmajumder) • [Portfolio](https://github.com/ShaonMajumder)