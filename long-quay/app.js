'use strict';
/* =============================================================
   LỒNG QUAY XỔ SỐ 2.5D (LỒNG CẦU)  –  app.js
   -------------------------------------------------------------
   Chương trình được chia thành các phần, đọc từ trên xuống:
    0. CONFIG: các thông số dễ chỉnh
    1. Lấy các phần tử trên trang
    2. Đọc danh sách học sinh và câu hỏi
    3. Trạng thái của chương trình
    4. Công cụ toán học và màu sắc
    5. Máy ảnh: biến điểm 3D thành điểm 2D trên màn hình
    6. Bố cục màn hình
    7. Tạo bóng và vật lý (rơi, nảy, va chạm)
    8. Quay lồng, chọn bóng trúng, chọn câu hỏi
    9. Vẽ sàn, chân đỡ, lồng cầu, máng, bóng
   10. Vẽ bóng trúng và pháo hoa
   11. Lịch sử và lưu vào trình duyệt (localStorage)
   12. Âm thanh (Web Audio API và file MP3)
   13. Nút bấm và bàn phím
   14. Vòng lặp chính
   ============================================================= */


/* =============================================================
   0. CONFIG – THẦY CÔ CÓ THỂ CHỈNH CÁC SỐ Ở ĐÂY
   ============================================================= */
const CONFIG = {
  // --- Thời gian (tính bằng giây) ---
  thoiGianQuay: 6,            // lồng quay ít nhất bao lâu
  thoiGianQuayThem: 1.5,      // cộng thêm ngẫu nhiên từ 0 tới số này (để mỗi lượt khác nhau)
  thoiGianTangToc: 1.0,       // thời gian lồng tăng tốc lúc đầu
  thoiGianGiamToc: 2.2,       // thời gian lồng chậm dần lúc cuối
  thoiGianChoSauKhiDung: 0.4, // nghỉ một chút sau khi lồng dừng
  thoiGianBongRaCua: 0.6,     // bóng trúng đi ra cửa lồng
  thoiGianLanXuongMang: 0.8,  // bóng lăn xuống máng
  thoiGianBayLen: 1.1,        // bóng bay lên và phóng to
  thoiGianBayVaoBang: 0.7,    // kết quả cũ bay vào bảng lịch sử

  // --- Tốc độ và vật lý ---
  tocDoQuayToiDa: 5.2,        // tốc độ quay lớn nhất
  trongLuc: 28,               // lực hút bóng xuống dưới
  doNayThanhLong: 0.35,       // bóng nảy khỏi thành lồng mạnh hay yếu (0 tới 1)
  doNayGiuaBong: 0.55,        // bóng nảy vào nhau mạnh hay yếu (0 tới 1)
  doBamThanhLong: 18,         // thành lồng kéo bóng chạy theo mạnh cỡ nào
  lucCanKhongKhi: 0.25,       // lực cản không khí khi lồng quay
  lucCanKhiNghi: 1.6,         // lực cản lúc lồng đứng yên (để bóng mau nằm im)
  soLanHatMoiGiay: 2.2,       // nan lồng hất bóng lên bao nhiêu lần mỗi giây
  lucHat: 3.2,                // hất mạnh cỡ nào

  // --- Hình dáng và góc nhìn ---
  soNanDoc: 12,               // số nan kim loại chạy từ đầu này sang đầu kia của quả cầu
  gocNhinNgang: -24,          // xoay góc nhìn sang ngang (độ)
  gocNhinXuong: 20,           // nhìn từ trên xuống bao nhiêu độ
  khoangCachMayAnh: 4.6,      // càng nhỏ thì phối cảnh xa – gần càng rõ

  // --- Màu sắc ---
  mauBong: ['#ff4757', '#ffa502', '#2ed573', '#1e90ff', '#a55eea', '#ff6b81',
            '#f9ca24', '#00cec9', '#ff7f50', '#6ab04c', '#4834d4', '#e056fd'],
  mauThanhToi: '#4f5866',     // nan lồng (bạc) phần tối
  mauThanhSang: '#f5f8ff',    // nan lồng (bạc) phần sáng
  mauVanhToi: '#9a6a00',      // vòng ngang (vàng đồng) phần tối
  mauVanhSang: '#ffe680',     // vòng ngang (vàng đồng) phần sáng
  mauChanDe: '#e8384f',       // chân đỡ
  mauMang: '#ffb703',         // máng hứng bóng
  mauSanSang: '#f3edff',      // mặt bục
  mauSanToi: '#7b5cd6',       // cạnh bục
  mauPhaoHoa: ['#ff4757', '#ffd32a', '#2ed573', '#1e90ff', '#ff6bcb', '#ffffff', '#ffa502', '#7efff5'],

  // --- Âm thanh ---
  amLuong: 0.6,               // 0 là tắt tiếng, 1 là to nhất
  // File MP3 đặt trong thư mục am-thanh. Để "" nếu không muốn dùng file đó.
  // Chưa có file thì chương trình tự dùng tiếng có sẵn.
  amThanhMp3: {
    chucMung: 'am-thanh/chuc-mung.mp3',  // tiếng chúc mừng / hoan hô khi hiện tên
    quay: ''   // tiếng lồng quay: muốn dùng thì ghi 'am-thanh/quay.mp3' (để trống = dùng tiếng lạch cạch có sẵn)
  },

  // --- Pháo hoa ---
  soPhaoHoa: 8,               // số quả pháo hoa mỗi lần trúng

  // --- Thời gian trả lời câu hỏi ---
  // Số giây mặc định. Thầy cô chỉnh ở trang quản lý (dashboard), giá trị đó được
  // lưu trong file settings.js và sẽ dùng thay cho số này. 0 = không đếm giờ.
  thoiGianTraLoi: 30,
  soGiayCanhBao: 5            // mấy giây cuối thì đồng hồ đỏ lên và kêu tích tắc
};

// Cài đặt thầy cô chỉnh ở trang quản lý (file settings.js). Không có file thì dùng CONFIG.
const CAI_DAT = (typeof settings !== 'undefined' && settings && typeof settings === 'object') ? settings : {};

// Số giây được trả lời mỗi câu (0 = không đếm giờ)
function thoiGianTraLoi() {
  const n = Number(CAI_DAT.thoiGianTraLoi);
  if (CAI_DAT.thoiGianTraLoi !== undefined && CAI_DAT.thoiGianTraLoi !== '' && Number.isFinite(n) && n >= 0) {
    return Math.min(3600, Math.round(n));
  }
  return CONFIG.thoiGianTraLoi;
}


/* =============================================================
   1. LẤY CÁC PHẦN TỬ TRÊN TRANG
   ============================================================= */
const $ = (id) => document.getElementById(id);

const canvasLong = $('canvasLong');
const ctx = canvasLong.getContext('2d');          // bút vẽ lồng
const canvasHieuUng = $('canvasHieuUng');
const ctxHU = canvasHieuUng.getContext('2d');     // bút vẽ pháo hoa, bóng trúng

const thanhTren = $('thanhTren');
const thanhDuoi = $('thanhDuoi');
const bangLichSu = $('bangLichSu');
const dsLichSu = $('danhSachLichSu');
const soDaQuay = $('soDaQuay');
const dem = $('dem');
const theKetQua = $('theKetQua');
const soTrung = $('soTrung');
const tenTrung = $('tenTrung');
const khungCauHoi = $('khungCauHoi');
const nhanCauHoi = $('nhanCauHoi');
const cacLuaChon = $('cacLuaChon');
const chonKieuCauHoi = $('chonKieuCauHoi');
const noiDungCauHoi = $('noiDungCauHoi');
const dapAn = $('dapAn');
const dongHoEl = $('dongHo');
const dongHoSo = $('dongHoSo');
const dongHoThanh = $('dongHoThanh');
const nutDapAn = $('nutDapAn');
const nutXong = $('nutXong');
const thongBaoHet = $('thongBaoHet');
const thongBaoLoi = $('thongBaoLoi');
const nutQuay = $('nutQuay');
const nutLamLai = $('nutLamLai');
const nutToanManHinh = $('nutToanManHinh');
const hopThoai = $('hopThoai');
const nutHuy = $('nutHuy');
const nutDongY = $('nutDongY');

const TAU = Math.PI * 2;   // một vòng tròn = 2 × 3,14


/* =============================================================
   2. ĐỌC DANH SÁCH HỌC SINH VÀ CÂU HỎI
   ============================================================= */

// Đọc biến "students" (trong students.js) và sửa cho gọn gàng
function docDanhSachHocSinh() {
  const goc = (typeof students !== 'undefined' && Array.isArray(students)) ? students : [];
  const ketQua = [];
  goc.forEach((hs, viTri) => {
    if (!hs || typeof hs !== 'object') return;
    // Bạn được đánh dấu vắng ("vang": true) thì không có bóng trong lồng
    if (hs.vang === true) return;
    const so = (hs.so === undefined || hs.so === null || String(hs.so).trim() === '')
      ? String(viTri + 1)
      : String(hs.so).trim();
    let ten = (hs.ten === undefined || hs.ten === null) ? '' : String(hs.ten).trim();
    if (ten === '') ten = 'Học sinh số ' + so;
    const mauHex = CONFIG.mauBong[ketQua.length % CONFIG.mauBong.length];
    ketQua.push({ so: so, ten: ten, mau: taoBoMau(mauHex) });
  });
  return ketQua;
}

// Đọc biến "questions" (trong questions.js). Bỏ qua câu bị trống.
// Có 2 loại câu hỏi:
//  - Tự luận:    { "cauHoi": ..., "dapAn": ... }
//  - Trắc nghiệm: { "cauHoi": ..., "luaChon": ["...", "...", "...", "..."], "dapAnDung": "B" }
function docDanhSachCauHoi() {
  const goc = (typeof questions !== 'undefined' && Array.isArray(questions)) ? questions : [];
  const ketQua = [];
  goc.forEach((c) => {
    if (!c) return;
    // Câu bị thầy cô tạm tắt ("tamTat": true) thì không đưa vào lượt quay
    if (typeof c === 'object' && (c.tamTat === true || c.disabled === true)) return;
    // Cho phép viết câu hỏi dạng chữ trơn, hoặc dạng { "cauHoi": ..., "dapAn": ... }
    const cauHoi = typeof c === 'string' ? c.trim() : String(c.cauHoi || c.question || '').trim();
    if (!cauHoi) return;
    const dap = typeof c === 'string' ? '' : String(c.dapAn || c.answer || '').trim();
    const mon = typeof c === 'string' ? '' : String(c.mon || '').trim();   // môn học (không bắt buộc)
    const cau = { cauHoi: cauHoi, dapAn: dap, mon: mon, luaChon: null, dapAnDung: -1 };

    // Câu trắc nghiệm: có từ 2 đến 4 phương án và chỉ rõ phương án đúng
    if (typeof c === 'object' && Array.isArray(c.luaChon)) {
      const luaChon = c.luaChon.map((x) => String(x).trim()).filter((x) => x !== '').slice(0, 4);
      const dung = viTriDapAnDung(c.dapAnDung, luaChon.length);
      if (luaChon.length >= 2 && dung >= 0) {
        cau.luaChon = luaChon;
        cau.dapAnDung = dung;
      }
    }
    ketQua.push(cau);
  });
  return ketQua;
}

// "A" → 0, "B" → 1, "C" → 2, "D" → 3 (cũng chấp nhận số 1, 2, 3, 4). Sai thì trả về −1.
function viTriDapAnDung(giaTri, soLuaChon) {
  let i = -1;
  if (typeof giaTri === 'number') i = giaTri - 1;
  else if (typeof giaTri === 'string') {
    const chu = giaTri.trim().toUpperCase();
    if (/^[A-D]$/.test(chu)) i = chu.charCodeAt(0) - 65;
    else if (/^[1-4]$/.test(chu)) i = Number(chu) - 1;
  }
  return (i >= 0 && i < soLuaChon) ? i : -1;
}

const CHU_CAI = ['A', 'B', 'C', 'D'];


/* =============================================================
   3. TRẠNG THÁI CỦA CHƯƠNG TRÌNH
   ============================================================= */

// Hình dáng lồng cầu (đơn vị: bán kính quả cầu = 1)
const HINH = {
  R: 1,               // bán kính lồng cầu
  R_TRONG: 0.93,      // bóng chỉ được đi trong bán kính này (không chạm nan)
  // Các vòng ngang màu vàng (tính bằng góc từ trục quay, vòng giữa là "xích đạo")
  cacVongNgang: [Math.PI / 6, Math.PI / 3, Math.PI / 2, 2 * Math.PI / 3, 5 * Math.PI / 6],
  sanY: -1.4,         // độ cao mặt bục
  sanRx: 1.55,        // bục hình bầu dục: rộng theo chiều ngang
  sanRz: 1.5,         // bục hình bầu dục: rộng theo chiều sâu
  sanDay: 0.16,       // độ dày bục
  chanX: 1.22,        // chân đỡ đặt ở hai bên
  chanZ: 0.75,        // chân xòe ra trước và sau
  mangRong: 0.3,      // nửa bề rộng máng
  mangDauY: -0.72,    // đầu máng (sát cửa lồng)
  mangDauZ: 0.62,
  mangCuoiY: -1.24,   // cuối máng (gần người xem)
  mangCuoiZ: 1.33,
  tayQuayX: 1.34,     // tay quay nằm ngoài chân đỡ bên phải
  tayQuayDai: 0.5     // độ dài tay quay
};

