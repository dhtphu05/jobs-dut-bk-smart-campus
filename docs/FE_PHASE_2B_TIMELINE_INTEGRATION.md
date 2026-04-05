# FE Integration: Phase 2B Recruitment Timeline

Tài liệu này dành riêng cho team frontend/theme implement phase 2B của `dut-recruitment`.

Phase 2B tập trung vào:

- candidate xem timeline tuyển dụng của hồ sơ mình đã nộp
- recruiter cập nhật trạng thái kèm note
- recruiter thêm note public/internal
- recruiter sửa note đã tạo

Phase này chưa có chat hai chiều.

## 1. Những gì backend đã có

Backend đã expose các endpoint timeline và đã trả thêm dữ liệu timeline ngay trong application DTO.

Candidate:

- chỉ thấy `created`, `status_changed`, và `note` có `visibility = public`

Recruiter/Admin:

- thấy toàn bộ timeline
- có thể tạo note
- có thể sửa note
- không thể xóa note

## 2. API wrappers frontend cần dùng

Trong theme, các wrapper sau đã có sẵn ở:

[api.js](/Users/mac/Local%20Sites/dut-jobs-local/app/public/wp-content/themes/cariera/src/lib/api.js)

### `getRecruitmentApplicationTimeline(applicationId)`

```js
const response = await getRecruitmentApplicationTimeline(applicationId);
```

Response:

```json
{
  "items": [],
  "total": 0,
  "viewerRole": "candidate"
}
```

### `createRecruitmentTimelineNote(applicationId, payload)`

```js
await createRecruitmentTimelineNote(applicationId, {
  note: 'Cần follow up sau buổi phỏng vấn',
  visibility: 'internal',
});
```

### `updateRecruitmentTimelineNote(applicationId, eventId, payload)`

```js
await updateRecruitmentTimelineNote(applicationId, eventId, {
  note: 'Ứng viên xác nhận tham gia phỏng vấn',
  visibility: 'public',
});
```

### `updateRecruitmentApplicationStatus(applicationId, status, extraPayload)`

```js
await updateRecruitmentApplicationStatus(applicationId, 'interviewed', {
  note: 'Mời phỏng vấn vòng 1',
  visibility: 'public',
});
```

## 3. Application DTO mới frontend cần dùng

Ngoài các field cũ, mỗi application item giờ có thêm:

```json
{
  "latestUpdate": {
    "id": "evt_01",
    "type": "status_changed",
    "status": "interviewed",
    "statusLabel": "Interviewed",
    "note": "Mời phỏng vấn vòng 1",
    "visibility": "public",
    "createdAt": "2026-04-05T14:30:00",
    "updatedAt": "2026-04-05T14:30:00",
    "edited": false
  },
  "timelineSummary": {
    "total": 3,
    "publicCount": 2,
    "internalCount": 1
  }
}
```

Ý nghĩa:

- `latestUpdate` là event timeline gần nhất mà viewer hiện tại được phép thấy
- `timelineSummary` đã được filter theo quyền hiện tại của viewer

Candidate sẽ không bao giờ nhận internal note trong `latestUpdate` hay `timelineSummary`.

## 4. Timeline event DTO

`GET /applications/{id}/timeline` trả `items` theo shape:

```json
{
  "id": "evt_01",
  "type": "note",
  "status": "interviewed",
  "statusLabel": "Interviewed",
  "note": "Ứng viên xác nhận tham gia phỏng vấn",
  "visibility": "public",
  "edited": true,
  "createdAt": "2026-04-05T14:30:00",
  "updatedAt": "2026-04-05T16:00:00",
  "actor": {
    "userId": 12,
    "name": "HR Company",
    "role": "recruiter"
  }
}
```

### `type`

- `created`
- `status_changed`
- `note`

### `visibility`

- `public`: candidate nhìn thấy
- `internal`: chỉ recruiter/admin nhìn thấy

### `edited`

- `true`: note đã được recruiter chỉnh sửa sau khi tạo
- UI nên hiển thị badge `Đã chỉnh sửa`

## 5. UI contract cho candidate dashboard

Trang hiện có:

[dut-my-applications.php](/Users/mac/Local%20Sites/dut-jobs-local/app/public/wp-content/themes/cariera/templates/dut-my-applications.php)

