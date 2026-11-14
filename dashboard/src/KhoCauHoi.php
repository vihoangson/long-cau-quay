<?php
declare(strict_types=1);

namespace Dashboard;

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
final class KhoCauHoi extends KhoTepJs
{
    public const CHU_CAI = ['A', 'B', 'C', 'D'];
    public const DO_DAI_TOI_DA = 600;
    protected const TEN_BIEN = 'questions';
    protected const TIEN_TO = 'questions';
    protected const TEN_DU_LIEU = 'câu hỏi';

    /** Thứ tự các môn khi sắp xếp trong file (môn khác xếp sau, theo thứ tự xuất hiện). */
    private const THU_TU_MON = ['', 'Toán', 'Tiếng Việt', 'Khoa học'];

    /**
     * Đọc file và trả về ['cauHoi' => danh sách câu hỏi, 'phienBan' => mã phiên bản].
     * "phienBan" dùng để phát hiện file bị thay đổi giữa lúc mở trang và lúc lưu.
     */
    public function doc(): array
    {
        $kq = $this->docDanhSach();
        return ['cauHoi' => $kq['ds'], 'phienBan' => $kq['phienBan']];
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
    // GHI FILE
    // ------------------------------------------------------------------

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
}
