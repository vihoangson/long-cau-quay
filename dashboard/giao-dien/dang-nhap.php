<?php
/** @var string $loiDangNhap */
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập – Quản lý câu hỏi</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22%3E%3Ctext y=%22.9em%22 font-size=%2290%22%3E%F0%9F%93%9A%3C/text%3E%3C/svg%3E">
    <link rel="stylesheet" href="<?= e(url('assets/dashboard.css')) ?>">
</head>
<body class="trang-dang-nhap">
<form method="post" class="khung khung-dang-nhap">
    <h1>📚 Quản lý câu hỏi</h1>
    <p>Nhập mật khẩu để vào trang quản lý.</p>
    <?php if ($loiDangNhap !== ''): ?><div class="thong-bao that-bai"><p><?= e($loiDangNhap) ?></p></div><?php endif; ?>
    <input type="hidden" name="csrf" value="<?= e(maCsrf()) ?>">
    <input type="hidden" name="hanh_dong" value="dang_nhap">
    <label class="truong">Mật khẩu
        <input type="password" name="mat_khau" required autofocus autocomplete="current-password">
    </label>
    <button class="nut nut-chinh">Đăng nhập</button>
</form>
</body>
</html>
