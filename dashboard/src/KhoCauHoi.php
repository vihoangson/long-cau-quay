<?php
declare(strict_types=1);

namespace Dashboard;

use RuntimeException;

/**
 * Đọc và ghi ngân hàng câu hỏi trong file long-quay/questions.js.
 *
 * File questions.js là "nguồn dữ liệu thật" mà trang quay số (chạy offline) đọc vào,
 * nên trang quản lý đọc/ghi thẳng vào file này. Mỗi lần ghi đều sao lưu bản cũ.
 *
 * Mỗi câu hỏi trong chương trình có dạng thống nhất:
 *   [
 *     'mon'       => 'Toán',             // môn học, có thể rỗng
 *     'cauHoi'    => 'Nội dung câu hỏi',
 *     'dapAn'     => 'Đáp án tự luận',    // chỉ dùng cho câu tự luận
 *     'luaChon'   => ['A', 'B', 'C', 'D'],// rỗng = câu tự luận
 *     'dapAnDung' => 'B',                // chữ cái đáp án đúng (câu trắc nghiệm)
 *   ]
 */
final class KhoCauHoi
{
    public const CHU_CAI = ['A', 'B', 'C', 'D'];
    public const DO_DAI_TOI_DA = 600;
    private const LOI_PHIEN_BAN = 'Danh sách câu hỏi vừa được thay đổi ở nơi khác (tab khác hoặc sửa tay file). Trang đã tải lại dữ liệu mới nhất, thầy cô hãy làm lại thao tác.';

    /** Thứ tự các môn khi sắp xếp trong file (môn khác xếp sau, theo thứ tự xuất hiện). */
    private const THU_TU_MON = ['', 'Toán', 'Tiếng Việt', 'Khoa học'];

    public function __construct(
        private string $duongDanFile,
        private string $thuMucSaoLuu,
        private int $soBanSaoLuuGiuLai = 30
    ) {
    }

    // ------------------------------------------------------------------
    // ĐỌC
    // ------------------------------------------------------------------

    /**
     * Đọc file và trả về ['cauHoi' => danh sách câu hỏi, 'phienBan' => mã phiên bản].
     * "phienBan" dùng để phát hiện file bị thay đổi giữa lúc mở trang và lúc lưu.
     */
    public function doc(): array
    {
        if (!is_file($this->duongDanFile)) {
            return ['cauHoi' => [], 'phienBan' => 'chua-co-file'];
        }
        $noiDung = (string) file_get_contents($this->duongDanFile);
        $danhSach = [];
        foreach (self::phanTichNoiDungJs($noiDung) as $muc) {
            $danhSach[] = self::chuanHoa(is_array($muc) ? $muc : ['cauHoi' => (string) $muc]);
        }
        return ['cauHoi' => $danhSach, 'phienBan' => md5($noiDung)];
    }

    /** Báo lỗi nếu file đã bị thay đổi kể từ lúc mở trang. */
    public function kiemTraPhienBan(string $phienBanCu): void
    {
        if ($phienBanCu !== $this->phienBanHienTai()) {
            throw new RuntimeException(self::LOI_PHIEN_BAN);
        }
    }

    public function phienBanHienTai(): string
    {
        return is_file($this->duongDanFile) ? md5((string) file_get_contents($this->duongDanFile)) : 'chua-co-file';
    }

