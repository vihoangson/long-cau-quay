<?php
declare(strict_types=1);

namespace Dashboard;

use RuntimeException;
use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLSXGen;

/**
 * Nhập câu hỏi từ file (Excel .xlsx, CSV, JSON, questions.js) và xuất ra file.
 *
 * Bảng Excel / CSV dùng các cột (tên cột có dấu hay không dấu đều được):
 *   Môn | Loại | Câu hỏi | Đáp án (tự luận) | A | B | C | D | Đáp án đúng (A-D) | Trạng thái
 */
final class NhapXuat
{
    public const TIEU_DE_COT = ['Môn', 'Loại', 'Câu hỏi', 'Đáp án (tự luận)', 'A', 'B', 'C', 'D', 'Đáp án đúng (A-D)', 'Trạng thái'];
    private const DO_RONG_COT = [14, 13, 60, 36, 22, 22, 22, 22, 12, 13];

    // ------------------------------------------------------------------
    // NHẬP
    // ------------------------------------------------------------------

    /**
     * Đọc file tải lên.
     * Trả về: ['cauHoi' => [['viTri' => 'Dòng 5', 'cau' => câu đã chuẩn hóa], ...], 'loi' => ['Dòng 7: ...', ...]]
     */
    public static function docFile(string $duongDan, string $tenGoc): array
    {
        $duoi = strtolower(pathinfo($tenGoc, PATHINFO_EXTENSION));
        switch ($duoi) {
            case 'xlsx':
                return self::docBang(self::docXlsx($duongDan));
            case 'csv':
                return self::docBang(self::docCsv($duongDan));
            case 'json':
            case 'js':
            case 'txt':
                return self::docJson($duongDan);
            case 'xls':
                throw new RuntimeException('File .xls (Excel đời cũ) chưa được hỗ trợ. Trong Excel chọn "Lưu thành" → "Excel Workbook (.xlsx)" rồi nhập lại.');
            default:
                throw new RuntimeException('Chỉ nhận file .xlsx, .csv, .json hoặc questions.js.');
        }
    }

