<?php
declare(strict_types=1);

// =============================================================
// TRANG QUẢN LÝ NGÂN HÀNG CÂU HỎI – Lồng Quay May Mắn
// Đọc / ghi trực tiếp file ../long-quay/questions.js
// =============================================================

use Dashboard\ChuViet;
use Dashboard\KhoCauHoi;

require __DIR__ . '/khoi-dong.php';
require __DIR__ . '/xu-ly.php';

// ---------------- Đăng nhập (chỉ khi có đặt mật khẩu trong config.php) ----------------
$canDangNhap = (string) $cauHinh['mat_khau'] !== '';
if ($canDangNhap && empty($_SESSION['da_dang_nhap'])) {
    $loiDangNhap = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['hanh_dong'] ?? '') === 'dang_nhap') {
        kiemTraCsrf();
        if (hash_equals((string) $cauHinh['mat_khau'], (string) ($_POST['mat_khau'] ?? ''))) {
            session_regenerate_id(true);
            $_SESSION['da_dang_nhap'] = true;
            chuyenHuong();
        }
        sleep(1);
        $loiDangNhap = 'Mật khẩu chưa đúng.';
    }
    require __DIR__ . '/giao-dien/dang-nhap.php';
    exit;
}

// ---------------- Xử lý các nút bấm (POST) ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $hanhDong = (string) ($_POST['hanh_dong'] ?? '');
    try {
        switch ($hanhDong) {
            case 'luu':
                xuLyLuu($kho);
                break;
            case 'xoa':
                xuLyXoa($kho);
                break;
            case 'tat':
                xuLyBatTat($kho, true);
                break;
            case 'bat':
                xuLyBatTat($kho, false);
                break;
            case 'nhap':
                xuLyNhap($kho, $cauHinh);
                break;
            case 'khoi_phuc':
                xuLyKhoiPhuc($kho);
                break;
            case 'dang_xuat':
                unset($_SESSION['da_dang_nhap']);
                break;
        }
    } catch (RuntimeException $e) {
        thongBao('loi', $e->getMessage());
    }
    chuyenHuong(thamSoLoc((array) ($_POST['loc'] ?? [])));
}

// ---------------- Tải file về (GET ?xuat=...) ----------------
if (isset($_GET['xuat'])) {
    try {
        xuLyXuat($kho, (string) $_GET['xuat']);
    } catch (RuntimeException $e) {
        thongBao('loi', $e->getMessage());
        chuyenHuong();
    }
}

// ---------------- Đọc dữ liệu ----------------
$loiDocFile = null;
try {
    $duLieu = $kho->doc();
} catch (RuntimeException $e) {
    $loiDocFile = $e->getMessage();
    $duLieu = ['cauHoi' => [], 'phienBan' => $kho->phienBanHienTai()];
}
$tatCaCau = $duLieu['cauHoi'];
$phienBan = $duLieu['phienBan'];

// Đánh dấu câu trùng và câu bị lỗi
$demKhoa = [];
foreach ($tatCaCau as $c) {
    $k = KhoCauHoi::khoaSoTrung($c['cauHoi']);
    $demKhoa[$k] = ($demKhoa[$k] ?? 0) + 1;
}
$cacMuc = [];
foreach ($tatCaCau as $i => $c) {
    $cacMuc[] = [
        'viTri' => $i,
        'cau' => $c,
        'tn' => KhoCauHoi::laTracNghiem($c),
        'loi' => KhoCauHoi::kiemTra($c),
        'trung' => ($demKhoa[KhoCauHoi::khoaSoTrung($c['cauHoi'])] ?? 0) > 1,
        'timKiem' => ChuViet::boDau($c['mon'] . ' ' . $c['cauHoi'] . ' ' . $c['dapAn'] . ' ' . implode(' ', $c['luaChon'])),
    ];
}

