<?php
declare(strict_types=1);

// =============================================================
// KHỞI ĐỘNG: nạp thư viện, cấu hình, phiên làm việc, hàm dùng chung
// =============================================================

use Dashboard\CaiDat;
use Dashboard\KhoCauHoi;
use Dashboard\KhoHocSinh;

if (!is_file(__DIR__ . '/vendor/autoload.php')) {
    http_response_code(500);
    exit('Chưa cài thư viện. Mở cửa sổ lệnh tại thư mục dashboard và chạy: composer install');
}
require __DIR__ . '/vendor/autoload.php';

date_default_timezone_set('Asia/Ho_Chi_Minh');
mb_internal_encoding('UTF-8');

$cauHinh = require __DIR__ . '/config.php';

// ------------------------------------------------------------------
// ĐƯỜNG DẪN GỐC CỦA TRANG QUẢN LÝ
// Tính từ vị trí thật của index.php (ví dụ "/dashboard/" hoặc
// "/vhosts/long%20cau%20quay/dashboard/"), để CSS, JS và các liên kết luôn đúng
// dù mở bằng ".../dashboard", ".../dashboard/" hay ".../dashboard/index.php".
// ------------------------------------------------------------------
$thuMucScript = str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php')));
$thuMucScript = rtrim($thuMucScript, '/');
define('DUONG_DAN_GOC', implode('/', array_map('rawurlencode', explode('/', $thuMucScript))) . '/');

// Mở thiếu dấu "/" ở cuối (".../dashboard") thì chuyển sang ".../dashboard/"
$duongDanYeuCau = rawurldecode((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH));
if ($thuMucScript !== '' && $duongDanYeuCau === $thuMucScript) {
    $chuoiTruyVan = (string) ($_SERVER['QUERY_STRING'] ?? '');
    header('Location: ' . DUONG_DAN_GOC . ($chuoiTruyVan === '' ? '' : '?' . $chuoiTruyVan), true, 301);
    exit;
}

/** Đường dẫn đầy đủ tới một file trong thư mục dashboard, ví dụ url('assets/dashboard.js'). */
function url(string $duongDan = ''): string
{
    return DUONG_DAN_GOC . ltrim($duongDan, '/');
}

session_name('quan_ly_cau_hoi');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'use_strict_mode' => true,
]);

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

$kho = new KhoCauHoi($cauHinh['file_cau_hoi'], $cauHinh['thu_muc_sao_luu'], (int) $cauHinh['so_ban_sao_luu_giu_lai']);
$khoHocSinh = new KhoHocSinh(
    $cauHinh['file_hoc_sinh'] ?? __DIR__ . '/../long-quay/students.js',
    $cauHinh['thu_muc_sao_luu'],
    (int) $cauHinh['so_ban_sao_luu_giu_lai']
);
$khoCaiDat = new CaiDat($cauHinh['file_cai_dat'] ?? __DIR__ . '/../long-quay/settings.js');

// ------------------------------------------------------------------
// HÀM DÙNG CHUNG
// ------------------------------------------------------------------

/** Chống chèn mã khi in chữ ra trang. */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Mã chống giả mạo yêu cầu (CSRF). */
function maCsrf(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function kiemTraCsrf(): void
{
    $ma = (string) ($_POST['csrf'] ?? '');
    if ($ma === '' || !hash_equals(maCsrf(), $ma)) {
        http_response_code(400);
        exit('Phiên làm việc đã hết hạn. Hãy tải lại trang rồi thử lại.');
    }
}

/** Ghi thông báo để hiện ở lần tải trang kế tiếp. */
function thongBao(string $loai, string $noiDung, array $chiTiet = []): void
{
    $_SESSION['thong_bao'][] = ['loai' => $loai, 'noi_dung' => $noiDung, 'chi_tiet' => $chiTiet];
}

function layThongBao(): array
{
    $ds = $_SESSION['thong_bao'] ?? [];
    unset($_SESSION['thong_bao']);
    return $ds;
}

/** Các tham số lọc hợp lệ, dùng để giữ nguyên bộ lọc sau khi lưu. */
function thamSoLoc(array $nguon): array
{
    $ra = [];
    foreach (['tu_khoa', 'mon', 'loai', 'tinh_trang', 'so_dong', 'trang'] as $khoa) {
        if (isset($nguon[$khoa]) && is_string($nguon[$khoa]) && $nguon[$khoa] !== '') {
            $ra[$khoa] = mb_substr($nguon[$khoa], 0, 200);
        }
    }
    return $ra;
}

function chuyenHuong(array $thamSo = [], string $trang = 'index.php'): void
{
    $chuoi = http_build_query($thamSo);
    header('Location: ' . url($trang) . ($chuoi === '' ? '' : '?' . $chuoi), true, 303);
    exit;
}

function dinhDangDungLuong(int $byte): string
{
    return $byte >= 1024 * 1024 ? round($byte / 1048576, 1) . ' MB' : max(1, (int) round($byte / 1024)) . ' KB';
}
