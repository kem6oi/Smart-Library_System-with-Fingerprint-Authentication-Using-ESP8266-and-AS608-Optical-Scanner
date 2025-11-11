<?php
/**
 * Export System - PDF and Excel Reports
 * Provides functionality to export library data to PDF and Excel formats
 */

require_once(__DIR__ . '/rbac.php');

/**
 * Export data to CSV (Excel-compatible)
 *
 * @param array $data Data array
 * @param array $headers Column headers
 * @param string $filename Output filename
 * @return void
 */
function exportToCSV($data, $headers, $filename) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // Write headers
    fputcsv($output, $headers);

    // Write data
    foreach ($data as $row) {
        fputcsv($output, $row);
    }

    fclose($output);
    exit();
}

/**
 * Export issued books report to CSV
 *
 * @param PDO $dbh Database handle
 * @param string $startDate Start date filter
 * @param string $endDate End date filter
 * @param string $status Filter: 'all', 'issued', 'returned', 'overdue'
 * @return void
 */
function exportIssuedBooksCSV($dbh, $startDate = null, $endDate = null, $status = 'all') {
    try {
        $sql = "SELECT
                    i.id, i.BookId, b.BookName, b.ISBNNumber,
                    i.StudentId, s.FullName as StudentName, s.EmailId,
                    i.IssuesDate, i.ReturnDate, i.RetrunStatus, i.fine,
                    CASE
                        WHEN i.RetrunStatus = 1 THEN 'Returned'
                        WHEN i.ReturnDate < CURDATE() THEN 'Overdue'
                        ELSE 'Issued'
                    END as Status
                FROM tblissuedbookdetails i
                INNER JOIN tblbooks b ON b.id = i.BookId
                INNER JOIN tblstudents s ON s.StudentId = i.StudentId
                WHERE 1=1";

        $params = [];

        if ($startDate) {
            $sql .= " AND DATE(i.IssuesDate) >= :start_date";
            $params[':start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND DATE(i.IssuesDate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        switch ($status) {
            case 'issued':
                $sql .= " AND i.RetrunStatus IS NULL AND DATE(i.ReturnDate) >= CURDATE()";
                break;
            case 'returned':
                $sql .= " AND i.RetrunStatus = 1";
                break;
            case 'overdue':
                $sql .= " AND i.RetrunStatus IS NULL AND DATE(i.ReturnDate) < CURDATE()";
                break;
        }

        $sql .= " ORDER BY i.IssuesDate DESC";

        $query = $dbh->prepare($sql);
        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        $headers = [
            'Issue ID', 'Book ID', 'Book Name', 'ISBN', 'Student ID',
            'Student Name', 'Email', 'Issue Date', 'Return Date', 'Status', 'Fine'
        ];

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                $row['id'],
                $row['BookId'],
                $row['BookName'],
                $row['ISBNNumber'],
                $row['StudentId'],
                $row['StudentName'],
                $row['EmailId'],
                $row['IssuesDate'],
                $row['ReturnDate'],
                $row['Status'],
                $row['fine'] ? '$' . number_format($row['fine'], 2) : '$0.00'
            ];
        }

        $filename = 'issued_books_' . date('Y-m-d') . '.csv';
        exportToCSV($data, $headers, $filename);

    } catch (PDOException $e) {
        log_error("Error exporting issued books: " . $e->getMessage());
        die("Export failed");
    }
}

/**
 * Export books list to CSV
 *
 * @param PDO $dbh Database handle
 * @param int $categoryId Category filter
 * @return void
 */
function exportBooksCSV($dbh, $categoryId = null) {
    try {
        $sql = "SELECT
                    b.id, b.BookName, b.ISBNNumber, b.BookPrice,
                    a.AuthorName, c.CategoryName,
                    (SELECT COUNT(*) FROM tblissuedbookdetails WHERE BookId = b.id) as total_borrows,
                    (SELECT COUNT(*) FROM tblissuedbookdetails WHERE BookId = b.id AND RetrunStatus IS NULL) as current_borrows
                FROM tblbooks b
                LEFT JOIN tblauthors a ON a.id = b.AuthorId
                LEFT JOIN tblcategory c ON c.id = b.CatId
                WHERE 1=1";

        if ($categoryId) {
            $sql .= " AND b.CatId = :category_id";
        }

        $sql .= " ORDER BY b.BookName";

        $query = $dbh->prepare($sql);
        if ($categoryId) {
            $query->bindParam(':category_id', $categoryId, PDO::PARAM_INT);
        }
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        $headers = [
            'Book ID', 'Book Name', 'ISBN', 'Author', 'Category',
            'Price', 'Total Borrows', 'Currently Borrowed'
        ];

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                $row['id'],
                $row['BookName'],
                $row['ISBNNumber'],
                $row['AuthorName'],
                $row['CategoryName'],
                '$' . number_format($row['BookPrice'], 2),
                $row['total_borrows'],
                $row['current_borrows']
            ];
        }

        $filename = 'books_list_' . date('Y-m-d') . '.csv';
        exportToCSV($data, $headers, $filename);

    } catch (PDOException $e) {
        log_error("Error exporting books: " . $e->getMessage());
        die("Export failed");
    }
}

