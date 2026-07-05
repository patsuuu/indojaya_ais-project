<?php
require_once 'auth_check.php';
require_once 'db_connect.php';
require_once __DIR__ . '/vendor/autoload.php';

$inline = isset($_GET['inline']) && $_GET['inline'] === '1';
$bioId = isset($_GET['bio_id']) ? trim($_GET['bio_id']) : '';
if ($bioId === '') {
    http_response_code(400);
    die('Bio ID is required.');
}

if (!isAdmin()) {
    http_response_code(403);
    die('Forbidden.');
}

$stmt = $conn->prepare("SELECT payslip_file, payslip_data, first_name, last_name, department, account_stage, date FROM records WHERE bio_id = ? ORDER BY date DESC, id DESC LIMIT 1");
if (!$stmt) {
    http_response_code(500);
    die('Query error: ' . $conn->error);
}
$stmt->bind_param('s', $bioId);
$stmt->execute();
$result = $stmt->get_result();
if (!$result || $result->num_rows === 0) {
    http_response_code(404);
    die('No payslip data found for this Bio ID.');
}
$row = $result->fetch_assoc();
$stmt->close();

if (!empty($row['payslip_file'])) {
    $payslipFile = $row['payslip_file'];
    $filepath = __DIR__ . '/uploads/' . $payslipFile;
    if (!file_exists($filepath) || !is_readable($filepath)) {
        http_response_code(404);
        die('Payslip file not found.');
    }

    $mimeType = mime_content_type($filepath) ?: 'application/octet-stream';
    $disposition = 'attachment';
    if ($inline && strpos($mimeType, 'pdf') !== false) {
        $disposition = 'inline';
    } elseif ($inline && strpos($mimeType, 'image/') === 0) {
        $disposition = 'inline';
    }

    if ($disposition === 'inline') {
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: inline; filename="' . basename($payslipFile) . '"');
    } else {
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . basename($payslipFile) . '"');
    }
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
    $conn->close();
    exit;
}

if (empty($row['payslip_data'])) {
    http_response_code(404);
    die('No payslip data found for this Bio ID.');
}

$payslipData = json_decode($row['payslip_data'], true);
if (!is_array($payslipData)) {
    http_response_code(500);
    die('Payslip data is invalid.');
}

function escapeText($value) {
    return htmlspecialchars($value === null ? '' : $value, ENT_QUOTES, 'UTF-8');
}

