<?php
declare(strict_types=1);

namespace Dashboard;

/**
 * Đọc và ghi danh sách học sinh trong file long-quay/students.js.
 *
 * Mỗi học sinh trong chương trình có dạng thống nhất:
 *   [
 *     'so'   => 5,                 // số in trên quả bóng (1–999, không trùng)
 *     'ten'  => 'Hoàng Minh Đức',  // họ và tên
 *     'vang' => false,             // true = vắng hôm nay, không có bóng trong lồng
 *   ]
 */
final class KhoHocSinh extends KhoTepJs
{
    public const SO_TOI_DA = 999;
    public const DO_DAI_TEN_TOI_DA = 60;
    protected const TEN_BIEN = 'students';
    protected const TIEN_TO = 'students';
    protected const TEN_DU_LIEU = 'học sinh';

    /** Thứ tự trong file: theo số. */
    public function doc(): array
    {
        $kq = $this->docDanhSach();
        return ['hocSinh' => $kq['ds'], 'phienBan' => $kq['phienBan']];
    }

    protected static function tuChuTron(string $chu): array
    {
        return ['ten' => $chu];
    }

    // ------------------------------------------------------------------
    // CHUẨN HÓA VÀ KIỂM TRA
    // ------------------------------------------------------------------

    public static function chuanHoa(array $hs): array
    {
        $ten = $hs['ten'] ?? $hs['hoTen'] ?? $hs['name'] ?? '';
        $ten = is_array($ten) || is_object($ten) ? '' : trim(preg_replace('/\s+/u', ' ', (string) $ten) ?? '');
        return [
            'so' => self::chuanHoaSo($hs['so'] ?? $hs['stt'] ?? $hs['number'] ?? ''),
            'ten' => $ten,
            'vang' => self::laVang($hs['vang'] ?? $hs['absent'] ?? false),
        ];
    }

    /** true, 1, "x", "có", "vắng", "vắng mặt"… → true */
    private static function laVang($v): bool
    {
        if (is_string($v) && in_array(trim(ChuViet::boDau($v)), ['vang', 'vang mat', 'absent', 'nghi'], true)) {
            return true;
        }
        return KhoCauHoi::laGiaTriDung($v);
    }

    /** "05", 5, "5.0" → 5. Không hợp lệ → 0 (nghĩa là chưa có số). */
    public static function chuanHoaSo($v): int
    {
        if (is_int($v)) {
            return max(0, $v);
        }
        if (is_float($v) && floor($v) === $v) {
            return max(0, (int) $v);
        }
        $v = trim((string) (is_scalar($v) ? $v : ''));
        if (preg_match('/^0*(\d{1,6})(?:\.0+)?$/', $v, $m)) {
            return (int) $m[1];
        }
        return 0;
    }

    /** Trả về danh sách lỗi của một học sinh (rỗng = hợp lệ). */
    public static function kiemTra(array $hs): array
    {
        $loi = [];
        if ($hs['so'] < 1 || $hs['so'] > self::SO_TOI_DA) {
            $loi[] = 'Số thứ tự phải là số nguyên từ 1 đến ' . self::SO_TOI_DA . '.';
        }
        if ($hs['ten'] === '') {
            $loi[] = 'Chưa nhập họ tên.';
        } elseif (mb_strlen($hs['ten']) > self::DO_DAI_TEN_TOI_DA) {
            $loi[] = 'Họ tên quá dài (tối đa ' . self::DO_DAI_TEN_TOI_DA . ' ký tự).';
        }
        return $loi;
    }

    /** Chuỗi để so trùng tên: bỏ qua chữ hoa/thường và khoảng trắng thừa (giữ dấu). */
    public static function khoaTen(string $ten): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $ten) ?? $ten));
    }

    /** Số nhỏ nhất (≥ 1) chưa có trong danh sách số đã dùng. */
    public static function soTrongTiepTheo(array $daDung): int
    {
        $so = 1;
        while (isset($daDung[$so])) {
            $so++;
        }
        return $so;
    }

    /** Số lớn nhất + 1 (để thêm bạn mới vào cuối danh sách). */
    public static function soKeTiep(array $danhSach): int
    {
        $lonNhat = 0;
        foreach ($danhSach as $hs) {
            $lonNhat = max($lonNhat, $hs['so']);
        }
        return $lonNhat + 1;
    }

    // ------------------------------------------------------------------
    // GHI FILE
    // ------------------------------------------------------------------

    public static function sapXep(array $danhSach): array
    {
        $kemViTri = [];
        foreach (array_values($danhSach) as $i => $hs) {
            $kemViTri[] = [$hs['so'], $i, $hs];
        }
        usort($kemViTri, static fn($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
        return array_map(static fn($x) => $x[2], $kemViTri);
    }

    public function taoNoiDungFile(array $danhSach): string
    {
        $dong = [];
        foreach (self::sapXep($danhSach) as $hs) {
            $dong[] = '  { so: ' . $hs['so'] . ', ten: ' . json_encode($hs['ten'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                . (!empty($hs['vang']) ? ', vang: true' : '') . ' }';
        }
        return self::DAU_FILE . 'var students = [' . ($dong ? "\n" . implode(",\n", $dong) : '') . "\n];\n";
    }

    private const DAU_FILE = <<<'TXT'
// =============================================================
// DANH SÁCH HỌC SINH
// -------------------------------------------------------------
// Cách dễ nhất để sửa: mở trang quản lý "dashboard" → "Học sinh"
// (thêm, sửa, xóa, đánh dấu vắng, nhập từ Excel). Vẫn có thể sửa tay
// file này bằng Notepad.
//
// Mỗi dòng là một bạn:  { so: số thứ tự, ten: "Họ và tên" },
//  - "so"  là số in trên quả bóng.
//  - "ten" là họ tên, nhớ đặt trong dấu ngoặc kép " ".
//  - Giữa các dòng phải có dấu phẩy ,
//  - Bạn nào vắng hôm nay thì thêm  vang: true  (không có bóng trong lồng):
//      { so: 3, ten: "Lê Hoàng Cường", vang: true },
// Có bao nhiêu bạn (không vắng) thì trong lồng có bấy nhiêu quả bóng.
// =============================================================


TXT;
}
