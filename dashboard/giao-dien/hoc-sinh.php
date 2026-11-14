<?php
/**
 * Giao diện trang quản lý học sinh.
 * Các biến được chuẩn bị sẵn trong hoc-sinh.php.
 *
 * @var array $cauHinh
 * @var array $thongKe
 * @var array $ketQuaLoc
 * @var array $boLoc
 * @var array $cacBanSaoLuu
 * @var array $thongBaoHienThi
 * @var array|null $formCu
 * @var string|null $loiDocFile
 * @var string $phienBan
 * @var string $csrf
 * @var int $soGoiY
 * @var bool $soLienMach
 */

use Dashboard\KhoHocSinh;

$tenLoaiThongBao = ['ok' => 'thanh-cong', 'loi' => 'that-bai', 'canh-bao' => 'canh-bao'];
$anTatCaThaoTac = $loiDocFile !== null;
$trangNay = url(TRANG_HOC_SINH);
$lienKetLoc = static fn(array $loc): string => $trangNay . ($loc ? '?' . http_build_query($loc) : '');

/** Các ô ẩn dùng chung cho mọi form gửi đi. */
$oAn = static function (?string $hanhDong) use ($csrf, $phienBan, $boLoc): string {
    $html = '<input type="hidden" name="csrf" value="' . e($csrf) . '">'
        . ($hanhDong === null ? '' : '<input type="hidden" name="hanh_dong" value="' . e($hanhDong) . '">')
        . '<input type="hidden" name="phien_ban" value="' . e($phienBan) . '">';
    foreach ($boLoc as $k => $v) {
        $html .= '<input type="hidden" name="loc[' . e($k) . ']" value="' . e($v) . '">';
    }
    return $html;
};
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quản lý học sinh – Lồng Quay May Mắn</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ctext y=%22.9em%22 font-size=%2290%22%3E%F0%9F%A7%92%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="<?= e(url('assets/dashboard.css')) ?>">
</head>
<body>

