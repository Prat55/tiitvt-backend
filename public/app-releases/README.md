# TIITVT Mobile Application (Android)

Welcome to the official documentation for the **TIITVT Mobile Application**. This application is designed to provide students and centers with a seamless interface to manage learning, tracking, and certification directly from their Android devices.

## 📱 Key Features

### 1. Unified Authentication

- **Secure Login**: Dedicated login for Students and Centers using Sanctum-based API tokens.
- **Persistent Sessions**: Mobile-optimized session management with automatic token pruning.

### 2. Student Dashboard

- **Profile Management**: View personal details, registration numbers, and enrollment status.
- **Course Overview**: Access all enrolled courses, including descriptions and durations.

### 3. Learning Management

- **Video Lectures**: Stream internal lectures or view external content directly within the app.
- **Study Materials**: Download PDFs and other documents for offline study.
- **Progress Tracking**: Monitor course categories and completion status.

### 4. Financial & Tracking

- **Payment History**: View detailed logs for down payments and installments.
- **Digital Receipts**: Download payment receipts directly to your device.

### 5. Exams & Certification

- **Exam Results**: View detailed scores, grades, and percentage history.
- **Certificate Eligibility**: Real-time check for certificate eligibility based on exam performance.
- **PDF Downloads**: Download and share certificates instantly once eligible.

---

## 🛠️ Technical Details (API Integration)

The mobile application interacts with the backend via a robust REST API.

### Authentication

- **Student Login**: `POST /api/student/login`
- **Center Login**: `POST /api/auth/login` (with `role: center`)
- **Headers**: Use `Authorization: Bearer <TOKEN>` for all authenticated requests.

### Core Endpoints

| Feature          | Endpoint                    | Method |
| :--------------- | :-------------------------- | :----- |
| Profile          | `/api/student/profile`      | `GET`  |
| Enrolled Courses | `/api/student/courses`      | `GET`  |
| Exam Results     | `/api/student/results`      | `GET`  |
| Payment Logs     | `/api/student/payment-logs` | `GET`  |
| Certificates     | `/api/student/certificates` | `GET`  |

### Video Streaming

The app supports both direct URLs and internal path-based streaming:

- **Internal Stream**: `/api/videos/stream/{base64_encoded_path}`
- **External URL**: Direct playback from providers like Vimeo or YouTube.

---

## 📥 Installation

1. **Download APK**: Download the latest release from the [Android releases directory](./android).
2. **Install**: Allow "Install from Unknown Sources" in your Android settings.
3. **Login**:
    - **Students**: Use your registered email. Default password: `12345` (unless changed).
    - **Centers**: Use your admin credentials.

---

## 📞 Support

For technical issues or app support, please contact <support@tiitvt.com>.
