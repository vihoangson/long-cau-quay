# 🎡 Lồng Quay May Mắn – Hướng dẫn cho thầy cô

Ứng dụng quay số ngẫu nhiên bằng **lồng cầu** để chọn học sinh trong các buổi sinh hoạt, sự kiện của lớp. Bạn nào được chọn sẽ nhận một **câu hỏi** để trả lời.
**Không cần Internet, không cần cài đặt gì.**

---

## 1. Cách chạy

1. Mở thư mục `long-quay`.
2. **Nhấp đúp vào file `index.html`**. Trang sẽ mở bằng trình duyệt (nên dùng **Google Chrome** hoặc **Microsoft Edge**).
3. Bấm nút **QUAY!** thật to ở giữa phía dưới, hoặc nhấn **phím cách (Space)** trên bàn phím.

Mỗi lượt quay:

- Lồng cầu quay khoảng 6–7,5 giây, có tiếng lạch cạch.
- Lồng dừng lại, một quả bóng lăn ra máng rồi bay lên thật to.
- Số và tên của bạn được chọn hiện ra, kèm pháo hoa và tiếng chúc mừng.
- Ngay sau đó hiện **câu hỏi dành cho bạn**. Thầy cô bấm **👀 Xem đáp án** để hiện đáp án.
- Bấm **✅ Xong**, hoặc bấm quay lượt tiếp theo: kết quả thu nhỏ rồi bay vào bảng **"Đã quay"** bên phải (kèm câu hỏi đã hỏi).

Bạn đã trúng sẽ **không bao giờ bị quay trùng**. Khi đã quay hết, màn hình báo **"Đã quay hết danh sách!"** và nút QUAY bị khóa.

> 🔊 Âm thanh chỉ bật sau lần đầu thầy cô bấm chuột hoặc nhấn phím (đây là quy định của trình duyệt). Nhớ bật loa máy tính.

---

## 2. Cách sửa danh sách học sinh

1. Bấm chuột phải vào file **`students.js`** → **Open with** (Mở bằng) → **Notepad**.
2. Sửa danh sách theo mẫu, mỗi dòng là một bạn:

```js
var students = [
  { so: 1, ten: "Nguyễn Văn An" },
  { so: 2, ten: "Trần Thị Bình" },
  { so: 3, ten: "Lê Hoàng Cường" }
];
```

- `so` là số in trên quả bóng, `ten` là họ tên (đặt trong dấu ngoặc kép `" "`).
- Giữa các dòng có dấu phẩy `,`.
- Có bao nhiêu dòng thì trong lồng có bấy nhiêu bóng. Bóng sẽ tự nhỏ lại khi danh sách dài.

3. Bấm **Ctrl + S** để lưu. Khi Notepad hỏi kiểu mã hóa (Encoding), chọn **UTF-8** để giữ đúng dấu tiếng Việt.
4. Bấm **F5** để tải lại trang.

---

## 3. Cách sửa danh sách câu hỏi

Câu hỏi nằm trong file **`questions.js`** (mở bằng Notepad như trên), viết theo kiểu **JSON**:

```js
var questions = [
  { "mon": "Toán", "cauHoi": "25% của 80 là bao nhiêu?", "dapAn": "20" },
  { "mon": "Tiếng Việt", "cauHoi": "Từ trái nghĩa với \"hòa bình\" là gì?", "dapAn": "Chiến tranh" },
  { "cauHoi": "Em hãy hát một đoạn bài hát em thích nhất.", "dapAn": "" }
];
```

- `"cauHoi"` là câu hỏi, `"dapAn"` là đáp án. Câu không có đáp án cố định thì để `""`, khi đó nút "Xem đáp án" tự ẩn.
- `"mon"` (môn học) không bắt buộc. Có ghi thì thẻ hiện "❓ Câu hỏi **Toán** dành cho bạn".

**Câu trắc nghiệm (4 phương án, chọn 1)** viết thêm `"luaChon"` và `"dapAnDung"`:

```js
{ "mon": "Toán", "cauHoi": "1,5 giờ bằng bao nhiêu phút?",
  "luaChon": ["90 phút", "150 phút", "105 phút", "15 phút"], "dapAnDung": "A" },
```

- `"luaChon"`: các phương án theo thứ tự A, B, C, D.
- `"dapAnDung"`: chữ cái của phương án đúng (`"A"`, `"B"`, `"C"` hoặc `"D"`).
- Trên màn hình, học sinh **bấm vào phương án**, hoặc thầy cô nhấn phím **A, B, C, D** (hay **1, 2, 3, 4**). Máy báo ngay **"Chính xác!"** (pháo hoa, tiếng nhạc) hoặc **"Chưa đúng"** (tô đỏ phương án sai, tô xanh đáp án đúng). Mỗi câu chỉ được chọn **một lần**.
- Nút **👀 Xem đáp án** tô xanh đáp án đúng mà không tính đúng/sai cho bạn đó.
- Bảng **"Đã quay"** ghi lại kết quả **✔ Đúng** / **✘ Sai** của từng bạn.

