# DUT Recruitment Theme Integration

Tài liệu này dành cho team theme/React implement UI cho flow tuyển dụng mới dựa trên plugin `dut-recruitment`.

Mục tiêu của phase 1:

- Sinh viên nộp hồ sơ bằng `resume` đã có
- Sinh viên xem trạng thái ứng tuyển của mình
- Nhà tuyển dụng xem danh sách hồ sơ theo job
- Nhà tuyển dụng đổi trạng thái hồ sơ

Plugin backend đã sẵn sàng tại namespace:

- `/wp-json/dut/v1`

## 1. Auth và fetch

Các endpoint nghiệp vụ đều yêu cầu user đã đăng nhập.

Theme hiện đã có:

- `window.dutJobsData.apiUrl` cho `/wp-json/wp/v2/`
- `window.dutJobsData.nonce` cho `X-WP-Nonce`
- `window.dutJobsData.isLoggedIn`

Cho plugin mới, theme nên thêm một base URL riêng:

```php
wp_localize_script( 'dut-jobs-islands', 'dutJobsData', [
  'apiUrl'   => rest_url( 'wp/v2/' ),
  'dutApiUrl'=> rest_url( 'dut/v1/' ),
  'nonce'    => wp_create_nonce( 'wp_rest' ),
  'isLoggedIn' => is_user_logged_in(),
  'siteUrl'  => get_site_url(),
] );
```

Trong `src/lib/api.js`, thêm base fetcher cho DUT Recruitment:

```js
const { dutApiUrl = '/wp-json/dut/v1/', nonce, isLoggedIn } = window.dutJobsData ?? {};

async function dutFetch(endpoint, options = {}) {
  const url = `${dutApiUrl}${endpoint}`.replace(/([^:]\/)\/+/g, '$1');
  const headers = {
    'Content-Type': 'application/json',
    ...(isLoggedIn ? { 'X-WP-Nonce': nonce } : {}),
    ...options.headers,
  };

  const res = await fetch(url, { ...options, headers });
  const json = await res.json().catch(() => ({}));

  if (!res.ok) {
    const error = new Error(json?.message || `API Error ${res.status}`);
    error.status = res.status;
    error.payload = json;
    throw error;
  }

  return json;
}
```

Lưu ý:

- `POST` và `PATCH` phải gửi `X-WP-Nonce`
- `GET` authenticated cũng nên gửi nonce để session cookie của WP hoạt động ổn định
- UI phải xử lý lỗi `401`, `403`, `404`, `409`, `422`, `503`

## 2. Endpoints cần dùng

### `GET /wp-json/dut/v1/health`

Dùng để kiểm tra plugin và dependency.

Ví dụ response:

```json
{
  "ok": true,
  "plugin": "dut-recruitment",
  "version": "0.1.0",
  "dependencies": {
    "ok": true,
    "missing": []
  },
  "endpoints": {
    "applications": "/wp-json/dut/v1/applications",
    "myApplications": "/wp-json/dut/v1/my-applications",
    "jobApplications": "/wp-json/dut/v1/jobs/{jobId}/applications",
    "updateStatus": "/wp-json/dut/v1/applications/{id}/status"
  }
}
```

Nếu thiếu dependency, endpoint trả `503`.

### `POST /wp-json/dut/v1/applications`

Tạo hồ sơ ứng tuyển mới.

Payload:

```json
{
  "jobId": 123,
  "resumeId": 456,
  "message": "Em mong muốn được ứng tuyển vị trí này."
}
```

Rules:

- Bắt buộc đăng nhập
- `resumeId` phải thuộc user hiện tại
- mỗi user chỉ apply một lần cho một `jobId`
- backend tự lấy tên và email ứng viên từ user/resume

Success response: `201`

### `GET /wp-json/dut/v1/my-applications`

Lấy danh sách hồ sơ của sinh viên hiện tại.

Success response:

```json
{
  "items": [],
  "total": 0
}
```

### `GET /wp-json/dut/v1/jobs/{jobId}/applications`

Lấy danh sách hồ sơ cho recruiter của job đó.

Rules:

- chỉ `post_author` của `job_listing` hoặc admin được xem

Success response:

```json
{
  "items": [],
  "total": 0
}
```

### `PATCH /wp-json/dut/v1/applications/{id}/status`

