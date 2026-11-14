<?php
declare(strict_types=1);

// =============================================================
// XỬ LÝ THAO TÁC VỚI DANH SÁCH HỌC SINH:
// thêm / sửa / xóa / đánh dấu vắng / đánh số lại / nhập / khôi phục / xuất file
// =============================================================

use Dashboard\KhoHocSinh;
use Dashboard\NhapXuatHocSinh;

const TEN_LY_DO_HOC_SINH = [
    'them hoc sinh' => 'Thêm học sinh',
    'sua hoc sinh' => 'Sửa học sinh',
    'xoa hoc sinh' => 'Xóa học sinh',
    'xoa nhieu hoc sinh' => 'Xóa nhiều học sinh',
    'danh dau vang' => 'Đánh dấu vắng',
    'danh dau co mat' => 'Đánh dấu có mặt',
    'danh so lai' => 'Đánh số lại',
    'nhap danh sach' => 'Nhập danh sách',
    'thay the bang danh sach nhap' => 'Thay thế bằng danh sách nhập',
    'truoc khi khoi phuc' => 'Trước khi khôi phục',
];

/** Lấy các vị trí hợp lệ (đang có trong danh sách) từ $_POST['vi_tri']. */
function viTriDaChon(array $ds): array
{
    $cacViTri = array_unique(array_map('intval', (array) ($_POST['vi_tri'] ?? [])));
    return array_values(array_filter($cacViTri, static fn($i) => isset($ds[$i])));
}

function xuLyLuuHocSinh(KhoHocSinh $kho): void
{
    $phienBan = (string) ($_POST['phien_ban'] ?? '');
    $viTri = ($_POST['vi_tri'] ?? '') === '' ? null : (int) $_POST['vi_tri'];
    $kho->kiemTraPhienBan($phienBan);
    $hs = KhoHocSinh::chuanHoa([
        'so' => $_POST['so'] ?? '',
        'ten' => $_POST['ten'] ?? '',
        'vang' => !empty($_POST['vang']),
    ]);

    // Giữ lại những gì thầy cô vừa gõ, để mở lại form nếu có lỗi
    $_SESSION['form_cu_hoc_sinh'] = [
        'vi_tri' => $viTri,
        'so' => (string) ($_POST['so'] ?? ''),
        'ten' => (string) ($_POST['ten'] ?? ''),
        'vang' => $hs['vang'],
    ];

    $ds = $kho->doc()['hocSinh'];
    $loi = KhoHocSinh::kiemTra($hs);
    if ($viTri !== null && !isset($ds[$viTri])) {
        $loi[] = 'Không tìm thấy học sinh cần sửa (có thể vừa bị xóa).';
    }
    $trungTen = null;
    foreach ($ds as $i => $khac) {
        if ($i === $viTri) {
            continue;
        }
        if ($hs['so'] > 0 && $khac['so'] === $hs['so']) {
            $loi[] = 'Số ' . $hs['so'] . ' đã có bạn "' . $khac['ten'] . '" dùng rồi. Hãy chọn số khác.';
        }
        if ($trungTen === null && $hs['ten'] !== '' && KhoHocSinh::khoaTen($khac['ten']) === KhoHocSinh::khoaTen($hs['ten'])) {
            $trungTen = $khac;
        }
    }
    if ($loi) {
        $_SESSION['form_cu_hoc_sinh']['loi'] = $loi;
        return;
    }

    if ($viTri === null) {
        $ds[] = $hs;
        $kho->ghi($ds, $phienBan, 'them-hoc-sinh');
        thongBao('ok', 'Đã thêm bạn ' . $hs['ten'] . ' (số ' . $hs['so'] . ').');
    } else {
        $ds[$viTri] = $hs;
        $kho->ghi($ds, $phienBan, 'sua-hoc-sinh');
        thongBao('ok', 'Đã lưu thay đổi cho bạn ' . $hs['ten'] . '.');
    }
    if ($trungTen !== null) {
        thongBao('canh-bao', 'Lưu ý: lớp đã có một bạn khác cũng tên "' . $trungTen['ten'] . '" (số ' . $trungTen['so'] . '). Nếu đúng là hai bạn trùng tên thì không sao.');
    }
    unset($_SESSION['form_cu_hoc_sinh']);
}

