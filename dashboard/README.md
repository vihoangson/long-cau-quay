# 📚 Trang quản lý câu hỏi – Lồng Quay May Mắn

Trang web nhỏ (PHP) để **xem, lọc, thêm, sửa, xóa, nhập và xuất** ngân hàng câu hỏi.
Mọi thay đổi được lưu thẳng vào `../long-quay/questions.js`, là file mà trang quay số đọc vào.

## Cách mở

1. Bật **Apache** trong XAMPP Control Panel.
2. Mở trình duyệt, vào:
   `http://localhost/vhosts/long%20cau%20quay/dashboard/`

**Hoặc không cần Apache**, dùng server có sẵn của PHP. Mở cửa sổ lệnh tại thư mục **gốc dự án** (`long cau quay`, thư mục chứa cả `dashboard` và `long-quay`) rồi chạy:

```
D:\xampp8.0\php\php.exe -S localhost:8000
```

rồi vào `http://localhost:8000/dashboard/`. Nên chạy ở thư mục gốc như trên. Nếu chạy ngay trong thư mục `dashboard` thì trang quản lý vẫn chạy, nhưng nút "Mở trang quay số" sẽ không mở được, vì thư mục `long-quay` nằm ngoài phạm vi server.

Lần đầu cài trên máy khác, mở cửa sổ lệnh tại thư mục `dashboard` và chạy (dùng đúng PHP của XAMPP):

```
D:\xampp8.0\php\php.exe C:\ProgramData\ComposerSetup\bin\composer.phar install
```

Yêu cầu: PHP 8.0 trở lên, đã bật `zip`, `xml`, `mbstring` (XAMPP có sẵn). Không cần cơ sở dữ liệu.

## Các chức năng

| Chức năng | Cách dùng |
|---|---|
| **Thống kê** | Tổng số câu, số câu tự luận / trắc nghiệm, theo từng môn, đáp án đúng nằm ở A/B/C/D bao nhiêu câu, số câu bị lỗi hoặc trùng |
| **Lọc, tìm** | Gõ từ khóa (gõ không dấu cũng tìm được), lọc theo môn, loại, tình trạng (trùng / có lỗi), chọn số dòng mỗi trang |
| **Thêm / Sửa** | Bấm "➕ Thêm câu hỏi" hoặc "✏️ Sửa". Chọn loại *Tự luận* (nhập đáp án) hoặc *Trắc nghiệm* (nhập A–D và bấm chữ cái của đáp án đúng) |
| **Bật / tắt** | "⏸ Tắt" để **tạm tắt** một câu: câu vẫn nằm trong ngân hàng nhưng trang quay số **không hỏi tới**. "▶ Bật" để dùng lại. Có thể tích chọn nhiều câu rồi bấm Tắt / Bật cùng lúc, hoặc tích ô "Tạm tắt" trong hộp sửa. Lọc "Đang dùng" / "Tạm tắt" để xem nhanh |
| **Xóa** | "🗑 Xóa" từng câu, hoặc tích chọn nhiều câu rồi bấm "Xóa" |
| **Nhập file** | Nhận Excel `.xlsx`, `.csv` (UTF-8), `.json` hoặc file `questions.js`. Chọn *Thêm vào* hoặc *Thay thế toàn bộ*; có thể bỏ qua câu trùng |
| **Xuất file** | Tải **file Excel mẫu**, xuất toàn bộ ra **Excel** hoặc **JSON** |
| **Sao lưu** | Mỗi lần thay đổi, bản cũ được tự cất vào `dashboard/backups/` (giữ 30 bản). Bấm "↩ Khôi phục" để quay lại |

### Cột trong file Excel / CSV

| Môn | Loại | Câu hỏi | Đáp án (tự luận) | A | B | C | D | Đáp án đúng (A-D) | Trạng thái |
|---|---|---|---|---|---|---|---|---|---|
| Toán | Tự luận | 25% của 80 là bao nhiêu? | 20 | | | | | | Đang dùng |
| Tiếng Việt | Trắc nghiệm | Từ nào đồng nghĩa với "hạnh phúc"? | | Sung sướng | Đau khổ | Buồn bã | Lo lắng | A | Tạm tắt |

- Cột "Trạng thái" ghi "Tạm tắt" nếu muốn giữ câu nhưng chưa dùng. Để trống thì hiểu là đang dùng.

