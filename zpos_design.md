# ZPOS Plugin Design Document

## 1. Tổng quan
**Tên plugin**: ZPOS  
**Mô tả ngắn**: Một plugin WordPress hỗ trợ quản lý điểm bán hàng (POS), tích hợp tùy chọn đồng bộ với WooCommerce, quản lý sản phẩm, khách hàng, đơn hàng, tồn kho, bảo hành, và báo cáo. Plugin cung cấp giao diện frontend để kiểm tra bảo hành dựa trên số điện thoại hoặc số serial.

**Mục tiêu**:
- Cung cấp giải pháp POS độc lập với cơ sở dữ liệu riêng, có thể đồng bộ với WooCommerce.
- Hỗ trợ quản lý bán hàng, tồn kho, khách hàng, và bảo hành.
- Giao diện trực quan với grid card cho dashboard và báo cáo.
- Cho phép khách hàng kiểm tra bảo hành qua frontend.

**Đối tượng người dùng**:
- **Quản trị viên**: Quản lý bán hàng, sản phẩm, khách hàng, tồn kho, bảo hành, báo cáo.
- **Khách hàng**: Kiểm tra trạng thái bảo hành qua frontend.

## 2. Tính năng chi tiết

### 2.1 Setup Wizard
- **Mục đích**: Hướng dẫn thiết lập plugin lần đầu, bao gồm tùy chọn đồng bộ với WooCommerce.
- **Chức năng**:
  - Bước 1: Chào mừng và giới thiệu plugin.
  - Bước 2: Tùy chọn đồng bộ với WooCommerce (bật/tắt).
    - Nếu bật: Kéo dữ liệu sản phẩm, khách hàng, đơn hàng từ WooCommerce.
    - Nếu tắt: Sử dụng cơ sở dữ liệu riêng của ZPOS.
  - Bước 3: Cấu hình cơ bản (đơn vị tiền tệ, múi giờ, thông tin cửa hàng).
  - Bước 4: Xác nhận và lưu thiết lập.
- **Giao diện**:
  - Wizard với các bước rõ ràng, nút "Tiếp theo" và "Quay lại".
  - Checkbox bật/tắt đồng bộ WooCommerce, các trường nhập thông tin cửa hàng.
  - Sử dụng giao diện WordPress admin với CSS tùy chỉnh.

### 2.2 Cơ sở dữ liệu
- **Mục đích**: Tạo bảng dữ liệu riêng, chỉ đồng bộ với WooCommerce khi được yêu cầu.
- **Bảng dữ liệu**:
  - `zpos_products`: ID, tên, giá, số serial, danh mục, tồn kho.
  - `zpos_customers`: ID, tên, email, số điện thoại, địa chỉ.
  - `zpos_orders`: ID, khách hàng, sản phẩm, tổng tiền, giảm giá, trạng thái.
  - `zpos_inventory`: ID sản phẩm, số lượng, ngày cập nhật.
  - `zpos_warranty`: ID, sản phẩm, khách hàng, số serial, gói bảo hành, ngày bắt đầu, ngày hết hạn.
  - `zpos_settings`: Lưu thiết lập plugin (đồng bộ, tiền tệ, v.v.).
- **Đồng bộ WooCommerce**:
  - Kéo dữ liệu từ `wp_posts`, `wp_postmeta`, `wp_woocommerce_order_items` nếu bật đồng bộ.
  - Đồng bộ định kỳ hoặc thủ công.
- **Lưu ý**:
  - Sử dụng `$wpdb` để tạo và quản lý bảng.
  - Tiền tố `zpos_` để tránh xung đột.
  - Cập nhật schema khi kích hoạt/nâng cấp plugin.

### 2.3 Menu Sidebar Trái (Admin)
- **Danh sách menu**:
  1. Dashboard
  2. POS
  3. Product
  4. Khách hàng
  5. Đơn hàng
  6. Tồn kho
  7. Bảo hành
  8. Báo cáo
  9. Settings
- **Giao diện**:
  - Menu tích hợp vào sidebar admin WordPress.
  - Biểu tượng và tên rõ ràng, hỗ trợ hover/active state.
  - Mỗi menu dẫn đến trang riêng với giao diện tùy chỉnh.

### 2.4 Dashboard
- **Mục đích**: Tổng quan về hoạt động kinh doanh.
- **Chức năng**:
  - Hiển thị: Tổng sản phẩm, đơn hàng, doanh thu, khách hàng.
  - Báo cáo nhanh (biểu đồ doanh thu, sản phẩm bán chạy).
  - Lọc dữ liệu theo thời gian (ngày, tuần, tháng).
- **Giao diện**:
  - Grid card (3-4 cột): Mỗi card hiển thị số liệu (ví dụ: "Sản phẩm: 150").
  - Card có màu sắc nổi bật (xanh, đỏ, vàng) và biểu tượng.
  - Biểu đồ doanh thu sử dụng Chart.js (line/bar).
  - Responsive trên mọi thiết bị.

