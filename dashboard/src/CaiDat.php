<?php
declare(strict_types=1);

namespace Dashboard;

use RuntimeException;

/**
 * Đọc / ghi file long-quay/settings.js – các cài đặt của trang quay số
 * (hiện có: thời gian trả lời mỗi câu hỏi).
 *
 * Dùng file .js (không phải .json) để trang quay số mở bằng nhấp đúp
 * (không có máy chủ) vẫn đọc được.
 */
final class CaiDat
{
    public const MAC_DINH = ['thoiGianTraLoi' => 30];
    public const THOI_GIAN_TOI_THIEU = 5;
    public const THOI_GIAN_TOI_DA = 600;

    public function __construct(private string $duongDanFile)
    {
    }

    /** Cài đặt hiện tại (thiếu file / thiếu mục thì dùng giá trị mặc định). */
    public function doc(): array
    {
        $caiDat = self::MAC_DINH;
        $noiDung = is_file($this->duongDanFile) ? (string) file_get_contents($this->duongDanFile) : '';
        // Bỏ các dòng chú thích để không đọc nhầm ví dụ trong phần hướng dẫn
        $noiDung = preg_replace('~^\s*//.*$~m', '', $noiDung) ?? $noiDung;
        if (preg_match('/["\']?thoiGianTraLoi["\']?\s*:\s*(\d{1,4})/', $noiDung, $m)) {
            $caiDat['thoiGianTraLoi'] = (int) $m[1];
        }
        return $caiDat;
    }

    /** Trả về lỗi (chuỗi rỗng = hợp lệ). */
    public static function kiemTraThoiGian(int $giay): string
    {
        if ($giay !== 0 && ($giay < self::THOI_GIAN_TOI_THIEU || $giay > self::THOI_GIAN_TOI_DA)) {
            return 'Thời gian trả lời phải từ ' . self::THOI_GIAN_TOI_THIEU . ' đến ' . self::THOI_GIAN_TOI_DA
                . ' giây (hoặc 0 để không đếm giờ).';
        }
        return '';
    }

    public function ghi(array $caiDat): void
    {
        $caiDat = array_merge(self::MAC_DINH, $caiDat);
        $noiDung = <<<JS
// =============================================================
// CÀI ĐẶT
// -------------------------------------------------------------
// Cách dễ nhất để sửa: mở trang quản lý "dashboard" → "Câu hỏi"
// → mục "⏱ Thời gian trả lời". Vẫn có thể sửa tay bằng Notepad.
//
//  - thoiGianTraLoi: số giây học sinh được trả lời mỗi câu hỏi.
//    Hết giờ mà chưa trả lời thì tính là SAI. Ghi 0 để không đếm giờ.
// =============================================================

var settings = {
  thoiGianTraLoi: {$caiDat['thoiGianTraLoi']}
};

JS;
        $tam = $this->duongDanFile . '.tmp';
        if (file_put_contents($tam, $noiDung, LOCK_EX) === false || !rename($tam, $this->duongDanFile)) {
            @unlink($tam);
            throw new RuntimeException('Không ghi được file settings.js. Kiểm tra quyền ghi của thư mục long-quay.');
        }
    }
}
