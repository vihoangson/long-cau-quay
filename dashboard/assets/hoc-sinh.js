'use strict';
/* =============================================================
   TRANG QUẢN LÝ HỌC SINH – phần chạy trên trình duyệt
   - Mở hộp Thêm / Sửa học sinh và điền sẵn dữ liệu
   - Hỏi lại trước khi xóa, khôi phục, thay thế toàn bộ, đánh số lại
   - Chọn nhiều bạn; đánh dấu Vắng / Có mặt không cần tải lại trang
   ============================================================= */
(function () {
  const $ = (chon, goc) => (goc || document).querySelector(chon);
  const $$ = (chon, goc) => Array.from((goc || document).querySelectorAll(chon));

  const hopThoai = $('#hopThoai');
  const form = $('#formHocSinh');
  const tieuDe = $('#tieuDeHopThoai');
  const loiForm = $('#loiForm');
  const nutThem = $('#nutThem');

  // ---------- Hộp thêm / sửa ----------

  function hienLoi(dsLoi) {
    loiForm.textContent = '';
    if (!dsLoi || !dsLoi.length) { loiForm.hidden = true; return; }
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

  /** duLieu: { vi_tri, so, ten, vang, loi[] } */
  function moHopThoai(duLieu) {
    const d = duLieu || {};
    const dangSua = d.vi_tri !== undefined && d.vi_tri !== null && d.vi_tri !== '';
    form.reset();
    form.elements.vi_tri.value = dangSua ? d.vi_tri : '';
    tieuDe.textContent = dangSua ? 'Sửa học sinh' : 'Thêm học sinh';
    form.elements.so.value = d.so !== undefined && d.so !== null && d.so !== 0 ? d.so : '';
    form.elements.ten.value = d.ten || '';
    form.elements.vang.checked = !!d.vang;
    hienLoi(d.loi);
    if (typeof hopThoai.showModal === 'function') hopThoai.showModal();
    else hopThoai.setAttribute('open', '');
    form.elements.ten.focus();
  }

  function dongHopThoai() {
    if (typeof hopThoai.close === 'function') hopThoai.close();
    else hopThoai.removeAttribute('open');
  }

  if (nutThem) nutThem.addEventListener('click', () => moHopThoai({ so: nutThem.dataset.soGoiY }));

  $$('.nut-sua').forEach((nut) => {
    nut.addEventListener('click', () => {
      const dong = nut.closest('tr');
      const hs = JSON.parse(dong.dataset.hs);
      moHopThoai({ vi_tri: dong.dataset.viTri, so: hs.so, ten: hs.ten, vang: hs.vang });
    });
  });

  $$('[data-dong]', hopThoai).forEach((nut) => nut.addEventListener('click', dongHopThoai));
  hopThoai.addEventListener('click', (e) => { if (e.target === hopThoai) dongHopThoai(); });

  // Có lỗi từ máy chủ sau khi lưu → mở lại hộp với những gì đã gõ
  const formCu = $('#duLieuFormCu');
  if (formCu) {
    try { moHopThoai(JSON.parse(formCu.textContent)); } catch (loi) { /* bỏ qua */ }
  }

  // ---------- Hỏi lại cho chắc ----------

  $$('.form-xoa').forEach((f) => {
    f.addEventListener('submit', (e) => {
      const hs = JSON.parse(f.closest('tr').dataset.hs);
      if (!confirm('Xóa bạn "' + hs.ten + '" (số ' + hs.so + ') khỏi danh sách?\n\n(Có thể khôi phục lại ở mục Sao lưu.)')) e.preventDefault();
    });
  });

  $$('.form-khoi-phuc').forEach((f) => {
    f.addEventListener('submit', (e) => {
      if (!confirm('Khôi phục danh sách học sinh về bản sao lưu này?\n\nBản đang dùng hiện tại cũng sẽ được sao lưu trước.')) e.preventDefault();
    });
  });

  const formDanhSoLai = $('#formDanhSoLai');
  if (formDanhSoLai) {
    formDanhSoLai.addEventListener('submit', (e) => {
      if (!confirm('Đánh số lại 1, 2, 3… theo thứ tự hiện tại?\n\nSố trên bóng của một số bạn sẽ thay đổi.')) e.preventDefault();
    });
  }

  const formNhap = $('#formNhap');
  if (formNhap) {
    formNhap.addEventListener('submit', (e) => {
      const coTep = formNhap.elements.tep.files.length > 0;
      const coChu = formNhap.elements.van_ban.value.trim() !== '';
      if (!coTep && !coChu) {
        e.preventDefault();
        alert('Hãy dán danh sách vào ô, hoặc chọn một file để nhập.');
        return;
      }
      if (coTep && coChu && !confirm('Đã chọn cả file và chữ dán vào. Máy sẽ nhập từ FILE (bỏ qua phần chữ dán). Tiếp tục?')) {
        e.preventDefault();
        return;
      }
      const cheDo = $('input[name="che_do"]:checked', formNhap);
      if (cheDo && cheDo.value === 'thay_the' &&
          !confirm('THAY THẾ TOÀN BỘ danh sách học sinh hiện có?\n\n(Danh sách cũ được sao lưu, có thể khôi phục.)')) {
        e.preventDefault();
      }
    });
  }

  // ---------- Thông báo nhỏ ở góc màn hình ----------

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
    if (loai !== 'loi' && !coNutTaiLai) setTimeout(() => { o.classList.add('dang-an'); setTimeout(() => o.remove(), 400); }, 3500);
  }

  // ---------- Giữ vị trí cuộn khi trang tải lại sau khi lưu ----------

  const KHOA_CUON = 'quanLyHocSinh_viTriCuon';

  function ghiNhoViTriCuon() {
    try { sessionStorage.setItem(KHOA_CUON, JSON.stringify({ y: window.scrollY, luc: Date.now() })); } catch (loi) { /* bỏ qua */ }
  }

  function taiLaiGiuViTri() {
    ghiNhoViTriCuon();
    if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
    location.reload();
  }

  document.addEventListener('submit', (e) => {
    if (!e.defaultPrevented && (e.target.method || '').toLowerCase() === 'post') ghiNhoViTriCuon();
  });

  (function khoiPhucViTriCuon() {
    let daLuu = null;
    try { daLuu = JSON.parse(sessionStorage.getItem(KHOA_CUON) || 'null'); sessionStorage.removeItem(KHOA_CUON); } catch (loi) { daLuu = null; }
    const traLaiCuonMacDinh = () => { if ('scrollRestoration' in history) history.scrollRestoration = 'auto'; };
    if (!daLuu || Date.now() - daLuu.luc > 15000) { traLaiCuonMacDinh(); return; }
    const datViTri = () => window.scrollTo(0, daLuu.y);
    datViTri();
    const sauKhiTaiXong = () => { datViTri(); requestAnimationFrame(datViTri); setTimeout(traLaiCuonMacDinh, 1000); };
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

  // ---------- Chọn nhiều bạn ----------

  const chonTatCa = $('#chonTatCa');
  const layOChon = () => $$('.o-chon');
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

  // ---------- Vắng / Có mặt KHÔNG tải lại trang (gửi ngầm bằng fetch) ----------

  const locTinhTrang = new URLSearchParams(location.search).get('tinh_trang') || '';

  function capNhatPhienBan(phienBan) {
    $$('input[name="phien_ban"]').forEach((o) => { o.value = phienBan; });
  }

  function capNhatThongKe(tong, vang) {
    const dat = (id, so) => { const o = $('#' + id); if (o) o.textContent = so; };
    dat('soTong', tong);
    dat('soCoMat', tong - vang);
    dat('soVang', vang);
  }

  function capNhatDong(viTri, vang) {
    const dong = $('tr[data-vi-tri="' + viTri + '"]');
    if (!dong) return;
    dong.classList.toggle('tam-tat', vang);

    const hs = JSON.parse(dong.dataset.hs);
    hs.vang = vang;
    dong.dataset.hs = JSON.stringify(hs);

    const cot = $('.cot-tinh-trang', dong);
    cot.textContent = '';
    const nhan = document.createElement('span');
    nhan.className = vang ? 'nhan-tam-tat' : 'nhan-co-mat';
    nhan.textContent = vang ? '🏠 Vắng' : '🎱 Có mặt';
    if (vang) nhan.title = 'Không có bóng trong lồng quay';
    cot.appendChild(nhan);

    const f = $('.form-vang', dong);
    if (f) {
      $('input[name="hanh_dong"]', f).value = vang ? 'co_mat' : 'vang';
      const nut = $('button', f);
      nut.className = 'nut nut-nho ' + (vang ? 'nut-bat' : 'nut-tat');
      nut.textContent = vang ? '🎱 Có mặt' : '🏠 Vắng';
      nut.title = vang ? 'Đánh dấu có mặt' : 'Đánh dấu vắng hôm nay';
    }

    dong.classList.remove('vua-doi');
    void dong.offsetWidth;
    dong.classList.add('vua-doi');

    // Đang lọc "Vắng" / "Có mặt" mà dòng không còn khớp → gỡ khỏi bảng
    if ((locTinhTrang === 'vang' && !vang) || (locTinhTrang === 'co_mat' && vang)) {
      dong.classList.add('dang-roi');
      setTimeout(() => { dong.remove(); capNhatChon(); }, 450);
    }
  }

  /** hanhDong: 'vang' | 'co_mat'. Trả về true nếu thành công. */
  async function guiVang(f, hanhDong, cacNutKhoa) {
    const duLieu = new FormData(f);
    duLieu.set('hanh_dong', hanhDong);
    cacNutKhoa.forEach((n) => { n.disabled = true; n.classList.add('dang-gui'); });
    try {
      const traLoi = await fetch(location.pathname, {
        method: 'POST',
        body: duLieu,
        credentials: 'same-origin',
        headers: { 'X-Requested-With': 'fetch', Accept: 'application/json' }
      });
      let kq;
      try {
        kq = await traLoi.clone().json();
      } catch (loi) {
        thongBaoNhanh('loi', 'Không lưu được: ' + ((await traLoi.text().catch(() => '')) || ('lỗi máy chủ ' + traLoi.status)).slice(0, 200), true);
        return false;
      }
      (kq.thongBao || []).forEach((tb) => thongBaoNhanh(tb.loai, tb.noi_dung, !kq.ok));
      if (!kq.ok) return false;

      capNhatPhienBan(kq.phienBan);
      if (kq.canTaiLai) { taiLaiGiuViTri(); return true; }
      Object.keys(kq.trangThai || {}).forEach((viTri) => capNhatDong(viTri, !!kq.trangThai[viTri]));
      capNhatThongKe(kq.tong, kq.vang);
      return true;
    } catch (loi) {
      thongBaoNhanh('loi', 'Không kết nối được máy chủ. Kiểm tra mạng rồi thử lại.', true);
      return false;
    } finally {
      cacNutKhoa.forEach((n) => { n.classList.remove('dang-gui'); if (n.isConnected) n.disabled = false; });
    }
  }

  $$('.form-vang').forEach((f) => {
    f.addEventListener('submit', (e) => {
      e.preventDefault();
      const nut = $('button', f);
      if (nut.disabled) return;
      guiVang(f, $('input[name="hanh_dong"]', f).value, [nut]);
    });
  });

  const formChonNhieu = $('#formChonNhieu');
  if (formChonNhieu) {
    formChonNhieu.addEventListener('submit', async (e) => {
      const so = layOChon().filter((o) => o.checked).length;
      const hanhDong = e.submitter ? e.submitter.value : '';
      if (!so) { e.preventDefault(); return; }
      if (hanhDong === 'vang' || hanhDong === 'co_mat') {
        e.preventDefault();
        if (await guiVang(formChonNhieu, hanhDong, nutChonNhieu)) {
          layOChon().forEach((o) => { o.checked = false; });
        }
        capNhatChon();
        return;
      }
      if (hanhDong === 'xoa' && !confirm('Xóa ' + so + ' học sinh đã chọn?\n\n(Có thể khôi phục lại ở mục Sao lưu.)')) e.preventDefault();
    });
  }
  if (nutChonNhieu.length) capNhatChon();

  // ---------- Đóng thông báo; tự gửi bộ lọc khi đổi lựa chọn ----------

  $$('.dong-thong-bao').forEach((nut) => nut.addEventListener('click', () => nut.parentElement.remove()));
  $$('.bo-loc select').forEach((s) => s.addEventListener('change', () => s.form.submit()));
})();