    /**
     * Lấy mảng câu hỏi từ nội dung questions.js (hoặc từ một chuỗi JSON).
     * Chấp nhận cả kiểu viết tay của thầy cô: có chú thích //, dấu phẩy thừa ở cuối,
     * chuỗi dùng nháy đơn '...', tên thuộc tính không có ngoặc kép.
     */
    public static function phanTichNoiDungJs(string $noiDung): array
    {
        $noiDung = preg_replace('/^\xEF\xBB\xBF/', '', $noiDung) ?? $noiDung;   // bỏ BOM
        $sach = self::boChuThich($noiDung);

        if (preg_match('/\bquestions\s*=\s*\[/', $sach, $m, PREG_OFFSET_CAPTURE)) {
            $batDau = $m[0][1] + strlen($m[0][0]) - 1;           // vị trí dấu [
        } else {
            $batDau = strpos($sach, '[');                         // file JSON thuần
            if ($batDau === false) {
                throw new RuntimeException('Không tìm thấy danh sách câu hỏi (thiếu "var questions = [ ... ]").');
            }
        }

        $json = self::sangJson($sach, $batDau);
        $mang = json_decode($json, true);
        if (!is_array($mang)) {
            throw new RuntimeException('Danh sách câu hỏi bị sai cú pháp: ' . json_last_error_msg()
                . '. Hãy kiểm tra dấu ngoặc kép, dấu phẩy, dấu ngoặc.');
        }
        return array_values($mang);
    }

    /** Bỏ chú thích // và /* *\/ nhưng giữ nguyên nội dung trong chuỗi. */
    private static function boChuThich(string $s): string
    {
        $ra = '';
        $n = strlen($s);
        $nhay = null;
        for ($i = 0; $i < $n; $i++) {
            $c = $s[$i];
            if ($nhay !== null) {
                $ra .= $c;
                if ($c === '\\' && $i + 1 < $n) {
                    $ra .= $s[++$i];
                } elseif ($c === $nhay) {
                    $nhay = null;
                }
                continue;
            }
            if ($c === '"' || $c === "'") {
                $nhay = $c;
                $ra .= $c;
            } elseif ($c === '/' && ($s[$i + 1] ?? '') === '/') {
                while ($i < $n && $s[$i] !== "\n") {
                    $i++;
                }
                $ra .= "\n";
            } elseif ($c === '/' && ($s[$i + 1] ?? '') === '*') {
                $ket = strpos($s, '*/', $i + 2);
                $i = $ket === false ? $n : $ket + 1;
                $ra .= ' ';
            } else {
                $ra .= $c;
            }
        }
        return $ra;
    }