const HOC_SINH = docDanhSachHocSinh();
const CAU_HOI = docDanhSachCauHoi();
const banKinhBong = tinhBanKinhBong(HOC_SINH.length);

let lichSu = [];            // các lượt đã trúng, cũ → mới. Mỗi lượt: { hs, cau }
let bongs = [];             // các quả bóng còn trong lồng
let gocLong = 0;            // lồng đã xoay được bao nhiêu
let tocDoLong = 0;          // lồng đang xoay nhanh cỡ nào
let cheDo = 'nghi';         // 'nghi' | 'quay' | 'cho' | 'bongRa'
let tQuay = 0;              // đã quay được bao lâu
let thoiGianLuotNay = 0;    // lượt này quay bao lâu
let tocDoLuotNay = 0;       // lượt này quay nhanh cỡ nào
let tCho = 0;               // đã chờ sau khi dừng bao lâu
let tichLuyLachCach = 0;    // dùng để phát tiếng lạch cạch đều đặn
let vaChamManhNhat = 0;     // cú va chạm mạnh nhất trong khung hình (để phát tiếng)
let bongTrung = null;       // quả bóng trúng đang bay ra
let bongBay = null;         // kết quả cũ đang bay vào bảng lịch sử
let soChuaVaoBang = 0;      // số kết quả đã trúng nhưng chưa vào bảng
let caoThe = 0;             // chiều cao thẻ kết quả (để chừa chỗ cho quả bóng lớn)
let kieuCauHoi = 'tatCa';   // 'tatCa' | 'tuLuan' | 'tracNghiem' (chọn ở bảng lịch sử)
let cauDangHoi = null;      // câu hỏi đang hiện trên thẻ
let daTraLoi = false;       // câu trắc nghiệm đã được trả lời (hoặc đã xem đáp án) chưa
let dongHo = null;          // đồng hồ đếm ngược đang chạy: { con: số giây còn lại, tong, giayTruoc }
let thoiGian = 0;           // đồng hồ chung của chương trình

const manHinh = { rong: 0, cao: 0, dpr: 1 };
const vung = { trai: 0, tren: 0, rong: 100, cao: 100 };   // khoảng trống để vẽ lồng
const vungThe = { trai: 0, tren: 0, rong: 100, cao: 100 }; // khoảng trống cho thẻ kết quả và quả bóng lớn
const cam = { cosNgang: 1, sinNgang: 0, cosXuong: 1, sinXuong: 0, D: 4.6, tiLe: 100, tamX: 0, tamY: 0 };

// Hướng ánh sáng chiếu tới (từ trên, phía trước, bên trái)
const ANH_SANG = chuanHoa(-0.35, 0.75, 0.55);


/* =============================================================
   4. CÔNG CỤ TOÁN HỌC VÀ MÀU SẮC
   ============================================================= */

// Đi một đoạn từ a tới b (t = 0 ở a, t = 1 ở b)
function lerp(a, b, t) { return a + (b - a) * t; }

// Giữ số x nằm trong khoảng từ nhỏ tới lớn
function kep(x, nho, lon) { return Math.max(nho, Math.min(lon, x)); }

// Các kiểu chuyển động mượt
function emVaoRa(t) { return t < 0.5 ? 2 * t * t : 1 - Math.pow(-2 * t + 2, 2) / 2; }
function emRa(t) { return 1 - Math.pow(1 - t, 3); }
function nayRa(t) { const c = 1.6; return 1 + (c + 1) * Math.pow(t - 1, 3) + c * Math.pow(t - 1, 2); }

// Làm cho mũi tên có độ dài bằng 1
function chuanHoa(x, y, z) {
  const d = Math.hypot(x, y, z) || 1;
  return { x: x / d, y: y / d, z: z / d };
}

// "#ff8800" → [255, 136, 0]
function hexSangRgb(hex) {
  const n = parseInt(hex.slice(1), 16);
  return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
}

// Trộn hai màu
function tronMau(a, b, t) {
  return [lerp(a[0], b[0], t), lerp(a[1], b[1], t), lerp(a[2], b[2], t)];
}

// [255, 136, 0] → "rgba(255,136,0,1)"
function chuMau(c, doDuc) {
  return 'rgba(' + (c[0] | 0) + ',' + (c[1] | 0) + ',' + (c[2] | 0) + ',' + (doDuc === undefined ? 1 : doDuc) + ')';
}

// Từ một màu, tạo ra màu sáng hơn và tối hơn để tô bóng cho tròn
function taoBoMau(hex) {
  const g = hexSangRgb(hex);
  return {
    goc: chuMau(g),
    sang: chuMau(tronMau(g, [255, 255, 255], 0.35)),
    ratSang: chuMau(tronMau(g, [255, 255, 255], 0.8)),
    toi: chuMau(tronMau(g, [10, 0, 40], 0.5))
  };
}

// Tính bán kính bóng: càng nhiều bóng thì bóng càng nhỏ để vừa lồng
function tinhBanKinhBong(soBong) {
  if (soBong <= 0) return 0.15;
  const theTichLong = 4 / 3 * Math.PI * Math.pow(HINH.R_TRONG, 3);
  const r = Math.cbrt(theTichLong * 0.1 / (soBong * 4 / 3 * Math.PI));
  return kep(r, 0.05, 0.19);
}


/* =============================================================
   5. MÁY ẢNH: BIẾN ĐIỂM 3D (x, y, z) THÀNH ĐIỂM 2D TRÊN MÀN HÌNH
   x: sang phải (trục quay), y: lên trên, z: tiến về phía người xem
   ============================================================= */

function capNhatGocNhin() {
  const a = CONFIG.gocNhinNgang * Math.PI / 180;
  const b = CONFIG.gocNhinXuong * Math.PI / 180;
  cam.cosNgang = Math.cos(a); cam.sinNgang = Math.sin(a);
  cam.cosXuong = Math.cos(b); cam.sinXuong = Math.sin(b);
  cam.D = CONFIG.khoangCachMayAnh;
}

// Trả về: x, y trên màn hình; s = to hay nhỏ (gần thì to); z = gần hay xa
function chieu(x, y, z) {
  // Xoay sang ngang
  const x1 = x * cam.cosNgang + z * cam.sinNgang;
  const z1 = -x * cam.sinNgang + z * cam.cosNgang;
  // Nghiêng nhìn từ trên xuống
  const y2 = y * cam.cosXuong - z1 * cam.sinXuong;
  const z2 = y * cam.sinXuong + z1 * cam.cosXuong;
  // Vật ở xa thì nhỏ lại (phối cảnh)
  const s = cam.D / (cam.D - z2);
  return { x: cam.tamX + x1 * cam.tiLe * s, y: cam.tamY - y2 * cam.tiLe * s, s: s, z: z2 };
}

// Một mặt có hướng (nx, ny, nz) có đang quay về phía người xem không? (> 0 là có)
function huongVeNguoiXem(nx, ny, nz) {
  return ny * cam.sinXuong + (-nx * cam.sinNgang + nz * cam.cosNgang) * cam.cosXuong;
}


/* =============================================================
   6. BỐ CỤC MÀN HÌNH
   ============================================================= */

function tinhBoCuc() {
  const W = window.innerWidth;
  const H = window.innerHeight;
  const dpr = Math.min(window.devicePixelRatio || 1, 3);
  manHinh.rong = W; manHinh.cao = H; manHinh.dpr = dpr;

  // Canvas có nhiều điểm ảnh hơn trên màn hình nét (devicePixelRatio)
  [canvasLong, canvasHieuUng].forEach((c) => {
    c.width = Math.round(W * dpr);
    c.height = Math.round(H * dpr);
    c.style.width = W + 'px';
    c.style.height = H + 'px';
  });
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  ctxHU.setTransform(dpr, 0, 0, dpr, 0, 0);

  // Đặt bảng lịch sử vào giữa thanh tiêu đề và thanh nút
  const hep = window.matchMedia('(max-width: 820px)').matches;
  const dayTieuDe = thanhTren.getBoundingClientRect().bottom;
  const dinhNut = thanhDuoi.getBoundingClientRect().top;
  bangLichSu.style.top = hep ? 'auto' : (dayTieuDe + 10) + 'px';
  bangLichSu.style.bottom = (H - dinhNut + 8) + 'px';
  thongBaoHet.style.bottom = (H - dinhNut + 10) + 'px';

  // Tìm khoảng trống còn lại để vẽ lồng
  const bang = bangLichSu.getBoundingClientRect();
  let phai = W, day = dinhNut;
  if (hep) day = Math.min(dinhNut, bang.top - 6);
  else phai = bang.left - 10;
  vung.trai = 8;
  vung.tren = dayTieuDe + 4;
  vung.rong = Math.max(120, phai - vung.trai);
  vung.cao = Math.max(120, day - vung.tren);

  // Chỗ cho thẻ kết quả: màn hình rộng thì giống chỗ vẽ lồng;
  // điện thoại thì được dùng gần hết màn hình (đè lên bảng lịch sử) để chữ to hơn
  if (hep) {
    vungThe.trai = 6; vungThe.rong = W - 12;
    vungThe.tren = vung.tren; vungThe.cao = Math.max(120, dinhNut - 4 - vung.tren);
  } else {
    vungThe.trai = vung.trai; vungThe.rong = vung.rong;
    vungThe.tren = vung.tren; vungThe.cao = vung.cao;
  }

  // Đặt máy ảnh sao cho cả lồng, bục, máng nằm gọn trong khoảng trống
  cam.tiLe = 1; cam.tamX = 0; cam.tamY = 0;
  let minX = Infinity, maxX = -Infinity, minY = Infinity, maxY = -Infinity;
  diemMoc().forEach((p) => {
    const q = chieu(p[0], p[1], p[2]);
    minX = Math.min(minX, q.x); maxX = Math.max(maxX, q.x);
    minY = Math.min(minY, q.y); maxY = Math.max(maxY, q.y);
  });
  const tiLe = Math.min(vung.rong * 0.94 / (maxX - minX), vung.cao * 0.93 / (maxY - minY));
  cam.tiLe = tiLe;
  cam.tamX = vung.trai + vung.rong / 2 - (minX + maxX) / 2 * tiLe;
  cam.tamY = vung.tren + vung.cao * 0.52 - (minY + maxY) / 2 * tiLe;

  if (!theKetQua.classList.contains('an')) doCaoThe();
  datViTriTheKetQua();
}

// Những điểm ngoài cùng của cảnh, dùng để tính cho vừa màn hình
function diemMoc() {
  const ds = [];
  for (let i = 0; i < 24; i++) {
    const t = i / 24 * TAU;
    ds.push([HINH.sanRx * Math.cos(t), HINH.sanY - HINH.sanDay, HINH.sanRz * Math.sin(t)]);
    ds.push([HINH.R * Math.cos(t), HINH.R * Math.sin(t), 0]);
    ds.push([0, HINH.R * Math.sin(t), HINH.R * Math.cos(t)]);
  }
  const x = HINH.tayQuayX + 0.25, d = HINH.tayQuayDai;
  ds.push([x, d, 0], [x, -d, 0], [x, 0, d], [x, 0, -d]);
  return ds;
}

// Đo chiều cao thẻ kết quả (kể cả khi đang ẩn) để chừa chỗ cho quả bóng lớn
function doCaoThe() {
  const dangAn = theKetQua.classList.contains('an');
  if (dangAn) {
    theKetQua.style.visibility = 'hidden';
    theKetQua.classList.remove('an');
  }
  theKetQua.style.maxWidth = Math.min(1100, Math.max(240, vungThe.rong - 8)) + 'px';
  // Chỗ cao nhất thẻ được dùng: chừa chỗ cho quả bóng lớn ở trên
  const choToiDa = vungThe.cao - 22 - 30 * 2.15;
  // Thử cỡ chữ to nhất trước. Không vừa thì thu nhỏ Số và Tên trước,
  // vẫn không vừa mới thu nhỏ câu hỏi (để câu hỏi luôn to dễ đọc).
  let coLon = 1, coCauHoi = 1;
  datCoChuThe(coLon, coCauHoi);
  caoThe = theKetQua.offsetHeight;
  while (caoThe > choToiDa && (coLon > 0.55 || coCauHoi > 0.6)) {
    if (coLon > 0.55) coLon -= 0.05; else coCauHoi -= 0.05;
    datCoChuThe(coLon, coCauHoi);
    caoThe = theKetQua.offsetHeight;
  }
  if (dangAn) {
    theKetQua.classList.add('an');
    theKetQua.style.visibility = '';
  }
}

