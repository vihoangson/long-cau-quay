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

var questions = [
  // =============================================================
  // PHẦN TỰ LUẬN (học sinh trả lời miệng, thầy cô bấm "Xem đáp án")
  // =============================================================

  // ---------- TỰ LUẬN – CÂU HỎI CHUNG (không ghi môn) ----------
  { "cauHoi": "Em hãy đọc một câu ca dao hoặc tục ngữ mà em thích.", "dapAn": "", "tamTat": true },
  { "cauHoi": "Hãy kể 3 ngày không phải thứ 2, t3, t4, t5, t6, t7, cn", "dapAn": "Ngày hôm qua, ngày hôm nay, ngày mai", "tamTat": true },

  // ---------- TỰ LUẬN – TIẾNG VIỆT ----------
  { "mon": "Tiếng Việt", "cauHoi": "Hôm nay là ngày thứ mấy", "dapAn": "Ngày hôm qua + 1", "tamTat": true }
];
