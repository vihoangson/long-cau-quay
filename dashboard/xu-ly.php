<?php
declare(strict_types=1);

// =============================================================
// XỬ LÝ THAO TÁC: thêm / sửa / xóa / nhập file / khôi phục / xuất file
// =============================================================

use Dashboard\CaiDat;
use Dashboard\KhoCauHoi;
use Dashboard\NhapXuat;

const TEN_LY_DO = [
    'them cau hoi' => 'Thêm câu hỏi',
    'sua cau hoi' => 'Sửa câu hỏi',
    'xoa cau hoi' => 'Xóa câu hỏi',
    'xoa nhieu cau hoi' => 'Xóa nhiều câu hỏi',
    'tat cau hoi' => 'Tạm tắt câu hỏi',
    'bat cau hoi' => 'Bật lại câu hỏi',
    'nhap file' => 'Nhập file',
    'thay the bang file nhap' => 'Thay thế bằng file nhập',
    'truoc khi khoi phuc' => 'Trước khi khôi phục',
];

/** Đọc câu hỏi từ form thêm / sửa. Trả về [loại, câu hỏi đã chuẩn hóa]. */
function cauTuForm(array $p): array
{
    $loai = ($p['loai'] ?? '') === 'tracNghiem' ? 'tracNghiem' : 'tuLuan';
    $luaChon = array_slice(array_values(array_filter((array) ($p['lua_chon'] ?? []), 'is_string')), 0, 4);
    $cau = KhoCauHoi::chuanHoa([
        'mon' => $p['mon'] ?? '',
        'cauHoi' => $p['cau_hoi'] ?? '',
        'dapAn' => $loai === 'tuLuan' ? ($p['dap_an'] ?? '') : '',
        'luaChon' => $loai === 'tracNghiem' ? $luaChon : [],
        'dapAnDung' => $loai === 'tracNghiem' ? ($p['dap_an_dung'] ?? '') : '',
        'tamTat' => !empty($p['tam_tat']),
    ]);
    return [$loai, $cau];
}

function xuLyLuu(KhoCauHoi $kho): void
{
    $phienBan = (string) ($_POST['phien_ban'] ?? '');
    $viTri = ($_POST['vi_tri'] ?? '') === '' ? null : (int) $_POST['vi_tri'];
    $kho->kiemTraPhienBan($phienBan);
    [$loai, $cau] = cauTuForm($_POST);

    // Giữ lại những gì thầy cô vừa gõ, để mở lại form nếu có lỗi
    $_SESSION['form_cu'] = [
        'vi_tri' => $viTri,
        'loai' => $loai,
        'mon' => $cau['mon'],
        'cau_hoi' => $cau['cauHoi'],
        'dap_an' => (string) ($_POST['dap_an'] ?? ''),
        'lua_chon' => array_map('strval', array_slice(array_values((array) ($_POST['lua_chon'] ?? [])), 0, 4)),
        'dap_an_dung' => (string) ($_POST['dap_an_dung'] ?? ''),
        'tam_tat' => $cau['tamTat'],
    ];

    $ds = $kho->doc()['cauHoi'];
    $loi = KhoCauHoi::kiemTra($cau);
    if ($loai === 'tracNghiem' && count($cau['luaChon']) === 0) {
        $loi[] = 'Câu trắc nghiệm cần ít nhất 2 phương án (A và B).';
        if ($cau['dapAnDung'] === '') {
            $loi[] = 'Chưa chọn đáp án đúng (A, B, C hoặc D).';
        }
    }
    if ($viTri !== null && !isset($ds[$viTri])) {
        $loi[] = 'Không tìm thấy câu hỏi cần sửa (có thể vừa bị xóa).';
    }
    $khoa = KhoCauHoi::khoaSoTrung($cau['cauHoi']);
    foreach ($ds as $i => $c) {
        if ($i !== $viTri && $khoa !== '' && KhoCauHoi::khoaSoTrung($c['cauHoi']) === $khoa) {
            $loi[] = 'Câu hỏi này đã có trong danh sách (câu #' . ($i + 1) . ').';
            break;
        }
    }
    if ($loi) {
        $_SESSION['form_cu']['loi'] = $loi;
        return;
    }

    if ($viTri === null) {
        $ds[] = $cau;
        $kho->ghi($ds, $phienBan, 'them-cau-hoi');
        thongBao('ok', 'Đã thêm câu hỏi mới.');
    } else {
        $ds[$viTri] = $cau;
        $kho->ghi($ds, $phienBan, 'sua-cau-hoi');
        thongBao('ok', 'Đã lưu thay đổi.');
    }
    unset($_SESSION['form_cu']);
}