// Đặt cỡ chữ cho thẻ kết quả (1 = to nhất)
function datCoChuThe(coLon, coCauHoi) {
  theKetQua.style.setProperty('--co-chu-lon', coLon.toFixed(2));
  theKetQua.style.setProperty('--co-chu-cau-hoi', coCauHoi.toFixed(2));
}

// Vị trí và kích thước quả bóng trúng khi hiện kết quả (thẻ kết quả nằm ngay dưới)
function viTriBongLon() {
  const V = vungThe;
  const tren = V.tren + 6;
  const day = V.tren + V.cao - 6;
  const choThe = caoThe > 0 ? caoThe + 10 : 0;
  let rad = kep(Math.min(V.rong, V.cao) * 0.13, 40, 150);
  const conCho = day - tren - choThe;            // chỗ còn lại cho quả bóng
  if (rad * 2.15 > conCho) rad = Math.max(30, conCho / 2.15);
  let y = Math.max(V.tren + V.cao * 0.24, tren + rad);
  if (y + rad * 1.12 + choThe > day) y = Math.max(tren + rad, day - choThe - rad * 1.12);
  return { x: V.trai + V.rong / 2, y: y, rad: rad };
}

// Đặt thẻ kết quả ngay dưới quả bóng trúng
function datViTriTheKetQua() {
  if (theKetQua.classList.contains('an') || bongBay) return;
  const T = viTriBongLon();
  theKetQua.style.left = T.x + 'px';
  theKetQua.style.top = (T.y + T.rad * 1.12 + 8) + 'px';
}


/* =============================================================
   7. TẠO BÓNG VÀ VẬT LÝ
   ============================================================= */

// Bạn thứ i đã trúng chưa?
function daTrung(i) {
  return lichSu.some((l) => l.hs === i);
}

// Tạo bóng cho các bạn chưa trúng.
// roiTuTren = true: bóng rơi từ trên xuống cho vui mắt (khi làm lại).
function taoDanhSachBong(roiTuTren) {
  bongs = [];
  HOC_SINH.forEach((hs, i) => {
    if (daTrung(i)) return;
    const b = { hs: i, x: 0, y: 0, z: 0, vx: 0, vy: 0, vz: 0, quay: Math.random() * TAU };
    b.x = (Math.random() * 2 - 1) * 0.5;
    b.z = (Math.random() * 2 - 1) * 0.5;
    if (roiTuTren) {
      b.y = 0.1 + Math.random() * 0.45;
      b.vy = Math.random() * 2;
    } else {
      b.y = -0.3 - Math.random() * 0.4;
    }
    giuTrongLong(b);
    bongs.push(b);
  });
}

// Cho bóng rơi một lúc thật nhanh (không vẽ) để bóng nằm gọn ở đáy lồng
function choBongNamYen() {
  const tocDoCu = tocDoLong;
  tocDoLong = 0;
  for (let i = 0; i < 720; i++) buocVatLy(1 / 240);
  tocDoLong = tocDoCu;
  vaChamManhNhat = 0;
}

// Một bước vật lý rất ngắn (khoảng 1/240 giây)
function buocVatLy(h) {
  const r = banKinhBong;
  const rMax = HINH.R_TRONG - r;
  const dangQuay = Math.abs(tocDoLong) > 0.05;
  const giam = Math.max(0, 1 - (dangQuay ? CONFIG.lucCanKhongKhi : CONFIG.lucCanKhiNghi) * h);

  // 1) Trọng lực kéo xuống, bóng di chuyển, chạm thành lồng
  for (let i = 0; i < bongs.length; i++) {
    const b = bongs[i];
    b.vy -= CONFIG.trongLuc * h;
    b.vx *= giam; b.vy *= giam; b.vz *= giam;
    b.x += b.vx * h; b.y += b.vy * h; b.z += b.vz * h;
    vaChamThanhLong(b, h, rMax);
    b.quay += (b.vy + b.vz * 0.6) * h * 1.6;
  }

  // 2) Bóng chạm bóng
  const n = bongs.length;
  for (let i = 0; i < n; i++) {
    for (let j = i + 1; j < n; j++) vaChamGiuaHaiBong(bongs[i], bongs[j], r);
  }

  // 3) Chắc chắn 100% không quả nào ra ngoài lồng
  for (let i = 0; i < n; i++) giuTrongLong(bongs[i]);
}

// Bóng chạm vào thành lồng cầu: bật lại và bị lồng kéo chạy theo
function vaChamThanhLong(b, h, rMax) {
  const d = Math.hypot(b.x, b.y, b.z);
  if (d <= rMax || d < 1e-9) return;
  const nx = b.x / d, ny = b.y / d, nz = b.z / d;   // hướng từ tâm ra thành lồng
  b.x = nx * rMax; b.y = ny * rMax; b.z = nz * rMax; // đẩy bóng vào lại trong lồng

  const vn = b.vx * nx + b.vy * ny + b.vz * nz;
  if (vn > 0) {                                      // đang lao ra ngoài → bật lại
    const k = (1 + CONFIG.doNayThanhLong) * vn;
    b.vx -= k * nx; b.vy -= k * ny; b.vz -= k * nz;
    if (vn > 2.2) ghiNhanVaCham(vn);
  }

  // Thành lồng quay quanh trục ngang x, tại chỗ này nó chạy với vận tốc (0, ω·z, −ω·y)
  const wy = tocDoLong * b.z, wz = -tocDoLong * b.y;
  // Phần trượt dọc theo thành lồng (bỏ phần hướng vào/ra)
  let rx = b.vx, ry = b.vy - wy, rz = b.vz - wz;
  const rn = rx * nx + ry * ny + rz * nz;
  rx -= rn * nx; ry -= rn * ny; rz -= rn * nz;
  const keo = Math.min(1, CONFIG.doBamThanhLong * h);
  b.vx -= rx * keo; b.vy -= ry * keo; b.vz -= rz * keo;

  // Nan lồng thỉnh thoảng hất bóng vào giữa
  const manh = Math.abs(tocDoLong) / CONFIG.tocDoQuayToiDa;
  if (manh > 0.3 && Math.random() < CONFIG.soLanHatMoiGiay * h) {
    const luc = CONFIG.lucHat * manh * (0.5 + Math.random());
    b.vx -= nx * luc + (Math.random() - 0.5) * luc * 0.6;
    b.vy -= ny * luc; b.vz -= nz * luc;
  }
}

// Hai quả bóng chạm nhau: đẩy ra xa và nảy đi
function vaChamGiuaHaiBong(a, b, r) {
  let dx = b.x - a.x, dy = b.y - a.y, dz = b.z - a.z;
  let d2 = dx * dx + dy * dy + dz * dz;
  const dMin = 2 * r;
  if (d2 >= dMin * dMin) return;             // chưa chạm
  if (d2 < 1e-10) {                          // trùng chỗ → đẩy nhẹ cho tách ra
    dx = (Math.random() - 0.5) * 1e-3; dy = 1e-3; dz = 0;
    d2 = dx * dx + dy * dy;
  }
  const d = Math.sqrt(d2);
  const nx = dx / d, ny = dy / d, nz = dz / d;
  const chong = (dMin - d) * 0.5;            // hai quả lấn vào nhau bao nhiêu
  a.x -= nx * chong; a.y -= ny * chong; a.z -= nz * chong;
  b.x += nx * chong; b.y += ny * chong; b.z += nz * chong;
  const vn = (b.vx - a.vx) * nx + (b.vy - a.vy) * ny + (b.vz - a.vz) * nz;
  if (vn < 0) {                              // đang lao vào nhau → nảy ra
    const e = vn < -0.6 ? CONFIG.doNayGiuaBong : 0;
    const j = -(1 + e) * vn * 0.5;
    a.vx -= j * nx; a.vy -= j * ny; a.vz -= j * nz;
    b.vx += j * nx; b.vy += j * ny; b.vz += j * nz;
    if (-vn > 2.2) ghiNhanVaCham(-vn);
  }
}

// Kẹp bóng lại trong lồng cầu. Đây là "hàng rào cuối cùng": dù có gì xảy ra,
// bóng cũng không bao giờ ra ngoài.
function giuTrongLong(b) {
  const rMax = HINH.R_TRONG - banKinhBong;
  if (!isFinite(b.x + b.y + b.z + b.vx + b.vy + b.vz)) {
    b.x = 0; b.y = -rMax * 0.8; b.z = 0; b.vx = 0; b.vy = 0; b.vz = 0;
  }
  const d = Math.hypot(b.x, b.y, b.z);
  if (d > rMax) { const k = rMax / d; b.x *= k; b.y *= k; b.z *= k; }
}

function ghiNhanVaCham(tocDo) {
  if (tocDo > vaChamManhNhat) vaChamManhNhat = tocDo;
}


/* =============================================================
   8. QUAY LỒNG, CHỌN BÓNG TRÚNG, CHỌN CÂU HỎI
   ============================================================= */

// Bấm nút QUAY hoặc phím cách (Space)
function batDauQuay() {
  if (cheDo !== 'nghi' || bongs.length === 0 || !hopThoai.classList.contains('an')) return;
  khoiTaoAmThanh();
  // Kết quả cũ (nếu có) thu nhỏ và bay vào bảng lịch sử
  if (bongTrung && bongTrung.giaiDoan === 'xong') guiKetQuaVaoBang();
  dungMp3('chucMung');
  cheDo = 'quay';
  tQuay = 0;
  thoiGianLuotNay = CONFIG.thoiGianQuay + Math.random() * CONFIG.thoiGianQuayThem;
  tocDoLuotNay = CONFIG.tocDoQuayToiDa * (0.9 + Math.random() * 0.2);
  capNhatNut();
}

// Tốc độ lồng theo thời gian: tăng dần → giữ đều → chậm dần → dừng
function tinhTocDoQuay(t) {
  const T = thoiGianLuotNay, a = CONFIG.thoiGianTangToc, d = CONFIG.thoiGianGiamToc;
  const w = tocDoLuotNay;
  if (t < a) return w * (0.5 - 0.5 * Math.cos(Math.PI * t / a));
  if (t < T - d) return w;
  if (t < T) return w * (0.5 + 0.5 * Math.cos(Math.PI * (t - (T - d)) / d));
  return 0;
}

// Điều khiển lồng mỗi khung hình
function dieuKhienLong(dt) {
  if (cheDo === 'quay') {
    tQuay += dt;
    tocDoLong = tinhTocDoQuay(tQuay);
    if (tQuay >= thoiGianLuotNay) {        // hết giờ quay → dừng lại
      tocDoLong = 0;
      cheDo = 'cho';
      tCho = 0;
    }
  } else if (cheDo === 'cho') {
    tCho += dt;
    if (tCho >= CONFIG.thoiGianChoSauKhiDung) chonBongTrung();
  } else {
    tocDoLong = 0;
  }
  gocLong += tocDoLong * dt;

  // Có file quay.mp3 thì dùng file đó, không thì tự tạo tiếng lạch cạch
  const dungFileMp3 = capNhatMp3Quay();
  if (tocDoLong > 0.05) {
    tichLuyLachCach += tocDoLong * dt;
    const buoc = TAU / CONFIG.soNanDoc;    // mỗi khi một nan đi qua → kêu "cạch"
    while (tichLuyLachCach >= buoc) {
      tichLuyLachCach -= buoc;
      if (!dungFileMp3) tiengLachCach();
    }
  }
}

// Lồng đã dừng: bốc thăm ngẫu nhiên một quả trong số bóng còn lại
function chonBongTrung() {
  if (bongs.length === 0) { cheDo = 'nghi'; capNhatNut(); return; }
  const viTri = Math.floor(Math.random() * bongs.length);
  const b = bongs.splice(viTri, 1)[0];        // lấy quả bóng ra khỏi lồng

  const cau = chonCauHoi();                   // bốc luôn câu hỏi cho bạn này
  lichSu.push({ hs: b.hs, cau: cau });        // ghi vào lịch sử ngay
  soChuaVaoBang++;
  luuLichSu();
  capNhatDem();
  chuanBiThe(b.hs, cau);                      // điền sẵn thẻ kết quả (chưa hiện)

  bongTrung = {
    hs: b.hs, giaiDoan: 1, t: 0,
    tu: { x: b.x, y: b.y, z: b.z },
    x: b.x, y: b.y, z: b.z,
    quay: b.quay
  };
  cheDo = 'bongRa';
  capNhatNut();
}

// Lọc câu hỏi theo kiểu thầy cô chọn (cả hai / tự luận / trắc nghiệm)
function locTheoKieu(ds) {
  if (kieuCauHoi === 'tuLuan') return ds.filter((c) => !c.luaChon);
  if (kieuCauHoi === 'tracNghiem') return ds.filter((c) => !!c.luaChon);
  return ds;
}

