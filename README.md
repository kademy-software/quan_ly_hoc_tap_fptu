# QLHT — Quản lý Học tập

> **Mọi thứ sinh viên cần nhớ, gom vào một nơi.**

QLHT là ứng dụng web giúp sinh viên theo dõi lịch học, điểm số, chuyên cần, deadline và kế hoạch tự học trên một dashboard duy nhất, có đồng bộ lịch học từ FAP và công cụ tạo quiz ôn tập bằng AI.



**Điểm nổi bật**
- Dashboard tuần: lịch học, deadline sắp tới, tiến độ tự học
- Đồng bộ lịch học từ FAP bằng token cá nhân
- Tạo quiz từ nội dung bài học bằng Google Gemini
- Phân quyền admin / sinh viên, dữ liệu tách theo từng tài khoản

## Vấn đề

Lịch học nằm trên FAP, điểm ở một nơi, deadline trong ghi chú, kế hoạch tự học trong đầu. Mỗi lần cần biết "tuần này mình phải làm gì" là phải mở nhiều chỗ và tự ghép lại.

## Giải pháp

QLHT đưa các thông tin đó vào một ứng dụng: lịch tuần, điểm, chuyên cần, việc cần làm và module tự học cùng hiển thị trên trang chủ. Phần việc lặp lại như nhập lịch học hay soạn câu hỏi ôn tập được tự động hóa.

## Tính năng đã triển khai

Danh sách dưới đây được đối chiếu với mã nguồn.

### Dashboard tuần
Trang chủ hiển thị lịch học tuần, deadline sắp tới, tiến độ tự học trong tuần và danh sách môn học của kỳ.



### Thời khóa biểu
Xếp lịch cố định hằng tuần từ danh sách môn học; các buổi đồng bộ từ FAP được đánh dấu riêng bằng nhãn FAP.

*Lịch tuần, buổi học từ FAP có nhãn riêng.*

### Điểm số và chuyên cần
Quản lý điểm thành phần theo môn, tổng kết, và ghi nhận điểm danh.

*Trang quản lý điểm.*

### Việc cần làm và tự học
Danh sách deadline, theo dõi trạng thái, cùng các module tự học. Ngoài ra có trang tiến độ học tập với mục tiêu và phân trang.



### Quiz và Assessment
Tạo quiz thủ công hoặc bằng AI (dán nội dung bài học từ 200 đến 30.000 ký tự, tối đa 30 câu), làm bài, chấm điểm và xem lại kết quả. Assessment lưu từng lượt làm bài.

*Trang tạo quiz.*

### Đồng bộ FAP
Lấy lịch học từ FAP qua token cá nhân (bookmarklet, Automa hoặc cookie) hoặc script CLI `scripts/sync_fap.php`; có thể chọn khoảng ngày cần lấy. Hệ thống lưu thời điểm và kết quả lần đồng bộ gần nhất.

### Các module khác
Kho video bài giảng (tự quét thư mục, thumbnail, trạng thái đã xem), đơn gửi trên FAP, và góc An ninh mạng gồm lab tracker, payload/lệnh, writeup, chứng chỉ.

## Trải nghiệm sử dụng

1. Đăng nhập, mở dashboard để xem lịch học và deadline của tuần.
2. Đồng bộ lịch từ FAP khi có thay đổi, thay vì nhập tay.
3. Ghi điểm và điểm danh sau mỗi môn; thêm deadline vào danh sách việc cần làm.
4. Cuối buổi, dán nội dung bài học để tạo quiz ôn tập và làm thử.

## Công nghệ

| Tầng | Công nghệ (xác minh từ mã nguồn) |
|---|---|
| Backend | PHP thuần, PDO + prepared statements, session |
| Cơ sở dữ liệu | MySQL / MariaDB (utf8mb4) |
| Frontend | HTML5, CSS3, JavaScript thuần, Font Awesome (CDN) |
| AI | Google Gemini API (REST) |
| Môi trường dev | XAMPP / Apache |

## Điểm nhấn kỹ thuật

- **Tách trang và API:** trang PHP render giao diện, `api/*.php` trả JSON cho JavaScript.
- **Cô lập dữ liệu:** truy vấn ràng buộc `student_id`, kể cả `UPDATE` / `DELETE`.
- **Phân quyền đọc từ CSDL** mỗi request nên đổi vai trò có hiệu lực ngay.
- **Bảo mật cơ bản:** `password_hash`, prepared statements, token CSRF ở trang tiến độ.
- **AI không chặn người dùng:** `session_write_close()` trước khi chờ Gemini; có danh sách model dự phòng.
- **Migration nhẹ:** `fapEnsureTable()` tự tạo bảng và thêm cột còn thiếu.

## Lộ trình (dự kiến, chưa triển khai)

- Biểu đồ GPA và thống kê học tập nâng cao
- Nhắc lịch học và deadline, thông báo
- Cải thiện giao diện mobile
- API cho ứng dụng mobile
- Cấu hình bằng `.env`, CSRF cho toàn bộ form

## Cài đặt và sử dụng

Xem hướng dẫn trong [README](../../README.md).
