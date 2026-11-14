<?php
declare(strict_types=1);

namespace Dashboard;

use RuntimeException;
use Shuchkin\SimpleXLSXGen;

/**
 * Nhập danh sách học sinh từ file (Excel .xlsx, CSV, JSON, students.js, .txt)
 * hoặc từ chữ dán vào, và xuất ra Excel.
 *
 * Bảng Excel / CSV dùng các cột (có dấu hay không dấu đều được):
 *   Số | Họ và tên | Vắng
 * Không có dòng tiêu đề thì hiểu: 1 cột = họ tên; 2 cột trở lên = số, họ tên.
 */
final class NhapXuatHocSinh
{
    public const TIEU_DE_COT = ['Số', 'Họ và tên', 'Vắng'];

    /** Trả về ['hocSinh' => [['viTri' => 'Dòng 3', 'hs' => [...]], ...], 'loi' => [...]] */
    public static function docFile(string $duongDan, string $tenGoc): array
    {
        $duoi = strtolower(pathinfo($tenGoc, PATHINFO_EXTENSION));
        switch ($duoi) {
            case 'xlsx':
                return self::docBang(NhapXuat::docXlsx($duongDan, 'hoc sinh'));
            case 'csv':
                return self::docBang(NhapXuat::docCsv($duongDan));
            case 'txt':
                return self::docVanBan((string) file_get_contents($duongDan));
            case 'json':
            case 'js':
                return self::docJson((string) file_get_contents($duongDan));
            case 'xls':
                throw new RuntimeException('File .xls (Excel đời cũ) chưa được hỗ trợ. Trong Excel chọn "Lưu thành" → "Excel Workbook (.xlsx)" rồi nhập lại.');
            default:
                throw new RuntimeException('Chỉ nhận file .xlsx, .csv, .txt, .json hoặc students.js.');
        }
    }

    /**
     * Chữ dán vào: mỗi dòng một bạn. Ví dụ các kiểu đều được:
     *   Nguyễn Văn An
     *   1. Nguyễn Văn An
     *   1<Tab>Nguyễn Văn An      (dán từ Excel)
     */
    public static function docVanBan(string $chu): array
    {
        $chu = preg_replace('/^\xEF\xBB\xBF/', '', $chu) ?? $chu;
        if (!mb_check_encoding($chu, 'UTF-8')) {
            throw new RuntimeException('Chữ / file không phải mã UTF-8 nên sẽ bị lỗi dấu tiếng Việt. Hãy lưu file với mã UTF-8 rồi thử lại.');
        }
        $ketQua = ['hocSinh' => [], 'loi' => []];
        foreach (preg_split('/\R/u', $chu) ?: [] as $i => $dong) {
            $dong = trim($dong);
            if ($dong === '') {
                continue;
            }
            $cot = array_values(array_filter(array_map('trim', explode("\t", $dong)), static fn($x) => $x !== ''));
            if ($i === 0 && self::laDongTieuDe($cot)) {
                continue;                                       // dòng tiêu đề dán từ Excel
            }
            if (count($cot) >= 2 && KhoHocSinh::chuanHoaSo($cot[0]) > 0) {
                [$so, $ten] = [$cot[0], $cot[1]];
            } elseif (preg_match('/^(\d{1,4})\s*[.):,;\-–]?\s+(.+)$/u', $dong, $m)) {
                [$so, $ten] = [$m[1], $m[2]];
            } else {
                [$so, $ten] = ['', $dong];
            }
            $ketQua['hocSinh'][] = ['viTri' => 'Dòng ' . ($i + 1), 'hs' => KhoHocSinh::chuanHoa(['so' => $so, 'ten' => $ten])];
        }
        return $ketQua;
    }

    private static function docJson(string $noiDung): array
    {
        $noiDung = preg_replace('/^\xEF\xBB\xBF/', '', $noiDung) ?? $noiDung;
        $mang = json_decode($noiDung, true);
        if (is_array($mang) && isset($mang['students']) && is_array($mang['students'])) {
            $mang = $mang['students'];
        }
        if (!is_array($mang)) {
            $mang = KhoHocSinh::phanTichNoiDungJs($noiDung, 'students');
        }
        $ketQua = ['hocSinh' => [], 'loi' => []];
        foreach (array_values($mang) as $i => $muc) {
            $ketQua['hocSinh'][] = [
                'viTri' => 'Bạn thứ ' . ($i + 1),
                'hs' => KhoHocSinh::chuanHoa(is_array($muc) ? $muc : ['ten' => (string) $muc]),
            ];
        }
        return $ketQua;
    }

    /** Đổi tên cột thành tên trường: 'so', 'ten', 'vang' hoặc null. */
    private static function nhanDienCot(string $ten): ?string
    {
        $t = trim(preg_replace('/[^a-z0-9]+/', ' ', ChuViet::boDau($ten)) ?? '');
        if (in_array($t, ['so', 'stt', 'so thu tu', 'so bong', 'ma so', 'no', 'number'], true)) {
            return 'so';
        }
        if (in_array($t, ['ten', 'ho ten', 'ho va ten', 'hoc sinh', 'ten hoc sinh', 'name', 'full name'], true)) {
            return 'ten';
        }
        if (in_array($t, ['vang', 'vang mat', 'trang thai', 'absent'], true)) {
            return 'vang';
        }
        return null;
    }