// Bốc ngẫu nhiên một câu hỏi chưa hỏi. Hỏi hết rồi thì dùng lại câu cũ.
function chonCauHoi() {
  if (CAU_HOI.length === 0) return null;
  let nguon = locTheoKieu(CAU_HOI);
  if (nguon.length === 0) nguon = CAU_HOI;        // không có câu loại đó thì lấy loại còn lại
  const daHoi = lichSu.map((l) => (l.cau ? l.cau.cauHoi : null)).filter((c) => c !== null);
  let conLai = nguon.filter((c) => daHoi.indexOf(c.cauHoi) < 0);
  if (conLai.length === 0) {
    // Đã hỏi hết: dùng lại, chỉ tránh câu vừa hỏi ở lượt trước
    const cauVuaHoi = daHoi[daHoi.length - 1];
    conLai = nguon.filter((c) => c.cauHoi !== cauVuaHoi);
    if (conLai.length === 0) conLai = nguon.slice();
  }
  const c = conLai[Math.floor(Math.random() * conLai.length)];
  return { cauHoi: c.cauHoi, dapAn: c.dapAn, mon: c.mon, luaChon: c.luaChon, dapAnDung: c.dapAnDung, kq: '' };
}

// Điền số, tên, câu hỏi vào thẻ kết quả rồi đo chiều cao của thẻ
function chuanBiThe(hsIndex, cau) {
  const hs = HOC_SINH[hsIndex];
  soTrung.textContent = 'Số ' + hs.so;
  tenTrung.textContent = hs.ten;
  cauDangHoi = cau;
  daTraLoi = false;
  cacLuaChon.textContent = '';
  dapAn.classList.remove('hien-dap-an', 'tra-loi-dung', 'tra-loi-sai', 'an');
  nutDapAn.disabled = false;
  nutDapAn.textContent = '👀 Xem đáp án';
  datLaiDongHo(cau);

  if (!cau) {
    khungCauHoi.classList.add('an');
    nutDapAn.classList.add('an');
  } else if (cau.luaChon) {
    // ----- Câu trắc nghiệm: vẽ các nút A, B, C, D -----
    khungCauHoi.classList.remove('an');
    nhanCauHoi.textContent = '📝 Trắc nghiệm' + (cau.mon ? ' ' + cau.mon : '') + ': chọn 1 đáp án đúng';
    noiDungCauHoi.textContent = cau.cauHoi;
    cacLuaChon.classList.remove('an');
    // Phương án nào cũng ngắn thì xếp 2 cột cho gọn (điện thoại vẫn đủ chỗ)
    const daiNhat = Math.max.apply(null, cau.luaChon.map((x) => x.length));
    cacLuaChon.classList.toggle('hai-cot', daiNhat <= 10);
    cau.luaChon.forEach((noiDung, i) => {
      const nut = document.createElement('button');
      nut.type = 'button';
      nut.className = 'lua-chon';
      const chu = document.createElement('span');
      chu.className = 'chu-cai';
      chu.textContent = CHU_CAI[i];
      const chuNoiDung = document.createElement('span');
      chuNoiDung.textContent = noiDung;
      nut.appendChild(chu);
      nut.appendChild(chuNoiDung);
      nut.addEventListener('click', () => { nut.blur(); chonDapAn(i); });
      cacLuaChon.appendChild(nut);
    });
    // Dòng này để hiện "Chính xác!" hoặc "Chưa đúng" (luôn giữ chỗ để thẻ không nhảy)
    dapAn.textContent = '🎉 Chính xác!';
    nutDapAn.classList.remove('an');
  } else {
    // ----- Câu tự luận -----
    khungCauHoi.classList.remove('an');
    cacLuaChon.classList.add('an');
    nhanCauHoi.textContent = cau.mon ? '❓ Câu hỏi ' + cau.mon + ' dành cho bạn' : '❓ Câu hỏi dành cho bạn';
    noiDungCauHoi.textContent = cau.cauHoi;
    // Có đếm giờ thì luôn giữ chỗ cho dòng đáp án (để hiện "Hết giờ!" mà thẻ không nhảy)
    const coDemGio = thoiGianTraLoi() > 0;
    dapAn.textContent = cau.dapAn ? '💡 Đáp án: ' + cau.dapAn : (coDemGio ? '⏰ Hết giờ!' : '');
    dapAn.classList.toggle('an', !cau.dapAn && !coDemGio);
    nutDapAn.classList.toggle('an', !cau.dapAn);
  }
  doCaoThe();
}

// Học sinh chọn một phương án trắc nghiệm (bấm chuột, hoặc phím A/B/C/D, 1/2/3/4)
function chonDapAn(i) {
  const cau = cauDangHoi;
  if (!cau || !cau.luaChon || daTraLoi) return;
  if (!bongTrung || bongTrung.giaiDoan !== 'xong') return;   // chỉ khi thẻ đang hiện
  if (i < 0 || i >= cau.luaChon.length) return;
  daTraLoi = true;
  dungDongHo();
  const dung = i === cau.dapAnDung;
  danhDauLuaChon(i);
  dapAn.classList.add('hien-dap-an', dung ? 'tra-loi-dung' : 'tra-loi-sai');
  dapAn.textContent = dung
    ? '🎉 Chính xác! Đáp án đúng là ' + CHU_CAI[cau.dapAnDung] + '.'
    : '😅 Chưa đúng rồi! Đáp án đúng là ' + CHU_CAI[cau.dapAnDung] + ': ' + cau.luaChon[cau.dapAnDung];
  nutDapAn.disabled = true;
  if (dung) { tiengDung(); batDauPhaoHoa(); } else { tiengSai(); }

  // Ghi kết quả đúng / sai vào lịch sử của lượt này
  cau.kq = dung ? 'dung' : 'sai';
  luuLichSu();
}

// Tô màu các nút: đáp án đúng màu xanh, nút chọn sai màu đỏ, còn lại mờ đi
function danhDauLuaChon(daChon) {
  const cacNut = cacLuaChon.querySelectorAll('.lua-chon');
  cacNut.forEach((nut, k) => {
    nut.disabled = true;
    if (k === cauDangHoi.dapAnDung) nut.classList.add('dung');
    else if (k === daChon) nut.classList.add('sai');
    else nut.classList.add('mo');
  });
}

// Bóng trúng: ra cửa → lăn xuống máng → bay lên phóng to
function capNhatBongTrung(dt) {
  const bt = bongTrung;
  if (!bt) return;
  bt.t += dt;
  const r = banKinhBong;

  if (bt.giaiDoan === 1) {                  // Giai đoạn 1: đi ra cửa lồng
    const u = Math.min(1, bt.t / CONFIG.thoiGianBongRaCua);
    const e = emVaoRa(u);
    bt.x = lerp(bt.tu.x, 0, e);
    bt.y = lerp(bt.tu.y, HINH.mangDauY + r, e);
    bt.z = lerp(bt.tu.z, HINH.mangDauZ, e);
    bt.quay += dt * 3;
    if (u >= 1) { bt.giaiDoan = 2; bt.t = 0; tiengCach(0.5, 1500); }
  } else if (bt.giaiDoan === 2) {           // Giai đoạn 2: lăn xuống máng, nhanh dần
    const u = Math.min(1, bt.t / CONFIG.thoiGianLanXuongMang);
    const e = u * u;
    bt.x = 0;
    bt.y = lerp(HINH.mangDauY + r, HINH.mangCuoiY + r, e);
    bt.z = lerp(HINH.mangDauZ, HINH.mangCuoiZ - r, e);
    bt.quay += dt * (4 + 10 * u);
    if (u >= 1) {
      bt.giaiDoan = 3; bt.t = 0;
      const p = chieu(bt.x, bt.y, bt.z);
      bt.sx0 = p.x; bt.sy0 = p.y;
      bt.rad0 = r * cam.tiLe * p.s;
      bt.goc0 = Math.sin(bt.quay) * 0.6;
      tiengCach(0.6, 900);
    }
  } else if (bt.giaiDoan === 3) {           // Giai đoạn 3: bay lên, phóng to, xoay để lộ số
    if (bt.t >= CONFIG.thoiGianBayLen) {
      bt.giaiDoan = 'xong';
      bt.t = 0;
      hienKetQua();
    }
  }
}

// Hiện số, tên và câu hỏi thật to, bắn pháo hoa, phát tiếng chúc mừng
function hienKetQua() {
  theKetQua.style.transition = '';
  theKetQua.style.transform = '';
  theKetQua.style.opacity = '';
  theKetQua.classList.remove('an', 'hien');
  void theKetQua.offsetWidth;               // mẹo nhỏ để hiệu ứng hiện ra chạy lại
  theKetQua.classList.add('hien');
  doCaoThe();
  datViTriTheKetQua();
  batDauPhaoHoa();
  tiengTing();
  phatMp3('chucMung', false);
  cheDo = 'nghi';
  capNhatNut();
  batDauDongHo();
}

/* ---------- Đồng hồ đếm ngược thời gian trả lời ---------- */

// Chuẩn bị đồng hồ trên thẻ (chưa chạy): hiện đủ số giây, thanh đầy
function datLaiDongHo(cau) {
  dongHo = null;
  const tong = thoiGianTraLoi();
  dongHoEl.classList.remove('gap', 'het-gio', 'da-dung');
  dongHoEl.classList.toggle('an', !cau || tong <= 0);
  dongHoSo.textContent = tong;
  dongHoThanh.style.transform = 'scaleX(1)';
}

// Bắt đầu đếm khi thẻ kết quả đã hiện hẳn
function batDauDongHo() {
  const tong = thoiGianTraLoi();
  if (!cauDangHoi || daTraLoi || tong <= 0) return;
  dongHo = { con: tong, tong: tong, giayTruoc: tong };
}

// Dừng đồng hồ (đã trả lời, đã xem đáp án). Giữ nguyên số giây đang hiện.
function dungDongHo() {
  if (!dongHo) return;
  dongHo = null;
  dongHoEl.classList.remove('gap');
  dongHoEl.classList.add('da-dung');
}

function capNhatDongHo(dt) {
  if (!dongHo) return;
  // Thẻ đã đóng / đã bay vào bảng / đã trả lời → thôi đếm
  if (!cauDangHoi || daTraLoi || !bongTrung || bongTrung.giaiDoan !== 'xong') { dongHo = null; return; }
  dongHo.con = Math.max(0, dongHo.con - dt);
  const giay = Math.ceil(dongHo.con);
  dongHoSo.textContent = giay;
  dongHoThanh.style.transform = 'scaleX(' + (dongHo.con / dongHo.tong).toFixed(4) + ')';
  const gap = dongHo.con <= CONFIG.soGiayCanhBao;
  dongHoEl.classList.toggle('gap', gap);
  if (giay < dongHo.giayTruoc) {
    dongHo.giayTruoc = giay;
    if (gap && giay > 0) tiengCach(0.35, 1800);       // tích tắc mấy giây cuối
  }
  if (dongHo.con <= 0) hetGio();
}

// Hết giờ mà chưa trả lời → tính là SAI và hiện đáp án đúng
function hetGio() {
  const cau = cauDangHoi;
  dongHo = null;
  if (!cau || daTraLoi) return;
  daTraLoi = true;
  dongHoEl.classList.remove('gap');
  dongHoEl.classList.add('het-gio');
  dongHoSo.textContent = '0';
  if (cau.luaChon) {
    danhDauLuaChon(-1);
    dapAn.textContent = '⏰ Hết giờ! Đáp án đúng là ' + CHU_CAI[cau.dapAnDung] + ': ' + cau.luaChon[cau.dapAnDung];
  } else {
    dapAn.classList.remove('an');
    dapAn.textContent = '⏰ Hết giờ!' + (cau.dapAn ? ' Đáp án: ' + cau.dapAn : '');
    nutDapAn.textContent = '👀 Xem đáp án';
  }
  dapAn.classList.add('hien-dap-an', 'tra-loi-sai');
  nutDapAn.disabled = true;
  tiengSai();
  cau.kq = 'sai';
  luuLichSu();
  doCaoThe();
  datViTriTheKetQua();
}

// Bấm "Xem đáp án"
//  - Câu tự luận: hiện / ẩn đáp án
//  - Câu trắc nghiệm: tô xanh phương án đúng (không tính đúng hay sai cho bạn)
function batTatDapAn() {
  if (daTraLoi && cauDangHoi && !cauDangHoi.luaChon) return;   // tự luận đã hết giờ
  dungDongHo();                             // thầy cô đã xem đáp án → không tính giờ nữa
  if (cauDangHoi && cauDangHoi.luaChon) {
    if (daTraLoi) return;
    daTraLoi = true;
    danhDauLuaChon(-1);
    dapAn.classList.add('hien-dap-an', 'tra-loi-dung');
    dapAn.textContent = '💡 Đáp án đúng là ' + CHU_CAI[cauDangHoi.dapAnDung] + ': ' + cauDangHoi.luaChon[cauDangHoi.dapAnDung];
    nutDapAn.disabled = true;
    return;
  }
  const dangHien = dapAn.classList.toggle('hien-dap-an');
  nutDapAn.textContent = dangHien ? '🙈 Ẩn đáp án' : '👀 Xem đáp án';
}

