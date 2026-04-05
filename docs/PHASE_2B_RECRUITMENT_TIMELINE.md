# Phase 2B: Recruitment Timeline Và Recruiter Notes

## Tóm tắt

Phase 2B bổ sung khả năng theo dõi tiến trình tuyển dụng theo timeline để:

- recruiter cập nhật trạng thái kèm ghi chú
- recruiter thêm note riêng mà không đổi trạng thái
- student xem được lịch sử tiến trình của hồ sơ

Phase này chưa làm chat hai chiều.

## Thay đổi chính

### Data model

Lưu timeline trên `job_application` bằng meta:

- `_dut_timeline`

Mỗi event có shape:

- `id`
- `type`: `created` | `status_changed` | `note`
- `status`
- `statusLabel`
- `note`
- `actor`
- `createdAt`

### API

#### `PATCH /wp-json/dut/v1/applications/{id}/status`

Input:

- `status`
- optional `note`

Behavior:

- update `post_status`
- append timeline event loại `status_changed`
- nếu có `note`, ghi kèm cùng event

#### `GET /wp-json/dut/v1/applications/{id}/timeline`

Permission:

- candidate owner
- recruiter của job cha
- admin/school

Response:

- danh sách timeline events theo thứ tự cũ nhất trước

#### `POST /wp-json/dut/v1/applications/{id}/timeline`

Permission:

- recruiter/admin

Behavior:

- thêm note thủ công
- không đổi trạng thái application

## UI

### Candidate dashboard

Mỗi application item cần có:

- status hiện tại
- latest update
- button xem timeline

Timeline hiển thị:

- created event
- status changes
- recruiter notes

### Recruiter dashboard

Mỗi application item cần có:

- current status
- action đổi status
- form note nhanh
- drawer/modal timeline

## Test Plan

- tạo application mới thì có event `created`
- đổi status có note thì append event `status_changed`
- thêm note riêng thì append event `note`
- candidate xem được timeline của application mình
- candidate không thêm note được
- recruiter không xem được timeline của application ngoài job mình sở hữu

## Assumptions

- timeline chỉ là recruiter/admin to student
- student chỉ đọc trong phase này
- note không thay thế cho hệ thống messaging