    private static function laDongTieuDe(array $dong): bool
    {
        $truong = array_filter(array_map(static fn($o) => self::nhanDienCot((string) $o), $dong));
        return in_array('ten', $truong, true);
    }

    private static function docBang(array $cacDong): array
    {
        // Tìm dòng tiêu đề (trong 10 dòng đầu) – dòng có cột "Họ tên"
        $viTriTieuDe = -1;
        $banDo = null;
        foreach (array_slice($cacDong, 0, 10, true) as $i => $dong) {
            $bd = [];
            foreach ((array) $dong as $cot => $giaTri) {
                $truong = self::nhanDienCot((string) $giaTri);
                if ($truong !== null && !isset($bd[$truong])) {
                    $bd[$truong] = $cot;
                }
            }
            if (isset($bd['ten'])) {
                $viTriTieuDe = $i;
                $banDo = $bd;
                break;
            }
        }

        $ketQua = ['hocSinh' => [], 'loi' => []];
        foreach ($cacDong as $i => $dong) {
            if ($i <= $viTriTieuDe) {
                continue;
            }
            $dong = array_map(static fn($o) => trim((string) $o), array_values((array) $dong));
            if (implode('', $dong) === '') {
                continue;
            }
            if ($banDo !== null) {
                $lay = static fn(string $truong): string => isset($banDo[$truong]) ? trim((string) (((array) $cacDong[$i])[$banDo[$truong]] ?? '')) : '';
                $hs = ['so' => $lay('so'), 'ten' => $lay('ten'), 'vang' => $lay('vang')];
            } else {
                // Không có tiêu đề: bỏ các ô trống, 1 ô = tên; ô đầu là số thì ô sau là tên
                $o = array_values(array_filter($dong, static fn($x) => $x !== ''));
                $hs = count($o) >= 2 && KhoHocSinh::chuanHoaSo($o[0]) > 0
                    ? ['so' => $o[0], 'ten' => $o[1]]
                    : ['so' => '', 'ten' => $o[0]];
            }
            $ketQua['hocSinh'][] = ['viTri' => 'Dòng ' . ($i + 1), 'hs' => KhoHocSinh::chuanHoa($hs)];
        }
        return $ketQua;
    }

    // ------------------------------------------------------------------
    // XUẤT
    // ------------------------------------------------------------------

    /** Tạo file Excel. $chiLaMau = true: file mẫu có vài dòng ví dụ. */
    public static function taoExcel(array $danhSach, bool $chiLaMau = false): SimpleXLSXGen
    {
        NhapXuat::canZip();
        $bang = [array_map(
            static fn($t) => '<style bgcolor="#6B3FE0" color="#FFFFFF"><b><middle>' . htmlspecialchars($t, ENT_XML1) . '</middle></b></style>',
            self::TIEU_DE_COT
        )];
        if ($chiLaMau) {
            $danhSach = [
                ['so' => 1, 'ten' => 'Nguyễn Văn An', 'vang' => false],
                ['so' => 2, 'ten' => 'Trần Thị Bình', 'vang' => false],
                ['so' => 3, 'ten' => 'Lê Hoàng Cường', 'vang' => true],
            ];
        }
        foreach (KhoHocSinh::sapXep($danhSach) as $hs) {
            $bang[] = [$hs['so'], SimpleXLSXGen::raw($hs['ten']), $hs['vang'] ? 'Vắng' : ''];
        }
        $xlsx = SimpleXLSXGen::fromArray($bang, 'Học sinh')
            ->setDefaultFont('Arial')
            ->setDefaultFontSize(11)
            ->setTitle('Danh sách học sinh – Lồng Quay May Mắn')
            ->freezePanes('A2')
            ->setColWidth('A', 8)
            ->setColWidth('B', 36)
            ->setColWidth('C', 10);
        $xlsx->addSheet([
            ['<b><style font-size="14">HƯỚNG DẪN ĐIỀN DANH SÁCH HỌC SINH</style></b>'],
            [''],
            ['Mỗi dòng ở trang "Học sinh" là một bạn. Giữ nguyên dòng tiêu đề đầu tiên.'],
            ['Cột "Số": số in trên quả bóng (1–' . KhoHocSinh::SO_TOI_DA . ', không trùng). Để trống thì máy tự đánh số tiếp theo.'],
            ['Cột "Họ và tên": bắt buộc.'],
            ['Cột "Vắng": ghi "Vắng" (hoặc "x") nếu bạn đó vắng – sẽ không có bóng trong lồng. Để trống = có mặt.'],
        ], 'Hướng dẫn')->setColWidth('A', 100);
        return $xlsx;
    }
}