<header class="dau-trang">
    <div class="dau-trang-trong">
        <h1>🧒 Quản lý học sinh</h1>
        <p class="phu-de">Danh sách học sinh (mỗi bạn một quả bóng) – thay đổi ở đây được lưu thẳng vào <code>long-quay/students.js</code></p>
        <div class="dau-trang-nut">
            <nav class="tab-trang" aria-label="Chọn trang quản lý">
                <a href="<?= e(url('index.php')) ?>">📚 Câu hỏi</a>
                <a href="<?= e($trangNay) ?>" class="dang-mo" aria-current="page">🧒 Học sinh</a>
            </nav>
            <a class="nut nut-sang" href="<?= e(url('../long-quay/index.html')) ?>" target="_blank" rel="noopener">🎡 Mở trang quay số</a>
            <?php if ((string) $cauHinh['mat_khau'] !== ''): ?>
                <form method="post" class="form-gon">
                    <?= $oAn('dang_xuat') ?>
                    <button class="nut nut-mo">Đăng xuất</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="noi-dung">

    <?php foreach ($thongBaoHienThi as $tb): ?>
        <div class="thong-bao <?= e($tenLoaiThongBao[$tb['loai']] ?? 'thanh-cong') ?>" role="status">
            <p><?= e($tb['noi_dung']) ?></p>
            <?php if ($tb['chi_tiet']): ?>
                <ul><?php foreach ($tb['chi_tiet'] as $dong): ?><li><?= e($dong) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
            <button type="button" class="dong-thong-bao" aria-label="Đóng">×</button>
        </div>
    <?php endforeach; ?>

    <?php if ($loiDocFile !== null): ?>
        <div class="thong-bao that-bai">
            <p><b>File students.js đang bị lỗi nên chưa đọc được:</b> <?= e($loiDocFile) ?></p>
            <p>Để không làm mất dữ liệu, trang tạm khóa việc thêm/sửa/xóa. Thầy cô có thể sửa tay file, <b>khôi phục một bản sao lưu</b> hoặc <b>nhập danh sách và chọn "Thay thế toàn bộ"</b> ở phía dưới.</p>
        </div>
    <?php endif; ?>

    <!-- ===================== THỐNG KÊ ===================== -->
    <section class="the-thong-ke the-thong-ke-hs" aria-label="Thống kê">
        <div class="o-so"><span class="so" id="soTong"><?= $thongKe['tong'] ?></span><span class="nhan">Tổng số học sinh</span></div>
        <div class="o-so co-mat"><span class="so" id="soCoMat"><?= $thongKe['tong'] - $thongKe['vang'] ?></span><span class="nhan">🎱 Có mặt <small>(số bóng trong lồng)</small></span></div>
        <div class="o-so vang"><span class="so" id="soVang"><?= $thongKe['vang'] ?></span><span class="nhan"><a href="<?= e($lienKetLoc(['tinh_trang' => 'vang'])) ?>">🏠 Vắng</a></span></div>
        <div class="o-so <?= $thongKe['loi'] || $thongKe['trung'] ? 'can-xem' : '' ?>">
            <span class="so"><?= $thongKe['loi'] + $thongKe['trung'] ?></span>
            <span class="nhan">⚠ Cần xem lại <small>(<?= $thongKe['loi'] ?> lỗi / trùng số, <?= $thongKe['trung'] ?> trùng tên)</small></span>
        </div>
    </section>

    <!-- ===================== BỘ LỌC ===================== -->
    <section class="khung">
        <form method="get" action="<?= e($trangNay) ?>" class="bo-loc" role="search">
            <label class="o-tim">
                <span class="an-chu">Tìm kiếm</span>
                <input type="search" name="tu_khoa" value="<?= e($tuKhoa) ?>" placeholder="🔍 Tìm theo tên hoặc số… (gõ không dấu cũng được)">
            </label>
            <label>
                <span class="an-chu">Tình trạng</span>
                <select name="tinh_trang">
                    <option value="">Tất cả</option>
                    <option value="co_mat" <?= $locTinhTrang === 'co_mat' ? 'selected' : '' ?>>🎱 Có mặt</option>
                    <option value="vang" <?= $locTinhTrang === 'vang' ? 'selected' : '' ?>>🏠 Vắng</option>
                    <option value="trung" <?= $locTinhTrang === 'trung' ? 'selected' : '' ?>>Trùng tên</option>
                    <option value="loi" <?= $locTinhTrang === 'loi' ? 'selected' : '' ?>>Có lỗi / trùng số</option>
                </select>
            </label>
            <button class="nut nut-chinh">Lọc</button>
            <?php if ($dangLoc): ?><a class="nut nut-mo" href="<?= e($trangNay) ?>">Bỏ lọc</a><?php endif; ?>
        </form>
    </section>

    <!-- ===================== BẢNG HỌC SINH ===================== -->
    <section class="khung">
        <div class="thanh-cong-cu">
            <h2>Danh sách học sinh
                <small><?= $dangLoc ? 'Tìm thấy ' . count($ketQuaLoc) . ' / ' . $thongKe['tong'] . ' bạn' : $thongKe['tong'] . ' bạn' ?></small>
            </h2>
            <div class="nhom-nut">
                <form method="post" id="formChonNhieu" class="form-gon nhom-nut">
                    <?= $oAn(null) ?>
                    <span class="nhan-da-chon">Đã chọn <b class="so-da-chon">0</b> bạn:</span>
                    <button class="nut nut-mo nut-chon-nhieu" name="hanh_dong" value="vang" disabled title="Vắng: không có bóng trong lồng quay">🏠 Vắng</button>
                    <button class="nut nut-mo nut-chon-nhieu" name="hanh_dong" value="co_mat" disabled title="Có mặt: có bóng trong lồng quay">🎱 Có mặt</button>
                    <button class="nut nut-nguy-hiem nut-chon-nhieu" name="hanh_dong" value="xoa" disabled>🗑 Xóa</button>
                </form>
                <?php if (!$soLienMach && $thongKe['tong'] > 0 && !$anTatCaThaoTac): ?>
                    <form method="post" class="form-gon" id="formDanhSoLai">
                        <?= $oAn('danh_so_lai') ?>
                        <button class="nut nut-mo" title="Đánh số lại 1, 2, 3… theo thứ tự hiện tại">🔢 Đánh số lại</button>
                    </form>
                <?php endif; ?>
                <button type="button" class="nut nut-chinh" id="nutThem" data-so-goi-y="<?= $soGoiY ?>" <?= $anTatCaThaoTac ? 'disabled' : '' ?>>➕ Thêm học sinh</button>
            </div>
        </div>

        <?php if (!$ketQuaLoc): ?>
            <p class="trong">
                <?= $thongKe['tong'] === 0 ? 'Chưa có học sinh nào. Bấm "➕ Thêm học sinh" hoặc dán / nhập danh sách ở bên dưới.' : 'Không có học sinh nào khớp với bộ lọc.' ?>
            </p>
        <?php else: ?>
            <div class="bang-cuon">
                <table class="bang bang-hoc-sinh">
                    <thead>
                    <tr>
                        <th class="cot-chon"><input type="checkbox" id="chonTatCa" aria-label="Chọn tất cả"></th>
                        <th class="cot-bong">Số</th>
                        <th>Họ và tên</th>
                        <th class="cot-tinh-trang">Tình trạng</th>
                        <th class="cot-thao-tac">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($ketQuaLoc as $m): $hs = $m['hs']; ?>
                        <tr class="<?= $m['loi'] || $m['trungSo'] ? 'co-loi' : '' ?> <?= $hs['vang'] ? 'tam-tat' : '' ?>"
                            data-vi-tri="<?= $m['viTri'] ?>"
                            data-hs="<?= e(json_encode($hs, JSON_UNESCAPED_UNICODE)) ?>">
                            <td class="cot-chon"><input type="checkbox" class="o-chon" value="<?= $m['viTri'] ?>" form="formChonNhieu" name="vi_tri[]" aria-label="Chọn <?= e($hs['ten']) ?>"></td>
                            <td class="cot-bong"><span class="bong-so"><?= $hs['so'] ?: '?' ?></span></td>
                            <td>
                                <div class="ten-hs"><?= $hs['ten'] === '' ? '<span class="mo">(chưa có tên)</span>' : e($hs['ten']) ?></div>
                                <?php if ($m['trungSo']): ?><div class="canh-bao-nho loi">⛔ Số <?= $hs['so'] ?> bị trùng với bạn khác</div><?php endif; ?>
                                <?php if ($m['trungTen']): ?><div class="canh-bao-nho">⚠ Có bạn khác trùng tên</div><?php endif; ?>
                                <?php foreach ($m['loi'] as $loi): ?><div class="canh-bao-nho loi">⛔ <?= e($loi) ?></div><?php endforeach; ?>
                            </td>
                            <td class="cot-tinh-trang">
                                <?php if ($hs['vang']): ?>
                                    <span class="nhan-tam-tat" title="Không có bóng trong lồng quay">🏠 Vắng</span>
                                <?php else: ?>
                                    <span class="nhan-co-mat">🎱 Có mặt</span>
                                <?php endif; ?>
                            </td>
                            <td class="cot-thao-tac">
                                <button type="button" class="nut nut-nho nut-sua" <?= $anTatCaThaoTac ? 'disabled' : '' ?>>✏️ Sửa</button>
                                <form method="post" class="form-gon form-vang">
                                    <?= $oAn($hs['vang'] ? 'co_mat' : 'vang') ?>
                                    <input type="hidden" name="vi_tri[]" value="<?= $m['viTri'] ?>">
                                    <?php if ($hs['vang']): ?>
                                        <button class="nut nut-nho nut-bat" title="Đánh dấu có mặt">🎱 Có mặt</button>
                                    <?php else: ?>
                                        <button class="nut nut-nho nut-tat" title="Đánh dấu vắng hôm nay">🏠 Vắng</button>
                                    <?php endif; ?>
                                </form>
                                <form method="post" class="form-gon form-xoa">
                                    <?= $oAn('xoa') ?>
                                    <input type="hidden" name="vi_tri[]" value="<?= $m['viTri'] ?>">
                                    <button class="nut nut-nho nut-xoa">🗑 Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <div class="hai-cot">
        <!-- ===================== NHẬP / XUẤT ===================== -->
        <section class="khung" id="nhap-xuat">
            <h2>📥 Nhập danh sách học sinh</h2>
            <form method="post" enctype="multipart/form-data" class="form-nhap" id="formNhap">
                <?= $oAn('nhap') ?>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) $cauHinh['dung_luong_nhap_toi_da'] ?>">
                <label class="truong">Dán danh sách <small>(mỗi dòng một bạn, có thể kèm số: "1. Nguyễn Văn An", hoặc dán 2 cột Số – Họ tên từ Excel)</small>
                    <textarea name="van_ban" rows="5" placeholder="Nguyễn Văn An&#10;Trần Thị Bình&#10;Lê Hoàng Cường"></textarea>
                </label>
                <label class="chon-tep">
                    <input type="file" name="tep" accept=".xlsx,.csv,.txt,.json,.js">
                    <span>…hoặc chọn file <b>Excel (.xlsx)</b>, <b>CSV</b>, <b>.txt</b>, <b>JSON</b> hoặc <b>students.js</b></span>
                </label>
                <fieldset>
                    <legend>Cách nhập</legend>
                    <label><input type="radio" name="che_do" value="them" checked> Thêm vào danh sách hiện có</label>
                    <label><input type="radio" name="che_do" value="thay_the"> Thay thế toàn bộ danh sách (lớp mới)</label>
                </fieldset>
                <label><input type="checkbox" name="bo_qua_trung" value="1" checked> Bỏ qua bạn trùng tên với bạn đã có</label>
                <button class="nut nut-chinh">📥 Nhập danh sách</button>
            </form>
            <p class="goi-y">Bạn nào không ghi số, hoặc số bị trùng, sẽ được tự đánh số tiếp theo. Trước khi nhập, danh sách cũ luôn được sao lưu.</p>

            <h2>📤 Tải về</h2>
            <div class="nhom-nut">
                <a class="nut nut-mo" href="<?= e($trangNay) ?>?xuat=mau">📄 Tải file Excel mẫu</a>
                <a class="nut nut-mo" href="<?= e($trangNay) ?>?xuat=excel">📊 Xuất Excel (.xlsx)</a>
            </div>
        </section>

        <!-- ===================== SAO LƯU ===================== -->
        <section class="khung" id="sao-luu">
            <h2>🕘 Sao lưu tự động</h2>
            <p class="goi-y">Mỗi lần thêm, sửa, xóa hoặc nhập danh sách, bản cũ được tự động cất lại (giữ <?= (int) $cauHinh['so_ban_sao_luu_giu_lai'] ?> bản gần nhất). Bấm "Khôi phục" để quay về bản đó.</p>
            <?php if (!$cacBanSaoLuu): ?>
                <p class="trong">Chưa có bản sao lưu nào.</p>
            <?php else: ?>
                <ul class="ds-sao-luu">
                    <?php foreach (array_slice($cacBanSaoLuu, 0, 12) as $ban): ?>
                        <li>
                            <span class="thoi-gian"><?= e($ban['thoiGian'] ? $ban['thoiGian']->format('H:i:s d/m/Y') : '') ?></span>
                            <span class="ly-do"><?= e(TEN_LY_DO_HOC_SINH[$ban['lyDo']] ?? $ban['lyDo']) ?></span>
                            <span class="kich-thuoc"><?= e(dinhDangDungLuong((int) $ban['kichThuoc'])) ?></span>
                            <a class="nut nut-nho nut-mo" href="<?= e($trangNay) ?>?xuat=sao_luu&amp;ten=<?= e(rawurlencode($ban['ten'])) ?>" title="Tải bản này về máy">⬇</a>
                            <form method="post" class="form-gon form-khoi-phuc">
                                <?= $oAn('khoi_phuc') ?>
                                <input type="hidden" name="ten" value="<?= e($ban['ten']) ?>">
                                <button class="nut nut-nho nut-mo">↩ Khôi phục</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>
    </div>