// Kết quả đang hiện thu nhỏ lại và bay vào bảng lịch sử
function guiKetQuaVaoBang() {
  if (!bongTrung || bongTrung.giaiDoan !== 'xong' || bongBay) return;
  const T = viTriBongLon();
  const dich = dsLichSu.getBoundingClientRect();
  const tx = dich.left + 32, ty = dich.top + 28;

  bongBay = {
    hs: bongTrung.hs, t: 0,
    x0: T.x, y0: T.y, rad0: T.rad, tx: tx, ty: ty
  };
  bongTrung = null;

  // Thẻ chữ cũng bay theo
  const the = theKetQua.getBoundingClientRect();
  const dx = tx - (the.left + the.width / 2);
  const dy = ty - (the.top + the.height / 2);
  theKetQua.classList.remove('hien');
  void theKetQua.offsetWidth;
  theKetQua.style.transition = 'transform ' + CONFIG.thoiGianBayVaoBang + 's cubic-bezier(.55,0,.8,.45), opacity ' + CONFIG.thoiGianBayVaoBang + 's';
  theKetQua.style.transform = 'translate(-50%, 0) translate(' + dx + 'px, ' + dy + 'px) scale(0.12)';
  theKetQua.style.opacity = '0';
  capNhatNut();
}

function capNhatBongBay(dt) {
  if (!bongBay) return;
  bongBay.t += dt;
  if (bongBay.t >= CONFIG.thoiGianBayVaoBang) {
    bongBay = null;
    soChuaVaoBang = Math.max(0, soChuaVaoBang - 1);
    anTheKetQua();
    veLichSu(true);
    capNhatNut();
    tiengCach(0.4, 2600);
  }
}

function anTheKetQua() {
  cauDangHoi = null;
  theKetQua.classList.add('an');
  theKetQua.classList.remove('hien');
  theKetQua.style.transition = '';
  theKetQua.style.transform = '';
  theKetQua.style.opacity = '';
}


/* =============================================================
   9. VẼ SÀN, CHÂN ĐỠ, LỒNG CẦU, MÁNG, BÓNG
   ============================================================= */

// Vẽ một hình nhiều cạnh từ danh sách điểm
function duongDaGiac(c, ds) {
  c.beginPath();
  c.moveTo(ds[0].x, ds[0].y);
  for (let i = 1; i < ds.length; i++) c.lineTo(ds[i].x, ds[i].y);
  c.closePath();
}

// Màu kim loại: k gần 0 là tối, gần 1 là sáng
const RGB_THANH_TOI = hexSangRgb(CONFIG.mauThanhToi);
const RGB_THANH_SANG = hexSangRgb(CONFIG.mauThanhSang);
const RGB_VANH_TOI = hexSangRgb(CONFIG.mauVanhToi);
const RGB_VANH_SANG = hexSangRgb(CONFIG.mauVanhSang);
const RGB_CHAN = hexSangRgb(CONFIG.mauChanDe);

function mauKimLoai(toi, sang, k) { return chuMau(tronMau(toi, sang, kep(k, 0, 1))); }

// Độ sáng của mặt có hướng (nx, ny, nz) dưới ánh đèn
function doSangMat(nx, ny, nz) {
  const d = ANH_SANG.x * nx + ANH_SANG.y * ny + ANH_SANG.z * nz;
  return 0.22 + 0.78 * Math.pow(Math.max(0, d), 0.8);
}

// Bục tròn bên dưới lồng (có độ dày nên trông nổi khối)
function veSan(c) {
  const tren = [], duoi = [];
  for (let i = 0; i < 48; i++) {
    const t = i / 48 * TAU;
    const x = HINH.sanRx * Math.cos(t), z = HINH.sanRz * Math.sin(t);
    tren.push(chieu(x, HINH.sanY, z));
    duoi.push(chieu(x, HINH.sanY - HINH.sanDay, z));
  }
  duongDaGiac(c, duoi);
  c.fillStyle = CONFIG.mauSanToi;
  c.fill();
  const tam = chieu(0, HINH.sanY, 0.4);
  const g = c.createRadialGradient(tam.x, tam.y, 0, tam.x, tam.y, cam.tiLe * 2.2);
  g.addColorStop(0, '#ffffff');
  g.addColorStop(1, CONFIG.mauSanSang);
  duongDaGiac(c, tren);
  c.fillStyle = g;
  c.fill();
  c.lineWidth = 2;
  c.strokeStyle = 'rgba(255,255,255,0.9)';
  c.stroke();
}

// Bóng đổ mờ của lồng trên mặt bục
function veBongDoSan(c) {
  const tam = chieu(0, HINH.sanY, 0);
  const trai = chieu(-1.15, HINH.sanY, 0), phai = chieu(1.15, HINH.sanY, 0);
  const truoc = chieu(0, HINH.sanY, 0.95), sau = chieu(0, HINH.sanY, -0.95);
  const rx = Math.max(1, Math.hypot(phai.x - trai.x, phai.y - trai.y) / 2);
  const ry = Math.max(1, Math.abs(truoc.y - sau.y) / 2);
  const goc = Math.atan2(phai.y - trai.y, phai.x - trai.x);
  c.save();
  c.translate(tam.x, tam.y);
  c.rotate(goc);
  c.scale(1, ry / rx);
  const g = c.createRadialGradient(0, 0, 0, 0, 0, rx);
  g.addColorStop(0, 'rgba(50,20,110,0.42)');
  g.addColorStop(0.6, 'rgba(50,20,110,0.2)');
  g.addColorStop(1, 'rgba(50,20,110,0)');
  c.fillStyle = g;
  c.beginPath();
  c.arc(0, 0, rx, 0, TAU);
  c.fill();
  c.restore();
}

// Vẽ một đoạn thẳng kim loại có vệt sáng
function veThanh(c, a, b, mau, rong, vetSang) {
  c.strokeStyle = mau;
  c.lineWidth = rong;
  c.beginPath(); c.moveTo(a.x, a.y); c.lineTo(b.x, b.y); c.stroke();
  if (vetSang > 0) {
    c.strokeStyle = 'rgba(255,255,255,' + vetSang.toFixed(3) + ')';
    c.lineWidth = rong * 0.32;
    c.beginPath();
    c.moveTo(a.x - rong * 0.15, a.y - rong * 0.2);
    c.lineTo(b.x - rong * 0.15, b.y - rong * 0.2);
    c.stroke();
  }
}

// Chân đỡ ở một đầu lồng (dau = 1 bên phải, −1 bên trái), kèm trục và tay quay
function veChanDe(c, dau) {
  const x = dau * HINH.chanX;
  const truc = chieu(x, 0, 0);
  const chanSau = chieu(x, HINH.sanY, -HINH.chanZ);
  const chanTruoc = chieu(x, HINH.sanY, HINH.chanZ);
  const de1 = chieu(x, HINH.sanY, -HINH.chanZ - 0.12);
  const de2 = chieu(x, HINH.sanY, HINH.chanZ + 0.12);
  const ngang1 = chieu(x, HINH.sanY * 0.55, -HINH.chanZ * 0.55), ngang2 = chieu(x, HINH.sanY * 0.55, HINH.chanZ * 0.55);
  const rong = cam.tiLe * 0.075 * truc.s;

  c.lineCap = 'round';
  // Trục nối từ đỉnh quả cầu ra chân đỡ (bên phải dài hơn để gắn tay quay)
  const dauLong = chieu(dau * HINH.R, 0, 0);
  const cuoiTruc = chieu(dau > 0 ? HINH.tayQuayX : -(HINH.chanX + 0.06), 0, 0);
  veThanh(c, dauLong, cuoiTruc, '#59606e', rong * 0.8, 0.5);
  // Đế, chân sau, thanh ngang, chân trước
  veThanh(c, de1, de2, chuMau(tronMau(RGB_CHAN, [60, 0, 20], 0.45)), rong * 0.9, 0);
  veThanh(c, truc, chanSau, chuMau(tronMau(RGB_CHAN, [40, 0, 20], 0.35)), rong, 0.15);
  veThanh(c, ngang1, ngang2, chuMau(tronMau(RGB_CHAN, [40, 0, 20], 0.2)), rong * 0.6, 0.2);
  veThanh(c, truc, chanTruoc, CONFIG.mauChanDe, rong, 0.45);
  // Ổ trục tròn màu vàng
  c.beginPath();
  c.arc(truc.x, truc.y, rong * 0.9, 0, TAU);
  c.fillStyle = mauKimLoai(RGB_VANH_TOI, RGB_VANH_SANG, 0.8);
  c.fill();

  if (dau > 0) veTayQuay(c);
}

// Tay quay bên hông, xoay cùng lồng
function veTayQuay(c) {
  const x = HINH.tayQuayX;
  const y = HINH.tayQuayDai * Math.sin(gocLong), z = HINH.tayQuayDai * Math.cos(gocLong);
  const goc1 = chieu(x, 0, 0);
  const dauTay = chieu(x, y, z);
  const nam = chieu(x + 0.24, y, z);
  const rong = cam.tiLe * 0.07 * goc1.s;
  c.lineCap = 'round';
  veThanh(c, goc1, dauTay, mauKimLoai(RGB_THANH_TOI, RGB_THANH_SANG, 0.65), rong, 0.6);
  veThanh(c, dauTay, nam, '#d62839', rong * 1.6, 0.5);
  c.beginPath();
  c.arc(goc1.x, goc1.y, rong * 0.9, 0, TAU);
  c.fillStyle = mauKimLoai(RGB_VANH_TOI, RGB_VANH_SANG, 0.9);
  c.fill();
}

// Tạo danh sách các nét của lồng cầu, chia thành phần phía sau và phía trước
function taoCacNetLong() {
  const sau = [], truoc = [];
  const R = HINH.R, tl = cam.tiLe;
  const dauGan = chieu(R, 0, 0).z > chieu(-R, 0, 0).z ? 1 : -1;   // đầu nào gần người xem

  // Thêm một đoạn nan: tự biết nó ở phía trước hay phía sau, sáng hay tối
  function themDoan(p, q, nx, ny, nz, toi, sang, doDay) {
    const phiaTruoc = huongVeNguoiXem(nx, ny, nz) > 0;
    const k = doSangMat(nx, ny, nz) * (phiaTruoc ? 1 : 0.62);
    (phiaTruoc ? truoc : sau).push({
      loai: 'net', a: p, b: q,
      mau: mauKimLoai(toi, sang, k),
      rong: tl * doDay * (p.s + q.s) / 2 * (phiaTruoc ? 1 : 0.85),
      sang: phiaTruoc ? 0.6 * k : 0.08
    });
  }

  // Các vòng ngang màu vàng (vòng tròn nên không cần xoay theo lồng)
  const SO_DOAN = 40;
  HINH.cacVongNgang.forEach((t) => {
    const x = R * Math.cos(t), rr = R * Math.sin(t);
    const laVongGiua = Math.abs(t - Math.PI / 2) < 1e-6;
    const diem = [];
    for (let j = 0; j <= SO_DOAN; j++) {
      const a = j / SO_DOAN * TAU;
      diem.push(chieu(x, rr * Math.sin(a), rr * Math.cos(a)));
    }
    for (let j = 0; j < SO_DOAN; j++) {
      const a = (j + 0.5) / SO_DOAN * TAU;
      themDoan(diem[j], diem[j + 1], Math.cos(t), Math.sin(t) * Math.sin(a), Math.sin(t) * Math.cos(a),
        RGB_VANH_TOI, RGB_VANH_SANG, laVongGiua ? 0.05 : 0.03);
    }
  });

  // Các nan dọc đi từ đầu trục này sang đầu trục kia (xoay theo lồng)
  const SO_KHUC = 18;
  for (let i = 0; i < CONFIG.soNanDoc; i++) {
    const a = gocLong + i * TAU / CONFIG.soNanDoc;
    const sa = Math.sin(a), ca = Math.cos(a);
    const diem = [];
    for (let k = 0; k <= SO_KHUC; k++) {
      const t = k / SO_KHUC * Math.PI;
      diem.push(chieu(R * Math.cos(t), R * Math.sin(t) * sa, R * Math.sin(t) * ca));
    }
    for (let k = 0; k < SO_KHUC; k++) {
      const t = (k + 0.5) / SO_KHUC * Math.PI;
      themDoan(diem[k], diem[k + 1], Math.cos(t), Math.sin(t) * sa, Math.sin(t) * ca,
        RGB_THANH_TOI, RGB_THANH_SANG, 0.026);
    }
  }

  // Hai núm vàng ở hai đầu trục
  [-1, 1].forEach((dau) => {
    const p = chieu(dau * R, 0, 0);
    (dau === dauGan ? truoc : sau).push({ loai: 'tron', x: p.x, y: p.y, r: tl * 0.09 * p.s, mau: mauKimLoai(RGB_VANH_TOI, RGB_VANH_SANG, 0.85) });
  });

  return { sau: sau, truoc: truoc, dauGan: dauGan };
}

function veCacNet(c, ds) {
  c.lineCap = 'round';
  for (let i = 0; i < ds.length; i++) {
    const n = ds[i];
    if (n.loai === 'net') {
      veThanh(c, n.a, n.b, n.mau, Math.max(1, n.rong), n.sang);
    } else if (n.loai === 'tron') {
      c.beginPath(); c.arc(n.x, n.y, Math.max(2, n.r), 0, TAU);
      c.fillStyle = n.mau; c.fill();
    }
  }
}