Đổi trạng thái hồ sơ.

Payload:

```json
{
  "status": "interviewed"
}
```

Allowed statuses:

- `new`
- `interviewed`
- `offer`
- `hired`
- `rejected`
- `archived`

## 3. DTO contract

Tất cả endpoint nghiệp vụ trả application DTO theo cùng shape:

```json
{
  "id": 999,
  "status": "new",
  "statusLabel": "New",
  "createdAt": "2026-03-29T10:15:30",
  "job": {
    "id": 123,
    "title": "Frontend Intern"
  },
  "candidate": {
    "userId": 88,
    "name": "Nguyen Van A",
    "email": "a@example.com"
  },
  "resume": {
    "id": 456,
    "title": "Nguyen Van A",
    "link": "https://example.com/resume/nguyen-van-a/?key=..."
  },
  "message": "Em mong muốn được ứng tuyển vị trí này."
}
```

Ý nghĩa các field:

- `status`: value kỹ thuật để gửi lại vào API
- `statusLabel`: text để render UI
- `resume.link`: link share/public mà recruiter có thể mở
- `message`: cover letter hoặc nội dung sinh viên gửi khi apply

## 4. UI flows cần implement ở theme

### Candidate apply box ở trang chi tiết job

UI nên làm theo flow:

1. Kiểm tra `isLoggedIn`
2. Nếu chưa login: hiện CTA đăng nhập
3. Nếu đã login:
   - gọi `GET /wp/v2/resumes?per_page=50`
   - cho user chọn 1 resume
   - nhập message
   - submit sang `POST /dut/v1/applications`

UI states cần có:

- loading resumes
- no resumes yet
- submitting
- success
- duplicate application (`409`)
- invalid resume (`404`)

### Candidate applications page / dashboard widget

Gọi:

- `GET /dut/v1/my-applications`

Render tối thiểu:

- job title
- createdAt
- statusLabel
- resume title

Khuyến nghị mapping màu:

- `new`: neutral / blue
- `interviewed`: warning / amber
- `offer`: purple hoặc accent
- `hired`: success / green
- `rejected`: danger / red
- `archived`: muted / gray

### Recruiter applications page cho từng job

Gọi:

- `GET /dut/v1/jobs/{jobId}/applications`

Render tối thiểu:

- candidate name
- candidate email
- resume title + link
- message
- createdAt
- status badge
- status select / action menu

Khi recruiter đổi status:

- gọi `PATCH /dut/v1/applications/{id}/status`
- cập nhật item tại chỗ theo response mới

## 5. Error handling chuẩn

Theme nên đọc cả `error.status` và `error.payload`.

Các mã lỗi chính:

- `401`: chưa đăng nhập
- `403`: không có quyền xem hoặc đổi trạng thái
- `404`: job/application/resume không tồn tại hoặc không thuộc quyền
- `409`: đã apply job này rồi
- `422`: payload thiếu hoặc status không hợp lệ
- `503`: plugin phụ thuộc chưa đủ

Gợi ý message UI:

- `401`: "Bạn cần đăng nhập để sử dụng tính năng ứng tuyển."
- `409`: "Bạn đã ứng tuyển công việc này rồi."
- `503`: "Hệ thống tuyển dụng đang tạm thời chưa sẵn sàng."

## 6. Hàm JS đề xuất cho `src/lib/api.js`

```js
export async function createRecruitmentApplication(payload) {
  return dutFetch('applications', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function getMyRecruitmentApplications() {
  return dutFetch('my-applications', {
    method: 'GET',
  });
}

export async function getJobRecruitmentApplications(jobId) {
  return dutFetch(`jobs/${jobId}/applications`, {
    method: 'GET',
  });
}

export async function updateRecruitmentApplicationStatus(applicationId, status) {
  return dutFetch(`applications/${applicationId}/status`, {
    method: 'PATCH',
    body: JSON.stringify({ status }),
  });
}
```

## 7. Phạm vi phase 1 chưa làm

Theme team không nên assume các tính năng sau đã có:

- pagination cho danh sách applications
- filter/search recruiter side
- application notes
- rating ứng viên
- audit log / timeline status changes
- company-level recruiter permissions
- apply bằng file upload rời không qua `resume`

Nếu UI cần các capability này, phải mở phase tiếp theo.
