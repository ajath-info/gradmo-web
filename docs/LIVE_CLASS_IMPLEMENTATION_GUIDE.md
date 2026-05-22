# LIVE CLASS IMPLEMENTATION GUIDE
## Teacher-Controlled Live Class Flow with Student Auto-Disconnect

---

## DATABASE STRUCTURE (Already in place ✅)

### batch_zoom_meetings
- `status = 1`: Meeting active, not started yet (created)
- `status = 0`: Meeting ended
- `host_joined_at`: When teacher joined (NOT NULL = class started)
- `ended_at`: When teacher ended the class

### batch_zoom_recordings
- Stores recording details when class ends
- recording_start, recording_end, play_url

### notifications
- student_id, batch_id, notification_type, msg, url

---

## IMPLEMENTATION STEPS

### STEP 1: API ENDPOINTS TO IMPLEMENT

#### A. GET /api/batch/class-status (CRITICAL - For polling)
**Purpose**: Students poll this every 2-3 seconds to check:
- Is class started? (host_joined_at set?)
- Is class ended? (status = 0?)
- Should I disconnect?

**Response**:
```json
{
  "status": "true",
  "data": {
    "classStarted": true/false,
    "classEnded": false/true,
    "hostJoinedAt": "2026-05-19 10:30:00",
    "endedAt": null,
    "studentCanJoin": true/false,
    "studentCanRejoin": true/false
  }
}
```

#### B. POST /api/batch/notify-class-ended (When teacher ends)
**Purpose**: Notify server that teacher ended class
**Updates**:
- Set status = 0 in batch_zoom_meetings
- Set ended_at = NOW()
- Create recording entry
- Send notifications to all enrolled students

#### C. POST /api/batch/student-session-status (For tracking)
**Purpose**: Log when student joins/leaves
**Params**: action (join/leave), student_id, batch_id, timestamp

---

### STEP 2: BATCH_ZOOM_MEETINGS TABLE ENHANCEMENT

Add column (if not exists):
```sql
ALTER TABLE batch_zoom_meetings 
ADD COLUMN class_status VARCHAR(32) DEFAULT 'created' AFTER status;
-- Values: 'created', 'started', 'ended'
```

---

### STEP 3: TEACHER SIDE LOGIC (batch_live_room.php)

Teacher Flow:
1. Page loads → Auto-create meeting if needed
2. Show "Start / Join Class" button
3. Teacher clicks → Joins Zoom (calls notifyServerClassStarted)
4. Show "End Class" button with warning
5. Teacher clicks "End Class" → Calls endMeetingOnServer + notifyClassEnded
6. All students auto-disconnect via polling

---

### STEP 4: STUDENT SIDE LOGIC (batch_live_room.php)

Student Flow:
1. Page loads → Fetch class status
2. If NOT started → Show "Waiting for teacher..." + disabled button
3. Poll every 2 seconds for status changes
4. When classStarted = true → Enable button
5. Student joins → Track in session
6. Continuous poll for classEnded
7. When classEnded = true → Auto leave + redirect
8. If tries to rejoin → Check if teacher ended → Block with message

---

### STEP 5: NOTIFICATION SYSTEM

When teacher starts (notifyServerClassStarted):
- Get all enrolled students: SELECT * FROM student_batchs WHERE batch_id = X
- Insert notification for each student:
  ```sql
  INSERT INTO notifications (student_id, batch_id, notification_type, msg, url, time)
  VALUES (student_id, batch_id, 'class_started', 'Class has started', '/batch/live-room?batch_id=X', NOW())
  ```

---

### STEP 6: RECORDING MANAGEMENT

When teacher ends class (notifyClassEnded):
1. Update batch_zoom_meetings: status = 0, ended_at = NOW()
2. Check for existing recording: SELECT * FROM batch_zoom_recordings WHERE batch_id = X AND live_class_id = Y
3. If recording in 'processing' status, update it
4. If no recording, create placeholder:
   ```sql
   INSERT INTO batch_zoom_recordings (batch_id, zoom_meeting_id, recording_start, recording_end, status)
   VALUES (batch_id, meeting_id, host_joined_at, ended_at, 'processing')
   ```

---

## POLLING STRATEGY (Recommended)

**Frequency**: Every 2-3 seconds for students
**Check**: classStarted, classEnded, shouldDisconnect
**On Change**: Update UI, enable/disable button, auto-leave

```javascript
// Student side - every 2 seconds
setInterval(function() {
  if (!joinStarted) {
    fetchClassStatus().then(function(status) {
      if (status.classStarted && !classWasStarted) {
        // Class just started
        showAlert('Class started! You can join now.');
        enableJoinButton();
        classWasStarted = true;
      }
      if (status.classEnded && joinStarted) {
        // Class ended while student in meeting
        autoLeaveClass();
        showAlert('Teacher ended the class.');
      }
    });
  }
}, 2000);
```

---

## ERROR HANDLING

1. **Network disconnected during class**: Next poll detects it, shows message
2. **Student page becomes stale**: Automatic refresh via polling
3. **Teacher forcefully disconnects**: status = 0, students detect via polling
4. **Database transaction fails**: Log error, retry, notify admin

---

## SECURITY CONSIDERATIONS

1. Verify student is enrolled in batch before allowing join
2. Verify teacher created/assigned to batch before allowing start/end
3. Verify meeting exists before allowing operations
4. Log all class start/end events for audit

---

## DATA FLOW DIAGRAM

```
Teacher Side:
Page Load → Auto-create meeting → Show "Start/Join" 
→ Click "Start/Join" → Join Zoom → Notify server (host_joined_at set)
→ Polling updates on server → Show "End Class"
→ Click "End Class" → Leave Zoom → Notify server (status=0, ended_at set)

Student Side:
Page Load → Check status (classStarted = false)
→ Show "Waiting..." + disabled button
→ Poll every 2s
→ Teacher joins → Poll detects classStarted = true
→ Button enables, show "Join Class"
→ Student clicks → Join Zoom
→ Continue polling every 2s
→ Teacher ends → Poll detects classEnded = true
→ Auto-leave Zoom + show "Class Ended"
```

---

## STATUS TRACKING TABLE

```
State              | host_joined_at | status | Action
------------------|----------------|--------|------------------
Created            | NULL           | 1      | Teacher can join
Started            | SET            | 1      | Students can join
Ended              | SET            | 0      | No one can join
Student can rejoin | NOT SET        | 1      | Only if not ended
```