App React:

[DashboardApp.jsx](/Users/mac/Local%20Sites/dut-jobs-local/app/public/wp-content/themes/cariera/src/dashboard/DashboardApp.jsx)

Mỗi application card của candidate nên hiển thị:

- job title
- application status
- createdAt
- profile source + link
- message nếu có
- `latestUpdate`
- button `Xem tiến trình`

Khi mở timeline:

- load `GET /applications/{id}/timeline`
- render danh sách event theo thứ tự backend trả về
- candidate chỉ thấy public timeline

Candidate không có action:

- không thêm note
- không sửa note
- không đổi trạng thái

## 6. UI contract cho recruiter dashboard

Trang hiện có:

[dut-job-applications.php](/Users/mac/Local%20Sites/dut-jobs-local/app/public/wp-content/themes/cariera/templates/dut-job-applications.php)

Recruiter card nên có:

- candidate name
- candidate email
- profile source + link
- current status badge
- `latestUpdate`
- dropdown đổi status
- textarea note khi đổi status
- select `public/internal`
- button `Lưu ghi chú`
- button `Xem tiến trình`

### Hành vi submit status

Gọi:

```js
await updateRecruitmentApplicationStatus(applicationId, status, {
  note,
  visibility,
});
```

Nếu `note` rỗng:

- backend vẫn tạo event `status_changed`
- frontend không cần chặn

Nếu `note` có giá trị:

- `visibility` mặc định UI nên để `public`

### Hành vi add note riêng

Trong timeline modal:

```js
await createRecruitmentTimelineNote(applicationId, {
  note,
  visibility,
});
```

Default UI nên để:

- `internal`

### Hành vi edit note

Chỉ cho event `type = note`.

```js
await updateRecruitmentTimelineNote(applicationId, eventId, {
  note,
  visibility,
});
```

Không render nút sửa cho:

- `created`
- `status_changed`

## 7. State management gợi ý

### Dashboard list

Khi load list applications:

- dùng `getJobRecruitmentApplications(jobId)` cho recruiter
- dùng `getMyRecruitmentApplications()` cho candidate

### Timeline modal

State tối thiểu:

- `timelineOpen`
- `timelineApplication`
- `timelineEvents`
- `timelineLoading`
- `timelineError`
- `timelineNoteDraft`
- `timelineNoteVisibility`
- `editingEventId`
- `editingEventDraft`
- `savingEventId`

### Refresh strategy

Sau các action:

- update status
- create note
- edit note

Frontend nên:

1. reload application list
2. cập nhật `timelineApplication`
3. reload timeline nếu modal đang mở

Lý do:

- `latestUpdate`
- `timelineSummary`
- status badge

đều phụ thuộc vào backend response mới nhất.

## 8. Error handling

Frontend tiếp tục dùng:

[recruitment.js](/Users/mac/Local%20Sites/dut-jobs-local/app/public/wp-content/themes/cariera/src/lib/recruitment.js)

Các lỗi cần chú ý:

- `401`: chưa đăng nhập
- `403`: không có quyền xem/sửa timeline
- `404`: application hoặc note không tồn tại
- `422`: payload note/status/visibility không hợp lệ
- `503`: plugin/dependency chưa sẵn sàng

Candidate gặp `403` khi cố mở internal-only resource không nên xảy ra qua UI đúng.

## 9. UX defaults nên giữ

- recruiter tạo note riêng: default `internal`
- recruiter đổi status kèm note: default `public`
- timeline dùng modal hoặc drawer, không tạo page detail riêng
- event `edited = true` hiển thị badge nhỏ
- `visibility = internal` nên có visual treatment muted hơn `public`

## 10. Test checklist cho FE

- candidate thấy `latestUpdate` trên card
- candidate mở timeline chỉ thấy public events
- recruiter đổi status kèm note public thành công
- recruiter đổi status không note vẫn thành công
- recruiter thêm note internal thành công
- recruiter sửa note và thấy badge `Đã chỉnh sửa`
- recruiter không thấy action sửa ở event `created` và `status_changed`
- sau mutation, list card cập nhật đúng `status`, `latestUpdate`, `timelineSummary`
- link profile PDF/resume vẫn mở đúng sau phase 2B