function xuLyXoa(KhoCauHoi $kho): void
{
    $phienBan = (string) ($_POST['phien_ban'] ?? '');
    $kho->kiemTraPhienBan($phienBan);
    $cacViTri = array_unique(array_map('intval', (array) ($_POST['vi_tri'] ?? [])));
    $ds = $kho->doc()['cauHoi'];
    $cacViTri = array_values(array_filter($cacViTri, static fn($i) => isset($ds[$i])));
    if (!$cacViTri) {
        thongBao('loi', 'Chưa chọn câu hỏi nào để xóa.');
        return;
    }
    foreach ($cacViTri as $i) {
        unset($ds[$i]);
    }
    $kho->ghi(array_values($ds), $phienBan, count($cacViTri) > 1 ? 'xoa-nhieu-cau-hoi' : 'xoa-cau-hoi');
    thongBao('ok', 'Đã xóa ' . count($cacViTri) . ' câu hỏi. (Lỡ tay xóa nhầm? Khôi phục ở mục "Sao lưu".)');
}

/**
 * Tạm tắt ($tat = true) hoặc bật lại ($tat = false) một hay nhiều câu hỏi.
 * Câu tạm tắt vẫn nằm trong ngân hàng nhưng trang quay số không bốc tới.
 */
function xuLyBatTat(KhoCauHoi $kho, bool $tat): void
{
    $phienBan = (string) ($_POST['phien_ban'] ?? '');
    $kho->kiemTraPhienBan($phienBan);
    $cacViTri = array_unique(array_map('intval', (array) ($_POST['vi_tri'] ?? [])));
    $ds = $kho->doc()['cauHoi'];
    $cacViTri = array_values(array_filter($cacViTri, static fn($i) => isset($ds[$i])));
    if (!$cacViTri) {
        thongBao('loi', 'Chưa chọn câu hỏi nào.');
        return;
    }
    $soDoi = 0;
    foreach ($cacViTri as $i) {
        if ($ds[$i]['tamTat'] !== $tat) {
            $ds[$i]['tamTat'] = $tat;
            $soDoi++;
        }
    }
    if ($soDoi === 0) {
        thongBao('ok', $tat ? 'Các câu đã chọn vốn đang tạm tắt.' : 'Các câu đã chọn vốn đang được dùng.');
        return;
    }
    $kho->ghi($ds, $phienBan, $tat ? 'tat-cau-hoi' : 'bat-cau-hoi');
    thongBao('ok', $tat
        ? 'Đã tạm tắt ' . $soDoi . ' câu hỏi. Trang quay số sẽ không hỏi các câu này cho tới khi bật lại.'
        : 'Đã bật lại ' . $soDoi . ' câu hỏi. Các câu này sẽ được dùng ở những lượt quay tiếp theo.');
}

/**
 * Bật / tắt bằng fetch: làm giống xuLyBatTat nhưng trả về JSON để trang tự cập nhật
 * từng dòng, không phải tải lại (nên không mất vị trí đang cuộn).
 *
 * Trả về: {
 *   ok, thongBao: [{loai, noi_dung}], phienBan (mã phiên bản MỚI của file),
 *   trangThai: {viTri: true/false (đang tắt?)}, tong, tamTat,
 *   canTaiLai: true nếu thứ tự câu trong file vừa bị sắp xếp lại (số # đã đổi)
 * }
 */
