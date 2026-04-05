# Phase 2A: Apply Bằng PDF CV Song Song Với Resume

## Tóm tắt

Phase 2A mở rộng flow apply để candidate có thể:

- chọn `resume` hiện có trong hệ thống
- hoặc upload trực tiếp `1 file PDF CV`

`resume` CPT vẫn được hỗ trợ, nhưng không còn là điều kiện bắt buộc để nộp hồ sơ.

## Thay đổi chính

### Backend model

Chuẩn hóa metadata trên `job_application`:

- `_dut_profile_type`: `resume` | `pdf_upload`
- `_resume_id`: chỉ có khi type là `resume`
- `_dut_cv_file_id`: attachment ID của file PDF
- `_dut_cv_file_url`: optional URL lưu sẵn để render

### API

#### `POST /wp-json/dut/v1/applications`

Chuyển sang nhận `multipart/form-data`.

Input hợp lệ:

- mode 1: `jobId`, `resumeId`, `message`
- mode 2: `jobId`, `cvFile`, `message`, optional `candidateName`

Validation:

- bắt buộc đăng nhập
- bắt buộc chỉ chọn đúng một nguồn profile: `resumeId` hoặc `cvFile`
- nếu dùng `resumeId`: resume phải thuộc current user
- nếu dùng `cvFile`: file phải là PDF hợp lệ
- chặn duplicate application theo `user + job`

### DTO

Application DTO đổi `resume` thành `profile`:

- `profile.type`
- `profile.resumeId`
- `profile.title`
- `profile.link`
- `profile.fileId`
- `profile.fileUrl`
- `profile.fileName`

### Dependency

Resume Manager đổi từ `hard required` sang `optional`.

Rule:

- apply bằng `resumeId` thì Resume Manager phải active
- apply bằng `cvFile` thì không phụ thuộc Resume Manager

## Theme/UI

### Job detail apply box

UI cần có 2 lựa chọn:

- `Chọn hồ sơ hệ thống`
- `Tải lên PDF CV`

Flow:

1. kiểm tra login
2. nếu chọn resume:
   - load resumes của current user
   - chọn 1 resume
3. nếu chọn PDF:
   - upload 1 file PDF
4. submit multipart đến `POST /dut/v1/applications`

UI states:

- loading resumes
- empty resumes
- uploading PDF
- submit success
- duplicate error
- invalid file error

## Test Plan

- Candidate apply bằng `resumeId` thành công như cũ
- Candidate apply bằng `cvFile` PDF thành công khi không có resume
- Apply fail `422` nếu vừa gửi `resumeId` vừa gửi `cvFile`
- Apply fail `422` nếu không gửi cả hai
- Apply fail `422` nếu file không phải PDF
- Apply fail `409` nếu apply trùng cùng job
- Recruiter xem được application có `profile.type = pdf_upload`
- Candidate xem lại được hồ sơ mình đã nộp với đúng loại profile

## Assumptions

- Mỗi application chỉ gắn với một profile source
- Chỉ hỗ trợ `PDF`, chưa hỗ trợ DOC/DOCX trong phase này
- Không tự tạo “resume ảo” khi upload PDF