// Kích thước hình tròn mà quả cầu chiếm trên màn hình
function vongNgoaiCau() {
  const t = chieu(0, 0, 0);
  const D = cam.D;
  return { x: t.x, y: t.y, rad: cam.tiLe * HINH.R * D / Math.sqrt(D * D - HINH.R * HINH.R) };
}

// Lớp kính mờ phía sau (để quả cầu trông trong suốt, có chiều sâu)
function veKinhSau(c) {
  const v = vongNgoaiCau();
  const g = c.createRadialGradient(v.x - v.rad * 0.2, v.y - v.rad * 0.25, v.rad * 0.1, v.x, v.y, v.rad);
  g.addColorStop(0, 'rgba(255,255,255,0.05)');
  g.addColorStop(0.75, 'rgba(220,240,255,0.13)');
  g.addColorStop(1, 'rgba(200,225,255,0.34)');
  c.beginPath();
  c.arc(v.x, v.y, v.rad, 0, TAU);
  c.fillStyle = g;
  c.fill();
}

// Vệt sáng trên mặt kính phía trước
function veKinhTruoc(c) {
  const v = vongNgoaiCau();
  c.save();
  c.lineCap = 'round';
  c.strokeStyle = 'rgba(255,255,255,0.5)';
  c.lineWidth = v.rad * 0.06;
  c.beginPath();
  c.arc(v.x, v.y, v.rad * 0.84, Math.PI * 1.1, Math.PI * 1.4);
  c.stroke();
  c.lineWidth = v.rad * 0.035;
  c.strokeStyle = 'rgba(255,255,255,0.35)';
  c.beginPath();
  c.arc(v.x, v.y, v.rad * 0.84, Math.PI * 1.46, Math.PI * 1.52);
  c.stroke();
  c.lineWidth = 2;
  c.strokeStyle = 'rgba(255,255,255,0.3)';
  c.beginPath();
  c.arc(v.x, v.y, v.rad, 0, TAU);
  c.stroke();
  c.restore();
}

// Máng hứng bóng phía trước lồng
function veMang(c) {
  const w = HINH.mangRong, cao = 0.12;
  const ys = HINH.mangDauY, zs = HINH.mangDauZ, ye = HINH.mangCuoiY, ze = HINH.mangCuoiZ;
  const p1 = chieu(-w, ys, zs), p2 = chieu(w, ys, zs), p3 = chieu(w, ye, ze), p4 = chieu(-w, ye, ze);
  const q1 = chieu(-w, ys + cao, zs), q2 = chieu(w, ys + cao, zs), q3 = chieu(w, ye + cao, ze), q4 = chieu(-w, ye + cao, ze);
  const vang = hexSangRgb(CONFIG.mauMang);

  // Bóng đổ của máng trên bục
  const b1 = chieu(-w, HINH.sanY, zs + 0.3), b2 = chieu(w, HINH.sanY, zs + 0.3), b3 = chieu(w, HINH.sanY, ze), b4 = chieu(-w, HINH.sanY, ze);
  duongDaGiac(c, [b1, b2, b3, b4]);
  c.fillStyle = 'rgba(50,20,110,0.18)';
  c.fill();

  // Chân đỡ máng
  c.lineCap = 'round';
  veThanh(c, chieu(0, ye, ze - 0.1), chieu(0, HINH.sanY, ze - 0.1), '#c77d00', cam.tiLe * 0.04, 0.3);

  // Mặt máng
  const g = c.createLinearGradient(p1.x, p1.y, p4.x, p4.y);
  g.addColorStop(0, chuMau(tronMau(vang, [120, 50, 0], 0.35)));
  g.addColorStop(1, chuMau(tronMau(vang, [255, 255, 255], 0.25)));
  duongDaGiac(c, [p1, p2, p3, p4]);
  c.fillStyle = g;
  c.fill();
  // Hai thành bên
  duongDaGiac(c, [p1, p4, q4, q1]);
  c.fillStyle = chuMau(tronMau(vang, [150, 60, 0], 0.3), 0.95);
  c.fill();
  duongDaGiac(c, [p2, p3, q3, q2]);
  c.fillStyle = chuMau(tronMau(vang, [150, 60, 0], 0.45), 0.95);
  c.fill();
  // Gờ chắn cuối máng
  duongDaGiac(c, [p4, p3, q3, q4]);
  c.fillStyle = chuMau(tronMau(vang, [255, 255, 255], 0.15));
  c.fill();
  c.lineWidth = 2;
  c.strokeStyle = 'rgba(255,255,255,0.7)';
  c.beginPath(); c.moveTo(q1.x, q1.y); c.lineTo(q4.x, q4.y); c.lineTo(q3.x, q3.y); c.lineTo(q2.x, q2.y); c.stroke();
}

// Vẽ một quả bóng tròn bóng loáng có in số
// gocNhan: số nghiêng bao nhiêu; doToi: bóng ở xa thì tối hơn
function veBong(c, x, y, rad, mau, nhan, gocNhan, doToi, doDuc) {
  if (rad < 0.5) return;
  c.save();
  if (doDuc !== undefined) c.globalAlpha = doDuc;

  // Thân bóng: sáng ở góc trên bên trái, tối dần về bên phải phía dưới
  const g = c.createRadialGradient(x - rad * 0.38, y - rad * 0.42, rad * 0.05, x, y, rad * 1.02);
  g.addColorStop(0, mau.ratSang);
  g.addColorStop(0.25, mau.sang);
  g.addColorStop(0.7, mau.goc);
  g.addColorStop(1, mau.toi);
  c.beginPath();
  c.arc(x, y, rad, 0, TAU);
  c.fillStyle = g;
  c.fill();

  // Vòng tròn trắng in số
  c.translate(x, y);
  c.rotate(gocNhan);
  const rn = rad * 0.52;
  c.beginPath();
  c.arc(0, 0, rn, 0, TAU);
  c.fillStyle = '#fffdf5';
  c.fill();
  c.lineWidth = Math.max(1, rad * 0.05);
  c.strokeStyle = 'rgba(0,0,0,0.15)';
  c.stroke();
  const coChu = rad * (nhan.length <= 1 ? 0.72 : nhan.length === 2 ? 0.6 : 0.44);
  c.font = '900 ' + coChu.toFixed(1) + 'px "Segoe UI", Arial, sans-serif';
  c.textAlign = 'center';
  c.textBaseline = 'middle';
  c.fillStyle = '#1d1d2b';
  c.fillText(nhan, 0, rad * 0.05);
  c.setTransform(manHinh.dpr, 0, 0, manHinh.dpr, 0, 0);

  // Bóng ở xa thì phủ thêm một lớp tối
  if (doToi > 0.01) {
    c.beginPath();
    c.arc(x, y, rad, 0, TAU);
    c.fillStyle = 'rgba(25,10,60,' + doToi.toFixed(3) + ')';
    c.fill();
  }

  // Đốm sáng bóng loáng
  const hx = x - rad * 0.4, hy = y - rad * 0.45;
  const hg = c.createRadialGradient(hx, hy, 0, hx, hy, rad * 0.4);
  hg.addColorStop(0, 'rgba(255,255,255,0.9)');
  hg.addColorStop(1, 'rgba(255,255,255,0)');
  c.fillStyle = hg;
  c.beginPath();
  c.arc(hx, hy, rad * 0.4, 0, TAU);
  c.fill();
  c.restore();
}

// Vẽ các bóng trong lồng: bóng xa vẽ trước, bóng gần vẽ sau
function veCacBongTrongLong(c) {
  const ds = [];
  const r = banKinhBong;
  bongs.forEach((b) => ds.push({ b: b, p: chieu(b.x, b.y, b.z) }));
  if (bongTrung && bongTrung.giaiDoan === 1) {
    ds.push({ b: bongTrung, p: chieu(bongTrung.x, bongTrung.y, bongTrung.z) });
  }
  ds.sort((m, n) => m.p.z - n.p.z);
  ds.forEach((m) => {
    const hs = HOC_SINH[m.b.hs];
    const doToi = kep(0.18 - m.p.z * 0.28, 0, 0.42);
    veBong(c, m.p.x, m.p.y, r * cam.tiLe * m.p.s, hs.mau, hs.so, Math.sin(m.b.quay) * 0.6, doToi);
  });
}

// Vẽ toàn bộ cảnh lồng quay
function veCanhLong() {
  const c = ctx;
  c.clearRect(0, 0, manHinh.rong, manHinh.cao);
  const net = taoCacNetLong();
  veSan(c);
  veBongDoSan(c);
  veChanDe(c, -net.dauGan);       // chân đỡ ở đầu xa
  veKinhSau(c);                   // kính mờ phía sau
  veCacNet(c, net.sau);           // nửa lồng phía sau
  veCacBongTrongLong(c);          // các quả bóng
  veCacNet(c, net.truoc);         // nửa lồng phía trước
  veKinhTruoc(c);                 // vệt sáng mặt kính
  veMang(c);                      // máng hứng bóng
  veChanDe(c, net.dauGan);        // chân đỡ ở đầu gần (có tay quay)
}


/* =============================================================
   10. VẼ BÓNG TRÚNG VÀ PHÁO HOA
   ============================================================= */

const phaoHoa = { lich: [], tenLua: [], hat: [] };

function batDauPhaoHoa() {
  for (let i = 0; i < CONFIG.soPhaoHoa; i++) {
    phaoHoa.lich.push(i * 0.28 + Math.random() * 0.25);
  }
}

function xoaPhaoHoa() {
  phaoHoa.lich = []; phaoHoa.tenLua = []; phaoHoa.hat = [];
}

// Bắn một quả pháo từ dưới lên
function banTenLua() {
  const x = vung.trai + vung.rong * (0.1 + 0.8 * Math.random());
  const y = vung.tren + vung.cao;
  const dinh = vung.tren + vung.cao * (0.06 + 0.35 * Math.random());
  const g = 300;
  phaoHoa.tenLua.push({
    x: x, y: y,
    vx: (Math.random() - 0.5) * 60,
    vy: -Math.sqrt(2 * g * Math.max(50, y - dinh)),
    dinh: dinh, g: g,
    mau: CONFIG.mauPhaoHoa[Math.floor(Math.random() * CONFIG.mauPhaoHoa.length)]
  });
}

// Pháo nổ thành nhiều hạt sáng
function noPhao(t) {
  const soHat = 60 + Math.floor(Math.random() * 30);
  const k = kep(Math.min(vung.rong, vung.cao) / 600, 0.6, 1.8);
  const mau2 = CONFIG.mauPhaoHoa[Math.floor(Math.random() * CONFIG.mauPhaoHoa.length)];
  for (let i = 0; i < soHat; i++) {
    const a = Math.random() * TAU;
    const v = (60 + Math.random() * 200) * k;
    const song = 1.1 + Math.random() * 0.8;
    phaoHoa.hat.push({
      x: t.x, y: t.y, px: t.x, py: t.y,
      vx: Math.cos(a) * v, vy: Math.sin(a) * v,
      song: song, songMax: song,
      mau: Math.random() < 0.7 ? t.mau : mau2
    });
  }
  tiengNo();
}

function capNhatPhaoHoa(dt) {
  // Đếm ngược để bắn
  const conLai = [];
  phaoHoa.lich.forEach((tre) => {
    if (tre - dt <= 0) banTenLua(); else conLai.push(tre - dt);
  });
  phaoHoa.lich = conLai;

  // Tên lửa bay lên
  phaoHoa.tenLua = phaoHoa.tenLua.filter((t) => {
    t.x += t.vx * dt; t.y += t.vy * dt; t.vy += t.g * dt;
    if (t.vy >= -30 || t.y <= t.dinh) { noPhao(t); return false; }
    return true;
  });

  // Hạt sáng rơi xuống và mờ dần
  const giam = Math.max(0, 1 - 1.3 * dt);
  phaoHoa.hat = phaoHoa.hat.filter((p) => {
    p.px = p.x; p.py = p.y;
    p.vx *= giam; p.vy = p.vy * giam + 120 * dt;
    p.x += p.vx * dt; p.y += p.vy * dt;
    p.song -= dt;
    return p.song > 0;
  });
}

function vePhaoHoa(c) {
  if (phaoHoa.tenLua.length === 0 && phaoHoa.hat.length === 0) return;
  c.save();
  c.globalCompositeOperation = 'lighter';
  c.lineCap = 'round';
  phaoHoa.tenLua.forEach((t) => {
    c.strokeStyle = t.mau;
    c.lineWidth = 3;
    c.beginPath(); c.moveTo(t.x, t.y); c.lineTo(t.x - t.vx * 0.05, t.y - t.vy * 0.05); c.stroke();
  });
  phaoHoa.hat.forEach((p) => {
    c.globalAlpha = kep(p.song / p.songMax, 0, 1);
    c.strokeStyle = p.mau;
    c.lineWidth = 2.6;
    c.beginPath();
    c.moveTo(p.px - (p.x - p.px) * 2, p.py - (p.y - p.py) * 2);
    c.lineTo(p.x, p.y);
    c.stroke();
  });
  c.restore();
}

