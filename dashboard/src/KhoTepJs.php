<?php
declare(strict_types=1);

namespace Dashboard;

use RuntimeException;

/**
 * Phần dùng chung để đọc / ghi một danh sách trong file .js của trang quay số
 * (questions.js, students.js): chống ghi đè, khóa khi ghi, sao lưu, khôi phục.
 *
 * Lớp con chỉ cần cho biết:
 *  - TEN_BIEN:   tên biến trong file JS ("questions", "students")
 *  - TIEN_TO:    tiền tố tên file sao lưu ("questions", "students")
 *  - TEN_DU_LIEU: tên gọi để báo lỗi ("câu hỏi", "học sinh")
 *  - chuanHoa(): đưa một mục về dạng thống nhất
 *  - taoNoiDungFile(): viết lại toàn bộ file
 */
abstract class KhoTepJs
{
    protected const TEN_BIEN = 'questions';
    protected const TIEN_TO = 'questions';
    protected const TEN_DU_LIEU = 'dữ liệu';

    public function __construct(
        protected string $duongDanFile,
        protected string $thuMucSaoLuu,
        protected int $soBanSaoLuuGiuLai = 30
    ) {
    }

    /** Đưa một mục đọc từ file về dạng thống nhất. */
    abstract public static function chuanHoa(array $muc): array;

    /** Nội dung đầy đủ của file JS sẽ ghi ra. */
    abstract public function taoNoiDungFile(array $danhSach): string;

    /** Mục trong file viết dạng chữ trơn (không phải { ... }) thì đổi thành mảng. */
    protected static function tuChuTron(string $chu): array
    {
        return ['cauHoi' => $chu];
    }

    // ------------------------------------------------------------------
    // ĐỌC
    // ------------------------------------------------------------------

    /**
     * Đọc file, trả về ['ds' => danh sách đã chuẩn hóa, 'phienBan' => mã phiên bản].
     * "phienBan" dùng để phát hiện file bị thay đổi giữa lúc mở trang và lúc lưu.
     */
    public function docDanhSach(): array
    {
        if (!is_file($this->duongDanFile)) {
            return ['ds' => [], 'phienBan' => 'chua-co-file'];
        }
        $noiDung = (string) file_get_contents($this->duongDanFile);
        return ['ds' => $this->phanTich($noiDung), 'phienBan' => md5($noiDung)];
    }

    /** Phân tích nội dung một file (file đang dùng hoặc bản sao lưu) thành danh sách đã chuẩn hóa. */
    protected function phanTich(string $noiDung): array
    {
        $ds = [];
        foreach (self::phanTichNoiDungJs($noiDung, static::TEN_BIEN) as $muc) {
            $ds[] = static::chuanHoa(is_array($muc) ? $muc : static::tuChuTron((string) $muc));
        }
        return $ds;
    }

    /** Báo lỗi nếu file đã bị thay đổi kể từ lúc mở trang. */
    public function kiemTraPhienBan(string $phienBanCu): void
    {
        if ($phienBanCu !== $this->phienBanHienTai()) {
            throw new RuntimeException($this->loiPhienBan());
        }
    }

    public function phienBanHienTai(): string
    {
        return is_file($this->duongDanFile) ? md5((string) file_get_contents($this->duongDanFile)) : 'chua-co-file';
    }

    private function loiPhienBan(): string
    {
        return 'Danh sách ' . static::TEN_DU_LIEU . ' vừa được thay đổi ở nơi khác (tab khác hoặc sửa tay file). '
            . 'Trang đã tải lại dữ liệu mới nhất, thầy cô hãy làm lại thao tác.';
    }

