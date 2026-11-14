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

  // ---------- Chọn nhiều câu để xóa ----------

  const chonTatCa = $('#chonTatCa');
  const oChon = $$('.o-chon');
  const nutChonNhieu = $$('.nut-chon-nhieu');
  const soDaChon = $('.so-da-chon');

  function capNhatChon() {
    const so = oChon.filter((o) => o.checked).length;
    if (soDaChon) soDaChon.textContent = so;
    nutChonNhieu.forEach((n) => { n.disabled = so === 0; });
    oChon.forEach((o) => o.closest('tr').classList.toggle('dang-chon', o.checked));
    if (chonTatCa) {
      chonTatCa.checked = so > 0 && so === oChon.length;
      chonTatCa.indeterminate = so > 0 && so < oChon.length;
    }
  }

  oChon.forEach((o) => o.addEventListener('change', capNhatChon));
  if (chonTatCa) {
    chonTatCa.addEventListener('change', () => {
      oChon.forEach((o) => { o.checked = chonTatCa.checked; });
      capNhatChon();
    });
  }
  const formXoaNhieu = $('#formXoaNhieu');
  if (formXoaNhieu) {
    formXoaNhieu.addEventListener('submit', (e) => {
      const so = oChon.filter((o) => o.checked).length;
      const hanhDong = e.submitter ? e.submitter.value : '';
      if (!so) { e.preventDefault(); return; }
      // Chỉ hỏi lại khi xóa; tắt / bật thì làm luôn (đổi lại được bất cứ lúc nào)
      if (hanhDong === 'xoa' && !confirm('Xóa ' + so + ' câu hỏi đã chọn?\n\n(Có thể khôi phục lại ở mục Sao lưu.)')) e.preventDefault();
    });
  }
  if (nutChonNhieu.length) capNhatChon();

  // ---------- Đóng thông báo; tự gửi bộ lọc khi đổi lựa chọn ----------

  $$('.dong-thong-bao').forEach((nut) => nut.addEventListener('click', () => nut.parentElement.remove()));
  $$('.bo-loc select').forEach((s) => s.addEventListener('change', () => s.form.submit()));
})();