function xuLyXoaHocSinh(KhoHocSinh $kho): void
{
    $phienBan = (string) ($_POST['phien_ban'] ?? '');
    $kho->kiemTraPhienBan($phienBan);
    $ds = $kho->doc()['hocSinh'];
    $cacViTri = viTriDaChon($ds);
    if (!$cacViTri) {
        thongBao('loi', 'Chưa chọn học sinh nào để xóa.');
        return;
    }
    $ten = $ds[$cacViTri[0]]['ten'];
    foreach ($cacViTri as $i) {
        unset($ds[$i]);
    }
    $kho->ghi(array_values($ds), $phienBan, count($cacViTri) > 1 ? 'xoa-nhieu-hoc-sinh' : 'xoa-hoc-sinh');
    thongBao('ok', (count($cacViTri) > 1 ? 'Đã xóa ' . count($cacViTri) . ' học sinh.' : 'Đã xóa bạn ' . $ten . '.')
        . ' (Lỡ tay xóa nhầm? Khôi phục ở mục "Sao lưu".)');
}

/** Đánh dấu vắng ($vang = true) hoặc có mặt ($vang = false) cho một hay nhiều bạn. */
function xuLyVangHocSinh(KhoHocSinh $kho, bool $vang): void
{
    $phienBan = (string) ($_POST['phien_ban'] ?? '');
    $kho->kiemTraPhienBan($phienBan);
    $ds = $kho->doc()['hocSinh'];
    $cacViTri = viTriDaChon($ds);
    if (!$cacViTri) {
        thongBao('loi', 'Chưa chọn học sinh nào.');
        return;
    }
    $soDoi = 0;
    foreach ($cacViTri as $i) {
        if ($ds[$i]['vang'] !== $vang) {
            $ds[$i]['vang'] = $vang;
            $soDoi++;
        }
    }
    if ($soDoi === 0) {
        thongBao('ok', $vang ? 'Các bạn đã chọn vốn đang được đánh dấu vắng.' : 'Các bạn đã chọn vốn đang có mặt.');
        return;
    }
    $kho->ghi($ds, $phienBan, $vang ? 'danh-dau-vang' : 'danh-dau-co-mat');
    $motBan = count($cacViTri) === 1;
    $ten = $motBan ? 'bạn ' . $ds[$cacViTri[0]]['ten'] : $soDoi . ' bạn';
    thongBao('ok', $vang
        ? 'Đã đánh dấu ' . $ten . ' vắng. Lồng quay sẽ không có bóng của ' . ($motBan ? 'bạn ấy.' : 'các bạn này.')
        : 'Đã đánh dấu ' . $ten . ' có mặt. Bóng sẽ có lại trong lồng quay.');
}

/**
 * Đánh dấu vắng / có mặt bằng fetch: trả về JSON để trang tự cập nhật từng dòng.
 * Trả về: { ok, thongBao, phienBan, trangThai: {viTri: vắng?}, tong, vang, canTaiLai }
 */
function traLoiAjaxVang(KhoHocSinh $kho, bool $vang): void
{
    header('Content-Type: application/json; charset=UTF-8');
    header('Cache-Control: no-store');
    $ketQua = ['ok' => false];
    try {
        $truoc = $kho->doc();
        // File luôn được sắp xếp lại theo số khi lưu. Nếu trước đó chưa đúng thứ tự
        // (ví dụ vừa sửa tay) thì vị trí các dòng sẽ đổi → trang phải tải lại.
        $daDungThuTu = KhoHocSinh::sapXep($truoc['hocSinh']) === $truoc['hocSinh'];
        xuLyVangHocSinh($kho, $vang);
        $sau = $kho->doc();
        $ds = $sau['hocSinh'];
        $trangThai = [];
        foreach (viTriDaChon($ds) as $i) {
            $trangThai[$i] = $ds[$i]['vang'];
        }
        $ketQua = [
            'ok' => true,
            'phienBan' => $sau['phienBan'],
            'trangThai' => (object) $trangThai,
            'tong' => count($ds),
            'vang' => count(array_filter($ds, static fn($hs) => $hs['vang'])),
            'canTaiLai' => !$daDungThuTu && $sau['phienBan'] !== $truoc['phienBan'],
        ];
    } catch (RuntimeException $e) {
        thongBao('loi', $e->getMessage());
    }
    $ketQua['thongBao'] = layThongBao();
    echo json_encode($ketQua, JSON_UNESCAPED_UNICODE);
}

