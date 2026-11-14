'use strict';
/* =============================================================
   TRANG QUẢN LÝ CÂU HỎI – phần chạy trên trình duyệt
   - Mở hộp Thêm / Sửa câu hỏi và điền sẵn dữ liệu
   - Đổi qua lại giữa câu Tự luận và Trắc nghiệm
   - Hỏi lại trước khi xóa, khôi phục, thay thế toàn bộ
   - Chọn nhiều câu để xóa cùng lúc
   ============================================================= */
(function () {
  const $ = (chon, goc) => (goc || document).querySelector(chon);
  const $$ = (chon, goc) => Array.from((goc || document).querySelectorAll(chon));

  const hopThoai = $('#hopThoai');
  const form = $('#formCauHoi');
  const tieuDe = $('#tieuDeHopThoai');
  const loiForm = $('#loiForm');
  const khoiTuLuan = $('.khoi-tu-luan', form);
  const khoiTracNghiem = $('.khoi-trac-nghiem', form);
  const oLuaChon = $$('input[name="lua_chon[]"]', form);
  const oDapAnDung = $$('input[name="dap_an_dung"]', form);

  // ---------- Hộp thêm / sửa ----------

  function loaiDangChon() {
    const o = $('input[name="loai"]:checked', form);
    return o ? o.value : 'tuLuan';
  }

  // Hiện phần nhập phù hợp với loại câu hỏi
  function capNhatLoai() {
    const tn = loaiDangChon() === 'tracNghiem';
    khoiTuLuan.hidden = tn;
    khoiTracNghiem.hidden = !tn;
    // Câu trắc nghiệm bắt buộc có A, B
    oLuaChon[0].required = tn;
    oLuaChon[1].required = tn;
    danhDauDapAnDung();
  }

  function danhDauDapAnDung() {
    oDapAnDung.forEach((o, i) => {
      oLuaChon[i].closest('.hang-lua-chon').classList.toggle('la-dung', o.checked);
    });
  }

  function hienLoi(dsLoi) {
    if (!dsLoi || !dsLoi.length) {
      loiForm.hidden = true;
      loiForm.textContent = '';
      return;
    }
    loiForm.textContent = '';
    const p = document.createElement('div');
    p.textContent = 'Chưa lưu được, thầy cô kiểm tra lại nhé:';
    const ul = document.createElement('ul');
    dsLoi.forEach((dong) => {
      const li = document.createElement('li');
      li.textContent = dong;
      ul.appendChild(li);
    });
    loiForm.append(p, ul);
    loiForm.hidden = false;
  }

  /** Mở hộp. duLieu: { vi_tri, loai, mon, cau_hoi, dap_an, lua_chon[], dap_an_dung, loi[] } */
  function moHopThoai(duLieu) {
    const d = duLieu || {};
    const dangSua = d.vi_tri !== undefined && d.vi_tri !== null && d.vi_tri !== '';
    form.reset();
    form.elements.vi_tri.value = dangSua ? d.vi_tri : '';
    tieuDe.textContent = dangSua ? 'Sửa câu hỏi #' + (Number(d.vi_tri) + 1) : 'Thêm câu hỏi';
    form.elements.mon.value = d.mon || '';
    form.elements.cau_hoi.value = d.cau_hoi || '';
    form.elements.dap_an.value = d.dap_an || '';
    const loai = d.loai === 'tracNghiem' ? 'tracNghiem' : 'tuLuan';
    $('input[name="loai"][value="' + loai + '"]', form).checked = true;
    oLuaChon.forEach((o, i) => { o.value = (d.lua_chon && d.lua_chon[i]) || ''; });
    oDapAnDung.forEach((o) => { o.checked = o.value === d.dap_an_dung; });
    form.elements.tam_tat.checked = !!d.tam_tat;
    hienLoi(d.loi);
    capNhatLoai();
    if (typeof hopThoai.showModal === 'function') hopThoai.showModal();
    else hopThoai.setAttribute('open', '');
    form.elements.cau_hoi.focus();
  }

  function dongHopThoai() {
    if (typeof hopThoai.close === 'function') hopThoai.close();
    else hopThoai.removeAttribute('open');
  }

  // Đổi dữ liệu câu hỏi (dạng lưu trong file) sang dạng của form
  function sangDuLieuForm(cau, viTri) {
    const tn = Array.isArray(cau.luaChon) && cau.luaChon.length > 0;
    return {
      vi_tri: viTri,
      loai: tn ? 'tracNghiem' : 'tuLuan',
      mon: cau.mon,
      cau_hoi: cau.cauHoi,
      dap_an: cau.dapAn,
      lua_chon: cau.luaChon || [],
      dap_an_dung: cau.dapAnDung,
      tam_tat: !!cau.tamTat
    };
  }

  const nutThem = $('#nutThem');
  if (nutThem) nutThem.addEventListener('click', () => moHopThoai({}));

  $$('.nut-sua').forEach((nut) => {
    nut.addEventListener('click', () => {
      const dong = nut.closest('tr');
      moHopThoai(sangDuLieuForm(JSON.parse(dong.dataset.cau), dong.dataset.viTri));
    });
  });

  $$('[data-dong]', hopThoai).forEach((nut) => nut.addEventListener('click', dongHopThoai));
  // Bấm ra ngoài hộp thì đóng
  hopThoai.addEventListener('click', (e) => { if (e.target === hopThoai) dongHopThoai(); });

  $$('input[name="loai"]', form).forEach((o) => o.addEventListener('change', capNhatLoai));
  oDapAnDung.forEach((o) => o.addEventListener('change', danhDauDapAnDung));

  // Kiểm tra nhanh trước khi gửi (máy chủ vẫn kiểm tra lại kỹ)
  form.addEventListener('submit', (e) => {
    if (loaiDangChon() !== 'tracNghiem') return;
    const loi = [];
    const daChon = oDapAnDung.find((o) => o.checked);
    if (!daChon) loi.push('Chưa chọn đáp án đúng: bấm vào chữ cái A, B, C hoặc D.');
    else {
      const i = oDapAnDung.indexOf(daChon);
      if (!oLuaChon[i].value.trim()) loi.push('Đáp án đúng là ' + daChon.value + ' nhưng phương án ' + daChon.value + ' đang để trống.');
    }
    // Không để trống ở giữa (ví dụ có A, B, D mà thiếu C)
    const giaTri = oLuaChon.map((o) => o.value.trim());
    const cuoi = giaTri.reduce((k, v, i) => (v ? i : k), -1);
    for (let i = 0; i < cuoi; i++) {
      if (!giaTri[i]) loi.push('Phương án ' + 'ABCD'[i] + ' đang để trống (điền lần lượt từ A).');
    }
    if (loi.length) {
      e.preventDefault();
      hienLoi(loi);
      loiForm.scrollIntoView({ block: 'nearest' });
    }
  });

  // Có lỗi từ máy chủ sau khi lưu → mở lại hộp với những gì đã gõ
  const formCu = $('#duLieuFormCu');
  if (formCu) {
    try { moHopThoai(JSON.parse(formCu.textContent)); } catch (loi) { /* bỏ qua */ }
  }

  // ---------- Xóa, khôi phục, nhập file: hỏi lại cho chắc ----------

  $$('.form-xoa').forEach((f) => {
    f.addEventListener('submit', (e) => {
      const cau = JSON.parse(f.closest('tr').dataset.cau);
      const ngan = cau.cauHoi.length > 90 ? cau.cauHoi.slice(0, 90) + '…' : cau.cauHoi;
      if (!confirm('Xóa câu hỏi này?\n\n"' + ngan + '"\n\n(Có thể khôi phục lại ở mục Sao lưu.)')) e.preventDefault();
    });
  });

  $$('.form-khoi-phuc').forEach((f) => {
    f.addEventListener('submit', (e) => {
      if (!confirm('Khôi phục danh sách câu hỏi về bản sao lưu này?\n\nBản đang dùng hiện tại cũng sẽ được sao lưu trước.')) e.preventDefault();
    });
  });

  const formNhap = $('#formNhap');
  if (formNhap) {
    formNhap.addEventListener('submit', (e) => {
      const cheDo = $('input[name="che_do"]:checked', formNhap);
      if (cheDo && cheDo.value === 'thay_the' &&
          !confirm('THAY THẾ TOÀN BỘ danh sách câu hỏi hiện có bằng nội dung file này?\n\n(Danh sách cũ được sao lưu, có thể khôi phục.)')) {
        e.preventDefault();
      }
    });
  }

  // ---------- Thông báo nhỏ ở góc màn hình (không làm trang nhảy lên đầu) ----------

  function thongBaoNhanh(loai, noiDung, coNutTaiLai) {
    let vung = $('#vungThongBaoNhanh');
    if (!vung) {
      vung = document.createElement('div');
      vung.id = 'vungThongBaoNhanh';
      vung.className = 'vung-thong-bao-nhanh';
      vung.setAttribute('role', 'status');
      vung.setAttribute('aria-live', 'polite');
      document.body.appendChild(vung);
    }
    const o = document.createElement('div');
    o.className = 'thong-bao-nhanh ' + (loai === 'loi' ? 'that-bai' : loai === 'canh-bao' ? 'canh-bao' : 'thanh-cong');
    const chu = document.createElement('span');
    chu.textContent = noiDung;
    o.appendChild(chu);
    if (coNutTaiLai) {
      const nut = document.createElement('button');
      nut.type = 'button';
      nut.className = 'nut nut-nho nut-mo';
      nut.textContent = '↻ Tải lại trang';
      nut.addEventListener('click', () => taiLaiGiuViTri());
      o.appendChild(nut);
    }
    const dong = document.createElement('button');
    dong.type = 'button';
    dong.className = 'dong-thong-bao';
    dong.setAttribute('aria-label', 'Đóng');
    dong.textContent = '×';
    dong.addEventListener('click', () => o.remove());
    o.appendChild(dong);
    vung.appendChild(o);
    // Thông báo thành công tự ẩn; thông báo lỗi giữ lại cho thầy cô đọc
    if (loai !== 'loi' && !coNutTaiLai) setTimeout(() => { o.classList.add('dang-an'); setTimeout(() => o.remove(), 400); }, 3500);
  }

  // ---------- Giữ vị trí cuộn khi trang phải tải lại (Sửa, Xóa, Nhập file…) ----------

  const KHOA_CUON = 'quanLyCauHoi_viTriCuon';

  // Nhớ cả "câu hỏi đang nằm ở đầu màn hình" (mốc), không chỉ số pixel: sau khi tải lại,
  // thông báo ở đầu trang hoặc thứ tự câu có thể thay đổi, nhưng câu hỏi đó vẫn còn.
  function ghiNhoViTriCuon() {
    let moc = null;
    const cacDong = $$('.bang tbody tr');
    for (let i = 0; i < cacDong.length; i++) {
      const khung = cacDong[i].getBoundingClientRect();
      if (khung.bottom > 0) {
        try { moc = { cauHoi: JSON.parse(cacDong[i].dataset.cau).cauHoi, lech: khung.top }; } catch (loi) { moc = null; }
        break;
      }
    }
    try { sessionStorage.setItem(KHOA_CUON, JSON.stringify({ y: window.scrollY, moc: moc, luc: Date.now() })); } catch (loi) { /* bỏ qua */ }
  }

  function taiLaiGiuViTri() {
    ghiNhoViTriCuon();
    // Không để trình duyệt tự cuộn lại theo số pixel cũ (sẽ đè lên vị trí theo "câu mốc")
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    location.reload();
  }

  // Mọi form gửi đi theo cách thường (không phải fetch) đều nhớ vị trí cuộn trước khi đi
  document.addEventListener('submit', (e) => {
    if (!e.defaultPrevented && (e.target.method || '').toLowerCase() === 'post') ghiNhoViTriCuon();
  });

  (function khoiPhucViTriCuon() {
    let daLuu = null;
    try { daLuu = JSON.parse(sessionStorage.getItem(KHOA_CUON) || 'null'); sessionStorage.removeItem(KHOA_CUON); } catch (loi) { daLuu = null; }
    // Trả lại cho trình duyệt cách cuộn mặc định (cho những lần thầy cô tự bấm F5) – làm sau khi trang tải xong
    const traLaiCuonMacDinh = () => { if ('scrollRestoration' in history) history.scrollRestoration = 'auto'; };
    if (!daLuu || Date.now() - daLuu.luc > 15000) {
      traLaiCuonMacDinh();
      return;
    }

    const dongMoc = daLuu.moc && daLuu.moc.cauHoi
      ? $$('.bang tbody tr').find((d) => {
        try { return JSON.parse(d.dataset.cau).cauHoi === daLuu.moc.cauHoi; } catch (loi) { return false; }
      })
      : null;
    // Đưa "câu mốc" về đúng chỗ cũ trên màn hình (không có câu mốc thì dùng số pixel)
    const datViTri = () => {
      if (dongMoc && dongMoc.isConnected) window.scrollTo(0, window.scrollY + dongMoc.getBoundingClientRect().top - daLuu.moc.lech);
      else window.scrollTo(0, daLuu.y);
    };
    datViTri();
    // Làm lại sau khi trang tải xong hẳn (bố cục phía trên có thể vừa đổi chiều cao).
    // Chrome tự khôi phục vị trí ngay SAU sự kiện load nếu đang ở chế độ "auto",
    // nên chỉ trả lại chế độ "auto" sau khi mọi việc đó đã xong.
    const sauKhiTaiXong = () => {
      datViTri();
      requestAnimationFrame(datViTri);
      setTimeout(traLaiCuonMacDinh, 1000);
    };
    if (document.readyState === 'complete') sauKhiTaiXong();
    else window.addEventListener('load', sauKhiTaiXong, { once: true });
    // Thông báo nằm ở đầu trang, mà trang đang cuộn xuống dưới → hiện thêm ở góc màn hình
    if (daLuu.y > 200) {
      $$('.noi-dung > .thong-bao').forEach((tb) => {
        const p = tb.querySelector('p');
        const loai = tb.classList.contains('that-bai') ? 'loi' : tb.classList.contains('canh-bao') ? 'canh-bao' : 'ok';
        if (p) thongBaoNhanh(loai, p.textContent.trim());
      });
    }
  })();

  // ---------- Chọn nhiều câu ----------

  const chonTatCa = $('#chonTatCa');
  const layOChon = () => $$('.o-chon');          // lấy mới mỗi lần (dòng có thể bị gỡ khỏi bảng)
  const nutChonNhieu = $$('.nut-chon-nhieu');
  const soDaChon = $('.so-da-chon');

  function capNhatChon() {
    const oChon = layOChon();
    const so = oChon.filter((o) => o.checked).length;
    if (soDaChon) soDaChon.textContent = so;
    nutChonNhieu.forEach((n) => { n.disabled = so === 0; });
    oChon.forEach((o) => o.closest('tr').classList.toggle('dang-chon', o.checked));
    if (chonTatCa) {
      chonTatCa.checked = so > 0 && so === oChon.length;
      chonTatCa.indeterminate = so > 0 && so < oChon.length;
    }
  }

  layOChon().forEach((o) => o.addEventListener('change', capNhatChon));
  if (chonTatCa) {
    chonTatCa.addEventListener('change', () => {
      layOChon().forEach((o) => { o.checked = chonTatCa.checked; });
      capNhatChon();
    });
  }

  // ---------- Bật / tắt câu hỏi KHÔNG tải lại trang (gửi ngầm bằng fetch) ----------

  const locTinhTrang = new URLSearchParams(location.search).get('tinh_trang') || '';

  // Sau mỗi lần lưu, file có mã phiên bản mới → cập nhật cho mọi form trên trang
  function capNhatPhienBan(phienBan) {
    $$('input[name="phien_ban"]').forEach((o) => { o.value = phienBan; });
  }

  function capNhatThongKe(tong, tamTat) {
    const o = $('#thongKeDangDung');
    if (!o) return;
    o.textContent = (tong - tamTat) + ' đang dùng';
    if (tamTat > 0) {
      o.append(' · ');
      const a = document.createElement('a');
      a.href = o.dataset.lienKetTamTat;
      a.textContent = tamTat + ' tạm tắt';
      o.appendChild(a);
    }
  }

  // Đổi giao diện một dòng sang "đang tắt" hoặc "đang dùng"
  function capNhatDong(viTri, tat) {
    const dong = $('tr[data-vi-tri="' + viTri + '"]');
    if (!dong) return;
    dong.classList.toggle('tam-tat', tat);

    const cau = JSON.parse(dong.dataset.cau);
    cau.tamTat = tat;
    dong.dataset.cau = JSON.stringify(cau);     // để hộp Sửa tích đúng ô "Tạm tắt"

    const cotLoai = $('.cot-loai', dong);
    const nhan = $('.nhan-tam-tat', cotLoai);
    if (tat && !nhan) {
      const moi = document.createElement('span');
      moi.className = 'nhan-tam-tat';
      moi.title = 'Không dùng khi quay số';
      moi.textContent = '⏸ Đang tắt';
      cotLoai.append(' ', moi);
    } else if (!tat && nhan) {
      nhan.remove();
    }

    const form = $('.form-bat-tat', dong);
    if (form) {
      $('input[name="hanh_dong"]', form).value = tat ? 'bat' : 'tat';
      const nut = $('button', form);
      nut.className = 'nut nut-nho ' + (tat ? 'nut-bat' : 'nut-tat');
      nut.textContent = tat ? '▶ Bật' : '⏸ Tắt';
      nut.title = tat ? 'Bật lại để dùng khi quay' : 'Tạm tắt: giữ lại nhưng không dùng khi quay';
    }

    // Nháy nhẹ cho thầy cô thấy dòng vừa đổi
    dong.classList.remove('vua-doi');
    void dong.offsetWidth;
    dong.classList.add('vua-doi');

    // Đang lọc "Tạm tắt" / "Đang dùng" mà dòng không còn khớp → gỡ khỏi bảng
    if ((locTinhTrang === 'tam_tat' && !tat) || (locTinhTrang === 'dang_dung' && tat)) {
      dong.classList.add('dang-roi');
      setTimeout(() => { dong.remove(); capNhatChon(); }, 450);
    }
  }

  /** Gửi form bật/tắt bằng fetch. hanhDong: 'tat' | 'bat'. Trả về true nếu thành công. */
  async function guiBatTat(form, hanhDong, cacNutKhoa) {
    const duLieu = new FormData(form);
    duLieu.set('hanh_dong', hanhDong);
    cacNutKhoa.forEach((n) => { n.disabled = true; n.classList.add('dang-gui'); });
    try {
      const traLoi = await fetch(form.getAttribute('action') || location.href, {
        method: 'POST',
        body: duLieu,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'fetch', Accept: 'application/json' }
      });
      let kq;
      try {
        kq = await traLoi.json();
      } catch (loi) {
        // Máy chủ không trả JSON (ví dụ phiên làm việc hết hạn)
        thongBaoNhanh('loi', 'Không lưu được: ' + ((await traLoi.text().catch(() => '')) || ('lỗi máy chủ ' + traLoi.status)).slice(0, 200), true);
        return false;
      }
      (kq.thongBao || []).forEach((tb) => thongBaoNhanh(tb.loai, tb.noi_dung, !kq.ok));
      if (!kq.ok) return false;

      capNhatPhienBan(kq.phienBan);
      if (kq.canTaiLai) {
        // Số thứ tự các câu vừa đổi (file được sắp xếp lại) → tải lại để không thao tác nhầm câu
        taiLaiGiuViTri();
        return true;
      }
      Object.keys(kq.trangThai || {}).forEach((viTri) => capNhatDong(viTri, !!kq.trangThai[viTri]));
      capNhatThongKe(kq.tong, kq.tamTat);
      return true;
    } catch (loi) {
      thongBaoNhanh('loi', 'Không kết nối được máy chủ. Kiểm tra mạng rồi thử lại.', true);
      return false;
    } finally {
      cacNutKhoa.forEach((n) => { n.classList.remove('dang-gui'); if (n.isConnected) n.disabled = false; });
    }
  }

  // Nút ⏸ Tắt / ▶ Bật trên từng dòng
  $$('.form-bat-tat').forEach((form) => {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const nut = $('button', form);
      if (nut.disabled) return;                 // đang gửi, bỏ qua bấm liên tiếp
      guiBatTat(form, $('input[name="hanh_dong"]', form).value, [nut]);
    });
  });

  // Thanh "Đã chọn N câu": Tắt / Bật gửi ngầm, Xóa thì hỏi lại rồi gửi bình thường
  const formXoaNhieu = $('#formXoaNhieu');
  if (formXoaNhieu) {
    formXoaNhieu.addEventListener('submit', async (e) => {
      const so = layOChon().filter((o) => o.checked).length;
      const hanhDong = e.submitter ? e.submitter.value : '';
      if (!so) { e.preventDefault(); return; }
      if (hanhDong === 'tat' || hanhDong === 'bat') {
        e.preventDefault();
        const thanhCong = await guiBatTat(formXoaNhieu, hanhDong, nutChonNhieu);
        if (thanhCong) {
          layOChon().forEach((o) => { o.checked = false; });
          capNhatChon();
        } else {
          capNhatChon();
        }
        return;
      }
      if (hanhDong === 'xoa' && !confirm('Xóa ' + so + ' câu hỏi đã chọn?\n\n(Có thể khôi phục lại ở mục Sao lưu.)')) e.preventDefault();
    });
  }
  if (nutChonNhieu.length) capNhatChon();

  // ---------- Đóng thông báo; tự gửi bộ lọc khi đổi lựa chọn ----------

  $$('.dong-thong-bao').forEach((nut) => nut.addEventListener('click', () => nut.parentElement.remove()));
  $$('.bo-loc select').forEach((s) => s.addEventListener('change', () => s.form.submit()));
})();
