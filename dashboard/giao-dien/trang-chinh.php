<?php
/**
 * Giao diện trang quản lý câu hỏi.
 * Các biến được chuẩn bị sẵn trong index.php.
 *
 * @var array $cauHinh
 * @var array $thongKe
 * @var array $trangHienTai
 * @var array $boLoc
 * @var array $danhSachMon
 * @var array $cacBanSaoLuu
 * @var array $thongBaoHienThi
 * @var array|null $formCu
 * @var string|null $loiDocFile
 * @var string $phienBan
 * @var string $csrf
 */

use Dashboard\KhoCauHoi;

$tenLoaiThongBao = ['ok' => 'thanh-cong', 'loi' => 'that-bai', 'canh-bao' => 'canh-bao'];
$anTatCaThaoTac = $loiDocFile !== null;

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
    <title>Quản lý câu hỏi – Lồng Quay May Mắn</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ctext y=%22.9em%22 font-size=%2290%22%3E%F0%9F%93%9A%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="<?= e(url('assets/dashboard.css')) ?>">
</head>
<body>

<header class="dau-trang">
    <div class="dau-trang-trong">
        <h1>📚 Quản lý câu hỏi</h1>
        <p class="phu-de">Ngân hàng câu hỏi cho <b>Lồng Quay May Mắn</b> – thay đổi ở đây được lưu thẳng vào <code>long-quay/questions.js</code></p>
        <div class="dau-trang-nut">
            <nav class="tab-trang" aria-label="Chọn trang quản lý">
                <a href="<?= e(url('index.php')) ?>" class="dang-mo" aria-current="page">📚 Câu hỏi</a>
                <a href="<?= e(url('hoc-sinh.php')) ?>">🧒 Học sinh</a>
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
            <p><b>File questions.js đang bị lỗi nên chưa đọc được:</b> <?= e($loiDocFile) ?></p>
            <p>Để không làm mất dữ liệu, trang tạm khóa việc thêm/sửa/xóa. Thầy cô có thể sửa tay file, <b>khôi phục một bản sao lưu</b> hoặc <b>nhập file và chọn "Thay thế toàn bộ"</b> ở phía dưới.</p>
        </div>
    <?php endif; ?>

    <!-- ===================== THỐNG KÊ ===================== -->
    <section class="the-thong-ke" aria-label="Thống kê">
        <div class="o-so"><span class="so"><?= $thongKe['tong'] ?></span><span class="nhan">Tổng số câu
            <small id="thongKeDangDung" data-lien-ket-tam-tat="<?= e(lienKet([], ['tinh_trang' => 'tam_tat'])) ?>"><?= $thongKe['tong'] - $thongKe['tamTat'] ?> đang dùng<?php if ($thongKe['tamTat']): ?> · <a href="<?= e(lienKet([], ['tinh_trang' => 'tam_tat'])) ?>"><?= $thongKe['tamTat'] ?> tạm tắt</a><?php endif; ?></small></span></div>
        <div class="o-so tu-luan"><span class="so"><?= $thongKe['tuLuan'] ?></span><span class="nhan">✍️ Tự luận</span></div>
        <div class="o-so trac-nghiem"><span class="so"><?= $thongKe['tracNghiem'] ?></span><span class="nhan">📝 Trắc nghiệm</span></div>
        <div class="o-so <?= $thongKe['loi'] || $thongKe['trung'] ? 'can-xem' : '' ?>">
            <span class="so"><?= $thongKe['loi'] + $thongKe['trung'] ?></span>
            <span class="nhan">⚠ Cần xem lại
                <small>(<?= $thongKe['loi'] ?> lỗi, <?= $thongKe['trung'] ?> trùng)</small>
            </span>
        </div>
        <div class="o-mon">
            <span class="nhan-nho">Theo môn</span>
            <div class="ds-mon">
                <?php foreach ($thongKe['mon'] as $tenMon => $so): ?>
                    <a class="chip" href="<?= e(lienKet([], ['mon' => $tenMon === '' ? '__khong' : $tenMon])) ?>">
                        <?= e($tenMon === '' ? 'Chưa ghi môn' : $tenMon) ?>
                        <b><?= $so['tuLuan'] + $so['tracNghiem'] ?></b>
                        <small><?= $so['tuLuan'] ?> TL · <?= $so['tracNghiem'] ?> TN</small>
                    </a>
                <?php endforeach; ?>
            </div>
            <?php if ($thongKe['tracNghiem'] > 0): ?>
                <span class="nhan-nho">Đáp án đúng trắc nghiệm nằm ở</span>
                <div class="thanh-dap-an" title="Nên rải đều để học sinh không đoán theo quy luật">
                    <?php foreach ($thongKe['dapAnDung'] as $chu => $so): ?>
                        <span class="phan chu-<?= strtolower($chu) ?>" style="flex-grow: <?= max($so, 0.0001) ?>"><?= $chu ?>: <?= $so ?></span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ===================== BỘ LỌC ===================== -->
    <section class="khung">
        <form method="get" class="bo-loc" role="search">
            <label class="o-tim">
                <span class="an-chu">Tìm kiếm</span>
                <input type="search" name="tu_khoa" value="<?= e($tuKhoa) ?>" placeholder="🔍 Tìm câu hỏi, đáp án… (gõ không dấu cũng được)">
            </label>
            <label>
                <span class="an-chu">Môn</span>
                <select name="mon">
                    <option value="">Tất cả môn</option>
                    <?php foreach ($danhSachMon as $m): ?>
                        <option value="<?= e($m) ?>" <?= $locMon === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                    <?php endforeach; ?>
                    <option value="__khong" <?= $locMon === '__khong' ? 'selected' : '' ?>>Chưa ghi môn</option>
                </select>
            </label>
            <label>
                <span class="an-chu">Loại</span>
                <select name="loai">
                    <option value="">Tất cả loại</option>
                    <option value="tuLuan" <?= $locLoai === 'tuLuan' ? 'selected' : '' ?>>✍️ Tự luận</option>
                    <option value="tracNghiem" <?= $locLoai === 'tracNghiem' ? 'selected' : '' ?>>📝 Trắc nghiệm</option>
                </select>
            </label>
            <label>
                <span class="an-chu">Tình trạng</span>
                <select name="tinh_trang">
                    <option value="">Mọi tình trạng</option>
                    <option value="dang_dung" <?= $locTinhTrang === 'dang_dung' ? 'selected' : '' ?>>▶ Đang dùng</option>
                    <option value="tam_tat" <?= $locTinhTrang === 'tam_tat' ? 'selected' : '' ?>>⏸ Tạm tắt</option>
                    <option value="trung" <?= $locTinhTrang === 'trung' ? 'selected' : '' ?>>Bị trùng</option>
                    <option value="loi" <?= $locTinhTrang === 'loi' ? 'selected' : '' ?>>Có lỗi</option>
                </select>
            </label>
            <label>
                <span class="an-chu">Số dòng mỗi trang</span>
                <select name="so_dong">
                    <?php foreach ([20, 50, 100, 0] as $n): ?>
                        <option value="<?= $n ?>" <?= $soDong === $n ? 'selected' : '' ?>><?= $n === 0 ? 'Hiện tất cả' : $n . ' dòng / trang' ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="nut nut-chinh">Lọc</button>
            <?php if ($dangLoc): ?><a class="nut nut-mo" href="<?= e(url('index.php')) ?>">Bỏ lọc</a><?php endif; ?>
        </form>
    </section>

    <!-- ===================== BẢNG CÂU HỎI ===================== -->
    <section class="khung">
        <div class="thanh-cong-cu">
            <h2>Danh sách câu hỏi
                <small><?= $dangLoc ? 'Tìm thấy ' . $tongKetQua . ' / ' . $thongKe['tong'] . ' câu' : $thongKe['tong'] . ' câu' ?></small>
            </h2>
            <div class="nhom-nut">
                <form method="post" id="formXoaNhieu" class="form-gon nhom-nut">
                    <?= $oAn(null) ?>
                    <span class="nhan-da-chon">Đã chọn <b class="so-da-chon">0</b> câu:</span>
                    <button class="nut nut-mo nut-chon-nhieu" name="hanh_dong" value="tat" disabled title="Tạm tắt: giữ lại nhưng không dùng khi quay">⏸ Tắt</button>
                    <button class="nut nut-mo nut-chon-nhieu" name="hanh_dong" value="bat" disabled title="Bật lại để dùng khi quay">▶ Bật</button>
                    <button class="nut nut-nguy-hiem nut-chon-nhieu" name="hanh_dong" value="xoa" disabled>🗑 Xóa</button>
                </form>
                <button type="button" class="nut nut-chinh" id="nutThem" <?= $anTatCaThaoTac ? 'disabled' : '' ?>>➕ Thêm câu hỏi</button>
            </div>
        </div>

        <?php if (!$trangHienTai): ?>
            <p class="trong">
                <?= $thongKe['tong'] === 0 ? 'Chưa có câu hỏi nào. Bấm "➕ Thêm câu hỏi" hoặc nhập từ file Excel ở bên dưới.' : 'Không có câu hỏi nào khớp với bộ lọc.' ?>
            </p>
        <?php else: ?>
            <div class="bang-cuon">
                <table class="bang">
                    <thead>
                    <tr>
                        <th class="cot-chon"><input type="checkbox" id="chonTatCa" aria-label="Chọn tất cả trên trang này"></th>
                        <th class="cot-so">#</th>
                        <th class="cot-mon">Môn</th>
                        <th class="cot-loai">Loại</th>
                        <th>Câu hỏi và đáp án</th>
                        <th class="cot-thao-tac">Thao tác</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($trangHienTai as $m): $c = $m['cau']; ?>
                        <tr class="<?= $m['loi'] ? 'co-loi' : '' ?> <?= $c['tamTat'] ? 'tam-tat' : '' ?>"
                            data-vi-tri="<?= $m['viTri'] ?>"
                            data-cau="<?= e(json_encode($c, JSON_UNESCAPED_UNICODE)) ?>">
                            <td class="cot-chon"><input type="checkbox" class="o-chon" value="<?= $m['viTri'] ?>" form="formXoaNhieu" name="vi_tri[]" aria-label="Chọn câu <?= $m['viTri'] + 1 ?>"></td>
                            <td class="cot-so"><?= $m['viTri'] + 1 ?></td>
                            <td class="cot-mon"><?= $c['mon'] === '' ? '<span class="mo">—</span>' : '<span class="chip-mon">' . e($c['mon']) . '</span>' ?></td>
                            <td class="cot-loai">
                                <span class="nhan-loai <?= $m['tn'] ? 'tn' : 'tl' ?>"><?= $m['tn'] ? '📝 Trắc nghiệm' : '✍️ Tự luận' ?></span>
                                <?php if ($c['tamTat']): ?><span class="nhan-tam-tat" title="Không dùng khi quay số">⏸ Đang tắt</span><?php endif; ?>
                            </td>
                            <td class="cot-cau-hoi">
                                <div class="cau-hoi"><?= e($c['cauHoi']) ?></div>
                                <?php if ($m['tn']): ?>
                                    <ol class="ds-lua-chon">
                                        <?php foreach ($c['luaChon'] as $j => $lc): $chu = KhoCauHoi::CHU_CAI[$j]; ?>
                                            <li class="<?= $chu === $c['dapAnDung'] ? 'dung' : '' ?>">
                                                <b><?= $chu ?>.</b> <?= e($lc) ?><?= $chu === $c['dapAnDung'] ? ' ✔' : '' ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ol>
                                <?php elseif ($c['dapAn'] !== ''): ?>
                                    <div class="dap-an">💡 <?= e($c['dapAn']) ?></div>
                                <?php else: ?>
                                    <div class="dap-an mo">(không có đáp án cố định)</div>
                                <?php endif; ?>
                                <?php if ($m['trung']): ?><div class="canh-bao-nho">⚠ Câu này bị trùng với câu khác</div><?php endif; ?>
                                <?php foreach ($m['loi'] as $loi): ?><div class="canh-bao-nho loi">⛔ <?= e($loi) ?></div><?php endforeach; ?>
                            </td>
                            <td class="cot-thao-tac">
                                <button type="button" class="nut nut-nho nut-sua">✏️ Sửa</button>
                                <form method="post" class="form-gon form-bat-tat">
                                    <?= $oAn($c['tamTat'] ? 'bat' : 'tat') ?>
                                    <input type="hidden" name="vi_tri[]" value="<?= $m['viTri'] ?>">
                                    <?php if ($c['tamTat']): ?>
                                        <button class="nut nut-nho nut-bat" title="Bật lại để dùng khi quay">▶ Bật</button>
                                    <?php else: ?>
                                        <button class="nut nut-nho nut-tat" title="Tạm tắt: giữ lại nhưng không dùng khi quay">⏸ Tắt</button>
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

            <?php if ($soTrang > 1): ?>
                <nav class="phan-trang" aria-label="Phân trang">
                    <?php if ($trang > 1): ?><a href="<?= e(lienKet($boLoc, ['trang' => $trang - 1])) ?>">‹ Trước</a><?php endif; ?>
                    <?php for ($p = 1; $p <= $soTrang; $p++): ?>
                        <?php if ($p === 1 || $p === $soTrang || abs($p - $trang) <= 2): ?>
                            <a href="<?= e(lienKet($boLoc, ['trang' => $p])) ?>" class="<?= $p === $trang ? 'hien-tai' : '' ?>"><?= $p ?></a>
                        <?php elseif (abs($p - $trang) === 3): ?>
                            <span>…</span>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($trang < $soTrang): ?><a href="<?= e(lienKet($boLoc, ['trang' => $trang + 1])) ?>">Sau ›</a><?php endif; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

    <div class="hai-cot">
        <!-- ===================== NHẬP / XUẤT ===================== -->
        <section class="khung" id="nhap-xuat">
            <h2>📥 Nhập câu hỏi từ file</h2>
            <form method="post" enctype="multipart/form-data" class="form-nhap" id="formNhap">
                <?= $oAn('nhap') ?>
                <input type="hidden" name="MAX_FILE_SIZE" value="<?= (int) $cauHinh['dung_luong_nhap_toi_da'] ?>">
                <label class="chon-tep">
                    <input type="file" name="tep" accept=".xlsx,.csv,.json,.js" required>
                    <span>Chọn file <b>Excel (.xlsx)</b>, <b>CSV</b>, <b>JSON</b> hoặc <b>questions.js</b></span>
                </label>
                <fieldset>
                    <legend>Cách nhập</legend>
                    <label><input type="radio" name="che_do" value="them" checked> Thêm vào danh sách hiện có</label>
                    <label><input type="radio" name="che_do" value="thay_the"> Thay thế toàn bộ danh sách</label>
                </fieldset>
                <label><input type="checkbox" name="bo_qua_trung" value="1" checked> Bỏ qua câu trùng với câu đã có</label>
                <button class="nut nut-chinh">📥 Nhập file</button>
            </form>
            <p class="goi-y">Mẹo: tải <b>file Excel mẫu</b>, điền câu hỏi theo các cột rồi nhập lại. Dòng nào lỗi sẽ được báo kèm số dòng, các dòng đúng vẫn được nhập. Trước khi nhập, danh sách cũ luôn được sao lưu.</p>

            <h2>📤 Tải về</h2>
            <div class="nhom-nut">
                <a class="nut nut-mo" href="<?= e(url('index.php')) ?>?xuat=mau">📄 Tải file Excel mẫu</a>
                <a class="nut nut-mo" href="<?= e(url('index.php')) ?>?xuat=excel">📊 Xuất Excel (.xlsx)</a>
                <a class="nut nut-mo" href="<?= e(url('index.php')) ?>?xuat=json">🧾 Xuất JSON</a>
            </div>
        </section>

        <!-- ===================== SAO LƯU ===================== -->
        <section class="khung" id="sao-luu">
            <h2>🕘 Sao lưu tự động</h2>
            <p class="goi-y">Mỗi lần thêm, sửa, xóa hoặc nhập file, bản cũ được tự động cất lại (giữ <?= (int) $cauHinh['so_ban_sao_luu_giu_lai'] ?> bản gần nhất). Bấm "Khôi phục" để quay về bản đó.</p>
            <?php if (!$cacBanSaoLuu): ?>
                <p class="trong">Chưa có bản sao lưu nào.</p>
            <?php else: ?>
                <ul class="ds-sao-luu">
                    <?php foreach (array_slice($cacBanSaoLuu, 0, 12) as $ban): ?>
                        <li>
                            <span class="thoi-gian"><?= e($ban['thoiGian'] ? $ban['thoiGian']->format('H:i:s d/m/Y') : '') ?></span>
                            <span class="ly-do"><?= e(TEN_LY_DO[$ban['lyDo']] ?? $ban['lyDo']) ?></span>
                            <span class="kich-thuoc"><?= e(dinhDangDungLuong((int) $ban['kichThuoc'])) ?></span>
                            <a class="nut nut-nho nut-mo" href="<?= e(url('index.php')) ?>?xuat=sao_luu&amp;ten=<?= e(rawurlencode($ban['ten'])) ?>" title="Tải bản này về máy">⬇</a>
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