function traLoiAjaxBatTat(KhoCauHoi $kho, bool $tat): void
{
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    $ketQua = ['ok' => false];
    try {
        $truoc = $kho->doc();
        // Khi lưu, file luôn được sắp xếp lại. Nếu trước đó file chưa theo thứ tự này
        // (ví dụ vừa sửa tay) thì số thứ tự các câu sẽ đổi → trang phải tải lại.
        $daDungThuTu = KhoCauHoi::sapXep($truoc['cauHoi']) === $truoc['cauHoi'];
        xuLyBatTat($kho, $tat);
        $sau = $kho->doc();
        $ds = $sau['cauHoi'];
        $trangThai = [];
        foreach ((array) ($_POST['vi_tri'] ?? []) as $v) {
            $i = (int) $v;
            if (isset($ds[$i])) {
                $trangThai[$i] = $ds[$i]['tamTat'];
            }
        }
        $ketQua = [
            'ok' => true,
            'phienBan' => $sau['phienBan'],
            'trangThai' => (object) $trangThai,
            'tong' => count($ds),
            'tamTat' => count(array_filter($ds, static fn($c) => $c['tamTat'])),
            'canTaiLai' => !$daDungThuTu && $sau['phienBan'] !== $truoc['phienBan'],
        ];
    } catch (RuntimeException $e) {
        thongBao('loi', $e->getMessage());
    }
    $ketQua['thongBao'] = layThongBao();      // lấy ra luôn để không hiện lại ở lần tải trang sau
    echo json_encode($ketQua, JSON_UNESCAPED_UNICODE);
}

