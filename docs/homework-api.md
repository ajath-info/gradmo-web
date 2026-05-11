# Homework APIs (Web + Mobile)

Base path: `/api/batch/*`  
Auth: pass `access_token` in body (or JSON) for all endpoints.

## 1) Homework List

**Endpoint**: `POST /api/batch/homework-list`  
**Roles**: student, teacher

### Request (JSON)
```json
{
  "access_token": "TOKEN",
  "batch_id": 12,
  "page": 1,
  "limit": 20
}
```

### Response (example)
```json
{
  "status": "true",
  "msg": "Fetched successfully",
  "homeWork": [
    {
      "id": "44",
      "adminId": "2",
      "teacherId": "18",
      "date": "2026-05-07",
      "subjectId": "5",
      "batchId": "12",
      "description": "Solve chapter 3 worksheet",
      "addedAt": "2026-05-07 09:30:10",
      "name": "Rohit Sir",
      "subjectName": "Mathematics"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 1,
    "total_pages": 1
  }
}
```

---

## 2) Homework Details

**Endpoint**: `POST /api/batch/homework-details`  
**Roles**: student, teacher

### Request
```json
{
  "access_token": "TOKEN",
  "homework_id": 44
}
```

### Response
```json
{
  "status": "true",
  "msg": "Success",
  "data": {
    "homework": {
      "id": "44",
      "teacherId": "18",
      "subjectId": "5",
      "batchId": "12",
      "date": "2026-05-07",
      "description": "Solve chapter 3 worksheet"
    }
  }
}
```

---

## 3) Homework Add (Teacher)

**Endpoint**: `POST /api/batch/homework-add`  
**Roles**: teacher

### Request
```json
{
  "access_token": "TOKEN",
  "batch_id": 12,
  "subject_id": 5,
  "date": "2026-05-10",
  "description": "Complete Q1 to Q15"
}
```

### Response
```json
{
  "status": "true",
  "msg": "Homework added successfully",
  "data": {
    "id": 51,
    "batchId": 12,
    "subjectId": 5,
    "date": "2026-05-10"
  }
}
```

---

## 4) Homework Edit (Teacher)

**Endpoint**: `POST /api/batch/homework-edit`  
**Roles**: teacher

### Request
```json
{
  "access_token": "TOKEN",
  "homework_id": 51,
  "description": "Complete Q1 to Q20",
  "date": "2026-05-11"
}
```

### Response
```json
{
  "status": "true",
  "msg": "Homework updated successfully",
  "data": {
    "id": 51
  }
}
```

---

## 5) Homework Delete (Teacher)

**Endpoint**: `POST /api/batch/homework-delete`  
**Roles**: teacher

### Request
```json
{
  "access_token": "TOKEN",
  "homework_id": 51
}
```

### Response
```json
{
  "status": "true",
  "msg": "Homework deleted",
  "data": {
    "id": 51
  }
}
```

---

## 6) Homework Submit (Student)

**Endpoint**: `POST /api/batch/homework-submit`  
**Roles**: student  
**Content-Type**: `multipart/form-data` when sending file.

### Request fields
- `access_token` (required)
- `homework_id` (required)
- `submission_text` (optional)
- `submission_file` (optional file upload)

At least one of `submission_text` or `submission_file` is required.

### Response
```json
{
  "status": "true",
  "msg": "Homework submitted successfully",
  "data": {
    "id": 7,
    "homework_id": 44,
    "student_id": 31,
    "submission_text": "Done and attached.",
    "attachment": "answer_250507113010.pdf",
    "attachment_url": "https://example.com/uploads/homework_submission/answer_250507113010.pdf"
  }
}
```

---

## 7) Homework Submissions List (Teacher)

**Endpoint**: `POST /api/batch/homework-submissions`  
**Roles**: teacher

### Request
```json
{
  "access_token": "TOKEN",
  "homework_id": 44,
  "page": 1,
  "limit": 20
}
```

### Response
```json
{
  "status": "true",
  "msg": "Success",
  "data": {
    "homework_id": 44,
    "submissions": [
      {
        "id": "7",
        "studentId": "31",
        "submissionText": "Done and attached.",
        "attachment": "answer_250507113010.pdf",
        "attachmentUrl": "https://example.com/uploads/homework_submission/answer_250507113010.pdf",
        "marks": "8.50",
        "remark": "Good attempt",
        "evalStatus": "1",
        "submittedAt": "2026-05-07 11:30:10"
      }
    ],
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 1,
      "total_pages": 1
    }
  }
}
```

---

## 8) Homework Evaluate (Teacher)

**Endpoint**: `POST /api/batch/homework-evaluate`  
**Roles**: teacher

### Request
```json
{
  "access_token": "TOKEN",
  "submission_id": 7,
  "marks": 8.5,
  "remark": "Good attempt",
  "eval_status": 1
}
```

### Response
```json
{
  "status": "true",
  "msg": "Submission evaluated successfully",
  "data": {
    "submission_id": 7,
    "homework_id": 44,
    "eval_status": 1
  }
}
```

> If `homeworks.max_marks` exists, API rejects marks greater than `max_marks`.

---

## 9) Student Submission History

**Endpoint**: `POST /api/batch/my-homework-submissions`  
**Roles**: student

### Request
```json
{
  "access_token": "TOKEN",
  "eval_status": 1,
  "page": 1,
  "limit": 20
}
```

### Response
```json
{
  "status": "true",
  "msg": "Success",
  "data": {
    "student_id": 31,
    "submissions": [],
    "pagination": {
      "page": 1,
      "limit": 20,
      "total": 0,
      "total_pages": 0
    }
  }
}
```

---

## 10) Homework Submission Details

**Endpoint**: `POST /api/batch/homework-submission-details`  
**Roles**: student, teacher

### Request
```json
{
  "access_token": "TOKEN",
  "submission_id": 7
}
```

### Response
```json
{
  "status": "true",
  "msg": "Success",
  "data": {
    "submission": {
      "id": "7",
      "homeworkId": "44",
      "studentId": "31",
      "teacherId": "18",
      "submissionText": "Done and attached.",
      "attachmentUrl": "https://example.com/uploads/homework_submission/answer_250507113010.pdf",
      "marks": "8.50",
      "remark": "Good attempt",
      "evalStatus": "1"
    }
  }
}
```

---

## Error format (common)
```json
{
  "status": "false",
  "msg": "Error message",
  "data": {}
}
```

## Notes
- Some legacy endpoints may still return older keys like `message`/`homeWork`; new endpoints use `status/msg/data`.
- Use `multipart/form-data` for file upload endpoints.
- Recommended file path for submissions: `uploads/homework_submission/`.