<!-- ===================== HỘP THÊM / SỬA CÂU HỎI ===================== -->
<dialog id="hopThoai" class="hop-thoai" aria-labelledby="tieuDeHopThoai">
    <form method="post" id="formCauHoi" class="form-cau-hoi">
        <?= $oAn('luu') ?>
        <input type="hidden" name="vi_tri" value="">
        <div class="dau-hop-thoai">
            <h2 id="tieuDeHopThoai">Thêm câu hỏi</h2>
            <button type="button" class="nut-dong" data-dong aria-label="Đóng">×</button>
        </div>

        <div class="loi-form" id="loiForm" hidden></div>

        <div class="hang-form">
            <label class="truong">Môn học <small>(không bắt buộc)</small>
                <input name="mon" list="dsMon" maxlength="40" placeholder="VD: Toán" autocomplete="off">
            </label>
            <fieldset class="chon-loai">
                <legend>Loại câu hỏi</legend>
                <label><input type="radio" name="loai" value="tuLuan" checked> ✍️ Tự luận</label>
                <label><input type="radio" name="loai" value="tracNghiem"> 📝 Trắc nghiệm</label>
            </fieldset>
        </div>
        <datalist id="dsMon">
            <?php foreach ($danhSachMon as $m): ?><option value="<?= e($m) ?>"><?php endforeach; ?>
        </datalist>

        <label class="truong">Câu hỏi
            <textarea name="cau_hoi" rows="3" maxlength="<?= KhoCauHoi::DO_DAI_TOI_DA ?>" required placeholder="Nhập nội dung câu hỏi…"></textarea>
        </label>

        <div class="khoi-tu-luan">
            <label class="truong">Đáp án / gợi ý trả lời <small>(có thể để trống nếu trả lời tự do)</small>
                <textarea name="dap_an" rows="2" maxlength="<?= KhoCauHoi::DO_DAI_TOI_DA ?>" placeholder="VD: 12"></textarea>
            </label>
        </div>

        <div class="khoi-trac-nghiem" hidden>
            <p class="goi-y">Điền các phương án (ít nhất A và B) và bấm vào <b>chữ cái của phương án đúng</b>.</p>
            <?php foreach (KhoCauHoi::CHU_CAI as $j => $chu): ?>
                <div class="hang-lua-chon">
                    <label class="chon-dung chu-<?= strtolower($chu) ?>" title="Chọn <?= $chu ?> là đáp án đúng">
                        <input type="radio" name="dap_an_dung" value="<?= $chu ?>">
                        <span><?= $chu ?></span>
                    </label>
                    <input name="lua_chon[]" maxlength="200" placeholder="Phương án <?= $chu ?><?= $j >= 2 ? ' (không bắt buộc)' : '' ?>" aria-label="Phương án <?= $chu ?>">
                </div>
            <?php endforeach; ?>
        </div>

        <label class="o-tam-tat">
            <input type="checkbox" name="tam_tat" value="1">
            <span>⏸ <b>Tạm tắt câu này</b> – vẫn giữ trong ngân hàng nhưng <b>không dùng khi quay số</b></span>
        </label>

        <div class="chan-hop-thoai">
            <button type="button" class="nut nut-mo" data-dong>Hủy</button>
            <button class="nut nut-chinh">💾 Lưu câu hỏi</button>
        </div>
    </form>
</dialog>

<?php if ($formCu !== null): ?>
    <script type="application/json" id="duLieuFormCu"><?= json_encode($formCu, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?></script>
<?php endif; ?>
<script src="<?= e(url('assets/dashboard.js')) ?>"></script>
</body>
</html>