/**
 * Export students list to CSV
 *
 * @param PDO $dbh Database handle
 * @param int $status Status filter (0=blocked, 1=active, null=all)
 * @return void
 */
function exportStudentsCSV($dbh, $status = null) {
    try {
        $sql = "SELECT
                    s.StudentId, s.FullName, s.EmailId, s.MobileNumber,
                    s.RegDate, s.Status,
                    (SELECT COUNT(*) FROM tblissuedbookdetails WHERE StudentId = s.StudentId) as total_borrows,
                    (SELECT COUNT(*) FROM tblissuedbookdetails WHERE StudentId = s.StudentId AND RetrunStatus IS NULL) as current_borrows
                FROM tblstudents s
                WHERE 1=1";

        if ($status !== null) {
            $sql .= " AND s.Status = :status";
        }

        $sql .= " ORDER BY s.FullName";

        $query = $dbh->prepare($sql);
        if ($status !== null) {
            $query->bindParam(':status', $status, PDO::PARAM_INT);
        }
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        $headers = [
            'Student ID', 'Full Name', 'Email', 'Mobile', 'Registration Date',
            'Status', 'Total Borrows', 'Current Borrows'
        ];

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                $row['StudentId'],
                $row['FullName'],
                $row['EmailId'],
                $row['MobileNumber'],
                $row['RegDate'],
                $row['Status'] == 1 ? 'Active' : 'Blocked',
                $row['total_borrows'],
                $row['current_borrows']
            ];
        }

        $filename = 'students_list_' . date('Y-m-d') . '.csv';
        exportToCSV($data, $headers, $filename);

    } catch (PDOException $e) {
        log_error("Error exporting students: " . $e->getMessage());
        die("Export failed");
    }
}

/**
 * Export overdue books report to CSV
 *
 * @param PDO $dbh Database handle
 * @return void
 */
function exportOverdueBooksCSV($dbh) {
    try {
        $finePerDay = getSystemConfig($dbh, 'fine_per_day', 0.50);
        $maxFine = getSystemConfig($dbh, 'max_fine_amount', 10.00);

        $sql = "SELECT
                    i.id, b.BookName, b.ISBNNumber,
                    s.StudentId, s.FullName, s.EmailId, s.MobileNumber,
                    i.IssuesDate, i.ReturnDate,
                    DATEDIFF(CURDATE(), i.ReturnDate) as days_overdue,
                    LEAST(DATEDIFF(CURDATE(), i.ReturnDate) * :fine_per_day, :max_fine) as fine_amount
                FROM tblissuedbookdetails i
                INNER JOIN tblbooks b ON b.id = i.BookId
                INNER JOIN tblstudents s ON s.StudentId = i.StudentId
                WHERE i.RetrunStatus IS NULL
                AND DATE(i.ReturnDate) < CURDATE()
                ORDER BY days_overdue DESC";

        $query = $dbh->prepare($sql);
        $query->bindParam(':fine_per_day', $finePerDay);
        $query->bindParam(':max_fine', $maxFine);
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        $headers = [
            'Issue ID', 'Book Name', 'ISBN', 'Student ID', 'Student Name',
            'Email', 'Mobile', 'Issue Date', 'Due Date', 'Days Overdue', 'Fine Amount'
        ];

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                $row['id'],
                $row['BookName'],
                $row['ISBNNumber'],
                $row['StudentId'],
                $row['FullName'],
                $row['EmailId'],
                $row['MobileNumber'],
                $row['IssuesDate'],
                $row['ReturnDate'],
                $row['days_overdue'],
                '$' . number_format($row['fine_amount'], 2)
            ];
        }

        $filename = 'overdue_books_' . date('Y-m-d') . '.csv';
        exportToCSV($data, $headers, $filename);

    } catch (PDOException $e) {
        log_error("Error exporting overdue books: " . $e->getMessage());
        die("Export failed");
    }
}

/**
 * Export audit logs to CSV
 *
 * @param PDO $dbh Database handle
 * @param string $startDate Start date
 * @param string $endDate End date
 * @param string $userType User type filter
 * @param string $category Category filter
 * @return void
 */
