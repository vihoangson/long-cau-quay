<?php
declare(strict_types=1);

// =============================================================
// TRANG QUẢN LÝ DANH SÁCH HỌC SINH – Lồng Quay May Mắn
// Đọc / ghi trực tiếp file ../long-quay/students.js
// =============================================================

use Dashboard\ChuViet;
use Dashboard\KhoHocSinh;

require __DIR__ . '/khoi-dong.php';
require __DIR__ . '/xu-ly-hoc-sinh.php';

const TRANG_HOC_SINH = 'hoc-sinh.php';

// Có đặt mật khẩu mà chưa đăng nhập → về trang chính để đăng nhập
if ((string) $cauHinh['mat_khau'] !== '' && empty($_SESSION['da_dang_nhap'])) {
    chuyenHuong();
}

// ---------------- Xử lý các nút bấm (POST) ----------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    kiemTraCsrf();
    $hanhDong = (string) ($_POST['hanh_dong'] ?? '');

    // Vắng / có mặt gửi bằng fetch (không tải lại trang): trả về JSON rồi dừng
    if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'fetch' && in_array($hanhDong, ['vang', 'co_mat'], true)) {
        traLoiAjaxVang($khoHocSinh, $hanhDong === 'vang');
        exit;
    }

    try {
        switch ($hanhDong) {
            case 'luu':
                xuLyLuuHocSinh($khoHocSinh);
                break;
            case 'xoa':
                xuLyXoaHocSinh($khoHocSinh);
                break;
            case 'vang':
                xuLyVangHocSinh($khoHocSinh, true);
                break;
            case 'co_mat':
                xuLyVangHocSinh($khoHocSinh, false);
                break;
            case 'danh_so_lai':
                xuLyDanhSoLai($khoHocSinh);
                break;
            case 'nhap':
                xuLyNhapHocSinh($khoHocSinh, $cauHinh);
                break;
            case 'khoi_phuc':
                xuLyKhoiPhucHocSinh($khoHocSinh);
                break;
            case 'dang_xuat':
                unset($_SESSION['da_dang_nhap']);
                break;
        }
    } catch (RuntimeException $e) {
        thongBao('loi', $e->getMessage());
    }
    chuyenHuong(thamSoLoc((array) ($_POST['loc'] ?? [])), TRANG_HOC_SINH);
}

// ---------------- Tải file về (GET ?xuat=...) ----------------
if (isset($_GET['xuat'])) {
    try {
        xuLyXuatHocSinh($khoHocSinh, (string) $_GET['xuat']);
    } catch (RuntimeException $e) {
        thongBao('loi', $e->getMessage());
        chuyenHuong([], TRANG_HOC_SINH);
    }
}

// ---------------- Đọc dữ liệu ----------------
$loiDocFile = null;
try {
    $duLieu = $khoHocSinh->doc();
} catch (RuntimeException $e) {
    $loiDocFile = $e->getMessage();
    $duLieu = ['hocSinh' => [], 'phienBan' => $khoHocSinh->phienBanHienTai()];
}
$tatCa = $duLieu['hocSinh'];
$phienBan = $duLieu['phienBan'];

// Đánh dấu số trùng, tên trùng và dòng bị lỗi
$demSo = [];
$demTen = [];
foreach ($tatCa as $hs) {
    $demSo[$hs['so']] = ($demSo[$hs['so']] ?? 0) + 1;
    $k = KhoHocSinh::khoaTen($hs['ten']);
    $demTen[$k] = ($demTen[$k] ?? 0) + 1;
}
$cacMuc = [];
foreach ($tatCa as $i => $hs) {
    $cacMuc[] = [
        'viTri' => $i,
        'hs' => $hs,
        'loi' => KhoHocSinh::kiemTra($hs),
        'trungSo' => $hs['so'] > 0 && $demSo[$hs['so']] > 1,
        'trungTen' => $hs['ten'] !== '' && $demTen[KhoHocSinh::khoaTen($hs['ten'])] > 1,
        'timKiem' => ChuViet::boDau($hs['so'] . ' ' . $hs['ten']),
    ];
}
// Hiện theo số thứ tự (file sửa tay có thể chưa đúng thứ tự)
usort($cacMuc, static fn($a, $b) => [$a['hs']['so'], $a['viTri']] <=> [$b['hs']['so'], $b['viTri']]);

// ---------------- Thống kê ----------------
$thongKe = ['tong' => count($cacMuc), 'vang' => 0, 'loi' => 0, 'trung' => 0];
foreach ($cacMuc as $m) {
    $thongKe['vang'] += $m['hs']['vang'] ? 1 : 0;
    $thongKe['loi'] += ($m['loi'] || $m['trungSo']) ? 1 : 0;
    $thongKe['trung'] += $m['trungTen'] ? 1 : 0;
}
$soGoiY = KhoHocSinh::soKeTiep($tatCa);
$soLienMach = $thongKe['tong'] > 0 && array_column(KhoHocSinh::sapXep($tatCa), 'so') === range(1, $thongKe['tong']);

// ---------------- Bộ lọc ----------------
$boLoc = array_intersect_key(thamSoLoc($_GET), ['tu_khoa' => 1, 'tinh_trang' => 1]);
$tuKhoa = trim($boLoc['tu_khoa'] ?? '');
$locTinhTrang = $boLoc['tinh_trang'] ?? '';
$cacTu = array_filter(preg_split('/\s+/u', ChuViet::boDau($tuKhoa)) ?: [], static fn($t) => $t !== '');
$ketQuaLoc = array_values(array_filter($cacMuc, static function (array $m) use ($cacTu, $locTinhTrang): bool {
    foreach ($cacTu as $tu) {
        if (!str_contains($m['timKiem'], $tu)) {
            return false;
        }
    }
    return match ($locTinhTrang) {
        'co_mat' => !$m['hs']['vang'],
        'vang' => $m['hs']['vang'],
        'loi' => $m['loi'] || $m['trungSo'],
        'trung' => $m['trungTen'],
        default => true,
    };
}));
$dangLoc = $tuKhoa !== '' || $locTinhTrang !== '';

$thongBaoHienThi = layThongBao();
$formCu = $_SESSION['form_cu_hoc_sinh'] ?? null;
unset($_SESSION['form_cu_hoc_sinh']);
$cacBanSaoLuu = $khoHocSinh->danhSachSaoLuu();
$csrf = maCsrf();

require __DIR__ . '/giao-dien/hoc-sinh.php';