// Hào quang vàng xoay chầm chậm phía sau bóng trúng
function veHaoQuang(c, x, y, rad, doDuc) {
  c.save();
  c.globalAlpha = doDuc;
  c.translate(x, y);
  c.rotate(thoiGian * 0.5);
  const g = c.createRadialGradient(0, 0, rad * 0.4, 0, 0, rad * 2.6);
  g.addColorStop(0, 'rgba(255,245,170,0.95)');
  g.addColorStop(1, 'rgba(255,245,170,0)');
  c.fillStyle = g;
  for (let i = 0; i < 12; i++) {
    c.beginPath();
    c.moveTo(0, 0);
    c.arc(0, 0, rad * 2.6, i * TAU / 12, i * TAU / 12 + TAU / 26);
    c.closePath();
    c.fill();
  }
  c.beginPath();
  c.arc(0, 0, rad * 1.5, 0, TAU);
  c.fill();
  c.restore();
}

// Vẽ bóng trúng ở giai đoạn 2, 3, khi hiện kết quả và khi bay vào bảng
function veBongTrung(c) {
  const bt = bongTrung;
  if (bt && bt.giaiDoan !== 1) {
    const hs = HOC_SINH[bt.hs];
    if (bt.giaiDoan === 2) {
      const p = chieu(bt.x, bt.y, bt.z);
      veBong(c, p.x, p.y, banKinhBong * cam.tiLe * p.s, hs.mau, hs.so, Math.sin(bt.quay) * 0.6, 0);
    } else if (bt.giaiDoan === 3) {
      const T = viTriBongLon();
      const u = Math.min(1, bt.t / CONFIG.thoiGianBayLen);
      const e = emRa(u);
      const x = lerp(bt.sx0, T.x, e);
      const y = lerp(bt.sy0, T.y, e) - Math.sin(Math.PI * u) * vung.cao * 0.06;
      const rad = lerp(bt.rad0, T.rad, nayRa(u));
      const goc = lerp(bt.goc0, 0, e) + Math.sin(u * TAU * 1.5) * 0.35 * (1 - u);
      veHaoQuang(c, x, y, rad, u);
      veBong(c, x, y, rad, hs.mau, hs.so, goc, 0);
    } else if (bt.giaiDoan === 'xong') {
      const T = viTriBongLon();
      const y = T.y + Math.sin(thoiGian * 2.2) * T.rad * 0.04;
      veHaoQuang(c, T.x, y, T.rad, 1);
      veBong(c, T.x, y, T.rad, hs.mau, hs.so, Math.sin(thoiGian * 1.6) * 0.07, 0);
    }
  }
  if (bongBay) {
    const hs = HOC_SINH[bongBay.hs];
    const u = Math.min(1, bongBay.t / CONFIG.thoiGianBayVaoBang);
    const e = u * u;
    const x = lerp(bongBay.x0, bongBay.tx, e);
    const y = lerp(bongBay.y0, bongBay.ty, e) - Math.sin(Math.PI * u) * 40;
    const rad = lerp(bongBay.rad0, 18, emRa(u));
    const doDuc = 1 - Math.max(0, (u - 0.75) / 0.25);
    veBong(c, x, y, rad, hs.mau, hs.so, u * TAU, 0, doDuc);
  }
}

function veHieuUng() {
  const c = ctxHU;
  c.clearRect(0, 0, manHinh.rong, manHinh.cao);
  vePhaoHoa(c);
  veBongTrung(c);
}


/* =============================================================
   11. LỊCH SỬ VÀ LƯU VÀO TRÌNH DUYỆT (localStorage)
   ============================================================= */

const KHOA_LUU_TRU = 'longQuayXoSo_lichSu_v1';
const KHOA_KIEU_CAU_HOI = 'longQuayXoSo_kieuCauHoi';

// Đọc lại kiểu câu hỏi thầy cô đã chọn lần trước
function taiKieuCauHoi() {
  let giaTri = 'tatCa';
  try { giaTri = window.localStorage.getItem(KHOA_KIEU_CAU_HOI) || 'tatCa'; } catch (loi) { /* bỏ qua */ }
  if (['tatCa', 'tuLuan', 'tracNghiem'].indexOf(giaTri) < 0) giaTri = 'tatCa';
  kieuCauHoi = giaTri;
  chonKieuCauHoi.value = giaTri;
  // Danh sách không có câu hỏi thì ẩn luôn ô chọn
  chonKieuCauHoi.parentElement.classList.toggle('an', CAU_HOI.length === 0);
}

// Đọc lịch sử đã lưu. Chỉ giữ những bạn vẫn còn đúng số và đúng tên
// trong students.js (nếu thầy cô sửa danh sách thì lịch sử cũ tự bỏ).
function taiLichSu() {
  let duLieu = null;
  try {
    duLieu = JSON.parse(window.localStorage.getItem(KHOA_LUU_TRU) || 'null');
  } catch (loi) {
    duLieu = null;
  }
  const ketQua = [];
  if (duLieu && Array.isArray(duLieu.lichSu)) {
    duLieu.lichSu.forEach((muc) => {
      if (!muc) return;
      const so = String(muc.so), ten = String(muc.ten);
      const i = HOC_SINH.findIndex((hs, k) => hs.so === so && hs.ten === ten && !ketQua.some((l) => l.hs === k));
      if (i < 0) return;
      const cau = muc.cauHoi ? {
        cauHoi: String(muc.cauHoi), dapAn: String(muc.dapAn || ''), mon: String(muc.mon || ''),
        tracNghiem: !!muc.tracNghiem, kq: muc.kq === 'dung' || muc.kq === 'sai' ? muc.kq : ''
      } : null;
      ketQua.push({ hs: i, cau: cau });
    });
  }
  return ketQua;
}

// Lưu lịch sử (số, tên, câu hỏi) vào trình duyệt
function luuLichSu() {
  try {
    const duLieu = {
      lichSu: lichSu.map((l) => ({
        so: HOC_SINH[l.hs].so,
        ten: HOC_SINH[l.hs].ten,
        cauHoi: l.cau ? l.cau.cauHoi : '',
        dapAn: l.cau ? l.cau.dapAn : '',
        mon: l.cau ? (l.cau.mon || '') : '',
        tracNghiem: !!(l.cau && (l.cau.luaChon || l.cau.tracNghiem)),
        kq: l.cau ? (l.cau.kq || '') : ''          // 'dung' | 'sai' | '' (chưa trả lời)
      }))
    };
    window.localStorage.setItem(KHOA_LUU_TRU, JSON.stringify(duLieu));
  } catch (loi) {
    // Trình duyệt không cho lưu thì thôi, vẫn quay bình thường
  }
}

// Vẽ lại bảng lịch sử: lượt mới nhất ở trên cùng
function veLichSu(coHieuUng) {
  const soHien = Math.max(0, lichSu.length - soChuaVaoBang);
  soDaQuay.textContent = '(' + soHien + ')';
  dsLichSu.textContent = '';
  if (soHien === 0) {
    const li = document.createElement('li');
    li.className = 'trong';
    li.textContent = 'Chưa có lượt quay nào';
    dsLichSu.appendChild(li);
    return;
  }
  for (let k = soHien - 1; k >= 0; k--) {
    const luotNay = lichSu[k];
    const hs = HOC_SINH[luotNay.hs];
    const li = document.createElement('li');
    if (coHieuUng && k === soHien - 1) li.className = 'moi';

    const bong = document.createElement('span');
    bong.className = 'bong-nho';
    bong.textContent = hs.so;
    bong.style.background = 'radial-gradient(circle at 35% 30%, ' + hs.mau.ratSang + ' 0%, ' + hs.mau.goc + ' 55%, ' + hs.mau.toi + ' 100%)';

    const thongTin = document.createElement('div');
    thongTin.className = 'thong-tin-luot';
    const ten = document.createElement('span');
    ten.className = 'ten';
    ten.textContent = hs.ten;
    const luot = document.createElement('span');
    luot.className = 'luot';
    luot.textContent = 'Lượt ' + (k + 1) + ' • Số ' + hs.so;
    thongTin.appendChild(ten);
    thongTin.appendChild(luot);
    if (luotNay.cau) {
      const cau = document.createElement('span');
      cau.className = 'cau-hoi-nho';
      const laTracNghiem = !!(luotNay.cau.luaChon || luotNay.cau.tracNghiem);
      cau.textContent = (laTracNghiem ? '📝 ' : '❓ ') + (luotNay.cau.mon ? luotNay.cau.mon + ': ' : '') + luotNay.cau.cauHoi;
      // Đã có kết quả (trả lời trắc nghiệm, hoặc hết giờ): ghi thêm "Đúng" hoặc "Sai"
      if (luotNay.cau.kq) {
        const nhan = document.createElement('span');
        nhan.className = 'ket-qua-tl ' + luotNay.cau.kq;
        nhan.textContent = luotNay.cau.kq === 'dung' ? '✔ Đúng' : '✘ Sai';
        cau.appendChild(nhan);
      }
      thongTin.appendChild(cau);
    }

    li.appendChild(bong);
    li.appendChild(thongTin);
    dsLichSu.appendChild(li);
  }
  dsLichSu.scrollTop = 0;
}

function capNhatDem() {
  dem.textContent = 'Còn lại: ' + (HOC_SINH.length - lichSu.length) + ' / ' + HOC_SINH.length + ' bạn';
}

// Bật / tắt các nút cho đúng lúc
function capNhatNut() {
  const ranh = cheDo === 'nghi';
  const het = HOC_SINH.length > 0 && lichSu.length >= HOC_SINH.length;
  nutQuay.disabled = !ranh || het || HOC_SINH.length === 0;
  nutQuay.textContent = !ranh ? 'ĐANG QUAY' : (het ? 'ĐÃ HẾT' : 'QUAY!');
  nutLamLai.disabled = !ranh;
  // Hết bóng: ô đếm trên cùng đổi thành lời thông báo (luôn nhìn thấy)
  if (het && ranh) dem.textContent = '🎊 Đã quay hết danh sách!';
  else capNhatDem();
  // Băng thông báo phía dưới chỉ hiện khi thẻ kết quả đã cất đi (để không bị che)
  const dangHienThe = !theKetQua.classList.contains('an') || bongBay !== null;
  thongBaoHet.classList.toggle('an', !(het && ranh && !dangHienThe));
}

// Xóa sạch và bắt đầu lại
function lamLaiTuDau() {
  lichSu = [];
  soChuaVaoBang = 0;
  bongTrung = null;
  bongBay = null;
  cheDo = 'nghi';
  tocDoLong = 0;
  try { window.localStorage.removeItem(KHOA_LUU_TRU); } catch (loi) { /* bỏ qua */ }
  dungMp3('chucMung');
  anTheKetQua();
  xoaPhaoHoa();
  taoDanhSachBong(true);   // bóng rơi lại vào lồng
  veLichSu(false);
  capNhatDem();
  capNhatNut();
}


/* =============================================================
   12. ÂM THANH (Web Audio API và file MP3)
   ============================================================= */

const amThanh = { ctx: null, tong: null, nhieu: null, lanCuoiVaCham: 0 };

// Chỉ tạo âm thanh sau khi người dùng bấm chuột hoặc phím lần đầu
// (trình duyệt không cho tự phát tiếng khi chưa ai bấm gì).
function khoiTaoAmThanh() {
  // Nếu trình duyệt báo "chưa có ai thật sự bấm" (ví dụ chỉ nhấn phím Shift) thì chờ lần sau
  if (navigator.userActivation && !navigator.userActivation.isActive) return;
  if (amThanh.ctx) {
    if (amThanh.ctx.state === 'suspended') amThanh.ctx.resume();
    return;
  }
  const AC = window.AudioContext || window.webkitAudioContext;
  if (!AC) return;
  try {
    const ac = new AC();
    const tong = ac.createGain();
    tong.gain.value = CONFIG.amLuong;
    tong.connect(ac.destination);
    // Tạo "tiếng xì" ngẫu nhiên dùng làm nguyên liệu cho tiếng cạch, tiếng nổ
    const doDai = Math.floor(ac.sampleRate * 0.5);
    const nhieu = ac.createBuffer(1, doDai, ac.sampleRate);
    const duLieu = nhieu.getChannelData(0);
    for (let i = 0; i < doDai; i++) duLieu[i] = Math.random() * 2 - 1;
    amThanh.ctx = ac; amThanh.tong = tong; amThanh.nhieu = nhieu;
  } catch (loi) {
    amThanh.ctx = null;
  }
}

// ---------- File MP3 ----------
// Mỗi file: { the: thẻ <audio>, sanSang: đã tải xong chưa }
const mp3 = {};