- Tên cột viết có dấu hay không dấu đều được (`Cau hoi`, `Dap an dung`...).
- Có điền A–D thì máy tự hiểu là câu trắc nghiệm.
- Dòng nào lỗi sẽ được báo kèm **số dòng**, các dòng đúng vẫn được nhập.

## Quản lý học sinh

Bấm tab **🧒 Học sinh** ở đầu trang (hoặc mở `dashboard/hoc-sinh.php`). Thay đổi được lưu thẳng vào `../long-quay/students.js`.

| Chức năng | Cách dùng |
|---|---|
| **Thêm / Sửa** | "➕ Thêm học sinh" (số trên bóng được gợi ý sẵn) hoặc "✏️ Sửa". Số phải từ 1–999 và không trùng |
| **Vắng / Có mặt** | "🏠 Vắng" để bạn đó **không có bóng trong lồng** hôm nay (ghi `vang: true` trong file), "🎱 Có mặt" để có lại. Chọn nhiều bạn rồi bấm cùng lúc được |
| **Xóa** | Từng bạn, hoặc tích chọn nhiều bạn |
| **Đánh số lại** | Sau khi xóa bị hổng số, bấm "🔢 Đánh số lại" để thành 1, 2, 3… |
| **Nhập** | Dán danh sách (mỗi dòng một bạn, có thể kèm số "1. Nguyễn Văn An" hoặc dán 2 cột từ Excel), hoặc chọn file `.xlsx`, `.csv`, `.txt`, `.json`, `students.js`. Bạn nào không có số / trùng số được tự đánh số tiếp |
| **Xuất** | File Excel mẫu (cột *Số, Họ và tên, Vắng*) hoặc xuất cả danh sách ra Excel |
| **Sao lưu** | Bản cũ được tự cất vào `dashboard/backups/students_*.js`, bấm "↩ Khôi phục" để quay lại |

## An toàn dữ liệu

- **Chống ghi đè:** nếu file `questions.js` vừa bị đổi ở nơi khác (mở hai tab, hoặc vừa sửa tay bằng Notepad), trang sẽ từ chối lưu và tải lại dữ liệu mới, để không làm mất thay đổi kia.
- **Không ghi khi file đang hỏng:** nếu `questions.js` bị sai cú pháp, trang chỉ báo lỗi và khóa việc sửa. Khi đó có thể khôi phục bản sao lưu hoặc nhập file với chế độ *Thay thế toàn bộ*.
- **Câu trùng:** hai câu bị coi là trùng nếu chỉ khác chữ hoa/thường, khoảng trắng hoặc dấu câu ở cuối câu.
- **Mật khẩu:** mở `config.php`, điền `'mat_khau' => '...'`. **Nên đặt** nếu máy nối mạng chung với học sinh.
- **Chặn truy cập trực tiếp:** file `.htaccess` chặn mở trực tiếp các thư mục `src/`, `vendor/`, `backups/`, `giao-dien/` và các file cấu hình.

## Cấu trúc thư mục

```
dashboard/
├── index.php          Trang chính (đọc dữ liệu, lọc, thống kê)
├── xu-ly.php          Xử lý thêm / sửa / xóa / nhập / khôi phục / xuất
├── khoi-dong.php      Nạp thư viện, phiên đăng nhập, hàm dùng chung
├── config.php         Cấu hình (đường dẫn file, mật khẩu, số bản sao lưu)
├── src/
│   ├── KhoCauHoi.php  Đọc / ghi / kiểm tra questions.js, sao lưu
│   ├── NhapXuat.php   Đọc Excel, CSV, JSON; xuất Excel, JSON
│   └── ChuViet.php    Bỏ dấu tiếng Việt (tìm kiếm không dấu)
├── giao-dien/         Mẫu HTML (trang chính, đăng nhập)
├── assets/            CSS và JavaScript
├── backups/           Các bản sao lưu (tự tạo)
└── vendor/            Thư viện Composer: shuchkin/simplexlsx, shuchkin/simplexlsxgen
```

Thư viện Excel dùng `shuchkin/simplexlsx` và `simplexlsxgen` (thuần PHP, nhẹ), vì PHP của XAMPP chưa bật `gd` nên không cài được PhpSpreadsheet.
