<?php
// C:\xampp\htdocs\jaria\admin\financials.php
session_start();

date_default_timezone_set('Asia/Jakarta');

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once dirname(__DIR__) . '/includes/database.php';
require_once dirname(__DIR__) . '/includes/functions.php';

$page_title = "Laporan Keuangan & Manajemen Kas — JARIA Admin";

$months_id = [
    1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni',
    7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
];

$current_realtime_month = (int)date('n');
$current_realtime_year  = (int)date('Y');

$selected_month = isset($_GET['month']) ? (int)$_GET['month'] : $current_realtime_month;
$selected_year  = isset($_GET['year']) ? (int)$_GET['year'] : $current_realtime_year;
$search_keyword = isset($_GET['q']) ? trim($_GET['q']) : '';

$success_msg = $_SESSION['success_msg'] ?? '';
$error_msg   = $_SESSION['error_msg'] ?? '';
unset($_SESSION['success_msg'], $_SESSION['error_msg']);

// Fetch daftar produk untuk dropdown modal
$all_products = [];
try {
    $all_products = $pdo->query("SELECT id, name, price FROM products ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// DETEKSI STRUKTUR KOLOM DARI DATABASE
$stmt_cols = $pdo->query("SHOW COLUMNS FROM orders");
$cols = $stmt_cols->fetchAll(PDO::FETCH_COLUMN);

$phone_col_sql = "'-'";
if (in_array('whatsapp', $cols)) $phone_col_sql = "o.whatsapp";
elseif (in_array('customer_phone', $cols)) $phone_col_sql = "o.customer_phone";
elseif (in_array('phone', $cols)) $phone_col_sql = "o.phone";
elseif (in_array('no_hp', $cols)) $phone_col_sql = "o.no_hp";

$customer_name_col_sql = "'Pelanggan Umum'";
if (in_array('customer_name', $cols)) $customer_name_col_sql = "o.customer_name";
elseif (in_array('name', $cols)) $customer_name_col_sql = "o.name";
elseif (in_array('buyer_name', $cols)) $customer_name_col_sql = "o.buyer_name";
elseif (in_array('nama_pemesan', $cols)) $customer_name_col_sql = "o.nama_pemesan";
elseif (in_array('nama', $cols)) $customer_name_col_sql = "o.nama";

$ord_num_col_sql = in_array('order_number', $cols) ? "o.order_number" : "CONCAT('ORD-', o.id)";

// DETEKSI KOLOM KAS
$stmt_ct_cols = $pdo->query("SHOW COLUMNS FROM cash_transactions");
$ct_cols = $stmt_ct_cols->fetchAll(PDO::FETCH_COLUMN);

$ct_person_col_sql = "'-'";
if (in_array('person_name', $ct_cols)) $ct_person_col_sql = "person_name";
elseif (in_array('penerima', $ct_cols)) $ct_person_col_sql = "penerima";
elseif (in_array('donatur', $ct_cols)) $ct_person_col_sql = "donatur";
elseif (in_array('nama', $ct_cols)) $ct_person_col_sql = "nama";

// ----------------------------------------------------
// PROSES CRUD (CREATE, UPDATE, DELETE)
// ----------------------------------------------------

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_transaction') {
    $type        = isset($_POST['type']) ? sanitize($_POST['type']) : 'income';
    $category    = isset($_POST['category']) ? sanitize($_POST['category']) : 'Donasi / Modal';
    $person_name = isset($_POST['person_name']) ? sanitize($_POST['person_name']) : '';
    $amount      = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
    $date        = isset($_POST['transaction_date']) ? sanitize($_POST['transaction_date']) : date('Y-m-d');

    if ($amount > 0 && !empty($date)) {
        try {
            if ($ct_person_col_sql !== "'-'") {
                $stmt = $pdo->prepare("INSERT INTO cash_transactions (type, category, {$ct_person_col_sql}, amount, description, transaction_date, created_at) VALUES (:type, :category, :person, :amount, :description, :transaction_date, NOW())");
                $stmt->execute([
                    'type'             => $type,
                    'category'         => $category,
                    'person'           => $person_name,
                    'amount'           => $amount,
                    'description'      => $description,
                    'transaction_date' => $date
                ]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cash_transactions (type, category, amount, description, transaction_date, created_at) VALUES (:type, :category, :amount, :description, :transaction_date, NOW())");
                $stmt->execute([
                    'type'             => $type,
                    'category'         => $category,
                    'amount'           => $amount,
                    'description'      => $description,
                    'transaction_date' => $date
                ]);
            }
            
            $tx_m = (int)date('m', strtotime($date));
            $tx_y = (int)date('Y', strtotime($date));

            if ($type === 'expense') {
                $_SESSION['success_msg'] = "Transaksi pengeluaran kas berhasil dicatat!";
            } else {
                $_SESSION['success_msg'] = "Transaksi pemasukan kas berhasil dicatat!";
            }

            header("Location: financials.php?month=$tx_m&year=$tx_y");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Gagal menyimpan jurnal: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_msg'] = "Nominal dan tanggal transaksi wajib diisi.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_transaction') {
    $id          = (int)$_POST['transaction_id'];
    $type        = sanitize($_POST['type'] ?? 'income');
    $category    = sanitize($_POST['category'] ?? 'Operasional');
    $person_name = sanitize($_POST['person_name'] ?? '');
    $amount      = (float)($_POST['amount'] ?? 0);
    $description = sanitize($_POST['description'] ?? '');
    $date        = sanitize($_POST['transaction_date'] ?? date('Y-m-d'));

    if ($id > 0 && $amount > 0) {
        try {
            if ($ct_person_col_sql !== "'-'") {
                $stmt = $pdo->prepare("UPDATE cash_transactions SET type = :type, category = :category, {$ct_person_col_sql} = :person, amount = :amount, description = :description, transaction_date = :transaction_date WHERE id = :id");
                $stmt->execute([
                    'type'             => $type,
                    'category'         => $category,
                    'person'           => $person_name,
                    'amount'           => $amount,
                    'description'      => $description,
                    'transaction_date' => $date,
                    'id'               => $id
                ]);
            } else {
                $stmt = $pdo->prepare("UPDATE cash_transactions SET type = :type, category = :category, amount = :amount, description = :description, transaction_date = :transaction_date WHERE id = :id");
                $stmt->execute([
                    'type'             => $type,
                    'category'         => $category,
                    'amount'           => $amount,
                    'description'      => $description,
                    'transaction_date' => $date,
                    'id'               => $id
                ]);
            }

            if ($type === 'expense') {
                $_SESSION['success_msg'] = "Data pengeluaran kas berhasil diperbarui!";
            } else {
                $_SESSION['success_msg'] = "Data pemasukan kas berhasil diperbarui!";
            }

            header("Location: financials.php?month=$selected_month&year=$selected_year");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Gagal memperbarui jurnal: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_order_detail') {
    $order_id       = (int)$_POST['order_id'];
    $customer_name  = sanitize($_POST['customer_name'] ?? '');
    $phone          = sanitize($_POST['phone'] ?? '');
    $payment_status = sanitize($_POST['payment_status'] ?? 'paid');
    $order_date     = sanitize($_POST['created_at'] ?? date('Y-m-d H:i:s'));

    if ($order_id > 0) {
        try {
            $update_fields = ["payment_status = :status", "created_at = :cdate"];
            $params_ord = ['status' => $payment_status, 'cdate' => $order_date, 'oid' => $order_id];

            if ($phone_col_sql !== "'-'") {
                $phone_col_name = str_replace('o.', '', $phone_col_sql);
                $update_fields[] = "{$phone_col_name} = :phone";
                $params_ord['phone'] = $phone;
            }

            if ($customer_name_col_sql !== "'Pelanggan Umum'") {
                $cust_col_name = str_replace('o.', '', $customer_name_col_sql);
                $update_fields[] = "{$cust_col_name} = :cname";
                $params_ord['cname'] = $customer_name;
            }

            $sql_ord = "UPDATE orders SET " . implode(', ', $update_fields) . " WHERE id = :oid";
            $stmt_o = $pdo->prepare($sql_ord);
            $stmt_o->execute($params_ord);

            $_SESSION['success_msg'] = "Detail transaksi pesanan berhasil diperbarui!";
            header("Location: financials.php?month=$selected_month&year=$selected_year");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error_msg'] = "Gagal merevisi penjualan: " . $e->getMessage();
        }
    }
}

if (isset($_GET['delete_id'])) {
    $del_id = (int)$_GET['delete_id'];
    try {
        $stmt_check = $pdo->prepare("SELECT type FROM cash_transactions WHERE id = :id");
        $stmt_check->execute(['id' => $del_id]);
        $tx_type = $stmt_check->fetchColumn();

        $stmt = $pdo->prepare("DELETE FROM cash_transactions WHERE id = :id");
        $stmt->execute(['id' => $del_id]);

        if ($tx_type === 'expense') {
            $_SESSION['success_msg'] = "Data pengeluaran kas berhasil dihapus.";
        } else {
            $_SESSION['success_msg'] = "Data pemasukan kas berhasil dihapus.";
        }

        header("Location: financials.php?month=$selected_month&year=$selected_year");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Gagal menghapus: " . $e->getMessage();
    }
}

if (isset($_GET['delete_order_id'])) {
    $del_ord_id = (int)$_GET['delete_order_id'];
    try {
        $pdo->prepare("DELETE FROM order_items WHERE order_id = :id")->execute(['id' => $del_ord_id]);
        $pdo->prepare("DELETE FROM orders WHERE id = :id")->execute(['id' => $del_ord_id]);
        $_SESSION['success_msg'] = "Pesanan produk berhasil dihapus.";
        header("Location: financials.php?month=$selected_month&year=$selected_year");
        exit;
    } catch (PDOException $e) {
        $_SESSION['error_msg'] = "Gagal menghapus pesanan: " . $e->getMessage();
    }
}

// ----------------------------------------------------
// KALKULASI METRIK TERPISAH (KAS VS PRODUK)
// ----------------------------------------------------

// 1. PEMASUKAN PENJUALAN PRODUK (BULAN INI)
$month_product_income = 0;
try {
    $stmt_psi = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity * oi.price), 0) 
                               FROM order_items oi 
                               JOIN orders o ON oi.order_id = o.id 
                               WHERE MONTH(o.created_at) = :m AND YEAR(o.created_at) = :y AND LOWER(o.payment_status) IN ('paid', 'lunas')");
    $stmt_psi->execute(['m' => $selected_month, 'y' => $selected_year]);
    $month_product_income = (float)$stmt_psi->fetchColumn();
} catch (PDOException $e) {}

// 2. PEMASUKAN KAS MANUAL / DONASI (BULAN INI)
$month_cash_income = 0;
try {
    $stmt_cmi = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions 
                               WHERE type = 'income' AND category != 'Penjualan Produk' AND description NOT LIKE '%Penjualan Otomatis%' 
                               AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y");
    $stmt_cmi->execute(['m' => $selected_month, 'y' => $selected_year]);
    $month_cash_income = (float)$stmt_cmi->fetchColumn();
} catch (PDOException $e) {}

// TOTAL PENDAPATAN BULAN INI
$month_total_income = $month_product_income + $month_cash_income;

// PENGELUARAN BULAN INI
$month_expense = 0;
try {
    $stmt_exp = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y");
    $stmt_exp->execute(['m' => $selected_month, 'y' => $selected_year]);
    $month_expense = (float)$stmt_exp->fetchColumn();
} catch (PDOException $e) {}

$month_net = $month_total_income - $month_expense;

// AKUMULASI TAHUNAN (TERPISAH)
$year_product_income = 0;
try {
    $stmt_yps = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity * oi.price), 0) 
                               FROM order_items oi 
                               JOIN orders o ON oi.order_id = o.id 
                               WHERE YEAR(o.created_at) = :y AND LOWER(o.payment_status) IN ('paid', 'lunas')");
    $stmt_yps->execute(['y' => $selected_year]);
    $year_product_income = (float)$stmt_yps->fetchColumn();
} catch (PDOException $e) {}

$year_cash_income = 0;
try {
    $stmt_ycm = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions 
                               WHERE type = 'income' AND category != 'Penjualan Produk' AND description NOT LIKE '%Penjualan Otomatis%' 
                               AND YEAR(transaction_date) = :y");
    $stmt_ycm->execute(['y' => $selected_year]);
    $year_cash_income = (float)$stmt_ycm->fetchColumn();
} catch (PDOException $e) {}

$year_total_income = $year_product_income + $year_cash_income;

$year_expense = 0;
try {
    $stmt_y_exp = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND YEAR(transaction_date) = :y");
    $stmt_y_exp->execute(['y' => $selected_year]);
    $year_expense = (float)$stmt_y_exp->fetchColumn();
} catch (PDOException $e) {}

$year_net = $year_total_income - $year_expense;

// ANGKA HARI INI (TERPISAH)
$today_product_income = 0;
$today_cash_income = 0;
$today_expense = 0;
try {
    $stmt_td_sales = $pdo->query("SELECT COALESCE(SUM(oi.quantity * oi.price), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE DATE(o.created_at) = CURDATE() AND LOWER(o.payment_status) IN ('paid', 'lunas')");
    $today_product_income = (float)$stmt_td_sales->fetchColumn();

    $stmt_td_cash = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'income' AND category != 'Penjualan Produk' AND description NOT LIKE '%Penjualan Otomatis%' AND DATE(transaction_date) = CURDATE()");
    $today_cash_income = (float)$stmt_td_cash->fetchColumn();

    $stmt_td_exp = $pdo->query("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND DATE(transaction_date) = CURDATE()");
    $today_expense = (float)$stmt_td_exp->fetchColumn();
} catch (PDOException $e) {}

$today_total_income = $today_product_income + $today_cash_income;

// PRODUK TERLARIS
$top_product_today = "Belum Ada Transaksi";
try {
    $stmt_tpt = $pdo->query("SELECT p.name, SUM(oi.quantity) as total_qty 
                            FROM order_items oi 
                            JOIN orders o ON oi.order_id = o.id 
                            JOIN products p ON oi.product_id = p.id 
                            WHERE DATE(o.created_at) = CURDATE() AND LOWER(o.payment_status) IN ('paid', 'lunas') 
                            GROUP BY p.id ORDER BY total_qty DESC LIMIT 1");
    $res_tpt = $stmt_tpt->fetch(PDO::FETCH_ASSOC);
    if ($res_tpt) {
        $top_product_today = $res_tpt['name'] . " (" . $res_tpt['total_qty'] . " Pcs)";
    }
} catch (PDOException $e) {}

$top_product_month = "Belum Ada Transaksi";
try {
    $stmt_tpm = $pdo->prepare("SELECT p.name, SUM(oi.quantity) as total_qty 
                             FROM order_items oi 
                             JOIN orders o ON oi.order_id = o.id 
                             JOIN products p ON oi.product_id = p.id 
                             WHERE MONTH(o.created_at) = :m AND YEAR(o.created_at) = :y AND LOWER(o.payment_status) IN ('paid', 'lunas') 
                             GROUP BY p.id ORDER BY total_qty DESC LIMIT 1");
    $stmt_tpm->execute(['m' => $selected_month, 'y' => $selected_year]);
    $res_tpm = $stmt_tpm->fetch(PDO::FETCH_ASSOC);
    if ($res_tpm) {
        $top_product_month = $res_tpm['name'] . " (" . $res_tpm['total_qty'] . " Pcs)";
    }
} catch (PDOException $e) {}

$top_product_year = "Belum Ada Transaksi";
try {
    $stmt_tpy = $pdo->prepare("SELECT p.name, SUM(oi.quantity) as total_qty 
                             FROM order_items oi 
                             JOIN orders o ON oi.order_id = o.id 
                             JOIN products p ON oi.product_id = p.id 
                             WHERE YEAR(o.created_at) = :y AND LOWER(o.payment_status) IN ('paid', 'lunas') 
                             GROUP BY p.id ORDER BY total_qty DESC LIMIT 1");
    $stmt_tpy->execute(['y' => $selected_year]);
    $res_tpy = $stmt_tpy->fetch(PDO::FETCH_ASSOC);
    if ($res_tpy) {
        $top_product_year = $res_tpy['name'] . " (" . $res_tpy['total_qty'] . " Pcs)";
    }
} catch (PDOException $e) {}

// Rata-rata
$months_passed = ($selected_year === $current_realtime_year) ? max(1, $current_realtime_month) : 12;
$avg_monthly_cash_income   = $year_cash_income / $months_passed;
$avg_monthly_prod_income   = $year_product_income / $months_passed;
$avg_monthly_expense_year  = $year_expense / $months_passed;

$days_in_month = cal_days_in_month(CAL_GREGORIAN, $selected_month, $selected_year);
$current_day_num = ($selected_month === $current_realtime_month && $selected_year === $current_realtime_year) ? (int)date('j') : $days_in_month;
$current_day_num = max(1, $current_day_num);

$avg_daily_cash_income  = $month_cash_income / $current_day_num;
$avg_daily_prod_income  = $month_product_income / $current_day_num;
$avg_daily_expense      = $month_expense / $current_day_num;

$profit_status = "NETRAL";
$profit_badge = "bg-secondary";
$profit_percent = $month_total_income > 0 ? round(($month_net / $month_total_income) * 100, 1) : 0;

if ($month_net > 0) {
    $profit_status = "SURPLUS (UNTUNG)";
    $profit_badge = "bg-success";
} elseif ($month_net < 0) {
    $profit_status = "DEFISIT (RUGI)";
    $profit_badge = "bg-danger";
}

$sales_by_product_category = [];
try {
    $stmt_spc = $pdo->prepare("SELECT COALESCE(c.name, 'Tanpa Kategori') as category_name, SUM(oi.quantity * oi.price) as total_amount 
                               FROM order_items oi 
                               JOIN orders o ON oi.order_id = o.id 
                               JOIN products p ON oi.product_id = p.id 
                               LEFT JOIN categories c ON p.category_id = c.id 
                               WHERE MONTH(o.created_at) = :m AND YEAR(o.created_at) = :y AND LOWER(o.payment_status) IN ('paid', 'lunas')
                               GROUP BY c.id ORDER BY total_amount DESC");
    $stmt_spc->execute(['m' => $selected_month, 'y' => $selected_year]);
    $sales_by_product_category = $stmt_spc->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$income_by_cash_category = [];
try {
    $stmt_icc = $pdo->prepare("SELECT category, SUM(amount) as total_amount FROM cash_transactions 
                               WHERE type = 'income' AND category != 'Penjualan Produk' AND description NOT LIKE '%Penjualan Otomatis%' 
                               AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y 
                               GROUP BY category ORDER BY total_amount DESC");
    $stmt_icc->execute(['m' => $selected_month, 'y' => $selected_year]);
    $income_by_cash_category = $stmt_icc->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$expenses_by_category = [];
try {
    $stmt_ebc = $pdo->prepare("SELECT category, SUM(amount) as total_amount FROM cash_transactions 
                               WHERE type = 'expense' AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y 
                               GROUP BY category ORDER BY total_amount DESC");
    $stmt_ebc->execute(['m' => $selected_month, 'y' => $selected_year]);
    $expenses_by_category = $stmt_ebc->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// GRAFIK DATA
$daily_labels          = [];
$daily_cash_incomes    = [];
$daily_product_incomes = [];
$daily_expenses        = [];

for ($d = 1; $d <= $days_in_month; $d++) {
    $date_str = sprintf('%04d-%02d-%02d', $selected_year, $selected_month, $d);
    $daily_labels[] = sprintf('%02d/%02d', $d, $selected_month);
    
    $stmt_s = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity * oi.price), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE DATE(o.created_at) = :dt AND LOWER(o.payment_status) IN ('paid', 'lunas')");
    $stmt_s->execute(['dt' => $date_str]);
    $daily_product_incomes[] = (float)$stmt_s->fetchColumn();

    $stmt_di = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'income' AND category != 'Penjualan Produk' AND description NOT LIKE '%Penjualan Otomatis%' AND DATE(transaction_date) = :dt");
    $stmt_di->execute(['dt' => $date_str]);
    $daily_cash_incomes[] = (float)$stmt_di->fetchColumn();

    $stmt_de = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND DATE(transaction_date) = :dt");
    $stmt_de->execute(['dt' => $date_str]);
    $daily_expenses[] = (float)$stmt_de->fetchColumn();
}

$weekly_labels         = ['Minggu 1 (Tgl 1-7)', 'Minggu 2 (Tgl 8-14)', 'Minggu 3 (Tgl 15-21)', 'Minggu 4 (Tgl 22-28)', 'Minggu 5 (Tgl 29+)'];
$weekly_cash_incomes   = [0, 0, 0, 0, 0];
$weekly_product_incomes= [0, 0, 0, 0, 0];
$weekly_expenses       = [0, 0, 0, 0, 0];

for ($i = 0; $i < count($daily_cash_incomes); $i++) {
    $day_idx = $i + 1;
    $w_idx = ($day_idx <= 7) ? 0 : (($day_idx <= 14) ? 1 : (($day_idx <= 21) ? 2 : (($day_idx <= 28) ? 3 : 4)));

    $weekly_cash_incomes[$w_idx]    += $daily_cash_incomes[$i];
    $weekly_product_incomes[$w_idx] += $daily_product_incomes[$i];
    $weekly_expenses[$w_idx]        += $daily_expenses[$i];
}

$chart_cash_incomes    = array_fill(1, 12, 0);
$chart_product_incomes = array_fill(1, 12, 0);
$chart_expenses        = array_fill(1, 12, 0);

for ($m = 1; $m <= 12; $m++) {
    $stmt_ms = $pdo->prepare("SELECT COALESCE(SUM(oi.quantity * oi.price), 0) FROM order_items oi JOIN orders o ON oi.order_id = o.id WHERE MONTH(o.created_at) = :m AND YEAR(o.created_at) = :y AND LOWER(o.payment_status) IN ('paid', 'lunas')");
    $stmt_ms->execute(['m' => $m, 'y' => $selected_year]);
    $chart_product_incomes[$m] = (float)$stmt_ms->fetchColumn();

    $stmt_mc = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'income' AND category != 'Penjualan Produk' AND description NOT LIKE '%Penjualan Otomatis%' AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y");
    $stmt_mc->execute(['m' => $m, 'y' => $selected_year]);
    $chart_cash_incomes[$m] = (float)$stmt_mc->fetchColumn();

    $stmt_me = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE type = 'expense' AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y");
    $stmt_me->execute(['m' => $m, 'y' => $selected_year]);
    $chart_expenses[$m] = (float)$stmt_me->fetchColumn();
}

// QUERY TABEL
$top_products = [];
try {
    $sql_top = "SELECT p.name, c.name as category_name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                JOIN products p ON oi.product_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE MONTH(o.created_at) = :m AND YEAR(o.created_at) = :y AND LOWER(o.payment_status) IN ('paid', 'lunas')
                GROUP BY p.id
                ORDER BY total_sold DESC LIMIT 5";
    $stmt_top = $pdo->prepare($sql_top);
    $stmt_top->execute(['m' => $selected_month, 'y' => $selected_year]);
    $top_products = $stmt_top->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// QUERY DENGAN GROUP BY ORDER ID UNTUK MENGGABUNGKAN ITEM PRODUK
$product_sales = [];
try {
    $sql_sales = "SELECT 
                    o.id as order_id, 
                    {$ord_num_col_sql} as order_code, 
                    {$customer_name_col_sql} as customer_name, 
                    o.created_at, 
                    {$phone_col_sql} as phone, 
                    o.payment_status, 
                    GROUP_CONCAT(p.name SEPARATOR '<br>') as product_names,
                    GROUP_CONCAT(CONCAT(oi.quantity, ' pcs') SEPARATOR '<br>') as quantities,
                    SUM(oi.quantity * oi.price) as total_price
                  FROM orders o
                  JOIN order_items oi ON o.id = oi.order_id
                  JOIN products p ON oi.product_id = p.id
                  WHERE MONTH(o.created_at) = :m AND YEAR(o.created_at) = :y";
    
    $p_sales = ['m' => $selected_month, 'y' => $selected_year];
    if ($search_keyword !== '') {
        $sql_sales .= " AND ({$ord_num_col_sql} LIKE :q OR {$customer_name_col_sql} LIKE :q OR p.name LIKE :q OR {$phone_col_sql} LIKE :q OR o.id LIKE :q)";
        $p_sales['q'] = '%' . $search_keyword . '%';
    }
    
    $sql_sales .= " GROUP BY o.id ORDER BY o.created_at DESC";
    $stmt_sales = $pdo->prepare($sql_sales);
    $stmt_sales->execute($p_sales);
    $product_sales = $stmt_sales->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$manual_incomes = [];
try {
    $person_select = ($ct_person_col_sql !== "'-'") ? "{$ct_person_col_sql} as person_name" : "'' as person_name";
    $sql_inc = "SELECT *, {$person_select} FROM cash_transactions 
                WHERE type = 'income' AND category != 'Penjualan Produk' AND description NOT LIKE '%Penjualan Otomatis%'
                AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y";
    if ($search_keyword !== '') {
        $sql_inc .= " AND (description LIKE :q OR category LIKE :q " . ($ct_person_col_sql !== "'-'" ? "OR {$ct_person_col_sql} LIKE :q" : "") . ")";
    }
    $sql_inc .= " ORDER BY transaction_date DESC, id DESC";
    $stmt_inc = $pdo->prepare($sql_inc);
    $p_inc = ['m' => $selected_month, 'y' => $selected_year];
    if ($search_keyword !== '') $p_inc['q'] = '%' . $search_keyword . '%';
    $stmt_inc->execute($p_inc);
    $manual_incomes = $stmt_inc->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

$expenses = [];
try {
    $person_select = ($ct_person_col_sql !== "'-'") ? "{$ct_person_col_sql} as person_name" : "'' as person_name";
    $sql_exp = "SELECT *, {$person_select} FROM cash_transactions 
                WHERE type = 'expense' AND MONTH(transaction_date) = :m AND YEAR(transaction_date) = :y";
    if ($search_keyword !== '') {
        $sql_exp .= " AND (description LIKE :q OR category LIKE :q " . ($ct_person_col_sql !== "'-'" ? "OR {$ct_person_col_sql} LIKE :q" : "") . ")";
    }
    $sql_exp .= " ORDER BY transaction_date DESC, id DESC";
    $stmt_exp = $pdo->prepare($sql_exp);
    $p_exp = ['m' => $selected_month, 'y' => $selected_year];
    if ($search_keyword !== '') $p_exp['q'] = '%' . $search_keyword . '%';
    $stmt_exp->execute($p_exp);
    $expenses = $stmt_exp->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #f8fafc; color: #0f172a; overflow-x: hidden; }
        .content-container { max-width: 1320px; margin: 0 auto; padding: 2.5rem 1.5rem; animation: fadeInPage 0.4s ease-out forwards; }
        
        @keyframes fadeInPage {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .progress { height: 6px; border-radius: 10px; }
        @keyframes fadeInUp { 0% { opacity: 0; transform: translateY(12px); } 100% { opacity: 1; transform: translateY(0); } }
        .animate-up { animation: fadeInUp 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards; }
        .hover-lift { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .hover-lift:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06) !important; }
        .table tbody tr { transition: background-color 0.15s ease; }
        .table tbody tr:hover { background-color: #f0f7ff !important; }

        .btn-back-main {
            background-color: #ffffff !important;
            color: #0f172a !important;
            border: 1px solid #cbd5e1 !important;
            transition: background-color 0.1s ease, color 0.1s ease !important;
        }
        .btn-back-main:hover {
            background-color: #f1f5f9 !important;
            color: #0f172a !important;
            border-color: #94a3b8 !important;
        }
        .btn-back-main:active, 
        .btn-back-main:focus,
        .btn-back-main:focus-visible {
            background-color: #6c757d !important;
            color: #ffffff !important;
            border-color: #5a6268 !important;
            box-shadow: 0 0 0 0.25rem rgba(108, 117, 125, 0.4) !important;
        }

        .print-header { display: none; }
        @media print {
            body { background-color: #ffffff !important; color: #000000 !important; font-size: 8.5pt !important; display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
            .no-print, .btn, .modal, .alert, form, .table-search-box { display: none !important; }
            .content-container { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
            .print-header { display: block !important; text-align: center; border-bottom: 2px solid #0f172a; padding-bottom: 8px; margin-bottom: 15px; }
            .card, .bg-white { border: 1px solid #cbd5e1 !important; box-shadow: none !important; border-radius: 6px !important; margin-bottom: 12px !important; page-break-inside: avoid !important; }
            .table { width: 100% !important; border-collapse: collapse !important; font-size: 7.5pt !important; }
            .table th, .table td { padding: 4px 6px !important; border: 1px solid #cbd5e1 !important; }
        }
        @media (max-width: 768px) {
            .content-container { padding: 1.25rem 1rem; }
        }
    </style>
</head>
<body>

<div class="content-container">

    <div class="print-header">
        <h2>JARIA CRAFT — PANTI ASUHAN AL-JIHAD</h2>
        <h4>LAPORAN KEUANGAN, ARUS KAS & MANAJEMEN AKUNTANSI</h4>
        <p>Periode Pembukuan: <strong><?php echo $months_id[$selected_month] . ' ' . $selected_year; ?></strong> | Tanggal Cetak: <?php echo date('d F Y, H:i'); ?> WIB</p>
    </div>

    <!-- HEADER NAVIGATION -->
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4 pb-2 border-bottom no-print animate-up">
        <div class="d-flex align-items-center gap-2">
            <div class="p-2 bg-primary text-white rounded-3 d-flex align-items-center justify-content-center" style="width: 42px; height: 42px;">
                <i class="bi bi-journal-text fs-5"></i>
            </div>
            <div>
                <h3 class="fw-extrabold text-primary mb-0" style="font-size: 1.65rem;">Laporan Keuangan & Manajemen Kas</h3>
                <p class="text-secondary small mb-0">Pemisahan Transparan Pemasukan Kas (Donasi/Modal) & Pemasukan Produk.</p>
            </div>
        </div>
        
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="index.php" class="btn btn-back-main rounded-pill px-3.5 py-2 fw-bold shadow-sm">
                &larr; Kembali ke Panel Utama
            </a>
            <button type="button" class="btn btn-outline-danger rounded-pill fw-bold px-3 py-2 shadow-sm hover-lift" onclick="window.print()">
                <i class="bi bi-file-earmark-pdf me-1"></i> Cetak (PDF)
            </button>
            <button type="button" class="btn btn-primary rounded-pill fw-bold px-3 py-2 shadow-sm hover-lift" data-bs-toggle="modal" data-bs-target="#modalAddTransaction">
                <i class="bi bi-plus-circle me-1"></i> Catat Jurnal Kas
            </button>
        </div>
    </div>

    <?php if (!empty($success_msg)): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 fw-bold mb-4 no-print animate-up" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i><?php echo htmlspecialchars($success_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error_msg)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-4 fw-bold mb-4 no-print animate-up" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo htmlspecialchars($error_msg); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- FILTER -->
    <div class="bg-white rounded-4 shadow-sm border p-3 mb-4 no-print animate-up hover-lift">
        <form action="financials.php" method="GET" class="row g-3 align-items-center">
            <div class="col-12 col-md-4">
                <label class="form-label small fw-bold text-secondary mb-1">Pilih Bulan</label>
                <select name="month" class="form-select rounded-3">
                    <?php foreach ($months_id as $m_num => $m_name): ?>
                        <option value="<?php echo $m_num; ?>" <?php echo $m_num === $selected_month ? 'selected' : ''; ?>><?php echo $m_name; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-bold text-secondary mb-1">Pilih Tahun</label>
                <select name="year" class="form-select rounded-3">
                    <?php for ($y = $current_realtime_year; $y >= 2024; $y--): ?>
                        <option value="<?php echo $y; ?>" <?php echo $y === $selected_year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-6 col-md-3">
                <label class="form-label small fw-bold text-secondary mb-1">Cari Global (Semua Tabel)</label>
                <input type="text" name="q" class="form-control rounded-3" placeholder="Kode / Nama / Kategori..." value="<?php echo htmlspecialchars($search_keyword); ?>">
            </div>
            <div class="col-12 col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold mt-2 mt-md-0"><i class="bi bi-filter me-1"></i> Terapkan</button>
            </div>
        </form>
    </div>

    <!-- EXECUTIVE SUMMARY -->
    <div class="bg-primary bg-opacity-10 rounded-4 border border-primary border-opacity-25 p-3 p-md-4 mb-4 animate-up hover-lift">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
            <h6 class="fw-bold text-primary mb-0"><i class="bi bi-calculator-fill me-2"></i>Ringkasan Laba Rugi Periode: <?php echo $months_id[$selected_month] . ' ' . $selected_year; ?></h6>
            <span class="badge <?php echo $profit_badge; ?> px-3 py-2 rounded-pill fw-bold">
                Kondisi: <?php echo $profit_status; ?> (Margin Laba: <?php echo $profit_percent; ?>%)
            </span>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-12 col-md-3">
                <div class="card p-3 rounded-4 border text-center shadow-sm hover-lift h-100 border-success">
                    <small class="text-success d-block mb-1 fw-bold"><i class="bi bi-wallet2 me-1"></i>Pemasukan Kas (Non-Produk)</small>
                    <h4 class="fw-extrabold text-success mb-1">Rp <?php echo number_format($month_cash_income, 0, ',', '.'); ?></h4>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success mt-2">Donasi / Modal / Kas</span>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card p-3 rounded-4 border text-center shadow-sm hover-lift h-100 border-primary">
                    <small class="text-primary d-block mb-1 fw-bold"><i class="bi bi-bag-check me-1"></i>Pemasukan Penjualan Produk</small>
                    <h4 class="fw-extrabold text-primary mb-1">Rp <?php echo number_format($month_product_income, 0, ',', '.'); ?></h4>
                    <span class="badge bg-primary bg-opacity-10 text-primary border border-primary mt-2">Pesanan Lunas (Orders)</span>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card p-3 rounded-4 border text-center shadow-sm hover-lift h-100 border-danger">
                    <small class="text-danger d-block mb-1 fw-bold"><i class="bi bi-arrow-up-circle me-1"></i>Pengeluaran Kas (Beban)</small>
                    <h4 class="fw-extrabold text-danger mb-1">Rp <?php echo number_format($month_expense, 0, ',', '.'); ?></h4>
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger mt-2">Beban Operasional</span>
                </div>
            </div>

            <div class="col-12 col-md-3">
                <div class="card p-3 rounded-4 border text-center shadow-sm hover-lift h-100">
                    <small class="text-secondary d-block mb-1 fw-bold"><i class="bi bi-bank me-1"></i>Saldo Bersih (Net Profit)</small>
                    <h4 class="fw-extrabold <?php echo $month_net >= 0 ? 'text-dark' : 'text-danger'; ?> mb-1">
                        Rp <?php echo number_format($month_net, 0, ',', '.'); ?>
                    </h4>
                    <small class="text-muted fw-bold mt-2 d-block">Total Pemasukan - Pengeluaran</small>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-4 p-3.5 border shadow-sm mt-3">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-5 text-center text-md-start">
                    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-pie-chart-fill me-2 text-primary"></i>Proporsi Pemasukan Kas vs Produk vs Beban</h6>
                    <p class="text-muted small mb-3">Membandingkan persentase Pemasukan Kas, Pemasukan Produk, dan Pengeluaran Kas.</p>
                </div>
                <div class="col-12 col-md-3 text-center" style="height: 160px; position: relative;">
                    <canvas id="pieChartExecutive"></canvas>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card p-2 rounded-3 border bg-light mb-1 text-center">
                        <small class="text-muted d-block fw-bold"><i class="bi bi-wallet2 text-success me-1"></i>Kas Non-Produk Tahun Ini: <strong class="text-success">Rp <?php echo number_format($year_cash_income, 0, ',', '.'); ?></strong></small>
                    </div>
                    <div class="card p-2 rounded-3 border bg-light mb-1 text-center">
                        <small class="text-muted d-block fw-bold"><i class="bi bi-bag-check text-primary me-1"></i>Penjualan Produk Tahun Ini: <strong class="text-primary">Rp <?php echo number_format($year_product_income, 0, ',', '.'); ?></strong></small>
                    </div>
                    <div class="card p-2 rounded-3 border bg-light text-center">
                        <small class="text-muted d-block fw-bold"><i class="bi bi-arrow-up-circle text-danger me-1"></i>Pengeluaran Tahun Ini: <strong class="text-danger">Rp <?php echo number_format($year_expense, 0, ',', '.'); ?></strong></small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RINCIAN PEMASUKAN PASTI DENGAN PEMISAHAN JELAS -->
    <div class="bg-white rounded-4 shadow-sm border p-4 mb-4 animate-up hover-lift">
        <h6 class="fw-bold text-success mb-3"><i class="bi bi-arrow-down-circle-fill me-2"></i>Rincian Pemasukan Kas vs Pemasukan Produk Periode Waktu</h6>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="p-3 rounded-3 bg-light border text-center hover-lift h-100 d-flex flex-column justify-content-between">
                    <div>
                        <small class="text-dark fw-bold d-block mb-2"><i class="bi bi-calendar-check text-primary me-1"></i>PEMASUKAN HARI INI</small>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1 fs-7">
                            <span class="text-success fw-bold"><i class="bi bi-wallet2 me-1"></i>Kas/Donasi:</span>
                            <span class="fw-bold text-dark">Rp <?php echo number_format($today_cash_income, 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-2 fs-7">
                            <span class="text-primary fw-bold"><i class="bi bi-bag-check me-1"></i>Produk:</span>
                            <span class="fw-bold text-dark">Rp <?php echo number_format($today_product_income, 0, ',', '.'); ?></span>
                        </div>
                        <div class="badge bg-success text-white w-100 py-1">Total: Rp <?php echo number_format($today_total_income, 0, ',', '.'); ?></div>
                    </div>
                    <div class="pt-2 border-top text-start mt-2">
                        <small class="text-muted fw-bold d-block fs-8">Produk Terlaris Hari Ini:</small>
                        <span class="fw-bold text-dark fs-7 d-block text-truncate"><?php echo htmlspecialchars($top_product_today); ?></span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="p-3 rounded-3 bg-light border text-center hover-lift h-100 d-flex flex-column justify-content-between">
                    <div>
                        <small class="text-dark fw-bold d-block mb-2"><i class="bi bi-calendar-month text-primary me-1"></i>PEMASUKAN BULAN INI (<?php echo $months_id[$selected_month]; ?>)</small>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1 fs-7">
                            <span class="text-success fw-bold"><i class="bi bi-wallet2 me-1"></i>Kas/Donasi:</span>
                            <span class="fw-bold text-dark">Rp <?php echo number_format($month_cash_income, 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-2 fs-7">
                            <span class="text-primary fw-bold"><i class="bi bi-bag-check me-1"></i>Produk:</span>
                            <span class="fw-bold text-dark">Rp <?php echo number_format($month_product_income, 0, ',', '.'); ?></span>
                        </div>
                        <div class="badge bg-success text-white w-100 py-1">Total: Rp <?php echo number_format($month_total_income, 0, ',', '.'); ?></div>
                    </div>
                    <div class="pt-2 border-top text-start mt-2">
                        <small class="text-muted fw-bold d-block fs-8">Produk Terlaris Bulan Ini:</small>
                        <span class="fw-bold text-dark fs-7 d-block text-truncate"><?php echo htmlspecialchars($top_product_month); ?></span>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-4">
                <div class="p-3 rounded-3 bg-light border text-center hover-lift h-100 d-flex flex-column justify-content-between">
                    <div>
                        <small class="text-dark fw-bold d-block mb-2"><i class="bi bi-calendar-range text-primary me-1"></i>PEMASUKAN TAHUN INI (<?php echo $selected_year; ?>)</small>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-1 fs-7">
                            <span class="text-success fw-bold"><i class="bi bi-wallet2 me-1"></i>Kas/Donasi:</span>
                            <span class="fw-bold text-dark">Rp <?php echo number_format($year_cash_income, 0, ',', '.'); ?></span>
                        </div>
                        <div class="d-flex justify-content-between border-bottom pb-1 mb-2 fs-7">
                            <span class="text-primary fw-bold"><i class="bi bi-bag-check me-1"></i>Produk:</span>
                            <span class="fw-bold text-dark">Rp <?php echo number_format($year_product_income, 0, ',', '.'); ?></span>
                        </div>
                        <div class="badge bg-success text-white w-100 py-1">Total: Rp <?php echo number_format($year_total_income, 0, ',', '.'); ?></div>
                    </div>
                    <div class="pt-2 border-top text-start mt-2">
                        <small class="text-muted fw-bold d-block fs-8">Produk Terlaris Tahun Ini:</small>
                        <span class="fw-bold text-dark fs-7 d-block text-truncate"><?php echo htmlspecialchars($top_product_year); ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- RINCIAN PENGELUARAN KAS -->
    <div class="bg-white rounded-4 shadow-sm border p-4 mb-4 animate-up hover-lift">
        <h6 class="fw-bold text-danger mb-3"><i class="bi bi-arrow-up-circle-fill me-2"></i>Rincian Pengeluaran Kas Periode Waktu</h6>
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <div class="p-3 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 text-center hover-lift h-100">
                    <small class="text-danger fw-bold d-block mb-1"><i class="bi bi-calendar-check text-danger me-1"></i>PENGELUARAN HARI INI</small>
                    <h4 class="fw-extrabold text-danger mb-2">Rp <?php echo number_format($today_expense, 0, ',', '.'); ?></h4>
                    <span class="badge bg-danger text-white px-2 py-1">Pengeluaran Kas Hari Ini</span>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-3 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 text-center hover-lift h-100">
                    <small class="text-danger fw-bold d-block mb-1"><i class="bi bi-calendar-month text-danger me-1"></i>PENGELUARAN BULAN INI (<?php echo $months_id[$selected_month]; ?>)</small>
                    <h4 class="fw-extrabold text-danger mb-2">Rp <?php echo number_format($month_expense, 0, ',', '.'); ?></h4>
                    <span class="badge bg-danger text-white px-2 py-1">Pengeluaran Kas Bulan Ini</span>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="p-3 rounded-3 bg-danger bg-opacity-10 border border-danger border-opacity-25 text-center hover-lift h-100">
                    <small class="text-danger fw-bold d-block mb-1"><i class="bi bi-calendar-range text-danger me-1"></i>PENGELUARAN TAHUN INI (<?php echo $selected_year; ?>)</small>
                    <h4 class="fw-extrabold text-danger mb-2">Rp <?php echo number_format($year_expense, 0, ',', '.'); ?></h4>
                    <span class="badge bg-danger text-white px-2 py-1">Akumulasi Beban Tahun Ini</span>
                </div>
            </div>
        </div>
    </div>

    <!-- RINCIAN SUMBER PEMASUKAN DAN PENGELUARAN -->
    <div class="row g-3 mb-4 animate-up">
        <div class="col-12 col-lg-6">
            <div class="bg-white rounded-4 shadow-sm border p-4 h-100 hover-lift">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="fw-bold text-success mb-0">
                        <i class="bi bi-arrow-down-left-circle-fill me-2"></i>Rincian Sumber Pemasukan
                    </h6>
                    <span class="badge bg-success-subtle text-success border border-success fw-bold">
                        Total: Rp <?php echo number_format($month_total_income, 0, ',', '.'); ?>
                    </span>
                </div>

                <div class="p-3 bg-light rounded-3 mb-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-dark"><i class="bi bi-bag-check me-1 text-primary"></i>1. Penjualan Produk (Tabel Orders)</span>
                        <strong class="text-primary">Rp <?php echo number_format($month_product_income, 0, ',', '.'); ?></strong>
                    </div>
                    <div class="ms-3 mt-2">
                        <?php if (!empty($sales_by_product_category)): ?>
                            <?php foreach ($sales_by_product_category as $spc): ?>
                                <div class="d-flex justify-content-between fs-8 text-secondary border-bottom border-light py-1">
                                    <span>• Kategori <?php echo htmlspecialchars($spc['category_name']); ?></span>
                                    <span class="fw-semibold">Rp <?php echo number_format($spc['total_amount'], 0, ',', '.'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <small class="text-muted italic fs-8">Belum ada transaksi penjualan produk lunas.</small>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="p-3 bg-light rounded-3 border">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span class="fw-bold text-dark"><i class="bi bi-wallet2 me-1 text-success"></i>2. Pemasukan Kas / Donasi / Modal</span>
                        <strong class="text-success">Rp <?php echo number_format($month_cash_income, 0, ',', '.'); ?></strong>
                    </div>
                    <div class="ms-3 mt-2">
                        <?php if (!empty($income_by_cash_category)): ?>
                            <?php foreach ($income_by_cash_category as $icc): ?>
                                <div class="d-flex justify-content-between fs-8 text-secondary border-bottom border-light py-1">
                                    <span>• Kategori <?php echo htmlspecialchars($icc['category']); ?></span>
                                    <span class="fw-semibold">Rp <?php echo number_format($icc['total_amount'], 0, ',', '.'); ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <small class="text-muted italic fs-8">Belum ada catatan kas masuk manual.</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="bg-white rounded-4 shadow-sm border p-4 h-100 hover-lift">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h6 class="fw-bold text-danger mb-0">
                        <i class="bi bi-arrow-up-right-circle-fill me-2"></i>Rincian Kategori Pengeluaran Kas
                    </h6>
                    <span class="badge bg-danger-subtle text-danger border border-danger fw-bold">
                        Total: Rp <?php echo number_format($month_expense, 0, ',', '.'); ?>
                    </span>
                </div>

                <div class="p-3 bg-light rounded-3 border">
                    <span class="fw-bold text-dark d-block mb-2"><i class="bi bi-receipt me-1 text-danger"></i>Pengeluaran Menurut Kategori</span>
                    <?php if (!empty($expenses_by_category)): ?>
                        <?php foreach ($expenses_by_category as $ebc): ?>
                            <div class="d-flex justify-content-between align-items-center border-bottom py-2">
                                <span class="fs-7 fw-semibold text-dark">• <?php echo htmlspecialchars($ebc['category']); ?></span>
                                <span class="fw-bold text-danger fs-7">- Rp <?php echo number_format($ebc['total_amount'], 0, ',', '.'); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <small class="text-muted italic fs-8">Belum ada data pengeluaran untuk periode ini.</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- DIAGRAM RATA-RATA -->
    <div class="row g-3 mb-4 animate-up">
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card p-3.5 rounded-4 border bg-white shadow-sm hover-lift h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-clock me-1 text-primary"></i>Rata-Rata Harian</h6>
                    <span class="badge bg-light text-dark border fs-8">Bulan Ini</span>
                </div>
                <div class="row align-items-center g-2">
                    <div class="col-7">
                        <small class="text-muted fw-bold d-block">Kas/Donasi / Hari</small>
                        <strong class="text-success fs-7 d-block mb-1">Rp <?php echo number_format($avg_daily_cash_income, 0, ',', '.'); ?></strong>
                        <small class="text-muted fw-bold d-block">Produk / Hari</small>
                        <strong class="text-primary fs-7 d-block mb-1">Rp <?php echo number_format($avg_daily_prod_income, 0, ',', '.'); ?></strong>
                        <small class="text-muted fw-bold d-block">Pengeluaran / Hari</small>
                        <strong class="text-danger fs-7 d-block">Rp <?php echo number_format($avg_daily_expense, 0, ',', '.'); ?></strong>
                    </div>
                    <div class="col-5" style="height: 100px; position: relative;">
                        <canvas id="chartDaily"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
            <div class="card p-3.5 rounded-4 border bg-white shadow-sm hover-lift h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-calendar-week me-1 text-primary"></i>Rata-Rata Mingguan</h6>
                    <span class="badge bg-light text-dark border fs-8">Estimasi</span>
                </div>
                <div class="row align-items-center g-2">
                    <div class="col-7">
                        <small class="text-muted fw-bold d-block">Kas/Donasi / Minggu</small>
                        <strong class="text-success fs-7 d-block mb-1">Rp <?php echo number_format($avg_daily_cash_income * 7, 0, ',', '.'); ?></strong>
                        <small class="text-muted fw-bold d-block">Produk / Minggu</small>
                        <strong class="text-primary fs-7 d-block mb-1">Rp <?php echo number_format($avg_daily_prod_income * 7, 0, ',', '.'); ?></strong>
                        <small class="text-muted fw-bold d-block">Pengeluaran / Minggu</small>
                        <strong class="text-danger fs-7 d-block">Rp <?php echo number_format($avg_daily_expense * 7, 0, ',', '.'); ?></strong>
                    </div>
                    <div class="col-5" style="height: 100px; position: relative;">
                        <canvas id="chartWeekly"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-12 col-lg-4">
            <div class="card p-3.5 rounded-4 border bg-white shadow-sm hover-lift h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-calendar2-range me-1 text-primary"></i>Rata-Rata Bulanan</h6>
                    <span class="badge bg-light text-dark border fs-8">Tahun <?php echo $selected_year; ?></span>
                </div>
                <div class="row align-items-center g-2">
                    <div class="col-7">
                        <small class="text-muted fw-bold d-block">Kas/Donasi / Bulan</small>
                        <strong class="text-success fs-7 d-block mb-1">Rp <?php echo number_format($avg_monthly_cash_income, 0, ',', '.'); ?></strong>
                        <small class="text-muted fw-bold d-block">Produk / Bulan</small>
                        <strong class="text-primary fs-7 d-block mb-1">Rp <?php echo number_format($avg_monthly_prod_income, 0, ',', '.'); ?></strong>
                        <small class="text-muted fw-bold d-block">Pengeluaran / Bulan</small>
                        <strong class="text-danger fs-7 d-block">Rp <?php echo number_format($avg_monthly_expense_year, 0, ',', '.'); ?></strong>
                    </div>
                    <div class="col-5" style="height: 100px; position: relative;">
                        <canvas id="chartMonthly"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- GRAFIK HARIAN & MINGGUAN -->
    <div class="row g-4 mb-4 animate-up">
        <div class="col-12 col-lg-6">
            <div class="bg-white rounded-4 shadow-sm border p-4 h-100 hover-lift">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-line-fill me-2 text-primary"></i>Grafik Batang Harian (Bulan <?php echo $months_id[$selected_month]; ?>)</h6>
                    <span class="badge bg-light text-dark border px-2.5 py-1 fs-8">Per Tanggal</span>
                </div>
                <div style="height: 250px;">
                    <canvas id="dailyBarChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="bg-white rounded-4 shadow-sm border p-4 h-100 hover-lift">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold text-dark mb-0"><i class="bi bi-bar-chart-steps me-2 text-info"></i>Grafik Batang Mingguan (Bulan <?php echo $months_id[$selected_month]; ?>)</h6>
                    <span class="badge bg-light text-dark border px-2.5 py-1 fs-8">Per Minggu</span>
                </div>
                <div style="height: 250px;">
                    <canvas id="weeklyBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-4 shadow-sm border p-4 mb-4 animate-up hover-lift">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-bold text-dark mb-0"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Grafik Batang Tahunan & Tren Bulanan (Tahun <?php echo $selected_year; ?>)</h5>
            <span class="badge bg-light text-dark border px-3 py-1">Analisis Kas vs Produk vs Beban</span>
        </div>
        <div style="height: 280px;">
            <canvas id="financialChart"></canvas>
        </div>
    </div>

    <!-- PRODUK TERLARIS TABLE -->
    <div class="bg-white rounded-4 shadow-sm border p-4 mb-4 animate-up hover-lift">
        <h5 class="fw-bold text-dark mb-3"><i class="bi bi-star-fill me-2 text-warning"></i>Daftar Produk Terlaris (Periode <?php echo $months_id[$selected_month] . ' ' . $selected_year; ?>)</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-secondary border-bottom small">
                        <th class="text-center" style="width: 50px;">No</th>
                        <th>Rank</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Total Terjual</th>
                        <th class="text-end">Total Pendapatan Produk</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($top_products)): ?>
                        <?php $rank = 1; foreach ($top_products as $tp): ?>
                            <tr>
                                <td class="text-center fw-bold text-secondary"><?php echo $rank; ?></td>
                                <td><span class="badge bg-primary rounded-circle p-2" style="width:28px; height:28px; display:inline-flex; align-items:center; justify-content:center;"><?php echo $rank++; ?></span></td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($tp['name']); ?></td>
                                <td><span class="badge bg-light text-secondary border"><?php echo htmlspecialchars($tp['category_name'] ?? 'Kerajinan'); ?></span></td>
                                <td><strong class="text-primary"><?php echo (int)$tp['total_sold']; ?> Items</strong></td>
                                <td class="text-end fw-extrabold text-success">Rp <?php echo number_format($tp['total_revenue'], 0, ',', '.'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted py-3">Belum ada data penjualan produk untuk periode ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TABEL 1: PENJUALAN PRODUK (DIGABUNGKAN PER PESANAN / ORDER) -->
    <div class="bg-white rounded-4 shadow-sm border p-4 mb-4 animate-up">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <h5 class="fw-bold text-primary mb-1"><i class="bi bi-bag-check-fill me-2"></i>Tabel Penjualan Produk Dipesan</h5>
                <span class="badge bg-primary rounded-pill px-3 py-1" id="badgeCountSales"><?php echo count($product_sales); ?> Data Ditemukan</span>
            </div>
            <div class="table-search-box" style="min-width: 280px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" id="searchTableSales" placeholder="Cari pemesan, kode, produk..." onkeyup="filterTable('searchTableSales', 'tableSalesBody', 'badgeCountSales')">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-secondary border-bottom small">
                        <th class="text-center" style="width: 50px;">No</th>
                        <th>Tanggal Order</th>
                        <th>Kode Pesanan</th>
                        <th>Nama Pemesan</th>
                        <th>No. WhatsApp</th>
                        <th>Nama Produk Dipesan</th>
                        <th>Jumlah (Qty)</th>
                        <th>Status Pembayaran</th>
                        <th class="text-end">Subtotal (Rp)</th>
                        <th class="text-center no-print">Aksi (CRUD)</th>
                    </tr>
                </thead>
                <tbody id="tableSalesBody">
                    <?php if (!empty($product_sales)): ?>
                        <?php $no = 1; foreach ($product_sales as $ps): 
                            $st_pay = strtolower($ps['payment_status'] ?? 'pending');
                            $c_name = !empty($ps['customer_name']) ? $ps['customer_name'] : 'Pelanggan General';
                        ?>
                            <tr class="border-bottom">
                                <td class="text-center fw-bold text-secondary"><?php echo $no++; ?></td>
                                <td class="small fw-semibold text-secondary"><?php echo date('d/m/Y H:i', strtotime($ps['created_at'])); ?></td>
                                <td><span class="badge bg-light text-dark border fw-bold">#<?php echo htmlspecialchars($ps['order_code']); ?></span></td>
                                <td class="fw-bold text-dark"><i class="bi bi-person-fill text-primary me-1"></i><?php echo htmlspecialchars($c_name); ?></td>
                                <td>
                                    <?php if (!empty($ps['phone']) && $ps['phone'] !== '-'): ?>
                                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $ps['phone']); ?>" target="_blank" class="text-success fw-bold text-decoration-none small">
                                            <i class="bi bi-whatsapp me-1"></i><?php echo htmlspecialchars($ps['phone']); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <!-- KANAL RENCANA PRODUK TERGABUNG DALAM 1 KOLOM -->
                                <td class="fw-semibold text-dark"><?php echo $ps['product_names']; ?></td>
                                <td><span class="badge bg-info text-dark"><?php echo $ps['quantities']; ?></span></td>
                                <td>
                                    <?php if (in_array($st_pay, ['paid', 'lunas'])): ?>
                                        <span class="badge bg-success rounded-pill px-2.5 py-1">Paid (Lunas)</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark rounded-pill px-2.5 py-1">Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-extrabold text-primary">+ Rp <?php echo number_format($ps['total_price'], 0, ',', '.'); ?></td>
                                <td class="text-center no-print">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle p-2 me-1" 
                                            data-bs-toggle="modal" data-bs-target="#modalEditOrder<?php echo $ps['order_id']; ?>" title="Edit Detail Pesanan">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <a href="financials.php?delete_order_id=<?php echo $ps['order_id']; ?>&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-circle p-2" onclick="return confirm('Hapus seluruh transaksi pesanan produk ini?')" title="Hapus">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="10" class="text-center py-4 text-muted">Belum ada transaksi penjualan produk untuk periode ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TABEL 2: KAS MASUK MANUAL -->
    <div class="bg-white rounded-4 shadow-sm border p-4 mb-4 animate-up">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <h5 class="fw-bold text-success mb-1"><i class="bi bi-arrow-down-left-circle me-2"></i>Tabel Pemasukan Kas / Kas Masuk (Donasi & Modal)</h5>
                <span class="badge bg-success rounded-pill px-3 py-1" id="badgeCountIncome"><?php echo count($manual_incomes); ?> Data Ditemukan</span>
            </div>
            <div class="table-search-box" style="min-width: 280px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" id="searchTableIncome" placeholder="Cari donatur, kategori, ket..." onkeyup="filterTable('searchTableIncome', 'tableIncomeBody', 'badgeCountIncome')">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-secondary border-bottom small">
                        <th class="text-center" style="width: 50px;">No</th>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Nama Donatur / Pembayar</th>
                        <th>Keterangan / Sumber Pemasukan Kas</th>
                        <th class="text-end">Jumlah (Rp)</th>
                        <th class="text-center no-print">Aksi (CRUD)</th>
                    </tr>
                </thead>
                <tbody id="tableIncomeBody">
                    <?php if (!empty($manual_incomes)): ?>
                        <?php $no = 1; foreach ($manual_incomes as $inc): 
                            $person_val = trim($inc['person_name'] ?? '');
                            $donor = (!empty($person_val) && $person_val !== '-') ? $person_val : htmlspecialchars($inc['category']);
                        ?>
                            <tr class="border-bottom">
                                <td class="text-center fw-bold text-secondary"><?php echo $no++; ?></td>
                                <td class="small fw-semibold text-secondary"><?php echo date('d/m/Y', strtotime($inc['transaction_date'])); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($inc['category']); ?></span></td>
                                <td class="fw-bold text-dark"><i class="bi bi-heart-fill text-danger me-1"></i><?php echo htmlspecialchars($donor); ?></td>
                                <td class="small text-secondary"><?php echo htmlspecialchars($inc['description']); ?></td>
                                <td class="text-end fw-extrabold text-success">+ Rp <?php echo number_format($inc['amount'], 0, ',', '.'); ?></td>
                                <td class="text-center no-print">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle p-2 me-1" 
                                            data-bs-toggle="modal" data-bs-target="#modalEditTransaction<?php echo $inc['id']; ?>" title="Edit Pemasukan Kas">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <a href="financials.php?delete_id=<?php echo $inc['id']; ?>&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-circle p-2" onclick="return confirm('Hapus transaksi pemasukan kas ini?')" title="Hapus">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada catatan pemasukan kas manual untuk bulan ini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TABEL 3: PENGELUARAN KAS -->
    <div class="bg-white rounded-4 shadow-sm border p-4 mb-4 animate-up">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
            <div>
                <h5 class="fw-bold text-danger mb-1"><i class="bi bi-arrow-up-right-circle me-2"></i>Tabel Pengeluaran (Kas Keluar)</h5>
                <span class="badge bg-danger rounded-pill px-3 py-1" id="badgeCountExpense"><?php echo count($expenses); ?> Data Ditemukan</span>
            </div>
            <div class="table-search-box" style="min-width: 280px;">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" class="form-control bg-light border-start-0" id="searchTableExpense" placeholder="Cari penerima, deskripsi..." onkeyup="filterTable('searchTableExpense', 'tableExpenseBody', 'badgeCountExpense')">
                </div>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr class="text-secondary border-bottom small">
                        <th class="text-center" style="width: 50px;">No</th>
                        <th>Tanggal</th>
                        <th>Kategori</th>
                        <th>Penerima / Penanggung Jawab</th>
                        <th>Keterangan / Deskripsi Pengeluaran</th>
                        <th class="text-end">Jumlah (Rp)</th>
                        <th class="text-center no-print">Aksi (CRUD)</th>
                    </tr>
                </thead>
                <tbody id="tableExpenseBody">
                    <?php if (!empty($expenses)): ?>
                        <?php $no = 1; foreach ($expenses as $exp): 
                            $person_val = trim($exp['person_name'] ?? '');
                            $receiver = (!empty($person_val) && $person_val !== '-') ? $person_val : htmlspecialchars($exp['category']);
                        ?>
                            <tr class="border-bottom">
                                <td class="text-center fw-bold text-secondary"><?php echo $no++; ?></td>
                                <td class="small fw-semibold text-secondary"><?php echo date('d/m/Y', strtotime($exp['transaction_date'])); ?></td>
                                <td><span class="badge bg-light text-dark border"><?php echo htmlspecialchars($exp['category']); ?></span></td>
                                <td class="fw-bold text-dark"><i class="bi bi-person-badge-fill text-danger me-1"></i><?php echo htmlspecialchars($receiver); ?></td>
                                <td class="small text-secondary"><?php echo htmlspecialchars($exp['description']); ?></td>
                                <td class="text-end fw-extrabold text-danger">- Rp <?php echo number_format($exp['amount'], 0, ',', '.'); ?></td>
                                <td class="text-center no-print">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-circle p-2 me-1" 
                                            data-bs-toggle="modal" data-bs-target="#modalEditTransaction<?php echo $exp['id']; ?>" title="Edit Pengeluaran">
                                        <i class="bi bi-pencil-fill"></i>
                                    </button>
                                    <a href="financials.php?delete_id=<?php echo $exp['id']; ?>&month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" 
                                       class="btn btn-sm btn-outline-danger rounded-circle p-2" onclick="return confirm('Hapus transaksi pengeluaran ini?')" title="Hapus">
                                        <i class="bi bi-trash-fill"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada catatan pengeluaran kas untuk bulan <?php echo $months_id[$selected_month] . ' ' . $selected_year; ?>.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL TAMBAH TRANSAKSI KAS -->
<div class="modal fade" id="modalAddTransaction" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <form action="financials.php" method="POST">
                <input type="hidden" name="action" value="add_transaction">
                <div class="modal-header bg-primary text-white p-3 px-4">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i>Tambah Jurnal Kas Baru</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Jenis Transaksi Kas</label>
                        <select name="type" class="form-select rounded-3 fw-semibold">
                            <option value="income">Pemasukan Kas (Donasi/Modal/Lainnya)</option>
                            <option value="expense">Pengeluaran Kas (Beban Operasional)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Kategori</label>
                        <select name="category" class="form-select rounded-3">
                            <option value="Donasi / Modal">Donasi / Modal Usaha</option>
                            <option value="Pembelian Bahan">Pembelian Bahan Kerajinan</option>
                            <option value="Operasional">Biaya Operasional / Lainnya</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Nama Donatur / Penanggung Jawab / Penerima</label>
                        <input type="text" name="person_name" class="form-control rounded-3" placeholder="Contoh: Bpk. Ahmad / Ibu Maria">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Nominal Jumlah (Rp) <span class="text-danger">*</span></label>
                        <input type="number" name="amount" class="form-control rounded-3 fw-bold" placeholder="Contoh: 50000" required min="1">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Tanggal Transaksi <span class="text-danger">*</span></label>
                        <input type="date" name="transaction_date" class="form-control rounded-3" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-secondary">Keterangan / Deskripsi</label>
                        <textarea name="description" class="form-control rounded-3" rows="3" placeholder="Contoh: Donasi harian panti asuhan"></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light border-0 p-3">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold"><i class="bi bi-check-lg me-1"></i>Simpan Transaksi Kas</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDIT ORDERS -->
<?php foreach ($product_sales as $ps): 
    $st_pay = strtolower($ps['payment_status'] ?? 'pending');
    $c_name = !empty($ps['customer_name']) ? $ps['customer_name'] : 'Pelanggan General';
?>
    <div class="modal fade" id="modalEditOrder<?php echo $ps['order_id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <form action="financials.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" method="POST">
                    <input type="hidden" name="action" value="edit_order_detail">
                    <input type="hidden" name="order_id" value="<?php echo $ps['order_id']; ?>">
                    <div class="modal-header bg-primary text-white p-3 px-4">
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Detail Pesanan</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Kode Pesanan</label>
                            <input type="text" class="form-control bg-light rounded-3" value="#<?php echo htmlspecialchars($ps['order_code']); ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Nama Pemesan / Pembeli</label>
                            <input type="text" name="customer_name" class="form-control rounded-3 fw-semibold" value="<?php echo htmlspecialchars($c_name); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Status Pembayaran</label>
                            <select name="payment_status" class="form-select rounded-3 fw-semibold">
                                <option value="paid" <?php echo in_array($st_pay, ['paid', 'lunas']) ? 'selected' : ''; ?>>Paid (Lunas)</option>
                                <option value="pending" <?php echo $st_pay === 'pending' ? 'selected' : ''; ?>>Pending (Belum Bayar)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">No. WhatsApp Pemesan</label>
                            <input type="text" name="phone" class="form-control rounded-3" value="<?php echo htmlspecialchars($ps['phone']); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Tanggal Transaksi Order</label>
                            <input type="datetime-local" name="created_at" class="form-control rounded-3" value="<?php echo date('Y-m-d\TH:i', strtotime($ps['created_at'])); ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 p-3">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Update Penjualan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- MODAL EDIT PEMASUKAN MANUAL KAS -->
<?php foreach ($manual_incomes as $inc): ?>
    <div class="modal fade" id="modalEditTransaction<?php echo $inc['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <form action="financials.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" method="POST">
                    <input type="hidden" name="action" value="edit_transaction">
                    <input type="hidden" name="transaction_id" value="<?php echo $inc['id']; ?>">
                    <div class="modal-header bg-primary text-white p-3 px-4">
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Pemasukan Kas</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Jenis Transaksi Kas</label>
                            <select name="type" class="form-select rounded-3 fw-semibold">
                                <option value="income" selected>Pemasukan Kas (Kas Masuk)</option>
                                <option value="expense">Pengeluaran Kas (Kas Keluar)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Kategori</label>
                            <input type="text" name="category" class="form-control rounded-3" value="<?php echo htmlspecialchars($inc['category']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Nama Donatur / Pembayar</label>
                            <input type="text" name="person_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($inc['person_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Nominal (Rp)</label>
                            <input type="number" name="amount" class="form-control rounded-3 fw-bold" value="<?php echo $inc['amount']; ?>" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Tanggal Transaksi</label>
                            <input type="date" name="transaction_date" class="form-control rounded-3" value="<?php echo $inc['transaction_date']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Keterangan / Deskripsi</label>
                            <textarea name="description" class="form-control rounded-3" rows="3"><?php echo htmlspecialchars($inc['description']); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 p-3">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Update Pemasukan Kas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<!-- MODAL EDIT PENGELUARAN -->
<?php foreach ($expenses as $exp): ?>
    <div class="modal fade" id="modalEditTransaction<?php echo $exp['id']; ?>" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <form action="financials.php?month=<?php echo $selected_month; ?>&year=<?php echo $selected_year; ?>" method="POST">
                    <input type="hidden" name="action" value="edit_transaction">
                    <input type="hidden" name="transaction_id" value="<?php echo $exp['id']; ?>">
                    <div class="modal-header bg-danger text-white p-3 px-4">
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i>Edit Pengeluaran Kas</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Jenis Transaksi Kas</label>
                            <select name="type" class="form-select rounded-3 fw-semibold">
                                <option value="expense" selected>Pengeluaran Kas (Kas Keluar)</option>
                                <option value="income">Pemasukan Kas (Kas Masuk)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Kategori</label>
                            <input type="text" name="category" class="form-control rounded-3" value="<?php echo htmlspecialchars($exp['category']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Penerima / Penanggung Jawab</label>
                            <input type="text" name="person_name" class="form-control rounded-3" value="<?php echo htmlspecialchars($exp['person_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Nominal (Rp)</label>
                            <input type="number" name="amount" class="form-control rounded-3 fw-bold" value="<?php echo $exp['amount']; ?>" required min="1">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Tanggal Transaksi</label>
                            <input type="date" name="transaction_date" class="form-control rounded-3" value="<?php echo $exp['transaction_date']; ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-secondary">Keterangan / Deskripsi</label>
                            <textarea name="description" class="form-control rounded-3" rows="3"><?php echo htmlspecialchars($exp['description']); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-light border-0 p-3">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold">Update Pengeluaran</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function id(e) { return document.getElementById(e); }

function filterTable(inputId, tbodyId, badgeId) {
    const filter = id(inputId).value.toLowerCase();
    const rows = id(tbodyId).getElementsByTagName('tr');
    let visibleCount = 0;
    let totalRows = 0;

    for (let i = 0; i < rows.length; i++) {
        const text = rows[i].textContent || rows[i].innerText;
        if (rows[i].getElementsByTagName('td').length <= 1) continue;
        totalRows++;
        
        if (text.toLowerCase().indexOf(filter) > -1) {
            rows[i].style.display = "";
            visibleCount++;
        } else {
            rows[i].style.display = "none";
        }
    }

    const badgeElem = id(badgeId);
    if (badgeElem) {
        if (filter.trim() === '') {
            badgeElem.innerText = totalRows + ' Data Ditemukan';
        } else {
            badgeElem.innerText = 'Menampilkan ' + visibleCount + ' dari ' + totalRows + ' Data';
        }
    }
}

// CHART PROPORSI
new Chart(id('pieChartExecutive').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: ['Pemasukan Kas', 'Penjualan Produk', 'Pengeluaran'],
        datasets: [{
            data: [<?php echo $month_cash_income; ?>, <?php echo $month_product_income; ?>, <?php echo $month_expense; ?>],
            backgroundColor: ['#10b981', '#2563eb', '#ef4444'],
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        cutout: '65%'
    }
});

// CHART RATA-RATA HARIAN
new Chart(id('chartDaily').getContext('2d'), {
    type: 'bar',
    data: {
        labels: ['Kas', 'Produk', 'Beban'],
        datasets: [{
            data: [<?php echo $avg_daily_cash_income; ?>, <?php echo $avg_daily_prod_income; ?>, <?php echo $avg_daily_expense; ?>],
            backgroundColor: ['#10b981', '#2563eb', '#ef4444'],
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { display: true }, y: { display: false } }
    }
});

// CHART RATA-RATA MINGGUAN
new Chart(id('chartWeekly').getContext('2d'), {
    type: 'bar',
    data: {
        labels: ['Kas', 'Produk', 'Beban'],
        datasets: [{
            data: [<?php echo $avg_daily_cash_income * 7; ?>, <?php echo $avg_daily_prod_income * 7; ?>, <?php echo $avg_daily_expense * 7; ?>],
            backgroundColor: ['#10b981', '#2563eb', '#ef4444'],
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { display: true }, y: { display: false } }
    }
});

// CHART RATA-RATA BULANAN
new Chart(id('chartMonthly').getContext('2d'), {
    type: 'bar',
    data: {
        labels: ['Kas', 'Produk', 'Beban'],
        datasets: [{
            data: [<?php echo $avg_monthly_cash_income; ?>, <?php echo $avg_monthly_prod_income; ?>, <?php echo $avg_monthly_expense_year; ?>],
            backgroundColor: ['#10b981', '#2563eb', '#ef4444'],
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { x: { display: true }, y: { display: false } }
    }
});

// GRAFIK HARIAN
new Chart(id('dailyBarChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($daily_labels); ?>,
        datasets: [
            {
                label: 'Pemasukan Kas (Rp)',
                data: <?php echo json_encode($daily_cash_incomes); ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.85)',
                borderRadius: 4
            },
            {
                label: 'Penjualan Produk (Rp)',
                data: <?php echo json_encode($daily_product_incomes); ?>,
                backgroundColor: 'rgba(37, 99, 235, 0.85)',
                borderRadius: 4
            },
            {
                label: 'Pengeluaran (Rp)',
                data: <?php echo json_encode($daily_expenses); ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.85)',
                borderRadius: 4
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { callback: function(v) { return 'Rp ' + v.toLocaleString('id-ID'); } } } }
    }
});

// GRAFIK MINGGUAN
new Chart(id('weeklyBarChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($weekly_labels); ?>,
        datasets: [
            {
                label: 'Pemasukan Kas (Rp)',
                data: <?php echo json_encode($weekly_cash_incomes); ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.85)',
                borderRadius: 6
            },
            {
                label: 'Penjualan Produk (Rp)',
                data: <?php echo json_encode($weekly_product_incomes); ?>,
                backgroundColor: 'rgba(37, 99, 235, 0.85)',
                borderRadius: 6
            },
            {
                label: 'Pengeluaran (Rp)',
                data: <?php echo json_encode($weekly_expenses); ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.85)',
                borderRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { callback: function(v) { return 'Rp ' + v.toLocaleString('id-ID'); } } } }
    }
});

// GRAFIK TAHUNAN
new Chart(id('financialChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
        datasets: [
            {
                label: 'Pemasukan Kas (Rp)',
                data: <?php echo json_encode(array_values($chart_cash_incomes)); ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.85)',
                borderRadius: 6
            },
            {
                label: 'Penjualan Produk (Rp)',
                data: <?php echo json_encode(array_values($chart_product_incomes)); ?>,
                backgroundColor: 'rgba(37, 99, 235, 0.85)',
                borderRadius: 6
            },
            {
                label: 'Pengeluaran (Rp)',
                data: <?php echo json_encode(array_values($chart_expenses)); ?>,
                backgroundColor: 'rgba(239, 68, 68, 0.85)',
                borderRadius: 6
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { callback: function(v) { return 'Rp ' + v.toLocaleString('id-ID'); } } } }
    }
});
</script>
</body>
</html>