/** Đánh số lại 1, 2, 3… theo thứ tự hiện tại (lấp các số bị bỏ trống sau khi xóa). */
function xuLyDanhSoLai(KhoHocSinh $kho): void
{
    $phienBan = (string) ($_POST['phien_ban'] ?? '');
    $kho->kiemTraPhienBan($phienBan);
    $ds = KhoHocSinh::sapXep($kho->doc()['hocSinh']);
    $soDoi = 0;
    foreach ($ds as $i => $hs) {
        if ($hs['so'] !== $i + 1) {
            $ds[$i]['so'] = $i + 1;
            $soDoi++;
        }
    }
    if ($soDoi === 0) {
        thongBao('ok', 'Danh sách đã được đánh số liền 1 → ' . count($ds) . ' rồi.');
        return;
    }
    $kho->ghi($ds, $phienBan, 'danh-so-lai');
    thongBao('ok', 'Đã đánh số lại 1 → ' . count($ds) . ' (' . $soDoi . ' bạn đổi số).');
}

/** Nhập từ file tải lên hoặc từ chữ dán vào ô "Dán danh sách". */
function xuLyNhapHocSinh(KhoHocSinh $kho, array $cauHinh): void
{
    $vanBan = trim((string) ($_POST['van_ban'] ?? ''));
    $tep = $_FILES['tep'] ?? null;
    $maLoi = is_array($tep) ? (int) $tep['error'] : UPLOAD_ERR_NO_FILE;
    $nguon = 'danh sách dán vào';

    if ($maLoi !== UPLOAD_ERR_NO_FILE) {
        if (in_array($maLoi, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            || ($maLoi === UPLOAD_ERR_OK && (int) $tep['size'] > $cauHinh['dung_luong_nhap_toi_da'])) {
            thongBao('loi', 'File quá lớn (tối đa ' . dinhDangDungLuong((int) $cauHinh['dung_luong_nhap_toi_da']) . ').');
            return;
        }
        if ($maLoi !== UPLOAD_ERR_OK || !is_uploaded_file($tep['tmp_name'])) {
            thongBao('loi', 'Tải file lên bị lỗi. Hãy thử lại.');
            return;
        }
    } elseif ($vanBan === '') {
        thongBao('loi', 'Chưa chọn file hoặc dán danh sách để nhập.');
        return;
    }

    $cheDo = ($_POST['che_do'] ?? '') === 'thay_the' ? 'thay_the' : 'them';
    $boQuaTrung = !empty($_POST['bo_qua_trung']);
    $phienBan = (string) ($_POST['phien_ban'] ?? '');
    $kho->kiemTraPhienBan($phienBan);

    if ($maLoi === UPLOAD_ERR_OK) {
        $docDuoc = NhapXuatHocSinh::docFile($tep['tmp_name'], (string) $tep['name']);
        $nguon = 'file "' . basename((string) $tep['name']) . '"';
    } else {
        $docDuoc = NhapXuatHocSinh::docVanBan($vanBan);
    }

    try {
        $hienCo = $kho->doc()['hocSinh'];
    } catch (RuntimeException $e) {
        if ($cheDo === 'them') {
            throw $e;                       // file hiện tại hỏng: chỉ cho "thay thế toàn bộ"
        }
        $hienCo = [];
    }
    $giuLai = $cheDo === 'them' ? $hienCo : [];

    $soDaDung = [];
    $tenDaCo = [];
    foreach ($giuLai as $hs) {
        $soDaDung[$hs['so']] = true;
        $tenDaCo[KhoHocSinh::khoaTen($hs['ten'])] = true;
    }

    $moi = [];
    $loi = $docDuoc['loi'];
    $trung = [];
    $doiSo = [];
    $canSo = [];                            // các bạn chưa có số / số bị trùng → đánh số sau
    foreach ($docDuoc['hocSinh'] as $muc) {
        $hs = $muc['hs'];
        $khoa = KhoHocSinh::khoaTen($hs['ten']);
        if ($hs['ten'] === '') {
            $loi[] = $muc['viTri'] . ': chưa có họ tên.';
            continue;
        }
        if ($boQuaTrung && isset($tenDaCo[$khoa])) {
            $trung[] = $muc['viTri'] . ': ' . $hs['ten'];
            continue;
        }
        $tenDaCo[$khoa] = true;
        $loiHs = array_filter(KhoHocSinh::kiemTra($hs), static fn($l) => !str_starts_with($l, 'Số thứ tự'));
        if ($loiHs) {
            $loi[] = $muc['viTri'] . ': ' . implode(' ', $loiHs);
            continue;
        }
        if ($hs['so'] >= 1 && $hs['so'] <= KhoHocSinh::SO_TOI_DA && !isset($soDaDung[$hs['so']])) {
            $soDaDung[$hs['so']] = true;
            $moi[] = $hs;
        } else {
            $canSo[] = ['viTri' => $muc['viTri'], 'soCu' => $hs['so'], 'hs' => $hs];
        }
    }
    // Đánh số cho các bạn còn lại: số trống nhỏ nhất (sau số lớn nhất hiện có nếu đang thêm vào)
    $batDau = $cheDo === 'them' ? KhoHocSinh::soKeTiep($giuLai) : 1;
    foreach ($canSo as $muc) {
        $so = $batDau;
        while (isset($soDaDung[$so])) {
            $so++;
        }
        if ($so > KhoHocSinh::SO_TOI_DA) {
            $loi[] = $muc['viTri'] . ': hết số (tối đa ' . KhoHocSinh::SO_TOI_DA . ').';
            continue;
        }
        $soDaDung[$so] = true;
        if ($muc['soCu'] > 0) {
            $doiSo[] = $muc['hs']['ten'] . ' (số ' . $muc['soCu'] . ' → ' . $so . ')';
        }
        $muc['hs']['so'] = $so;
        $moi[] = $muc['hs'];
    }

    $chiTiet = [];
    if ($trung) {
        $chiTiet[] = 'Bỏ qua ' . count($trung) . ' bạn trùng tên với bạn đã có: ' . implode(' | ', array_slice($trung, 0, 10))
            . (count($trung) > 10 ? ' …' : '');
    }
    if ($doiSo) {
        $chiTiet[] = 'Số bị trùng nên đã đổi: ' . implode(', ', array_slice($doiSo, 0, 15)) . (count($doiSo) > 15 ? ' …' : '');
    }
    foreach (array_slice($loi, 0, 30) as $dong) {
        $chiTiet[] = '⚠ ' . $dong;
    }
    if (count($loi) > 30) {
        $chiTiet[] = '… và ' . (count($loi) - 30) . ' lỗi khác.';
    }

    if (!$moi) {
        thongBao('loi', 'Không có học sinh hợp lệ nào để nhập. Danh sách cũ được giữ nguyên.', $chiTiet);
        return;
    }
    $kho->ghi(array_merge($giuLai, $moi), $phienBan, $cheDo === 'them' ? 'nhap-danh-sach' : 'thay-the-bang-danh-sach-nhap');
    thongBao(
        $loi ? 'canh-bao' : 'ok',
        'Đã nhập ' . count($moi) . ' học sinh từ ' . $nguon
        . ($cheDo === 'thay_the' ? ' và thay thế toàn bộ danh sách cũ' : '') . '.'
        . ($loi ? ' Có ' . count($loi) . ' dòng bị lỗi chưa nhập được.' : ''),
        $chiTiet
    );
}

function xuLyKhoiPhucHocSinh(KhoHocSinh $kho): void
{
    $so = $kho->khoiPhuc((string) ($_POST['ten'] ?? ''), (string) ($_POST['phien_ban'] ?? ''));
    thongBao('ok', 'Đã khôi phục bản sao lưu (' . $so . ' học sinh). Bản đang dùng trước đó cũng đã được sao lưu, có thể quay lại bất cứ lúc nào.');
}

/** Tải file về máy: Excel, file mẫu, bản sao lưu. */
function xuLyXuatHocSinh(KhoHocSinh $kho, string $kieu): void
{
    switch ($kieu) {
        case 'mau':
            NhapXuatHocSinh::taoExcel([], true)->downloadAs('mau-danh-sach-hoc-sinh.xlsx');
            exit;
        case 'excel':
            NhapXuatHocSinh::taoExcel($kho->doc()['hocSinh'])->downloadAs('hoc-sinh_' . date('Y-m-d') . '.xlsx');
            exit;
        case 'sao_luu':
            $duongDan = $kho->duongDanSaoLuu((string) ($_GET['ten'] ?? ''));
            header('Content-Type: application/javascript; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . basename($duongDan) . '"');
            readfile($duongDan);
            exit;
    }
}
