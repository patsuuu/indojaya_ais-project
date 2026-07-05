<?php
require_once 'auth_check.php';
require_once 'db_connect.php';
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance Records</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
      .table-responsive {
        max-height: 600px;
        overflow-y: auto;
      }
      img.photo-thumb {
        max-width: 80px;
        height: 80px;
        border-radius: 6px;
        cursor: pointer;
        object-fit: cover;
        background-color: #f0f0f0;
        border: 2px solid #ddd;
      }
      img.photo-thumb:hover {
        border-color: #007bff;
        transform: scale(1.05);
      }
      .no-photo {
        color: #999;
        font-size: 12px;
        padding: 5px;
        display: inline-block;
      }
      .user-info {
        background: rgba(255, 255, 255, 0.1);
        padding: 8px 15px;
        border-radius: 5px;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
        color: white;
      }
      .user-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 600;
      }
      .user-badge.admin {
        background-color: #dc3545;
        color: #fff;
        box-shadow: 0 1px 0 rgba(0,0,0,0.15) inset;
        border: 1px solid rgba(0,0,0,0.08);
      }
      .user-badge.hr {
        background-color: #dc3545;
        color: #fff;
        box-shadow: 0 1px 0 rgba(0,0,0,0.15) inset;
        border: 1px solid rgba(0,0,0,0.08);
      }
      .user-badge.user {
        background-color: #28a745;
      }
      .filter-section {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 20px;
      }
      .filter-group {
        display: flex;
        gap: 15px;
        align-items: flex-end;
        flex-wrap: wrap;
      }
      .filter-item {
        display: flex;
        flex-direction: column;
        gap: 5px;
      }
      .filter-item label {
        margin: 0;
        font-weight: 600;
        font-size: 13px;
      }
      .filter-item input {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 13px;
        min-width: 150px;
      }
      .filter-buttons {
        display: flex;
        gap: 10px;
      }
      .filter-buttons button {
        padding: 8px 15px;
        font-size: 13px;
      }
      .pagination-section {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 8px;
        flex-wrap: wrap;
        gap: 15px;
      }
      .pagination-info {
        font-size: 13px;
        font-weight: 600;
        color: #333;
      }
      .records-per-page {
        display: flex;
        align-items: center;
        gap: 10px;
      }
      .records-per-page label {
        margin: 0;
        font-weight: 600;
        font-size: 13px;
      }
      .records-per-page select {
        padding: 6px 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 13px;
      }
      .pagination-controls {
        display: flex;
        gap: 5px;
        flex-wrap: wrap;
      }
      .pagination-controls button {
        padding: 6px 10px;
        font-size: 12px;
        border: 1px solid #ddd;
        background: white;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
      }
      .pagination-controls button:hover {
        background: #007bff;
        color: white;
        border-color: #007bff;
      }
      .pagination-controls button.active {
        background: #007bff;
        color: white;
        border-color: #007bff;
        font-weight: 600;
      }
      .pagination-controls button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
      }
      .double-pay-row {
        background-color: #d4edda !important;
        font-weight: 600;
      }
      .double-pay-row td {
        background-color: rgba(212, 237, 218, 0.3) !important;
      }
      .payslip-modal-body {
        max-height: 70vh;
        overflow-y: auto;
      }
      .payslip-section {
        background: #ffffff;
        border: 1px solid #e9ecef;
        border-radius: 0.85rem;
        padding: 1rem 1.1rem;
        margin-bottom: 1rem;
      }
      .payslip-section-title {
        margin-bottom: 1rem;
        font-size: 1rem;
        font-weight: 700;
        color: #212529;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 0.5rem;
      }
      .payslip-item {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 0.75rem;
        padding: 0.85rem;
      }
      .payslip-item .form-label {
        font-size: 0.92rem;
        margin-bottom: 0.35rem;
      }
      .payslip-item .form-control {
        min-height: calc(1.5em + 0.75rem + 2px);
      }
    </style>
  </head>
  <body class="bg-light">
    <nav class="navbar navbar-dark bg-dark">
      <div class="container-fluid">
        <span class="navbar-brand mb-0 h1">📊 Attendance Records</span>
        <div class="d-flex align-items-center gap-3">
          <?php $roleList = getRoles(); ?>
          <div class="user-info">
            <span>👤 <?php echo htmlspecialchars($_SESSION['username']); ?></span>
            <span class="user-badge <?php echo htmlspecialchars(implode('-', $roleList)); ?>">
              <?php echo strtoupper(htmlspecialchars(implode(', ', $roleList))); ?>
            </span>
          </div>
          <a href="logout.php" class="btn btn-danger btn-sm">🔓 Logout</a>
        </div>
      </div>
    </nav>

    <div class="container-fluid py-5">
      <div class="row mb-3">
        <div class="col-md-6">
          <h3>📊 Attendance Records</h3>
        </div>
        <div class="col-md-6 text-end">
          <button class="btn btn-success btn-sm" id="refresh-btn">🔄 Refresh</button>
          <?php if (isAdmin()): ?>
            <button class="btn btn-warning btn-sm" id="download-payslip-btn" disabled>📄 Download Payslip</button>
          <?php elseif (isHr()): ?>
            <button class="btn btn-warning btn-sm" id="download-excel-btn" disabled>📥 Download Excel</button>
          <?php endif; ?>
          <?php if (isHr()): ?>
            <button class="btn btn-info btn-sm ms-2" id="upload-payslip-btn" disabled>📤 Upload Payslip</button>
            <button class="btn btn-outline-primary btn-sm ms-2" id="open-register-btn">➕ Register Bio ID</button>
          <?php endif; ?>
          <span id="upload-status" class="badge bg-secondary ms-2" style="display:none; font-size: 0.9rem;"></span>
          <span id="record-count" class="badge bg-info ms-2"></span>
          <input type="file" id="payslip-file-input" accept=".pdf,image/*" style="display:none;">
        </div>
      </div>

            <!-- Filter Section -->
      <div class="filter-section">
        <div class="filter-group">
          <div class="filter-item">
            <label for="search-bio-id">🔍 Search Bio ID:</label>
            <input type="text" id="search-bio-id" class="form-control" placeholder="Enter Bio ID">
          </div>
          <div class="filter-item">
            <label for="filter-date-from">📅 From Date:</label>
            <input type="date" id="filter-date-from" class="form-control">
          </div>
          <div class="filter-item">
            <label for="filter-date-to">📅 To Date:</label>
            <input type="date" id="filter-date-to" class="form-control">
          </div>
                    <div class="filter-item">
            <label for="filter-status">Status:</label>
            <select id="filter-status" class="form-control">
              <option value="">All Records</option>
              <option value="complete">✅ Complete</option>
              <option value="incomplete_no_timein">❌ No Timein</option>
              <option value="no_time_in">🚫 Never Time In</option>
              <option value="no_time_out">🚫 Never Time Out</option>
                    <option value="early_out">⚠️ Early Out</option>
                    <option value="none">ℹ️ None</option>
            </select>
          </div>
          <div class="filter-buttons">
            <button class="btn btn-primary btn-sm" id="filter-btn">🔍 Filter</button>
            <button class="btn btn-secondary btn-sm" id="clear-filter-btn">❌ Clear</button>
          </div>
        </div>
      </div>

      <!-- Modal for Register Bio ID (inline) -->
      <div class="modal fade" id="registerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">➕ Register Bio ID</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <form id="register-form">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Bio ID</label>
                    <input type="text" id="reg-bio-id" name="bio_id" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Gmail</label>
                    <input type="email" id="reg-gmail" name="gmail" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Last Name</label>
                    <input type="text" id="reg-last-name" name="last_name" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">First Name</label>
                    <input type="text" id="reg-first-name" name="first_name" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Department</label>
                    <select id="reg-department" name="department" class="form-control" required>
                      <option value="">Select department</option>
                      <option value="Collection">Collection</option>
                      <option value="Telemarketing">Telemarketing</option>
                      <option value="Reviewer">Reviewer</option>
                      <option value="Compliance">Compliance</option>
                      <option value="Management">Management</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Account Stage</label>
                    <select id="reg-account-stage" name="account_stage" class="form-control" required>
                      <option value="">Select stage</option>
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
                  <div class="col-md-6">
                    <label class="form-label">Account</label>
                    <input type="text" id="reg-account" name="account" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Team Leader</label>
                    <input type="text" id="reg-team-leader" name="team_leader" class="form-control" required>
                  </div>
                </div>
                <div class="mt-3 text-end">
                  <button type="submit" class="btn btn-primary" id="register-submit">Register</button>
                  <button type="button" class="btn btn-secondary ms-2" data-bs-dismiss="modal">Close</button>
                </div>
              </form>
              <div id="register-feedback" class="mt-3" style="display:none"></div>
            </div>
          </div>
        </div>
      </div>
      <div class="card shadow-sm">
        <div class="card-body">
          <div id="loading" class="text-center py-5">
            <div class="spinner-border" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading records...</p>
          </div>
          <div class="table-responsive" id="table-container" style="display:none;">
            <table class="table table-bordered table-hover table-sm">
              <thead class="table-dark sticky-top">
                <tr>
                  <th>#</th>
                  <th>Bio ID</th>
                  <th>Gmail</th>
                  <th>Last Name</th>
                  <th>First Name</th>
                  <th>Department</th>
                  <th>Account Stage</th>
                  <th>Account</th>
                  <th>Team Leader</th>
                  <th>OT Hours</th>
                  <th>Time In</th>
                  <th>Late In (min)</th>
                  <th>Time Out</th>
                  <th>Late Out (min)</th>
                  <th>Total Hours</th>
                  <th>Pay (PHP)</th>
                  <th>Date</th>
                  <th>Photo</th>
                </tr>
              </thead>
              <tbody id="records-body">
                <!-- Data will be loaded here by JS -->
              </tbody>
            </table>
          </div>
          <div id="error-message" class="alert alert-danger" style="display:none;"></div>
          <div id="no-data" class="alert alert-info" style="display:none;">No records found</div>
        </div>
      </div>

      <!-- Pagination Section -->
      <div class="pagination-section" id="pagination-section" style="display:none;">
        <div class="pagination-info">
          <span>📊 Showing <span id="showing-from">0</span> to <span id="showing-to">0</span> of <span id="total-records">0</span> records</span>
        </div>
        <div class="records-per-page">
          <label for="records-per-page">Records per page:</label>
          <select id="records-per-page">
            <option value="10">10</option>
            <option value="25" selected>25</option>
            <option value="50">50</option>
            <option value="100">100</option>
            <option value="200">200</option>
          </select>
        </div>
        <div class="pagination-controls">
          <button id="prev-btn" title="Previous">← Prev</button>
          <div id="page-buttons" style="display: flex; gap: 5px; flex-wrap: wrap;"></div>
          <button id="next-btn" title="Next">Next →</button>
        </div>
      </div>
    </div>

    <!-- Modal for HR payslip form -->
    <div class="modal fade" id="payslipModal" tabindex="-1">
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">📄 Upload Payslip Details</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <form id="payslip-form">
            <div class="modal-body payslip-modal-body">
              <div class="payslip-section">
                <div class="payslip-section-title">Employee / Bank Details</div>
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label">Bio ID</label>
                    <input type="text" id="payslip-bio-id" class="form-control" readonly>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Account Number</label>
                    <input type="text" id="account-number" class="form-control" required>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Bank Name</label>
                    <input type="text" id="bank-name" class="form-control" required>
                  </div>
                </div>
              </div>

              <div class="payslip-section">
                <div class="payslip-section-title">Earnings</div>
                <div class="row g-3">
                  <div class="col-md-6 col-xl-4">
                    <div class="payslip-item row gx-2 gy-2">
                      <div class="col-8">
                        <label class="form-label">Basic Salary</label>
                        <input type="number" step="0.01" min="0" id="basic-salary" class="form-control" required>
                      </div>
                      <div class="col-4">
                        <label class="form-label">Days</label>
                        <input type="number" step="1" min="0" id="basic-salary-days" class="form-control" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <div class="payslip-item row gx-2 gy-2">
                      <div class="col-8">
                        <label class="form-label">Overtime</label>
                        <input type="number" step="0.01" min="0" id="overtime" class="form-control" required>
                      </div>
                      <div class="col-4">
                        <label class="form-label">Days</label>
                        <input type="number" step="1" min="0" id="overtime-days" class="form-control" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <div class="payslip-item row gx-2 gy-2">
                      <div class="col-8">
                        <label class="form-label">Legal Holiday</label>
                        <input type="number" step="0.01" min="0" id="legal-holiday" class="form-control" required>
                      </div>
                      <div class="col-4">
                        <label class="form-label">Days</label>
                        <input type="number" step="1" min="0" id="legal-holiday-days" class="form-control" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <div class="payslip-item row gx-2 gy-2">
                      <div class="col-8">
                        <label class="form-label">Legal Holiday OT</label>
                        <input type="number" step="0.01" min="0" id="legal-holiday-ot" class="form-control" required>
                      </div>
                      <div class="col-4">
                        <label class="form-label">Days</label>
                        <input type="number" step="1" min="0" id="legal-holiday-ot-days" class="form-control" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <div class="payslip-item row gx-2 gy-2">
                      <div class="col-8">
                        <label class="form-label">Special Holiday (30%)</label>
                        <input type="number" step="0.01" min="0" id="special-holiday-30" class="form-control" required>
                      </div>
                      <div class="col-4">
                        <label class="form-label">Days</label>
                        <input type="number" step="1" min="0" id="special-holiday-30-days" class="form-control" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <div class="payslip-item row gx-2 gy-2">
                      <div class="col-8">
                        <label class="form-label">Special Holiday OT</label>
                        <input type="number" step="0.01" min="0" id="special-holiday-ot" class="form-control" required>
                      </div>
                      <div class="col-4">
                        <label class="form-label">Days</label>
                        <input type="number" step="1" min="0" id="special-holiday-ot-days" class="form-control" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <div class="payslip-item row gx-2 gy-2">
                      <div class="col-8">
                        <label class="form-label">Weekend OT</label>
                        <input type="number" step="0.01" min="0" id="weekend-ot" class="form-control" required>
                      </div>
                      <div class="col-4">
                        <label class="form-label">Days</label>
                        <input type="number" step="1" min="0" id="weekend-ot-days" class="form-control" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <div class="payslip-item row gx-2 gy-2">
                      <div class="col-8">
                        <label class="form-label">Performance Bonus</label>
                        <input type="number" step="0.01" min="0" id="performance-bonus" class="form-control" required>
                      </div>
                      <div class="col-4">
                        <label class="form-label">Days</label>
                        <input type="number" step="1" min="0" id="performance-bonus-days" class="form-control" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <div class="payslip-item row gx-2 gy-2">
                      <div class="col-8">
                        <label class="form-label">Adjustments</label>
                        <input type="number" step="0.01" id="adjustments" class="form-control" required>
                      </div>
                      <div class="col-4">
                        <label class="form-label">Days</label>
                        <input type="number" step="1" min="0" id="adjustments-days" class="form-control" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <div class="payslip-item row gx-2 gy-2">
                      <div class="col-8">
                        <label class="form-label">Allowance</label>
                        <input type="number" step="0.01" min="0" id="allowance" class="form-control" required>
                      </div>
                      <div class="col-4">
                        <label class="form-label">Days</label>
                        <input type="number" step="1" min="0" id="allowance-days" class="form-control" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <div class="payslip-item row gx-2 gy-2">
                      <div class="col-8">
                        <label class="form-label">Internet / Loan Allowance</label>
                        <input type="number" step="0.01" min="0" id="internet-loan-allowance" class="form-control" required>
                      </div>
                      <div class="col-4">
                        <label class="form-label">Days</label>
                        <input type="number" step="1" min="0" id="internet-loan-allowance-days" class="form-control" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <label class="form-label">Total</label>
                    <input type="number" step="0.01" min="0" id="total-earnings" class="form-control" required>
                  </div>
                </div>
              </div>

              <div class="payslip-section">
                <div class="payslip-section-title">Deductions</div>
                <div class="row g-3">
                  <div class="col-md-6 col-xl-4">
                    <label class="form-label">SSS</label>
                    <input type="number" step="0.01" min="0" id="sss" class="form-control" required>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <label class="form-label">PHIC</label>
                    <input type="number" step="0.01" min="0" id="phic" class="form-control" required>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <label class="form-label">HDMF</label>
                    <input type="number" step="0.01" min="0" id="hdmf" class="form-control" required>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <label class="form-label">Tax</label>
                    <input type="number" step="0.01" min="0" id="tax" class="form-control" required>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <label class="form-label">SSS Loan</label>
                    <input type="number" step="0.01" min="0" id="sss-loan" class="form-control" required>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <label class="form-label">Pagi-ibig Loan</label>
                    <input type="number" step="0.01" min="0" id="pagibig-loan" class="form-control" required>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <label class="form-label">Late / UT</label>
                    <input type="number" step="0.01" id="late-ut" class="form-control" required>
                  </div>
                  <div class="col-md-6 col-xl-4">
                    <label class="form-label">Net Pay</label>
                    <input type="number" step="0.01" min="0" id="net-pay" class="form-control" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Optional Payslip File</label>
                    <input type="file" id="payslip-attachment-input" class="form-control" accept=".pdf,image/*">
                    <div id="attachment-file-name" class="form-text text-muted"></div>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-primary" id="payslip-submit-btn">Save Payslip</button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <!-- Modal for inline payslip PDF preview -->
    <div class="modal fade" id="pdfPreviewModal" tabindex="-1">
      <div class="modal-dialog modal-fullscreen-lg-down modal-xl">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">📄 Payslip Preview</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body p-0">
            <iframe id="payslip-preview-frame" src="" style="width:100%;height:80vh;border:none;"></iframe>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal for viewing large photo -->
    <div class="modal fade" id="photoModal" tabindex="-1">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title">📷 Photo Preview</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body text-center">
            <img id="photoPreview" src="" class="img-fluid" alt="Photo" style="max-height: 600px;">
          </div>
          <div class="modal-footer">
            <small class="text-muted" id="photo-info"></small>
          </div>
        </div>
      </div>
    </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      const loadingDiv = document.getElementById('loading');
      const tableContainer = document.getElementById('table-container');
      const recordsBody = document.getElementById('records-body');
      const errorDiv = document.getElementById('error-message');
      const noDataDiv = document.getElementById('no-data');
      const refreshBtn = document.getElementById('refresh-btn');
      const downloadExcelBtn = document.getElementById('download-excel-btn');
      const downloadPayslipBtn = document.getElementById('download-payslip-btn');
      const uploadPayslipBtn = document.getElementById('upload-payslip-btn');
      const payslipFileInput = document.getElementById('payslip-file-input');
      const filterBtn = document.getElementById('filter-btn');
      const clearFilterBtn = document.getElementById('clear-filter-btn');
      const searchBioIdInput = document.getElementById('search-bio-id');
      const filterDateFromInput = document.getElementById('filter-date-from');
      const filterDateToInput = document.getElementById('filter-date-to');
      const filterStatusInput = document.getElementById('filter-status');
      const recordCount = document.getElementById('record-count');
      const photoModal = new bootstrap.Modal(document.getElementById('photoModal'));
      const photoPreview = document.getElementById('photoPreview');
      const photoInfo = document.getElementById('photo-info');
      const recordsPerPageSelect = document.getElementById('records-per-page');
      const paginationSection = document.getElementById('pagination-section');
      const prevBtn = document.getElementById('prev-btn');
      const nextBtn = document.getElementById('next-btn');
      const pageButtonsDiv = document.getElementById('page-buttons');
      const showingFrom = document.getElementById('showing-from');
      const showingTo = document.getElementById('showing-to');
      const totalRecords = document.getElementById('total-records');
      const payslipModal = new bootstrap.Modal(document.getElementById('payslipModal'));
      const payslipForm = document.getElementById('payslip-form');
      const payslipBioIdInput = document.getElementById('payslip-bio-id');
      const payslipAttachmentInput = document.getElementById('payslip-attachment-input');
      const attachmentFileName = document.getElementById('attachment-file-name');
      const payslipSubmitBtn = document.getElementById('payslip-submit-btn');
      const pdfPreviewModal = new bootstrap.Modal(document.getElementById('pdfPreviewModal'));
      const payslipPreviewFrame = document.getElementById('payslip-preview-frame');
      const registerModal = new bootstrap.Modal(document.getElementById('registerModal'));
      const openRegisterBtn = document.getElementById('open-register-btn');
      const registerForm = document.getElementById('register-form');
      const registerFeedback = document.getElementById('register-feedback');
      let overtimeMap = new Map();
      
      let allRecords = [];
      let attendanceMap = new Map();
      let currentPage = 1;
      let recordsPerPage = 25;
      let totalPages = 1;

      function formatDuration(ms) {
        if (ms < 0) ms = Math.abs(ms);
        const totalSeconds = Math.floor(ms / 1000);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;
        const pad = (n) => n.toString().padStart(2, '0');
        return `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;
      }

      function loadRecords() {
        loadingDiv.style.display = 'block';
        tableContainer.style.display = 'none';
        errorDiv.style.display = 'none';
        noDataDiv.style.display = 'none';
        paginationSection.style.display = 'none';

        fetch('get_records.php')
          .then(res => {
            if (!res.ok) throw new Error(`HTTP error! status: ${res.status}`);
            return res.json();
          })
          .then(data => {
            loadingDiv.style.display = 'none';

            if (data.error) {
              errorDiv.innerHTML = '<strong>Error:</strong> ' + data.error;
              errorDiv.style.display = 'block';
              return;
            }

            if (!data.records || data.records.length === 0) {
              noDataDiv.style.display = 'block';
              recordCount.textContent = '0 records';
              allRecords = [];
              return;
            }

            allRecords = data.records;
            attendanceMap = buildAttendanceMap(allRecords);
            currentPage = 1;
            displayPage();
          })
          .catch(error => {
            loadingDiv.style.display = 'none';
            errorDiv.innerHTML = '<strong>Error loading records:</strong> ' + error.message;
            errorDiv.style.display = 'block';
          });
      }

      function displayPage() {
        // Calculate pagination
        totalPages = Math.ceil(allRecords.length / recordsPerPage);
        const startIdx = (currentPage - 1) * recordsPerPage;
        const endIdx = startIdx + recordsPerPage;
        const pageRecords = allRecords.slice(startIdx, endIdx);

        // Update info
        recordCount.textContent = allRecords.length + ' records';
        totalRecords.textContent = allRecords.length;
        showingFrom.textContent = allRecords.length === 0 ? 0 : startIdx + 1;
        showingTo.textContent = Math.min(endIdx, allRecords.length);

        // Display records
        if (allRecords.length === 0) {
          noDataDiv.style.display = 'block';
          tableContainer.style.display = 'none';
          paginationSection.style.display = 'none';
          return;
        }

        recordsBody.innerHTML = '';
          pageRecords.forEach((record, idx) => {
          const timeIn = record.time_in ? record.time_in.substring(11, 19) : '❌ no time in';
          const timeOut = record.time_out ? record.time_out.substring(11, 19) : '❌ no time out';

          // Calculate total hours between time_in and time_out and compute pay
          let totalHours = '—';
          let payDisplay = '—';
          let isDoublePay = false;
          let hoursDecimal = 0;
          if (record.status === 'holiday_off') {
            const dateOnly = record.date;
            const payInfo = getHolidayOffInfo(dateOnly);
            totalHours = '0:00:00';
            payDisplay = '₱ ' + payInfo.pay.toFixed(2);
            if (payInfo.payLabel && payInfo.payLabel.includes('Double Pay')) {
              payDisplay += ' (Double pay)';
              isDoublePay = true;
            } else if (payInfo.payLabel) {
              payDisplay += ' (' + payInfo.payLabel + ')';
            }
          } else if (record.time_in && record.time_out) {
            try {
              const inIso = record.time_in.replace(' ', 'T');
              const outIso = record.time_out.replace(' ', 'T');
              let inDate = new Date(inIso);
              let outDate = new Date(outIso);
              let diff = outDate - inDate;
              // If negative (overnight), add 24h
              if (!isNaN(diff) && diff < 0) diff += 24 * 60 * 60 * 1000;
              if (isNaN(diff)) {
                totalHours = 'Invalid';
                payDisplay = '—';
              } else {
                const payInfo = getPayInfo(inDate, outDate, record, attendanceMap);
                totalHours = formatDuration(payInfo.payHours * 1000 * 60 * 60);
                const basePay = payInfo.pay;
                const otPay = record.ot_pay ? Number(record.ot_pay) : 0;
                const totalPay = basePay + otPay;
                payDisplay = '₱ ' + totalPay.toFixed(2);
                if (otPay > 0) {
                  payDisplay += ` (incl. OT ₱ ${otPay.toFixed(2)})`;
                }
                if (payInfo.payLabel && payInfo.payLabel.includes('Double Pay')) {
                  payDisplay += ' (Double pay)';
                  isDoublePay = true;
                } else if (payInfo.payLabel && payInfo.payLabel !== 'Regular') {
                  payDisplay += ' (' + payInfo.payLabel + ')';
                }
              }
            } catch (e) {
              totalHours = 'Error';
              payDisplay = '—';
            }
          }
          
          // Format late time for Time IN
          const lateInDisplay = record.time_in 
            ? (record.late_in_minutes && record.late_in_minutes > 0 
              ? '<span class="badge bg-danger">' + record.late_in_minutes + ' min</span>' 
              : '<span class="badge bg-success">✓</span>')
            : '<span class="badge bg-danger">❌ no Check-Out</span>';
          
          // Format late time for Time OUT
          const lateOutDisplay = record.time_out
            ? (record.late_out_minutes 
              ? (record.late_out_minutes > 0 
                ? '<span class="badge bg-warning">' + record.late_out_minutes + ' min Late</span>'
                : '<span class="badge bg-info">' + Math.abs(record.late_out_minutes) + ' min EARLY</span>')
              : '<span class="badge bg-success">✓</span>')
            : '—';
          
          // Status Badge with comprehensive checks
          const statusBadge = (() => {
            // No Time In
            if (record.no_time_in) {
              return '<span class="badge bg-danger">🚫 Never Time In</span>';
            }
            // No Time Out
            if (record.no_time_out) {
              return '<span class="badge bg-danger">🚫 Never Time Out</span>';
            }
            // Complete
            if (record.status === 'complete') {
              return '<span class="badge bg-success">✅ Complete</span>';
            }
            // Early Out
            if (record.status === 'early_out') {
              return '<span class="badge bg-warning">⚠️ Early Out</span>';
            }
            // None (explicit none status for OUT when not early)
            if (record.status === 'none') {
              return '<span class="badge bg-secondary">ℹ️ None</span>';
            }
            // Incomplete - No Timein
            if (record.status === 'incomplete_no_timein') {
              return '<span class="badge bg-danger">❌ No Timein</span>';
            }
            return '<span class="badge bg-secondary">-</span>';
          })();
          
          let photoHtml = '<span class="no-photo">❌ No photo</span>';
          if (record.photo_url) {
            photoHtml = `
              <img 
                src="${record.photo_url}" 
                class="photo-thumb" 
                alt="Photo ID:${record.id}" 
                data-id="${record.id}"
                onerror="handlePhotoError(this, ${record.id})"
              >
            `;
          }

          const row = `
            <tr class="${isDoublePay ? 'double-pay-row' : ''}">
              <td>${startIdx + idx + 1}</td>
              <td>${escapeHtml(record.bio_id)}</td>
              <td>${escapeHtml(record.gmail)}</td>
              <td>${escapeHtml(record.last_name)}</td>
              <td>${escapeHtml(record.first_name)}</td>
              <td>${escapeHtml(record.department)}</td>
              <td>${escapeHtml(record.account_stage)}</td>
              <td>${escapeHtml(record.account)}</td>
              <td>${escapeHtml(record.team_leader)}</td>
              <td>${record.ot_hours ? record.ot_hours.toFixed(2) : '0.00'}</td>
              <td>${timeIn}</td>
              <td>${lateInDisplay}</td>
              <td>${timeOut}</td>
              <td>${lateOutDisplay}</td>
              <td>${totalHours}</td>
              <td>${payDisplay}</td>
              <td>${record.date}</td>
              <td>${photoHtml}</td>
            </tr>
          `;
          recordsBody.innerHTML += row;
        });

        // Add photo click handlers
        document.querySelectorAll('.photo-thumb').forEach(img => {
          img.addEventListener('click', function(e) {
            const photoUrl = this.src;
            const photoId = this.getAttribute('data-id');
            photoPreview.src = photoUrl;
            photoInfo.textContent = 'Record ID: ' + photoId;
            photoModal.show();
          });
        });

        tableContainer.style.display = 'block';
        noDataDiv.style.display = 'none';

        // Show pagination if more than one page
        if (totalPages > 1) {
          paginationSection.style.display = 'flex';
          updatePaginationButtons();
        } else {
          paginationSection.style.display = 'none';
        }
      }

      function updatePaginationButtons() {
        pageButtonsDiv.innerHTML = '';
        
        // Calculate visible page range (show max 7 pages at a time)
        let startPage = Math.max(1, currentPage - 3);
        let endPage = Math.min(totalPages, currentPage + 3);

        if (totalPages <= 7) {
          startPage = 1;
          endPage = totalPages;
        }

        // Prev button
        prevBtn.disabled = currentPage === 1;

        // First page button if not visible
        if (startPage > 1) {
          const btn = createPageButton(1);
          pageButtonsDiv.appendChild(btn);
          if (startPage > 2) {
            const dots = document.createElement('span');
            dots.style.padding = '6px 5px';
            dots.textContent = '...';
            pageButtonsDiv.appendChild(dots);
          }
        }

        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
          const btn = createPageButton(i);
          pageButtonsDiv.appendChild(btn);
        }

        // Last page button if not visible
        if (endPage < totalPages) {
          if (endPage < totalPages - 1) {
            const dots = document.createElement('span');
            dots.style.padding = '6px 5px';
            dots.textContent = '...';
            pageButtonsDiv.appendChild(dots);
          }
          const btn = createPageButton(totalPages);
          pageButtonsDiv.appendChild(btn);
        }

        // Next button
        nextBtn.disabled = currentPage === totalPages;
      }

      function createPageButton(pageNum) {
        const btn = document.createElement('button');
        btn.textContent = pageNum;
        btn.addEventListener('click', () => {
          currentPage = pageNum;
          displayPage();
        });
        if (pageNum === currentPage) {
          btn.classList.add('active');
        }
        return btn;
      }

      function filterRecords() {
        const bioIdSearch = searchBioIdInput.value.trim().toLowerCase();
        const dateFrom = filterDateFromInput.value;
        const dateTo = filterDateToInput.value;
        const statusFilter = filterStatusInput.value;
        
        if (!bioIdSearch && !dateFrom && !dateTo && !statusFilter) {
          alert('⚠️ Please enter at least one filter');
          return;
        }

        if (dateFrom && dateTo && dateFrom > dateTo) {
          alert('⚠️ From date cannot be after To date');
          return;
        }

        let filtered = allRecords;

        if (bioIdSearch) {
          filtered = filtered.filter(record => 
            record.bio_id.toLowerCase().includes(bioIdSearch)
          );
        }

        if (dateFrom && dateTo) {
          filtered = filtered.filter(record => 
            record.date >= dateFrom && record.date <= dateTo
          );
        } else if (dateFrom) {
          filtered = filtered.filter(record => record.date >= dateFrom);
        } else if (dateTo) {
          filtered = filtered.filter(record => record.date <= dateTo);
        }

        // Status filter
        if (statusFilter) {
          if (statusFilter === 'no_time_in') {
            filtered = filtered.filter(record => record.no_time_in === true);
          } else if (statusFilter === 'no_time_out') {
            filtered = filtered.filter(record => record.no_time_out === true);
          } else {
            filtered = filtered.filter(record => record.status === statusFilter);
          }
        }

        if (filtered.length === 0) {
          let filterMessage = 'No records found';
          if (bioIdSearch) filterMessage += ` for Bio ID: ${bioIdSearch}`;
          if (dateFrom || dateTo) {
            filterMessage += ` between ${dateFrom || 'any date'} and ${dateTo || 'any date'}`;
          }
          if (statusFilter) filterMessage += ` with status: ${statusFilter}`;
          noDataDiv.textContent = `📭 ${filterMessage}`;
          noDataDiv.style.display = 'block';
          tableContainer.style.display = 'none';
          paginationSection.style.display = 'none';
          recordCount.textContent = '0 records';
        } else {
          allRecords = filtered;
          currentPage = 1;
          displayPage();
        }
      }

      function clearFilter() {
        searchBioIdInput.value = '';
        filterDateFromInput.value = '';
        filterDateToInput.value = '';
        filterStatusInput.value = '';
        loadRecords();
      }

      function handlePhotoError(img, recordId) {
        img.onerror = null;
        img.src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2280%22 height=%2280%22%3E%3Crect fill=%22%23ddd%22 width=%2280%22 height=%2280%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23999%22 font-size=%2210%22%3ELoad Failed%3C/text%3E%3C/svg%3E';
      }

      function buildAttendanceMap(records) {
        const map = new Map();
        records.forEach(record => {
          if (!record.bio_id || !record.date) return;
          if (!record.time_in || !record.time_in.trim()) return;

          const dateOnly = record.date;
          if (!map.has(record.bio_id)) {
            map.set(record.bio_id, new Set());
          }
          map.get(record.bio_id).add(dateOnly);
        });
        return map;
      }

      function toLocalISO(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
      }

      function getYesterdayISO(isoDate) {
        // Parse ISO date without creating a UTC date - work in local time
        const [year, month, day] = isoDate.split('-').map(Number);
        const date = new Date(year, month - 1, day);
        date.setDate(date.getDate() - 1);
        return toLocalISO(date);
      }

      function getTomorrowISO(isoDate) {
        // Parse ISO date without creating a UTC date - work in local time
        const [year, month, day] = isoDate.split('-').map(Number);
        const date = new Date(year, month - 1, day);
        date.setDate(date.getDate() + 1);
        return toLocalISO(date);
      }

      function isRegularHoliday(isoDate) {
        const regularHolidays = [
          '2026-01-01', '2026-04-09', '2026-05-01', '2026-05-27', '2026-06-12',
          '2026-08-31', '2026-11-30', '2026-12-25', '2026-12-30'
        ];
        return regularHolidays.includes(isoDate);
      }

      function isSpecialHoliday(isoDate) {
        const specialHolidays = [
          '2026-02-25', '2026-03-08', '2026-11-01', '2026-12-31'
        ];
        return specialHolidays.includes(isoDate);
      }

      function getPayMultiplierForDate(date, record, attendanceMap) {
        const isoDate = toLocalISO(date);
        const dayOfWeek = date.getUTCDay();

        if (isRegularHoliday(isoDate)) {
          if (record && record.time_in && record.time_out && record.bio_id) {
            // Calculate day before and after from ISO date string
            const dayBefore = getYesterdayISO(isoDate);
            const dayAfter = getTomorrowISO(isoDate);
            
            const bioId = record.bio_id;
            const bioIdAttendance = attendanceMap.get(bioId);
            
            // Double pay only if they worked day before AND day after the holiday
            if (bioIdAttendance && bioIdAttendance.has(dayBefore) && bioIdAttendance.has(dayAfter)) {
              return { multiplier: 2.0, label: 'Double Pay (Regular Holiday)' };
            }
          }
          return { multiplier: 1.0, label: 'Regular Holiday' };
        }

        if (isSpecialHoliday(isoDate)) {
          return { multiplier: 1.3, label: 'Special Holiday' };
        }

        return { multiplier: 1.0, label: 'Regular' };
      }

      function getHolidayOffInfo(dateString) {
        const regularHolidays = [
          '2026-01-01', '2026-04-09', '2026-05-01', '2026-05-27', '2026-06-12',
          '2026-08-31', '2026-11-30', '2026-12-25', '2026-12-30'
        ];
        const specialHolidays = [
          '2026-02-25', '2026-03-08', '2026-11-01', '2026-12-31'
        ];
        const baseAmount = 611.00;
        if (regularHolidays.includes(dateString)) {
          return { pay: baseAmount, payLabel: 'Regular Holiday' };
        }
        if (specialHolidays.includes(dateString)) {
          const parts = dateString.split('-');
          const d = new Date(parts[0], parts[1] - 1, parts[2]);
          if (d.getUTCDay() === 0) {
            return { pay: Math.round(baseAmount * 0.50 * 100) / 100, payLabel: 'Special Holiday Rest Day' };
          }
          return { pay: Math.round(baseAmount * 0.30 * 100) / 100, payLabel: 'Special Holiday' };
        }
        return { pay: 0, payLabel: 'Regular' };
      }

      function getPayInfo(inDate, outDate, record, attendanceMap) {
        const rate = 76.375;
        const holidayInfo = getPayMultiplierForDate(inDate, record, attendanceMap);
        const halfDayThreshold = new Date(inDate);
        halfDayThreshold.setHours(18, 30, 0, 0);

        const hasRequestedOvertime = Boolean(
          record?.ot_requested ||
          Number(record?.ot_hours || 0) > 0 ||
          Number(record?.ot_pay || 0) > 0
        );

        if (hasRequestedOvertime && outDate > halfDayThreshold) {
          return { payHours: 8, pay: 8 * rate * holidayInfo.multiplier, payLabel: holidayInfo.label };
        }

        if (!hasRequestedOvertime && outDate > halfDayThreshold) {
          return { payHours: 4, pay: (611 / 2) * holidayInfo.multiplier, payLabel: `${holidayInfo.label} - Half Day` };
        }

        const workStart = new Date(inDate);
        workStart.setHours(9, 0, 0, 0);
        const lunchStart = new Date(inDate);
        lunchStart.setHours(12, 0, 0, 0);
        const lunchEnd = new Date(inDate);
        lunchEnd.setHours(13, 0, 0, 0);
        const workEnd = new Date(inDate);
        workEnd.setHours(18, 0, 0, 0);

        const payStart = new Date(Math.max(inDate.getTime(), workStart.getTime()));
        let payMs = 0;

        if (payStart < lunchStart) {
          payMs += Math.max(0, Math.min(outDate.getTime(), lunchStart.getTime()) - payStart.getTime());
        }

        if (outDate > lunchEnd) {
          const afternoonStart = new Date(Math.max(payStart.getTime(), lunchEnd.getTime()));
          if (afternoonStart < workEnd) {
            payMs += Math.max(0, Math.min(outDate.getTime(), workEnd.getTime()) - afternoonStart.getTime());
          }
        }

        const payHours = payMs / (1000 * 60 * 60);
        return {
          payHours,
          pay: payHours * rate * holidayInfo.multiplier,
          payLabel: holidayInfo.label
        };
      }

      function escapeHtml(text) {
        if (!text) return '';
        const map = {
          '&': '&amp;',
          '<': '&lt;',
          '>': '&gt;',
          '"': '&quot;',
          "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, m => map[m]);
      }

      // Event listeners
      // refreshBtn.addEventListener('click', loadRecords);
      filterBtn.addEventListener('click', filterRecords);
      clearFilterBtn.addEventListener('click', clearFilter);
      prevBtn.addEventListener('click', () => {
        if (currentPage > 1) {
          currentPage--;
          displayPage();
        }
      });
      nextBtn.addEventListener('click', () => {
        if (currentPage < totalPages) {
          currentPage++;
          displayPage();
        }
      });
      recordsPerPageSelect.addEventListener('change', (e) => {
        recordsPerPage = parseInt(e.target.value);
        currentPage = 1;
        displayPage();
      });

      searchBioIdInput.addEventListener('keyup', function(e) {
        if (e.key === 'Enter') {
          filterRecords();
        }
      });
      
      filterDateToInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          filterRecords();
        }
      });

      function updateDownloadButtonState() {
        const bioId = searchBioIdInput.value.trim();
        const dateFrom = filterDateFromInput.value.trim();
        const dateTo = filterDateToInput.value.trim();
        const status = filterStatusInput.value;
        const canDownloadExcel = bioId || dateFrom || dateTo || status;

        if (downloadExcelBtn) {
          downloadExcelBtn.disabled = !canDownloadExcel;
        }
        if (downloadPayslipBtn) {
          downloadPayslipBtn.disabled = !bioId || !hasPayslipForBioId(bioId);
        }
        if (uploadPayslipBtn) {
          uploadPayslipBtn.disabled = !bioId;
        }
      }

      function hasPayslipForBioId(bioId) {
        if (!bioId) return false;
        return allRecords.some(record => record.bio_id === bioId && (record.payslip_file || record.payslip_data));
      }

      function setupDownloadButton(button, label, href) {
        if (!button) return;
        button.addEventListener('click', function() {
          const bioId = searchBioIdInput.value.trim();
          if (!bioId) return;
          const params = new URLSearchParams();
          params.append('bio_id', bioId);

          const dateFrom = filterDateFromInput.value.trim();
          const dateTo = filterDateToInput.value.trim();
          const status = filterStatusInput.value;

          if (dateFrom) params.append('date_from', dateFrom);
          if (dateTo) params.append('date_to', dateTo);
          if (status) params.append('status', status);

          button.disabled = true;
          const originalText = button.innerHTML;
          button.innerHTML = '⏳ Generating...';
          window.location.href = href + '?' + params.toString();
          setTimeout(() => {
            button.disabled = false;
            button.innerHTML = originalText;
          }, 2000);
        });
      }

      function setupPayslipDownloadButton(button, href) {
        if (!button) return;
        button.addEventListener('click', async function() {
          const bioId = searchBioIdInput.value.trim();
          if (!bioId) return;
          button.disabled = true;
          const originalText = button.innerHTML;
          button.innerHTML = '⏳ Preparing PDF...';

          try {
            const response = await fetch(href + '?bio_id=' + encodeURIComponent(bioId) + '&inline=1');
            if (!response.ok) {
              throw new Error('Error generating PDF: ' + response.statusText);
            }
            const blob = await response.blob();
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = 'payslip_' + bioId.replace(/[^a-zA-Z0-9_-]/g, '') + '.pdf';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(url);
          } catch (error) {
            showUploadStatus(error.message, 'danger');
          } finally {
            button.disabled = false;
            button.innerHTML = originalText;
          }
        });
      }

      function setupUploadPayslip() {
        if (!uploadPayslipBtn || !payslipForm) return;

        uploadPayslipBtn.addEventListener('click', function() {
          const bioId = searchBioIdInput.value.trim();
          if (!bioId) {
            alert('Please enter a Bio ID first.');
            return;
          }
          payslipForm.reset();
          payslipBioIdInput.value = bioId;
          attachmentFileName.textContent = '';
          payslipModal.show();
        });

        payslipAttachmentInput.addEventListener('change', function() {
          const file = payslipAttachmentInput.files[0];
          attachmentFileName.textContent = file ? file.name : '';
        });

        payslipForm.addEventListener('submit', function(event) {
          event.preventDefault();
          const bioId = payslipBioIdInput.value.trim();
          if (!bioId) {
            alert('Bio ID is required.');
            return;
          }

          const data = {
            basic_salary: document.getElementById('basic-salary').value,
            basic_salary_days: document.getElementById('basic-salary-days').value,
            overtime: document.getElementById('overtime').value,
            overtime_days: document.getElementById('overtime-days').value,
            legal_holiday: document.getElementById('legal-holiday').value,
            legal_holiday_days: document.getElementById('legal-holiday-days').value,
            legal_holiday_ot: document.getElementById('legal-holiday-ot').value,
            legal_holiday_ot_days: document.getElementById('legal-holiday-ot-days').value,
            special_holiday_30: document.getElementById('special-holiday-30').value,
            special_holiday_30_days: document.getElementById('special-holiday-30-days').value,
            special_holiday_ot: document.getElementById('special-holiday-ot').value,
            special_holiday_ot_days: document.getElementById('special-holiday-ot-days').value,
            weekend_ot: document.getElementById('weekend-ot').value,
            weekend_ot_days: document.getElementById('weekend-ot-days').value,
            performance_bonus: document.getElementById('performance-bonus').value,
            performance_bonus_days: document.getElementById('performance-bonus-days').value,
            adjustments: document.getElementById('adjustments').value,
            adjustments_days: document.getElementById('adjustments-days').value,
            allowance: document.getElementById('allowance').value,
            allowance_days: document.getElementById('allowance-days').value,
            internet_loan_allowance: document.getElementById('internet-loan-allowance').value,
            internet_loan_allowance_days: document.getElementById('internet-loan-allowance-days').value,
            total_earnings: document.getElementById('total-earnings').value,
            sss: document.getElementById('sss').value,
            phic: document.getElementById('phic').value,
            hdmf: document.getElementById('hdmf').value,
            tax: document.getElementById('tax').value,
            sss_loan: document.getElementById('sss-loan').value,
            pagibig_loan: document.getElementById('pagibig-loan').value,
            late_ut: document.getElementById('late-ut').value,
            net_pay: document.getElementById('net-pay').value,
            account_number: document.getElementById('account-number').value,
            bank_name: document.getElementById('bank-name').value
          };

          const formData = new FormData();
          formData.append('bio_id', bioId);
          formData.append('payslip_data', JSON.stringify(data));
          if (payslipAttachmentInput.files[0]) {
            formData.append('payslip', payslipAttachmentInput.files[0]);
          }

          uploadPayslipBtn.disabled = true;
          payslipSubmitBtn.disabled = true;
          payslipSubmitBtn.textContent = 'Saving...';

          fetch('save_payslip.php', {
            method: 'POST',
            body: formData
          })
            .then(response => response.json())
            .then(data => {
              payslipSubmitBtn.disabled = false;
              payslipSubmitBtn.textContent = 'Save Payslip';
              uploadPayslipBtn.disabled = false;
              if (data.success) {
                showUploadStatus('Payslip saved successfully', 'success');
                payslipModal.hide();
                loadRecords();
              } else {
                showUploadStatus(data.error || 'Failed to save payslip', 'danger');
              }
            })
            .catch(error => {
              payslipSubmitBtn.disabled = false;
              payslipSubmitBtn.textContent = 'Save Payslip';
              uploadPayslipBtn.disabled = false;
              showUploadStatus('Upload error: ' + error.message, 'danger');
            });
        });
      }

      // Register modal handlers
      if (openRegisterBtn) {
        openRegisterBtn.addEventListener('click', function() {
          registerForm.reset();
          registerFeedback.style.display = 'none';
          registerModal.show();
        });
      }

      if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
          e.preventDefault();
          const formData = new FormData(registerForm);
          const submitBtn = document.getElementById('register-submit');
          submitBtn.disabled = true;
          submitBtn.textContent = 'Saving...';

          fetch('register_employee_ajax.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Register';
            registerFeedback.style.display = 'block';
            if (data.success) {
              registerFeedback.className = 'alert alert-success';
              registerFeedback.textContent = data.message || 'Registered successfully.';
              registerModal.hide();
              loadRecords();
            } else {
              registerFeedback.className = 'alert alert-danger';
              registerFeedback.textContent = data.error || 'Registration failed.';
            }
          })
          .catch(err => {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Register';
            registerFeedback.style.display = 'block';
            registerFeedback.className = 'alert alert-danger';
            registerFeedback.textContent = 'Network error: ' + err.message;
          });
        });
      }

      function uploadPayslip(bioId, file) {
        const formData = new FormData();
        formData.append('bio_id', bioId);
        formData.append('payslip', file);

        fetch('save_payslip.php', {
          method: 'POST',
          body: formData
        })
          .then(response => response.json())
          .then(data => {
            uploadPayslipBtn.disabled = false;
            uploadPayslipBtn.innerHTML = '📤 Upload Payslip';
            if (data.success) {
              showUploadStatus('Payslip uploaded successfully', 'success');
            } else {
              showUploadStatus(data.error || 'Failed to upload payslip', 'danger');
            }
          })
          .catch(error => {
            uploadPayslipBtn.disabled = false;
            uploadPayslipBtn.innerHTML = '📤 Upload Payslip';
            showUploadStatus('Upload error: ' + error.message, 'danger');
          });
      }

      function showUploadStatus(message, type) {
        const status = document.getElementById('upload-status');
        if (!status) return;
        status.style.display = 'inline-block';
        status.textContent = message;
        status.className = `badge bg-${type} ms-2`;
        setTimeout(() => {
          status.style.display = 'none';
        }, 5000);
      }

      setupPayslipDownloadButton(downloadPayslipBtn, 'download_payslip.php');
      setupDownloadButton(downloadExcelBtn, '📥 Download Excel', 'generate_excel.php');
      setupUploadPayslip();

      searchBioIdInput.addEventListener('input', updateDownloadButtonState);
      updateDownloadButtonState();
      loadRecords();
    </script>
  </body>
</html>