// Tải trước các file MP3 ghi trong CONFIG. File nào không có thì bỏ qua.
function taiCacFileMp3() {
  const ds = CONFIG.amThanhMp3 || {};
  Object.keys(ds).forEach((ten) => {
    if (!ds[ten]) return;
    const the = new Audio();
    const muc = { the: the, sanSang: false };
    the.preload = 'auto';
    the.addEventListener('canplay', () => { muc.sanSang = true; });
    the.addEventListener('error', () => { muc.sanSang = false; });
    the.src = ds[ten];
    mp3[ten] = muc;
  });
}

// Phát một file MP3. Trả về true nếu phát được.
function phatMp3(ten, lapLai) {
  const muc = mp3[ten];
  if (!muc || !muc.sanSang || CONFIG.amLuong <= 0) return false;
  try {
    muc.the.loop = !!lapLai;
    muc.the.volume = kep(CONFIG.amLuong, 0, 1);
    muc.the.currentTime = 0;
    const hua = muc.the.play();
    if (hua && hua.catch) hua.catch(() => {});
    return true;
  } catch (loi) {
    return false;
  }
}

function dungMp3(ten) {
  const muc = mp3[ten];
  if (muc && !muc.the.paused) muc.the.pause();
}

// Tiếng lồng quay bằng file MP3: to dần khi quay nhanh, nhỏ dần khi chậm lại.
// Trả về true nếu đang dùng file MP3 (thì không cần tiếng lạch cạch tự tạo).
function capNhatMp3Quay() {
  const muc = mp3.quay;
  if (!muc || !muc.sanSang || CONFIG.amLuong <= 0) return false;
  if (tocDoLong > 0.05) {
    if (muc.the.paused) phatMp3('quay', true);
    muc.the.volume = kep(CONFIG.amLuong * tocDoLong / CONFIG.tocDoQuayToiDa, 0, 1);
  } else if (!muc.the.paused) {
    muc.the.pause();
  }
  return true;
}

// ---------- Tiếng tự tạo bằng Web Audio ----------

// Tiếng "cạch" ngắn: lọc tiếng xì cho còn một khoảng cao độ
function tiengCach(amLuong, tanSo) {
  const ac = amThanh.ctx;
  if (!ac || CONFIG.amLuong <= 0) return;
  const t = ac.currentTime;
  const nguon = ac.createBufferSource();
  nguon.buffer = amThanh.nhieu;
  const loc = ac.createBiquadFilter();
  loc.type = 'bandpass';
  loc.frequency.value = tanSo;
  loc.Q.value = 4;
  const g = ac.createGain();
  g.gain.setValueAtTime(0.0001, t);
  g.gain.exponentialRampToValueAtTime(Math.max(0.001, amLuong), t + 0.003);
  g.gain.exponentialRampToValueAtTime(0.0001, t + 0.05);
  nguon.connect(loc); loc.connect(g); g.connect(amThanh.tong);
  nguon.start(t, Math.random() * 0.4);
  nguon.stop(t + 0.07);
}

// Tiếng lạch cạch khi nan lồng đi qua
function tiengLachCach() {
  tiengCach(0.45, 1800 + Math.random() * 900);
  tiengCach(0.18, 700 + Math.random() * 200);
}

// Tiếng bóng va vào nhau (không phát dồn dập quá)
function phatTiengVaCham() {
  if (vaChamManhNhat <= 0 || !amThanh.ctx) return;
  const bayGio = amThanh.ctx.currentTime;
  if (bayGio - amThanh.lanCuoiVaCham > 0.035) {
    tiengCach(Math.min(0.35, vaChamManhNhat * 0.05), 3000 + Math.random() * 1800);
    amThanh.lanCuoiVaCham = bayGio;
  }
}

// Phát một nốt nhạc trong trẻo
function phatNot(tanSo, batDau, keoDai, amLuong) {
  const ac = amThanh.ctx;
  const osc = ac.createOscillator();
  osc.type = 'sine';
  osc.frequency.value = tanSo;
  const g = ac.createGain();
  g.gain.setValueAtTime(0.0001, batDau);
  g.gain.exponentialRampToValueAtTime(amLuong, batDau + 0.006);
  g.gain.exponentialRampToValueAtTime(0.0001, batDau + keoDai);
  osc.connect(g); g.connect(amThanh.tong);
  osc.start(batDau);
  osc.stop(batDau + keoDai + 0.05);
}

// Tiếng "ting" như chuông nhỏ
function tiengTing() {
  const ac = amThanh.ctx;
  if (!ac || CONFIG.amLuong <= 0) return;
  const t = ac.currentTime + 0.02;
  phatNot(1567.98, t, 1.3, 0.45);
  phatNot(3135.96, t, 0.6, 0.12);
  phatNot(2093.0, t + 0.13, 1.7, 0.42);
  phatNot(2093.0 * 2.76, t + 0.13, 0.45, 0.06);
}

// Trả lời đúng: ba nốt nhạc đi lên "tinh – tinh – ting"
function tiengDung() {
  const ac = amThanh.ctx;
  if (!ac || CONFIG.amLuong <= 0) return;
  const t = ac.currentTime + 0.02;
  phatNot(1046.5, t, 0.35, 0.4);
  phatNot(1318.5, t + 0.12, 0.35, 0.4);
  phatNot(1568.0, t + 0.24, 0.9, 0.45);
}

// Trả lời sai: tiếng "bùm bụp" trầm đi xuống
function tiengSai() {
  const ac = amThanh.ctx;
  if (!ac || CONFIG.amLuong <= 0) return;
  const t = ac.currentTime + 0.02;
  [0, 0.22].forEach((tre, k) => {
    const osc = ac.createOscillator();
    osc.type = 'square';
    osc.frequency.setValueAtTime(k === 0 ? 330 : 247, t + tre);
    osc.frequency.exponentialRampToValueAtTime(k === 0 ? 280 : 180, t + tre + 0.25);
    const g = ac.createGain();
    g.gain.setValueAtTime(0.0001, t + tre);
    g.gain.exponentialRampToValueAtTime(0.18, t + tre + 0.01);
    g.gain.exponentialRampToValueAtTime(0.0001, t + tre + 0.28);
    osc.connect(g); g.connect(amThanh.tong);
    osc.start(t + tre);
    osc.stop(t + tre + 0.3);
  });
}

// Tiếng pháo nổ "bụp"
function tiengNo() {
  const ac = amThanh.ctx;
  if (!ac || CONFIG.amLuong <= 0) return;
  const t = ac.currentTime;
  const nguon = ac.createBufferSource();
  nguon.buffer = amThanh.nhieu;
  const loc = ac.createBiquadFilter();
  loc.type = 'lowpass';
  loc.frequency.value = 700 + Math.random() * 500;
  const g = ac.createGain();
  g.gain.setValueAtTime(0.0001, t);
  g.gain.exponentialRampToValueAtTime(0.5, t + 0.005);
  g.gain.exponentialRampToValueAtTime(0.0001, t + 0.35);
  nguon.connect(loc); loc.connect(g); g.connect(amThanh.tong);
  nguon.start(t);
  nguon.stop(t + 0.4);
}


/* =============================================================
   13. NÚT BẤM VÀ BÀN PHÍM
   ============================================================= */

function moHopThoai() {
  if (cheDo !== 'nghi') return;
  hopThoai.classList.remove('an');
  nutHuy.focus();
}

function dongHopThoai() {
  hopThoai.classList.add('an');
}

function batTatToanManHinh() {
  const dangToanManHinh = document.fullscreenElement || document.webkitFullscreenElement;
  try {
    if (!dangToanManHinh) {
      const el = document.documentElement;
      const hua = el.requestFullscreen ? el.requestFullscreen() : (el.webkitRequestFullscreen && el.webkitRequestFullscreen());
      if (hua && hua.catch) hua.catch(() => {});
    } else {
      const hua = document.exitFullscreen ? document.exitFullscreen() : (document.webkitExitFullscreen && document.webkitExitFullscreen());
      if (hua && hua.catch) hua.catch(() => {});
    }
  } catch (loi) { /* trình duyệt không hỗ trợ thì thôi */ }
}

function capNhatNutToanManHinh() {
  const dang = document.fullscreenElement || document.webkitFullscreenElement;
  const chu = dang ? 'Thoát toàn màn hình' : 'Toàn màn hình';
  nutToanManHinh.textContent = dang ? '↩️' : '🖥️';
  const nhan = document.createElement('span');
  nhan.className = 'chu-nut';
  nhan.textContent = ' ' + chu;
  nutToanManHinh.appendChild(nhan);
  nutToanManHinh.title = chu;
  nutToanManHinh.setAttribute('aria-label', chu);
  tinhBoCuc();
}

function ganSuKien() {
  // Lần bấm đầu tiên ở bất kỳ đâu → bật âm thanh
  document.addEventListener('pointerdown', khoiTaoAmThanh);

  nutQuay.addEventListener('click', () => { nutQuay.blur(); batDauQuay(); });
  nutLamLai.addEventListener('click', () => { nutLamLai.blur(); moHopThoai(); });
  nutToanManHinh.addEventListener('click', () => { nutToanManHinh.blur(); batTatToanManHinh(); });
  nutHuy.addEventListener('click', dongHopThoai);
  nutDongY.addEventListener('click', () => { dongHopThoai(); lamLaiTuDau(); });
  hopThoai.addEventListener('click', (e) => { if (e.target === hopThoai) dongHopThoai(); });
  nutDapAn.addEventListener('click', () => { nutDapAn.blur(); batTatDapAn(); });
  chonKieuCauHoi.addEventListener('change', () => {
    kieuCauHoi = chonKieuCauHoi.value;
    try { window.localStorage.setItem(KHOA_KIEU_CAU_HOI, kieuCauHoi); } catch (loi) { /* bỏ qua */ }
    chonKieuCauHoi.blur();                 // để phím cách (Space) lại dùng cho nút QUAY
  });
  nutXong.addEventListener('click', () => { nutXong.blur(); guiKetQuaVaoBang(); });

  document.addEventListener('keydown', (e) => {
    khoiTaoAmThanh();
    // Khi hộp xác nhận đang mở: chỉ nghe phím Esc
    if (!hopThoai.classList.contains('an')) {
      if (e.key === 'Escape') dongHopThoai();
      return;
    }
    // Đang hiện câu trắc nghiệm: phím A/B/C/D hoặc 1/2/3/4 để chọn đáp án
    if (cauDangHoi && cauDangHoi.luaChon && !daTraLoi && !e.ctrlKey && !e.altKey && !e.metaKey) {
      const phim = (e.key || '').toUpperCase();
      let i = CHU_CAI.indexOf(phim);
      if (i < 0 && /^[1-4]$/.test(phim)) i = Number(phim) - 1;
      if (i >= 0 && i < cauDangHoi.luaChon.length) {
        e.preventDefault();
        chonDapAn(i);
        return;
      }
    }
    if (e.code === 'Space' || e.key === ' ') {
      e.preventDefault();               // không cho trang cuộn hoặc bấm nhầm nút khác
      if (!e.repeat) batDauQuay();
    }
  });

  window.addEventListener('resize', tinhBoCuc);
  document.addEventListener('fullscreenchange', capNhatNutToanManHinh);
  document.addEventListener('webkitfullscreenchange', capNhatNutToanManHinh);
}


/* =============================================================
   14. VÒNG LẶP CHÍNH: mỗi khung hình (khoảng 60 lần/giây)
   tính toán chuyển động rồi vẽ lại mọi thứ
   ============================================================= */

let lanTruoc = 0;

function capNhat(dt) {
  dieuKhienLong(dt);
  // Chia nhỏ thời gian thành nhiều bước để vật lý chính xác, bóng không xuyên tường
  const soBuoc = Math.max(1, Math.min(12, Math.ceil(dt * 240)));
  const h = dt / soBuoc;
  vaChamManhNhat = 0;
  for (let i = 0; i < soBuoc; i++) buocVatLy(h);
  phatTiengVaCham();
  capNhatBongTrung(dt);
  capNhatDongHo(dt);
  capNhatBongBay(dt);
  capNhatPhaoHoa(dt);
}

function vongLap(bayGio) {
  let dt = lanTruoc ? (bayGio - lanTruoc) / 1000 : 1 / 60;
  lanTruoc = bayGio;
  if (!(dt > 0)) dt = 0;
  if (dt > 0.05) dt = 0.05;     // máy bị khựng thì cũng không nhảy cóc quá xa
  thoiGian += dt;
  capNhat(dt);
  veCanhLong();
  veHieuUng();
  requestAnimationFrame(vongLap);
}

function khoiDong() {
  capNhatGocNhin();
  lichSu = taiLichSu();
  luuLichSu();                  // ghi lại bản đã lọc sạch
  taiKieuCauHoi();
  taoDanhSachBong(false);
  choBongNamYen();
  taiCacFileMp3();
  ganSuKien();
  tinhBoCuc();
  veLichSu(false);
  capNhatDem();
  capNhatNut();
  if (HOC_SINH.length === 0) {
    thongBaoLoi.textContent = '⚠️ Chưa có học sinh nào. Thầy cô hãy mở file students.js để thêm danh sách nhé!';
    thongBaoLoi.classList.remove('an');
  }
  requestAnimationFrame(vongLap);
}

khoiDong();