function exportAuditLogsCSV($dbh, $startDate = null, $endDate = null, $userType = null, $category = null) {
    try {
        $sql = "SELECT
                    id, user_id, user_type, action, action_category,
                    description, ip_address, geo_location, status, created_at
                FROM tblaudit_logs
                WHERE 1=1";

        $params = [];

        if ($startDate) {
            $sql .= " AND DATE(created_at) >= :start_date";
            $params[':start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND DATE(created_at) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        if ($userType) {
            $sql .= " AND user_type = :user_type";
            $params[':user_type'] = $userType;
        }

        if ($category) {
            $sql .= " AND action_category = :category";
            $params[':category'] = $category;
        }

        $sql .= " ORDER BY created_at DESC LIMIT 5000";

        $query = $dbh->prepare($sql);
        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        $headers = [
            'Log ID', 'User ID', 'User Type', 'Action', 'Category',
            'Description', 'IP Address', 'Location', 'Status', 'Timestamp'
        ];

        $data = [];
        foreach ($results as $row) {
            $data[] = [
                $row['id'],
                $row['user_id'],
                $row['user_type'],
                $row['action'],
                $row['action_category'],
                $row['description'],
                $row['ip_address'],
                $row['geo_location'],
                $row['status'],
                $row['created_at']
            ];
        }

        $filename = 'audit_logs_' . date('Y-m-d') . '.csv';
        exportToCSV($data, $headers, $filename);

    } catch (PDOException $e) {
        log_error("Error exporting audit logs: " . $e->getMessage());
        die("Export failed");
    }
}

/**
 * Generate simple HTML to PDF conversion
 *
 * @param string $html HTML content
 * @param string $filename Output filename
 * @param string $title Document title
 * @return void
 */
function generatePDFFromHTML($html, $filename, $title = 'Library Report') {
    // Since TCPDF may not be available, we'll create a simple HTML page
    // that can be printed as PDF using browser's print function

    header('Content-Type: text/html; charset=utf-8');

    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>' . htmlspecialchars($title) . '</title>
    <style>
        @media print {
            .no-print { display: none; }
        }
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #337ab7;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .print-btn {
            margin: 20px 0;
            padding: 10px 20px;
            background: #337ab7;
            color: white;
            border: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
    </div>
    <div class="header">
        <h1>Smart Library System</h1>
        <h2>' . htmlspecialchars($title) . '</h2>
        <p>Generated on: ' . date('F j, Y g:i A') . '</p>
    </div>
    ' . $html . '
    <div class="no-print">
        <button class="print-btn" onclick="window.print()">Print / Save as PDF</button>
    </div>
</body>
</html>';
    exit();
}

/**
 * Export issued books report to PDF
 *
 * @param PDO $dbh Database handle
 * @param string $startDate Start date filter
 * @param string $endDate End date filter
 * @param string $status Filter status
 * @return void
 */
function exportIssuedBooksPDF($dbh, $startDate = null, $endDate = null, $status = 'all') {
    try {
        $sql = "SELECT
                    i.id, b.BookName, s.FullName as StudentName,
                    i.IssuesDate, i.ReturnDate,
                    CASE
                        WHEN i.RetrunStatus = 1 THEN 'Returned'
                        WHEN i.ReturnDate < CURDATE() THEN 'Overdue'
                        ELSE 'Issued'
                    END as Status
                FROM tblissuedbookdetails i
                INNER JOIN tblbooks b ON b.id = i.BookId
                INNER JOIN tblstudents s ON s.StudentId = i.StudentId
                WHERE 1=1";

        $params = [];

        if ($startDate) {
            $sql .= " AND DATE(i.IssuesDate) >= :start_date";
            $params[':start_date'] = $startDate;
        }

        if ($endDate) {
            $sql .= " AND DATE(i.IssuesDate) <= :end_date";
            $params[':end_date'] = $endDate;
        }

        switch ($status) {
            case 'issued':
                $sql .= " AND i.RetrunStatus IS NULL AND DATE(i.ReturnDate) >= CURDATE()";
                break;
            case 'returned':
                $sql .= " AND i.RetrunStatus = 1";
                break;
            case 'overdue':
                $sql .= " AND i.RetrunStatus IS NULL AND DATE(i.ReturnDate) < CURDATE()";
                break;
        }

        $sql .= " ORDER BY i.IssuesDate DESC LIMIT 500";

        $query = $dbh->prepare($sql);
        foreach ($params as $key => $value) {
            $query->bindValue($key, $value);
        }
        $query->execute();
        $results = $query->fetchAll(PDO::FETCH_ASSOC);

        $html = '<table>
            <thead>
                <tr>
                    <th>Issue ID</th>
                    <th>Book Name</th>
                    <th>Student Name</th>
                    <th>Issue Date</th>
                    <th>Return Date</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($results as $row) {
            $html .= '<tr>
                <td>' . htmlspecialchars($row['id']) . '</td>
                <td>' . htmlspecialchars($row['BookName']) . '</td>
                <td>' . htmlspecialchars($row['StudentName']) . '</td>
                <td>' . htmlspecialchars($row['IssuesDate']) . '</td>
                <td>' . htmlspecialchars($row['ReturnDate']) . '</td>
                <td>' . htmlspecialchars($row['Status']) . '</td>
            </tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<p><strong>Total Records:</strong> ' . count($results) . '</p>';

        generatePDFFromHTML($html, 'issued_books_' . date('Y-m-d') . '.pdf', 'Issued Books Report');

    } catch (PDOException $e) {
        log_error("Error exporting issued books PDF: " . $e->getMessage());
        die("Export failed");
    }
}
?>