    /** Chép từ dấu [ tới dấu ] tương ứng, đồng thời sửa cú pháp JS cho thành JSON hợp lệ. */
    private static function sangJson(string $s, int $i): string
    {
        $ra = '';
        $n = strlen($s);
        $sau = 0;
        while ($i < $n) {
            $c = $s[$i];
            if ($c === '"') {                                    // chuỗi nháy kép
                $chuoi = '"';
                for ($j = $i + 1; $j < $n; $j++) {
                    $k = $s[$j];
                    if ($k === '\\') {
                        $tiep = $s[$j + 1] ?? '';
                        $chuoi .= $tiep === "'" ? "'" : '\\' . $tiep;
                        $j++;
                    } elseif ($k === '"') {
                        break;
                    } elseif ($k === "\n") {
                        $chuoi .= '\n';
                    } elseif ($k === "\r") {
                        // bỏ
                    } elseif ($k === "\t") {
                        $chuoi .= '\t';
                    } else {
                        $chuoi .= $k;
                    }
                }
                $ra .= $chuoi . '"';
                $i = $j + 1;
                continue;
            }
            if ($c === "'") {                                    // chuỗi nháy đơn → nháy kép
                $tho = '';
                for ($j = $i + 1; $j < $n; $j++) {
                    $k = $s[$j];
                    if ($k === '\\') {
                        $tiep = $s[$j + 1] ?? '';
                        $tho .= $tiep === 'n' ? "\n" : ($tiep === 't' ? "\t" : $tiep);
                        $j++;
                    } elseif ($k === "'") {
                        break;
                    } else {
                        $tho .= $k;
                    }
                }
                $ra .= json_encode($tho, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $i = $j + 1;
                continue;
            }
            if ($c === '[' || $c === '{') {
                $sau++;
                $ra .= $c;
                $i++;
                continue;
            }
            if ($c === ']' || $c === '}') {
                $ra = rtrim($ra);
                if (substr($ra, -1) === ',') {                   // dấu phẩy thừa
                    $ra = substr($ra, 0, -1);
                }
                $ra .= $c;
                $i++;
                if (--$sau === 0) {
                    return $ra;
                }
                continue;
            }
            if (ctype_alpha($c) || $c === '_' || $c === '$') {   // tên thuộc tính không có ngoặc kép
                $j = $i;
                while ($j < $n && (ctype_alnum($s[$j]) || $s[$j] === '_' || $s[$j] === '$')) {
                    $j++;
                }
                $tu = substr($s, $i, $j - $i);
                $k = $j;
                while ($k < $n && ctype_space($s[$k])) {
                    $k++;
                }
                $ra .= ($k < $n && $s[$k] === ':') ? '"' . $tu . '"' : $tu;
                $i = $j;
                continue;
            }
            $ra .= $c;
            $i++;
        }
        throw new RuntimeException('Danh sách câu hỏi thiếu dấu "]" ở cuối.');
    }

    // ------------------------------------------------------------------
    // CHUẨN HÓA VÀ KIỂM TRA
    // ------------------------------------------------------------------

    /** Đưa một câu hỏi (từ file, từ form, từ file nhập) về dạng thống nhất. */
    public static function chuanHoa(array $c): array
    {
        $chu = static function ($v): string {
            if (is_array($v) || is_object($v)) {
                return '';
            }
            $v = trim(preg_replace('/\s+/u', ' ', (string) $v) ?? '');
            return $v;
        };

        $luaChon = $c['luaChon'] ?? $c['options'] ?? [];
        $luaChon = is_array($luaChon) ? array_values($luaChon) : [];
        $luaChon = array_map($chu, $luaChon);
        // Bỏ các phương án trống ở cuối (A, B rồi để trống C, D vẫn hợp lệ)
        while ($luaChon && end($luaChon) === '') {
            array_pop($luaChon);
        }
        $luaChon = array_slice($luaChon, 0, 4);

        return [
            'mon' => $chu($c['mon'] ?? $c['subject'] ?? ''),
            'cauHoi' => $chu($c['cauHoi'] ?? $c['question'] ?? ''),
            'dapAn' => $chu($c['dapAn'] ?? $c['answer'] ?? ''),
            'luaChon' => $luaChon,
            'dapAnDung' => self::chuCaiDapAn($c['dapAnDung'] ?? $c['correct'] ?? ''),
            // Câu tạm tắt: vẫn giữ trong ngân hàng nhưng trang quay số không bốc tới
            'tamTat' => self::laGiaTriDung($c['tamTat'] ?? $c['disabled'] ?? false),
        ];
    }

    /** true, 1, "1", "true", "có", "tạm tắt"… → true */
    public static function laGiaTriDung($v): bool
    {
        if (is_bool($v)) {
            return $v;
        }
        if (is_int($v) || is_float($v)) {
            return $v != 0;
        }
        $t = trim(ChuViet::boDau((string) $v));
        return in_array($t, ['1', 'true', 'yes', 'x', 'co', 'tat', 'tam tat', 'da tat', 'an', 'disabled'], true);
    }

    /** "b", "B", 2, "2" → "B". Không hợp lệ → "". */
    public static function chuCaiDapAn($giaTri): string
    {
        $v = strtoupper(trim((string) $giaTri));
        if (preg_match('/^[A-D]$/', $v)) {
            return $v;
        }
        if (preg_match('/^[1-4]$/', $v)) {
            return self::CHU_CAI[(int) $v - 1];
        }
        return '';
    }

    public static function laTracNghiem(array $c): bool
    {
        return count($c['luaChon']) > 0;
    }

    /** Trả về danh sách lỗi (rỗng = hợp lệ). */
    public static function kiemTra(array $c): array
    {
        $loi = [];
        if ($c['cauHoi'] === '') {
            $loi[] = 'Chưa nhập nội dung câu hỏi.';
        } elseif (mb_strlen($c['cauHoi']) > self::DO_DAI_TOI_DA) {
            $loi[] = 'Câu hỏi quá dài (tối đa ' . self::DO_DAI_TOI_DA . ' ký tự).';
        }
        if (mb_strlen($c['mon']) > 40) {
            $loi[] = 'Tên môn quá dài (tối đa 40 ký tự).';
        }
        if (self::laTracNghiem($c)) {
            $soLuaChon = count($c['luaChon']);
            if ($soLuaChon < 2) {
                $loi[] = 'Câu trắc nghiệm cần ít nhất 2 phương án (A và B).';
            }
            foreach ($c['luaChon'] as $i => $lc) {
                if ($lc === '') {
                    $loi[] = 'Phương án ' . self::CHU_CAI[$i] . ' đang để trống.';
                }
            }
            $khongTrong = array_filter($c['luaChon'], static fn($x) => $x !== '');
            $chuanHoaSoSanh = array_map(static fn($x) => mb_strtolower($x), $khongTrong);
            if (count(array_unique($chuanHoaSoSanh)) < count($chuanHoaSoSanh)) {
                $loi[] = 'Có hai phương án giống nhau.';
            }
            if ($c['dapAnDung'] === '') {
                $loi[] = 'Chưa chọn đáp án đúng (A, B, C hoặc D).';
            } elseif (array_search($c['dapAnDung'], self::CHU_CAI, true) >= $soLuaChon) {
                $loi[] = 'Đáp án đúng là ' . $c['dapAnDung'] . ' nhưng phương án ' . $c['dapAnDung'] . ' chưa có nội dung.';
            }
        }
        return $loi;
    }

    /**
     * Chuỗi dùng để so trùng hai câu hỏi.
     * Chỉ bỏ qua khác biệt về chữ hoa/thường, khoảng trắng thừa và dấu câu ở cuối câu.
     * GIỮ NGUYÊN dấu tiếng Việt và ký hiệu (², ³, ×, +…), vì "1 m²" và "1 m³",
     * "5 + 7" và "5 × 7", "bàn" và "bán" là những câu hỏi khác nhau.
     */
    public static function khoaSoTrung(string $cauHoi): string
    {
        $s = mb_strtolower(trim($cauHoi));
        $s = preg_replace('/\s+/u', ' ', $s) ?? $s;
        return preg_replace('/[\s.?!…:;]+$/u', '', $s) ?? $s;
    }

    // ------------------------------------------------------------------
    // GHI
    // ------------------------------------------------------------------

    /**
     * Ghi danh sách câu hỏi vào questions.js.
     * - $phienBanCu: mã phiên bản lúc mở trang. Nếu file đã bị đổi (người khác vừa lưu,
     *   hoặc thầy cô sửa tay), từ chối ghi để không làm mất thay đổi đó.
     * - Luôn sao lưu bản cũ trước khi ghi.
     */
    public function ghi(array $danhSach, string $phienBanCu, string $lyDo): void
    {
        $this->damBaoThuMucSaoLuu();
        $khoa = $this->giuKhoaGhi();
        try {
            if ($phienBanCu !== $this->phienBanHienTai()) {
                throw new RuntimeException(self::LOI_PHIEN_BAN);
            }
            $this->saoLuu($lyDo);
            $noiDung = $this->taoNoiDungFile($danhSach);
            $tam = $this->duongDanFile . '.tmp';
            if (file_put_contents($tam, $noiDung) === false || !rename($tam, $this->duongDanFile)) {
                @unlink($tam);
                throw new RuntimeException('Không ghi được file questions.js. Kiểm tra quyền ghi của thư mục long-quay.');
            }
            $this->donBanSaoLuuCu();
        } finally {
            flock($khoa, LOCK_UN);
            fclose($khoa);
        }
    }

    /**
     * Giành quyền ghi (khóa) để hai lần lưu cùng lúc không ghi đè lên nhau.
     * - Thử lại trong tối đa 5 giây (đề phòng lần lưu khác hoặc phần mềm diệt virus
     *   đang giữ file trong chốc lát).
     * - Không mở được file khóa trong thư mục sao lưu thì dùng file khóa dự phòng
     *   trong thư mục tạm của hệ thống.
     * - Vẫn không được thì báo rõ lý do thật (và ghi vào log của PHP).
     *
     * @return resource
     */
    private function giuKhoaGhi()
    {
        $cacFileKhoa = [
            $this->thuMucSaoLuu . '/.khoa',
            rtrim(sys_get_temp_dir(), '/\\') . '/long-quay-' . md5((string) (realpath($this->duongDanFile) ?: $this->duongDanFile)) . '.khoa',
        ];
        $cacLyDo = [];
        foreach ($cacFileKhoa as $duongDan) {
            $tep = @fopen($duongDan, 'c');
            if ($tep === false) {
                $cacLyDo[] = 'không mở được "' . $duongDan . '" (' . (error_get_last()['message'] ?? 'không rõ lý do') . ')';
                continue;
            }
            $hetHan = microtime(true) + 5;
            do {
                $dangBiGiu = 0;
                if (@flock($tep, LOCK_EX | LOCK_NB, $dangBiGiu)) {
                    return $tep;
                }
                if (!$dangBiGiu) {
                    // Lỗi thật của hệ thống (không phải do đang bị giữ) → thử chỗ khác
                    $cacLyDo[] = 'không khóa được "' . $duongDan . '" (' . (error_get_last()['message'] ?? 'không rõ lý do') . ')';
                    break;
                }
                usleep(100000);
            } while (microtime(true) < $hetHan);
            fclose($tep);
            if (!empty($dangBiGiu)) {
                // Có một lần lưu khác đang giữ khóa: KHÔNG chuyển sang file khóa khác,
                // vì như vậy hai lần lưu sẽ cùng ghi và làm hỏng dữ liệu.
                throw new RuntimeException('Đang có một lần lưu khác chưa xong (đã chờ 5 giây). Hãy đợi vài giây rồi bấm lưu lại.');
            }
        }
        error_log('[Quản lý câu hỏi] Không khóa được file để ghi: ' . implode('; ', $cacLyDo));
        throw new RuntimeException('Không khóa được file để ghi: ' . implode('; ', $cacLyDo)
            . '. Hãy kiểm tra quyền ghi của thư mục dashboard/backups, hoặc phần mềm diệt virus đang chặn.');
    }

    /** Sắp xếp: tự luận trước, trắc nghiệm sau; trong mỗi phần nhóm theo môn. Giữ thứ tự gốc trong cùng nhóm. */
    public static function sapXep(array $danhSach): array
    {
        $thuTuMon = array_flip(self::THU_TU_MON);
        foreach ($danhSach as $c) {
            if (!isset($thuTuMon[$c['mon']])) {
                $thuTuMon[$c['mon']] = count($thuTuMon);
            }
        }
        $kemViTri = [];
        foreach (array_values($danhSach) as $i => $c) {
            $kemViTri[] = [self::laTracNghiem($c) ? 1 : 0, $thuTuMon[$c['mon']], $i, $c];
        }
        usort($kemViTri, static fn($a, $b) => [$a[0], $a[1], $a[2]] <=> [$b[0], $b[1], $b[2]]);
        return array_map(static fn($x) => $x[3], $kemViTri);
    }

    public function taoNoiDungFile(array $danhSach): string
    {
        $danhSach = self::sapXep($danhSach);
        $json = static fn($v) => json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $cacMuc = [];
        $phanTruoc = null;
        $monTruoc = null;
        foreach ($danhSach as $c) {
            $tn = self::laTracNghiem($c);
            $tieuDe = '';
            if ($tn !== $phanTruoc) {
                $tieuDe .= "\n  // =============================================================\n"
                    . ($tn
                        ? "  // PHẦN TRẮC NGHIỆM (4 phương án, chọn 1)\n"
                          . "  // \"luaChon\": các phương án theo thứ tự A, B, C, D\n"
                          . "  // \"dapAnDung\": chữ cái của phương án đúng (\"A\", \"B\", \"C\" hoặc \"D\")\n"
                        : "  // PHẦN TỰ LUẬN (học sinh trả lời miệng, thầy cô bấm \"Xem đáp án\")\n")
                    . "  // =============================================================\n";
                $phanTruoc = $tn;
                $monTruoc = null;
            }
            if ($c['mon'] !== $monTruoc) {
                $tenMon = $c['mon'] === '' ? 'CÂU HỎI CHUNG (không ghi môn)' : mb_strtoupper($c['mon']);
                $tieuDe .= "\n  // ---------- " . ($tn ? 'TRẮC NGHIỆM' : 'TỰ LUẬN') . ' – ' . $tenMon . " ----------\n";
                $monTruoc = $c['mon'];
            }

            $phan = [];
            if ($c['mon'] !== '') {
                $phan[] = '"mon": ' . $json($c['mon']);
            }
            $phan[] = '"cauHoi": ' . $json($c['cauHoi']);
            $tat = !empty($c['tamTat']) ? ', "tamTat": true' : '';
            if ($tn) {
                $dong = '  { ' . implode(', ', $phan) . ",\n    \"luaChon\": [" . implode(', ', array_map($json, $c['luaChon']))
                    . '], "dapAnDung": ' . $json($c['dapAnDung']) . $tat . ' }';
            } else {
                $phan[] = '"dapAn": ' . $json($c['dapAn']);
                $dong = '  { ' . implode(', ', $phan) . $tat . ' }';
            }
            $cacMuc[] = ltrim($tieuDe, "\n") === '' ? $dong : $tieuDe . $dong;
        }

        $than = $cacMuc ? implode(",\n", $cacMuc) : '';
        return self::DAU_FILE . "var questions = [" . ($than === '' ? '' : "\n" . ltrim($than, "\n")) . "\n];\n";
    }

    private const DAU_FILE = <<<'TXT'
// =============================================================
// DANH SÁCH CÂU HỎI
// -------------------------------------------------------------
// Mỗi khi quay trúng một bạn, máy sẽ bốc ngẫu nhiên MỘT câu hỏi ở đây
// để hỏi bạn đó. Câu nào đã hỏi rồi sẽ không hỏi lại, cho tới khi hết
// câu thì mới bắt đầu dùng lại.
//
// Cách dễ nhất để sửa: mở trang quản lý "dashboard" (thêm, sửa, xóa,
// lọc, nhập từ Excel). Vẫn có thể sửa tay file này bằng Notepad.
//
// Câu TỰ LUẬN viết như sau:
//   { "mon": "Toán", "cauHoi": "Nội dung câu hỏi", "dapAn": "Đáp án" },
//  - "mon" (môn học) không bắt buộc. Có ghi thì thẻ sẽ hiện "Câu hỏi Toán dành cho bạn".
//  - "dapAn" có thể bỏ trống ("") nếu câu hỏi không có đáp án cố định.
//
// Muốn TẠM TẮT một câu (giữ lại nhưng không dùng khi quay), thêm  "tamTat": true
//   { "cauHoi": "...", "dapAn": "...", "tamTat": true },
//
// Câu TRẮC NGHIỆM (4 phương án, chọn 1) viết như sau:
//   { "mon": "Toán", "cauHoi": "2 + 3 = ?",
//     "luaChon": ["4", "5", "6", "7"], "dapAnDung": "B" },
//  - "luaChon": các phương án theo thứ tự A, B, C, D.
//  - "dapAnDung": chữ cái của phương án đúng.
//
//  - Chữ phải nằm trong dấu ngoặc kép " ", giữa các dòng có dấu phẩy.
//
// Vì sao không dùng file .json riêng? Khi mở index.html bằng cách nhấp đúp,
// trình duyệt không cho đọc file .json. Thầy cô có sẵn file JSON thì chỉ
// cần dán nội dung (phần [ ... ]) vào sau chữ "var questions =" là được.
// =============================================================


TXT;

    // ------------------------------------------------------------------
    // SAO LƯU
    // ------------------------------------------------------------------

    private function damBaoThuMucSaoLuu(): void
    {
        if (!is_dir($this->thuMucSaoLuu) && !mkdir($this->thuMucSaoLuu, 0775, true) && !is_dir($this->thuMucSaoLuu)) {
            throw new RuntimeException('Không tạo được thư mục sao lưu.');
        }
    }

    private function saoLuu(string $lyDo): void
    {
        if (!is_file($this->duongDanFile)) {
            return;
        }
        $lyDo = preg_replace('/[^a-z0-9-]+/', '-', strtolower(ChuViet::boDau($lyDo))) ?: 'thay-doi';
        $bayGio = \DateTime::createFromFormat('U.u', sprintf('%.6F', microtime(true)))->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $ten = sprintf('questions_%s_%s.js', $bayGio->format('Ymd-His-u'), trim($lyDo, '-'));
        if (!copy($this->duongDanFile, $this->thuMucSaoLuu . '/' . $ten)) {
            throw new RuntimeException('Không sao lưu được bản cũ, nên chưa ghi thay đổi.');
        }
    }

    private function donBanSaoLuuCu(): void
    {
        $cacBan = $this->danhSachSaoLuu();
        foreach (array_slice($cacBan, $this->soBanSaoLuuGiuLai) as $ban) {
            @unlink($this->thuMucSaoLuu . '/' . $ban['ten']);
        }
    }

    /** Các bản sao lưu, mới nhất trước. */
    public function danhSachSaoLuu(): array
    {
        $ds = [];
        foreach (glob($this->thuMucSaoLuu . '/questions_*.js') ?: [] as $duongDan) {
            $ten = basename($duongDan);
            if (!preg_match('/^questions_(\d{8}-\d{6})-\d{6}_([a-z0-9-]*)\.js$/', $ten, $m)) {
                continue;
            }
            $ds[] = [
                'ten' => $ten,
                'thoiGian' => \DateTime::createFromFormat('Ymd-His', $m[1]),
                'lyDo' => str_replace('-', ' ', $m[2]),
                'kichThuoc' => filesize($duongDan),
            ];
        }
        usort($ds, static fn($a, $b) => strcmp($b['ten'], $a['ten']));
        return $ds;
    }

    public function duongDanSaoLuu(string $ten): string
    {
        if (!preg_match('/^questions_\d{8}-\d{6}-\d{6}_[a-z0-9-]*\.js$/', $ten) || !is_file($this->thuMucSaoLuu . '/' . $ten)) {
            throw new RuntimeException('Không tìm thấy bản sao lưu này.');
        }
        return $this->thuMucSaoLuu . '/' . $ten;
    }

    /** Khôi phục một bản sao lưu (bản hiện tại cũng được sao lưu trước). */
    public function khoiPhuc(string $ten, string $phienBanCu): int
    {
        $noiDung = (string) file_get_contents($this->duongDanSaoLuu($ten));
        $danhSach = array_map(
            static fn($m) => self::chuanHoa(is_array($m) ? $m : ['cauHoi' => (string) $m]),
            self::phanTichNoiDungJs($noiDung)
        );
        $this->ghi($danhSach, $phienBanCu, 'truoc-khi-khoi-phuc');
        return count($danhSach);
    }
}