function xuLyNhap(KhoCauHoi $kho, array $cauHinh): void
{
    $tep = $_FILES['tep'] ?? null;
    $maLoi = is_array($tep) ? (int) $tep['error'] : UPLOAD_ERR_NO_FILE;
    if ($maLoi === UPLOAD_ERR_NO_FILE) {
        thongBao('loi', 'Chưa chọn file để nhập.');
        return;
    }
    if (in_array($maLoi, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
        || ($maLoi === UPLOAD_ERR_OK && (int) $tep['size'] > $cauHinh['dung_luong_nhap_toi_da'])) {
        thongBao('loi', 'File quá lớn (tối đa ' . dinhDangDungLuong((int) $cauHinh['dung_luong_nhap_toi_da']) . ').');
        return;
    }
    if ($maLoi !== UPLOAD_ERR_OK || !is_uploaded_file($tep['tmp_name'])) {
        thongBao('loi', 'Tải file lên bị lỗi. Hãy thử lại.');
        return;
    }

    $cheDo = ($_POST['che_do'] ?? '') === 'thay_the' ? 'thay_the' : 'them';
    $boQuaTrung = !empty($_POST['bo_qua_trung']);
    $phienBan = (string) ($_POST['phien_ban'] ?? '');

    $kho->kiemTraPhienBan($phienBan);
    $docDuoc = NhapXuat::docFile($tep['tmp_name'], (string) $tep['name']);
    try {
        $hienCo = $kho->doc()['cauHoi'];
    } catch (RuntimeException $e) {
        if ($cheDo === 'them') {
            throw $e;                       // file hiện tại hỏng: chỉ cho "thay thế toàn bộ"
        }
        $hienCo = [];
    }

    $daCo = [];
    if ($cheDo === 'them') {
        foreach ($hienCo as $c) {
            $daCo[KhoCauHoi::khoaSoTrung($c['cauHoi'])] = true;
        }
    }
    $moi = [];
    $loi = $docDuoc['loi'];
    $trung = [];
    foreach ($docDuoc['cauHoi'] as $muc) {
        $c = $muc['cau'];
        $loiCau = KhoCauHoi::kiemTra($c);
        if ($loiCau) {
            $loi[] = $muc['viTri'] . ': ' . implode(' ', $loiCau);
            continue;
        }
        $khoa = KhoCauHoi::khoaSoTrung($c['cauHoi']);
        if ($boQuaTrung && isset($daCo[$khoa])) {
            $trung[] = $muc['viTri'] . ': ' . $c['cauHoi'];
            continue;
        }
        $daCo[$khoa] = true;
        $moi[] = $c;
    }

    $chiTiet = [];
    if ($trung) {
        $chiTiet[] = 'Bỏ qua ' . count($trung) . ' câu trùng với câu đã có: ' . implode(' | ', array_slice($trung, 0, 10))
            . (count($trung) > 10 ? ' …' : '');
    }
    foreach (array_slice($loi, 0, 30) as $dong) {
        $chiTiet[] = '⚠ ' . $dong;
    }
    if (count($loi) > 30) {
        $chiTiet[] = '… và ' . (count($loi) - 30) . ' lỗi khác.';
    }

    if (!$moi) {
        thongBao('loi', 'Không có câu hỏi hợp lệ nào để nhập. Danh sách cũ được giữ nguyên.', $chiTiet);
        return;
    }
    $ghi = $cheDo === 'them' ? array_merge($hienCo, $moi) : $moi;
    $kho->ghi($ghi, $phienBan, $cheDo === 'them' ? 'nhap-file' : 'thay-the-bang-file-nhap');
    thongBao(
        $loi ? 'canh-bao' : 'ok',
        'Đã nhập ' . count($moi) . ' câu hỏi từ file "' . basename((string) $tep['name']) . '"'
        . ($cheDo === 'thay_the' ? ' và thay thế toàn bộ danh sách cũ' : '') . '.'
        . ($loi ? ' Có ' . count($loi) . ' dòng bị lỗi chưa nhập được.' : ''),
        $chiTiet
    );
}

/** Lưu thời gian trả lời mỗi câu hỏi (ghi vào long-quay/settings.js). */
function xuLyLuuCaiDat(CaiDat $khoCaiDat): void
{
    $chu = trim((string) ($_POST['thoi_gian_tra_loi'] ?? ''));
    if (!preg_match('/^\d{1,4}$/', $chu)) {
        thongBao('loi', 'Thời gian trả lời phải là một số nguyên (số giây).');
        return;
    }
    $giay = (int) $chu;
    $loi = CaiDat::kiemTraThoiGian($giay);
    if ($loi !== '') {
        thongBao('loi', $loi);
        return;
    }
    $khoCaiDat->ghi(['thoiGianTraLoi' => $giay]);
    thongBao('ok', $giay === 0
        ? 'Đã tắt đồng hồ đếm ngược: học sinh trả lời không giới hạn thời gian.'
        : 'Đã đặt thời gian trả lời là ' . $giay . ' giây. Hết giờ mà chưa trả lời sẽ tính là sai. (Trang quay số đang mở cần tải lại – F5 – để nhận cài đặt mới.)');
}

function xuLyKhoiPhuc(KhoCauHoi $kho): void
{
    $ten = (string) ($_POST['ten'] ?? '');
    $soCau = $kho->khoiPhuc($ten, (string) ($_POST['phien_ban'] ?? ''));
    thongBao('ok', 'Đã khôi phục bản sao lưu (' . $soCau . ' câu hỏi). Bản đang dùng trước đó cũng đã được sao lưu, có thể quay lại bất cứ lúc nào.');
}

/** Tải file về máy: Excel, JSON, file mẫu, bản sao lưu. */
function xuLyXuat(KhoCauHoi $kho, string $kieu): void
{
    $ngay = date('Y-m-d');
    switch ($kieu) {
        case 'mau':
            NhapXuat::taoExcel([], true)->downloadAs('mau-nhap-cau-hoi.xlsx');
            exit;
        case 'excel':
            NhapXuat::taoExcel($kho->doc()['cauHoi'])->downloadAs('cau-hoi_' . $ngay . '.xlsx');
            exit;
        case 'json':
            header('Content-Type: application/json; charset=UTF-8');
            header('Content-Disposition: attachment; filename="cau-hoi_' . $ngay . '.json"');
            echo NhapXuat::xuatJson($kho->doc()['cauHoi']);
            exit;
        case 'sao_luu':
            $duongDan = $kho->duongDanSaoLuu((string) ($_GET['ten'] ?? ''));
            header('Content-Type: application/javascript; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . basename($duongDan) . '"');
            readfile($duongDan);
            exit;
    }
}
