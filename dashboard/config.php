<?php
// =============================================================
// CẤU HÌNH TRANG QUẢN LÝ CÂU HỎI
// =============================================================
return [
    // File câu hỏi mà trang quay số đọc vào
    'file_cau_hoi' => __DIR__ . '/../long-quay/questions.js',

    // File danh sách học sinh mà trang quay số đọc vào
    'file_hoc_sinh' => __DIR__ . '/../long-quay/students.js',

    // Nơi cất các bản sao lưu (tự tạo trước mỗi lần lưu thay đổi)
    'thu_muc_sao_luu' => __DIR__ . '/backups',
    'so_ban_sao_luu_giu_lai' => 30,

    // Mật khẩu vào trang quản lý. Để trống ('') = không cần đăng nhập.
    // NÊN đặt mật khẩu nếu máy tính này nối mạng chung với học sinh.
    'mat_khau' => '',

    // Số câu hỏi hiện trên mỗi trang (mặc định)
    'so_dong_moi_trang' => 20,

    // Dung lượng file nhập tối đa (byte)
    'dung_luong_nhap_toi_da' => 5 * 1024 * 1024,
];
