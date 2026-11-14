<?php
declare(strict_types=1);

// =============================================================
// TRANG CHỦ – điều hướng tới các chức năng của Lồng Quay May Mắn
//   - long-quay/  : trang quay số (chạy offline được)
//   - dashboard/  : trang quản lý ngân hàng câu hỏi
// =============================================================

header('Content-Type: text/html; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

/** Chống chèn mã khi in chữ ra trang. */
function e(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

// Đường dẫn gốc của trang (đúng dù mở bằng ".../long cau quay" hay ".../long cau quay/")
$thuMuc = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'))), '/');
$goc = implode('/', array_map('rawurlencode', explode('/', $thuMuc))) . '/';

// ---------------- Đọc số liệu (nếu đọc lỗi thì chỉ hiện "—", trang vẫn chạy) ----------------
$soHocSinh = null;
$thongKe = null;
$loiCauHoi = null;
$autoload = __DIR__ . '/dashboard/vendor/autoload.php';

if (is_file($autoload)) {
    require $autoload;
    try {
        $hs = \Dashboard\KhoCauHoi::phanTichNoiDungJs((string) @file_get_contents(__DIR__ . '/long-quay/students.js'));
        $soHocSinh = count(array_filter($hs, 'is_array'));
    } catch (\Throwable $loi) {
        $soHocSinh = null;
    }
    try {
        $kho = new \Dashboard\KhoCauHoi(__DIR__ . '/long-quay/questions.js', __DIR__ . '/dashboard/backups');
        $dsCau = $kho->doc()['cauHoi'];
        $thongKe = ['tong' => count($dsCau), 'tuLuan' => 0, 'tracNghiem' => 0, 'tamTat' => 0, 'mon' => []];
        foreach ($dsCau as $c) {
            $thongKe['tamTat'] += empty($c['tamTat']) ? 0 : 1;
            $tn = \Dashboard\KhoCauHoi::laTracNghiem($c);
            $thongKe[$tn ? 'tracNghiem' : 'tuLuan']++;
            $tenMon = $c['mon'] === '' ? 'Chung' : $c['mon'];
            $thongKe['mon'][$tenMon] = ($thongKe['mon'][$tenMon] ?? 0) + 1;
        }
        arsort($thongKe['mon']);
    } catch (\Throwable $loi) {
        $loiCauHoi = $loi->getMessage();
    }
}

$so = static fn(?int $n): string => $n === null ? '—' : number_format($n, 0, ',', '.');
$mauBong = ['#ff4757', '#ffa502', '#2ed573', '#1e90ff', '#a55eea', '#ff6b81', '#f9ca24', '#00cec9', '#ff7f50', '#6ab04c'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lồng Quay May Mắn – Trang chủ</title>
    <meta name="description" content="Quay chọn học sinh ngẫu nhiên, hỏi đáp tự luận và trắc nghiệm cho lớp học.">
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ctext y=%22.9em%22 font-size=%2290%22%3E%F0%9F%8E%A1%3C/text%3E%3C/svg%3E">
    <style>
        /* =============================================================
           TRANG CHỦ – cùng bộ màu với trang quay số
           ============================================================= */
        :root {
            --tim: #6b3fe0;
            --tim-dam: #4d27b8;
            --tim-nhat: #f3eeff;
            --vang: #ffd23f;
            --cam: #ff9f1c;
            --hong: #ff2e7e;
            --xanh: #1f9d55;
            --chu: #24124f;
            --chu-mo: #6f6491;
            --vien: #e7e0fa;
            --nen: #f7f4ff;
        }

        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
            color: var(--chu);
            background: var(--nen);
            line-height: 1.5;
        }
        a { color: inherit; }

        /* ---------- Phần đầu trang ---------- */
        .hero {
            position: relative;
            overflow: hidden;
            color: #fff;
            background: linear-gradient(150deg, #4fd8ff 0%, #8f7bff 42%, #ff7fc6 100%);
            padding: 28px 20px 120px;
        }
        /* Chấm tròn trang trí */
        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            pointer-events: none;
            background-image:
                radial-gradient(circle, rgba(255, 255, 255, .35) 0 5px, transparent 6px),
                radial-gradient(circle, rgba(255, 240, 120, .45) 0 3px, transparent 4px);
            background-size: 130px 130px, 80px 80px;
            background-position: 0 0, 40px 55px;
        }
        /* Đường lượn sóng ở đáy */
        .hero::after {
            content: "";
            position: absolute;
            left: 0; right: 0; bottom: -1px;
            height: 90px;
            background: var(--nen);
            clip-path: polygon(0 55%, 8% 45%, 18% 52%, 30% 38%, 42% 50%, 55% 35%, 68% 48%, 80% 36%, 91% 47%, 100% 40%, 100% 100%, 0 100%);
        }

        .thanh-dieu-huong {
            position: relative;
            z-index: 2;
            max-width: 1180px;
            margin: 0 auto 26px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }
        .logo { display: flex; align-items: center; gap: 10px; font-weight: 900; font-size: 20px; text-decoration: none; }
        .logo span { font-size: 30px; }
        .menu { display: flex; gap: 6px; flex-wrap: wrap; }
        .menu a {
            text-decoration: none;
            font-weight: 700;
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .18);
            backdrop-filter: blur(4px);
        }
        .menu a:hover { background: rgba(255, 255, 255, .32); }

        .hero-trong {
            position: relative;
            z-index: 2;
            max-width: 1180px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 1.15fr .85fr;
            align-items: center;
            gap: 30px;
        }
        .nhan-tren {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .22);
            font-weight: 700;
            font-size: 14px;
        }
        .hero h1 {
            margin: 14px 0 12px;
            font-size: clamp(38px, 6vw, 70px);
            line-height: 1.05;
            font-weight: 900;
            letter-spacing: -.5px;
            text-shadow: 0 5px 0 var(--tim-dam), 0 12px 26px rgba(50, 20, 120, .35);
        }
        .hero h1 em { font-style: normal; color: var(--vang); }
        .hero p.mo-ta { font-size: clamp(17px, 2vw, 21px); max-width: 560px; margin: 0 0 26px; opacity: .96; }
        .nhom-nut { display: flex; flex-wrap: wrap; gap: 14px; }

        .nut {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 15px 26px;
            border-radius: 999px;
            font-weight: 900;
            font-size: 18px;
            text-decoration: none;
            transition: transform .12s, box-shadow .12s, filter .2s;
        }
        .nut:hover { transform: translateY(-2px); filter: brightness(1.05); }
        .nut:active { transform: translateY(3px); }
        .nut:focus-visible { outline: 4px solid #fff; outline-offset: 3px; }
        .nut-chinh {
            color: #6b2c00;
            background: linear-gradient(#fff07a, var(--vang) 45%, var(--cam));
            box-shadow: 0 7px 0 #c96a00, 0 14px 26px rgba(0, 0, 0, .22);
        }
        .nut-phu {
            color: var(--tim);
            background: #fff;
            box-shadow: 0 7px 0 #c9b8ff, 0 14px 26px rgba(0, 0, 0, .15);
        }

        /* ---------- Lồng cầu minh họa (vẽ bằng CSS) ---------- */
        .minh-hoa { position: relative; height: 360px; display: grid; place-items: center; }
        .vong-sang {
            position: absolute;
            width: 380px; height: 380px;
            border-radius: 50%;
            background: repeating-conic-gradient(rgba(255, 245, 170, .38) 0 10deg, transparent 10deg 30deg);
            animation: xoay 30s linear infinite;
            mask: radial-gradient(circle, #000 35%, transparent 70%);
            -webkit-mask: radial-gradient(circle, #000 35%, transparent 70%);
        }
        .cau {
            position: relative;
            width: 250px; height: 250px;
            border-radius: 50%;
            background: radial-gradient(circle at 35% 30%, rgba(255, 255, 255, .55), rgba(255, 255, 255, .12) 45%, rgba(120, 90, 255, .18) 100%);
            border: 4px solid rgba(255, 255, 255, .85);
            box-shadow: inset 0 0 40px rgba(255, 255, 255, .5), 0 22px 40px rgba(60, 20, 140, .35);
            perspective: 600px;
        }
        /* Các vòng nan xoay quanh trục để trông như lồng cầu đang quay */
        .nan {
            position: absolute;
            inset: -4px;
            border-radius: 50%;
            border: 3px solid rgba(255, 230, 128, .9);
            animation: quayNan 4s linear infinite;
        }
        .nan:nth-child(2) { animation-delay: -1s; border-color: rgba(255, 255, 255, .85); }
        .nan:nth-child(3) { animation-delay: -2s; }
        .nan:nth-child(4) { animation-delay: -3s; border-color: rgba(255, 255, 255, .85); }
        .xich-dao {
            position: absolute;
            left: -4px; right: -4px; top: 50%;
            height: 60px;
            margin-top: -30px;
            border-radius: 50%;
            border: 4px solid rgba(255, 210, 63, .95);
        }
        .bong {
            position: absolute;
            width: 46px; height: 46px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 900;
            font-size: 15px;
            color: #1d1d2b;
            background: radial-gradient(circle at 35% 30%, #fff 0 12%, var(--mau) 45%, color-mix(in srgb, var(--mau) 65%, #1a0040));
            box-shadow: inset -4px -6px 10px rgba(0, 0, 0, .18);
            animation: nhay 2.6s ease-in-out infinite;
        }
        .bong::before {
            content: "";
            position: absolute;
            width: 24px; height: 24px;
            border-radius: 50%;
            background: #fffdf5;
            z-index: 0;
        }
        .bong b { position: relative; z-index: 1; }
        .chan-de {
            position: absolute;
            bottom: 2px;
            width: 300px; height: 40px;
            border-radius: 50%;
            background: radial-gradient(ellipse, rgba(60, 20, 140, .35), transparent 70%);
        }
        /* Quả bóng trúng bay lên phía trên */
        .bong-trung {
            position: absolute;
            top: 6px; right: 40px;
            width: 70px; height: 70px;
            font-size: 22px;
            animation: bayLen 5s ease-in-out infinite;
        }
        .bong-trung::before { width: 38px; height: 38px; }

        @keyframes xoay { to { transform: rotate(360deg); } }
        @keyframes quayNan { from { transform: rotateY(0); } to { transform: rotateY(180deg); } }
        @keyframes nhay {
            0%, 100% { transform: translate(0, 0); }
            25% { transform: translate(var(--dx), -38px); }
            50% { transform: translate(calc(var(--dx) * -.5), -10px); }
            75% { transform: translate(calc(var(--dx) * .6), -26px); }
        }
        @keyframes bayLen {
            0%, 35% { transform: translateY(150px) scale(.5); opacity: 0; }
            50% { transform: translateY(0) scale(1.1); opacity: 1; }
            85% { transform: translateY(-6px) scale(1); opacity: 1; }
            100% { transform: translateY(-6px) scale(1); opacity: 0; }
        }

        /* ---------- Nội dung ---------- */
        main { max-width: 1180px; margin: -70px auto 0; padding: 0 20px 50px; position: relative; z-index: 3; }
        .tieu-de-phan { display: flex; align-items: baseline; justify-content: space-between; gap: 10px; margin: 46px 0 16px; }
        .tieu-de-phan h2 { margin: 0; font-size: 28px; font-weight: 900; }
        .tieu-de-phan p { margin: 0; color: var(--chu-mo); }

        /* Thẻ chức năng */
        .luoi-chuc-nang { display: grid; grid-template-columns: repeat(12, 1fr); gap: 18px; }
        .the {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 12px;
            padding: 24px;
            border-radius: 22px;
            background: #fff;
            border: 1px solid var(--vien);
            box-shadow: 0 10px 30px rgba(77, 39, 184, .09);
            text-decoration: none;
            transition: transform .18s, box-shadow .18s;
        }
        a.the:hover { transform: translateY(-5px); box-shadow: 0 18px 40px rgba(77, 39, 184, .16); }
        a.the:focus-visible { outline: 4px solid var(--vang); outline-offset: 3px; }
        .the-lon { grid-column: span 7; }
        .the-vua { grid-column: span 5; }
        .the-nho { grid-column: span 4; }

        .bieu-tuong {
            width: 58px; height: 58px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            font-size: 30px;
            box-shadow: inset 0 -4px 0 rgba(0, 0, 0, .1);
        }
        .the h3 { margin: 0; font-size: 22px; font-weight: 900; }
        .the p { margin: 0; color: var(--chu-mo); }
        .the .di-toi { margin-top: auto; font-weight: 800; color: var(--tim); display: inline-flex; gap: 6px; align-items: center; }
        a.the:hover .di-toi span { transform: translateX(4px); }
        .di-toi span { transition: transform .18s; }

        .the-quay { background: linear-gradient(140deg, #fff9df, #fff 55%); border-color: #ffe7a3; }
        .the-quay .bieu-tuong { background: var(--vang); }
        .the-quan-ly .bieu-tuong { background: #e6dcff; }
        .bieu-tuong.hong { background: #ffe0ec; }
        .bieu-tuong.xanh { background: #d9f6e4; }
        .bieu-tuong.lam { background: #dcefff; }

        .hang-so { display: flex; flex-wrap: wrap; gap: 10px; }
        .o-so {
            flex: 1 1 110px;
            padding: 10px 14px;
            border-radius: 14px;
            background: var(--tim-nhat);
        }
        .o-so b { display: block; font-size: 26px; line-height: 1.1; color: var(--tim); }
        .o-so small { color: var(--chu-mo); font-weight: 600; }
        .the-quay .o-so { background: #fff3c4; }
        .the-quay .o-so b { color: #b46200; }

        .tien-do { display: grid; gap: 6px; }
        .tien-do .nhan { display: flex; justify-content: space-between; font-size: 14px; font-weight: 700; color: var(--chu-mo); }
        .thanh { height: 12px; border-radius: 999px; background: #ffeab0; overflow: hidden; }
        .thanh i { display: block; height: 100%; width: 0; border-radius: inherit; background: linear-gradient(90deg, var(--cam), var(--hong)); transition: width .8s; }
        .gan-nhat { font-size: 14px; color: var(--chu-mo); min-height: 21px; }
        .gan-nhat b { color: var(--chu); }

        .chip-mon { display: flex; flex-wrap: wrap; gap: 6px; }
        .chip-mon span { padding: 3px 10px; border-radius: 999px; background: #f1ecff; font-size: 13px; font-weight: 700; }
        .chip-mon span b { color: var(--tim); margin-left: 4px; }

        .canh-bao { padding: 10px 14px; border-radius: 12px; background: #fff4dc; color: #6b4100; font-size: 14px; }

        /* Các bước */
        .cac-buoc { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; counter-reset: buoc; }
        .buoc { position: relative; padding: 26px 22px 22px; border-radius: 22px; background: #fff; border: 1px solid var(--vien); }
        .buoc::before {
            counter-increment: buoc;
            content: counter(buoc);
            position: absolute;
            top: -18px; left: 22px;
            width: 40px; height: 40px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            font-weight: 900;
            font-size: 19px;
            color: #fff;
            background: var(--tim);
            box-shadow: 0 4px 0 var(--tim-dam);
        }
        .buoc h3 { margin: 6px 0 6px; font-size: 18px; }
        .buoc p { margin: 0; color: var(--chu-mo); font-size: 15px; }
        .buoc code { background: var(--tim-nhat); padding: 1px 6px; border-radius: 6px; font-size: 13px; color: var(--tim-dam); }
        .buoc a { color: var(--tim); font-weight: 700; }

        /* Phím tắt */
        .phim-tat { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .phim { display: flex; align-items: center; gap: 12px; padding: 14px 16px; border-radius: 16px; background: #fff; border: 1px solid var(--vien); }
        kbd {
            min-width: 44px;
            padding: 6px 10px;
            border-radius: 10px;
            background: #fff;
            border: 2px solid var(--vien);
            border-bottom-width: 5px;
            font: 800 15px "Segoe UI", Arial, sans-serif;
            text-align: center;
            color: var(--tim-dam);
        }
        .phim span { font-size: 14px; color: var(--chu-mo); }
        .phim span b { color: var(--chu); display: block; }

        footer { text-align: center; color: var(--chu-mo); font-size: 14px; padding: 10px 20px 30px; }

        /* ---------- Màn hình nhỏ ---------- */
        @media (max-width: 980px) {
            .hero-trong { grid-template-columns: 1fr; }
            .minh-hoa { height: 300px; order: -1; }
            .the-lon, .the-vua { grid-column: span 12; }
            .the-nho { grid-column: span 6; }
            .phim-tat { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 640px) {
            .hero { padding-bottom: 100px; }
            .menu { display: none; }
            .the-nho { grid-column: span 12; }
            .cac-buoc { grid-template-columns: 1fr; gap: 28px; }
            .phim-tat { grid-template-columns: 1fr; }
            .nut { width: 100%; justify-content: center; }
            .minh-hoa { transform: scale(.82); height: 260px; }
            .tieu-de-phan { flex-direction: column; }
        }
        /* Người dùng không thích chuyển động thì tắt hiệu ứng */
        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { animation: none !important; transition: none !important; }
            .bong-trung { opacity: 1; }
        }
    </style>
</head>
<body>

<header class="hero">
    <nav class="thanh-dieu-huong" aria-label="Điều hướng chính">
        <a class="logo" href="<?= e($goc) ?>"><span aria-hidden="true">🎡</span> Lồng Quay May Mắn</a>
        <div class="menu">
            <a href="#chuc-nang">Chức năng</a>
            <a href="#bat-dau">Bắt đầu</a>
            <a href="#phim-tat">Phím tắt</a>
        </div>
    </nav>

    <div class="hero-trong">
        <div>
            <span class="nhan-tren">✨ Dành cho thầy cô tiểu học</span>
            <h1>Quay số <em>vui học</em>,<br>gọi tên bất ngờ!</h1>
            <p class="mo-ta">Lồng cầu quay chọn học sinh ngẫu nhiên, không bao giờ trùng. Mỗi bạn trúng nhận một câu hỏi tự luận hoặc trắc nghiệm, kèm pháo hoa và tiếng chúc mừng.</p>
            <div class="nhom-nut">
                <a class="nut nut-chinh" href="<?= e($goc) ?>long-quay/index.html">🎡 Bắt đầu quay</a>
                <a class="nut nut-phu" href="<?= e($goc) ?>dashboard/">📚 Quản lý câu hỏi</a>
            </div>
        </div>

        <div class="minh-hoa" aria-hidden="true">
            <div class="vong-sang"></div>
            <div class="chan-de"></div>
            <div class="cau">
                <div class="nan"></div><div class="nan"></div><div class="nan"></div><div class="nan"></div>
                <div class="xich-dao"></div>
                <?php
                // Vài quả bóng nảy trong lồng: [trái, dưới, số, độ lệch ngang, độ trễ]
                $cacBong = [[40, 30, 3, 14, 0], [92, 18, 7, -10, .4], [146, 26, 1, 12, .9], [64, 70, 9, -16, 1.3], [120, 66, 5, 10, .2], [176, 60, 2, -8, 1.6]];
                foreach ($cacBong as $i => [$trai, $duoi, $soBong, $dx, $tre]):
                    ?><div class="bong" style="left: <?= $trai ?>px; bottom: <?= $duoi ?>px; --mau: <?= $mauBong[$i % count($mauBong)] ?>; --dx: <?= $dx ?>px; animation-delay: -<?= $tre ?>s"><b><?= $soBong ?></b></div><?php
                endforeach; ?>
            </div>
            <div class="bong bong-trung" style="--mau: #f9ca24; --dx: 0px"><b>8</b></div>
        </div>
    </div>
</header>

<main>
    <!-- ===================== CHỨC NĂNG ===================== -->
    <div class="tieu-de-phan" id="chuc-nang">
        <h2>Chức năng</h2>
        <p>Chọn việc cần làm hôm nay</p>
    </div>

    <div class="luoi-chuc-nang">
        <a class="the the-lon the-quay" href="<?= e($goc) ?>long-quay/index.html">
            <div class="bieu-tuong">🎡</div>
            <h3>Lồng quay may mắn</h3>
            <p>Bấm QUAY (hoặc phím cách), lồng cầu quay 6–7 giây rồi nhả ra một quả bóng. Số và tên bạn trúng hiện thật to cùng câu hỏi.</p>
            <div class="hang-so">
                <div class="o-so"><b><?= e($so($soHocSinh)) ?></b><small>học sinh trong lồng</small></div>
                <div class="o-so"><b id="soDaQuay">0</b><small>bạn đã được quay</small></div>
            </div>
            <div class="tien-do">
                <div class="nhan"><span>Tiến độ lượt quay</span><span id="phanTram">0%</span></div>
                <div class="thanh"><i id="thanhTienDo"></i></div>
                <div class="gan-nhat" id="ganNhat">Chưa có lượt quay nào trên trình duyệt này.</div>
            </div>
            <span class="di-toi">Mở trang quay số <span>→</span></span>
        </a>

        <a class="the the-vua the-quan-ly" href="<?= e($goc) ?>dashboard/">
            <div class="bieu-tuong">📚</div>
            <h3>Quản lý câu hỏi</h3>
            <p>Thêm, sửa, xóa, lọc câu hỏi tự luận và trắc nghiệm. Mọi thay đổi được lưu thẳng vào trang quay số.</p>
            <?php if ($thongKe !== null): ?>
                <div class="hang-so">
                    <div class="o-so"><b><?= e($so($thongKe['tong'] - $thongKe['tamTat'])) ?></b><small>câu đang dùng<?= $thongKe['tamTat'] ? ' (' . (int) $thongKe['tamTat'] . ' tạm tắt)' : '' ?></small></div>
                    <div class="o-so"><b><?= e($so($thongKe['tuLuan'])) ?></b><small>tự luận</small></div>
                    <div class="o-so"><b><?= e($so($thongKe['tracNghiem'])) ?></b><small>trắc nghiệm</small></div>
                </div>
                <div class="chip-mon">
                    <?php foreach ($thongKe['mon'] as $tenMon => $soCau): ?>
                        <span><?= e((string) $tenMon) ?><b><?= (int) $soCau ?></b></span>
                    <?php endforeach; ?>
                </div>
            <?php elseif ($loiCauHoi !== null): ?>
                <div class="canh-bao">⚠ File câu hỏi đang lỗi: <?= e($loiCauHoi) ?></div>
            <?php else: ?>
                <div class="canh-bao">Chưa cài thư viện cho trang quản lý. Xem hướng dẫn trong <code>dashboard/README.md</code>.</div>
            <?php endif; ?>
            <span class="di-toi">Mở trang quản lý <span>→</span></span>
        </a>

        <a class="the the-nho" href="<?= e($goc) ?>dashboard/#nhap-xuat">
            <div class="bieu-tuong xanh">📥</div>
            <h3>Nhập từ Excel</h3>
            <p>Soạn câu hỏi trong Excel theo file mẫu rồi nhập một lần. Dòng lỗi được báo kèm số dòng.</p>
            <span class="di-toi">Nhập / xuất file <span>→</span></span>
        </a>

        <a class="the the-nho" href="<?= e($goc) ?>dashboard/?loai=tracNghiem">
            <div class="bieu-tuong hong">📝</div>
            <h3>Câu trắc nghiệm</h3>
            <p>Bốn phương án A, B, C, D. Học sinh bấm chọn hoặc thầy cô nhấn phím, máy báo đúng sai ngay.</p>
            <span class="di-toi">Xem câu trắc nghiệm <span>→</span></span>
        </a>

        <a class="the the-nho" href="<?= e($goc) ?>dashboard/#sao-luu">
            <div class="bieu-tuong lam">🕘</div>
            <h3>Sao lưu tự động</h3>
            <p>Mỗi lần thay đổi đều cất lại bản cũ. Lỡ tay xóa nhầm thì khôi phục chỉ với một cú bấm.</p>
            <span class="di-toi">Xem các bản sao lưu <span>→</span></span>
        </a>
    </div>

    <!-- ===================== BẮT ĐẦU ===================== -->
    <div class="tieu-de-phan" id="bat-dau">
        <h2>Bắt đầu trong 3 bước</h2>
        <p>Chuẩn bị một lần, dùng cả năm học</p>
    </div>
    <div class="cac-buoc">
        <div class="buoc">
            <h3>👩‍🎓 Nhập danh sách lớp</h3>
            <p>Mở file <code>long-quay/students.js</code> bằng Notepad, mỗi dòng một bạn: số thứ tự và họ tên. Có bao nhiêu bạn thì lồng có bấy nhiêu bóng.</p>
        </div>
        <div class="buoc">
            <h3>📚 Chuẩn bị câu hỏi</h3>
            <p>Vào <a href="<?= e($goc) ?>dashboard/">trang quản lý</a> để thêm câu hỏi hoặc nhập từ Excel. Có sẵn hơn 200 câu Toán, Tiếng Việt, Khoa học lớp 5.</p>
        </div>
        <div class="buoc">
            <h3>🎉 Chiếu lên và quay</h3>
            <p>Mở <a href="<?= e($goc) ?>long-quay/index.html">trang quay số</a>, bấm "Toàn màn hình", nhấn phím cách để quay. Lịch sử được lưu, tải lại trang vẫn còn.</p>
        </div>
    </div>

    <!-- ===================== PHÍM TẮT ===================== -->
    <div class="tieu-de-phan" id="phim-tat">
        <h2>Phím tắt khi đứng lớp</h2>
        <p>Không cần chạm chuột</p>
    </div>
    <div class="phim-tat">
        <div class="phim"><kbd>Space</kbd><span><b>Quay</b>lượt tiếp theo</span></div>
        <div class="phim"><kbd>A–D</kbd><span><b>Chọn đáp án</b>câu trắc nghiệm (hoặc 1–4)</span></div>
        <div class="phim"><kbd>F11</kbd><span><b>Toàn màn hình</b>khi chiếu lên máy chiếu</span></div>
        <div class="phim"><kbd>Esc</kbd><span><b>Thoát</b>toàn màn hình / đóng hộp thoại</span></div>
    </div>
</main>

<footer>
    🎡 Lồng Quay May Mắn – chạy được cả khi không có mạng. Mở trực tiếp <code>long-quay/index.html</code> cũng được.
</footer>

<script>
    // Đọc lịch sử quay mà trang quay số đã lưu trong trình duyệt (cùng địa chỉ trang web)
    (function () {
        var tongSo = <?= json_encode($soHocSinh) ?>;
        var duLieu = null;
        try { duLieu = JSON.parse(localStorage.getItem('longQuayXoSo_lichSu_v1') || 'null'); } catch (loi) { duLieu = null; }
        var lichSu = duLieu && Array.isArray(duLieu.lichSu) ? duLieu.lichSu : [];
        if (!lichSu.length) return;

        var daQuay = lichSu.length;
        document.getElementById('soDaQuay').textContent = daQuay;
        if (tongSo) {
            var phanTram = Math.min(100, Math.round(daQuay / tongSo * 100));
            document.getElementById('phanTram').textContent = phanTram + '%';
            requestAnimationFrame(function () { document.getElementById('thanhTienDo').style.width = phanTram + '%'; });
        }

        var cuoi = lichSu[lichSu.length - 1] || {};
        var dung = lichSu.filter(function (l) { return l.kq === 'dung'; }).length;
        var daTraLoi = lichSu.filter(function (l) { return l.kq === 'dung' || l.kq === 'sai'; }).length;
        var o = document.getElementById('ganNhat');
        o.textContent = '';
        o.append('Gần nhất: ');
        var b = document.createElement('b');
        b.textContent = 'Số ' + (cuoi.so || '?') + ' – ' + (cuoi.ten || '');
        o.append(b);
        if (daTraLoi) o.append(' · Trắc nghiệm đúng ' + dung + '/' + daTraLoi + ' câu');
    })();
</script>
</body>
</html>