### 2.5 POS (Trang bán hàng)
- **Mục đích**: Bán hàng trực tiếp tại cửa hàng.
- **Chức năng**:
  - Chọn/tìm kiếm khách hàng (tên, số điện thoại) hoặc tạo mới.
  - Chọn/tìm kiếm sản phẩm (tên, mã sản phẩm, mã vạch).
  - Áp dụng giảm giá (phần trăm/số tiền).
  - Tạo đơn hàng, cập nhật tồn kho.
  - In hóa đơn/gửi email xác nhận.
- **Giao diện**:
  - Phần trái: Danh sách sản phẩm (tìm kiếm, danh mục).
  - Phần phải: Giỏ hàng (sản phẩm, số lượng, giá, giảm giá).
  - Phần dưới: Thông tin khách hàng, nút thanh toán.
  - AJAX cho cập nhật giỏ hàng.
  - Responsive, tối ưu cho tablet.

### 2.6 Product
- **Mục đích**: Quản lý sản phẩm và danh mục.
- **Chức năng**:
  - Thêm/sửa/xóa sản phẩm (tên, giá, số serial, danh mục, mô tả, hình ảnh, tồn kho).
  - Quản lý danh mục.
  - Đồng bộ sản phẩm từ WooCommerce (nếu bật).
- **Giao diện**:
  - Bảng danh sách sản phẩm: Tên, giá, danh mục, tồn kho, hành động.
  - Form thêm/sửa sản phẩm, hỗ trợ upload hình ảnh.

### 2.7 Khách hàng
- **Mục đích**: Quản lý thông tin khách hàng.
- **Chức năng**:
  - Thêm/sửa/xóa khách hàng (tên, email, số điện thoại, địa chỉ).
  - Đồng bộ khách hàng từ WooCommerce.
  - Xem lịch sử mua hàng.
- **Giao diện**:
  - Bảng danh sách khách hàng: Tên, email, số điện thoại, hành động.
  - Form thêm/sửa khách hàng.

### 2.8 Đơn hàng
- **Mục đích**: Quản lý đơn hàng từ POS hoặc WooCommerce.
- **Chức năng**:
  - Xem danh sách đơn hàng (ID, khách hàng, tổng tiền, trạng thái, ngày tạo).
  - Đồng bộ đơn hàng từ WooCommerce.
  - Xem chi tiết đơn hàng (sản phẩm, số lượng, giảm giá).
- **Giao diện**:
  - Bảng danh sách đơn hàng: ID, khách hàng, tổng tiền, trạng thái, hành động.
  - Trang chi tiết đơn hàng.

### 2.9 Tồn kho
- **Mục đích**: Quản lý số lượng tồn kho.
- **Chức năng**:
  - Hiển thị sản phẩm và số lượng tồn kho.
  - Cập nhật tồn kho (thủ công/tự động).
  - Cảnh báo sản phẩm sắp hết hàng (ngưỡng tùy chỉnh).
- **Giao diện**:
  - Bảng danh sách tồn kho: Sản phẩm, số lượng, trạng thái.
  - Form cập nhật số lượng.

### 2.10 Bảo hành
- **Mục đích**: Quản lý gói bảo hành và thông tin bảo hành.
- **Chức năng**:
  - Thêm/sửa/xóa gói bảo hành (6 tháng, 1 năm, v.v.).
  - Ghi nhận bảo hành: Sản phẩm, khách hàng, số serial, ngày bắt đầu, ngày hết hạn.
  - Tìm kiếm bảo hành theo số serial/khách hàng.
- **Giao diện**:
  - Bảng danh sách bảo hành: Sản phẩm, khách hàng, số serial, gói bảo hành, trạng thái.
  - Form thêm/sửa bảo hành.

### 2.11 Báo cáo
- **Mục đích**: Báo cáo doanh thu, sản phẩm bán chạy, lợi nhuận.
- **Chức năng**:
  - Báo cáo doanh thu (ngày, tuần, tháng).
  - Sản phẩm bán chạy (theo số lượng/doanh thu).
  - Báo cáo lợi nhuận (doanh thu trừ chi phí).
  - Lọc dữ liệu theo thời gian.
- **Giao diện**:
  - Grid card: Tổng doanh thu, sản phẩm bán chạy (top 5), lợi nhuận.
  - Biểu đồ (line/bar) sử dụng Chart.js.
  - Responsive và trực quan.

### 2.12 Settings
- **Mục đích**: Cấu hình plugin.
- **Chức năng**:
  - Bật/tắt đồng bộ WooCommerce.
  - Cấu hình tiền tệ, múi giờ, thông tin cửa hàng.
  - Cài đặt ngưỡng cảnh báo tồn kho.
  - Tùy chọn giao diện (màu sắc, bố cục).
  - Chạy lại setup wizard.