$employeeName = trim((string)($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
$designation = $row['account_stage'] ?: $row['department'] ?: '';
$recordDate = $row['date'] ?: date('Y-m-d');
$recordDateObj = DateTimeImmutable::createFromFormat('Y-m-d', $recordDate) ?: new DateTimeImmutable();
$payDate = $recordDateObj->format('F j, Y');
$dayOfMonth = (int)$recordDateObj->format('j');
$lastDay = $recordDateObj->format('t');
if ($dayOfMonth <= 15) {
    $payrollPeriod = $recordDateObj->format('F 1, Y') . ' - ' . $recordDateObj->format('F 15, Y');
} else {
    $payrollPeriod = $recordDateObj->format('F 16, Y') . ' - ' . $recordDateObj->format('F ' . $lastDay . ', Y');
}

$bioIdEscaped = escapeText($bioId);
$employeeNameEscaped = escapeText($employeeName);
$designationEscaped = escapeText($designation);
$payDateEscaped = escapeText($payDate);
$payrollPeriodEscaped = escapeText($payrollPeriod);
$accountNumberEscaped = escapeText($payslipData['account_number'] ?? '');
$bankNameEscaped = escapeText($payslipData['bank_name'] ?? '');
$basicSalaryEscaped = escapeText($payslipData['basic_salary'] ?? '0.00');
$basicSalaryDaysEscaped = escapeText($payslipData['basic_salary_days'] ?? '');
$overtimeEscaped = escapeText($payslipData['overtime'] ?? '0.00');
$overtimeDaysEscaped = escapeText($payslipData['overtime_days'] ?? '');
$legalHolidayEscaped = escapeText($payslipData['legal_holiday'] ?? '0.00');
$legalHolidayDaysEscaped = escapeText($payslipData['legal_holiday_days'] ?? '');
$legalHolidayOtEscaped = escapeText($payslipData['legal_holiday_ot'] ?? '0.00');
$legalHolidayOtDaysEscaped = escapeText($payslipData['legal_holiday_ot_days'] ?? '');
$specialHoliday30Escaped = escapeText($payslipData['special_holiday_30'] ?? '0.00');
$specialHoliday30DaysEscaped = escapeText($payslipData['special_holiday_30_days'] ?? '');
$specialHolidayOtEscaped = escapeText($payslipData['special_holiday_ot'] ?? '0.00');
$specialHolidayOtDaysEscaped = escapeText($payslipData['special_holiday_ot_days'] ?? '');
$weekendOtEscaped = escapeText($payslipData['weekend_ot'] ?? '0.00');
$weekendOtDaysEscaped = escapeText($payslipData['weekend_ot_days'] ?? '');
$performanceBonusEscaped = escapeText($payslipData['performance_bonus'] ?? '0.00');
$performanceBonusDaysEscaped = escapeText($payslipData['performance_bonus_days'] ?? '');
$adjustmentsEscaped = escapeText($payslipData['adjustments'] ?? '0.00');
$adjustmentsDaysEscaped = escapeText($payslipData['adjustments_days'] ?? '');
$allowanceEscaped = escapeText($payslipData['allowance'] ?? '0.00');
$allowanceDaysEscaped = escapeText($payslipData['allowance_days'] ?? '');
$internetLoanAllowanceEscaped = escapeText($payslipData['internet_loan_allowance'] ?? '0.00');
$internetLoanAllowanceDaysEscaped = escapeText($payslipData['internet_loan_allowance_days'] ?? '');
$sssEscaped = escapeText($payslipData['sss'] ?? '0.00');
$phicEscaped = escapeText($payslipData['phic'] ?? '0.00');
$hdmfEscaped = escapeText($payslipData['hdmf'] ?? '0.00');
$taxEscaped = escapeText($payslipData['tax'] ?? '0.00');
$sssLoanEscaped = escapeText($payslipData['sss_loan'] ?? '0.00');
$pagibigLoanEscaped = escapeText($payslipData['pagibig_loan'] ?? '0.00');
$lateUtEscaped = escapeText($payslipData['late_ut'] ?? '0.00');
$netPayEscaped = escapeText($payslipData['net_pay'] ?? '0.00');
$totalEarningsEscaped = escapeText($payslipData['total_earnings'] ?? '0.00');

$html = <<<HTML
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Payslip - {$bioIdEscaped}</title>
  <style>
    @page { size: A4 portrait; margin: 9mm; }
    html, body { margin: 0; padding: 0; width: 100%; font-family: Arial, Helvetica, sans-serif; color: #222; }
    body { background: #f4f4f4; }
    .sheet { background: #fff; border: 1px solid #ccc; padding: 10px; max-width: 100%; margin: 0 auto; box-sizing: border-box; }
    .d-flex { display: flex; }
    .justify-content-between { justify-content: space-between; }
    .align-items-center { align-items: center; }
    .text-end { text-align: right; }
    .sheet h1 { margin: 0 0 4px; font-size: 1rem; letter-spacing: 0.5px; }
    .sheet .sub { color: #555; font-size: 0.82rem; margin-top: 2px; }
    .details-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin: 12px 0; }
    .details-grid .box { border: 1px solid #ddd; padding: 8px 10px; background: #fcfcfc; min-height: 56px; }
    .details-grid .box strong { display: block; margin-bottom: 4px; font-size: 0.88rem; }
    table { width: 100%; border-collapse: collapse; font-size: 0.74rem; line-height: 1.18; }
    th, td { border: 1px solid #999; padding: 5px 6px; vertical-align: top; }
    th { background: #dde7f5; font-weight: 600; }
    td { word-break: break-word; }
    .cell-right { text-align: right; white-space: nowrap; }
    .total-row { font-weight: 700; background: #fff3cd; }
    .note { margin-top: 10px; padding: 8px 10px; background: #fff7e6; border: 1px solid #ffe3a8; font-size: 0.78rem; }
    .sheet-content { page-break-inside: avoid; }
  </style>
</head>
<body>
  <div class="sheet">
    <div class="d-flex justify-content-between align-items-center">
      <div>
        <h1>Indojaya Philippines</h1>
        <div class="sub">PAYSLIP</div>
      </div>
      <div class="text-end">
        <div><strong>Pay Date:</strong> {$payDateEscaped}</div>
        <div><strong>Payroll Period:</strong> {$payrollPeriodEscaped}</div>
      </div>
    </div>

    <div class="details-grid">
      <div class="box">
        <strong>BIO ID NO:</strong>
        {$bioIdEscaped}
      </div>
      <div class="box">
        <strong>Employee Name:</strong>
        {$employeeNameEscaped}
      </div>
      <div class="box">
        <strong>Designation:</strong>
        {$designationEscaped}
      </div>
      <div class="box">
        <strong>Disbursement Account</strong>
        Account No: {$accountNumberEscaped}<br>
        Bank Name: {$bankNameEscaped}
      </div>
    </div>

    <table>
      <thead>
        <tr>
          <th>ITEMS</th>
          <th>NO. OF DAYS</th>
          <th class="cell-right">AMOUNT</th>
          <th>ITEMS</th>
          <th class="cell-right">AMOUNT</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>Basic Salary</td>
          <td class="cell-right">{$basicSalaryDaysEscaped}</td>
          <td class="cell-right">₱ {$basicSalaryEscaped}</td>
          <td>SSS</td>
          <td class="cell-right">₱ {$sssEscaped}</td>
        </tr>
        <tr>
          <td>Overtime</td>
          <td class="cell-right">{$overtimeDaysEscaped}</td>
          <td class="cell-right">₱ {$overtimeEscaped}</td>
          <td>PHIC</td>
          <td class="cell-right">₱ {$phicEscaped}</td>
        </tr>
        <tr>
          <td>Legal Holiday</td>
          <td class="cell-right">{$legalHolidayDaysEscaped}</td>
          <td class="cell-right">₱ {$legalHolidayEscaped}</td>
          <td>HDMF</td>
          <td class="cell-right">₱ {$hdmfEscaped}</td>
        </tr>
        <tr>
          <td>Legal Holiday OT</td>
          <td class="cell-right">{$legalHolidayOtDaysEscaped}</td>
          <td class="cell-right">₱ {$legalHolidayOtEscaped}</td>
          <td>Tax</td>
          <td class="cell-right">₱ {$taxEscaped}</td>
        </tr>
        <tr>
          <td>Special Holiday (30%)</td>
          <td class="cell-right">{$specialHoliday30DaysEscaped}</td>
          <td class="cell-right">₱ {$specialHoliday30Escaped}</td>
          <td>SSS Loan</td>
          <td class="cell-right">₱ {$sssLoanEscaped}</td>
        </tr>
        <tr>
          <td>Special Holiday OT</td>
          <td class="cell-right">{$specialHolidayOtDaysEscaped}</td>
          <td class="cell-right">₱ {$specialHolidayOtEscaped}</td>
          <td>Pagi-ibig Loan</td>
          <td class="cell-right">₱ {$pagibigLoanEscaped}</td>
        </tr>
        <tr>
          <td>Weekend OT</td>
          <td class="cell-right">{$weekendOtDaysEscaped}</td>
          <td class="cell-right">₱ {$weekendOtEscaped}</td>
          <td>Late/UT</td>
          <td class="cell-right">₱ {$lateUtEscaped}</td>
        </tr>
        <tr>
          <td>Performance Bonus</td>
          <td class="cell-right">{$performanceBonusDaysEscaped}</td>
          <td class="cell-right">₱ {$performanceBonusEscaped}</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td>Adjustments</td>
          <td class="cell-right">{$adjustmentsDaysEscaped}</td>
          <td class="cell-right">₱ {$adjustmentsEscaped}</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td>Allowance</td>
          <td class="cell-right">{$allowanceDaysEscaped}</td>
          <td class="cell-right">₱ {$allowanceEscaped}</td>
          <td></td>
          <td></td>
        </tr>
        <tr>
          <td>Internet / Loan Allowance</td>
          <td class="cell-right">{$internetLoanAllowanceDaysEscaped}</td>
          <td class="cell-right">₱ {$internetLoanAllowanceEscaped}</td>
          <td></td>
          <td></td>
        </tr>
      </tbody>
      <tfoot>
        <tr class="total-row">
          <td>Total</td>
          <td></td>
          <td class="cell-right">₱ {$totalEarningsEscaped}</td>
          <td>Net Pay</td>
          <td class="cell-right">₱ {$netPayEscaped}</td>
        </tr>
      </tfoot>
    </table>

    <div class="note">
      <strong>DISBURSEMENT ACCOUNT</strong>
      Account No.: {$accountNumberEscaped}<br>
      Bank Name: {$bankNameEscaped}
    </div>
  </div>
</body>
</html>
HTML;

if ($inline) {
    require_once __DIR__ . '/vendor/autoload.php';
    $dompdf = new Dompdf\Dompdf();
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="payslip_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $bioId) . '.pdf"');
    echo $dompdf->output();
    $conn->close();
    exit;
}

header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: inline; filename="payslip_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $bioId) . '.html"');
echo $html;
$conn->close();
exit;
?>