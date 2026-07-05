<?php
require_once 'db_connect.php';
require_once 'role_helpers.php';
session_start();
$isHr = isset($_SESSION['user_id']) && (hasRole('Hr') || hasRole('HR'));
$userBioId = $_SESSION['bio_id'] ?? '';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Time In / Time Out</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      #camera-floating-container {
        position: fixed;
        top: 20px;
        left: 20px;
        z-index: 1000;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        padding: 12px;
        width: 260px;
        max-width: 90vw;
      }
      #camera {
        border-radius: 6px;
        width: 100% !important;
        height: auto;
      }
      .attendance-main-container {
        margin-left: 182px;
        margin-top: -27px;
      }
      .photo-preview {
        width: 100%;
        max-width: 200px;
        margin-top: 10px;
        border-radius: 6px;
        display: none;
        border: 2px solid green;
      }
      .camera-status {
        font-size: 11px;
        color: #666;
        margin-top: 5px;
      }
    </style>
  </head>
  <body class="bg-light">
    <div id="camera-floating-container">
      <label class="form-label">📷 Live Camera</label>
      <div id="camera-container" class="mb-2">
        <video id="camera" autoplay playsinline muted></video>
        <canvas id="snapshot" style="display:none;"></canvas>
      </div>
      <img id="photo-preview" class="photo-preview" alt="Preview">
      <div class="camera-status">
        <span id="camera-status-text">🟢 Camera Ready</span>
      </div>
    </div>

    <div class="container py-5 attendance-main-container">
      <div class="row justify-content-center">
        <div class="col-md-10">
          <div class="card shadow-sm">
            <div class="card-body">
              <h3 class="card-title mb-3">👨‍💼 Employee Attendance</h3>
              <p class="text-muted">Only employees with registered Bio IDs can log Time IN / Time OUT.</p>
              <?php if ($isHr): ?>
                <p><a href="employee_register.php" class="btn btn-sm btn-outline-primary">Register New Employee Bio ID</a></p>
              <?php endif; ?>

              <form id="attendance-form">
                <div class="row">
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Gmail <span style="color:red;">*</span></label>
                    <input type="email" class="form-control" id="gmail-input" name="gmail" placeholder="Enter your Gmail" value="spatag14@gmail.com" required>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Bio ID Number</label>
                    <input type="text" class="form-control" name="bio_id" required>
                    <div id="bio-check-message" class="form-text text-danger mt-1"></div>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" required>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Last Name</label>
                    <input type="text" class="form-control" name="last_name" required>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">First Name</label>
                    <input type="text" class="form-control" name="first_name" required>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Department</label>
                    <select class="form-control" name="department" required>
                      <option value="">Select Department</option>
                      <option value="Collection">Collection</option>
                      <option value="Telemarketing">Telemarketing</option>
                      <option value="Reviewer">Reviewer</option>
                      <option value="Compliance">Compliance</option>
                      <option value="Management">Management</option>
                    </select>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Account Stage</label>
                    <select class="form-control" name="account_stage" required>
                      <option value="">Select Stage</option>
                      <option value="S0">S0</option>
                      <option value="S1">S1</option>
                      <option value="S2">S2</option>
                      <option value="S3">S3</option>
                      <option value="S4">S4</option>
                      <option value="Telemarketing">Telemarketing</option>
                      <option value="Hr">Hr</option>
                      <option value="Admin">Admin</option>
                      <option value="Accounting">Accounting</option>
                      <option value="It">It</option>
                      <option value="Trainee">Trainee</option>
                    </select>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Account</label>
                    <input type="text" class="form-control" name="account" required>
                  </div>
                  <div class="col-md-4 mb-3">
                    <label class="form-label">Team Leader Name</label>
                    <input type="text" class="form-control" name="team_leader" required>
                  </div>
                </div>
                <div class="d-flex gap-2 mb-3 flex-column flex-md-row">
                  <button id="btn-time-in" type="button" class="btn btn-success w-100 btn-lg">
                    <i class="bi bi-clock-in"></i> ⏱️ Time IN
                  </button>
                  <button id="btn-time-out" type="button" class="btn btn-danger w-100 btn-lg">
                    <i class="bi bi-clock-out"></i> ⏱️ Time OUT
                  </button>
                  <button id="btn-holiday-off" type="button" class="btn btn-warning w-100 btn-lg" disabled>
                    🎉 Holiday Off
                  </button>
                    <button id="btn-ot" type="button" class="btn btn-primary w-100 btn-lg">⏫ Request OT</button>
                </div>
                <input type="hidden" name="action" id="action-input">
                <input type="hidden" name="photo_filename" id="photo-filename">
              </form>
              <div id="form-message" class="mt-2"></div>
            </div>
          </div>
        </div>

        <!-- OT Request Modal -->
        <div class="modal fade" id="otModal" tabindex="-1">
          <div class="modal-dialog modal-md">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title">⏫ Overtime Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
              </div>
              <div class="modal-body">
                <form id="ot-form">
                  <div class="mb-3">
                    <label class="form-label">Bio ID</label>
                    <input type="text" id="ot-bio-id" name="bio_id" class="form-control" value="<?php echo htmlspecialchars($userBioId); ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" id="ot-date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Duration</label>
                    <select id="ot-duration" name="duration" class="form-control" required>
                      <option value="0.5" data-label="30 minutes">30 minutes</option>
                      <option value="1" data-label="1 hour">1 hour</option>
                      <option value="2" data-label="2 hours">2 hours</option>
                    </select>
                  </div>
                  <div class="mb-3">
                    <label class="form-label">Reason</label>
                    <textarea id="ot-reason" name="reason" class="form-control" rows="3" required></textarea>
                  </div>
                  <div class="text-end">
                    <button type="submit" class="btn btn-primary">Submit OT</button>
                    <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Cancel</button>
                  </div>
                </form>
                <div id="ot-feedback" class="mt-3" style="display:none"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      let video = document.getElementById('camera');
      let canvas = document.getElementById('snapshot');
      let photoFilename = document.getElementById('photo-filename');
      let photoPreview = document.getElementById('photo-preview');
      let cameraStatusText = document.getElementById('camera-status-text');
      const form = document.getElementById('attendance-form');
      const messageDiv = document.getElementById('form-message');
      const actionInput = document.getElementById('action-input');
      const btnTimeIn = document.getElementById('btn-time-in');
      const btnTimeOut = document.getElementById('btn-time-out');
      const btnHolidayOff = document.getElementById('btn-holiday-off');
      const btnOt = document.getElementById('btn-ot');
      const gmailInput = document.getElementById('gmail-input');
      const bioIdInput = form.querySelector('input[name="bio_id"]');
      const bioCheckMessage = document.getElementById('bio-check-message');
      let registeredBioId = false;
      let holidayOffAlreadyRecorded = false;
      let alreadyHasTimeIn = false;
      let alreadyHasTimeOut = false;
      btnTimeIn.disabled = true;
      btnTimeOut.disabled = true;
      btnHolidayOff.disabled = true;
      if (btnOt) btnOt.disabled = true;

      // Get server time
      let serverTime = null;
      let clientTime = null;
      let timeDifference = 0;

      // Fetch server time on page load
      fetch('get_server_time.php')
        .then(res => res.json())
        .then(data => {
          serverTime = new Date(data.server_time);
          clientTime = new Date();
          timeDifference = serverTime - clientTime;
          console.log('✅ Server time synchronized');
          console.log('Server Time:', serverTime);
          console.log('Time difference:', timeDifference + 'ms');
        })
        .catch(err => console.error('❌ Error getting server time:', err));

      // Function to get accurate server time
      function getServerTime() {
        return new Date(new Date().getTime() + timeDifference);
      }

      function isHolidayDate(dateString) {
        if (!dateString) return false;

        const year = dateString.substring(0, 4) || new Date().getFullYear().toString();
        const regularHolidays = [
          `${year}-01-01`, `${year}-04-09`, `${year}-05-01`, `${year}-05-27`, `${year}-06-12`,
          `${year}-08-31`, `${year}-11-30`, `${year}-12-25`, `${year}-12-30`
        ];
        const specialHolidays = [
          `${year}-02-25`, `${year}-03-08`, `${year}-11-01`, `${year}-12-31`
        ];
        return regularHolidays.includes(dateString) || specialHolidays.includes(dateString);
      }

      async function checkDailyStatus() {
        holidayOffAlreadyRecorded = false;
        alreadyHasTimeIn = false;
        alreadyHasTimeOut = false;

        const bioId = bioIdInput.value.trim();
        const selectedDate = form.querySelector('input[name="date"]').value;

        if (!registeredBioId || !bioId || !selectedDate) {
          updateHolidayButton();
          updateOtButton();
          return;
        }

        try {
          const response = await fetch('check_bio_id.php?bio_id=' + encodeURIComponent(bioId) + '&date=' + encodeURIComponent(selectedDate));
          const data = await response.json();

          if (data.success) {
            holidayOffAlreadyRecorded = !!data.holiday_off_exists;
            alreadyHasTimeIn = !!data.has_time_in;
            alreadyHasTimeOut = !!data.has_time_out;
          } else {
            holidayOffAlreadyRecorded = false;
            alreadyHasTimeIn = false;
            alreadyHasTimeOut = false;
          }
        } catch (error) {
          console.error('❌ Error checking daily status:', error);
          holidayOffAlreadyRecorded = false;
          alreadyHasTimeIn = false;
          alreadyHasTimeOut = false;
        }

        updateHolidayButton();
        updateOtButton();
      }

      function updateOtButton() {
        if (!btnOt) return;
        btnOt.disabled = !registeredBioId || !bioIdInput.value.trim();
        if (!registeredBioId) {
          btnOt.title = 'Enter Bio ID to request OT.';
        } else {
          btnOt.title = 'Request OT for the selected date.';
        }
      }

      function updateHolidayButton() {
        const selectedDate = form.querySelector('input[name="date"]').value;
        const isHoliday = isHolidayDate(selectedDate);

        // Holiday button is only enabled if:
        // 1. Bio ID is registered
        // 2. Selected date is actually a holiday
        // 3. Holiday off hasn't been recorded yet
        // 4. No time-in and no time-out recorded
        btnHolidayOff.disabled = !registeredBioId || !isHoliday || holidayOffAlreadyRecorded || alreadyHasTimeIn || alreadyHasTimeOut;

        // Update button text based on state
        if (!isHoliday) {
          btnHolidayOff.textContent = '🎉 Not a Holiday';
        } else if (holidayOffAlreadyRecorded) {
          btnHolidayOff.textContent = '🎉 Holiday Off (Already used today)';
        } else {
          btnHolidayOff.textContent = '🎉 Holiday Off';
        }
      }

      // Set default date to today (server time)
      document.querySelector('input[name="date"]').valueAsDate = getServerTime();

      function setBioIdState(isValid, employee = null) {
        registeredBioId = isValid;
        btnTimeIn.disabled = !isValid;
        btnTimeOut.disabled = !isValid;
        btnHolidayOff.disabled = true;
        if (btnOt) btnOt.disabled = !isValid;
        if (btnOt) updateOtButton();

        if (isValid && employee) {
          updateOtButton();
          gmailInput.value = employee.gmail;
          form.querySelector('input[name="last_name"]').value = employee.last_name;
          form.querySelector('input[name="first_name"]').value = employee.first_name;
          form.querySelector('select[name="department"]').value = employee.department;
          form.querySelector('select[name="account_stage"]').value = employee.account_stage;
          form.querySelector('input[name="account"]').value = employee.account;
          form.querySelector('input[name="team_leader"]').value = employee.team_leader;
          bioCheckMessage.textContent = '✅ Registered employee found. Ready for Time IN/OUT.';
          bioCheckMessage.classList.remove('text-danger');
          bioCheckMessage.classList.add('text-success');
        } else {
          bioCheckMessage.textContent = '❌ Bio ID not registered yet. Please register employee info first.';
          bioCheckMessage.classList.remove('text-success');
          bioCheckMessage.classList.add('text-danger');
        }
      }

      async function checkBioId() {
        const bioId = bioIdInput.value.trim();

        if (!bioId) {
          setBioIdState(false);
          return;
        }

        try {
          const response = await fetch('check_bio_id.php?bio_id=' + encodeURIComponent(bioId));
          const data = await response.json();

          if (data.success && data.employee) {
            setBioIdState(true, data.employee);
            await checkDailyStatus();
          } else {
            setBioIdState(false);
            holidayOffAlreadyRecorded = false;
            alreadyHasTimeIn = false;
            alreadyHasTimeOut = false;
            updateHolidayButton();
          }
        } catch (error) {
          console.error('❌ Error checking Bio ID:', error);
          setBioIdState(false);
          holidayOffAlreadyRecorded = false;
          updateHolidayButton();
        }
      }

      bioIdInput.addEventListener('blur', checkBioId);
      bioIdInput.addEventListener('input', function () {
        registeredBioId = false;
        holidayOffAlreadyRecorded = false;
        alreadyHasTimeIn = false;
        alreadyHasTimeOut = false;
        btnTimeIn.disabled = true;
        btnTimeOut.disabled = true;
        btnHolidayOff.disabled = true;
        if (btnOt) btnOt.disabled = true;
        if (btnOt) updateOtButton();
        bioCheckMessage.textContent = '';
      });

      async function startCamera() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
          cameraStatusText.textContent = '🔴 Camera Not Available';
          console.error('getUserMedia not supported');
          return;
        }

        try {
          const stream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user' } });
          video.srcObject = stream;
          await video.play();
          cameraStatusText.textContent = '🟢 Camera Ready';
          console.log('✅ Camera started successfully');
        } catch (err) {
          console.error('❌ Camera error: ' + err);
          cameraStatusText.textContent = '🔴 Camera Error';
          messageDiv.innerHTML = '<div class="alert alert-warning">⚠️ Camera access was blocked. You can still submit attendance without a photo.</div>';
        }
      }

      startCamera();

      // Overtime modal handling (HR only)
      const otModal = new bootstrap.Modal(document.getElementById('otModal'));
      const otForm = document.getElementById('ot-form');
      const otFeedback = document.getElementById('ot-feedback');

      if (btnOt) {
        btnOt.addEventListener('click', async function() {
          const currentBio = bioIdInput.value.trim();
          if (!currentBio) {
            bioCheckMessage.textContent = 'Please enter Bio ID first.';
            bioCheckMessage.classList.remove('text-success');
            bioCheckMessage.classList.add('text-danger');
            return;
          }

          // If not already validated, run checkBioId()
          if (!registeredBioId) {
            await checkBioId();
          }

          // Ensure we have the latest daily status
          await checkDailyStatus();

          if (!registeredBioId) {
            bioCheckMessage.textContent = '❌ Bio ID not registered yet. Please register employee info first.';
            bioCheckMessage.classList.remove('text-success');
            bioCheckMessage.classList.add('text-danger');
            return;
          }

          // Block OT request if Time IN hasn't been recorded for the selected date
          if (!alreadyHasTimeIn) {
            bioCheckMessage.textContent = '❌ Cannot request OT before Time IN for the selected date.';
            bioCheckMessage.classList.remove('text-success');
            bioCheckMessage.classList.add('text-danger');
            return;
          }

          otForm.reset();
          otFeedback.style.display = 'none';
          // Prefill modal Bio ID from the main form
          const otBioInput = document.getElementById('ot-bio-id');
          if (otBioInput) otBioInput.value = currentBio;
          otModal.show();
        });
      }

      if (otForm) {
        otForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const duration = parseFloat(document.getElementById('ot-duration').value) || 0;
          const durationLabel = document.getElementById('ot-duration').selectedOptions[0].text || '';
          const formData = new FormData(otForm);
          formData.append('hours', duration);
          formData.append('duration_label', durationLabel);

          const submitBtn = otForm.querySelector('button[type="submit"]');
          submitBtn.disabled = true;
          submitBtn.textContent = 'Submitting...';

          fetch('register_ot_ajax.php', {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit OT';
            otFeedback.style.display = 'block';
            if (data.success) {
              otFeedback.className = 'alert alert-success';
              otFeedback.textContent = data.message || 'OT submitted';

              // Send OT confirmation email
              sendConfirmationEmail(
                document.querySelector('input[name="gmail"]').value,
                'OT',
                document.querySelector('input[name="bio_id"]').value,
                document.querySelector('input[name="first_name"]').value,
                {
                  date: document.getElementById('ot-date').value,
                  hours: duration,
                  duration_label: durationLabel
                }
              );

              setTimeout(() => otModal.hide(), 1000);
            } else {
              otFeedback.className = 'alert alert-danger';
              otFeedback.textContent = data.error || 'Failed to submit OT';
            }
          })
          .catch(err => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit OT';
            otFeedback.style.display = 'block';
            otFeedback.className = 'alert alert-danger';
            otFeedback.textContent = 'Network error: ' + err.message;
          });
        });
      }

      // Function to capture photo and upload it
      function captureAndUploadPhoto() {
        return new Promise((resolve) => {
          try {
            // Use server time for timestamp
            const now = getServerTime();
            const timestamp = now.toLocaleString();
            
            // Check if video is ready
            if (!video.videoWidth || !video.videoHeight) {
              console.warn('⚠️ Video not ready');
              photoFilename.value = '';
              resolve(null);
              return;
            }
            
            // Set canvas size to video size
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const ctx = canvas.getContext('2d');
            
            // Draw video frame to canvas
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
            
            // Draw timestamp on image
            ctx.font = 'bold 20px Arial';
            ctx.fillStyle = 'yellow';
            ctx.strokeStyle = 'black';
            ctx.lineWidth = 4;
            ctx.strokeText(timestamp, 10, canvas.height - 20);
            ctx.fillText(timestamp, 10, canvas.height - 20);
            
            // Convert to blob and upload
            canvas.toBlob(blob => {
              const formData = new FormData();
              formData.append('photo', blob, 'attendance_' + Date.now() + '.png');
              
              cameraStatusText.textContent = '⏳ Uploading...';
              
              fetch('save_photo.php', {
                method: 'POST',
                body: formData
              })
              .then(res => res.json())
              .then(data => {
                if (data.success && data.filename) {
                  photoFilename.value = data.filename;
                  photoPreview.src = 'uploads/' + data.filename;
                  photoPreview.style.display = 'block';
                  cameraStatusText.textContent = '✅ Photo Captured';
                  console.log('✅ Photo saved: ' + data.filename);
                  resolve(data.filename);
                } else {
                  console.warn('⚠️ Photo upload failed: ' + (data.error || 'Unknown error'));
                  photoFilename.value = '';
                  cameraStatusText.textContent = '❌ Upload Failed';
                  resolve(null);
                }
              })
              .catch(err => {
                console.error('❌ Photo upload error: ' + err);
                photoFilename.value = '';
                cameraStatusText.textContent = '❌ Error';
                resolve(null);
              });
            }, 'image/png');
            
          } catch(e) {
            console.error('❌ Photo capture error: ' + e);
            photoFilename.value = '';
            resolve(null);
          }
        });
      }

      // Send confirmation email
      function sendConfirmationEmail(gmail, action, bio_id, first_name, details = {}) {
        console.log('📧 Sending email to:', gmail);
        
        const payload = {
          gmail: gmail,
          action: action,
          bio_id: bio_id,
          first_name: first_name,
          date: details.date || document.querySelector('input[name="date"]').value,
          hours: details.hours || '',
          duration_label: details.duration_label || ''
        };

        fetch('send_email.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify(payload)
        })
        .then(res => {
          console.log('Response status:', res.status);
          return res.json();
        })
        .then(data => {
          console.log('Email response:', data);
          
          if (data.success) {
            if (data.email_sent) {
              messageDiv.innerHTML += '<div class="alert alert-success" style="margin-top: 10px;">📧 Confirmation email sent to ' + gmail + '</div>';
              console.log('✅ Email sent successfully');
            } else {
              messageDiv.innerHTML += '<div class="alert alert-info" style="margin-top: 10px;">📧 Email delivery is pending. Attendance has been recorded.</div>';
              console.log('⚠️ Email pending');
            }
          } else {
            messageDiv.innerHTML += '<div class="alert alert-warning" style="margin-top: 10px;">⚠️ Could not send confirmation email.</div>';
            console.warn('❌ Email failed:', data.message);
          }
        })
        .catch(err => {
          console.error('❌ Email fetch error:', err);
          messageDiv.innerHTML += '<div class="alert alert-info" style="margin-top: 10px;">📧 Attendance recorded successfully!</div>';
        });
      }

      // Submit form function
      async function submitForm(action) {
        actionInput.value = action;
        
        // Validate form
        if (!form.checkValidity()) {
          messageDiv.innerHTML = '<div class="alert alert-danger">❌ Please fill in all required fields</div>';
          form.reportValidity();
          return;
        }

        if (!registeredBioId) {
          messageDiv.innerHTML = '<div class="alert alert-danger">❌ Cannot proceed. The Bio ID is not registered yet.</div>';
          return;
        }

        messageDiv.innerHTML = '<div class="alert alert-info">📷 Capturing photo...</div>';
        cameraStatusText.textContent = '📷 Capturing...';

        try {
          // Capture photo and upload
          const filename = await captureAndUploadPhoto();
          
          // Wait for upload to complete
          await new Promise(resolve => setTimeout(resolve, 1000));
          
          messageDiv.innerHTML = '<div class="alert alert-info">💾 Saving attendance record...</div>';
          
          const formData = new FormData(form);
          
          console.log('📤 Submitting form - Action: ' + action + ', Photo: ' + (filename || 'none'));
          
          fetch('save_attendance.php', {
            method: 'POST',
            body: formData
          })
          .then(response => response.json())
          .then(data => {
            console.log("✅ Response:", data);
            
            if (data.success) {
              messageDiv.innerHTML = '<div class="alert alert-success"><strong>✅ ' + (data.message || 'Attendance saved successfully!') + '</strong></div>';
              
              // Send confirmation email 📧
              console.log('📧 Sending confirmation email...');
              sendConfirmationEmail(
                gmailInput.value,
                action,
                form.querySelector('input[name="bio_id"]').value,
                form.querySelector('input[name="first_name"]').value
              );
              
              // Reset form
              setTimeout(() => {
                form.reset();
                photoFilename.value = '';
                photoPreview.style.display = 'none';
                document.querySelector('input[name="date"]').valueAsDate = getServerTime();
                cameraStatusText.textContent = '🟢 Camera Ready';
                messageDiv.innerHTML = '';
                registeredBioId = false;
                holidayOffAlreadyRecorded = false;
                btnTimeIn.disabled = true;
                btnTimeOut.disabled = true;
                btnHolidayOff.disabled = true;
              }, 2000);
            } else {
              messageDiv.innerHTML = '<div class="alert alert-danger"><strong>❌ Error:</strong> ' + (data.error || 'Unknown error') + (data.fields ? '<br>Missing: ' + data.fields.join(', ') : '') + '</div>';
              cameraStatusText.textContent = '🔴 Error';
            }
          })
          .catch((error) => {
            console.error("❌ Error:", error);
            messageDiv.innerHTML = '<div class="alert alert-danger"><strong>❌ Network Error:</strong> ' + error.message + '</div>';
            cameraStatusText.textContent = '🔴 Error';
          });
        } catch(err) {
          console.error('❌ Submit error:', err);
          messageDiv.innerHTML = '<div class="alert alert-danger"><strong>❌ Error:</strong> ' + err.message + '</div>';
          cameraStatusText.textContent = '🔴 Error';
        }
      }

      // Button click handlers
      btnTimeIn.addEventListener('click', function(e) {
        e.preventDefault();
        submitForm('IN');
      });

      btnTimeOut.addEventListener('click', function(e) {
        e.preventDefault();
        submitForm('OUT');
      });

      btnHolidayOff.addEventListener('click', function(e) {
        e.preventDefault();
        submitForm('HOLIDAY_OFF');
      });

      form.querySelector('input[name="date"]').addEventListener('change', function () {
        if (registeredBioId) {
          checkDailyStatus();
        } else {
          updateHolidayButton();
        }
      });
      form.querySelector('input[name="date"]').addEventListener('input', function () {
        if (registeredBioId) {
          checkDailyStatus();
        } else {
          updateHolidayButton();
        }
      });
    </script>
  </body>
</html>