    /** File Excel (.xlsx) thực chất là file nén zip, nên PHP cần bật extension "zip". */
    public static function canZip(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            throw new RuntimeException('PHP đang chạy trang này chưa bật extension "zip" nên chưa đọc/ghi được file Excel. '
                . 'Mở file php.ini (' . (php_ini_loaded_file() ?: 'php.ini') . '), bỏ dấu ; trước dòng "extension=zip" rồi khởi động lại server. '
                . 'Trong lúc chờ, có thể dùng CSV hoặc JSON.');
        }
    }

    /** Đọc các dòng của file Excel. Ưu tiên trang tính có tên chứa $tenTrang (không dấu), không có thì lấy trang đầu. */
    public static function docXlsx(string $duongDan, string $tenTrang = 'cau hoi'): array
    {
        self::canZip();
        $xlsx = SimpleXLSX::parse($duongDan);
        if (!$xlsx) {
            throw new RuntimeException('Không đọc được file Excel: ' . SimpleXLSX::parseError());
        }
        $chiSo = 0;
        foreach ($xlsx->sheetNames() as $i => $ten) {
            if (str_contains(ChuViet::boDau((string) $ten), $tenTrang)) {
                $chiSo = (int) $i;
                break;
            }
        }
        return $xlsx->rows($chiSo) ?: [];
    }

    public static function docCsv(string $duongDan): array
    {
        $noiDung = (string) file_get_contents($duongDan);
        $noiDung = preg_replace('/^\xEF\xBB\xBF/', '', $noiDung) ?? $noiDung;
        if (!mb_check_encoding($noiDung, 'UTF-8')) {
            throw new RuntimeException('File CSV không phải mã UTF-8 nên sẽ bị lỗi dấu tiếng Việt. Trong Excel chọn "Lưu thành" → "CSV UTF-8 (Comma delimited)" rồi nhập lại, hoặc nhập thẳng file .xlsx.');
        }
        // Đoán dấu ngăn cách cột từ dòng đầu tiên: dấu phẩy, chấm phẩy hoặc tab
        $dongDau = strtok($noiDung, "\n") ?: '';
        $dauNgan = ',';
        $nhieuNhat = -1;
        foreach ([',', ';', "\t"] as $d) {
            if (substr_count($dongDau, $d) > $nhieuNhat) {
                $nhieuNhat = substr_count($dongDau, $d);
                $dauNgan = $d;
            }
        }
        $luong = fopen('php://temp', 'r+');
        fwrite($luong, $noiDung);
        rewind($luong);
        $cacDong = [];
        while (($dong = fgetcsv($luong, 0, $dauNgan)) !== false) {
            $cacDong[] = $dong;
        }
        fclose($luong);
        return $cacDong;
    }

    /** Đổi tên cột (có dấu / không dấu / tiếng Anh) thành tên trường trong chương trình. */
    private static function nhanDienCot(string $ten): ?string
    {
        $t = trim(preg_replace('/[^a-z0-9]+/', ' ', ChuViet::boDau($ten)) ?? '');
        if ($t === '') {
            return null;
        }
        if (in_array($t, ['mon', 'mon hoc', 'subject'], true)) {
            return 'mon';
        }
        if (in_array($t, ['trang thai', 'tinh trang', 'status'], true)) {
            return 'trangThai';
        }
        if (str_starts_with($t, 'loai') || in_array($t, ['kieu', 'kieu cau hoi', 'type'], true)) {
            return 'loai';
        }
        if (str_starts_with($t, 'dap an dung') || in_array($t, ['correct', 'dap an trac nghiem'], true)) {
            return 'dapAnDung';
        }
        if (preg_match('/^(?:phuong an |lua chon |dap an |option )?([abcd])$/', $t, $m)) {
            return strtoupper($m[1]);
        }
        if (str_starts_with($t, 'dap an') || in_array($t, ['answer', 'goi y'], true)) {
            return 'dapAn';
        }
        if (str_starts_with($t, 'cau hoi') || in_array($t, ['noi dung', 'noi dung cau hoi', 'question'], true)) {
            return 'cauHoi';
        }
        return null;
    }

    private static function docBang(array $cacDong): array
    {
        // Tìm dòng tiêu đề (trong 10 dòng đầu) – dòng có cột "Câu hỏi"
        $viTriTieuDe = null;
        $banDoCot = [];
        foreach (array_slice($cacDong, 0, 10, true) as $i => $dong) {
            $banDo = [];
            foreach ((array) $dong as $cot => $giaTri) {
                $truong = self::nhanDienCot((string) $giaTri);
                if ($truong !== null && !isset($banDo[$truong])) {
                    $banDo[$truong] = $cot;
                }
            }
            if (isset($banDo['cauHoi'])) {
                $viTriTieuDe = $i;
                $banDoCot = $banDo;
                break;
            }
        }
        if ($viTriTieuDe === null) {
            throw new RuntimeException('Không tìm thấy dòng tiêu đề có cột "Câu hỏi". Hãy dùng file mẫu (nút "Tải file Excel mẫu").');
        }

        $ketQua = ['cauHoi' => [], 'loi' => []];
        $lay = static fn(array $dong, string $truong): string =>
            isset($banDoCot[$truong]) ? trim((string) ($dong[$banDoCot[$truong]] ?? '')) : '';

        foreach ($cacDong as $i => $dong) {
            if ($i <= $viTriTieuDe) {
                continue;
            }
            $dong = (array) $dong;
            if (trim(implode('', array_map('strval', $dong))) === '') {
                continue;                                     // dòng trống
            }
            $viTri = 'Dòng ' . ($i + 1);
            $luaChon = [$lay($dong, 'A'), $lay($dong, 'B'), $lay($dong, 'C'), $lay($dong, 'D')];
            $coLuaChon = implode('', $luaChon) !== '';
            $loai = ChuViet::boDau($lay($dong, 'loai'));

            if (str_contains($loai, 'trac') && !$coLuaChon) {
                $ketQua['loi'][] = $viTri . ': loại là Trắc nghiệm nhưng chưa điền phương án A, B, C, D.';
                continue;
            }
            $cau = KhoCauHoi::chuanHoa([
                'mon' => $lay($dong, 'mon'),
                'cauHoi' => $lay($dong, 'cauHoi'),
                'dapAn' => $coLuaChon ? '' : $lay($dong, 'dapAn'),
                'luaChon' => $coLuaChon ? $luaChon : [],
                'dapAnDung' => $lay($dong, 'dapAnDung'),
                'tamTat' => $lay($dong, 'trangThai'),
            ]);
            $ketQua['cauHoi'][] = ['viTri' => $viTri, 'cau' => $cau];
        }
        return $ketQua;
    }

    private static function docJson(string $duongDan): array
    {
        $noiDung = (string) file_get_contents($duongDan);
        $noiDung = preg_replace('/^\xEF\xBB\xBF/', '', $noiDung) ?? $noiDung;
        $mang = json_decode($noiDung, true);
        if (is_array($mang) && isset($mang['questions']) && is_array($mang['questions'])) {
            $mang = $mang['questions'];
        }
        if (!is_array($mang)) {
            $mang = KhoCauHoi::phanTichNoiDungJs($noiDung);    // file questions.js hoặc JSON viết tay
        }
        $ketQua = ['cauHoi' => [], 'loi' => []];
        foreach (array_values($mang) as $i => $muc) {
            $ketQua['cauHoi'][] = [
                'viTri' => 'Câu thứ ' . ($i + 1),
                'cau' => KhoCauHoi::chuanHoa(is_array($muc) ? $muc : ['cauHoi' => (string) $muc]),
            ];
        }
        return $ketQua;
    }

    // ------------------------------------------------------------------
    // XUẤT
    // ------------------------------------------------------------------

    /** JSON gọn gàng, cùng định dạng với questions.js. */
    public static function xuatJson(array $danhSach): string
    {
        $ra = [];
        foreach (KhoCauHoi::sapXep($danhSach) as $c) {
            $muc = [];
            if ($c['mon'] !== '') {
                $muc['mon'] = $c['mon'];
            }
            $muc['cauHoi'] = $c['cauHoi'];
            if (KhoCauHoi::laTracNghiem($c)) {
                $muc['luaChon'] = $c['luaChon'];
                $muc['dapAnDung'] = $c['dapAnDung'];
            } else {
                $muc['dapAn'] = $c['dapAn'];
            }
            if ($c['tamTat']) {
                $muc['tamTat'] = true;
            }
            $ra[] = $muc;
        }
        return (string) json_encode($ra, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Một dòng dữ liệu trong bảng Excel (mọi ô đều ghi dạng chữ nguyên văn). */
    private static function dongBang(array $c): array
    {
        $tn = KhoCauHoi::laTracNghiem($c);
        $o = static fn(string $v) => $v === '' ? '' : SimpleXLSXGen::raw($v);
        $dong = [$o($c['mon']), $tn ? 'Trắc nghiệm' : 'Tự luận', $o($c['cauHoi']), $o($tn ? '' : $c['dapAn'])];
        for ($i = 0; $i < 4; $i++) {
            $dong[] = $o($tn ? ($c['luaChon'][$i] ?? '') : '');
        }
        $dong[] = $tn ? $c['dapAnDung'] : '';
        $dong[] = $c['tamTat'] ? 'Tạm tắt' : 'Đang dùng';
        return $dong;
    }

    /** Tạo file Excel. $chiLaMau = true: file mẫu trống, chỉ có 2 dòng ví dụ. */
    public static function taoExcel(array $danhSach, bool $chiLaMau = false): SimpleXLSXGen
    {
        self::canZip();
        $tieuDe = array_map(
            static fn($t) => '<style bgcolor="#6B3FE0" color="#FFFFFF"><b><middle>' . htmlspecialchars($t, ENT_XML1) . '</middle></b></style>',
            self::TIEU_DE_COT
        );
        $bang = [$tieuDe];
        if ($chiLaMau) {
            $bang[] = self::dongBang(KhoCauHoi::chuanHoa(['mon' => 'Toán', 'cauHoi' => '25% của 80 là bao nhiêu?', 'dapAn' => '20']));
            $bang[] = self::dongBang(KhoCauHoi::chuanHoa([
                'mon' => 'Tiếng Việt', 'cauHoi' => 'Từ nào đồng nghĩa với "hạnh phúc"?',
                'luaChon' => ['Sung sướng', 'Đau khổ', 'Buồn bã', 'Lo lắng'], 'dapAnDung' => 'A',
            ]));
        } else {
            foreach (KhoCauHoi::sapXep($danhSach) as $c) {
                $bang[] = self::dongBang($c);
            }
        }

        $huongDan = [
            ['<b><style font-size="14">HƯỚNG DẪN ĐIỀN FILE CÂU HỎI</style></b>'],
            [''],
            ['Mỗi dòng ở trang "Câu hỏi" là một câu hỏi. Giữ nguyên dòng tiêu đề đầu tiên.'],
            [''],
            ['<b>Câu TỰ LUẬN</b>: điền cột "Câu hỏi" và "Đáp án (tự luận)". Để trống các cột A, B, C, D.'],
            ['<b>Câu TRẮC NGHIỆM</b>: điền "Câu hỏi", các phương án vào cột A, B, C, D (ít nhất A và B),'],
            ['    và chữ cái của đáp án đúng vào cột "Đáp án đúng (A-D)".'],
            ['Cột "Loại" ghi "Tự luận" hoặc "Trắc nghiệm". Nếu để trống, máy tự hiểu: có phương án A–D là trắc nghiệm.'],
            ['Cột "Môn" không bắt buộc (ví dụ: Toán, Tiếng Việt, Khoa học).'],
            ['Cột "Trạng thái": ghi "Tạm tắt" nếu muốn giữ câu nhưng chưa dùng khi quay. Để trống = Đang dùng.'],
            [''],
            ['Khi nhập: câu trùng với câu đã có sẽ được bỏ qua (nếu chọn "Bỏ qua câu trùng").'],
            ['Dòng nào bị lỗi sẽ được báo lại kèm số dòng, các dòng đúng vẫn được nhập.'],
        ];

        // Lưu ý: các lệnh định dạng áp dụng cho trang tính vừa thêm gần nhất,
        // nên định dạng trang "Câu hỏi" xong rồi mới thêm trang "Hướng dẫn".
        $xlsx = SimpleXLSXGen::fromArray($bang, 'Câu hỏi')
            ->setDefaultFont('Arial')
            ->setDefaultFontSize(11)
            ->setTitle('Ngân hàng câu hỏi – Lồng Quay May Mắn')
            ->freezePanes('A2')
            ->autoFilter('A1:J' . count($bang));
        foreach (self::DO_RONG_COT as $i => $rong) {
            $xlsx->setColWidth(chr(65 + $i), $rong);
        }
        $xlsx->addSheet($huongDan, 'Hướng dẫn')->setColWidth('A', 110);
        return $xlsx;
    }
}