</main>

<!-- ===================== HỘP THÊM / SỬA HỌC SINH ===================== -->
<dialog id="hopThoai" class="hop-thoai hop-thoai-nho" aria-labelledby="tieuDeHopThoai">
    <form method="post" id="formHocSinh" class="form-cau-hoi">
        <?= $oAn('luu') ?>
        <input type="hidden" name="vi_tri" value="">
        <div class="dau-hop-thoai">
            <h2 id="tieuDeHopThoai">Thêm học sinh</h2>
            <button type="button" class="nut-dong" data-dong aria-label="Đóng">×</button>
        </div>

        <div class="loi-form" id="loiForm" hidden></div>

        <div class="hang-form hang-form-hs">
            <label class="truong o-so-bong">Số trên bóng
                <input type="number" name="so" min="1" max="<?= KhoHocSinh::SO_TOI_DA ?>" step="1" required inputmode="numeric">
            </label>
            <label class="truong">Họ và tên
                <input name="ten" maxlength="<?= KhoHocSinh::DO_DAI_TEN_TOI_DA ?>" required placeholder="VD: Nguyễn Văn An" autocomplete="off">
            </label>
        </div>

        <label class="o-tam-tat">
            <input type="checkbox" name="vang" value="1">
            <span>🏠 <b>Vắng hôm nay</b> – vẫn giữ trong danh sách nhưng <b>không có bóng trong lồng quay</b></span>
        </label>

        <div class="chan-hop-thoai">
            <button type="button" class="nut nut-mo" data-dong>Hủy</button>
            <button class="nut nut-chinh">💾 Lưu</button>
        </div>
    </form>
</dialog>

<?php if ($formCu !== null): ?>
    <script type="application/json" id="duLieuFormCu"><?= json_encode($formCu, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>
<script src="<?= e(url('assets/hoc-sinh.js')) ?>"></script>
</body>
</html>