// ---------------- Thống kê ----------------
$thongKe = ['tong' => count($cacMuc), 'tuLuan' => 0, 'tracNghiem' => 0, 'loi' => 0, 'trung' => 0, 'tamTat' => 0, 'mon' => [], 'dapAnDung' => array_fill_keys(KhoCauHoi::CHU_CAI, 0)];
foreach ($cacMuc as $m) {
    $thongKe[$m['tn'] ? 'tracNghiem' : 'tuLuan']++;
    $thongKe['loi'] += $m['loi'] ? 1 : 0;
    $thongKe['trung'] += $m['trung'] ? 1 : 0;
    $thongKe['tamTat'] += $m['cau']['tamTat'] ? 1 : 0;
    $tenMon = $m['cau']['mon'];
    $thongKe['mon'][$tenMon] ??= ['tuLuan' => 0, 'tracNghiem' => 0];
    $thongKe['mon'][$tenMon][$m['tn'] ? 'tracNghiem' : 'tuLuan']++;
    if ($m['tn'] && isset($thongKe['dapAnDung'][$m['cau']['dapAnDung']])) {
        $thongKe['dapAnDung'][$m['cau']['dapAnDung']]++;
    }
}
$danhSachMon = array_values(array_unique(array_merge(
    ['Toán', 'Tiếng Việt', 'Khoa học'],
    array_filter(array_keys($thongKe['mon']), static fn($m) => $m !== '')
)));

// ---------------- Bộ lọc ----------------
$boLoc = thamSoLoc($_GET);
$tuKhoa = trim($boLoc['tu_khoa'] ?? '');
$locMon = $boLoc['mon'] ?? '';
$locLoai = $boLoc['loai'] ?? '';
$locTinhTrang = $boLoc['tinh_trang'] ?? '';
$soDong = (int) ($boLoc['so_dong'] ?? $cauHinh['so_dong_moi_trang']);
if (!in_array($soDong, [20, 50, 100, 0], true)) {
    $soDong = 20;
}

$cacTu = array_filter(preg_split('/\s+/u', ChuViet::boDau($tuKhoa)) ?: [], static fn($t) => $t !== '');
$ketQuaLoc = array_values(array_filter($cacMuc, static function (array $m) use ($cacTu, $locMon, $locLoai, $locTinhTrang): bool {
    foreach ($cacTu as $tu) {
        if (!str_contains($m['timKiem'], $tu)) {
            return false;
        }
    }
    if ($locMon === '__khong' && $m['cau']['mon'] !== '') {
        return false;
    }
    if ($locMon !== '' && $locMon !== '__khong' && $m['cau']['mon'] !== $locMon) {
        return false;
    }
    if ($locLoai === 'tuLuan' && $m['tn']) {
        return false;
    }
    if ($locLoai === 'tracNghiem' && !$m['tn']) {
        return false;
    }
    if ($locTinhTrang === 'trung' && !$m['trung']) {
        return false;
    }
    if ($locTinhTrang === 'loi' && !$m['loi']) {
        return false;
    }
    if ($locTinhTrang === 'dang_dung' && $m['cau']['tamTat']) {
        return false;
    }
    if ($locTinhTrang === 'tam_tat' && !$m['cau']['tamTat']) {
        return false;
    }
    return true;
}));

$tongKetQua = count($ketQuaLoc);
$soTrang = $soDong === 0 ? 1 : max(1, (int) ceil($tongKetQua / $soDong));
$trang = min($soTrang, max(1, (int) ($boLoc['trang'] ?? 1)));
$trangHienTai = $soDong === 0 ? $ketQuaLoc : array_slice($ketQuaLoc, ($trang - 1) * $soDong, $soDong);
$dangLoc = $tuKhoa !== '' || $locMon !== '' || $locLoai !== '' || $locTinhTrang !== '';

/** Đường dẫn giữ nguyên bộ lọc, chỉ đổi vài tham số. */
function lienKet(array $boLoc, array $doi): string
{
    $thamSo = array_filter(array_merge($boLoc, $doi), static fn($v) => $v !== '' && $v !== null);
    return url('index.php') . ($thamSo ? '?' . http_build_query($thamSo) : '');
}

$thongBaoHienThi = layThongBao();
$formCu = $_SESSION['form_cu'] ?? null;
unset($_SESSION['form_cu']);
$cacBanSaoLuu = $kho->danhSachSaoLuu();
$csrf = maCsrf();

require __DIR__ . '/giao-dien/trang-chinh.php';
