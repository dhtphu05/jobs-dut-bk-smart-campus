# Phase 2 Overview: PDF CV Apply + Recruitment Timeline + School Analytics

## Tóm tắt

Phase 2 mở rộng flow tuyển dụng theo 3 hướng:

- Candidate có thể apply bằng `resume` hiện có hoặc `upload PDF CV trực tiếp`
- Recruiter và student xem được tiến trình tuyển dụng theo `timeline notes`
- Nhà trường có dashboard thống kê có `drill-down`, dùng role có quyền `manage_options` hoặc capability tương đương

Phase này vẫn giữ `job_application` là nguồn dữ liệu chính, không tạo custom table. `resume` CPT tiếp tục được hỗ trợ nhưng không còn là đường apply duy nhất.

## Định hướng triển khai

Phase 2 được tách thành 3 phase con:

1. `Phase 2A`: hỗ trợ apply bằng PDF CV song song với resume hiện có
2. `Phase 2B`: bổ sung timeline tuyển dụng và recruiter notes
3. `Phase 2C`: bổ sung dashboard thống kê cho nhà trường có drill-down

Thứ tự triển khai phải đi từ `2A -> 2B -> 2C`.

## Model dữ liệu mục tiêu

Backend `dut-recruitment` đổi từ “application luôn gắn với resume CPT” sang “application gắn với một application profile”.

Hai nguồn profile hợp lệ:

- `resume`: dùng `resumeId` như hiện tại
- `pdf_upload`: user upload 1 file PDF CV trực tiếp khi apply

Metadata chuẩn hóa trên `job_application`:

- `_dut_profile_type`: `resume` | `pdf_upload`
- `_resume_id`: chỉ có khi type là `resume`
- `_dut_cv_file_id`: attachment ID của PDF CV khi type là `pdf_upload`
- `_dut_cv_file_url`: optional denormalized URL để render nhanh
- `_candidate_user_id`
- `_candidate_email`
- `_dut_timeline`: mảng notes/timeline event của application

## API direction

Giữ namespace `/wp-json/dut/v1`, mở rộng contract như sau:

- `POST /applications`
- `GET /my-applications`
- `GET /jobs/{jobId}/applications`
- `PATCH /applications/{id}/status`
- `GET /applications/{id}/timeline`
- `POST /applications/{id}/timeline`
- `GET /analytics/recruitment/overview`
- `GET /analytics/recruitment/applications`

## DTO direction

Application DTO dùng `profile` thay cho `resume` thuần:

```json
{
  "id": 999,
  "status": "interviewed",
  "statusLabel": "Interviewed",
  "createdAt": "2026-04-02T09:00:00",
  "updatedAt": "2026-04-05T14:30:00",
  "latestUpdate": {
    "type": "status_changed",
    "status": "interviewed",
    "statusLabel": "Interviewed",
    "note": "Mời phỏng vấn vòng 1",
    "createdAt": "2026-04-05T14:30:00"
  },
  "job": {
    "id": 123,
    "title": "Frontend Intern",
    "companyId": 45,
    "companyName": "ABC Company"
  },
  "candidate": {
    "userId": 88,
    "name": "Nguyen Van A",
    "email": "a@example.com"
  },
  "profile": {
    "type": "pdf_upload",
    "resumeId": 0,
    "title": "CV Nguyen Van A",
    "link": "",
    "fileId": 777,
    "fileUrl": "https://example.com/uploads/cv-nguyen-van-a.pdf",
    "fileName": "cv-nguyen-van-a.pdf"
  },
  "message": "Em mong muốn được ứng tuyển vị trí này."
}
```

Timeline Event DTO:

```json
{
  "id": "evt_01",
  "type": "status_changed",
  "status": "interviewed",
  "statusLabel": "Interviewed",
  "note": "Mời phỏng vấn vòng 1",
  "actor": {
    "userId": 12,
    "name": "HR Company",
    "role": "recruiter"
  },
  "createdAt": "2026-04-05T14:30:00"
}
```

## Permission direction

- Candidate: create application, xem application/timeline của mình
- Recruiter: xem applications/timeline của job mình sở hữu, đổi status, thêm note
- School/Admin: xem toàn bộ analytics và drill-down

Phase này chưa làm nhắn tin hai chiều giữa recruiter và student.

## Assumptions đã chốt

- Apply flow phase tới hỗ trợ song song `resume hiện có` và `upload PDF trực tiếp`
- “Phản hồi” trong phase này là `timeline notes` từ recruiter/admin
- Dashboard nhà trường có `drill-down`
- “Nhà trường” mặc định đi qua user có `manage_options` hoặc capability tương đương
- Không tạo custom table; tiếp tục dùng `job_application` + post meta/attachment để lưu dữ liệu
