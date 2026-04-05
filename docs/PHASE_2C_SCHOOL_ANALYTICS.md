# Phase 2C: School Analytics Dashboard

## Tóm tắt

Phase 2C bổ sung dashboard thống kê cho nhà trường với khả năng:

- xem overview số liệu tuyển dụng
- xem biểu đồ theo thời gian và trạng thái
- drill-down vào danh sách hồ sơ theo bộ lọc

Role mặc định là user có `manage_options` hoặc capability tương đương.

## Thay đổi chính

### API

#### `GET /wp-json/dut/v1/analytics/recruitment/overview`

Trả dữ liệu tổng hợp cho dashboard:

- tổng số applications
- số unique candidates
- số jobs có ứng tuyển
- breakdown theo status
- applications theo thời gian
- top companies
- top jobs

#### `GET /wp-json/dut/v1/analytics/recruitment/applications`

Trả drill-down list với filter:

- `dateFrom`
- `dateTo`
- `companyId`
- `jobId`
- `status`
- `profileType`
- `page`
- `perPage`

### UI

Dùng page `templates/dut-dashboard.php` và app React đã có.

Dashboard gồm:

- overview cards
- charts
- filters
- drill-down table

Table cần hiển thị tối thiểu:

- candidate name
- company name
- job title
- profile type
- status
- created date
- latest update

Row click mở:

- application detail
- timeline
- CV/resume link nếu được phép

## Test Plan

- overview trả đúng số lượng applications
- breakdown theo status khớp dữ liệu thực
- filter theo thời gian hoạt động đúng
- filter theo công ty, job, status, profile type hoạt động đúng
- user không có quyền analytics nhận `403`
- dashboard render được cả dữ liệu resume và PDF CV

## Assumptions

- dashboard trường dùng role admin/staff có capability phù hợp
- chưa tạo role riêng `school_manager`
- chưa làm export CSV trong phase này