**Chọn kiểu câu hỏi:** ở cuối bảng "Đã quay" có ô **Kiểu câu hỏi**: *Cả hai loại*, *Tự luận* hoặc *Trắc nghiệm*. Lựa chọn áp dụng từ lượt quay tiếp theo và được ghi nhớ cho lần mở sau.

**Ngân hàng câu hỏi có sẵn: 225 câu**, chia nhóm rõ ràng trong file:

| Loại | Chung | Toán lớp 5 | Tiếng Việt lớp 5 | Khoa học lớp 5 | Cộng |
|---|---|---|---|---|---|
| Tự luận | 25 | 35 | 35 | 30 | **125** |
| Trắc nghiệm | – | 34 | 33 | 33 | **100** |

- Mỗi bạn trúng nhận ngẫu nhiên **một câu chưa hỏi**. Hỏi hết mọi câu rồi thì mới quay lại dùng câu cũ.
- Đáp án đúng của câu trắc nghiệm được rải đều ở A, B, C, D (mỗi chữ 25 câu) để học sinh không đoán theo quy luật.
- Thầy cô cứ sửa, xóa hoặc thêm câu tùy ý.
- **Đã có sẵn file `.json`?** Mở file đó, chép toàn bộ phần `[ ... ]` rồi dán thay vào sau chữ `var questions =` trong `questions.js`.
  (Khi mở `index.html` bằng cách nhấp đúp, trình duyệt không cho đọc thẳng file `.json`, nên phải để trong file `.js`.)
- Muốn tắt phần câu hỏi: để danh sách trống, tức `var questions = [];`

---

## 4. Âm thanh MP3 (tiếng chúc mừng, tiếng quay)

Xem thêm hướng dẫn trong file `am-thanh/HUONG-DAN.txt`.

- **Tiếng chúc mừng / hoan hô:** bỏ file MP3 vào thư mục `am-thanh` và đặt tên đúng là **`chuc-mung.mp3`**. File sẽ phát mỗi khi hiện tên bạn trúng. Chưa có file thì chỉ có tiếng "ting" có sẵn.
- **Tiếng lồng quay (không bắt buộc):** bỏ file `quay.mp3` vào `am-thanh`, rồi mở `app.js`, tìm dòng `quay: ''` và sửa thành `quay: 'am-thanh/quay.mp3'`.

---

## 5. Bật toàn màn hình (khi chiếu lên máy chiếu)

- Bấm nút **🖥️ Toàn màn hình** ở dưới bên phải, **hoặc** nhấn phím **F11**.
- Để thoát: bấm **↩️ Thoát toàn màn hình** hoặc nhấn phím **Esc**.

---

## 6. Làm lại từ đầu

- Lịch sử quay (cả câu hỏi đã hỏi) được **tự động lưu** trong trình duyệt. Lỡ tắt trang hay tải lại thì vẫn còn nguyên.
- Muốn quay lại từ đầu (ví dụ sang sự kiện mới): bấm **🔄 Làm lại từ đầu** → chọn **"Có, làm lại"**. Toàn bộ lịch sử bị xóa và tất cả bóng trở lại lồng.
- Nếu thầy cô **sửa danh sách học sinh**, những lượt cũ không còn khớp (bạn bị xóa hoặc bị đổi số/tên) sẽ tự bỏ khỏi lịch sử.

---

## 7. Chỉnh thêm (không bắt buộc)

Mở `app.js` bằng Notepad. Ở ngay đầu file có phần **`CONFIG`**, mỗi dòng đều có ghi chú tiếng Việt. Ví dụ:

| Thông số | Ý nghĩa |
|---|---|
| `thoiGianQuay` | Lồng quay ít nhất bao nhiêu giây (hiện là 6) |
| `thoiGianQuayThem` | Cộng thêm ngẫu nhiên tối đa bao nhiêu giây (hiện là 1,5) |
| `tocDoQuayToiDa` | Lồng quay nhanh cỡ nào |
| `mauBong` | Danh sách màu của các quả bóng |
| `amLuong` | Độ to âm thanh (0 là tắt tiếng, 1 là to nhất) |
| `amThanhMp3` | Tên các file MP3 |
| `soPhaoHoa` | Số quả pháo hoa mỗi lần trúng |

---

## Các file trong thư mục

| File | Dùng để |
|---|---|
| `index.html` | Trang chính, nhấp đúp để mở |
| `students.js` | **Danh sách học sinh**, thầy cô sửa ở đây |
| `questions.js` | **Danh sách câu hỏi** (kiểu JSON), thầy cô sửa ở đây |
| `am-thanh/` | Nơi bỏ file MP3 (`chuc-mung.mp3`, `quay.mp3`) |
| `style.css` | Màu sắc, kiểu chữ, giao diện |
| `app.js` | Chương trình: vẽ lồng, quay bóng, câu hỏi, âm thanh, pháo hoa |