- **Giao diện**:
  - Form với các tab: Chung, Đồng bộ, Tồn kho, Giao diện.
  - Nút lưu thay đổi và chạy wizard.

### 2.13 Frontend: Kiểm tra bảo hành
- **Mục đích**: Cho phép khách hàng kiểm tra bảo hành.
- **Chức năng**:
  - Nhập số điện thoại/số serial để tìm kiếm.
  - Hiển thị: Sản phẩm, gói bảo hành, ngày hết hạn, trạng thái.
- **Giao diện**:
  - Form: 2 trường (số điện thoại, số serial), nút "Kiểm tra".
  - Kết quả: Bảng/thẻ với thông tin bảo hành.
  - Responsive, tích hợp với theme.

## 3. Cấu trúc thư mục
```
/zpos
├── /assets
│   ├── /css
│   │   ├── admin.css
│   │   ├── frontend.css
│   ├── /js
│   │   ├── admin.js
│   │   ├── frontend.js
│   ├── /images
│   │   ├── logo.png
├── /includes
│   ├── setup-wizard.php
│   ├── admin-menus.php
│   ├── database.php
│   ├── frontend-warranty.php
│   ├── pos.php
│   ├── products.php
│   ├── customers.php
│   ├── orders.php
│   ├── inventory.php
│   ├── warranty.php
│   ├── reports.php
│   ├── settings.php
├── /templates
│   ├── /admin
│   │   ├── dashboard.php
│   │   ├── pos.php
│   │   ├── products.php
│   │   ├── customers.php
│   │   ├── orders.php
│   │   ├── inventory.php
│   │   ├── warranty.php
│   │   ├── reports.php
│   │   ├── settings.php
│   ├── /frontend
│   │   ├── warranty-check.php
├── zpos.php
├── readme.txt
├── uninstall.php
```

## 4. Giao diện chi tiết

### 4.1 Admin
- **Phong cách**: Tích hợp WP Admin UI.
- **Màu sắc**: Xanh dương (#0073aa), trắng, xám; card có gradient (xanh, đỏ, vàng).
- **Bố cục**:
  - **Dashboard/Báo cáo**: Grid card (3-4 cột), biểu đồ Chart.js.
  - **POS**: 3 phần (trái: sản phẩm, phải: giỏ hàng, dưới: khách hàng).
  - **Danh sách (Product, Khách hàng, v.v.)**: Bảng với tìm kiếm, lọc, phân trang.
  - **Settings**: Form với tab, nút lưu/wizard.
- **Responsive**: Grid card điều chỉnh trên mobile, bảng chuyển thành danh sách dọc.

### 4.2 Frontend (Kiểm tra bảo hành)
- **Phong cách**: Tích hợp với theme WordPress, shortcode `[zpos_warranty_check]`.
- **Màu sắc**: Phù hợp theme (mặc định: trắng, xanh dương, xám).
- **Bố cục**:
  - Form: 2 trường (số điện thoại, số serial), nút "Kiểm tra".
  - Kết quả: Bảng/thẻ với thông tin bảo hành.
- **Responsive**: Tốt trên mọi thiết bị.

## 5. Lưu ý kỹ thuật
- **Tương thích**: WordPress 6.0+, WooCommerce 8.0+.
- **Bảo mật**:
  - Sử dụng nonce cho hành động thêm/sửa/xóa.
  - Vệ sinh dữ liệu (`sanitize_text_field`, `esc_html`).
  - Kiểm tra quyền (`current_user_can`).
- **Hiệu suất**:
  - AJAX cho tìm kiếm, cập nhật giỏ hàng.
  - Cache báo cáo để giảm truy vấn.
- **Tích hợp WooCommerce**:
  - Sử dụng hook/API WooCommerce.
  - Kiểm tra WooCommerce trước khi đồng bộ.
- **Biểu đồ**: Chart.js (line/bar).
- **Shortcode**: `[zpos_warranty_check]`.
- **Đa ngôn ngữ**: Text domain `zpos`, file `.pot`.

## 6. Các bước triển khai
1. **Lên kế hoạch**: Phân chia backend/frontend, ước lượng 4-6 tuần.
2. **Thiết kế giao diện**: Mockup cho dashboard, POS, frontend.
3. **Phát triển**:
   - Bắt đầu với `zpos.php`, setup wizard.
   - Tạo cơ sở dữ liệu, menu admin, frontend.
4. **Kiểm thử**:
   - Kiểm tra đồng bộ WooCommerce.
   - Kiểm tra giao diện trên desktop, tablet, mobile.
   - Kiểm tra bảo mật, hiệu suất.
5. **Triển khai**:
   - Đóng gói plugin, tải lên WordPress.org hoặc phân phối riêng.
   - Cung cấp tài liệu hướng dẫn.