# 📘 Software Requirements Specification (SRS)
## Product: VideoChat (LAN Business Communication App)

---

# 1. Overview

## 1.1 Purpose
VideoChat is a **LAN-based business communication application** designed for fast, secure, and minimal interaction between users through **chat and real-time audio/video calls**.

## 1.2 Product Vision
A lightweight alternative to tools like Google Meet, optimized for:
- Local Area Network (LAN)
- Internal business communication
- Minimal and efficient user experience
- Easy integration with business systems

---

# 2. Design Principles

- “More is Less” UI philosophy
- Clean, modern, and minimal interface
- Fast interaction, low latency
- Business-focused and energetic visual theme

## Theme
- Primary: Deep Blue / Indigo
- Accent: Cyan / Electric Green
- Rounded UI, soft shadows, subtle animations

---

# 3. User Types

| Role | Description |
|------|------------|
| Admin | Manages users and system |
| User | Uses chat and call features |

---

# 4. Functional Requirements

## 4.1 Public Pages

### Home (`/`)
- Conversion-focused landing page
- Sections:
  - Hero with CTA
  - Key features (3–4 max)
  - Demo preview
  - Signup prompt

### Authentication
- `/login` → User login
- `/signup` → User registration

---

## 4.2 Core Application

### Chat Hub (`/app/chat`)
- Central communication page
- Features:
  - Search users
  - Recent chats list
  - Online users indicator

### Communication Page (`/app/chat/:userId`)
Single page handling:
- Direct chat (1:1)
- Audio call
- Video call
- File sharing

#### Behavior
- Default: chat interface
- On call: video/audio UI appears in same page

---

## 4.3 Profile (`/app/profile`)
- User info (name, role)
- Avatar
- Status message

---

## 4.4 Settings (`/app/settings`)
- Account settings
- Audio/video device selection
- Notifications
- Theme (light/dark)

---

## 4.5 Admin (Optional)

### Admin Dashboard (`/app/admin`)
- System overview

### User Management (`/app/admin/users`)
- Add/remove users
- Assign roles

---

# 5. Non-Functional Requirements

## Performance
- Optimized for LAN
- Low latency communication (<100ms)

## Security
- Secure authentication
- Role-based access control

## Scalability
- Supports 10–500 users

## Reliability
- Works within LAN without internet dependency

---

# 8. UX Guidelines

- Max 3 clicks to any action
- Minimal navigation
- Context-driven UI
- Single communication page approach

---

# 9. Final URL Structure

```
/
/login
/signup

/app
/app/chat
/app/chat/:userId
/app/profile
/app/settings

/app/admin
/app/admin/users
```

---

# 10. Key Decisions

- No group chat in MVP
- No separate call page
- Chat and call unified in one page
- No separate connect page
- Minimal navigation structure

---

# 11. Future Enhancements

- Group chat
- Meeting scheduling
- AI meeting summaries
- External integrations

---

# 12. Success Metrics

- Fast onboarding
- Low latency calls
- High daily usage
- Reduced user friction

---

**End of SRS**