    /**
     * Lấy mảng từ nội dung file .js (hoặc từ một chuỗi JSON).
     * Chấp nhận cả kiểu viết tay của thầy cô: có chú thích //, dấu phẩy thừa ở cuối,
     * chuỗi dùng nháy đơn '...', tên thuộc tính không có ngoặc kép.
     */
    public static function phanTichNoiDungJs(string $noiDung, string $tenBien = 'questions'): array
    {
        $noiDung = preg_replace('/^\xEF\xBB\xBF/', '', $noiDung) ?? $noiDung;   // bỏ BOM
        $sach = self::boChuThich($noiDung);

        if (preg_match('/\b' . preg_quote($tenBien, '/') . '\s*=\s*\[/', $sach, $m, PREG_OFFSET_CAPTURE)) {
            $batDau = $m[0][1] + strlen($m[0][0]) - 1;           // vị trí dấu [
        } else {
            $batDau = strpos($sach, '[');                         // file JSON thuần
            if ($batDau === false) {
                throw new RuntimeException('Không tìm thấy danh sách (thiếu "var ' . $tenBien . ' = [ ... ]").');
            }
        }

        $json = self::sangJson($sach, $batDau);
        $mang = json_decode($json, true);
        if (!is_array($mang)) {
            throw new RuntimeException('Danh sách bị sai cú pháp: ' . json_last_error_msg()
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
        throw new RuntimeException('Danh sách thiếu dấu "]" ở cuối.');
    }

    // ------------------------------------------------------------------
    // GHI
    // ------------------------------------------------------------------

    /**
     * Ghi danh sách vào file.
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
                throw new RuntimeException($this->loiPhienBan());
            }
            $this->saoLuu($lyDo);
            $noiDung = $this->taoNoiDungFile($danhSach);
            $tam = $this->duongDanFile . '.tmp';
            if (file_put_contents($tam, $noiDung) === false || !rename($tam, $this->duongDanFile)) {
                @unlink($tam);
                throw new RuntimeException('Không ghi được file ' . basename($this->duongDanFile) . '. Kiểm tra quyền ghi của thư mục long-quay.');
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
        error_log('[Lồng quay] Không khóa được file để ghi: ' . implode('; ', $cacLyDo));
        throw new RuntimeException('Không khóa được file để ghi: ' . implode('; ', $cacLyDo)
            . '. Hãy kiểm tra quyền ghi của thư mục dashboard/backups, hoặc phần mềm diệt virus đang chặn.');
    }

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
        $ten = sprintf('%s_%s_%s.js', static::TIEN_TO, $bayGio->format('Ymd-His-u'), trim($lyDo, '-'));
        if (!copy($this->duongDanFile, $this->thuMucSaoLuu . '/' . $ten)) {
            throw new RuntimeException('Không sao lưu được bản cũ, nên chưa ghi thay đổi.');
        }
    }

    private function donBanSaoLuuCu(): void
    {
        foreach (array_slice($this->danhSachSaoLuu(), $this->soBanSaoLuuGiuLai) as $ban) {
            @unlink($this->thuMucSaoLuu . '/' . $ban['ten']);
        }
    }

    /** Các bản sao lưu, mới nhất trước. */
    public function danhSachSaoLuu(): array
    {
        $ds = [];
        $mau = '/^' . preg_quote(static::TIEN_TO, '/') . '_(\d{8}-\d{6})-\d{6}_([a-z0-9-]*)\.js$/';
        foreach (glob($this->thuMucSaoLuu . '/' . static::TIEN_TO . '_*.js') ?: [] as $duongDan) {
            $ten = basename($duongDan);
            if (!preg_match($mau, $ten, $m)) {
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
        $mau = '/^' . preg_quote(static::TIEN_TO, '/') . '_\d{8}-\d{6}-\d{6}_[a-z0-9-]*\.js$/';
        if (!preg_match($mau, $ten) || !is_file($this->thuMucSaoLuu . '/' . $ten)) {
            throw new RuntimeException('Không tìm thấy bản sao lưu này.');
        }
        return $this->thuMucSaoLuu . '/' . $ten;
    }

    /** Khôi phục một bản sao lưu (bản hiện tại cũng được sao lưu trước). Trả về số mục. */
    public function khoiPhuc(string $ten, string $phienBanCu): int
    {
        $danhSach = $this->phanTich((string) file_get_contents($this->duongDanSaoLuu($ten)));
        $this->ghi($danhSach, $phienBanCu, 'truoc-khi-khoi-phuc');
        return count($danhSach);
    }
}
